# Setup Windows Scheduled Task for VectraHub GPU System
# Run this script as Administrator to set up automatic startup

Write-Host "🔧 Setting up VectraHub GPU System for automatic startup..." -ForegroundColor Green
Write-Host ""

# Check if running as Administrator
$currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Host "❌ This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "   Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    exit 1
}

$TaskName = "VectraHub-GPU-System"
$ScriptPath = Join-Path $PSScriptRoot "start_everything.ps1"
$WorkingDirectory = $PSScriptRoot

Write-Host "📋 Configuration:" -ForegroundColor Cyan
Write-Host "   Task Name: $TaskName" -ForegroundColor Gray
Write-Host "   Script Path: $ScriptPath" -ForegroundColor Gray
Write-Host "   Working Directory: $WorkingDirectory" -ForegroundColor Gray
Write-Host ""

try {
    # Remove existing task if it exists
    $ExistingTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if ($ExistingTask) {
        Write-Host "🗑️  Removing existing task..." -ForegroundColor Yellow
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
    }

    # Create action
    $Action = New-ScheduledTaskAction -Execute "PowerShell.exe" -Argument "-ExecutionPolicy Bypass -WindowStyle Hidden -File `"$ScriptPath`"" -WorkingDirectory $WorkingDirectory

    # Create trigger (at startup)
    $Trigger = New-ScheduledTaskTrigger -AtStartup

    # Create settings
    $Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RunOnlyIfNetworkAvailable

    # Create principal (run with highest privileges)
    $Principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

    # Register the task
    Register-ScheduledTask -TaskName $TaskName -Action $Action -Trigger $Trigger -Settings $Settings -Principal $Principal -Description "VectraHub GPU Vectorization System - Auto-start monitor, tunnel, and GPU runner"

    Write-Host "✅ Scheduled task created successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "📋 What happens now:" -ForegroundColor Cyan
    Write-Host "   ✅ System will start automatically when Windows boots" -ForegroundColor Green
    Write-Host "   ✅ Cloudflare Tunnel will be running" -ForegroundColor Green
    Write-Host "   ✅ Monitor will watch for vectorization requests" -ForegroundColor Green
    Write-Host "   ✅ GPU Runner will start on-demand when needed" -ForegroundColor Green
    Write-Host ""
    Write-Host "🎯 Your system is now fully automated!" -ForegroundColor Green
    Write-Host ""
    Write-Host "📖 Manual Control:" -ForegroundColor Yellow
    Write-Host "   Start manually: schtasks /run /tn `"$TaskName`"" -ForegroundColor Gray
    Write-Host "   Stop: schtasks /end /tn `"$TaskName`"" -ForegroundColor Gray
    Write-Host "   Remove: schtasks /delete /tn `"$TaskName`" /f" -ForegroundColor Gray

} catch {
    Write-Host "❌ Failed to create scheduled task: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "🔧 Manual Setup Alternative:" -ForegroundColor Yellow
    Write-Host "   1. Open Task Scheduler (taskschd.msc)" -ForegroundColor Gray
    Write-Host "   2. Create Basic Task: '$TaskName'" -ForegroundColor Gray
    Write-Host "   3. Trigger: 'When the computer starts'" -ForegroundColor Gray
    Write-Host "   4. Action: Start PowerShell with:" -ForegroundColor Gray
    Write-Host "      Program: PowerShell.exe" -ForegroundColor Gray
    Write-Host "      Arguments: -ExecutionPolicy Bypass -File `"$ScriptPath`"" -ForegroundColor Gray
}

Write-Host ""
Write-Host "Press any key to continue..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")