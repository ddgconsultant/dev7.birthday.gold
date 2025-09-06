<?php
// Include site controller
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set response header
header('Content-Type: application/json');

// Check if user is logged in
if (!$account->isactive()) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Get the tokens from POST
$tokens = isset($_POST['tokens']) ? json_decode($_POST['tokens'], true) : [];

// Process mode: 'count' (default), 'single' (1 user for preview), 'all' (all matching users)
$process = isset($_POST['process']) ? $_POST['process'] : 'count';
$ctaCategory = isset($_POST['cta_category']) ? $_POST['cta_category'] : '';
$ctaMode = isset($_POST['cta_mode']) ? $_POST['cta_mode'] : 'inclusive';
$limit = isset($_POST['limit']) ? intval($_POST['limit']) : 100; // For 'all' mode
$debug = isset($_POST['debug']) && $_POST['debug'] === 'true';

// Debug logging
if ($debug) {
    error_log("=== Newsletter Preview Debug ===");
    error_log("Process mode: " . $process);
    error_log("Tokens received: " . json_encode($tokens));
    error_log("CTA Category: " . $ctaCategory);
    error_log("CTA Mode: " . $ctaMode);
}

// If no tokens provided, return appropriate empty response
if (empty($tokens)) {
    switch ($process) {
        case 'single':
        case 'all':
            echo json_encode(['success' => true, 'users' => [], 'count' => 0]);
            break;
        default:
            echo json_encode(['success' => true, 'count' => 0]);
    }
    exit;
}

