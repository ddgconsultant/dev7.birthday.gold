#!/bin/bash

LOG_FILE=~/installhistory_web_$(date +"%Y%m%d%H%M%S").log
STATE_FILE=~/install_state_web
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

log "Starting installation process on $(hostname)"

STATE=$(load_state)

# Check if the state is "completed" and no actions have been performed
if [ "$STATE" == "completed" ] && [ "$ACTION_COUNTER" -eq 0 ]; then
    figlet "Check State File"
    log "The state file [$STATE_FILE] = completed"
    exit 0
fi

case $STATE in
"pre")
echo " ____  _             _   _             "
echo "/ ___|| |_ __ _ ____| |_(_)____   ____ "
echo "\___ \| __/ _\ |  __| __| |  _ \ / _  |"
echo " ___) | || (_| | |  | |_| | | | | (_| |"
echo "|____/ \__\__,_|_|   \__|_|_| |_|\__, |"
echo "                                 |___/ "
echo ""
    timedatectl set-timezone America/Denver
    timedatectl status
    validate "Setting timezone to America/Denver"

    export DEBIAN_FRONTEND=noninteractive

    # Suppress kernel warnings by disabling the motd-news service
    echo 'ENABLED=0' > /etc/default/motd-news

    apt-get dist-upgrade -y -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold"
    validate "Running dist-upgrade"

    apt update -y
    validate "Running apt update"

    apt upgrade -y
    validate "Running apt upgrade"

    apt -y install figlet
    save_state "start"
    ;&
"start")

    ##########################################################
    ## Set up the FTP Password in .profile:
    figlet "Input Required"
    read -sp "Enter FTP password: " MYSUPERSECUREPASSWORD
    echo

    log "Setting up FTP password"
    echo "export MYSUPERSECUREPASSWORD=\"$MYSUPERSECUREPASSWORD\"" >> ~/.profile
    source ~/.profile
    validate "Setting up FTP password"

    save_state "post_reboot_ssl_apache_ufw"
    ;&
"update_upgrade")
    ##########################################################
    ## Update and Upgrade the System
    log "Updating and upgrading system"
    
    figlet "Rebooting"
    log "Rebooting system"
    save_state "post_reboot_ssl_apache_ufw"
    reboot
    ;;
"post_reboot_ssl_apache_ufw")
    ##########################################################
    ## set up SSL, Apache and Firewall (UFW)
    log "Setting up SSL, Apache and Firewall"
    apt-get install openssl -y
    validate "Installing OpenSSL"

    apt install openssh-server -y
    validate "Installing OpenSSH server"

    systemctl start ssh
    validate "Starting SSH service"

    ufw default deny incoming
    ufw default allow outgoing

    ufw allow ssh
    ufw allow 80
    ufw allow 443
    ufw allow 3306
    yes | ufw enable
    validate "Configuring UFW firewall"

    systemctl is-enabled --quiet ufw
    validate "Ensuring UFW is enabled"

    ufw status
    validate "Checking UFW status"

    save_state "create_rdavis_user"
    ;&
"create_rdavis_user")
    ##########################################################
    ## Create rdavis user
    log "Creating rdavis user"
    adduser rdavis --gecos "Richard Davis,RoomNumber,WorkPhone,HomePhone" --disabled-password
    validate "Creating rdavis user"

    echo 'rdavis:$MYSUPERSECUREPASSWORD' | sudo chpasswd
    validate "Setting rdavis user password"

    echo 'rdavis ALL=(ALL) NOPASSWD: ALL' | sudo tee /etc/sudoers.d/rdavis
    validate "Adding rdavis to sudoers"

    save_state "transfer_certs_env"
    ;&
"transfer_certs_env")
    ##########################################################
    ## TRANSFER CERTS AND ENV CONFIG FILE
    log "Transferring certs and env config file"
    mkdir -p /home/rdavis/.ssh
    chown rdavis:rdavis /home/rdavis/.ssh
    chmod 700 /home/rdavis/.ssh

    ftp -inv dev.birthday.gold <<EOF
user richard $MYSUPERSECUREPASSWORD
get /BIRTHDAY_SERVER/ENV_CONFIGS/keys/hostinger_rdavis.pub /home/rdavis/.ssh/hostinger_rdavis.pub
EOF
    validate "Transferring SSH key"

    cat /home/rdavis/.ssh/hostinger_rdavis.pub >> /home/rdavis/.ssh/authorized_keys
    validate "Setting up authorized_keys for rdavis"

    chmod 600 /home/rdavis/.ssh/authorized_keys
    validate "Setting permissions for authorized_keys"

    save_state "install_basics"
    ;&
