<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core'].'/' .$website['ui_version']. '/header3.inc');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container main-content my-5">
      <div class="card ">
            <div class="card-header bg-body-tertiary">
              <h6 class="mb-0">Reports</h6>
            </div>
            <div class="card-body">
              <h5 class="fs-9 mb-2">Analysis of the Helpdesk</h5>
              <div class="row g-3">
                <div class="col-xxl-4 col-lg-6">
                  <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative"><img src="/public/assets/img/tickets/reports/1.png" alt="" width="39">
                    <div class="ms-3 my-x1">
                      <h5 class="fs-9 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" href="#!">In-Depth Helpdesk</a></h5>
                      <h6 class="mb-0 text-600">an overview of your helpdesk system</h6>
                    </div>
                  </div>
                </div>
                <div class="col-xxl-4 col-lg-6">
                  <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative"><img src="/public/assets/img/tickets/reports/2.png" alt="" width="39">
                    <div class="ms-3 my-x1">
                      <h5 class="fs-9 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Trends in Ticket Volume</a></h5>
                      <h6 class="mb-0 text-600">an overview of the number of tickets</h6>
                    </div>
                  </div>
                </div>
              </div>
              <h5 class="fs-9 mb-2 mt-5">Customer Satisfaction</h5>
              <div class="row g-3">
                <div class="col-xxl-4 col-lg-6">
                  <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative"><img src="/public/assets/img/tickets/reports/3.png" alt="" width="39">
                    <div class="ms-3 my-x1">
                      <h5 class="fs-9 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Analysis of the Top Customers</a></h5>
                      <h6 class="mb-0 text-600">Check out our customer stories</h6>
                    </div>
                  </div>
                </div>
                <div class="col-xxl-4 col-lg-6">
                  <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative"><img src="/public/assets/img/tickets/reports/4.png" alt="" width="39">
                    <div class="ms-3 my-x1">
                      <h5 class="fs-9 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Customer Satisfaction Survey</a></h5>
                      <h6 class="mb-0 text-600">Check out the report details</h6>
                    </div>
                  </div>
                </div>
              </div>
              <h5 class="fs-9 mb-2 mt-5">Productivity</h5>
              <div class="row g-3">
                <div class="col-xxl-4 col-lg-6">
                  <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative"><img src="/public/assets/img/tickets/reports/5.png" alt="" width="39">
                    <div class="ms-3 my-x1">
                      <h5 class="fs-9 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Performance of Agents</a></h5>
                      <h6 class="mb-0 text-600">Check out the report details</h6>
                    </div>
                  </div>
                </div>
                <div class="col-xxl-4 col-lg-6">
                  <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative"><img src="/public/assets/img/tickets/reports/6.png" alt="" width="39">
                    <div class="ms-3 my-x1">
                      <h5 class="fs-9 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Performance in a Group</a></h5>
                      <h6 class="mb-0 text-600">Check out the report details</h6>
                    </div>
                  </div>
                </div>
                <div class="col-xxl-4 col-lg-6">
                  <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative"><img src="/public/assets/img/tickets/reports/7.png" alt="" width="39">
                    <div class="ms-3 my-x1">
                      <h5 class="fs-9 fw-semi-bold mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Distribution of Performance</a></h5>
                      <h6 class="mb-0 text-600">Check out the report details</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="card mt-3">
            <div class="card-header border-bottom border-200">
              <h6 class="mb-0">Team</h6>
            </div>
            <div class="card-body">
              <div class="row gx-3">
                <div class="col-xxl-4 col-md-6">
                  <div class="hover-bg-100 py-x1 text-center rounded-3 position-relative">
                    <div class="icon-item icon-item-xl shadow-none mx-auto mt-x1 bg-primary-subtle"><span class="text-primary" data-feather="user"></span></div>
                    <h5 class="mt-3 mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Agents</a></h5>
                    <h6 class="w-75 mx-auto text-600 mb-x1">Define agents' scope of work, type, language, and other details.</h6>
                  </div>
                </div>
                <div class="col-xxl-4 col-md-6">
                  <div class="hover-bg-100 py-x1 text-center rounded-3 position-relative">
                    <div class="icon-item icon-item-xl shadow-none mx-auto mt-x1 bg-info-subtle"><span class="text-info" data-feather="users"></span></div>
                    <h5 class="mt-3 mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Groups</a></h5>
                    <h6 class="w-75 mx-auto text-600 mb-x1">Agents can be organized and unattended tickets can be notified.</h6>
                  </div>
                </div>
                <div class="col-xxl-4 col-md-6">
                  <div class="hover-bg-100 py-x1 text-center rounded-3 position-relative">
                    <div class="icon-item icon-item-xl shadow-none mx-auto mt-x1 bg-warning-subtle"><span class="text-warning" data-feather="git-pull-request"></span></div>
                    <h5 class="mt-3 mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Roles</a></h5>
                    <h6 class="w-75 mx-auto text-600 mb-x1">Provide agents with fine-grained access and privileges.</h6>
                  </div>
                </div>
                <div class="col-xxl-4 col-md-6">
                  <div class="hover-bg-100 py-x1 text-center rounded-3 position-relative">
                    <div class="icon-item icon-item-xl shadow-none mx-auto mt-x1 bg-success-subtle"><span class="text-success" data-feather="clock"></span></div>
                    <h5 class="mt-3 mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Working Hours</a></h5>
                    <h6 class="w-75 mx-auto text-600 mb-x1">To set client expectations, define operating hours and holidays.</h6>
                  </div>
                </div>
                <div class="col-xxl-4 col-md-6">
                  <div class="hover-bg-100 py-x1 text-center rounded-3 position-relative">
                    <div class="icon-item icon-item-xl shadow-none mx-auto mt-x1 bg-primary-subtle"><span class="text-primary" data-feather="briefcase"></span></div>
                    <h5 class="mt-3 mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Skills</a></h5>
                    <h6 class="w-75 mx-auto text-600 mb-x1">Assign kinds of tickets to agents based on their expertise.</h6>
                  </div>
                </div>
                <div class="col-xxl-4 col-md-6">
                  <div class="hover-bg-100 py-x1 text-center rounded-3 position-relative">
                    <div class="icon-item icon-item-xl shadow-none mx-auto mt-x1 bg-info-subtle"><span class="text-info" data-feather="repeat"></span></div>
                    <h5 class="mt-3 mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Agent Changes</a></h5>
                    <h6 class="w-75 mx-auto text-600 mb-x1">Create and manage agent schedules all in one spot.</h6>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="card mt-3">
            <div class="card-header border-bottom border-200">
              <h6 class="mb-0">Account</h6>
            </div>
            <div class="card-body">
              <div class="row gx-3">
                <div class="col-xxl-4 col-md-6">
                  <div class="hover-bg-100 py-x1 text-center rounded-3 position-relative">
                    <div class="icon-item icon-item-xl shadow-none mx-auto mt-x1 bg-primary-subtle"><span class="text-primary" data-feather="user-check"></span></div>
                    <h5 class="mt-3 mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Account Information</a></h5>
                    <h6 class="w-75 mx-auto text-600 mb-x1">View the status of your account as well as your invoice email address.</h6>
                  </div>
                </div>
                <div class="col-xxl-4 col-md-6">
                  <div class="hover-bg-100 py-x1 text-center rounded-3 position-relative">
                    <div class="icon-item icon-item-xl shadow-none mx-auto mt-x1 bg-info-subtle"><span class="text-info" data-feather="file-text"></span></div>
                    <h5 class="mt-3 mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Billing &amp; Plans</a></h5>
                    <h6 class="w-75 mx-auto text-600 mb-x1">Plan, add-ons, team size, and billing cycle are all under your control.</h6>
                  </div>
                </div>
                <div class="col-xxl-4 col-md-6">
                  <div class="hover-bg-100 py-x1 text-center rounded-3 position-relative">
                    <div class="icon-item icon-item-xl shadow-none mx-auto mt-x1 bg-warning-subtle"><span class="text-warning" data-feather="sunrise"></span></div>
                    <h5 class="mt-3 mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Passes for the day</a></h5>
                    <h6 class="w-75 mx-auto text-600 mb-x1">Part-time agents can purchase on-demand licenses.</h6>
                  </div>
                </div>
                <div class="col-xxl-4 col-md-6">
                  <div class="hover-bg-100 py-x1 text-center rounded-3 position-relative">
                    <div class="icon-item icon-item-xl shadow-none mx-auto mt-x1 bg-success-subtle"><span class="text-success" data-feather="lock"></span></div>
                    <h5 class="mt-3 mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Security</a></h5>
                    <h6 class="w-75 mx-auto text-600 mb-x1">Advanced SSO settings, password policy, and domain restriction.</h6>
                  </div>
                </div>
                <div class="col-xxl-4 col-md-6">
                  <div class="hover-bg-100 py-x1 text-center rounded-3 position-relative">
                    <div class="icon-item icon-item-xl shadow-none mx-auto mt-x1 bg-primary-subtle"><span class="text-primary" data-feather="hexagon"></span></div>
                    <h5 class="mt-3 mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Log of Audits</a></h5>
                    <h6 class="w-75 mx-auto text-600 mb-x1">All of the changes made to your falcon Support tickets account.</h6>
                  </div>
                </div>
                <div class="col-xxl-4 col-md-6">
                  <div class="hover-bg-100 py-x1 text-center rounded-3 position-relative">
                    <div class="icon-item icon-item-xl shadow-none mx-auto mt-x1 bg-info-subtle"><span class="text-info" data-feather="alert-circle"></span></div>
                    <h5 class="mt-3 mb-2"><a class="text-900 hover-primary stretched-link" href="#!">Configure the Helpdesk</a></h5>
                    <h6 class="w-75 mx-auto text-600 mb-x1">Your Falcon Support Tickets will be personalized.</h6>
                  </div>
                </div>
              </div>
            </div>
          </div>
          


        </div>
        <div class="modal fade" id="authentication-modal" tabindex="-1" role="dialog" aria-labelledby="authentication-modal-label" aria-hidden="true">
          <div class="modal-dialog mt-6" role="document">
            <div class="modal-content border-0">
              <div class="modal-header px-5 position-relative modal-shape-header bg-shape">
                <div class="position-relative z-1">
                  <h4 class="mb-0 text-white" id="authentication-modal-label">Register</h4>
                  <p class="fs-10 mb-0 text-white">Please create your free Falcon account</p>
                </div>
                <button class="btn-close position-absolute top-0 end-0 mt-2 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body py-4 px-5">
                <form>
                  <div class="mb-3">
                    <label class="form-label" for="modal-auth-name">Name</label>
                    <input class="form-control" type="text" autocomplete="on" id="modal-auth-name">
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="modal-auth-email">Email address</label>
                    <input class="form-control" type="email" autocomplete="on" id="modal-auth-email">
                  </div>
                  <div class="row gx-2">
                    <div class="mb-3 col-sm-6">
                      <label class="form-label" for="modal-auth-password">Password</label>
                      <input class="form-control" type="password" autocomplete="on" id="modal-auth-password">
                    </div>
                    <div class="mb-3 col-sm-6">
                      <label class="form-label" for="modal-auth-confirm-password">Confirm Password</label>
                      <input class="form-control" type="password" autocomplete="on" id="modal-auth-confirm-password">
                    </div>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="modal-auth-register-checkbox">
                    <label class="form-label" for="modal-auth-register-checkbox">I accept the <a href="#!">terms </a>and <a class="white-space-nowrap" href="#!">privacy policy</a></label>
                  </div>
                  <div class="mb-3">
                    <button class="btn btn-primary d-block w-100 mt-3" type="submit" name="submit">Register</button>
                  </div>
                </form>
                <div class="position-relative mt-5">
                  <hr>
                  <div class="divider-content-center">or register with</div>
                </div>
                <div class="row g-2 mt-2">
                  <div class="col-sm-6"><a class="btn btn-outline-google-plus btn-sm d-block w-100" href="#"><span class="fab fa-google-plus-g me-2" data-fa-transform="grow-8"></span> google</a></div>
                  <div class="col-sm-6"><a class="btn btn-outline-facebook btn-sm d-block w-100" href="#"><span class="fab fa-facebook-square me-2" data-fa-transform="grow-8"></span> facebook</a></div>
                </div>
              </div>
              </div>
    <!-- ===============================================-->
    <!--    End of Main Content-->
    <!-- ===============================================-->
    </div>
              </div>   

  <?PHP
  $display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();