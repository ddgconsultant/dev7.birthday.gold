<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Add bg_theme.css for content-header-dark
$additionalstyles = '<link href="' . cssUrl('/public/css/v7/bg_theme.css') . '" rel="stylesheet">';

$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <h1>Enrollment Problem Help</h1>
        <p class="lead">Understanding and resolving enrollment issues</p>
    </div>
</div>

<section class="py-5 main-content">
    <div class="container">
        <!-- Hero Card -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-10">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5 text-center">
                        <div class="mb-4">
                            <i class="bi bi-person-check-fill text-warning" style="font-size: 5rem;"></i>
                        </div>
                        <h2 class="display-5 fw-bold mb-3">Account Already Exists</h2>
                        <p class="lead text-muted mb-4">Great news! You're already enrolled in this rewards program</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Good News Banner -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-10">
                <div class="alert alert-success border-0 shadow-sm" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-3" style="font-size: 3rem;"></i>
                        <div>
                            <h4 class="alert-heading mb-2"><strong>This is NOT a problem!</strong></h4>
                            <p class="mb-0">You already have an account with this business, which means you're already enrolled in their rewards program. You don't need us to sign you up again - you're all set!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Why This Happened -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h4 class="mb-0"><i class="bi bi-question-circle me-2"></i>Why This Happened</h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="text-center p-3 h-100 bg-light rounded">
                                    <i class="bi bi-calendar-check text-primary mb-3" style="font-size: 2.5rem;"></i>
                                    <h5>Previous Enrollment</h5>
                                    <p class="text-muted small mb-0">You may have signed up directly before joining Birthday Gold</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3 h-100 bg-light rounded">
                                    <i class="bi bi-clock-history text-primary mb-3" style="font-size: 2.5rem;"></i>
                                    <h5>Old Account</h5>
                                    <p class="text-muted small mb-0">You might have an account from years ago that you forgot about</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3 h-100 bg-light rounded">
                                    <i class="bi bi-envelope text-primary mb-3" style="font-size: 2.5rem;"></i>
                                    <h5>Different Email</h5>
                                    <p class="text-muted small mb-0">The account might be under a different email address</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Steps -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-success text-white py-3">
                        <h4 class="mb-0"><i class="bi bi-list-check me-2"></i>What You Should Do</h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                            <i class="bi bi-1-circle-fill"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5>Locate Your Account</h5>
                                        <ul class="text-muted mb-0">
                                            <li>Try logging into the business's website or app</li>
                                            <li>Use "Forgot Password" if needed</li>
                                            <li>Check your email for old confirmations</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                            <i class="bi bi-2-circle-fill"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5>Verify Your Birthday</h5>
                                        <p class="text-muted mb-0">Once logged in, make sure your birthdate is correctly entered in your profile</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                            <i class="bi bi-3-circle-fill"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5>Update Information</h5>
                                        <p class="text-muted mb-0">Ensure your email, phone, and mailing address are current</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                            <i class="bi bi-4-circle-fill"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5>Opt-in to Emails</h5>
                                        <p class="text-muted mb-0">Make sure you're subscribed to promotional emails so you don't miss your reward</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0 bg-light">
                    <div class="card-body p-4 text-center">
                        <h4 class="mb-3"><i class="bi bi-question-circle-fill text-primary me-2"></i>Need Help?</h4>
                        <p class="text-muted mb-4">If you can't find or access your existing account, we're here to help!</p>
                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <a href="/myaccount/enrollment-schedule" class="btn btn-primary btn-lg px-4">
                                <i class="bi bi-list-ul me-2"></i>View My Enrollments
                            </a>
                            <a href="mailto:support@birthday.gold" class="btn btn-outline-primary btn-lg px-4">
                                <i class="bi bi-envelope me-2"></i>Contact Support
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
