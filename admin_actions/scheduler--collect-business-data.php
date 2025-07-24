<?php
// scheduler--collect-business-data.php - Collect additional data for ONE approved business
// Can be triggered via URL: /admin_actions/scheduler--collect-business-data.php?key=YOUR_KEY

// Set up environment
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check authorization key
$auth_key = $_GET['key'] ?? '';
$expected_key = $sitesettings['scheduler_key'] ?? 'default_scheduler_key_change_me';

if ($auth_key !== $expected_key) {
    http_response_code(403);
    die("Unauthorized");
}

// Set execution limits for one business (5-10 minutes expected)
set_time_limit(600); // 10 minutes max
ini_set('memory_limit', '256M');

// Output as plain text
header('Content-Type: text/plain; charset=utf-8');

// Log start
$start_time = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] Starting business data collection\n";

try {
    // Get ONE company with 'approved_pending_data' status
    $sql = "SELECT c.*, 
            (SELECT description FROM bg_company_attributes 
             WHERE company_id = c.company_id 
             AND name = 'submitted_by_user_id' 
             LIMIT 1) as submitted_by_user_id
            FROM bg_companies c 
            WHERE c.status = 'approved_pending_data' 
            ORDER BY c.create_dt ASC 
            LIMIT 1";
    
    $stmt = $database->query($sql);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        echo "No businesses pending data collection.\n";
        echo "\nSTATUS: SUCCESS - No pending data collection\n";
        exit(0);
    }
    
    $company_id = $company['company_id'];
    $company_name = $company['company_name'];
    $home_url = $company['company_url'];
    $signup_url = $company['signup_url'];
    
    echo "Collecting comprehensive data for: $company_name (ID: $company_id)\n";
    echo "Home URL: $home_url\n";
    echo "Signup URL: $signup_url\n";
    echo str_repeat('-', 60) . "\n\n";
    
    $collected_data = [];
    $errors = [];
    
    // 1. Verify URLs are still valid
    echo "Step 1: Verifying URLs...\n";
    $home_status = getUrlStatus($home_url);
    $signup_status = getUrlStatus($signup_url);
    
    echo "  - Home URL status: $home_status\n";
    echo "  - Signup URL status: $signup_status\n";
    
    if ($home_status !== 200) {
        $errors[] = "Home URL returned status: $home_status";
    }
    if ($signup_status !== 200) {
        $errors[] = "Signup URL returned status: $signup_status";
    }
    
    // 2. Extract meta information from home page
    echo "\nStep 2: Extracting website metadata...\n";
    $meta_data = extractMetadata($home_url);
    if ($meta_data) {
        if (!empty($meta_data['description'])) {
            $collected_data['meta_description'] = $meta_data['description'];
            echo "  - Found description: " . substr($meta_data['description'], 0, 80) . "...\n";
        }
        if (!empty($meta_data['keywords'])) {
            $collected_data['meta_keywords'] = $meta_data['keywords'];
            echo "  - Found keywords: " . substr($meta_data['keywords'], 0, 80) . "...\n";
        }
    } else {
        echo "  - No metadata found\n";
    }
    
    // 3. Look for social media links
    echo "\nStep 3: Searching for social media links...\n";
    $social_links = findSocialMediaLinks($home_url);
    if ($social_links) {
        foreach ($social_links as $platform => $url) {
            $collected_data["social_$platform"] = $url;
            echo "  - Found $platform: $url\n";
        }
    } else {
        echo "  - No social media links found\n";
    }
    
    // 4. Try to determine business type/category more accurately
    echo "\nStep 4: Analyzing business category...\n";
    $enhanced_category = analyzeBusinessCategory($company_name, $home_url, $meta_data);
    if ($enhanced_category && $enhanced_category !== 'other') {
        $collected_data['enhanced_category'] = $enhanced_category;
        echo "  - Determined category: $enhanced_category\n";
    } else {
        echo "  - Category remains: " . ($company['category'] ?? 'unspecified') . "\n";
    }
    
    // 5. Store collected data
    echo "\nStep 5: Storing collected data...\n";
    
    // Begin transaction
    $database->beginTransaction();
    
    try {
        // Store each piece of collected data as attributes
        foreach ($collected_data as $key => $value) {
            $attr_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'collected_data', :name, :description, 'active', NOW())
                        ON DUPLICATE KEY UPDATE
                        description = VALUES(description),
                        modify_dt = NOW()";
            
            $database->query($attr_sql, [
                'company_id' => $company_id,
                'name' => $key,
                'description' => substr($value, 0, 1000) // Limit to field size
            ]);
        }
        
        // Store any errors encountered
        if (!empty($errors)) {
            $error_sql = "INSERT INTO bg_company_attributes 
                         (company_id, type, name, description, status, create_dt)
                         VALUES 
                         (:company_id, 'collection_error', 'errors', :errors, 'active', NOW())";
            
            $database->query($error_sql, [
                'company_id' => $company_id,
                'errors' => implode('; ', $errors)
            ]);
        }
        
        // Update company with collected data
        $update_fields = [];
        
        // Social media fields
        if (isset($collected_data['social_facebook'])) {
            $update_fields['facebook'] = $collected_data['social_facebook'];
        }
        if (isset($collected_data['social_twitter'])) {
            $update_fields['twitter'] = $collected_data['social_twitter'];
        }
        if (isset($collected_data['social_instagram'])) {
            $update_fields['instagram'] = $collected_data['social_instagram'];
        }
        if (isset($collected_data['social_tiktok'])) {
            $update_fields['tiktok'] = $collected_data['social_tiktok'];
        }
        
        // Update category if we found a better one
        if (isset($collected_data['enhanced_category'])) {
            $update_fields['category'] = $collected_data['enhanced_category'];
            $update_fields['display_category'] = $collected_data['enhanced_category'];
        }
        
        // Add description if we found one
        if (isset($collected_data['meta_description'])) {
            $update_fields['description'] = $collected_data['meta_description'];
        }
        
        // Build update query if we have fields to update
        if (!empty($update_fields)) {
            $set_parts = [];
            $update_params = ['company_id' => $company_id];
            
            foreach ($update_fields as $field => $value) {
                $set_parts[] = "$field = :$field";
                $update_params[$field] = $value;
            }
            
            $update_sql = "UPDATE bg_companies SET " . implode(', ', $set_parts) . 
                         ", modify_dt = NOW() WHERE company_id = :company_id";
            
            $database->query($update_sql, $update_params);
        }
        
        // Determine final status based on errors
        $final_status = 'active';
        $status_message = 'Business activated successfully';
        
        if (!empty($errors) && count($errors) >= 2) {
            // Too many errors, needs manual review
            $final_status = 'data_collection_failed';
            $status_message = 'Data collection failed, needs manual review';
        }
        
        // Update status
        $status_sql = "UPDATE bg_companies 
                      SET status = :status, company_status = :status 
                      WHERE company_id = :company_id";
        
        $database->query($status_sql, [
            'status' => $final_status,
            'company_id' => $company_id
        ]);
        
        echo "  - Status: $status_message\n";
        
        // Commit transaction
        $database->commit();
        
        // Summary
        $elapsed_time = round(microtime(true) - $start_time, 2);
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "Data collection completed in {$elapsed_time} seconds\n";
        echo "Business: $company_name (ID: $company_id)\n";
        echo "Final Status: $final_status\n";
        
        if ($final_status === 'active') {
            echo "\nData collected:\n";
            foreach ($collected_data as $key => $value) {
                $display_value = substr($value, 0, 60);
                if (strlen($value) > 60) $display_value .= '...';
                echo "  - $key: $display_value\n";
            }
            echo "\nSTATUS: SUCCESS - Business activated successfully\n";
        } else {
            echo "\nErrors encountered:\n";
            foreach ($errors as $error) {
                echo "  - $error\n";
            }
            echo "\nSTATUS: FAILURE - Data collection failed\n";
            exit(1);
        }
        
    } catch (Exception $e) {
        $database->rollBack();
        echo "  - DATABASE ERROR: " . $e->getMessage() . "\n";
        echo "\nSTATUS: FAILURE - Database error occurred\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\nFATAL ERROR: " . $e->getMessage() . "\n";
    error_log("Business data collection error: " . $e->getMessage());
    echo "\nSTATUS: FAILURE - Fatal error occurred\n";
    exit(1);
}

