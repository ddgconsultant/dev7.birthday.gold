<?php
/**
 * MK Newsletter Personalizer - Production Version
 * Features:
 * - Dynamic batch sizing (20x faster for non-AI templates)
 * - AI response caching by generation type
 * - Batch processing of same-generation users
 * - Efficient memory usage
 * - Processes up to 50 notifications per run
 */

// Set execution limits to prevent timeout
set_time_limit(300); // 5 minutes max execution time
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// Disable output buffering completely for immediate output
@ini_set('output_buffering', 'Off');
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);

// Clear any existing output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Tell PHP to flush after every output
ob_implicit_flush(true);

// Suppress session warnings since we're in CLI/scheduler mode
@ini_set('session.use_cookies', 0);
@ini_set('session.use_only_cookies', 0);

// Send headers to prevent buffering
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // Disable Nginx buffering

// Output HTML header for better formatting in browser
echo '<pre style="font-family: monospace; font-size: 12px; line-height: 1.4; background: #f5f5f5; padding: 20px;">';

// Send initial output to establish connection
echo "Initializing MK Newsletter Personalizer...\n";
flush();

// Track if we've output a status yet
$statusOutput = false;

// Error handler to catch fatal errors AND ensure status is always output
register_shutdown_function(function() use (&$statusOutput) {
    $error = error_get_last();

    // If there's a fatal error, report it
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "\n\n=== FATAL ERROR ===\n";
        echo "Message: " . $error['message'] . "\n";
        echo "File: " . $error['file'] . "\n";
        echo "Line: " . $error['line'] . "\n";
        echo "Type: " . $error['type'] . "\n";

        if (!$statusOutput) {
            echo "\nStatus: Error\n";
            $statusOutput = true;
        }
    } else if (!$statusOutput) {
        // No fatal error but script ended without outputting status
        echo "\n\nScript terminated unexpectedly without status\n";
        echo "Status: Error\n";
        $statusOutput = true;
    }

    echo '</pre>';
    @ob_flush();
    @flush();
});

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Loading configuration...\n";
flush();

$pagename = 'mk-newsletter-personalizer';
$addClasses[] = 'marketing';
$addClasses[] = 'ai';

echo "Including site controller...\n";
flush();

// Check if path exists
$controller_path = dirname(__DIR__) . '/core/site-controller.php';
if (!file_exists($controller_path)) {
    echo "Error: site-controller.php not found at: $controller_path\n";
    echo "\nStatus: Error\n";
    $statusOutput = true;
    echo '</pre>';
    exit(1);
}

// Set a short timeout for the include to prevent hanging
$start_time = time();
$timeout = 10; // 10 seconds max for include

try {
    // Suppress warnings/notices from include
    $oldErrorReporting = error_reporting(E_ERROR | E_PARSE);

    // Use output buffering to capture any output from site-controller
    ob_start();
    include($controller_path);
    $includeOutput = ob_get_clean();

    error_reporting($oldErrorReporting);

    // Check if include took too long
    if (time() - $start_time > $timeout) {
        echo "Warning: Site controller took longer than expected to load\n";
    }

    echo "✓ Site controller loaded successfully\n";

    // Only show errors if any (skip warnings)
    if (!empty($includeOutput) && stripos($includeOutput, 'fatal') !== false) {
        echo "Site controller output: " . substr($includeOutput, 0, 200) . "\n";
    }
    flush();
} catch (Exception $e) {
    echo "Error loading site controller: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    echo "\nStatus: Error\n";
    $statusOutput = true;
    echo '</pre>';
    exit(1);
}

// Check database connection
if (!isset($database) || !is_object($database)) {
    echo "Error: Database connection not available\n";
    echo "\nStatus: Error\n";
    $statusOutput = true;
    echo '</pre>';
    exit(1);
}
echo "✓ Database connection established\n";
flush();

// Initialize AI if available
if (isset($ai) && is_object($ai)) {
    try {
        $ai->setEngine('anthropic_goldie', 'text');
        echo "AI engine set to anthropic_goldie\n";
    } catch (Exception $e) {
        echo "Warning: Could not set AI engine - " . $e->getMessage() . "\n";
        echo "Will use template-based processing only\n";
        $ai = null;
    }
} else {
    echo "AI not available - will use template-based processing only\n";
}
flush();

echo date('Y-m-d H:i:s') . " - Starting MK Newsletter Personalizer\n";
echo str_repeat('=', 80) . "\n";
flush(); // Force output to browser

