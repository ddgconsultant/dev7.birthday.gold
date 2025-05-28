#!/bin/bash

LOG_FILE=~/mail_server_setup_$(date +"%Y%m%d%H%M%S").log
STATE_FILE=~/mail_server_setup_state
ACTION_COUNTER=0

log() {
    echo "$(date +"%Y-%m-%d %H:%M:%S") - $1" | tee -a $LOG_FILE
    echo ""
}

validate() {
    if [ $? -ne 0 ]; then
        echo "FAIL" | tee -a $LOG_FILE
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
    echo "$1" >$STATE_FILE
    ACTION_COUNTER=$((ACTION_COUNTER + 1))
}

load_state() {
    if [ -f $STATE_FILE ]; then
        cat $STATE_FILE
    else
        echo "pre"
    fi
}

log "Starting mail server setup process on $(hostname)"

STATE=$(load_state)

# Check if the state is "completed" and no actions have been performed
if [ "$STATE" == "completed" ] && [ "$ACTION_COUNTER" -eq 0 ]; then
    echo "Check State File"
    log "The state file [$STATE_FILE] = completed"
    exit 0
fi

case $STATE in
"pre")
    echo "Starting"

    log "Setting up initial variables"
    file_to_copy="/root/db_password.sh"
    backup_file="/root/db_password.sh.bak$(date +%F)"
    if [ -f "$file_to_copy" ]; then
        cp "$file_to_copy" "$backup_file"
        echo "Backup of $file_to_copy created as $backup_file"
    fi
    dbpass=$(tr -dc 'A-Za-z0-9' </dev/urandom | head -c 16)
    dbuser=birthday_gold_mailslinger
    maildomain=mybdaygold.com
    echo "export dbuser=$dbuser" >/root/db_password.sh
    echo "export dbpass=$dbpass" >>/root/db_password.sh
    echo "export maildomain=$maildomain" >>/root/db_password.sh
    chmod 600 /root/db_password.sh
    cat /root/db_password.sh
    validate "Setting up initial variables"

    save_state "install_packages"
    ;&
##########################################################
"install_packages")
    log "Updating system"
    timedatectl set-timezone America/Denver

    export DEBIAN_FRONTEND=noninteractive

    # Suppress kernel warnings by disabling the motd-news service
    echo 'ENABLED=0' >/etc/default/motd-news

    apt-get dist-upgrade -y -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold"

    apt update
    apt upgrade -y

    log "postfix configuration"
    # Preconfigure Postfix to avoid interactive screens
    echo "postfix postfix/mailname string mybdaygold.com" | debconf-set-selections
    echo "postfix postfix/main_mailer_type select Internet Site" | debconf-set-selections

    log "Installing necessary packages"
    apt install -y net-tools figlet php php-cli php-mbstring unzip curl php-xml composer php-mysql postfix postfix-mysql \
        dovecot-core dovecot-imapd dovecot-mysql mysql-server php-mailparse php-dev php-pear dos2unix logrotate mysql-client \
        php-curl php-opcache php-pdo php-calendar php-ctype php-dom php-exif php-ffi php-fileinfo php-ftp php-iconv php-intl php-phar \
        php-posix php-readline php-shmop php-simplexml php-sockets php-sysvmsg php-sysvsem php-sysvshm php-tokenizer php-xmlreader php-xmlwriter php-xsl
    validate "Installing necessary packages"
    save_state "configure_composer"
    ;&
##########################################################
"configure_composer")
    figlet "Composer"
    log "Setting up Composer"
    cd ~
    curl -sS https://getcomposer.org/installer -o composer-setup.php
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
    # Prevent the root user prompt by setting the COMPOSER_ALLOW_SUPERUSER environment variable
    export COMPOSER_ALLOW_SUPERUSER=1
    composer --version
    # composer require php-mime-mail-parser/php-mime-mail-parser    ### don't install under superuser --needs to be installed for vmail
    validate "Setting up Composer"

    log "Creating vmail user and directories"
    groupadd -g 5000 vmail
    useradd -g vmail -u 5000 vmail -d /var/vmail -m

    # Ensure Composer dependencies are installed
    su - vmail -c "mkdir -p /var/vmail && cd /var/vmail && echo '{}' > composer.json && composer require php-mime-mail-parser/php-mime-mail-parser"

    # Source the database configuration file
    source /root/db_password.sh

    su - vmail -c "mkdir -p /var/vmail/ENV_CONFIGS/"

    cat <<EOL >/var/vmail/ENV_CONFIGS/config-database.inc
