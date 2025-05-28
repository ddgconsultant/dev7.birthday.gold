#!/bin/bash

apt -y install figlet
##########################################################
# Log file location
LOG_FILE="/var/log/vps_setup.log"


##########################################################
# Function to log messages
log_message() {
     figlet "==================" | tee -a "$LOG_FILE"
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
   
}


##########################################################
# Function to handle errors
handle_error() {
    log_message "An error occurred: $1"
    log_message "Exiting with status code 1"
    return 1
}


##########################################################
# Check if the script is run as root
if [ "$(id -u)" -ne 0 ]; then
    handle_error "This script must be run as root"
fi


##########################################################
# Prompt for the super secure password
 log_message "ENTER SETUP PASSWORD"
read -sp "Enter the super secure password: " SUPER_SECURE_PASSWORD
echo


##########################################################
# Validate the provided password
if [ -z "$SUPER_SECURE_PASSWORD" ]; then
    handle_error "No password provided"
fi


##########################################################
# Start setup
log_message "Starting VPS setup"



##########################################################
# Set timezone and update system
log_message "Setting timezone to America/Denver"
sudo timedatectl set-timezone America/Denver || handle_error "Failed to set timezone"
log_message "Timezone set successfully"

log_message "Updating and upgrading system packages"
sudo DEBIAN_FRONTEND=noninteractive apt-get dist-upgrade -y || handle_error "Failed to dist-upgrade"
sudo apt update -y || handle_error "Failed to update apt"
sudo apt upgrade -y || handle_error "Failed to upgrade apt"
log_message "System packages updated and upgraded successfully"



##########################################################
# Install required packages
log_message "Installing required packages: openssl, openssh-server, ufw, unzip"
sudo apt-get install -y openssl openssh-server ufw unzip || handle_error "Failed to install required packages"
log_message "Required packages installed successfully"



##########################################################
# Configure SSH and UFW
log_message "Configuring SSH and UFW"
sudo systemctl start ssh || handle_error "Failed to start SSH"
sudo ufw default deny incoming || handle_error "Failed to set UFW default deny"
sudo ufw default allow outgoing || handle_error "Failed to set UFW default allow"
sudo ufw allow ssh || handle_error "Failed to allow SSH through UFW"
sudo ufw allow 80 || handle_error "Failed to allow HTTP through UFW"
sudo ufw allow 443 || handle_error "Failed to allow HTTPS through UFW"
sudo ufw allow 3306 || handle_error "Failed to allow MySQL through UFW"
echo "y" | sudo ufw enable || handle_error "Failed to enable UFW"
log_message "SSH and UFW configured successfully"



##########################################################
# Add user rdavis
log_message "Adding user rdavis"
adduser rdavis --gecos "Richard Davis,RoomNumber,WorkPhone,HomePhone" --disabled-password || handle_error "Failed to add user rdavis"
echo "rdavis:$SUPER_SECURE_PASSWORD" | sudo chpasswd || handle_error "Failed to set password for user rdavis"
echo 'rdavis ALL=(ALL) NOPASSWD: ALL' | sudo tee /etc/sudoers.d/rdavis || handle_error "Failed to give rdavis sudo privileges"
log_message "User rdavis added successfully"



##########################################################
##  TRANSFER CERTS AND ENV CONFIG FILE
mkdir -p /home/rdavis/.ssh
chown rdavis:rdavis /home/rdavis/.ssh
chmod 700 /home/rdavis/.ssh

ftp -inv dev.birthday.gold <<EOF
user richard {{MYSUPERSECUREPASSWORD}}
get /BIRTHDAY_SERVER/ENV_CONFIGS/keys/hostinger_rdavis.pub /home/rdavis/.ssh/hostinger_rdavis.pub
EOF

cat /home/rdavis/.ssh/hostinger_rdavis.pub >> /home/rdavis/.ssh/authorized_keys

chmod 600 /home/rdavis/.ssh/authorized_keys



##########################################################
# Begin additional setup actions
log_message "Starting additional setup actions"


# Logging the hostname
v_HOST=$(hostname)
log_message "Hostname: ${v_HOST}"

# Updating the package lists
log_message "Updating package lists..."
apt-get update || { log_message "Failed to update package lists"; exit 1; }

