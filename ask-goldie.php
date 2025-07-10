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
                    
                    $systemPrompt = "You are Goldie, the friendly AI assistant for Birthday Gold. You help users understand how Birthday Gold works, answer questions about enrollment, rewards, features, and general service inquiries.

IMPORTANT RULES:
1. Only answer questions about Birthday Gold services, features, enrollment, rewards, pricing, and general help
2. Do NOT provide any technical details about infrastructure, databases, APIs, or implementation
3. Do NOT discuss security details, passwords, or authentication methods
4. Keep responses concise (under 200 words)
5. Be helpful and friendly
6. If asked about technical/infrastructure details, politely redirect to contact support
7. Reference relevant pages when appropriate: /how-it-works, /pricing, /faq, /contact

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
                    
                    $showAnswer = true;
                    
                } catch (Exception $e) {
                    error_log('Ask Goldie error: ' . $e->getMessage());
                    $errorMessage = 'Sorry, I could not process your question at this time. Please try again or contact support.';
                }
            }
        }
    }
}

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
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

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

<div class="container py-5">
    <div class="ask-goldie-container">
        
        <!-- Rate Limit Notice -->
        <?php if ($rateLimitData['count'] > 7): ?>
        <div class="rate-limit-notice">
            <i class="bi bi-info-circle me-2"></i>
            You have <?php echo 10 - $rateLimitData['count']; ?> questions remaining this hour.
        </div>
        <?php endif; ?>
        
        <!-- Chat Interface -->
        <div class="chat-interface">
            <h2 class="h4 mb-4">How can I help you today?</h2>
            
            <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($errorMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Question Form -->
            <form method="POST" action="/ask-goldie" class="question-form">
                <?php echo $display->inputcsrf_token(); ?>
                
                <div class="mb-3">
                    <label for="question" class="form-label fw-semibold">Your Question:</label>
                    <textarea 
                        name="question" 
                        id="question" 
                        class="form-control" 
                        placeholder="Ask me about Birthday Gold enrollment, rewards, features, or how our service works..."
                        maxlength="500"
                        required
                    ><?php echo htmlspecialchars($question); ?></textarea>
                    <div class="char-counter">
                        <span id="charCount">0</span> / 500 characters
                    </div>
                </div>
                
                <?php if ($requireCaptcha): ?>
                <div class="mb-3">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Multiple rapid requests detected. Please complete the security check.
                    </div>
                    <?php echo $app->generateCaptcha(); ?>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-send me-2"></i>Ask Goldie
                </button>
            </form>
            
            <!-- Answer Section -->
            <?php if ($showAnswer && !empty($answer)): ?>
            <div class="answer-section">
                <div class="answer-header">
                    <img src="/public/images/logo/goldie_72.png" alt="Goldie" style="height: 24px; width: 24px;">
                    <span>Goldie's Answer:</span>
                </div>
                <div class="answer-content">
                    <?php echo nl2br(htmlspecialchars($answer)); ?>
                </div>
                
                <div class="mt-3 pt-3 border-top">
                    <p class="mb-2 text-muted small">Was this helpful?</p>
                    <a href="/contact" class="btn btn-sm btn-outline-primary">Contact Support</a>
                    <a href="/faq" class="btn btn-sm btn-outline-secondary ms-2">View FAQ</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Example Questions -->
        <div class="example-questions">
            <h3 class="h5 mb-3">Example questions you can ask:</h3>
            <div class="example-question" onclick="setQuestion('How does Birthday Gold work?')">
                <i class="bi bi-question-circle me-2"></i>How does Birthday Gold work?
            </div>
            <div class="example-question" onclick="setQuestion('What birthday rewards can I get?')">
                <i class="bi bi-gift me-2"></i>What birthday rewards can I get?
            </div>
            <div class="example-question" onclick="setQuestion('How much does Birthday Gold cost?')">
                <i class="bi bi-tag me-2"></i>How much does Birthday Gold cost?
            </div>
            <div class="example-question" onclick="setQuestion('How do I manage my enrollments?')">
                <i class="bi bi-gear me-2"></i>How do I manage my enrollments?
            </div>
            <div class="example-question" onclick="setQuestion('Is my personal information safe?')">
                <i class="bi bi-shield-check me-2"></i>Is my personal information safe?
            </div>
        </div>
        
        <!-- Additional Help -->
        <div class="text-center mt-4">
            <p class="text-muted">Need more help?</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="/help" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Help Center
                </a>
                <a href="/contact" class="btn btn-outline-primary">
                    <i class="bi bi-envelope me-2"></i>Contact Support
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$footerattribute['postfooter'] = '
<script>
// Character counter
const textarea = document.getElementById("question");
const charCount = document.getElementById("charCount");
const charCounter = document.querySelector(".char-counter");

function updateCharCount() {
    const count = textarea.value.length;
    charCount.textContent = count;
    
    if (count > 450) {
        charCounter.classList.add("warning");
    } else {
        charCounter.classList.remove("warning");
    }
}

textarea.addEventListener("input", updateCharCount);
updateCharCount();

// Set example question
function setQuestion(question) {
    textarea.value = question;
    updateCharCount();
    textarea.focus();
}

// Auto-scroll to answer if present
document.addEventListener("DOMContentLoaded", function() {
    const answerSection = document.querySelector(".answer-section");
    if (answerSection) {
        answerSection.scrollIntoView({ behavior: "smooth", block: "center" });
    }
});
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();