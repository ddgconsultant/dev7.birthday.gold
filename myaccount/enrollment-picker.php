<?php
/**
 * Mobile-First Enrollment Picker
 * Modern interface inspired by Groupon/Amazon mobile apps
 * 
 * BIRTHDAY GOLD DEVELOPMENT STANDARDS COMPLIANT
 * - Single PHP block with echo statements
 * - No mixed HTML/PHP output
 * - Bootstrap 5 utility-first approach
 * - No abbreviations in comments
 */

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.allocationmanager.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.configmanager.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.enrollment.php');

// Check login - handled by site-controller.php
$activeuser = $account->isactive();
if (empty($activeuser)) {
    header('Location: /login');
    exit;
}

$allocationManager = new AllocationManager($database);
$configManager = new ConfigManager($database);
$enrollment = new Enrollment();

// Get current user data using existing patterns from businessselect.php
$current_user_data = $session->get('current_user_data');
$current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');
$user_id = $current_user_data['user_id'];
$alive = $app->calculateage($current_user_data['birthdate']);
$accountstats = $account->account_getstats();
$plandatafeatures = $app->plandetail('details_id', $current_user_data['account_product_id']);

// Get selection lists from session
$selectionList = $session->get('goldmine_selectionList', []);
$existingList = $session->get('goldmine_existingList', []);

// Get user balance
$balance = $allocationManager->getUserBalance($user_id);
$allocation_warning = $allocationManager->getAllocationWarning($user_id);

// Get categories using existing system
list($rewardCategoriesData, $iconList) = $app->get_rewardcategories([], 'extended');
$get_rewardcategories = $app->get_rewardcategories();
$rewardiconlist = $get_rewardcategories[1];

// Category list for filter - using existing categories from businessselect.php
$display_categories = ['All', 'Food', 'Beverage', 'Beauty', 'Retail', 'Other', 'App Only', 'Local', 'More'];

// Get user's state for Local filter
$user_state = $current_user_data['state'] ?? $current_user_data['profile_state'] ?? '';

// Get selected categories and filters (supports multiple selection with comma separation)
$category_param = $_GET['category'] ?? 'All';
$selected_categories = explode(',', $category_param);

// Clean up selected categories
$selected_categories = array_map('trim', $selected_categories);
$selected_categories = array_filter($selected_categories); // Remove empty

// If no valid categories, default to All
if (empty($selected_categories)) {
    $selected_categories = ['All'];
}

// If 'All' is selected with others, just use 'All'
if (in_array('All', $selected_categories) && count($selected_categories) > 1) {
    $selected_categories = ['All'];
}

$search_query = $_GET['search'] ?? '';
$show_suppressed = isset($_GET['show_suppressed']) ? true : false;
// Parse suppression filters - can be comma-separated for multiple filters
$suppression_filter = $_GET['suppression_filter'] ?? 'all';
$active_filters = $suppression_filter === 'all' ? [] : explode(',', $suppression_filter);

// Advanced filters
$rating_filter = $_GET['rating'] ?? '';  // e.g., "3+" for 3 stars and above
$popular_filter = $_GET['popular'] ?? '';  // e.g., "top10", "top25", "top50"
$value_filter = $_GET['value'] ?? '';  // e.g., "10+", "25+", "50+"

// Get companies using existing system from businessselect.php
$companies = [];
$resultsize = 300;
$counter = ['total' => 0, 'record' => 0, 'display' => 0, 'rewards' => 0];

// If search query, use direct query
if ($search_query) {
    // Initialize params with search terms
    $params = [
        'search1' => "%{$search_query}%",
        'search2' => "%{$search_query}%"
    ];
    
    // Direct search query
    $sql = "SELECT DISTINCT c.*, a.description as company_logo 
            FROM bg_companies AS c
            LEFT JOIN bg_company_attributes AS a ON c.company_id = a.company_id 
                AND a.category = 'company_logos' AND a.grouping = 'primary_logo'
            WHERE c.status = 'finalized' 
                AND (c.company_name LIKE :search1 OR c.description LIKE :search2)";
    
    // Handle category filtering for search
    if (!in_array('All', $selected_categories)) {
        $category_conditions = [];
        $has_app_only = in_array('App Only', $selected_categories);
        $has_local = in_array('Local', $selected_categories);
        $regular_categories = array_diff($selected_categories, ['App Only', 'Local']);
        
        // Add regular category filter
        if (!empty($regular_categories)) {
            $category_placeholders = [];
            $idx = 0;
            foreach ($regular_categories as $cat) {
                $placeholder = "cat_" . $idx;
                $category_placeholders[] = ":" . $placeholder;
                $params[$placeholder] = $cat;
                $idx++;
            }
            $category_conditions[] = "c.display_category IN (" . implode(',', $category_placeholders) . ")";
        }
        
        // Add app-only filter
        if ($has_app_only) {
            $category_conditions[] = "c.signup_url = :app_only_tag";
            $params['app_only_tag'] = $website['apponlytag'];
        }
        
        // Add local filter (matches user's state)
        if ($has_local && !empty($user_state)) {
            // Check if company has locations in user's state
            $category_conditions[] = "EXISTS (
                SELECT 1 FROM bg_company_locations cl 
                WHERE cl.company_id = c.company_id 
                AND cl.state = :user_state
            )";
            $params['user_state'] = $user_state;
        }
        
        // Combine conditions with AND logic
        if (!empty($category_conditions)) {
            $sql .= " AND (" . implode(' AND ', $category_conditions) . ")";
        }
    }
    
    $sql .= " ORDER BY c.company_name LIMIT " . $resultsize;
    
    $companies = $database->getrows($sql, $params);
} else {
    // Use existing getSelectionCompanies method which filters out already enrolled companies
    if (in_array('All', $selected_categories)) {
        // Get companies from all categories
        foreach ($display_categories as $category) {
            if ($category === 'All' || $category === 'App Only') continue;
            $catCompanies = $app->getSelectionCompanies($resultsize, $category);
            foreach ($catCompanies as $company) {
                // Check for duplicates
                $isDuplicate = false;
                foreach ($companies as $existingCompany) {
                    if ($existingCompany['company_id'] == $company['company_id']) {
                        $isDuplicate = true;
                        break;
                    }
                }
                if (!$isDuplicate) {
                    $companies[] = $company;
                }
            }
        }
    } else {
        // Get companies for specific categories with filtering
        $all_companies = [];
        
        // Check if we need special filters
        $has_app_only = in_array('App Only', $selected_categories);
        $has_local = in_array('Local', $selected_categories);
        $regular_categories = array_diff($selected_categories, ['App Only', 'Local']);
        
        // Get companies for regular categories
        if (!empty($regular_categories)) {
            foreach ($regular_categories as $category) {
                $catCompanies = $app->getSelectionCompanies($resultsize, $category);
                foreach ($catCompanies as $company) {
                    // Check for duplicates
                    $isDuplicate = false;
                    foreach ($all_companies as $existingCompany) {
                        if ($existingCompany['company_id'] == $company['company_id']) {
                            $isDuplicate = true;
                            break;
                        }
                    }
                    if (!$isDuplicate) {
                        $all_companies[] = $company;
                    }
                }
            }
        } else if ($has_app_only) {
            // If only App Only selected, get all companies (we'll filter below)
            foreach (['Food', 'Beverage', 'Beauty', 'Retail', 'Other'] as $category) {
                $catCompanies = $app->getSelectionCompanies($resultsize, $category);
                foreach ($catCompanies as $company) {
                    // Check for duplicates
                    $isDuplicate = false;
                    foreach ($all_companies as $existingCompany) {
                        if ($existingCompany['company_id'] == $company['company_id']) {
                            $isDuplicate = true;
                            break;
                        }
                    }
                    if (!$isDuplicate) {
                        $all_companies[] = $company;
                    }
                }
            }
        }
        
        // Apply special filters
        $companies = $all_companies;
        
        // Filter by app-only if needed
        if ($has_app_only) {
            $filtered = [];
            foreach ($companies as $company) {
                if ($company['signup_url'] == $website['apponlytag']) {
                    $filtered[] = $company;
                }
            }
            $companies = $filtered;
        }
        
        // Filter by local (state) if needed
        if ($has_local && !empty($user_state)) {
            $filtered = [];
            foreach ($companies as $company) {
                // Check if company has locations in user's state
                $location_sql = "SELECT COUNT(*) as count FROM bg_company_locations 
                                WHERE company_id = :company_id AND state = :user_state LIMIT 1";
                $location_result = $database->get_row($location_sql, [
                    'company_id' => $company['company_id'],
                    'user_state' => $user_state
                ]);
                if ($location_result['count'] > 0) {
                    $filtered[] = $company;
                }
            }
            $companies = $filtered;
        }
    }
}

