#Requires -Modules PSSQLite
<#
.SYNOPSIS
    Mail Server Firewall - Management Commands
.DESCRIPTION
    Add/remove whitelist entries, manually block/unblock IPs, purge old data.
#>

param(
    [string]$DatabasePath = "C:\MailServer_AccessManagement\firewall_tracker.db",

    # Actions
    [switch]$AddWhitelist,
    [switch]$RemoveWhitelist,
    [switch]$BlockIP,
    [switch]$UnblockIP,
    [switch]$UnblockAll,
    [switch]$PurgeOldData,
    [switch]$ApplyPermanentBlocks,  # Create firewall rules for all permanent blocks in DB
    [switch]$ShowPermanentBlocks,   # List IPs that should be permanently blocked

    # Parameters
    [string]$IP,
    [string]$Description,
    [switch]$Permanent,
    [int]$PurgeDays = 30
)

Import-Module PSSQLite

if (-not (Test-Path $DatabasePath)) {
    Write-Host "Database not found: $DatabasePath" -ForegroundColor Red
    exit 1
}

function Remove-FirewallRule {
    param([string]$RuleName)
    if ($RuleName) {
        Remove-NetFirewallRule -DisplayName $RuleName -ErrorAction SilentlyContinue
    }
}

#region Add Whitelist
if ($AddWhitelist) {
    if (-not $IP) {
        Write-Host "Usage: .\MailFirewall-Manage.ps1 -AddWhitelist -IP '1.2.3.4' -Description 'Reason'" -ForegroundColor Yellow
        exit 1
    }

    $isSubnet = $IP -match '/\d+$'
    $desc = if ($Description) { $Description } else { "Manually added" }

    $query = @"
INSERT OR REPLACE INTO whitelist (ip_or_subnet, description, is_subnet, created_at)
VALUES (@ip, @desc, @subnet, datetime('now'))
"@
    Invoke-SqliteQuery -DataSource $DatabasePath -Query $query -SqlParameters @{
        ip = $IP
        desc = $desc
        subnet = if ($isSubnet) { 1 } else { 0 }
    }

    # If IP was blocked, unblock it
    $blocked = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT firewall_rule_name FROM blocked_ips WHERE ip_address = '$IP' AND is_active = 1
"@
    if ($blocked) {
        Remove-FirewallRule -RuleName $blocked.firewall_rule_name
        Invoke-SqliteQuery -DataSource $DatabasePath -Query "UPDATE blocked_ips SET is_active = 0 WHERE ip_address = '$IP'"
    }

    Write-Host "Added to whitelist: $IP - $desc" -ForegroundColor Green
}
#endregion

#region Remove Whitelist
if ($RemoveWhitelist) {
    if (-not $IP) {
        Write-Host "Usage: .\MailFirewall-Manage.ps1 -RemoveWhitelist -IP '1.2.3.4'" -ForegroundColor Yellow
        exit 1
    }

    $result = Invoke-SqliteQuery -DataSource $DatabasePath -Query "DELETE FROM whitelist WHERE ip_or_subnet = '$IP'"
    Invoke-SqliteQuery -DataSource $DatabasePath -Query "DELETE FROM auto_whitelist WHERE ip_address = '$IP'"

    Write-Host "Removed from whitelist: $IP" -ForegroundColor Yellow
}
#endregion

