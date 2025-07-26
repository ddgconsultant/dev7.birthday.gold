<?php
// abo-status.php - Admin page to monitor ABO (Automated Business Onboarding) progress
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Admin access is handled by site-controller.php
// This page is in /admin/ directory which should have proper access control

$pagetitle = "ABO Status Monitor";
$messages = array();

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_company = $_GET['company_id'] ?? null;

// Get all ABO processors from bg_config
$processors_sql = "SELECT 
    config_key as processor_key,
    config_value as processor_name,
    config_data
FROM bg_config
WHERE config_type = 'automation_processor'
AND is_active = 1
ORDER BY display_order";

$processors_stmt = $database->query($processors_sql);
$processors = $processors_stmt->fetchAll(PDO::FETCH_ASSOC);

// Parse processor data
foreach ($processors as &$processor) {
    $processor['data'] = json_decode($processor['config_data'], true);
}

// Get companies - include ALL companies from user recommendations
$companies_sql = "SELECT c.company_id, c.company_name, c.status, c.create_dt
FROM bg_companies c
WHERE c.source = 'user_recommendation'";

$params = [];
if ($filter_company) {
    $companies_sql .= " AND c.company_id = :company_id";
    $params['company_id'] = $filter_company;
}

if ($filter_status !== 'all') {
    $companies_sql .= " AND c.status = :status";
    $params['status'] = $filter_status;
}

$companies_sql .= " GROUP BY c.company_id, c.company_name, c.status, c.create_dt ORDER BY c.create_dt DESC LIMIT 100";

$companies_stmt = $database->query($companies_sql, $params);
$companies = $companies_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get progress for each company
foreach ($companies as $key => $company) {
    // Get progress with all details
    $progress_sql = "SELECT 
        name as processor_key,
        description as status,
        create_dt,
        modify_dt
    FROM bg_company_attributes
    WHERE company_id = :company_id
    AND type = 'onboarding_progress'
    AND status = 'active'";
    
    $progress_stmt = $database->query($progress_sql, ['company_id' => $company['company_id']]);
    $progress_details = $progress_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create progress array with processor_key as key and status as value
    $company['progress'] = [];
    $company['progress_details'] = [];
    foreach ($progress_details as $detail) {
        $company['progress'][$detail['processor_key']] = $detail['status'];
        $company['progress_details'][$detail['processor_key']] = $detail;
    }
    
    // Calculate completion percentage
    $total_steps = count($processors);
    $completed_steps = 0;
    
    // Calculate completion percentage based on actual progress
    foreach ($company['progress'] as $status) {
        if ($status === 'completed') $completed_steps++;
    }
    $company['completion_percentage'] = $total_steps > 0 ? round(($completed_steps / $total_steps) * 100) : 0;
    
    // Update the array with the modified company data
    $companies[$key] = $company;
}

// Reset array pointer after reference usage
unset($company);

// Get status counts
$status_counts_sql = "SELECT status, COUNT(*) as count 
FROM bg_companies 
WHERE source = 'user_recommendation'
GROUP BY status";
$status_counts = $database->query($status_counts_sql)->fetchAll(PDO::FETCH_KEY_PAIR);

$bodycontentclass = '';
$header_flush = true;

$additionalstyles = '
<style>
.abo-container {
    max-width: 1400px;
    margin: 0 auto;
}

/* Modern Tab Navigation */
.nav-tabs-modern {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    gap: 0;
    overflow: visible;
    position: relative;
}

