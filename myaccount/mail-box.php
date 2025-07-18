<?php
// inbox.php
$addClasses[] = 'mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Mail Inbox";

// Security Note: Message IDs are encoded using $qik->encodeId() when displayed in URLs 
// and data attributes, then decoded when processing requests. Server names are validated
// with regex to ensure they only contain safe characters.

// Retrieve any messages from previous page (e.g., mail-read.php errors)
$transferpagedata = $system->startpostpage();

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------


$errormessage = '';

#$uid=($qik->decodeId($_REQUEST['uid']) ?? $current_user_data['user_id']);
$uid = !empty($_REQUEST['uid']) ? $qik->decodeId($_REQUEST['uid']) : $current_user_data['user_id'];

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------


// Initialize variables for sorting and filtering
$sort = $_GET['sort'] ?? 'date';
$order = $_GET['order'] ?? 'desc';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 100;

// Get company filter from URL (decode if present)
$company_filter_encoded = $_GET['company'] ?? '';
$company_filter = $company_filter_encoded ? $qik->decodeId($company_filter_encoded) : '';

// Store current filter/search state in session for "Back to Inbox" functionality
$_SESSION['mail_box_state'] = [
    'sort' => $sort,
    'order' => $order,
    'search' => $search,
    'company' => $company_filter_encoded,
    'page' => $page
];

// Get messages
$filter_params = [
    'sort' => $sort,
    'order' => $order,
    'search' => $search,
    'page' => $page,
    'per_page' => $per_page,
    'exclude_deleted' => true
];

// Add company filter if specified
if ($company_filter) {
    $filter_params['company_id'] = $company_filter;
}

// First get ALL messages to build company list (without pagination)
$all_messages_results = $mail->getmessagelist($uid, 'user', [
    'per_page' => 1000, // Get a large number to see all companies
    'exclude_deleted' => true
]);

// Get unique companies for filter dropdown from ALL messages
$unique_companies = [];
if (!empty($all_messages_results['messages'])) {
    foreach ($all_messages_results['messages'] as $message) {
        if (!empty($message['company_id'])) {
            $company = $app->getcompany($message['company_id']);
            if ($company && !isset($unique_companies[$message['company_id']])) {
                $unique_companies[$message['company_id']] = $company['company_display_name'] ?? 'Unknown';
            }
        }
    }
}
asort($unique_companies); // Sort alphabetically by company name

// If company filter is set, we need to get all messages and filter manually
if ($company_filter) {
    // Get all messages without pagination for filtering
    $all_filter_params = [
        'sort' => $sort,
        'order' => $order,
        'search' => $search,
        'per_page' => 1000
    ];
    
    $all_results = $mail->getmessagelist($uid, 'user', $all_filter_params);
    $all_messages = $all_results['messages'] ?? [];
    
    // Filter by company
    $filtered_messages = [];
    foreach ($all_messages as $message) {
        if (!empty($message['company_id']) && $message['company_id'] == $company_filter) {
            $filtered_messages[] = $message;
        }
    }
    
    // Calculate pagination
    $total_messages = count($filtered_messages);
    $total_pages = ceil($total_messages / $per_page);
    
    // Apply pagination
    $messages = array_slice($filtered_messages, ($page - 1) * $per_page, $per_page);
} else {
    // No company filter, use normal pagination
    $messages_results = $mail->getmessagelist($uid, 'user', $filter_params);
    $messages = $messages_results['messages'];
    $total_messages = $messages_results['counts']['total'];
    $total_pages = ceil($total_messages / $per_page);
}


// Add v7 theme CSS and custom styles
$additionalstyles = '<link rel="stylesheet" href="/public/css/v7/bg_theme.css">
<style>
.message-row { transition: background-color 0.15s ease-in-out; }
.message-row:hover { background-color: rgba(0, 0, 0, .03); }
.message-row.selected { background-color: rgba(13, 110, 253, .1); }
.message-row.unread { background-color: rgba(248, 249, 250, .7); font-weight: 600; }

/* Only content cells are clickable, not the checkbox cell */
.message-row td:first-child { cursor: default; }

