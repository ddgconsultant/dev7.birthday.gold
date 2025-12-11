MAIL SERVER FIREWALL - Installation Guide
==========================================

This system monitors ArgoSoft Mail Server logs for brute force attacks
and automatically blocks malicious IPs using Windows Firewall.

REQUIREMENTS
------------
- Windows Server with PowerShell 5.1+
- PSSQLite module (already installed)
- Administrator privileges
- ArgoSoft Mail Server logs at: C:\ProgramData\ArGoSoft\MailServer.Net\_logs


INSTALLATION STEPS
------------------

1. Create the directory and copy scripts:

   mkdir C:\MailServer_AccessManagement

   Copy all .ps1 files from this folder to C:\MailServer_AccessManagement\


2. Initialize the database:

   cd C:\MailServer_AccessManagement
   powershell -ExecutionPolicy Bypass -File .\MailFirewall-Setup.ps1

   This creates the SQLite database and populates default whitelist entries.


3. Test the monitor manually:

   powershell -ExecutionPolicy Bypass -File .\MailFirewall-Monitor.ps1 -DryRun

   The -DryRun flag shows what WOULD be blocked without actually blocking.


4. Run for real (single pass):

   powershell -ExecutionPolicy Bypass -File .\MailFirewall-Monitor.ps1


5. Install scheduled task (runs every 5 minutes):

   powershell -ExecutionPolicy Bypass -File .\Install-ScheduledTask.ps1


SCRIPTS INCLUDED
----------------

MailFirewall-Setup.ps1    - One-time setup, creates database and tables
MailFirewall-Monitor.ps1  - Main log parser and blocker (run on schedule)
MailFirewall-Status.ps1   - View current status, blocks, and statistics
MailFirewall-Manage.ps1   - Add/remove whitelist, manual block/unblock
Install-ScheduledTask.ps1 - Create Windows scheduled task


COMMON COMMANDS
---------------

# View current status and statistics
.\MailFirewall-Status.ps1 -All

# View just blocked IPs
.\MailFirewall-Status.ps1 -ShowBlocked

# View recent attack statistics
.\MailFirewall-Status.ps1 -ShowRecentAttacks -Hours 4

# Add an IP to whitelist
.\MailFirewall-Manage.ps1 -AddWhitelist -IP "1.2.3.4" -Description "Office IP"

# Add a subnet to whitelist
.\MailFirewall-Manage.ps1 -AddWhitelist -IP "10.0.0.0/8" -Description "VPN"

# Manually block an IP permanently
.\MailFirewall-Manage.ps1 -BlockIP -IP "5.6.7.8" -Permanent -Description "Known attacker"

# Unblock an IP
.\MailFirewall-Manage.ps1 -UnblockIP -IP "5.6.7.8"

# Purge data older than 30 days
.\MailFirewall-Manage.ps1 -PurgeOldData -PurgeDays 30


CONFIGURATION
-------------

Default thresholds (can be changed via parameters):
- Block IP after 3 failed auths in 30 minutes
- Block /24 subnet after 3 IPs from same subnet are blocked
- Blocks use escalating duration based on offense count

To change thresholds, edit MailFirewall-Monitor.ps1 parameters or pass them:

.\MailFirewall-Monitor.ps1 -FailureThreshold 5 -FailureWindowMinutes 60


ESCALATION SCALE
----------------

Repeat offenders get progressively longer bans (exponential x3):

  1st offense:  8 hours
  2nd offense:  24 hours (1 day)
  3rd offense:  72 hours (3 days)
  4th offense:  216 hours (9 days)
  5th offense:  648 hours (27 days)
  6th+ offense: PERMANENT

The offense count persists in the database even after blocks expire.
This means if an IP is blocked, released after 8 hours, and attacks again,
they will get a 24-hour ban on their second offense.


DEFAULT WHITELIST
-----------------

The setup script adds these to whitelist automatically:
- 192.168.0.0/16, 10.0.0.0/8, 172.16.0.0/12 (private networks)
- 71.33.250.0/24 (DDG mail server network)
- Amazon SES, Mailchimp, SendGrid, Google, Microsoft IP ranges

Any IP that successfully authenticates is auto-whitelisted.


DATABASE LOCATION
-----------------

C:\MailServer_AccessManagement\firewall_tracker.db

This is a SQLite file. You can browse it with any SQLite tool, or use
the MailFirewall-Status.ps1 script for common queries.


TROUBLESHOOTING
---------------

1. "Database not found" error:
   Run MailFirewall-Setup.ps1 first

2. Blocks not appearing in firewall:
   Run PowerShell as Administrator

3. Legitimate IP got blocked:
   .\MailFirewall-Manage.ps1 -UnblockIP -IP "x.x.x.x"
   .\MailFirewall-Manage.ps1 -AddWhitelist -IP "x.x.x.x" -Description "reason"

4. View Windows Firewall rules:
   Get-NetFirewallRule -DisplayName "MailFirewall_*"

5. Remove all MailFirewall rules:
   Get-NetFirewallRule -DisplayName "MailFirewall_*" | Remove-NetFirewallRule
