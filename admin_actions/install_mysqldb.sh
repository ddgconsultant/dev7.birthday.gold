#!/bin/bash

# Use fixed log filename so we can always tail it after reboots
LOG_FILE=~/installhistory_mysql_current.log
# Also create a timestamped backup
BACKUP_LOG=~/installhistory_mysql_$(date +"%Y%m%d%H%M%S").log
STATE_FILE=~/install_state_mysql
ACTION_COUNTER=0

# Initialize log file if it doesn't exist
if [ ! -f "$LOG_FILE" ]; then
    touch "$LOG_FILE"
fi

log() {
    echo "$(date +"%Y-%m-%d %H:%M:%S") - $1" | tee -a $LOG_FILE -a $BACKUP_LOG
    echo ""
}

validate() {
    if [ $? -ne 0 ]; then
        figlet "FAIL" | tee -a $LOG_FILE
        log "FAIL: $1 (auto-continuing)"
        # Auto-continue on errors - do not prompt for input
        log "Continuing installation despite error..."
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

log "Starting MySQL installation process on $(hostname)"

# ============================================================================
# CHECK AND PROMPT FOR REQUIRED PASSWORDS
# ============================================================================
figlet "Password Check"

# Check for LEGACY_ROOT_PASSWORD (SSH password for july02.bday.gold)
if [ -z "$LEGACY_ROOT_PASSWORD" ]; then
    echo ""
    echo "=========================================================================="
    echo "LEGACY_ROOT_PASSWORD is NOT set!"
    echo "This is the ROOT SSH password for july02.bday.gold"
    echo "Used for: SSH to july02 to dump the database"
    echo "=========================================================================="
    read -s -p "Enter ROOT SSH password for july02.bday.gold: " LEGACY_ROOT_PASSWORD
    echo ""
    export LEGACY_ROOT_PASSWORD
else
    log "✓ LEGACY_ROOT_PASSWORD is set"
fi

# Check for MYSQL_REPL_PASSWORD (bgdbreplicator1 MySQL user password)
if [ -z "$MYSQL_REPL_PASSWORD" ]; then
    echo ""
    echo "=========================================================================="
    echo "MYSQL_REPL_PASSWORD is NOT set!"
    echo "This is the MySQL password for user: bgdbreplicator1"
    echo "Used for: MySQL replication between servers"
    echo "=========================================================================="
    read -s -p "Enter MySQL password for 'bgdbreplicator1': " MYSQL_REPL_PASSWORD
    echo ""
    export MYSQL_REPL_PASSWORD
else
    log "✓ MYSQL_REPL_PASSWORD is set"
fi

# Check for MYSQL_ADMIN_PASSWORD (birthday_gold_admin MySQL user password)
if [ -z "$MYSQL_ADMIN_PASSWORD" ]; then
    echo ""
    echo "=========================================================================="
    echo "MYSQL_ADMIN_PASSWORD is NOT set!"
    echo "This is the MySQL password for user: birthday_gold_admin"
    echo "Used for: Remote MySQL admin operations on july02.bday.gold"
    echo "=========================================================================="
    read -s -p "Enter MySQL password for 'birthday_gold_admin': " MYSQL_ADMIN_PASSWORD
    echo ""
    export MYSQL_ADMIN_PASSWORD
else
    log "✓ MYSQL_ADMIN_PASSWORD is set"
fi

echo ""
log "All required passwords are now set. Proceeding with installation..."
echo ""

# Set up systemd service for auto-resume after reboot
if [ ! -f /etc/systemd/system/mysql-install-resume.service ]; then
    log "Creating auto-resume systemd service"
    cat > /etc/systemd/system/mysql-install-resume.service <<'EOF'
[Unit]
Description=Resume MySQL Installation After Reboot
After=network.target

[Service]
Type=oneshot
ExecStart=/root/install_mysqldb.sh
RemainAfterExit=yes
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF
    systemctl enable mysql-install-resume.service
    log "Auto-resume service enabled"
fi

STATE=$(load_state)

# Check if the state is "completed" and no actions have been performed
if [ "$STATE" == "completed" ] && [ "$ACTION_COUNTER" -eq 0 ]; then
    figlet "Check State File"
    log "The state file [$STATE_FILE] = completed"
    # Clean up auto-resume service
    systemctl disable mysql-install-resume.service 2>/dev/null
    rm -f /etc/systemd/system/mysql-install-resume.service
    systemctl daemon-reload
    log "Auto-resume service removed"
    exit 0
fi

case $STATE in
"pre")
    export DEBIAN_FRONTEND=noninteractive
    figlet "Starting MySQL INSTALL"
    apt update -y
    validate "Running apt update"

    apt -y install mysql-server
    validate "Installing MySQL server"

    apt -y install mysql-client mysql-client-core-8.0

    save_state "configure_mysql"
    ;&
