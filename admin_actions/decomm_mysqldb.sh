#!/bin/bash
################################################################################
# Decommission MySQL Database Server Script
################################################################################
#
# Usage: ./decomm_mysqldb.sh [source_db_host]
# Example: ./decomm_mysqldb.sh july04.bday.gold
#
# This script decommissions a MySQL database server by:
# 1. Stopping replication channels
# 2. Removing monitors from Uptime Kuma
# 3. Updating system_availability status to 'X' in database
# 4. Stopping MySQL service (optional: --stop-services)
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
    echo "Usage: ./decomm_mysqldb.sh [source_db_hostname] [--stop-services] [--remove-packages]"
    echo "Example: ./decomm_mysqldb.sh july04.bday.gold"
    echo ""
    exit 1
fi

LOG_FILE=~/decomm_mysqldb_$(date +"%Y%m%d%H%M%S").log

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

figlet "DECOMM MySQL"
log "Starting MySQL decommission process for $HOSTNAME ($IP_ADDRESS)"
log "Source DB host: $SOURCE_DB_HOST"
log "Stop services: $STOP_SERVICES"
log "Remove packages: $REMOVE_PACKAGES"

echo ""
echo "WARNING: This will decommission MySQL server $HOSTNAME"
echo "  - Stop all replication channels"
echo "  - Remove Uptime Kuma monitors"
echo "  - Set database status to 'X'"
if [ "$STOP_SERVICES" = true ]; then
    echo "  - Stop MySQL service"
fi
if [ "$REMOVE_PACKAGES" = true ]; then
    echo "  - Remove MySQL packages"
fi
echo ""
read -p "Are you sure you want to continue? (yes/no): " CONFIRM
if [ "$CONFIRM" != "yes" ]; then
    log "Aborted by user"
    exit 1
fi

################################################################################
## Step 1: Stop replication channels
################################################################################
figlet "Replication"
log "Stopping replication channels"

read -sp "Enter MySQL root password for localhost: " MYSQL_ROOT_PASSWORD
echo ""

# Get list of replication channels
log "Checking for active replication channels..."
CHANNELS=$(mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "SELECT channel_name FROM performance_schema.replication_connection_status;" 2>/dev/null | tail -n +2)

if [ -n "$CHANNELS" ]; then
    log "Found replication channels: $CHANNELS"
    for channel in $CHANNELS; do
        log "Stopping replication channel: $channel"
        mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "STOP REPLICA FOR CHANNEL '$channel';" 2>/dev/null
        validate "Stopping replica for channel $channel"
    done
else
    log "No active replication channels found"
fi

# Also stop default replica if running
log "Stopping default replica..."
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "STOP REPLICA;" 2>/dev/null
log "Default replica stopped (may have already been stopped)"

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

# Delete MySQL monitors
UPPERHOSTNAME=$(echo $HOSTNAME | tr '[:lower:]' '[:upper:]')
delete_monitor "$HOSTNAME.bday.gold"
delete_monitor "$UPPERHOSTNAME MySQL"
delete_monitor "$UPPERHOSTNAME MySQL-3306"
delete_monitor "$UPPERHOSTNAME DB-Replication"

################################################################################
## Step 3: Update database status on source
################################################################################
figlet "Database"
log "Updating system_availability status to 'X' on $SOURCE_DB_HOST"

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

    log "Stopping MySQL service"
    systemctl stop mysql
    validate "Stopping MySQL"
    systemctl disable mysql
    validate "Disabling MySQL"
fi

################################################################################
## Step 5: Remove packages (if requested)
################################################################################
if [ "$REMOVE_PACKAGES" = true ]; then
    figlet "Remove Packages"
    log "Removing MySQL packages"

    apt-get remove --purge -y mysql-server mysql-client mysql-common
    validate "Removing MySQL packages"

    apt-get autoremove -y
    validate "Autoremove unused packages"

    # Clean up MySQL data directory
    log "WARNING: MySQL data directory (/var/lib/mysql) has NOT been removed"
    log "To remove data, manually run: rm -rf /var/lib/mysql"
fi

################################################################################
## Complete
################################################################################
figlet "DECOMM Done"
log "Decommission process completed for MySQL server $HOSTNAME"
log ""
log "Summary:"
log "  - Replication stopped: YES"
log "  - Removed Uptime monitors: YES"
log "  - Database status set to X: YES"
log "  - Services stopped: $STOP_SERVICES"
log "  - Packages removed: $REMOVE_PACKAGES"
log ""
log "IMPORTANT MANUAL STEPS:"
log "  1. Remove this server from other servers' replication sources"
log "     Run on other MySQL servers: STOP REPLICA FOR CHANNEL 'channel_to_$HOSTNAME';"
log "  2. Remove Metabase database connection manually via UI"
log "     https://metabase.birthdaygold.cloud/admin/databases"
log "  3. Update any application configs that reference this MySQL server"