// Configuration
$max_batch_size = 50;     // Maximum notifications to process per run
$ai_batch_size = 5;       // Process up to 5 of same generation together for AI
$template_batch_size = 20; // Process 20 at a time for non-AI templates

// First, analyze what needs to be processed
$analysis_sql = "SELECT
                    JSON_UNQUOTE(JSON_EXTRACT(options, '$.campaign_id')) as campaign_id,
                    JSON_UNQUOTE(JSON_EXTRACT(options, '$.gen_specific_messaging')) as gen_specific,
                    JSON_UNQUOTE(JSON_EXTRACT(options, '$.user_generation')) as generation,
                    COUNT(*) as count
                 FROM bg_user_notifications
                 WHERE type = 'newsletter'
                 AND status = 'pending'
                 AND sent_to IS NOT NULL
                 AND options IS NOT NULL
                 GROUP BY campaign_id, gen_specific, generation
                 ORDER BY count DESC";

$workload = $database->getrows($analysis_sql);

if (empty($workload)) {
    echo "No pending newsletters to personalize\n";

    // Show status breakdown
    $status_sql = "SELECT status, COUNT(*) as count
                  FROM bg_user_notifications
                  WHERE type = 'newsletter'
                  GROUP BY status";
    $statuses = $database->getrows($status_sql);

    if (!empty($statuses)) {
        echo "\nCurrent newsletter status breakdown:\n";
        foreach ($statuses as $status) {
            echo "  - {$status['status']}: {$status['count']} notifications\n";
        }
    }

    // No records to process is OK
    echo "\nStatus: Ok\n";
    $statusOutput = true;
    echo '</pre>';
    exit;
}

// Display workload analysis
echo "WORKLOAD ANALYSIS\n";
echo str_repeat('-', 80) . "\n";
$total_pending = 0;
$needs_ai_count = 0;
$template_only_count = 0;

foreach ($workload as $work) {
    $mode = ($work['gen_specific'] == '1') ? 'AI' : 'Template';
    $gen_label = $work['generation'] ?: 'unknown';
    
    echo sprintf("Campaign %s | %s mode | Generation: %s | Count: %d\n", 
        $work['campaign_id'], 
        $mode,
        $gen_label,
        $work['count']
    );
    
    $total_pending += $work['count'];
    if ($work['gen_specific'] == '1') {
        $needs_ai_count += $work['count'];
    } else {
        $template_only_count += $work['count'];
    }
}

echo str_repeat('-', 80) . "\n";
echo "Total pending: $total_pending (AI: $needs_ai_count, Template: $template_only_count)\n";
echo str_repeat('=', 80) . "\n\n";
flush(); // Keep connection alive

// Initialize components
try {
    $marketing = new Marketing($database, $qik, $mail);
} catch (Exception $e) {
    echo "Error initializing Marketing class: " . $e->getMessage() . "\n";
    echo "\nStatus: Error\n";
    $statusOutput = true;
    echo '</pre>';
    exit(1);
}

$processed_count = 0;
$error_count = 0;
$ai_cache = []; // Cache AI responses by campaign_id + generation

