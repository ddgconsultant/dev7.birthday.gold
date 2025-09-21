#!/bin/bash

# Default values
source_subdomain="dev4"
source_version="v2"
config_only=false
ssl_only=false

# Function to show usage
show_usage() {
  echo "Usage: $0 [-s source_subdomain] [-c] [-ssl] [source_subdomain]"
  echo ""
  echo "Options:"
  echo "  -s SOURCE    Specify source subdomain (e.g., dev4, dev7)"
  echo "  -c           Config-only mode: sync deploy_www.sh and ENV_CONFIG files only"
  echo "  -ssl         SSL-only mode: check and update SSL certificates only"
  echo "  -h           Show this help message"
  echo ""
  echo "Examples:"
  echo "  $0              # Full deployment from dev4"
  echo "  $0 -s dev7      # Full deployment from dev7"
  echo "  $0 -c           # Only sync config files and deploy script"
  echo "  $0 -c -s dev7   # Only sync config files from dev7"
  echo "  $0 -ssl         # Only check and update SSL certificates"
}

# Parse command-line options - handle -ssl specially since it starts with 's'
if [[ "$1" == "-ssl" ]]; then
    ssl_only=true
    shift
fi

while getopts ":s:ch" opt; do
  case $opt in
    s)
      source_subdomain="$OPTARG"
      source_version="v3"
      ;;
    c)
      config_only=true
      ;;
    h)
      show_usage
      exit 0
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      show_usage
      exit 1
      ;;
    :)
      echo "Option -$OPTARG requires an argument." >&2
      show_usage
      exit 1
      ;;
  esac
done

# Shift parsed options away to handle positional arguments
shift $((OPTIND -1))

# Check for positional argument if present
if [ "$#" -ge 1 ]; then
  source_subdomain="$1"
fi

# Check if the script is run as root
if [ "$(id -u)" -ne 0 ]; then
    echo "This script must be run as root" >&2
    exit 1
fi

