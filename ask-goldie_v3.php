<?php
$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Debug session state at start
if ($mode === 'dev' && isset($_GET['debug_session'])) {
    echo '<pre>Session state at start:';
    echo "\nRate limit: " . json_encode($_SESSION['ask_goldie_rate_limit'] ?? 'not set');
    echo "\nConversations: " . json_encode(array_keys($_SESSION['ask_goldie_conversations'] ?? []));
    echo "\nConversation ID in session: " . ($_SESSION['ask_goldie_conversation_id'] ?? 'not set');
    echo '</pre>';
}

// Session-based rate limiting with flood detection
if (!isset($_SESSION['ask_goldie_rate_limit'])) {
    $_SESSION['ask_goldie_rate_limit'] = [
        'count' => 0,
        'reset_time' => time() + 3600, // 1 hour from now
        'last_request' => 0,
        'requests_30s' => [] // Track requests in last 30 seconds for flood detection
    ];
}

// Reset counter if hour has passed
if (time() > $_SESSION['ask_goldie_rate_limit']['reset_time']) {
    $_SESSION['ask_goldie_rate_limit'] = [
        'count' => 0,
        'reset_time' => time() + 3600,
        'last_request' => $_SESSION['ask_goldie_rate_limit']['last_request'] ?? 0,
        'requests_30s' => $_SESSION['ask_goldie_rate_limit']['requests_30s'] ?? []
    ];
    
    // Clear lockout since rate limit has reset
    if (isset($_SESSION['ask_goldie_lockout_until'])) {
        unset($_SESSION['ask_goldie_lockout_until']);
    }
}

// Clean up old flood detection entries (older than 30 seconds)
$_SESSION['ask_goldie_rate_limit']['requests_30s'] = array_filter(
    $_SESSION['ask_goldie_rate_limit']['requests_30s'] ?? [],
    function($timestamp) { return $timestamp > (time() - 30); }
);

$rateLimitData = $_SESSION['ask_goldie_rate_limit'];
$requireCaptcha = false; // Default: no captcha

// Check for flooding (more than 3 requests in 30 seconds)
if (count($rateLimitData['requests_30s']) >= 4) {
    $requireCaptcha = true;
}

// Initialize conversation ID (stored in session for all users)
if (!isset($_SESSION['ask_goldie_conversation_id'])) {
    if (!empty($current_user_data['user_id'])) {
        // For logged-in users, include user ID and unique ID
        $_SESSION['ask_goldie_conversation_id'] = 'user_' . $current_user_data['user_id'] . '_' . uniqid();
    } else {
        // For anonymous users
        $_SESSION['ask_goldie_conversation_id'] = 'anon_' . uniqid() . '_' . date('Ymd');
    }
}
$conversationId = $_SESSION['ask_goldie_conversation_id'];

