#!/bin/bash
# Add Mail Count Sync monitor to Uptime Kuma
# This should be in "System Operations" parent group alongside other BG JOBs

KUMA_API="http://april21.bday.gold:5000"
MONITOR_NAME="BG JOB--mail_count_sync"

# Check if monitor exists
echo "Checking if monitor '$MONITOR_NAME' already exists..."
RESPONSE=$(curl -s -X POST $KUMA_API/check_monitor_exists -H "Content-Type: application/json" -d "{\"name\": \"$MONITOR_NAME\"}")
EXISTS=$(echo $RESPONSE | jq -r '.exists')

if [ "$EXISTS" == "true" ]; then
    echo "Monitor '$MONITOR_NAME' already exists. Skipping."
    exit 0
fi

# Create the monitor
# Note: parent should be the "System Operations" group ID (check Uptime Kuma for correct ID)
# Using parent: 3 as default, update if System Operations has a different ID

MONITOR_DATA=$(jq -n '{
    type: "HTTP(s) - Keyword",
    name: "BG JOB--mail_count_sync",
    url: "https://dev.birthday.gold/admin_actions/scheduler--sync-mail-counts.php",
    interval: 3600,
    retryInterval: 3600,
    timeout: 600,
    maxretries: 2,
    resendInterval: 0,
    parent: 3,
    description: "Hourly sync of mail counts from march01/02/03 servers to bg_user_attributes cache",
    notificationIDList: [1],
    expiryNotification: true,
    ignoreTls: false,
    upsideDown: false,
    accepted_statuscodes: ["200-299"],
    keyword: "COMPLETED",
    invertKeyword: false,
    method: "GET"
}')

echo "Creating monitor '$MONITOR_NAME'..."
echo "$MONITOR_DATA" | jq '.'

RESULT=$(curl -s -X POST $KUMA_API/create_monitor -H "Content-Type: application/json" -d "$MONITOR_DATA")

echo ""
echo "Result:"
echo "$RESULT" | jq '.' 2>/dev/null || echo "$RESULT"

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Monitor '$MONITOR_NAME' successfully added to Uptime Kuma!"
    echo "   - URL: https://dev.birthday.gold/admin_actions/scheduler--sync-mail-counts.php"
    echo "   - Check interval: Every 1 hour (3600 seconds)"
    echo "   - Keyword: COMPLETED"
else
    echo ""
    echo "❌ Failed to add monitor. Check the error above."
    exit 1
fi
