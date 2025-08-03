<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check admin access
if (!$account->isadmin()) {
    header('Location: /admin/');
    exit;
}

$pagetitle = 'AI Dashboard - Ask Goldie Analytics';
$header_flush = true; // Ensure header content is flush with admin header

// Date range filters
$dateFrom = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$dateTo = $_GET['to'] ?? date('Y-m-d');
$userFilter = $_GET['user'] ?? '';
$conversationFilter = $_GET['conversation'] ?? '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Build query for Ask Goldie responses
$query = "SELECT 
    st.id,
    st.name,
    st.create_dt,
    st.user_id,
    st.ip,
    st.tracking_data,
    u.first_name,
    u.last_name,
    u.username
FROM bg_sessiontracking st
LEFT JOIN bg_users u ON st.user_id = u.user_id
WHERE st.name IN ('ask-goldie-question', 'ask-goldie-response', 'ask-goldie-conversation', 'ask-goldie-new-conversation')
AND DATE(st.create_dt) BETWEEN :date_from AND :date_to";

$params = [
    'date_from' => $dateFrom,
    'date_to' => $dateTo
];

if ($userFilter) {
    $query .= " AND st.user_id = :user_id";
    $params['user_id'] = $userFilter;
}

if ($conversationFilter) {
    $query .= " AND st.tracking_data LIKE :conversation";
    $params['conversation'] = '%"conversation_id":"' . $conversationFilter . '"%';
}

$query .= " ORDER BY st.create_dt DESC";

// Get total count - build a separate count query
$countQuery = "SELECT COUNT(*) as count 
FROM bg_sessiontracking st
LEFT JOIN bg_users u ON st.user_id = u.user_id
WHERE st.name IN ('ask-goldie-question', 'ask-goldie-response', 'ask-goldie-conversation', 'ask-goldie-new-conversation')
AND DATE(st.create_dt) BETWEEN :date_from AND :date_to";

$countParams = [
    'date_from' => $dateFrom,
    'date_to' => $dateTo
];

if ($userFilter) {
    $countQuery .= " AND st.user_id = :user_id";
    $countParams['user_id'] = $userFilter;
}

if ($conversationFilter) {
    $countQuery .= " AND st.tracking_data LIKE :conversation";
    $countParams['conversation'] = '%"conversation_id":"' . $conversationFilter . '"%';
}

$countResult = $database->fetchOne($countQuery, $countParams);
$totalCount = $countResult['count'] ?? 0;
$totalPages = ceil($totalCount / $perPage);

// Add pagination
$query .= " LIMIT :limit OFFSET :offset";
$params['limit'] = $perPage;
$params['offset'] = $offset;

// Get results
$results = $database->getrows($query, $params);

// Process results to decode tracking data and group by conversation
$processedResults = [];
$conversationGroups = [];
foreach ($results as $row) {
    $trackingData = json_decode($row['tracking_data'], true);
    if ($trackingData) {
        $row['decoded_data'] = $trackingData;
        
        // Group by conversation ID
        if (!empty($trackingData['conversation_id'])) {
            $convId = $trackingData['conversation_id'];
            if (!isset($conversationGroups[$convId])) {
                $conversationGroups[$convId] = [
                    'conversation_id' => $convId,
                    'user_id' => $row['user_id'],
                    'user_name' => $row['first_name'] . ' ' . $row['last_name'],
                    'username' => $row['username'],
                    'start_time' => $row['create_dt'],
                    'last_time' => $row['create_dt'],
                    'questions' => 0,
                    'responses' => 0,
                    'total_tokens' => 0,
                    'entries' => []
                ];
            }
            
            // Update conversation stats
            $conversationGroups[$convId]['last_time'] = $row['create_dt'];
            if ($row['name'] === 'ask-goldie-question') {
                $conversationGroups[$convId]['questions']++;
            }
            if ($row['name'] === 'ask-goldie-response') {
                $conversationGroups[$convId]['responses']++;
                if (!empty($trackingData['total_tokens'])) {
                    $conversationGroups[$convId]['total_tokens'] += intval($trackingData['total_tokens']);
                }
            }
            $conversationGroups[$convId]['entries'][] = $row;
        }
    }
    $processedResults[] = $row;
}