// Get all company IDs for eligibility check
$company_ids = array_column($companies, 'company_id');
$eligibilities = $enrollment->getCompanyEligibilities($user_id, $company_ids);

// Process companies to add additional data
$processed_companies = [];
$suppressed_companies = [];
$total_suppressed_count = 0; // Track total suppressed count

foreach ($companies as $company) {
    $suppression_reasons = [];
    $skip_company = false;
    
    // Age check
    if (($company['minage'] > $alive['years']) || ($company['maxage'] < $alive['years'])) {
        $age_range = $company['minage'] . '-' . $company['maxage'];
        $suppression_reasons['age'] = "Age requirement: $age_range years (you are {$alive['years']} years old)";
    }
    
    // Check for dietary restrictions (placeholder for future implementation)
    // This would check user preferences against company attributes
    if (!empty($company['dietary_restrictions'])) {
        // Example: if user is vegan and company serves only meat
        // suppression_reasons diet equals Contains non-vegan options
    }
    
    // Process suppressions if any exist
    if (!empty($suppression_reasons)) {
        // Track for statistics
        $company['suppression_reasons'] = $suppression_reasons;
        $suppressed_companies[] = $company;
        $total_suppressed_count++;
        
        // Check if this company should be shown based on active filters
        if ($show_suppressed) {
            // If showing suppressed, check if any active suppression matches filters
            if ($suppression_filter !== 'all' && !empty($active_filters)) {
                $has_matching_filter = false;
                foreach (array_keys($suppression_reasons) as $reason_type) {
                    if (in_array($reason_type, $active_filters)) {
                        $has_matching_filter = true;
                        break;
                    }
                }
                if (!$has_matching_filter) {
                    continue; // Skip if no matching filter
                }
            }
            // If we get here, show the company with suppression warning
        } else {
            // Not showing suppressed items at all
            continue;
        }
    }
    
    // Store suppression info
    $company['is_suppressed'] = !empty($suppression_reasons);
    $company['suppression_reasons'] = $suppression_reasons;
    
    // Check if already selected or enrolled
    $company['is_enrolled'] = in_array($company['company_id'], $selectionList) || in_array($company['company_id'], $existingList);
    $company['is_selected'] = in_array($company['company_id'], $selectionList);
    $company['is_existing'] = in_array($company['company_id'], $existingList);
    
    // Get rewards
    $query = "SELECT * FROM bg_company_rewards WHERE company_id = ? AND status = 'active'";
    $stmt = $database->prepare($query);
    $stmt->execute([$company['company_id']]);
    $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $company['rewards'] = $rewards;
    $company['reward_count'] = count($rewards);
    
    // Get total value
    $totalvalue = 0;
    $reward_descriptions = [];
    foreach ($rewards as $reward) {
        $totalvalue += $reward['cash_value'];
        if (!empty($reward['description'])) {
            $reward_descriptions[] = $reward['description'];
        }
    }
    $company['total_value'] = $totalvalue;
    $company['reward_preview'] = !empty($reward_descriptions) ? $reward_descriptions[0] : $company['spinner_description'];
    
    // App only check
    $company['is_app_only'] = ($company['signup_url'] == $website['apponlytag']);
    
    // Category for display
    $company['categories'] = $company['display_category'] ?? '';
    
    // Add eligibility information
    $company['eligibility'] = $eligibilities[$company['company_id']] ?? ['eligible' => true];
    
    $processed_companies[] = $company;
}
$companies = $processed_companies;

