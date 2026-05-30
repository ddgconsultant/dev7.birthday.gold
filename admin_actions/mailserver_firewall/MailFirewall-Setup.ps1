#Requires -Modules PSSQLite
<#
.SYNOPSIS
    Mail Server Firewall - Database Setup Script
.DESCRIPTION
    Creates the SQLite database and tables for tracking failed auth attempts,
    blocked IPs, and whitelist entries.
.NOTES
    Run this once to initialize the database.
#>

param(
    [string]$DatabasePath = "C:\MailServer_AccessManagement\firewall_tracker.db"
)

# Ensure directory exists
$dbDir = Split-Path -Parent $DatabasePath
if (-not (Test-Path $dbDir)) {
    New-Item -ItemType Directory -Path $dbDir -Force | Out-Null
    Write-Host "Created directory: $dbDir" -ForegroundColor Green
}

# Import SQLite module
Import-Module PSSQLite

Write-Host "Initializing Mail Firewall Database..." -ForegroundColor Cyan
Write-Host "Database path: $DatabasePath" -ForegroundColor Gray

# Create tables
$createTables = @"
-- Escalation configuration (block duration increases with repeat offenses)
CREATE TABLE IF NOT EXISTS escalation_config (
    offense_number INTEGER PRIMARY KEY,
    block_hours INTEGER NOT NULL,
    description TEXT
);

-- Failed authentication attempts (aggregated by IP)
CREATE TABLE IF NOT EXISTS failed_auth (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL UNIQUE,
    first_seen DATETIME NOT NULL,
    last_seen DATETIME NOT NULL,
    attempt_count INTEGER DEFAULT 1,
    usernames TEXT
);

-- Currently blocked IPs
CREATE TABLE IF NOT EXISTS blocked_ips (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL UNIQUE,
    block_reason TEXT,
    failure_count INTEGER DEFAULT 0,
    offense_count INTEGER DEFAULT 1,
    blocked_at DATETIME NOT NULL,
    expires_at DATETIME,
    is_permanent INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    firewall_rule_name TEXT
);

-- Blocked subnets (/24)
CREATE TABLE IF NOT EXISTS blocked_subnets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    subnet TEXT NOT NULL UNIQUE,
    block_reason TEXT,
    ip_count INTEGER DEFAULT 0,
    offense_count INTEGER DEFAULT 1,
    blocked_at DATETIME NOT NULL,
    expires_at DATETIME,
    is_permanent INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    firewall_rule_name TEXT
);

-- Whitelist (never block these)
CREATE TABLE IF NOT EXISTS whitelist (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_or_subnet TEXT NOT NULL UNIQUE,
    description TEXT,
    is_subnet INTEGER DEFAULT 0,
    created_at DATETIME NOT NULL
);

-- Successful authentications (aggregated by IP)
CREATE TABLE IF NOT EXISTS successful_auth (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL UNIQUE,
    first_seen DATETIME NOT NULL,
    last_seen DATETIME NOT NULL,
    success_count INTEGER DEFAULT 1,
    usernames TEXT
);

-- Auto-whitelisted IPs from successful logins
CREATE TABLE IF NOT EXISTS auto_whitelist (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL UNIQUE,
    first_success DATETIME NOT NULL,
    last_success DATETIME NOT NULL,
    success_count INTEGER DEFAULT 1,
    usernames TEXT
);

-- Processing state (track last processed log position)
CREATE TABLE IF NOT EXISTS processing_state (
    id INTEGER PRIMARY KEY,
    log_file TEXT NOT NULL,
    last_position INTEGER DEFAULT 0,
    last_processed DATETIME
);

-- Run history (track each execution for monitoring)
CREATE TABLE IF NOT EXISTS run_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    run_start DATETIME NOT NULL,
    run_end DATETIME,
    run_mode TEXT,
    status TEXT DEFAULT 'running',
    files_processed INTEGER DEFAULT 0,
    lines_processed INTEGER DEFAULT 0,
    failed_auths_found INTEGER DEFAULT 0,
    successful_auths_found INTEGER DEFAULT 0,
    new_blocks_created INTEGER DEFAULT 0,
    expired_blocks_removed INTEGER DEFAULT 0,
    error_message TEXT
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_failed_auth_ip ON failed_auth(ip_address);
CREATE INDEX IF NOT EXISTS idx_failed_auth_last_seen ON failed_auth(last_seen);
CREATE INDEX IF NOT EXISTS idx_blocked_ips_active ON blocked_ips(is_active);
CREATE INDEX IF NOT EXISTS idx_blocked_subnets_active ON blocked_subnets(is_active);
CREATE INDEX IF NOT EXISTS idx_successful_auth_ip ON successful_auth(ip_address);
"@

# Execute table creation
foreach ($statement in ($createTables -split ';' | Where-Object { $_.Trim() })) {
    try {
        Invoke-SqliteQuery -DataSource $DatabasePath -Query $statement.Trim()
    } catch {
        Write-Warning "Statement issue: $($_.Exception.Message)"
    }
}

Write-Host "Tables created successfully." -ForegroundColor Green

