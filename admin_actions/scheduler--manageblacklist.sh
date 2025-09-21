#!/bin/bash

DEBUG=false

# URL of the JSON data
JSON_URL="https://metabase.birthdaygold.cloud/public/question/0315a2f2-0760-4aca-934f-d35a47f78ac0.json"
# Path to the HAProxy blocked IPs list
BLOCKED_IPS_FILE="/etc/haproxy/blocked_ips.lst"
# Temporary file to store the fetched JSON data
TEMP_JSON="/tmp/temp_haproxy_ips.json"
# Current date and time
CURRENT_DATETIME=$(date '+%Y-%m-%d %H:%M:%S')

# Debugging function
debug() {
    if $DEBUG; then
        echo "$1"
    fi
}

# Fetch the JSON data with curl following redirects
curl -s -L -o "$TEMP_JSON" "$JSON_URL"
debug "Fetched JSON data from $JSON_URL"

# Check if the file was fetched correctly
if [[ ! -s "$TEMP_JSON" ]]; then
    debug "Failed to fetch JSON data"
    exit 1
fi

# Output the JSON content for debugging
if $DEBUG; then
    debug "JSON content:"
    cat "$TEMP_JSON"
fi

# Flag to indicate if any new IP was added
new_ips_added=false

# Read and process the JSON data
jq -c '.[]' "$TEMP_JSON" | while IFS= read -r line; do
    cip=$(echo "$line" | jq -r '.cip')
    city=$(echo "$line" | jq -r '.city')
    state=$(echo "$line" | jq -r '.state')
    country_code=$(echo "$line" | jq -r '.country_code')
    hit_count=$(echo "$line" | jq -r '.hit_count')
    first_seen=$(echo "$line" | jq -r '.first_seen_formatted')
    last_seen=$(echo "$line" | jq -r '.last_seen_formatted')

    debug "Raw data: $cip, $city, $state, $country_code, $hit_count, $first_seen, $last_seen"

    debug "Processing IP: $cip, City: $city, State: $state, Country: $country_code, First seen: $first_seen, Last seen: $last_seen"

    # Check if the IP already exists in the blocked IPs file
    if ! grep -q "$cip" "$BLOCKED_IPS_FILE"; then
        # Append the new IP entry to the blocked IPs file
        if [ "$new_ips_added" = false ]; then
            echo "#" >> "$BLOCKED_IPS_FILE"
            echo "# ====================================" >> "$BLOCKED_IPS_FILE"
            echo "# ADDED: $CURRENT_DATETIME" >> "$BLOCKED_IPS_FILE"
            new_ips_added=true
        fi
        echo "# $city, $state, $country_code :: [ $hit_count ] $first_seen - $last_seen" >> "$BLOCKED_IPS_FILE"
        echo "$cip" >> "$BLOCKED_IPS_FILE"
        debug "Added new IP to blocked list: $cip"
    else
        debug "IP already exists in blocked list: $cip"
    fi
done

# Clean up
rm "$TEMP_JSON"
debug "Temporary JSON file removed"

# Reload HAProxy to apply the changes
systemctl reload haproxy
if [[ $? -ne 0 ]]; then
    debug "Failed to reload HAProxy. Check the configuration for errors."
else
    debug "HAProxy reloaded"
fi
