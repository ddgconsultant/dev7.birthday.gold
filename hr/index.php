<?PHP
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');


$bodycontentclass='';
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

        <div class="content my-5 py-2">
   
          <div class="card">
            <div class="card-header bg-body-tertiary">
              <h6 class="mb-0">Human Resources Office</h6>
            </div>
            <div class="card-body">
              <h5 class="fs-9 mb-2">Your File</h5>
              <div class="row g-3">
                <div class="col-xxl-4 col-lg-6">
                  <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative"><img src="/public/assets/img/tickets/reports/1.png" alt="" width="39" />
                    <div class="ms-3 my-x1">
                      <h5 class="fs-9 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" href="/faq">Frequently Asked Questions</a></h5>
                      <h6 class="mb-0 text-600">Get answers to common issues</h6>
                    </div>
                  </div>
                </div>
               
                
              </div>
              <h5 class="fs-9 mb-2 mt-5">Team Activities</h5>
              <div class="row g-3">
                <div class="col-xxl-4 col-lg-6">
                  <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative"><img src="/public/assets/img/tickets/reports/6.png" alt="" width="39" />
                    <div class="ms-3 my-x1">
                      <h5 class="fs-9 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Forum</a></h5>
                      <h6 class="mb-0 text-600">Engage with users on our forum</h6>
                    </div>
                  </div>
                </div>
                <div class="col-xxl-4 col-lg-6">
                  <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative">
                  <i class="bi bi-discord h2 text-primary"></i>
                    <div class="ms-3 my-x1">
                      <h5 class="fs-9 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" target="_discord" href="https://discord.gg/TXkCCbdt">Discord</a></h5>
                      <h6 class="mb-0 text-600">Talk with members on our channel.</h6>
                    </div>
                  </div>
                </div>
              </div>
              <h5 class="fs-9 mb-2 mt-5">birthday.gold Customer Service</h5>
              <div class="row g-3">
                <div class="col-xxl-4 col-lg-6">
                  <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative">
                    <img src="/public/assets/img/tickets/reports/5.png" alt="" width="39" />
                    <div class="ms-3 my-x1">
                      <h5 class="fs-9 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" target="_chat" href="https://tawk.to/chat/64d42e54cc26a871b02e57a7/1h7ed9udu">Chat with HR</a></h5>
                      <h6 class="mb-0 text-600">Get online help fast<br>co-browsing available.</h6>
                    </div>
                  </div>
                </div>
                <div class="col-xxl-4 col-lg-6">
                  <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative">
                    <img src="/public/assets/img/tickets/reports/3.png" alt="" width="39" />
                    <div class="ms-3 my-x1">
                      <h5 class="fs-9 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" href="tel:877-234-6532">Call / Text Us</a></h5>
                      <h6 class="mb-0 text-600">Speak with us during office hours<br>M-F 9AM - 5PM MST</h6>
                    </div>
                  </div>
                </div>
                <div class="col-xxl-4 col-lg-6">
                  <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative">
                    <img src="/public/assets/img/tickets/reports/7.png" alt="" width="39" />
                    <div class="ms-3 my-x1">
                      <h5 class="fs-9 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Submit A Ticket</a></h5>
                      <h6 class="mb-0 text-600">Use to provide detailed information<br>and/or upload files to us.</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>
        
        
           <div class="card-footer mt-4 py-4">

            </div>
         
            </div>

            </div>
            </div>


 <?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();