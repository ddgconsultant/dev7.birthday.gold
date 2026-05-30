#!/bin/bash

# Source DB hostname for system_availability record - defaults to july03.bday.gold
# Can be overridden via command line: ./install_haproxynode.sh [source_db_hostname]
SOURCE_DB_HOST="${1:-july03.bday.gold}"

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
log "Source DB host for system_availability: $SOURCE_DB_HOST"

# Set up systemd service for auto-resume after reboot
if [ ! -f /etc/systemd/system/haproxy-install-resume.service ]; then
    log "Creating auto-resume systemd service"
    cat > /etc/systemd/system/haproxy-install-resume.service <<'EOF'
[Unit]
Description=Resume HAProxy Installation After Reboot
After=network.target

[Service]
Type=oneshot
ExecStart=/root/install_haproxynode.sh
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
RESUME_STATE=$(load_resume_state)

# Check if the state is "completed" and no actions have been performed
if [ "$STATE" == "completed" ] && [ "$ACTION_COUNTER" -eq 0 ]; then
    figlet "Check State File"
    # Clean up auto-resume service
    systemctl disable haproxy-install-resume.service 2>/dev/null
    rm -f /etc/systemd/system/haproxy-install-resume.service
    systemctl daemon-reload
    log "Auto-resume service removed"
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
        log "IP address $IP_ADDRESS already exists in HAProxy configuration"
        read -p "Do you want to REMOVE existing entries and re-add? (y/n): " choice
        if [ "$choice" != "y" ]; then
            log "Aborting process"
            exit 1
        fi
        # Mark that we need to remove old entries before adding new ones
        export REMOVE_OLD_ENTRIES=1
        log "Will remove existing entries for $IP_ADDRESS before adding new ones"
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
        save_state "system_availability_mysqlrecord"
    fi
    [ -f $RESUME_FILE ] && rm $RESUME_FILE
    ;&
    ##########################################################
"system_availability_mysqlrecord")
    check_password_set "system_availability_mysqlrecord"

    figlet "System Availability"
    log "Inserting system availability record into $SOURCE_DB_HOST"

    # Get current hostname
    current_hostname=$(hostname)
    # Get current OS version
    os_version=$(lsb_release -d | awk -F'\t' '{print $2}')
    # Get current Apache version
    apache_version=$(apache2 -v | grep "Server version" | awk '{print $3}')
    # Get current PHP version
    php_version=$(php -v | head -n 1 | awk '{print $2}')
    # Get current host IP address
    ip_address=$(hostname -I | awk '{print $1}')

    # Prompt for MySQL admin password if not set
    if [ -z "$MYSQL_ADMIN_PASSWORD" ]; then
        echo ""
        read -sp "Enter MySQL password for birthday_gold_admin@$SOURCE_DB_HOST: " MYSQL_ADMIN_PASSWORD
        echo ""
    fi

    # Insert System Availability DB record
    mysql -u birthday_gold_admin -h${SOURCE_DB_HOST} -p"${MYSQL_ADMIN_PASSWORD}" -e "
    INSERT INTO \`birthday_gold_www\`.\`bg_system_availability\`
    (\`system_id\`, \`name\`, \`description\`, \`url\`, \`port\`, \`system_status\`, \`status\`, \`last_success_dt\`, \`last_failure_dt\`, \`create_dt\`, \`modify_dt\`)
    VALUES
    (180, '${current_hostname} / Production LAMP Stack', '=== Production LAMP Stack\n\n${os_version}\n+ ${apache_version}\n+ PHP ${php_version}\n+ MySQL 8 (ID: ###)', '${ip_address}', 80, 'green', 'A', NOW(), NOW(), NOW(), NOW());"
    validate "Insert System Availability DB record"

    save_state "backup_haproxy_cfg"
    ;&
    ##########################################################
"backup_haproxy_cfg")
    check_password_set "backup_haproxy_cfg"

    log "Backing up current HAProxy configuration"
    sshpass -p "$rootpass" ssh root@april21.bday.gold "cp /etc/haproxy/haproxy.cfg /etc/haproxy/haproxy.cfg_\$(date +%Y%m%d%H%M)"
    validate "Backing up HAProxy configuration"

    save_state "remove_old_entries"
    ;&
    ##########################################################
"remove_old_entries")
    check_password_set "remove_old_entries"

    # Remove any existing entries for this IP address to prevent duplicates
    log "Removing any existing entries for IP $IP_ADDRESS"
    sshpass -p "$rootpass" ssh root@april21.bday.gold "sed -i '/$IP_ADDRESS:80 check/d' /etc/haproxy/haproxy.cfg"
    sshpass -p "$rootpass" ssh root@april21.bday.gold "sed -i '/$IP_ADDRESS:443 ssl verify none check/d' /etc/haproxy/haproxy.cfg"
    log "Old entries removed (if any existed)"

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
