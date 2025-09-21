<?php
// abo_processsubmission.php - Process submitted business recommendations
// Part of the Automation Business Onboarding (ABO) system
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get company ID - support both encoded and raw for debugging
$specific_company_id = null;

if (isset($_GET['rawid'])) {
    // Debug mode - use raw ID directly
    $specific_company_id = intval($_GET['rawid']);
} elseif (isset($_GET['id'])) {
    // Production mode - decode the ID
    $encoded_id = $_GET['id'];
    $specific_company_id = $qik->decodeID($encoded_id);
}

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'processed' => 0,
    'successful' => 0,
    'failed' => 0,
    'errors' => [],
    'debug' => [
        'specific_company_id' => $specific_company_id,
        'encoded_id' => $_GET['id'] ?? null,
        'raw_id' => $_GET['rawid'] ?? null
    ]
];

try {
    // Get companies to process
    if ($specific_company_id) {
        // First check what status this company actually has
        $check_sql = "SELECT company_id, company_name, status, source FROM bg_companies WHERE company_id = :company_id";
        $check_result = $database->query($check_sql, ['company_id' => $specific_company_id])->fetch(PDO::FETCH_ASSOC);
        
        $result['debug']['company_check'] = $check_result;
        
        // For manual trigger, process companies in any of these statuses
        $sql = "SELECT c.* FROM bg_companies c 
                WHERE c.company_id = :company_id 
                AND c.status IN ('submitted', 'pending_review', 'approved_pending_data', 'active')
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
    } else {
        // For automatic processing, only get submitted companies
        $sql = "SELECT c.* FROM bg_companies c 
                WHERE c.status = 'submitted' 
                AND c.source = 'user_recommendation'
                ORDER BY c.create_dt ASC 
                LIMIT 10";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result['debug']['found_companies'] = count($submissions);
    
    foreach ($submissions as $submission) {
        $result['processed']++;
        $company_id = $submission['company_id'];
        
        try {
            // Start fresh transaction
            $database->beginTransaction();
            
            // Update onboarding progress to in_progress
            $progress_sql = "UPDATE bg_company_attributes 
                            SET description = 'in_progress', modify_dt = NOW() 
                            WHERE company_id = :company_id 
                            AND type = 'onboarding_progress' 
                            AND name = 'abo_processsubmission'";
            $progress_params = ['company_id' => $company_id];
            
            $result['debug']['progress_at_line'] = __LINE__;
            $database->query($progress_sql, $progress_params);
            
            // 1. Validate URLs
            $home_url = $submission['company_url'];
            $signup_url = $submission['signup_url'];
            
            $result['debug']['validate_urls_at_line'] = __LINE__;
            
            $home_accessible = checkUrlAccessible($home_url);
            $signup_accessible = checkUrlAccessible($signup_url);
            
            if (!$home_accessible || !$signup_accessible) {
                $attr_sql = "INSERT INTO bg_company_attributes 
                            (company_id, type, name, description, status, create_dt)
                            VALUES 
                            (:company_id, 'validation', 'url_check', :status, 'active', NOW())";
                $attr_params = [
                    'company_id' => $company_id,
                    'status' => json_encode([
                        'home_accessible' => $home_accessible,
                        'signup_accessible' => $signup_accessible
                    ])
                ];
                $result['debug']['url_check_at_line'] = __LINE__;
                $database->query($attr_sql, $attr_params);
            }
            
            // 2. AI-assisted categorization
            $result['debug']['categorization_at_line'] = __LINE__;
            $category = determineCategory($submission['company_name'], $home_url);
            if ($category) {
                $update_sql = "UPDATE bg_companies 
                              SET category = :category, display_category = :display_category 
                              WHERE company_id = :company_id";
                $update_params = [
                    'category' => $category,
                    'display_category' => $category,
                    'company_id' => $company_id
                ];
                $result['debug']['update_category_at_line'] = __LINE__;
                $database->query($update_sql, $update_params);
                
                // Log categorization
                $attr_sql = "INSERT INTO bg_company_attributes 
                            (company_id, type, name, description, status, create_dt)
                            VALUES 
                            (:company_id, 'metadata', 'auto_category', :description, 'active', NOW())";
                $attr_params = [
                    'company_id' => $company_id,
                    'description' => $category
                ];
                $result['debug']['log_category_at_line'] = __LINE__;
                $database->query($attr_sql, $attr_params);
            }
            
            // 3. Check for duplicates
            $result['debug']['duplicate_check_at_line'] = __LINE__;
            $domain = parse_url($home_url, PHP_URL_HOST);
            $domain = preg_replace('/^www\./', '', $domain);
            
            $result['debug']['domain'] = $domain;
            
            // First update the company with the extracted domain
            $update_domain_sql = "UPDATE bg_companies 
                                  SET email_domain = :domain 
                                  WHERE company_id = :company_id";
            $domain_params = [
                'domain' => $domain,
                'company_id' => $company_id
            ];
            $result['debug']['update_domain_at_line'] = __LINE__;
            $database->query($update_domain_sql, $domain_params);
            
            // Check for duplicates - simpler query to avoid parameter issues
            $dup_sql = "SELECT company_id, company_name FROM bg_companies 
                        WHERE company_id != :current_id 
                        AND status NOT IN ('submitted', 'rejected')
                        AND (email_domain = :domain OR signup_url = :signup_url)
                        LIMIT 1";
            
            $dup_params = [
                'current_id' => $company_id,
                'domain' => $domain,
                'signup_url' => $signup_url
            ];
            
            $result['debug']['dup_query'] = $dup_sql;
            $result['debug']['dup_params'] = $dup_params;
            $result['debug']['dup_check_at_line'] = __LINE__;
            
            $dup_stmt = $database->query($dup_sql, $dup_params);
            
            if ($duplicate = $dup_stmt->fetch(PDO::FETCH_ASSOC)) {
                $attr_sql = "INSERT INTO bg_company_attributes 
                            (company_id, type, name, description, status, create_dt)
                            VALUES 
                            (:company_id, 'validation', 'possible_duplicate_id', :dup_id, 'active', NOW())";
                $database->query($attr_sql, [
                    'company_id' => $company_id,
                    'dup_id' => $duplicate['company_id']
                ]);
            }
            
            // 4. Update status to pending_review
            $update_sql = "UPDATE bg_companies 
                          SET status = 'pending_review', 
                              company_status = 'pending_review',
                              modify_dt = NOW()
                          WHERE company_id = :company_id";
            $database->query($update_sql, ['company_id' => $company_id]);
            
            // 5. Update onboarding progress to completed
            $complete_sql = "UPDATE bg_company_attributes 
                            SET description = 'completed', modify_dt = NOW() 
                            WHERE company_id = :company_id 
                            AND type = 'onboarding_progress' 
                            AND name = 'abo_processsubmission'";
            $database->query($complete_sql, ['company_id' => $company_id]);
            
            // 6. Add remaining processor steps now that initial validation passed
            $remaining_processors = [
                'abo_grabgoogleapp' => 'Collect Google App Data',
                'abo_grabiosapp' => 'Collect iOS App Data', 
                'abo_grabsocialmedia' => 'Collect Social Media Data',
                'abo_grabmetadata' => 'Collect Website Metadata',
                'abo_grabimages' => 'Collect Business Images',
                'abo_grablocations' => 'Collect Location Data',
                'abo_grabbirthday' => 'Collect Birthday Program Details',
                'abo_grabterms' => 'Collect Terms and Conditions',
                'abo_grabprivacy' => 'Collect Privacy Policy',
                'abo_grabage' => 'Collect Age Requirements',
                'abo_grabhours' => 'Collect Business Hours',
                'abo_aienhance' => 'AI Enhancement',
                'abo_mapformfields' => 'Map Form Fields',
                'abo_aivalidate' => 'AI Validation',
                'abo_finalize' => 'Finalize Onboarding'
            ];
            
            foreach ($remaining_processors as $processor_key => $processor_name) {
                $attr_sql = "INSERT INTO bg_company_attributes 
                             (company_id, type, name, description, status, create_dt)
                             VALUES 
                             (:company_id, 'onboarding_progress', :processor_name, 'pending', 'active', NOW())";
                $database->query($attr_sql, [
                    'company_id' => $company_id,
                    'processor_name' => $processor_key
                ]);
            }
            
            // 7. Log processing completion
            $log_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'tracking', 'abo_processed_at', :timestamp, 'active', NOW())";
            $database->query($log_sql, [
                'company_id' => $company_id,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            $database->commit();
            $result['successful']++;
            if (!isset($result['message'])) {
                $result['message'] = '';
            }
            $result['message'] .= " Added " . count($remaining_processors) . " additional processing steps.";
            
        } catch (Exception $e) {
            $database->rollBack();
            
            // Get the exact error location
            $error_trace = $e->getTraceAsString();
            $result['debug']['error_trace'] = substr($error_trace, 0, 500); // First 500 chars
            $result['debug']['error_line'] = $e->getLine();
            $result['debug']['error_file'] = basename($e->getFile());
            
            // Update onboarding progress to error
            try {
                $error_sql = "UPDATE bg_company_attributes 
                             SET description = 'error', modify_dt = NOW() 
                             WHERE company_id = :company_id 
                             AND type = 'onboarding_progress' 
                             AND name = 'abo_processsubmission'";
                $database->query($error_sql, ['company_id' => $company_id]);
                
                // Log the error details as an attribute
                $error_log_sql = "INSERT INTO bg_company_attributes 
                                 (company_id, type, name, description, status, create_dt)
                                 VALUES 
                                 (:company_id, 'error_log', :error_type, :error_msg, 'active', NOW())";
                $database->query($error_log_sql, [
                    'company_id' => $company_id,
                    'error_type' => 'abo_processsubmission_error',
                    'error_msg' => $e->getMessage() . ' at line ' . $e->getLine()
                ]);
            } catch (Exception $updateError) {
                // If we cannot even update the error status, log it
                session_tracking('Failed to update error status', "Company $company_id: " . $updateError->getMessage());
            }
            
            $result['failed']++;
            $result['errors'][] = "Company $company_id: " . $e->getMessage();
            session_tracking('ABO process submission error', "Company $company_id: " . $e->getMessage() . " at line " . $e->getLine());
        }
    }
    
    if (!isset($result['message'])) {
        $result['message'] = '';
    }
    $result['message'] = "Processed {$result['processed']} submissions: {$result['successful']} successful, {$result['failed']} failed" . $result['message'];
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    session_tracking('ABO process submission fatal error', $e->getMessage());
}

// Output JSON response for monitoring
header('Content-Type: application/json');
echo json_encode($result);

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
 * Determine business category using AI or keyword matching
 */
function determineCategory($name, $url) {
    global $ai;
    
    // Try AI categorization if available
    if (isset($ai) && method_exists($ai, 'categorize')) {
        try {
            $prompt = "Categorize this business into one of these categories: restaurant, retail, entertainment, beauty, fitness, travel, services, online. Business name: $name, URL: $url. Return only the category name.";
            $category = $ai->quick($prompt);
            if (in_array(strtolower(trim($category)), ['restaurant', 'retail', 'entertainment', 'beauty', 'fitness', 'travel', 'services', 'online'])) {
                return strtolower(trim($category));
            }
        } catch (Exception $e) {
            // Fall back to keyword matching
        }
    }
    
    // Keyword-based categorization fallback
    $name_lower = strtolower($name);
    $domain = parse_url($url, PHP_URL_HOST);
    
    $categories = [
        'restaurant' => ['restaurant', 'pizza', 'burger', 'cafe', 'coffee', 'diner', 'grill', 'kitchen', 'bistro'],
        'retail' => ['store', 'shop', 'mart', 'depot', 'target', 'walmart', 'boutique'],
        'entertainment' => ['cinema', 'theater', 'movie', 'games', 'arcade', 'bowl', 'museum'],
        'beauty' => ['salon', 'spa', 'beauty', 'nail', 'hair', 'cosmetic'],
        'fitness' => ['gym', 'fitness', 'yoga', 'pilates', 'crossfit', 'health'],
        'travel' => ['hotel', 'inn', 'resort', 'lodge', 'travel', 'airline'],
        'services' => ['service', 'repair', 'clean', 'laundry', 'print'],
        'online' => ['online', 'digital', 'app', 'software', 'cloud']
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