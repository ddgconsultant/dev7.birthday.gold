<?php
// Include your site controller and any required files
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
// Cloudron API token and URL
$api_url = "https://my.birthdaygold.cloud/api/v1/apps";
$api_token = "3367ea37dc7590e21bdd20ea5b7991b2000418f01f893a04c8ccbeaa656acdd4";
#$error_msg='';
// Define your Rocket.Chat user
$rocketchatuser = "@Richard";
$rocketchatuser = "#BG-Technical";
$day=date('l');

if ($day !== 'Sunday') {    
    echo "nothing to run today: ".$day; 
    exit;
}

// Random message array - for varied weekly notifications
$intro_messages = [
    "Hi {username}, during my {day} system check, I found that {count} Cloudron applications have updates available:",
    "Hi {username}, my {day} system audit shows {count} Cloudron apps that need updating:",
    "Hi {username}, I've completed the {day} system review and discovered {count} Cloudron app updates:",
    "Hi {username}, just finishing up my {day} checks - {count} Cloudron apps have pending updates:",
    "Hi {username}, hope you're having a good {day}! {count} Cloudron apps have new versions available:",
    "Hi {username}, my {day} maintenance scan shows {count} Cloudron apps with pending updates:",
    "Hi {username}, as part of the scheduled {day} check, I found {count} Cloudron apps that need updating:",
    "Hi {username}, the {day} system scan indicates {count} Cloudron applications have updates ready to install:",
    "Hi {username}, {day} system check complete - {count} Cloudron apps have updates waiting:",
    "Hi {username}, I've identified {count} Cloudron applications with available updates during today's {day} check:"
];

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
// Function to compare versions and return the appropriate icon
function getVersionIcon($latest, $current) {
    // Split the version into major, minor, and patch numbers
    $latest_parts = explode('.', $latest);
    $current_parts = explode('.', $current);

    // If either version doesn't have 3 parts, return green by default
    if (count($latest_parts) !== 3 || count($current_parts) !== 3) {
        return ':attention-green:'; // Green for safe fallback
    }

    // Compare major versions first
    if ($latest_parts[0] > $current_parts[0]) {
        return ':attention-red:'; // Red icon for major version difference
    }

    // If major versions are the same, compare minor versions
    if ($latest_parts[1] > $current_parts[1]) {
        return ':attention-yellow:'; // Yellow icon for minor version difference
    }

    // If major and minor versions are the same, compare patch versions
    if ($latest_parts[2] > $current_parts[2]) {
        return ':attention-green:'; // Green icon for patch version difference
    }

    // Default to green if everything is up-to-date
    return ':attention-green:';
}



// Step 1: Fetch the list of apps from Cloudron
$response=$system->curlRequest(
    $api_url,
    [
    "Authorization: Bearer $api_token",
    "Content-Type: application/json"
] , [], 'GET' );

if (isset($error_msg)) {
    // Log or send the error message to Rocket.Chat
    $system->postToRocketChat("Error fetching app data from Cloudron API: $error_msg", $rocketchatuser);
    exit;
}

// Parse the response
$data = $response['decoded'];
foreach ($data['apps'] as &$app) {
    if (isset($app['manifest'])) {
        unset($app['manifest']['description'], $app['manifest']['changelog'], $app['manifest']['postInstallMessage']);
    }
}
unset($app); // Break the reference to the last element