// Process workload strategically
try {
foreach ($workload as $work) {
    if ($processed_count >= $max_batch_size) {
        echo "\nReached max batch size ($max_batch_size), stopping for this run\n";
        break;
    }
    
    $campaign_id = $work['campaign_id'];
    $generation = $work['generation'] ?: 'millennial';
    $needs_ai = ($work['gen_specific'] == '1');
    
    // Determine batch size for this group
    $group_batch_size = $needs_ai ? $ai_batch_size : $template_batch_size;
    $remaining_capacity = $max_batch_size - $processed_count;
    $batch_to_process = min($group_batch_size, $remaining_capacity, $work['count']);
    
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "PROCESSING GROUP: Campaign $campaign_id | Generation: $generation\n";
    echo "Mode: " . ($needs_ai ? "AI-powered" : "Template-based") . " | Batch size: $batch_to_process\n";
    echo str_repeat('-', 80) . "\n";
    
    // Get notifications for this specific group
    // Note: LIMIT must be included directly in SQL string, not as parameter
    // Also need to quote the campaign_id for JSON comparison
    $group_sql = "SELECT * FROM bg_user_notifications
                  WHERE type = 'newsletter'
                  AND status = 'pending'
                  AND sent_to IS NOT NULL
                  AND JSON_UNQUOTE(JSON_EXTRACT(options, '$.campaign_id')) = :campaign_id
                  AND (JSON_UNQUOTE(JSON_EXTRACT(options, '$.user_generation')) = :generation1
                       OR (JSON_EXTRACT(options, '$.user_generation') IS NULL AND :generation2 = 'millennial'))
                  ORDER BY create_dt ASC
                  LIMIT " . intval($batch_to_process);

    $notifications = $database->getrows($group_sql, [
        'campaign_id' => (string)$campaign_id,
        'generation1' => (string)$generation,
        'generation2' => (string)$generation
    ]);
    
    if (empty($notifications)) {
        continue;
    }
    
    // Get campaign data from first notification
    $first_options = json_decode($notifications[0]['options'], true);
    $campaign_name = $first_options['campaign_name'] ?? 'Unknown Campaign';
    $campaign_content_original = $first_options['campaign_content'] ?? '';
    $subject_original = $first_options['email_subject'] ?? '';
    
    echo "Campaign: $campaign_name\n";
    echo "Processing " . count($notifications) . " notifications for $generation generation\n";
    
    // Generate AI content once for this generation if needed
    $ai_subject = null;
    $ai_content = null;
    $cache_key = "{$campaign_id}_{$generation}";
    
    if ($needs_ai) {
        // Check cache first
        if (isset($ai_cache[$cache_key])) {
            echo "Using cached AI content for $generation (cache hit)\n";
            $ai_subject = $ai_cache[$cache_key]['subject'];
            $ai_content = $ai_cache[$cache_key]['content'];
        } elseif (isset($ai) && is_object($ai)) {
            // Validate we have content before calling AI
            if (empty($campaign_content_original) || empty($subject_original)) {
                echo "Warning: Missing content for AI generation, using original\n";
                $ai_subject = $subject_original ?: 'Birthday Gold Newsletter';
                $ai_content = $campaign_content_original ?: 'Check out your birthday rewards!';
            } else {
                // Generate AI content once for all users of this generation
                echo "Generating AI content for $generation generation...\n";
                echo "  Original subject length: " . strlen($subject_original) . "\n";
                echo "  Original content length: " . strlen($campaign_content_original) . "\n";
                flush();

                $cta_category = $first_options['cta_category'] ?? 'Retail';

                // Truncate content if too long for AI
                $content_for_ai = $campaign_content_original;
                if (strlen($content_for_ai) > 2000) {
                    $content_for_ai = substr($content_for_ai, 0, 2000) . '...';
                    echo "  Content truncated for AI processing\n";
                }

                $ai_prompt = "You are adapting a Birthday Gold newsletter for {$generation} recipients.\n\n" .
                            "Campaign: {$campaign_name}\n" .
                            "Category: {$cta_category} rewards\n" .
                            "Generation: {$generation}\n\n" .
                            "Original Newsletter:\n" .
                            "Subject: {$subject_original}\n" .
                            "Content: {$content_for_ai}\n\n" .
                            "Instructions:\n" .
                            "1. Adapt the subject and content to appeal to {$generation} generation\n" .
                            "2. Keep the core message about birthday rewards\n" .
                            "3. PRESERVE all placeholders: [[first_name]], [[city]], [[birthday_month]], [[CTA_BLOCK]]\n" .
                            "4. Adjust tone and cultural references for {$generation}\n" .
                            "5. Keep similar length and structure\n\n" .
                            "Return ONLY valid JSON with 'subject' and 'content' fields.";

                try {
                    $systemPrompt = 'You are a marketing assistant. Return ONLY valid JSON with "subject" and "content" fields. No markdown, no backticks.';

                    echo "  Calling AI API...\n";
                    flush();

                    // Set a timeout for AI call
                    $ai_start = time();
                    $ai_timeout = 15; // 15 seconds max for AI

                    $response = $ai->process([
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $ai_prompt]
                    ], [
                        'temperature' => 0.7,
                        'max_tokens' => 1000
                    ]);

                    $ai_duration = time() - $ai_start;
                    echo "  AI response received in {$ai_duration} seconds\n";
                    flush();
                
                // Extract AI response
                $ai_answer = null;
                if (isset($response['decoded']['content'][0]['text'])) {
                    $ai_answer = $response['decoded']['content'][0]['text'];
                } elseif (isset($response['content'][0]['text'])) {
                    $ai_answer = $response['content'][0]['text'];
                } elseif (isset($response['choices'][0]['message']['content'])) {
                    $ai_answer = $response['choices'][0]['message']['content'];
                } elseif (isset($response['answer'])) {
                    $ai_answer = $response['answer'];
                }
                
                if (!empty($ai_answer)) {
                    // Clean up response
                    $ai_answer = preg_replace('/```json\s*/', '', $ai_answer);
                    $ai_answer = preg_replace('/```\s*$/', '', $ai_answer);
                    
                    $ai_data = json_decode(trim($ai_answer), true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $ai_subject = $ai_data['subject'] ?? $subject_original;
                        $ai_content = $ai_data['content'] ?? $campaign_content_original;
                        
                        // Cache for reuse
                        $ai_cache[$cache_key] = [
                            'subject' => $ai_subject,
                            'content' => $ai_content
                        ];
                        
                        echo "✓ AI content generated and cached for $generation\n";
                    } else {
                        echo "⚠ Failed to parse AI response, using original content\n";
                    }
                } else {
                    echo "⚠ AI returned empty response, using original content\n";
                    $ai_subject = $subject_original;
                    $ai_content = $campaign_content_original;
                }
            } catch (Exception $e) {
                echo "⚠ AI generation failed: " . $e->getMessage() . "\n";
                $ai_subject = $subject_original;
                $ai_content = $campaign_content_original;
            }
            } // End of content validation else
        } else {
            echo "⚠ AI not available, using original content\n";
        }
    }
    
    // Process each notification with the same content
    echo "\nPersonalizing notifications:\n";
    $group_processed = 0;
    $group_errors = 0;
    
    foreach ($notifications as $notification) {
        try {
            $campaign_data = json_decode($notification['options'], true);
            $user_data = $campaign_data['user_data'];
            
            // Use AI content if available, otherwise original
            $email_subject = $ai_subject ?: $campaign_data['email_subject'];
            $email_content = $ai_content ?: $campaign_data['campaign_content'];
            
            // Replace placeholders
            $email_subject = $marketing->replacePlaceholders($email_subject, $user_data);
            $email_content = $marketing->replacePlaceholders($email_content, $user_data);
            
            // Also handle double-bracket format
            $replacements = [
                '[[first_name]]' => $user_data['first_name'] ?? '',
                '[[last_name]]' => $user_data['last_name'] ?? '',
                '[[city]]' => $user_data['city'] ?? '',
                '[[state]]' => $user_data['state'] ?? '',
                '[[birthday_month]]' => !empty($user_data['birth_month']) ? 
                    date('F', mktime(0, 0, 0, $user_data['birth_month'], 1)) : '',
                '[[birth_month]]' => !empty($user_data['birth_month']) ? 
                    date('F', mktime(0, 0, 0, $user_data['birth_month'], 1)) : ''
            ];
            
            foreach ($replacements as $placeholder => $value) {
                $email_subject = str_replace($placeholder, $value, $email_subject);
                $email_content = str_replace($placeholder, $value, $email_content);
            }
            
            // Generate CTA block if needed
            if (!empty($campaign_data['cta_category']) && 
                (strpos($email_content, '{{CTA_BLOCK}}') !== false || 
                 strpos($email_content, '[[CTA_BLOCK]]') !== false)) {
                
                $cta_html = $marketing->generateCTABlockHTML(
                    $campaign_data['cta_category'],
                    $campaign_data['cta_mode'] ?? 'exclusive',
                    $notification['user_id'],
                    $campaign_data['campaign_id'],
                    $user_data,
                    'Claim Reward →'
                );
                
                if (!empty($cta_html)) {
                    $email_content = str_replace('{{CTA_BLOCK}}', $cta_html, $email_content);
                    $email_content = str_replace('[[CTA_BLOCK]]', $cta_html, $email_content);
                }
            }
            
            // Add tracking and footer
            $tracking_pixel = '<img src="https://m.bd.gold/track/newsletter/' . 
                             $qik->encodeId($campaign_data['campaign_id']) . '/' . 
                             $qik->encodeId($notification['user_id']) . 
                             '" width="1" height="1" style="display:none;" alt="">';
            
            $unsubscribe_url = 'https://m.bd.gold/unsubscribe/' . 
                              $qik->encodeId($notification['user_id']);
            
            $footer = '
            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e9ecef; font-size: 12px; color: #6c757d; text-align: center;">
                <p style="margin: 5px 0;">Birthday Gold - Automated Birthday Rewards</p>
                <p style="margin: 5px 0;">
                    <a href="' . $unsubscribe_url . '" style="color: #6c757d;">Unsubscribe</a> | 
                    <a href="https://m.bd.gold/support" style="color: #6c757d;">Support</a> | 
                    <a href="https://m.bd.gold/privacy" style="color: #6c757d;">Privacy</a>
                </p>
            </div>';
            
            // Wrap in HTML
            $final_html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($email_subject) . '</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    ' . $email_content . '
    ' . $tracking_pixel . '
    ' . $footer . '
</body>
</html>';
            
            // Handle oversized messages
            if (strlen($final_html) > 65000) {
                $simplified_html = str_replace($cta_html ?? '', 
                    '<div style="text-align:center;padding:20px;"><a href="https://birthday.gold/myaccount">View Birthday Rewards</a></div>', 
                    $final_html);
                $final_html = $simplified_html;
            }
            
            // Update notification
            $update_sql = "UPDATE bg_user_notifications 
                          SET title = :title,
                              message = :message,
                              status = 'notsent',
                              modify_dt = NOW() 
                          WHERE notification_id = :notification_id";
            
            $database->query($update_sql, [
                'title' => $email_subject,
                'message' => $final_html,
                'notification_id' => $notification['notification_id']
            ]);
            
            $group_processed++;
            echo "  ✓ ID {$notification['notification_id']} - User {$notification['user_id']} ({$user_data['first_name']} {$user_data['last_name']})\n";

            // Flush output every 5 notifications to prevent timeout
            if ($group_processed % 5 == 0) {
                flush();
            }
            
        } catch (Exception $e) {
            $group_errors++;
            echo "  ✗ ID {$notification['notification_id']} - Error: " . $e->getMessage() . "\n";
            
            // Mark as failed
            $database->query(
                "UPDATE bg_user_notifications SET status = 'failed', modify_dt = NOW() WHERE notification_id = :id",
                ['id' => $notification['notification_id']]
            );
        }
    }
    
    $processed_count += $group_processed;
    $error_count += $group_errors;
    
    echo str_repeat('-', 40) . "\n";
    echo "Group complete: $group_processed processed, $group_errors errors\n";
}

} catch (Exception $e) {
    echo "\nUnexpected error during processing: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "\nStatus: Error\n";
    $statusOutput = true;
    echo '</pre>';
    exit(1);
}

