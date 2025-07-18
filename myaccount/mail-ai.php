<?php
// mail-ai.php - AI-powered mail summary
$addClasses[] = 'mail';
$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "AI Mail Summary";

// Security Note: Message IDs are encoded using $qik->encodeId() when displayed in URLs 
// and data attributes, then decoded when processing requests.

// Retrieve any messages from previous page (e.g., mail-read.php errors)
$transferpagedata = $system->startpostpage();

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$errormessage = '';
$uid = !empty($_REQUEST['uid']) ? $qik->decodeId($_REQUEST['uid']) : $current_user_data['user_id'];

// Initialize variables for AI summary
$view_mode = $_GET['view'] ?? 'daily'; // 'daily' or 'company'
$days_back = intval($_GET['days'] ?? 30);
$days_back = min(max($days_back, 7), 90); // Between 7 and 90 days

// Calculate date range
$end_date = new DateTime();
$start_date = new DateTime();
$start_date->modify("-{$days_back} days");

// Get messages for the date range
$messages_results = $mail->getMessagesForAI($uid, $start_date->format('Y-m-d'), $end_date->format('Y-m-d'));
$messages = $messages_results['messages'] ?? [];

// Process messages for AI summaries
$summaries = [];
if (!empty($messages) && isset($ai)) {
    // Set AI engine
    $ai->setEngine('anthropic_goldie', 'text');
    
    if ($view_mode === 'daily') {
        // Group messages by day
        $messages_by_day = [];
        foreach ($messages as $message) {
            $date = date('Y-m-d', strtotime($message['create_dt']));
            if (!isset($messages_by_day[$date])) {
                $messages_by_day[$date] = [];
            }
            $messages_by_day[$date][] = $message;
        }
        
        // Generate summaries for each day with messages
        foreach ($messages_by_day as $date => $day_messages) {
            if (count($day_messages) > 0) {
                $summary = generateDailySummary($ai, $date, $day_messages, $app);
                if ($summary) {
                    $summaries[] = $summary;
                }
            }
        }
    } else {
        // Group messages by company
        $messages_by_company = [];
        foreach ($messages as $message) {
            if (!empty($message['company_id'])) {
                if (!isset($messages_by_company[$message['company_id']])) {
                    $messages_by_company[$message['company_id']] = [];
                }
                $messages_by_company[$message['company_id']][] = $message;
            }
        }
        
        // Generate summaries for each company
        foreach ($messages_by_company as $company_id => $company_messages) {
            $summary = generateCompanySummary($ai, $company_id, $company_messages, $app);
            if ($summary) {
                $summaries[] = $summary;
            }
        }
    }
}

// Function to generate daily summary
function generateDailySummary($ai, $date, $messages, $app) {
    // Get unique companies for this day
    $companies = [];
    $message_texts = [];
    
    foreach ($messages as $message) {
        if (!empty($message['company_id'])) {
            $company = $app->getcompany($message['company_id']);
            if ($company) {
                $companies[$message['company_id']] = $company['company_display_name'] ?? 'Unknown';
            }
        }
        
        // Collect message subjects and preview of body
        $message_texts[] = "From: " . ($companies[$message['company_id']] ?? 'Unknown') . 
                          "\nSubject: " . $message['subject'] . 
                          "\n" . substr(strip_tags($message['body'] ?? ''), 0, 200);
    }
    
    $prompt = "Summarize these birthday reward messages from " . date('F j, Y', strtotime($date)) . 
              " into a brief, friendly summary highlighting the best deals and offers:\n\n" . 
              implode("\n---\n", $message_texts);
    
    try {
        $response = $ai->process([
            ['role' => 'system', 'content' => 'You are a helpful assistant that summarizes birthday reward emails. Focus on the most valuable offers and deals. Keep summaries concise and highlight specific discounts or free items. Use a friendly, enthusiastic tone.'],
            ['role' => 'user', 'content' => $prompt]
        ], [
            'temperature' => 0.7,
            'max_tokens' => 300
        ]);
        
        $normalizedResponse = $ai->getNormalizedResponse($response);
        
        return [
            'date' => $date,
            'display_date' => date('F j, Y', strtotime($date)),
            'message_count' => count($messages),
            'company_count' => count($companies),
            'companies' => array_values($companies),
            'summary' => $normalizedResponse['content'],
            'messages' => $messages
        ];
    } catch (Exception $e) {
        error_log("AI Summary Error: " . $e->getMessage());
        return null;
    }
}

