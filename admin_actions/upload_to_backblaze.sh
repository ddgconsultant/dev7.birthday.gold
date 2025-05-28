#!/bin/bash

# Backblaze S3 details
BUCKET_NAME="birthdaygold202306-technical"
ENDPOINT="s3.us-east-005.backblazeb2.com"
FOLDER_NAME="$1" # Folder name passed as the first argument
FILE_PATH="$2"   # File path passed as the second argument

if [[ -z "$FOLDER_NAME" || -z "$FILE_PATH" ]]; then
    echo "Usage: $0 <folder_name> <file_path>"
    exit 1
fi

# Extract the filename from the file path
FILENAME=$(basename "$FILE_PATH")

# Upload to Backblaze bucket
s3cmd put "$FILE_PATH" "s3://$BUCKET_NAME/$FOLDER_NAME/$FILENAME" --host="$ENDPOINT" --host-bucket="%(bucket)s.$ENDPOINT"

if [[ $? -eq 0 ]]; then
    echo "File uploaded successfully: s3://$BUCKET_NAME/$FOLDER_NAME/$FILENAME"
else
    echo "File upload failed"
fi
