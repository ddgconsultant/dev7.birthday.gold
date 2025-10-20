<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PAGE SETUP
#-------------------------------------------------------------------------------
$page_title = "Enhanced User Management - Birthday.Gold";
$page_description = "Advanced user management with real-time search and filtering";

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$p_displaylength = 180;
$searchTerm = $_GET['searchTerm'] ?? $_POST['searchTerm'] ?? '';
$initialLoadCount = 50; // Initial users to load
$loadMoreCount = 25; // Users to load per scroll

#-------------------------------------------------------------------------------
# HANDLE THE PROFILE UPDATE ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    if (isset($_POST['formtype'])) {
        switch ($_POST['formtype']) {
            case 'changedisplaylength':
                $p_displaylength = $_POST['displaylength'];
                break;
            case 'search':
                $searchTerm = trim($_POST['searchTerm'] ?? '');
                break;
        }
    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
// This variable is no longer used - we'll handle it in the main query
$userlimitsql = '';

// Clean, Professional User Management CSS
$additionalstyles .= '
<style>
/* Clean Professional Styles - Less is More */
.main-content {
    min-height: calc(100vh - 200px);
    padding: 2rem 1rem;
    background-color: #f8f9fa;
}

/* Remove container-fluid override - use standard Bootstrap container */

/* Consistent spacing */
.mb-4 {
    margin-bottom: 1.5rem !important;
}

.mb-3 {
    margin-bottom: 1rem !important;
}

/* Header Section - Clean and readable */
h1 {
    font-size: 2rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.5rem;
    letter-spacing: -0.02em;
}

.text-muted {
    color: #6c757d !important;
    font-size: 1rem;
    font-weight: 400;
}

/* Search Bar - Simple and functional */
.search-box {
    max-width: 100%;
    position: relative;
}

.search-input {
    width: 100%;
    padding: 0.875rem 1.25rem;
    padding-left: 2.75rem;
    font-size: 0.9375rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    transition: all 0.2s ease;
    background: white;
}

.search-input {
    color: #6c757d !important;
}

.search-input::placeholder {
    color: #adb5bd;
}

/* Search icon is now an element, not pseudo-element */

.search-input:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.1);
}

/* Stats Cards - Using Bootstrap utilities */
.stat-card {
    transition: border-color 0.2s ease;
}

.stat-card:hover {
    border-color: #dee2e6;
}

.stat-value {
    font-variant-numeric: tabular-nums;
}

/* Filter Section - Functional */
.filter-section {
    background: transparent;
    padding: 0;
    border: none;
}

.filter-section h5 {
    font-weight: 600;
    color: #212529;
    font-size: 1rem;
}

/* Remove custom form-select styles - let Bootstrap handle it */

.form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.1);
}

/* User List Container - Using Bootstrap utilities */
.user-list {
    display: flex;
    flex-direction: column;
    gap: 0.25rem; /* Reduced from 0.5rem */
}

/* User List Item - Using Bootstrap card utilities */
.user-item {
    transition: all 0.15s ease;
    overflow: visible;
}

/* Test user highlighting */
.user-item.test-user {
    background-color: #fff3cd !important;
    border-color: #ffeaa7 !important;
}

.user-item.test-user:hover {
    background-color: #ffeaa7 !important;
    border-color: #ffc107 !important;
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.15) !important;
}

/* Parental/Minor account highlighting */
.user-item.parental-user {
    border-left: 4px solid #17a2b8 !important; /* Info blue color */
}

.user-item:hover {
    border-color: #dee2e6;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    background-color: rgba(13, 110, 253, 0.02);
}

/* Removed user-item-content as we are using Bootstrap grid now */

/* User avatar - styles applied inline with Bootstrap utilities */

/* User info styles - using Bootstrap utilities instead */

/* Clean Badge Styles */
.badge {
    font-size: 0.6875rem !important;
    padding: 0.1875rem 0.5rem !important;
    font-weight: 500 !important;
    border-radius: 4px;
    white-space: nowrap;
    border: none !important;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

/* Use Bootstraps default badge colors - theyre already well-designed */
.badge.text-bg-success {
    background-color: #198754 !important;
}

.badge.text-bg-warning {
    background-color: #ffc107 !important;
    color: #000 !important;
}

.badge.text-bg-danger {
    background-color: #dc3545 !important;
}

.badge.text-bg-info {
    background-color: #0dcaf0 !important;
    color: #000 !important;
}

.badge.text-bg-secondary {
    background-color: #6c757d !important;
}

.badge.text-bg-primary {
    background-color: #0d6efd !important;
}

.user-details {
    display: flex;
    gap: 1rem;
    align-items: center;
    font-size: 0.8125rem;
    color: #6c757d;
    flex-wrap: wrap;
}

.user-detail-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-detail-item i {
    font-size: 0.75rem;
    flex-shrink: 0;
    color: #adb5bd;
}

.user-stats {
    display: flex;
    gap: 1.5rem;
    align-items: center;
    flex-shrink: 0;
}

.user-stat {
    text-align: center;
    min-width: 60px;
}

.user-stat-value {
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.user-stat-label {
    font-size: 0.6875rem;
    color: #6c757d;
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    margin-top: 0.125rem;
}

.user-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-shrink: 0;
    position: relative;
}

/* Professional Button Styles */
.user-actions .btn {
    font-size: 0.8125rem;
    padding: 0.375rem 0.75rem;
    border-radius: 4px;
    white-space: nowrap;
    font-weight: 500;
    transition: all 0.15s ease;
}

.user-actions .btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

.user-actions .btn-primary:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
}