# Installing dos2unix
log_message "Installing dos2unix..."
apt install -y dos2unix || { log_message "Failed to install dos2unix"; exit 1; }

# Installing additional utilities
log_message "Installing additional utilities..."
apt -y install wget tmux make gcc g++ software-properties-common mlocate unzip jq || { log_message "Failed to install one or more utilities"; exit 1; }

# Updating the file database
log_message "Updating the file database..."
updatedb || { log_message "Failed to update the file database"; exit 1; }



##########################################################
# Apache installation
log_message "Installing Apache"
sudo apt -y install apache2 || handle_error "Failed to install Apache"
sudo systemctl enable apache2 || handle_error "Failed to enable Apache2"
sudo systemctl start apache2 || handle_error "Failed to start Apache2"
log_message "Apache installed and started successfully"



##########################################################
# PHP installation
log_message "Installing software-properties-common..."
apt -y install software-properties-common

log_message "Adding PHP repository from Ondřej Surý..."
add-apt-repository ppa:ondrej/php -y

log_message "Updating package lists..."
apt update

log_message "Installing PHP 8.1 and common extensions..."
apt -y install php8.1 php8.1-bcmath php8.1-common php8.1-intl php8.1-cli php8.1-gd php8.1-curl php8.1-opcache php8.1-mysql php8.1-ldap php8.1-zip php8.1-xml php8.1-mbstring php8.1-soap php8.1-xmlrpc php8.1-fpm php8.1-odbc php8.1-phpdbg php8.1-odbc php8.1-fileinfo

log_message "Installing additional packages required for PECL..."
apt -y install gcc make autoconf libc-dev pkg-config libmcrypt-dev php-pear php-dev

log_message "Updating PECL channels..."
pecl channel-update pecl.php.net
pecl update-channels

log_message "Installing mcrypt extension via PECL..."
pecl install mcrypt

log_message "Creating test PHP files..."
echo "<?php phpinfo(); ?>" > /var/www/html/__info.php
echo "<?php header('location:http://www.ddg.mx'); ?>" > /var/www/html/index.php
mv /var/www/html/index.html /var/www/html/__unused_ubuntu.index.html 

log_message "PHP installation and configuration complete. PHP version:"
php -v




##########################################################
# MySQL installation
# Install MySQL Server
log_message "Installing MySQL Server..."
apt -y install mysql-server

# Secure MySQL installation
log_message "Securing MySQL installation..."
mysql_secure_installation

# Update MySQL configuration to disable remote access
log_message "Updating MySQL configuration to disable remote access..."
sudo sed -i '/^bind-address\s*=\s*127\.0\.0\.1/s/^bind-address\s*=\s*127\.0\.0\.1/# bind-address = 127.0.0.1    ## uncomment to disable WAN access/' /etc/mysql/mysql.conf.d/mysqld.cnf

# Restart MySQL to apply configuration changes
log_message "Restarting MySQL service to apply configuration changes..."
systemctl restart mysql

# Check MySQL service status
log_message "Checking MySQL service status..."
systemctl status mysql --no-pager

# Install MySQL Client
log_message "Installing MySQL Client..."
apt -y install mysql-client mysql-client-core-8.0

log_message "MySQL Server and Client installation and configuration completed."



##########################################################
# Directory structure setup
log_message "Setting up directory structures and permissions"
mkdir -p /var/www/BIRTHDAY_SERVER && chown -R www-data:www-data /var/www/BIRTHDAY_SERVER || handle_error "Failed to create /var/www/BIRTHDAY_SERVER"
mkdir -p /var/www/html && chown -R www-data:www-data /var/www/html || handle_error "Failed to set ownership for /var/www/html"
mkdir -p /var/web_certs/BIRTHDAY_SERVER/birthday.gold && chown -R www-data:www-data /var/web_certs/BIRTHDAY_SERVER/birthday.gold || handle_error "Failed to create /var/web_certs/BIRTHDAY_SERVER/birthday.gold"
mkdir -p /var/www/BIRTHDAY_SERVER/tmp && chown -R www-data:www-data /var/www/BIRTHDAY_SERVER/tmp || handle_error "Failed to create /var/www/BIRTHDAY_SERVER/tmp"
mkdir -p /var/www/BIRTHDAY_SERVER/ENV_CONFIGS && chown -R www-data:www-data /var/www/BIRTHDAY_SERVER/ENV_CONFIGS || handle_error "Failed to create /var/www/BIRTHDAY_SERVER/ENV_CONFIGS"
mkdir -p /var/log/BIRTHDAY_SERVER && chown -R www-data:www-data /var/log/BIRTHDAY_SERVER || handle_error "Failed to create /var/log/BIRTHDAY_SERVER"
log_message "Directory structures and permissions set up successfully"




