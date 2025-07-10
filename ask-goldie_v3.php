<?php
$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

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
}

// Clean up old flood detection entries (older than 30 seconds)
$_SESSION['ask_goldie_rate_limit']['requests_30s'] = array_filter(
    $_SESSION['ask_goldie_rate_limit']['requests_30s'] ?? [],
    function($timestamp) { return $timestamp > (time() - 30); }
);

$rateLimitData = $_SESSION['ask_goldie_rate_limit'];
$requireCaptcha = false; // Default: no captcha

// Check for flooding (more than 1 request in 30 seconds)
if (count($rateLimitData['requests_30s']) >= 2) {
    $requireCaptcha = true;
}

// Initialize conversation ID (persists across sessions for logged-in users)
if (!empty($current_user_data['user_id'])) {
    $conversationId = 'user_' . $current_user_data['user_id'] . '_' . date('Ymd');
} else {
    if (!isset($_SESSION['ask_goldie_conversation_id'])) {
        $_SESSION['ask_goldie_conversation_id'] = 'anon_' . uniqid() . '_' . date('Ymd');
    }
    $conversationId = $_SESSION['ask_goldie_conversation_id'];
}

// Option to start new conversation
if (isset($_GET['new']) && $_GET['new'] == 1) {
    if (!empty($current_user_data['user_id'])) {
        $conversationId = 'user_' . $current_user_data['user_id'] . '_' . uniqid();
    } else {
        $_SESSION['ask_goldie_conversation_id'] = 'anon_' . uniqid() . '_' . date('Ymd');
        $conversationId = $_SESSION['ask_goldie_conversation_id'];
    }
    header('Location: /ask-goldie_v3');
    exit;
}

// Load conversation history from session
$conversationHistory = [];
if (isset($_SESSION['ask_goldie_conversations'][$conversationId])) {
    $conversationHistory = $_SESSION['ask_goldie_conversations'][$conversationId];
}

// Allow 10 questions per hour per session
if ($rateLimitData['count'] >= 10) {
    $system->addMessage('error', 'You have reached the hourly limit of 10 questions. Please try again later.');
    header('Location: /help');
    exit;
}

#-------------------------------------------------------------------------------
# HANDLE QUESTION SUBMISSION
#-------------------------------------------------------------------------------
$question = '';
$answer = '';
$showAnswer = false;
$errorMessage = '';

