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
                            <i class="bi bi-shield-lock-fill text-danger" style="font-size: 5rem;"></i>
                        </div>
                        <h2 class="display-5 fw-bold mb-3">Password Needs an Upgrade</h2>
                        <p class="lead text-muted mb-4">Your Birthday Gold password doesn't meet this business's security requirements</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- What This Means -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h4 class="mb-0"><i class="bi bi-info-circle me-2"></i>What This Means</h4>
                    </div>
                    <div class="card-body p-4">
                        <p class="lead">Your current Birthday Gold password doesn't meet this business's security requirements. The business we're trying to enroll you with has specific password rules, and your current password doesn't include all the elements they require.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Password Requirements -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-warning text-dark py-3">
                        <h4 class="mb-0"><i class="bi bi-list-check me-2"></i>Password Requirements</h4>
                    </div>
                    <div class="card-body p-4">
                        <p class="mb-4">Your current Birthday Gold password might be missing:</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start p-3 bg-light rounded">
                                    <i class="bi bi-asterisk text-warning me-3" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <h6 class="mb-1">Special Characters</h6>
                                        <p class="text-muted small mb-0">Symbols like !, @, #, $, %, etc.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start p-3 bg-light rounded">
                                    <i class="bi bi-123 text-warning me-3" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <h6 class="mb-1">Numbers</h6>
                                        <p class="text-muted small mb-0">At least one digit (0-9)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start p-3 bg-light rounded">
                                    <i class="bi bi-type text-warning me-3" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <h6 class="mb-1">Uppercase Letters</h6>
                                        <p class="text-muted small mb-0">At least one capital letter (A-Z)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start p-3 bg-light rounded">
                                    <i class="bi bi-rulers text-warning me-3" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <h6 class="mb-1">Minimum Length</h6>
                                        <p class="text-muted small mb-0">At least 12 characters long</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Solution -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-key-fill text-success" style="font-size: 3rem;"></i>
                            <h3 class="mt-3 fw-bold">Update Your Birthday Gold Password</h3>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-3 text-center">
                                <div class="bg-white rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                    <span class="display-6 fw-bold text-success">1</span>
                                </div>
                                <h6>Go to Profile</h6>
                                <a href="/myaccount/profile" target="_blank" class="btn btn-sm btn-success mt-2">Open Profile</a>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="bg-white rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                    <span class="display-6 fw-bold text-success">2</span>
                                </div>
                                <h6>Update Password</h6>
                                <p class="small text-muted mb-0">Include all requirements</p>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="bg-white rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                    <span class="display-6 fw-bold text-success">3</span>
                                </div>
                                <h6>Save Changes</h6>
                                <p class="small text-muted mb-0">Confirm your new password</p>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="bg-white rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                    <span class="display-6 fw-bold text-success">4</span>
                                </div>
                                <h6>Contact Support</h6>
                                <p class="small text-muted mb-0">We'll retry enrollment</p>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-white rounded text-center">
                            <p class="mb-2"><strong>Example of a strong password:</strong></p>
                            <code class="fs-5 text-success">Birthday2025!@Gold</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0 bg-light">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5><i class="bi bi-lightbulb-fill text-warning me-2"></i>Password Tips</h5>
                                <ul class="mb-0 text-muted">
                                    <li>Use a phrase you'll remember</li>
                                    <li>Mix capital letters, numbers, and symbols</li>
                                    <li>Make it at least 12 characters long</li>
                                    <li>Use a password manager for extra security</li>
                                </ul>
                            </div>
                            <div class="col-md-4 text-center">
                                <a href="mailto:support@birthday.gold" class="btn btn-primary btn-lg px-4">
                                    <i class="bi bi-envelope me-2"></i>Get Help
                                </a>
                            </div>
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