// Option to start new conversation
if (isset($_GET['new']) && $_GET['new'] == 1) {
    // Clear ALL conversation history to start fresh
    if (isset($_SESSION['ask_goldie_conversations'])) {
        $_SESSION['ask_goldie_conversations'] = [];
    }
    
    // Generate new conversation ID and store in session
    if (!empty($current_user_data['user_id'])) {
        $newConversationId = 'user_' . $current_user_data['user_id'] . '_' . uniqid();
        $_SESSION['ask_goldie_conversation_id'] = $newConversationId;
    } else {
        $newConversationId = 'anon_' . uniqid() . '_' . date('Ymd');
        $_SESSION['ask_goldie_conversation_id'] = $newConversationId;
    }
    
    // Reset rate limit counters for new conversation
    $_SESSION['ask_goldie_rate_limit'] = [
        'count' => 0,
        'reset_time' => time() + 3600, // 1 hour from now
        'last_request' => 0,
        'requests_30s' => []
    ];
    
    // Track new conversation start
    session_tracking('ask-goldie-new-conversation', [
        'conversation_id' => $newConversationId,
        'user_id' => !empty($current_user_data['user_id']) ? $current_user_data['user_id'] : null,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    header('Location: /ask-goldie_v3');
    exit;
}

// Allow 10 questions per hour per session
if ($rateLimitData['count'] >= 10) {
    // Set lockout time in session (time when they can ask again)
    $_SESSION['ask_goldie_lockout_until'] = $_SESSION['ask_goldie_rate_limit']['reset_time'];
    
    // Calculate time remaining
    $timeRemaining = $_SESSION['ask_goldie_rate_limit']['reset_time'] - time();
    $minutesRemaining = ceil($timeRemaining / 60);
    
    $transferpagedata = array();
    $transferpagedata['message'] = '<div class="alert alert-warning"><i class="bi bi-hourglass-split"></i> You have reached the hourly limit of 10 questions. Please try again in ' . $qik->plural2($minutesRemaining, 'minute') . '.</div>';
    $transferpagedata['url'] = '/help';
    $system->endpostpage($transferpagedata);
}

#-------------------------------------------------------------------------------
# HANDLE QUESTION SUBMISSION
#-------------------------------------------------------------------------------
$question = '';
$answer = '';
$showAnswer = false;
$errorMessage = '';
$conversationHistory = []; // Initialize here, will load after processing

// Debug logging only in dev mode with debug parameter
if ($mode === 'dev' && isset($_GET['debug'])) {
    error_log('Ask Goldie v3 - REQUEST_METHOD: ' . ($_SERVER['REQUEST_METHOD'] ?? 'NOT SET'));
    error_log('Ask Goldie v3 - POST data: ' . json_encode($_POST));
    error_log('Ask Goldie v3 - Session CSRF token: ' . ($_SESSION['csrf_token'] ?? 'NOT SET'));
}

if (($formdata = $app->formposted())) {
    
    // Check 5-second rate limit
    $timeSinceLastRequest = time() - ($rateLimitData['last_request'] ?? 0);
    if ($timeSinceLastRequest < 5) {
        $errorMessage = 'Please wait ' . (5 - $timeSinceLastRequest) . ' seconds before asking another question.';
    } 
    // Validate captcha only if required (flooding detected)
    elseif ($requireCaptcha && !$app->validateCaptcha()) {
        $errorMessage = 'Please complete the security check.';
    } else {
        $question = isset($_POST['question']) ? trim($_POST['question']) : '';
        
        // Guardrail 1: Check question length (max 500 characters)
        if (strlen($question) > 500) {
            $errorMessage = 'Questions must be 500 characters or less.';
        } elseif (strlen($question) < 10) {
            $errorMessage = 'Please ask a more detailed question.';
        } else {
            // Guardrail 2: Check for prohibited content
            $prohibitedPatterns = [
                '/\b(password|secret|key|token|api|database|sql|injection|exploit|hack)\b/i',
                '/\b(infrastructure|server|config|configuration|env|environment)\b/i',
                '/\b(admin|administrator|root|sudo)\b/i',
                '/\b(credit card|ssn|social security)\b/i'
            ];
            
            $blocked = false;
            foreach ($prohibitedPatterns as $pattern) {
                if (preg_match($pattern, $question)) {
                    $blocked = true;
                    break;
                }
            }
            
            if ($blocked) {
                $errorMessage = 'Your question contains restricted topics. Please ask about Birthday Gold features, enrollment, rewards, or general service questions.';
            } else {
                try {
                    // Update rate limit and tracking
                    $_SESSION['ask_goldie_rate_limit']['count']++;
                    $_SESSION['ask_goldie_rate_limit']['last_request'] = time();
                    $_SESSION['ask_goldie_rate_limit']['requests_30s'][] = time();
                    
                    // Track the question before processing
                    $preRequestTracking = [
                        'action' => 'ask-goldie-question',
                        'question' => $question,
                        'question_length' => strlen($question),
                        'session_count' => $_SESSION['ask_goldie_rate_limit']['count'],
                        'captcha_required' => $requireCaptcha,
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                    session_tracking('ask-goldie-question', $preRequestTracking);
                    
                    // Prepare AI prompt with context and guardrails
                    $ai->setEngine('anthropic_goldie', 'text');
                    
                    // Get user first name if logged in
                    $firstName = '';
                    if (!empty($current_user_data['user_id'])) {
                        $firstName = $current_user_data['first_name'] ?? '';
                    }
                    
                    // Check if this is first message in conversation
                    $isFirstMessage = empty($conversationHistory);
                    
                    $systemPrompt = "You are Goldie, the friendly AI assistant for Birthday Gold. You help users understand how Birthday Gold works, answer questions about enrollment, rewards, features, and general service inquiries.
" . (!empty($firstName) ? "\nThe user name is $firstName. Address them by name occasionally to make the conversation more personal.\n" : '') . "
IMPORTANT RULES:
1. Only answer questions about Birthday Gold services, features, enrollment, rewards, pricing, and general help
2. Do NOT provide any technical details about infrastructure, databases, APIs, or implementation
3. Do NOT discuss security details, passwords, or authentication methods
4. Keep responses concise (under 200 words)
5. Be helpful and friendly" . (!empty($firstName) ? " and address $firstName by name when appropriate" : '') . "
6. If asked about technical/infrastructure details, politely redirect to contact support
7. Reference relevant pages when appropriate: /how-it-works, /pricing, /faq, /contact
8. " . ($isFirstMessage ? "This is the first message in the conversation. Greet the user warmly." : "This is a continuing conversation. Do NOT introduce yourself again. Just answer the question directly.") . "
9. Vary your responses - do not use the same greeting patterns repeatedly
10. At the end of your response, add a line break and then provide exactly 4 follow-up questions in this exact JSON format:
QUESTIONS_JSON: [\"Question 1?\", \"Question 2?\", \"Question 3?\", \"Question 4?\"]

Birthday Gold is a service that automatically enrolls users in birthday reward programs from various businesses.";

                    $userPrompt = "User Question: " . $question;
                    
                    // Process with AI (limited tokens for cost control)
                    $response = $ai->process([
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt]
                    ], [
                        'temperature' => 0.7,
                        'max_tokens' => 400 // Limit response length
                    ]);
                    
                    $normalizedResponse = $ai->getNormalizedResponse($response);
                    $answer = $normalizedResponse['content'];
                    
                    // Additional post-processing to ensure no sensitive info
                    $sensitivePatterns = [
                        '/\b\d{4,}\b/', // Remove long numbers
                        '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', // Remove emails
                        '/\bhttps?:\/\/(?!birthday\.gold|www\.birthday\.gold)[^\s]+/i' // Remove non-birthday.gold URLs
                    ];
                    
                    foreach ($sensitivePatterns as $pattern) {
                        $answer = preg_replace($pattern, '[removed]', $answer);
                    }
                    
                    // Extract follow-up questions from the response
                    $followUpQuestions = [];
                    if (preg_match('/QUESTIONS_JSON:\s*\[(.*?)\]/s', $answer, $matches)) {
                        // Parse the JSON array
                        $questionsJson = '[' . $matches[1] . ']';
                        $parsedQuestions = json_decode($questionsJson, true);
                        if (is_array($parsedQuestions) && count($parsedQuestions) > 0) {
                            $followUpQuestions = array_slice($parsedQuestions, 0, 4); // Ensure max 4 questions
                        }
                        
                        // Remove the JSON from the answer
                        $answer = preg_replace('/\s*QUESTIONS_JSON:\s*\[.*?\]/s', '', $answer);
                        $answer = trim($answer);
                    }
                    
                    // Track the complete Q&A session with full question and response
                    $qaTracking = [
                        'action' => 'ask-goldie-response',
                        'question' => $question,
                        'answer' => $answer,
                        'answer_length' => strlen($answer),
                        'usage' => $normalizedResponse['usage'] ?? [],
                        'engine' => $normalizedResponse['engine'] ?? 'anthropic_goldie',
                        'model' => $normalizedResponse['model'] ?? '',
                        'prompt_tokens' => $normalizedResponse['usage']['prompt_tokens'] ?? 0,
                        'completion_tokens' => $normalizedResponse['usage']['completion_tokens'] ?? 0,
                        'total_tokens' => $normalizedResponse['usage']['total_tokens'] ?? 0,
                        'processing_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? 0),
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                    session_tracking('ask-goldie-response', $qaTracking);
                    
                    // Load current conversation history to ensure we have the latest
                    if (isset($_SESSION['ask_goldie_conversations'][$conversationId])) {
                        $conversationHistory = $_SESSION['ask_goldie_conversations'][$conversationId];
                    } else {
                        $conversationHistory = [];
                    }
                    
                    // Add to conversation history
                    $conversationHistory[] = [
                        'question' => $question,
                        'answer' => $answer,
                        'timestamp' => time(),
                        'followUpQuestions' => $followUpQuestions
                    ];
                    
                    // Keep only last 20 exchanges
                    if (count($conversationHistory) > 20) {
                        array_shift($conversationHistory);
                    }
                    
                    // Save conversation to session with conversation ID
                    if (!isset($_SESSION['ask_goldie_conversations'])) {
                        $_SESSION['ask_goldie_conversations'] = [];
                    }
                    $_SESSION['ask_goldie_conversations'][$conversationId] = $conversationHistory;
                    
                    // Update the local conversation history variable to reflect the change
                    $conversationHistory = $_SESSION['ask_goldie_conversations'][$conversationId];
                    
                    // Also track in database for analytics
                    $conversationData = [
                        'conversation_id' => $conversationId,
                        'user_id' => !empty($current_user_data['user_id']) ? $current_user_data['user_id'] : null,
                        'message_count' => count($conversationHistory),
                        'last_updated' => date('Y-m-d H:i:s')
                    ];
                    session_tracking('ask-goldie-conversation', $conversationData);
                    
                    $showAnswer = true;
                    
                    
                } catch (Exception $e) {
                    error_log('Ask Goldie error: ' . $e->getMessage());
                    $errorMessage = 'Sorry, I could not process your question at this time. Please try again or contact support.';
                }
            }
        }
    }
}

// Load conversation history from session AFTER form processing
if (isset($_SESSION['ask_goldie_conversations'][$conversationId])) {
    $conversationHistory = $_SESSION['ask_goldie_conversations'][$conversationId];
    
    // Check if 30 minutes have elapsed since last response
    if (!empty($conversationHistory)) {
        $lastExchange = end($conversationHistory);
        $lastTimestamp = $lastExchange['timestamp'] ?? 0;
        $timeSinceLastExchange = time() - $lastTimestamp;
        
        // If more than 30 minutes (1800 seconds), clear the conversation
        if ($timeSinceLastExchange > 1800) {
            $conversationHistory = [];
            unset($_SESSION['ask_goldie_conversations'][$conversationId]);
        }
    }
} else {
    $conversationHistory = [];
}

// Debug logging
if ($mode === 'dev') {
    error_log('Ask Goldie Page Load Debug:');
    error_log('Conversation ID: ' . $conversationId);
    error_log('Conversation exists in session: ' . (isset($_SESSION['ask_goldie_conversations'][$conversationId]) ? 'Yes' : 'No'));
    error_log('Conversation history count: ' . count($conversationHistory));
    error_log('Form posted: ' . ($formdata ? 'Yes' : 'No'));
    error_log('Show answer: ' . ($showAnswer ? 'Yes' : 'No'));
}

// Generate dynamic quick questions based on conversation context
$quickQuestions = [];

if (!empty($conversationHistory)) {
    // Get the last exchange for context
    $lastExchange = end($conversationHistory);
    $lastAnswer = strtolower($lastExchange['answer'] ?? '');
    $lastQuestion = strtolower($lastExchange['question'] ?? '');
    
    // First, check if we have follow-up questions from the AI
    if (!empty($lastExchange['followUpQuestions']) && is_array($lastExchange['followUpQuestions'])) {
        $quickQuestions = $lastExchange['followUpQuestions'];
    } else {
        // Fallback to context-based generation if no AI questions
    
    // Context-based question generation
    if (strpos($lastAnswer, 'enroll') !== false || strpos($lastQuestion, 'enroll') !== false) {
        $quickQuestions[] = "How do I manage my current enrollments and see which businesses I'm enrolled in?";
        $quickQuestions[] = "Can I enroll family members in Birthday Gold programs?";
        $quickQuestions[] = "What happens if enrollment fails for a specific business?";
        $quickQuestions[] = "How many businesses can I enroll in with my current plan?";
    } elseif (strpos($lastAnswer, 'reward') !== false || strpos($lastQuestion, 'reward') !== false) {
        $quickQuestions[] = "How do I redeem my birthday rewards when they become available?";
        $quickQuestions[] = "What types of birthday rewards can I expect to receive?";
        $quickQuestions[] = "Can I save my rewards to use after my birthday month?";
        $quickQuestions[] = "How far in advance do birthday rewards typically arrive?";
    } elseif (strpos($lastAnswer, 'cost') !== false || strpos($lastAnswer, 'price') !== false || strpos($lastAnswer, 'plan') !== false) {
        $quickQuestions[] = "What is included in the different Birthday Gold subscription plans?";
        $quickQuestions[] = "Can I upgrade or downgrade my plan at any time?";
        $quickQuestions[] = "Are there any discounts for annual subscriptions?";
        $quickQuestions[] = "Is there a free trial period before I'm charged?";
    } elseif (strpos($lastAnswer, 'profile') !== false || strpos($lastAnswer, 'information') !== false) {
        $quickQuestions[] = "What personal information do I need to complete my profile?";
        $quickQuestions[] = "How is my personal information protected and secured?";
        $quickQuestions[] = "Can I update my birthday or other profile details later?";
        $quickQuestions[] = "Why do some businesses require additional information?";
    } elseif (strpos($lastAnswer, 'business') !== false || strpos($lastAnswer, 'partner') !== false) {
        $quickQuestions[] = "Which popular businesses and restaurants participate in Birthday Gold?";
        $quickQuestions[] = "How often are new partner businesses added to the platform?";
        $quickQuestions[] = "Can I suggest a business to be added to Birthday Gold?";
        $quickQuestions[] = "Are partner businesses available in my local area?";
    } else {
        // Contextual follow-ups based on conversation length
        if (count($conversationHistory) > 2) {
            $quickQuestions[] = "Can you show me a summary of everything we have discussed?";
            $quickQuestions[] = "I would like to know more about the specific features you mentioned";
            $quickQuestions[] = "What should I do next to get the most from Birthday Gold?";
            $quickQuestions[] = "Are there any tips for maximizing my birthday rewards?";
        } else {
            // Early conversation follow-ups
            $quickQuestions[] = "Tell me more about how the enrollment process works";
            $quickQuestions[] = "What makes Birthday Gold different from signing up myself?";
            $quickQuestions[] = "How quickly can I start receiving birthday rewards?";
            $quickQuestions[] = "What if I have questions about a specific enrollment?";
        }
    }
    } // Close the else from AI questions check
} else {
    // Default questions for new conversations
    $quickQuestions = [
        "How does Birthday Gold work and what are the main benefits?",
        "What birthday rewards and perks can I expect to receive?",
        "How much does Birthday Gold cost and what plans are available?",
        "How do I get started with Birthday Gold today?",
        "What businesses and restaurants participate in Birthday Gold?",
        "Is my personal information safe and secure with Birthday Gold?",
        "Can I add my family members to my Birthday Gold account?",
        "How do I track and redeem my birthday rewards?"
    ];
}

// Ensure we have at least 4 questions, add defaults if needed
if (count($quickQuestions) < 4) {
    $defaultQuestions = [
        "What else can Birthday Gold help me with?",
        "Tell me more about the benefits of Birthday Gold",
        "How can I get the most value from my membership?",
        "What should I know about Birthday Gold?"
    ];
    
    $needed = 4 - count($quickQuestions);
    $quickQuestions = array_merge($quickQuestions, array_slice($defaultQuestions, 0, $needed));
}

// Limit questions based on screen size
// We will handle the mobile limitation in JavaScript/CSS
$quickQuestions = array_slice($quickQuestions, 0, 8);

// Page styling
$additionalstyles = '
<style>
/* Ask Goldie Styles */
html {
    scroll-behavior: smooth;
}

.ask-goldie-container {
    max-width: 800px;
    margin: 0 auto;
}

.chat-interface {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    padding: 2rem;
    margin-bottom: 2rem;
}

.question-form textarea {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1rem;
    font-size: 1.1rem;
    resize: vertical;
    min-height: 100px;
    transition: border-color 0.3s ease;
}

.question-form textarea:focus {
    border-color: var(--bs-primary);
    outline: none;
}

.char-counter {
    text-align: right;
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.char-counter.warning {
    color: #dc3545;
}

.answer-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 2rem;
    border-left: 4px solid var(--bs-primary);
}

.answer-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    color: var(--bs-primary);
    font-weight: 600;
}

.answer-content {
    color: #212529;
    line-height: 1.6;
}

.example-questions {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
}

.example-question {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.example-question:hover {
    border-color: var(--bs-primary);
    transform: translateX(4px);
}

.rate-limit-notice {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

/* Robot Icon Animation */
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

@keyframes shadowPulse {
    0%, 100% { 
        transform: translateX(-50%) scaleX(1);
        opacity: 0.3;
    }
    50% { 
        transform: translateX(-50%) scaleX(0.8);
        opacity: 0.5;
    }
}

.floating-icon {
    animation: float 3s ease-in-out infinite;
     width: 100px !important;
}

.shadow-icon {
    animation: shadowPulse 3s ease-in-out infinite;
    opacity: 0.3;
    width: 100px !important;
}

/* Full Chat Interface Styles */
.chat-container {
    display: flex;
    flex-direction: column;
    background: white;
    border-radius: 12px 12px 0 0;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    overflow: hidden;
    margin-bottom: 0;
    border: 2px solid #dee2e6;
    border-bottom: none;
    /* No fixed height - will expand with flex */
}

/* Adjust container on smaller screens */
@media (max-width: 768px) {
    .chat-container {
        min-height: 400px;
    }
}

.chat-header {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    background: #fafbfc;
    scroll-behavior: smooth;
}

.chat-input-area {
    background: white;
    border-top: 2px solid #dee2e6;
    padding: 1rem;
    border-radius: 0;
}

.message {
    margin-bottom: 1rem;
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.message-user {
    display: flex;
    justify-content: flex-end;
}

.message-goldie {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.message-content {
    max-width: 70%;
    word-wrap: break-word;
}

.message-user .message-content {
    background: #007bff;
    color: white;
    padding: 0.75rem 1rem;
    border-radius: 18px 18px 4px 18px;
}

.message-goldie .message-content {
    background: white;
    border: 1px solid #dee2e6;
    padding: 0.75rem 1rem;
    border-radius: 4px 18px 18px 18px;
}

.message-goldie img {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e7f3ff;
    padding: 4px;
}

.message-timestamp {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.25rem;
    text-align: right;
}

/* Better contrast for user message timestamps */
.message-user .message-timestamp {
    color: rgba(255, 255, 255, 0.8);
}

/* Bold styling for latest messages */
.message.latest-message .message-content {
    font-weight: 600;
}

/* Also bold the last visible messages dynamically */
.chat-messages > .message:nth-last-child(2) .message-content,
.chat-messages > .message:nth-last-child(1) .message-content {
    font-weight: 600;
}

.chat-form {
    display: flex;
    gap: 0.5rem;
}

.chat-input {
    flex: 1;
    border: 2px solid #dee2e6;
    border-radius: 20px;
    padding: 0.75rem 1.25rem;
    font-size: 1rem;
    transition: border-color 0.3s ease, height 0.2s ease;
    resize: none;
    max-height: 200px;
    overflow-y: hidden;
    font-family: inherit;
    line-height: 1.5;
    min-height: 48px;
}

.chat-input:focus {
    outline: none;
    border-color: #007bff;
}

.chat-input:disabled {
    background-color: #f8f9fa;
    color: #6c757d;
    cursor: not-allowed;
}

.chat-submit {
    border-radius: 50%;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: #007bff;
    color: white;
    transition: all 0.3s ease;
}

.chat-submit:hover {
    background: #0056b3;
    transform: scale(1.05);
}

.chat-submit:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
}

.chat-submit.loading {
    background: #0056b3;
}

/* Visual feedback overlay with blur */
.sending-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 100;
    border-radius: 0 0 12px 12px;
}

.sending-overlay.show {
    display: flex;
}

.sending-message {
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.95rem;
    font-weight: 500;
}

.sending-message .spinner-border {
    color: #007bff;
    width: 1.25rem;
    height: 1.25rem;
}

/* Apply blur to form elements when overlay is shown */
.chat-input-area.processing .chat-form {
    filter: blur(2px);
    pointer-events: none;
}

.typing-indicator {
    display: none;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    margin-bottom: 1rem;
}

.typing-indicator.show {
    display: flex;
    animation: fadeIn 0.3s ease-in;
}

.typing-dots {
    display: flex;
    gap: 4px;
}

.typing-dot {
    width: 8px;
    height: 8px;
    background: #6c757d;
    border-radius: 50%;
    animation: typing 1.4s infinite;
}

.typing-dot:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-dot:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.7;
    }
    30% {
        transform: translateY(-10px);
        opacity: 1;
    }
}

.welcome-message {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
}

.quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
    margin-top: 1rem;
}

.quick-action {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    padding: 0.75rem 1.25rem;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: normal;
    text-align: left;
    max-width: 300px;
    line-height: 1.4;
}

.quick-action:hover {
    background: #f8f9fa;
    border-color: #007bff;
    transform: translateY(-2px);
}

/* User avatars */
.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    margin-left: 0.5rem;
}

/* Mobile responsiveness */
@media (max-width: 575px) {
    /* Hide avatars on mobile */
    .user-avatar,
    .message-goldie img {
        display: none;
    }
    
    /* Make conversation bubbles wider on mobile */
    .message-content {
        max-width: 85%;
    }
    
    /* Full width chat container on mobile */
    .chat-container {
        border-radius: 0;
        margin: 0 -12px; /* Negative margin to counteract container padding */
        border-left: none;
        border-right: none;
    }
    
    /* Adjust quick questions wrapper for mobile */
    .quick-questions-wrapper {
        border-radius: 0;
        margin: -2px -12px 1rem -12px;
        border-left: none;
        border-right: none;
    }
    
    .quick-questions-toggle {
        border-radius: 0;
    }
    
    /* Adjust chat messages padding */
    .chat-messages {
        padding: 1rem;
    }
    
    /* Keep quick questions text visible on mobile */
    .quick-questions-toggle {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
    }
    
    /* Adjust message styling for mobile */
    .message-goldie {
        gap: 0;
    }
    
    /* Make user messages align better without avatar */
    .message-user .message-content {
        margin-right: 0;
    }
    
    /* Reduce font size slightly on mobile */
    .message-content {
        font-size: 0.95rem;
    }
    
    /* Hide chat header content on mobile */
    .chat-header {
        display: none;
    }
    
    /* Adjust input area on mobile */
    .chat-input-area {
        padding: 0.75rem;
    }
    
    /* Smaller send button on mobile */
    .chat-submit {
        width: 44px;
        height: 44px;
    }
}

/* Main container adjustments */
.container.py-4 {
    padding-top: 2rem !important;
    padding-bottom: 1rem !important;
}

/* Ensure chat messages take available space */
.chat-messages {
    min-height: 300px;
}

/* Collapsible quick questions - attached tray style */
.quick-questions-wrapper {
    background: #e7f3ff;
    border: 2px solid #b8daff;
    border-top: none;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 4px 12px rgba(0,123,255,0.15);
    margin-top: -2px; /* Overlap with chat container */
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    position: relative;
}

.quick-questions-wrapper::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: #b8daff;
}

