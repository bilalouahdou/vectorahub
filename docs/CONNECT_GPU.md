# GPU Vectorization Runner Setup Guide

This guide will help you set up a production-grade GPU vectorization runner that connects your local GPU PC to your PHP website.

## 🎯 Overview

The GPU runner is a FastAPI service that:
- Runs on your local GPU PC
- Processes vectorization requests from your website
- Auto-shuts down after idle timeout to save resources
- Can be exposed securely via Cloudflare Tunnel

## 📋 Prerequisites

### 1. Local GPU PC Requirements
- **NVIDIA GPU** with CUDA support
- **Python 3.8+** installed
- **Waifu2x-ncnn-vulkan** (already configured in your setup)
- **Windows 10/11** (tested on Windows)

### 2. Software Installation
- **Cloudflare Tunnel** (cloudflared) for secure exposure
- **Git** (optional, for version control)

## 🚀 Step-by-Step Setup

### Step 1: Install NVIDIA + CUDA (if not already done)

1. **Install NVIDIA Drivers**
   ```bash
   # Download from: https://www.nvidia.com/Download/index.aspx
   # Install the latest drivers for your GPU
   ```

2. **Verify CUDA Installation**
   ```bash
   nvidia-smi
   # Should show your GPU and CUDA version
   ```

3. **Verify Waifu2x Setup**
   - Ensure `C:\waifu2x-ncnn-vulkan-20230413-win64` exists
   - Test that `waifu2x-ncnn-vulkan.exe` runs

### Step 2: Set Up the GPU Runner

1. **Navigate to Runner Directory**
   ```bash
   cd runner
   ```

2. **Create Environment File**
   ```bash
   copy env.example .env
   ```

3. **Edit Configuration** (`.env`)
   ```env
   # GPU Runner Configuration
   RUNNER_PORT=8787
   IDLE_EXIT_MIN=8
   WORK_DIR=C:/vh_runner/tmp
   
   # Vectorizer Command (adapt to your existing script)
   VECTORIZER_CMD=python ../python/trace_with_tolerance_pil.py --input {in} --output {out} --mode {mode}
   
   # Result Upload Configuration
   RESULT_UPLOAD_MODE=local_path
   RESULT_UPLOAD_SIGNED_PUT_URL=
   RESULT_NAMING={uuid}.svg
   
   # Security (CHANGE THIS!)
   RUNNER_SHARED_TOKEN=your-super-secret-token-here
   
   # Waifu2x Configuration (from your existing setup)
   WAIFU2X_DIR=C:/waifu2x-ncnn-vulkan-20230413-win64
   WAIFU2X_SCALE=4
   WAIFU2X_NOISE=3
   WAIFU2X_MODEL=models-upconv_7_anime_style_art_rgb
   ```

4. **Start the Runner**
   ```bash
   run.bat
   ```

5. **Test Local Health Check**
   ```bash
   curl http://127.0.0.1:8787/health
   # Should return: {"status": "healthy", ...}
   ```

### Step 3: Expose via Cloudflare Tunnel

1. **Install Cloudflare Tunnel**
   ```bash
   # Download from: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/
   # Extract cloudflared.exe to a folder in your PATH
   ```

2. **Login to Cloudflare**
   ```bash
   cloudflared tunnel login
   # Follow the browser authentication
   ```

3. **Create a Tunnel**
   ```bash
   cloudflared tunnel create gpu-runner
   # Note the tunnel ID from the output
   ```

4. **Configure Tunnel** (create `tunnel-config.yml`)
   ```yaml
   tunnel: YOUR_TUNNEL_ID_HERE
   credentials-file: C:\Users\YOUR_USER\.cloudflared\YOUR_TUNNEL_ID_HERE.json
   
   ingress:
     - hostname: gpu-runner.your-domain.com
       service: http://127.0.0.1:8787
     - service: http_status:404
   ```

5. **Start the Tunnel**
   ```bash
   cloudflared tunnel run gpu-runner
   # Keep this running as a service
   ```

6. **Set Up DNS** (in Cloudflare Dashboard)
   - Add CNAME record: `gpu-runner.your-domain.com` → `YOUR_TUNNEL_ID_HERE.cfargotunnel.com`

### Step 4: Configure Your Website

1. **Set Environment Variables** (in your PHP deployment)
   ```env
   GPU_RUNNER_URL=https://gpu-runner.your-domain.com
   GPU_RUNNER_TOKEN=your-super-secret-token-here
   ```

2. **Test the Connection**
   ```bash
   curl -X POST https://gpu-runner.your-domain.com/run \
     -H "Authorization: Bearer your-super-secret-token-here" \
     -H "Content-Type: application/json" \
     -d '{"input_url": "https://example.com/test.png", "mode": "color"}'
   ```

### Step 5: Integrate with Your Website