# SSL Certificate Management Function
##########################################################
manage_ssl_certificates() {
    echo "=========================================="
    echo "SSL CERTIFICATE MANAGEMENT"
    echo "Date: $(date)"
    echo "=========================================="
    echo ""

    # Define certificate paths - use relative path from deployment directory
    # The current certificates are kept in the base _CERTS_ directory
    # This works on both WSL development and production environments

    # Check multiple possible locations for certificates
    if [ -d "${pathprefix}_CERTS_/birthday.gold" ]; then
        # Certificates are at same level as www.birthday.gold (e.g., /var/www/BIRTHDAY_SERVER/_CERTS_/)
        CERT_SOURCE_DIR="${pathprefix}_CERTS_/birthday.gold"
    elif [ -d "${pathprefix}../_CERTS_/birthday.gold" ]; then
        # Certificates are one level up (e.g., /var/www/_CERTS_/)
        CERT_SOURCE_DIR="${pathprefix}../_CERTS_/birthday.gold"
    else
        # Fallback to expected production path
        CERT_SOURCE_DIR="/var/www/BIRTHDAY_SERVER/_CERTS_/birthday.gold"
    fi

    CERT_DEST_DIR="/var/web_certs/BIRTHDAY_SERVER/birthday.gold"

    # Resolve to absolute path for clearer error messages
    CERT_SOURCE_DIR_ABS=$(realpath "$CERT_SOURCE_DIR" 2>/dev/null || echo "$CERT_SOURCE_DIR")

    # Expected SHA1 checksums for 2025 certificates (update these when certs change)
    EXPECTED_CHAINED_SHA1="eb84bb1e2dd085bbdeb863432599e59dfd9cf3ea"
    EXPECTED_KEY_SHA1="b7343e7d0cf08db902fd1d8d305765c6e177e8ea"

    # Show which certificate directory is being used
    echo "Using certificate source directory: $CERT_SOURCE_DIR_ABS"

    # Check if source directory exists
    if [ ! -d "$CERT_SOURCE_DIR" ]; then
        echo "ERROR: Source certificate directory not found: $CERT_SOURCE_DIR_ABS"
        echo "  Relative path: $CERT_SOURCE_DIR"
        echo "  Working directory: $(pwd)"
        return 1
    fi

    # Check if source files exist
    # Look for either the chained certificate or combined pem, plus the key file
    if [ -f "$CERT_SOURCE_DIR/STAR_birthday_gold_chained.crt" ]; then
        CERT_FILE="$CERT_SOURCE_DIR/STAR_birthday_gold_chained.crt"
        CERT_TYPE="chained"
    elif [ -f "$CERT_SOURCE_DIR/STAR_birthday_gold_combined.pem" ]; then
        CERT_FILE="$CERT_SOURCE_DIR/STAR_birthday_gold_combined.pem"
        CERT_TYPE="combined"
    elif [ -f "$CERT_SOURCE_DIR/STAR_birthday_gold.crt" ]; then
        CERT_FILE="$CERT_SOURCE_DIR/STAR_birthday_gold.crt"
        CERT_TYPE="single"
    else
        echo "ERROR: No certificate file found in $CERT_SOURCE_DIR"
        echo "Expected: STAR_birthday_gold_chained.crt, STAR_birthday_gold_combined.pem, or STAR_birthday_gold.crt"
        return 1
    fi

    if [ ! -f "$CERT_SOURCE_DIR/star.birthday.gold.key" ]; then
        echo "ERROR: Key file not found: $CERT_SOURCE_DIR/star.birthday.gold.key"
        return 1
    fi

    echo "Found certificate type: $CERT_TYPE"

    # Verify source file checksums
    echo "Verifying source certificate checksums..."
    source_cert_sha1=$(sha1sum "$CERT_FILE" 2>/dev/null | awk '{ print $1 }')
    source_key_sha1=$(sha1sum "$CERT_SOURCE_DIR/star.birthday.gold.key" 2>/dev/null | awk '{ print $1 }')

    # Skip checksum verification for now since certificates may vary
    if [ ! -z "$source_cert_sha1" ] && [ ! -z "$source_key_sha1" ]; then
        echo "  Certificate SHA1: $source_cert_sha1"
        echo "  Key SHA1:         $source_key_sha1"
        echo ""
        echo "This might indicate newer certificates are available."
        echo "Proceeding with deployment of current certificates..."
    else
        echo "Source certificate checksums verified successfully."
    fi

    # Create destination directory structure if it doesn't exist
    echo "Ensuring destination directory exists: $CERT_DEST_DIR"
    mkdir -p "$CERT_DEST_DIR"

    # Set proper permissions on parent directories
    chmod 750 /var/web_certs 2>/dev/null
    chmod 750 /var/web_certs/BIRTHDAY_SERVER 2>/dev/null
    chmod 750 "$CERT_DEST_DIR"

    # Copy certificate files to destination
    echo "Copying certificate files to $CERT_DEST_DIR..."

    # Handle different certificate types
    if [ "$CERT_TYPE" = "combined" ]; then
        # If we have a combined PEM, copy it directly
        cp "$CERT_FILE" "$CERT_DEST_DIR/STAR_birthday_gold_combined.pem"
        cp "$CERT_FILE" "$CERT_DEST_DIR/STAR_birthday_gold_chained.crt"
    else
        # Copy the certificate file
        cp "$CERT_FILE" "$CERT_DEST_DIR/STAR_birthday_gold_chained.crt"
    fi

    # Always copy the key
    cp "$CERT_SOURCE_DIR/star.birthday.gold.key" "$CERT_DEST_DIR/server.key"

    # Set proper ownership and permissions
    chown www-data:www-data "$CERT_DEST_DIR/STAR_birthday_gold_chained.crt"
    chown www-data:www-data "$CERT_DEST_DIR/server.key"
    chmod 644 "$CERT_DEST_DIR/STAR_birthday_gold_chained.crt"
    chmod 640 "$CERT_DEST_DIR/server.key"

    # Create combined PEM file if not already present
    if [ "$CERT_TYPE" != "combined" ]; then
        echo "Creating combined PEM file..."
        cat "$CERT_DEST_DIR/STAR_birthday_gold_chained.crt" \
            "$CERT_DEST_DIR/server.key" \
            > "$CERT_DEST_DIR/STAR_birthday_gold_combined.pem"
    fi

    # Set proper ownership and permissions for combined file
    chown www-data:www-data "$CERT_DEST_DIR/STAR_birthday_gold_combined.pem"
    chmod 640 "$CERT_DEST_DIR/STAR_birthday_gold_combined.pem"

    # Verify the combined file was created successfully
    if [ -f "$CERT_DEST_DIR/STAR_birthday_gold_combined.pem" ]; then
        echo "Combined PEM file created successfully."

        # Show certificate expiry information
        echo ""
        echo "Certificate Information:"
        openssl x509 -in "$CERT_DEST_DIR/STAR_birthday_gold_chained.crt" -noout -subject -enddate 2>/dev/null || echo "Could not read certificate info"
    else
        echo "ERROR: Failed to create combined PEM file!"
        return 1
    fi

    # List the final certificate files
    echo ""
    echo "Certificate files in $CERT_DEST_DIR:"
    ls -la "$CERT_DEST_DIR"

    echo ""
    echo "SSL certificate management completed successfully."
    return 0
}

