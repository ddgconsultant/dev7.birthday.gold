#!/bin/bash

# Check if the script is run as root
if [ "$(id -u)" -ne 0 ]; then
    echo "This script must be run as root" >&2
    exit 1
fi

# Define unified variable names
MESSAGING_URL=""
MESSAGING_USER=""
MESSAGING_PASSWORD=""
MESSAGE_ROOM_ID=""

# Check for required environment variables based on the message platform
if [ -z "$GITHUB_TOKEN" ] || [ -z "$MESSAGE_PLATFORM" ]; then
    echo "Required environment variables (GITHUB_TOKEN, MESSAGE_PLATFORM) are not set. Exiting..."
    exit 1
fi

case "$MESSAGE_PLATFORM" in
    'element')
        MESSAGING_URL="https://matrix.org"
        MESSAGING_USER="$MATRIX_USER"
        MESSAGING_PASSWORD="$MATRIX_PASSWORD"
        MESSAGE_ROOM_ID="$MATRIX_ROOM_ID"
        if [ -z "$MESSAGING_USER" ] || [ -z "$MESSAGING_PASSWORD" ] || [ -z "$MESSAGE_ROOM_ID" ]; then
            echo "Required environment variables for Element (MATRIX_USER, MATRIX_PASSWORD, MATRIX_ROOM_ID) are not set. Exiting..."
            exit 1
        fi
        ;;
    'rocketchat')
        MESSAGING_URL="$ROCKETCHAT_URL"
        MESSAGING_USER="$ROCKETCHAT_USER"
        MESSAGING_PASSWORD="$ROCKETCHAT_PASSWORD"
        MESSAGE_ROOM_ID="$ROCKETCHAT_ROOM_ID"
        if [ -z "$MESSAGING_URL" ] || [ -z "$MESSAGING_USER" ] || [ -z "$MESSAGING_PASSWORD" ] || [ -z "$MESSAGE_ROOM_ID" ]; then
            echo "Required environment variables for Rocket.Chat (ROCKETCHAT_URL, ROCKETCHAT_USER, ROCKETCHAT_PASSWORD, ROCKETCHAT_ROOM_ID) are not set. Exiting..."
            exit 1
        fi
        ;;
    *)
        echo "Invalid MESSAGE_PLATFORM specified. Exiting..."
        exit 1
        ;;
esac

# DEPLOY PRODUCTION BIRTHDAY GOLD
##########################################################
echo "Starting Deployment Process..."

# Set the working directory to the base path
pathprefix="/var/www/BIRTHDAY_SERVER/"
cd "${pathprefix}" || { echo "Failed to change directory to ${pathprefix}. Exiting..."; exit 1; }

subdomain=www
figlet "Deploy: ${subdomain}"
echo "$(date)"

# Retrieve the GitHub token securely
# Ensure GITHUB_TOKEN is set in a secure manner outside of this script
if [ -z "$GITHUB_TOKEN" ]; then
    echo "GitHub token not set. Exiting..."
    exit 1
fi

# Clone the repository
git clone https://$GITHUB_TOKEN@github.com/ddgconsultant/dev.birthday.gold.git "${subdomain}.birthday.gold_STAGE"

# Remove the previous directory if exists
rm -rf "${pathprefix}${subdomain}.birthday.gold_PREVIOUS"

# Rename the directories
mv "${pathprefix}${subdomain}.birthday.gold" "${subdomain}.birthday.gold_PREVIOUS"
mv "${pathprefix}${subdomain}.birthday.gold_STAGE" "${subdomain}.birthday.gold"

# Modify the site-controller.php file
sed -i "s/\$site = 'dev4';/\$site = '${subdomain}';/g" "${pathprefix}${subdomain}.birthday.gold/core/site-controller.php"
sed -i "s/\$mode = 'dev';/\$mode = 'production';/g" "${pathprefix}${subdomain}.birthday.gold/core/site-controller.php"

# Echo the release date to a file
echo "$(date "+%Y-%m-%d %H:%M:%S")" > "${pathprefix}${subdomain}.birthday.gold/__releasedate.txt"
cd "${pathprefix}${subdomain}.birthday.gold" || exit
git log -1 --pretty=%B > __latest_commit_message.txt

# Get the commit date
commit_date=$(git log -1 --format="%ad" --date=short)

# Convert the commit date to day of the year
day_of_year=$(date -d "$commit_date" '+%j')

# Get the current hour
hour=$(date '+%H')

# Construct the version string using day of the year
version_string="v2.${day_of_year}.${hour}"

# Update the PHP file (assuming v2/footerversion.php is writable)
echo "<?PHP \$footerappversion = '${version_string}';" > "${pathprefix}${subdomain}.birthday.gold/core/'.$website['ui_version'].'/footerversion.inc"

