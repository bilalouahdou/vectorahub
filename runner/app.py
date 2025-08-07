import os
import sys
import uuid
import time
import subprocess
import requests
import shutil
import logging
from pathlib import Path
from typing import Optional, Dict, Any
from datetime import datetime

from fastapi import FastAPI, HTTPException, Depends, Header
from fastapi.responses import JSONResponse
from pydantic import BaseModel, HttpUrl
from dotenv import load_dotenv

# Add parent directory to path to import existing vectorization modules
sys.path.append(os.path.join(os.path.dirname(__file__), '..', 'python'))

try:
    import vtracer
    from PIL import Image
except ImportError as e:
    print(f"Missing required dependencies: {e}")
    print("Install with: pip install vtracer Pillow")
    sys.exit(1)

# Load environment variables
load_dotenv()

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Import idle guard
from idle_guard import get_idle_guard

# Initialize FastAPI app
app = FastAPI(
    title="GPU Vectorization Runner",
    description="Production GPU runner for image vectorization",
    version="1.0.0"
)

# Pydantic models
class VectorizeRequest(BaseModel):
    input_url: HttpUrl
    mode: str  # 'bw' or 'color'
    filename: Optional[str] = None

class VectorizeResponse(BaseModel):
    job_id: str
    status: str
    output: Dict[str, str]
    duration_ms: int

# Configuration
RUNNER_PORT = int(os.getenv('RUNNER_PORT', '8787'))
WORK_DIR = Path(os.getenv('WORK_DIR', 'C:/vh_runner/tmp'))
VECTORIZER_CMD = os.getenv('VECTORIZER_CMD', 'python ../python/trace_with_tolerance_pil.py --input {in} --output {out} --mode {mode}')
RESULT_UPLOAD_MODE = os.getenv('RESULT_UPLOAD_MODE', 'local_path')
RESULT_UPLOAD_SIGNED_PUT_URL = os.getenv('RESULT_UPLOAD_SIGNED_PUT_URL', '')
RESULT_NAMING = os.getenv('RESULT_NAMING', '{uuid}.svg')
RUNNER_SHARED_TOKEN = os.getenv('RUNNER_SHARED_TOKEN', 'change-me-to-strong-secret-key')

# Waifu2x configuration
WAIFU2X_DIR = os.getenv('WAIFU2X_DIR', 'C:/waifu2x-ncnn-vulkan-20230413-win64')
WAIFU2X_SCALE = int(os.getenv('WAIFU2X_SCALE', '4'))
WAIFU2X_NOISE = int(os.getenv('WAIFU2X_NOISE', '3'))
WAIFU2X_MODEL = os.getenv('WAIFU2X_MODEL', 'models-upconv_7_anime_style_art_rgb')

# Create work directory
WORK_DIR.mkdir(parents=True, exist_ok=True)
logger.info(f"Work directory: {WORK_DIR}")

def verify_token(authorization: str = Header(None)) -> bool:
    """Verify the authorization token"""
    if not authorization:
        raise HTTPException(status_code=401, detail="Missing authorization header")
    
    if not authorization.startswith('Bearer '):
        raise HTTPException(status_code=401, detail="Invalid authorization format")
    
    token = authorization[7:]  # Remove 'Bearer ' prefix
    if token != RUNNER_SHARED_TOKEN:
        raise HTTPException(status_code=401, detail="Invalid token")
    
    return True

def download_image(url: str, save_path: Path) -> bool:
    """Download image from URL"""
    try:
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        }
        response = requests.get(url, headers=headers, timeout=30, stream=True)
        response.raise_for_status()
        
        # Check content type
        content_type = response.headers.get('content-type', '').lower()
        if not any(img_type in content_type for img_type in ['image/jpeg', 'image/png', 'image/jpg']):
            raise ValueError(f"Invalid content type: {content_type}")
        
        with open(save_path, 'wb') as f:
            for chunk in response.iter_content(chunk_size=8192):
                f.write(chunk)
        
        return True
    except Exception as e:
        logger.error(f"Failed to download image: {e}")
        raise

def run_waifu2x(input_path: Path, output_path: Path) -> bool:
    """Run Waifu2x upscaling"""
    waifu2x_exe = Path(WAIFU2X_DIR) / "waifu2x-ncnn-vulkan.exe"
    
    if not waifu2x_exe.exists():
        logger.warning(f"Waifu2x not found at {waifu2x_exe}, copying original file")
        shutil.copy2(input_path, output_path)
        return True
    
    cmd = [
        str(waifu2x_exe),
        "-i", str(input_path),
        "-o", str(output_path),
        "-n", str(WAIFU2X_NOISE),
        "-s", str(WAIFU2X_SCALE),
        "-m", WAIFU2X_MODEL
    ]
    
    logger.info(f"Running Waifu2x: {' '.join(cmd)}")
    
    try:
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=600)
        if result.returncode != 0:
            error_msg = result.stderr or result.stdout or "Unknown Waifu2x error"
            logger.error(f"Waifu2x failed: {error_msg}")
            # Fallback: copy original file
            shutil.copy2(input_path, output_path)
            logger.info("Fallback: copied original file instead of upscaling")
        
        return True
    except subprocess.TimeoutExpired:
        logger.error("Waifu2x process timed out, using fallback")
        shutil.copy2(input_path, output_path)
        return True
    except Exception as e:
        logger.error(f"Waifu2x execution error: {e}, using fallback")
        shutil.copy2(input_path, output_path)
        return True