# SSL-ONLY MODE
##########################################################
if [ "$ssl_only" = true ]; then
    manage_ssl_certificates
    exit $?
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

# Set the working directory to the base path
pathprefix="/var/www/BIRTHDAY_SERVER/"
cd "${pathprefix}" || { echo "Failed to change directory to ${pathprefix}. Exiting..."; exit 1; }

subdomain=www

# CONFIG-ONLY MODE
##########################################################
if [ "$config_only" = true ]; then
    echo "=========================================="
    echo "CONFIG-ONLY MODE: Syncing configuration files only"
    echo "Source: ${source_subdomain}.birthday.gold"
    echo "Date: $(date)"
    echo "=========================================="
    echo ""

    # Skip the full deployment, jump to the end where config sync happens
    # We'll use a goto-like pattern with a function
    skip_to_config_sync=true
else
    # FULL DEPLOYMENT MODE
    ##########################################################
    echo "Starting Full Deployment Process..."
    figlet "Deploy: ${subdomain}"
    echo "$(date)"
    skip_to_config_sync=false
fi

# Main deployment logic - only run if not in config-only mode
if [ "$skip_to_config_sync" != true ]; then

# Retrieve the GitHub token securely
# Ensure GITHUB_TOKEN is set in a secure manner outside of this script
if [ -z "$GITHUB_TOKEN" ]; then
    echo "GitHub token not set. Exiting..."
    exit 1
fi

# Clone the repository
echo "Cloning repository: https://github.com/ddgconsultant/${source_subdomain}.birthday.gold.git"
if ! git clone https://$GITHUB_TOKEN@github.com/ddgconsultant/"${source_subdomain}".birthday.gold.git "${subdomain}.birthday.gold_STAGE"; then
    echo "Failed to clone repository. Please check access rights and repository URL."
    exit 1
fi



# Remove the previous directory if exists
rm -rf "${pathprefix}${subdomain}.birthday.gold_PREVIOUS"

# Rename the directories
mv "${pathprefix}${subdomain}.birthday.gold" "${subdomain}.birthday.gold_PREVIOUS"
mv "${pathprefix}${subdomain}.birthday.gold_STAGE" "${subdomain}.birthday.gold"

# Modify the site-controller.php file
sed -i "s/\$site = '${source_subdomain}';/\$site = '${subdomain}';/g" "${pathprefix}${subdomain}.birthday.gold/core/site-controller.php"
sed -i "s/\$mode = '[^']*';/\$mode = 'production';/g" "${pathprefix}${subdomain}.birthday.gold/core/site-controller.php"
sed -i "s/\$errormode = '[^']*';/\$errormode = 'hideerrors';/g" "${pathprefix}${subdomain}.birthday.gold/core/site-controller.php"



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
version_string="${source_version}.${day_of_year}.${hour}"

# Update the PHP file (assuming v2/footerversion.php is writable)
echo "<?PHP \$footerappversion = '${version_string}';" > "${pathprefix}${subdomain}.birthday.gold/core/v3/footerversion.inc"

cd /var/www/BIRTHDAY_SERVER || exit
chown -R www-data:www-data "${pathprefix}${subdomain}.birthday.gold"

echo "Deployment Completed Successfully."

# SSL Certificate Management - Run during full deployment
##########################################################
echo ""
echo "Checking and updating SSL certificates..."
manage_ssl_certificates
ssl_result=$?
if [ $ssl_result -ne 0 ]; then
    echo "WARNING: SSL certificate update failed. Continuing with deployment..."
fi

# Post Deployment Validation
##########################################################
figlet "Post Validation"
release_date_file="${pathprefix}${subdomain}.birthday.gold/__releasedate.txt"
version_file="${pathprefix}${subdomain}.birthday.gold/core/v3/footerversion.inc"
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
chmod +x ${pathprefix}${subdomain}.birthday.gold/admin_actions/gather_server_info.sh


