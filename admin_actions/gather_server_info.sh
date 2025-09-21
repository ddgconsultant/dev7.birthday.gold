#!/bin/bash

# Define the target directory for the information
TARGET_DIR="/var/www/BIRTHDAY_SERVER/MONITORING/php_info"

# Create the directory to store the information
mkdir -p $TARGET_DIR

# Get PHP version
php -v > $TARGET_DIR/php_version.txt

# Get PHP ini location
php --ini > $TARGET_DIR/php_ini_location.txt

# Get loaded PHP extensions
php -m > $TARGET_DIR/php_modules.txt

# Get PHP configuration
php -i > $TARGET_DIR/php_info.txt

# Get list of installed packages (for Debian-based systems, adjust for other distributions)
dpkg -l > $TARGET_DIR/installed_packages.txt

# Archive the collected information
tar -czvf /var/www/BIRTHDAY_SERVER/MONITORING/php_info_$(hostname).tar.gz -C $TARGET_DIR .

echo "PHP information has been collected and archived in /var/www/BIRTHDAY_SERVER/MONITORING/php_info_$(hostname).tar.gz"

# Transfer the archive to december20.bday.gold
scp /var/www/BIRTHDAY_SERVER/MONITORING/php_info_$(hostname).tar.gz root@december20.bday.gold:/root/MONITORING/.

echo "PHP information has been sent to december20.bday.gold"
