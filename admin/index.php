<?php
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 


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



<?PHP

include($dir['core_components'] . '/bg_admin_leftpanel.inc');
?>

<div class="container-fluid main-content">

  <div class="row">
    <div class="col-12">

    <?PHP
echo '
<div class="card">
    <div class="card-header bg-body-tertiary">
        <h6 class="mb-0">Admin Functions</h6>
    </div>
    <div class="card-body">
        <h5 class="fs-9 mb-2">Productivity</h5>
        <div class="row g-3">
            <div class="col-xxl-4 col-lg-6">
                <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative">
                    <img src="/public/assets/img/tickets/reports/1.png" alt="" width="40">
                    <div class="ms-3 my-x1">
                        <h5 class="fs-9 fw-semi-bold mb-2">
                            <a class="text-900 hover-primary stretched-link" href="/admin/accessmanager">Access Manager</a>
                        </h5>
                        <h6 class="mb-0 text-600">Manage access and permissions</h6>
                    </div>
                </div>
            </div>
            <div class="col-xxl-4 col-lg-6">
                <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative">
                    <img src="/public/images/system_icons/io.leantime.cloudronapp.png" alt="" width="40">
                    <div class="ms-3 my-x1">
                        <h5 class="fs-9 fw-semi-bold mb-2">
                            <a class="text-900 hover-primary stretched-link" href="/admin/redirect-leantime">Leantime</a>
                        </h5>
                        <h6 class="mb-0 text-600">Overview of ticket trends</h6>
                    </div>
                </div>
            </div>

                <div class="col-xxl-4 col-lg-6">
                <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative">
                    <img src="/public/images/system_icons/pageeditor-icon.png" alt="" width="40">
                    <div class="ms-3 my-x1">
                        <h5 class="fs-9 fw-semi-bold mb-2">
                            <a class="text-900 hover-primary stretched-link" href="/admin/manage-pageeditor">Page Editor</a>
                        </h5>
                        <h6 class="mb-0 text-600">Toggle Page Editor feature</h6>
                    </div>
                </div>
            </div>
        </div>


        <h5 class="fs-9 mb-2 mt-5">User</h5>
        <div class="row g-3">
            <div class="col-xxl-4 col-lg-6">
                <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative">
                    <img src="/public/assets/img/tickets/reports/3.png" alt="" width="40">
                    <div class="ms-3 my-x1">
                        <h5 class="fs-9 fw-semi-bold mb-2">
                            <a class="text-900 hover-primary stretched-link" href="/admin/user-list">User List</a>
                        </h5>
                        <h6 class="mb-0 text-600">Manage users and permissions</h6>
                    </div>
                </div>
            </div>
            <div class="col-xxl-4 col-lg-6">
                <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative">
                    <img src="/public/assets/img/tickets/reports/3.png" alt="" width="40">
                    <div class="ms-3 my-x1">
                        <h5 class="fs-9 fw-semi-bold mb-2">
                            <a class="text-900 hover-primary stretched-link" href="/admin/redirect-enrollments">Enrollments</a>
                        </h5>
                        <h6 class="mb-0 text-600">Manage Pending Enrollments</h6>
                    </div>
                </div>
            </div>

            <div class="col-xxl-4 col-lg-6">
                <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative">
                    <img src="/public/assets/img/tickets/reports/4.png" alt="" width="40">
                    <div class="ms-3 my-x1">
                        <h5 class="fs-9 fw-semi-bold mb-2">
                            <a class="text-900 hover-primary stretched-link" href="/admin/customer-satisfaction">Customer Satisfaction Survey</a>
                        </h5>
                        <h6 class="mb-0 text-600">Review customer feedback</h6>
                    </div>
                </div>
            </div>
        </div>
        <h5 class="fs-9 mb-2 mt-5">System</h5>
        <div class="row g-3 mb-5">
            <div class="col-xxl-4 col-lg-6">
                <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative">
                    <img src="/public/images/system_icons/com.metabase.cloudronapp.png" alt="" width="40">
                    <div class="ms-3 my-x1">
                        <h5 class="fs-9 fw-semi-bold mb-2">
                            <a class="text-900 hover-primary stretched-link" href="/admin/redirect-metabase">Metabase</a>
                        </h5>
                        <h6 class="mb-0 text-600">View system analytics</h6>
                    </div>
                </div>
            </div>
            <div class="col-xxl-4 col-lg-6">
                <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative">
                    <img src="/public/images/system_icons/io.cloudron.buildservice.png" alt="" width="40">
                    <div class="ms-3 my-x1">
                        <h5 class="fs-9 fw-semi-bold mb-2">
                            <a class="text-900 hover-primary stretched-link" href="/admin/redirect-cloudron">Cloudron</a>
                        </h5>
                        <h6 class="mb-0 text-600">Manage cloud services</h6>
                    </div>
                </div>
            </div>
            <div class="col-xxl-4 col-lg-6">
                <div class="d-flex align-items-center px-4 py-x1 bg-body-tertiary rounded-3 border position-relative">
                    <img src="/public/assets/logos/netdata.png" alt="" width="40">
                    <div class="ms-3 my-x1">
                        <h5 class="fs-9 fw-semi-bold mb-2">
                            <a class="text-900 hover-primary stretched-link" href="/admin/redirect-netdata">System Performance</a>
                        </h5>
                        <h6 class="mb-0 text-600">Review infrastructure performance metrics</h6>
                    </div>
                </div>
            </div>
        </div>

';


$accountlinks_display=true;
$accountlinkspresentation = '';
include($dir['core_components'] . '/user_accountlinks.inc');
if ($accountlinks_display !== false) {

  echo $accountlinks_output;
 
}
?>

</div>
</div>
</div></div></div>
</div></div></div>
<?PHP
  $display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