# Insert default whitelist entries
$defaultWhitelist = @(
    @{ ip = "192.168.0.0/16"; desc = "Internal private network"; subnet = 1 },
    @{ ip = "10.0.0.0/8"; desc = "Internal private network"; subnet = 1 },
    @{ ip = "172.16.0.0/12"; desc = "Internal private network"; subnet = 1 },
    @{ ip = "71.33.250.0/24"; desc = "DDG Mail Server network"; subnet = 1 },
    @{ ip = "127.0.0.1"; desc = "Localhost"; subnet = 0 },

    # Amazon SES
    @{ ip = "199.255.192.0/22"; desc = "Amazon SES"; subnet = 1 },
    @{ ip = "199.127.232.0/22"; desc = "Amazon SES"; subnet = 1 },
    @{ ip = "54.240.0.0/18"; desc = "Amazon SES"; subnet = 1 },

    # Mailchimp/Mandrill
    @{ ip = "205.201.128.0/20"; desc = "Mailchimp"; subnet = 1 },
    @{ ip = "198.2.128.0/18"; desc = "Mailchimp"; subnet = 1 },

    # SendGrid
    @{ ip = "167.89.0.0/17"; desc = "SendGrid"; subnet = 1 },
    @{ ip = "198.37.144.0/20"; desc = "SendGrid"; subnet = 1 },

    # Google/Gmail
    @{ ip = "209.85.128.0/17"; desc = "Google Mail"; subnet = 1 },
    @{ ip = "74.125.0.0/16"; desc = "Google Mail"; subnet = 1 },

    # Microsoft/Outlook
    @{ ip = "40.92.0.0/15"; desc = "Microsoft Outlook"; subnet = 1 },
    @{ ip = "40.107.0.0/16"; desc = "Microsoft Outlook"; subnet = 1 },
    @{ ip = "52.100.0.0/14"; desc = "Microsoft Outlook"; subnet = 1 }
)

Write-Host "Adding default whitelist entries..." -ForegroundColor Cyan

foreach ($entry in $defaultWhitelist) {
    $checkQuery = "SELECT COUNT(*) as cnt FROM whitelist WHERE ip_or_subnet = @ip"
    $exists = Invoke-SqliteQuery -DataSource $DatabasePath -Query $checkQuery -SqlParameters @{ ip = $entry.ip }

    if ($exists.cnt -eq 0) {
        $insertQuery = @"
INSERT INTO whitelist (ip_or_subnet, description, is_subnet, created_at)
VALUES (@ip, @desc, @subnet, datetime('now'))
"@
        Invoke-SqliteQuery -DataSource $DatabasePath -Query $insertQuery -SqlParameters @{
            ip = $entry.ip
            desc = $entry.desc
            subnet = $entry.subnet
        }
        Write-Host "  Added: $($entry.ip) - $($entry.desc)" -ForegroundColor Gray
    }
}

# Insert default escalation configuration (exponential scale starting at 8 hours)
# Scale: 8h -> 24h -> 72h -> 216h (9d) -> 648h (27d) -> PERMANENT
Write-Host "`nAdding escalation configuration..." -ForegroundColor Cyan

$escalationConfig = @(
    @{ offense = 1; hours = 8; desc = "1st offense - 8 hour ban" },
    @{ offense = 2; hours = 24; desc = "2nd offense - 24 hour ban (1 day)" },
    @{ offense = 3; hours = 72; desc = "3rd offense - 72 hour ban (3 days)" },
    @{ offense = 4; hours = 216; desc = "4th offense - 216 hour ban (9 days)" },
    @{ offense = 5; hours = 648; desc = "5th offense - 648 hour ban (27 days)" },
    @{ offense = 6; hours = -1; desc = "6th+ offense - PERMANENT ban" }
)

foreach ($esc in $escalationConfig) {
    $checkQuery = "SELECT COUNT(*) as cnt FROM escalation_config WHERE offense_number = @num"
    $exists = Invoke-SqliteQuery -DataSource $DatabasePath -Query $checkQuery -SqlParameters @{ num = $esc.offense }

    if ($exists.cnt -eq 0) {
        $insertQuery = "INSERT INTO escalation_config (offense_number, block_hours, description) VALUES (@num, @hours, @desc)"
        Invoke-SqliteQuery -DataSource $DatabasePath -Query $insertQuery -SqlParameters @{
            num = $esc.offense
            hours = $esc.hours
            desc = $esc.desc
        }
        Write-Host "  Added: Offense $($esc.offense) - $($esc.desc)" -ForegroundColor Gray
    }
}

Write-Host "`nDatabase initialization complete!" -ForegroundColor Green
Write-Host "`nEscalation scale (exponential x3):" -ForegroundColor Yellow
Write-Host "  1st offense:  8 hours" -ForegroundColor Gray
Write-Host "  2nd offense:  24 hours (1 day)" -ForegroundColor Gray
Write-Host "  3rd offense:  72 hours (3 days)" -ForegroundColor Gray
Write-Host "  4th offense:  216 hours (9 days)" -ForegroundColor Gray
Write-Host "  5th offense:  648 hours (27 days)" -ForegroundColor Gray
Write-Host "  6th+ offense: PERMANENT" -ForegroundColor Red
Write-Host "`nNext steps:" -ForegroundColor Yellow
Write-Host "  1. Run MailFirewall-Monitor.ps1 to start monitoring logs"
Write-Host "  2. Set up a scheduled task to run the monitor periodically"