// Apply advanced filters
if ($value_filter) {
    $filtered = [];
    $min_value = intval(str_replace('+', '', $value_filter));
    foreach ($companies as $company) {
        if ($company['total_value'] >= $min_value) {
            $filtered[] = $company;
        }
    }
    $companies = $filtered;
}

// Apply popularity filter (this would normally use enrollment counts or other metrics)
if ($popular_filter) {
    // For now, we'll simulate popularity based on reward value as a proxy
    // In production, this would use actual enrollment/view counts
    usort($companies, function($a, $b) {
        return $b['total_value'] <=> $a['total_value'];
    });
    
    switch($popular_filter) {
        case 'top10':
            $companies = array_slice($companies, 0, 10);
            break;
        case 'top25':
            $companies = array_slice($companies, 0, 25);
            break;
        case 'top50':
            $companies = array_slice($companies, 0, 50);
            break;
        case 'trending':
            // Would normally check recent enrollment velocity
            // For now, just take top 20 by value
            $companies = array_slice($companies, 0, 20);
            break;
    }
}

// Apply rating filter (would need actual rating data)
if ($rating_filter) {
    // This would normally filter by actual company ratings
    // For demonstration, we'll simulate based on reward count
    $filtered = [];
    $min_rating = floatval(str_replace('+', '', $rating_filter));
    
    foreach ($companies as $company) {
        // Simulate rating based on reward count (1-5 scale)
        $simulated_rating = min(5, 2 + ($company['reward_count'] * 0.5));
        
        if ($rating_filter === '5' && $simulated_rating == 5) {
            $filtered[] = $company;
        } else if (strpos($rating_filter, '+') !== false && $simulated_rating >= $min_rating) {
            $filtered[] = $company;
        }
    }
    $companies = $filtered;
}

// Debug: Check if we got any results
if (empty($companies)) {
    echo '<!-- DEBUG: No companies returned from query -->';
    
    // Try a simpler query to see if we get any companies
    $simple_sql = "SELECT * FROM bg_companies WHERE status = 'active' LIMIT 5";
    $simple_results = $database->getrows($simple_sql);
    echo '<!-- DEBUG: Simple query returned ' . count($simple_results) . ' companies -->';
}

// Set up token labels for flexibility
$label_token = 'Pick';
$label_tokened = 'Picked';

// Page setup - MUST be before includes
$pagetitle = 'Pick Your Birthday Rewards';
$bodycontentclass = '';
$additionalstyles .= '<link rel="stylesheet" href="/public/css/enrollment-picker.css?v=' . time() . '">';

// Add custom CSS using Bootstrap 5 utilities where possible
$additionalstyles .= '
<style>
/* Ensure success color is defined */
:root {
    --success-color: #28a745;
}

/* Selected buttons - regular green for newly picked items */
button.action-btn.selected,
.company-card .action-btn.selected,
.company-action .action-btn.selected,
.action-btn.selected {
    background: #28a745 !important;
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: white !important;
    cursor: default !important;
    opacity: 1 !important;
}

/* Enrolled buttons - dark green for already saved enrollments */
button.action-btn.enrolled,
.company-card .action-btn.enrolled,
.company-action .action-btn.enrolled,
.action-btn.enrolled {
    background: #155724 !important;  /* Dark green */
    background-color: #155724 !important;
    border-color: #155724 !important;
    color: white !important;
    cursor: default !important;
    opacity: 1 !important;
}

/* Selected disabled states - regular green */
button.action-btn.selected[disabled],
button.action-btn.selected:disabled,
.company-card .action-btn.selected:disabled,
.company-action .action-btn.selected:disabled,
.action-btn.selected:disabled,
.action-btn.selected[disabled] {
    background: #28a745 !important;
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: white !important;
    opacity: 1 !important;
}

/* Enrolled disabled states - dark green */
button.action-btn.enrolled[disabled],
button.action-btn.enrolled:disabled,
.company-card .action-btn.enrolled:disabled,
.company-action .action-btn.enrolled:disabled,
.action-btn.enrolled:disabled,
.action-btn.enrolled[disabled] {
    background: #155724 !important;  /* Dark green */
    background-color: #155724 !important;
    border-color: #155724 !important;
    color: white !important;
    opacity: 1 !important;
}

/* Also try Bootstrap button override */
.btn.action-btn.selected,
.btn.action-btn.selected:disabled,
.btn.action-btn.selected[disabled] {
    background: #28a745 !important;
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: white !important;
}

.btn.action-btn.enrolled,
.btn.action-btn.enrolled:disabled,
.btn.action-btn.enrolled[disabled] {
    background: #155724 !important;  /* Dark green */
    background-color: #155724 !important;
    border-color: #155724 !important;
    color: white !important;
}

/* Floating counter button */
.selection-counter {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: var(--bs-success);
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: bold;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
    cursor: pointer;
    z-index: 1040;
    transition: all 0.3s ease;
}

.selection-counter:hover {
    transform: scale(1.1);
    background: var(--bs-success);
    filter: brightness(0.9);
}

.selection-counter i {
    font-size: 1.5rem;
    margin-right: 0.25rem;
}

/* Mobile responsive */
@media (max-width: 576px) {
    .selection-counter {
        bottom: 4.5rem; /* Account for mobile bottom nav */
        right: 1rem;
        width: 50px;
        height: 50px;
        font-size: 1rem;
    }
    
    .selection-counter i {
        font-size: 1.25rem;
    }
}

/* Position cart icon above bottom nav on mobile */
@media (max-width: 768px) {
    .selection-counter {
        bottom: 80px !important;
    }
}

/* Modal customization */
.basket-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    border-bottom: 1px solid #f0f0f0;
}

.basket-item:last-child {
    border-bottom: none;
}

.basket-item img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 0.5rem;
    margin-right: 1rem;
}

.basket-item-info {
    flex: 1;
}

.basket-item-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.basket-item-category {
    font-size: 0.875rem;
    color: #6c757d;
}

.basket-item-remove {
    background: none;
    border: none;
    color: #dc3545;
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.5rem;
    transition: all 0.2s;
}

.basket-item-remove:hover {
    color: #c82333;
    transform: scale(1.1);
}

/* Fix modal z-index issues */
.modal-backdrop {
    z-index: 1040 !important;
}