.quick-questions-wrapper.collapsed {
    box-shadow: 0 2px 8px rgba(0,123,255,0.1);
}

.quick-questions-wrapper.collapsed .quick-questions-content {
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    padding-top: 0;
    padding-bottom: 0;
}

/* Animation classes for smooth opening */
.quick-questions-wrapper.expanding {
    animation: trayExpand 0.4s ease-out forwards;
}

@keyframes trayExpand {
    from {
        transform: translateY(-10px);
        opacity: 0.8;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}


.quick-questions-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem 1rem 0.75rem;
    cursor: pointer;
    background: #d4e9ff;
    border: none;
    width: 100%;
    font-size: 0.9rem;
    font-weight: 600;
    color: #0056b3;
    border-radius: 0 0 12px 12px;
    position: relative;
    min-height: 48px;
}

.quick-questions-toggle:hover {
    background: #c4e1ff;
    color: #004494;
}

.quick-questions-toggle i {
    transition: transform 0.3s ease;
    margin-right: 0.5rem;
    font-size: 0.6rem;
    vertical-align: middle;
}

.quick-questions-wrapper.collapsed .quick-questions-toggle i {
    transform: rotate(180deg);
}

/* Double line handle */
.quick-questions-toggle::before,
.quick-questions-toggle::after {
    content: "";
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 36px;
    height: 2px;
    background: #a8d0ff;
    border-radius: 1px;
}

