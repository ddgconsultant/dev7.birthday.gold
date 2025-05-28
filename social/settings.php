<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page and header includes
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

include($_SERVER['DOCUMENT_ROOT'] . '/social/components/header-nav.inc');
?>

<div class="container my-5">
    <div class="row">
        <h1>Settings</h1>
        <!-- Left Panel: Navigation -->
        <div class="col-lg-3">
            <div class="list-group">
                <a href="#profile-settings" class="list-group-item list-group-item-action active">Profile Settings</a>
                <a href="#privacy-settings" class="list-group-item list-group-item-action">Privacy & Security</a>
                <a href="#notification-settings" class="list-group-item list-group-item-action">Notifications</a>
                <a href="#display-settings" class="list-group-item list-group-item-action">Display Preferences</a>
                <a href="#content-settings" class="list-group-item list-group-item-action">Content Settings</a>
            </div>
        </div>

        <!-- Right Panel: Social Media Settings -->
        <div class="col-lg-9">
            <!-- Profile Settings -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Profile Settings</h5>
                    <button class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProfile" aria-expanded="false" aria-controls="collapseProfile">
                        <i class="bi bi-caret-down-fill"></i>
                    </button>
                </div>
                <div id="collapseProfile" class="collapse show">
                    <div class="card-body">
                        <form>
                            <div class="mb-3">
                                <label for="profileVisibility" class="form-label">Profile Visibility</label>
                                <select class="form-select" id="profileVisibility">
                                    <option value="public">Public</option>
                                    <option value="friends">Friends Only</option>
                                    <option value="private">Private</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" rows="3" placeholder="Update your bio..."></textarea>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Privacy & Security -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Privacy & Security</h5>
                    <button class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePrivacy" aria-expanded="false" aria-controls="collapsePrivacy">
                        <i class="bi bi-caret-down-fill"></i>
                    </button>
                </div>
                <div id="collapsePrivacy" class="collapse">
                    <div class="card-body">
                        <form>
                            <div class="mb-3">
                                <label for="twoFactorAuth" class="form-label">Two-Factor Authentication</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="twoFactorAuth">
                                    <label class="form-check-label" for="twoFactorAuth">Enable Two-Factor Authentication</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="postVisibility" class="form-label">Post Visibility</label>
                                <select class="form-select" id="postVisibility">
                                    <option value="public">Public</option>
                                    <option value="friends">Friends Only</option>
                                    <option value="private">Private</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Notifications</h5>
                    <button class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNotifications" aria-expanded="false" aria-controls="collapseNotifications">
                        <i class="bi bi-caret-down-fill"></i>
                    </button>
                </div>
                <div id="collapseNotifications" class="collapse">
                    <div class="card-body">
                        <form>
                            <div class="mb-3">
                                <label for="emailNotifications" class="form-label">Email Notifications</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                    <label class="form-check-label" for="emailNotifications">Receive Email Notifications</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="pushNotifications" class="form-label">Push Notifications</label>
                                <select class="form-select" id="pushNotifications">
                                    <option value="every_activity">Every Activity</option>
                                    <option value="hourly">Hourly</option>
                                    <option value="daily">Daily</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Display Preferences -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Display Preferences</h5>
                    <button class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDisplay" aria-expanded="false" aria-controls="collapseDisplay">
                        <i class="bi bi-caret-down-fill"></i>
                    </button>
                </div>
                <div id="collapseDisplay" class="collapse">
                    <div class="card-body">
                        <form>
                            <div class="mb-3">
                                <label for="darkMode" class="form-label">Dark Mode</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="darkMode" checked>
                                    <label class="form-check-label" for="darkMode">Enable Dark Mode</label>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Content Settings -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Content Settings</h5>
                    <button class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContent" aria-expanded="false" aria-controls="collapseContent">
                        <i class="bi bi-caret-down-fill"></i>
                    </button>
                </div>
                <div id="collapseContent" class="collapse">
                    <div class="card-body">
                        <form>
                            <div class="mb-3">
                                <label for="autoplayVideos" class="form-label">Autoplay Videos</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="autoplayVideos" checked>
                                    <label class="form-check-label" for="autoplayVideos">Autoplay Videos</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="showLikes" class="form-label">Show Likes Count</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="showLikes">
                                    <label class="form-check-label" for="showLikes">Show Likes Count on Posts</label>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
// Footer includes
$display_footertype = 'none';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
