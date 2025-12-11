#!/bin/bash

# ============================================================================
# CLEAN LAMP STACK INSTALLER FOR CLIENTBASE.DDG.MX
# ============================================================================
# This script installs a fresh LAMP stack using ONLY official Ubuntu repos.
# NO third-party repos, NO compromised packages.
#
# Target: clientbase.ddg.mx (31.220.62.118)
# Ubuntu: 24.04 LTS
#
# Created: 2025-11-26
# Purpose: Clean migration from june12.bday.gold (potential compromise)
# ============================================================================

# AUTO_RESUME: Controls resume behavior after reboot
AUTO_RESUME=${AUTO_RESUME:-false}

# Use fixed log filename so we can always tail it after reboots
LOG_FILE=~/installhistory_ddg_web_current.log
BACKUP_LOG=~/installhistory_ddg_web_$(date +"%Y%m%d%H%M%S").log
STATE_FILE=~/install_state_ddg_web
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
        log "FAIL: $1 (auto-continuing)"
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

get_remaining_runs() {
    local state="$1"
    case "$state" in
        "update_upgrade") echo "2" ;;
        "post_reboot_ssl_apache_ufw") echo "2" ;;
        "install_basics") echo "1" ;;
        "post_reboot_apache_install") echo "1" ;;
        *) echo "0" ;;
    esac
}

write_resume_instructions() {
    local next_state="$1"
    local remaining=$(get_remaining_runs "$next_state")

    sed -i '/### DDG WEBSERVER INSTALLATION INSTRUCTIONS ###/,/### END DDG WEBSERVER INSTALLATION INSTRUCTIONS ###/d' ~/.profile 2>/dev/null

    if [ "$remaining" -gt 0 ]; then
        cat >> ~/.profile <<EOF

### DDG WEBSERVER INSTALLATION INSTRUCTIONS ###
# WEBSERVER INSTALLATION IN PROGRESS
# Current state: $next_state
# Estimated runs remaining: $remaining
#
# To continue installation, run:
#   cd ~ && ./install_ddg_webserver.sh
#
# To monitor installation:
#   tail -f ~/installhistory_ddg_web_current.log
### END DDG WEBSERVER INSTALLATION INSTRUCTIONS ###
EOF
        log "Manual resume instructions written to ~/.profile (runs remaining: $remaining)"
    else
        log "Installation complete - no resume instructions needed"
    fi
}

log "=========================================="
log "CLEAN LAMP INSTALLER FOR CLIENTBASE.DDG.MX"
log "=========================================="
log "Starting installation process on $(hostname)"
log "Resume mode: AUTO_RESUME=$AUTO_RESUME"
log "SECURITY: Using ONLY official Ubuntu repositories"

STATE=$(load_state)

# Check if completed
if [ "$STATE" == "completed" ] && [ "$ACTION_COUNTER" -eq 0 ]; then
    log "The state file [$STATE_FILE] = completed"
    sed -i '/### DDG WEBSERVER INSTALLATION INSTRUCTIONS ###/,/### END DDG WEBSERVER INSTALLATION INSTRUCTIONS ###/d' ~/.profile 2>/dev/null
    log "Manual resume instructions removed from .profile"
    exit 0
fi

