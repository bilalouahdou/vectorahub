from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from PIL import Image
import httpx
import os
import logging
import io

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

def is_black_and_white(image_bytes: bytes) -> bool:
    """
    Strictly checks if an image is purely black and white (or grayscale).
    Returns True only if all pixels are either very dark or very light.
    """
    try:
        image = Image.open(io.BytesIO(image_bytes)).convert("L") # Convert to grayscale
        
        # Define strict thresholds for "black" and "white"
        black_threshold = 20  # Pixels darker than this are considered black
        white_threshold = 235 # Pixels lighter than this are considered white
        
        # Iterate through all pixels
        for pixel_value in image.getdata():
            # If a pixel is NOT black AND NOT white, then it's not purely B&W
            if not (pixel_value <= black_threshold or pixel_value >= white_threshold):
                return False # Found a pixel that is not strictly black or white
        
        return True # All pixels are strictly black or white
    except Exception as e:
        logger.error(f"Error checking if image is black and white: {e}")
        return False # Default to false on error, treat as regular image

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
    
    is_black_image = is_black_and_white(content)
    logger.info(f"Image '{image.filename}' detected as strictly black and white: {is_black_image}")

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
                
                # Return SVG content directly for PHP to save and serve
                if 'svg_content' not in result:
                    raise HTTPException(
                        status_code=500,
                        detail="Salad API did not return SVG content."
                    )

                return {
                    "success": True,
                    "message": "Image vectorized successfully",
                    "data": {
                        "svg_content": result['svg_content'],
                        "svg_filename": f"{os.path.splitext(image.filename)[0]}.svg" # Ensure .svg extension
                    },
                    "is_black_image": is_black_image, # Pass this strict B&W flag back to PHP
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
