#!/bin/bash
################################################################################
# Decommission Mailserver Script
################################################################################
#
# Usage: ./decomm_mailserver.sh [source_db_host]
# Example: ./decomm_mailserver.sh july04.bday.gold
#
# This script decommissions a mailserver by:
# 1. Commenting out server in class.mail.php (via API call)
# 2. Removing monitors from Uptime Kuma
# 3. Updating system_availability status to 'X' in database
# 4. Stopping Postfix/Dovecot services (optional: --stop-services)
# 5. Removing packages (optional: --remove-packages)
#
# NOTE: The mailserver list in class.mail.php must be updated manually or via
#       the birthday.gold admin panel to remove the server from rotation.
#
################################################################################

SOURCE_DB_HOST="${1:-july03.bday.gold}"
STOP_SERVICES=false
REMOVE_PACKAGES=false

# Parse flags
for arg in "$@"; do
    case $arg in
        --stop-services)
            STOP_SERVICES=true
            shift
            ;;
        --remove-packages)
            REMOVE_PACKAGES=true
            STOP_SERVICES=true  # Must stop services before removing packages
            shift
            ;;
    esac
done

# Validate FQDN
if [[ ! "$SOURCE_DB_HOST" =~ \. ]]; then
    echo ""
    echo "ERROR: Source host '$SOURCE_DB_HOST' is not a fully qualified domain name."
    echo ""
    echo "Usage: ./decomm_mailserver.sh [source_db_hostname] [--stop-services] [--remove-packages]"
    echo "Example: ./decomm_mailserver.sh july04.bday.gold"
    echo ""
    exit 1
fi

LOG_FILE=~/decomm_mailserver_$(date +"%Y%m%d%H%M%S").log

log() {
    echo "$(date +"%Y-%m-%d %H:%M:%S") - $1" | tee -a $LOG_FILE
    echo ""
}

validate() {
    if [ $? -ne 0 ]; then
        figlet "FAIL" | tee -a $LOG_FILE
        log "FAIL: $1"
        read -p "FAIL: $1. Do you want to continue? (y/n): " choice
        if [ "$choice" != "y" ]; then
            log "Aborting process"
            exit 1
        fi
    else
        log "PASS: $1"
    fi
}

HOSTNAME=$(hostname -s)
IP_ADDRESS=$(hostname -I | awk '{print $1}')

figlet "DECOMM MAIL"
log "Starting mailserver decommission process for $HOSTNAME ($IP_ADDRESS)"
log "Source DB host: $SOURCE_DB_HOST"
log "Stop services: $STOP_SERVICES"
log "Remove packages: $REMOVE_PACKAGES"

echo ""
echo "WARNING: This will decommission mailserver $HOSTNAME"
echo "  - Comment out in class.mail.php mailserver list"
echo "  - Remove Uptime Kuma monitors"
echo "  - Set database status to 'X'"
if [ "$STOP_SERVICES" = true ]; then
    echo "  - Stop Postfix/Dovecot services"
fi
if [ "$REMOVE_PACKAGES" = true ]; then
    echo "  - Remove Postfix/Dovecot packages"
fi
echo ""
read -p "Are you sure you want to continue? (yes/no): " CONFIRM
if [ "$CONFIRM" != "yes" ]; then
    log "Aborted by user"
    exit 1
fi

################################################################################
## Step 1: Update config to disable this mailserver
################################################################################
figlet "Config Update"
log "Updating config to disable mailserver $HOSTNAME"

# The mailserver list is now in config-main-production.inc [mailservers] section
# We need to change the status from 'active' to 'inactive'

# Try to update via a webserver that has access to the config
log "Attempting to update mailserver status via API..."
API_RESPONSE=$(curl -s -X POST "https://www.birthday.gold/api/admin/toggle_mailserver.php" \
    -H "Content-Type: application/json" \
    -d "{\"hostname\": \"$HOSTNAME\", \"action\": \"disable\"}" 2>/dev/null)

if [ $? -eq 0 ] && echo "$API_RESPONSE" | grep -q '"success"'; then
    log "API call successful: $API_RESPONSE"
    validate "Disabling mailserver via API"
else
    log "WARNING: API call failed or endpoint doesn't exist"
    log ""
    log "MANUAL ACTION REQUIRED:"
    log "Edit config-main-production.inc and change the mailserver status:"
    log ""
    log "  In [mailservers] section, change:"
    log "    $HOSTNAME = \"active\""
    log "  To:"
    log "    $HOSTNAME = \"inactive\""
    log ""
    log "  Or comment it out:"
    log "    ; $HOSTNAME = \"inactive\""
    log ""
    log "Location: ENV_CONFIGS/config-main-production.inc"
    log ""
    read -p "Press Enter to continue after manual edit, or Ctrl+C to abort..."
