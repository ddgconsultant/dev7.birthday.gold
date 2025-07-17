<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Notifications";

// Add custom styles for notifications
$additionalstyles = '
<style>
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
        margin-bottom: 2rem;
    }
    
    .nav-tab-clean {
        position: relative;
        margin-right: 3rem; /* Increased from 2rem */
    }
    
    .nav-tab-clean a {
        display: block;
        padding: 1rem 1.5rem; /* Added horizontal padding */
        text-decoration: none;
        color: #666;
        font-weight: 500;
        font-size: 16px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: color 0.2s ease;
        border: none;
        background: none;
    }
    
    .nav-tab-clean a:hover {
        color: #333;
    }
    
    .nav-tab-clean.active a {
        color: #0d6efd; /* Bootstrap primary blue */
    }
    
    .nav-tab-clean.active::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 2px;
        background-color: #0d6efd; /* Bootstrap primary blue */
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
    
    .notification-item {
        padding: 1rem;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: start;
        gap: 1rem;
        transition: background-color 0.2s;
    }
    
    .notification-item:hover {
        background-color: #f8f9fa;
    }
    
    .notification-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .notification-content {
        flex: 1;
    }
    
    .notification-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .notification-text {
        color: #666;
        font-size: 14px;
        margin: 0;
    }
    
    .notification-time {
        color: #999;
        font-size: 12px;
    }
    
    .tab-content {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
        </style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-bell me-3"></i>Notifications</h1>
            <p class="lead mb-0">Stay updated with your account activity and important updates</p>
        </div>
    </div>
</div>

<?php
/*
  <div class="p-5 text-center text-muted">
                <i class="bi bi-bell-slash" style="font-size: 3rem;"></i>
                <p class="mt-3">All notifications will appear here</p>
            </div>
            */


$localcontent = array();
$notification_counts = array();
$nonotificationoutput = true;

// Collect notifications for each filter
$filters = array('unread', 'read', 'all');
foreach ($filters as $filter) {
    $_GET['notification_filter'] = $filter;
    include($dir['core_components'] . '/user_notifications_display.inc');
    $localcontent[$filter] = $notifications_output;
    $notification_counts[$filter] = $notifications_count;
}
?>

<div class="container my-5 pt-5">
    <div class="container">

        <!-- Clean tab navigation with settings gear -->
        <div class="d-flex justify-content-between align-items-center tabs-container">
            <ul class="nav-tabs-clean mb-0">
                <?php
                $tab_labels = array(
                    'unread' => '<i class="bi bi-envelope-exclamation me-2"></i>Unread', 
                    'read' => '<i class="bi bi-envelope-open me-2"></i>Read', 
                    'all' => '<i class="bi bi-envelope me-2"></i>All'
                );
                $is_first = true;
                foreach ($tab_labels as $key => $label) {
                    $active_class = $is_first ? ' active' : '';
                    $badge = '';
                    if ($key === 'unread' && $notification_counts[$key] > 0) {
                        $badge = '<span class="tab-badge">' . $notification_counts[$key] . '</span>';
                    }
                    echo '
                <li class="nav-tab-clean' . $active_class . '">
                    <a href="#' . $key . '" data-bs-toggle="tab">
                        ' . $label . '
                        ' . $badge . '
                    </a>
                </li>';
                    $is_first = false;
                }
                ?>
            </ul>
            <ul class="nav-tabs-clean mb-0">
                <li class="nav-tab-clean">
                    <a href="#settings" id="settingsButton">
                        <i class="bi bi-gear-fill"></i>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Tab content -->
        <div class="tab-content">
            <?php
            $is_first = true;
            foreach ($localcontent as $key => $value) {
                $active_classes = $is_first ? ' show active' : '';
                echo '
            <div class="tab-pane fade' . $active_classes . '" id="' . $key . '">
                ' . $value . '
            </div>';
                $is_first = false;
            }
            ?>
            
            <div class="tab-pane fade" id="settings">
                <div class="card px-4 mt-0 pt-0">
                    <?php include($dir['core_components'] . '/user_notification_settings.inc'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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

    // Check URL hash on page load
    document.addEventListener('DOMContentLoaded', function() {
        if (window.location.hash) {
            activateTab(window.location.hash);
        }
    });

    // Handle tab switching
    document.querySelectorAll('.nav-tab-clean a').forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            var target = this.getAttribute('href');
            activateTab(target);
            
            // Update URL hash without scrolling
            history.pushState(null, null, target);
        });
    });

    // Handle settings button click
    document.getElementById('settingsButton').addEventListener('click', function(e) {
        e.preventDefault();
        activateTab('#settings');
        
        // Update URL hash without scrolling
        history.pushState(null, null, '#settings');
    });

    // Handle browser back/forward buttons
    window.addEventListener('hashchange', function() {
        if (window.location.hash) {
            activateTab(window.location.hash);
        }
    });
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');

$app->outputpage();
?>