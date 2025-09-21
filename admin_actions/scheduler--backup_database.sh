#!/bin/bash
#### THIS SCRIPT IS STORED/EXECUTED at:  automation

# MySQL credentials
MYSQL_USER="root"
MYSQL_PASS=""

# Database names
DATABASES=("mysql" "birthday_gold_www")

# Backup directory
BACKUP_DIR="/root/DB_BACKUPS"
mkdir -p "$BACKUP_DIR"

# Backblaze S3 details
BUCKET_NAME="birthdaygold202306-technical"
ENDPOINT="s3.us-east-005.backblazeb2.com"

# Current date
DATE=$(date +%Y%m%d_%H%M%S)
DAY_OF_MONTH=$(date +%d)

# Determine the expiration date
if [ "$DAY_OF_MONTH" -eq "01" ]; then
    EXPIRATION_DAYS=60
else
    EXPIRATION_DAYS=7
fi

# Calculate the expiration date
EXPIRATION_DATE=$(date -d "+$EXPIRATION_DAYS days" -u +"%Y-%m-%dT%H:%M:%SZ")

# Loop through each database and process backups
for DB in "${DATABASES[@]}"; do
    # Define filename
    FILENAME="${DB}_${DATE}.sql.gz"

    # Dump, drop, and create with GTID enabled
    mysqldump --user="$MYSQL_USER" --single-transaction --set-gtid-purged=ON --databases $DB 2>&1 | grep -v 'Warning: A partial dump from a server that has GTIDs' | gzip > "$BACKUP_DIR/$FILENAME"

    # Upload to Backblaze bucket with expiration
    s3cmd put "$BACKUP_DIR/$FILENAME" s3://$BUCKET_NAME/december20.bday.gold/$FILENAME
    s3cmd setexp --expire-date="$EXPIRATION_DATE" s3://$BUCKET_NAME/december20.bday.gold/$FILENAME

    echo "Backup and upload complete for database: $DB with expiration date: $EXPIRATION_DATE"
done

# Delete local backups older than 7 days
find "$BACKUP_DIR" -type f -name "*.sql.gz" -mtime +7 -exec rm {} \;

echo "All operations completed."