/* Make checkbox area not show pointer cursor */
.message-row .form-check { cursor: default; }
.message-row .form-check-input { cursor: pointer; }
.company-logo { width: 32px; height: 32px; object-fit: cover; border-radius: 4px; }
.sort-icon { opacity: 0.3; }
.sort-active .sort-icon { opacity: 1; }
@media (max-width:768px) {
    .sender-col { max-width: 120px; }
    .date-col { max-width: 70px; }
    
    /* Mobile tabs adjustment */
    .nav-tabs-clean {
        flex-wrap: nowrap;
    }
    
    /* Full width container on mobile */
    .container {
        padding-left: 0 !important;
        padding-right: 0 !important;
        max-width: 100% !important;
    }
    
    /* Keep search container padded */
    .search-container {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    /* Tabs container adjustments */
    .tabs-container {
        margin-bottom: 0;
        padding-left: 1rem;
        padding-right: 1rem;
        border-bottom: none;
    }
    
    /* Reduce tab padding and font on mobile */
    .nav-tab-clean a {
        padding: 0.5rem 0.25rem;
        font-size: 0.8125rem;
        letter-spacing: 0;
    }
    
    /* Override desktop margin on mobile */
    .nav-tab-clean {
        margin-right: 0.5rem !important;
    }
    
    /* Even less margin for settings tab */
    .nav-tab-clean.settings-tab {
        margin-left: 0.5rem;
    }
    
    /* Hide tab icons on mobile to save space */
    .nav-tab-clean a i:not(.bi-gear-fill) {
        display: none;
    }
    
    /* Collapsible filter bar on mobile */
    .filter-bar {
        position: relative;
        transition: max-height 0.3s ease;
        overflow: hidden;
        margin: 0;
        max-height: 500px; /* Enough for expanded content */
        border-radius: 0;
    }
    
    .filter-bar.collapsed {
        max-height: 40px;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .filter-bar-toggle {
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 40px;
        background: #f8f9fa;
        border: none;
        border-top: 1px solid #dee2e6;
        border-bottom: 1px solid #dee2e6;
        cursor: pointer;
        position: relative;
        gap: 0.5rem;
    }
    
    .filter-bar-toggle .filter-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #495057;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .filter-bar-toggle .handle {
        width: 30px;
        height: 3px;
        background: #6c757d;
        border-radius: 2px;
        transition: transform 0.3s ease;
    }
    
    .filter-bar.collapsed .filter-bar-toggle .handle {
        transform: scaleY(1);
    }
    
    .filter-bar:not(.collapsed) .filter-bar-toggle .handle {
        transform: scaleY(1.5);
    }
    
    .filter-content {
        padding: 1rem;
        background: #f8f9fa;
    }
    
    /* Table and message list adjustments */
    .card {
        border-radius: 0;
        border-left: 0;
        border-right: 0;
        margin-top: 0;
    }
    
    .table {
        margin-bottom: 0;
    }
    
    /* Mobile message row styling */
    .message-row {
        background: white;
        border: none;
        border-bottom: 1px solid #e9ecef;
        border-radius: 0;
        margin-bottom: 0;
    }
    
    .message-row:hover {
        background-color: #f8f9fa;
    }
    
    .message-row td {
        border: none;
        position: relative;
        padding: 0.75rem 1rem;
    }
    
    /* Position checkbox on mobile */
    .message-row td:first-child {
        width: 40px;
        vertical-align: top;
        padding: 0.75rem 0.5rem 0.75rem 1rem !important;
    }
    
    /* Mobile content cell - ensure it shows */
    .message-row td.d-md-none {
        padding: 0.75rem 1rem 0.75rem 0.5rem !important;
        display: table-cell !important;
    }
    
    /* Mobile compact layout */
    .mobile-message-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.25rem;
    }
    
    .mobile-company-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        min-width: 0; /* Allow truncation */
        flex: 1; /* Take available space but allow date to show */
    }
    
    .mobile-company-info span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .mobile-date {
        font-size: 0.75rem;
        color: #6c757d;
        flex-shrink: 0;
        margin-left: 0.5rem;
    }
    
    .mobile-subject {
        font-size: 0.9375rem;
        line-height: 1.4;
        font-weight: normal;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    /* Make unread subjects slightly bolder */
    .unread .mobile-subject {
        font-weight: 500;
    }
    
    /* Mobile pagination layout */
    .pagination-info {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-top: 1px solid #dee2e6;
    }
    
    .pagination-text {
        font-size: 0.875rem;
        color: #6c757d;
        margin: 0;
    }
    
    .pagination {
        margin: 0;
    }
    
    /* Smaller pagination buttons on mobile */
    .pagination .page-link {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    
}

/* Clean modern tab navigation - like Material Design */
.nav-tabs-clean {
    display: flex;
    border-bottom: none;
    padding: 0;
    list-style: none;
}

/* Container for tabs and gear button */
.tabs-container {
    border-bottom: 1px solid #e0e0e0;
    margin-bottom: 0;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
}

/* Hide scrollbar for Chrome, Safari and Opera */
.tabs-container::-webkit-scrollbar {
    display: none;
}

/* Ensure tabs don\'t wrap on any screen size */
.tabs-container .nav-tabs-clean {
    flex-wrap: nowrap;
    min-width: max-content;
}

.nav-tab-clean {
    position: relative;
    margin-right: 2rem;
}

@media (max-width: 768px) {
    .nav-tab-clean {
        margin-right: 1rem;
    }
}

/* Remove margin for the settings gear */
.nav-tab-clean.settings-tab {
    margin-right: 0;
}

.nav-tab-clean a {
    display: block;
    padding: 1rem 1.5rem;
    text-decoration: none;
    color: #666;
    font-weight: 500;
    font-size: 16px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: color 0.2s ease;
    border: none;
    background: none;
    white-space: nowrap;
}

.nav-tab-clean a:hover {
    color: #333;
}

.nav-tab-clean.active a {
    color: #0d6efd;
}

.nav-tab-clean.active::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background-color: #0d6efd;
}

