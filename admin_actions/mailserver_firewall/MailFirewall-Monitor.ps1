#Requires -Modules PSSQLite
<#
.SYNOPSIS
    Mail Server Firewall - Log Monitor and Blocker
.DESCRIPTION
    Parses ArgoSoft mail server logs, detects failed auth attempts,
    and automatically blocks malicious IPs via Windows Firewall.
.NOTES
    Run as scheduled task every 5 minutes, or continuously with -Continuous flag.
#>

param(
    [string]$DatabasePath = "C:\MailServer_AccessManagement\firewall_tracker.db",
    [string]$LogPath = "C:\ProgramData\ArGoSoft\MailServer.Net\_logs",
    [string]$LogFile,  # Specific log file to process (e.g., "ms251208.log")
    [string]$LogDirectory,  # Process all logs in a directory (e.g., "2025" or full path)
    [int]$FailureThreshold = 3,
    [int]$FailureWindowMinutes = 30,
    [int]$SubnetThreshold = 3,
    [int]$BlockDurationHours = 24,
    [switch]$Continuous,
    [int]$PollIntervalSeconds = 60,
    [switch]$DryRun,
    [switch]$ImportOnly  # Just import data, don't block (for historical logs)
)

# Import SQLite module
Import-Module PSSQLite

#region Helper Functions

function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $color = switch ($Level) {
        "ERROR" { "Red" }
        "WARN" { "Yellow" }
        "SUCCESS" { "Green" }
        "BLOCK" { "Magenta" }
        default { "White" }
    }
    Write-Host "[$timestamp] [$Level] $Message" -ForegroundColor $color
}

function Get-SubnetFromIP {
    param([string]$IP)
    $parts = $IP -split '\.'
    if ($parts.Count -eq 4) {
        return "$($parts[0]).$($parts[1]).$($parts[2]).0/24"
    }
    return $null
}

function Test-IPInWhitelist {
    param([string]$IP)

    # Check exact IP match
    $exactQuery = "SELECT COUNT(*) as cnt FROM whitelist WHERE ip_or_subnet = @ip AND is_subnet = 0"
    $exact = Invoke-SqliteQuery -DataSource $DatabasePath -Query $exactQuery -SqlParameters @{ ip = $IP }
    if ($exact.cnt -gt 0) { return $true }

    # Check auto-whitelist (successful logins)
    $autoQuery = "SELECT COUNT(*) as cnt FROM auto_whitelist WHERE ip_address = @ip"
    $auto = Invoke-SqliteQuery -DataSource $DatabasePath -Query $autoQuery -SqlParameters @{ ip = $IP }
    if ($auto.cnt -gt 0) { return $true }

    # Check subnet matches
    $parts = $IP -split '\.'
    if ($parts.Count -ne 4) { return $false }

    $subnetsQuery = "SELECT ip_or_subnet FROM whitelist WHERE is_subnet = 1"
    $subnets = Invoke-SqliteQuery -DataSource $DatabasePath -Query $subnetsQuery

    foreach ($subnet in $subnets) {
        $subnetStr = $subnet.ip_or_subnet
        if (Test-IPInSubnet -IP $IP -Subnet $subnetStr) {
            return $true
        }
    }

    return $false
}

function Test-IPInSubnet {
    param([string]$IP, [string]$Subnet)

    try {
        if ($Subnet -match '^(\d+\.\d+\.\d+\.\d+)/(\d+)$') {
            $subnetIP = $Matches[1]
            $cidr = [int]$Matches[2]

            $ipBytes = [System.Net.IPAddress]::Parse($IP).GetAddressBytes()
            $subnetBytes = [System.Net.IPAddress]::Parse($subnetIP).GetAddressBytes()

            # Convert to UInt32 for comparison
            [Array]::Reverse($ipBytes)
            [Array]::Reverse($subnetBytes)

            $ipInt = [BitConverter]::ToUInt32($ipBytes, 0)
            $subnetInt = [BitConverter]::ToUInt32($subnetBytes, 0)

            $mask = [uint32]::MaxValue -shl (32 - $cidr)

            return (($ipInt -band $mask) -eq ($subnetInt -band $mask))
        }
    } catch {
        return $false
    }
    return $false
}

