<?php
// mail-goldie-process.php - AJAX endpoint for Goldie Managed Inbox

// Prevent any output buffering that might interfere with SSE
if (ob_get_level()) ob_end_clean();

// Set execution limits BEFORE loading anything
set_time_limit(300); // 5 minutes
ini_set('memory_limit', '256M');

// Set up for Server-Sent Events FIRST
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable Nginx buffering

// Force immediate output
ob_implicit_flush(true);
flush();

// Now set up classes and logging
$addClasses[] = 'mail';
$addClasses[] = 'ai';

// Start error logging
$start_time = microtime(true);
$debug_mode = ($_REQUEST['debug'] ?? '') === '1'; // Allow debug via query param

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "data: " . json_encode([
            'type' => 'error',
            'message' => 'Fatal error: ' . $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ]) . "\n\n";
        @ob_flush();
        flush();
    }
});

// Custom error handler for debugging
if ($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        error_log("[Goldie Mail Process] Error: $errstr in $errfile:$errline");
        echo "data: " . json_encode([
            'type' => 'error',
            'message' => "PHP Error: $errstr",
            'file' => basename($errfile),
            'line' => $errline
        ]) . "\n\n";
        @ob_flush();
        flush();
        return true;
    });
}

try {
    include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
} catch (Exception $e) {
    echo "data: " . json_encode(['type' => 'error', 'message' => 'Failed to load site controller: ' . $e->getMessage()]) . "\n\n";
    exit;
}

