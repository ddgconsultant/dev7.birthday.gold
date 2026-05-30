#Requires -Modules PSSQLite
<#
.SYNOPSIS
    Analyze historical failed_auth data and populate blocked_ips table
.DESCRIPTION
    After historical log import, this script analyzes failed_auth data
    and creates blocked_ips entries with appropriate offense counts.
.EXAMPLE
    .\MailFirewall-AnalyzeHistory.ps1 -MinAttempts 10
    .\MailFirewall-AnalyzeHistory.ps1 -MinAttempts 50 -ApplyBlocks
#>

param(
    [string]$DatabasePath = "C:\MailServer_AccessManagement\firewall_tracker.db",
    [int]$MinAttempts = 10,          # Minimum failed attempts to consider
    [int]$Tier1Threshold = 10,       # Offense 1: 10+ attempts
    [int]$Tier2Threshold = 50,       # Offense 2: 50+ attempts
    [int]$Tier3Threshold = 200,      # Offense 3: 200+ attempts
    [int]$Tier4Threshold = 500,      # Offense 4: 500+ attempts
    [int]$Tier5Threshold = 1000,     # Offense 5: 1000+ attempts
    [int]$Tier6Threshold = 5000,     # Offense 6+: 5000+ attempts (PERMANENT)
    [switch]$ApplyBlocks,            # Actually create firewall rules
    [switch]$AnalyzeSubnets          # Also analyze and block bad subnets
)

Import-Module PSSQLite

if (-not (Test-Path $DatabasePath)) {
    Write-Host "Database not found: $DatabasePath" -ForegroundColor Red
    exit 1
}

Write-Host "`n=== MAIL FIREWALL HISTORICAL ANALYSIS ===" -ForegroundColor Cyan
Write-Host "Database: $DatabasePath" -ForegroundColor Gray
Write-Host "Minimum attempts threshold: $MinAttempts" -ForegroundColor Gray
Write-Host ""

#region Analyze Individual IPs

Write-Host "=== ANALYZING INDIVIDUAL IPs ===" -ForegroundColor Yellow

# Get all IPs with failed attempts above threshold
$badIPs = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT ip_address, attempt_count, first_seen, last_seen, usernames
FROM failed_auth
WHERE attempt_count >= $MinAttempts
ORDER BY attempt_count DESC
"@

Write-Host "Found $($badIPs.Count) IPs with $MinAttempts+ failed attempts`n" -ForegroundColor White

# Categorize by offense level
$tier6 = @($badIPs | Where-Object { $_.attempt_count -ge $Tier6Threshold })
$tier5 = @($badIPs | Where-Object { $_.attempt_count -ge $Tier5Threshold -and $_.attempt_count -lt $Tier6Threshold })
$tier4 = @($badIPs | Where-Object { $_.attempt_count -ge $Tier4Threshold -and $_.attempt_count -lt $Tier5Threshold })
$tier3 = @($badIPs | Where-Object { $_.attempt_count -ge $Tier3Threshold -and $_.attempt_count -lt $Tier4Threshold })
$tier2 = @($badIPs | Where-Object { $_.attempt_count -ge $Tier2Threshold -and $_.attempt_count -lt $Tier3Threshold })
$tier1 = @($badIPs | Where-Object { $_.attempt_count -ge $Tier1Threshold -and $_.attempt_count -lt $Tier2Threshold })

Write-Host "Tier 6 (PERMANENT - $Tier6Threshold+ attempts): $($tier6.Count) IPs" -ForegroundColor Red
Write-Host "Tier 5 ($Tier5Threshold-$($Tier6Threshold-1) attempts): $($tier5.Count) IPs" -ForegroundColor Magenta
Write-Host "Tier 4 ($Tier4Threshold-$($Tier5Threshold-1) attempts): $($tier4.Count) IPs" -ForegroundColor Yellow
Write-Host "Tier 3 ($Tier3Threshold-$($Tier4Threshold-1) attempts): $($tier3.Count) IPs" -ForegroundColor Yellow
Write-Host "Tier 2 ($Tier2Threshold-$($Tier3Threshold-1) attempts): $($tier2.Count) IPs" -ForegroundColor White
Write-Host "Tier 1 ($Tier1Threshold-$($Tier2Threshold-1) attempts): $($tier1.Count) IPs" -ForegroundColor Gray

