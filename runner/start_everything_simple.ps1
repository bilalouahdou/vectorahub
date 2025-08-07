# Simple VectraHub GPU Auto-Start System
# This script monitors for trigger files and starts the GPU runner

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   VectraHub GPU System Startup" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$RunnerPath = "C:\wamp64\www\test\runner"
$TriggerFile = "C:\vh_runner\start_trigger.txt"
$TriggerDir = "C:\vh_runner"

# Create trigger directory if it doesn't exist
if (!(Test-Path $TriggerDir)) {
    New-Item -ItemType Directory -Path $TriggerDir -Force | Out-Null
    Write-Host "Created trigger directory: $TriggerDir" -ForegroundColor Green
}

Write-Host "Starting Cloudflare Tunnel..." -ForegroundColor Yellow
try {
    Start-Process -FilePath "C:\Program Files (x86)\cloudflared\cloudflared.exe" -ArgumentList "tunnel", "run", "vectrahub-gpu" -WindowStyle Hidden
    Write-Host "Cloudflare Tunnel started successfully" -ForegroundColor Green
} catch {
    Write-Host "Warning: Could not start Cloudflare Tunnel: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "Starting Auto-Monitor..." -ForegroundColor Yellow
Write-Host "Monitoring: $TriggerFile" -ForegroundColor Gray
Write-Host "Runner Path: $RunnerPath" -ForegroundColor Gray
Write-Host ""
Write-Host "System is ready! GPU runner will start on-demand." -ForegroundColor Green
Write-Host "Press Ctrl+C to stop." -ForegroundColor Red
Write-Host ""

# Monitor loop
while ($true) {
    try {
        # Check if trigger file exists
        if (Test-Path $TriggerFile) {
            Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Trigger detected! Starting GPU Runner..." -ForegroundColor Green
            
            # Delete the trigger file
            Remove-Item $TriggerFile -Force -ErrorAction SilentlyContinue
            
            # Check if runner is already running on port 8787
            $portCheck = netstat -an | Select-String ":8787"
            if ($portCheck) {
                Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Runner already running on port 8787" -ForegroundColor Blue
            } else {
                Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Starting GPU Runner in new window..." -ForegroundColor Green
                
                # Start the runner
                Set-Location $RunnerPath
                Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$RunnerPath'; .\run.bat"
                
                Write-Host "[$(Get-Date -Format 'HH:mm:ss')] GPU Runner started successfully" -ForegroundColor Green
            }
        }
        
        # Wait 5 seconds before checking again
        Start-Sleep -Seconds 5
        
    } catch {
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Monitor error: $($_.Exception.Message)" -ForegroundColor Red
        Start-Sleep -Seconds 10
    }
}