// Step 2: Ensure $data['apps'] exists and is an array
if (is_array($data) && isset($data['apps']) && is_array($data['apps']) && isset($data['apps'][0]['id'])) {
    $appId = $data['apps'][0]['id']; // First app's id
    $appTitle = $data['apps'][0]['manifest']['title'] ?? 'Unknown App';

    // Step 3: Check for updates via POST request to /apps/$APPID/check_for_updates (ONLY ONCE)
    $update_check_url = "https://my.birthdaygold.cloud/api/v1/apps/$appId/check_for_updates";

    $update_response=$system->curlRequest(
        $update_check_url,
        [
        "Authorization: Bearer $api_token",
        "Content-Type: application/json"
    ] );

    if ($update_response) {
        $update_data = $update_response['decoded'];
        foreach ($update_data['update'] as $key => $update) {
            if (isset($update['manifest'])) {
                unset($update['manifest']['description'], $update['manifest']['changelog'], $update['manifest']['postInstallMessage']);
            }
        }

        // Step 4: Merge the two arrays (updates + apps data)
        if (isset($update_data['update'])) {
            $apps_to_update = [];
            foreach ($update_data['update'] as $key => $update) {
                // Get the array key as $key, not the 'id'
                $latest_version = $update['manifest']['version'] ?? 'Unknown'; // Latest version from update_data
                
                // Find the matching app in $data['apps'] by key
                $current_version = 'Unknown';
                foreach ($data['apps'] as $app_data) {
                    if ($app_data['id'] === $key) {
                        $current_version = $app_data['manifest']['version'] ?? 'Unknown'; // Current version from data
                        break;  // Stop searching once the app is found
                    }
                }

                // Add each app's info to the update list
                $apps_to_update[] = [
                    'title' => $update['manifest']['title'] ?? 'Unknown',
                    'latest_version' => $latest_version,
                    'current_version' => $current_version,  // Use the found current version from $data['apps']
                    'documentationUrl' => $update['manifest']['documentationUrl'] ?? '#',
                    'forumUrl' => $update['manifest']['forumUrl'] ?? '#',  // Grab the forum URL from manifest
                ];
            }

            // Step 5: Prepare the Rocket.Chat message
            if (!empty($apps_to_update)) {
                // Extract username from the rocketchat user
                if (strpos($rocketchatuser, '@') === 0) {
                    $username = $rocketchatuser; // Remove @ if it's a direct message
                } elseif (strpos($rocketchatuser, '#') === 0) {
                    $username = "@Jeanine"; // For channels, use "Jeanine" instead of the channel name
                }
                
                // Pick a random intro message
                $random_index = array_rand($intro_messages);
                $intro = str_replace(
                    ['{count}', '{username}', '{day}'], 
                    [count($apps_to_update), $username, $day], 
                    $intro_messages[$random_index]
                );
                
                $message = $intro . "\nhttps://my.birthdaygold.cloud/#/apps\n\n";
                
                foreach ($apps_to_update as $app) {
                    $icon = getVersionIcon($app['latest_version'], $app['current_version']);
    
                    $message .= "$icon *" . $app['title'] . "*\n";  // App name bold
                    $message .= "* Latest Version: * " . $app['latest_version'] . "\n";  // Latest version from the update check
                    $message .= "* Current Version: * " . $app['current_version'] . "\n";  // Current version from $data['apps']
                    $message .= "* Update Detail: * [" . $app['forumUrl'] . "](" . $app['forumUrl'] . "?sort=recently_created)\n\n"; // Format the URL as a clickable link
                }

                // Define recipients for the message
                $recipients = [$rocketchatuser];
                if ($rocketchatuser !== '@Richard') {
                    $recipients[] = '@Richard'; // Add Richard as recipient if not already the primary
                }
                
                // Send the message to all recipients
                foreach ($recipients as $recipient) {
                    // Get username for personalization
                    $recipient_username = $recipient;
                    if (strpos($recipient, '@') === 0) {
                        $recipient_username = substr($recipient, 1);
                    } elseif (strpos($recipient, '#') === 0) {
                        $recipient_username = "team";
                    }
                    
                    // Personalize message for each recipient
                    $personalized_message = str_replace(
                        ['{username}', '{day}'],
                        [$recipient_username, $day],
                        $message
                    );
                    
                    $system->postToRocketChat($personalized_message, $recipient);
                }
            } else {
                $system->postToRocketChat("All apps are up to date.", $rocketchatuser);
            }
        } else {
            $system->postToRocketChat("No updates found for app: $appTitle", $rocketchatuser);
        }
    } else {
        $system->postToRocketChat("Error checking updates for app: $appTitle", $rocketchatuser);
    }
} else {
    // Handle the case where the API response does not contain apps or the app ID is missing
    $system->postToRocketChat("Error: No apps found in the response from Cloudron API.", $rocketchatuser);
}