.quick-questions-toggle::before {
    top: 8px;
}

.quick-questions-toggle::after {
    top: 13px;
}

.quick-questions-content {
    padding: 1rem 1.5rem;
    background: #f0f7ff;
    border-top: 1px solid #d4e9ff;
    max-height: 500px; /* Adjust based on content */
    overflow: hidden;
    opacity: 1;
    transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), 
                opacity 0.3s ease, 
                padding 0.3s ease;
}

.quick-question-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
}

.quick-pill {
    background: white;
    border: 1px solid #dee2e6;
    color: #495057;
    padding: 0.625rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: normal;
    display: inline-block;
    margin: 0.25rem;
    max-width: calc(100% - 0.5rem);
    text-align: left;
    line-height: 1.4;
}

.quick-pill:hover {
    background: #007bff;
    color: white;
    border-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,123,255,0.25);
}

.quick-pill i {
    opacity: 0.7;
}

/* Help links styling */
.help-links {
    font-size: 0.875rem; /* 14px */
}

.help-links a {
    font-size: 0.875rem; /* 14px */
}

/* Hide questions after the 4th one on mobile screens */
@media (max-width: 575px) {
    .quick-pill:nth-child(n+5) {
        display: none;
    }
    
    /* Hide initial sample questions in welcome message on mobile */
    .welcome-message .quick-actions {
        display: none;
    }
    
    /* Further reduce help links font size on mobile */
    .help-links {
        font-size: 0.75rem; /* 12px */
    }
    
    .help-links a {
        font-size: 0.75rem; /* 12px */
    }
    
    /* Fix avatar and shadow alignment on mobile */
    .floating-icon {
        position: relative !important;
        left: auto !important;
        right: 0 !important;
        width: 80px !important; /* 20% smaller than 200px */
    }
    
    .shadow-icon {
        left: 50% !important;
        transform: translateX(-50%) !important;
       
        width: 80px !important; /* 20% smaller than 200px */
    }
    
    /* Adjust container height for smaller avatar */
    .content-header-dark .position-relative {
        height: 96px !important; /* 20% smaller than 120px */
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div style="display: flex; flex-direction: column; min-height: calc(100vh - 60px);">
<!-- Hero Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-auto d-flex align-items-center">
                <div class="text-end me-3 position-relative" style="width: 100px; height: 120px;">
                    <img src="/public/images/logo/goldie-avatar_200.png" alt="Goldie" class="floating-icon" style="position: absolute; top: 0; left: 0; z-index: 2;">
                    <img src="/public/images/logo/goldie-shadow_200.png" alt="" class="shadow-icon" style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); z-index: 1;">
                </div>
                <div>
                    <h1 class="mb-2">Ask Goldie</h1>
                    <p class="lead mb-0">Get answers about Birthday.Gold from our AI assistant</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-4" style="flex: 1; display: flex; flex-direction: column;">
    
    <div class="chat-container" style="flex: 1;">
        <!-- Chat Header -->
        <div class="chat-header">
            <div class="d-flex align-items-center gap-2">
                <img src="/public/images/logo/goldie_72.png" alt="Goldie" style="height: 40px;">
                <div>
                    <h5 class="mb-0">Chat with Goldie</h5>
                    <small class="text-muted">
                        <?php if ($rateLimitData['count'] > 0): ?>
                            <?php echo 10 - $rateLimitData['count']; ?> questions left this hour
                        <?php else: ?>
                            AI Assistant for Birthday.Gold
                        <?php endif; ?>
                    </small>
                </div>
            </div>
            <a href="/ask-goldie_v3?new=1" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-clockwise"></i> New Chat
            </a>
        </div>
        
        <!-- Chat Messages Area -->
        <div class="chat-messages" id="chatMessages">
            <?php if (empty($conversationHistory)): ?>
                <!-- Welcome Message for New Conversations -->
                <div class="welcome-message">
                    <img src="/public/images/logo/goldie_72.png" alt="Goldie" style="height: 72px; opacity: 0.5;" class="mb-3">
                    <h6>Welcome! I'm Goldie, your Birthday.Gold assistant.</h6>
                    <p class="mb-0 caption">Ask me anything about Birthday.Gold!</p>
                    <div class="quick-actions">
                        <?php 
                        // Show first 4 questions as quick actions in welcome message
                        $welcomeQuestions = array_slice($quickQuestions, 0, 4);
                        foreach ($welcomeQuestions as $question): 
                        ?>
                        <button class="quick-action" onclick="quickQuestion(<?php echo htmlspecialchars(json_encode($question), ENT_QUOTES); ?>)">
                            <?php echo htmlspecialchars($question); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Display Conversation History -->
                <?php 
                // Get user avatar if logged in
                $userAvatar = '/public/images/defaultavatar.png';
                if (!empty($current_user_data['avatar'])) {
                    $userAvatar = '/' . $current_user_data['avatar'];
                }
                
                $totalMessages = count($conversationHistory);
                foreach ($conversationHistory as $index => $item): 
                    $isLatest = ($index === $totalMessages - 1);
                ?>
                    <div class="message message-user<?php echo $isLatest ? ' latest-message' : ''; ?>">
                        <div class="message-content">
                            <?php echo htmlspecialchars($item['question']); ?>
                            <div class="message-timestamp">
                                <?php echo date('g:i a', $item['timestamp']); ?>
                            </div>
                        </div>
                        <?php if (!empty($current_user_data['user_id'])): ?>
                        <img src="<?php echo $userAvatar; ?>" alt="You" class="user-avatar">
                        <?php endif; ?>
                    </div>
                    <div class="message message-goldie<?php echo $isLatest ? ' latest-message' : ''; ?>">
                        <img src="/public/images/logo/goldie_72.png" alt="Goldie">
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($item['answer'])); ?>
                            <div class="message-timestamp">
                                <?php echo date('g:i a', $item['timestamp']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Typing Indicator -->
            <div class="typing-indicator" id="typingIndicator">
                <img src="/public/images/logo/goldie_72.png" alt="Goldie" style="width: 32px; height: 32px;">
                <div class="typing-dots">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        </div>
        
        <!-- Chat Input Area -->
        <div class="chat-input-area position-relative">
            <!-- Sending overlay -->
            <div class="sending-overlay" id="sendingOverlay">
                <div class="sending-message">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Processing...</span>
                    </div>
                    <span>Processing...</span>
                </div>
            </div>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger alert-sm mb-2">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="/ask-goldie_v3" class="chat-form" id="chatForm">
                <?php echo $display->inputcsrf_token(); ?>
                <textarea 
                    name="question" 
                    class="chat-input" 
                    placeholder="Type your question... (Shift+Enter to send)"
                    maxlength="500"
                    required
                    autocomplete="off"
                    id="chatInput"
                    rows="1"
                ><?php echo (isset($_POST['question']) && !$showAnswer) ? htmlspecialchars($_POST['question']) : ''; ?></textarea>
                <?php if ($requireCaptcha): ?>
                    <!-- Hidden captcha that shows when needed -->
                    <div id="captchaModal" style="display: none;">
                        <?php echo $app->generateCaptcha(); ?>
                    </div>
                <?php endif; ?>
                <button type="submit" class="chat-submit btn btn-primary" id="submitBtn" name="submit" value="1">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Collapsible Quick Questions -->
    <div class="quick-questions-wrapper collapsed" id="quickQuestionsWrapper">
        <button class="quick-questions-toggle" onclick="toggleQuickQuestions()">
            <i class="bi bi-triangle-fill me-2"></i>
            <span>Quick Questions</span>
        </button>
    <div class="quick-questions-content">
        <div class="quick-question-pills">
            <?php 
            // Icon mapping based on question content
            $iconMap = [
                'enroll' => 'bi-card-checklist',
                'manage' => 'bi-gear',
                'reward' => 'bi-gift',
                'redeem' => 'bi-ticket-perforated',
                'cost' => 'bi-tag',
                'price' => 'bi-tag',
                'plan' => 'bi-list-check',
                'personal' => 'bi-shield-check',
                'information' => 'bi-person-check',
                'business' => 'bi-shop',
                'partner' => 'bi-building',
                'family' => 'bi-people',
                'start' => 'bi-rocket',
                'track' => 'bi-graph-up',
                'summary' => 'bi-journal-text',
                'tips' => 'bi-lightbulb'
            ];
            
            foreach ($quickQuestions as $question): 
                // Determine icon based on question content
                $icon = 'bi-question-circle'; // default
                $lowerQuestion = strtolower($question);
                foreach ($iconMap as $keyword => $iconClass) {
                    if (strpos($lowerQuestion, $keyword) !== false) {
                        $icon = $iconClass;
                        break;
                    }
                }
            ?>
            <div class="quick-pill" onclick="quickQuestion(<?php echo htmlspecialchars(json_encode($question), ENT_QUOTES); ?>)">
                <i class="bi <?php echo $icon; ?> me-1"></i><?php echo htmlspecialchars($question); ?>
            </div>
            <?php endforeach; ?>
        </div>
        </div>
    </div>
    
    <!-- Help Links -->
    <div class="text-center mt-3 help-links">
        <a href="/help" class="text-muted me-3">
            <i class="bi bi-arrow-left"></i> Help Center
        </a>
        <a href="/contact" class="text-muted">
            <i class="bi bi-envelope"></i> Contact Support
        </a>
    </div>
</div>
</div><!-- End of flex wrapper -->

<?php
// Prepare JavaScript variables
$lastSubmitTime = isset($_SESSION['ask_goldie_rate_limit']['last_request']) ? intval($_SESSION['ask_goldie_rate_limit']['last_request']) : 0;
$currentTime = time();
$jsQuestion = $showAnswer && !empty($question) ? htmlspecialchars($question, ENT_QUOTES) : '';
$jsAnswer = $showAnswer && !empty($answer) ? str_replace(["\r\n", "\n", "\r"], "<br>", htmlspecialchars($answer, ENT_QUOTES)) : '';
$jsTimestamp = date("g:i a");
$jsUserAvatar = !empty($current_user_data['avatar']) ? '/' . $current_user_data['avatar'] : '/public/images/defaultavatar.png';

$footerattribute['postfooter'] = '
<script>
// Auto-scroll to bottom of chat
function scrollToBottom() {
    const chatMessages = document.getElementById("chatMessages");
    chatMessages.scrollTop = chatMessages.scrollHeight;
}


// Quick question buttons
function quickQuestion(question) {
    const input = document.getElementById("chatInput");
    const form = document.getElementById("chatForm");
    const submitBtn = document.getElementById("submitBtn");
    
    // Set the question value
    input.value = question;
    
    // Auto-resize the textarea to fit the question
    autoResizeTextarea(input);
    
    // Close the quick questions panel
    const wrapper = document.getElementById("quickQuestionsWrapper");
    if (wrapper && !wrapper.classList.contains("collapsed")) {
        wrapper.classList.add("collapsed");
    }
    
    // Reset processing flag
    isProcessing = false;
    
    // Submit the form by clicking the submit button
    if (submitBtn && !submitBtn.disabled) {
        // Focus the input first to ensure it is ready
        input.focus();
        // Click the submit button to trigger natural form submission
        setTimeout(function() {
            submitBtn.click();
        }, 50);
    }
}

// Toggle quick questions panel with smooth animations
function toggleQuickQuestions() {
    const wrapper = document.getElementById("quickQuestionsWrapper");
    const content = wrapper.querySelector(".quick-questions-content");
    const isCollapsing = !wrapper.classList.contains("collapsed");
    
    if (!isCollapsing) {
        // Opening the tray
        wrapper.classList.remove("collapsed");
        wrapper.classList.add("expanding");
        
        // Calculate the full height of the content
        const contentHeight = content.scrollHeight;
        const wrapperHeight = wrapper.offsetHeight + contentHeight;
        
        // Use requestAnimationFrame for smooth animation coordination
        requestAnimationFrame(() => {
            // Get current viewport and wrapper positions
            const wrapperRect = wrapper.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            
            // Calculate where the bottom of the expanded tray will be
            const futureBottom = currentScroll + wrapperRect.top + wrapperHeight;
            const visibleBottom = currentScroll + windowHeight;
            
            // If the expanded tray will go below viewport, start scrolling
            if (futureBottom > visibleBottom - 50) {
                const targetScroll = futureBottom - windowHeight + 80;
                
                // Smooth scroll synchronized with CSS animation
                const startScroll = currentScroll;
                const distance = targetScroll - startScroll;
                const duration = 400; // Match CSS animation duration
                const startTime = performance.now();
                
                function animateScroll(currentTime) {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    
                    // Cubic bezier easing for smooth animation
                    const easing = cubicBezier(0.4, 0, 0.2, 1, progress);
                    const scrollPosition = startScroll + (distance * easing);
                    
                    window.scrollTo(0, scrollPosition);
                    
                    if (progress < 1) {
                        requestAnimationFrame(animateScroll);
                    }
                }
                
                requestAnimationFrame(animateScroll);
            }
        });
        
        // Remove animation class after completion
        setTimeout(() => {
            wrapper.classList.remove("expanding");
        }, 400);
        
    } else {
        // Closing the tray
        wrapper.classList.add("collapsed");
        
        // Ensure input stays visible when closing
        requestAnimationFrame(() => {
            const inputArea = document.querySelector(".chat-input-area");
            if (inputArea) {
                const inputRect = inputArea.getBoundingClientRect();
                const windowHeight = window.innerHeight;
                
                if (inputRect.bottom > windowHeight || inputRect.top < 100) {
                    inputArea.scrollIntoView({ 
                        behavior: "smooth", 
                        block: "center",
                        inline: "nearest"
                    });
                }
            }
        });
    }
}

// Cubic bezier function for smooth easing
function cubicBezier(x1, y1, x2, y2, t) {
    const cx = 3 * x1;
    const bx = 3 * (x2 - x1) - cx;
    const ax = 1 - cx - bx;
    const cy = 3 * y1;
    const by = 3 * (y2 - y1) - cy;
    const ay = 1 - cy - by;
    
    function sampleCurveX(t) {
        return ((ax * t + bx) * t + cx) * t;
    }
    
    function sampleCurveY(t) {
        return ((ay * t + by) * t + cy) * t;
    }
    
    function solveCurveX(x) {
        let t = x;
        for (let i = 0; i < 4; i++) {
            const currentX = sampleCurveX(t);
            if (Math.abs(currentX - x) < 0.001) return t;
            const currentSlope = (3 * ax * t + 2 * bx) * t + cx;
            if (Math.abs(currentSlope) < 0.001) break;
            t -= (currentX - x) / currentSlope;
        }
        return t;
    }
    
    return sampleCurveY(solveCurveX(t));
}

// Auto-resize textarea
function autoResizeTextarea(textarea) {
    // Reset height to recalculate
    textarea.style.height = "48px";
    
    // Calculate new height
    const scrollHeight = textarea.scrollHeight;
    const maxHeight = 200;
    
    // Set new height, capped at max
    if (scrollHeight > 48) {
        textarea.style.height = Math.min(scrollHeight, maxHeight) + "px";
    }
    
    // Show scrollbar only if content exceeds max height
    if (scrollHeight > maxHeight) {
        textarea.style.overflowY = "auto";
    } else {
        textarea.style.overflowY = "hidden";
    }
}

// Handle form submission with typing indicator
const chatForm = document.getElementById("chatForm");
const submitBtn = document.getElementById("submitBtn");
const typingIndicator = document.getElementById("typingIndicator");
const chatInput = document.getElementById("chatInput");
const chatMessages = document.getElementById("chatMessages");
const sendingOverlay = document.getElementById("sendingOverlay");

// Flag to track if we are processing
let isProcessing = false;
let processingTimeout = null;

// Disable submit if within 5 second cooldown
const lastSubmitTime = ' . $lastSubmitTime . ';
const currentTime = ' . $currentTime . ';
const timeSinceLastSubmit = currentTime - lastSubmitTime;

if (timeSinceLastSubmit < 5) {
    const remaining = 5 - timeSinceLastSubmit;
    submitBtn.disabled = true;
    setTimeout(() => {
        submitBtn.disabled = false;
    }, remaining * 1000);
}

// Function to update placeholder based on screen size
function updatePlaceholder() {
    if (chatInput) {
        if (window.innerWidth <= 575) {
            chatInput.placeholder = "Type your question...";
        } else {
            chatInput.placeholder = "Type your question... (Shift+Enter to send)";
        }
    }
}

// Array of processing words to randomly choose from
const processingWords = [
    "Thinking...",
    "Pondering...",
    "Contemplating...",
    "Analyzing...",
    "Considering...",
    "Reflecting...",
    "Processing...",
    "Musing..."
];

// Track last used processing word
let lastProcessingWord = "";

// Function to get random processing word (ensures different from last)
function getRandomProcessingWord() {
    let newWord;
    do {
        newWord = processingWords[Math.floor(Math.random() * processingWords.length)];
    } while (newWord === lastProcessingWord && processingWords.length > 1);
    
    lastProcessingWord = newWord;
    return newWord;
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function() {
    // Focus input only on desktop
    if (window.innerWidth > 575) {
        chatInput.focus();
    }
    scrollToBottom();
    
    // Add auto-resize to textarea
    chatInput.addEventListener("input", function() {
        autoResizeTextarea(this);
    });
    
    // Initial resize
    autoResizeTextarea(chatInput);
    
    // Clear any lingering typing indicators
    typingIndicator.classList.remove("show");
    
    // Ensure quick questions start collapsed on mobile
    const quickQuestionsWrapper = document.getElementById("quickQuestionsWrapper");
    if (window.innerWidth <= 575 && quickQuestionsWrapper) {
        quickQuestionsWrapper.classList.add("collapsed");
    }
    
    // Set initial placeholder
    updatePlaceholder();
});

// Update placeholder on window resize
window.addEventListener("resize", function() {
    updatePlaceholder();
});

// Handle Enter key - Shift+Enter to submit, Enter for new line
chatInput.addEventListener("keydown", function(e) {
    if (e.key === "Enter") {
        if (e.shiftKey) {
            // Shift+Enter submits
            e.preventDefault();
            if (!submitBtn.disabled && this.value.trim()) {
                // Trigger submit button click instead of form.submit()
                submitBtn.click();
            }
        }
        // Regular Enter allows new line (default behavior)
    }
});

// Function to show visual feedback
function showProcessingFeedback() {
    // Add processing class to input area
    const inputArea = document.querySelector(".chat-input-area");
    if (inputArea) {
        inputArea.classList.add("processing");
    }
    
    // Show sending overlay with random processing word
    if (sendingOverlay) {
        const processingText = sendingOverlay.querySelector(".sending-message span:last-child");
        if (processingText) {
            processingText.textContent = getRandomProcessingWord();
        }
        sendingOverlay.style.display = "flex";
        sendingOverlay.classList.add("show");
    }
    
    // Add loading class but keep button enabled for form submission
    submitBtn.classList.add("loading");
    
    // Change button to loading state
    submitBtn.innerHTML = \'<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>\';
    
    // Remove welcome message if exists
    const welcomeMsg = document.querySelector(".welcome-message");
    if (welcomeMsg) welcomeMsg.remove();
    
    // Set timeout for processing (30 seconds)
    processingTimeout = setTimeout(function() {
        hideProcessingFeedback();
        showTimeoutError();
    }, 30000);
}

// Function to hide processing feedback
function hideProcessingFeedback() {
    // Clear the timeout
    if (processingTimeout) {
        clearTimeout(processingTimeout);
        processingTimeout = null;
    }
    
    // Remove processing class
    const inputArea = document.querySelector(".chat-input-area");
    if (inputArea) {
        inputArea.classList.remove("processing");
    }
    
    // Hide overlay
    if (sendingOverlay) {
        sendingOverlay.style.display = "none";
        sendingOverlay.classList.remove("show");
    }
    
    // Reset button
    submitBtn.classList.remove("loading");
    submitBtn.innerHTML = \'<i class="bi bi-send-fill"></i>\';
    submitBtn.disabled = false;
    
    // Reset processing flag
    isProcessing = false;
}

// Function to show timeout error
function showTimeoutError() {
    // Add error message to chat
    const errorDiv = document.createElement("div");
    errorDiv.className = "alert alert-danger alert-sm mb-2";
    errorDiv.innerHTML = \'<i class="bi bi-exclamation-triangle-fill me-2"></i>Sorry, unable to process your request at this time. Please try again.\';
    
    const inputArea = document.querySelector(".chat-input-area");
    inputArea.insertBefore(errorDiv, inputArea.firstChild);
    
    // Auto-hide error after 5 seconds
    setTimeout(function() {
        errorDiv.remove();
    }, 5000);
    
    // Clear the input
    chatInput.value = "";
    autoResizeTextarea(chatInput);
}

// Show visual feedback when form is submitted
chatForm.addEventListener("submit", function(e) {
    // Check if question is empty
    if (!chatInput.value.trim()) {
        e.preventDefault();
        return false;
    }
    
    // Only show feedback if not already processing
    if (!isProcessing) {
        isProcessing = true;
        showProcessingFeedback();
    } else {
        // Prevent duplicate submission
        e.preventDefault();
        return false;
    }
    
    // Let the form submit naturally
    return true;
});
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();