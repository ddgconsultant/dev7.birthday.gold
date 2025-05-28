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
                <p class="mb-4">Last updated: August 10, 2023</p>

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

                <footer>
                    <p>For further details, please refer to C.R.S. § 6-1-1308.</p>
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
</div>
<!--  End -->



<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