.tab-badge {
    display: inline-block;
    min-width: 20px;
    padding: 2px 6px;
    margin-left: 8px;
    font-size: 12px;
    font-weight: 500;
    line-height: 1;
    color: #fff;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    background-color: #dc3545;
    border-radius: 10px;
}

@media (max-width: 768px) {
    .tab-badge {
        font-size: 10px;
        padding: 1px 4px;
        margin-left: 4px;
        min-width: 16px;
    }
}

/* Search bar styles - matching help page */
.search-container {
    max-width: 600px;
    margin: -2rem auto 3rem;
    position: relative;
    z-index: 10;
}

.search-input {
    width: 100%;
    padding: 1rem 3rem 1rem 1.5rem;
    font-size: 1.125rem;
    border: 1px solid #dee2e6;
    border-radius: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.search-input::placeholder {
    color: #adb5bd;
}

.search-icon {
    position: absolute;
    right: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}

/* Filter bar styles */
.filter-bar {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    margin-top: 0;
}

/* Hide filter toggle on desktop */
.filter-bar-toggle {
    display: none;
}

/* Remove collapsed state on desktop */
@media (min-width: 769px) {
    .filter-bar.collapsed {
        max-height: none !important;
        height: auto !important;
        padding: 1.5rem !important;
    }
}

/* Modern dropdown styles */
.form-select {
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    padding: 0.6rem 2.5rem 0.6rem 1rem;
    font-weight: 500;
    transition: all 0.2s ease;
    background-color: white;
}

.form-select:hover {
    border-color: #c0c0c0;
}

.form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, .1);
}

/* Dropdown button styles */
.dropdown-toggle {
    border-radius: 25px;
    padding: 0.5rem 1.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
    border: 2px solid #dee2e6;
}

.dropdown-toggle:not(:disabled):hover {
    background-color: #f8f9fa;
    border-color: #adb5bd;
}

.dropdown-toggle:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Dropdown menu styles */
.dropdown-menu {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding: 0.5rem;
}

