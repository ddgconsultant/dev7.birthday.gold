#!/bin/bash

LOG_FILE=~/haproxy_add_web_$(date +"%Y%m%d%H%M%S").log
STATE_FILE=~/haproxy_add_state_web
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

log "Starting HAProxy webserver addition process"

# Set up systemd service for auto-resume after reboot
if [ ! -f /etc/systemd/system/haproxy-install-resume.service ]; then
    log "Creating auto-resume systemd service"
    cat > /etc/systemd/system/haproxy-install-resume.service <<'EOF'
[Unit]
Description=Resume HAProxy Configuration After Reboot
After=network.target

[Service]
Type=oneshot
ExecStart=/root/install_addtohaproxy_web.sh
RemainAfterExit=yes
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF
    systemctl enable haproxy-install-resume.service
    log "Auto-resume service enabled"
fi

STATE=$(load_state)

# Check if the state is "completed" and no actions have been performed
if [ "$STATE" == "completed" ] && [ "$ACTION_COUNTER" -eq 0 ]; then
    figlet "Check State File"
    log "The state file [$STATE_FILE] = completed"
    # Clean up auto-resume service
    systemctl disable haproxy-install-resume.service 2>/dev/null
    rm -f /etc/systemd/system/haproxy-install-resume.service
    systemctl daemon-reload
    log "Auto-resume service removed"
    exit 0
fi

case $STATE in
"pre")
    figlet "Starting"

    # Get current hostname (e.g., july05)
    HOSTNAME=$(hostname)
    SERVER_NAME=$(echo "$HOSTNAME" | cut -d'.' -f1)

    # Get server IP address
    SERVER_IP=$(hostname -I | awk '{print $1}')

    log "Server Name: $SERVER_NAME"
    log "Server IP: $SERVER_IP"
    validate "Retrieved hostname and IP address"

    # Ensure sshpass is installed
    if ! command -v sshpass &> /dev/null; then
        log "sshpass not found, installing..."
        apt-get update && apt-get install -y sshpass
        validate "Installing sshpass"
    fi

    save_state "fetch_haproxy_config"
    ;&
##########################################################
"fetch_haproxy_config")
    figlet "Fetch Config"

    # Define HAProxy nodes (add more if you have multiple HAProxy servers)
    HAPROXY_NODES=("april21.bday.gold")
    PRIMARY_NODE="${HAPROXY_NODES[0]}"

    log "Fetching current HAProxy configuration from $PRIMARY_NODE"

    figlet "Input Required"
    read -s -p "Enter root password for HAProxy servers: " ROOT_PASSWORD
    echo ""

    # Fetch the current haproxy.cfg from the primary node
    sshpass -p "$ROOT_PASSWORD" scp root@${PRIMARY_NODE}:/etc/haproxy/haproxy.cfg /tmp/haproxy.cfg
    validate "Fetching HAProxy configuration from $PRIMARY_NODE"

    save_state "update_haproxy_config"
    ;&
##########################################################
"update_haproxy_config")
    figlet "Update Config"

    log "Updating HAProxy configuration with new webserver"

    # Check if server already exists in config
    if grep -q "server $SERVER_NAME $SERVER_IP" /tmp/haproxy.cfg; then
        log "WARNING: Server $SERVER_NAME already exists in HAProxy configuration"
        log "Skipping configuration update"
    else
        # Add HTTP backend entry (port 80)
        sed -i "/## END OF 80webservers/i\    server $SERVER_NAME $SERVER_IP:80 check" /tmp/haproxy.cfg
        validate "Adding HTTP backend entry for $SERVER_NAME"

        # Add HTTPS backend entry (port 443)
        sed -i "/## END OF 443webservers/i\    server $SERVER_NAME $SERVER_IP:443 ssl verify none check" /tmp/haproxy.cfg
        validate "Adding HTTPS backend entry for $SERVER_NAME"

        log "Configuration updated successfully"
    fi

    save_state "deploy_haproxy_config"
    ;&
##########################################################
"deploy_haproxy_config")
    figlet "Deploy Config"

    log "Deploying updated HAProxy configuration to all nodes"

    # Deploy to all HAProxy nodes
    for node in "${HAPROXY_NODES[@]}"; do
        log "Deploying to $node"

        # Backup existing config on remote server
        sshpass -p "$ROOT_PASSWORD" ssh root@${node} "cp /etc/haproxy/haproxy.cfg /etc/haproxy/haproxy.cfg.backup.$(date +%Y%m%d%H%M%S)"
        validate "Backing up HAProxy config on $node"

        # Copy new config
        sshpass -p "$ROOT_PASSWORD" scp /tmp/haproxy.cfg root@${node}:/etc/haproxy/haproxy.cfg
        validate "Deploying HAProxy config to $node"

        # Test configuration
        log "Testing HAProxy configuration on $node"
        sshpass -p "$ROOT_PASSWORD" ssh root@${node} "haproxy -c -f /etc/haproxy/haproxy.cfg"
        validate "Testing HAProxy configuration on $node"

        # Reload HAProxy service
        log "Reloading HAProxy service on $node"
        sshpass -p "$ROOT_PASSWORD" ssh root@${node} "systemctl reload haproxy"
        validate "Reloading HAProxy on $node"

        # Check HAProxy status
        sshpass -p "$ROOT_PASSWORD" ssh root@${node} "systemctl status haproxy --no-pager --lines=5"
        validate "Checking HAProxy status on $node"
    done

    save_state "verify_deployment"
    ;&
##########################################################
"verify_deployment")
    figlet "Verify"

    log "Verifying server is in HAProxy rotation"

    for node in "${HAPROXY_NODES[@]}"; do
        log "Checking configuration on $node"
        sshpass -p "$ROOT_PASSWORD" ssh root@${node} "grep '$SERVER_NAME' /etc/haproxy/haproxy.cfg"
        validate "Verifying $SERVER_NAME in HAProxy config on $node"
    done

    log "Server $SERVER_NAME ($SERVER_IP) has been successfully added to HAProxy"
    log "The server is now in the load balancer rotation"

    # Update the local dev repository haproxy.cfg
    log "Updating local repository HAProxy config"
    cp /tmp/haproxy.cfg /var/www/BIRTHDAY_SERVER/dev7.birthday.gold/admin_actions/haproxy.cfg 2>/dev/null || \
    log "Note: Could not update local dev repository (this is normal on production servers)"

    save_state "completed"
    ;&
##########################################################
"completed")
    figlet "Completed"
    log "HAProxy webserver addition process completed successfully"
    log "Server: $SERVER_NAME ($SERVER_IP)"
    log "Next steps:"
    log "  - Run install_uptime_monitors_web.sh to add monitoring"
    log "  - Run install_addtometabase_web.sh to add to Metabase"
    log "  - Test website access through HAProxy"
    ;;
*)
    log "Unknown state: $STATE"
    exit 1
    ;;
esac
