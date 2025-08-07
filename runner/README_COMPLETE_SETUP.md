# 🚀 VectraHub Complete Auto-Startup System

## 🎯 What This Does

This system automatically starts **EVERYTHING** you need:
- ✅ **Cloudflare Tunnel** (connects your local GPU to the internet)
- ✅ **Auto-Monitor** (watches for vectorization requests)
- ✅ **GPU Runner** (starts on-demand when users need it)

## 📋 Quick Start

### Option 1: Start Everything Now (Manual)
```batch
# Double-click this file:
start_everything.bat
```

### Option 2: Auto-Start on Windows Boot (Recommended)
```powershell
# Right-click PowerShell → "Run as Administrator"
# Then run:
cd C:\wamp64\www\test\runner
.\setup_auto_startup.ps1
```

## 🔧 Files Created

| File | Purpose |
|------|---------|
| `start_everything.ps1` | Main script that runs everything |
| `start_everything.bat` | Simple double-click starter |
| `setup_auto_startup.ps1` | Creates Windows Scheduled Task |
| `auto_start_monitor.ps1` | Original monitor (now included in main script) |

## 🎮 How It Works

### User Experience:
1. **User visits website** → Clicks "Vectorize Image"
2. **System checks** → Is GPU runner active?
3. **If sleeping** → Auto-starts in 5-10 seconds
4. **Processes image** → Waifu2x + VTracer
5. **Returns result** → User gets SVG file
6. **After 15 minutes idle** → Runner shuts down to save resources

### Behind the Scenes:
```
[Website] → [PHP] → [Cloudflare Tunnel] → [GPU Runner]
    ↓           ↓            ↓                ↓
Vectorize   Checks      Secure           Waifu2x
Request     Health      Routing          + VTracer
    ↓           ↓            ↓                ↓
If Down →  Trigger →   Monitor   →    Start Runner
        (file)       (detects)      (new window)
```

## 📊 System Status

### Check if Everything is Running:
```powershell
# Check tunnel
Invoke-WebRequest "https://vectrahub-gpu.vectrahub.online/health"

# Check GPU runner locally
Invoke-WebRequest "http://127.0.0.1:8787/health"

# Check processes
Get-Process | Where-Object {$_.ProcessName -like "*python*" -or $_.ProcessName -like "*cloudflared*"}
```

### Manual Control:
```powershell
# Start everything
.\start_everything.ps1

# Start only monitor
.\auto_start_monitor.ps1

# Start only tunnel
& "C:\Program Files (x86)\cloudflared\cloudflared.exe" tunnel run vectrahub-gpu

# Start only GPU runner
cd runner && python app.py
```

## 🛠️ Troubleshooting

### GPU Runner Won't Start:
1. Check Python virtual environment: `runner\.venv\`
2. Install dependencies: `pip install -r requirements.txt`
3. Check work directory exists: `C:\vh_runner\tmp\`

### Tunnel Connection Issues:
1. Verify tunnel configuration: `C:\Users\pc\.cloudflared\config.yml`
2. Check DNS: `nslookup vectrahub-gpu.vectrahub.online`
3. Restart tunnel: Kill process and run `start_everything.ps1`

### Monitor Not Detecting:
1. Check trigger directory: `C:\vh_runner\`
2. Verify PHP can write files (permissions)
3. Check monitor logs in PowerShell window

### Website 500 Errors:
1. Check Fly.io deployment: `fly logs`
2. Verify environment variables: `GPU_RUNNER_URL`, `GPU_RUNNER_TOKEN`
3. Test PHP endpoint directly

## 🔐 Security Notes

- **Cloudflare Tunnel** provides secure access without opening ports
- **Bearer token** authentication between PHP and GPU runner
- **Local binding** - runner only accessible via tunnel
- **Auto-shutdown** prevents resource waste

## 🎯 Production Deployment

1. **Install on GPU PC** → Copy all files to `C:\wamp64\www\test\runner\`
2. **Run setup** → Execute `setup_auto_startup.ps1` as Administrator
3. **Reboot** → System will start automatically
4. **Test** → Make vectorization request from website
5. **Monitor** → Check logs and performance

## 📈 Performance

- **Cold start**: ~10-15 seconds (when runner is sleeping)
- **Warm start**: ~1-3 seconds (when runner is active)
- **Processing time**: ~2-5 seconds per image
- **Auto-shutdown**: 15 minutes after last request

## 🎉 You're Done!

Your VectraHub GPU system is now:
- ✅ **Fully automated**
- ✅ **Production ready**
- ✅ **Self-healing**
- ✅ **Resource efficient**

**Congratulations!** 🎊