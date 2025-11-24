#!/bin/bash
################################################################################
# Metabase Database Connection Setup Script
################################################################################
#
# This script adds a database connection to Metabase for monitoring purposes.
#
# AFTER RUNNING THIS SCRIPT - MANUAL METABASE CONFIGURATION REQUIRED:
#
# 1. ADD SERVER TO "SERVER HITS" QUERY:
#    https://metabase.birthdaygold.cloud/question/9-server-hits
#    - Edit the query to include the new server's IP address
#
# 2. CREATE SESSION TRACKING CARD (clone from existing):
#    https://metabase.birthdaygold.cloud/question/17-sessiontracking-dec05
#    - Duplicate this card
#    - Update to point to new server's database
#    - Rename to match new server (e.g., "SessionTracking-July05")
#
# 3. CREATE REPLICATION CHANNEL CARDS (clone from existing):
#    https://metabase.birthdaygold.cloud/question/11-replication-channelsource-december04-to-december02
#    - Duplicate this card for each replication channel on new server
#    - Update channel names and database connections
#    - Rename appropriately (e.g., "Replication-ChannelSource-July05-to-July02")
#
# 4. CREATE SERVER LOAD CARD (clone from existing):
#    https://metabase.birthdaygold.cloud/question/26-december02-server-load
#    - Duplicate this card
#    - Update to monitor new server
#    - Rename to match new server (e.g., "July05-Server-Load")
#
# NOTE: This script only creates the database CONNECTION in Metabase.
#       The queries/cards/dashboards must be configured manually through the UI.
#
################################################################################

LOG_FILE=~/metabase_add_db_$(date +"%Y%m%d%H%M%S").log
STATE_FILE=~/metabase_add_state_web
ACTION_COUNTER=0

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

save_state() {
    echo "$1" > $STATE_FILE
    ACTION_COUNTER=$((ACTION_COUNTER + 1))
}

load_state() {
    if [ -f $STATE_FILE ]; then
        cat $STATE_FILE
    else
        echo "pre"
    fi
}

log "Starting Metabase database addition process"

# Set up systemd service for auto-resume after reboot
if [ ! -f /etc/systemd/system/metabase-install-resume.service ]; then
    log "Creating auto-resume systemd service"
    cat > /etc/systemd/system/metabase-install-resume.service <<'EOF'
[Unit]
Description=Resume Metabase Installation After Reboot
After=network.target

[Service]
Type=oneshot
ExecStart=/root/install_addtometabase_web.sh
RemainAfterExit=yes
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF
    systemctl enable metabase-install-resume.service
    log "Auto-resume service enabled"
fi

STATE=$(load_state)

# Check if the state is "completed" and no actions have been performed
if [ "$STATE" == "completed" ] && [ "$ACTION_COUNTER" -eq 0 ]; then
    figlet "Check State File"
    log "The state file [$STATE_FILE] = completed"
    # Clean up auto-resume service
    systemctl disable metabase-install-resume.service 2>/dev/null
    rm -f /etc/systemd/system/metabase-install-resume.service
    systemctl daemon-reload
    log "Auto-resume service removed"
    exit 0
fi

case $STATE in
"pre")
    figlet "Starting"
HOSTNAME=$(hostname)
NEW_HOSTNAME=$(echo "$HOSTNAME" | sed 's/july/December/')
DB_NAME="Birthday.Gold - ${NEW_HOSTNAME^}"
    validate "Retrieving hostname and setting DB name as $DB_NAME"

    # Ensure jq is installed
    if ! command -v jq &> /dev/null; then
        log "jq not found, installing..."
        apt-get update && apt-get install -y jq
        validate "Installing jq"
    fi
    
    METABASE_URL="https://metabase.birthdaygold.cloud"
    USERNAME="richard@birthday.gold"

    figlet "Input Required"
    # Prompt for the Metabase and MySQL passwords
    echo "Metabase Web UI Password (for richard@birthday.gold login):"
    read -s -p "Enter Metabase password: " METABASE_PASSWORD
    echo ""
    echo ""
    echo "MySQL Database Password (for birthday_gold_admin@'%' user):"
    read -s -p "Enter MySQL birthday_gold_admin password: " DB_PASSWORD
    echo ""

    # Get session token
    log "Getting session token"
    TOKEN=$(curl -s -X POST "$METABASE_URL/api/session" \
      -H "Content-Type: application/json" \
      -d "{\"username\": \"$USERNAME\", \"password\": \"$METABASE_PASSWORD\"}" | jq -r .id)
    
    if [ -z "$TOKEN" ]; then
        validate "Failed to get session token"
    else
        log "Session Token: $TOKEN"
        validate "Got session token"
    fi

    save_state "add_database"
    ;&
##########################################################
"add_database")
    DB_HOST="$HOSTNAME.bday.gold"
    DB_PORT="3306"
    DB_DBNAME="mailserver"
    DB_USER="birthday_gold_admin"

    DB_DETAILS=$(jq -n \
      --arg name "$DB_NAME" \
      --arg engine "mysql" \
      --arg host "$DB_HOST" \
      --arg port "$DB_PORT" \
      --arg dbname "$DB_DBNAME" \
      --arg user "$DB_USER" \
      --arg password "$DB_PASSWORD" \
      '{
        name: $name,
        engine: $engine,
        details: {
          host: $host,
          port: $port,
          dbname: $dbname,
          user: $user,
          password: $password
        }
      }')

    log "Adding database to Metabase"
    RESPONSE=$(curl -s -X POST "$METABASE_URL/api/database" \
      -H "Content-Type: application/json" \
      -H "X-Metabase-Session: $TOKEN" \
      -d "$DB_DETAILS")

    echo "Database added: $RESPONSE" | tee -a $LOG_FILE
    validate "Adding database to Metabase"

    save_state "completed"
    ;&
##########################################################
"completed")
    log "Metabase database addition process completed successfully"
    ;;
*)
    log "Unknown state: $STATE"
    exit 1
    ;;
esac