.modal {
    z-index: 1050 !important;
}

.modal-dialog {
    z-index: 1055 !important;
}

/* Ensure buttons in modal are clickable */
.modal-footer button {
    position: relative;
    z-index: 1;
    cursor: pointer !important;
}

/* Suppression Controls */
.suppression-controls {
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
    margin-top: -1px;
}

/* Suppression filter items in modal */
.suppression-filter-item {
    background: #f8f9fa;
    transition: all 0.2s ease;
}

.suppression-filter-item:hover {
    background: #e9ecef;
}

/* Suppressed company cards */
.company-card.suppressed {
    opacity: 0.7;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.company-card.suppressed .company-image {
    position: relative;
}

.company-card.suppressed .company-image::after {
    content: "\F5D6"; /* Bootstrap Icons eye-slash */
    font-family: "Bootstrap Icons";
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: rgba(255, 255, 255, 0.9);
    color: #6c757d;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.suppression-warning {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.5rem;
    background-color: #e7f3ff;
    border-radius: 0.25rem;
    margin-top: 0.5rem;
}

.suppression-warning i {
    flex-shrink: 0;
    margin-top: 2px;
}

/* Sticky header adjustments for suppression controls */
.enrollment-header.sticky-top {
    z-index: 1020;
}

/* Suppression toggle in filter bar */
.suppression-toggle-wrapper {
    white-space: nowrap;
    border-left: 1px solid var(--border-color);
}

/* Eye toggle button styling */
.eye-toggle-btn {
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    transition: all 0.2s;
}

.eye-toggle-btn:hover {
    transform: scale(1.05);
}

.eye-toggle-btn.active {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.eye-toggle-btn.active .badge {
    background: rgba(255, 255, 255, 0.2) !important;
}

.eye-toggle-btn .badge {
    padding: 0.2rem 0.4rem;
    font-size: 0.7rem;
}

/* More button styling */
button.category-pill {
    background: var(--light-gray);
    color: var(--dark);
    border: none;
    text-decoration: none;
}

button.category-pill:hover {
    background: #e9ecef;
}

button.category-pill.active {
    background: var(--primary-color);
    color: white;
}

/* Special styling for More Filters pill */
.more-filters-pill {
    border: 2px solid var(--primary-color) !important;
    background: white !important;
    color: var(--primary-color) !important;
    font-weight: 600;
}

.more-filters-pill:hover {
    background: var(--primary-color) !important;
    color: white !important;
}

.more-filters-pill.active {
    background: var(--primary-color) !important;
    color: white !important;
    box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb), 0.25);
}

/* Mobile adjustments for filter bar */
@media (max-width: 576px) {
    .suppression-toggle-wrapper .form-check-label {
        font-size: 0.75rem;
    }
    
    .suppression-toggle-wrapper .badge {
        font-size: 0.625rem;
    }
}

/* Success modal icon fix */
#successModal .modal-body i.bi-check-circle-fill {
    font-size: 4rem !important;
    display: block;
    margin-bottom: 1rem;
}

/* Fix modal close button positioning */
.modal-header .btn-close {
    padding: 0.5rem;
    margin: -0.5rem -0.5rem -0.5rem auto;
    opacity: 0.5;
}

.modal-header .btn-close:hover {
    opacity: 1;
}
</style>';

// Include header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Build the page output using echo statements
$output = '';

// Hero Section
$output .= '
<div class="content-header-dark">
    <div class="container">
        <h1>Pick Your Birthday Rewards</h1>
        <p class="lead">Choose from hundreds of birthday reward programs</p>
    </div>
</div>';

$output .= '
<div class="main-content py-3">
    <div class="container-fluid px-0 px-md-3">
        <div class="enrollment-container mx-auto" style="max-width: 1400px;">';

// Sticky Header
$output .= '
<div class="enrollment-header sticky-top">
    <div class="balance-bar">
        <div class="balance-info">
            <span class="balance-number">' . $balance['available_allocations'] . '</span>
            <span class="balance-label">' . $qik->plural($label_token, $balance['available_allocations']) . ' available</span>
        </div>
        
        <div class="selected-info" id="selectedInfo" style="display: none;" onclick="toggleBasketDetails()">
            <i class="bi bi-basket-fill"></i>
            <span class="selected-number" id="selectedCount">0</span>
            <span class="selected-label">' . $label_tokened . '</span>
        </div>';

if ($balance['expiring_soon_count'] > 0) {
    $output .= '
        <div class="expiring-warning">
            <i class="bi bi-clock-history"></i>
            ' . $balance['expiring_soon_count'] . ' expiring soon
        </div>';
}

$output .= '
    </div>';

// Search Bar
$output .= '
    <div class="search-bar">
        <form method="GET" class="search-form">
            <div class="search-input-wrapper">
                <i class="bi bi-search"></i>
                <input type="search" 
                       name="search" 
                       class="search-input" 
                       placeholder="Search birthday rewards..." 
                       value="' . htmlspecialchars($search_query) . '"
                       autocomplete="off">';

if ($search_query) {
    $output .= '
                <button type="button" class="clear-search" onclick="clearSearch()">
                    <i class="bi bi-x-circle"></i>
                </button>';
}

$output .= '
            </div>';

if (!in_array('All', $selected_categories)) {
    $output .= '
            <input type="hidden" name="category" value="' . htmlspecialchars(implode(',', $selected_categories)) . '">';
}

$output .= '
        </form>
    </div>';

// Category Filter with Suppression Controls
$output .= '
    <div class="category-filter d-flex justify-content-between align-items-center">
        <div class="category-scroll flex-grow-1">';