.dropdown-item {
    border-radius: 8px;
    padding: 0.7rem 1rem;
    transition: all 0.2s ease;
    font-weight: 500;
}

.dropdown-item:hover {
    background-color: #f0f0f0;
}

.dropdown-item.text-danger:hover {
    background-color: #fee;
    color: #dc3545;
}

/* Label styles */
.form-label {
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.75rem;
}
</style>';
    
    
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-envelope me-3"></i>Mail Inbox</h1>
            <p class="lead mb-4">View and manage your birthday reward messages for <?php echo htmlspecialchars($current_user_data['feature_email'] ?? ''); ?></p>
        </div>
    </div>
</div>

<div class="container">
    <!-- Search bar outside header -->
    <div class="search-container">
        <form method="GET" id="search-form">
            <div class="position-relative">
                <input type="text" 
                       class="search-input" 
                       placeholder="Search messages..." 
                       name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       id="mailSearch"
                       autocomplete="off">
                <i class="bi bi-search search-icon"></i>
            </div>
        </form>
    </div>
    
    <?php if (!empty($transferpagedata['message'])): ?>
        <div class="alert-container" style="max-width: 800px; margin: 2rem auto;">
            <?php echo $transferpagedata['message']; ?>
        </div>
    <?php endif; ?>
</div>