<?php
\$db = [
    "host" => "mysql:host=localhost;dbname=mailserver",
    "user" => "$dbuser",
    "password" => "$dbpass"
];
EOL
    chown vmail:vmail /var/vmail/ENV_CONFIGS/config-database.inc
    validate "Creating vmail user and directories"

    save_state "configure_firewall"
    ;&
##########################################################
"configure_firewall")
    figlet "Firewall"
    log "Configuring firewall"
    ufw --force enable
    ufw allow 22
    ufw allow 3306
    ufw allow 25
    ufw allow 465
    ufw allow 587
    ufw allow 110
    ufw allow 995
    ufw allow 143
    ufw allow 993
    ufw status
    validate "System update and firewall configuration"
    log "Rebooting"
    figlet "Rebooting"
    save_state "configure_mysql"

    reboot
    exit
    ;&
##########################################################
"configure_mysql")
    figlet "MySQL"
    log "Configuring MySQL database and user"

    # Secure MySQL installation
    mysql -e "DELETE FROM mysql.user WHERE User='';"
    mysql -e "DROP DATABASE IF EXISTS test;"
    mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
    mysql -e "FLUSH PRIVILEGES;"

    # Modify bind address to allow remote connections
    sed -i '/^bind-address/s/^bind-address/# bind-address/' /etc/mysql/mysql.conf.d/mysqld.cnf
    systemctl restart mysql

    # Source the database configuration file
    source /root/db_password.sh

    # Prompt user for the birthday_gold_admin password
    read -sp "Enter the password for MySQL [birthday_gold_admin]: " birthday_gold_admin_dbpass

mailserver=$(hostname)
    # Create database and users
    cat <<EOL >setup.sql
CREATE DATABASE IF NOT EXISTS mailserver;

USE mailserver;