#region Block IP
if ($BlockIP) {
    if (-not $IP) {
        Write-Host "Usage: .\MailFirewall-Manage.ps1 -BlockIP -IP '1.2.3.4' [-Permanent] [-Description 'reason']" -ForegroundColor Yellow
        exit 1
    }

    $ruleName = "MailFirewall_Block_$($IP -replace '[./]', '_')"
    $desc = if ($Description) { $Description } else { "Manually blocked" }
    $expiresAt = if ($Permanent) { $null } else { (Get-Date).AddHours(24).ToString("yyyy-MM-dd HH:mm:ss") }

    # Remove existing rule
    Remove-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue

    # Create firewall rule
    New-NetFirewallRule -DisplayName $ruleName `
        -Direction Inbound `
        -Action Block `
        -RemoteAddress $IP `
        -Protocol Any `
        -Description "MailFirewall: $desc" | Out-Null

    # Record in database
    $isSubnet = $IP -match '/\d+$'
    if ($isSubnet) {
        $query = @"
INSERT OR REPLACE INTO blocked_subnets (subnet, block_reason, blocked_at, expires_at, is_permanent, is_active, firewall_rule_name)
VALUES (@ip, @desc, datetime('now'), @expires, @perm, 1, @rule)
"@
    } else {
        $query = @"
INSERT OR REPLACE INTO blocked_ips (ip_address, block_reason, blocked_at, expires_at, is_permanent, is_active, firewall_rule_name)
VALUES (@ip, @desc, datetime('now'), @expires, @perm, 1, @rule)
"@
    }

    Invoke-SqliteQuery -DataSource $DatabasePath -Query $query -SqlParameters @{
        ip = $IP
        desc = $desc
        expires = $expiresAt
        perm = if ($Permanent) { 1 } else { 0 }
        rule = $ruleName
    }

    $permText = if ($Permanent) { "PERMANENTLY" } else { "for 24 hours" }
    Write-Host "Blocked $IP $permText - $desc" -ForegroundColor Red
}
#endregion

#region Unblock IP
if ($UnblockIP) {
    if (-not $IP) {
        Write-Host "Usage: .\MailFirewall-Manage.ps1 -UnblockIP -IP '1.2.3.4'" -ForegroundColor Yellow
        exit 1
    }

    # Get rule name and remove
    $blocked = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT firewall_rule_name FROM blocked_ips WHERE ip_address = '$IP' AND is_active = 1
"@
    if ($blocked.firewall_rule_name) {
        Remove-FirewallRule -RuleName $blocked.firewall_rule_name
    }

    $blockedSub = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT firewall_rule_name FROM blocked_subnets WHERE subnet = '$IP' AND is_active = 1
"@
    if ($blockedSub.firewall_rule_name) {
        Remove-FirewallRule -RuleName $blockedSub.firewall_rule_name
    }

    # Update database
    Invoke-SqliteQuery -DataSource $DatabasePath -Query "UPDATE blocked_ips SET is_active = 0 WHERE ip_address = '$IP'"
    Invoke-SqliteQuery -DataSource $DatabasePath -Query "UPDATE blocked_subnets SET is_active = 0 WHERE subnet = '$IP'"

    Write-Host "Unblocked: $IP" -ForegroundColor Green
}
#endregion

#region Unblock All
if ($UnblockAll) {
    Write-Host "WARNING: This will unblock ALL currently blocked IPs and subnets!" -ForegroundColor Red
    $confirm = Read-Host "Type 'YES' to confirm"

    if ($confirm -eq 'YES') {
        # Get all active blocks
        $blockedIPs = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT firewall_rule_name FROM blocked_ips WHERE is_active = 1
"@
        $blockedSubs = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT firewall_rule_name FROM blocked_subnets WHERE is_active = 1
"@

        foreach ($b in $blockedIPs) {
            Remove-FirewallRule -RuleName $b.firewall_rule_name
        }
        foreach ($b in $blockedSubs) {
            Remove-FirewallRule -RuleName $b.firewall_rule_name
        }

        Invoke-SqliteQuery -DataSource $DatabasePath -Query "UPDATE blocked_ips SET is_active = 0"
        Invoke-SqliteQuery -DataSource $DatabasePath -Query "UPDATE blocked_subnets SET is_active = 0"

        Write-Host "All blocks removed." -ForegroundColor Green
    } else {
        Write-Host "Cancelled." -ForegroundColor Yellow
    }
}
#endregion

#region Purge Old Data
if ($PurgeOldData) {
    Write-Host "Purging data older than $PurgeDays days..." -ForegroundColor Yellow

    $cutoff = (Get-Date).AddDays(-$PurgeDays).ToString("yyyy-MM-dd HH:mm:ss")

    # Purge failed_auth
    $count1 = Invoke-SqliteQuery -DataSource $DatabasePath -Query "SELECT COUNT(*) as cnt FROM failed_auth WHERE timestamp < '$cutoff'"
    Invoke-SqliteQuery -DataSource $DatabasePath -Query "DELETE FROM failed_auth WHERE timestamp < '$cutoff'"
    Write-Host "  Purged $($count1.cnt) failed auth records" -ForegroundColor Gray

    # Purge successful_auth
    $count2 = Invoke-SqliteQuery -DataSource $DatabasePath -Query "SELECT COUNT(*) as cnt FROM successful_auth WHERE timestamp < '$cutoff'"
    Invoke-SqliteQuery -DataSource $DatabasePath -Query "DELETE FROM successful_auth WHERE timestamp < '$cutoff'"
    Write-Host "  Purged $($count2.cnt) successful auth records" -ForegroundColor Gray

    # Purge inactive blocks
    $count3 = Invoke-SqliteQuery -DataSource $DatabasePath -Query "SELECT COUNT(*) as cnt FROM blocked_ips WHERE is_active = 0 AND blocked_at < '$cutoff'"
    Invoke-SqliteQuery -DataSource $DatabasePath -Query "DELETE FROM blocked_ips WHERE is_active = 0 AND blocked_at < '$cutoff'"
    Write-Host "  Purged $($count3.cnt) inactive block records" -ForegroundColor Gray

    # Vacuum database
    Invoke-SqliteQuery -DataSource $DatabasePath -Query "VACUUM"
    Write-Host "Database vacuumed." -ForegroundColor Green
}
#endregion

#region Show Permanent Blocks
if ($ShowPermanentBlocks) {
    Write-Host "`n=== IPs QUALIFYING FOR PERMANENT BLOCK (6+ offenses) ===" -ForegroundColor Yellow

    # IPs with 6+ offenses
    $permIPs = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT ip_address, offense_count, failure_count, block_reason, blocked_at
FROM blocked_ips
WHERE offense_count >= 6
ORDER BY offense_count DESC, failure_count DESC
"@

    if ($permIPs) {
        Write-Host "`nPermanent Block IPs: $($permIPs.Count)" -ForegroundColor Red
        $permIPs | Format-Table -Property @(
            @{Label="IP Address"; Expression={$_.ip_address}; Width=18},
            @{Label="Offenses"; Expression={$_.offense_count}; Width=10},
            @{Label="Failures"; Expression={$_.failure_count}; Width=10},
            @{Label="Reason"; Expression={$_.block_reason}; Width=35}
        ) -AutoSize
    } else {
        Write-Host "  No IPs with 6+ offenses found." -ForegroundColor Gray
    }

    # Subnets with 6+ offenses
    $permSubs = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT subnet, offense_count, ip_count, block_reason, blocked_at
FROM blocked_subnets
WHERE offense_count >= 6
ORDER BY offense_count DESC, ip_count DESC
"@

    if ($permSubs) {
        Write-Host "`nPermanent Block Subnets: $($permSubs.Count)" -ForegroundColor Red
        $permSubs | Format-Table -Property @(
            @{Label="Subnet"; Expression={$_.subnet}; Width=18},
            @{Label="Offenses"; Expression={$_.offense_count}; Width=10},
            @{Label="IPs"; Expression={$_.ip_count}; Width=6},
            @{Label="Reason"; Expression={$_.block_reason}; Width=35}
        ) -AutoSize
    }
}
#endregion

