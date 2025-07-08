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
        margin-right: 2rem;
    }
    
    .nav-tab-clean a {
        display: block;
        padding: 1rem 0;
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
        color: #000;
    }
    
    .nav-tab-clean.active::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 2px;
        background-color: #000;
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

include($dir['core_components'] . '/bg_user_profileheader.inc');

/*
  <div class="p-5 text-center text-muted">
                <i class="bi bi-bell-slash" style="font-size: 3rem;"></i>
                <p class="mt-3">All notifications will appear here</p>
            </div>
            */
?>

<div class="container my-lg-5 pt-4">
<div class="container mt-4">
    <div class="mb-4">
        <h2>Notifications</h2>
    </div>
    
    <!-- Clean tab navigation with settings gear -->
    <div class="d-flex justify-content-between align-items-center tabs-container">
        <ul class="nav-tabs-clean mb-0">
            <li class="nav-tab-clean active">
                <a href="#unread" data-bs-toggle="tab">
                    Unread
                    <span class="tab-badge">3</span>
                </a>
            </li>
            <li class="nav-tab-clean">
                <a href="#read" data-bs-toggle="tab">
                    Read
                </a>
            </li>
            <li class="nav-tab-clean">
                <a href="#all" data-bs-toggle="tab">
                    All
                </a>
            </li>
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
        <div class="tab-pane fade show active" id="unread">
        <?php 
                $_GET['notification_filter'] = 'unread';
                include($dir['core_components'] . '/user_notifications_display.inc'); 
                ?>
        </div>
        
        <div class="tab-pane fade" id="read">
        <?php 
                $_GET['notification_filter'] = 'read';
                include($dir['core_components'] . '/user_notifications_display.inc'); 
                ?>
        </div>
        
        <div class="tab-pane fade" id="all">
        <?php 
                $_GET['notification_filter'] = 'all';
                include($dir['core_components'] . '/user_notifications_display.inc'); 
                ?> 
        
        
      
        </div>
        
        <div class="tab-pane fade" id="settings">
            <div class="p-4">
                <h5 class="mb-4">Notification Settings</h5>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                        <label class="form-check-label" for="emailNotifications">
                            Email Notifications
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="pushNotifications" checked>
                        <label class="form-check-label" for="pushNotifications">
                            Push Notifications
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="smsNotifications">
                        <label class="form-check-label" for="smsNotifications">
                            SMS Notifications
                        </label>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <h6 class="mb-3">Notification Types</h6>
                
                <div class="mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="birthdayReminders" checked>
                        <label class="form-check-label" for="birthdayReminders">
                            Birthday Reward Reminders
                        </label>
                    </div>
                </div>
                
                <div class="mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="accountUpdates" checked>
                        <label class="form-check-label" for="accountUpdates">
                            Account Updates
                        </label>
                    </div>
                </div>
                
                <div class="mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="promotions">
                        <label class="form-check-label" for="promotions">
                            Promotional Offers
                        </label>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button class="btn btn-primary">Save Settings</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Handle tab switching
    document.querySelectorAll('.nav-tab-clean a').forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs
            document.querySelectorAll('.nav-tab-clean').forEach(function(t) {
                t.classList.remove('active');
            });
            
            // Add active class to clicked tab
            this.parentElement.classList.add('active');
            
            // Show corresponding content
            var target = this.getAttribute('href');
            document.querySelectorAll('.tab-pane').forEach(function(pane) {
                pane.classList.remove('show', 'active');
            });
            document.querySelector(target).classList.add('show', 'active');
        });
    });
    
    // Handle settings button click
    document.getElementById('settingsButton').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs
        document.querySelectorAll('.nav-tab-clean').forEach(function(t) {
            t.classList.remove('active');
        });
        
        // Add active class to settings tab
        this.parentElement.classList.add('active');
        
        // Show settings content
        document.querySelectorAll('.tab-pane').forEach(function(pane) {
            pane.classList.remove('show', 'active');
        });
        document.querySelector('#settings').classList.add('show', 'active');
    });
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');

$app->outputpage();
?>