CREATE TABLE IF NOT EXISTS bg_mail_companies (
  id BIGINT NOT NULL AUTO_INCREMENT,
  company_id BIGINT UNSIGNED NOT NULL,
  email_domain VARCHAR(255) NOT NULL,
  status VARCHAR(32) DEFAULT 'active',
  create_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id) USING BTREE,
  KEY idx0 (email_domain, company_id) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS bg_mail_users (
  user_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  feature_email VARCHAR(320) NOT NULL,
  status VARCHAR(32) DEFAULT 'active',
  create_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY feature_email (feature_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS messages (
  message_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED DEFAULT NULL,
  recipient VARCHAR(320) DEFAULT NULL,
  sender VARCHAR(320) DEFAULT NULL,
  company_id BIGINT UNSIGNED DEFAULT NULL,
  subject VARCHAR(1000) DEFAULT NULL,
  body LONGTEXT,
  size BIGINT UNSIGNED DEFAULT '0',
  mailserver varchar(32) DEFAULT  '$mailserver'
  processstatus VARCHAR(32) DEFAULT 'new',
  create_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



CREATE DATABASE email_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE email_db;

-- Table to store email messages
CREATE TABLE emails (
  id bigint NOT NULL AUTO_INCREMENT,
  message_id varchar(255) NOT NULL,
  mailserver varchar(32) NOT NULL DEFAULT '$mailserver',
  subject varchar(1000) DEFAULT NULL,
  company_id bigint DEFAULT NULL,
  from_email varchar(320) DEFAULT NULL,
  from_name varchar(255) DEFAULT NULL,
  user_id bigint DEFAULT NULL,
  to_email longtext,
  cc_email longtext,
  bcc_email longtext,
  reply_to varchar(320) DEFAULT NULL,
  date_sent datetime DEFAULT NULL,
  body_plain longtext,
  body_html longtext,
  compressed_json longblob,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id,mailserver DESC) USING BTREE,
  UNIQUE KEY message_id (message_id)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Table to store email headers
CREATE TABLE email_headers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    email_id BIGINT,
    header_name VARCHAR(255),
    header_value TEXT,
    FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Table to store sender fingerprint
CREATE TABLE sender_fingerprints (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    email_id BIGINT,
    sender_ip VARCHAR(45),
    sender_host VARCHAR(255),
    sender_user_agent VARCHAR(255),
    sender_geo_location VARCHAR(255),
    FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Table to store email attachments
CREATE TABLE email_attachments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    email_id BIGINT,
    file_name VARCHAR(255),
    file_type VARCHAR(255),
    file_size BIGINT,
    file_content LONGBLOB,
    FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Table to store email metadata
CREATE TABLE email_metadata (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    email_id BIGINT,
    meta_key VARCHAR(255),
    meta_value TEXT,
    FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Table to store email flags (e.g., read, important)
CREATE TABLE email_flags (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    email_id BIGINT,
    flag_name VARCHAR(255),
    flag_value BOOLEAN,
    set_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;



-- CREATE USERS ==================================================================
CREATE USER '$dbuser'@'localhost' IDENTIFIED BY '$dbpass';
GRANT ALL PRIVILEGES ON mailserver.* TO '$dbuser'@'localhost';
GRANT ALL PRIVILEGES ON email_db.* TO '$dbuser'@'localhost';


-- CREATE ADMIN CREDENTIAL =======================================================
CREATE USER 'birthday_gold_admin'@'%' IDENTIFIED BY '$birthday_gold_admin_dbpass';
GRANT ALL ON *.* TO 'birthday_gold_admin'@'%' WITH GRANT OPTION;

FLUSH PRIVILEGES;



EOL
    mysql <setup.sql
    validate "Configuring MySQL database and user"

    save_state "configure_postfix"
    ;&
##########################################################
"configure_postfix")

    source /root/db_password.sh

    figlet "Postfix"
    log "Configuring Postfix"
    echo "postfix postfix/mailname string ${maildomain}" | debconf-set-selections
    echo "postfix postfix/main_mailer_type select Internet Site" | debconf-set-selections
    sed -i '/^myhostname/ s/^/#/' /etc/postfix/main.cf
    sed -i '/^mydestination/ s/^/#/' /etc/postfix/main.cf
    sed -i '/^virtual_mailbox_domains/ s/^/#/' /etc/postfix/main.cf
    sed -i '/^virtual_mailbox_maps/ s/^/#/' /etc/postfix/main.cf
    sed -i '/^virtual_alias_maps/ s/^/#/' /etc/postfix/main.cf
    echo "myhostname = $(hostname).$maildomain" | sudo tee -a /etc/postfix/main.cf
    echo "mydestination = \$myhostname, localhost" | tee -a /etc/postfix/main.cf

    echo "virtual_mailbox_domains = $maildomain" | tee -a /etc/postfix/main.cf
    echo "virtual_mailbox_base = /var/vmail" | tee -a /etc/postfix/main.cf
    echo "virtual_mailbox_maps = mysql:/etc/postfix/mysql_virtual_mailbox_maps.cf" | tee -a /etc/postfix/main.cf
    echo "virtual_minimum_uid = 100" | tee -a /etc/postfix/main.cf
    echo "virtual_uid_maps = static:1001" | tee -a /etc/postfix/main.cf
    echo "virtual_gid_maps = static:1001" | tee -a /etc/postfix/main.cf
    echo "virtual_transport = dovecot" | tee -a /etc/postfix/main.cf
    echo "dovecot_destination_recipient_limit = 1" | tee -a /etc/postfix/main.cf

    # echo "virtual_alias_maps = mysql:/etc/postfix/mysql_virtual_alias_maps.cf" | tee -a /etc/postfix/main.cf

    cat <<EOF | tee /etc/postfix/mysql_virtual_mailbox_maps.cf
user = $dbuser
password = $dbpass
hosts = 127.0.0.1
dbname = mailserver
query = SELECT 1 FROM bg_mail_users WHERE feature_email='%s' and status='active'
EOF
    systemctl restart postfix
    validate "Configuring Postfix"

    save_state "configure_dovecot"
    ;&
##########################################################
"configure_dovecot")
    figlet "Dovecot"
    log "Configuring Dovecot"
    source /root/db_password.sh
    cat <<EOF | tee /etc/dovecot/dovecot-sql.conf.ext
driver = mysql
connect = host=127.0.0.1 dbname=mailserver user=$dbuser password=$dbpass
default_pass_scheme = SHA512-CRYPT
password_query = SELECT feature_email as user, password FROM bg_mail_users WHERE feature_email='%u';
user_query = SELECT '/var/vmail/%d/%n' as home, 'maildir:/var/vmail/%d/%n' as mail FROM bg_mail_users WHERE feature_email='%u';
EOF

    echo "mail_location = maildir:/var/vmail/%d/%n" | tee /etc/dovecot/conf.d/10-mail.conf
    systemctl restart dovecot
    validate "Configuring Dovecot"

    save_state "configure_php_script"
    ;&
##########################################################
"configure_php_script")
    figlet "PHP"
  
    log "Add mailserver IP $ip_address to Authorized Mail Servers API"
    ip_address=$(hostname -I | awk '{print $1}')
    full_hostname=$(hostname -f)
    URL="https://api.birthday.gold/authorizemailserver.php?ip=$ip_address&hostname=$full_hostname"
    log "$URL"
    curl "$URL" && echo ""  
        validate "Authorize IP"
      
    log "Get delivery.php script"
    wget -O /usr/local/bin/delivery.php https://api.birthday.gold/mailserver/delivery.php
    dos2unix /usr/local/bin/delivery.php
    chmod +x /usr/local/bin/delivery.php
    chown vmail:vmail /usr/local/bin/delivery.php
    validate "Validate delivery.php file"


    log "Get mail_datarefresher.inc script"
    wget -O /usr/local/bin/mail_datarefresher.inc https://api.birthday.gold/mailserver/mail_datarefresher.inc
    dos2unix /usr/local/bin/mail_datarefresher.inc
    chmod +x /usr/local/bin/mail_datarefresher.inc
    chown vmail:vmail /usr/local/bin/mail_datarefresher.inc
    validate "Validate mail_datarefresher.inc file"


    echo -e "\ndovecot unix - n n - - pipe\n  flags=DRhu user=vmail:vmail argv=/usr/local/bin/delivery.php \${recipient}" | tee -a /etc/postfix/master.cf
    validate "Configuring PHP mail delivery script"
     systemctl restart postfix
    validate "Restart postfix"

    save_state "dns_setup"

    ;&
##########################################################
"dns_setup")
    log "Setting up DNS records"
    figlet "Add DNS Records"
    full_hostname=$(hostname -f)
    primary_ip=$(hostname -I | awk '{print $1}')
    numeric_part=$(echo $full_hostname | grep -o '[0-9]\+')
    dns_record="mail${numeric_part}.mybdaygold.com"
    mx_record="mail${numeric_part}.mybdaygold.com"
    cat <<EOF
============================================================
Please add the following DNS records to your DNS server:

A Record:
Hostname: $dns_record
IP Address: $primary_ip
TTL: 10800 (3 hours)
$dns_record IN A $primary_ip ; TTL 3 hours

MX Record:
Hostname: $mx_record
Priority: 10
TTL: 10800 (3 hours)
mybdaygold.com IN MX 10 $mx_record ; TTL 3 hours
============================================================
EOF
    validate "Setting up DNS records"

    save_state "testing"
    ;&
##########################################################
"testing")
    log "Testing mail server setup"

    # Insert a test user into the database
    mysql -D mailserver -e "INSERT INTO bg_mail_users (feature_email) VALUES ('test@mybdaygold.com');"

    # Send a test email
    echo -e "From: test@mybdaygold.com\r\nSubject: Test\r\n\r\nThis is a test body" | php /usr/local/bin/delivery.php test@mybdaygold.com
    validate "Testing mail server setup"

    save_state "logrotate_setup"
    ;&
##########################################################
"logrotate_setup")
    log "Setting up logrotate configurations"
    figlet "LogRotate"
    # ###--------------------------------------------------------------------------
    CONFIG_APACHE2=$(
        cat <<'EOF'
/var/log/apache2/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 640 root adm
    sharedscripts
    prerotate
        if [ -d /etc/logrotate.d/httpd-prerotate ]; then
            run-parts /etc/logrotate.d/httpd-prerotate
        fi
    endscript
    postrotate
        if pgrep -f ^/usr/sbin/apache2 > /dev/null; then
            invoke-rc.d apache2 reload 2>&1 | logger -t apache2.logrotate
        fi
    endscript
}
EOF
    )
    # ###--------------------------------------------------------------------------
    CONFIG_MYSQL_SERVER=$(
        cat <<'EOF'
/var/log/mysql.log /var/log/mysql/*log {
    daily
    rotate 7
    missingok
    create 640 mysql adm
    compress
    sharedscripts
    postrotate
        test -x /usr/bin/mysqladmin || exit 0
        MYADMIN="/usr/bin/mysqladmin --defaults-file=/etc/mysql/debian.cnf"
        if [ -z "`$MYADMIN ping 2>/dev/null`" ]; then
            if killall -q -s0 -umysql mysqld; then
                exit 1
            fi
        else
            $MYADMIN flush-logs
        fi
    endscript
}
EOF
    )
    # ###--------------------------------------------------------------------------
    CONFIG_REFRESH_USERS=$(
        cat <<'EOF'
$HOME/refresh_users.log {
    weekly
    missingok
    rotate 4
    compress
    delaycompress
    notifempty
    create 0664 $USER $USER
}
EOF
    )

    # Replace variables in the configuration
    CONFIG_REFRESH_USERS="${CONFIG_REFRESH_USERS//\$HOME/$HOME}"
    CONFIG_REFRESH_USERS="${CONFIG_REFRESH_USERS//\$USER/$USER}"
    # ###--------------------------------------------------------------------------
    CONFIG_REFRESH_COMPANIES=$(
        cat <<'EOF'
$HOME/refresh_companies.log {
    monthly
    missingok
    rotate 12
    compress
    delaycompress
    notifempty
    create 0664 $USER $USER
}
EOF
    )

    # Replace variables in the configuration
    CONFIG_REFRESH_COMPANIES="${CONFIG_REFRESH_COMPANIES//\$HOME/$HOME}"
    CONFIG_REFRESH_COMPANIES="${CONFIG_REFRESH_COMPANIES//\$USER/$USER}"
    # ###--------------------------------------------------------------------------
    CONFIG_MAIL=$(
        cat <<'EOF'
/var/log/mail.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 root adm
    postrotate
        /etc/init.d/rsyslog restart > /dev/null
    endscript
}
EOF
    )
    # ###--------------------------------------------------------------------------
    echo "$CONFIG_APACHE2" | tee /etc/logrotate.d/apache2
    echo "$CONFIG_MYSQL_SERVER" | tee /etc/logrotate.d/mysql-server
    echo "$CONFIG_REFRESH_USERS" | tee /etc/logrotate.d/refresh_users
    echo "$CONFIG_REFRESH_COMPANIES" | tee /etc/logrotate.d/refresh_companies
    echo "$CONFIG_MAIL" | tee /etc/logrotate.d/mail
    validate "Setting up logrotate configurations"

    save_state "crontab_setup"
    ;&
##########################################################
"crontab_setup")
    # Add cron jobs to root crontab
    log "Adding cron jobs to root crontab"
    crontab -l > mycron
    echo "0 * * * * /usr/local/bin/delivery.php refresh_users >> ~/refresh_users.log 2>&1" >> mycron
    echo "30 2 * * * /usr/local/bin/delivery.php refresh_companies >> ~/refresh_companies.log 2>&1" >> mycron
    crontab mycron
    rm mycron
    validate "Adding cron jobs to root crontab"
    save_state "populate_data"
    ;&
##########################################################
"populate_data")
    log "Populating Data"
    figlet "Populating Data"

    log "running: /usr/local/bin/delivery.php populate_companies >> ~/refresh_companies.log 2>&1"
    /usr/local/bin/delivery.php populate_companies >> ~/refresh_companies.log 2>&1 
    validate "Populating Company Data"

    log "running: /usr/local/bin/delivery.php populate_users >> ~/refresh_users.log 2>&1"
    /usr/local/bin/delivery.php populate_users >> ~/refresh_users.log 2>&1
    validate "Populating User Data"

    mysql mailserver -e"select 'bg_mail_companies', count(*) from bg_mail_companies;select 'bg_mail_users', count(*) from bg_mail_users;"
    validate "Validate MySQL Data Counts"

    save_state "completed"
    ;&
##########################################################
"completed")
    # Re-enable kernel warnings by disabling the motd-news service
    echo 'ENABLED=1' >/etc/default/motd-news
    figlet "Completed MAILSERVER"
    log "Mail server setup process completed successfully on $(hostname)"
    ;;
*)
    log "Unknown state: $STATE"
    exit 1
    ;;
esac
