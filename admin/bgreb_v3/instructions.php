<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$page_title = "BGREB Chrome Extension Setup Guide";
$section = "admin";

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    // No form actions needed for this page
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '    
<div class="container main-content mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Birthday.Gold Registration Enrollment Bar (BGREB) Chrome Extension Setup Guide</h2>
        <a href="/admin" class="btn btn-sm btn-outline-secondary">Back to Admin Dashboard</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Please follow these steps in order to properly set up the BGREB extension and configure Chrome settings.
                    </div>

                    <h5 class="mt-4 mb-3 fw-bold">Installing BGREB Extension</h5>
                    <ol class="list-group list-group-numbered mb-4">
                        <li class="list-group-item">Download the BGREB extension
                            <div class="small text-muted mt-1">
                                <i class="bi bi-link-45deg"></i> 
                                Visit <a href="/admin/bgreb_v3/enrollment-listv2">birthday.gold/admin/bgreb_v3/enrollment-listv2</a> and click "Download Chrome Extension"
                            </div>
                        </li>
                        <li class="list-group-item">Extract the downloaded ZIP file to a folder on your computer</li>
                        <li class="list-group-item">Open Google Chrome browser</li>
                        <li class="list-group-item">
                            Navigate to Chrome Extensions
                            <div class="small text-muted mt-1">
                                <ol class="ms-3">
                                    <li>Click the three dots menu (⋮) in Chrome</li>
                                    <li>Go to Extensions > Manage Extensions</li>
                                    <li>Click <a href="chrome://settings/content" target="_settings">chrome://settings/content</a> or type it in your address bar</li>
                                </ol>
                            </div>
                        </li>
                        <li class="list-group-item">Enable "Developer mode" using the toggle switch in the top-right corner</li>
                        <li class="list-group-item">Click "Load unpacked" button</li>
                        <li class="list-group-item">Browse to and select the folder where you extracted the BGREB extension</li>
                        <li class="list-group-item">Verify the BGREB icon appears in your Chrome toolbar</li>
                    </ol>

                               <h5 class="mt-5 pt-5 mb-3 fw-bold">Chrome Settings to make your life easier</h5>
                    <div class="list-group mb-4">
                       
                        <div class="list-group-item">
                            <strong>1. Verify Password Settings</strong>
                            <div class="mt-2 ms-3">
                                <ol class="small">
                                    <li>Click <a href="chrome://settings/passwords" target="_settings">chrome://settings/passwords</a> or type it in your address bar</li>
                                    <li>Confirm "Offer to save passwords" is turned OFF</li>
                                    <li>Confirm "Auto Sign-in" is turned OFF</li>
                                    <li>Review "Saved Passwords" list and remove any stored passwords for reward program websites</li>
                                </ol>
                            </div>
                        </div>

                        <div class="list-group-item">
                            <strong>2. Verify Site Permissions</strong>
                            <div class="mt-2 ms-3">
                                <ol class="small">
                                    <li>Type <code>chrome://settings/content</code> in your address bar</li>
                                    <li>Check each of these settings:
                                        <ul class="mt-1">
                                            <li>Location: Should show "Don\'t allow sites to ask"</li>
                                            <li>Camera: Should show "Don\'t allow sites to ask"</li>
                                            <li>Microphone: Should show "Don\'t allow sites to ask"</li>
                                            <li>Notifications: Should show "Don\'t allow sites to ask"</li>
                                            <li>Background Sync: Should show "Don\'t allow sites to ask"</li>
                                        </ul>
                                    </li>
                                    <li>Click each permission and review the "Allowed" list - remove any exceptions</li>
                                </ol>
                            </div>
                        </div>
                    </div>

 <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Note:</strong> These settings should be verified periodically, especially after Chrome updates, to ensure they remain configured correctly.
                    </div>
                    

                </div>
            </div>
        </div>
    </div>
</div>';

$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();