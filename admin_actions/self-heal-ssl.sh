#!/bin/bash
#
# Self-Healing SSL Certificate Script
# Each server can run this to automatically update its SSL certificates
#

SERVER_TYPE=""
CERT_DIR="/var/web_certs/BIRTHDAY_SERVER/birthday.gold/"
DEV_SERVER="https://dev7.birthday.gold/.claude/ssl/"
HAPROXY_SERVERS=("april21.bday.gold")

# Detect server type
HOSTNAME=$(hostname -f)
if printf '%s\n' "${HAPROXY_SERVERS[@]}" | grep -q "^${HOSTNAME}$"; then
    SERVER_TYPE="haproxy"
else
    SERVER_TYPE="webserver"
fi

echo "🔐 SSL Certificate Self-Heal Started on ${HOSTNAME} (${SERVER_TYPE})"

# Check if certificates need updating
REMOTE_CERT_DATE=$(curl -s --insecure "${DEV_SERVER}STAR_birthday_gold.crt" | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2)
LOCAL_CERT_DATE=$(openssl x509 -in "${CERT_DIR}STAR_birthday_gold.crt" -noout -enddate 2>/dev/null | cut -d= -f2)

if [ "$REMOTE_CERT_DATE" != "$LOCAL_CERT_DATE" ]; then
    echo "📡 Certificate update available, downloading..."
    
    cd "$CERT_DIR" || exit 1
    
    # Download new certificates (force overwrite)
    wget --no-check-certificate -O STAR_birthday_gold.crt "${DEV_SERVER}STAR_birthday_gold.crt"
    wget --no-check-certificate -O server.key "${DEV_SERVER}server.key"
    wget --no-check-certificate -O SectigoRSADomainValidationSecureServerCA.crt "${DEV_SERVER}SectigoRSADomainValidationSecureServerCA.crt"
    wget --no-check-certificate -O USERTrustRSAAAACA.crt "${DEV_SERVER}USERTrustRSAAAACA.crt"
    wget --no-check-certificate -O STAR_birthday_gold_chained.crt "${DEV_SERVER}STAR_birthday_gold_chained.crt"
    
    if [ "$SERVER_TYPE" = "haproxy" ]; then
        echo "⚖️  Updating HAProxy combined certificates..."
        cat STAR_birthday_gold.crt server.key SectigoRSADomainValidationSecureServerCA.crt USERTrustRSAAAACA.crt > combined.pem
        cat STAR_birthday_gold.crt server.key SectigoRSADomainValidationSecureServerCA.crt USERTrustRSAAAACA.crt > STAR_birthday_gold_combined.pem
        chmod 440 combined.pem STAR_birthday_gold_combined.pem
        chown root:root combined.pem STAR_birthday_gold_combined.pem
        systemctl restart haproxy
        echo "✅ HAProxy restarted with new certificates"
    else
        echo "🌐 Updating web server certificates..."
        chown -R www-data:www-data /var/web_certs/BIRTHDAY_SERVER
        chmod 440 ${CERT_DIR}*
        chmod 400 ${CERT_DIR}server.key
        systemctl restart apache2
        echo "✅ Apache restarted with new certificates"
    fi
    
    echo "🎉 Certificate update completed on ${HOSTNAME}"
else
    echo "✨ Certificates are up to date on ${HOSTNAME}"
fi