# Show top 20 worst offenders
Write-Host "`n=== TOP 20 WORST OFFENDERS ===" -ForegroundColor Red
$badIPs | Select-Object -First 20 | ForEach-Object {
    $offense = if ($_.attempt_count -ge $Tier6Threshold) { "6+ (PERM)" }
               elseif ($_.attempt_count -ge $Tier5Threshold) { "5" }
               elseif ($_.attempt_count -ge $Tier4Threshold) { "4" }
               elseif ($_.attempt_count -ge $Tier3Threshold) { "3" }
               elseif ($_.attempt_count -ge $Tier2Threshold) { "2" }
               else { "1" }

    $color = if ($_.attempt_count -ge $Tier6Threshold) { "Red" }
             elseif ($_.attempt_count -ge $Tier4Threshold) { "Yellow" }
             else { "White" }

    Write-Host ("{0,-18} {1,10:N0} attempts  Offense: {2}" -f $_.ip_address, $_.attempt_count, $offense) -ForegroundColor $color
}

#endregion

#region Analyze Subnets

if ($AnalyzeSubnets) {
    Write-Host "`n=== ANALYZING SUBNETS ===" -ForegroundColor Yellow

    # Group IPs by /24 subnet
    $subnetData = @{}
    foreach ($ip in $badIPs) {
        $parts = $ip.ip_address -split '\.'
        if ($parts.Count -eq 4) {
            $subnet = "$($parts[0]).$($parts[1]).$($parts[2]).0/24"
            if (-not $subnetData[$subnet]) {
                $subnetData[$subnet] = @{
                    IPs = @()
                    TotalAttempts = 0
                }
            }
            $subnetData[$subnet].IPs += $ip.ip_address
            $subnetData[$subnet].TotalAttempts += $ip.attempt_count
        }
    }

    # Find subnets with multiple bad IPs
    $badSubnets = $subnetData.GetEnumerator() |
        Where-Object { $_.Value.IPs.Count -ge 3 } |
        Sort-Object { $_.Value.TotalAttempts } -Descending

    Write-Host "Found $($badSubnets.Count) subnets with 3+ malicious IPs`n" -ForegroundColor White

    Write-Host "=== TOP BAD SUBNETS ===" -ForegroundColor Red
    $badSubnets | Select-Object -First 15 | ForEach-Object {
        $ipCount = $_.Value.IPs.Count
        $totalAttempts = $_.Value.TotalAttempts
        $offense = if ($ipCount -ge 10) { "6+ (PERM)" }
                   elseif ($ipCount -ge 7) { "5" }
                   elseif ($ipCount -ge 5) { "4" }
                   else { "3" }

        Write-Host ("{0,-18} {1,3} IPs  {2,10:N0} total attempts  Offense: {3}" -f $_.Key, $ipCount, $totalAttempts, $offense) -ForegroundColor Red
    }
}

#endregion

#region Populate blocked_ips table

