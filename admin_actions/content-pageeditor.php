<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


$errormessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page = $_POST['page'];    // The page (PHP file) to update
    $block = $_POST['block'];  // Block identifier (e.g., 'body-1')
    $newContent = $_POST['editedContent']; // New content to insert

    // Read the contents of the PHP file
    $fileContent = file_get_contents($page);

    // Regular expression to match the block of PHP code between the markers
    $pattern = '/### ADMIN PAGE EDITOR: START-' . preg_quote($block) . ' ###(.*?)### ADMIN PAGE EDITOR: END-' . preg_quote($block) . ' ###/s';

    // Extract the old content
    if (preg_match($pattern, $fileContent, $matches)) {
        $oldContent = $matches[1];
    } else {
        $errormessage = "Block not found.";
        exit();
    }

    // Ensure the "echo" statement is on the next line after the marker
    $newFormattedContent = "\n" . $newContent;

    // Replace the old content with the new content in the PHP file
    $newFileContent = preg_replace($pattern, '### ADMIN PAGE EDITOR: START-' . $block . ' ###' . $newFormattedContent . '### ADMIN PAGE EDITOR: END-' . $block . ' ###', $fileContent);

    // Write the new content back to the file
    if (file_put_contents($page, $newFileContent)) {
        $errormessage = "Page updated successfully.";

        // Generate a basic diff between old and new content
      #  $diff = generateSimpleDiff($oldContent, $newContent);

        // Prepare details for the notification
        $editorDetails = $current_user_data['username']??'Unknown user';  // Adjust according to how users are managed
        $timestamp = date('Y-m-d H:i:s');
        $personalMessage = 'Page **' . basename($page) . '** has been edited. Block: **' . $block . '** by **' . $editorDetails . '** at **' . $timestamp . '**.';

        // Send the message to RocketChat (BG-Technical channel)
        $channel = '#BG-Technical';  // Channel to send the message to
        $channel = '@Richard';  // Direct message to a specific user
        $system->postToRocketChat($personalMessage, $channel);

    } else {
        $errormessage = "Failed to update the page.";
    }

    // Redirect back to the page to avoid form resubmission issues
    $transferpage['message'] = $errormessage;   
    $transferpage['url'] = $_SERVER['HTTP_REFERER'];
    $system->endpostpage($transferpage);

    exit();
}

// Function to generate a simple line-by-line diff
function generateSimpleDiff($old, $new) {
    $oldLines = explode("\n", $old);
    $newLines = explode("\n", $new);
    
    $diff = '';
    
    foreach ($newLines as $i => $line) {
        if (isset($oldLines[$i]) && $oldLines[$i] !== $line) {
            $diff .= "- " . trim($oldLines[$i]) . "\n+ " . trim($line) . "\n";
        } elseif (!isset($oldLines[$i])) {
            $diff .= "+ " . trim($line) . "\n";
        }
    }
    
    return $diff;
}
?>
