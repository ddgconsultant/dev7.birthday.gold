<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting test...\n";

// Try to include site-controller
$GLOBALS['nooutput'] = true;
$addClasses[] = 'mail';

try {
    $_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
    include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
    echo "Site controller loaded\n";
    
    // Check if mail object exists
    if (isset($mail)) {
        echo "Mail object exists: " . get_class($mail) . "\n";
        
        // Check methods
        $methods = ['markMessageRead', 'markMessageUnread', 'deleteMessage'];
        foreach ($methods as $method) {
            echo "Method $method exists: " . (method_exists($mail, $method) ? 'YES' : 'NO') . "\n";
        }
        
        // Try to call a method
        echo "Calling markMessageUnread...\n";
        $result = $mail->markMessageUnread(8536, 20, 'march01:1072');
        echo "Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
        
    } else {
        echo "Mail object NOT set\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

// Check for errors
$error = error_get_last();
if ($error) {
    echo "Last error: " . print_r($error, true) . "\n";
}