##########################################################
"configure_mysql")
    # Configure MySQL settings
    figlet "Configure MySQL"
    cd /etc/mysql/mysql.conf.d/
    sed -i '/^bind-address/s/^/#/' /etc/mysql/mysql.conf.d/mysqld.cnf
    validate "Commenting bind-address"

    # Create custom my.cnf files
    bash -c 'cat <<EOF > /etc/mysql/mysql.conf.d/99-mysql_1-settings.cnf
[mysqld]
#=========================================================================
read_only = 0
super_read_only = OFF

#=========================================================================
# Network Settings
#-------------------------------------------------
bind-address = 0.0.0.0
max_connections = 5000
max_connect_errors = 1000000

# Allow MySQL X Protocol connections (if using X Protocol)
mysqlx-bind-address = 127.0.0.1

# Admin connections (setting up super read-only for admin users)
skip_name_resolve = 1

# If you need an admin port (to manage administrative connections)
admin-address = 127.0.0.1
admin_port = 33062

#=========================================================================
# Buffer Pool Settings
#-------------------------------------------------
innodb_buffer_pool_size = $(awk "/MemTotal/ { printf \"%.0f\", \$2*0.8/1024/1024 }" /proc/meminfo)G
innodb_buffer_pool_instances = 8
innodb_flush_log_at_trx_commit = 2 
innodb_flush_neighbors = 0
innodb_buffer_pool_dump_at_shutdown = ON
innodb_buffer_pool_load_at_startup = ON

#=========================================================================
# Table Cache Settings
#-------------------------------------------------
table_open_cache = 4000
table_open_cache_instances = 16

#=========================================================================
# Thread Settings
#-------------------------------------------------
innodb_thread_concurrency = 0
innodb_read_io_threads = 64
innodb_write_io_threads = 64
innodb_io_capacity = 2000
innodb_io_capacity_max = 4000

#=========================================================================
# Log Buffer Settings
#-------------------------------------------------
innodb_log_buffer_size = 256M

#=========================================================================
# Temp Table Settings
#-------------------------------------------------
tmp_table_size = 512M
max_heap_table_size = 512M

#=========================================================================
# Other InnoDB Settings
#-------------------------------------------------
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1
innodb_stats_on_metadata = off

#=========================================================================
# Performance Schema
#-------------------------------------------------
performance_schema = ON
performance_schema_instrument = "% = ON"

#=========================================================================
# Adjust the following paths based on your system
#-------------------------------------------------
log_error = /var/log/mysql/error.log
slow_query_log = 1
slow_query_log_file = mysql-slow.log
long_query_time = 5
EOF'
    validate "Creating 99-mysql_1-settings.cnf"

    # Create mysql_2-replication.cnf
    hostname=$(hostname)
    month=$(echo $hostname | grep -oP '^[a-zA-Z]+')
    day=$(echo $hostname | grep -oP '\d+$')

    case "$month" in
        jan*) month_num=1 ;;
        feb*) month_num=2 ;;
        mar*) month_num=3 ;;
        apr*) month_num=4 ;;
        may*) month_num=5 ;;
        jun*) month_num=6 ;;
        jul*) month_num=7 ;;
        aug*) month_num=8 ;;
        sep*) month_num=9 ;;
        oct*) month_num=10 ;;
        nov*) month_num=11 ;;
        dec*) month_num=12 ;;
        *) echo "Invalid month in hostname"; exit 1 ;;
    esac

    server_id="${month_num}${day}"

    # Get last digit of day for auto_increment_offset (july05=5, july08=8, etc)
    last_digit=${day: -1}

    bash -c "cat <<EOF > /etc/mysql/mysql.conf.d/99-mysql_2-replication.cnf
