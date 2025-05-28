<?php
// Cloudron API token and URL
$api_url = "https://my.birthdaygold.cloud/api/v1";
$api_token = "c38285f6a1b1702c67297f3647509a985ab5d19e6158da00cacb931bffffd1df"; #"3367ea37dc7590e21bdd20ea5b7991b2000418f01f893a04c8ccbeaa656acdd4";

/**
 * Function to make API requests
 */
function apiRequest($url, $method, $token, $data = null) {
    $curl = curl_init();
    $headers = [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ];

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ];

    if ($data) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }

    curl_setopt_array($curl, $options);
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);

    if ($error) {
        throw new Exception("API Request Error: $error");
    }

    return json_decode($response, true);
}

/**
 * Fetch the list of apps
 */
function fetchApps($api_url, $api_token) {
    $url = "$api_url/apps";
    return apiRequest($url, "GET", $api_token);
}

/**
 * Fetch details of a specific app
 */
function fetchAppDetails($appId, $api_url, $api_token) {
    $url = "$api_url/apps/$appId";
    return apiRequest($url, "GET", $api_token);
}

/**
 * Create a persisted labeled backup
 */
function createBackup($appId, $label, $api_url, $api_token) {
    $url = "$api_url/apps/$appId/backups"; // Constructed endpoint
    echo "Constructed API URL for backup: $url<br>"; // Debugging
    $data = ['label' => $label, 'persist' => true];
    return apiRequest($url, "POST", $api_token, $data);
}

try {
    // Fetch apps
    $apps = fetchApps($api_url, $api_token);

    if (empty($apps['apps'])) {
        echo "No apps found in the Cloudron instance.<br>";
        print_r($apps); // Debugging: Print full API response
        exit;
    }

    // Get the first app
    $firstApp = $apps['apps'][0];
    $appId = $firstApp['id'] ?? null;
    $appLabel = $firstApp['label'] ?? "Unknown App";

    if (!$appId) {
        echo "Invalid app data: ";
        print_r($firstApp); // Debugging: Print full app details
        exit;
    }

    // Fetch app details for additional debugging
    $appDetails = fetchAppDetails($appId, $api_url, $api_token);
    echo "Fetched details for app ID: $appId<br>";
    print_r($appDetails);

    // Check if backups are enabled
    if (empty($firstApp['enableBackup']) || $firstApp['enableBackup'] != 1) {
        echo "Backups are not enabled for the app '$appLabel' (ID: $appId).<br>";
        exit;
    }

    echo "Attempting to create backup for app ID: $appId ($appLabel)<br>";

    // Create a backup
    $backupLabel = "{$appLabel}_Backup_" . date('Ymd');
    $backupResult = createBackup($appId, $backupLabel, $api_url, $api_token);

    if (isset($backupResult['id'])) {
        echo "Backup created successfully for app '$appLabel' with label: $backupLabel<br>";
        echo "Backup ID: " . $backupResult['id'];
    } else {
        echo "Failed to create backup for app '$appLabel'. Error: ";
        print_r($backupResult); // Debugging: Print full API response
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