.user-actions .btn-outline-secondary {
    background: transparent;
    border: 1px solid #dee2e6;
    color: #6c757d;
}

.user-actions .btn-outline-secondary:hover {
    background-color: #f8f9fa;
    border-color: #dee2e6;
    color: #495057;
}

/* Dropdown Styling */
.user-actions .dropdown {
    position: static;
}

.user-actions .dropdown-menu {
    z-index: 105000;
    position: absolute;
    right: 0;
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-radius: 6px;
    padding: 0.25rem;
    min-width: 180px;
}

.dropdown-item {
    border-radius: 4px;
    padding: 0.5rem 0.75rem;
    transition: background-color 0.15s ease;
    font-size: 0.875rem;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.dropdown-item i {
    width: 16px;
    text-align: center;
    font-size: 0.875rem;
}

/* Loading States */
.loading-spinner {
    text-align: center;
    padding: 4rem;
}

.loading-spinner .spinner-border {
    width: 2.5rem;
    height: 2.5rem;
    border-width: 0.2rem;
    color: #0d6efd;
}

.skeleton-card {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    border: 1px solid #e9ecef;
    position: relative;
    overflow: hidden;
}

.skeleton-card::after {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.02), transparent);
    animation: skeleton-loading 1.5s infinite;
}

@keyframes skeleton-loading {
    0% { left: -100%; }
    100% { left: 100%; }
}

.skeleton-header {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.skeleton-avatar {
    width: 48px;
    height: 48px;
    background: #e9ecef;
    border-radius: 8px;
}

.skeleton-info {
    flex: 1;
}

.skeleton-line {
    background: #e9ecef;
    height: 0.75rem;
    border-radius: 4px;
    margin-bottom: 0.5rem;
}

.skeleton-line.short {
    width: 60%;
}

/* Load More Button */
.load-more-container {
    text-align: center;
    margin: 2rem 0;
}

.load-more-btn {
    padding: 0.625rem 2rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 6px;
    transition: all 0.15s ease;
    background-color: #0d6efd;
    border: 1px solid #0d6efd;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.load-more-btn:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
}

.load-more-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

/* No Results */
.no-results {
    text-align: center;
    padding: 4rem;
    color: #6c757d;
}

.no-results i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #dee2e6;
}

.no-results h4 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

/* Verified Badge Icon */
.bi-patch-check-fill {
    color: #0d6efd !important;
    font-size: 0.875rem !important;
}

