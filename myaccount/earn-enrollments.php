<?php
/**
 * Earn More Enrollments Page
 * Shows users how they can earn more enrollment allocations
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.allocationmanager.php');

// Check if user is active
$activeuser = $account->isactive();
if (empty($activeuser)) {
    header('Location: /login');
    exit;
}

// Get user data
$current_user_data = $session->get('current_user_data');
$current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');
$user_id = $current_user_data['user_id'];

// Initialize AllocationManager
$allocationManager = new AllocationManager($database);

// Get user's current allocation balance
$balance = $allocationManager->getUserBalance($user_id);

// Get referral code
$referralcode = $account->manageReferralCode();

// Page setup
$pagetitle = 'Earn More Enrollments';
$additionalstyles .= '
<style>
/* Method Cards */
.method-section {
    margin-bottom: 3rem;
}

.method-header {
    display: flex;
    align-items: center;
}

/* When header is inside card, no border needed */
.method-card .method-header {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.method-icon {
    width: 48px;
    height: 48px;
    background: #f8f9fa;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-right: 1rem;
}

.method-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.method-card:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

.method-card.featured {
    border-color: #667eea;
    background: linear-gradient(135deg, #f8f9ff 0%, #fff 100%);
}

.method-card.featured::before {
    content: "FEATURED";
    position: absolute;
    top: 0;
    right: 0;
    background: #667eea;
    color: white;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.25rem 1rem;
    border-bottom-left-radius: 12px;
}

.method-card.fastest {
    border-color: #dc3545;
    background: linear-gradient(135deg, #fff5f5 0%, #fff 100%);
}

.method-card.fastest::before {
    content: "FASTEST";
    position: absolute;
    top: 0;
    right: 0;
    background: #dc3545;
    color: white;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.25rem 1rem;
    border-bottom-left-radius: 12px;
}

.method-card.biggest {
    border-color: #0dcaf0;
    background: linear-gradient(135deg, #f0fbff 0%, #fff 100%);
}

.method-card.biggest::before {
    content: "BIGGEST";
    position: absolute;
    top: 0;
    right: 0;
    background: #0dcaf0;
    color: white;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.25rem 1rem;
    border-bottom-left-radius: 12px;
}

.reward-amount {
    font-size: 2rem;
    font-weight: 700;
    color: #28a745;
}

.method-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.method-status.available {
    color: #28a745;
}

.method-status.completed {
    color: #6c757d;
}

.method-status.locked {
    color: #dc3545;
}

/* Quick Stats */
.stats-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    height: 100%;
}

.stat-item {
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #212529;
}

/* Progress Bar */
.allocation-progress {
    margin-top: 1rem;
}

.progress-custom {
    height: 8px;
    background-color: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar-custom {
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    height: 100%;
    transition: width 0.3s ease;
}

/* CTA Sections */
.cta-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    margin: 2rem 0;
}

.cta-section h3 {
    margin-bottom: 1rem;
}

/* Page Content Wrapper */
.page-content-wrapper {
    position: relative;
    overflow: visible;
    padding-bottom: 2rem;
}

/* Responsive */
@media (max-width: 768px) {
    .earn-hero {
        padding: 2rem 0;
    }
    
    .balance-display {
        font-size: 3rem;
    }
    
    .method-card {
        padding: 1rem;
    }
    
    .reward-amount {
        font-size: 1.5rem;
    }
    
    .page-content-wrapper {
        min-height: calc(100vh - 400px) !important;
    }
}
</style>
';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header3.inc');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-12 text-center">
                <h1 class="mb-3"><i class="bi bi-stars me-3"></i>Earn More Enrollments</h1>
                <p class="lead mb-0">Discover ways to increase your enrollment allocations</p>
            </div>
        </div>
    </div>
</div>

<div class="page-content-wrapper" style="min-height: calc(100vh - 300px); margin-bottom: 60px;">
<div class="container" style="margin-top: 3rem; margin-bottom: 2rem;">
    <div class="row">
        <div class="col-lg-8">
            <!-- Recommend Businesses Section -->
            <div class="method-section">
                <div class="method-card featured">
                    <div class="method-header mb-3">
                        <div class="method-icon" style="background: #d4edda;">
                            <i class="bi bi-building-add text-success"></i>
                        </div>
                        <div>
                            <h2 class="h3 mb-0">Recommend Businesses</h2>
                            <p class="text-muted mb-0">Earn rewards by helping us grow our directory</p>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h4 class="h5 mb-2">Submit Business Recommendations</h4>
                            <p class="text-muted mb-3">Know a business that offers birthday rewards? Share it with our community!</p>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div>
                                    <div class="reward-amount text-success">+1</div>
                                    <small class="text-muted">Immediate reward</small>
                                </div>
                                <div class="text-muted">+</div>
                                <div>
                                    <div class="reward-amount text-success">+2</div>
                                    <small class="text-muted">When someone enrolls</small>
                                </div>
                            </div>
                            <a href="/myaccount/recommend-business" class="btn btn-success">
                                <i class="bi bi-plus-circle me-2"></i>Recommend a Business
                            </a>
                        </div>
                    </div>
                </div>
            </div>
<?PHP
/*
            <!-- Quick Actions Section -->
            <div class="method-section">
                <div class="method-header">
                    <div class="method-icon" style="background: #cfe2ff;">
                        <i class="bi bi-lightning-charge-fill text-primary"></i>
                    </div>
                    <div>
                        <h2 class="h3 mb-0">Quick Actions</h2>
                        <p class="text-muted mb-0">Complete these tasks to earn instant rewards</p>
                    </div>
                </div>
                
                <div class="method-card">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-person-circle fs-4 text-primary me-3 mt-1"></i>
                                <div class="flex-grow-1">
                                    <h4 class="h5 mb-1">Complete Your Profile</h4>
                                    <p class="text-muted mb-2">Add your birthday and preferences to unlock personalized recommendations</p>
                                    <div class="method-status available">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Available
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto text-center">
                            <div class="reward-amount">+3</div>
                            <small class="text-muted d-block">enrollments</small>
                            <a href="/myaccount/profile" class="btn btn-sm btn-primary mt-2">Complete Now</a>
                        </div>
                    </div>
                </div>
                
                <div class="method-card">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-envelope-check fs-4 text-primary me-3 mt-1"></i>
                                <div class="flex-grow-1">
                                    <h4 class="h5 mb-1">Verify Your Email</h4>
                                    <p class="text-muted mb-2">Confirm your email address to secure your account</p>
                                    <div class="method-status completed">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Completed
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto text-center">
                            <div class="reward-amount text-muted">+2</div>
                            <small class="text-muted d-block">enrollments</small>
                            <button class="btn btn-sm btn-secondary mt-2" disabled>Earned</button>
                        </div>
                    </div>
                </div>
            </div>
*/
?>
            <!-- Referral Section -->
            <div class="method-section">
                <div class="method-card fastest">
                    <div class="method-header mb-3">
                        <div class="method-icon" style="background: #f8d7da;">
                            <i class="bi bi-people-fill text-danger"></i>
                        </div>
                        <div>
                            <h2 class="h3 mb-0">Refer Friends</h2>
                            <p class="text-muted mb-0">Share Birthday.Gold with friends and earn rewards</p>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h4 class="h5 mb-2">Invite Your Friends</h4>
                            <p class="text-muted mb-3">Get rewarded when your friends join and start earning birthday rewards</p>
                            <div class="d-flex align-items-center gap-4">
                                <div>
                                    <strong class="d-block">Your referral code:</strong>
                                    <code class="fs-5"><?php echo htmlspecialchars($referralcode['code']); ?></code>
                                </div>
                                <div>
                                    <div class="reward-amount">+1</div>
                                    <small class="text-muted">per friend</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary" onclick="copyReferralCode()">
                                    <i class="bi bi-clipboard me-2"></i>Copy Code
                                </button>
                                <a href="/myaccount/invite" class="btn btn-primary">
                                    <i class="bi bi-send me-2"></i>Invite Friends
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Birthday Feedback Section -->
            <div class="method-section">
                <div class="method-card">
                    <div class="method-header mb-3">
                        <div class="method-icon" style="background: #fff3cd;">
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <div>
                            <h2 class="h3 mb-0">Share Birthday Experiences</h2>
                            <p class="text-muted mb-0">Tell us about your birthday rewards experiences</p>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h4 class="h5 mb-2">Submit Birthday Feedback</h4>
                            <p class="text-muted mb-3">Share your birthday reward experiences and help others discover great offers. Get rewarded for quality feedback!</p>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div>
                                    <div class="reward-amount text-warning">+1</div>
                                    <small class="text-muted">Per feedback</small>
                                </div>
                                <div class="text-muted">+</div>
                                <div>
                                    <div class="reward-amount text-warning">+2</div>
                                    <small class="text-muted">Featured feedback</small>
                                </div>
                            </div>
                            <a href="/myaccount/birthday-feedback" class="btn btn-warning">
                                <i class="bi bi-chat-left-text me-2"></i>Share Feedback
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Media Share Section -->
            <div class="method-section">
                <div class="method-card">
                    <div class="method-header mb-3">
                        <div class="method-icon" style="background: #e3f2fd;">
                            <i class="bi bi-share-fill text-primary"></i>
                        </div>
                        <div>
                            <h2 class="h3 mb-0">Post on Social Media</h2>
                            <p class="text-muted mb-0">Share Birthday.Gold with your followers</p>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h4 class="h5 mb-2">Share with #birthdaygold</h4>
                            <p class="text-muted mb-3">Post about Birthday.Gold on your social media with our hashtag and earn allocations!</p>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div>
                                    <div class="reward-amount text-primary">+1</div>
                                    <small class="text-muted">Per day</small>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted d-block"><i class="bi bi-twitter-x me-1"></i>Twitter/X</small>
                                    <small class="text-muted d-block"><i class="bi bi-facebook me-1"></i>Facebook</small>
                                    <small class="text-muted d-block"><i class="bi bi-instagram me-1"></i>Instagram</small>
                                    <small class="text-muted d-block"><i class="bi bi-tiktok me-1"></i>TikTok</small>
                                </div>
                            </div>
                            <a href="/myaccount/social-share-verify" class="btn btn-primary">
                                <i class="bi bi-share me-2"></i>Post
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Get Verified Section -->
            <div class="method-section">
                <div class="method-card biggest">
                    <div class="method-header mb-3">
                        <div class="method-icon" style="background: #cff4fc;">
                            <i class="bi bi-patch-check-fill text-info"></i>
                        </div>
                        <div>
                            <h2 class="h3 mb-0">Get Verified</h2>
                            <p class="text-muted mb-0">Earn the blue checkmark and stand out</p>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h4 class="h5 mb-2">Account Verification</h4>
                            <p class="text-muted mb-3">Get verified and unlock exclusive benefits with the coveted blue checkmark badge!</p>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div>
                                    <div class="reward-amount text-info">+20</div>
                                    <small class="text-muted">Enrollments</small>
                                </div>
                                <div>
                                    <i class="bi bi-plus text-muted"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-patch-check-fill text-info fs-4 me-2"></i>
                                        <div>
                                            <strong class="d-block">Blue Checkmark</strong>
                                            <small class="text-muted">Verified status</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <a href="/myaccount/manage-verification" class="btn btn-info">
                                <i class="bi bi-patch-check me-2"></i>Get Verified
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php 
            // Only show upgrade CTA for users on free or gold plans (not lifetime)
            if (empty($current_user_data['account_plan']) || 
                in_array(strtolower($current_user_data['account_plan']), ['free', 'gold', 'bgfree1', 'bggold1'])):
            ?>
            <!-- Upgrade CTA -->
            <div class="cta-section">
                <i class="bi bi-rocket-takeoff fs-1 text-primary mb-3 d-block"></i>
                <h3>Need More Enrollments?</h3>
                <p class="text-muted mb-4">Upgrade to Premium and get unlimited annual enrollments</p>
                <a href="/myaccount/subscription" class="btn btn-primary btn-lg">
                    <i class="bi bi-star-fill me-2"></i>View Premium Plans
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Stats Card -->
            <div class="stats-card mb-4">
                <h3 class="h5 mb-3">Your Allocation Summary</h3>
                
                <div class="stat-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Available Now</span>
                        <span class="stat-value text-primary"><?php echo $balance['available_allocations']; ?></span>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Total Earned</span>
                        <span class="stat-value"><?php echo $balance['total_earned']; ?></span>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Total Used</span>
                        <span class="stat-value"><?php echo $balance['total_used']; ?></span>
                    </div>
                </div>
                
                <?php if ($balance['pending_allocations'] > 0): ?>
                <div class="stat-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Pending</span>
                        <span class="stat-value text-warning"><?php echo $balance['pending_allocations']; ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="allocation-progress mt-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Usage</small>
                        <small class="text-muted"><?php echo $balance['total_used']; ?>/<?php echo $balance['total_earned']; ?></small>
                    </div>
                    <div class="progress-custom">
                        <div class="progress-bar-custom" style="width: <?php echo $balance['total_earned'] > 0 ? ($balance['total_used'] / $balance['total_earned'] * 100) : 0; ?>%"></div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="/myaccount/allocation-history" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-clock-history me-2"></i>View History
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</div><!-- end page-content-wrapper -->


<script>
function copyReferralCode() {
    const code = '<?php echo htmlspecialchars($referralcode['code']); ?>';
    navigator.clipboard.writeText(code).then(function() {
        // Show success message
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check me-2"></i>Copied!';
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-success');
        
        setTimeout(function() {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-primary');
        }, 2000);
    });
}
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>