/**
 * Get HTTP status code for a URL
 */
function getUrlStatus($url) {
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
    
    return $httpCode;
}

/**
 * Extract metadata from a webpage
 */
function extractMetadata($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; BirthdayGold/1.0)');
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || empty($html)) {
        return false;
    }
    
    $metadata = [];
    
    // Extract description
    if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
        $metadata['description'] = html_entity_decode($matches[1], ENT_QUOTES);
    }
    
    // Extract keywords
    if (preg_match('/<meta\s+name=["\']keywords["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
        $metadata['keywords'] = html_entity_decode($matches[1], ENT_QUOTES);
    }
    
    // Extract og:description as fallback
    if (empty($metadata['description']) && preg_match('/<meta\s+property=["\']og:description["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
        $metadata['description'] = html_entity_decode($matches[1], ENT_QUOTES);
    }
    
    return $metadata;
}

/**
 * Find social media links on a webpage
 */
function findSocialMediaLinks($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; BirthdayGold/1.0)');
    
    $html = curl_exec($ch);
    curl_close($ch);
    
    if (empty($html)) {
        return false;
    }
    
    $social_links = [];
    
    // Facebook
    if (preg_match('/href=["\']([^"\']*(?:facebook\.com|fb\.com)[^"\']*)/i', $html, $matches)) {
        $social_links['facebook'] = $matches[1];
    }
    
    // Twitter/X
    if (preg_match('/href=["\']([^"\']*(?:twitter\.com|x\.com)[^"\']*)/i', $html, $matches)) {
        $social_links['twitter'] = $matches[1];
    }
    
    // Instagram
    if (preg_match('/href=["\']([^"\']*instagram\.com[^"\']*)/i', $html, $matches)) {
        $social_links['instagram'] = $matches[1];
    }
    
    // TikTok
    if (preg_match('/href=["\']([^"\']*tiktok\.com[^"\']*)/i', $html, $matches)) {
        $social_links['tiktok'] = $matches[1];
    }
    
    // LinkedIn
    if (preg_match('/href=["\']([^"\']*linkedin\.com[^"\']*)/i', $html, $matches)) {
        $social_links['linkedin'] = $matches[1];
    }
    
    // YouTube
    if (preg_match('/href=["\']([^"\']*youtube\.com[^"\']*)/i', $html, $matches)) {
        $social_links['youtube'] = $matches[1];
    }
    
    return $social_links;
}

