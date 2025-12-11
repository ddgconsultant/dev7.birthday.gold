<#
.SYNOPSIS
    Installs the Mail Firewall Monitor as a Windows Scheduled Task
.DESCRIPTION
    Creates a scheduled task that runs the monitor every 5 minutes.
#>

param(
    [int]$IntervalMinutes = 5,
    [string]$ScriptPath = "C:\MailServer_AccessManagement\MailFirewall-Monitor.ps1"
)

$taskName = "MailFirewallMonitor"
$taskDescription = "Monitors mail server logs and blocks malicious IPs"

# Check if script exists
if (-not (Test-Path $ScriptPath)) {
    Write-Host "Script not found at: $ScriptPath" -ForegroundColor Red
    Write-Host "Please copy the scripts to C:\MailServer_AccessManagement\ first." -ForegroundColor Yellow
    exit 1
}

# Remove existing task if present
$existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($existingTask) {
    Write-Host "Removing existing scheduled task..." -ForegroundColor Yellow
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
}

# Create the action
$action = New-ScheduledTaskAction -Execute "powershell.exe" `
    -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$ScriptPath`""

# Create trigger - every X minutes
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes $IntervalMinutes) `
    -RepetitionDuration (New-TimeSpan -Days 9999)

# Create principal - run as SYSTEM with highest privileges
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" `
    -LogonType ServiceAccount `
    -RunLevel Highest

# Create settings
$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 10) `
    -RestartCount 3 `
    -RestartInterval (New-TimeSpan -Minutes 1)

# Register the task
Register-ScheduledTask -TaskName $taskName `
    -Description $taskDescription `
    -Action $action `
    -Trigger $trigger `
    -Principal $principal `
    -Settings $settings

Write-Host "`nScheduled task '$taskName' created successfully!" -ForegroundColor Green
Write-Host "  - Runs every $IntervalMinutes minutes" -ForegroundColor Gray
Write-Host "  - Runs as SYSTEM account" -ForegroundColor Gray
Write-Host "`nTo check status: Get-ScheduledTask -TaskName '$taskName'" -ForegroundColor Cyan
Write-Host "To run now: Start-ScheduledTask -TaskName '$taskName'" -ForegroundColor Cyan
Write-Host "To disable: Disable-ScheduledTask -TaskName '$taskName'" -ForegroundColor Cyan