fi

################################################################################
## Step 2: Remove Uptime Kuma monitors
################################################################################
figlet "Uptime Kuma"
log "Removing Uptime Kuma monitors"

# Ensure jq is installed
if ! command -v jq &> /dev/null; then
    apt-get update && apt-get install -y jq
fi

delete_monitor() {
    MONITOR_NAME=$1
    log "Checking if monitor '$MONITOR_NAME' exists"
    RESPONSE=$(curl -s -k -X POST https://april21.bday.gold:5443/check_monitor_exists -H "Content-Type: application/json" -d "{\"name\": \"$MONITOR_NAME\"}")
    EXISTS=$(echo $RESPONSE | jq -r '.exists')

    if [ "$EXISTS" == "true" ]; then
        MONITOR_ID=$(echo $RESPONSE | jq -r '.id')
        log "Deleting monitor '$MONITOR_NAME' (ID: $MONITOR_ID)"
        curl -s -k -X POST https://april21.bday.gold:5443/delete_monitor -H "Content-Type: application/json" -d "{\"id\": $MONITOR_ID}"
        validate "Deleting monitor '$MONITOR_NAME'"
    else
        log "Monitor '$MONITOR_NAME' does not exist, skipping"
    fi
}

# Delete mailserver monitors
UPPERHOSTNAME=$(echo $HOSTNAME | tr '[:lower:]' '[:upper:]')
delete_monitor "$HOSTNAME.bday.gold"
delete_monitor "$UPPERHOSTNAME SMTP"
delete_monitor "$UPPERHOSTNAME IMAP"
delete_monitor "$UPPERHOSTNAME Mail Delivery"

################################################################################
## Step 3: Update database status
################################################################################
figlet "Database"
log "Updating system_availability status to 'X'"

read -sp "Enter MySQL password for birthday_gold_admin@$SOURCE_DB_HOST: " MYSQL_ADMIN_PASSWORD
echo ""

mysql -u birthday_gold_admin -h${SOURCE_DB_HOST} -p"${MYSQL_ADMIN_PASSWORD}" -e "
UPDATE \`birthday_gold_www\`.\`bg_system_availability\`
SET \`status\` = 'X', \`system_status\` = 'red', \`modify_dt\` = NOW()
WHERE \`url\` = '${IP_ADDRESS}';" 2>/dev/null
validate "Updating system_availability status"

################################################################################
## Step 4: Stop services (if requested)
################################################################################
if [ "$STOP_SERVICES" = true ]; then
    figlet "Stop Services"

    log "Stopping Postfix service"
    systemctl stop postfix
    validate "Stopping Postfix"
    systemctl disable postfix
    validate "Disabling Postfix"

    log "Stopping Dovecot service"
    systemctl stop dovecot
    validate "Stopping Dovecot"
    systemctl disable dovecot
    validate "Disabling Dovecot"
fi

################################################################################
## Step 5: Remove packages (if requested)
################################################################################
if [ "$REMOVE_PACKAGES" = true ]; then
    figlet "Remove Packages"
    log "Removing Postfix and Dovecot packages"

    apt-get remove --purge -y postfix postfix-mysql
    validate "Removing Postfix packages"

    apt-get remove --purge -y dovecot-core dovecot-imapd dovecot-mysql
    validate "Removing Dovecot packages"

    apt-get autoremove -y
    validate "Autoremove unused packages"
fi

################################################################################
## Complete
################################################################################
figlet "DECOMM Done"
log "Decommission process completed for mailserver $HOSTNAME"
log ""
log "Summary:"
log "  - class.mail.php updated: CHECK MANUALLY"
log "  - Removed Uptime monitors: YES"
log "  - Database status set to X: YES"
log "  - Services stopped: $STOP_SERVICES"
log "  - Packages removed: $REMOVE_PACKAGES"
log ""
log "IMPORTANT MANUAL STEPS:"
log "  1. Verify config-main-production.inc has $HOSTNAME set to 'inactive'"
log "     Location: ENV_CONFIGS/config-main-production.inc [mailservers] section"
log "  2. Remove Metabase database connection manually via UI"
log "     https://metabase.birthdaygold.cloud/admin/databases"
log "  3. Update DNS records if applicable (remove MX record)"
