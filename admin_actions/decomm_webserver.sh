#!/bin/bash
################################################################################
# Decommission Webserver Script
################################################################################
#
# Usage: ./decomm_webserver.sh [source_db_host]
# Example: ./decomm_webserver.sh july04.bday.gold
#
# This script decommissions a webserver by:
# 1. Removing from HAProxy (stops traffic)
# 2. Removing monitors from Uptime Kuma
# 3. Updating system_availability status to 'X' in database
# 4. Stopping Apache service (optional: --stop-services)
# 5. Removing packages (optional: --remove-packages)
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
    echo "Usage: ./decomm_webserver.sh [source_db_hostname] [--stop-services] [--remove-packages]"
    echo "Example: ./decomm_webserver.sh july04.bday.gold"
    echo ""
    exit 1
fi

LOG_FILE=~/decomm_webserver_$(date +"%Y%m%d%H%M%S").log

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

figlet "DECOMM WEB"
log "Starting decommission process for $HOSTNAME ($IP_ADDRESS)"
log "Source DB host: $SOURCE_DB_HOST"
log "Stop services: $STOP_SERVICES"
log "Remove packages: $REMOVE_PACKAGES"

echo ""
echo "WARNING: This will decommission webserver $HOSTNAME"
echo "  - Remove from HAProxy (stop receiving traffic)"
echo "  - Remove Uptime Kuma monitors"
echo "  - Set database status to 'X'"
if [ "$STOP_SERVICES" = true ]; then
    echo "  - Stop Apache service"
fi
if [ "$REMOVE_PACKAGES" = true ]; then
    echo "  - Remove Apache/PHP packages"
fi
echo ""
read -p "Are you sure you want to continue? (yes/no): " CONFIRM
if [ "$CONFIRM" != "yes" ]; then
    log "Aborted by user"
    exit 1
fi

################################################################################
## Step 1: Remove from HAProxy
################################################################################
figlet "HAProxy"
log "Removing from HAProxy"

# Get password for HAProxy server
read -sp "Enter password for root@april21.bday.gold: " rootpass
echo ""

# Add HAProxy to known hosts if needed
ssh-keyscan -H april21.bday.gold >> ~/.ssh/known_hosts 2>/dev/null

apt-get -y install sshpass >/dev/null 2>&1

# Backup HAProxy config
log "Backing up HAProxy configuration"
sshpass -p "$rootpass" ssh -o StrictHostKeyChecking=no root@april21.bday.gold "cp /etc/haproxy/haproxy.cfg /etc/haproxy/haproxy.cfg_decomm_\$(date +%Y%m%d%H%M)"
validate "Backing up HAProxy configuration"

# Remove entries for this IP
log "Removing HAProxy entries for $IP_ADDRESS"
sshpass -p "$rootpass" ssh -o StrictHostKeyChecking=no root@april21.bday.gold "sed -i '/$IP_ADDRESS:80 check/d' /etc/haproxy/haproxy.cfg"
sshpass -p "$rootpass" ssh -o StrictHostKeyChecking=no root@april21.bday.gold "sed -i '/$IP_ADDRESS:443 ssl verify none check/d' /etc/haproxy/haproxy.cfg"
validate "Removing HAProxy entries"

# Validate and reload HAProxy
log "Validating HAProxy configuration"
sshpass -p "$rootpass" ssh -o StrictHostKeyChecking=no root@april21.bday.gold "haproxy -c -f /etc/haproxy/haproxy.cfg"
validate "Validating HAProxy configuration"

log "Reloading HAProxy"
sshpass -p "$rootpass" ssh -o StrictHostKeyChecking=no root@april21.bday.gold "systemctl reload haproxy"
validate "Reloading HAProxy"

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

# Delete the monitors that were created by install_uptime_monitors_web.sh
UPPERHOSTNAME=$(echo $HOSTNAME | tr '[:lower:]' '[:upper:]')
delete_monitor "$HOSTNAME.bday.gold"
delete_monitor "$UPPERHOSTNAME SiteChecker"
delete_monitor "$UPPERHOSTNAME HTTP-STATUS"

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
    log "Stopping Apache service"
    systemctl stop apache2
    validate "Stopping Apache"

    systemctl disable apache2
    validate "Disabling Apache"
fi

################################################################################
## Step 5: Remove packages (if requested)
################################################################################
if [ "$REMOVE_PACKAGES" = true ]; then
    figlet "Remove Packages"
    log "Removing Apache and PHP packages"

    apt-get remove --purge -y apache2 apache2-* libapache2-mod-php*
    validate "Removing Apache packages"

    apt-get remove --purge -y php8.1 php8.1-*
    validate "Removing PHP packages"

    apt-get autoremove -y
    validate "Autoremove unused packages"
fi

################################################################################
## Complete
################################################################################
figlet "DECOMM Done"
log "Decommission process completed for $HOSTNAME"
log ""
log "Summary:"
log "  - Removed from HAProxy: YES"
log "  - Removed Uptime monitors: YES"
log "  - Database status set to X: YES"
log "  - Services stopped: $STOP_SERVICES"
log "  - Packages removed: $REMOVE_PACKAGES"
log ""
log "Note: Metabase database connection must be removed manually via UI"
log "      https://metabase.birthdaygold.cloud/admin/databases"
