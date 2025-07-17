<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Minimal custom styles - only what Bootstrap 5 cannot handle
$additionalstyles .= '
<style>
/* Only gradients, animations, and fixed-size circles - not available in Bootstrap */
.icon-circle {
    width: 60px;
    height: 60px;
}

/* Ensure DM Sans font is used */
body {
    font-family: "DM Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif !important;
}

/* Gradient backgrounds */
.legal-card .card-header {
    background: linear-gradient(135deg, #0C2B4B 0%, #1a3a5a 100%);
}

.legal-card:hover .card-header {
    background: linear-gradient(135deg, #1a3a5a 0%, #0C2B4B 100%);
}

.trust-banner {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
}

.trust-banner::before {
    content: "";
    position: absolute;
    inset: -50%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: subtle-pulse 10s ease-in-out infinite;
}

@keyframes subtle-pulse {
    0%, 100% { opacity: 0.3; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.1); }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>


<?PHP
if (!empty($enableadminpageeditor)) {   $admin->admineditor('body-1'); }
### ADMIN PAGE EDITOR: START-body-1 ###
?>
<!-- Hero Section -->
<div class="content-header-dark">
    <div class="container">
        <h1>Your Trust, Our Priority</h1>
        <p class="lead">Understanding our platform's commitment to transparency, privacy, and responsibility.</p>
    </div>
</div>

<div class="container flex-grow-1 my-5">

    <div class="container mt-4">
        <div class="row">

            <!-- Terms and Conditions Card -->
            <div class="col-md-6 col-sm-12 mb-4 mb-lg-5">
                <div class="card legal-card shadow-sm border-0">
                    <div class="card-header h5 text-white border-0 fw-semibold p-3 d-flex align-items-center gap-3">
                        <i class="bi bi-file-text fs-4"></i>
                        Terms and Conditions
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Explore our terms to understand our platform's rules. Ensuring clarity and fairness for all users!</p>
                        <div class="d-md-flex">
                            <div class="mb-2 mb-md-0">
                                <a href="/legalhub/terms" class="btn btn-dark rounded-pill px-4">Read our Terms</a>
                            </div>
                            <div class="d-none d-md-inline mx-2 mt-1">
                                -
                            </div>
                            <div>
                                <a href="/legalhub/terms?full" class="btn btn-sm btn-warning rounded-pill mt-1 fw-semibold">Full Terms and Conditions</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Privacy Policy Card -->
            <div class="col-md-6 col-sm-12 mb-4 mb-lg-5">
                <div class="card legal-card shadow-sm border-0">
                    <div class="card-header h5 text-white border-0 fw-semibold p-3 d-flex align-items-center gap-3">
                        <i class="bi bi-shield-check fs-4"></i>
                        Privacy Policy
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Your privacy is paramount to us. Dive into our policy to see how we protect your data.</p>
                        <div class="d-md-flex flex-wrap">
                            <div class="mb-2 mb-md-0">
                                <a href="/legalhub/privacy" class="btn btn-dark rounded-pill px-4">Read our Policy</a>
                            </div>
                            <div class="d-none d-md-inline mx-2 mt-1">
                                -
                            </div>
                            <div class="mb-2 mb-md-0">
                                <a href="/legalhub/privacy?full" class="btn btn-sm btn-warning rounded-pill mt-1 fw-semibold">Full Privacy Policy</a>
                            </div>
                            <div class="ms-md-3">
                                <a href="/legalhub/cookies" class="btn btn-sm btn-warning rounded-pill mt-1 fw-semibold">Manage Cookies</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Our Responsibilities Card -->
            <div class="col-md-6 col-sm-12 mb-4 mb-lg-5">
                <div class="card legal-card shadow-sm border-0">                    <div class="card-header h5 text-white border-0 fw-semibold p-3 d-flex align-items-center gap-3">
                        <i class="bi bi-award fs-4"></i>
                        Our Responsibilities
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Discover our commitment to you. Upholding our responsibilities is a top priority.</p>
                        <a href="/legalhub/responsibilities" class="btn btn-dark rounded-pill px-4">Learn More</a>
                    </div>
                </div>
            </div>

            <!-- Your Data Rights Card -->
            <div class="col-md-6 col-sm-12 mb-4 mb-lg-5">
                <div class="card legal-card shadow-sm border-0">                    <div class="card-header h5 text-white border-0 fw-semibold p-3 d-flex align-items-center gap-3">
                        <i class="bi bi-person-lock fs-4"></i>
                        Your Data Rights
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Learn about your data rights. Empowering users through transparent data practices!</p>
                        <a href="/legalhub/datarights" class="btn btn-dark rounded-pill px-4">Know Your Rights</a>
                    </div>
                </div>
            </div>

        </div>
        <div class="row justify-content-center">

            <!-- Partner Terms Card -->
            <div class="col-md-6 col-sm-12 mb-4 mb-lg-5">
                <div class="card legal-card shadow-sm border-0">                    <div class="card-header h5 text-white border-0 fw-semibold p-3 d-flex align-items-center gap-3">
                        <i class="bi bi-briefcase fs-4"></i>
                        Partner Terms and Conditions
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Terms for businesses partnering with Birthday Gold. Learn about our partnership agreement and requirements.</p>
                        <div class="d-md-flex">
                            <div class="mb-2 mb-md-0">
                                <a href="/legalhub/partnerterms" class="btn btn-dark rounded-pill px-4">Partner Terms</a>
                            </div>
                            <div class="d-none d-md-inline mx-2 mt-1">
                                -
                            </div>
                            <div>
                                <a href="/legalhub/partnerterms?full" class="btn btn-sm btn-warning rounded-pill mt-1 fw-semibold">Full Partner Terms</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CopyRight Card -->
            <div class="col-md-6 col-sm-12 mb-4 mb-lg-5">
                <div class="card legal-card shadow-sm border-0">                    <div class="card-header h5 text-white border-0 fw-semibold p-3 d-flex align-items-center gap-3">
                        <i class="bi bi-c-circle fs-4"></i>
                        Copyrights, Licenses, DMCA
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Copyright notices, Licenses and DMCA take down information.</p>
                        <a href="/legalhub/digitalrights" class="btn btn-dark rounded-pill px-4">Read more</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    <!-- Trust and Security Banner -->
    <div class="trust-banner py-5 mt-5 position-relative overflow-hidden">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <div class="text-center p-3 position-relative">
                        <div class="icon-circle bg-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm">
                            <i class="bi bi-shield-lock-fill fs-3 text-success"></i>
                        </div>
                        <h5 class="fs-6 fw-semibold text-dark mb-2">256-bit SSL Encryption</h5>
                        <p class="small text-muted m-0">Your data is protected with bank-level security</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <div class="text-center p-3 position-relative">
                        <div class="icon-circle bg-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm">
                            <i class="bi bi-patch-check-fill fs-3 text-primary"></i>
                        </div>
                        <h5 class="fs-6 fw-semibold text-dark mb-2">GDPR Compliant</h5>
                        <p class="small text-muted m-0">Full compliance with EU data protection laws</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <div class="text-center p-3 position-relative">
                        <div class="icon-circle bg-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm">
                            <i class="bi bi-clock-history fs-3 text-info"></i>
                        </div>
                        <h5 class="fs-6 fw-semibold text-dark mb-2">24/7 Monitoring</h5>
                        <p class="small text-muted m-0">Continuous security monitoring and updates</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <div class="text-center p-3 position-relative">
                        <div class="icon-circle bg-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm">
                            <i class="bi bi-geo-alt-fill fs-3 text-secondary"></i>
                        </div>
                        <h5 class="fs-6 fw-semibold text-dark mb-2">Data Sovereignty</h5>
                        <p class="small text-muted m-0">Your data stays in secure US-based servers</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?PHP
### ADMIN PAGE EDITOR: END-body-1 ###
?>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
