import os
import subprocess
import sys
import uuid
import requests
from flask import Flask, request, jsonify, send_file
from flask_cors import CORS
from werkzeug.utils import secure_filename
import logging
import traceback

try:
    import vtracer
except ImportError:
    print("You need to install vtracer: python -m pip install vtracer")
    sys.exit(1)

from PIL import Image

# Initialize Flask app
app = Flask(__name__)
CORS(app)  # Enable CORS for cross-domain requests

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(),
        logging.FileHandler('api.log')
    ]
)
logger = logging.getLogger(__name__)

# === CONFIG ===
waifu2x_dir = r"C:\waifu2x-ncnn-vulkan-20230413-win64"  # Update this path
waifu2x_exe = os.path.join(waifu2x_dir, "waifu2x-ncnn-vulkan.exe" if os.name == "nt" else "waifu2x-ncnn-vulkan")
scale = 4
noise = 3
model = "models-upconv_7_anime_style_art_rgb"

# Directory configuration
UPLOAD_FOLDER = os.path.abspath('uploads')
OUTPUT_FOLDER = os.path.abspath('outputs')
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg'}
MAX_FILE_SIZE = 5 * 1024 * 1024  # 5MB

def create_directories():
    """Create upload and output directories if they don't exist"""
    os.makedirs(UPLOAD_FOLDER, exist_ok=True)
    os.makedirs(OUTPUT_FOLDER, exist_ok=True)
    logger.info(f"Directories created: {UPLOAD_FOLDER}, {OUTPUT_FOLDER}")

def allowed_file(filename):
    """Check if file extension is allowed"""
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

def generate_unique_filename(extension):
    """Generate a unique filename with the given extension"""
    return f"{uuid.uuid4().hex}.{extension}"

def download_image_from_url(url, save_path):
    """Download image from URL and save to specified path"""
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
        
        # Check file size
        content_length = response.headers.get('content-length')
        if content_length and int(content_length) > MAX_FILE_SIZE:
            raise ValueError("File too large")
        
        with open(save_path, 'wb') as f:
            for chunk in response.iter_content(chunk_size=8192):
                f.write(chunk)
        
        return True
    except Exception as e:
        logger.error(f"Failed to download image from URL: {str(e)}")
        raise

def run_waifu2x(input_path, output_path):
    """Run Waifu2x upscaling with the same settings as original script"""
    # Check if waifu2x exists
    if not os.path.exists(waifu2x_exe):
        logger.warning(f"Waifu2x not found at {waifu2x_exe}, skipping upscaling")
        # Copy original file instead of upscaling
        import shutil
        shutil.copy2(input_path, output_path)
        return True
    
    cmd = [
        waifu2x_exe,
        "-i", input_path,
        "-o", output_path,
        "-n", str(noise),
        "-s", str(scale),
        "-m", model
    ]
    
    logger.info(f"Running Waifu2x: {' '.join(cmd)}")
    
    try:
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=600)  # Increased from 300 to 600 seconds (10 minutes)
        if result.returncode != 0:
            error_msg = result.stderr or result.stdout or "Unknown Waifu2x error"
            logger.error(f"Waifu2x failed: {error_msg}")
            # Fallback: copy original file
            import shutil
            shutil.copy2(input_path, output_path)
            logger.info("Fallback: copied original file instead of upscaling")
        
        logger.info(f"Waifu2x completed successfully: {output_path}")
        return True
    except subprocess.TimeoutExpired:
        logger.error("Waifu2x process timed out after 10 minutes, using fallback")
        import shutil
        shutil.copy2(input_path, output_path)
        return True
    except Exception as e:
        logger.error(f"Waifu2x execution error: {str(e)}, using fallback")
        import shutil
        shutil.copy2(input_path, output_path)
        return True