#region Apply Permanent Blocks
if ($ApplyPermanentBlocks) {
    Write-Host "`n=== APPLYING PERMANENT FIREWALL BLOCKS ===" -ForegroundColor Yellow

    # Get all IPs that should be permanently blocked (6+ offenses)
    $permIPs = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT ip_address, offense_count, block_reason FROM blocked_ips WHERE offense_count >= 6
"@

    $permSubs = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT subnet, offense_count, block_reason FROM blocked_subnets WHERE offense_count >= 6
"@

    $totalRules = 0
    $created = 0
    $failed = 0

    # Process IPs
    if ($permIPs) {
        Write-Host "`nCreating firewall rules for $($permIPs.Count) permanent IP blocks..." -ForegroundColor Cyan

        foreach ($block in $permIPs) {
            $totalRules++
            $ip = $block.ip_address
            $ruleName = "MailFirewall_Block_$($ip -replace '[./]', '_')_PERM"

            try {
                # Remove any existing rules for this IP
                Remove-NetFirewallRule -DisplayName "MailFirewall_Block_$($ip -replace '[./]', '_')" -ErrorAction SilentlyContinue
                Remove-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue

                # Create permanent block rule
                New-NetFirewallRule -DisplayName $ruleName `
                    -Direction Inbound `
                    -Action Block `
                    -RemoteAddress $ip `
                    -Protocol Any `
                    -Description "PERMANENT: $($block.block_reason) (Offense #$($block.offense_count))" `
                    -ErrorAction Stop | Out-Null

                # Update database
                Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
UPDATE blocked_ips SET is_active = 1, is_permanent = 1, firewall_rule_name = '$ruleName' WHERE ip_address = '$ip'
"@
                Write-Host "  [OK] $ip" -ForegroundColor Green
                $created++
            } catch {
                Write-Host "  [FAIL] $ip - $($_.Exception.Message)" -ForegroundColor Red
                $failed++
            }
        }
    }

    # Process Subnets
    if ($permSubs) {
        Write-Host "`nCreating firewall rules for $($permSubs.Count) permanent subnet blocks..." -ForegroundColor Cyan

        foreach ($block in $permSubs) {
            $totalRules++
            $subnet = $block.subnet
            $ruleName = "MailFirewall_Block_$($subnet -replace '[./]', '_')_PERM"

            try {
                # Remove any existing rules for this subnet
                Remove-NetFirewallRule -DisplayName "MailFirewall_Block_$($subnet -replace '[./]', '_')" -ErrorAction SilentlyContinue
                Remove-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue

                # Create permanent block rule
                New-NetFirewallRule -DisplayName $ruleName `
                    -Direction Inbound `
                    -Action Block `
                    -RemoteAddress $subnet `
                    -Protocol Any `
                    -Description "PERMANENT: $($block.block_reason) (Offense #$($block.offense_count))" `
                    -ErrorAction Stop | Out-Null

                # Update database
                Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
UPDATE blocked_subnets SET is_active = 1, is_permanent = 1, firewall_rule_name = '$ruleName' WHERE subnet = '$subnet'
"@
                Write-Host "  [OK] $subnet" -ForegroundColor Green
                $created++
            } catch {
                Write-Host "  [FAIL] $subnet - $($_.Exception.Message)" -ForegroundColor Red
                $failed++
            }
        }
    }

    Write-Host "`n=== SUMMARY ===" -ForegroundColor Yellow
    Write-Host "  Total rules: $totalRules" -ForegroundColor White
    Write-Host "  Created: $created" -ForegroundColor Green
    if ($failed -gt 0) {
        Write-Host "  Failed: $failed" -ForegroundColor Red
    }
    Write-Host "`nTo view rules: Get-NetFirewallRule -DisplayName 'MailFirewall_*_PERM'" -ForegroundColor Cyan
}
#endregion

