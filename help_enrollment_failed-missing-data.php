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
                    <div class="card-header bg-warning text-dark">
                        <h2 class="mb-0"><i class="bi bi-exclamation-circle"></i> Enrollment Failed: Missing Required Data</h2>
                    </div>
                    <div class="card-body">
                        <h4>What does this mean?</h4>
                        <p>The business's signup form requires information that isn't currently in your Birthday Gold profile. We can only enroll you with the information you've provided to us.</p>

                        <hr>

                        <h4>What information might be missing?</h4>
                        <p>Common required fields that might be incomplete:</p>
                        <ul>
                            <li><strong>Phone number:</strong> Some businesses require a mobile phone for verification or text message rewards</li>
                            <li><strong>Mailing address:</strong> Physical address including street, city, state, and ZIP code</li>
                            <li><strong>Birthdate:</strong> While unlikely, some profiles might have incomplete birth information</li>
                            <li><strong>First/Last name:</strong> Full legal names may be required</li>
                            <li><strong>ZIP code:</strong> Required for location-based businesses</li>
                        </ul>

                        <hr>

                        <h4>How to fix this:</h4>
                        <div class="alert alert-info">
                            <h5>Step 1: Complete Your Profile</h5>
                            <ol>
                                <li>Go to your <a href="/myaccount/profile" target="_blank">Birthday Gold Profile</a></li>
                                <li>Fill in any missing information, especially:
                                    <ul>
                                        <li>Phone number</li>
                                        <li>Complete mailing address</li>
                                        <li>Full first and last name</li>
                                    </ul>
                                </li>
                                <li>Save your changes</li>
                            </ol>
                        </div>

                        <div class="alert alert-success">
                            <h5>Step 2: Request Re-Enrollment</h5>
                            <p>Once your profile is complete, contact our support team and we'll retry your enrollment:</p>
                            <p><a href="mailto:support@birthday.gold?subject=Retry Enrollment - Missing Data Resolved" class="btn btn-sm btn-success">Request Re-Enrollment</a></p>
                        </div>

                        <hr>

                        <h4>Alternative: Manual Enrollment</h4>
                        <p>If you prefer, you can also enroll directly with the business:</p>
                        <ol>
                            <li>Visit the business's website</li>
                            <li>Create an account with your information</li>
                            <li>You'll receive rewards directly from them</li>
                        </ol>

                        <hr>

                        <h4>Why do we need this information?</h4>
                        <p>Birthday Gold acts on your behalf to enroll you in rewards programs. We can only provide the information you've shared with us. More complete profiles mean more successful enrollments!</p>

                        <div class="text-center mt-4">
                            <a href="/myaccount/profile" class="btn btn-primary">Complete My Profile</a>
                            <a href="/myaccount/enrollment-schedule" class="btn btn-secondary">View My Enrollments</a>
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
