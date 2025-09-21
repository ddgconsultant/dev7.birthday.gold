<?php
// Include the site-controller to access $sitesettings
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
exit;

// Access root credentials from $sitesettings['root_credentials']
$root_credentials = [];
foreach ($sitesettings['root_credentials'] as $key => $value) {
    // Parse out the root credentials based on the format
    if (preg_match('/^(\d+)\.(username|password)$/', $key, $matches)) {
        $cred_id = $matches[1];
        $type = $matches[2];
        $root_credentials[$cred_id][$type] = $value;
    }
}

// List of servers and corresponding rootcred_id

$listofservers = [
    ['ip' => '86.38.218.59', 'hostname' => 'july02.bday.gold', 'rootcred_id' => 1],

    ['ip' => '195.35.14.143', 'hostname' => 'april21.bday.gold', 'rootcred_id' => 1],
    ['ip' => '45.90.220.66', 'hostname' => 'december20.bday.gold', 'rootcred_id' => 1],
    ['ip' => '86.38.218.59', 'hostname' => 'july02.bday.gold', 'rootcred_id' => 1],
    ['ip' => '82.180.131.216', 'hostname' => 'july03.bday.gold', 'rootcred_id' => 2],
    ['ip' => '178.16.140.230', 'hostname' => 'july04.bday.gold', 'rootcred_id' => 2],
    ['ip' => '195.35.11.8', 'hostname' => 'june01.bday.gold', 'rootcred_id' => 1],
    ['ip' => '178.16.140.200', 'hostname' => 'june12.bday.gold', 'rootcred_id' => 1],
    ['ip' => '77.37.74.85', 'hostname' => 'june14.bday.gold', 'rootcred_id' => 1],
    ['ip' => '82.197.95.68', 'hostname' => 'june27.bday.gold', 'rootcred_id' => 1],

    ['ip' => '178.16.140.199', 'hostname' => 'march01.bday.gold', 'rootcred_id' => 3],
    ['ip' => '217.196.51.179', 'hostname' => 'march02.bday.gold', 'rootcred_id' => 3],
    ['ip' => '77.243.85.216', 'hostname' => 'march03.bday.gold', 'rootcred_id' => 3],

    ['ip' => '45.90.220.10', 'hostname' => 'ns1.thedatadesigngroup.com', 'rootcred_id' => 4],
    ['ip' => '77.243.85.250', 'hostname' => 'ns2.thedatadesigngroup.com', 'rootcred_id' => 4],
    ['ip' => '167.88.38.127', 'hostname' => 'ns3.thedatadesigngroup.com', 'rootcred_id' => 4],

];


// The user and key you want to add
// Rocket chat user to be notified
// Delete user indicator
$user = '[USER]';  // Replace with actual username
$key = '[SSH KEY]';  // Replace with actual public key
$rocketchatuser = '@jeaninerenee';
$delete_user = false;

// Log file path
$log_file = 'createuser_' . $user . '_logfile.log';


// To keep track of successful host creations
$created_hosts = [];
$failed_hosts = [];

// Function to log output
function logOutput($message, $log_file, $type = 'text')
{
    switch ($type) {
        case 'code':
            echo  '<pre>' . $message . '</pre>';
            break;
        case 'text':
            echo  '<hr><b>' . $message . '</b><br>';
            break;
    }
    file_put_contents($log_file, date('r') . "\n" . $message . PHP_EOL, FILE_APPEND);
}


