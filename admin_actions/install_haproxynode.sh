#!/bin/bash

LOG_FILE=~/haproxy_add_webserver_$(date +"%Y%m%d%H%M%S").log
STATE_FILE=~/haproxy_add_state
RESUME_FILE=~/install_haproxy_resumepassword
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

save_resume_state() {
    echo "$1" > $RESUME_FILE
}

load_resume_state() {
    if [ -f $RESUME_FILE ]; then
        cat $RESUME_FILE
    else
        echo ""
    fi
}

check_password_set() {
    if [ -z "$rootpass" ]; then
        save_resume_state $1
        save_state "get_password"
        figlet "Password Required"
        log "Password for root@april21.bday.gold is not set. Please rerun the script and provide the password."
        exit 1
    fi
}

log "Starting HAProxy webserver node addition process on $(hostname)"

STATE=$(load_state)
RESUME_STATE=$(load_resume_state)

# Check if the state is "completed" and no actions have been performed
if [ "$STATE" == "completed" ] && [ "$ACTION_COUNTER" -eq 0 ]; then
    figlet "Check State File"
    log "The state file [$STATE_FILE] = completed"
    exit 0
fi

if [ "$STATE" == "get_password" ] && [ -n "$RESUME_STATE" ]; then
    figlet "Input Required"
    read -sp "Enter password for [root LEGACY]: " rootpass
    STATE=$RESUME_STATE
    save_state $STATE
    rm $RESUME_FILE
fi

case $STATE in
"pre")
    log "Getting hostname and IP address"
    HOSTNAME=$(hostname -s)
    IP_ADDRESS=$(hostname -I | awk '{print $1}')
    validate "Getting hostname and IP address"

    log "Adding HAProxy server to known hosts"
    ssh-keyscan -H april21.bday.gold >> ~/.ssh/known_hosts
    validate "Adding HAProxy server to known hosts"

    sudo apt-get -y install sshpass
    validate "Install sshpass"

    log "Checking if IP address already exists in HAProxy configuration"
    if ssh root@april21.bday.gold "grep -q '$IP_ADDRESS' /etc/haproxy/haproxy.cfg"; then
        figlet "!! Host exists !!"
        read -p "IP address $IP_ADDRESS already exists in HAProxy configuration. Do you want to continue? (y/n): " choice
        if [ "$choice" != "y" ]; then
            log "Aborting process"
            exit 1
        fi
    fi

    save_state "get_password"
    ;&
    ##########################################################
"get_password")
    if [ -z "$rootpass" ]; then
        figlet "Input Required"
        read -sp "Enter password for [root LEGACY]: " rootpass
    fi
    if [ -n "$RESUME_STATE" ]; then
        STATE=$RESUME_STATE
        save_state $STATE
    else        
        save_state "backup_haproxy_cfg"
    fi
    [ -f $RESUME_FILE ] && rm $RESUME_FILE
    ;&
    ##########################################################
"backup_haproxy_cfg")
    check_password_set "backup_haproxy_cfg"

    log "Backing up current HAProxy configuration"
    sshpass -p "$rootpass" ssh root@april21.bday.gold "cp /etc/haproxy/haproxy.cfg /etc/haproxy/haproxy.cfg_\$(date +%Y%m%d%H%M)"
    validate "Backing up HAProxy configuration"

    save_state "add_http_server"
    ;&
    ##########################################################
"add_http_server")
    check_password_set "add_http_server"

    log "Adding HTTP server configuration"
    sshpass -p "$rootpass" ssh root@april21.bday.gold "sed -i '/## END OF 80webservers-do not delete this line - it is used to add new webservers/i\    server $HOSTNAME $IP_ADDRESS:80 check' /etc/haproxy/haproxy.cfg"
    validate "Adding HTTP server configuration"

    save_state "add_https_server"
    ;&
    ##########################################################
"add_https_server")
    check_password_set "add_https_server"

    log "Adding HTTPS server configuration"
    sshpass -p "$rootpass" ssh root@april21.bday.gold "sed -i '/## END OF 443webservers-do not delete this line - it is used to add new webservers/i\    server $HOSTNAME $IP_ADDRESS:443 ssl verify none check' /etc/haproxy/haproxy.cfg"
    validate "Adding HTTPS server configuration"

    save_state "validate_haproxy_cfg"
    ;&
    ##########################################################
"validate_haproxy_cfg")
    check_password_set "validate_haproxy_cfg"

    log "Validating HAProxy configuration"
    sshpass -p "$rootpass" ssh root@april21.bday.gold "haproxy -c -f /etc/haproxy/haproxy.cfg"
    validate "Validating HAProxy configuration"

    save_state "reload_haproxy"
    ;&
    ##########################################################
"restart_haproxy")
    check_password_set "restart_haproxy"

    log "Restarting HAProxy service"
    sshpass -p "$rootpass" ssh root@april21.bday.gold "systemctl restart haproxy"
    validate "Restarting HAProxy service"

    save_state "completed"
    ;&
    ##########################################################
"reload_haproxy")
    check_password_set "reload_haproxy"

    log "Reload HAProxy service"
    sshpass -p "$rootpass" ssh root@april21.bday.gold "systemctl reload haproxy"
    validate "Reload HAProxy service"

    save_state "completed"
    ;&
    ##########################################################
"completed")
    figlet "HAProxy Node Added"
    log "HAProxy webserver node addition process completed successfully on $(hostname)"
    ;;
*)
    log "Unknown state: $STATE"
    exit 1
    ;;
esac
