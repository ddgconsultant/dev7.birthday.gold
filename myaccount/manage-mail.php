<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');
$additionalstyles .= '<style>
    .avatar-img {
        width: 60px;
        height: 60px;
        margin-right: 15px;
    }

    .primary-switch:checked {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }

    .primary-switch:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); /* Adjust the shadow color if needed */
    }
</style>';

?>


<div class="container main-content mt-0 pt-0">
    <div class="row justify-content-center">
        <div class="col-12">
            <h2 class="h3 mb-4 page-title">Inbox Settings</h2>
            <div class="my-4">
                <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                      <li class="nav-item" role="presentation">
                        <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false">Mail History</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="true">Settings</button>
                    </li>
              
                </ul>




                <!-- Tab panes -->
                <div class="tab-content" id="myTabContent">


                <!-- Notification History -->
                <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                    <?php include($dir['core_components'] . '/user_notifications.inc'); ?>
                </div>


                 <!-- Notification Settings -->
                 <div class="tab-pane fade show active" id="settings" role="tabpanel" aria-labelledby="settings-tab">
                 <?php include($dir['core_components'] . '/user_mail_settings.inc'); ?>
                </div>


            </div>
        </div>
    </div>
</div>
</div>
</div>
</div>
</div>

<!-- script to handle tab switching and URL hash -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Get the hash from the URL
        var hash = window.location.hash;
        
        // If there is a hash, and it matches a tab ID, show that tab
        if (hash) {
            var tabButton = document.querySelector('button[data-bs-target="' + hash + '"]');
            if (tabButton) {
                var tab = new bootstrap.Tab(tabButton);
                tab.show();
            }
        } else {
            // If no hash is present, activate the first tab by default
            var firstTabButton = document.querySelector('#myTab .nav-link:first-child');
            if (firstTabButton) {
                var firstTab = new bootstrap.Tab(firstTabButton);
                firstTab.show();
            }
        }

        // Update the URL hash when a tab is shown
        var tabLinks = document.querySelectorAll('#myTab button[data-bs-toggle="tab"]');
        tabLinks.forEach(function (button) {
            button.addEventListener('shown.bs.tab', function (event) {
                window.history.replaceState(null, null, event.target.getAttribute('data-bs-target'));
            });
        });
    });
</script>
<?php

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
