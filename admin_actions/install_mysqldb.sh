#!/bin/bash

LOG_FILE=~/installhistory_mysql_$(date +"%Y%m%d%H%M%S").log
STATE_FILE=~/install_state_mysql
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
            log "Aborting installation"
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

log "Starting MySQL installation process on $(hostname)"

STATE=$(load_state)

# Check if the state is "completed" and no actions have been performed
if [ "$STATE" == "completed" ] && [ "$ACTION_COUNTER" -eq 0 ]; then
    figlet "Check State File"
    log "The state file [$STATE_FILE] = completed"
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
auto_increment_offset = 2

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

    save_state "setup_replication"
    ;&
##########################################################
"setup_replication")  
    ssh-keyscan -H july02.bday.gold >> ~/.ssh/known_hosts
    validate "Adding july02.bday.gold to known_hosts"

    log "Performing source MySQL dump ~4 minutes"
    figlet "Input Required"
    log "Enter july02.bday.gold OS [root LEGACY] user password"
    ssh root@july02.bday.gold 'mysqldump -u root --all-databases --single-transaction --flush-logs --source-data=2 --set-gtid-purged=ON | gzip > /tmp/dump.sql.gz'
    validate "Dumping MySQL data from july02.bday.gold"
    save_state "transfer_dump"
    ;&
##########################################################
"transfer_dump")
    figlet "Input Required"
    log "Enter july02.bday.gold OS [root LEGACY] user password"
    scp root@july02.bday.gold:/tmp/dump.sql.gz /tmp/.
    validate "Copying MySQL dump file"
    save_state "load_dbdump"
    ;&
##########################################################
"load_dbdump")
    log "Loading db dump ~5 minutes"
    gunzip < /tmp/dump.sql.gz | mysql -u root
    validate "Restoring MySQL dump"
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
    master_channel="channel_prod${master_num}_to_prod${slave_num}"    
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
    master_channel="channel_prod${master_num}_to_prod${slave_num}"
    
    figlet "Adding Replication Channel" 
    log "creating $master_channel on: $slave_host"
    figlet "Input Required"
    # Check if MYSQL_REPL_PASSWORD is set or empty
    if [ -z "$MYSQL_REPL_PASSWORD" ]; then
        read -sp "Enter MySQL [bgdbreplicator1] user password: " MYSQL_REPL_PASSWORD
    fi
    
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
    master_channel="channel_prod${slave_num}_to_prod${master_num}"
     # Prompt for the MySQL password
    figlet "Input Required"
    echo "Enter MySQL [birthday_gold_admin] user password:"        
    # Check if the replication channel exists
    channel_exists=$(mysql -u birthday_gold_admin -h${slave_host} -p -e "SHOW SLAVE STATUS FOR CHANNEL '$master_channel'\G" | grep -c "Channel_Name: $master_channel")
    if [ $channel_exists -gt 0 ]; then
        # Stop and reset the reverse replication channel
        figlet "Input Required"
        echo "Enter MySQL [birthday_gold_admin] user password:"
        mysql -u birthday_gold_admin -h${slave_host} -p -e "STOP SLAVE FOR CHANNEL '$master_channel'; RESET SLAVE ALL FOR CHANNEL '$master_channel';"
        validate "Replication reverse channel $master_channel removed"
    else
        figlet "Input Required"
        echo "Enter MySQL [birthday_gold_admin] user password:"
   mysql -u birthday_gold_admin -h${slave_host}  -e "SHOW SLAVE STATUS FOR CHANNEL '$master_channel'\G"  
        echo "Replication reverse channel $master_channel does not exist"
    fi 
    save_state "configure_reverse_replication"
    ;&
##########################################################
"configure_reverse_replication")   
 # Check if MYSQL_REPL_PASSWORD is set or empty
  figlet "Reverse Replication"
    if [ -z "$MYSQL_REPL_PASSWORD" ]; then
    figlet "Input Required"
        read -sp "Enter MySQL [bgdbreplicator1] user password: " MYSQL_REPL_PASSWORD
    fi 
    slave_host='july02.bday.gold'
    master_host=$(hostname).bday.gold    
    # Extract the numbers from the hostnames
    master_num=$(echo $master_host | grep -oP '\d+')
    slave_num=$(echo $slave_host | grep -oP '\d+')    
    master_channel="channel_prod${slave_num}_to_prod${master_num}"
    # Set up replication reverse channel
    figlet "Input Required"
    echo "Enter MySQL [birthday_gold_admin] user password:"
    mysql -u birthday_gold_admin -h${slave_host} -p -e "CHANGE MASTER TO 
     MASTER_HOST='$master_host',
     MASTER_USER='bgdbreplicator1',
     MASTER_PASSWORD='$MYSQL_REPL_PASSWORD',
     MASTER_PORT=3306,
     MASTER_AUTO_POSITION=1
     FOR CHANNEL '$master_channel';
    START SLAVE FOR CHANNEL '$master_channel';"
    validate "Setting up reverse replication"
    # Check replication status
   figlet "Input Required"
    echo "Enter MySQL [birthday_gold_admin] user password:"
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