function Test-IPBlocked {
    param([string]$IP)

    $query = "SELECT COUNT(*) as cnt FROM blocked_ips WHERE ip_address = @ip AND is_active = 1"
    $result = Invoke-SqliteQuery -DataSource $DatabasePath -Query $query -SqlParameters @{ ip = $IP }
    return $result.cnt -gt 0
}

function Add-FirewallBlock {
    param(
        [string]$IP,
        [string]$Reason,
        [int]$FailureCount,
        [bool]$IsSubnet = $false
    )

    # Look up previous offense count for escalation
    $offenseCount = 1
    $isPermanent = 0

    if ($IsSubnet) {
        $prevQuery = "SELECT offense_count FROM blocked_subnets WHERE subnet = @ip"
    } else {
        $prevQuery = "SELECT offense_count FROM blocked_ips WHERE ip_address = @ip"
    }
    $prevBlock = Invoke-SqliteQuery -DataSource $DatabasePath -Query $prevQuery -SqlParameters @{ ip = $IP }

    if ($prevBlock -and $prevBlock.offense_count) {
        $offenseCount = $prevBlock.offense_count + 1
    }

    # Get block duration from escalation config (cap at offense 6 for permanent)
    $escLevel = [math]::Min($offenseCount, 6)
    $escQuery = "SELECT block_hours, description FROM escalation_config WHERE offense_number = @level"
    $escalation = Invoke-SqliteQuery -DataSource $DatabasePath -Query $escQuery -SqlParameters @{ level = $escLevel }

    if ($escalation) {
        $blockHours = $escalation.block_hours
        $escDesc = $escalation.description
    } else {
        # Fallback if escalation config missing
        $blockHours = $BlockDurationHours
        $escDesc = "Default $BlockDurationHours hours"
    }

    # Calculate expiration (-1 means permanent)
    if ($blockHours -eq -1) {
        $expiresAt = $null
        $isPermanent = 1
        $expiresText = "PERMANENT"
    } else {
        $expiresAt = (Get-Date).AddHours($blockHours).ToString("yyyy-MM-dd HH:mm:ss")
        $expiresText = "$blockHours hours"
    }

    # Build rule name with PERM suffix for permanent blocks
    $ruleBase = "MailFirewall_Block_$($IP -replace '[./]', '_')"
    $ruleName = if ($isPermanent) { "${ruleBase}_PERM" } else { $ruleBase }

    if ($DryRun) {
        Write-Log "DRY RUN: Would block $IP (Offense #$offenseCount - $expiresText)" -Level "BLOCK"
        return
    }

    # Create Windows Firewall rule
    try {
        # Remove existing rules (both permanent and non-permanent versions)
        Remove-NetFirewallRule -DisplayName $ruleBase -ErrorAction SilentlyContinue
        Remove-NetFirewallRule -DisplayName "${ruleBase}_PERM" -ErrorAction SilentlyContinue

        # Create new blocking rule
        New-NetFirewallRule -DisplayName $ruleName `
            -Direction Inbound `
            -Action Block `
            -RemoteAddress $IP `
            -Protocol Any `
            -Description "Blocked by MailFirewall: $Reason (Offense #$offenseCount)" `
            -ErrorAction Stop | Out-Null

        Write-Log "BLOCKED: $IP - $Reason (Offense #$offenseCount - $expiresText)" -Level "BLOCK"

        # Record in database with offense tracking
        if ($IsSubnet) {
            # Check if record exists
            $existsQuery = "SELECT id FROM blocked_subnets WHERE subnet = @ip"
            $exists = Invoke-SqliteQuery -DataSource $DatabasePath -Query $existsQuery -SqlParameters @{ ip = $IP }

            if ($exists) {
                $updateQuery = @"
UPDATE blocked_subnets SET
    block_reason = @reason,
    ip_count = @count,
    offense_count = @offense,
    blocked_at = datetime('now'),
    expires_at = @expires,
    is_permanent = @perm,
    is_active = 1,
    firewall_rule_name = @rule
WHERE subnet = @ip
"@
                Invoke-SqliteQuery -DataSource $DatabasePath -Query $updateQuery -SqlParameters @{
                    ip = $IP
                    reason = $Reason
                    count = $FailureCount
                    offense = $offenseCount
                    expires = $expiresAt
                    perm = $isPermanent
                    rule = $ruleName
                }
            } else {
                $insertQuery = @"
INSERT INTO blocked_subnets (subnet, block_reason, ip_count, offense_count, blocked_at, expires_at, is_permanent, is_active, firewall_rule_name)
VALUES (@ip, @reason, @count, @offense, datetime('now'), @expires, @perm, 1, @rule)
"@
                Invoke-SqliteQuery -DataSource $DatabasePath -Query $insertQuery -SqlParameters @{
                    ip = $IP
                    reason = $Reason
                    count = $FailureCount
                    offense = $offenseCount
                    expires = $expiresAt
                    perm = $isPermanent
                    rule = $ruleName
                }
            }
        } else {
            # Check if record exists
            $existsQuery = "SELECT id FROM blocked_ips WHERE ip_address = @ip"
            $exists = Invoke-SqliteQuery -DataSource $DatabasePath -Query $existsQuery -SqlParameters @{ ip = $IP }

            if ($exists) {
                $updateQuery = @"
UPDATE blocked_ips SET
    block_reason = @reason,
    failure_count = @count,
    offense_count = @offense,
    blocked_at = datetime('now'),
    expires_at = @expires,
    is_permanent = @perm,
    is_active = 1,
    firewall_rule_name = @rule
WHERE ip_address = @ip
"@
                Invoke-SqliteQuery -DataSource $DatabasePath -Query $updateQuery -SqlParameters @{
                    ip = $IP
                    reason = $Reason
                    count = $FailureCount
                    offense = $offenseCount
                    expires = $expiresAt
                    perm = $isPermanent
                    rule = $ruleName
                }
            } else {
                $insertQuery = @"
INSERT INTO blocked_ips (ip_address, block_reason, failure_count, offense_count, blocked_at, expires_at, is_permanent, is_active, firewall_rule_name)
VALUES (@ip, @reason, @count, @offense, datetime('now'), @expires, @perm, 1, @rule)
"@
                Invoke-SqliteQuery -DataSource $DatabasePath -Query $insertQuery -SqlParameters @{
                    ip = $IP
                    reason = $Reason
                    count = $FailureCount
                    offense = $offenseCount
                    expires = $expiresAt
                    perm = $isPermanent
                    rule = $ruleName
                }
            }
        }

    } catch {
        Write-Log "Failed to create firewall rule for $IP : $($_.Exception.Message)" -Level "ERROR"
    }
}