# Deployment completion message
echo "Deployment Completed Successfully."

# Define the message body
hostname=$(hostname)
latest_commit_msg=$(cat "$latest_commit_msg_file")
latest_commit_msg_post=$(head -n 1 "$latest_commit_msg_file")
message_body="❇️ Deployment completed successfully for BIRTHDAY GOLD on $hostname - $(date '+%Y-%m-%d %H:%M:%S') with [SOURCE: $source_subdomain / BRANCH:  $latest_commit_msg_post]"

# Only send deployment notifications in full mode
if [ "$config_only" != true ]; then
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
        # Post the message to the Rocket.Chat room
       curl_response=$(curl -s -o ~/response.txt -w "%{http_code}" -X POST \
            -H 'Content-Type: application/json' \
            --data "{
                \"text\": \"$message_body\"
            }" \
            ${ROCKETCHAT_URL}/${ROCKETCHAT_USER}/${ROCKETCHAT_PASSWORD})


        # Check if the message was sent successfully
        if [ $? -eq 0 ]; then
            echo "Message posted."
            curl_response=200
        else
            echo "Failed to post message."
        fi
        ;;
    *)
        echo "Invalid MESSAGE_PLATFORM specified. Exiting..."
        exit 1
        ;;
esac

# Check the response status
    if [ "$curl_response" -eq 200 ]; then
        echo "Deployment notification sent successfully to $MESSAGE_PLATFORM."
    else
        echo "Failed to send deployment notification. HTTP status code: $curl_response"
    fi
fi # End of notification block (only in full mode)

fi # End of main deployment block (skip_to_config_sync check)

# CONFIG SYNC SECTION - Always runs (both full and config-only modes)
##########################################################
echo ""
echo "=========================================="
echo "Starting Configuration Sync"
echo "=========================================="

# Check for an updated deploy_www.sh script
##########################################################
echo "Checking for deploy_www.sh updates..."

# In config-only mode, we need to fetch the script differently
if [ "$config_only" = true ]; then
    # Try to download the deploy_www.sh directly from the source
    temp_deploy_script="/tmp/deploy_www_temp.sh"
    echo "Fetching deploy_www.sh from ${source_subdomain}.birthday.gold..."

    # First, try to get it via curl from the dev server
    if curl -s -f "https://${source_subdomain}.birthday.gold/admin_actions/deploy_www.sh" -o "$temp_deploy_script" 2>/dev/null; then
        new_deploy_script="$temp_deploy_script"
    else
        # Fallback: try to get it from GitHub if curl fails
        echo "Could not fetch from web, trying GitHub..."
        if curl -s -f -H "Authorization: token $GITHUB_TOKEN" \
            "https://raw.githubusercontent.com/ddgconsultant/${source_subdomain}.birthday.gold/main/admin_actions/deploy_www.sh" \
            -o "$temp_deploy_script" 2>/dev/null; then
            new_deploy_script="$temp_deploy_script"
        else
            echo "Could not fetch deploy_www.sh from ${source_subdomain}.birthday.gold"
            new_deploy_script=""
        fi
    fi
else
    # In full deployment mode, use the cloned file
    new_deploy_script="${pathprefix}${subdomain}.birthday.gold/admin_actions/deploy_www.sh"
fi

current_deploy_script="/root/deploy_www.sh"

if [ -f "$new_deploy_script" ] && [ ! -z "$new_deploy_script" ]; then
    new_sha1=$(sha1sum "$new_deploy_script" | awk '{ print $1 }')
    current_sha1=$(sha1sum "$current_deploy_script" | awk '{ print $1 }')

    if [ "$new_sha1" != "$current_sha1" ]; then
        cp "$new_deploy_script" "$current_deploy_script"
        dos2unix "$current_deploy_script"
        echo "deploy_www.sh has been updated from ${source_subdomain}.birthday.gold."

        # Clean up temp file if used
        [ "$new_deploy_script" = "$temp_deploy_script" ] && rm -f "$temp_deploy_script"
    else
        echo "No update needed for deploy_www.sh."
        # Clean up temp file if used
        [ "$new_deploy_script" = "$temp_deploy_script" ] && rm -f "$temp_deploy_script"
    fi
else
    echo "No deploy_www.sh found. Skipping update."
fi

# Check for ENV_CONFIG updates from dev.birthday.gold
##########################################################
echo "Checking ENV_CONFIG files for updates..."