"install_basics")
    ##########################################################
    ## INSTALL THE BASICS
    log "Installing basic tools"
    v_HOST=$(hostname)
    echo ${v_HOST}

    apt-get update -y
    validate "Running apt update"

    apt -y install dos2unix tmux make gcc g++ software-properties-common mlocate unzip jq
    validate "Installing basic packages"

    updatedb
    validate "Updating file database"

    figlet "Done"
    figlet "Rebooting"
    log "Rebooting system"
    save_state "post_reboot_apache_install"
    reboot
    ;;
"post_reboot_apache_install")
    ##########################################################
    ## INSTALL APACHE
    log "Installing Apache"
    apt update -y
    validate "Running apt update"

    apt -y install apache2
    validate "Installing Apache2"

    systemctl enable apache2
    validate "Enabling Apache2 service"

    systemctl start apache2
    validate "Starting Apache2 service"


    ##########################################################
    ## Enable SSL and the Virtual Host
    log "Enabling SSL and virtual host"
    a2enmod ssl
    validate "Enabling SSL module"

    a2enmod rewrite
    validate "Enabling rewrite module"

    systemctl reload apache2
    validate "Reloading Apache2 service"

    figlet "Apache Done"

    save_state "install_php"
    ;&
"install_php")
    ##########################################################
    ## Install PHP and Required Extensions
    log "Installing PHP and required extensions"
    apt -y install software-properties-common
    validate "Installing software-properties-common"

    add-apt-repository -y ppa:ondrej/php
    validate "Adding PHP repository"

    apt update -y
    validate "Running apt update"

    apt --fix-broken install
    validate "Fixing broken dependencies"

    apt clean
    validate "Cleaning apt cache"

    apt autoclean
    validate "Running apt autoclean"

    apt autoremove -y
    validate "Running apt autoremove"

    apt install -f
    validate "Installing required packages"

    apt update
    validate "Running apt update"

    apt install php8.1 php8.1-cli php8.1-common php8.1-mysql libapache2-mod-php8.1 -y
    validate "Installing PHP and required extensions"

    sudo a2enmod php8.1
    validate "Enabling PHP 8.1 module"

    sudo systemctl restart apache2
    validate "Restarting Apache2 service"

    save_state "install_php_extensions"
    ;&
"install_php_extensions")
    ##########################################################
    # Install PHP 8.1 additional extensions
    apt -y install php8.1-cli php8.1-common php8.1-mysql php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl php8.1-xml php8.1-bcmath php8.1-intl php8.1-readline php8.1-ldap php8.1-soap php8.1-sqlite3 php8.1-opcache php8.1-xmlrpc  php8.1-odbc php8.1-fpm php8.1-phpdbg php8.1-fileinfo php-mysql php-curl 
    validate "Installing additional PHP 8.1 extensions"

    apt -y install php-pear
    validate "Installing php-pear"

    pecl channel-update pecl.php.net
    validate "Updating PECL channels"

    pecl update-channels
    validate "Updating PECL channels"

    apt-get install -f
    validate "Fixing dependencies"

    apt -y install aptitude
    validate "Installing aptitude"

    aptitude install libc6-dev libtirpc-dev libnsl-dev libssl-dev
    validate "Installing development libraries"

    php -v
    validate "Checking PHP version"

    echo "<?php phpinfo(); ?>" > /var/www/html/__info.php
    validate "Creating PHP info page"

    figlet "PHP Done"

    echo "<?php header('location:http://www.ddg.mx'); ?>" > /var/www/html/index.php
    validate "Creating index page redirect"

    mv /var/www/html/index.html /var/www/html/__unused_ubuntu.index.html 
    validate "Moving default index.html"

    apt update -y
    validate "Running apt update"

    save_state "create_directories"
    ;&