def run_vtracer(input_path, output_path):
    """Run VTracer with the same settings as original script"""
    try:
        logger.info(f"Running VTracer: {input_path} -> {output_path}")
        
        vtracer.convert_image_to_svg_py(
            input_path,
            output_path,
            colormode="color",
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
        logger.error(f"VTracer error: {str(e)}")
        logger.error(f"VTracer traceback: {traceback.format_exc()}")
        raise RuntimeError(f"VTracer failed: {str(e)}")

def cleanup_file(file_path):
    """Safely delete a file"""
    try:
        if os.path.exists(file_path):
            os.remove(file_path)
            logger.info(f"Cleaned up file: {file_path}")
    except Exception as e:
        logger.warning(f"Failed to cleanup file {file_path}: {str(e)}")

def optimize_large_image(input_path, max_dimension=2048):
    """Optimize large images before processing to prevent timeouts"""
    try:
        with Image.open(input_path) as img:
            width, height = img.size
            logger.info(f"Original image size: {width}x{height}")
            
            # If image is too large, resize it
            if width > max_dimension or height > max_dimension:
                logger.info(f"Image is large, resizing to max dimension: {max_dimension}")
                
                # Calculate new size maintaining aspect ratio
                if width > height:
                    new_width = max_dimension
                    new_height = int((height * max_dimension) / width)
                else:
                    new_height = max_dimension
                    new_width = int((width * max_dimension) / height)
                
                # Resize image
                resized_img = img.resize((new_width, new_height), Image.Resampling.LANCZOS)
                
                # Save optimized image
                optimized_path = input_path.replace('.', '_optimized.')
                resized_img.save(optimized_path, optimize=True, quality=95)
                
                logger.info(f"Optimized image saved: {optimized_path} ({new_width}x{new_height})")
                return optimized_path
            
            return input_path
    except Exception as e:
        logger.error(f"Image optimization failed: {str(e)}")
        return input_path

@app.route('/vectorize', methods=['POST'])
def vectorize_image():
    """Main endpoint for image vectorization"""
    input_file_path = None
    upscaled_file_path = None
    
    try:
        logger.info(f"Received vectorize request")
        logger.info(f"Content-Type: {request.content_type}")
        logger.info(f"Files: {list(request.files.keys())}")
        
        # Check if request contains file upload or URL
        if 'image' in request.files:
            # Handle file upload
            file = request.files['image']
            logger.info(f"Received file: {file.filename}")
            
            if file.filename == '':
                return jsonify({'success': False, 'error': 'No file selected'}), 400
            
            if not allowed_file(file.filename):
                return jsonify({'success': False, 'error': 'Invalid file type. Only PNG, JPG, JPEG allowed'}), 400
            
            # Save uploaded file
            file_extension = file.filename.rsplit('.', 1)[1].lower()
            input_filename = generate_unique_filename(file_extension)
            input_file_path = os.path.join(UPLOAD_FOLDER, input_filename)
            
            logger.info(f"Saving file to: {input_file_path}")
            file.save(input_file_path)
            
            # Verify file was saved
            if not os.path.exists(input_file_path):
                return jsonify({'success': False, 'error': 'Failed to save uploaded file'}), 500
            
            file_size = os.path.getsize(input_file_path)
            logger.info(f"File saved successfully: {input_file_path} ({file_size} bytes)")
            
        elif request.is_json and 'image_url' in request.json:
            # Handle URL download
            image_url = request.json['image_url']
            logger.info(f"Received URL: {image_url}")
            
            if not image_url:
                return jsonify({'success': False, 'error': 'No image URL provided'}), 400
            
            # Determine file extension from URL or default to jpg
            url_lower = image_url.lower()
            if url_lower.endswith('.png'):
                file_extension = 'png'
            elif url_lower.endswith(('.jpg', '.jpeg')):
                file_extension = 'jpg'
            else:
                file_extension = 'jpg'  # Default
            
            input_filename = generate_unique_filename(file_extension)
            input_file_path = os.path.join(UPLOAD_FOLDER, input_filename)
            
            # Download image from URL
            download_image_from_url(image_url, input_file_path)
            logger.info(f"Image downloaded from URL: {input_file_path}")
            
        else:
            logger.warning(f"Invalid request format")
            return jsonify({'success': False, 'error': 'No image file or URL provided'}), 400
        
        # Validate that the file exists and is a valid image
        if not os.path.exists(input_file_path):
            return jsonify({'success': False, 'error': 'Failed to save input file'}), 500

        try:
            # Verify it's a valid image
            with Image.open(input_file_path) as img:
                width, height = img.size
                logger.info(f"Image dimensions: {width}x{height}")
                img.verify()
        except Exception as e:
            cleanup_file(input_file_path)
            return jsonify({'success': False, 'error': f'Invalid image file: {str(e)}'}), 400

        # Optimize large images before processing
        input_file_path = optimize_large_image(input_file_path)
        
        # Generate paths for processing
        base_name = os.path.splitext(input_filename)[0]
        upscaled_filename = f"{base_name}_waifu2x.png"
        upscaled_file_path = os.path.join(UPLOAD_FOLDER, upscaled_filename)
        
        svg_filename = f"{base_name}.svg"
        svg_file_path = os.path.join(OUTPUT_FOLDER, svg_filename)
        
        logger.info(f"Processing paths:")
        logger.info(f"  Input: {input_file_path}")
        logger.info(f"  Upscaled: {upscaled_file_path}")
        logger.info(f"  SVG: {svg_file_path}")
        
        # Step 1: Run Waifu2x upscaling
        logger.info("Starting Waifu2x upscaling...")
        run_waifu2x(input_file_path, upscaled_file_path)
        
        # Verify upscaled file exists
        if not os.path.exists(upscaled_file_path):
            cleanup_file(input_file_path)
            return jsonify({'success': False, 'error': 'Upscaling failed - no output file'}), 500
        
        # Step 2: Run VTracer vectorization
        logger.info("Starting VTracer vectorization...")
        run_vtracer(upscaled_file_path, svg_file_path)
        
        # Verify SVG was created
        if not os.path.exists(svg_file_path):
            cleanup_file(input_file_path)
            cleanup_file(upscaled_file_path)
            return jsonify({'success': False, 'error': 'Vectorization failed - no SVG output'}), 500
        
        svg_size = os.path.getsize(svg_file_path)
        logger.info(f"SVG created successfully: {svg_file_path} ({svg_size} bytes)")
        
        # Cleanup temporary files
        cleanup_file(input_file_path)
        cleanup_file(upscaled_file_path)
        
        logger.info(f"Vectorization completed successfully: {svg_filename}")
        
        return jsonify({
            'success': True,
            'svg_filename': svg_filename,
            'download_url': f'/download/{svg_filename}'
        })
        
    except Exception as e:
        # Cleanup on error
        cleanup_file(input_file_path)
        cleanup_file(upscaled_file_path)
        
        error_message = str(e)
        logger.error(f"Vectorization failed: {error_message}")
        logger.error(f"Traceback: {traceback.format_exc()}")
        
        return jsonify({
            'success': False,
            'error': error_message
        }), 500

@app.route('/download/<filename>')
def download_file(filename):
    """Download endpoint for SVG files"""
    try:
        file_path = os.path.join(OUTPUT_FOLDER, secure_filename(filename))
        logger.info(f"Download request for: {file_path}")
        
        if not os.path.exists(file_path):
            logger.warning(f"File not found: {file_path}")
            return jsonify({'error': 'File not found'}), 404
        
        return send_file(file_path, as_attachment=True)
    except Exception as e:
        logger.error(f"Download error: {str(e)}")
        return jsonify({'error': 'Download failed'}), 500

@app.route('/health')
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'waifu2x_available': os.path.exists(waifu2x_exe),
        'directories': {
            'uploads': os.path.exists(UPLOAD_FOLDER),
            'outputs': os.path.exists(OUTPUT_FOLDER)
        }
    })

@app.errorhandler(413)
def too_large(e):
    return jsonify({'success': False, 'error': 'File too large'}), 413

if __name__ == '__main__':
    # Create necessary directories
    create_directories()
    
    # Check if Waifu2x executable exists
    if not os.path.exists(waifu2x_exe):
        logger.warning(f"Waifu2x executable not found at: {waifu2x_exe}")
        logger.warning("API will work but without upscaling (fallback mode)")
    
    # Run the Flask app
    logger.info("Starting Flask API server...")
    app.run(host='0.0.0.0', port=5000, debug=False)