// Function to generate company summary
function generateCompanySummary($ai, $company_id, $messages, $app) {
    $company = $app->getcompany($company_id);
    if (!$company) return null;
    
    $message_texts = [];
    foreach ($messages as $message) {
        $message_texts[] = "Date: " . date('M j', strtotime($message['create_dt'])) . 
                          "\nSubject: " . $message['subject'] . 
                          "\n" . substr(strip_tags($message['body'] ?? ''), 0, 200);
    }
    
    $prompt = "Summarize all birthday reward messages from " . $company['company_display_name'] . 
              " over the past period. Identify patterns in their offers and highlight the best deals:\n\n" . 
              implode("\n---\n", $message_texts);
    
    try {
        $response = $ai->process([
            ['role' => 'system', 'content' => 'You are a helpful assistant that analyzes birthday reward patterns from companies. Identify the types of offers they send, any patterns, and highlight the most valuable rewards. Be concise and specific.'],
            ['role' => 'user', 'content' => $prompt]
        ], [
            'temperature' => 0.7,
            'max_tokens' => 300
        ]);
        
        $normalizedResponse = $ai->getNormalizedResponse($response);
        
        return [
            'company_id' => $company_id,
            'company_name' => $company['company_display_name'],
            'company_logo' => $company['company_logo'] ?? null,
            'message_count' => count($messages),
            'date_range' => date('M j', strtotime($messages[count($messages)-1]['create_dt'])) . ' - ' . 
                           date('M j', strtotime($messages[0]['create_dt'])),
            'summary' => $normalizedResponse['content'],
            'messages' => $messages
        ];
    } catch (Exception $e) {
        error_log("AI Summary Error: " . $e->getMessage());
        return null;
    }
}

// Add v7 theme CSS and custom styles
$additionalstyles = '<link rel="stylesheet" href="/public/css/v7/bg_theme.css">
<style>
.summary-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.summary-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.summary-date {
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
}

.summary-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: #6c757d;
}

