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
                    <div class="card-header bg-danger text-white">
                        <h2 class="mb-0"><i class="bi bi-x-octagon"></i> Enrollment Failed: Form Failure</h2>
                    </div>
                    <div class="card-body">
                        <h4>What does this mean?</h4>
                        <p>We encountered a technical problem while trying to submit your enrollment to the business's website. This is typically a temporary issue with their signup form or website.</p>

                        <hr>

                        <h4>Why did this happen?</h4>
                        <p>Common causes of form failures:</p>
                        <ul>
                            <li><strong>Website changes:</strong> The business updated their signup process and we need to update our enrollment method</li>
                            <li><strong>Technical issues:</strong> Their website was temporarily down or experiencing problems</li>
                            <li><strong>Security measures:</strong> New anti-bot protections that we need to work around</li>
                            <li><strong>Form validation:</strong> Unexpected field requirements or changes to their form structure</li>
                            <li><strong>Server timeouts:</strong> Their website took too long to respond</li>
                        </ul>

                        <hr>

                        <h4>What we're doing about it:</h4>
                        <div class="alert alert-info">
                            <h5><i class="bi bi-tools"></i> Our Technical Team Is On It</h5>
                            <p>When we encounter form failures, our team:</p>
                            <ol>
                                <li>Investigates the specific business's signup process</li>
                                <li>Updates our enrollment methods if needed</li>
                                <li>Retries failed enrollments automatically when possible</li>
                                <li>Contacts businesses directly if there are ongoing issues</li>
                            </ol>
                        </div>

                        <hr>

                        <h4>What should you do?</h4>
                        <div class="alert alert-warning">
                            <h5>Option 1: Wait for Us to Retry (Recommended)</h5>
                            <p>In most cases, we'll automatically retry your enrollment once we've identified and fixed the issue. You don't need to do anything - we'll send you an update when it's complete.</p>
                        </div>

                        <div class="alert alert-success">
                            <h5>Option 2: Enroll Manually (Faster)</h5>
                            <p>If you don't want to wait, you can enroll directly with the business:</p>
                            <ol>
                                <li>Visit the business's website</li>
                                <li>Click "Sign Up" or "Join Rewards"</li>
                                <li>Complete the enrollment form yourself</li>
                                <li>Let us know so we can update our records</li>
                            </ol>
                        </div>

                        <hr>

                        <h4>Need immediate help?</h4>
                        <p>If this enrollment is urgent or important to you:</p>
                        <ul>
                            <li>Contact us at <a href="mailto:support@birthday.gold">support@birthday.gold</a></li>
                            <li>Include the business name in your message</li>
                            <li>We'll prioritize investigating and resolving the issue</li>
                        </ul>

                        <hr>

                        <h4>Will I still get my birthday reward?</h4>
                        <p class="alert alert-success">
                            <strong>Yes!</strong> Form failures are usually temporary. Whether we fix the issue and retry, or you enroll manually, you'll still receive your birthday reward. We just need to get you successfully enrolled first.
                        </p>

                        <div class="text-center mt-4">
                            <a href="/myaccount/enrollment-schedule" class="btn btn-primary">View My Enrollments</a>
                            <a href="mailto:support@birthday.gold?subject=Form Failure - Need Help" class="btn btn-danger">Contact Support</a>
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