<div class="container" style="margin-top: -1rem;">
        <!-- Clean tab navigation with settings gear -->
        <div class="d-flex justify-content-between align-items-center tabs-container">
            <ul class="nav-tabs-clean mb-0">
                <li class="nav-tab-clean active">
                    <a href="#all" data-bs-toggle="tab">
                        <i class="bi bi-envelope me-2"></i>All Mail
                        <?php if ($total_messages > 0): ?>
                        <span class="tab-badge bg-secondary"><?php echo $total_messages; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-tab-clean">
                    <a href="#unread" data-bs-toggle="tab">
                        <i class="bi bi-envelope-exclamation me-2"></i>Unread
                        <span class="tab-badge" id="unreadCount">0</span>
                    </a>
                </li>
                <li class="nav-tab-clean">
                    <a href="#read" data-bs-toggle="tab">
                        <i class="bi bi-envelope-open me-2"></i>Read
                    </a>
                </li>
                <li class="nav-tab-clean">
                    <a href="#starred" data-bs-toggle="tab">
                        <i class="bi bi-star me-2"></i>Starred
                    </a>
                </li>
            </ul>
            <ul class="nav-tabs-clean mb-0">
                <li class="nav-tab-clean settings-tab">
                    <a href="/myaccount/manage-mail#settings" id="settingsButton">
                        <i class="bi bi-gear-fill"></i>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar collapsed" id="filterBar">
            <!-- Mobile toggle button - must be at top -->
            <button class="filter-bar-toggle d-md-none" onclick="toggleFilterBar()" type="button">
                <span class="filter-label">Filter</span>
                <div class="handle"></div>
            </button>
            
            <!-- Filter content wrapper -->
            <div class="filter-content">
                <!-- Desktop and Mobile filters -->
                <div class="row g-3 align-items-end">
                <div class="col-md-2 d-none d-md-block">
                    <div class="btn-group">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" id="bulkActionsBtn" disabled>
                            <i class="bi bi-check2-square me-1"></i> Actions
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item bulk-action" href="#" data-action="mark-read">
                                <i class="bi bi-envelope-open me-2"></i>Mark as Read
                            </a></li>
                            <li><a class="dropdown-item bulk-action" href="#" data-action="mark-unread">
                                <i class="bi bi-envelope me-2"></i>Mark as Unread
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item bulk-action text-danger" href="#" data-action="delete">
                                <i class="bi bi-trash me-2"></i>Delete
                            </a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted">Filter by Company</label>
                    <select class="form-select" id="companyFilter" onchange="updateCompanyFilter(this.value)">
                        <option value="">All Companies</option>
                        <?php foreach ($unique_companies as $company_id => $company_name): ?>
                            <?php $encoded_id = $qik->encodeId($company_id); ?>
                            <option value="<?php echo $encoded_id; ?>" <?php echo $company_filter == $company_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($company_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted">Sort by</label>
                    <select class="form-select" id="sortBy" onchange="updateSort(this.value)">
                        <option value="date-desc" <?php echo $sort === 'date' && $order === 'desc' ? 'selected' : ''; ?>>Date (Newest First)</option>
                        <option value="date-asc" <?php echo $sort === 'date' && $order === 'asc' ? 'selected' : ''; ?>>Date (Oldest First)</option>
                        <option value="sender-asc" <?php echo $sort === 'sender' && $order === 'asc' ? 'selected' : ''; ?>>Sender (A-Z)</option>
                        <option value="sender-desc" <?php echo $sort === 'sender' && $order === 'desc' ? 'selected' : ''; ?>>Sender (Z-A)</option>
                        <option value="subject-asc" <?php echo $sort === 'subject' && $order === 'asc' ? 'selected' : ''; ?>>Subject (A-Z)</option>
                        <option value="subject-desc" <?php echo $sort === 'subject' && $order === 'desc' ? 'selected' : ''; ?>>Subject (Z-A)</option>
                    </select>
                </div>
                <div class="col-md-4 text-end d-none d-md-block">
                    <button type="button" class="btn btn-outline-secondary" id="refresh-btn" style="border-radius: 25px;">
                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                    </button>
                </div>
                </div>
                
                <!-- Mobile: Actions and Refresh below filters -->
                <div class="d-flex d-md-none mt-3">
                <div class="btn-group me-4">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" id="bulkActionsBtnMobile" disabled>
                        <i class="bi bi-check2-square me-1"></i> Actions
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item bulk-action" href="#" data-action="mark-read">
                            <i class="bi bi-envelope-open me-2"></i>Mark as Read
                        </a></li>
                        <li><a class="dropdown-item bulk-action" href="#" data-action="mark-unread">
                            <i class="bi bi-envelope me-2"></i>Mark as Unread
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item bulk-action text-danger" href="#" data-action="delete">
                            <i class="bi bi-trash me-2"></i>Delete
                        </a></li>
                    </ul>
                </div>
                <button type="button" class="btn btn-outline-secondary" id="refresh-btn-mobile" style="border-radius: 25px;">
                    <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                </button>
                </div>
            </div><!-- End filter-content -->
        </div><!-- End filter-bar -->

        <!-- Tab content -->
        <div class="tab-content">
            <!-- All Mail -->
            <div class="tab-pane fade show active" id="all">
                <div class="mail-content">
                    <!-- Messages List -->
                    <div class="card">
                       <div class="card-body p-0">
                           <!-- Table Header -->
                           <div class="table-responsive">
                               <table class="table table-hover mb-0">
                                   <thead class="table-light">
                                       <tr>
                                           <th class="ps-3" style="width: 40px;">
                                               <div class="form-check">
                                                   <input class="form-check-input" type="checkbox" id="select-all">
                                               </div>
                                           </th>
                                           <th colspan="3" class="d-md-none">Messages</th>
                                           <th style="width: 200px;" class="d-none d-md-table-cell">Sender</th>
                                           <th class="d-none d-md-table-cell">Subject</th>
                                           <th class="text-end d-none d-md-table-cell" style="width: 120px;">Date</th>
                                       </tr>
                                   </thead>
                                   <tbody>

<?php

if (empty($messages)) {
   echo '<tr><td colspan="4" class="text-center">No messages found.</td></tr>';
} else {
   foreach ($messages as $message) {
       $date = new DateTime($message['create_dt']);
       $today = new DateTime();
       $current_year = $today->format('Y');
       $message_year = $date->format('Y');
       
       // Show time for today, "M j" for this year, "M j, y" for previous years
       if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
           $dateformat = 'h:i a';
       } elseif ($message_year === $current_year) {
           $dateformat = 'M j';
       } else {
           $dateformat = 'M j, y';  // Shows like "Dec 25, 24"
       }
       
       $formatted_date = $display->formatdate($message['create_dt'], $dateformat);

       $is_unread = $message['processstatus'] !== 'read';
       $company = !empty($message['company_id']) ? $app->getcompany($message['company_id']) : null;

       echo '
       <tr class="message-row ' . ($is_unread ? 'unread' : '') . '" 
           data-message-id="' . $qik->encodeId($message['message_id']) . '"
         data-server="' . htmlspecialchars($message['host'] ?? '') . '"
         >
           <td class="ps-3">
               <div class="form-check">
                   <input class="form-check-input message-checkbox" type="checkbox" 
                          value="' . $qik->encodeId($message['message_id']) . '" 
>
               </div>
           </td>
           <td class="sender-col d-none d-md-table-cell">
               <div class="d-flex align-items-center">';
       
       if (!empty($company['company_logo'])) {
           echo '<img src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" 
                     class="company-logo me-2" alt="Company Logo">';
       } else {
           echo '<div class="company-logo bg-secondary me-2 d-flex align-items-center justify-content-center">
                   <i class="bi bi-cake text-white"></i>
                 </div>';
       }
       
       echo '<span class="text-truncate ' . ($is_unread ? 'fw-bold' : '') . '">
               ' . htmlspecialchars($company["company_display_name"] ?? "Reward Provider") . '
             </span>
               </div>
           </td>
           <td class="d-none d-md-table-cell">
               <span class="' . ($is_unread ? 'fw-bold' : '') . '">
                   ' . htmlspecialchars($message['subject']) . '
               </span>
           </td>
           <td class="text-end date-col d-none d-md-table-cell ' . ($is_unread ? 'fw-bold' : '') . '">
               ' . $formatted_date . '
           </td>
           <!-- Mobile layout -->
           <td class="d-md-none" colspan="3">
               <div class="mobile-message-header">
                   <div class="mobile-company-info">';
       
       if (!empty($company['company_logo'])) {
           echo '<img src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" 
                     class="company-logo" alt="Company Logo">';
       } else {
           echo '<div class="company-logo bg-secondary d-flex align-items-center justify-content-center">
                   <i class="bi bi-cake text-white"></i>
                 </div>';
       }
       
       echo '<span class="' . ($is_unread ? '' : '') . '">
                       ' . htmlspecialchars($company["company_display_name"] ?? "Reward Provider") . '
                   </span>
                   </div>
                   <span class="mobile-date ' . ($is_unread ? 'fw-bold' : '') . '">' . $formatted_date . '</span>
               </div>
               <div class="mobile-subject ' . ($is_unread ? 'unread' : '') . '">
                   ' . htmlspecialchars($message['subject']) . '
               </div>
           </td>
       </tr>';
   }
}

