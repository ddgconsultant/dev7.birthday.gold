<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$bodycontentclass='';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');
?>

<div class="container mt-4">
    <h2 class="mb-4">Notifications</h2>
    
    <!-- Nav tabs -->
    <ul class="nav nav-tabs" id="notificationTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="unread-tab" data-bs-toggle="tab" data-bs-target="#unread" type="button" role="tab">
                Unread <span class="badge bg-danger ms-1">3</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="read-tab" data-bs-toggle="tab" data-bs-target="#read" type="button" role="tab">
                Read
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                All
            </button>
        </li>
        <li class="nav-item ms-auto" role="presentation">
            <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab">
                <i class="bi bi-gear-fill"></i>
            </button>
        </li>
    </ul>

    <!-- Tab content -->
    <div class="tab-content" id="notificationTabContent">
        <div class="tab-pane fade show active" id="unread" role="tabpanel">
            <div class="p-4">
                <?php include($dir['core_components'] . '/user_notifications_clean.inc'); ?>
            </div>
        </div>
        <div class="tab-pane fade" id="read" role="tabpanel">
            <div class="p-4">
                <p>Read notifications will go here</p>
            </div>
        </div>
        <div class="tab-pane fade" id="all" role="tabpanel">
            <div class="p-4">
                <p>All notifications will go here</p>
            </div>
        </div>
        <div class="tab-pane fade" id="settings" role="tabpanel">
            <div class="p-4">
                <?php include($dir['core_components'] . '/user_notification_settings.inc'); ?>
            </div>
        </div>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>