foreach ($display_categories as $cat) {
    $cat_icon = 'bi-tag';
    $is_active = in_array($cat, $selected_categories);
    
    switch($cat) {
        case 'All':
            $cat_icon = 'bi-grid';
            break;
        case 'Food': 
            $cat_icon = 'bi-egg-fried'; 
            break;
        case 'Beverage': 
            $cat_icon = 'bi-cup-straw'; 
            break;
        case 'Beauty': 
            $cat_icon = 'bi-stars'; 
            break;
        case 'Retail': 
            $cat_icon = 'bi-shop'; 
            break;
        case 'Other': 
            $cat_icon = 'bi-three-dots'; 
            break;
        case 'App Only':
            $cat_icon = 'bi-phone';
            break;
        case 'Local':
            $cat_icon = 'bi-geo-alt-fill';
            break;
        case 'More':
            $cat_icon = 'bi-sliders';
            break;
    }
    
    // Special handling for More pill - opens modal instead of URL
    if ($cat === 'More') {
        // Count active advanced filters
        $active_advanced_count = 0;
        if ($rating_filter) $active_advanced_count++;
        if ($popular_filter) $active_advanced_count++;
        if ($value_filter) $active_advanced_count++;
        
        $output .= '
            <button type="button" class="category-pill more-filters-pill' . ($active_advanced_count > 0 ? ' active' : '') . '" 
                    data-bs-toggle="modal" data-bs-target="#advancedFiltersModal">
                <i class="' . $cat_icon . '"></i>
                More Filters' . ($active_advanced_count > 0 ? ' (' . $active_advanced_count . ')' : '') . '
            </button>';
        continue;
    }
    
    // Build URL for this category
    if ($cat === 'All') {
        // All clears other selections
        $cat_url = '?category=All';
    } else {
        // Toggle this category
        if ($is_active && count($selected_categories) > 1) {
            // Remove this category from selection
            $new_categories = array_diff($selected_categories, [$cat, 'All']);
            if (empty($new_categories)) {
                $cat_url = '?category=All';
            } else {
                $cat_url = '?category=' . implode(',', $new_categories);
            }
        } else if ($is_active) {
            // If only this is selected, go to All
            $cat_url = '?category=All';
        } else {
            // Add this category to selection
            $new_categories = array_diff($selected_categories, ['All']); // Remove 'All' when selecting specific
            $new_categories[] = $cat;
            $cat_url = '?category=' . implode(',', $new_categories);
        }
    }
    
    // Preserve other parameters
    if ($search_query) {
        $cat_url .= '&search=' . urlencode($search_query);
    }
    if ($show_suppressed) {
        $cat_url .= '&show_suppressed=1';
    }
    if ($suppression_filter !== 'all') {
        $cat_url .= '&suppression_filter=' . urlencode($suppression_filter);
    }
    
    $output .= '
            <a href="' . htmlspecialchars($cat_url) . '" 
               class="category-pill ' . ($is_active ? 'active' : '') . '">
                <i class="' . $cat_icon . '"></i>
                ' . $cat . '
            </a>';
}

$output .= '
        </div>';

// Add suppression controls to the right side of filter bar
if ($total_suppressed_count > 0 || $show_suppressed) {
    /* Original toggle switch design - kept for rollback
    $output .= '
        <div class="suppression-toggle-wrapper d-flex align-items-center gap-2 px-3">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="showSuppressed" 
                       ' . ($show_suppressed ? 'checked' : '') . '
                       onchange="toggleSuppressed(this.checked)">
                <label class="form-check-label small" for="showSuppressed">
                    Hidden <span class="badge bg-secondary">' . $total_suppressed_count . '</span>
                </label>
            </div>
            <button type="button" class="btn btn-sm p-1" data-bs-toggle="modal" data-bs-target="#suppressionModal"
                    title="Why are some hidden?">
                <i class="bi bi-info-circle"></i>
            </button>
        </div>';
    */
    
    // New eye icon button design
    $eye_icon = $show_suppressed ? 'bi-eye' : 'bi-eye-slash';
    $eye_title = $show_suppressed ? 'Hide suppressed ' . $website['biznames'] : 'Show hidden ' . $website['biznames'];
    
    $output .= '
        <div class="suppression-toggle-wrapper d-flex align-items-center gap-1 px-3">
            <button type="button" class="btn btn-sm btn-outline-secondary eye-toggle-btn' . ($show_suppressed ? ' active' : '') . '" 
                    id="eyeToggleBtn"
                    onclick="toggleSuppressed(' . ($show_suppressed ? 'false' : 'true') . ')"
                    data-bs-toggle="tooltip"
                    data-bs-placement="left"
                    data-bs-html="true"
                    title="' . htmlspecialchars('<strong>' . $eye_title . '</strong><br><small>' . $total_suppressed_count . ' ' . $website['biznames'] . ' hidden</small>', ENT_QUOTES) . '">
                <i class="bi ' . $eye_icon . '"></i>
                <span class="badge bg-secondary ms-1">' . $total_suppressed_count . '</span>
            </button>
            <span data-bs-toggle="tooltip" data-bs-placement="left" title="' . htmlspecialchars('Learn why some ' . $website['biznames'] . ' are hidden', ENT_QUOTES) . '">
                <button type="button" class="btn btn-sm btn-link p-1" 
                        data-bs-toggle="modal"
                        data-bs-target="#suppressionModal">
                    <i class="bi bi-info-circle"></i>
                </button>
            </span>
        </div>';
}

$output .= '
    </div>';

// Helper function to build filter URLs
$buildFilterUrl = function($params, $key, $value) {
    $new_params = $params;
    if (empty($value)) {
        unset($new_params[$key]);
    } else {
        $new_params[$key] = $value;
    }
    return '?' . http_build_query($new_params);
};

$output .= '</div>'; // Close enrollment-header

// Warning Messages
$containermargin = '';
if ($allocation_warning) {
    $output .= '
<div class="container ' . $containermargin . '"></div>
<div class="allocation-alert alert-' . $allocation_warning['type'] . '">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <i class="bi bi-exclamation-circle me-2"></i>
                ' . $allocation_warning['message'] . '
            </div>';
    
    if ($balance['available_allocations'] == 0) {
        $output .= '
            <a href="/myaccount/earn-enrollments" class="btn btn-sm btn-light">Earn More ' . $qik->plural2(2, $label_token) . '</a>';
    }
    
    $output .= '
        </div>
    </div>
</div>';
    $containermargin = '';
}

// Company Grid
$output .= '
<div class="companies-container' . ($allocation_warning ? '' : ' ' . $containermargin) . '">';

