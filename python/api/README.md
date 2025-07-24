# VectorizeAI Python API

This Flask-based API service provides image vectorization using Waifu2x upscaling and VTracer conversion.

## Installation

1. **Install Python dependencies:**
   \`\`\`bash
   cd python/api
   pip install -r requirements.txt
   \`\`\`

2. **Configure Waifu2x path:**
   Edit `app.py` and update the `waifu2x_dir` variable to point to your Waifu2x installation:
   ```python
   waifu2x_dir = r"C:\path\to\your\waifu2x-ncnn-vulkan"
   \`\`\`

3. **Start the API server:**
   \`\`\`bash
   # Linux/Mac
   ./start_api.sh
   
   # Windows
   start_api.bat
   
   # Or manually
   python run.py
   \`\`\`

## API Endpoints

### POST /vectorize
Vectorize an image using Waifu2x + VTracer.

**File Upload:**
\`\`\`bash
curl -X POST http://localhost:5000/vectorize \
  -F "image=@/path/to/image.png"
\`\`\`

**URL Input:**
\`\`\`bash
curl -X POST http://localhost:5000/vectorize \
  -H "Content-Type: application/json" \
  -d '{"image_url": "https://example.com/image.png"}'
\`\`\`

**Response:**
\`\`\`json
{
  "success": true,
  "svg_filename": "abc123.svg",
  "download_url": "/download/abc123.svg"
}
\`\`\`

### GET /download/<filename>
Download a generated SVG file.

### GET /health
Check API health and dependencies.

## Configuration

- **Host/Port:** Set `FLASK_HOST` and `FLASK_PORT` environment variables
- **Debug Mode:** Set `FLASK_DEBUG=true` for development
- **File Limits:** Modify `MAX_FILE_SIZE` in `app.py`
- **Timeouts:** Adjust timeout values for long-running processes

## Integration with PHP

The API integrates with your PHP application through the `PythonApiClient` class:

\`\`\`php
$client = new PythonApiClient('http://localhost:5000');
$result = $client->vectorizeFile('/path/to/image.png');
\`\`\`

## Troubleshooting

1. **Waifu2x not found:** Update the `waifu2x_dir` path in `app.py`
2. **Port conflicts:** Change the port in `run.py` or set `FLASK_PORT` environment variable
3. **Memory issues:** Reduce image size or increase system memory
4. **Timeout errors:** Increase timeout values for large images

## Production Deployment

For production use:
1. Use a WSGI server like Gunicorn
2. Set up proper logging
3. Configure reverse proxy (nginx)
4. Set up monitoring and health checks