1. **Update Vectorize Button** (in your dashboard)
   ```javascript
   // Replace existing vectorization call with:
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
           // Handle successful vectorization
           console.log('Vectorization completed:', data.data);
       } else {
           // Handle error
           console.error('Vectorization failed:', data.error);
       }
   });
   ```

## 🔧 Advanced Configuration

### Auto-Start on Boot (Windows)

1. **Create Startup Script** (`start_gpu_runner.bat`)
   ```batch
   @echo off
   cd /d C:\path\to\your\project\runner
   call run.bat
   ```

2. **Add to Windows Startup**
   - Press `Win + R`, type `shell:startup`
   - Create shortcut to your startup script

### Cloudflare Tunnel as Service

1. **Install as Windows Service**
   ```bash
   cloudflared service install
   ```

2. **Configure Service**
   ```bash
   cloudflared tunnel --config tunnel-config.yml run gpu-runner
   ```

### Monitoring and Logs

1. **Check Runner Status**
   ```bash
   curl https://gpu-runner.your-domain.com/health
   ```

2. **View Logs**
   - Runner logs: Check console output from `run.bat`
   - Tunnel logs: Check Cloudflare dashboard

## 🧪 Testing

### Manual Testing

1. **Test Runner Locally**
   ```bash
   # Start runner
   cd runner && run.bat
   
   # In another terminal, test health
   curl http://127.0.0.1:8787/health
   
   # Test vectorization
   curl -X POST http://127.0.0.1:8787/run \
     -H "Authorization: Bearer your-token" \
     -H "Content-Type: application/json" \
     -d @examples/request.json
   ```

2. **Test Through Website**
   - Upload an image through your website
   - Check that vectorization completes
   - Verify SVG output is generated

### Integration Testing

1. **Test PHP Endpoint**
   ```bash
   curl -X POST https://your-website.com/php/api/gpu_vectorize.php \
     -H "Content-Type: application/json" \
     -d '{"input_url": "https://example.com/test.png", "mode": "color"}'
   ```

2. **Test Error Handling**
   - Test with invalid URLs
   - Test with invalid modes
   - Test with missing authentication

## 🔒 Security Considerations

1. **Token Security**
   - Use a strong, random token
   - Keep token secret and secure
   - Rotate token periodically

2. **Network Security**
   - Runner only binds to localhost
   - Cloudflare Tunnel provides HTTPS
   - No direct internet exposure

3. **Input Validation**
   - Validate all input URLs
   - Check file types and sizes
   - Sanitize filenames

## 🚨 Troubleshooting

### Common Issues

1. **Runner Won't Start**
   ```bash
   # Check Python installation
   python --version
   
   # Check dependencies
   pip install -r requirements.txt
   
   # Check port availability
   netstat -an | findstr 8787
   ```

2. **Tunnel Connection Issues**
   ```bash
   # Check tunnel status
   cloudflared tunnel list
   
   # Check DNS configuration
   nslookup gpu-runner.your-domain.com
   ```

3. **Vectorization Fails**
   ```bash
   # Check Waifu2x installation
   dir C:\waifu2x-ncnn-vulkan-20230413-win64
   
   # Check GPU drivers
   nvidia-smi
   
   # Check logs in runner console
   ```

4. **PHP Connection Issues**
   ```bash
   # Test direct connection
   curl -X POST https://gpu-runner.your-domain.com/run \
     -H "Authorization: Bearer your-token" \
     -H "Content-Type: application/json" \
     -d '{"input_url": "https://example.com/test.png", "mode": "color"}'
   ```

### Performance Optimization

1. **GPU Utilization**
   - Monitor GPU usage with `nvidia-smi`
   - Adjust batch sizes if needed
   - Consider multiple runners for high load

2. **Memory Management**
   - Monitor RAM usage during processing
   - Clean up temporary files
   - Restart runner periodically

## 🔄 Cloud GPU Deployment (Optional)

If you want to deploy to a cloud GPU VM later:

1. **Keep the Same Runner Code**
   - No changes needed to the FastAPI runner
   - Same environment variables and configuration

2. **Provider-Specific Setup**
   - **AWS**: Use EC2 with GPU instances
   - **Google Cloud**: Use Compute Engine with GPUs
   - **Azure**: Use NC-series VMs

3. **Start/Stop Scripts**
   ```bash
   # Example for AWS
   aws ec2 start-instances --instance-ids i-1234567890abcdef0
   aws ec2 stop-instances --instance-ids i-1234567890abcdef0
   ```

## 📞 Support

If you encounter issues:

1. Check the logs in the runner console
2. Verify all configuration values
3. Test each component individually
4. Check network connectivity
5. Verify GPU drivers and CUDA installation

## 🎉 Success Indicators

You'll know everything is working when:

- ✅ Runner starts without errors
- ✅ Health check returns `{"status": "healthy"}`
- ✅ Tunnel connects and shows active status
- ✅ Website can successfully vectorize images
- ✅ SVG files are generated and downloadable
- ✅ Runner auto-shuts down after idle timeout 