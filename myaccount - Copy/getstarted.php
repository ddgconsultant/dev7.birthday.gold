<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
// Get user's first name for personalization
$user_firstname = $current_user_data['first_name'] ?? 'Friend';

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    // No form handling needed for this welcome page
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

include($dir['core_components'] . '/bg_user_profileheader.inc');

$additionalstyles .= '
<style>
.step-card {
    transition: transform 0.2s;
    cursor: pointer;
}
.step-card:hover {
    transform: translateY(-5px);
}
.step-number {
    width: 40px;
    height: 40px;
    background-color: #ffc107;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.25rem;
    margin-right: 1rem;
}
.welcome-icon {
    font-size: 2.5rem;
    color: #ffc107;
}
</style>
';

echo '    
<div class="container main-content mt-0 pt-0">
    <div class="text-center my-5">
        <i class="bi bi-emoji-smile-fill welcome-icon mb-3"></i>
        <h1 class="display-4">Welcome to Birthday.Gold, ' . htmlspecialchars($user_firstname) . '!</h1>
        <p class="lead text-muted">Get ready to enjoy amazing rewards from your favorite businesses.</p>
    </div>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card h-100 step-card p-3">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="step-number">1</div>
                    <h3 class="card-title mb-0">Complete Your Profile</h3>
                </div>
                <p class="card-text">Provide the enrollment information needed to get you in reward programs. This one-time setup ensures smooth registration with any '.$website['bizname'].' you pick.</p>
            </div>
        </div>
    </div>

        <div class="col-md-4">
            <div class="card h-100 step-card p-3">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number">2</div>
                        <h3 class="card-title mb-0">Pick Your Businesses</h3>
                    </div>
                    <p class="card-text">Browse our list of '.$website['biznames'].' and pick your favorites. We\'ll handle the reward program enrollments for you automatically.</p>
                   
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 step-card p-3">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number">3</div>
                        <h3 class="card-title mb-0">Enjoy Your Rewards</h3>
                    </div>
                    <p class="card-text">Sit back and relax! We\'ll notify you when you have rewards available and keep track of all your benefits in one place.</p>
                   
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mb-5">
        <a class="btn btn-primary btn-lg" href="/myaccount/profile">Get Started <i class="bi bi-arrow-right-circle-fill ms-2"></i>
        </a>
    </div>
</div>';

$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();