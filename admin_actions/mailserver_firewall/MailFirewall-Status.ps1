#Requires -Modules PSSQLite
<#
.SYNOPSIS
    Mail Server Firewall - Status and Reporting
.DESCRIPTION
    Shows current blocked IPs, recent attacks, whitelist entries, and statistics.
#>

param(
    [string]$DatabasePath = "C:\MailServer_AccessManagement\firewall_tracker.db",
    [switch]$ShowBlocked,
    [switch]$ShowWhitelist,
    [switch]$ShowRecentAttacks,
    [switch]$ShowStats,
    [switch]$All,
    [int]$Hours = 24
)

Import-Module PSSQLite

if (-not (Test-Path $DatabasePath)) {
    Write-Host "Database not found: $DatabasePath" -ForegroundColor Red
    exit 1
}

if ($All) {
    $ShowBlocked = $true
    $ShowWhitelist = $true
    $ShowRecentAttacks = $true
    $ShowStats = $true
}

# Default to showing stats if no options specified
if (-not $ShowBlocked -and -not $ShowWhitelist -and -not $ShowRecentAttacks -and -not $ShowStats) {
    $ShowStats = $true
    $ShowBlocked = $true
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "    MAIL FIREWALL STATUS REPORT" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Gray
Write-Host ""

#region Statistics
if ($ShowStats) {
    Write-Host "--- STATISTICS ---" -ForegroundColor Yellow

    $windowStart = (Get-Date).AddHours(-$Hours).ToString("yyyy-MM-dd HH:mm:ss")

    # Total failed auth attempts (sum of all counts)
    $failedCount = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT COALESCE(SUM(attempt_count), 0) as cnt FROM failed_auth
"@
    Write-Host "Total failed auth attempts: $($failedCount.cnt)" -ForegroundColor White

    # Failed auths in window (recent activity)
    $recentFailed = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT COALESCE(SUM(attempt_count), 0) as cnt FROM failed_auth WHERE last_seen > '$windowStart'
"@
    Write-Host "Failed auths (last $Hours hours): $($recentFailed.cnt)" -ForegroundColor White

    # Unique attacking IPs
    $uniqueIPs = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT COUNT(*) as cnt FROM failed_auth
"@
    Write-Host "Unique attacking IPs: $($uniqueIPs.cnt)" -ForegroundColor White

    # Currently blocked IPs
    $blockedIPs = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT COUNT(*) as cnt FROM blocked_ips WHERE is_active = 1
"@
    Write-Host "Currently blocked IPs: $($blockedIPs.cnt)" -ForegroundColor Red

    # Permanently blocked IPs
    $permBlocked = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT COUNT(*) as cnt FROM blocked_ips WHERE is_active = 1 AND is_permanent = 1
"@
    Write-Host "  Permanently blocked: $($permBlocked.cnt)" -ForegroundColor Red

    # Currently blocked subnets
    $blockedSubnets = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT COUNT(*) as cnt FROM blocked_subnets WHERE is_active = 1
"@
    Write-Host "Currently blocked subnets: $($blockedSubnets.cnt)" -ForegroundColor Red

    # Total successful logins
    $successCount = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT COALESCE(SUM(success_count), 0) as cnt FROM successful_auth
"@
    Write-Host "Total successful logins: $($successCount.cnt)" -ForegroundColor Green

    # Auto-whitelisted IPs
    $autoWhitelist = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT COUNT(*) as cnt FROM auto_whitelist
"@
    Write-Host "Auto-whitelisted IPs: $($autoWhitelist.cnt)" -ForegroundColor Green

    Write-Host ""
}
#endregion

#region Blocked IPs
if ($ShowBlocked) {
    Write-Host "--- CURRENTLY BLOCKED IPs ---" -ForegroundColor Yellow

    $blocked = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT ip_address, block_reason, failure_count, offense_count, blocked_at, expires_at, is_permanent
FROM blocked_ips WHERE is_active = 1 ORDER BY offense_count DESC, blocked_at DESC LIMIT 50
"@

    if ($blocked) {
        $blocked | Format-Table -Property @(
            @{Label="IP Address"; Expression={$_.ip_address}; Width=18},
            @{Label="Offense#"; Expression={$_.offense_count}; Width=9},
            @{Label="Fails"; Expression={$_.failure_count}; Width=6},
            @{Label="Reason"; Expression={$_.block_reason}; Width=30},
            @{Label="Blocked At"; Expression={$_.blocked_at}; Width=20},
            @{Label="Expires"; Expression={ if($_.is_permanent) {"PERMANENT"} else {$_.expires_at} }; Width=20}
        ) -AutoSize
    } else {
        Write-Host "  No IPs currently blocked." -ForegroundColor Gray
    }

    Write-Host "--- CURRENTLY BLOCKED SUBNETS ---" -ForegroundColor Yellow

    $blockedSubs = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT subnet, block_reason, ip_count, offense_count, blocked_at, expires_at, is_permanent
FROM blocked_subnets WHERE is_active = 1 ORDER BY offense_count DESC, blocked_at DESC
"@

    if ($blockedSubs) {
        $blockedSubs | Format-Table -Property @(
            @{Label="Subnet"; Expression={$_.subnet}; Width=18},
            @{Label="Offense#"; Expression={$_.offense_count}; Width=9},
            @{Label="IPs"; Expression={$_.ip_count}; Width=5},
            @{Label="Reason"; Expression={$_.block_reason}; Width=30},
            @{Label="Blocked At"; Expression={$_.blocked_at}; Width=20},
            @{Label="Expires"; Expression={ if($_.is_permanent) {"PERMANENT"} else {$_.expires_at} }; Width=20}
        ) -AutoSize
    } else {
        Write-Host "  No subnets currently blocked." -ForegroundColor Gray
    }
    Write-Host ""
}
#endregion

#region Recent Attacks
if ($ShowRecentAttacks) {
    Write-Host "--- TOP ATTACKING IPs ---" -ForegroundColor Yellow

    $windowStart = (Get-Date).AddHours(-$Hours).ToString("yyyy-MM-dd HH:mm:ss")

    # Top attackers by attempt count (using aggregated schema)
    $topAttackers = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT ip_address, attempt_count, first_seen, last_seen, usernames
FROM failed_auth
ORDER BY attempt_count DESC
LIMIT 20
"@

    if ($topAttackers) {
        $topAttackers | Format-Table -Property @(
            @{Label="IP Address"; Expression={$_.ip_address}; Width=18},
            @{Label="Attempts"; Expression={$_.attempt_count}; Width=10},
            @{Label="First Seen"; Expression={$_.first_seen}; Width=20},
            @{Label="Last Seen"; Expression={$_.last_seen}; Width=20}
        ) -AutoSize
    } else {
        Write-Host "  No attacks recorded." -ForegroundColor Gray
    }

    Write-Host "--- RECENT ATTACKERS (Last $Hours hours) ---" -ForegroundColor Yellow

    $recentAttackers = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT ip_address, attempt_count, last_seen, usernames
FROM failed_auth
WHERE last_seen > '$windowStart'
ORDER BY last_seen DESC
LIMIT 15
"@

    if ($recentAttackers) {
        $recentAttackers | Format-Table -Property @(
            @{Label="IP Address"; Expression={$_.ip_address}; Width=18},
            @{Label="Attempts"; Expression={$_.attempt_count}; Width=10},
            @{Label="Last Seen"; Expression={$_.last_seen}; Width=20},
            @{Label="Usernames"; Expression={
                if($_.usernames -and $_.usernames.Length -gt 25) {
                    $_.usernames.Substring(0,25) + "..."
                } else { $_.usernames }
            }; Width=30}
        ) -AutoSize
    } else {
        Write-Host "  No recent attacks in the last $Hours hours." -ForegroundColor Gray
    }

    Write-Host "--- TOP ATTACKING SUBNETS ---" -ForegroundColor Yellow

    # Build subnet stats from aggregated IP data
    $topSubnets = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT
    substr(ip_address, 1, instr(ip_address, '.') +
           instr(substr(ip_address, instr(ip_address, '.') + 1), '.') +
           instr(substr(ip_address, instr(ip_address, '.') +
                 instr(substr(ip_address, instr(ip_address, '.') + 1), '.') + 1), '.')) || '0/24' as subnet,
    COUNT(*) as unique_ips,
    SUM(attempt_count) as total_attempts
FROM failed_auth
GROUP BY subnet
HAVING unique_ips > 1
ORDER BY unique_ips DESC, total_attempts DESC
LIMIT 15
"@

    if ($topSubnets) {
        $topSubnets | Format-Table -Property @(
            @{Label="Subnet"; Expression={$_.subnet}; Width=18},
            @{Label="Unique IPs"; Expression={$_.unique_ips}; Width=12},
            @{Label="Total Attempts"; Expression={$_.total_attempts}; Width=15}
        ) -AutoSize
    } else {
        Write-Host "  No subnet patterns detected." -ForegroundColor Gray
    }
    Write-Host ""
}
#endregion

#region Whitelist
if ($ShowWhitelist) {
    Write-Host "--- WHITELIST ENTRIES ---" -ForegroundColor Yellow

    $whitelist = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT ip_or_subnet, description, is_subnet, created_at FROM whitelist ORDER BY is_subnet DESC, ip_or_subnet
"@

    if ($whitelist) {
        $whitelist | Format-Table -Property @(
            @{Label="IP/Subnet"; Expression={$_.ip_or_subnet}; Width=22},
            @{Label="Type"; Expression={ if($_.is_subnet) {"Subnet"} else {"IP"} }; Width=8},
            @{Label="Description"; Expression={$_.description}; Width=35}
        ) -AutoSize
    }

    Write-Host "--- AUTO-WHITELISTED (Successful Logins) ---" -ForegroundColor Yellow

    $autoWL = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT ip_address, success_count, first_success, last_success, usernames
FROM auto_whitelist
ORDER BY last_success DESC
LIMIT 20
"@

    if ($autoWL) {
        $autoWL | Format-Table -Property @(
            @{Label="IP Address"; Expression={$_.ip_address}; Width=18},
            @{Label="Logins"; Expression={$_.success_count}; Width=8},
            @{Label="First Success"; Expression={$_.first_success}; Width=20},
            @{Label="Last Success"; Expression={$_.last_success}; Width=20},
            @{Label="Users"; Expression={$_.usernames.Substring(0, [Math]::Min(30, $_.usernames.Length))}; Width=30}
        ) -AutoSize
    } else {
        Write-Host "  No auto-whitelisted IPs yet." -ForegroundColor Gray
    }
    Write-Host ""
}
#endregion

Write-Host "========================================" -ForegroundColor Cyan