"create_directories")
    ##########################################################
    ## CREATE THE NECESSARY DIRECTORIES
    log "Creating necessary directories"
    mkdir -p /var/web_certs/BIRTHDAY_SERVER
    mkdir -p /var/web_certs/BIRTHDAY_SERVER/birthday.gold/public/uploads
    mkdir -p /var/www/BIRTHDAY_SERVER
    mkdir -p /var/www/BIRTHDAY_SERVER/tmp
    mkdir -p /var/www/BIRTHDAY_SERVER/ENV_CONFIGS
    mkdir -p /var/log/BIRTHDAY_SERVER

    sudo chown -R www-data:www-data /var/www/html
    validate "Setting ownership for /var/www/html"

    sudo chown -R www-data:www-data /var/www/BIRTHDAY_SERVER
    validate "Setting ownership for /var/www/BIRTHDAY_SERVER"

    save_state "config_php"
    ;&
"config_php")
    ##########################################################
    ## Config PHP
    log "Configuring PHP"
    wget -O /var/www/BIRTHDAY_SERVER/full_php_browscap.ini https://browscap.org/stream?q=Full_PHP_BrowsCapINI
    validate "Downloading browscap.ini"

    chown www-data:www-data /var/www/BIRTHDAY_SERVER/full_php_browscap.ini
    validate "Setting ownership for browscap.ini"

    chmod 644 /var/www/BIRTHDAY_SERVER/full_php_browscap.ini
    validate "Setting permissions for browscap.ini"

    sh -c "echo '
    date.timezone = America/Denver
    browscap = /var/www/BIRTHDAY_SERVER/full_php_browscap.ini
    error_log = /var/www/BIRTHDAY_SERVER/php_errors.log
    max_execution_time = 630
    memory_limit = 256M
    post_max_size = 128M
    short_open_tag = On
    upload_max_filesize = 128M
    upload_tmp_dir =/var/www/BIRTHDAY_SERVER/tmp
    user_dir = /var/www/BIRTHDAY_SERVER/birthday.gold/public/uploads
    session.gc_probability = 1
    ' > /etc/php/8.1/apache2/conf.d/birthday.gold.php.ini"
    validate "Configuring PHP settings"

    save_state "transfer_certs_env_files"
    ;&
"transfer_certs_env_files_old")
    ##########################################################
    ## TRANSFER CERTS AND ENV CONFIG FILE
    log "Transferring certs and env config file"
    ftp -inv dev.birthday.gold <<EOF
user richard $MYSUPERSECUREPASSWORD
binary
get "/BIRTHDAY_SERVER/ENV_CONFIGS/config-main-production.inc" "/var/www/BIRTHDAY_SERVER/ENV_CONFIGS/config-main-production.inc"
get "/BIRTHDAY_SERVER/ENV_CONFIGS/www-profile.txt" "/root/www-profile.txt"
get "/BIRTHDAY_SERVER/dev.birthday.gold/admin_actions/deploy_www.sh" "/root/deploy_www.sh"
get "/BIRTHDAY_SERVER/_CERTS_/birthday.gold/xfer/AAACertificateServices.crt" "/var/web_certs/BIRTHDAY_SERVER/birthday.gold/AAACertificateServices.crt"
get "/BIRTHDAY_SERVER/_CERTS_/birthday.gold/xfer/SectigoRSADomainValidationSecureServerCA.crt" "/var/web_certs/BIRTHDAY_SERVER/birthday.gold/SectigoRSADomainValidationSecureServerCA.crt"
get "/BIRTHDAY_SERVER/_CERTS_/birthday.gold/xfer/server.key" "/var/web_certs/BIRTHDAY_SERVER/birthday.gold/server.key"
get "/BIRTHDAY_SERVER/_CERTS_/birthday.gold/xfer/STAR_birthday_gold.crt" "/var/web_certs/BIRTHDAY_SERVER/birthday.gold/STAR_birthday_gold.crt"
get "/BIRTHDAY_SERVER/_CERTS_/birthday.gold/xfer/USERTrustRSAAAACA.crt" "/var/web_certs/BIRTHDAY_SERVER/birthday.gold/USERTrustRSAAAACA.crt"
bye
EOF
    validate "Transferring certificates and config files"