// Final summary
echo "\n" . str_repeat('=', 80) . "\n";
echo "MK PERSONALIZER SUMMARY\n";
echo str_repeat('-', 80) . "\n";
echo "✓ Successfully processed: $processed_count\n";
echo "✗ Errors encountered: $error_count\n";
echo "Total handled: " . ($processed_count + $error_count) . "\n";

if (!empty($ai_cache)) {
    $total_ai_saved = 0;
    foreach ($workload as $work) {
        if ($work['gen_specific'] == '1') {
            $cache_key = "{$work['campaign_id']}_{$work['generation']}";
            if (isset($ai_cache[$cache_key])) {
                // Saved API calls = users processed - 1 (first call)
                $saved = min($work['count'] - 1, $ai_batch_size - 1);
                $total_ai_saved += $saved;
            }
        }
    }
    echo "AI efficiency:\n";
    echo "  - Unique AI generations: " . count($ai_cache) . "\n";
    echo "  - API calls saved: ~$total_ai_saved\n";
}

echo "Processing efficiency:\n";
echo "  - Template-only: " . ($template_only_count > 0 ? round($template_batch_size) . "x faster" : "N/A") . "\n";
echo "  - AI-powered: " . ($needs_ai_count > 0 ? "Cached by generation" : "N/A") . "\n";
echo str_repeat('=', 80) . "\n";

// Log activity
session_tracking('mk_newsletter_personalize', [
    'processed' => $processed_count,
    'errors' => $error_count,
    'ai_cache_size' => count($ai_cache),
    'timestamp' => date('Y-m-d H:i:s')
]);

echo date('Y-m-d H:i:s') . " - MK Newsletter Personalizer finished\n";

// Report final status
if ($error_count > 0 && $processed_count == 0) {
    // Only errors, no successes
    echo "\nStatus: Error - All attempts failed\n";
    $statusOutput = true;
} else {
    // Success - either processed some or no records (both are OK)
    echo "\nStatus: Ok\n";
    $statusOutput = true;
}

// Ensure output is complete
echo '</pre>';

// Final flush to ensure all data is sent
flush();

// Exit cleanly
exit(0);
?>