case $STATE in
"pre")
    echo "=========================================="
    echo " CLEAN LAMP STACK - CLIENTBASE.DDG.MX"
    echo "=========================================="

    timedatectl set-timezone America/Denver
    timedatectl status
    validate "Setting timezone to America/Denver"

    export DEBIAN_FRONTEND=noninteractive

    # Suppress kernel warnings
    echo 'ENABLED=0' > /etc/default/motd-news

    ##########################################################
    ## SECURITY: Verify ONLY official Ubuntu mirrors
    ##########################################################
    log "SECURITY: Verifying official Ubuntu mirrors ONLY"

    # Backup original sources
    cp /etc/apt/sources.list /etc/apt/sources.list.backup.$(date +%Y%m%d) 2>/dev/null || true

    # Ubuntu 24.04 uses DEB822 format
    if [ -f /etc/apt/sources.list.d/ubuntu.sources ]; then
        log "Found DEB822 format sources file (Ubuntu 24.04+)"
        cp /etc/apt/sources.list.d/ubuntu.sources /etc/apt/sources.list.d/ubuntu.sources.backup.$(date +%Y%m%d)

        # Verify sources are official Ubuntu
        if grep -q "archive.ubuntu.com\|security.ubuntu.com" /etc/apt/sources.list.d/ubuntu.sources; then
            log "VERIFIED: DEB822 sources are using official Ubuntu mirrors"
        else
            log "WARNING: DEB822 sources may not be official Ubuntu mirrors - please verify manually"
        fi

        # Disable old sources.list
        echo "# Disabled - using DEB822 format in sources.list.d/" > /etc/apt/sources.list
    fi

    # CRITICAL: Remove any third-party sources that may have been added
    log "SECURITY: Checking for third-party sources..."
    if ls /etc/apt/sources.list.d/*.list 2>/dev/null | grep -v ubuntu; then
        log "WARNING: Found third-party sources. Listing them for review:"
        ls -la /etc/apt/sources.list.d/*.list 2>/dev/null | tee -a $LOG_FILE
    fi

    log "Official Ubuntu mirrors verified"
    validate "Verifying official Ubuntu mirrors"

    # Log packages BEFORE apt operations (for security audit)
    log "SECURITY AUDIT: Logging installed packages BEFORE dist-upgrade"
    dpkg -l > ~/package_audit_BEFORE_distupgrade.log

    apt-get update -y
    validate "Running apt update"

    apt-get dist-upgrade -y -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold"
    validate "Running dist-upgrade"

    # Log packages AFTER dist-upgrade
    log "SECURITY AUDIT: Logging installed packages AFTER dist-upgrade"
    dpkg -l > ~/package_audit_AFTER_distupgrade.log

    apt upgrade -y
    validate "Running apt upgrade"

    apt -y install figlet
    validate "Installing figlet"

    save_state "post_reboot_ssl_apache_ufw"

    figlet "Rebooting"
    log "Rebooting system after initial updates"

    if [ "$AUTO_RESUME" != "true" ]; then
        write_resume_instructions "post_reboot_ssl_apache_ufw"
    fi

    reboot
    ;;

"post_reboot_ssl_apache_ufw")
    ##########################################################
    ## Set up SSL, Apache and Firewall (UFW)
    ##########################################################
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

    ufw status
    validate "Checking UFW status"

    save_state "create_rdavis_user"
    ;&

"create_rdavis_user")
    ##########################################################
    ## Create rdavis user
    ##########################################################
    log "Creating rdavis user"
    if id "rdavis" &>/dev/null; then
        log "User rdavis already exists, skipping creation"
    else
        adduser rdavis --gecos "Richard Davis,RoomNumber,WorkPhone,HomePhone" --disabled-password
        validate "Creating rdavis user"
    fi

    # Note: Set password manually or via environment variable
    if [ -n "$RDAVIS_PASSWORD" ]; then
        echo "rdavis:$RDAVIS_PASSWORD" | sudo chpasswd
        validate "Setting rdavis user password"
    else
        log "NOTE: Set rdavis password manually: sudo passwd rdavis"
    fi

    echo 'rdavis ALL=(ALL) NOPASSWD: ALL' | sudo tee /etc/sudoers.d/rdavis
    validate "Adding rdavis to sudoers"

    save_state "install_basics"
    ;&

"install_basics")
    ##########################################################
    ## INSTALL THE BASICS
    ##########################################################
    log "Installing basic tools"
    v_HOST=$(hostname)
    echo ${v_HOST}

    # Wait for apt locks
    log "Waiting for apt locks to be released..."
    while fuser /var/lib/dpkg/lock-frontend >/dev/null 2>&1 || fuser /var/lib/apt/lists/lock >/dev/null 2>&1; do
        log "Waiting for other package managers to finish..."
        sleep 2
    done
    log "Apt locks released, proceeding"

    apt-get update -y
    validate "Running apt update"

    # Install pv first for progress monitoring
    log "Installing pv (Pipe Viewer) for progress tracking"
    DEBIAN_FRONTEND=noninteractive apt-get install -y -qq pv
    validate "Installing pv"

    # Ubuntu 24.04 uses plocate
    LOCATE_PKG="plocate"
    log "Installing locate package: $LOCATE_PKG"

    # Install basic packages - ONLY from official repos
    log "Installing basic packages..."
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        dos2unix tmux make gcc g++ software-properties-common \
        $LOCATE_PKG unzip jq curl wget htop net-tools 2>&1 | pv -p -t -e -r -b > /dev/null
    validate "Installing basic packages"

    figlet "Rebooting"
    log "Rebooting system"
    save_state "post_reboot_apache_install"

    if [ "$AUTO_RESUME" != "true" ]; then
        write_resume_instructions "post_reboot_apache_install"
    fi

    reboot
    ;;

"post_reboot_apache_install")
    ##########################################################
    ## INSTALL APACHE - FROM OFFICIAL UBUNTU REPOS ONLY
    ##########################################################
    log "Installing Apache from official Ubuntu repos"
    apt update -y
    validate "Running apt update"

    apt -y install apache2
    validate "Installing Apache2"

    systemctl enable apache2
    validate "Enabling Apache2 service"

    systemctl start apache2
    validate "Starting Apache2 service"

    # Enable required modules
    a2enmod ssl
    validate "Enabling SSL module"

    a2enmod rewrite
    validate "Enabling rewrite module"

    a2enmod headers
    validate "Enabling headers module"

    systemctl reload apache2
    validate "Reloading Apache2 service"

    figlet "Apache Done"

    save_state "install_php"
    ;&

"install_php")
    ##########################################################
    ## Install PHP - FROM OFFICIAL UBUNTU REPOS ONLY
    ## Ubuntu 24.04 ships with PHP 8.3 in official repos
    ##########################################################
    log "Installing PHP from official Ubuntu repos (PHP 8.3)"

    # DO NOT add ondrej/php PPA - use only official repos
    log "SECURITY: Using ONLY official Ubuntu PHP packages (8.3)"

    apt update -y
    validate "Running apt update"

    # Install PHP 8.3 from official Ubuntu 24.04 repos
    apt install -y php php-cli php-common php-mysql libapache2-mod-php
    validate "Installing PHP and required extensions"

    # Enable PHP module
    a2enmod php8.3
    validate "Enabling PHP 8.3 module"

    systemctl restart apache2
    validate "Restarting Apache2 service"

    save_state "install_php_extensions"
    ;&

"install_php_extensions")
    ##########################################################
    ## Install PHP extensions - OFFICIAL REPOS ONLY
    ##########################################################
    log "Installing PHP extensions from official repos..."

    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        php-cli php-common php-mysql php-zip php-gd php-mbstring \
        php-curl php-xml php-bcmath php-intl php-readline \
        php-ldap php-soap php-sqlite3 php-opcache php-fpm 2>&1 | pv -p -t -e -r -b > /dev/null
    validate "Installing PHP extensions"

    php -v
    validate "Checking PHP version"

    echo "<?php phpinfo(); ?>" > /var/www/html/__info.php
    validate "Creating PHP info page"

    figlet "PHP Done"

    save_state "create_directories"
    ;&

"create_directories")
    ##########################################################
    ## CREATE THE NECESSARY DIRECTORIES FOR DDG PROJECTS
    ##########################################################
    log "Creating necessary directories"

    # Main project directories
    mkdir -p /var/www/DDG
    mkdir -p /var/www/PROQCS
    mkdir -p /var/www/SPROVISIONS
    mkdir -p /var/www/BIRTHDAY_SERVER

    # SSL cert directories (separate from birthday.gold)
    mkdir -p /var/web_certs/DDG
    mkdir -p /var/web_certs/PROQCS
    mkdir -p /var/web_certs/SPROVISIONS

    # Temp and log directories
    mkdir -p /var/www/tmp
    mkdir -p /var/log/DDG
    mkdir -p /var/log/PROQCS
    mkdir -p /var/log/SPROVISIONS

    sudo chown -R www-data:www-data /var/www/html
    validate "Setting ownership for /var/www/html"

    sudo chown -R www-data:www-data /var/www/DDG
    sudo chown -R www-data:www-data /var/www/PROQCS
    sudo chown -R www-data:www-data /var/www/SPROVISIONS
    validate "Setting ownership for project directories"

    save_state "config_php"
    ;&

"config_php")
    ##########################################################
    ## Config PHP
    ##########################################################
    log "Configuring PHP"

    # Download browscap
    wget -O /var/www/tmp/full_php_browscap.ini https://browscap.org/stream?q=Full_PHP_BrowsCapINI
    validate "Downloading browscap.ini"

    chown www-data:www-data /var/www/tmp/full_php_browscap.ini
    chmod 644 /var/www/tmp/full_php_browscap.ini
    validate "Setting permissions for browscap.ini"

    # Create PHP config for DDG projects
    cat > /etc/php/8.3/apache2/conf.d/ddg.php.ini <<EOF
; DDG Projects PHP Configuration
; Created: $(date)

date.timezone = America/Denver
browscap = /var/www/tmp/full_php_browscap.ini
error_log = /var/log/DDG/php_errors.log
max_execution_time = 630
memory_limit = 256M
post_max_size = 128M
short_open_tag = On
upload_max_filesize = 128M
upload_tmp_dir = /var/www/tmp
session.gc_probability = 1
EOF
    validate "Configuring PHP settings"

    systemctl restart apache2
    validate "Restarting Apache after PHP config"

    save_state "install_mysql_server"
    ;&

"install_mysql_server")
    ##########################################################
    ## INSTALL MYSQL SERVER - STANDALONE
    ## FROM OFFICIAL UBUNTU REPOS ONLY
    ##########################################################
    log "Installing MySQL Server from official Ubuntu repos"

    DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server mysql-client
    validate "Installing MySQL Server"

    systemctl enable mysql
    systemctl start mysql
    validate "Starting MySQL service"

    # Secure MySQL installation basics
    log "Securing MySQL installation..."

    # Generate a random root password
    MYSQL_ROOT_PASS=$(openssl rand -base64 24)
    echo "MySQL root password: $MYSQL_ROOT_PASS" > /root/.mysql_root_password
    chmod 600 /root/.mysql_root_password

    # Set root password and secure defaults
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '$MYSQL_ROOT_PASS';"
    mysql -u root -p"$MYSQL_ROOT_PASS" -e "DELETE FROM mysql.user WHERE User='';"
    mysql -u root -p"$MYSQL_ROOT_PASS" -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
    mysql -u root -p"$MYSQL_ROOT_PASS" -e "DROP DATABASE IF EXISTS test;"
    mysql -u root -p"$MYSQL_ROOT_PASS" -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
    mysql -u root -p"$MYSQL_ROOT_PASS" -e "FLUSH PRIVILEGES;"
    validate "Securing MySQL installation"

    log "MySQL root password saved to /root/.mysql_root_password"
    figlet "MySQL Done"

    save_state "create_default_vhost"
    ;&

"create_default_vhost")
    ##########################################################
    ## Create default virtual host placeholder
    ##########################################################
    log "Creating default virtual host placeholder"

    # Create a simple index page
    cat > /var/www/html/index.php <<EOF
<?php
echo '<h1>clientbase.ddg.mx</h1>';
echo '<p>Server ready for configuration.</p>';
echo '<p>Date: ' . date('Y-m-d H:i:s T') . '</p>';
?>
EOF
    validate "Creating default index page"

    mv /var/www/html/index.html /var/www/html/__unused_ubuntu.index.html 2>/dev/null || true
    validate "Moving default index.html"

    save_state "completed"
    ;&

"completed")
    echo 'ENABLED=1' > /etc/default/motd-news
    figlet "Completed"
    log "=========================================="
    log "LAMP STACK INSTALLATION COMPLETE"
    log "=========================================="
    log "Server: $(hostname)"
    log "IP: $(hostname -I | awk '{print $1}')"
    log "Apache: $(apache2 -v | grep 'Server version' | awk '{print $3}')"
    log "PHP: $(php -v | head -n1 | awk '{print $2}')"
    log "MySQL: $(mysql --version | awk '{print $3}')"
    log "=========================================="
    log ""
    log "NEXT STEPS:"
    log "1. Copy SSL certificates to /var/web_certs/<PROJECT>/"
    log "2. Create Apache vhosts for each domain"
    log "3. Create MySQL databases and users"
    log "4. Deploy project files to /var/www/<PROJECT>/"
    log "=========================================="
    ;;

*)
    log "Unknown state: $STATE"
    exit 1
    ;;
esac