?>
                                   </tbody>
                               </table>
                           </div>

<?php
if ($total_pages > 1) {
   echo '
   <div class="pagination-info m-3">
       <div class="pagination-text">
           Showing ' . (($page - 1) * $per_page + 1) . ' to ' . 
           min($page * $per_page, $total_messages) . ' of ' . 
           $total_messages . ' messages
       </div>
       <nav class="my-2">
           <ul class="pagination mb-0">';
           
           $show_pages = 5;
           
           // Always show first page
           $url_params = ($sort ? "&sort=" . urlencode($sort) : '') .
                        ($order ? "&order=" . urlencode($order) : '') .
                        ($search ? "&search=" . urlencode($search) : '') .
                        ($company_filter_encoded ? "&company=" . urlencode($company_filter_encoded) : '');
           
           echo '<li class="page-item ' . (1 === $page ? 'active' : '') . '">
                   <a class="page-link" href="?page=1' . $url_params . '">1</a>
                 </li>';

           // Show first few pages after 1
           if ($page <= $show_pages + 3) {
               for ($i = 2; $i <= $show_pages; $i++) {
                   if ($i < $page - 2) continue;
                   echo '<li class="page-item ' . ($i === $page ? 'active' : '') . '">
                           <a class="page-link" href="?page=' . $i . $url_params . '">' . $i . '</a>
                         </li>';
               }
           } else {
               echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
           }

           // Show current page range
           $start = max($show_pages + 1, $page - 2);
           $end = min($total_pages - $show_pages, $page + 2);
           for ($i = $start; $i <= $end; $i++) {
               echo '<li class="page-item ' . ($i === $page ? 'active' : '') . '">
                       <a class="page-link" href="?page=' . $i . $url_params . '">' . $i . '</a>
                     </li>';
           }

           // Show ellipsis before last pages
           if ($page < $total_pages - ($show_pages + 2)) {
               echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
           }

           // Show last few pages
           for ($i = max($total_pages - $show_pages + 1, $end + 1); $i <= $total_pages; $i++) {
               echo '<li class="page-item ' . ($i === $page ? 'active' : '') . '">
                       <a class="page-link" href="?page=' . $i . $url_params . '">' . $i . '</a>
                     </li>';
           }

   echo '
           </ul>
       </nav>
   </div>';
}
?>
                       </div>
                   </div>
                </div>
            </div>
            
            <!-- Unread Mail -->
            <div class="tab-pane fade" id="unread">
                <div class="mail-content">
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-envelope-exclamation" style="font-size: 3rem;"></i>
                        <p class="mt-3">No unread messages</p>
                        <p class="small">Your unread mail will appear here</p>
                    </div>
                </div>
            </div>
            
            <!-- Read Mail -->
            <div class="tab-pane fade" id="read">
                <div class="mail-content">
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-envelope-open" style="font-size: 3rem;"></i>
                        <p class="mt-3">No read messages</p>
                        <p class="small">Your read mail will appear here</p>
                    </div>
                </div>
            </div>
            
            <!-- Starred Mail -->
            <div class="tab-pane fade" id="starred">
                <div class="mail-content">
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-star" style="font-size: 3rem;"></i>
                        <p class="mt-3">No starred messages</p>
                        <p class="small">Star important messages to see them here</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Store CSRF token for AJAX requests