function Remove-ExpiredBlocks {
    # Get expired blocks
    $expiredIPs = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT ip_address, firewall_rule_name FROM blocked_ips
WHERE is_active = 1 AND is_permanent = 0 AND expires_at < datetime('now')
"@

    $expiredSubnets = Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
SELECT subnet, firewall_rule_name FROM blocked_subnets
WHERE is_active = 1 AND is_permanent = 0 AND expires_at < datetime('now')
"@

    foreach ($block in $expiredIPs) {
        if ($block.firewall_rule_name -and -not $DryRun) {
            Remove-NetFirewallRule -DisplayName $block.firewall_rule_name -ErrorAction SilentlyContinue
        }
        Write-Log "Unblocked expired IP: $($block.ip_address)" -Level "SUCCESS"
    }

    foreach ($block in $expiredSubnets) {
        if ($block.firewall_rule_name -and -not $DryRun) {
            Remove-NetFirewallRule -DisplayName $block.firewall_rule_name -ErrorAction SilentlyContinue
        }
        Write-Log "Unblocked expired subnet: $($block.subnet)" -Level "SUCCESS"
    }

    # Mark as inactive in database
    if (-not $DryRun) {
        Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
UPDATE blocked_ips SET is_active = 0 WHERE is_active = 1 AND is_permanent = 0 AND expires_at < datetime('now')
"@
        Invoke-SqliteQuery -DataSource $DatabasePath -Query @"
UPDATE blocked_subnets SET is_active = 0 WHERE is_active = 1 AND is_permanent = 0 AND expires_at < datetime('now')
"@
    }
}