.nav-tab-item {
    flex: 0 0 auto;
    padding: 1rem 1.5rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.3s ease-out;
    background: none;
    border-radius: 0;
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-tab-item::after {
    content: "";
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 3px;
    background-color: #6c757d;
    transition: width 0.3s ease-out;
}

.nav-tab-item:hover {
    color: #495057;
    text-decoration: none;
}

.nav-tab-item:hover::after {
    width: 100%;
}

.nav-tab-item.active {
    color: #0d6efd;
    border-bottom: 3px solid #0d6efd;
    background: none;
    position: relative;
    z-index: 1;
}

.nav-tab-item.active::after {
    display: none;
}

/* Badge styling */
.badge-count {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
    border-radius: 0.375rem;
    margin-left: 0.25rem;
}

.badge-count.secondary {
    background-color: #f8c146;
    color: #000;
}

.badge-count.warning {
    background-color: #f8c146;
    color: #000;
}

.badge-count.info {
    background-color: #17a2b8;
    color: #fff;
}

.badge-count.primary {
    background-color: #0d6efd;
    color: #fff;
}

.badge-count.success {
    background-color: #198754;
    color: #fff;
}

.processor-table {
    width: 100%;
    margin-bottom: 20px;
}

.processor-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    padding: 8px 10px;
    text-align: left;
    border-bottom: 2px solid #dee2e6;
    font-size: 0.875rem;
}

.processor-table td {
    padding: 6px 10px;
    border-bottom: 1px solid #dee2e6;
    vertical-align: middle;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    min-width: 90px;
    text-align: center;
}

.status-badge.not_started {
    background-color: #e9ecef;
    color: #6c757d;
}

.status-badge.pending {
    background-color: #f8f9fa;
    color: #6c757d;
}

.status-badge.in_progress {
    background-color: #cfe2ff;
    color: #0d6efd;
    animation: pulse 2s infinite;
}

.status-badge.completed {
    background-color: #d1e7dd;
    color: #198754;
}

.status-badge.attempted {
    background-color: #fff3cd;
    color: #856404;
}

.status-badge.error {
    background-color: #f8d7da;
    color: #dc3545;
}

.status-badge.skipped {
    background-color: #fff3cd;
    color: #856404;
}


@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.progress-bar-container {
    position: relative;
    background-color: #e9ecef;
    height: 30px;
    border-radius: 15px;
    overflow: hidden;
    margin-bottom: 15px;
}

.progress-bar-fill {
    height: 100%;
    background-color: #0d6efd;
    transition: width 0.3s ease;
}

.progress-bar-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.875rem;
    font-weight: bold;
    color: #fff;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.table > tbody > tr.collapse > td {
    padding: 0;
    border: 0;
}

.table-hover > tbody > tr:hover {
    background-color: #f8f9fa;
}

.legend {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 15px;
    font-size: 0.8rem;
}

.legend-item {
    display: flex;
    align-items: center;
    font-size: 0.8rem;
}

.processor-description {
    font-size: 0.8rem;
    color: #6c757d;
    margin: 0;
}

/* Action buttons styling */
.table .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Ensure progress bar text is visible */
.progress {
    font-size: 0.75rem;
    font-weight: 600;
}

/* Mobile Card View Styles */
.mobile-card-view {
    display: block;
}

@media (min-width: 768px) {
    .mobile-card-view {
        display: none;
    }
}

.desktop-table-view {
    display: none;
}

@media (min-width: 768px) {
    .desktop-table-view {
        display: block;
    }
}

.company-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    overflow: hidden;
    transition: box-shadow 0.2s;
}

.company-card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.company-card-header {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    background-color: #f8f9fa;
}

.company-card-body {
    padding: 1rem;
}

.mobile-progress-section {
    margin-bottom: 1rem;
}

.mobile-status-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
    margin-top: 0.5rem;
}

@media (max-width: 400px) {
    .mobile-status-grid {
        grid-template-columns: 1fr;
    }
}

.mobile-status-item {
    background: #f8f9fa;
    padding: 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    min-height: 55px;
    overflow: hidden;
}

.mobile-status-item .processor-name {
    font-weight: 600;
    color: #495057;
    display: block;
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 0.7rem;
    max-width: calc(100% - 2px);
}

.mobile-status-item .status-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.25rem;
    flex-wrap: nowrap;
}

