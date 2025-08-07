# Auto-Start Setup Instructions

## Overview
This system automatically starts the GPU runner when users request vectorization and stops it after idle time.

## 🚀 Setup Steps

### 1. Update Configuration
Update your `.env` file:
```bash
cd runner
copy env.example .env
# Edit .env and change IDLE_EXIT_MIN=15 (15 minutes idle timeout)
```

### 2. Start the Auto-Monitor
Choose ONE of these methods:

#### Method A: PowerShell Monitor (Recommended)
```powershell
# Run this in PowerShell as Administrator
cd runner
.\auto_start_monitor.ps1
```

#### Method B: Batch Monitor
```cmd
# Run this in Command Prompt
cd runner
auto_start_monitor.bat
```

### 3. Test the System
1. **Stop any running GPU runner**
2. **Make a vectorization request** from your website
3. **Monitor should automatically start the runner**
4. **Wait 15 minutes** - runner should auto-shutdown
5. **Make another request** - runner should auto-start again

## 🔧 How It Works

### User Flow:
1. **User clicks vectorize** → PHP checks if runner is healthy
2. **If runner not running** → PHP writes trigger file `C:\vh_runner\start_trigger.txt`
3. **Monitor detects trigger** → Starts GPU runner automatically
4. **Runner processes request** → Returns result to user
5. **After 15 minutes idle** → Runner shuts down automatically

### Files Created:
- `C:\vh_runner\start_trigger.txt` - Trigger file for starting runner
- `C:\vh_runner\tmp\` - Working directory for processed files

## 🛠️ Advanced Setup (Optional)

### Windows Scheduled Task (For Production)
Create a Windows Scheduled Task to run the monitor on system startup:

1. **Open Task Scheduler** → Create Basic Task
2. **Name**: "GPU Runner Auto-Monitor"
3. **Trigger**: "When the computer starts"
4. **Action**: "Start a program"
5. **Program**: `powershell.exe`
6. **Arguments**: `-File "C:\wamp64\www\test\runner\auto_start_monitor.ps1"`
7. **Run with highest privileges**: ✅

## 📊 Monitoring

### Check if Monitor is Running:
```powershell
Get-Process | Where-Object {$_.ProcessName -like "*powershell*"}
```

### Check if Runner is Running:
```powershell
Get-NetTCPConnection -LocalPort 8787
```

### View Trigger File:
```powershell
Get-Content "C:\vh_runner\start_trigger.txt" -Tail 10
```

## 🔍 Troubleshooting

### Runner Won't Start:
1. Check if Python virtual environment exists in `runner\.venv\`
2. Verify all dependencies installed: `pip install -r requirements.txt`
3. Check Windows permissions for `C:\vh_runner\` directory

### Monitor Not Detecting Triggers:
1. Verify trigger file path: `C:\vh_runner\start_trigger.txt`
2. Check PHP error logs for trigger file creation issues
3. Run monitor with elevated permissions

### Cloudflare Tunnel Issues:
1. Ensure tunnel is running: `cloudflared tunnel run vectrahub-gpu`
2. Check tunnel health: `https://vectrahub-gpu.vectrahub.online/health`
3. Restart tunnel if needed