.company-logos {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.company-logo-small {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    object-fit: cover;
}

.summary-text {
    color: #555;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.view-toggle {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
}

.toggle-btn {
    padding: 0.5rem 1.5rem;
    border: 2px solid #dee2e6;
    background: white;
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.2s;
    cursor: pointer;
}

.toggle-btn.active {
    background: #0d6efd;
    color: white;
    border-color: #0d6efd;
}

.date-range-selector {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.expand-icon {
    transition: transform 0.3s;
}

.summary-card.expanded .expand-icon {
    transform: rotate(180deg);
}

.message-list {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.summary-card.expanded .message-list {
    max-height: 500px;
    overflow-y: auto;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
}

.mini-message {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
    font-size: 0.875rem;
}

.mini-message:last-child {
    border-bottom: none;
}
</style>';

$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="mb-0">
                    <i class="bi bi-magic me-3"></i>AI Mail Insights
                </h1>
                <p class="lead mb-0">Smart summaries of your birthday rewards</p>
            </div>
            <div class="col-auto">
                <a href="/myaccount/mail-box" class="btn btn-outline-light" style="border-radius: 25px;">
                    <i class="bi bi-envelope me-2"></i>Regular Inbox
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <!-- View toggle and date range selector -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="view-toggle">
                <button class="toggle-btn <?php echo $view_mode === 'daily' ? 'active' : ''; ?>" 
                        onclick="changeView('daily')">
                    <i class="bi bi-calendar3 me-2"></i>Daily Summaries
                </button>
                <button class="toggle-btn <?php echo $view_mode === 'company' ? 'active' : ''; ?>" 
                        onclick="changeView('company')">
                    <i class="bi bi-building me-2"></i>By Company
                </button>
            </div>
        </div>
        <div class="col-md-6">
            <div class="date-range-selector">
                <label class="form-label mb-0">Show last:</label>
                <select class="form-select" style="width: auto;" onchange="changeDays(this.value)">
                    <option value="7" <?php echo $days_back == 7 ? 'selected' : ''; ?>>7 days</option>
                    <option value="14" <?php echo $days_back == 14 ? 'selected' : ''; ?>>14 days</option>
                    <option value="30" <?php echo $days_back == 30 ? 'selected' : ''; ?>>30 days</option>
                    <option value="60" <?php echo $days_back == 60 ? 'selected' : ''; ?>>60 days</option>
                    <option value="90" <?php echo $days_back == 90 ? 'selected' : ''; ?>>90 days</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <?php if (empty($summaries)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h3>No messages to summarize</h3>
            <p>You don't have any birthday reward messages in the selected time period.</p>
            <a href="/myaccount/mail-box" class="btn btn-primary" style="border-radius: 25px;">
                Go to Inbox
            </a>
        </div>
    <?php else: ?>
        <div id="summaries-container">
            <?php foreach ($summaries as $summary): ?>
                <div class="summary-card" onclick="toggleCard(this)">
                    <div class="summary-header">
                        <?php if ($view_mode === 'daily'): ?>
                            <div>
                                <div class="summary-date"><?php echo $summary['display_date']; ?></div>
                                <div class="summary-meta">
                                    <span><i class="bi bi-envelope me-1"></i><?php echo $summary['message_count']; ?> messages</span>
                                    <span><i class="bi bi-building me-1"></i><?php echo $summary['company_count']; ?> companies</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-center">
                                <?php if (!empty($summary['company_logo'])): ?>
                                    <img src="<?php echo $display->companyimage($summary['company_id'] . '/' . $summary['company_logo']); ?>" 
                                         class="company-logo-small me-3" alt="">
                                <?php endif; ?>
                                <div>
                                    <div class="summary-date"><?php echo htmlspecialchars($summary['company_name']); ?></div>
                                    <div class="summary-meta">
                                        <span><i class="bi bi-envelope me-1"></i><?php echo $summary['message_count']; ?> messages</span>
                                        <span><i class="bi bi-calendar me-1"></i><?php echo $summary['date_range']; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <i class="bi bi-chevron-down expand-icon"></i>
                    </div>
                    
                    <?php if ($view_mode === 'daily' && !empty($summary['companies'])): ?>
                        <div class="company-logos">
                            <?php foreach (array_slice($summary['companies'], 0, 8) as $company_name): ?>
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($company_name); ?></span>
                            <?php endforeach; ?>
                            <?php if (count($summary['companies']) > 8): ?>
                                <span class="badge bg-secondary">+<?php echo count($summary['companies']) - 8; ?> more</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-text">
                        <?php echo nl2br(htmlspecialchars($summary['summary'])); ?>
                    </div>
                    
                    <div class="message-list">
                        <?php foreach ($summary['messages'] as $msg): ?>
                            <?php 
                            $company = !empty($msg['company_id']) ? $app->getcompany($msg['company_id']) : null;
                            ?>
                            <div class="mini-message">
                                <div class="d-flex justify-content-between">
                                    <strong><?php echo htmlspecialchars($company['company_display_name'] ?? 'Unknown'); ?></strong>
                                    <small class="text-muted"><?php echo date('M j', strtotime($msg['create_dt'])); ?></small>
                                </div>
                                <div><?php echo htmlspecialchars($msg['subject']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function changeView(mode) {
    const currentParams = new URLSearchParams(window.location.search);
    currentParams.set('view', mode);
    window.location.search = currentParams.toString();
}

function changeDays(days) {
    const currentParams = new URLSearchParams(window.location.search);
    currentParams.set('days', days);
    window.location.search = currentParams.toString();
}

function toggleCard(card) {
    card.classList.toggle('expanded');
}
</script>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>