if (empty($companies)) {
    $output .= '
    <div class="no-results">
        <i class="bi bi-search"></i>
        <h3>No ' . $website['biznames'] . ' found</h3>
        <p>Try adjusting your search or filters</p>
    </div>';
} else {
    $output .= '
    <div class="companies-grid">';
    
    // Debug first company to see field names
    if (!isset($debugged) && count($companies) > 0) {
        $output .= '<!-- DEBUG Company fields: ' . implode(', ', array_keys($companies[0])) . ' -->';
        $debugged = true;
    }
    
    foreach ($companies as $company) {
        $card_classes = 'company-card';
        if ($company['is_enrolled']) $card_classes .= ' enrolled';
        if (!empty($company['is_suppressed'])) $card_classes .= ' suppressed';
        
        $output .= '
        <div class="' . $card_classes . '" 
             data-company-id="' . $company['company_id'] . '"';
        
        if (!empty($company['is_suppressed'])) {
            $output .= '
             data-suppression-reasons=\'' . htmlspecialchars(json_encode($company['suppression_reasons'])) . '\'';
        }
        
        $output .= '>';
        
        // Company Image
        $output .= '
            <div class="company-image">';
        
        if (!empty($company['company_logo'])) {
            $company_image = $display->companyimage($company['company_id'] . '/' . $company['company_logo']);
            $output .= '<img class="h-100 w-100 object-fit-cover" loading="lazy" src="' . $company_image . '" alt="' . htmlspecialchars($company['company_name']) . '" />';
        } else {
            $output .= '<div class="company-placeholder"><i class="bi bi-gift"></i></div>';
        }
        
        $output .= '
            </div>';
        
        // Company Info
        $output .= '
            <div class="company-info">
                <h3 class="company-name">' . htmlspecialchars($company['company_name']) . '</h3>';
        
        if (!empty($company['reward_preview'])) {
            $output .= '
                <p class="reward-preview">' . htmlspecialchars($company['reward_preview']) . '</p>';
        } elseif (!empty($company['spinner_description'])) {
            $output .= '
                <p class="reward-preview">' . htmlspecialchars($company['spinner_description']) . '</p>';
        } elseif (!empty($company['description'])) {
            $output .= '
                <p class="reward-preview">' . htmlspecialchars(substr($company['description'], 0, 100)) . '...</p>';
        }
        
        $output .= '
                <div class="company-categories">';
        
        if (!empty($company['categories'])) {
            $output .= '
                    <span class="category-tag">' . htmlspecialchars($company['categories']) . '</span>';
        }
        
        if ($company['is_app_only']) {
            $output .= '
                    <span class="category-tag app-only"><i class="bi bi-phone-fill"></i> App Only</span>';
        }
        
        if ($company['total_value'] > 0) {
            $output .= '
                    <span class="category-tag value">$' . number_format($company['total_value'], 0) . ' value</span>';
        }
        
        $output .= '
                </div>';
        
        // Suppression or eligibility warning
        if (!empty($company['is_suppressed'])) {
            $reasons = array_values($company['suppression_reasons']);
            $reason_text = htmlspecialchars($reasons[0]);
            if (count($reasons) > 1) {
                $reason_text .= ' (+' . (count($reasons) - 1) . ' more)';
            }
            
            $output .= '
                <div class="suppression-warning mt-2">
                    <i class="bi bi-eye-slash-fill text-info"></i>
                    <span class="text-muted small">Hidden: ' . $reason_text . '</span>
                </div>';
        } elseif (!$company['eligibility']['eligible']) {
            $output .= '
                <div class="eligibility-warning mt-2">
                    <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                    <span class="text-danger small">' . htmlspecialchars($company['eligibility']['message']) . '</span>';
            
            if (!empty($company['eligibility']['action_url'])) {
                $output .= '
                    <a href="' . htmlspecialchars($company['eligibility']['action_url']) . '" class="small">Fix</a>';
            }
            
            $output .= '
                </div>';
        }
        
        $output .= '
            </div>';
        
        // Action Button
        $output .= '
            <div class="company-action">';
        
        if ($company['is_enrolled']) {
            $output .= '
                <button class="action-btn enrolled" disabled>
                    <i class="bi bi-check-circle-fill"></i> ' . $label_tokened . '
                </button>';
        } elseif (!$company['eligibility']['eligible']) {
            $output .= '
                <button class="action-btn disabled" disabled 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="' . htmlspecialchars($company['eligibility']['message']) . '">
                    <i class="bi bi-exclamation-circle"></i> Not Eligible
                </button>';
        } elseif ($balance['available_allocations'] > 0) {
            $company_logo_url = isset($company['company_logo']) ? $display->companyimage($company['company_id'] . '/' . $company['company_logo']) : '';
            $output .= '
                <button class="action-btn enroll" 
                        onclick="addToBasket(' . $company['company_id'] . ', \'' . htmlspecialchars(addslashes($company['company_name'])) . '\', \'' . $company_logo_url . '\')">
                    <i class="bi bi-plus-circle"></i> ' . $label_token . '
                </button>';
        } else {
            $output .= '
                <button class="action-btn disabled" disabled>
                    <i class="bi bi-lock"></i> No ' . $qik->plural2(0, $label_token) . '
                </button>';
        }
        
        $output .= '
            </div>
        </div>';
    }
    
    $output .= '
    </div>';
}

$output .= '
</div>';

// Loading Overlay
$output .= '
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>';

// Success Modal
$output .= '
<div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <i class="bi bi-check-circle-fill text-success"></i>
                <h4 class="mt-3">' . $qik->plural2(2, $label_token) . ' Submitted!</h4>
                <p id="successMessage" class="mb-3"></p>
                <p class="text-muted small mb-4">You will receive a notification when the enrollment has been completed.</p>
                <button type="button" class="btn btn-primary px-5" onclick="redirectToMyAccount()">OK</button>
            </div>
        </div>
    </div>
</div>';

// Floating Selection Counter
$output .= '
<div id="selectionCounter" class="selection-counter" style="display: none;" onclick="toggleBasketDetails()">
    <i class="bi bi-basket-fill"></i>
    <span id="basketCount">0</span>
</div>';