;&
"transfer_certs_env_files")
    ##########################################################
    ## TRANSFER CERTS AND ENV CONFIG FILE
    log "Transferring certs and env config file"

    # Define the array with source and destination paths
    files=(
        "/BIRTHDAY_SERVER/ENV_CONFIGS/config-main-production.inc:/var/www/BIRTHDAY_SERVER/ENV_CONFIGS/config-main-production.inc"
        "/BIRTHDAY_SERVER/ENV_CONFIGS/www-profile.txt:/root/www-profile.txt"
        "/BIRTHDAY_SERVER/dev.birthday.gold/admin_actions/deploy_www.sh:/root/deploy_www.sh"
        "/BIRTHDAY_SERVER/_CERTS_/birthday.gold/xfer/AAACertificateServices.crt:/var/web_certs/BIRTHDAY_SERVER/birthday.gold/AAACertificateServices.crt"
        "/BIRTHDAY_SERVER/_CERTS_/birthday.gold/xfer/SectigoRSADomainValidationSecureServerCA.crt:/var/web_certs/BIRTHDAY_SERVER/birthday.gold/SectigoRSADomainValidationSecureServerCA.crt"
        "/BIRTHDAY_SERVER/_CERTS_/birthday.gold/xfer/server.key:/var/web_certs/BIRTHDAY_SERVER/birthday.gold/server.key"
        "/BIRTHDAY_SERVER/_CERTS_/birthday.gold/xfer/STAR_birthday_gold.crt:/var/web_certs/BIRTHDAY_SERVER/birthday.gold/STAR_birthday_gold.crt"
        "/BIRTHDAY_SERVER/_CERTS_/birthday.gold/xfer/USERTrustRSAAAACA.crt:/var/web_certs/BIRTHDAY_SERVER/birthday.gold/USERTrustRSAAAACA.crt"
    )

    # Start FTP transfer
    ftp -inv dev.birthday.gold <<EOF
user richard $MYSUPERSECUREPASSWORD
binary
$(for file in "${files[@]}"; do
    src="${file%%:*}"
    dest="${file##*:}"
    echo "get \"$src\" \"$dest\""
done)
bye
EOF

    # Validate the presence of each file
    log "Validating transferred files"
    for file in "${files[@]}"; do
        dest="${file##*:}"
        if [ ! -f "$dest" ]; then
            log "File not found: $dest"
            exit 1
        fi
    done

    log "All files transferred and validated successfully."
    validate "Transferring certificates and config files"
    
    save_state "adjust_directory_permissions"
&;
"adjust_directory_permissions")
    ##########################################################
    ## 
    sudo chown -R www-data:www-data /var/www/BIRTHDAY_SERVER/ENV_CONFIGS
    validate "Setting ownership for /var/www/BIRTHDAY_SERVER/ENV_CONFIGS"

    sudo chown -R www-data:www-data /var/web_certs
    validate "Setting ownership for /var/web_certs"

    chmod 440 -R /var/www/BIRTHDAY_SERVER/ENV_CONFIGS/config-main-production.inc
    validate "Setting permissions for config-main-production.inc"

    chmod 440 -R /var/web_certs/BIRTHDAY_SERVER
    validate "Setting permissions for /var/web_certs/BIRTHDAY_SERVER"

    chmod 400 /var/web_certs/BIRTHDAY_SERVER
    validate "Setting permissions for /var/web_certs/BIRTHDAY_SERVER"

    mv /root/www-profile.txt /root/.profile
    validate "Moving www-profile.txt to .profile"

    chmod 540 /root/.profile
    validate "Setting permissions for .profile"

    sed -i 's/[^[:print:]\t]//g' /root/.profile
    validate "Removing non-printable characters from .profile"

    source ~/.profile
    validate "Sourcing .profile"

    chmod 770 /root/deploy_www.sh 
    validate "Setting permissions for deploy_www.sh"

    save_state "create_subdomain_directories"
    ;&
"create_subdomain_directories")
    ##########################################################
    ## 
    subdomain="www"
    mkdir -p /var/www/BIRTHDAY_SERVER/${subdomain}.birthday.gold
    validate "Creating directory for ${subdomain}.birthday.gold"

    chown -R www-data:www-data /var/www/BIRTHDAY_SERVER/${subdomain}.birthday.gold
    validate "Setting ownership for ${subdomain}.birthday.gold"

    echo "<?php echo 'Hello Birthday Gold World - '. date('r'); ?>" > /var/www/BIRTHDAY_SERVER/${subdomain}.birthday.gold/index.php
    validate "Creating index.php for ${subdomain}.birthday.gold"

    echo "subdomain=${subdomain}" > /var/www/BIRTHDAY_SERVER/${subdomain}.birthday.gold/subdomain.id
    validate "Creating subdomain.id for ${subdomain}.birthday.gold"

    chmod 444 /var/www/BIRTHDAY_SERVER/${subdomain}.birthday.gold/subdomain.id
    validate "Setting permissions for subdomain.id"

    save_state "create_virtual_host"
    ;&
