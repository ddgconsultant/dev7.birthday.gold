<?php
// Marketing Navigation Component
$current_page = basename($_SERVER['PHP_SELF']);

echo '
<style>
/* Marketing navigation tabs */
.marketing-nav-tabs {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
    gap: 0;
    position: relative;
    flex-wrap: wrap;
}

.marketing-nav-item {
    flex: 0 0 auto;
    min-width: 140px;
    padding: 1rem 1.5rem;
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

.marketing-nav-item:hover {
    color: #495057;
    text-decoration: none;
    background: #f8f9fa;
}

.marketing-nav-item.active {
    color: #3498db;
    border-bottom-color: #3498db !important;
    background: none;
}

.marketing-nav-item.ms-auto {
    margin-left: auto;
}

.marketing-nav-item i {
    margin-right: 0.5rem;
}

@media (max-width: 768px) {
    .marketing-nav-item {
        min-width: 120px;
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }
}
</style>';

echo '
<div class="container mt-3">
    <nav class="marketing-nav-tabs">';

// Campaigns tab
$isActive = ($current_page == 'marketing-campaigns.php') ? 'active' : '';
echo '
        <a href="/staff/marketing-campaigns.php" class="marketing-nav-item ' . $isActive . '">
            <i class="fas fa-bullhorn"></i>Campaigns
        </a>';

// Create New tab
$isActive = ($current_page == 'marketing-edit.php' && !isset($_GET['id'])) ? 'active' : '';
echo '
        <a href="/staff/marketing-edit.php" class="marketing-nav-item ' . $isActive . '">
            <i class="fas fa-plus-circle"></i>Create New
        </a>';

// Edit Campaign tab (only show when editing)
if ($current_page == 'marketing-edit.php' && isset($_GET['id'])) {
    echo '
        <a href="#" class="marketing-nav-item active">
            <i class="fas fa-edit"></i>Edit Campaign
        </a>';
}

// View Campaign tab (only show when viewing)
if ($current_page == 'marketing-view.php') {
    echo '
        <a href="#" class="marketing-nav-item active">
            <i class="fas fa-eye"></i>View Campaign
        </a>';
}

// Analytics tab
$isActive = ($current_page == 'marketing-analytics.php') ? 'active' : '';
echo '
        <a href="/staff/marketing-analytics.php" class="marketing-nav-item ' . $isActive . '">
            <i class="fas fa-chart-line"></i>Analytics
        </a>';

// Platforms tab
$isActive = ($current_page == 'marketing-platforms.php') ? 'active' : '';
echo '
        <a href="/staff/marketing-platforms.php" class="marketing-nav-item ' . $isActive . '">
            <i class="fas fa-link"></i>Platforms
        </a>';

// Calendar tab
$isActive = ($current_page == 'marketing-calendar.php') ? 'active' : '';
echo '
        <a href="/staff/marketing-calendar.php" class="marketing-nav-item ' . $isActive . '">
            <i class="fas fa-calendar-alt"></i>Calendar
        </a>';

// Staff Dashboard link (right-aligned)
echo '
        <a href="/staff/" class="marketing-nav-item ms-auto">
            <i class="fas fa-home"></i>Dashboard
        </a>';

echo '
    </nav>
</div>';
?>