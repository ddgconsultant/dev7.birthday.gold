<?php
// mail-goldie-regenerate.php - Regenerate summary for a specific date
$addClasses[] = 'mail';
$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

header('Content-Type: application/json');

// Check if user is logged in
$uid = $current_user_data['user_id'] ?? 0;
if (!$uid) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Check CSRF
if (!$app->formposted()) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$date = $input['date'] ?? '';

// Validate date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'error' => 'Invalid date format']);
    exit;
}

// Check if admin/dev for non-own accounts
$is_admin_dev = $account->checkrole('admin') || $mode === 'dev';
$target_uid = $uid;

// If viewing another user's mail (admin only)
if ($is_admin_dev && !empty($input['target_uid'])) {
    $target_uid = intval($input['target_uid']);
}

try {
    // Delete existing summary for this date
    $sql = "DELETE FROM bg_user_message_summaries 
            WHERE user_id = :user_id 
            AND summary_date = :date 
            AND summary_type = 'daily'";
    
    $stmt = $database->query($sql, [
        'user_id' => $target_uid,
        'date' => $date
    ]);
    
    // Get messages for this specific date
    $messages_results = $mail->getMessagesForAI($target_uid, $date, $date);
    $messages = $messages_results['messages'] ?? [];
    
    if (empty($messages)) {
        echo json_encode([
            'success' => false, 
            'error' => 'No messages found for this date'
        ]);
        exit;
    }
    
    // Generate new summary using AI
    if (isset($ai)) {
        $ai->setEngine('anthropic_goldie', 'text');
        
        // Collect company info and message details
        $companies = [];
        $message_texts = [];
        $message_ids = [];
        
        foreach ($messages as $message) {
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
                        :companies, :ai_summary, :offers, 'completed', 'admin_regenerate')";
        
        $params = [
            'user_id' => $target_uid,
            'summary_date' => $date,
            'message_count' => count($messages),
            'message_ids' => json_encode($message_ids),
            'companies' => json_encode(array_keys($companies)),
            'ai_summary' => $summary_text,
            'offers' => json_encode($offers)
        ];
        
        $database->query($sql, $params);
        
        // Return success with the new summary
        echo json_encode([
            'success' => true,
            'summary' => $summary_text,
            'offers' => $offers,
            'messageCount' => count($messages),
            'companies' => array_values($companies)
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'AI service not available'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Mail Goldie Regenerate Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to regenerate summary: ' . $e->getMessage()
    ]);
}
?>