try {
    switch ($process) {
        case 'single':
            // Get single user for preview
            $debugInfo = [];
            
            if ($debug) {
                $debugInfo['tokens_received'] = $tokens;
                $debugInfo['process_mode'] = $process;
            }
            
            $users = $marketing->getRecipients($tokens, 1);
            
            // Debug logging
            error_log("Preview - Tokens: " . json_encode($tokens));
            error_log("Preview - Users found: " . count($users));
            if (!empty($users)) {
                error_log("Preview - First user: " . json_encode($users[0]));
            }
            
            if ($debug) {
                $debugInfo['users_found'] = count($users);
                $debugInfo['first_user'] = !empty($users) ? $users[0] : null;
            }
            
            // Format birth month name helper
            $monthNames = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ];
            
            if (!empty($users)) {
                $previewUser = $users[0];
                
                // Parse birthdate to get month and day
                $birthdate = $previewUser['birthdate'] ?? null;
                $birthMonth = 1;
                $birthDay = 1;
                $birthdayMonthName = 'January';
                
                if ($birthdate) {
                    $dateTime = new DateTime($birthdate);
                    $birthMonth = (int)$dateTime->format('n');
                    $birthDay = (int)$dateTime->format('j');
                    $birthdayMonthName = $monthNames[$birthMonth] ?? 'January';
                }
                
                // Get companies for CTA block using the new marketing method
                $companies = [];
                if ($ctaCategory) {
                    $userEnrollments = !empty($previewUser['enrolled_company_ids']) 
                        ? explode(',', $previewUser['enrolled_company_ids']) 
                        : [];
                    
                    // If inclusive mode but user has no enrollments, switch to showing all companies
                    if ($ctaMode === 'inclusive' && empty($userEnrollments)) {
                        error_log("User has no enrollments, showing all companies in category");
                        $companies = $marketing->getCompaniesForCTA(
                            $ctaCategory, 
                            'exclusive',  // Switch to exclusive to show companies
                            [],           // No enrollments to exclude
                            4
                        );
                    } else {
                        $companies = $marketing->getCompaniesForCTA(
                            $ctaCategory, 
                            $ctaMode, 
                            $userEnrollments, 
                            4
                        );
                    }
                    
                    // Convert logo URLs to base64 for email embedding
                    foreach ($companies as &$company) {
                        if (!empty($company['logo'])) {
                            // Convert CDN URL to full URL
                            $logoUrl = $company['logo'];
                            if (strpos($logoUrl, '//') === 0) {
                                $logoUrl = 'https:' . $logoUrl;
                            }
                            
                            // Try to fetch and encode the image
                            try {
                                $imageData = @file_get_contents($logoUrl);
                                if ($imageData !== false) {
                                    // Detect mime type from URL extension
                                    $extension = strtolower(pathinfo($logoUrl, PATHINFO_EXTENSION));
                                    $mimeType = 'image/jpeg'; // default
                                    
                                    switch($extension) {
                                        case 'png':
                                            $mimeType = 'image/png';
                                            break;
                                        case 'gif':
                                            $mimeType = 'image/gif';
                                            break;
                                        case 'webp':
                                            $mimeType = 'image/webp';
                                            break;
                                        case 'jpg':
                                        case 'jpeg':
                                            $mimeType = 'image/jpeg';
                                            break;
                                    }
                                    
                                    // Convert to base64 data URI
                                    $base64 = base64_encode($imageData);
                                    $company['logo'] = 'data:' . $mimeType . ';base64,' . $base64;
                                    
                                    if ($debug) {
                                        error_log("Converted logo to base64 for company " . $company['company_id'] . " (size: " . strlen($base64) . " bytes)");
                                    }
                                } else {
                                    // Keep original URL if fetch fails
                                    if ($debug) {
                                        error_log("Failed to fetch logo from: " . $logoUrl);
                                    }
                                }
                            } catch (Exception $e) {
                                // Keep original URL if error occurs
                                if ($debug) {
                                    error_log("Error converting logo: " . $e->getMessage());
                                }
                            }
                        }
                    }
                    
                    // Debug log
                    if ($debug) {
                        error_log("CTA Category: " . $ctaCategory);
                        error_log("CTA Mode: " . $ctaMode);
                        error_log("User Enrollments: " . (!empty($userEnrollments) ? implode(',', $userEnrollments) : 'none'));
                        error_log("CTA Companies found: " . count($companies));
                        if (!empty($companies)) {
                            error_log("First company: " . json_encode($companies[0]));
                        }
                    }
                }
                
                $response = [
                    'success' => true,
                    'user' => [
                        'user_id' => $previewUser['user_id'],
                        'first_name' => $previewUser['first_name'] ?: 'John',
                        'last_name' => $previewUser['last_name'] ?: 'Doe',
                        'email' => $previewUser['email'] ?: 'john.doe@example.com',
                        'city' => $previewUser['city'] ?: 'Seattle',
                        'state' => $previewUser['state'] ?: '',
                        'birthdate' => $previewUser['birthdate'] ?? '2000-01-01',
                        'birth_month' => $birthMonth,
                        'birth_day' => $birthDay,
                        'birthday_month' => $birthdayMonthName,
                        'enrolled_company_ids' => $previewUser['enrolled_company_ids'] ?: ''
                    ],
                    'companies' => $companies,
                    'matched_criteria' => true,
                    'count' => 1
                ];
                
                if ($debug) {
                    $debugInfo['companies_returned'] = count($companies);
                    $debugInfo['companies_data'] = $companies;
                    $response['debug'] = $debugInfo;
                }
            } else {
                // No users match, get a default user
                error_log("No users found matching criteria, using default");
                
                $defaultUser = $database->getrow("SELECT user_id, first_name, last_name, email, city, state, birthdate 
                                                 FROM bg_users 
                                                 WHERE status = 'active' 
                                                 LIMIT 1");
                
                // Parse birthdate for default user
                $defaultBirthdate = $defaultUser['birthdate'] ?? null;
                $defaultBirthMonth = 1;
                $defaultBirthDay = 1;
                $defaultBirthdayMonthName = 'January';
                
                if ($defaultBirthdate) {
                    $defaultDateTime = new DateTime($defaultBirthdate);
                    $defaultBirthMonth = (int)$defaultDateTime->format('n');
                    $defaultBirthDay = (int)$defaultDateTime->format('j');
                    $defaultBirthdayMonthName = $monthNames[$defaultBirthMonth] ?? 'January';
                }
                
                if ($debug) {
                    $debugInfo['fallback_reason'] = 'No users matched criteria';
                    $debugInfo['default_user'] = $defaultUser;
                }
                
                $response = [
                    'success' => true,
                    'user' => [
                        'user_id' => $defaultUser['user_id'] ?? 0,
                        'first_name' => $defaultUser['first_name'] ?: 'John',
                        'last_name' => $defaultUser['last_name'] ?: 'Doe',
                        'email' => $defaultUser['email'] ?: 'john.doe@example.com',
                        'city' => $defaultUser['city'] ?: 'Seattle',
                        'state' => $defaultUser['state'] ?: '',
                        'birthdate' => $defaultUser['birthdate'] ?? '2000-01-01',
                        'birth_month' => $defaultBirthMonth,
                        'birth_day' => $defaultBirthDay,
                        'birthday_month' => $defaultBirthdayMonthName,
                        'enrolled_company_ids' => ''
                    ],
                    'companies' => [],
                    'matched_criteria' => false,
                    'count' => 0
                ];
                
                if ($debug) {
                    $response['debug'] = $debugInfo;
                }
            }
            break;
            
        case 'all':
            // Get all matching users (with limit)
            $users = $marketing->getRecipients($tokens, $limit);
            $count = $marketing->getRecipientCount($tokens);
            
            // Format birth month names for all users
            $monthNames = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ];
            
            // Parse birthdate and add formatted birthday month to each user
            foreach ($users as &$user) {
                if (!empty($user['birthdate'])) {
                    $dateTime = new DateTime($user['birthdate']);
                    $user['birth_month'] = (int)$dateTime->format('n');
                    $user['birth_day'] = (int)$dateTime->format('j');
                    $user['birthday_month'] = $monthNames[$user['birth_month']] ?? '';
                } else {
                    $user['birth_month'] = 1;
                    $user['birth_day'] = 1;
                    $user['birthday_month'] = 'January';
                }
            }
            
            $response = [
                'success' => true,
                'users' => $users,
                'count' => $count,
                'returned' => count($users),
                'limit' => $limit
            ];
            break;
            
        case 'count':
        default:
            // Just get count (original behavior)
            $count = $marketing->getRecipientCount($tokens);
            
            $response = [
                'success' => true,
                'count' => $count
            ];
            
            // Add debug info for staff users when requested
            if (isset($_REQUEST['debug']) && $account->isstaff()) {
                $response['debug'] = [
                    'tokens' => $tokens,
                    'count' => $count,
                    'user_id' => $current_user_data['user_id']
                ];
            }
            break;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log error for debugging
    error_log('Newsletter recipient count error: ' . $e->getMessage());
    error_log('Error trace: ' . $e->getTraceAsString());
    
    // Return graceful error response
    $response = [
        'success' => false,
        'count' => 0,
        'error' => $e->getMessage()
    ];
    
    if ($debug) {
        $response['debug'] = [
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'tokens' => $tokens,
            'process' => $process
        ];
    }
    
    echo json_encode($response);
}
?>