if ($ApplyBlocks) {
    Write-Host "`n=== POPULATING BLOCKED_IPS TABLE ===" -ForegroundColor Cyan

    $inserted = 0
    $updated = 0
    $errors = 0

    foreach ($ip in $badIPs) {
        # Calculate offense level based on attempt count
        $offense = if ($ip.attempt_count -ge $Tier6Threshold) { 6 }
                   elseif ($ip.attempt_count -ge $Tier5Threshold) { 5 }
                   elseif ($ip.attempt_count -ge $Tier4Threshold) { 4 }
                   elseif ($ip.attempt_count -ge $Tier3Threshold) { 3 }
                   elseif ($ip.attempt_count -ge $Tier2Threshold) { 2 }
                   else { 1 }

        $isPermanent = if ($offense -ge 6) { 1 } else { 0 }
        $reason = "Historical: $($ip.attempt_count) failed attempts ($($ip.first_seen) to $($ip.last_seen))"

        try {
            # Check if already exists
            $exists = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT id, offense_count FROM blocked_ips WHERE ip_address = '$($ip.ip_address)'
"@

            if ($exists) {
                # Update if new offense is higher
                if ($offense -gt $exists.offense_count) {
                    Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
UPDATE blocked_ips SET
    offense_count = $offense,
    failure_count = $($ip.attempt_count),
    block_reason = '$($reason -replace "'", "''")',
    is_permanent = $isPermanent,
    is_active = 0
WHERE ip_address = '$($ip.ip_address)'
"@
                    $updated++
                }
            } else {
                # Insert new record
                Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
INSERT INTO blocked_ips (ip_address, block_reason, failure_count, offense_count, blocked_at, is_permanent, is_active)
VALUES ('$($ip.ip_address)', '$($reason -replace "'", "''")', $($ip.attempt_count), $offense, datetime('now'), $isPermanent, 0)
"@
                $inserted++
            }
        } catch {
            $errors++
            if ($errors -le 5) {
                Write-Host "Error processing $($ip.ip_address): $($_.Exception.Message)" -ForegroundColor Red
            }
        }

        # Progress indicator
        if (($inserted + $updated) % 500 -eq 0) {
            Write-Host "." -NoNewline -ForegroundColor Green
        }
    }

    Write-Host ""
    Write-Host "`nResults:" -ForegroundColor Green
    Write-Host "  Inserted: $inserted" -ForegroundColor White
    Write-Host "  Updated: $updated" -ForegroundColor White
    if ($errors -gt 0) {
        Write-Host "  Errors: $errors" -ForegroundColor Red
    }

    # Now check permanent blocks
    Write-Host "`n=== PERMANENT BLOCK SUMMARY ===" -ForegroundColor Yellow
    $permCount = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT COUNT(*) as cnt FROM blocked_ips WHERE offense_count >= 6
"@
    Write-Host "IPs qualifying for permanent block (offense 6+): $($permCount.cnt)" -ForegroundColor Red

    Write-Host "`nNext step: Run .\MailFirewall-Manage.ps1 -ApplyPermanentBlocks" -ForegroundColor Cyan

    #region Also handle subnets if requested
    if ($AnalyzeSubnets -and $badSubnets) {
        Write-Host "`n=== POPULATING BLOCKED_SUBNETS TABLE ===" -ForegroundColor Cyan

        $subInserted = 0
        foreach ($sub in $badSubnets) {
            $ipCount = $sub.Value.IPs.Count
            $totalAttempts = $sub.Value.TotalAttempts

            $offense = if ($ipCount -ge 10) { 6 }
                       elseif ($ipCount -ge 7) { 5 }
                       elseif ($ipCount -ge 5) { 4 }
                       else { 3 }

            $isPermanent = if ($offense -ge 6) { 1 } else { 0 }
            $reason = "Historical: $ipCount malicious IPs, $totalAttempts total attempts"

            try {
                $exists = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT id FROM blocked_subnets WHERE subnet = '$($sub.Key)'
"@
                if (-not $exists) {
                    Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
INSERT INTO blocked_subnets (subnet, block_reason, ip_count, offense_count, blocked_at, is_permanent, is_active)
VALUES ('$($sub.Key)', '$($reason -replace "'", "''")', $ipCount, $offense, datetime('now'), $isPermanent, 0)
"@
                    $subInserted++
                }
            } catch {
                Write-Host "Error processing subnet $($sub.Key): $($_.Exception.Message)" -ForegroundColor Red
            }
        }

        Write-Host "Subnets inserted: $subInserted" -ForegroundColor White
    }
    #endregion

} else {
    Write-Host "`n=== DRY RUN - No changes made ===" -ForegroundColor Yellow
    Write-Host "To populate blocked_ips table, run with -ApplyBlocks flag:" -ForegroundColor White
    Write-Host "  .\MailFirewall-AnalyzeHistory.ps1 -ApplyBlocks" -ForegroundColor Cyan
    Write-Host "  .\MailFirewall-AnalyzeHistory.ps1 -ApplyBlocks -AnalyzeSubnets" -ForegroundColor Cyan
}

#endregion

Write-Host "`n=== ANALYSIS COMPLETE ===" -ForegroundColor Green