##########################################################
# PHP Configuration
log_message "Configuring PHP"
PHP_INI_FILE="/etc/php/8.1/apache2/conf.d/birthday.gold.php.ini"
# Create or overwrite the PHP INI file with custom settings
cat <<EOF > "$PHP_INI_FILE"
browscap=/var/www/BIRTHDAY_SERVER/full_php_browscap.ini
error_log=/var/www/BIRTHDAY_SERVER/php_errors.log
max_execution_time=630
memory_limit=256M
post_max_size=128M
short_open_tag=On
upload_max_filesize=128M
upload_tmp_dir=/var/www/BIRTHDAY_SERVER/tmp
user_dir=/var/www/BIRTHDAY_SERVER/birthday.gold/public/uploads
session.gc_probability=1
EOF

# Log the completion
log_message "Custom PHP settings for birthday.gold have been written to $PHP_INI_FILE"

# Optionally, restart Apache to apply the changes
log_message "Restarting Apache to apply PHP configuration changes..."
systemctl restart apache2
log_message "Apache restarted successfully."




##########################################################
# Enabling Apache SSL and rewrite modules
log_message "Enabling Apache SSL and rewrite modules"
sudo a2enmod ssl rewrite || handle_error "Failed to enable Apache modules"
sudo systemctl reload apache2 || handle_error "Failed to reload Apache2"
log_message "Apache SSL and rewrite modules enabled successfully"





##########################################################
# FTP Transfer for ENV_CONFIGS and Certificates
log_message "Starting FTP transfers for ENV_CONFIGS and Certificates"
ftp -inv dev.birthday.gold <<EOF
user richard $SUPER_SECURE_PASSWORD
binary
get /BIRTHDAY_SERVER/ENV_CONFIGS/config-main-production.inc /var/www/BIRTHDAY_SERVER/ENV_CONFIGS/config-main-production.inc
get /BIRTHDAY_SERVER/_CERTS_/birthday.gold/xfer/AAACertificateServices.crt /var/web_certs/BIRTHDAY_SERVER/birthday.gold/AAACertificateServices.crt
get /BIRTHDAY_SERVER/_CERTS_/birthday.gold/xfer/SectigoRSADomainValidationSecureServerCA.crt /var/web_certs/BIRTHDAY_SERVER/birthday.gold/SectigoRSADomainValidationSecureServerCA.crt
get /BIRTHDAY_SERVER/_CERTS_/birthday.gold/xfer/server.key /var/web_certs/BIRTHDAY_SERVER/birthday.gold/server.key
get /BIRTHDAY_SERVER/_CERTS_/birthday.gold/xfer/STAR_birthday_gold.crt /var/web_certs/BIRTHDAY_SERVER/birthday.gold/STAR_birthday_gold.crt
get /BIRTHDAY_SERVER/_CERTS_/birthday.gold/xfer/USERTrustRSAAAACA.crt /var/web_certs/BIRTHDAY_SERVER/birthday.gold/USERTrustRSAAAACA.crt
get /BIRTHDAY_SERVER/dev./birthday.gold/admin_actions/deploy_www.sh /root/deploy_www.sh
get /BIRTHDAY_SERVER/ENV_CONFIGS/vps-root.profile /root/vps-root.profile
bye
EOF
mv vps-root.profile .profile
source .profile
log_message "FTP transfers completed"