// Sort conversations by last activity
usort($conversationGroups, function($a, $b) {
    return strtotime($b['last_time']) - strtotime($a['last_time']);
});

// Get summary statistics with more metrics
$statsQuery = "SELECT 
    COUNT(DISTINCT CASE WHEN tracking_data LIKE '%conversation_id%' THEN JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.conversation_id')) END) as total_conversations,
    COUNT(CASE WHEN name = 'ask-goldie-question' THEN 1 END) as total_questions,
    COUNT(CASE WHEN name = 'ask-goldie-response' THEN 1 END) as total_responses,
    COUNT(DISTINCT user_id) as unique_users,
    SUM(CASE WHEN name = 'ask-goldie-response' THEN JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.total_tokens')) END) as total_tokens,
    AVG(CASE WHEN name = 'ask-goldie-response' THEN JSON_UNQUOTE(JSON_EXTRACT(tracking_data, '$.processing_time')) END) as avg_processing_time,
    SUM(CASE WHEN name = 'ask-goldie-response' AND tracking_data LIKE '%\"isStaffMode\":true%' THEN 1 END) as staff_mode_responses,
    COUNT(DISTINCT DATE(create_dt)) as active_days
FROM bg_sessiontracking
WHERE name IN ('ask-goldie-question', 'ask-goldie-response', 'ask-goldie-conversation', 'ask-goldie-new-conversation')
AND DATE(create_dt) BETWEEN :date_from AND :date_to";

$stats = $database->getrow($statsQuery, ['date_from' => $dateFrom, 'date_to' => $dateTo]);

// Calculate additional metrics
$stats['avg_questions_per_conversation'] = $stats['total_conversations'] > 0 ? round($stats['total_questions'] / $stats['total_conversations'], 1) : 0;
$stats['avg_tokens_per_response'] = $stats['total_responses'] > 0 ? round($stats['total_tokens'] / $stats['total_responses']) : 0;
$stats['response_rate'] = $stats['total_questions'] > 0 ? round(($stats['total_responses'] / $stats['total_questions']) * 100, 1) : 0;

// Get unique users for filter dropdown
$usersQuery = "SELECT DISTINCT st.user_id, u.first_name, u.last_name, u.username
FROM bg_sessiontracking st
LEFT JOIN bg_users u ON st.user_id = u.user_id
WHERE st.name IN ('ask-goldie-question', 'ask-goldie-response')
AND st.user_id IS NOT NULL
ORDER BY u.first_name, u.last_name";
$uniqueUsers = $database->getrows($usersQuery);

// Additional styles as string
$additionalstyles .= '<style>
.ai-dashboard {
    padding: 2rem 0;
}

.stats-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-align: center;
    margin-bottom: 1rem;
    transition: transform 0.2s, box-shadow 0.2s;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.stats-card h3 {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.25rem;
}

.stats-card p {
    color: #6c757d;
    margin: 0;
    font-size: 0.875rem;
    font-weight: 500;
}

.stats-card.highlight {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid #007bff;
}

.filter-form {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.conversation-entry {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.conversation-entry.question {
    border-left: 4px solid #007bff;
}

.conversation-entry.response {
    border-left: 4px solid #28a745;
}

.conversation-entry.new-conversation {
    border-left: 4px solid #ffc107;
}

.conversation-entry.conversation-update {
    border-left: 4px solid #6c757d;
}

.entry-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 0.5rem;
}

.entry-type {
    font-weight: bold;
    text-transform: uppercase;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    display: inline-block;
}

.type-question {
    background: #e7f3ff;
    color: #0056b3;
}

.type-response {
    background: #d4edda;
    color: #155724;
}

.type-new {
    background: #fff3cd;
    color: #856404;
}

.type-update {
    background: #e2e3e5;
    color: #383d41;
}

.entry-meta {
    font-size: 0.875rem;
    color: #6c757d;
}

.question-text, .answer-text {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 4px;
    margin: 0.5rem 0;
}

.staff-mode-indicator {
    display: inline-block;
    background: #ff5252;
    color: white;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 3px;
    margin-left: 0.5rem;
}

.token-usage {
    display: flex;
    gap: 1rem;
    margin-top: 0.5rem;
    font-size: 0.875rem;
}

.token-stat {
    background: #e9ecef;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

.processing-time {
    color: #6c757d;
    font-size: 0.875rem;
}

.json-preview {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 0.5rem;
    margin-top: 0.5rem;
    font-family: monospace;
    font-size: 0.75rem;
    max-height: 200px;
    overflow-y: auto;
}

.toggle-json {
    font-size: 0.75rem;
    cursor: pointer;
    color: #007bff;
    text-decoration: underline;
}

/* Conversation Overview Styles */
.conversation-list {
    max-height: 600px;
    overflow-y: auto;
}

.conversation-item {
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
}

.conversation-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
    border-color: #dee2e6 !important;
}

.conversation-item.border-primary {
    border-color: #007bff !important;
    box-shadow: 0 4px 12px rgba(0,123,255,0.15);
}

.conversation-details {
    max-height: 400px;
    overflow-y: auto;
    margin-top: 1rem;
}

/* Make card body relative for positioning */
.conversation-item .card-body {
    position: relative;
}

/* Show a clickable indicator */
.conversation-item .card-body::after {
    content: "▼";
    position: absolute;
    right: 1rem;
    top: 1rem;
    color: #6c757d;
    font-size: 0.875rem;
    transition: transform 0.2s;
}

.conversation-item.border-primary .card-body::after {
    transform: rotate(180deg);
    color: #007bff;
}

.conversation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.conversation-stats {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: #6c757d;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.conversation-stat {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    white-space: nowrap;
}

@media (max-width: 768px) {
    .conversation-stats {
        justify-content: flex-start;
        margin-top: 0.5rem;
    }
}

.conversation-details {
    border-top: 1px solid #e9ecef;
    padding-top: 1rem;
}

/* View states */
.view-section {
    display: none;
}

#conversations-view {
    display: block;
}

.metric-trend {
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.metric-trend.up {
    color: #28a745;
}

.metric-trend.down {
    color: #dc3545;
}
</style>';

// Additional scripts as string
$additionalscripts = "<script>
function toggleJson(id) {
    const element = document.getElementById('json-' + id);
    element.style.display = element.style.display === 'none' ? 'block' : 'none';
}

function toggleConversation(convId, event) {
    // Prevent event bubbling
    if (event) event.stopPropagation();
    
    const item = document.querySelector('[data-conversation-id=\"' + convId + '\"]');
    if (item) {
        const details = item.querySelector('.conversation-details');
        
        // Toggle current conversation
        if (details.style.display === 'none' || details.style.display === '') {
            // Close all other conversations first
            document.querySelectorAll('.conversation-details').forEach(detail => {
                detail.style.display = 'none';
            });
            document.querySelectorAll('.conversation-item').forEach(card => {
                card.classList.remove('border-primary');
            });
            
            // Open this conversation
            details.style.display = 'block';
            item.classList.add('border-primary');
        } else {
            // Close this conversation
            details.style.display = 'none';
            item.classList.remove('border-primary');
        }
    }
}

function toggleView(view, element) {
    // Hide all view sections
    document.querySelectorAll('.view-section').forEach(section => {
        section.style.display = 'none';
    });
    
    // Show selected view
    document.getElementById(view + '-view').style.display = 'block';
    
    // Update button states
    document.querySelectorAll('.btn-group button').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.remove('active');
        btn.classList.add('btn-outline-primary');
    });
    
    // Set active button
    element.classList.remove('btn-outline-primary');
    element.classList.add('btn-primary');
    element.classList.add('active');
}

function viewFullConversation(convId, event) {
    event.stopPropagation();
    // Open in new window or redirect to detailed view
    window.open('/admin/ai-conversation-detail.php?id=' + encodeURIComponent(convId), '_blank');
}

function copyConversationId(convId, event) {
    event.stopPropagation();
    
    // Copy to clipboard
    if (navigator.clipboard) {
        navigator.clipboard.writeText(convId).then(() => {
            // Show success message
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = 'Copied!';
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-success');
            
            setTimeout(() => {
                btn.textContent = originalText;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-secondary');
            }, 2000);
        });
    } else {
        // Fallback for older browsers
        const input = document.createElement('input');
        input.value = convId;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
    }
}

function filterConversations() {
    const searchTerm = document.getElementById('conversationSearch').value.toLowerCase();
    const conversations = document.querySelectorAll('.conversation-item');
    let visibleCount = 0;
    
    conversations.forEach(conv => {
        const text = conv.textContent.toLowerCase();
        const convId = conv.getAttribute('data-conversation-id').toLowerCase();
        
        if (text.includes(searchTerm) || convId.includes(searchTerm)) {
            conv.style.display = 'block';
            visibleCount++;
        } else {
            conv.style.display = 'none';
        }
    });
    
    // Update count
    document.querySelector('.badge.bg-secondary').textContent = visibleCount + ' conversations';
}

function clearSearch() {
    document.getElementById('conversationSearch').value = '';
    filterConversations();
}

// Removed auto-refresh - can be annoying when interacting with the page
// To enable, uncomment the following:
// setTimeout(() => {
//     if (!document.hidden) {
//         location.reload();
//     }
// }, 30000);
</script>";

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Admin Section -->
<div class="content-header-admin">
    <div class="container">
        <h1 class="mb-3"><i class="bi bi-robot me-3"></i>AI Dashboard</h1>
        <p class="lead mb-0">Ask Goldie Analytics and Conversation Tracking</p>
    </div>
</div>

<div class="container ai-dashboard">
    
    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card highlight">
                <h3><?php echo number_format($stats['total_conversations'] ?? 0); ?></h3>
                <p>Total Conversations</p>
                <?php if ($stats['active_days'] > 0): ?>
                <div class="metric-trend"><?php echo round($stats['total_conversations'] / $stats['active_days'], 1); ?>/day avg</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card">
                <h3><?php echo number_format($stats['total_questions'] ?? 0); ?></h3>
                <p>Questions Asked</p>
                <div class="metric-trend"><?php echo $stats['avg_questions_per_conversation']; ?> per conv</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card">
                <h3><?php echo number_format($stats['total_responses'] ?? 0); ?></h3>
                <p>AI Responses</p>
                <div class="metric-trend"><?php echo $stats['response_rate']; ?>% response rate</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card">
                <h3><?php echo number_format($stats['unique_users'] ?? 0); ?></h3>
                <p>Unique Users</p>
                <?php if ($stats['staff_mode_responses'] > 0): ?>
                <div class="metric-trend"><?php echo $stats['staff_mode_responses']; ?> staff mode</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card">
                <h3><?php echo number_format($stats['total_tokens'] ?? 0); ?></h3>
                <p>Total Tokens</p>
                <div class="metric-trend"><?php echo number_format($stats['avg_tokens_per_response']); ?> per response</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card">
                <h3><?php echo number_format($stats['avg_processing_time'] ?? 0, 2); ?>s</h3>
                <p>Avg Response Time</p>
                <div class="metric-trend <?php echo $stats['avg_processing_time'] < 3 ? 'up' : 'down'; ?>">
                    <?php echo $stats['avg_processing_time'] < 3 ? 'Fast' : 'Slow'; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filter-form">
        <form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <div class="row">
                <div class="col-md-3">
                    <label for="from">From Date:</label>
                    <input type="date" name="from" id="from" class="form-control" value="<?php echo $dateFrom; ?>">
                </div>
                <div class="col-md-3">
                    <label for="to">To Date:</label>
                    <input type="date" name="to" id="to" class="form-control" value="<?php echo $dateTo; ?>">
                </div>
                <div class="col-md-3">
                    <label for="user">User:</label>
                    <select name="user" id="user" class="form-control">
                        <option value="">All Users</option>
                        <?php foreach ($uniqueUsers as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>" <?php echo $userFilter == $user['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['username'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="conversation">Conversation ID:</label>
                    <input type="text" name="conversation" id="conversation" class="form-control" placeholder="e.g., user_123_abc..." value="<?php echo htmlspecialchars($conversationFilter); ?>">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- View Toggle -->
    <div class="btn-group mb-3" role="group">
        <button type="button" class="btn btn-primary active" onclick="toggleView('conversations', this)">Conversations View</button>
        <button type="button" class="btn btn-outline-primary" onclick="toggleView('timeline', this)">Timeline View</button>
    </div>
    
    <!-- Conversations View -->
    <div id="conversations-view" class="view-section" style="display: block;">
        <div class="card shadow-sm">
            <div class="card-header bg-primary">
                <h4 class="mb-0 text-white">Recent Conversations</h4>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-muted mb-0">Click on a conversation to view details</p>
                    <span class="badge bg-secondary"><?php echo count($conversationGroups); ?> conversations</span>
                </div>
                
                <!-- Quick Search -->
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="conversationSearch" placeholder="Search conversations by user, ID, or content..." onkeyup="filterConversations()">
                    <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">Clear</button>
                </div>
                
                <div class="conversation-list">
                <?php foreach ($conversationGroups as $conv): ?>
                <div class="card mb-3 conversation-item" data-conversation-id="<?php echo htmlspecialchars($conv['conversation_id']); ?>" onclick="toggleConversation('<?php echo htmlspecialchars($conv['conversation_id']); ?>', event)">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="mb-1"><?php echo htmlspecialchars($conv['user_name'] ?: 'Anonymous'); ?></h6>
                                <?php if ($conv['user_id']): ?>
                                    <small class="text-muted">User ID: <?php echo $conv['user_id']; ?></small><br>
                                <?php endif; ?>
                                <small class="text-muted">Conv: <?php echo htmlspecialchars(substr($conv['conversation_id'], 0, 20)); ?>...</small>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="conversation-stats">
                            <div class="conversation-stat">
                                <span style="color: #007bff;">❓</span>
                                <?php echo $conv['questions']; ?> questions
                            </div>
                            <div class="conversation-stat">
                                <span style="color: #28a745;">💬</span>
                                <?php echo $conv['responses']; ?> responses
                            </div>
                            <div class="conversation-stat">
                                <span style="color: #ffc107;">🪙</span>
                                <?php echo number_format($conv['total_tokens']); ?> tokens
                            </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">
                                    Started: <?php echo date('M j, g:i a', strtotime($conv['start_time'])); ?> |
                                    Last activity: <?php echo date('M j, g:i a', strtotime($conv['last_time'])); ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="conversation-details" style="display: none;">
                            <?php foreach ($conv['entries'] as $entry): 
                                $data = json_decode($entry['tracking_data'], true);
                                $entryType = str_replace('ask-goldie-', '', $entry['name']);
                            ?>
                            <div class="alert alert-<?php echo $entryType === 'question' ? 'info' : ($entryType === 'response' ? 'success' : 'secondary'); ?> py-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span class="badge bg-<?php echo $entryType === 'question' ? 'info' : ($entryType === 'response' ? 'success' : 'secondary'); ?>">
                                        <?php echo ucfirst($entryType); ?>
                                    </span>
                                    <small class="text-muted"><?php echo date('g:i a', strtotime($entry['create_dt'])); ?></small>
                                </div>
                                <?php if ($entry['name'] === 'ask-goldie-question' && !empty($data['question'])): ?>
                                    <div class="mt-2"><strong>Q:</strong> <?php echo htmlspecialchars($data['question']); ?></div>
                                <?php endif; ?>
                                <?php if ($entry['name'] === 'ask-goldie-response' && !empty($data['answer'])): ?>
                                    <div class="mt-2"><strong>A:</strong> <?php echo nl2br(htmlspecialchars(substr($data['answer'], 0, 300))); ?>...</div>
                                    <?php if (!empty($data['usage'])): ?>
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                Tokens: <?php echo number_format($data['usage']['total_tokens'] ?? 0); ?> | 
                                                Time: <?php echo number_format($data['processing_time'] ?? 0, 2); ?>s
                                                <?php if (!empty($data['model'])): ?>
                                                    | Model: <?php echo htmlspecialchars($data['model']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="mt-3 d-flex justify-content-between align-items-center">
                                <button class="btn btn-sm btn-outline-primary" onclick="viewFullConversation('<?php echo htmlspecialchars($conv['conversation_id']); ?>', event)">
                                    View Full Conversation
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="copyConversationId('<?php echo htmlspecialchars($conv['conversation_id']); ?>', event)">
                                    Copy Conv ID
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Timeline View (Original) -->
    <div id="timeline-view" class="view-section" style="display: none;">
        <h2>Activity Timeline</h2>
        <p class="text-muted">Showing <?php echo number_format($totalCount); ?> entries</p>
        
        <?php foreach ($processedResults as $entry): 
            $data = $entry['decoded_data'] ?? [];
            $entryType = str_replace('ask-goldie-', '', $entry['name']);
            $userName = $entry['first_name'] ? $entry['first_name'] . ' ' . $entry['last_name'] : 'Anonymous';
        ?>
        <div class="conversation-entry <?php echo $entryType; ?>">
            <div class="entry-header">
                <div>
                    <span class="entry-type type-<?php echo str_replace(['response', 'question', 'new-conversation', 'conversation'], ['response', 'question', 'new', 'update'], $entryType); ?>">
                        <?php echo ucfirst($entryType); ?>
                    </span>
                    <?php if (!empty($data['isStaffMode'])): ?>
                        <span class="staff-mode-indicator">STAFF MODE</span>
                    <?php endif; ?>
                </div>
                <div class="entry-meta">
                    <strong><?php echo htmlspecialchars($userName); ?></strong>
                    <?php if ($entry['user_id']): ?>(ID: <?php echo $entry['user_id']; ?>)<?php endif; ?>
                    | <?php echo htmlspecialchars($entry['ip']); ?>
                    | <?php echo date('Y-m-d H:i:s', strtotime($entry['create_dt'])); ?>
                    <?php if (!empty($data['conversation_id'])): ?>
                        | Conv: <code><?php echo htmlspecialchars($data['conversation_id']); ?></code>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($entryType === 'question' && !empty($data['question'])): ?>
                <div class="question-text">
                    <strong>Question:</strong> <?php echo htmlspecialchars($data['question']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($entryType === 'response'): ?>
                <?php if (!empty($data['question'])): ?>
                    <div class="question-text">
                        <strong>Question:</strong> <?php echo htmlspecialchars($data['question']); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($data['answer'])): ?>
                    <div class="answer-text">
                        <strong>Answer:</strong> <?php echo nl2br(htmlspecialchars($data['answer'])); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($data['usage'])): ?>
                    <div class="token-usage">
                        <span class="token-stat">Prompt: <?php echo number_format($data['usage']['prompt_tokens'] ?? 0); ?> tokens</span>
                        <span class="token-stat">Completion: <?php echo number_format($data['usage']['completion_tokens'] ?? 0); ?> tokens</span>
                        <span class="token-stat">Total: <?php echo number_format($data['usage']['total_tokens'] ?? 0); ?> tokens</span>
                        <?php if (!empty($data['processing_time'])): ?>
                            <span class="processing-time">Processing: <?php echo number_format($data['processing_time'], 2); ?>s</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($data['model'])): ?>
                    <div class="mt-2 text-muted" style="font-size: 0.875rem;">
                        Model: <?php echo htmlspecialchars($data['model']); ?> 
                        <?php if (!empty($data['engine'])): ?>(<?php echo htmlspecialchars($data['engine']); ?>)<?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($entryType === 'new-conversation'): ?>
                <div class="mt-2">
                    <strong>New conversation started</strong>
                    <?php if (!empty($data['conversation_id'])): ?>
                        - ID: <code><?php echo htmlspecialchars($data['conversation_id']); ?></code>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($entryType === 'conversation' && !empty($data['message_count'])): ?>
                <div class="mt-2">
                    <strong>Conversation update:</strong> <?php echo $data['message_count']; ?> messages
                </div>
            <?php endif; ?>
            
            <div class="mt-2">
                <span class="toggle-json" onclick="toggleJson(<?php echo $entry['id']; ?>)">Show/Hide Raw JSON</span>
                <pre id="json-<?php echo $entry['id']; ?>" class="json-preview" style="display: none;"><?php echo json_encode($data, JSON_PRETTY_PRINT); ?></pre>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&from=<?php echo $dateFrom; ?>&to=<?php echo $dateTo; ?>&user=<?php echo $userFilter; ?>&conversation=<?php echo $conversationFilter; ?>">Previous</a>
                </li>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&from=<?php echo $dateFrom; ?>&to=<?php echo $dateTo; ?>&user=<?php echo $userFilter; ?>&conversation=<?php echo $conversationFilter; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&from=<?php echo $dateFrom; ?>&to=<?php echo $dateTo; ?>&user=<?php echo $userFilter; ?>&conversation=<?php echo $conversationFilter; ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
    <!-- End Timeline View -->
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>