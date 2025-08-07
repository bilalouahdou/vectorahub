# Complete Auto-Start System for VectraHub GPU Runner
# This script starts all components: Monitor, Tunnel, and handles GPU Runner

Write-Host "🚀 Starting VectraHub GPU System..." -ForegroundColor Green
Write-Host ""

# Configuration
$RunnerPath = "C:\wamp64\www\test\runner"
$TriggerFile = "C:\vh_runner\start_trigger.txt"
$CloudflaredPath = "C:\Program Files (x86)\cloudflared\cloudflared.exe"
$TunnelName = "vectrahub-gpu"

# Create necessary directories
$TriggerDir = Split-Path $TriggerFile
if (!(Test-Path $TriggerDir)) {
    New-Item -ItemType Directory -Path $TriggerDir -Force | Out-Null
    Write-Host "✅ Created trigger directory: $TriggerDir" -ForegroundColor Green
}

Write-Host "🔧 Starting Cloudflare Tunnel..." -ForegroundColor Yellow
try {
    # Start Cloudflare Tunnel in background
    $TunnelProcess = Start-Process -FilePath $CloudflaredPath -ArgumentList "tunnel", "run", $TunnelName -WindowStyle Hidden -PassThru
    Write-Host "✅ Cloudflare Tunnel started (PID: $($TunnelProcess.Id))" -ForegroundColor Green
    Start-Sleep 3
} catch {
    Write-Host "❌ Failed to start Cloudflare Tunnel: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "   Please check if cloudflared is installed and tunnel is configured" -ForegroundColor Yellow
}

Write-Host "🔍 Starting Auto-Monitor..." -ForegroundColor Yellow
Write-Host "   Monitoring: $TriggerFile" -ForegroundColor Gray
Write-Host "   Runner Path: $RunnerPath" -ForegroundColor Gray
Write-Host ""
Write-Host "📋 System Status:" -ForegroundColor Cyan
Write-Host "   - Monitor: Running" -ForegroundColor Green
Write-Host "   - Tunnel: Running" -ForegroundColor Green
Write-Host "   - GPU Runner: On-Demand (will start when needed)" -ForegroundColor Yellow
Write-Host ""
Write-Host "🎯 Ready! Users can now vectorize images." -ForegroundColor Green
Write-Host "   The GPU runner will start automatically when first request comes in." -ForegroundColor Gray
Write-Host ""
Write-Host "Press Ctrl+C to stop all services." -ForegroundColor Red
Write-Host ""

# Monitor loop
while ($true) {
    try {
        # Check if trigger file exists
        if (Test-Path $TriggerFile) {
            Write-Host "[$(Get-Date -Format 'HH:mm:ss')] 🔔 Start trigger detected!" -ForegroundColor Yellow
            
            # Delete the trigger file
            Remove-Item $TriggerFile -Force -ErrorAction SilentlyContinue
            
            # Check if runner is already running
            $RunnerRunning = $false
            try {
                $NetStat = netstat -an | Select-String ":8787"
                if ($NetStat) {
                    $RunnerRunning = $true
                }
            } catch {
                # Ignore netstat errors
            }
            
            if ($RunnerRunning) {
                Write-Host "[$(Get-Date -Format 'HH:mm:ss')] ℹ️  Runner already running on port 8787" -ForegroundColor Blue
            } else {
                Write-Host "[$(Get-Date -Format 'HH:mm:ss')] 🚀 Starting GPU Runner..." -ForegroundColor Green
                
                try {
                    # Start the runner in a new window
                    $StartScript = @"
Write-Host '🚀 Starting VectraHub GPU Runner...' -ForegroundColor Green
cd '$RunnerPath'
if (Test-Path '.venv\Scripts\Activate.ps1') {
    Write-Host '📦 Activating virtual environment...' -ForegroundColor Yellow
    .\.venv\Scripts\Activate.ps1
} else {
    Write-Host '⚠️  Virtual environment not found, using system Python' -ForegroundColor Yellow
}
Write-Host '🔥 Launching GPU Runner...' -ForegroundColor Green
python app.py
"@
                    
                    # Start in new window
                    $RunnerProcess = Start-Process powershell -ArgumentList "-NoExit", "-Command", $StartScript -PassThru
                    
                    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] ✅ GPU Runner started (PID: $($RunnerProcess.Id))" -ForegroundColor Green
                } catch {
                    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] ❌ Failed to start GPU Runner: $($_.Exception.Message)" -ForegroundColor Red
                }
            }
        }
        
        # Check if tunnel is still running
        if ($TunnelProcess -and $TunnelProcess.HasExited) {
            Write-Host "[$(Get-Date -Format 'HH:mm:ss')] ⚠️  Tunnel process stopped, restarting..." -ForegroundColor Yellow
            try {
                $TunnelProcess = Start-Process -FilePath $CloudflaredPath -ArgumentList "tunnel", "run", $TunnelName -WindowStyle Hidden -PassThru
                Write-Host "[$(Get-Date -Format 'HH:mm:ss')] ✅ Tunnel restarted (PID: $($TunnelProcess.Id))" -ForegroundColor Green
            } catch {
                Write-Host "[$(Get-Date -Format 'HH:mm:ss')] ❌ Failed to restart tunnel: $($_.Exception.Message)" -ForegroundColor Red
            }
        }
        
        # Show a heartbeat every 60 seconds
        if ((Get-Date).Second -eq 0) {
            Write-Host "." -NoNewline -ForegroundColor DarkGray
        }
        
        # Wait 5 seconds before checking again
        Start-Sleep -Seconds 5
        
    } catch {
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] ❌ Monitor error: $($_.Exception.Message)" -ForegroundColor Red
        Start-Sleep -Seconds 10
    }
}

# Cleanup when script ends
Write-Host ""
Write-Host "🛑 Stopping services..." -ForegroundColor Yellow
if ($TunnelProcess -and !$TunnelProcess.HasExited) {
    $TunnelProcess.Kill()
    Write-Host "✅ Tunnel stopped" -ForegroundColor Green
}