# Auto-start monitor for GPU Vectorization Runner
# This script monitors for start trigger files and launches the runner

Write-Host "Starting GPU Runner Auto-Start Monitor..." -ForegroundColor Green
Write-Host "Monitoring: C:\vh_runner\start_trigger.txt"
Write-Host ""

$TriggerFile = "C:\vh_runner\start_trigger.txt"
$RunnerPath = "C:\wamp64\www\test\runner"
$RunnerPort = 8787

# Create trigger directory if it doesn't exist
$TriggerDir = Split-Path $TriggerFile
if (!(Test-Path $TriggerDir)) {
    New-Item -ItemType Directory -Path $TriggerDir -Force | Out-Null
    Write-Host "Created trigger directory: $TriggerDir" -ForegroundColor Yellow
}

Write-Host "Monitor is running. Press Ctrl+C to stop." -ForegroundColor Green
Write-Host ""

while ($true) {
    try {
        # Check if trigger file exists
        if (Test-Path $TriggerFile) {
            Write-Host "[$(Get-Date)] Start trigger detected!" -ForegroundColor Yellow
            
            # Delete the trigger file
            Remove-Item $TriggerFile -Force -ErrorAction SilentlyContinue
            
            # Check if runner is already running using simpler method
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
                Write-Host "[$(Get-Date)] Runner already running on port $RunnerPort" -ForegroundColor Blue
            } else {
                Write-Host "[$(Get-Date)] Starting GPU Runner..." -ForegroundColor Green
                
                try {
                    # Use simpler approach to start the runner
                    $StartScript = @"
cd '$RunnerPath'
if (Test-Path '.venv\Scripts\Activate.ps1') {
    .\.venv\Scripts\Activate.ps1
} else {
    Write-Host 'Virtual environment not found, using system Python'
}
python app.py
"@
                    
                    # Start in new window
                    Start-Process powershell -ArgumentList "-NoExit", "-Command", $StartScript
                    
                    Write-Host "[$(Get-Date)] GPU Runner startup initiated" -ForegroundColor Green
                } catch {
                    Write-Host "[$(Get-Date)] Failed to start GPU Runner: $($_.Exception.Message)" -ForegroundColor Red
                }
            }
        } else {
            # Show a dot every 30 seconds to indicate it's running
            if ((Get-Date).Second % 30 -eq 0) {
                Write-Host "." -NoNewline -ForegroundColor Gray
            }
        }
        
        # Wait 5 seconds before checking again
        Start-Sleep -Seconds 5
        
    } catch {
        Write-Host "[$(Get-Date)] Monitor error: $($_.Exception.Message)" -ForegroundColor Red
        Start-Sleep -Seconds 10
    }
}