/* Responsive Design - simplified with Bootstrap grid */
@media (max-width: 767px) {
    h1 {
        font-size: 1.5rem;
    }
    
    .text-muted {
        font-size: 0.875rem;
    }
    
    .user-item {
        padding: 0.875rem;
    }
    
    .search-input {
        font-size: 0.875rem;
        padding: 0.625rem 1rem;
        padding-left: 2.5rem;
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .stat-label {
        font-size: 0.75rem;
    }
}

/* Back Button */
.back-button {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    z-index: 100;
}

/* Smooth transitions for interactive elements */
a, button, input, select, .btn {
    transition: all 0.15s ease;
}

/* Focus states for accessibility */
button:focus-visible,
a:focus-visible,
input:focus-visible,
select:focus-visible {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
}

/* Ensure dropdowns dont get cut off */
.user-item {
    position: relative;
}

.user-actions {
    position: relative;
    z-index: 2;
}

/* Animation for page load - subtle */
.user-item {
    animation: fadeIn 0.3s ease-out;
    animation-fill-mode: both;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Stagger animation for multiple items */
.user-item:nth-child(1) { animation-delay: 0.05s; }
.user-item:nth-child(2) { animation-delay: 0.1s; }
.user-item:nth-child(3) { animation-delay: 0.15s; }
.user-item:nth-child(4) { animation-delay: 0.2s; }
.user-item:nth-child(5) { animation-delay: 0.25s; }

/* Remove animations for users who prefer reduced motion */
@media (prefers-reduced-motion: reduce) {
    * {
        animation: none !important;
        transition: none !important;
    }
}

/* Print styles */
@media print {
    .back-button,
    .user-actions,
    .filter-section,
    .search-box {
        display: none !important;
    }
    
    .user-item {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>
';

// Add Bootstrap Icons
$additionalheaders = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">';

$bodycontentclass = '';
$header_flush = true; // Ensure header content is flush with admin header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Get initial stats - now includes both real and test users by default
// Filter based on typeFilter if provided
$typeFilterForStats = $_GET['typeFilter'] ?? '';
$typeClause = '';
if ($typeFilterForStats === 'real') {
    $typeClause = "type='real'";
} elseif ($typeFilterForStats === 'test') {
    $typeClause = "type='test'";
} elseif (empty($typeFilterForStats) || in_array($typeFilterForStats, ['individual', 'business', 'parental'])) {
    // Default to real users for stats unless specifically filtering for test
    $typeClause = "type='real'";
}

$totalUsers = $database->prepare("SELECT COUNT(*) as total FROM bg_users WHERE $typeClause");
$totalUsers->execute();
$totalUsersCount = $totalUsers->fetch(PDO::FETCH_ASSOC)['total'];

$activeUsers = $database->prepare("SELECT COUNT(*) as total FROM bg_users WHERE $typeClause AND status='active'");
$activeUsers->execute();
$activeUsersCount = $activeUsers->fetch(PDO::FETCH_ASSOC)['total'];

$newUsersToday = $database->prepare("SELECT COUNT(*) as total FROM bg_users WHERE $typeClause AND DATE(create_dt) = CURDATE()");
$newUsersToday->execute();
$newUsersTodayCount = $newUsersToday->fetch(PDO::FETCH_ASSOC)['total'];

$paidUsers = $database->prepare("SELECT COUNT(*) as total FROM bg_users WHERE $typeClause AND account_plan != 'free'");
$paidUsers->execute();
$paidUsersCount = $paidUsers->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!-- Admin Header Section -->
<div class="content-header-admin">
    <div class="container">
        <h1 class="mb-3"><i class="bi bi-people me-3"></i>User Management</h1>
        <p class="lead mb-4">Advanced user search with real-time filtering</p>
    </div>
</div>

<!-- Search Bar in header area like help page -->
<div class="container" style="margin-top: -2rem; margin-bottom: 2rem; position: relative; z-index: 10;">
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="search-box mx-auto" style="max-width: 600px; position: relative;">
        <?php echo $display->inputcsrf_token(); ?>
        <input type="hidden" name="formtype" value="search">
        <i class="bi bi-search" style="position: absolute; left: 1.5rem; top: 50%; transform: translateY(-50%); font-size: 1.25rem; color: #6c757d; z-index: 10;"></i>
        <input 
            type="text" 
            name="searchTerm"
            id="searchInput"
            class="form-control form-control-lg search-input" 
            placeholder="Search users by name, email, username, location, status..."
            value="<?php echo htmlspecialchars($searchTerm); ?>"
            style="border-radius: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 1rem 3.5rem 1rem 3.5rem;"
        >
        <?php if (!empty($searchTerm)): ?>
        <button type="button" class="btn btn-link p-0" onclick="document.getElementById('searchInput').value=''; this.form.submit();" style="position: absolute; right: 1.5rem; top: 50%; transform: translateY(-50%); color: #6c757d; text-decoration: none;">
            <i class="bi bi-x-lg" style="font-size: 1.25rem;"></i>
        </button>
        <?php endif; ?>
    </form>
</div>

<div class="container py-2">
        <div class="col-12">
            
            <!-- Quick Stats -->
            <div class="row mb-4">
                <div class="col-md-3 col-6 mb-3">
                    <div class="card text-center p-3 stat-card">
                        <div class="fs-2 fw-bold text-primary stat-value"><?php echo number_format($totalUsersCount); ?></div>
                        <div class="text-muted small text-uppercase">Total Users</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="card text-center p-3 stat-card">
                        <div class="fs-2 fw-bold text-primary stat-value"><?php echo number_format($activeUsersCount); ?></div>
                        <div class="text-muted small text-uppercase">Active Users</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="card text-center p-3 stat-card">
                        <div class="fs-2 fw-bold text-primary stat-value"><?php echo number_format($newUsersTodayCount); ?></div>
                        <div class="text-muted small text-uppercase">New Today</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="card text-center p-3 stat-card">
                        <div class="fs-2 fw-bold text-primary stat-value"><?php echo number_format($paidUsersCount); ?></div>
                        <div class="text-muted small text-uppercase">Paid Users</div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="filter-section mb-4">
                <?php if (!empty($searchTerm)): ?>
                <input type="hidden" name="searchTerm" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <?php endif; ?>
                <div class="row align-items-center">
                    <div class="col-md-2">
                        <h5 class="mb-0">Quick Filters</h5>
                    </div>
                    <div class="col-md-10">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <select class="form-select" name="statusFilter" id="statusFilter" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo ($_GET['statusFilter'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="pending" <?php echo ($_GET['statusFilter'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="suspended" <?php echo ($_GET['statusFilter'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    <option value="validated" <?php echo ($_GET['statusFilter'] ?? '') === 'validated' ? 'selected' : ''; ?>>Validated</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="planFilter" id="planFilter" onchange="this.form.submit()">
                                    <option value="">All Plans</option>
                                    <option value="free" <?php echo ($_GET['planFilter'] ?? '') === 'free' ? 'selected' : ''; ?>>Free</option>
                                    <option value="basic" <?php echo ($_GET['planFilter'] ?? '') === 'basic' ? 'selected' : ''; ?>>Basic</option>
                                    <option value="premium" <?php echo ($_GET['planFilter'] ?? '') === 'premium' ? 'selected' : ''; ?>>Premium</option>
                                    <option value="vip" <?php echo ($_GET['planFilter'] ?? '') === 'vip' ? 'selected' : ''; ?>>VIP</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="typeFilter" id="typeFilter" onchange="this.form.submit()">
                                    <option value="">All Types</option>
                                    <option value="real" <?php echo ($_GET['typeFilter'] ?? '') === 'real' ? 'selected' : ''; ?>>Real Users</option>
                                    <option value="test" <?php echo ($_GET['typeFilter'] ?? '') === 'test' ? 'selected' : ''; ?>>Test Users</option>
                                    <option value="individual" <?php echo ($_GET['typeFilter'] ?? '') === 'individual' ? 'selected' : ''; ?>>Individual</option>
                                    <option value="business" <?php echo ($_GET['typeFilter'] ?? '') === 'business' ? 'selected' : ''; ?>>Business</option>
                                    <option value="parental" <?php echo ($_GET['typeFilter'] ?? '') === 'parental' ? 'selected' : ''; ?>>Parental</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="dayFilter" id="dayFilter" onchange="this.form.submit()">
                                    <option value="180" <?php echo ($_GET['dayFilter'] ?? '180') === '180' ? 'selected' : ''; ?>>Last 180 Days</option>
                                    <option value="90" <?php echo ($_GET['dayFilter'] ?? '') === '90' ? 'selected' : ''; ?>>Last 90 Days</option>
                                    <option value="30" <?php echo ($_GET['dayFilter'] ?? '') === '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                                    <option value="7" <?php echo ($_GET['dayFilter'] ?? '') === '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                                    <option value="1" <?php echo ($_GET['dayFilter'] ?? '') === '1' ? 'selected' : ''; ?>>Today</option>
                                    <option value="all" <?php echo ($_GET['dayFilter'] ?? '') === 'all' ? 'selected' : ''; ?>>All Time</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
    
            <?php if (!empty($searchTerm)): ?>
            <div class="alert alert-info mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-search me-2"></i>
                    Showing search results for: <strong><?php echo htmlspecialchars($searchTerm); ?></strong>
                    <span class="text-muted ms-2" id="searchResultCount"></span>
                </div>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-sm btn-outline-secondary">Clear Search</a>
            </div>
            <?php endif; ?>
            
            <!-- User List -->
            <div class="user-list" id="userList">
        <!-- Users will be loaded here via AJAX -->
        <?php
        // Load initial users for fallback
        $searchWhere = '';
        $searchParams = [];
        
        // Get filters from GET/POST
        $statusFilter = $_GET['statusFilter'] ?? $_POST['statusFilter'] ?? '';
        $planFilter = $_GET['planFilter'] ?? $_POST['planFilter'] ?? '';
        $typeFilter = $_GET['typeFilter'] ?? $_POST['typeFilter'] ?? '';
        $dayFilter = $_GET['dayFilter'] ?? $_POST['dayFilter'] ?? '180';
        
        if (!empty($searchTerm)) {
            $searchLike = '%' . $searchTerm . '%';
            $searchWhere = " AND (
                u.first_name LIKE :search1 OR 
                u.last_name LIKE :search2 OR 
                u.username LIKE :search3 OR 
                u.email LIKE :search4 OR 
                u.city LIKE :search5 OR 
                u.state LIKE :search6 OR 
                u.zip_code LIKE :search7 OR 
                u.status LIKE :search8 OR 
                u.account_plan LIKE :search9 OR 
                u.account_type LIKE :search10 OR
                DATE_FORMAT(u.birthdate, '%M %d') LIKE :search11 OR
                DATE_FORMAT(u.create_dt, '%M %d') LIKE :search12 OR
                DATE_FORMAT(u.create_dt, '%Y-%m-%d') LIKE :search13
            )";
            
            // Create search parameters array
            for ($i = 1; $i <= 13; $i++) {
                $searchParams[':search' . $i] = $searchLike;
            }
        }
        
        // Add filter conditions
        if (!empty($statusFilter)) {
            $searchWhere .= " AND u.status = :status";
            $searchParams[':status'] = $statusFilter;
        }
        
        if (!empty($planFilter)) {
            $searchWhere .= " AND u.account_plan = :plan";
            $searchParams[':plan'] = $planFilter;
        }
        
        // Handle user type filter - distinguish between real/test users and account types
        if (!empty($typeFilter)) {
            if ($typeFilter === 'real' || $typeFilter === 'test') {
                // Filter by user type (real vs test)
                $searchWhere .= " AND u.type = :usertype";
                $searchParams[':usertype'] = $typeFilter;
            } else {
                // Filter by account type (individual/business/parental)
                $searchWhere .= " AND u.account_type = :type";
                $searchParams[':type'] = $typeFilter;
            }
        } else {
            // Default to showing only real users when no filter is specified
            $searchWhere .= " AND u.type = 'real'";
        }

        // Fix the day filter to properly handle 'all' option
        if ($dayFilter !== 'all' && !empty($dayFilter) && is_numeric($dayFilter)) {
            $searchWhere .= " AND u.create_dt >= DATE_SUB(CURDATE(), INTERVAL " . intval($dayFilter) . " DAY)";
        }
        // When $dayFilter === 'all', we don't add any date restriction
        
        $initialUsersSql = "
            SELECT
                u.user_id,
                u.first_name,
                u.last_name,
                u.username,
                u.email,
                u.birthdate,
                TIMESTAMPDIFF(YEAR, u.birthdate, CURDATE()) AS age,
                u.city,
                u.state,
                u.status,
                u.account_plan,
                u.account_type,
                u.create_dt,
                a.description as avatar,
                COALESCE(ec.pending_count, 0) as pending_enrollments,
                COALESCE(ec.success_count, 0) as success_enrollments,
                COALESCE(ec.total_count, 0) as total_enrollments
            FROM bg_users u
            LEFT JOIN bg_user_attributes a ON u.user_id = a.user_id
                AND a.name = 'avatar'
                AND a.category = 'primary'
                AND a.status = 'active'
            LEFT JOIN (
                SELECT
                    user_id,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
                    COUNT(*) as total_count
                FROM bg_user_companies
                GROUP BY user_id
            ) ec ON u.user_id = ec.user_id
            WHERE 1=1 $searchWhere
            ORDER BY u.create_dt DESC
            LIMIT 50
        ";
        
        $initialStmt = $database->prepare($initialUsersSql);
        if (!empty($searchParams)) {
            $initialStmt->execute($searchParams);
        } else {
            $initialStmt->execute();
        }
        
        $searchResultCount = 0;
        
        if ($initialStmt) {
            while ($user = $initialStmt->fetch(PDO::FETCH_ASSOC)) {
                $searchResultCount++;
            $avatar = $user['avatar'] ?: '/public/avatars/problemavatar.png';
            $avatar = str_replace('cdn.birthday.gold', $website['cdnurl'], $avatar);
            $location = trim(($user['city'] ?: '') . ($user['city'] && $user['state'] ? ', ' : '') . ($user['state'] ?: '')) ?: 'Unknown';
            
            // Get badges
            $isAdmin = $account->isadmin(['user_id' => $user['user_id']]);
            $isStaff = $account->isstaff('*', $user['user_id']);
            $isVerified = !empty($account->getUserAttribute($user['user_id'], 'verified'));
            
            // Determine badge colors
            $statusColors = [
                'active' => 'success',
                'pending' => 'warning',
                'suspended' => 'danger',
                'validated' => 'info'
            ];
            $statusColor = $statusColors[$user['status']] ?? 'secondary';
            
            $planColors = [
                'free' => 'secondary',
                'basic' => 'primary',
                'premium' => 'warning',
                'vip' => 'danger'
            ];
            $planColor = $planColors[$user['account_plan']] ?? 'secondary';
            ?>
            <?php
            // Check if this is a test user
            $isTestUser = (strpos(strtolower($user['username']), 'test') !== false || 
                          strpos(strtolower($user['email']), 'test') !== false ||
                          strpos(strtolower($user['first_name']), 'test') !== false ||
                          strpos(strtolower($user['last_name']), 'test') !== false);
            
            // Check if this is a parental/minor account
            $isParentalUser = ($user['account_type'] === 'parental');
            ?>
            <div class="card p-2 px-4 mb-1 user-item<?php echo $isTestUser ? ' test-user' : ''; ?><?php echo $isParentalUser ? ' parental-user' : ''; ?>" data-user-id="<?php echo $user['user_id']; ?>">
                <div class="row align-items-start g-3">
                    <!-- Column A: Avatar, name, email, username -->
                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-start gap-3">
                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="" class="rounded-circle user-avatar" style="width: 48px; height: 48px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <div class="fw-semibold user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                <div class="small text-muted">
                                    <div>
                                        <?php if ($user['age']): ?>
                                        <i class="bi bi-cake me-1"></i><?php echo $user['age']; ?>
                                        <?php endif; ?>
                                        <i class="bi bi-envelope <?php echo $user['age'] ? 'ms-2' : ''; ?> me-1"></i><?php echo htmlspecialchars($user['email']); ?>
                                        <i class="bi bi-person ms-2 me-1"></i>@<?php echo htmlspecialchars($user['username']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Column B: Location -->
                    <div class="col-12 col-md-2">
                        <div class="text-muted small" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($location); ?>">
                            <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($location); ?>
                        </div>
                    </div>

                    <!-- Column C: Badges -->
                    <div class="col-12 col-md-2">
                        <div class="d-flex flex-wrap gap-1">
                            <span class="badge text-bg-<?php echo $statusColor; ?>"><?php echo htmlspecialchars($user['status']); ?></span>
                            <span class="badge text-bg-<?php echo $planColor; ?>"><?php echo htmlspecialchars($user['account_plan'] ?: 'free'); ?></span>
                            <?php if ($isStaff): ?>
                                <span class="badge text-bg-danger">staff</span>
                            <?php endif; ?>
                            <?php if ($isAdmin): ?>
                                <span class="badge text-bg-danger">admin</span>
                            <?php endif; ?>
                            <?php if ($isVerified): ?>
                                <i class="bi bi-patch-check-fill text-primary" style="font-size: 1rem;"></i>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Column D: Dates, enrollment stats, and action button -->
                    <div class="col-12 col-md-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex flex-column gap-0" style="min-width: 200px;">
                                <div class="d-flex gap-3 mb-1">
                                    <div class="text-center" style="min-width: 55px;">
                                        <div class="user-stat-value"><?php echo date('M d', strtotime($user['create_dt'])); ?></div>
                                    </div>
                                    <div class="text-center" style="min-width: 55px;">
                                        <div class="user-stat-value"><?php echo $user['birthdate'] ? date('M d', strtotime($user['birthdate'])) : '-'; ?></div>
                                    </div>
                                    <?php
                                    $pending_enrollments = $user['pending_enrollments'] ?? 0;
                                    $success_enrollments = $user['success_enrollments'] ?? 0;
                                    $total_enrollments = $user['total_enrollments'] ?? 0;
                                    $user_status = $user['status'];
                                    $show_enrollments = ($user_status !== 'pending');

                                    // Determine what to display for enrollments
                                    $enrollment_display = '';
                                    if ($show_enrollments) {
                                        if ($pending_enrollments == 0 && $success_enrollments == 0 && $total_enrollments == 0) {
                                            // All zeros: show dash
                                            $enrollment_display = '-';
                                        } else {
                                            // Has enrollments: show counts
                                            $enrollment_display = '<span class="text-warning">' . $pending_enrollments . '</span> / ' .
                                                                '<span class="text-success">' . $success_enrollments . '</span> / ' .
                                                                '<span class="text-primary">' . $total_enrollments . '</span>';
                                        }
                                    }
                                    ?>
                                    <?php if ($show_enrollments): ?>
                                    <div class="text-center" style="min-width: 65px;">
                                        <div class="user-stat-value" style="font-size: 0.7rem;">
                                            <?php echo $enrollment_display; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-3">
                                    <div class="user-stat-label small text-muted text-center" style="min-width: 55px;">JOINED</div>
                                    <div class="user-stat-label small text-muted text-center" style="min-width: 55px;">BIRTHDAY</div>
                                    <?php if ($show_enrollments): ?>
                                    <div class="user-stat-label small text-muted text-center" style="min-width: 65px;">ENROLLMENTS</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="/admin/user-details?u=<?php echo $qik->encodeId($user['user_id']); ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            }
        }
        ?>
    </div>
    
    <?php if (!empty($searchTerm)): ?>
    <script>
    // Update search result count
    document.addEventListener('DOMContentLoaded', function() {
        const countElement = document.getElementById('searchResultCount');
        if (countElement) {
            countElement.textContent = '(<?php echo $searchResultCount; ?> results found)';
        }
    });
    </script>
    <?php endif; ?>
    
    <?php if ($searchResultCount === 0 && (!empty($searchTerm) || !empty($_REQUEST['statusFilter']))): ?>
    <!-- No Results Message -->
    <div class="no-results">
        <i class="bi bi-search"></i>
        <h4>No users found</h4>
        <p>Try adjusting your search or filters</p>
    </div>
    <?php else: ?>
    
    <!-- Loading Indicator -->
    <div class="loading-spinner" id="loadingSpinner" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Loading users...</p>
    </div>
    
    <!-- No Results Message -->
    <div class="no-results" id="noResults" style="display: none;">
        <i class="bi bi-search"></i>
        <h4>No users found</h4>
        <p>Try adjusting your search or filters</p>
    </div>
    <?php endif; ?>
    
            <!-- Load More Button -->
            <div class="load-more-container" id="loadMoreContainer" style="display: none;">
                <button class="btn btn-primary load-more-btn" id="loadMoreBtn">
                    Load More Users
                </button>
            </div>
        </div>
    </div>

<!-- Back to Admin Button -->
<div class="back-button">
    <a href="/admin/" class="btn btn-light shadow-sm border border-secondary border-2">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
</div>

<!-- User Item Template -->
<template id="userItemTemplate">
    <div class="card p-2 px-4 mb-1 user-item" data-user-id="">
        <div class="row align-items-center g-3">
            <!-- Column A: Avatar, name, email, username -->
            <div class="col-12 col-md-4">
                <div class="d-flex align-items-start gap-3">
                    <img src="" alt="" class="rounded-circle user-avatar" style="width: 48px; height: 48px; object-fit: cover;">
                    <div class="flex-grow-1">
                        <div class="fw-semibold user-name"></div>
                        <div class="small text-muted">
                            <div>
                                <span class="age-info"></span>
                                <i class="bi bi-envelope me-1"></i><span class="email"></span>
                                <i class="bi bi-person ms-2 me-1"></i><span class="username"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column B: Location -->
            <div class="col-12 col-md-2">
                <div class="text-muted small location-container" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <i class="bi bi-geo-alt me-1"></i><span class="location"></span>
                </div>
            </div>

            <!-- Column C: Badges -->
            <div class="col-12 col-md-2">
                <div class="d-flex flex-wrap gap-1 user-badges"></div>
            </div>

            <!-- Column D: Dates, enrollment stats, and action button -->
            <div class="col-12 col-md-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-column gap-0" style="min-width: 200px;">
                        <div class="d-flex gap-3 mb-1">
                            <div class="text-center" style="min-width: 55px;">
                                <div class="user-stat-value joined-date"></div>
                            </div>
                            <div class="text-center" style="min-width: 55px;">
                                <div class="user-stat-value birthday-date"></div>
                            </div>
                            <div class="text-center enrollments-container" style="min-width: 65px;">
                                <div class="user-stat-value enrollments-stats" style="font-size: 0.7rem;"></div>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <div class="user-stat-label small text-muted text-center" style="min-width: 55px;">JOINED</div>
                            <div class="user-stat-label small text-muted text-center" style="min-width: 55px;">BIRTHDAY</div>
                            <div class="user-stat-label small text-muted text-center enrollments-label" style="min-width: 65px;">ENROLLMENTS</div>
                        </div>
                    </div>
                    <a href="" class="btn btn-primary btn-sm view-details-btn">
                        <i class="bi bi-eye"></i> View Details
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Skeleton Card Template -->
<template id="skeletonCardTemplate">
    <div class="skeleton-card">
        <div class="skeleton-header">
            <div class="skeleton-avatar"></div>
            <div class="skeleton-info">
                <div class="skeleton-line"></div>
                <div class="skeleton-line short"></div>
            </div>
        </div>
        <div class="skeleton-line"></div>
        <div class="skeleton-line short"></div>
    </div>
</template>

<script>
// User Management System with Lazy Loading
class UserManager {
    constructor() {
        this.users = [];
        this.filteredUsers = [];
        this.currentOffset = 0;
        this.batchSize = <?php echo $initialLoadCount; ?>;
        this.loadMoreSize = <?php echo $loadMoreCount; ?>;
        this.isLoading = false;
        this.hasMore = true;
        this.searchTimeout = null;
        this.filters = {
            search: '<?php echo addslashes($searchTerm); ?>',
            status: '',
            plan: '',
            type: '',
            days: '180'
        };
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        // Check if we already have some users loaded (fallback PHP)
        const existingItems = document.querySelectorAll('.user-item').length;
        if (existingItems > 0) {
            // We have fallback users, just set up for load more
            this.currentOffset = existingItems;
            this.showLoadMore();
        } else {
            // No fallback users, load via AJAX
            this.loadInitialUsers();
        }
    }
    
    bindEvents() {
        // Search input - disabled for form submission
        // Search is now handled by form POST submission

        // Auto-change day filter to "all" when user starts typing in search
        const searchInput = document.getElementById('searchInput');
        const dayFilter = document.getElementById('dayFilter');

        if (searchInput && dayFilter) {
            let hasAutoChangedToAll = false;

            searchInput.addEventListener('input', (e) => {
                // If user types something and dayFilter is not already "all"
                if (e.target.value.trim() !== '' && dayFilter.value !== 'all' && !hasAutoChangedToAll) {
                    dayFilter.value = 'all';
                    hasAutoChangedToAll = true;
                    // Trigger change event to update filter
                    dayFilter.dispatchEvent(new Event('change'));

                    // Show a subtle notification to user
                    const notification = document.createElement('div');
                    notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
                    notification.style.cssText = 'top: 80px; right: 20px; z-index: 1050; max-width: 300px;';
                    notification.innerHTML = `
                        <i class="bi bi-info-circle me-2"></i>
                        Time filter changed to "All Time" for complete search results
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(notification);

                    // Auto-dismiss after 3 seconds
                    setTimeout(() => {
                        notification.classList.remove('show');
                        setTimeout(() => notification.remove(), 150);
                    }, 3000);
                }

                // Reset the flag when search is cleared
                if (e.target.value.trim() === '') {
                    hasAutoChangedToAll = false;
                }
            });
        }

        // Filter changes
        document.getElementById('statusFilter').addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.resetAndReload();
        });
        
        document.getElementById('planFilter').addEventListener('change', (e) => {
            this.filters.plan = e.target.value;
            this.resetAndReload();
        });
        
        document.getElementById('typeFilter').addEventListener('change', (e) => {
            this.filters.type = e.target.value;
            this.resetAndReload();
        });
        
        document.getElementById('dayFilter').addEventListener('change', (e) => {
            this.filters.days = e.target.value;
            this.resetAndReload();
        });
        
        // Load more button
        document.getElementById('loadMoreBtn').addEventListener('click', () => {
            this.loadMoreUsers();
        });
        
        // Infinite scroll
        window.addEventListener('scroll', () => {
            if (this.shouldLoadMore()) {
                this.loadMoreUsers();
            }
        });
    }
    
    shouldLoadMore() {
        if (this.isLoading || !this.hasMore) return false;
        
        const scrollPosition = window.scrollY + window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        
        return scrollPosition > documentHeight - 500;
    }
    
    resetAndReload() {
        this.currentOffset = 0;
        this.hasMore = true;
        this.users = [];
        document.getElementById('userList').innerHTML = '';
        this.loadInitialUsers();
    }
    
    async loadInitialUsers() {
        this.showLoading();
        this.hideNoResults();
        
        try {
            const users = await this.fetchUsers(0, this.batchSize);
            this.users = users;
            this.renderUsers(users, true);
            
            if (users.length < this.batchSize) {
                this.hasMore = false;
                this.hideLoadMore();
            } else {
                this.showLoadMore();
            }
            
            this.currentOffset = this.batchSize;
        } catch (error) {
            console.error('Error loading users:', error);
        } finally {
            this.hideLoading();
        }
    }
    
    async loadMoreUsers() {
        if (this.isLoading || !this.hasMore) return;
        
        this.isLoading = true;
        this.showLoadingMore();
        
        try {
            const users = await this.fetchUsers(this.currentOffset, this.loadMoreSize);
            this.users = this.users.concat(users);
            this.renderUsers(users, false);
            
            if (users.length < this.loadMoreSize) {
                this.hasMore = false;
                this.hideLoadMore();
            }
            
            this.currentOffset += this.loadMoreSize;
        } catch (error) {
            console.error('Error loading more users:', error);
        } finally {
            this.isLoading = false;
            this.hideLoadingMore();
        }
    }
    
    async fetchUsers(offset, limit) {
        const params = new URLSearchParams({
            offset: offset,
            limit: limit,
            ...this.filters
        });
        
        try {
            const response = await fetch(`/api/admin/users?${params}`, {
                headers: {
                    'X-Claude-Code-Key': '<?php echo $sitesettings['app']['CLAUDE_CODE_AUTH_KEY'] ?? ''; ?>'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to fetch users');
            }
            
            const data = await response.json();
            return data.users || [];
        } catch (error) {
            console.error('API error, falling back to empty result:', error);
            // Return empty array to prevent breaking the UI
            return [];
        }
    }
    
    renderUsers(users, clear = false) {
        const list = document.getElementById('userList');
        
        if (clear) {
            list.innerHTML = '';
        }
        
        if (users.length === 0 && this.users.length === 0) {
            this.showNoResults();
            return;
        }
        
        const template = document.getElementById('userItemTemplate');
        
        users.forEach(user => {
            const item = template.content.cloneNode(true);
            
            // Set user data
            const userItem = item.querySelector('.user-item');
            userItem.dataset.userId = user.user_id;
            
            // Check if test user and apply styling
            const isTestUser = (user.username.toLowerCase().includes('test') || 
                               user.email.toLowerCase().includes('test') ||
                               user.first_name.toLowerCase().includes('test') ||
                               user.last_name.toLowerCase().includes('test'));
            
            // Check if parental/minor account
            const isParentalUser = (user.account_type === 'parental');
            
            if (isTestUser) {
                userItem.classList.add('test-user');
            }
            if (isParentalUser) {
                userItem.classList.add('parental-user');
            }
            item.querySelector('.user-avatar').src = user.avatar || '/public/avatars/problemavatar.png';
            item.querySelector('.user-name').textContent = `${user.first_name} ${user.last_name}`;
            item.querySelector('.username').textContent = `@${user.username}`;
            item.querySelector('.email').textContent = user.email;

            // Age info with cake icon
            const ageInfo = item.querySelector('.age-info');
            if (user.age) {
                const icon = document.createElement('i');
                icon.className = 'bi bi-cake me-1';
                ageInfo.appendChild(icon);

                const text = document.createTextNode(user.age);
                ageInfo.appendChild(text);

                // Add spacing for email icon
                const emailIcon = ageInfo.nextElementSibling;
                if (emailIcon && emailIcon.classList.contains('bi-envelope')) {
                    emailIcon.classList.add('ms-2');
                }
            }

            // Location
            const location = [user.city, user.state].filter(Boolean).join(', ') || 'Unknown';
            const locationContainer = item.querySelector('.location-container');
            locationContainer.setAttribute('title', location);
            item.querySelector('.location').textContent = location;

            // Dates
            item.querySelector('.joined-date').textContent = this.formatShortDate(user.create_dt);
            item.querySelector('.birthday-date').textContent = user.birthdate ? this.formatShortDate(user.birthdate) : '-';

            // Enrollments
            const enrollmentsContainer = item.querySelector('.enrollments-container');
            const enrollmentsLabel = item.querySelector('.enrollments-label');
            const enrollmentsStats = item.querySelector('.enrollments-stats');
            const pendingCount = user.pending_enrollments || 0;
            const successCount = user.success_enrollments || 0;
            const totalEnrollments = user.total_enrollments || 0;

            // Show/hide enrollment column based on user status
            if (user.status === 'pending') {
                // Pending users: hide enrollment column entirely
                enrollmentsContainer.style.display = 'none';
                enrollmentsLabel.style.display = 'none';
            } else {
                // Non-pending users: show enrollment column
                enrollmentsContainer.style.display = 'block';
                enrollmentsLabel.style.display = 'block';

                // Determine what to display
                let enrollmentDisplay = '';
                if (pendingCount === 0 && successCount === 0 && totalEnrollments === 0) {
                    // All zeros: show dash
                    enrollmentDisplay = '-';
                } else {
                    // Has enrollments: show counts
                    enrollmentDisplay = `
                        <span class="text-warning">${pendingCount}</span> /
                        <span class="text-success">${successCount}</span> /
                        <span class="text-primary">${totalEnrollments}</span>
                    `;
                }
                enrollmentsStats.innerHTML = enrollmentDisplay;
            }

            // Badges
            const badgesContainer = item.querySelector('.user-badges');
            
            // Status badge
            const statusBadge = this.createBadge(user.status, this.getStatusColor(user.status));
            badgesContainer.appendChild(statusBadge);
            
            // Plan badge
            const planBadge = this.createBadge(user.account_plan || 'free', this.getPlanColor(user.account_plan));
            badgesContainer.appendChild(planBadge);
            
            // Staff/Admin badges
            if (user.is_staff) {
                const staffBadge = this.createBadge('staff', 'danger');
                badgesContainer.appendChild(staffBadge);
            }
            
            if (user.is_admin) {
                const adminBadge = this.createBadge('admin', 'danger');
                badgesContainer.appendChild(adminBadge);
            }
            
            // Verified badge
            if (user.is_verified) {
                const verifiedIcon = document.createElement('i');
                verifiedIcon.className = 'bi bi-patch-check-fill text-primary';
                verifiedIcon.style.fontSize = '1rem';
                badgesContainer.appendChild(verifiedIcon);
            }
            
            // Actions
            const detailsBtn = item.querySelector('.view-details-btn');
            detailsBtn.href = `/admin/user-details?u=${this.encodeId(user.user_id)}`;
            
            list.appendChild(item);
        });
    }
    
    createBadge(text, colorClass) {
        const badge = document.createElement('span');
        badge.className = `badge text-bg-${colorClass}`;
        badge.textContent = text;
        return badge;
    }
    
    getStatusColor(status) {
        const colors = {
            'active': 'success',
            'pending': 'warning',
            'suspended': 'danger',
            'validated': 'info'
        };
        return colors[status] || 'secondary';
    }
    
    getPlanColor(plan) {
        const colors = {
            'free': 'secondary',
            'basic': 'primary',
            'premium': 'warning',
            'vip': 'danger'
        };
        return colors[plan] || 'secondary';
    }
    
    getTypeColor(type) {
        const colors = {
            'individual': 'primary',
            'business': 'success',
            'parental': 'info'
        };
        return colors[type] || 'secondary';
    }
    
    formatDate(dateString) {
        if (!dateString) return 'Unknown';
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays < 1) {
            return 'Today';
        } else if (diffDays < 2) {
            return 'Yesterday';
        } else if (diffDays < 7) {
            return `${diffDays} days ago`;
        } else {
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }
    
    formatShortDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
    
    encodeId(id) {
        // For now, just return the plain ID since admin users have access
        // The PHP decodeId function will handle plain numeric IDs
        return id;
    }
    
    showLoading() {
        document.getElementById('loadingSpinner').style.display = 'block';
        this.hideLoadMore();
    }
    
    hideLoading() {
        document.getElementById('loadingSpinner').style.display = 'none';
    }
    
    showLoadingMore() {
        const btn = document.getElementById('loadMoreBtn');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
        btn.disabled = true;
    }
    
    hideLoadingMore() {
        const btn = document.getElementById('loadMoreBtn');
        btn.innerHTML = 'Load More Users';
        btn.disabled = false;
    }
    
    showLoadMore() {
        document.getElementById('loadMoreContainer').style.display = 'block';
    }
    
    hideLoadMore() {
        document.getElementById('loadMoreContainer').style.display = 'none';
    }
    
    showNoResults() {
        document.getElementById('noResults').style.display = 'block';
        this.hideLoadMore();
    }
    
    hideNoResults() {
        document.getElementById('noResults').style.display = 'none';
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize user manager
    window.userManager = new UserManager();
});
</script>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>