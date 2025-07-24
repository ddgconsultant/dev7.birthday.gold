<?php
// scheduler--process-business-submissions.php - Process submitted business recommendations
// Can be triggered via URL: /admin_actions/scheduler--process-business-submissions.php?key=YOUR_KEY

// Set up environment
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check authorization key
$auth_key = $_GET['key'] ?? '';
$expected_key = $sitesettings['scheduler_key'] ?? 'default_scheduler_key_change_me';

if ($auth_key !== $expected_key) {
    http_response_code(403);
    die("Unauthorized");
}

// Set execution limits
set_time_limit(600); // 10 minutes
ini_set('memory_limit', '256M');

// Output as plain text
header('Content-Type: text/plain; charset=utf-8');

// Log start
$start_time = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] Starting business submission processing\n";

try {
    // Get all companies with 'submitted' status
    $sql = "SELECT c.*, 
            (SELECT description FROM bg_company_attributes 
             WHERE company_id = c.company_id 
             AND name = 'submitted_by_user_id' 
             LIMIT 1) as submitted_by_user_id,
            (SELECT description FROM bg_company_attributes 
             WHERE company_id = c.company_id 
             AND name = 'submission_notes' 
             LIMIT 1) as submission_notes
            FROM bg_companies c 
            WHERE c.status = 'submitted' 
            AND c.create_dt >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY c.create_dt ASC 
            LIMIT 50";
    
    $stmt = $database->query($sql);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_submissions = count($submissions);
    echo "Found $total_submissions submitted businesses to process\n\n";
    
    if ($total_submissions === 0) {
        echo "No submissions to process. Exiting.\n";
        echo "\nSTATUS: SUCCESS - No pending submissions\n";
        exit(0);
    }
    
    $processed = 0;
    $successful = 0;
    $failed = 0;
    
    foreach ($submissions as $submission) {
        $processed++;
        $company_id = $submission['company_id'];
        $company_name = $submission['company_name'];
        $home_url = $submission['company_url'];
        $signup_url = $submission['signup_url'];
        
        echo "[$processed/$total_submissions] Processing: $company_name (ID: $company_id)\n";
        
        // Basic validation and enrichment
        $updates = [];
        $attributes = [];
        
        // 1. Validate URLs are still accessible
        echo "  - Checking URL accessibility...\n";
        $home_accessible = checkUrlAccessible($home_url);
        $signup_accessible = checkUrlAccessible($signup_url);
        
        if (!$home_accessible || !$signup_accessible) {
            echo "  - WARNING: One or more URLs not accessible\n";
            $attributes[] = [
                'name' => 'validation_status',
                'description' => 'URLs not accessible',
                'type' => 'validation'
            ];
        }
        
        // 2. Extract additional information from the website
        echo "  - Extracting website information...\n";
        
        // Try to determine category based on domain/content
        $category = determineCategory($company_name, $home_url);
        if ($category) {
            $updates['category'] = $category;
            $updates['display_category'] = $category;
            echo "  - Detected category: $category\n";
        }
        
        // 3. Check for duplicate companies by domain
        $domain = parse_url($home_url, PHP_URL_HOST);
        $domain = preg_replace('/^www\./', '', $domain);
        
        $dup_sql = "SELECT company_id, company_name FROM bg_companies 
                    WHERE email_domain = :domain 
                    AND company_id != :current_id 
                    AND status != 'submitted'
                    LIMIT 1";
        
        $dup_stmt = $database->query($dup_sql, [
            'domain' => $domain,
            'current_id' => $company_id
        ]);
        
        if ($duplicate = $dup_stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  - WARNING: Possible duplicate of {$duplicate['company_name']} (ID: {$duplicate['company_id']})\n";
            $attributes[] = [
                'name' => 'possible_duplicate_id',
                'description' => $duplicate['company_id'],
                'type' => 'validation'
            ];
        }
        
        // 4. Set initial processing status
        $updates['status'] = 'pending_review';
        $updates['company_status'] = 'pending_review';
        
        // 5. Update the company record
        if (!empty($updates)) {
            $update_parts = [];
            $update_params = ['company_id' => $company_id];
            
            foreach ($updates as $field => $value) {
                $update_parts[] = "$field = :$field";
                $update_params[$field] = $value;
            }
            
            $update_sql = "UPDATE bg_companies SET " . implode(', ', $update_parts) . 
                         ", modify_dt = NOW() WHERE company_id = :company_id";
            
            $database->query($update_sql, $update_params);
        }
        
        // 6. Store validation attributes
        foreach ($attributes as $attr) {
            $attr_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, :type, :name, :description, 'active', NOW())";
            
            $database->query($attr_sql, [
                'company_id' => $company_id,
                'type' => $attr['type'],
                'name' => $attr['name'],
                'description' => $attr['description']
            ]);
        }
        
        // 7. Log processing completion
        $proc_sql = "INSERT INTO bg_company_attributes 
                    (company_id, type, name, description, status, create_dt)
                    VALUES 
                    (:company_id, 'metadata', 'processed_timestamp', :timestamp, 'active', NOW())";
        
        $database->query($proc_sql, [
            'company_id' => $company_id,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        echo "  - Status: Moved to pending_review\n\n";
        $successful++;
        
        // Add delay between processing
        if ($processed % 5 == 0) {
            sleep(1);
        }
    }
    
    // Summary
    $elapsed_time = round(microtime(true) - $start_time, 2);
    echo "========================================\n";
    echo "Processing completed in {$elapsed_time} seconds\n";
    echo "Total processed: $processed\n";
    echo "Successful: $successful\n";
    echo "Failed: $failed\n";
    echo "========================================\n";
    
    // Determine status for Uptime Kuma
    if ($failed === 0) {
        echo "\nSTATUS: SUCCESS - All submissions processed successfully\n";
    } else {
        echo "\nSTATUS: FAILURE - Some submissions failed to process\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\nFATAL ERROR: " . $e->getMessage() . "\n";
    error_log("Business submission processor error: " . $e->getMessage());
    echo "\nSTATUS: FAILURE - Fatal error occurred\n";
    exit(1);
}

/**
 * Check if a URL is accessible
 */
function checkUrlAccessible($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; BirthdayGold/1.0)');
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($httpCode >= 200 && $httpCode < 400);
}

/**
 * Try to determine business category
 */
function determineCategory($name, $url) {
    // Simple keyword-based categorization
    $name_lower = strtolower($name);
    $domain = parse_url($url, PHP_URL_HOST);
    
    $categories = [
        'restaurant' => ['restaurant', 'pizza', 'burger', 'cafe', 'coffee', 'diner', 'grill', 'kitchen'],
        'retail' => ['store', 'shop', 'mart', 'depot', 'target', 'walmart'],
        'entertainment' => ['cinema', 'theater', 'movie', 'games', 'arcade', 'bowl'],
        'beauty' => ['salon', 'spa', 'beauty', 'nail', 'hair'],
        'fitness' => ['gym', 'fitness', 'yoga', 'pilates', 'crossfit'],
        'hotel' => ['hotel', 'inn', 'resort', 'lodge'],
        'automotive' => ['auto', 'car', 'tire', 'oil', 'mechanic']
    ];
    
    foreach ($categories as $category => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($name_lower, $keyword) !== false || strpos($domain, $keyword) !== false) {
                return $category;
            }
        }
    }
    
    return 'other';
}

exit(0);
?>