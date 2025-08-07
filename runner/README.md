# GPU Vectorization Runner

A production-grade FastAPI service that runs your existing vectorization script on a local GPU PC and connects to your PHP website via Cloudflare Tunnel.

## 🚀 Quick Start

### 1. Setup Environment
```bash
cd runner
copy env.example .env
# Edit .env with your configuration
```

### 2. Start the Runner
```bash
run.bat
```

### 3. Test Locally
```bash
curl http://127.0.0.1:8787/health
```

### 4. Expose via Cloudflare Tunnel
```bash
cloudflared tunnel create gpu-runner
cloudflared tunnel run gpu-runner
```

## 📁 File Structure

```
runner/
├── app.py              # Main FastAPI application
├── idle_guard.py       # Auto-shutdown functionality
├── requirements.txt    # Python dependencies
├── env.example         # Environment configuration template
├── run.bat            # Windows startup script
├── test_runner.py     # Test suite
├── examples/
│   └── request.json   # Example API request
└── README.md          # This file
```

## ⚙️ Configuration

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `RUNNER_PORT` | `8787` | Port for the FastAPI server |
| `IDLE_EXIT_MIN` | `8` | Minutes of inactivity before auto-shutdown |
| `WORK_DIR` | `C:/vh_runner/tmp` | Temporary working directory |
| `RUNNER_SHARED_TOKEN` | `change-me-to-strong-secret-key` | **CHANGE THIS!** Authentication token |
| `WAIFU2X_DIR` | `C:/waifu2x-ncnn-vulkan-20230413-win64` | Path to Waifu2x installation |
| `WAIFU2X_SCALE` | `4` | Upscaling factor |
| `WAIFU2X_NOISE` | `3` | Noise reduction level |
| `WAIFU2X_MODEL` | `models-upconv_7_anime_style_art_rgb` | Waifu2x model |

### Security Token

**IMPORTANT**: Change the `RUNNER_SHARED_TOKEN` in your `.env` file to a strong, random string. This token is used to authenticate requests between your website and the GPU runner.

## 🔌 API Endpoints

### Health Check
```bash
GET /health
```
Returns the status of the runner and its dependencies.

### Vectorization
```bash
POST /run
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "input_url": "https://example.com/image.png",
  "mode": "color",  // or "bw"
  "filename": "optional_filename.png"
}
```

Returns:
```json
{
  "job_id": "uuid-string",
  "status": "done",
  "output": {
    "local_path": "C:/vh_runner/tmp/job-id/output.svg"
  },
  "duration_ms": 15000
}
```

## 🧪 Testing

### Run Test Suite
```bash
python test_runner.py
```

### Manual Testing
```bash
# Test health
curl http://127.0.0.1:8787/health

# Test vectorization
curl -X POST http://127.0.0.1:8787/run \
  -H "Authorization: Bearer your-token" \
  -H "Content-Type: application/json" \
  -d @examples/request.json
```

## 🔒 Security Features

- **Bearer Token Authentication**: All requests require a valid token
- **Localhost Binding**: Server only binds to 127.0.0.1
- **Input Validation**: Validates URLs, file types, and modes
- **Secure Exposure**: Use Cloudflare Tunnel for HTTPS access

## 🔄 Auto-Shutdown

The runner automatically shuts down after `IDLE_EXIT_MIN` minutes of inactivity to save resources. This is perfect for:

- Personal GPU PCs that aren't always running
- Cost optimization for cloud GPU instances
- Resource management

## 🌐 Cloudflare Tunnel Setup

1. **Install cloudflared**
   ```bash
   # Download from Cloudflare website
   ```

2. **Login and create tunnel**
   ```bash
   cloudflared tunnel login
   cloudflared tunnel create gpu-runner
   ```

3. **Configure tunnel** (`tunnel-config.yml`)
   ```yaml
   tunnel: YOUR_TUNNEL_ID
   credentials-file: C:\Users\USER\.cloudflared\TUNNEL_ID.json
   
   ingress:
     - hostname: gpu-runner.your-domain.com
       service: http://127.0.0.1:8787
     - service: http_status:404
   ```

4. **Start tunnel**
   ```bash
   cloudflared tunnel run gpu-runner
   ```

5. **Configure DNS** in Cloudflare dashboard

## 🔗 Website Integration

### PHP Configuration
Add these environment variables to your PHP deployment:
```env
GPU_RUNNER_URL=https://gpu-runner.your-domain.com
GPU_RUNNER_TOKEN=your-super-secret-token-here
```

### JavaScript Usage
```javascript
fetch('/php/api/gpu_vectorize.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        input_url: imageUrl,
        mode: 'color' // or 'bw'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Vectorization completed:', data.data);
    } else {
        console.error('Vectorization failed:', data.error);
    }
});
```

## 🚨 Troubleshooting

### Common Issues

1. **Runner won't start**
   - Check Python installation: `python --version`
   - Install dependencies: `pip install -r requirements.txt`
   - Check port availability: `netstat -an | findstr 8787`

2. **Vectorization fails**
   - Verify Waifu2x installation
   - Check GPU drivers: `nvidia-smi`
   - Review logs in console output

3. **Authentication errors**
   - Verify token matches between runner and website
   - Check Authorization header format

4. **Network issues**
   - Test local connection first
   - Verify Cloudflare Tunnel is running
   - Check DNS configuration

### Logs

- **Runner logs**: Check console output from `run.bat`
- **Tunnel logs**: Check Cloudflare dashboard
- **Application logs**: Check `api.log` in the runner directory

## 🔄 Cloud Deployment

The same runner code works for cloud GPU instances:

1. **AWS EC2 with GPU**
   - Use g4dn.xlarge or similar
   - Install NVIDIA drivers and CUDA
   - Run the same setup

2. **Google Cloud GPU**
   - Use n1-standard-4 with Tesla T4
   - Install GPU drivers
   - Same configuration

3. **Azure NC-series**
   - Use NC6s_v3 or similar
   - Install GPU drivers
   - Same setup process

## 📞 Support

For issues:
1. Check the troubleshooting section
2. Review logs and error messages
3. Test each component individually
4. Verify all configuration values

## 🎯 Success Indicators

You'll know everything is working when:
- ✅ Runner starts without errors
- ✅ Health check returns `{"status": "healthy"}`
- ✅ Tunnel connects and shows active status
- ✅ Website can successfully vectorize images
- ✅ SVG files are generated and downloadable
- ✅ Runner auto-shuts down after idle timeout 