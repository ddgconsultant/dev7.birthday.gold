#!/bin/bash

LOG_FILE=~/install_powerdns_$(date +"%Y%m%d%H%M%S").log
STATE_FILE=~/install_powerdns_state
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

log "Starting PowerDNS installation process on $(hostname)"

STATE=$(load_state)

# Check if the state is "completed" and no actions have been performed
if [ "$STATE" == "completed" ] && [ "$ACTION_COUNTER" -eq 0 ]; then
    figlet "Check State File"
    log "The state file [$STATE_FILE] = completed"
    exit 0
fi
##########################################################
########################################################## 
case $STATE in
"pre")
    figlet "Starting"
    log "Updating package list"
    apt update
    validate "Updating package list"

    log "Installing MySQL server"
    sudo apt install mysql-server -y
    validate "Installing MySQL server"

    log "Running mysql_secure_installation"
    sudo mysql_secure_installation
    validate "Running mysql_secure_installation"

    log "Creating PowerDNS database and user"
    sudo mysql -e "CREATE DATABASE powerdns;"
    sudo mysql -e "CREATE USER 'powerdns'@'localhost' IDENTIFIED BY '{{SUPERSECUREPASSWORD}}';"
    sudo mysql -e "GRANT ALL PRIVILEGES ON powerdns.* TO 'powerdns'@'localhost';"
    sudo mysql -e "FLUSH PRIVILEGES;"
    validate "Creating PowerDNS database and user"

    save_state "add_pdns_repo"
    ;&
    ##########################################################
"add_pdns_repo")
    log "Adding PowerDNS repository and installing PowerDNS"
    sudo install -d /etc/apt/keyrings
    curl https://repo.powerdns.com/FD380FBB-pub.asc | sudo tee /etc/apt/keyrings/auth-49-pub.asc
    echo "deb [signed-by=/etc/apt/keyrings/auth-49-pub.asc] http://repo.powerdns.com/debian bullseye-auth-49 main" | sudo tee /etc/apt/sources.list.d/pdns.list
    sudo apt-get update
    validate "Adding PowerDNS repository"

    log "Installing PowerDNS server and MySQL backend"
    sudo apt-get install pdns-server pdns-backend-mysql -y
    validate "Installing PowerDNS server and MySQL backend"

    save_state "configure_pdns"
    ;&
    ##########################################################
"configure_pdns")
    log "Disabling and stopping systemd-resolved"
    sudo systemctl disable systemd-resolved
    sudo systemctl stop systemd-resolved
    sudo rm /etc/resolv.conf
    echo "nameserver 8.8.8.8" | sudo tee /etc/resolv.conf
    validate "Disabling and stopping systemd-resolved"

    log "Restarting PowerDNS"
    sudo systemctl restart pdns
    validate "Restarting PowerDNS"

    log "Backing up original configuration files"
    cd /etc/powerdns/
    sudo mv named.conf named.conf.orig
    sudo mv pdns.conf pdns.conf.orig
    cd pdns.d/
    sudo mv bind.conf bind.conf.orig
    validate "Backing up original configuration files"

    log "Creating and configuring pdns.local.gmysql.conf"
    sudo tee /etc/powerdns/pdns.d/pdns.local.gmysql.conf > /dev/null <<EOL
# MySQL Configuration for PowerDNS
launch=gmysql
gmysql-host=localhost
gmysql-user=powerdns
gmysql-password={{SUPERSECUREPASSWORD}}
gmysql-dbname=powerdns
EOL
    validate "Creating and configuring pdns.local.gmysql.conf"

    log "Replacing pdns.conf with the desired configuration"
    sudo cp /root/pdns.conf /etc/powerdns/
    validate "Replacing pdns.conf with the desired configuration"

    log "Setting correct permissions"
    sudo chown root:pdns /etc/powerdns/pdns.conf
    validate "Setting correct permissions"

    save_state "restart_pdns"
    ;&
    ##########################################################
"restart_pdns")
    log "Restarting and enabling PowerDNS service"
    sudo systemctl restart pdns
    sudo systemctl enable pdns
    validate "Restarting and enabling PowerDNS service"

    log "Checking PowerDNS status"
    sudo systemctl status pdns
    validate "Checking PowerDNS status"

    save_state "completed"
    ;&
    ##########################################################
"completed")
    log "PowerDNS installation and configuration process completed successfully on $(hostname)"
    ;;
*)
    log "Unknown state: $STATE"
    exit 1
    ;;
esac
