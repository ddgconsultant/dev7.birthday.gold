#!/bin/bash

LOG_FILE=~/uptime_kuma_add_node_$(date +"%Y%m%d%H%M%S").log
STATE_FILE=~/uptime_kuma_add_state_mail
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

check_monitor_exists() {
    MONITOR_NAME=$1
    log "Checking if monitor '$MONITOR_NAME' exists"
    RESPONSE=$(curl -s -X POST http://april21.bday.gold:5000/check_monitor_exists -H "Content-Type: application/json" -d "{\"name\": \"$MONITOR_NAME\"}")
    log "Response: $RESPONSE"
    EXISTS=$(echo $RESPONSE | jq -r '.exists')
    log "Exists: $EXISTS"
    if [ "$EXISTS" == "true" ]; then
        return 0
    else
        return 1
    fi
}

log "Starting Uptime Kuma node addition process on $(hostname)"

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
    validate "Retrieving hostname"

    # Ensure jq is installed
    if ! command -v jq &> /dev/null; then
        log "jq not found, installing..."
        apt-get update && apt-get install -y jq
        validate "Installing jq"
    fi

    MONITOR_NAME="$HOSTNAME.bday.gold"
    if check_monitor_exists "$MONITOR_NAME"; then
        figlet "Already Exists"
        log "Monitor '$MONITOR_NAME' already exists. Skipping creation."
    else
        log "Creating JSON data for HOST monitor"
        HOST_DATA=$(jq -n --arg name "$HOSTNAME.bday.gold" --arg hostname "$HOSTNAME.bday.gold" --arg description "Monitor for $HOSTNAME.bday.gold" '{
            type: "ping",
            name: $name,
            hostname: $hostname,
            interval: 600,
            retryInterval: 600,
            resendInterval: 3,
            maxretries: 1,
            packetSize: 56,
            parent: 70,
            tags: ["MAIL"],
            description: $description,
            notificationIDList: [1]
        }')
        validate "Creating JSON data for HOST monitor"

        echo ${HOST_DATA} | tee -a $LOG_FILE

        log "Sending POST request to create HOST monitor"
        curl -X POST http://april21.bday.gold:5000/create_monitor -H "Content-Type: application/json" -d "$HOST_DATA"
        validate "Creating HOST monitor in Uptime Kuma"
        figlet "Monitor Added"
    fi

    save_state "create_3306_monitor"
    ;&
##########################################################
"create_3306_monitor")
    UPPERHOSTNAME=$(hostname | tr '[:lower:]' '[:upper:]')
    validate "Transforming hostname to uppercase"

    MONITOR_NAME="$UPPERHOSTNAME 3306-STATUS"
    if check_monitor_exists "$MONITOR_NAME"; then
        figlet "Already Exists"
        log "Monitor '$MONITOR_NAME' already exists. Skipping creation."
    else
        log "Creating JSON data for HTTP STATUS monitor"
        HTTP_DATA=$(jq -n --arg name "$MONITOR_NAME" --arg hostname "$HOSTNAME.birthday.gold" --arg url "https://$HOSTNAME.birthday.gold" --arg description "Monitor for $HOSTNAME.birthday.gold" '{
            type: "http",
            name: $name,
            hostname: $hostname,
            url: $url,
            interval: 300,
            maxretries: 1,
            retryInterval: 300,
            timeout: 48,
            resendInterval: 10,
            expiryNotification: true,
            ignoreTls: true,
            upsideDown: false,
            maxredirects: 10,
            accepted_statuscodes: ["200-299"],
            parent: 3,
            description: $description,
            tags: ["DATABASE"],
            notificationIDList: [1],
            method: "GET",
            httpBodyEncoding: "json",
            headers: {
                "HeaderName": "HeaderValue"
            },
            body: {
                "key": "value"
            }
        }')
        validate "Creating JSON data for 3306-STATUS monitor"

        echo ${HTTP_DATA} | tee -a $LOG_FILE

        log "Sending POST request to create 3306-STATUS monitor"
        curl -X POST http://april21.bday.gold:5000/create_monitor -H "Content-Type: application/json" -d "$HTTP_DATA"
        validate "Creating 3306-STATUS monitor in Uptime Kuma"
        figlet "Monitor Added"
    fi

    save_state "completed"
    ;&
##########################################################
"completed")
     log "Uptime Kuma node addition process completed successfully on $(hostname)"
    ;;
*)
    log "Unknown state: $STATE"
    exit 1
    ;;
esac