/**
 * Analyze business category based on various signals
 */
function analyzeBusinessCategory($name, $url, $metadata) {
    $signals = strtolower($name . ' ' . ($metadata['description'] ?? '') . ' ' . ($metadata['keywords'] ?? ''));
    $domain = parse_url($url, PHP_URL_HOST);
    
    $categories = [
        'restaurant' => ['restaurant', 'dining', 'cuisine', 'menu', 'chef', 'food', 'eat', 'dine', 'bistro', 'cafe', 'grill', 'kitchen', 'tavern'],
        'retail' => ['shop', 'store', 'buy', 'purchase', 'product', 'merchandise', 'retail', 'mart', 'depot', 'outlet'],
        'entertainment' => ['entertainment', 'movie', 'cinema', 'theater', 'show', 'ticket', 'event', 'concert', 'game', 'arcade', 'bowling'],
        'beauty' => ['beauty', 'salon', 'spa', 'hair', 'nail', 'makeup', 'cosmetic', 'skincare', 'wellness', 'massage'],
        'fitness' => ['fitness', 'gym', 'workout', 'exercise', 'health', 'yoga', 'pilates', 'training', 'athletic', 'sports'],
        'hotel' => ['hotel', 'accommodation', 'lodging', 'stay', 'room', 'suite', 'resort', 'inn', 'motel'],
        'automotive' => ['auto', 'car', 'vehicle', 'tire', 'oil', 'mechanic', 'repair', 'service', 'dealership'],
        'grocery' => ['grocery', 'supermarket', 'food', 'produce', 'market', 'organic'],
        'pharmacy' => ['pharmacy', 'drug', 'medication', 'prescription', 'health', 'wellness'],
        'pet' => ['pet', 'animal', 'dog', 'cat', 'veterinary', 'grooming']
    ];
    
    $scores = [];
    
    foreach ($categories as $category => $keywords) {
        $score = 0;
        foreach ($keywords as $keyword) {
            if (strpos($signals, $keyword) !== false) {
                $score += 1;
            }
            if (strpos($domain, $keyword) !== false) {
                $score += 2; // Domain match is stronger signal
            }
        }
        if ($score > 0) {
            $scores[$category] = $score;
        }
    }
    
    if (empty($scores)) {
        return 'other';
    }
    
    // Return category with highest score
    arsort($scores);
    return key($scores);
}

exit(0);
?>