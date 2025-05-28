<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 

$headerattribute['additionalcss']='<link rel="stylesheet" href="/public/css/myaccount.css">';
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');
?>

<div class="container-xl px-4 mt-4 flex-grow-1">


<?PHP  include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-myaccount.php'); 
$till=$app->getTimeTilBirthday($current_user_data['birthdate']);
$astrosign=$app->getastrosign($current_user_data['birthdate']);
$astroicon=$app->getZodiacInfo($astrosign);




$userstatdata=$app->admin_getuserstats();
$revenuestatdata=$app->admin_getrevenuestats();
$systemstatdata=$app->admin_getsystemstats();


$businessstatdata=$app->admin_getbusinessstats();

    echo '
    <hr class="mt-0 mb-4">
    <div class="row">

        <div class="col-xl-4">
            <!-- Profile picture card-->
            <div class="card mb-4 mb-xl-0">
                <div class="card-header">STATS: as of '.date('r').'</div>
                <div class="card-body text-center">
                  <h1>'.$userstatdata['total'].' USERS</h1>
                  <hr class="m-5">
                  <h1>$'.number_format(($revenuestatdata['total']/100),2).'</h1>
                  <hr class="m-5">
                  <h1>'.$systemstatdata['sessions_total'].' SESSIONS</h1>
                </div>
            </div>
        </div>


        <div class="col-xl-8">
        <div class="row">
';


echo '   <!-- USERS-->


        <div class="col-lg-4 mb-4">
            <!-- Billing card 2-->
            <div class="card h-100 border-start-lg border-start-secondary">
                <div class="card-body">
                    <div class="small text-muted fw-bold">Total Users</div>
                    <div class="h3">'.$userstatdata['total'].'</div>
                    <a class="text-arrow-icon small text-secondary" href="/admin/user-list">
                    Charts <i class="bi bi-graph-up-arrow"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <!-- Billing card 3-->
            <div class="card h-100 border-start-lg border-start-success">
                <div class="card-body">
                    <div class="small text-muted fw-bold">Active Users</div>
                    <div class="h3 d-flex align-items-center">'.$userstatdata['month'].'</div>
                    <a class="text-arrow-icon small text-secondary" href="/admin/user-list">
                    User List <i class="bi bi-arrow-right-square"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <!-- Billing card 2-->
            <div class="card h-100 border-start-lg border-start-secondary">
                <div class="card-body">
                    <div class="small text-muted fw-bold">Pending Users</div>
                    <div class="h3">'.$userstatdata['pending'].'</div>
                    <a class="text-arrow-icon small text-secondary" href="/admin/pendingusers">
                        Show Pending <i class="bi bi-arrow-right-square"></i>
                    </a>
                </div>
            </div>
        </div>

';


echo '
        <!-- REVENUE-->

        <div class="col-lg-4 mb-4">
            <!-- Billing card 3-->
            <div class="card h-100 border-start-lg border-start-success">
                <div class="card-body">
                    <div class="small text-muted fw-bold">Grand Total Revenue</div>
                    <div class="h3 d-flex align-items-center">$'.number_format(($revenuestatdata['total']/100),2).'</div>
                    <a class="text-arrow-icon small text-secondary" href="myaccount-admin-revenuedetails">
                    Charts <i class="bi bi-graph-up-arrow"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
        <!-- Billing card 3-->
        <div class="card h-100 border-start-lg border-start-success">
            <div class="card-body">
                <div class="small text-muted fw-bold">Revenue This Month</div>
                <div class="h3 d-flex align-items-center">$'.number_format(($revenuestatdata['month']/100),2).'</div>
                <a class="text-arrow-icon small text-secondary" href="myaccount-admin-revenuedetails">
                Details <i class="bi bi-arrow-right-square"></i>
                </a>
            </div>
        </div>
        </div>
        <div class="col-lg-4 mb-4">
        <!-- Billing card 3-->
        <div class="card h-100 border-start-lg border-start-success">
            <div class="card-body">
                <div class="small text-muted fw-bold">Revenue Today</div>
                <div class="h3 d-flex align-items-center">$'.number_format(($revenuestatdata['today']/100),2).'</div>
                <a class="text-arrow-icon small text-secondary" href="myaccount-admin-revenuedetails">
                    Details <i class="bi bi-arrow-right-square"></i>
                </a>
            </div>
        </div>
    </div>

    ';



    echo '   <!-- BUSINESSES-->

    <div class="col-lg-4 mb-4">
        <!-- Billing card 2-->
        <div class="card h-100 border-start-lg border-start-secondary">
            <div class="card-body">
                <div class="small text-muted fw-bold">Total Businesses</div>
                <div class="h3">'.$businessstatdata['total'].'</div>
                <a class="text-arrow-icon small text-secondary" href="/admin/user-list">
                Charts <i class="bi bi-graph-up-arrow"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <!-- Billing card 3-->
        <div class="card h-100 border-start-lg border-start-success">
            <div class="card-body">
                <div class="small text-muted fw-bold">Live Businesses</div>
                <div class="h3 d-flex align-items-center">'.$businessstatdata['status_finalized'].'</div>
                <a class="text-arrow-icon small text-secondary" href="/admin/user-list">
                Business List <i class="bi bi-arrow-right-square"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <!-- Billing card 2-->
        <div class="card h-100 border-start-lg border-start-secondary">
            <div class="card-body">
                <div class="small text-muted fw-bold">Pending Businesses</div>
                <div class="h3">'.$businessstatdata['status_pending'].'</div>
                <a class="text-arrow-icon small text-secondary" href="/admin/pendingusers">
                    Processing Pending <i class="bi bi-arrow-right-square"></i>
                </a>
            </div>
        </div>
    </div>

';




echo '   <!-- SITE DATA-->

<div class="col-lg-4 mb-4">
    <!-- Billing card 2-->
    <div class="card h-100 border-start-lg border-start-secondary">
        <div class="card-body">
            <div class="small text-muted fw-bold">Days Live</div>
            <div class="h3">'.$systemstatdata['days_live'].'</div>
            <a class="text-arrow-icon small text-secondary" href="/admin/user-list">
            Charts <i class="bi bi-graph-up-arrow"></i>
            </a>
        </div>
    </div>
</div>
<div class="col-lg-4 mb-4">
    <!-- Billing card 3-->
    <div class="card h-100 border-start-lg border-start-success">
        <div class="card-body">
            <div class="small text-muted fw-bold">Sessions This Month</div>
            <div class="h3 d-flex align-items-center">'.$systemstatdata['sessions_month'].'</div>
            <a class="text-arrow-icon small text-secondary" href="/admin/user-list">
            Show Stats <i class="bi bi-arrow-right-square"></i>
            </a>
        </div>
    </div>
</div>

<div class="col-lg-4 mb-4">
    <!-- Billing card 2-->
    <div class="card h-100 border-start-lg border-start-secondary">
        <div class="card-body">
            <div class="small text-muted fw-bold">Page Hits</div>
            <div class="h4">'.$systemstatdata['pagehits_total'].' / '.$systemstatdata['pagehits_month'].' / '.$systemstatdata['pagehits_day'].'</div>
            <a class="text-arrow-icon small text-secondary" href="/admin/pendingusers">
                Show Stats <i class="bi bi-arrow-right-square"></i>
            </a>
        </div>
    </div>
</div>

';



    echo '
    </div>
        </div>
    </div>
    </div>
<div class="row py-5 my-5"></div>
';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.php');
