<?PHP
$addClasses[] = 'mail';
$addClasses[] = 'marketing'; 
$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set response header
header('Content-Type: application/json');

// Get POST data
$campaign_title = isset($_POST['campaign_title']) ? trim($_POST['campaign_title']) : '';
$cta_category = isset($_POST['cta_category']) ? trim($_POST['cta_category']) : '';
$send_date = isset($_POST['send_date']) ? trim($_POST['send_date']) : '';

// Validate inputs
if (empty($campaign_title) || empty($cta_category) || empty($send_date)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Format the send date nicely
$send_date_formatted = date('F j, Y', strtotime($send_date));
$month = date('F', strtotime($send_date));

// Check if AI class is available
if (!isset($ai)) {
    echo json_encode(['success' => false, 'message' => 'AI service not available']);
    exit;
}

// Set the AI engine to use Anthropic/Claude
$ai->setEngine('anthropic_goldie', 'text');

// Prepare the prompt for Claude
$prompt = "Create a newsletter email for Birthday Gold, a service that helps users get birthday rewards from businesses. 

Campaign Details:
- Campaign Title: {$campaign_title}
- Category Focus: {$cta_category}
- Send Date: {$send_date_formatted}

Requirements:
1. Create an engaging email subject line that includes personalization placeholders
2. Write an HTML email body that:
   - Has a friendly, conversational tone
   - Highlights birthday rewards related to the {$cta_category} category
   - Includes the [[CTA_BLOCK]] placeholder where brand recommendations will appear
   - Uses personalization placeholders naturally throughout
   - Is approximately 150-200 words
   - Includes a clear call-to-action

Available placeholders to use:
- [[first_name]] - User's first name
- [[city]] - User's city
- [[birthday_month]] - User's birthday month
- [[CTA_BLOCK]] - Where personalized brand recommendations will appear

Please format the response as JSON with 'subject' and 'body' fields. The body should be in HTML format with proper paragraph tags.";

// System prompt for the AI
$systemPrompt = 'You are a helpful marketing assistant creating newsletter content for Birthday Gold. Always respond with valid JSON containing subject and body fields only, no other text.';

// Make the AI request using the AI class
try {
    // Process with AI using the correct method signature
    $response = $ai->process([
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $prompt]
    ], [
        'temperature' => 0.7,
        'max_tokens' => 1000
    ]);
    
    // Check for errors in response
    if (isset($response['error'])) {
        echo json_encode(['success' => false, 'message' => 'AI Error: ' . $response['error']]);
        exit;
    }
    
    // The response should have the AI's answer
    $ai_answer = null;
    
    // Check for the decoded response structure (which is what we have)
    if (isset($response['decoded']['content'][0]['text'])) {
        // Anthropic format from decoded response
        $ai_answer = $response['decoded']['content'][0]['text'];
    } elseif (isset($response['content'][0]['text'])) {
        // Direct Anthropic format
        $ai_answer = $response['content'][0]['text'];
    } elseif (isset($response['choices'][0]['message']['content'])) {
        // OpenAI format
        $ai_answer = $response['choices'][0]['message']['content'];
    } elseif (isset($response['answer'])) {
        // Already normalized format
        $ai_answer = $response['answer'];
    }
    
    if (!$ai_answer) {
        // Debug output to understand response structure
        echo json_encode([
            'success' => false, 
            'message' => 'Could not extract answer from AI response',
            'debug' => $response
        ]);
        exit;
    }
    
    // Claude sometimes wraps JSON in markdown code blocks, so we need to extract it
    if (strpos($ai_answer, '```json') !== false) {
        // Extract JSON from markdown code block
        preg_match('/```json\s*(.*?)\s*```/s', $ai_answer, $matches);
        if (isset($matches[1])) {
            $ai_answer = $matches[1];
        }
    } elseif (strpos($ai_answer, '```') !== false) {
        // Extract from generic code block
        preg_match('/```\s*(.*?)\s*```/s', $ai_answer, $matches);
        if (isset($matches[1])) {
            $ai_answer = $matches[1];
        }
    }
    
    // Parse the AI response
    $ai_content = json_decode($ai_answer, true);
    
    // If JSON parsing failed, try to clean up the string
    if ($ai_content === null) {
        // Remove any remaining backticks or extra whitespace
        $ai_answer = trim($ai_answer, " \t\n\r\0\x0B`");
        $ai_content = json_decode($ai_answer, true);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'AI service error: ' . $e->getMessage()]);
    exit;
}

if (!isset($ai_content['subject']) || !isset($ai_content['body'])) {
    // Fallback to a default template if AI response is malformed
    $subject = "[[first_name]], Your {$month} Birthday Rewards Are Waiting!";
    
    $body = "<p>Hi [[first_name]],</p>
    
    <p>Your birthday month is coming up, and we want to make it extra special! We've handpicked some amazing {$cta_category} birthday rewards just for you in [[city]].</p>
    
    <p>Whether you're looking for a free birthday treat, exclusive discounts, or special perks, we've got you covered. Check out these fantastic offers from your favorite {$cta_category} brands:</p>
    
    [[CTA_BLOCK]]
    
    <p>Don't miss out on these birthday rewards! Click on any brand above to learn more and claim your birthday perks. Remember, [[first_name]], these offers are specially selected for [[birthday_month]] birthdays.</p>
    
    <p>Make your birthday month unforgettable with Birthday Gold!</p>
    
    <p>Best wishes,<br>
    The Birthday.Gold Team</p>";
} else {
    $subject = $ai_content['subject'];
    $body = $ai_content['body'];
}

// Log the AI generation for analytics (optional - only if table exists)
try {
    // Get current user ID from session/global variable
    $user_id = isset($current_user_data['user_id']) ? $current_user_data['user_id'] : 0;
    
    $log_sql = "INSERT INTO bg_ai_generations (user_id, generation_type, prompt, response, created_dt) 
                VALUES (:user_id, 'newsletter', :prompt, :response, NOW())";
    $database->query($log_sql, [
        'user_id' => $user_id,
        'prompt' => substr($prompt, 0, 1000),
        'response' => json_encode(['subject' => $subject, 'body' => $body])
    ]);
} catch (Exception $e) {
    // Silently fail if table doesn't exist - logging is optional
}

// Return success response
echo json_encode([
    'success' => true,
    'subject' => $subject,
    'body' => $body
]);
?>