#!/bin/bash

LOG_FILE=~/metabase_add_db_$(date +"%Y%m%d%H%M%S").log
STATE_FILE=~/metabase_add_state_mail
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

STATE=$(load_state)

# Check if the state is "completed" and no actions have been performed
if [ "$STATE" == "completed" ] && [ "$ACTION_COUNTER" -eq 0 ]; then
    figlet "Check State File"
    log "The state file [$STATE_FILE] = completed"
    exit 0
fi

case $STATE in
"pre")
    figlet "Starting"
    HOSTNAME=$(hostname)
    DB_NAME="Birthday.Gold [mail] - ${HOSTNAME^}"
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
    read -s -p "Enter Metabase password: " METABASE_PASSWORD
    echo ""
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
