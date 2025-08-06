<?php
/**
 * Initialize AIRTOP Social Media Grabber in ABO system
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if scheduler key is provided
$scheduler_key = $_GET['key'] ?? '';
if (empty($scheduler_key) || $scheduler_key !== 'DDG!2345scheduling') {
    die('Unauthorized');
}

echo "<h2>Initializing AIRTOP Social Media Grabber</h2>";

try {
    $database->beginTransaction();
    
    // Check if already exists
    $check_sql = "SELECT config_id FROM bg_config 
                  WHERE config_type = 'automation_processor' 
                  AND config_key = 'abo_grabsocialmedia_airtop'";
    $check_stmt = $database->query($check_sql);
    
    if ($check_stmt->rowCount() > 0) {
        echo "<p style='color: orange;'>AIRTOP Social Media Grabber already configured.</p>";
    } else {
        // Insert the configuration
        $config_data = [
            'description' => 'Enhanced social media extraction using AIRTOP browser automation',
            'scheduler' => 'abo_grabsocialmedia_airtop.php',
            'frequency' => '*/30 * * * *', // Every 30 minutes
            'order' => 4.5, // Run after regular social media grabber
            'enabled' => true,
            'escalation' => true,
            'requires' => ['abo_grabsocialmedia'],
            'targets' => [
                'platforms' => ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'tiktok'],
                'min_platforms' => 3
            ]
        ];
        
        $insert_sql = "INSERT INTO bg_config 
                      (config_type, config_key, config_value, config_data, create_dt, status) 
                      VALUES 
                      ('automation_processor', 'abo_grabsocialmedia_airtop', 
                       'Enhanced Social Media Extraction', :config_data, NOW(), 'active')";
        
        $database->query($insert_sql, [
            'config_data' => json_encode($config_data)
        ]);
        
        echo "<p style='color: green;'>✓ AIRTOP Social Media Grabber configured successfully!</p>";
    }
    
    // Add escalation rule
    $escalation_check = "SELECT config_id FROM bg_config 
                        WHERE config_type = 'abo_escalation' 
                        AND config_key = 'social_media_incomplete'";
    $esc_stmt = $database->query($escalation_check);
    
    if ($esc_stmt->rowCount() == 0) {
        $escalation_data = [
            'trigger' => 'social_media_platforms_found < 3',
            'action' => 'run_abo_grabsocialmedia_airtop',
            'description' => 'Escalate to AIRTOP when fewer than 3 social media platforms found'
        ];
        
        $esc_sql = "INSERT INTO bg_config 
                   (config_type, config_key, config_value, config_data, create_dt, status) 
                   VALUES 
                   ('abo_escalation', 'social_media_incomplete', 
                    'Incomplete Social Media Escalation', :config_data, NOW(), 'active')";
        
        $database->query($esc_sql, [
            'config_data' => json_encode($escalation_data)
        ]);
        
        echo "<p style='color: green;'>✓ Escalation rule configured!</p>";
    }
    
    $database->commit();
    
    echo "<h3>Configuration Complete</h3>";
    echo "<p>The AIRTOP Social Media Grabber will:</p>";
    echo "<ul>";
    echo "<li>Run every 30 minutes</li>";
    echo "<li>Process companies that have incomplete social media data</li>";
    echo "<li>Use browser automation to find hidden or JavaScript-rendered social links</li>";
    echo "<li>Target 6 major platforms: Facebook, Twitter/X, Instagram, LinkedIn, YouTube, TikTok</li>";
    echo "<li>Escalate automatically when regular processor finds < 3 platforms</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    $database->rollback();
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='/admin/'>Back to Admin</a></p>";
?>