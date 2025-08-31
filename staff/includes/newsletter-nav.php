<?PHP
// Newsletter Navigation Component with Modern Tab Design
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Add the modern tab styles
echo '
<style>
/* Modern tab navigation matching loginhistory style */
.nav-tabs-modern {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
    gap: 0;
    overflow: hidden;
    position: relative;
}

.nav-tab-item {
    flex: 0 0 auto;
    min-width: 150px;
    padding: 1rem 2rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
    background: none;
    border-radius: 0;
    position: relative;
    white-space: nowrap;
    text-align: center;
}

.nav-tab-item:hover {
    color: #495057;
    text-decoration: none;
    background: #f8f9fa;
}

.nav-tab-item.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd !important;
    background: none;
}

.nav-tab-item.ms-auto {
    margin-left: auto;
}

/* Icon spacing */
.nav-tab-item i {
    margin-right: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .nav-tabs-modern {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .nav-tab-item {
        min-width: 120px;
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }
}
</style>';

echo '
<div class="container mt-3">
    <nav class="nav-tabs-modern">';

// Campaigns tab
$isActive = ($current_page == 'newsletter-list.php') ? 'active' : '';
echo '
        <a href="/staff/marketing/newsletter-list.php" class="nav-tab-item ' . $isActive . '">
            <i class="fas fa-list"></i>Campaigns
        </a>';

// Create New tab
$isActive = ($current_page == 'newsletter-edit.php' && !isset($_GET['id'])) ? 'active' : '';
echo '
        <a href="/staff/marketing/newsletter-edit.php" class="nav-tab-item ' . $isActive . '">
            <i class="fas fa-plus-circle"></i>Create New
        </a>';

// Edit Campaign tab (only show when editing)
if ($current_page == 'newsletter-edit.php' && isset($_GET['id'])) {
    echo '
        <a href="#" class="nav-tab-item active">
            <i class="fas fa-edit"></i>Edit Campaign
        </a>';
}

// Campaign Report tab (only show when viewing report)
if ($current_page == 'newsletter-reports.php') {
    echo '
        <a href="#" class="nav-tab-item active">
            <i class="fas fa-chart-bar"></i>Campaign Report
        </a>';
}

// Staff Dashboard link (right-aligned)
echo '
        <a href="/staff/" class="nav-tab-item ms-auto">
            <i class="fas fa-home"></i>Staff Dashboard
        </a>';

echo '
    </nav>
</div>';
?>