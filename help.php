<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');



$additionalstyles .= "
<style>
.disabled-content {
opacity: 0.5;
pointer-events: none;
}

.my-x1 {
margin-top: 2rem;
margin-bottom: 2rem;
}
</style>
";
?>

<!-- ===============================================-->
<!--    Main Content-->
<!-- ===============================================-->
<div class="container main-content">

  <div class="content">

    <div class="card">
      <div class="card-header bg-body-tertiary">
        <h6 class="mb-0">Help Center</h6>
      </div>
      <div class="card-body">
        <h5 class="fs-6 mb-2">Self Help</h5>
        <div class="row g-3">
          <div class="col-xxl-4 col-lg-6">
            <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative"><img src="/public/assets/img/tickets/reports/1.png" alt="" width="39">
              <div class="ms-3 my-x1">
                <h5 class="fs-6 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" href="/faq">Frequently Asked Questions</a></h5>
                <h6 class="mb-0 text-600">Get answers to common issues</h6>
              </div>
            </div>
          </div>
          <div class="col-xxl-4 col-lg-6">
            <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative"><img src="/public/assets/img/tickets/reports/1.png" alt="" width="39">
              <div class="ms-3 my-x1">
                <h5 class="fs-6 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" href="/how">How It Works</a></h5>
                <h6 class="mb-0 text-600">Learn how Birthday.Gold celebrates you.</h6>
              </div>
            </div>
          </div>
          <div class="col-xxl-4 col-lg-6">
            <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative"><img src="/public/assets/img/tickets/reports/1.png" alt="" width="39">
              <div class="ms-3 my-x1">
                <h5 class="fs-6 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" href="/plans">Plans and Pricing</a></h5>
                <h6 class="mb-0 text-600">Find the plan that works best for you</h6>
              </div>
            </div>
          </div>



        </div>



        <h5 class="fs-6 mb-2 mt-5">Community Based Help</h5>
        <div class="row g-3">
          <div class="col-xxl-4 col-lg-6">
            <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative"><img src="/public/assets/img/tickets/reports/6.png" alt="" width="39">
              <div class="ms-3 my-x1">
                <h5 class="fs-6 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" target="_blank" href="https://forum.birthdaygold.cloud">Forum</a></h5>
                <h6 class="mb-0 text-600">Engage with users on our forum</h6>
              </div>
            </div>
          </div>

        </div>
        <?php
// Get business hours settings from the app
$businessHours = $app->bg_businesshours();

// Extract variables for easy access
$disabledClass = $businessHours['display']['disabledClass'];
$afterhourtag = $businessHours['display']['afterhourtag'];
$workingHoursString = $businessHours['display']['workingHoursString'];

// Display customer service heading and after hours tag
echo '
<h5 class="fs-6 mb-2 mt-5">birthday.gold Customer Service</h5>'.$afterhourtag.'
';

// Display holiday alert if applicable
echo $businessHours['display']['alertMessage'];

// Display chat with agent option
echo '
<div class="row g-3">
<div class="col-xxl-4 col-lg-6">
<div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative ' . $disabledClass . '">
<img src="/public/assets/img/tickets/reports/5.png" alt="" width="39"  class="' . $disabledClass . '">
<div class="ms-3 my-x1">
<h5 class="fs-6 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link ' . $disabledClass . '" target="chatwindow" href="/chat">Chat with an Agent</a></h5>
<h6 class="mb-0 text-600 ' . $disabledClass . '">Get online help fast<br>co-browsing available.</h6>
</div>
</div>
</div>
';

// Display call/text option
echo '
<div class="col-xxl-4 col-lg-6">
<div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative ' . $disabledClass . '">
<img src="/public/assets/img/tickets/reports/3.png" alt="" width="39" class="' . $disabledClass . '">
<div class="ms-3 my-x1">
<h5 class="fs-6 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link ' . $disabledClass . '" href="tel:877-234-6532">Call / Text Us</a></h5>
<h6 class="mb-0 text-600 ' . $disabledClass . '">Speak with us during office hours<br>' . $workingHoursString . '</h6>
</div>
</div>
</div>
';

// Conditionally display ticket submission option (currently disabled with 1 == 2)
if (1 == 2) {
  echo '
<div class="col-xxl-4 col-lg-6">
<div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative">
<img src="/public/assets/img/tickets/reports/7.png" alt="" width="39">
<div class="ms-3 my-x1">
<h5 class="fs-6 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Submit A Ticket</a></h5>
<h6 class="mb-0 text-600">Use to provide detailed information<br>and/or upload files to us.</h6>
</div>
</div>
</div>
';
}

        ?>

        <div class="card-footer mt-4 py-4">
          <h6 class="mb-0 text-600">Or connect with us on these networks:</h6>

          <div class="icon-group mt-3">

            <a class="icon-item btn-outline-light me-1" href="https://twitter.com/birthday_gold" target="smwindow"><i class="bi bi-twitter-x"></i></a>
            <a class="icon-item btn-outline-light me-1" href="https://www.facebook.com/birthdaygold/" target="smwindow"><i class="bi bi-facebook"></i></a>
            <a class="icon-item btn-outline-light me-1" href="https://www.instagram.com/birthday_gold/" target="smwindow"><i class="bi bi-instagram"></i></a>
            <a class="icon-item btn-outline-light me-1" href="https://www.linkedin.com/company/birthdaygold" target="smwindow"><i class="bi bi-linkedin"></i></a>
            <a class="icon-item btn-outline-light me-1" href="https://www.tiktok.com/@birthday.gold" target="smwindow"><i class="bi bi-tiktok"></i></a>
            <a class="icon-item btn-outline-light me-1" href="https://www.youtube.com/@birthdaygold" target="smwindow"><i class="bi bi-youtube"></i></a>
            <a class="icon-item btn-outline-light me-0" href="https://www.pinterest.com/birthdaygold/" target="smwindow"><i class="bi bi-pinterest"></i></a>

          </div>
        </div>

      </div>
    </div>
  </div>
</div>
</div>


<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