function Process-LogLine {
    param([string]$Line, [hashtable]$SessionCache)

    # Pattern: [PROTOCOL SESSION_ID DATE TIME] Message
    if ($Line -match '^\[(SMTP|SMT2|POP3|IMAP)\s+(\d+)\s+(\d{2}-\d{2}-\d{2})\s+(\d{2}:\d{2}:\d{2})\]\s+(.+)$') {
        $protocol = $Matches[1]
        $sessionId = $Matches[2]
        $dateStr = $Matches[3]
        $timeStr = $Matches[4]
        $message = $Matches[5]

        # Convert date (DD-MM-YY to YYYY-MM-DD)
        $dateParts = $dateStr -split '-'
        $fullDate = "20$($dateParts[2])-$($dateParts[1])-$($dateParts[0]) $timeStr"

        # Track connection source IP
        if ($message -match 'connection on .+ from (\d+\.\d+\.\d+\.\d+):') {
            $ip = $Matches[1]
            $SessionCache[$sessionId] = @{
                IP = $ip
                Protocol = $protocol
                Timestamp = $fullDate
            }
        }

        # Detect failed authentication
        if ($message -match 'Invalid password used: (.+)') {
            $password = $Matches[1]
            $session = $SessionCache[$sessionId]

            if ($session -and $session.IP) {
                return @{
                    Type = "FailedAuth"
                    IP = $session.IP
                    Protocol = $protocol
                    SessionId = $sessionId
                    Timestamp = $fullDate
                    Password = $password
                }
            }
        }

        # Detect successful authentication
        if ($message -match 'User (.+) has been logged in') {
            $username = $Matches[1]
            $session = $SessionCache[$sessionId]

            if ($session -and $session.IP) {
                return @{
                    Type = "SuccessAuth"
                    IP = $session.IP
                    Protocol = $protocol
                    SessionId = $sessionId
                    Timestamp = $fullDate
                    Username = $username
                }
            }
        }

        # Track username for failed auth context
        if ($message -match 'USER\s+(\S+)') {
            if ($SessionCache[$sessionId]) {
                $SessionCache[$sessionId].Username = $Matches[1]
            }
        }
    }

    return $null
}

function Check-AndBlockIP {
    param([string]$IP)

    # Skip if whitelisted
    if (Test-IPInWhitelist -IP $IP) {
        return $false
    }

    # Skip if already blocked
    if (Test-IPBlocked -IP $IP) {
        return $false
    }

    # Check attempt count and recency
    $windowStart = (Get-Date).AddMinutes(-$FailureWindowMinutes).ToString("yyyy-MM-dd HH:mm:ss")
    $countQuery = @"
SELECT attempt_count as cnt FROM failed_auth
WHERE ip_address = @ip AND last_seen > @window
"@
    $result = Invoke-SqliteQuery -DataSource $DatabasePath -Query $countQuery -SqlParameters @{
        ip = $IP
        window = $windowStart
    }

    if ($result -and $result.cnt -ge $FailureThreshold) {
        Add-FirewallBlock -IP $IP -Reason "Failed auth $($result.cnt)x in $FailureWindowMinutes min" -FailureCount $result.cnt
        return $true
    }
    return $false
}

function Check-AndBlockSubnet {
    param([string]$Subnet)

    # Count unique IPs from this subnet that are blocked
    $subnetPrefix = $Subnet -replace '\.0/24$', ''

    $countQuery = @"
SELECT COUNT(DISTINCT ip_address) as cnt FROM blocked_ips
WHERE ip_address LIKE @prefix AND is_active = 1
"@
    $result = Invoke-SqliteQuery -DataSource $DatabasePath -Query $countQuery -SqlParameters @{
        prefix = "$subnetPrefix.%"
    }

    if ($result.cnt -ge $SubnetThreshold) {
        # Check if subnet already blocked
        $existsQuery = "SELECT COUNT(*) as cnt FROM blocked_subnets WHERE subnet = @subnet AND is_active = 1"
        $exists = Invoke-SqliteQuery -DataSource $DatabasePath -Query $existsQuery -SqlParameters @{ subnet = $Subnet }

        if ($exists.cnt -eq 0) {
            Add-FirewallBlock -IP $Subnet -Reason "Subnet blocked: $($result.cnt) malicious IPs detected" -FailureCount $result.cnt -IsSubnet $true
            return $true
        }
    }
    return $false
}

