<?php
/**
 * AI Assist for Contact Message Reply
 * Uses Claude AI to generate a helpful reply draft
 */

// Start output buffering to catch any stray output
ob_start();

// Load AI class
$addClasses[] = 'ai';

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Clean any output from included files
ob_clean();

// Load AI class if not already loaded
if (!class_exists('AI')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.ai.php');
}

// Load AI configuration
$config_ai_path = $dir['configs'] . '/config-ai.inc';
if (file_exists($config_ai_path)) {
    $config_ai = file_get_contents($config_ai_path);
    $sitesettings_ai = parse_ini_string($config_ai, true);
} else {
    // Fallback to empty config
    $sitesettings_ai = ['ai' => []];
}

header('Content-Type: application/json');

// Admin only access
if (!$account->isadmin()) {
    error_log('AI Assist - Admin check failed. isadmin(): ' . ($account->isadmin() ? 'true' : 'false'));
    echo json_encode(['success' => false, 'error' => 'Unauthorized access - Admin access required']);
    exit;
}

error_log('AI Assist - Admin check passed');

// Get POST data
$contact_message_id = $_POST['contact_message_id'] ?? null;
$current_draft = $_POST['current_draft'] ?? '';

error_log('AI Assist - Contact Message ID: ' . $contact_message_id);
error_log('AI Assist - Current draft length: ' . strlen($current_draft));

// Validate required fields
if (!$contact_message_id) {
    error_log('AI Assist - ERROR: Missing contact message ID');
    echo json_encode(['success' => false, 'error' => 'Missing contact message ID']);
    exit;
}

try {
    // Get original contact message data
    $original_sql = "SELECT tracking_data FROM bg_sessiontracking WHERE id = :id";
    $original = $database->getrow($original_sql, ['id' => $contact_message_id]);

    if (!$original || empty($original['tracking_data'])) {
        echo json_encode(['success' => false, 'error' => 'Contact message not found']);
        exit;
    }

    $original_data = json_decode($original['tracking_data'], true);
    if (!$original_data || !is_array($original_data)) {
        echo json_encode(['success' => false, 'error' => 'Unable to parse contact message data']);
        exit;
    }

    // Build AI prompt
    $prompt = "You are a helpful customer service assistant for Birthday.Gold, a service that helps people remember and celebrate birthdays.\n\n";
    $prompt .= "A customer sent us this contact form message:\n\n";

    if (!empty($original_data['name'])) {
        $prompt .= "Name: " . $original_data['name'] . "\n";
    }
    if (!empty($original_data['email'])) {
        $prompt .= "Email: " . $original_data['email'] . "\n";
    }
    if (!empty($original_data['subject'])) {
        $prompt .= "Subject: " . $original_data['subject'] . "\n";
    }
    if (!empty($original_data['message_preview'])) {
        $prompt .= "\nMessage:\n" . $original_data['message_preview'] . "\n";
    }

    $prompt .= "\nPlease draft a professional, friendly, and helpful reply to this customer. ";
    $prompt .= "Be empathetic, address their concerns, and provide useful information or next steps. ";
    $prompt .= "Keep the tone warm but professional. ";
    $prompt .= "Start with an appropriate greeting and end with a polite closing.\n\n";

    if (!empty($current_draft)) {
        $prompt .= "Here's the current draft I've started:\n" . $current_draft . "\n\n";
        $prompt .= "Please improve and complete this draft, maintaining the same tone and intent.\n";
    }

    // Use AI to generate response
    $ai = new AI($system, $sitesettings_ai);
    $ai->setEngine('anthropic_goldie', 'text');

    $response = $ai->process($prompt, [
        'temperature' => 0.7,
        'max_tokens' => 500
    ]);

    $normalized = $ai->getNormalizedResponse($response);

    if (!empty($normalized['content'])) {
        echo json_encode([
            'success' => true,
            'suggested_reply' => trim($normalized['content']),
            'tokens_used' => $normalized['usage']['total_tokens'] ?? 0
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'AI did not generate a response'
        ]);
    }

} catch (Exception $e) {
    error_log('Contact reply AI assist error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => 'AI assist failed: ' . $e->getMessage()
    ]);
}