const csrfToken = '<?php echo $display->input_csrftoken('tokenonly'); ?>';

// Toggle filter bar on mobile
function toggleFilterBar() {
    const filterBar = document.getElementById('filterBar');
    filterBar.classList.toggle('collapsed');
}

// Function to update sort order
function updateSort(value) {
    const [sortBy, sortOrder] = value.split('-');
    const searchParams = new URLSearchParams(window.location.search);
    searchParams.set('sort', sortBy);
    searchParams.set('order', sortOrder);
    window.location.search = searchParams.toString();
}

// Function to update company filter
function updateCompanyFilter(companyId) {
    const searchParams = new URLSearchParams(window.location.search);
    if (companyId) {
        searchParams.set('company', companyId);
    } else {
        searchParams.delete('company');
    }
    // Reset to page 1 when filtering
    searchParams.set('page', '1');
    window.location.search = searchParams.toString();
}

// Function to activate a tab by its target
function activateTab(targetHash) {
    // Remove active class from all tabs
    document.querySelectorAll('.nav-tab-clean').forEach(function(t) {
        t.classList.remove('active');
    });

    // Find and activate the tab with matching href
    var targetTab = document.querySelector('.nav-tab-clean a[href="' + targetHash + '"]');
    if (targetTab) {
        targetTab.parentElement.classList.add('active');
        
        // Show corresponding content
        document.querySelectorAll('.tab-pane').forEach(function(pane) {
            pane.classList.remove('show', 'active');
        });
        var targetPane = document.querySelector(targetHash);
        if (targetPane) {
            targetPane.classList.add('show', 'active');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Ensure filter bar starts collapsed on mobile
    if (window.innerWidth < 768) {
        const filterBar = document.getElementById('filterBar');
        if (filterBar && !filterBar.classList.contains('collapsed')) {
            filterBar.classList.add('collapsed');
        }
    }
    
    // Count unread messages
    const unreadCount = document.querySelectorAll('.message-row.unread').length;
    const unreadBadge = document.getElementById('unreadCount');
    if (unreadBadge && unreadCount > 0) {
        unreadBadge.textContent = unreadCount;
        unreadBadge.style.display = 'inline-block';
    }
    
    // Handle tab clicks
    document.querySelectorAll('.nav-tab-clean a').forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            var targetHash = this.getAttribute('href');
            activateTab(targetHash);
        });
    });
    
    // Message row click handler - only on content cells, not checkbox cell
    document.querySelectorAll('.message-row').forEach(row => {
        // Get all cells except the first one (checkbox cell)
        const contentCells = row.querySelectorAll('td:not(:first-child)');
        
        contentCells.forEach(cell => {
            cell.style.cursor = 'pointer';
            cell.addEventListener('click', function(e) {
                // Don't navigate if clicking on a checkbox
                if (e.target.type === 'checkbox') return;
                
                const messageId = row.dataset.messageId;
                const server = row.dataset.server;
                window.location.href = `/myaccount/mail-read?id=${messageId}&server=${server}`;
            });
        });
        
        // Remove pointer cursor from the row itself
        row.style.cursor = 'default';
    });

    // Select all functionality
    const selectAllCheckbox = document.getElementById('select-all');
    const messageCheckboxes = document.querySelectorAll('.message-checkbox');

    selectAllCheckbox.addEventListener('change', function() {
        messageCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
            updateRowSelection(checkbox);
        });
        updateSelectAllState(); // Update button state
    });

    messageCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateRowSelection(this);
            updateSelectAllState();
        });
    });

    // Bulk actions
    document.querySelectorAll('.bulk-action').forEach(action => {
        action.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const selectedIds = Array.from(document.querySelectorAll('.message-checkbox:checked'))
                                   .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                showErrorModal('Please select at least one message');
                return;
            }

            const actionType = this.dataset.action;
            
            if (actionType === 'delete' && !confirm('Are you sure you want to delete the selected messages?')) {
                return;
            }

            try {
                const response = await fetch('/api/messages/bulk-action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        _token: csrfToken,
                        action: actionType,
                        messageIds: selectedIds
                    })
                });

                if (!response.ok) throw new Error('Network response was not ok');
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    throw new Error(result.message || 'Unknown error occurred');
                }
            } catch (error) {
                console.error('Error:', error);
                showErrorModal('An error occurred while processing your request: ' + error.message);
            }
        });
    });

    // Refresh buttons (desktop and mobile)
    document.getElementById('refresh-btn').addEventListener('click', () => {
        location.reload();
    });
    
    const refreshBtnMobile = document.getElementById('refresh-btn-mobile');
    if (refreshBtnMobile) {
        refreshBtnMobile.addEventListener('click', () => {
            location.reload();
        });
    }
});

