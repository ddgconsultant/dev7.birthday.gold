<?php
// Fix celebration page branding - Update "Birthday Gold" to "Birthday.Gold" with period
$_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "<h1>Fix Celebration Page Branding</h1>\n";

// Update default celebration title
$sql = "UPDATE bg_product_features
        SET value = 'Welcome to Birthday.Gold!'
        WHERE name = 'celebration_title'
        AND plan = 'default'";

try {
    $result = $database->query($sql, []);
    echo "<p>✓ Updated default celebration_title to 'Welcome to Birthday.Gold!'</p>\n";
} catch (Exception $e) {
    echo "<p>✗ Error updating celebration_title: " . $e->getMessage() . "</p>\n";
}

// Check for any other "Birthday Gold" references in celebration messages
$sql = "SELECT id, name, value FROM bg_product_features
        WHERE name LIKE 'celebration_%'
        AND value LIKE '%Birthday Gold%'
        AND value NOT LIKE '%Birthday.Gold%'";

$messages = $database->getrows($sql, []);

if (!empty($messages)) {
    echo "<h2>Found other 'Birthday Gold' references to fix:</h2>\n";

    foreach ($messages as $message) {
        $new_value = str_replace('Birthday Gold', 'Birthday.Gold', $message['value']);

        $update_sql = "UPDATE bg_product_features
                       SET value = :new_value
                       WHERE id = :id";

        try {
            $database->query($update_sql, [
                'new_value' => $new_value,
                'id' => $message['id']
            ]);
            echo "<p>✓ Updated {$message['name']}: '{$message['value']}' → '{$new_value}'</p>\n";
        } catch (Exception $e) {
            echo "<p>✗ Error updating {$message['name']}: " . $e->getMessage() . "</p>\n";
        }
    }
} else {
    echo "<p>No other 'Birthday Gold' references found in celebration messages.</p>\n";
}

// Remove any "Premium" references
$sql = "SELECT id, name, value FROM bg_product_features
        WHERE name LIKE 'celebration_%'
        AND value LIKE '%Premium%'";

$premium_messages = $database->getrows($sql, []);

if (!empty($premium_messages)) {
    echo "<h2>Found 'Premium' references to remove:</h2>\n";

    foreach ($premium_messages as $message) {
        // Remove "Premium" and clean up the text
        $new_value = $message['value'];
        $new_value = str_replace(['Premium', 'premium'], '', $new_value);
        $new_value = str_replace(['Birthday Gold Gold', 'Birthday.Gold Gold'], 'Birthday.Gold', $new_value);
        $new_value = trim(preg_replace('/\s+/', ' ', $new_value)); // Clean up extra spaces

        $update_sql = "UPDATE bg_product_features
                       SET value = :new_value
                       WHERE id = :id";

        try {
            $database->query($update_sql, [
                'new_value' => $new_value,
                'id' => $message['id']
            ]);
            echo "<p>✓ Removed 'Premium' from {$message['name']}: '{$message['value']}' → '{$new_value}'</p>\n";
        } catch (Exception $e) {
            echo "<p>✗ Error updating {$message['name']}: " . $e->getMessage() . "</p>\n";
        }
    }
} else {
    echo "<p>No 'Premium' references found in celebration messages.</p>\n";
}

echo "<h2>Current default celebration messages:</h2>\n";
$sql = "SELECT name, value FROM bg_product_features
        WHERE plan = 'default'
        AND name LIKE 'celebration_%'
        ORDER BY name";
$current_messages = $database->getrows($sql, []);

foreach ($current_messages as $message) {
    echo "<p><strong>{$message['name']}:</strong> {$message['value']}</p>\n";
}

echo "\n<p><strong>Done!</strong></p>\n";
?>