function Process-LogFile {
    param([string]$LogFile)

    $fileName = Split-Path -Leaf $LogFile

    # Get last processed position
    $stateQuery = "SELECT last_position FROM processing_state WHERE log_file = @file"
    $state = Invoke-SqliteQuery -DataSource $DatabasePath -Query $stateQuery -SqlParameters @{ file = $fileName }
    $lastPosition = if ($state) { $state.last_position } else { 0 }

    # Read log file
    if (-not (Test-Path $LogFile)) {
        Write-Log "Log file not found: $LogFile" -Level "WARN"
        return
    }

    $fileInfo = Get-Item $LogFile
    if ($fileInfo.Length -le $lastPosition) {
        # File may have rotated, start from beginning
        if ($fileInfo.Length -lt $lastPosition) {
            $lastPosition = 0
        } else {
            return  # No new content
        }
    }

    $totalBytes = $fileInfo.Length
    $bytesToProcess = $totalBytes - $lastPosition
    $totalMB = [math]::Round($bytesToProcess / 1MB, 2)
    Write-Log "Processing $fileName from position $lastPosition ($totalMB MB to process)" -Level "INFO"

    $sessionCache = @{}
    $newFailures = 0
    $newSuccesses = 0
    $newBlocks = 0
    $newPosition = $lastPosition
    $linesProcessed = 0
    $lastProgressPct = 0
    $chunkFailures = 0
    $chunkBlocks = 0

    # Read file content from last position
    $stream = [System.IO.File]::Open($LogFile, [System.IO.FileMode]::Open, [System.IO.FileAccess]::Read, [System.IO.FileShare]::ReadWrite)
    $reader = New-Object System.IO.StreamReader($stream)
    $reader.BaseStream.Seek($lastPosition, [System.IO.SeekOrigin]::Begin) | Out-Null

    while (-not $reader.EndOfStream) {
        $line = $reader.ReadLine()
        $newPosition = $reader.BaseStream.Position
        $linesProcessed++

        # Progress indicator every 2%
        # White = normal, Yellow = failures detected, Red = blocks issued
        # "|" marker at every 20%
        if ($bytesToProcess -gt 0) {
            $currentPct = [math]::Floor((($newPosition - $lastPosition) / $bytesToProcess) * 100)
            if ($currentPct -ge ($lastProgressPct + 2)) {
                # Determine color based on chunk activity
                # Green = normal, Yellow = failures detected, Red = blocks issued
                $dotColor = if ($chunkBlocks -gt 0) { "Red" } elseif ($chunkFailures -gt 0) { "Yellow" } else { "Green" }

                # Use "|" at 20% intervals, "." otherwise
                if ($currentPct % 20 -eq 0 -and $currentPct -gt 0) {
                    Write-Host "|" -NoNewline -ForegroundColor $dotColor
                } else {
                    Write-Host "." -NoNewline -ForegroundColor $dotColor
                }
                $chunkFailures = 0
                $chunkBlocks = 0
                $lastProgressPct = $currentPct
            }
        }

        $event = Process-LogLine -Line $line -SessionCache $sessionCache

        if ($event) {
            switch ($event.Type) {
                "FailedAuth" {
                    # Skip whitelisted IPs
                    if (-not (Test-IPInWhitelist -IP $event.IP)) {
                        # Record failed auth (aggregated by IP)
                        $username = $sessionCache[$event.SessionId].Username
                        $existsQuery = "SELECT id, usernames FROM failed_auth WHERE ip_address = @ip"
                        $existing = Invoke-SqliteQuery -DataSource $DatabasePath -Query $existsQuery -SqlParameters @{ ip = $event.IP }

                        if ($existing) {
                            # Update existing record
                            $newUsernames = $existing.usernames
                            if ($username -and $newUsernames -notlike "*$username*") {
                                $newUsernames = "$newUsernames,$username"
                            }
                            $updateQuery = "UPDATE failed_auth SET last_seen = @ts, attempt_count = attempt_count + 1, usernames = @users WHERE ip_address = @ip"
                            Invoke-SqliteQuery -DataSource $DatabasePath -Query $updateQuery -SqlParameters @{
                                ip = $event.IP
                                ts = $event.Timestamp
                                users = $newUsernames
                            }
                        } else {
                            # Insert new record
                            $insertQuery = "INSERT INTO failed_auth (ip_address, first_seen, last_seen, attempt_count, usernames) VALUES (@ip, @ts, @ts, 1, @user)"
                            Invoke-SqliteQuery -DataSource $DatabasePath -Query $insertQuery -SqlParameters @{
                                ip = $event.IP
                                ts = $event.Timestamp
                                user = $username
                            }
                        }
                        $newFailures++
                        $chunkFailures++

                        # Check if IP should be blocked (skip in import-only mode)
                        if (-not $ImportOnly) {
                            $blocked = Check-AndBlockIP -IP $event.IP
                            if ($blocked) { $newBlocks++; $chunkBlocks++ }

                            # Check if subnet should be blocked
                            $subnet = Get-SubnetFromIP -IP $event.IP
                            if ($subnet) {
                                $subnetBlocked = Check-AndBlockSubnet -Subnet $subnet
                                if ($subnetBlocked) { $newBlocks++; $chunkBlocks++ }
                            }
                        }
                    }
                }
                "SuccessAuth" {
                    # Record successful auth (aggregated by IP)
                    $existsQuery = "SELECT id, usernames FROM successful_auth WHERE ip_address = @ip"
                    $existing = Invoke-SqliteQuery -DataSource $DatabasePath -Query $existsQuery -SqlParameters @{ ip = $event.IP }

                    if ($existing) {
                        # Update existing record
                        $newUsernames = $existing.usernames
                        if ($event.Username -and $newUsernames -notlike "*$($event.Username)*") {
                            $newUsernames = "$newUsernames,$($event.Username)"
                        }
                        $updateQuery = "UPDATE successful_auth SET last_seen = @ts, success_count = success_count + 1, usernames = @users WHERE ip_address = @ip"
                        Invoke-SqliteQuery -DataSource $DatabasePath -Query $updateQuery -SqlParameters @{
                            ip = $event.IP
                            ts = $event.Timestamp
                            users = $newUsernames
                        }
                    } else {
                        # Insert new record
                        $insertQuery = "INSERT INTO successful_auth (ip_address, first_seen, last_seen, success_count, usernames) VALUES (@ip, @ts, @ts, 1, @user)"
                        Invoke-SqliteQuery -DataSource $DatabasePath -Query $insertQuery -SqlParameters @{
                            ip = $event.IP
                            ts = $event.Timestamp
                            user = $event.Username
                        }
                    }

                    # Also update auto_whitelist
                    $existsAutoQuery = "SELECT id, usernames FROM auto_whitelist WHERE ip_address = @ip"
                    $existingAuto = Invoke-SqliteQuery -DataSource $DatabasePath -Query $existsAutoQuery -SqlParameters @{ ip = $event.IP }

                    if ($existingAuto) {
                        $newUsernames = $existingAuto.usernames
                        if ($event.Username -and $newUsernames -notlike "*$($event.Username)*") {
                            $newUsernames = "$newUsernames,$($event.Username)"
                        }
                        $updateAutoQuery = "UPDATE auto_whitelist SET last_success = @ts, success_count = success_count + 1, usernames = @users WHERE ip_address = @ip"
                        Invoke-SqliteQuery -DataSource $DatabasePath -Query $updateAutoQuery -SqlParameters @{
                            ip = $event.IP
                            ts = $event.Timestamp
                            users = $newUsernames
                        }
                    } else {
                        $insertAutoQuery = "INSERT INTO auto_whitelist (ip_address, first_success, last_success, success_count, usernames) VALUES (@ip, @ts, @ts, 1, @user)"
                        Invoke-SqliteQuery -DataSource $DatabasePath -Query $insertAutoQuery -SqlParameters @{
                            ip = $event.IP
                            ts = $event.Timestamp
                            user = $event.Username
                        }
                    }
                    $newSuccesses++
                }
            }
        }
    }

    $reader.Close()
    $stream.Close()

    # Update processing state (check if exists first)
    $stateExists = Invoke-SqliteQuery -DataSource $DatabasePath -Query "SELECT id FROM processing_state WHERE id = 1"

    if ($stateExists) {
        $updateStateQuery = "UPDATE processing_state SET log_file = @file, last_position = @pos, last_processed = datetime('now') WHERE id = 1"
    } else {
        $updateStateQuery = "INSERT INTO processing_state (id, log_file, last_position, last_processed) VALUES (1, @file, @pos, datetime('now'))"
    }
    Invoke-SqliteQuery -DataSource $DatabasePath -Query $updateStateQuery -SqlParameters @{
        file = $fileName
        pos = $newPosition
    }

    # Newline after progress dots
    if ($bytesToProcess -gt 0) {
        Write-Host ""
    }

    $blockMsg = if ($newBlocks -gt 0) { ", $newBlocks IPs blocked" } else { "" }
    Write-Log "Completed: $linesProcessed lines, $newFailures failed auths, $newSuccesses successful logins$blockMsg" -Level "SUCCESS"
}

