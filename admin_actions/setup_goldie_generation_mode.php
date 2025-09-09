<?php
/**
 * Setup Ask Goldie Generation Mode Configuration
 */

include('../core/site-controller.php');

echo "<pre>";
echo "=== Setting up Ask Goldie Generation Mode ===\n\n";

if (!isset($database)) {
    die("ERROR: Database not available\n");
}

try {
    // Check if config already exists
    $check_sql = "SELECT * FROM bg_config WHERE config_type = 'ask_goldie' AND config_key = 'generation_mode'";
    $existing = $database->getrow($check_sql);
    
    if ($existing) {
        echo "Configuration already exists:\n";
        echo "  Type: " . $existing['config_type'] . "\n";
        echo "  Key: " . $existing['config_key'] . "\n";
        echo "  Value: " . $existing['config_value'] . "\n";
        echo "  Data: " . $existing['config_data'] . "\n\n";
        
        // Update to ensure it's enabled
        $update_sql = "UPDATE bg_config 
                      SET config_value = '1', 
                          config_data = 'Enable generation-specific language in Ask Goldie based on user age',
                          updated_at = NOW()
                      WHERE config_type = 'ask_goldie' AND config_key = 'generation_mode'";
        $database->query($update_sql);
        echo "✓ Configuration updated and enabled\n";
    } else {
        // Insert new config
        $insert_sql = "INSERT INTO bg_config (config_type, config_key, config_value, config_data, status, created_at) 
                      VALUES ('ask_goldie', 'generation_mode', '1', 
                              'Enable generation-specific language in Ask Goldie based on user age', 
                              '1', NOW())";
        $database->query($insert_sql);
        echo "✓ Configuration added and enabled\n";
    }
    
    // Verify it worked
    $verify = $database->getrow("SELECT * FROM bg_config WHERE config_type = 'ask_goldie' AND config_key = 'generation_mode'");
    if ($verify && $verify['config_value'] == '1') {
        echo "\n=== SUCCESS ===\n";
        echo "Ask Goldie Generation Mode is now ENABLED\n";
        echo "Ask Goldie will adapt its language based on user generation:\n";
        echo "  - Gen Z: Casual, trendy language with emojis\n";
        echo "  - Millennials: Friendly, relatable tone\n";
        echo "  - Gen X: Straightforward, practical\n";
        echo "  - Baby Boomers: Professional, respectful\n";
        echo "  - Silent Generation: Formal, courteous\n";
    } else {
        echo "\nWARNING: Configuration may not have been set correctly\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>