[mysqld]
#=========================================================================
server-id = $server_id
log_bin = /var/log/mysql/mysql-bin.log
log_timestamps = SYSTEM

log_replica_updates = 1
binlog_expire_logs_seconds = 3888000
max_binlog_size = 10737418240   #1G

sync_binlog = 0

relay-log = mysql-relay-bin.log
relay-log-index = /var/log/mysql/mysql-relay-bin.index
relay-log-info-repository=TABLE
relay-log-recovery = 1

# Auto Increment Settings for Master-Master
auto_increment_increment = 10
auto_increment_offset = $last_digit

replicate-ignore-db = ccswag_dev8
slave_skip_errors = 1062

# GTID replication settings
gtid_mode = ON
enforce_gtid_consistency = ON
EOF"
    validate "Creating 99-mysql_2-replication.cnf"

    save_state "restart_mysql"
    ;&
##########################################################
"restart_mysql")
    systemctl restart mysql
    validate "Restarting MySQL service"

    save_state "dump_dbdump"
    ;&
##########################################################
"dump_dbdump")
    figlet "Dump Data"
    log "Performing MySQL dump from july02.bday.gold (this takes ~15 minutes)"
    log "Using birthday_gold_admin user to connect to july02.bday.gold MySQL"

    # Dump directly from july02's MySQL to local file (no SSH needed)
    mysqldump -h july02.bday.gold -u birthday_gold_admin -p"$MYSQL_ADMIN_PASSWORD" \
        --all-databases --single-transaction --flush-logs \
        --source-data=2 --set-gtid-purged=ON --force | gzip > /tmp/dump.sql.gz
    validate "Dumping MySQL data from july02.bday.gold"

    # Check dump file size
    DUMP_SIZE=$(du -h /tmp/dump.sql.gz | cut -f1)
    log "Dump file created: /tmp/dump.sql.gz (Size: $DUMP_SIZE)"

    save_state "load_dbdump"
    ;&
##########################################################
"load_dbdump")
    figlet "Load Data"
    log "Loading database dump into local MySQL (~15 minutes)"
    log "Decompressing and importing /tmp/dump.sql.gz"

    # Import with --force to continue on errors, capture warnings/errors
    gunzip < /tmp/dump.sql.gz | mysql -u root --force 2>&1 | tee -a $LOG_FILE
    validate "Restoring MySQL dump"

    log "Database import completed successfully"
    save_state "configure_replication"
    ;&
##########################################################
"remove_replication_channel")
    figlet "Removing Replication Channel"    
    master_host='july02.bday.gold'
    slave_host=$(hostname)        
    # Extract the numbers from the hostnames
    master_num=$(echo $master_host | grep -oP '\d+')
    slave_num=$(echo $slave_host | grep -oP '\d+')    
    master_channel="channelsource_prod${master_num}_to_prod${slave_num}"    
    # Check if the replication channel exists
    channel_exists=$(mysql -u root -e "SHOW SLAVE STATUS FOR CHANNEL '$master_channel'\G" | grep -c "Channel_Name: $master_channel")
        if [ $channel_exists -gt 0 ]; then
        log "Removing $master_channel on: $slave_host"
        # Stop and reset the replication channel --- on current secondary/localhost
        mysql -u root -e "STOP SLAVE FOR CHANNEL '$master_channel'; RESET SLAVE ALL FOR CHANNEL '$master_channel';"
        validate "Replication channel $master_channel removed on $slave_host"
    else
        log "Replication channel $master_channel does not exist on $slave_host - no remove action taken."
    fi    
    save_state "configure_replication"