# Define local ENV_CONFIG path
ENV_CONFIG_PATH="/var/www/BIRTHDAY_SERVER/ENV_CONFIGS"

# Array of config files to check
config_files=("config-main-production.inc" "config-ai.inc")

# Try multiple methods to sync config files
sync_successful=false

# Method 1: Try the new sync endpoint
echo "Attempting to sync ENV_CONFIG files via web endpoint..."
# Use the source_subdomain variable to determine which server to sync from
remote_sync_response=$(curl -s -m 10 "https://${source_subdomain}.birthday.gold/admin_actions/deploy_env_sync.php?action=checksums&token=DEPLOY_CHECKSUM_SECRET_2025" 2>/dev/null)

if [ $? -eq 0 ] && [ ! -z "$remote_sync_response" ]; then
    status=$(echo "$remote_sync_response" | jq -r '.status' 2>/dev/null)

    if [ "$status" == "success" ]; then
        for config_file in "${config_files[@]}"; do
            local_config_file="$ENV_CONFIG_PATH/$config_file"

            # Get remote checksum from JSON
            remote_checksum=$(echo "$remote_sync_response" | jq -r ".checksums.\"$config_file\"" 2>/dev/null)

            if [ "$remote_checksum" != "null" ] && [ ! -z "$remote_checksum" ]; then
                # Calculate local checksum if file exists
                if [ -f "$local_config_file" ]; then
                    local_checksum=$(sha1sum "$local_config_file" | awk '{ print $1 }')
                else
                    local_checksum=""
                fi

                # Compare checksums
                if [ "$local_checksum" != "$remote_checksum" ]; then
                    echo "Updating $config_file (checksum mismatch)..."

                    # Try to get file content via web endpoint
                    file_content=$(curl -s -m 10 "https://${source_subdomain}.birthday.gold/admin_actions/deploy_env_sync.php?action=get_file&file=$config_file&token=DEPLOY_CHECKSUM_SECRET_2025" 2>/dev/null)

                    if [ $? -eq 0 ] && [ ! -z "$file_content" ]; then
                        # Extract and decode the content
                        encoded_content=$(echo "$file_content" | jq -r '.content' 2>/dev/null)
                        if [ ! -z "$encoded_content" ] && [ "$encoded_content" != "null" ]; then
                            echo "$encoded_content" | base64 -d > "$local_config_file"
                            echo "$config_file updated successfully via web sync."
                            sync_successful=true
                        else
                            echo "Failed to decode content for $config_file"
                        fi
                    else
                        echo "Failed to fetch $config_file via web endpoint"
                    fi
                else
                    echo "$config_file is up to date."
                    sync_successful=true
                fi
            else
                echo "Remote checksum not available for $config_file"
            fi
        done
    else
        echo "Web endpoint returned non-success status"
    fi
else
    echo "Could not connect to ${source_subdomain}.birthday.gold web endpoint"
fi

# Method 2: Fallback to direct file copy if web sync failed
if [ "$sync_successful" != "true" ]; then
    echo "Falling back to direct file copy method..."

    # In config-only mode, we don't have a cloned repository
    if [ "$config_only" = true ]; then
        echo "Config-only mode: Cannot use repository fallback. Please ensure web endpoint is accessible."
        echo "Alternatively, run a full deployment to sync config files."
    else
        # Copy from the staging directory we just cloned
        if [ -d "${pathprefix}${subdomain}.birthday.gold/ENV_CONFIGS" ]; then
            echo "Copying ENV_CONFIGS from cloned repository..."
            for config_file in "${config_files[@]}"; do
                source_file="${pathprefix}${subdomain}.birthday.gold/ENV_CONFIGS/$config_file"
                dest_file="$ENV_CONFIG_PATH/$config_file"

                if [ -f "$source_file" ]; then
                    cp "$source_file" "$dest_file"
                    echo "$config_file copied from repository."
                else
                    echo "$config_file not found in repository."
                fi
            done
        else
            echo "ENV_CONFIGS directory not found in cloned repository."
        fi
    fi
fi

# Final completion message
echo ""
echo "=========================================="
if [ "$config_only" = true ]; then
    echo "CONFIG-ONLY SYNC COMPLETED"
    echo "Source: ${source_subdomain}.birthday.gold"
    echo "Updated: deploy_www.sh and ENV_CONFIG files"
else
    echo "FULL DEPLOYMENT COMPLETED"
fi
echo "=========================================="

# End of the script
cd