"create_virtual_host")
    ##########################################################
    ## Create the Virtual Host file
    log "Creating virtual host file"
    sh -c "echo \"
<VirtualHost *:80>
    ServerName ${subdomain}.birthday.gold
    Redirect permanent / https://${subdomain}.birthday.gold/
</VirtualHost>

<VirtualHost *:443>
  ServerAdmin webmaster@birthday.gold
  DocumentRoot /var/www/BIRTHDAY_SERVER/${subdomain}.birthday.gold
  DirectoryIndex index.php index.html
  ServerName ${subdomain}.birthday.gold
  ServerAlias $(hostname).birthday.gold
  ErrorLog /var/log/BIRTHDAY_SERVER/${subdomain}.birthday.gold-error.log
  CustomLog /var/log/BIRTHDAY_SERVER/${subdomain}.birthday.gold-access.log common
  <Directory \"/var/www/BIRTHDAY_SERVER/${subdomain}.birthday.gold\">
    Options Indexes Includes FollowSymLinks
    AllowOverride All
    Require all granted
  </Directory>
  SSLEngine on
  SSLCertificateKeyFile \"/var/web_certs/BIRTHDAY_SERVER/birthday.gold/server.key\"
  SSLCertificateFile \"/var/web_certs/BIRTHDAY_SERVER/birthday.gold/STAR_birthday_gold.crt\"
  SSLCertificateChainFile \"/var/web_certs/BIRTHDAY_SERVER/birthday.gold/SectigoRSADomainValidationSecureServerCA.crt\"
  SSLCACertificateFile \"/var/web_certs/BIRTHDAY_SERVER/birthday.gold/USERTrustRSAAAACA.crt\"
  SSLCACertificateFile \"/var/web_certs/BIRTHDAY_SERVER/birthday.gold/AAACertificateServices.crt\"
</VirtualHost>
\" > /etc/apache2/sites-available/${subdomain}.birthday.gold.conf"
    validate "Creating virtual host configuration for ${subdomain}.birthday.gold"

    a2ensite ${subdomain}.birthday.gold
    validate "Enabling site ${subdomain}.birthday.gold"

    apache2ctl configtest
    validate "Testing Apache configuration"

    systemctl reload apache2
    validate "Reloading Apache2 service"

    systemctl status apache2 --lines=2
    validate "Checking Apache2 service status"

    save_state "install_mysql_client"
    ;&
"install_mysql_client")
    apt -y install mysql-client mysql-client-core-8.0
    validate "Installing MySQL client"
    save_state "deploy"
    ;&
"deploy")
    ./deploy_www.sh
    validate "Running deploy_www.sh"
    save_state "system_availability_mysqlrecord"
    ;&
"system_availability_mysqlrecord")
        master_host='july02.bday.gold'
        figlet "Input Required"        
        # Prompt for the MySQL password
        echo "Enter MySQL birthday_gold_admin password:"
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
        # Insert System Availability DB record
        mysql -u birthday_gold_admin -h${master_host} -p -e "
        INSERT INTO \`birthday_gold_www\`.\`bg_system_availability\` 
        (\`system_id\`, \`name\`, \`description\`, \`url\`, \`port\`, \`system_status\`, \`status\`, \`last_success_dt\`, \`last_failure_dt\`, \`create_dt\`, \`modify_dt\`) 
        VALUES 
        (180, '${current_hostname} / Production LAMP Stack', '=== Production LAMP Stack\n\n${os_version}\n+ ${apache_version}\n+ PHP ${php_version}\n+ MySQL 8 (ID: ###)', '${ip_address}', 80, 'green', 'A', NOW(), NOW(), NOW(), NOW());" -p
        validate "Insert System Availability DB record"
        save_state "completed"
    ;&
"completed")
    # Re-enable kernel warnings by disabling the motd-news service
    echo 'ENABLED=1' > /etc/default/motd-news
    figlet "Completed WEB"
    log "Installation process completed successfully on $(hostname)"
    log "Perform Execute: install_mysqldb.sh"
    ;;
*)
    log "Unknown state: $STATE"
    exit 1
    ;;
esac
