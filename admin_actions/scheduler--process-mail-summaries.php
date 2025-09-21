<?php
// scheduler--process-mail-summaries.php - Batch process mail summaries for all users
$addClasses[] = 'mail';
$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// This script should be run via cron at night (e.g., 2 AM)
// Example cron: 0 2 * * * php /path/to/admin_actions/scheduler--process-mail-summaries.php

echo "Starting mail summary batch processing at " . date('Y-m-d H:i:s') . "\n";

// Configuration
$BATCH_SIZE = 50; // Process 50 users at a time
$DAYS_TO_PROCESS = 7; // Process last 7 days
$MAX_MESSAGES_PER_DAY = 20; // Skip days with too many messages to avoid AI overload

// Get users who have received messages in the last 30 days
$sql = "SELECT DISTINCT m.user_id, u.email 
        FROM messages m
        JOIN bg_users u ON m.user_id = u.user_id
        WHERE m.create_dt >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND m.processstatus != 'delete'
        ORDER BY m.user_id";

$stmt = $database->query($sql);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_users = count($users);

echo "Found {$total_users} users with recent messages\n";

// Process users in batches
$processed_users = 0;
$total_summaries = 0;
$errors = 0;

// Calculate date range
$end_date = new DateTime();
$start_date = new DateTime();
$start_date->modify("-{$DAYS_TO_PROCESS} days");

foreach ($users as $user) {
    $processed_users++;
    $user_id = $user['user_id'];
    
    echo "\nProcessing user {$user_id} ({$processed_users}/{$total_users})...\n";
    
    try {
        // Get messages for this user
        $messages_results = $mail->getMessagesForAI($user_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d'));
        $messages = $messages_results['messages'] ?? [];
        
        if (empty($messages)) {
            echo "  No messages found for user {$user_id}\n";
            continue;
        }
        
        // Group messages by day
        $messages_by_day = [];
        foreach ($messages as $message) {
            $date = date('Y-m-d', strtotime($message['create_dt']));
            if (!isset($messages_by_day[$date])) {
                $messages_by_day[$date] = [];
            }
            $messages_by_day[$date][] = $message;
        }
        
        // Process each day
        foreach ($messages_by_day as $date => $day_messages) {
            // Skip if too many messages (to control costs)
            if (count($day_messages) > $MAX_MESSAGES_PER_DAY) {
                echo "  Skipping {$date} - too many messages (" . count($day_messages) . ")\n";
                continue;
            }
            
            // Check if summary already exists
            $stmt = $database->query(
                "SELECT summary_id FROM bg_user_message_summaries 
                 WHERE user_id = :user_id AND summary_date = :date AND summary_type = 'daily'",
                ['user_id' => $user_id, 'date' => $date]
            );
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                echo "  Summary already exists for {$date}\n";
                continue;
            }
            
            echo "  Processing {$date} (" . count($day_messages) . " messages)...\n";
            
            // Generate summary using AI
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
                    
                    $body_text = strip_tags($message['body'] ?? '');
                    $body_preview = substr($body_text, 0, 400);
                    
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
                                    'offer' => trim($offer_match[2])
                                ];
                            }
                        }
                    }
                    
                    if (empty($summary_text)) {
                        $summary_text = $ai_response;
                    }
                    
                    // Store the summary
                    $sql = "INSERT INTO bg_user_message_summaries 
                            (user_id, summary_date, summary_type, message_count, message_ids, 
                             companies_included, ai_summary, offer_details, processing_status, processed_by)
                            VALUES (:user_id, :summary_date, 'daily', :message_count, :message_ids,
                                    :companies, :ai_summary, :offers, 'completed', 'batch')";
                    
                    $params = [
                        'user_id' => $user_id,
                        'summary_date' => $date,
                        'message_count' => count($day_messages),
                        'message_ids' => json_encode($message_ids),
                        'companies' => json_encode(array_keys($companies)),
                        'ai_summary' => $summary_text,
                        'offers' => json_encode($offers)
                    ];
                    
                    $database->query($sql, $params);
                    $total_summaries++;
                    
                    echo "    ✓ Summary created\n";
                    
                    // Rate limiting
                    usleep(1000000); // 1 second between AI calls
                    
                } catch (Exception $e) {
                    $errors++;
                    echo "    ✗ Error: " . $e->getMessage() . "\n";
                    
                    // Log the error
                    $sql = "INSERT INTO bg_user_message_summaries 
                            (user_id, summary_date, summary_type, message_count, processing_status, processing_error)
                            VALUES (:user_id, :summary_date, 'daily', :message_count, 'failed', :error)";
                    
                    $params = [
                        'user_id' => $user_id,
                        'summary_date' => $date,
                        'message_count' => count($day_messages),
                        'error' => $e->getMessage()
                    ];
                    
                    $database->query($sql, $params);
                }
            }
        }
        
    } catch (Exception $e) {
        echo "  Error processing user: " . $e->getMessage() . "\n";
        $errors++;
    }
    
    // Batch delay to avoid overload
    if ($processed_users % $BATCH_SIZE == 0) {
        echo "\nPausing between batches...\n";
        sleep(10);
    }
}

// Summary
echo "\n\nBatch processing complete!\n";
echo "Users processed: {$processed_users}\n";
echo "Summaries created: {$total_summaries}\n";
echo "Errors: {$errors}\n";
echo "Completed at " . date('Y-m-d H:i:s') . "\n";

// Log to session tracking
session_tracking('mail-summaries-batch', [
    'users_processed' => $processed_users,
    'summaries_created' => $total_summaries,
    'errors' => $errors,
    'date_range' => $start_date->format('Y-m-d') . ' to ' . $end_date->format('Y-m-d')
]);

exit(0);
?>