// Loop through each server
foreach ($listofservers as $server) {
    $ip = $server['ip'];
    $cred = $root_credentials[$server['rootcred_id']];

    $command = "
if id '$user' >/dev/null 2>&1; then
userdel -r '$user' > /dev/null 2>&1;
fi
";
    #   exec($command, $output, $return_var);

    $connection = ssh2_connect($ip, 22);
    if (!$connection) {
        logOutput("Failed to connect to $ip", $log_file);
        continue;
    }

    if (!ssh2_auth_password($connection, $cred['username'], $cred['password'])) {
        logOutput("Authentication failed for $ip", $log_file);
        continue;
    }



    if ($delete_user) {
        $commands = [
            // Check if the user exists and delete them if they do
            "if id '$user' >/dev/null 2>&1; then userdel '$user' && echo 'User $user deleted'; fi",

            // Remove authorized keys
            "if [ -f /home/$user/.ssh/authorized_keys ]; then rm /home/$user/.ssh/authorized_keys && echo 'Authorized_keys file deleted successfully'; else echo 'Authorized_keys file does not exist to delete'; fi"
        ];
    }
    
    if (!$delete_user) {
        $commands = [
            // Check if the user exists and delete them if they do
            "if id '$user' >/dev/null 2>&1; then userdel -r '$user' && echo 'User $user deleted'; fi",
        
            // Add the user and check if successful
            "useradd -m -s /bin/bash '$user' && echo 'User $user created' || { echo 'Failed to create user $user'; exit 1; }",
            
            // Check if the home directory exists and setup SSH keys, else report an error
            "if [ -d /home/$user ]; then mkdir -p /home/$user/.ssh && echo '$key' > /home/$user/.ssh/authorized_keys && chmod 700 /home/$user/.ssh && chmod 600 /home/$user/.ssh/authorized_keys && chown -R $user:$user /home/$user/.ssh && echo 'SSH directory and authorized_keys file created' || { echo 'Failed to set up SSH directory and key'; exit 1; } else echo 'Home directory for $user was not created properly'; exit 1; fi",
            
            // Verify if the SSH key was added successfully
            "if grep -q '$key' /home/$user/.ssh/authorized_keys; then echo 'SSH key added successfully'; else echo 'Failed to add SSH key'; exit 1; fi",
            
            // Add sudo privileges for the user
            "echo '$user ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers && echo 'Sudo privileges added successfully'"
        ];
    }


    foreach ($commands as $command) {
        logOutput("Executing command on $ip: $command", $log_file);

        // Execute the command and capture both stdout and stderr
        $stream = ssh2_exec($connection, $command);
        $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR); // Get stderr

        if (!$stream || !$errorStream) {
            logOutput("Failed to execute command on $ip: $command", $log_file);
            continue;
        }

        // Ensure both stdout and stderr streams are blocking
        stream_set_blocking($stream, true);
        stream_set_blocking($errorStream, true);

        // Capture the output of stdout
        $output = stream_get_contents($stream);
        fclose($stream);

        // Capture the output of stderr
        $errorOutput = stream_get_contents($errorStream);
        fclose($errorStream);

        // Log the results

        if (!empty($errorOutput)) {
            logOutput("Error output from $ip (stderr): $errorOutput", $log_file, 'code');
            $commandsuccess = false;
        } else {
            logOutput("Output from $ip (stdout): $output", $log_file, 'code');
            #  $created_hosts[] = $server['hostname'].' ('.$ip.')';
            $commandsuccess = true;
        }
    }

    if ($commandsuccess) {
        $created_hosts[] = $server['hostname'] . ' (' . $ip . ')';
    } else {
        $failed_hosts[] = $server['hostname'] . ' (' . $ip . ')';
    }

    logOutput(" >> Processed Server: $ip", $log_file);
   
}

// If any hosts were created, send a Rocket.Chat message
$servercount_source = count($listofservers ?? []);
$servercount_target = count($created_hosts ?? []);
$servercount_failed = count($failed_hosts ?? []);

$message = "✅ User '$user' was successfully created with your supplied Public Key or deleted as specified on the following servers ($servercount_target/$servercount_source):\n" .
    implode("\n", $created_hosts) . "\n\n\n👉 Please test your access.";

if (empty($created_hosts)) {
    $message = "⚠️ No new user ('$user') was created. Either the user already existed or there was an error.";
}

if (!empty($failed_hosts)) {
    $message .= "\n\n❌ Failed to create user ('$user') on the following servers ($servercount_failed):\n" . implode("\n", $failed_hosts);
}

echo $system->postToRocketChat($message, $rocketchatuser);