# Setting ownership and permissions
sudo chown -R www-data:www-data /var/www/BIRTHDAY_SERVER/ENV_CONFIGS
sudo chown -R www-data:www-data /var/web_certs
chmod 440 -R /var/www/BIRTHDAY_SERVER/ENV_CONFIGS/config-main-production.inc
chmod 440 -R /var/web_certs/BIRTHDAY_SERVER
chmod 400 /var/web_certs/BIRTHDAY_SERVER/*
chmod 400 /root/.profile
chmod 700 -R /root/deploy_www.sh
log_message "Ownership and permissions set"



# Enabling SSL and rewrite modules for Apache
a2enmod ssl
a2enmod rewrite
systemctl reload apache2
log_message "SSL and rewrite modules enabled and Apache reloaded"







# SSH Key Setup for User rdavis
log_message "Setting up SSH keys for user rdavis"
mkdir -p /home/rdavis/.ssh && chown rdavis:rdavis /home/rdavis/.ssh && chmod 700 /home/rdavis/.ssh


# Assuming public key is transferred via earlier FTP step or another secure method
cat /var/www/BIRTHDAY_SERVER/ENV_CONFIGS/rdavis.pub > /home/rdavis/.ssh/authorized_keys
chmod 600 /home/rdavis/.ssh/authorized_keys && chown rdavis:rdavis /home/rdavis/.ssh/authorized_keys
log_message "SSH keys set up successfully for user rdavis"



# Apache and PHP Additional Configurations
log_message "Configuring Apache and PHP for BIRTHDAY_SERVER"


# Enable SSL, rewrite modules, and virtual hosts if not already done
a2enmod ssl
a2enmod rewrite


# Create or modify virtual host files as necessary

# Define the subdomain
subdomain="www"

# Create the directory for the subdomain
mkdir -p /var/www/BIRTHDAY_SERVER/${subdomain}.birthday.gold

# Change ownership to www-data (Apache's user)
chown -R www-data:www-data /var/www/BIRTHDAY_SERVER/${subdomain}.birthday.gold

# Create a simple PHP index page
echo "<?php echo 'Hello Birthday Gold World - '. date('r'); ?>" > /var/www/BIRTHDAY_SERVER/${subdomain}.birthday.gold/index.php

# Create a file to store the subdomain information
echo "subdomain=${subdomain}" >  /var/www/BIRTHDAY_SERVER/${subdomain}.birthday.gold/subdomain.id

# Set read-only permissions for the subdomain id file
chmod 444  /var/www/BIRTHDAY_SERVER/${subdomain}.birthday.gold/subdomain.id

# Create the Virtual Host file for the subdomain
VHOST_FILE="/etc/apache2/sites-available/${subdomain}.birthday.gold.conf"
cat <<EOF > $VHOST_FILE
<VirtualHost *:80>
    ServerName ${subdomain}.birthday.gold
    Redirect permanent / https://${subdomain}.birthday.gold/
</VirtualHost>

<VirtualHost *:443>
    ServerAdmin webmaster@birthday.gold
    DocumentRoot /var/www/BIRTHDAY_SERVER/${subdomain}.birthday.gold
    DirectoryIndex index.php index.html
    ServerName ${subdomain}.birthday.gold

    ErrorLog /var/log/BIRTHDAY_SERVER/${subdomain}.birthday.gold-error.log
    CustomLog /var/log/BIRTHDAY_SERVER/${subdomain}.birthday.gold-access.log common

    <Directory "/var/www/BIRTHDAY_SERVER/${subdomain}.birthday.gold">
        Options Includes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateKeyFile "/var/web_certs/BIRTHDAY_SERVER/birthday.gold/server.key"
    SSLCertificateFile "/var/web_certs/BIRTHDAY_SERVER/birthday.gold/STAR_birthday_gold.crt"
    SSLCertificateChainFile "/var/web_certs/BIRTHDAY_SERVER/birthday.gold/SectigoRSADomainValidationSecureServerCA.crt"
    SSLCACertificateFile "/var/web_certs/BIRTHDAY_SERVER/birthday.gold/USERTrustRSAAAACA.crt"
    SSLCACertificateFile "/var/web_certs/BIRTHDAY_SERVER/birthday.gold/AAACertificateServices.crt"
</VirtualHost>
EOF

# Enable the site
a2ensite ${subdomain}.birthday.gold

# Test Apache configuration for syntax errors
apache2ctl configtest

# Reload Apache to apply the changes
systemctl reload apache2

# Check the status of Apache
systemctl status apache2 --lines=2
systemctl reload apache2

systemctl restart apache2
log_message "Apache and PHP configured successfully for BIRTHDAY_SERVER"


log_message "VPS setup completed successfully"

. ./deploy_www.sh
