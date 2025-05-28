#!/bin/bash

# File to log the output
LOG_FILE="reset_slave_log.txt"

# MySQL credentials and connection details
MYSQL_USER="bgdbreplicator1"
MYSQL_HOST="71.33.250.235"
MYSQL_PORT=3306
# Consider using a secure method to handle the password, like a credentials file or mysql_config_editor
MYSQL_PASS="your_mysql_password"

# Function to execute MySQL commands
execute_mysql_command() {
    mysql  -e "$1"
}

# Get current Master Log File and Position
CURRENT_LOG_INFO=$(execute_mysql_command "SHOW SLAVE STATUS\G" | grep -E 'Master_Log_File|Read_Master_Log_Pos' | awk '{print $2}')
MASTER_LOG_FILE=$(echo "$CURRENT_LOG_INFO" | head -1)
MASTER_LOG_POS=$(echo "$CURRENT_LOG_INFO" | tail -1)

# Construct the command set
CMD_SET=$(cat <<EOF
STOP SLAVE;
RESET SLAVE;
CHANGE MASTER TO MASTER_HOST='$MYSQL_HOST', MASTER_USER='$MYSQL_USER', MASTER_PORT=$MYSQL_PORT, MASTER_LOG_FILE='$MASTER_LOG_FILE', MASTER_LOG_POS=$MASTER_LOG_POS;
START SLAVE;
EOF
)

# Print current slave status, the commands, and the new slave status
{
    echo "Before reset:"
    execute_mysql_command "SHOW SLAVE STATUS\G"
    echo "Commands to be executed:"
    echo "$CMD_SET"
    execute_mysql_command "$CMD_SET"
    echo "After reset:"
    execute_mysql_command "SHOW SLAVE STATUS\G"
} | tee "$LOG_FILE"

echo "Commands executed and logged to $LOG_FILE"
