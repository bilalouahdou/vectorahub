from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.middleware.cors import CORSMiddleware
import httpx
import os
import logging

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Initialize FastAPI with root_path for reverse proxy
app = FastAPI(
    title="VectraHub API",
    description="AI Image Vectorization Service",
    version="1.0.0",
    root_path="/api"
)

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["https://vectrahub.online", "http://localhost:8080"],
    allow_credentials=True,
    allow_methods=["GET", "POST", "PUT", "DELETE"],
    allow_headers=["*"],
)

# Get Salad API key from environment
SALAD_KEY = os.getenv("SALAD_KEY")
if not SALAD_KEY:
    logger.warning("SALAD_KEY environment variable not set")

# Salad API configuration
SALAD_API_URL = "https://api.salad.com/api/v1/vectorize"

@app.get("/")
async def root():
    """Health check endpoint"""
    return {
        "message": "VectraHub API is running",
        "status": "healthy",
        "salad_key_configured": bool(SALAD_KEY)
    }

@app.post("/vectorize")
async def vectorize_image(image: UploadFile = File(...)):
    """
    Vectorize an uploaded image using Salad's vectorization service
    """
    if not SALAD_KEY:
        raise HTTPException(
            status_code=500, 
            detail="SALAD_KEY not configured. Please set the environment variable."
        )
    
    # Validate file type
    if not image.content_type or not image.content_type.startswith('image/'):
        raise HTTPException(
            status_code=400,
            detail="Invalid file type. Please upload an image file."
        )
    
    # Check file size (limit to 10MB)
    content = await image.read()
    if len(content) > 10 * 1024 * 1024:
        raise HTTPException(
            status_code=400,
            detail="File too large. Maximum size is 10MB."
        )
    
    try:
        # Prepare the request to Salad API
        files = {
            'image': (image.filename, content, image.content_type)
        }
        
        headers = {
            'Authorization': f'Bearer {SALAD_KEY}',
            'User-Agent': 'VectraHub/1.0.0'
        }
        
        # Make request to Salad vectorization service
        async with httpx.AsyncClient(timeout=30.0) as client:
            logger.info(f"Sending vectorization request for file: {image.filename}")
            
            response = await client.post(
                SALAD_API_URL,
                files=files,
                headers=headers
            )
            
            if response.status_code == 200:
                result = response.json()
                logger.info("Vectorization successful")
                return {
                    "status": "success",
                    "message": "Image vectorized successfully",
                    "data": result,
                    "filename": image.filename
                }
            else:
                logger.error(f"Salad API error: {response.status_code} - {response.text}")
                raise HTTPException(
                    status_code=response.status_code,
                    detail=f"Vectorization service error: {response.text}"
                )
                
    except httpx.TimeoutException:
        logger.error("Timeout while calling Salad API")
        raise HTTPException(
            status_code=504,
            detail="Vectorization service timeout. Please try again."
        )
    except httpx.RequestError as e:
        logger.error(f"Request error: {str(e)}")
        raise HTTPException(
            status_code=503,
            detail="Unable to connect to vectorization service"
        )
    except Exception as e:
        logger.error(f"Unexpected error: {str(e)}")
        raise HTTPException(
            status_code=500,
            detail="Internal server error during vectorization"
        )

@app.get("/health")
async def health_check():
    """Detailed health check"""
    return {
        "status": "healthy",
        "service": "VectraHub API",
        "version": "1.0.0",
        "salad_integration": "configured" if SALAD_KEY else "missing_key"
    }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