if (-not ($AddWhitelist -or $RemoveWhitelist -or $BlockIP -or $UnblockIP -or $UnblockAll -or $PurgeOldData -or $ShowPermanentBlocks -or $ApplyPermanentBlocks)) {
    Write-Host @"
Mail Firewall Management Commands
==================================

Usage:
  .\MailFirewall-Manage.ps1 -AddWhitelist -IP '1.2.3.4' -Description 'Office IP'
  .\MailFirewall-Manage.ps1 -AddWhitelist -IP '10.0.0.0/8' -Description 'Internal network'
  .\MailFirewall-Manage.ps1 -RemoveWhitelist -IP '1.2.3.4'

  .\MailFirewall-Manage.ps1 -BlockIP -IP '5.6.7.8' -Description 'Spammer'
  .\MailFirewall-Manage.ps1 -BlockIP -IP '5.6.7.0/24' -Permanent -Description 'Known bad subnet'
  .\MailFirewall-Manage.ps1 -UnblockIP -IP '5.6.7.8'
  .\MailFirewall-Manage.ps1 -UnblockAll

  .\MailFirewall-Manage.ps1 -PurgeOldData -PurgeDays 30

After historical import:
  .\MailFirewall-Manage.ps1 -ShowPermanentBlocks      # View IPs with 6+ offenses
  .\MailFirewall-Manage.ps1 -ApplyPermanentBlocks     # Create firewall rules for them
"@ -ForegroundColor Cyan
}