#endregion

#region Main

Write-Log "Mail Firewall Monitor starting..." -Level "INFO"
Write-Log "Database: $DatabasePath" -Level "INFO"
Write-Log "Log path: $LogPath" -Level "INFO"
if ($LogDirectory) {
    Write-Log "Processing all logs in directory: $LogDirectory" -Level "INFO"
}
if ($LogFile) {
    Write-Log "Processing specific log file: $LogFile" -Level "INFO"
}
Write-Log "Thresholds: $FailureThreshold failures in $FailureWindowMinutes min, $SubnetThreshold IPs for subnet block" -Level "INFO"
Write-Log "Block duration: $BlockDurationHours hours (escalation applies)" -Level "INFO"
if ($DryRun) {
    Write-Log "DRY RUN MODE - No actual blocking will occur" -Level "WARN"
}
if ($ImportOnly) {
    Write-Log "IMPORT ONLY MODE - Recording data but not blocking" -Level "WARN"
}

# Verify database exists
if (-not (Test-Path $DatabasePath)) {
    Write-Log "Database not found. Run MailFirewall-Setup.ps1 first!" -Level "ERROR"
    exit 1
}

# Process directory of logs (batch import mode)
if ($LogDirectory) {
    # Resolve directory path
    if (-not [System.IO.Path]::IsPathRooted($LogDirectory)) {
        $LogDirectory = Join-Path $LogPath $LogDirectory
    }

    if (-not (Test-Path $LogDirectory)) {
        Write-Log "Directory not found: $LogDirectory" -Level "ERROR"
        exit 1
    }

    # Get all log files sorted by name (oldest first - msYYMMDD.log format sorts chronologically)
    $logFiles = Get-ChildItem -Path $LogDirectory -Filter "ms*.log" | Sort-Object Name

    if ($logFiles.Count -eq 0) {
        Write-Log "No log files found in: $LogDirectory" -Level "WARN"
        exit 0
    }

    Write-Log "Found $($logFiles.Count) log files to process" -Level "INFO"
    Write-Host ""

    $fileNum = 0
    $totalFiles = $logFiles.Count

    foreach ($file in $logFiles) {
        $fileNum++
        Write-Host "=== [$fileNum/$totalFiles] " -NoNewline -ForegroundColor Cyan
        Write-Host "$($file.Name) " -NoNewline -ForegroundColor White
        Write-Host "===" -ForegroundColor Cyan

        Process-LogFile -LogFile $file.FullName
        Write-Host ""
    }

    Write-Log "Batch import completed: $totalFiles files processed" -Level "SUCCESS"
    exit 0
}

do {
    # Remove expired blocks (skip in import-only mode)
    if (-not $ImportOnly) {
        Remove-ExpiredBlocks
    }

    # Determine which log file to process
    if ($LogFile) {
        # Specific log file provided
        $targetLog = Join-Path $LogPath $LogFile
    } else {
        # Default to today's log file (format: msYYMMDD.log)
        $today = Get-Date -Format "yyMMdd"
        $targetLog = Join-Path $LogPath "ms$today.log"
    }

    if (Test-Path $targetLog) {
        Process-LogFile -LogFile $targetLog
    } else {
        Write-Log "Log file not found: $targetLog" -Level "WARN"
    }

    if ($Continuous) {
        Start-Sleep -Seconds $PollIntervalSeconds
    }

} while ($Continuous)

Write-Log "Mail Firewall Monitor completed." -Level "INFO"

#endregion
