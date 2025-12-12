#!/bin/bash
################################################################################
# Uptime Kuma Monitor Setup Script
################################################################################
#
# PREREQUISITES:
#
# 1. FLASK API SERVER (april21.bday.gold)
#    - Python Flask API must be running on april21.bday.gold:5443
#    - Location: /root/PYTHON_API/uptime_api_handler.py
#    - Required endpoints: /check_monitor_exists, /create_monitor
#
# 2. ENVIRONMENT VARIABLE
#    - UPTIME_KUMA_PASSWORD must be set before starting Flask app
#    - Username: ddgconsultant
#    - Password: 80t7gPv8X0DmxnaJ (stored in UPTIME_KUMA_PASSWORD env var)
#
# 3. START FLASK API:
#    cd /root/PYTHON_API
#    export UPTIME_KUMA_PASSWORD='80t7gPv8X0DmxnaJ'
#    nohup /root/PYTHON_API/venv/bin/python /root/PYTHON_API/uptime_api_handler.py > uptime_api.log 2>&1 &
#
# 4. VERIFY FLASK API:
#    curl -k -X POST https://localhost:5443/check_monitor_exists \
#         -H "Content-Type: application/json" -d '{"name": "test"}'
#    Expected response: {"exists":false} or {"exists":true,"id":123}
#
# 5. NETWORK CONNECTIVITY:
#    - Server running this script must be able to reach april21.bday.gold:5443
#    - Test: curl -k https://april21.bday.gold:5443/check_monitor_exists
#
# 6. UPTIME KUMA ACCESS:
#    - URL: https://uptime.birthdaygold.cloud
#    - API credentials configured in Flask app
#
################################################################################

LOG_FILE=~/uptime_kuma_add_node_$(date +"%Y%m%d%H%M%S").log
STATE_FILE=~/uptime_kuma_add_state
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
    RESPONSE=$(curl -s -k -X POST https://april21.bday.gold:5443/check_monitor_exists -H "Content-Type: application/json" -d "{\"name\": \"$MONITOR_NAME\"}")
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

# Set up systemd service for auto-resume after reboot
if [ ! -f /etc/systemd/system/uptime-kuma-install-resume.service ]; then
    log "Creating auto-resume systemd service"
    cat > /etc/systemd/system/uptime-kuma-install-resume.service <<'EOF'
[Unit]
Description=Resume Uptime Kuma Installation After Reboot
After=network.target

[Service]
Type=oneshot
ExecStart=/root/install_uptime_monitors_web.sh
RemainAfterExit=yes
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF
    systemctl enable uptime-kuma-install-resume.service
    log "Auto-resume service enabled"
fi

STATE=$(load_state)

# Check if the state is "completed" and no actions have been performed
if [ "$STATE" == "completed" ] && [ "$ACTION_COUNTER" -eq 0 ]; then
    figlet "Check State File"
    log "The state file [$STATE_FILE] = completed"
    # Clean up auto-resume service
    systemctl disable uptime-kuma-install-resume.service 2>/dev/null
    rm -f /etc/systemd/system/uptime-kuma-install-resume.service
    systemctl daemon-reload
    log "Auto-resume service removed"
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
            description: $description,
            notificationIDList: [1]
        }')
        validate "Creating JSON data for HOST monitor"

        echo ${HOST_DATA} | tee -a $LOG_FILE

        log "Sending POST request to create HOST monitor"
        curl -k -X POST https://april21.bday.gold:5443/create_monitor -H "Content-Type: application/json" -d "$HOST_DATA"
        validate "Creating HOST monitor in Uptime Kuma"
        figlet "Monitor Added"
    fi

    save_state "create_http_sitechecker"
    ;&
