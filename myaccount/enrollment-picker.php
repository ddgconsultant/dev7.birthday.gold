<?php
/**
 * Mobile-First Enrollment Picker
 * Modern interface inspired by Groupon/Amazon mobile apps
 */

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.allocationmanager.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.configmanager.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.enrollment.php');

// Check login
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
$display_categories = ['All', 'Food', 'Beverage', 'Beauty', 'Retail', 'Other'];

// Get selected category
$selected_category = $_GET['category'] ?? 'All';
$search_query = $_GET['search'] ?? '';

// Get companies using existing system from businessselect.php
$companies = [];
$resultsize = 300;
$counter = ['total' => 0, 'record' => 0, 'display' => 0, 'rewards' => 0];

// If search query, use direct query
if ($search_query) {
    // Direct search query
    $sql = "SELECT DISTINCT c.*, a.description as company_logo 
            FROM bg_companies AS c
            LEFT JOIN bg_company_attributes AS a ON c.company_id = a.company_id 
                AND a.category = 'company_logos' AND a.grouping = 'primary_logo'
            WHERE c.status = 'finalized' 
                AND (c.company_name LIKE :search1 OR c.description LIKE :search2)";
    
    if ($selected_category !== 'All') {
        $sql .= " AND c.display_category = :category";
    }
    
    $sql .= " ORDER BY c.company_name LIMIT " . $resultsize;
    
    $params = [
        'search1' => "%{$search_query}%",
        'search2' => "%{$search_query}%"
    ];
    if ($selected_category !== 'All') {
        $params['category'] = $selected_category;
    }
    $companies = $database->getrows($sql, $params);
} else {
    // Use existing getSelectionCompanies method which filters out already enrolled companies
    if ($selected_category === 'All') {
        // Get companies from all categories
        foreach ($display_categories as $category) {
            if ($category === 'All') continue;
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
        // Get companies for specific category
        $companies = $app->getSelectionCompanies($resultsize, $selected_category);
    }
}

// Get all company IDs for eligibility check
$company_ids = array_column($companies, 'company_id');
$eligibilities = $enrollment->getCompanyEligibilities($user_id, $company_ids);

// Process companies to add additional data
$processed_companies = [];
foreach ($companies as $company) {
    // Age check
    if (($company['minage'] > $alive['years']) || ($company['maxage'] < $alive['years'])) {
        continue; // Skip age-restricted companies
    }
    
    // Check if already selected/enrolled
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

// Debug: Check if we got any results
if (empty($companies)) {
    echo "<!-- DEBUG: No companies returned from query -->";
    
    // Try a simpler query to see if we get any companies
    $simple_sql = "SELECT * FROM bg_companies WHERE status = 'active' LIMIT 5";
    $simple_results = $database->getrows($simple_sql);
    echo "<!-- DEBUG: Simple query returned " . count($simple_results) . " companies -->";
}

// Set up token labels for flexibility
$label_token = 'Pick';
$label_tokened = 'Picked';

// Page setup - MUST be before includes
$pagetitle = 'Pick Your Birthday Rewards';
$bodycontentclass = '';
$additionalstyles .= '<link rel="stylesheet" href="/public/css/enrollment-picker.css">';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="content-header-dark">
    <div class="container">
        <h1>Pick Your Birthday Rewards</h1>
        <p class="lead">Choose from hundreds of birthday reward programs</p>
    </div>
</div>

<div class="main-content py-3">
    <!-- Use container-fluid on mobile, container on desktop -->
    <div class="container-fluid px-0 px-md-3">
        <div class="enrollment-container mx-auto" style="max-width: 1400px;">

<!-- Sticky Header -->
<div class="enrollment-header sticky-top">
    <!-- Balance Bar -->
    <div class="balance-bar">
        <div class="balance-info">
            <span class="balance-number"><?php echo $balance['available_allocations']; ?></span>
            <span class="balance-label"><?php echo $qik->plural( $label_token, $balance['available_allocations']); ?> available</span>
        </div>
        
        <div class="selected-info" id="selectedInfo" style="display: none;" onclick="toggleBasketDetails()">
            <i class="bi bi-basket-fill"></i>
            <span class="selected-number" id="selectedCount">0</span>
            <span class="selected-label"><?php echo $label_tokened; ?></span>
        </div>
        
        <?php if ($balance['expiring_soon_count'] > 0): ?>
        <div class="expiring-warning">
            <i class="bi bi-clock-history"></i>
            <?php echo $balance['expiring_soon_count']; ?> expiring soon
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Search Bar -->
    <div class="search-bar">
        <form method="GET" class="search-form">
            <div class="search-input-wrapper">
                <i class="bi bi-search"></i>
                <input type="search" 
                       name="search" 
                       class="search-input" 
                       placeholder="Search birthday rewards..." 
                       value="<?php echo htmlspecialchars($search_query); ?>"
                       autocomplete="off">
                <?php if ($search_query): ?>
                <button type="button" class="clear-search" onclick="clearSearch()">
                    <i class="bi bi-x-circle"></i>
                </button>
                <?php endif; ?>
            </div>
            <?php if ($selected_category !== 'all'): ?>
            <input type="hidden" name="category" value="<?php echo $selected_category; ?>">
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Category Filter -->
    <div class="category-filter">
        <div class="category-scroll">
            <a href="?category=All" 
               class="category-pill <?php echo $selected_category === 'All' ? 'active' : ''; ?>">
                <i class="bi bi-grid"></i> All
            </a>
            <?php foreach ($display_categories as $cat): 
                if ($cat === 'All') continue;
                $cat_slug = strtolower($cat);
                $cat_icon = 'bi-tag';
                // Set icons for known categories
                switch($cat) {
                    case 'Food': $cat_icon = 'bi-egg-fried'; break;
                    case 'Beverage': $cat_icon = 'bi-cup-straw'; break;
                    case 'Beauty': $cat_icon = 'bi-stars'; break;
                    case 'Retail': $cat_icon = 'bi-shop'; break;
                    case 'Other': $cat_icon = 'bi-three-dots'; break;
                }
            ?>
            <a href="?category=<?php echo $cat; ?>" 
               class="category-pill <?php echo $selected_category === $cat ? 'active' : ''; ?>">
                <i class="<?php echo $cat_icon; ?>"></i>
                <?php echo $cat; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Warning Messages -->
<?php
$containermargin= '';
if ($allocation_warning): 

echo ' <div class="container '.$containermargin.'"></div>';
?>
<div class="allocation-alert alert-<?php echo $allocation_warning['type']; ?>">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <i class="bi bi-exclamation-circle me-2"></i>
                <?php echo $allocation_warning['message']; ?>
            </div>
            <?php if ($balance['available_allocations'] == 0): ?>
            <a href="/myaccount/earn-enrollments" class="btn btn-sm btn-light">Earn More <?php echo $qik->plural2(2, $label_token); ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php 
$containermargin= '';
endif; ?>

<!-- Company Grid -->
<div class="companies-container<?php echo $allocation_warning ? '' : ' '.$containermargin; ?>"><?php 
// Removed the extra echo statement
?>
    <?php if (empty($companies)): ?>
    <div class="no-results">
        <i class="bi bi-search"></i>
        <h3>No companies found</h3>
        <p>Try adjusting your search or filters</p>
    </div>
    <?php else: ?>
    
    <div class="companies-grid">
        <?php 
        // Debug first company to see field names
        if (!isset($debugged) && count($companies) > 0) {
            echo "<!-- DEBUG Company fields: " . implode(', ', array_keys($companies[0])) . " -->";
            $debugged = true;
        }
        foreach ($companies as $company): ?>
        <div class="company-card <?php echo $company['is_enrolled'] ? 'enrolled' : ''; ?>" 
             data-company-id="<?php echo $company['company_id']; ?>">
            
            <!-- Company Image -->
            <div class="company-image">
                <?php 
                // Use the company logo from the query
                if (!empty($company['company_logo'])) {
                    $company_image = $display->companyimage($company['company_id'] . '/' . $company['company_logo']);
                    echo '<img class="h-100 w-100 object-fit-cover" loading="lazy" src="' . $company_image . '" alt="' . htmlspecialchars($company['company_name']) . '" />';
                } else {
                    // Default placeholder
                    echo '<div class="company-placeholder"><i class="bi bi-gift"></i></div>';
                }
                ?>
              
            
            </div>
            
            <!-- Company Info -->
            <div class="company-info">
                <h3 class="company-name"><?php echo htmlspecialchars($company['company_name']); ?></h3>
                
                <?php if (!empty($company['reward_preview'])): ?>
                <p class="reward-preview"><?php echo htmlspecialchars($company['reward_preview']); ?></p>
                <?php elseif (!empty($company['spinner_description'])): ?>
                <p class="reward-preview"><?php echo htmlspecialchars($company['spinner_description']); ?></p>
                <?php elseif (!empty($company['description'])): ?>
                <p class="reward-preview"><?php echo htmlspecialchars(substr($company['description'], 0, 100)) . '...'; ?></p>
                <?php endif; ?>
                
                <div class="company-categories">
                    <?php if (!empty($company['categories'])): ?>
                    <span class="category-tag"><?php echo $company['categories']; ?></span>
                    <?php endif; ?>
                    <?php if ($company['is_app_only']): ?>
                    <span class="category-tag app-only"><i class="bi bi-phone-fill"></i> App Only</span>
                    <?php endif; ?>
                    <?php if ($company['total_value'] > 0): ?>
                    <span class="category-tag value">$<?php echo number_format($company['total_value'], 0); ?> value</span>
                    <?php endif; ?>
                </div>
                
                <?php if (!$company['eligibility']['eligible']): ?>
                <div class="eligibility-warning mt-2">
                    <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                    <span class="text-danger small"><?php echo htmlspecialchars($company['eligibility']['message']); ?></span>
                    <?php if (!empty($company['eligibility']['action_url'])): ?>
                    <a href="<?php echo htmlspecialchars($company['eligibility']['action_url']); ?>" class="small">Fix</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Action Button -->
            <div class="company-action">
                <?php if ($company['is_enrolled']): ?>
                <button class="action-btn enrolled" disabled>
                    <i class="bi bi-check-circle-fill"></i> <?php echo $label_tokened; ?>
                </button>
                <?php elseif (!$company['eligibility']['eligible']): ?>
                <button class="action-btn disabled" disabled 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="<?php echo htmlspecialchars($company['eligibility']['message']); ?>">
                    <i class="bi bi-exclamation-circle"></i> Not Eligible
                </button>
                <?php elseif ($balance['available_allocations'] > 0): ?>
                <button class="action-btn enroll" 
                        onclick="addToBasket(<?php echo $company['company_id']; ?>, '<?php echo htmlspecialchars(addslashes($company['company_name'])); ?>', '<?php echo isset($company['company_logo']) ? $display->companyimage($company['company_id'] . '/' . $company['company_logo']) : ''; ?>')">
                    <i class="bi bi-plus-circle"></i> <?php echo $label_token; ?>
                </button>
                <?php else: ?>
                <button class="action-btn disabled" disabled>
                    <i class="bi bi-lock"></i> No <?php echo $qik->plural2(0, $label_token); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php endif; ?>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                <h4 class="mt-3"><?php echo $qik->plural2(2, $label_token); ?> Submitted!</h4>
                <p id="successMessage" class="mb-3"></p>
                <p class="text-muted small mb-4">You will receive a notification when the enrollment has been completed.</p>
                <button type="button" class="btn btn-primary px-5" onclick="redirectToMyAccount()">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Floating Selection Counter -->
<div id="selectionCounter" class="selection-counter" style="display: none;" onclick="toggleBasketDetails()">
    <i class="bi bi-basket-fill"></i>
    <span id="basketCount">0</span>
</div>

<!-- Selection Details Modal -->
<div class="modal fade" id="basketModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Businesses Picked (<span id="modalBasketCount">0</span>)
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
</div>


<style>
/* Floating counter button */
.selection-counter {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: var(--bs-primary);
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: bold;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
    cursor: pointer;
    z-index: 1040;
    transition: all 0.3s ease;
}

.selection-counter:hover {
    transform: scale(1.1);
    background: var(--bs-primary);
    filter: brightness(0.9);
}

.selection-counter i {
    font-size: 1.5rem;
    margin-right: 0.25rem;
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

/* Selected state for cards */
.action-btn.selected {
    background: #28a745;
    color: white;
}

.action-btn.selected:hover {
    background: #218838;
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

/* Fix for Android Chrome black background */
body {
    background-color: #f8f9fa !important;
    color: #212529 !important;
}

/* Position cart icon above bottom nav on mobile */
@media (max-width: 768px) {
    .selection-counter {
        bottom: 80px !important;
    }
}

/* First-time picker help popover styling */
.first-picker-popover {
    z-index: 9999;
}

.first-picker-popover .popover-body {
    background: var(--bs-primary); /* Using Bootstrap primary color */
    color: white;
    font-weight: 500;
    padding: 12px 16px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); /* Added shadow for more prominence */
}

/* Fix popover arrow for different placements */
.first-picker-popover .popover-arrow::before {
    border-left-color: var(--bs-primary) !important;
}

.first-picker-popover.bs-popover-start .popover-arrow::after,
.first-picker-popover.bs-popover-auto[data-popper-placement^="left"] .popover-arrow::after {
    border-left-color: var(--bs-primary) !important;
}

.first-picker-popover.bs-popover-end .popover-arrow::after,
.first-picker-popover.bs-popover-auto[data-popper-placement^="right"] .popover-arrow::after {
    border-right-color: var(--bs-primary) !important;
}

.first-picker-popover.bs-popover-top .popover-arrow::after,
.first-picker-popover.bs-popover-auto[data-popper-placement^="top"] .popover-arrow::after {
    border-top-color: var(--bs-primary) !important;
}

.first-picker-popover.bs-popover-bottom .popover-arrow::after,
.first-picker-popover.bs-popover-auto[data-popper-placement^="bottom"] .popover-arrow::after {
    border-bottom-color: var(--bs-primary) !important;
}

/* Smooth transitions for header elements */
.search-bar, .category-filter {
    transition: all 0.3s ease-in-out;
}

/* Visual indicators for scrollable content */
.category-filter {
    position: relative;
}

/* Gradient fade on edges when scrollable */
.category-filter.has-scroll-left::before,
.category-filter.has-scroll-right::after {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 20px;
    pointer-events: none;
    z-index: 1;
}

.category-filter.has-scroll-left::before {
    left: 0;
    background: linear-gradient(to right, white, transparent);
}

.category-filter.has-scroll-right::after {
    right: 0;
    background: linear-gradient(to left, white, transparent);
}

/* Ensure header stays on top during transitions */
.enrollment-header {
    transition: transform 0.3s ease-in-out;
}

/* Eligibility warning styles */
.eligibility-warning {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    background-color: #fff3cd;
    border-radius: 0.25rem;
    margin-top: 0.5rem;
}

.eligibility-warning i {
    flex-shrink: 0;
}

.eligibility-warning a {
    margin-left: auto;
    white-space: nowrap;
}

/* Not eligible button styling */
.action-btn.disabled[data-bs-toggle="tooltip"] {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}
</style>

        </div> <!-- close enrollment-container -->
    </div> <!-- close container-fluid -->
</div> <!-- close main-content -->

<script>
// Initialize user data
window.userData = {
    userId: <?php echo $user_id; ?>,
    availableAllocations: <?php echo $balance['available_allocations']; ?>,
    labels: {
        token: '<?php echo $label_token; ?>',
        tokened: '<?php echo $label_tokened; ?>'
    },
    hasEnrollments: <?php echo (($accountstats['business_success'] ?? 0) + ($accountstats['business_pending'] ?? 0)) > 0 ? 'true' : 'false'; ?>,
    forceShowHelp: <?php echo isset($_GET['showhelp']) ? 'true' : 'false'; ?>
};

// Smart header scroll behavior
document.addEventListener('DOMContentLoaded', function() {
    let lastScrollTop = 0;
    const header = document.querySelector('.enrollment-header');
    const searchBar = document.querySelector('.search-bar');
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
        
        // If we haven't scrolled past the original header position, keep it relative
        if (scrollTop < headerOriginalOffset) {
            header.classList.remove('is-fixed');
            header.classList.remove('hidden');
        } else {
            // We've scrolled past the header's original position
            header.classList.add('is-fixed');
            
            if (scrollTop > lastScrollTop) {
                // Scrolling down - hide header only if we're past its original position
                header.classList.add('hidden');
            } else {
                // Scrolling up - show header
                header.classList.remove('hidden');
            }
        }
        
        lastScrollTop = scrollTop;
        ticking = false;
    }
    
    // Listen to both scroll and touchmove for better mobile support
    ['scroll', 'touchmove'].forEach(eventType => {
        window.addEventListener(eventType, function() {
            if (!ticking) {
                window.requestAnimationFrame(updateHeader);
                ticking = true;
            }
        }, { passive: true });
    });
    
    // Add fade effect to category scroll edges
    const categoryScroll = document.querySelector('.category-scroll');
    if (categoryScroll) {
        const checkScroll = () => {
            const parent = categoryScroll.parentElement;
            const scrollLeft = parent.scrollLeft;
            const scrollWidth = parent.scrollWidth;
            const clientWidth = parent.clientWidth;
            
            // Add gradient indicators for more content
            if (scrollLeft > 0) {
                parent.classList.add('has-scroll-left');
            } else {
                parent.classList.remove('has-scroll-left');
            }
            
            if (scrollLeft + clientWidth < scrollWidth - 5) {
                parent.classList.add('has-scroll-right');
            } else {
                parent.classList.remove('has-scroll-right');
            }
        };
        
        categoryScroll.parentElement.addEventListener('scroll', checkScroll);
        checkScroll(); // Initial check
    }
});
</script>

<script src="/public/js/enrollment-picker.js"></script>
<script src="/public/js/enrollment-basket.js"></script>

<?php
$display_footertype='min';+
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>