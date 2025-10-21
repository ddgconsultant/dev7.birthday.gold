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

<section class="pt-4 main-content">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h2 class="mb-0"><i class="bi bi-info-circle"></i> Enrollment Unsuccessful</h2>
                    </div>
                    <div class="card-body">
                        <h4>What happened?</h4>
                        <p>We encountered an issue while trying to enroll you in this business's rewards program. The specific details should be included in your enrollment notification email.</p>

                        <hr>

                        <h4>Common reasons for enrollment failures:</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="bi bi-person-check"></i> Account Already Exists</h5>
                                        <p class="card-text">You may already have an account with this business.</p>
                                        <a href="/help/enrollment_failed-account-exists" class="btn btn-sm btn-outline-primary">Learn More</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="bi bi-shield-exclamation"></i> Password Issues</h5>
                                        <p class="card-text">The business has specific password requirements we couldn't meet.</p>
                                        <a href="/help/enrollment_failed-password-validation" class="btn btn-sm btn-outline-primary">Learn More</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="bi bi-exclamation-circle"></i> Missing Information</h5>
                                        <p class="card-text">Your profile may be missing required information.</p>
                                        <a href="/help/enrollment_failed-missing-data" class="btn btn-sm btn-outline-primary">Learn More</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="bi bi-x-octagon"></i> Technical Problems</h5>
                                        <p class="card-text">The business's website had a technical issue.</p>
                                        <a href="/help/enrollment_failed-form-failure" class="btn btn-sm btn-outline-primary">Learn More</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h4>What can you do?</h4>
                        <div class="alert alert-info">
                            <h5>Recommended Next Steps:</h5>
                            <ol>
                                <li><strong>Check your email:</strong> Review the specific reason for the failure in your notification</li>
                                <li><strong>Review the help pages above:</strong> Click on the relevant card to learn more about that specific issue</li>
                                <li><strong>Complete your profile:</strong> Make sure all your information is filled out in <a href="/myaccount/profile" target="_blank">your profile</a></li>
                                <li><strong>Try manual enrollment:</strong> Visit the business's website and sign up directly</li>
                                <li><strong>Contact support:</strong> If you need personalized assistance, reach out to our team</li>
                            </ol>
                        </div>

                        <hr>

                        <h4>Will I still get birthday rewards?</h4>
                        <p class="alert alert-success">
                            <strong>Yes!</strong> An enrollment failure doesn't mean you can't get rewards from this business. It just means we need to resolve the specific issue preventing enrollment. Once resolved (either by us or by you enrolling manually), you'll be all set to receive your birthday rewards.
                        </p>

                        <hr>

                        <h4>Need help?</h4>
                        <p>Our support team is here to help you resolve any enrollment issues:</p>
                        <ul>
                            <li>Email: <a href="mailto:support@birthday.gold">support@birthday.gold</a></li>
                            <li>Include the business name and any error details from your notification</li>
                            <li>We typically respond within 24 hours</li>
                        </ul>

                        <div class="text-center mt-4">
                            <a href="/myaccount/profile" class="btn btn-primary">Update My Profile</a>
                            <a href="/myaccount/enrollment-schedule" class="btn btn-secondary">View My Enrollments</a>
                            <a href="mailto:support@birthday.gold" class="btn btn-outline-secondary">Contact Support</a>
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