def run_vtracer(input_path: Path, output_path: Path, mode: str = 'color') -> bool:
    """Run VTracer vectorization"""
    try:
        logger.info(f"Running VTracer: {input_path} -> {output_path} (mode: {mode})")
        
        # Determine color mode based on input mode
        colormode = "bw" if mode == "bw" else "color"
        
        vtracer.convert_image_to_svg_py(
            str(input_path),
            str(output_path),
            colormode=colormode,
            mode="spline",
            filter_speckle=12,
            color_precision=8,
            layer_difference=16,
            corner_threshold=55,
            length_threshold=3.0,
            max_iterations=15,
            splice_threshold=55,
            path_precision=3
        )
        
        logger.info(f"VTracer completed successfully: {output_path}")
        return True
    except Exception as e:
        logger.error(f"VTracer error: {e}")
        raise RuntimeError(f"VTracer failed: {e}")

def cleanup_file(file_path: Path):
    """Safely delete a file"""
    try:
        if file_path.exists():
            file_path.unlink()
            logger.info(f"Cleaned up file: {file_path}")
    except Exception as e:
        logger.warning(f"Failed to cleanup file {file_path}: {e}")

@app.on_event("startup")
async def startup_event():
    """Initialize the runner on startup"""
    logger.info("Starting GPU Vectorization Runner...")
    logger.info(f"Runner will auto-shutdown after {os.getenv('IDLE_EXIT_MIN', '8')} minutes of inactivity")
    
    # Start idle monitoring
    idle_guard = get_idle_guard()
    idle_guard.start_monitoring()

@app.on_event("shutdown")
async def shutdown_event():
    """Cleanup on shutdown"""
    logger.info("Shutting down GPU Vectorization Runner...")
    idle_guard = get_idle_guard()
    idle_guard.stop_monitoring()

@app.post("/run", response_model=VectorizeResponse)
async def run_vectorization(
    request: VectorizeRequest,
    _: bool = Depends(verify_token)
):
    """Main vectorization endpoint"""
    start_time = time.time()
    job_id = str(uuid.uuid4())
    
    # Update idle guard
    idle_guard = get_idle_guard()
    idle_guard.update_request_time()
    
    logger.info(f"Starting vectorization job {job_id}")
    logger.info(f"Input URL: {request.input_url}")
    logger.info(f"Mode: {request.mode}")
    
    # Validate mode
    if request.mode not in ['bw', 'color']:
        raise HTTPException(status_code=400, detail="Invalid mode. Must be 'bw' or 'color'")
    
    # Create job directory
    job_dir = WORK_DIR / job_id
    job_dir.mkdir(parents=True, exist_ok=True)
    
    input_file = None
    upscaled_file = None
    output_file = None
    
    try:
        # Download input image
        input_filename = request.filename or f"input_{job_id}.png"
        input_file = job_dir / input_filename
        download_image(str(request.input_url), input_file)
        
        # Verify it's a valid image
        with Image.open(input_file) as img:
            logger.info(f"Image dimensions: {img.size}")
        
        # Generate output paths
        upscaled_file = job_dir / f"upscaled_{job_id}.png"
        output_filename = RESULT_NAMING.format(uuid=job_id)
        output_file = job_dir / output_filename
        
        # Step 1: Run Waifu2x upscaling
        logger.info("Starting Waifu2x upscaling...")
        run_waifu2x(input_file, upscaled_file)
        
        # Step 2: Run VTracer vectorization
        logger.info("Starting VTracer vectorization...")
        run_vtracer(upscaled_file, output_file, request.mode)
        
        # Verify output was created
        if not output_file.exists():
            raise RuntimeError("Vectorization failed - no SVG output")
        
        # Calculate duration
        duration_ms = int((time.time() - start_time) * 1000)
        
        # Prepare response
        if RESULT_UPLOAD_MODE == 'signed_put' and RESULT_UPLOAD_SIGNED_PUT_URL:
            # Upload to signed URL
            with open(output_file, 'rb') as f:
                upload_response = requests.put(RESULT_UPLOAD_SIGNED_PUT_URL, data=f)
                upload_response.raise_for_status()
            
            output_info = {"uploaded_url": RESULT_UPLOAD_SIGNED_PUT_URL}
        else:
            # Return local path
            output_info = {"local_path": str(output_file)}
        
        logger.info(f"Vectorization completed successfully in {duration_ms}ms")
        
        return VectorizeResponse(
            job_id=job_id,
            status="done",
            output=output_info,
            duration_ms=duration_ms
        )
        
    except Exception as e:
        # Cleanup on error
        cleanup_file(input_file)
        cleanup_file(upscaled_file)
        cleanup_file(output_file)
        
        error_msg = str(e)
        logger.error(f"Vectorization failed: {error_msg}")
        
        raise HTTPException(status_code=500, detail=error_msg)

@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "status": "healthy",
        "timestamp": datetime.now().isoformat(),
        "waifu2x_available": Path(WAIFU2X_DIR).exists(),
        "work_dir": str(WORK_DIR),
        "work_dir_exists": WORK_DIR.exists()
    }

@app.get("/")
async def root():
    """Root endpoint"""
    return {
        "message": "GPU Vectorization Runner",
        "version": "1.0.0",
        "endpoints": {
            "health": "/health",
            "vectorize": "/run"
        }
    }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(
        "app:app",
        host="127.0.0.1",
        port=RUNNER_PORT,
        log_level="info"
    ) 