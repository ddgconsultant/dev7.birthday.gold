<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Add custom styles for large screen spacing and visual enhancements
$additionalstyles = '
<style>
/* Add more vertical spacing between cards on large screens */
@media (min-width: 992px) {
    .legal-card-spacing {
        margin-bottom: 3rem !important; /* Increased from default 1.5rem (mb-4) */
    }
}

/* Even more spacing on extra large screens */
@media (min-width: 1200px) {
    .legal-card-spacing {
        margin-bottom: 4rem !important;
    }
}

/* Card styling and color themes */
.legal-card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    overflow: hidden;
    cursor: default;
}

.legal-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.legal-card .card-header {
    position: relative;
    padding: 1.25rem;
    font-weight: 600;
    border: none;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.legal-card .card-header i {
    font-size: 1.5rem;
}

/* All Cards - Uniform Dark Blue Theme */
.legal-card .card-header {
    background: linear-gradient(135deg, #0C2B4B 0%, #1a3a5a 100%);
    color: white;
}

.legal-card:hover .card-header {
    background: linear-gradient(135deg, #1a3a5a 0%, #0C2B4B 100%);
}

/* Button styling within cards - using sites dark blue */
.legal-card .btn-primary {
    background: #0C2B4B;
    border: none;
    border-radius: 25px;
    padding: 0.5rem 1.5rem;
    transition: all 0.3s ease;
}

.legal-card .btn-primary:hover {
    background: #1a3a5a;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(12, 43, 75, 0.35);
}

.legal-card .btn-secondary {
    background: #F3BD00;
    color: #0C2B4B;
    border: none;
    border-radius: 25px;
    padding: 0.375rem 1rem;
    transition: all 0.3s ease;
}

.legal-card .btn-secondary:hover {
    background: #d4a500;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(243, 189, 0, 0.35);
}

/* Trust Banner Styles */
.trust-banner {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    padding: 3rem 0;
    margin-top: 4rem;
    border-top: 1px solid #a5d6a7;
    position: relative;
    overflow: hidden;
}

.trust-banner::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: subtle-pulse 10s ease-in-out infinite;
}

@keyframes subtle-pulse {
    0%, 100% { opacity: 0.3; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.1); }
}

.trust-item {
    text-align: center;
    padding: 1rem;
    position: relative;
    z-index: 1;
}

.trust-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #ffffff 0%, #f1f8e9 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
    font-size: 1.5rem;
    color: #2e7d32;
    border: 2px solid rgba(76, 175, 80, 0.1);
}

.trust-item h5 {
    font-size: 1rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.trust-item p {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0;
}

@media (max-width: 768px) {
    .trust-item {
        margin-bottom: 2rem;
    }
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
            <div class="col-md-6 col-sm-12 mb-4 legal-card-spacing">
                <div class="card legal-card card-terms" data-href='/legalhub/terms';">
                    <div class="card-header h5">
                        <i class="bi bi-file-text"></i>
                        Terms and Conditions
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Explore our terms to understand our platform's rules. Ensuring clarity and fairness for all users!</p>
                        <div class="d-md-flex">
                            <div class="mb-2 mb-md-0">
                                <a href="/legalhub/terms" class="btn btn-primary">Read our Terms</a>
                            </div>
                            <div class="d-none d-md-inline mx-2 mt-1">
                                -
                            </div>
                            <div>
                                <a href="/legalhub/terms?full" class="btn btn-sm btn-secondary mt-1">Full Terms and Conditions</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Privacy Policy Card -->
            <div class="col-md-6 col-sm-12 mb-4 legal-card-spacing">
                <div class="card legal-card card-privacy" data-href='/legalhub/privacy';">
                    <div class="card-header h5">
                        <i class="bi bi-shield-check"></i>
                        Privacy Policy
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Your privacy is paramount to us. Dive into our policy to see how we protect your data.</p>
                        <div class="d-md-flex flex-wrap">
                            <div class="mb-2 mb-md-0">
                                <a href="/legalhub/privacy" class="btn btn-primary">Read our Policy</a>
                            </div>
                            <div class="d-none d-md-inline mx-2 mt-1">
                                -
                            </div>
                            <div class="mb-2 mb-md-0">
                                <a href="/legalhub/privacy?full" class="btn btn-sm btn-secondary mt-1">Full Privacy Policy</a>
                            </div>
                            <div class="ms-md-3">
                                <a href="/legalhub/cookies" class="btn btn-sm btn-secondary mt-1">Manage Cookies</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Our Responsibilities Card -->
            <div class="col-md-6 col-sm-12 mb-4 legal-card-spacing">
                <div class="card legal-card card-responsibilities" data-href='/legalhub/responsibilities';">
                    <div class="card-header h5">
                        <i class="bi bi-award"></i>
                        Our Responsibilities
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Discover our commitment to you. Upholding our responsibilities is a top priority.</p>
                        <a href="/legalhub/responsibilities" class="btn btn-primary">Learn More</a>
                    </div>
                </div>
            </div>

            <!-- Your Data Rights Card -->
            <div class="col-md-6 col-sm-12 mb-4 legal-card-spacing">
                <div class="card legal-card card-rights" data-href='/legalhub/datarights';">
                    <div class="card-header h5">
                        <i class="bi bi-person-lock"></i>
                        Your Data Rights
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Learn about your data rights. Empowering users through transparent data practices!</p>
                        <a href="/legalhub/datarights" class="btn btn-primary">Know Your Rights</a>
                    </div>
                </div>
            </div>

        </div>
        <div class="row justify-content-center">

            <!-- Partner Terms Card -->
            <div class="col-md-6 col-sm-12 mb-4 legal-card-spacing">
                <div class="card legal-card card-partner" data-href='/legalhub/partnerterms';">
                    <div class="card-header h5">
                        <i class="bi bi-briefcase"></i>
                        Partner Terms and Conditions
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Terms for businesses partnering with Birthday Gold. Learn about our partnership agreement and requirements.</p>
                        <div class="d-md-flex">
                            <div class="mb-2 mb-md-0">
                                <a href="/legalhub/partnerterms" class="btn btn-primary">Partner Terms</a>
                            </div>
                            <div class="d-none d-md-inline mx-2 mt-1">
                                -
                            </div>
                            <div>
                                <a href="/legalhub/partnerterms?full" class="btn btn-sm btn-secondary mt-1">Full Partner Terms</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CopyRight Card -->
            <div class="col-md-6 col-sm-12 mb-4 legal-card-spacing">
                <div class="card legal-card card-copyright" data-href='/legalhub/digitalrights';">
                    <div class="card-header h5">
                        <i class="bi bi-c-circle"></i>
                        Copyrights, Licenses, DMCA
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Copyright notices, Licenses and DMCA take down information.</p>
                        <a href="/legalhub/digitalrights" class="btn btn-primary">Read more</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    <!-- Trust and Security Banner -->
    <div class="trust-banner">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="trust-item">
                        <div class="trust-icon">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <h5>256-bit SSL Encryption</h5>
                        <p>Your data is protected with bank-level security</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="trust-item">
                        <div class="trust-icon">
                            <i class="bi bi-patch-check-fill"></i>
                        </div>
                        <h5>GDPR Compliant</h5>
                        <p>Full compliance with EU data protection laws</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="trust-item">
                        <div class="trust-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <h5>24/7 Monitoring</h5>
                        <p>Continuous security monitoring and updates</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="trust-item">
                        <div class="trust-icon">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <h5>Data Sovereignty</h5>
                        <p>Your data stays in secure US-based servers</p>
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
