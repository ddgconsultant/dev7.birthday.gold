<?php
// Marketing tab navigation component
// Usage: include('nav.inc.php'); before main content

// Determine current page for active tab
$current_page = basename($_SERVER['PHP_SELF'], '.php');

$nav_tabs = [
    'index' => ['Dashboard', '/myaccount/marketing/', 'bi bi-speedometer2'],
    'platforms' => ['Platforms', '/myaccount/marketing/platforms.php', 'bi bi-link'],
    'campaigns' => ['Campaigns', '/myaccount/marketing/campaigns.php', 'bi bi-megaphone'],
    'calendar' => ['Calendar', '/myaccount/marketing/calendar.php', 'bi bi-calendar'],
    'reports' => ['Reports', '/myaccount/marketing/reports.php', 'bi bi-bar-chart']
];

echo '
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <nav class="nav-tabs-modern">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div class="d-flex">';

foreach ($nav_tabs as $page => $tab_info) {
    $is_active = ($current_page == $page) ? ' active' : '';
    echo '
                        <a href="' . $tab_info[1] . '" class="nav-tab-item' . $is_active . '">
                            <i class="' . $tab_info[2] . ' me-2"></i>' . $tab_info[0] . '
                        </a>';
}

echo '
                    </div>
                    <div>
                        <a href="/myaccount/marketing/settings.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-gear"></i>
                        </a>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</div>';

// Add tab navigation CSS
$additionalstyles .= '
<style>
.nav-tabs-modern {
    background: white;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
    padding: 0 1rem;
    border-radius: 8px 8px 0 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.nav-tab-item {
    flex: 0 0 auto;
    padding: 1rem 2rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
}

.nav-tab-item:hover {
    color: #0d6efd;
    text-decoration: none;
    background-color: #f8f9fa;
}

.nav-tab-item.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
    background-color: #f8f9fa;
}

.nav-tab-item i {
    font-size: 1rem;
}
</style>
';
?>