# Test script to check if GPU runner is working
Write-Host "Testing GPU Runner..." -ForegroundColor Green

# Test health endpoint
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8787/health" -Method GET -TimeoutSec 10
    Write-Host "Health check: SUCCESS" -ForegroundColor Green
    Write-Host "Response: $($response.Content)" -ForegroundColor Gray
} catch {
    Write-Host "Health check: FAILED" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "If health check failed, the runner may not be started yet." -ForegroundColor Yellow
Write-Host "Try running: .\run.bat" -ForegroundColor Cyan



