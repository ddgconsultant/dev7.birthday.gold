#!/bin/bash

# ============================================================================
# MYSQL DATABASE SETUP FOR CLIENTBASE.DDG.MX
# ============================================================================
# This script sets up MySQL databases for DDG, PROQCS, SPROVISIONS projects.
# Standalone MySQL server - NOT connected to birthday.gold mesh.
#
# Target: clientbase.ddg.mx (31.220.62.118)
# Ubuntu: 24.04 LTS
#
# Created: 2025-11-26
# ============================================================================

LOG_FILE=~/installhistory_ddg_mysql_$(date +"%Y%m%d%H%M%S").log

log() {
    echo "$(date +"%Y-%m-%d %H:%M:%S") - $1" | tee -a $LOG_FILE
}

validate() {
    if [ $? -ne 0 ]; then
        log "FAIL: $1"
    else
        log "PASS: $1"
    fi
}

log "=========================================="
log "MYSQL DATABASE SETUP FOR CLIENTBASE.DDG.MX"
log "=========================================="

# Check if MySQL root password file exists
if [ ! -f /root/.mysql_root_password ]; then
    log "ERROR: MySQL root password file not found at /root/.mysql_root_password"
    log "Please run install_ddg_webserver.sh first"
    exit 1
fi

MYSQL_ROOT_PASS=$(cat /root/.mysql_root_password | grep "MySQL root password:" | cut -d' ' -f4)

if [ -z "$MYSQL_ROOT_PASS" ]; then
    log "ERROR: Could not read MySQL root password"
    exit 1
fi

log "MySQL root password loaded"

##########################################################
## CREATE DATABASES AND USERS
##########################################################

# Generate random passwords for each database user
DDG_DB_PASS=$(openssl rand -base64 16)
PROQCS_DB_PASS=$(openssl rand -base64 16)
SPROVISIONS_DB_PASS=$(openssl rand -base64 16)

log "Creating databases and users..."

# Create DDG database and user
mysql -u root -p"$MYSQL_ROOT_PASS" <<EOF
-- DDG Database
CREATE DATABASE IF NOT EXISTS ddg_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'ddg_admin'@'localhost' IDENTIFIED BY '$DDG_DB_PASS';
CREATE USER IF NOT EXISTS 'ddg_admin'@'%' IDENTIFIED BY '$DDG_DB_PASS';
GRANT ALL PRIVILEGES ON ddg_production.* TO 'ddg_admin'@'localhost';
GRANT ALL PRIVILEGES ON ddg_production.* TO 'ddg_admin'@'%';

-- PROQCS Database
CREATE DATABASE IF NOT EXISTS proqcs_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'proqcs_admin'@'localhost' IDENTIFIED BY '$PROQCS_DB_PASS';
CREATE USER IF NOT EXISTS 'proqcs_admin'@'%' IDENTIFIED BY '$PROQCS_DB_PASS';
GRANT ALL PRIVILEGES ON proqcs_production.* TO 'proqcs_admin'@'localhost';
GRANT ALL PRIVILEGES ON proqcs_production.* TO 'proqcs_admin'@'%';

-- SPROVISIONS Database
CREATE DATABASE IF NOT EXISTS sprovisions_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'sprovisions_admin'@'localhost' IDENTIFIED BY '$SPROVISIONS_DB_PASS';
CREATE USER IF NOT EXISTS 'sprovisions_admin'@'%' IDENTIFIED BY '$SPROVISIONS_DB_PASS';
GRANT ALL PRIVILEGES ON sprovisions_production.* TO 'sprovisions_admin'@'localhost';
GRANT ALL PRIVILEGES ON sprovisions_production.* TO 'sprovisions_admin'@'%';

FLUSH PRIVILEGES;
EOF
validate "Creating databases and users"

# Save credentials to secure file
cat > /root/.ddg_db_credentials <<EOF
# DDG Database Credentials
# Created: $(date)
# Server: $(hostname)
# ==========================================

DDG Database:
  Database: ddg_production
  User: ddg_admin
  Password: $DDG_DB_PASS

PROQCS Database:
  Database: proqcs_production
  User: proqcs_admin
  Password: $PROQCS_DB_PASS

SPROVISIONS Database:
  Database: sprovisions_production
  User: sprovisions_admin
  Password: $SPROVISIONS_DB_PASS

# ==========================================
# Connection strings for PHP:
# DDG: mysql:host=localhost;dbname=ddg_production
# PROQCS: mysql:host=localhost;dbname=proqcs_production
# SPROVISIONS: mysql:host=localhost;dbname=sprovisions_production
EOF
chmod 600 /root/.ddg_db_credentials
validate "Saving database credentials"

log "Database credentials saved to /root/.ddg_db_credentials"

##########################################################
## CONFIGURE MYSQL SETTINGS (99-mysql_1-settings.cnf format)
##########################################################
log "Configuring MySQL settings..."

# Comment out default bind-address in mysqld.cnf
sed -i '/^bind-address/s/^/#/' /etc/mysql/mysql.conf.d/mysqld.cnf
validate "Commenting default bind-address"

# Calculate buffer pool size (80% of RAM)
BUFFER_POOL_SIZE=$(awk '/MemTotal/ { printf "%.0f", $2*0.8/1024/1024 }' /proc/meminfo)
log "Calculated innodb_buffer_pool_size: ${BUFFER_POOL_SIZE}G"

# Create 99-mysql_1-settings.cnf
cat > /etc/mysql/mysql.conf.d/99-mysql_1-settings.cnf <<EOF
[mysqld]
#=========================================================================
# DDG/CLIENTBASE MySQL Settings
# Created: $(date)
# Server: $(hostname) - STANDALONE (no replication)
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
innodb_buffer_pool_size = ${BUFFER_POOL_SIZE}G
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
# Logging
#-------------------------------------------------
log_error = /var/log/mysql/error.log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/mysql-slow.log
long_query_time = 5
EOF
validate "Creating 99-mysql_1-settings.cnf"

# Create 99-mysql_2-standalone.cnf (no replication, but keeping binary logging for backup purposes)
cat > /etc/mysql/mysql.conf.d/99-mysql_2-standalone.cnf <<EOF
[mysqld]
#=========================================================================
# DDG/CLIENTBASE Standalone Settings (NO REPLICATION)
# Created: $(date)
# Server: $(hostname)
#=========================================================================
# Server ID (unique identifier - using 999 for standalone)
server-id = 999

# Binary logging (useful for point-in-time recovery even without replication)
log_bin = /var/log/mysql/mysql-bin.log
log_timestamps = SYSTEM
binlog_expire_logs_seconds = 604800  # 7 days
max_binlog_size = 1073741824  # 1GB

# No replication configured - this is a standalone server
# To enable replication later, replace this file with proper replication config
EOF
validate "Creating 99-mysql_2-standalone.cnf"

# Restart MySQL to apply changes
systemctl restart mysql
validate "Restarting MySQL with new configuration"

# Verify MySQL is listening on 0.0.0.0
log "Verifying MySQL network binding..."
netstat -tlnp | grep 3306 | tee -a $LOG_FILE

log "=========================================="
log "MYSQL SETUP COMPLETE"
log "=========================================="
log ""
log "Databases created:"
log "  - ddg_production"
log "  - proqcs_production"
log "  - sprovisions_production"
log ""
log "Credentials saved to: /root/.ddg_db_credentials"
log ""
log "To import existing data, use:"
log "  mysql -u ddg_admin -p ddg_production < dump.sql"
log "=========================================="