cd /var/www/BIRTHDAY_SERVER || exit
chown -R www-data:www-data "${pathprefix}${subdomain}.birthday.gold"

echo "Deployment Completed Successfully."

# Post Deployment Validation
##########################################################
figlet "Post Validation"
release_date_file="${pathprefix}${subdomain}.birthday.gold/__releasedate.txt"
version_file="${pathprefix}${subdomain}.birthday.gold/core/'.$website['ui_version'].'/footerversion.inc"
latest_commit_msg_file="${pathprefix}${subdomain}.birthday.gold/__latest_commit_message.txt"  # Path to the latest commit message file

# Check if release date file exists and display its content
if [ -f "$release_date_file" ]; then
    echo "Release Date:"
    cat "$release_date_file"
else
    echo "Release date file not found."
fi

# Check if version file exists and display its content
if [ -f "$version_file" ]; then
    echo "Version Info:"
    cat "$version_file"
else
    echo "Version file not found."
fi

# Check if the latest commit message file exists and display its content
if [ -f "$latest_commit_msg_file" ]; then
    echo "Latest Commit Message:"
    cat "$latest_commit_msg_file"
else
    echo "Latest commit message file not found."
fi


# change gather_server permissions
chmod +x admin_actions/gather_server_info.sh


# Deployment completion message
echo "Deployment Completed Successfully."

# Define the message body
hostname=$(hostname)
latest_commit_msg=$(cat "$latest_commit_msg_file")
message_body="❇️ Deployment completed successfully for BIRTHDAY GOLD on $hostname - $(date '+%Y-%m-%d %H:%M:%S') USING branch:  $latest_commit_msg"

# Handle different platforms
case "$MESSAGE_PLATFORM" in
    'element')
        # Obtain Matrix access token
        echo "Obtaining Matrix access token..."
        access_token_response=$(curl -s -XPOST -d "{\"type\":\"m.login.password\", \"user\":\"$MESSAGING_USER\", \"password\":\"$MESSAGING_PASSWORD\"}" "${MESSAGING_URL}/_matrix/client/r0/login")
        MATRIX_ACCESS_TOKEN=$(echo $access_token_response | jq -r '.access_token')

        if [ -z "$MATRIX_ACCESS_TOKEN" ]; then
            echo "Failed to obtain Matrix access token. Exiting..."
            exit 1
        fi

        echo "Access token obtained successfully."
        
        # Post deployment message to Matrix room
        echo "Sending deployment notification to Element Birthday.Gold Technical room..."
        api_endpoint="${MESSAGING_URL}/_matrix/client/r0/rooms/$MESSAGE_ROOM_ID/send/m.room.message?access_token=$MATRIX_ACCESS_TOKEN"
        curl_response=$(curl -s -w "%{http_code}" -o /dev/null -X POST -d "{\"msgtype\":\"m.text\", \"body\":\"$message_body\"}" -H "Content-Type: application/json" $api_endpoint)
        ;;
    'rocketchat')
        # Obtain Rocket.Chat authentication token
        echo "Obtaining Rocket.Chat authentication token..."
        auth_response=$(curl -s -X POST \
            --data-urlencode "user=${MESSAGING_USER}" \
            --data-urlencode "password=${MESSAGING_PASSWORD}" \
            "${MESSAGING_URL}/api/v1/login")
        auth_token=$(echo $auth_response | jq -r '.data.authToken')
        user_id=$(echo $auth_response | jq -r '.data.userId')

        if [ -z "$auth_token" ] || [ -z "$user_id" ]; then
            echo "Failed to obtain Rocket.Chat authentication token. Exiting..."
            exit 1
        fi

        echo "Authentication token obtained successfully."

        # Post deployment message to Rocket.Chat room
        echo "Sending deployment notification to Rocket.Chat..."
        api_endpoint="${MESSAGING_URL}/api/v1/chat.postMessage"
        
        curl_response=$(curl -s -w "%{http_code}" -o /dev/null -X POST \
            -H "X-Auth-Token: $auth_token" \
            -H "X-User-Id: $user_id" \
            -H "Content-Type: application/json" \
            -d "{\"channel\":\"#${MESSAGE_ROOM_ID}\", \"text\":\"$message_body\"}" \
            $api_endpoint)
        ;;
    *)
        echo "Invalid MESSAGE_PLATFORM specified. Exiting..."
        exit 1
        ;;
esac

# Check the response status
if [ "$curl_response" -eq 200 ]; then
    echo "Deployment notification sent successfully."
else
    echo "Failed to send deployment notification. HTTP status code: $curl_response"
fi

# End of the script

cd