if (($formdata = $app->formposted())) {
    // Check 10-second rate limit
    $timeSinceLastRequest = time() - ($rateLimitData['last_request'] ?? 0);
    if ($timeSinceLastRequest < 10) {
        $errorMessage = 'Please wait ' . (10 - $timeSinceLastRequest) . ' seconds before asking another question.';
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
                    
                    // Get user's first name if logged in
                    $firstName = '';
                    if (!empty($current_user_data['user_id'])) {
                        $firstName = $current_user_data['first_name'] ?? '';
                    }
                    
                    // Check if this is first message in conversation
                    $isFirstMessage = empty($conversationHistory);
                    
                    $systemPrompt = "You are Goldie, the friendly AI assistant for Birthday Gold. You help users understand how Birthday Gold works, answer questions about enrollment, rewards, features, and general service inquiries.
" . (!empty($firstName) ? "\nThe user's name is $firstName. Address them by name occasionally to make the conversation more personal.\n" : '') . "
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
                    
                    // Add to conversation history
                    $conversationHistory[] = [
                        'question' => $question,
                        'answer' => $answer,
                        'timestamp' => time()
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

// Generate dynamic quick questions based on conversation context
$quickQuestions = [];

if (!empty($conversationHistory)) {
    // Get the last exchange for context
    $lastExchange = end($conversationHistory);
    $lastAnswer = strtolower($lastExchange['answer'] ?? '');
    $lastQuestion = strtolower($lastExchange['question'] ?? '');
    
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
        $quickQuestions[] = "What's included in the different Birthday Gold subscription plans?";
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
            $quickQuestions[] = "Can you show me a summary of everything we've discussed?";
            $quickQuestions[] = "I'd like to know more about the specific features you mentioned";
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

// Limit to 8 questions
$quickQuestions = array_slice($quickQuestions, 0, 8);

// Page styling
$additionalstyles = '
<style>
/* Ask Goldie Styles */
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

.floating-icon {
    animation: float 3s ease-in-out infinite;
}

/* Full Chat Interface Styles */
.chat-container {
    display: flex;
    flex-direction: column;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    overflow: hidden;
    margin-bottom: 0.75rem;
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
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
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

/* Main container adjustments */
.container.py-4 {
    padding-top: 2rem !important;
    padding-bottom: 1rem !important;
}

/* Ensure chat messages take available space */
.chat-messages {
    min-height: 300px;
}

/* Collapsible quick questions */
.quick-questions-wrapper {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-top: 0.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.quick-questions-wrapper.collapsed {
    margin-bottom: 1rem;
}

.quick-questions-wrapper.collapsed .quick-questions-content {
    display: none;
}

.quick-questions-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem;
    cursor: pointer;
    background: #f8f9fa;
    border: none;
    width: 100%;
    font-size: 0.9rem;
    color: #6c757d;
    border-radius: 12px 12px 0 0;
}

.quick-questions-toggle:hover {
    background: #e9ecef;
}

.quick-questions-toggle i {
    transition: transform 0.3s ease;
}

.quick-questions-wrapper.collapsed .quick-questions-toggle i {
    transform: rotate(180deg);
}

.quick-questions-wrapper.collapsed .quick-questions-toggle {
    border-radius: 12px;
}

.quick-questions-content {
    padding: 1rem;
    border-top: 1px solid #dee2e6;
}

.quick-question-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
}

.quick-pill {
    background: #e7f3ff;
    border: 1px solid #b8daff;
    color: #0056b3;
    padding: 0.75rem 1.25rem;
    border-radius: 25px;
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
    background: #cce5ff;
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
                <div class="text-end me-3">
                    <img src="/public/images/logo/goldie_200.png" alt="Goldie" class="floating-icon" style="height: 100px;">
                </div>
                <div>
                    <h1 class="mb-2">Ask Goldie</h1>
                    <p class="lead mb-0">Get instant answers about Birthday Gold from our AI assistant</p>
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
                            AI Assistant for Birthday Gold
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
                    <img src="/public/images/logo/goldie_200.png" alt="Goldie" style="height: 80px; opacity: 0.5;" class="mb-3">
                    <h6>Welcome! I'm Goldie, your Birthday Gold assistant.</h6>
                    <p class="mb-0">Ask me anything about Birthday Gold!</p>
                    <div class="quick-actions">
                        <?php 
                        // Show first 4 questions as quick actions in welcome message
                        $welcomeQuestions = array_slice($quickQuestions, 0, 4);
                        foreach ($welcomeQuestions as $question): 
                        ?>
                        <button class="quick-action" onclick="quickQuestion(<?php echo htmlspecialchars(json_encode($question), ENT_QUOTES); ?>)">
                            <?php 
                            // Shorten for button display
                            $shortText = $question;
                            if (strpos($question, 'Birthday Gold work') !== false) $shortText = 'How it works';
                            elseif (strpos($question, 'birthday rewards') !== false) $shortText = 'Birthday rewards';
                            elseif (strpos($question, 'cost') !== false || strpos($question, 'price') !== false) $shortText = 'Pricing';
                            elseif (strpos($question, 'started') !== false) $shortText = 'Get started';
                            echo htmlspecialchars($shortText);
                            ?>
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
                ></textarea>
                <?php if ($requireCaptcha): ?>
                    <!-- Hidden captcha that shows when needed -->
                    <div id="captchaModal" style="display: none;">
                        <?php echo $app->generateCaptcha(); ?>
                    </div>
                <?php endif; ?>
                <button type="submit" class="chat-submit btn btn-primary" id="submitBtn">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Collapsible Quick Questions -->
    <div class="quick-questions-wrapper" id="quickQuestionsWrapper">
        <button class="quick-questions-toggle" onclick="toggleQuickQuestions()">
            <i class="bi bi-chevron-up me-2"></i>
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
    <div class="text-center mt-3">
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
    
    // Submit the form if button is enabled
    if (submitBtn && !submitBtn.disabled) {
        // Trigger the submit event instead of direct submit
        const submitEvent = new Event(\'submit\', { cancelable: true });
        form.dispatchEvent(submitEvent);
        
        // If event was not prevented, submit the form
        if (!submitEvent.defaultPrevented) {
            form.submit();
        }
    }
}

// Toggle quick questions panel
function toggleQuickQuestions() {
    const wrapper = document.getElementById("quickQuestionsWrapper");
    wrapper.classList.toggle("collapsed");
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

// Disable submit if within 10 second cooldown
const lastSubmitTime = ' . $lastSubmitTime . ';
const currentTime = ' . $currentTime . ';
const timeSinceLastSubmit = currentTime - lastSubmitTime;

if (timeSinceLastSubmit < 10) {
    const remaining = 10 - timeSinceLastSubmit;
    submitBtn.disabled = true;
    setTimeout(() => {
        submitBtn.disabled = false;
    }, remaining * 1000);
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function() {
    // Focus input
    chatInput.focus();
    scrollToBottom();
    
    // Add auto-resize to textarea
    chatInput.addEventListener("input", function() {
        autoResizeTextarea(this);
    });
    
    // Initial resize
    autoResizeTextarea(chatInput);
    
    // Clear any lingering typing indicators
    typingIndicator.classList.remove("show");
});

// Handle Enter key - Shift+Enter to submit, Enter for new line
chatInput.addEventListener("keydown", function(e) {
    if (e.key === "Enter") {
        if (e.shiftKey) {
            // Shift+Enter submits
            e.preventDefault();
            if (!submitBtn.disabled && this.value.trim()) {
                // Trigger submit event instead of direct submit
                const submitEvent = new Event(\'submit\', { cancelable: true });
                chatForm.dispatchEvent(submitEvent);
            }
        }
        // Regular Enter allows new line (default behavior)
    }
});

// Show typing when form is submitted
chatForm.addEventListener("submit", function(e) {
    // Do not submit immediately - let visual feedback show first
    e.preventDefault();
    
    // Get the question text before disabling
    const questionText = chatInput.value.trim();
    
    // Add processing class to input area
    const inputArea = document.querySelector(".chat-input-area");
    if (inputArea) {
        inputArea.classList.add("processing");
    }
    
    // Show sending overlay with force
    if (sendingOverlay) {
        sendingOverlay.style.display = "flex";
        sendingOverlay.classList.add("show");
    }
    
    // Disable input and button
    chatInput.disabled = true;
    submitBtn.disabled = true;
    submitBtn.classList.add("loading");
    
    // Change button to loading state
    submitBtn.innerHTML = \'<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>\';
    
    // Remove welcome message if exists
    const welcomeMsg = document.querySelector(".welcome-message");
    if (welcomeMsg) welcomeMsg.remove();
    
    // Add user message immediately to chat
    const userMessage = document.createElement("div");
    userMessage.className = "message message-user";
    const userAvatar = ' . (!empty($current_user_data['user_id']) ? '"' . $jsUserAvatar . '"' : '""') . ';
    
    let userMessageHTML = \'<div class="message-content">\';
    userMessageHTML += questionText.replace(/\\n/g, "<br>");
    userMessageHTML += \'<div class="message-timestamp">\' + new Date().toLocaleTimeString("en-US", {hour: "numeric", minute: "numeric", hour12: true}) + \'</div>\';
    userMessageHTML += \'</div>\';
    if (userAvatar) {
        userMessageHTML += \'<img src="\' + userAvatar + \'" alt="You" class="user-avatar">\';
    }
    
    userMessage.innerHTML = userMessageHTML;
    chatMessages.appendChild(userMessage);
    
    // Submit the form after a small delay to ensure visual feedback is shown
    setTimeout(() => {
        chatForm.submit();
    }, 100);
});
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();