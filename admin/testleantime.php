<?php 

/* $apiUrl = "https://leantime.birthdaygold.cloud/api/jsonrpc";
$authToken = "lt_71ThrZozgs7IJh2AKJJhtfdJKbwpPFrr_P8SgPULOKn985wnM3ZKfj0l8U8kQmW1c";   // test2 key

$addClasses[] = 'leantime';
$classparams1['leantime']=$apiUrl  ;
$classparams2['leantime']=$authToken ; */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


// Include and instantiate the class

include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.leantime.php');
$apiUrl = "https://leantime.birthdaygold.cloud";
$apiKey = "lt_71ThrZozgs7IJh2AKJJhtfdJKbwpPFrr_P8SgPULOKn985wnM3ZKfj0l8U8kQmW1c";   // test2 key
$leantime = new Leantime($apiUrl, $apiKey);

$allUsers = $leantime->getAllUsers(false);

echo "<pre>";
print_r($allUsers);
echo "</pre>";
exit;

// Step 1: Fetch the project ID for "BG Technical"
$projectName = "BG Technical";
$projectId = $leantime->getProjectIdByName($projectName);

if (!$projectId) {
    echo "Project '$projectName' not found.";
    exit;
}

echo "Project ID for '$projectName': $projectId";

// Step 2: Fetch the user ID for "richard@birthday.gold"
$userEmail = "richard@birthday.gold";
$userId = $leantime->getUserIdByEmail($userEmail);

if (!$userId) {
    echo "User with email '$userEmail' not found.";
    exit;
}

echo "User ID for '$userEmail': $userId";

// Step 3: Create a ticket for the project and assign it to Richard
$newTicket = $leantime->createTicket(
    $projectId, // Use the Project ID
    "New Feature Request", // Ticket title
    "Request to implement a new feature.", // Ticket description
    "open", // Status
    "normal", // Priority
    $userId // Assigned to Richard (User ID)
);

echo "<pre>";
print_r($newTicket);
echo "</pre>";