// Log execution checkpoint
if ($debug_mode) {
    error_log("[Goldie Mail Process] Site controller loaded in " . round((microtime(true) - $start_time) * 1000) . "ms");
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$days = intval($input['days'] ?? 7);
$forceRefresh = $input['forceRefresh'] ?? false;
$uid = $current_user_data['user_id'] ?? 0;

if ($debug_mode) {
    error_log("[Goldie Mail Process] Request params: days=$days, forceRefresh=" . ($forceRefresh ? 'true' : 'false') . ", uid=$uid");
}

// Check if user is logged in
if (!$uid) {
    echo "data: " . json_encode(['type' => 'error', 'message' => 'User not logged in']) . "\n\n";
    exit;
}

// Helper function to send SSE message
function sendEvent($data) {
    global $debug_mode;
    if ($debug_mode) {
        error_log("[Goldie Mail Process] Sending event: " . $data['type'] . " - " . ($data['message'] ?? ''));
    }
    echo "data: " . json_encode($data) . "\n\n";
    @ob_flush();
    flush();
}

// Send initial heartbeat to confirm connection
sendEvent([
    'type' => 'heartbeat',
    'message' => 'Connection established',
    'timestamp' => time()
]);

// Send debug info if in debug mode
if ($debug_mode) {
    sendEvent([
        'type' => 'debug',
        'message' => 'Debug mode active',
        'user_id' => $uid,
        'days' => $days,
        'php_version' => PHP_VERSION
    ]);
}

// Calculate date range first
$end_date = new DateTime();
$start_date = new DateTime();
$start_date->modify("-{$days} days");

// Step 1: Check for existing summaries (fast check)
$existing_summaries = [];
if (!$forceRefresh) {
    try {
        // Quick count check first
        $count_sql = "SELECT COUNT(*) as cnt FROM bg_user_message_summaries 
                      WHERE user_id = :user_id 
                      AND summary_date >= :start_date 
                      AND summary_date <= :end_date 
                      AND summary_type = 'daily'
                      AND processing_status = 'completed'";
        
        $count_params = [
            'user_id' => $uid,
            'start_date' => $start_date->format('Y-m-d'),
            'end_date' => $end_date->format('Y-m-d')
        ];
        
        if ($debug_mode) {
            error_log("[Goldie Mail Process] Checking for existing summaries with params: " . json_encode($count_params));
        }
        
        $stmt = $database->query($count_sql, $count_params);
        $count_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $summary_count = $count_result['cnt'] ?? 0;
        
        if ($debug_mode) {
            error_log("[Goldie Mail Process] Found {$summary_count} existing summaries");
        }
    } catch (Exception $e) {
        if ($debug_mode) {
            error_log("[Goldie Mail Process] Database error checking summaries: " . $e->getMessage());
        }
        // Table might not exist, continue without cached summaries
        $summary_count = 0;
    }
    
    if ($summary_count > 0) {
        sendEvent([
            'type' => 'progress',
            'message' => 'Checking for existing summaries...',
            'detail' => "Found {$summary_count} cached summaries",
            'percent' => 20
        ]);
        
        // Now get the actual summaries
        $sql = "SELECT * FROM bg_user_message_summaries 
                WHERE user_id = :user_id 
                AND summary_date >= :start_date 
                AND summary_date <= :end_date 
                AND summary_type = 'daily'
                AND processing_status = 'completed'
                ORDER BY summary_date DESC";
        
        $stmt = $database->query($sql, $count_params);
        while ($summary = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing_summaries[$summary['summary_date']] = $summary;
        }
    } else {
        // Flash through quickly if no summaries
        sendEvent([
            'type' => 'progress',
            'message' => 'No cached summaries found',
            'detail' => 'Will generate fresh summaries',
            'percent' => 25
        ]);
        usleep(100000); // 0.1 second brief pause
    }
} else {
    sendEvent([
        'type' => 'progress',
        'message' => 'Force refresh requested',
        'detail' => 'Regenerating all summaries',
        'percent' => 20
    ]);
}

sendEvent([
    'type' => 'progress',
    'message' => 'Retrieving messages...',
    'detail' => 'Fetching your birthday reward messages',
    'percent' => 30
]);

try {
    // Get messages for the date range
    if ($debug_mode) {
        error_log("[Goldie Mail Process] Calling getMessagesForAI for user $uid from " . $start_date->format('Y-m-d') . " to " . $end_date->format('Y-m-d'));
    }
    
    $messages_results = $mail->getMessagesForAI($uid, $start_date->format('Y-m-d'), $end_date->format('Y-m-d'));
    $messages = $messages_results['messages'] ?? [];
    
    if ($debug_mode) {
        error_log("[Goldie Mail Process] Retrieved " . count($messages) . " messages");
    }
} catch (Exception $e) {
    if ($debug_mode) {
        error_log("[Goldie Mail Process] Error retrieving messages: " . $e->getMessage());
    }
    sendEvent([
        'type' => 'error',
        'message' => 'Failed to retrieve messages: ' . $e->getMessage()
    ]);
    exit;
}

sendEvent([
    'type' => 'progress',
    'message' => 'Found ' . count($messages) . ' messages',
    'detail' => 'Organizing messages by date',
    'percent' => 40
]);

// Group messages by day
$messages_by_day = [];
foreach ($messages as $message) {
    $date = date('Y-m-d', strtotime($message['create_dt']));
    if (!isset($messages_by_day[$date])) {
        $messages_by_day[$date] = [];
    }
    $messages_by_day[$date][] = $message;
}

// Process summaries
$total_days = count($messages_by_day);
$processed = 0;
$summaries_sent = 0;

foreach ($messages_by_day as $date => $day_messages) {
    $processed++;
    $percent = 40 + (40 * $processed / $total_days);
    
    // Check if we have a cached summary for this date
    if (isset($existing_summaries[$date]) && !$forceRefresh) {
        sendEvent([
            'type' => 'progress',
            'message' => 'Loading cached summary...',
            'detail' => 'Processing ' . date('F j, Y', strtotime($date)),
            'percent' => $percent
        ]);
        
        // Parse the stored summary data
        $summary = $existing_summaries[$date];
        $offers = json_decode($summary['offer_details'], true) ?? [];
        $companies = json_decode($summary['companies_included'], true) ?? [];
        
        // Get company names
        $company_names = [];
        foreach ($companies as $company_id) {
            $company = $app->getcompany($company_id);
            if ($company) {
                $company_names[] = $company['company_display_name'];
            }
        }
        
        sendEvent([
            'type' => 'summary',
            'summary' => [
                'date' => $date,
                'displayDate' => date('F j, Y', strtotime($date)),
                'messageCount' => $summary['message_count'],
                'companyCount' => count($companies),
                'companies' => $company_names,
                'summary' => $summary['ai_summary'],
                'offers' => $offers,
                'cached' => true
            ]
        ]);
        
        $summaries_sent++;
        sleep(0.1); // Small delay for visual effect
        continue;
    }
    
    // Generate new summary
    sendEvent([
        'type' => 'progress',
        'message' => 'Goldie is analyzing messages...',
        'detail' => 'Processing ' . date('F j, Y', strtotime($date)) . ' (' . count($day_messages) . ' messages)',
        'percent' => $percent
    ]);
    
    // Set up AI if available
    if (isset($ai)) {
        $ai->setEngine('anthropic_goldie', 'text');
        
        // Collect company info and message details
        $companies = [];
        $message_texts = [];
        $message_ids = [];
        
        foreach ($day_messages as $message) {
            $message_ids[] = $message['message_id'];
            
            if (!empty($message['company_id'])) {
                $company = $app->getcompany($message['company_id']);
                if ($company) {
                    $companies[$message['company_id']] = $company['company_display_name'] ?? 'Unknown';
                }
            }
            
            // Extract more detailed content from messages
            $body_text = strip_tags($message['body'] ?? '');
            $body_preview = substr($body_text, 0, 500); // Get more content
            
            $message_texts[] = "Company: " . ($companies[$message['company_id']] ?? 'Unknown') . 
                              "\nSubject: " . $message['subject'] . 
                              "\nContent: " . $body_preview;
        }
        
        $prompt = "Analyze these birthday reward emails from " . date('F j, Y', strtotime($date)) . 
                  " and provide:\n" .
                  "1. A brief, friendly summary of all offers (2-3 sentences)\n" .
                  "2. Extract specific offers with details on what the deal is and how to redeem it\n\n" .
                  "Messages:\n" . implode("\n---\n", $message_texts) . "\n\n" .
                  "Format the response as:\n" .
                  "SUMMARY: [your 2-3 sentence summary]\n" .
                  "OFFERS:\n" .
                  "- [Company Name]: [Specific offer and redemption details]\n" .
                  "- [Company Name]: [Specific offer and redemption details]";
        
        try {
            $response = $ai->process([
                ['role' => 'system', 'content' => 'You are Goldie, a helpful assistant that analyzes birthday reward emails. Focus on extracting specific offers, discounts, and redemption instructions. Be concise but include important details like discount amounts, free items, and how to claim rewards.'],
                ['role' => 'user', 'content' => $prompt]
            ], [
                'temperature' => 0.7,
                'max_tokens' => 500
            ]);
            
            $normalizedResponse = $ai->getNormalizedResponse($response);
            $ai_response = $normalizedResponse['content'];
            
            // Parse the AI response
            $summary_text = '';
            $offers = [];
            
            if (preg_match('/SUMMARY:\s*(.+?)(?=OFFERS:|$)/si', $ai_response, $matches)) {
                $summary_text = trim($matches[1]);
            }
            
            if (preg_match('/OFFERS:\s*(.+)/si', $ai_response, $matches)) {
                $offers_section = $matches[1];
                if (preg_match_all('/[-•]\s*([^:]+):\s*([^\n]+)/i', $offers_section, $offer_matches, PREG_SET_ORDER)) {
                    foreach ($offer_matches as $offer_match) {
                        $offers[] = [
                            'company' => trim($offer_match[1]),
                            'offer' => trim($offer_match[2]),
                            'action' => 'Click to view in inbox'
                        ];
                    }
                }
            }
            
            // If parsing failed, use the whole response as summary
            if (empty($summary_text)) {
                $summary_text = $ai_response;
            }
            
            // Store the summary in database
            $sql = "INSERT INTO bg_user_message_summaries 
                    (user_id, summary_date, summary_type, message_count, message_ids, 
                     companies_included, ai_summary, offer_details, processing_status, processed_by)
                    VALUES (:user_id, :summary_date, 'daily', :message_count, :message_ids,
                            :companies, :ai_summary, :offers, 'completed', 'realtime')
                    ON DUPLICATE KEY UPDATE
                    ai_summary = VALUES(ai_summary),
                    offer_details = VALUES(offer_details),
                    message_count = VALUES(message_count),
                    updated_at = NOW()";
            
            $params = [
                'user_id' => $uid,
                'summary_date' => $date,
                'message_count' => count($day_messages),
                'message_ids' => json_encode($message_ids),
                'companies' => json_encode(array_keys($companies)),
                'ai_summary' => $summary_text,
                'offers' => json_encode($offers)
            ];
            
            $database->query($sql, $params);
            
            // Send the summary
            sendEvent([
                'type' => 'summary',
                'summary' => [
                    'date' => $date,
                    'displayDate' => date('F j, Y', strtotime($date)),
                    'messageCount' => count($day_messages),
                    'companyCount' => count($companies),
                    'companies' => array_values($companies),
                    'summary' => $summary_text,
                    'offers' => $offers,
                    'cached' => false
                ]
            ]);
            
            $summaries_sent++;
            
        } catch (Exception $e) {
            error_log("AI Summary Error: " . $e->getMessage());
            
            // Store failed attempt
            $sql = "INSERT INTO bg_user_message_summaries 
                    (user_id, summary_date, summary_type, message_count, processing_status, processing_error)
                    VALUES (:user_id, :summary_date, 'daily', :message_count, 'failed', :error)
                    ON DUPLICATE KEY UPDATE
                    processing_status = 'failed',
                    processing_error = VALUES(processing_error)";
            
            $params = [
                'user_id' => $uid,
                'summary_date' => $date,
                'message_count' => count($day_messages),
                'error' => $e->getMessage()
            ];
            
            $database->query($sql, $params);
        }
        
        // Small delay to avoid rate limiting
        usleep(500000); // 0.5 seconds
    }
}

// Send completion event
sendEvent([
    'type' => 'progress',
    'message' => 'Processing complete!',
    'detail' => 'Generated ' . $summaries_sent . ' summaries',
    'percent' => 100
]);

sleep(1); // Brief pause before final event

sendEvent([
    'type' => 'complete',
    'totalSummaries' => $summaries_sent
]);

exit;
?>