##########################################################
"create_http_sitechecker")
    UPPERHOSTNAME=$(hostname | tr '[:lower:]' '[:upper:]')
    validate "Transforming hostname to uppercase"

    MONITOR_NAME="$UPPERHOSTNAME SiteChecker"
    if check_monitor_exists "$MONITOR_NAME"; then
        figlet "Already Exists"
        log "Monitor '$MONITOR_NAME' already exists. Skipping creation."
    else
        log "Creating JSON data for HTTP SiteChecker monitor"
        HTTP_DATA=$(jq -n --arg name "$UPPERHOSTNAME SiteChecker" --arg hostname "$HOSTNAME.birthday.gold" --arg url "https://$HOSTNAME.birthday.gold/admin_actions/scheduler--sitechecker" --arg description "Execute the sitechecker.php script to look for errors" '{
            type: "keyword",
            name: $name,
            hostname: $hostname,
            url: $url,
            interval: 14400,
            maxretries: 0,
            retryInterval: 14400,
            timeout: 300,
            resendInterval: 0,
            expiryNotification: true,
            ignoreTls: true,
            upsideDown: false,
            maxredirects: 10,
            accepted_statuscodes: ["200-299"],
            parent: 3,
            description: $description,
            tags: ["WEB"],
            notificationIDList: [1],
            method: "GET",
            httpBodyEncoding: "json",
            headers: {
                "HeaderName": "HeaderValue"
            },
            body: {
                "key": "value"
            },
            keyword: "No errors found"
        }')
        validate "Creating JSON data for HTTP SiteChecker monitor"

        echo ${HTTP_DATA} | tee -a $LOG_FILE

        log "Sending POST request to create HTTP SiteChecker monitor"
        curl -k -X POST https://april21.bday.gold:5443/create_monitor -H "Content-Type: application/json" -d "$HTTP_DATA"
        validate "Creating HTTP SiteChecker monitor in Uptime Kuma"
        figlet "Monitor Added"
    fi

    save_state "create_http_monitor"
    ;&
##########################################################
"create_http_monitor")
    UPPERHOSTNAME=$(hostname | tr '[:lower:]' '[:upper:]')
    validate "Transforming hostname to uppercase"

    MONITOR_NAME="$UPPERHOSTNAME HTTP-STATUS"
    if check_monitor_exists "$MONITOR_NAME"; then
        figlet "Already Exists"
        log "Monitor '$MONITOR_NAME' already exists. Skipping creation."
    else
        log "Creating JSON data for HTTP STATUS monitor"
        HTTP_DATA=$(jq -n --arg name "$UPPERHOSTNAME HTTP-STATUS" --arg hostname "$HOSTNAME.birthday.gold" --arg url "https://$HOSTNAME.birthday.gold" --arg description "Monitor for $HOSTNAME.birthday.gold" '{
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
            tags: ["WEB"],
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
        validate "Creating JSON data for HTTP STATUS monitor"

        echo ${HTTP_DATA} | tee -a $LOG_FILE

        log "Sending POST request to create HTTP STATUS monitor"
        curl -k -X POST https://april21.bday.gold:5443/create_monitor -H "Content-Type: application/json" -d "$HTTP_DATA"
        validate "Creating HTTP STATUS monitor in Uptime Kuma"
        figlet "Monitor Added"
    fi

    save_state "create_replicalag_monitor"
    ;&
##########################################################
"create_replicalag_monitor")
    UPPERHOSTNAME=$(hostname | tr '[:lower:]' '[:upper:]')
    validate "Transforming hostname to uppercase"

    MONITOR_NAME="$UPPERHOSTNAME ReplicaLag"
    if check_monitor_exists "$MONITOR_NAME"; then
        figlet "Already Exists"
        log "Monitor '$MONITOR_NAME' already exists. Skipping creation."
    else
        log "Creating JSON data for ReplicaLag monitor"
        HTTP_DATA=$(jq -n --arg name "$UPPERHOSTNAME ReplicaLag" --arg hostname "$HOSTNAME.birthday.gold" --arg url "https://$HOSTNAME.birthday.gold/api/monitoror/dataserver_replicalag.php" --arg description "MySQL Replica Lag monitor for $HOSTNAME" '{
            type: "keyword",
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
            tags: ["WEB", "DATABASE"],
            notificationIDList: [1],
            method: "GET",
            httpBodyEncoding: "json",
            headers: {
                "HeaderName": "HeaderValue"
            },
            body: {
                "key": "value"
            },
            keyword: "seconds behind"
        }')
        validate "Creating JSON data for ReplicaLag monitor"

        echo ${HTTP_DATA} | tee -a $LOG_FILE

        log "Sending POST request to create ReplicaLag monitor"
        curl -k -X POST https://april21.bday.gold:5443/create_monitor -H "Content-Type: application/json" -d "$HTTP_DATA"
        validate "Creating ReplicaLag monitor in Uptime Kuma"
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
