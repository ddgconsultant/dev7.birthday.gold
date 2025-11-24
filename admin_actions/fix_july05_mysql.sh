#!/bin/bash
# Fix July05 MySQL Configuration
# Sets server-id and restarts MySQL

echo "Fixing july05 MySQL configuration..."

# Set server-id to 705
sed -i 's/^server-id = $/server-id = 705/' /etc/mysql/mysql.conf.d/99-mysql_2-replication.cnf

# Verify the change
echo "Server ID set to:"
grep "^server-id" /etc/mysql/mysql.conf.d/99-mysql_2-replication.cnf

# Restart MySQL
echo "Restarting MySQL..."
systemctl restart mysql

# Check status
echo "MySQL status:"
systemctl status mysql --no-pager -l

echo "Done!"