.mobile-status-item .status-badge {
    flex-shrink: 0;
    min-width: 70px;
    max-width: 70px;
    text-align: center;
    display: inline-block;
    font-size: 0.65rem;
    padding: 2px 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

@media (max-width: 400px) {
    .mobile-status-item .status-badge {
        min-width: 85px;
        max-width: 85px;
        font-size: 0.7rem;
        padding: 3px 6px;
    }
}

.mobile-status-item .trigger-icon {
    color: #0d6efd;
    font-size: 1.1rem;
    line-height: 1;
    opacity: 0.8;
    transition: opacity 0.2s;
    margin-left: auto;
}

.mobile-status-item .trigger-icon:hover {
    opacity: 1;
}

/* Mobile Action Buttons */
.mobile-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.mobile-actions .btn {
    flex: 1;
}

/* Responsive Table Adjustments */
@media (max-width: 767px) {
    .table th, .table td {
        padding: 0.5rem;
        font-size: 0.875rem;
    }
    
    .table .btn-sm {
        padding: 0.2rem 0.4rem;
        font-size: 0.75rem;
    }
}

/* Touch-friendly buttons */
.btn-trigger-mobile {
    min-width: 44px;
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Tab Navigation Mobile Styling */
@media (max-width: 576px) {
    .nav-tabs-modern {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        white-space: nowrap;
    }
    
    .nav-tab-item {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }
    
    /* Hide scrollbar but keep functionality */
    .nav-tabs-modern::-webkit-scrollbar {
        display: none;
    }
    .nav-tabs-modern {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
}

/* Remove container padding on mobile */
@media (max-width: 767px) {
    .main-content .container {
        padding-left: 0;
        padding-right: 0;
    }
    
    .abo-container {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    
    .company-card {
        border-radius: 0;
        border-left: none;
        border-right: none;
    }
}
</style>';

$additionalscripts = '
<script>
function refreshStatus() {
    location.reload();
}

// Auto-refresh every 30 seconds if there are in-progress items
if (document.querySelector(".status-badge.in_progress")) {
    setTimeout(refreshStatus, 30000);
}

// Optional: Prevent double-clicking on trigger links
document.addEventListener("click", function(e) {
    if (e.target.closest("a.btn") && e.target.closest("a.btn").innerHTML.includes("Trigger")) {
        const link = e.target.closest("a.btn");
        if (link.dataset.clicked) {
            e.preventDefault();
            return false;
        }
        link.dataset.clicked = "true";
        link.innerHTML = \'<i class="bi bi-hourglass-split"></i> Loading...\';
        link.classList.add("disabled");
    }
});

// Toggle legend button text
document.addEventListener("DOMContentLoaded", function() {
    const legendCollapse = document.getElementById("legendCollapse");
    const legendButton = document.querySelector(\'[data-bs-target="#legendCollapse"]\');
    
    if (legendCollapse && legendButton) {
        legendCollapse.addEventListener("show.bs.collapse", function() {
            legendButton.innerHTML = \'<i class="bi bi-info-circle"></i> Hide Legend\';
        });
        
        legendCollapse.addEventListener("hide.bs.collapse", function() {
            legendButton.innerHTML = \'<i class="bi bi-info-circle"></i> Show Legend\';
        });
    }
    
    // Handle company detail chevron rotation
    document.querySelectorAll(\'[data-bs-toggle="collapse"]\').forEach(button => {
        if (button.getAttribute(\'data-bs-target\').startsWith(\'#company-\')) {
            const targetId = button.getAttribute(\'data-bs-target\');
            const collapseEl = document.querySelector(targetId);
            
            if (collapseEl) {
                collapseEl.addEventListener(\'show.bs.collapse\', function() {
                    button.innerHTML = \'<i class="bi bi-chevron-up"></i> Details\';
                });
                
                collapseEl.addEventListener(\'hide.bs.collapse\', function() {
                    button.innerHTML = \'<i class="bi bi-chevron-down"></i> Details\';
                });
            }
        }
    });
});
</script>';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="content-header-admin no-rounded-corners">
    <div class="container">
        <h1 class="mt-3">ABO Status Monitor</h1>
        <p class="lead mb-4">Automated Business Onboarding Progress Tracker</p>
    </div>
</div>

<div class="main-content py-4 py-md-5 bg-light">
    <div class="container">
        <div class="abo-container">
            <!-- Modern Tab Navigation with Refresh -->
            <div class="d-flex align-items-center justify-content-between mb-2">
                <nav class="nav-tabs-modern flex-grow-1">
                    <a href="?status=all" class="nav-tab-item <?php echo $filter_status === 'all' ? 'active' : ''; ?>">
                        All 
                        <span class="badge-count secondary"><?php echo array_sum($status_counts); ?></span>
                    </a>
                    
                    <a href="?status=submitted" class="nav-tab-item <?php echo $filter_status === 'submitted' ? 'active' : ''; ?>">
                        Submitted
                        <?php if (($status_counts['submitted'] ?? 0) > 0): ?>
                        <span class="badge-count warning"><?php echo $status_counts['submitted']; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="?status=pending_review" class="nav-tab-item <?php echo $filter_status === 'pending_review' ? 'active' : ''; ?>">
                        Pending Review
                        <?php if (($status_counts['pending_review'] ?? 0) > 0): ?>
                        <span class="badge-count info"><?php echo $status_counts['pending_review']; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="?status=approved_pending_data" class="nav-tab-item <?php echo $filter_status === 'approved_pending_data' ? 'active' : ''; ?>">
                        In Progress
                        <?php if (($status_counts['approved_pending_data'] ?? 0) > 0): ?>
                        <span class="badge-count primary"><?php echo $status_counts['approved_pending_data']; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="?status=active" class="nav-tab-item <?php echo $filter_status === 'active' ? 'active' : ''; ?>">
                        Active
                        <?php if (($status_counts['active'] ?? 0) > 0): ?>
                        <span class="badge-count success"><?php echo $status_counts['active']; ?></span>
                        <?php endif; ?>
                    </a>
                </nav>
                
                <button onclick="refreshStatus()" class="btn btn-sm btn-outline-primary ms-3 d-none d-md-block">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>

            <!-- Legend (Collapsible) -->
            <div class="mb-4">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#legendCollapse" aria-expanded="false" aria-controls="legendCollapse">
                    <i class="bi bi-info-circle"></i> Show Legend
                </button>
                <div class="collapse" id="legendCollapse">
                    <div class="legend mt-3 p-3 bg-white border rounded">
                        <div class="legend-item">
                            <span class="status-badge not_started">Not Started</span>
                            <span class="ms-1">- Processor has not been run yet</span>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge pending">Pending</span>
                            <span class="ms-1">- Waiting to be processed</span>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge in_progress">In Progress</span>
                            <span class="ms-1">- Currently processing</span>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge completed">Completed</span>
                            <span class="ms-1">- Successfully processed</span>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge attempted">Attempted</span>
                            <span class="ms-1">- Partially successful</span>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge error">Error</span>
                            <span class="ms-1">- Processing failed</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Company Progress -->
            <?php if (empty($companies)): ?>
                <div class="alert alert-info">
                    No companies found with the selected filter.
                </div>
            <?php else: ?>
                
                <!-- Mobile Card View -->
                <div class="mobile-card-view">
                    <?php foreach ($companies as $company): ?>
                    <div class="company-card">
                        <div class="company-card-header">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($company['company_name']); ?></h6>
                                    <small class="text-muted">ID: <?php echo $company['company_id']; ?></small>
                                </div>
                                <span class="badge bg-<?php 
                                    echo $company['status'] === 'active' ? 'success' : 
                                        ($company['status'] === 'approved_pending_data' ? 'primary' : 
                                        ($company['status'] === 'pending_final_review' ? 'info' : 
                                        ($company['status'] === 'pending_review' ? 'warning' : 'secondary'))); 
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $company['status'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="company-card-body">
                            <!-- Progress Section -->
                            <div class="mobile-progress-section">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">Progress</small>
                                    <small class="text-muted"><?php echo $company['completion_percentage']; ?>%</small>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $company['completion_percentage']; ?>%" 
                                         aria-valuenow="<?php echo $company['completion_percentage']; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Status Grid with Trigger Icons -->
                            <div class="mobile-status-grid">
                                <?php foreach ($processors as $processor): 
                                    $processor_status = $company['progress'][$processor['processor_key']] ?? 'not_started';
                                    $scheduler_file = $processor['data']['scheduler_file'] ?? '';
                                    $can_trigger = in_array($company['status'], ['pending_review', 'approved_pending_data', 'active']) && 
                                                  !in_array($processor_status, ['in_progress', 'completed']);
                                ?>
                                <div class="mobile-status-item">
                                    <span class="processor-name"><?php echo htmlspecialchars($processor['processor_name']); ?></span>
                                    <div class="status-line">
                                        <span class="status-badge <?php echo $processor_status; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $processor_status)); ?>
                                        </span>
                                        <?php if ($can_trigger): ?>
                                            <?php 
                                            $encoded_id = $qik->encodeID($company['company_id']);
                                            $trigger_url = "/admin_actions/abo/{$scheduler_file}?id={$encoded_id}&rawid={$company['company_id']}";
                                            ?>
                                            <a href="<?php echo htmlspecialchars($trigger_url); ?>" 
                                               class="trigger-icon" 
                                               target="_blank"
                                               title="Trigger <?php echo htmlspecialchars($processor['processor_name']); ?>">
                                                <i class="bi bi-play-circle-fill"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Actions -->
                            <div class="mobile-actions">
                                <button class="btn btn-sm btn-outline-secondary" 
                                        type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#mobile-details-<?php echo $company['company_id']; ?>" 
                                        aria-expanded="false">
                                    <i class="bi bi-list-ul"></i> Details
                                </button>
                                <a href="/admin/company-editor-main?cid=<?php echo $company['company_id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                            </div>
                            
                            <!-- Collapsible Details -->
                            <div class="collapse mt-3" id="mobile-details-<?php echo $company['company_id']; ?>">
                                <div class="border rounded p-2">
                                    <small class="text-muted d-block mb-2">Submitted: <?php echo date('M j, Y', strtotime($company['create_dt'])); ?></small>
                                    <?php 
                                    foreach ($processors as $processor): 
                                        $processor_status = $company['progress'][$processor['processor_key']] ?? 'not_started';
                                        $scheduler_file = $processor['data']['scheduler_file'] ?? '';
                                        $can_trigger = in_array($company['status'], ['pending_review', 'approved_pending_data', 'active']) && 
                                                      !in_array($processor_status, ['in_progress', 'completed']);
                                    ?>
                                    <div class="mb-2 pb-2 border-bottom">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <small class="fw-bold d-block"><?php echo htmlspecialchars($processor['processor_name']); ?></small>
                                                <small class="text-muted"><?php echo htmlspecialchars($processor['data']['description']); ?></small>
                                            </div>
                                            <?php if ($can_trigger): ?>
                                                <?php 
                                                $encoded_id = $qik->encodeID($company['company_id']);
                                                $trigger_url = "/admin_actions/abo/{$scheduler_file}?id={$encoded_id}&rawid={$company['company_id']}";
                                                ?>
                                                <a href="<?php echo htmlspecialchars($trigger_url); ?>" 
                                                   class="btn btn-sm btn-outline-primary ms-2" 
                                                   target="_blank">
                                                    <i class="bi bi-play-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Desktop Table View -->
                <div class="desktop-table-view">
                <!-- Summary View Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th class="col-5">Company</th>
                                <th class="col-2">Status</th>
                                <th class="col-2">Progress</th>
                                <th class="col-1">Submitted</th>
                                <th class="col-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($companies as $company): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($company['company_name']); ?></div>
                                        <small class="text-muted">ID: <?php echo $company['company_id']; ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $company['status'] === 'active' ? 'success' : 
                                                ($company['status'] === 'approved_pending_data' ? 'primary' : 
                                                ($company['status'] === 'pending_final_review' ? 'info' : 
                                                ($company['status'] === 'pending_review' ? 'warning' : 'secondary'))); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $company['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo $company['completion_percentage']; ?>%" 
                                                 aria-valuenow="<?php echo $company['completion_percentage']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $company['completion_percentage']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted small">
                                        <?php echo date('M j, Y', strtotime($company['create_dt'])); ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#company-<?php echo $company['company_id']; ?>" 
                                                aria-expanded="false">
                                            <i class="bi bi-chevron-down"></i> Details
                                        </button>
                                        <a href="/admin/company-editor-main?cid=<?php echo $company['company_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                                <tr class="collapse" id="company-<?php echo $company['company_id']; ?>">
                                    <td colspan="5" class="p-0">
                                        <div class="p-3 bg-light">
                                            <!-- Processor Table -->
                                            <h6 class="mb-3">Processor Status</h6>
                                            <table class="processor-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th class="col-1">#</th>
                                                        <th class="col-5">Processor</th>
                                                        <th class="col-2">Status</th>
                                                        <th class="col-2">Last Updated</th>
                                                        <th class="col-2">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $step_num = 1;
                                                    foreach ($processors as $processor): 
                                                        $processor_status = $company['progress'][$processor['processor_key']] ?? 'not_started';
                                                        $scheduler_file = $processor['data']['scheduler_file'] ?? '';
                                                        
                                                        // Get last updated time if available
                                                        $last_updated = '';
                                                        if (isset($company['progress_details'][$processor['processor_key']])) {
                                                            $detail = $company['progress_details'][$processor['processor_key']];
                                                            if ($detail['modify_dt']) {
                                                                $last_updated = date('M j, g:i A', strtotime($detail['modify_dt']));
                                                            }
                                                        }
                                                    ?>
                                                    <tr>
                                                        <td class="text-muted"><?php echo $step_num++; ?></td>
                                                        <td>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($processor['processor_name']); ?></div>
                                                            <div class="processor-description"><?php echo htmlspecialchars($processor['data']['description']); ?></div>
                                                        </td>
                                                        <td>
                                                            <span class="status-badge <?php echo $processor_status; ?>">
                                                                <?php echo ucfirst(str_replace('_', ' ', $processor_status)); ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-muted small">
                                                            <?php echo $last_updated ?: '-'; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (in_array($company['status'], ['pending_review', 'approved_pending_data', 'active'])): ?>
                                                                <?php if (!in_array($processor_status, ['in_progress', 'completed'])): ?>
                                                                    <?php 
                                                                    $encoded_id = $qik->encodeID($company['company_id']);
                                                                    $trigger_url = "/admin_actions/abo/{$scheduler_file}?id={$encoded_id}&rawid={$company['company_id']}";
                                                                    ?>
                                                                    <a href="<?php echo htmlspecialchars($trigger_url); ?>" 
                                                                       class="btn btn-sm btn-outline-primary" 
                                                                       target="_blank"
                                                                       title="Manually trigger this processor">
                                                                        <i class="bi bi-play-circle"></i> Trigger
                                                                    </a>
                                                                <?php else: ?>
                                                                    <span class="text-muted small">Processing...</span>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="text-muted small">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                </div><!-- End desktop table view -->
            <?php endif; ?>
            
            <!-- Mobile Floating Refresh Button -->
            <div class="position-fixed bottom-0 end-0 p-3 d-md-none" style="z-index: 100;">
                <button onclick="refreshStatus()" class="btn btn-primary rounded-circle shadow" style="width: 56px; height: 56px;">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>