;&
##########################################################
"configure_replication")   
    master_host='july02.bday.gold'
    slave_host=$(hostname)    
    # Extract the numbers from the hostnames
    master_num=$(echo $master_host | grep -oP '\d+')
    slave_num=$(echo $slave_host | grep -oP '\d+')    
    master_channel="channelsource_prod${master_num}_to_prod${slave_num}"
    
    figlet "Adding Replication Channel" 
    log "creating $master_channel on: $slave_host"
    # Password should be pre-set via environment variable MYSQL_REPL_PASSWORD

    # Set up replication
    mysql -u root -e "CHANGE MASTER TO 
     MASTER_HOST='$master_host',
     MASTER_USER='bgdbreplicator1',
     MASTER_PASSWORD='$MYSQL_REPL_PASSWORD',
     MASTER_PORT=3306,
     MASTER_AUTO_POSITION=1
     FOR CHANNEL '$master_channel';
    START SLAVE FOR CHANNEL '$master_channel';"
    validate "Setting up replication on $slave_host"
    # Check replication status  --- on current secondary/localhost
    mysql -u root -e "SHOW SLAVE STATUS FOR CHANNEL '$master_channel'\G"
    validate "Checking replication status on $slave_host"
    save_state "remove_reverse_replication_channel"
    ;&
##########################################################
"remove_reverse_replication_channel")
    figlet "Removing Reverse Replication Channel"        
    slave_host='july02.bday.gold'
    master_host=$(hostname).bday.gold
    # Extract the numbers from the hostnames
   master_num=$(echo $master_host | grep -oP '\d+')
    slave_num=$(echo $slave_host | grep -oP '\d+')
    master_channel="channelsource_prod${slave_num}_to_prod${master_num}"

    log "Checking reverse replication channel on july02 using MYSQL_ADMIN_PASSWORD"
    # Check if the replication channel exists
    channel_exists=$(mysql -u birthday_gold_admin -h${slave_host} -p${MYSQL_ADMIN_PASSWORD} -e "SHOW SLAVE STATUS FOR CHANNEL '$master_channel'\G" | grep -c "Channel_Name: $master_channel")
    if [ $channel_exists -gt 0 ]; then
        log "Removing reverse replication channel $master_channel on july02"
        mysql -u birthday_gold_admin -h${slave_host} -p${MYSQL_ADMIN_PASSWORD} -e "STOP SLAVE FOR CHANNEL '$master_channel'; RESET SLAVE ALL FOR CHANNEL '$master_channel';"
        validate "Replication reverse channel $master_channel removed"
    else
        log "Reverse replication channel $master_channel does not exist - checking status"
        mysql -u birthday_gold_admin -h${slave_host} -p${MYSQL_ADMIN_PASSWORD} -e "SHOW SLAVE STATUS FOR CHANNEL '$master_channel'\G"
        echo "Replication reverse channel $master_channel does not exist"
    fi 
    save_state "configure_reverse_replication"
    ;&
##########################################################
"configure_reverse_replication")
    figlet "Reverse Replication"

    slave_host='july02.bday.gold'
    master_host=$(hostname).bday.gold
    # Extract the numbers from the hostnames
    master_num=$(echo $master_host | grep -oP '\d+')
    slave_num=$(echo $slave_host | grep -oP '\d+')
    master_channel="channelsource_prod${slave_num}_to_prod${master_num}"

    log "Setting up reverse replication channel $master_channel on july02"
    log "Using MYSQL_ADMIN_PASSWORD to connect to july02"
    log "Using MYSQL_REPL_PASSWORD for bgdbreplicator1 user"

    mysql -u birthday_gold_admin -h${slave_host} -p${MYSQL_ADMIN_PASSWORD} -e "CHANGE MASTER TO
     MASTER_HOST='$master_host',
     MASTER_USER='bgdbreplicator1',
     MASTER_PASSWORD='$MYSQL_REPL_PASSWORD',
     MASTER_PORT=3306,
     MASTER_AUTO_POSITION=1
     FOR CHANNEL '$master_channel';
    START SLAVE FOR CHANNEL '$master_channel';"
    validate "Setting up reverse replication"

    # Check replication status
    log "Checking reverse replication status"
    mysql -u root -e "SHOW SLAVE STATUS FOR CHANNEL '$master_channel'\G"
    validate "Checking replication status"
    save_state "restart"
    ;&
##########################################################
"restart")
    log "restarting DB"
    service mysql restart
    validate "Restarted DB"
    save_state "completed"
    ;&
##########################################################
"completed")
    figlet "MySQL Setup Complete"
    log "MySQL installation and configuration process completed successfully on $(hostname)"
    ;;
*)
    log "Unknown state: $STATE"
    exit 1
    ;;
esac
