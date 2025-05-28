<?php

// Get the page from the URI
$page = isset($_GET['page']) ? $_GET['page'] : 'default';

// Set the API key and the base Uptime Kuma URL
$apiKey = "uk1_-ab19Zw9iduGFiMPTDGUeNCDFv0EJk4L6aCcLkA5";
$baseKumaUrl = "https://uptime.birthdaygold.cloud";


// Map pages to specific API endpoints or monitor IDs
$monitorEndpoints = [
    'infrastructuresystems' => "monitororfeed-infrastructuresystems", // Assuming 1 is the ID for infrastructure systems
    'frontendsystems' => "monitororfeed-frontendsystems",       // Assuming 2 is the ID for customer support systems
    'vendors' => "monitororfeed-vendors"    ,
    'default' => "production"               // Default endpoint if page is not specified
];
// Ensure the $page variable exists in the $monitorEndpoints array
$dashboard = isset($monitorEndpoints[$page]) ? $monitorEndpoints[$page] : $monitorEndpoints['default'];

// Determine which URL to use based on the page parameter
$url = $baseKumaUrl . '/api/status-page/heartbeat/'. $dashboard;

// Initialize cURL session

$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $apiKey));
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);


// Execute cURL session and get the result
$response = curl_exec($ch);


// Check if cURL executed successfully
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    $data = json_decode($response, true);
    

    if (isset($data['heartbeatList']) && is_array($data['heartbeatList'])) {
        // Check for a specific key or loop through all keys
        foreach ($data['heartbeatList'] as $key => $heartbeats) {
     
            $lastEntry = end($heartbeats); // Get the last entry of the heartbeat array
  
            // Check if the last entry is not empty and has necessary data
            if ($lastEntry && isset($lastEntry['status'], $lastEntry['time'])) {
                $status = $lastEntry['status'];
                $time = $lastEntry['time'];

                // Determine if the system is up or down
                $systemStatus = $status == 1 ? 'up' : 'down';

                // Print details for the current key
            #    echo "Key $key: ";
                switch ($status) {
                    case 1:
                        echo "All Systems Operational"; exit;
                        break;

                    case 0:
                        echo "System: $systemStatus (last checked at $time)";  exit;
                        break;

                    default:
                        echo "No heartbeat data available.";  exit;
                        break;
                }
                echo "\n"; // New line for better readability in output
            } else {
                echo "Key $key: No heartbeat data available.\n";  
            }
        }
    } else {
        echo "Invalid or missing heartbeat data.";
    }
}

// Close cURL session
curl_close($ch);