// Selection Details Modal
$output .= '
<div class="modal fade" id="basketModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    ' . ucwords($website['biznames']) . ' Picked (<span id="modalBasketCount">0</span>)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="basketItems">
                    <!-- Items will be added here dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="clearBasket()">Clear All</button>
                <button type="button" class="btn btn-success" onclick="confirmEnrollments()" id="confirmButton">
                    <i class="bi bi-check-circle"></i> Confirm
                </button>
            </div>
        </div>
    </div>
</div>';

// Suppression Explanation Modal
$output .= '
<div class="modal fade" id="suppressionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Why Some ' . ucfirst($website['biznames']) . ' Are Hidden</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">We automatically hide ' . $website['biznames'] . ' that may not be suitable for you based on various factors:</p>';

if (count($suppressed_companies) > 0) {
    $output .= '
                <div class="suppression-summary mb-3">';
    
    $suppression_counts = [];
    foreach ($suppressed_companies as $sc) {
        foreach ($sc['suppression_reasons'] as $type => $reason) {
            if (!isset($suppression_counts[$type])) {
                $suppression_counts[$type] = 0;
            }
            $suppression_counts[$type]++;
        }
    }
    
    $output .= '
                    <h6 class="mb-3">Currently Hidden:</h6>';
    
    if (count($suppression_counts) > 1 && $show_suppressed) {
        $output .= '
                    <div class="alert alert-light border mb-3">
                        <small class="text-muted">Toggle which types to show (you can select multiple):</small>
                    </div>';
    }
    
    $output .= '
                    <div class="suppression-filters">';
    
    foreach ($suppression_counts as $type => $count) {
        $icon = 'bi-x-circle';
        $label = ucfirst($type);
        $desc = '';
        
        switch($type) {
            case 'age':
                $icon = 'bi-calendar-x';
                $label = 'Age Restrictions';
                $desc = 'Outside your age range';
                break;
            case 'diet':
                $icon = 'bi-egg-fill';
                $label = 'Dietary Restrictions';
                $desc = 'Based on your dietary preferences';
                break;
            case 'gender':
                $icon = 'bi-gender-ambiguous';
                $label = 'Gender Specific';
                $desc = 'Targeted to different gender';
                break;
            case 'location':
                $icon = 'bi-geo-alt';
                $label = 'Location Based';
                $desc = 'Not available in your area';
                break;
        }
        
        $is_active = in_array($type, $active_filters) || $suppression_filter === 'all' || empty($active_filters);
        
        $output .= '
                        <div class="suppression-filter-item mb-3 p-2 border rounded">
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="flex-grow-1">
                                    <div>
                                        <i class="bi ' . $icon . ' text-muted me-2"></i>
                                        <strong>' . $label . ':</strong> 
                                        <span class="badge bg-secondary">' . $count . '</span>
                                        ' . ($count == 1 ? $website['bizname'] : $website['biznames']) . '
                                    </div>
                                    <small class="text-muted d-block ms-4">' . $desc . '</small>
                                </div>';
        
        if (count($suppression_counts) > 1 && $show_suppressed) {
            $output .= '
                                <div class="form-check form-switch ms-3">
                                    <input class="form-check-input suppression-type-toggle" 
                                           type="checkbox" 
                                           data-type="' . $type . '"
                                           id="toggle_' . $type . '" 
                                           ' . ($is_active ? 'checked' : '') . '
                                           onchange="toggleSuppressionType(\'' . $type . '\')">
                                </div>';
        }
        
        $output .= '
                            </div>
                        </div>';
    }
    
    $output .= '
                    </div>
                </div>';
}

$output .= '
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    You can toggle "Show Hidden ' . ucfirst($website['biznames']) . '" above to see and enroll in these ' . $website['biznames'] . ' if you wish.
                </div>
            </div>
        </div>
    </div>
</div>';

// Advanced Filters Modal
$output .= '
<div class="modal fade" id="advancedFiltersModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Advanced Filters</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="advancedFiltersForm" method="get" action="">
                    <!-- Preserve existing parameters -->
                    <input type="hidden" name="category" value="' . htmlspecialchars(implode(',', $selected_categories)) . '">
                    <input type="hidden" name="search" value="' . htmlspecialchars($search_query) . '">
                    ' . ($show_suppressed ? '<input type="hidden" name="show_suppressed" value="1">' : '') . '
                    
                    <!-- Rating Filter -->
                    <div class="mb-4">
                        <label class="form-label fw-bold" for="ratingSelect">
                            <i class="bi bi-star-fill text-warning"></i> Minimum Rating
                        </label>
                        <select class="form-select" name="rating" id="ratingSelect">
                            <option value=""' . (!$rating_filter ? ' selected' : '') . '>Any Rating</option>
                            <option value="5"' . ($rating_filter === '5' ? ' selected' : '') . '>⭐⭐⭐⭐⭐ 5 stars only</option>
                            <option value="4+"' . ($rating_filter === '4+' ? ' selected' : '') . '>⭐⭐⭐⭐ 4+ stars</option>
                            <option value="3+"' . ($rating_filter === '3+' ? ' selected' : '') . '>⭐⭐⭐ 3+ stars</option>
                        </select>
                    </div>
                    
                    <!-- Popular Filter -->
                    <div class="mb-4">
                        <label class="form-label fw-bold" for="popularSelect">
                            <i class="bi bi-fire text-danger"></i> Popularity
                        </label>
                        <select class="form-select" name="popular" id="popularSelect">
                            <option value=""' . (!$popular_filter ? ' selected' : '') . '>All ' . ucfirst($website['biznames']) . '</option>
                            <option value="top10"' . ($popular_filter === 'top10' ? ' selected' : '') . '>🔥 Top 10 Most Popular</option>
                            <option value="top25"' . ($popular_filter === 'top25' ? ' selected' : '') . '>🔥 Top 25 Most Popular</option>
                            <option value="top50"' . ($popular_filter === 'top50' ? ' selected' : '') . '>🔥 Top 50 Most Popular</option>
                            <option value="trending"' . ($popular_filter === 'trending' ? ' selected' : '') . '>📈 Trending Now</option>
                        </select>
                    </div>
                    
                    <!-- Value Filter -->
                    <div class="mb-4">
                        <label class="form-label fw-bold" for="valueSelect">
                            <i class="bi bi-cash-stack text-success"></i> Minimum Reward Value
                        </label>
                        <select class="form-select" name="value" id="valueSelect">
                            <option value=""' . (!$value_filter ? ' selected' : '') . '>Any Value</option>
                            <option value="50+"' . ($value_filter === '50+' ? ' selected' : '') . '>💰 $50+ value</option>
                            <option value="25+"' . ($value_filter === '25+' ? ' selected' : '') . '>💵 $25+ value</option>
                            <option value="10+"' . ($value_filter === '10+' ? ' selected' : '') . '>💵 $10+ value</option>
                            <option value="5+"' . ($value_filter === '5+' ? ' selected' : '') . '>💵 $5+ value</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>';