function updateRowSelection(checkbox) {
    const row = checkbox.closest('.message-row');
    row.classList.toggle('selected', checkbox.checked);
}

function updateSelectAllState() {
    const selectAllCheckbox = document.getElementById('select-all');
    const messageCheckboxes = document.querySelectorAll('.message-checkbox');
    const checkedBoxes = document.querySelectorAll('.message-checkbox:checked');
    const bulkActionsBtn = document.getElementById('bulkActionsBtn');
    const bulkActionsBtnMobile = document.getElementById('bulkActionsBtnMobile');
    
    selectAllCheckbox.checked = checkedBoxes.length === messageCheckboxes.length;
    selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < messageCheckboxes.length;
    
    // Enable/disable bulk actions buttons (both desktop and mobile) and change color
    const updateButton = (btn) => {
        if (btn) {
            if (checkedBoxes.length === 0) {
                btn.disabled = true;
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-secondary');
            } else {
                btn.disabled = false;
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-primary');
            }
        }
    };
    
    updateButton(bulkActionsBtn);
    updateButton(bulkActionsBtnMobile);
}

// Function to show error modal
function showErrorModal(message) {
    const modalHtml = `
        <div class="modal fade" id="errorModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Error</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            ${message}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('errorModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('errorModal'));
    modal.show();
    
    // Clean up after modal is hidden
    document.getElementById('errorModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}
</script>



<?PHP
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
