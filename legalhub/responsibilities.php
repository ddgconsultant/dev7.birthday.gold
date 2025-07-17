<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>



<!--  Start -->
<div class="container-xxl py-6">
    <div class="container">
        <div class="row">
            <div class="col">
                <h1 class="display-1">Our Responsibilities</h1>
                <p class="mb-4">Last updated: July 14, 2025</p>

                Here at birthday.gold, we take our responsibilities to you and our community of users seriously. Here is our commitment of the duties of controllers of data that we uphold.

                <h5 class="mt-5">Our Responsibilities to You</h5>

                <p><strong>1. Transparency is Key:</strong> We are committed to being open with our users. We provide clear privacy notices detailing:
                <ul>
                    <li>The kind of data we collect and process.</li>
                    <li>The purpose behind collecting such data.</li>
                    <li>Steps on how you can exercise your rights and how to reach us.</li>
                    <li>The only third parties we share your data with are the businesses that you select to enroll with and any other service that helps make our website service actually operate.</li>
                </ul>
                
                <p><strong>2. Clarity on Data Sale:</strong> We won't sell your personal data but may use aggregated information to provide you with sponsored promotions. If we ever provide that that service, we'll be upfront about it. Plus, you'll always have the option to opt-out.</p>

                <p><strong>3. Your Convenience:</strong> You won't need to create a new account just to exercise your rights with us. Moreover, we won't hike our prices or reduce our service offerings just because you exercised your rights. And yes, we enroll you into other business' loyalty and reward programs that you select, with all the information we know, providing you an enjoyable transparent experience.</p>

                <p><strong>4. Purpose-Driven Data Collection:</strong> We only collect data with a clear purpose in mind, and we make sure you're aware of it.</p>

                <p><strong>5. Minimum Data, Maximum Care:</strong> We collect only what is necessary and ensure that your data is safe with us, both during storage and use.</p>

                <p><strong>6. No Unintended Uses:</strong> Your data won't be used for anything outside of the specified purposes without your explicit consent.</p>

                <p><strong>7. Commitment to Fairness:</strong> We're against discrimination. Your data will never be used in a way that goes against state or federal anti-discrimination laws.</p>

                <p><strong>8. Special Care for Sensitive Data:</strong> We handle sensitive data with extra caution, always seeking consent before processing, especially when it concerns minors.</p>

                <p>Trust is the foundation of our community. We're committed to maintaining and building on this with every step we take.</p>

                <h5 class="mt-5">Legal Compliance and Mandatory Reporting</h5>

                <p><strong>1. Compliance with Law Enforcement:</strong> While we prioritize your privacy, we are obligated to comply with valid legal requests:
                <ul>
                    <li>We will respond to lawful subpoenas, court orders, and warrants as required by law.</li>
                    <li>We maintain a transparent approach and will notify you of legal requests when legally permitted to do so.</li>
                    <li>We carefully review each request to ensure it meets legal requirements before sharing any data.</li>
                </ul>

                <p><strong>2. Child Safety Obligations:</strong> We take the protection of minors seriously:
                <ul>
                    <li>We report suspected child exploitation or abuse to the National Center for Missing & Exploited Children (NCMEC) and law enforcement as required by law.</li>
                    <li>We maintain systems to detect and prevent the distribution of child sexual abuse material (CSAM).</li>
                    <li>We cooperate fully with child safety investigations while respecting user privacy to the extent permitted by law.</li>
                </ul>

                <p><strong>3. Financial Crime Prevention:</strong> As a platform processing payments, we have obligations to:
                <ul>
                    <li>Report suspicious financial activities to appropriate authorities under anti-money laundering (AML) regulations.</li>
                    <li>Comply with Know Your Customer (KYC) requirements for certain transaction thresholds.</li>
                    <li>Maintain records as required by financial regulations and make them available to authorized regulators.</li>
                </ul>

                <p><strong>4. Data Breach Notification:</strong> In the unlikely event of a data breach:
                <ul>
                    <li>We will notify affected users within 72 hours of discovering the breach, as required by law.</li>
                    <li>We will provide clear information about what data was affected and steps you can take to protect yourself.</li>
                    <li>We will notify relevant regulatory authorities as required by applicable data protection laws.</li>
                    <li>We maintain incident response procedures to minimize impact and prevent future breaches.</li>
                </ul>

                <p><strong>5. International Data Transfer Compliance:</strong> When transferring data internationally:
                <ul>
                    <li>We ensure appropriate safeguards are in place as required by GDPR and other privacy frameworks.</li>
                    <li>We maintain Standard Contractual Clauses (SCCs) with international partners where required.</li>
                    <li>We are transparent about where your data is stored and processed.</li>
                </ul>

                <p><strong>6. Regulatory Reporting:</strong> We maintain compliance with various regulatory requirements:
                <ul>
                    <li>We file required reports with the Federal Trade Commission (FTC) regarding consumer complaints and data practices.</li>
                    <li>We comply with state-specific privacy law reporting requirements, including those under CCPA, CPA, and other state privacy acts.</li>
                    <li>We maintain records of our data processing activities as required by privacy regulations.</li>
                </ul>

                <p class="mt-4"><em>Note: While we strive to protect your privacy, these legal obligations ensure we operate responsibly and help maintain a safe platform for all users. We will always seek to balance our legal obligations with your privacy rights, notifying you whenever possible and appropriate.</em></p>

                <footer>
                    <p>For further details, please refer to C.R.S. § 6-1-1308 and applicable federal regulations.</p>
                </footer>


                <?PHP
                if (isset($_REQUEST['register'])) {
                    echo '                <a class="btn btn-primary py-3 px-5 no-print" href="/signup">Go Back To Sign Up</a>';
                } else {
                    echo '                <a class="btn btn-primary py-3 px-5 no-print" href="/">Go Back To Home</a>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <!-- Compliance and Trust Banner -->
    <div class="py-5 mt-5 mb-5 position-relative overflow-hidden" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <div class="text-center p-3 position-relative">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm" style="width: 60px; height: 60px;">
                            <i class="bi bi-shield-fill fs-3 text-primary"></i>
                        </div>
                        <h5 class="fs-6 fw-semibold text-dark mb-2">GDPR & CCPA Compliant</h5>
                        <p class="small text-muted m-0">Full compliance with major privacy regulations</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <div class="text-center p-3 position-relative">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm" style="width: 60px; height: 60px;">
                            <i class="bi bi-clock-fill fs-3 text-success"></i>
                        </div>
                        <h5 class="fs-6 fw-semibold text-dark mb-2">72-Hour Breach Notice</h5>
                        <p class="small text-muted m-0">Rapid notification in case of data incidents</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <div class="text-center p-3 position-relative">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm" style="width: 60px; height: 60px;">
                            <i class="bi bi-file-earmark-lock-fill fs-3 text-warning"></i>
                        </div>
                        <h5 class="fs-6 fw-semibold text-dark mb-2">Transparent Reporting</h5>
                        <p class="small text-muted m-0">Annual transparency reports available</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <div class="text-center p-3 position-relative">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm" style="width: 60px; height: 60px;">
                            <i class="bi bi-people-fill fs-3 text-info"></i>
                        </div>
                        <h5 class="fs-6 fw-semibold text-dark mb-2">Child Safety First</h5>
                        <p class="small text-muted m-0">COPPA compliant with parental controls</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--  End -->



<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