// Add clear button if filters are active
if ($rating_filter || $popular_filter || $value_filter) {
    $output .= '
                <a href="?' . http_build_query(array_diff_key($_GET, array_flip(['rating', 'popular', 'value']))) . '" 
                   class="btn btn-outline-danger">Clear Filters</a>';
}

$output .= '
                <button type="button" class="btn btn-primary" onclick="document.getElementById(\'advancedFiltersForm\').submit()">Apply Filters</button>
            </div>
        </div>
    </div>
</div>';

$output .= '
        </div> <!-- close enrollment-container -->
    </div> <!-- close container-fluid -->
</div> <!-- close main-content -->';

// Output the built HTML
echo $output;

// JavaScript section
echo '
<script>
// Initialize user data
window.userData = {
    userId: ' . $user_id . ',
    availableAllocations: ' . $balance['available_allocations'] . ',
    labels: {
        token: "' . $label_token . '",
        tokened: "' . $label_tokened . '"
    },
    hasEnrollments: ' . ((($accountstats['business_success'] ?? 0) + ($accountstats['business_pending'] ?? 0)) > 0 ? 'true' : 'false') . ',
    forceShowHelp: ' . (isset($_GET['showhelp']) ? 'true' : 'false') . '
};

// Define hasMoreCompanies variable for enrollment-picker.js
window.hasMoreCompanies = false; // Set to true if there are more companies to load
';
?>
// Smart header scroll behavior
document.addEventListener("DOMContentLoaded", function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover'
        });
    });
    
    let lastScrollTop = 0;
    const header = document.querySelector(".enrollment-header");
    const searchBar = document.querySelector(".search-bar");
    let ticking = false;
    let headerOriginalOffset = 0;
    
    // Get the original offset position of the header
    function getHeaderOffset() {
        const rect = header.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        return rect.top + scrollTop;
    }
    
    // Initialize header offset after a small delay to ensure layout is complete
    setTimeout(() => {
        headerOriginalOffset = getHeaderOffset();
    }, 100);
    
    function updateHeader() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;
        
        // Skip very small scroll differences (less than 5px) to avoid jitter
        if (Math.abs(scrollTop - lastScrollTop) < 5) {
            ticking = false;
            return;
        }
        
        // If we have not scrolled past the original header position, keep it relative
        if (scrollTop < headerOriginalOffset) {
            header.classList.remove("is-fixed");
            header.classList.remove("hidden");
        } else {
            // We have scrolled past the header original position
            header.classList.add("is-fixed");
            
            if (scrollTop > lastScrollTop) {
                // Scrolling down - hide header only if we are past its original position
                header.classList.add("hidden");
            } else {
                // Scrolling up - show header
                header.classList.remove("hidden");
            }
        }
        
        lastScrollTop = scrollTop;
        ticking = false;
    }
    
    // Listen to both scroll and touchmove for better mobile support
    ["scroll", "touchmove"].forEach(eventType => {
        window.addEventListener(eventType, function() {
            if (!ticking) {
                window.requestAnimationFrame(updateHeader);
                ticking = true;
            }
        }, { passive: true });
    });
    
    // Add fade effect to category scroll edges
    const categoryScroll = document.querySelector(".category-scroll");
    if (categoryScroll) {
        const checkScroll = () => {
            const parent = categoryScroll.parentElement;
            const scrollLeft = parent.scrollLeft;
            const scrollWidth = parent.scrollWidth;
            const clientWidth = parent.clientWidth;
            
            // Add gradient indicators for more content
            if (scrollLeft > 0) {
                parent.classList.add("has-scroll-left");
            } else {
                parent.classList.remove("has-scroll-left");
            }
            
            if (scrollLeft + clientWidth < scrollWidth - 5) {
                parent.classList.add("has-scroll-right");
            } else {
                parent.classList.remove("has-scroll-right");
            }
        };
        
        categoryScroll.parentElement.addEventListener("scroll", checkScroll);
        checkScroll(); // Initial check
    }
});

// Suppression toggle functions
function toggleSuppressed(show) {
    const currentUrl = new URL(window.location.href);
    if (show) {
        currentUrl.searchParams.set("show_suppressed", "1");
    } else {
        currentUrl.searchParams.delete("show_suppressed");
        currentUrl.searchParams.delete("suppression_filter");
    }
    window.location.href = currentUrl.toString();
}

function toggleSuppressionType(type) {
    const currentUrl = new URL(window.location.href);
    const currentFilter = currentUrl.searchParams.get("suppression_filter") || "all";
    
    let filters = [];
    if (currentFilter !== "all" && currentFilter !== "") {
        filters = currentFilter.split(",");
    }
    
    // Get all checked toggles
    const checkedTypes = [];
    document.querySelectorAll(".suppression-type-toggle:checked").forEach(toggle => {
        checkedTypes.push(toggle.dataset.type);
    });
    
    // If all are checked or none are checked, use all
    const allTypes = Array.from(document.querySelectorAll(".suppression-type-toggle")).map(t => t.dataset.type);
    if (checkedTypes.length === 0 || checkedTypes.length === allTypes.length) {
        currentUrl.searchParams.set("suppression_filter", "all");
    } else {
        currentUrl.searchParams.set("suppression_filter", checkedTypes.join(","));
    }
    
    window.location.href = currentUrl.toString();
}

// Clear search
function clearSearch() {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.delete("search");
    window.location.href = currentUrl.toString();
}
</script>

<script src="/public/js/enrollment-picker.js"></script>
<script src="/public/js/enrollment-basket.js"></script>
<?PHP
$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();