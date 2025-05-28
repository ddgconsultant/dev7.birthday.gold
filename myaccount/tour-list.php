<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');

$additionalstyles .= '
<style>
@media print {
body * {
display: none;
}

#printContainer {
display: block;
}
}
</style>
';


echo '
<div class="container main-content">
    <!-- Account page navigation-->


    <div class="row d-none">
        <div class="col-lg-4 mb-4  mt-5">
            <!-- Billing card 1-->
            <div class="card h-100 border-start-lg border-start-primary">
                <div class="card-body">
                    <div class="small text-muted">Number of Tours</div>
                    <div class="h3">12</div>
                    <a class="text-arrow-icon small" href="#!">
                        Switch to yearly billing
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <!-- Billing card 2-->
            <div class="card h-100 border-start-lg border-start-secondary">
                <div class="card-body">
                    <div class="small text-muted">Number of '.ucfirst($website['biznames']).' Enrolled</div>
                    <div class="h3">10</div>
                    <a class="text-arrow-icon small text-secondary" href="/myccount/enrollment-history">
                        View enrollment history
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <!-- Billing card 3-->
            <div class="card h-100 border-start-lg border-start-success">
                <div class="card-body">
                    <div class="small text-muted">Actions</div>
                    <div class="h4 d-flex align-items-center">Print Map</div>
                    <div class="h4 d-flex align-items-center">Print Steps</div>
                    <a class="text-arrow-icon small text-success" href="#!">
                        Upgrade plan
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
';
?>


    <!-- Account page navigation ===============================================-->
    <div class="container  mt-5">
        <div class="row">
            <div class="card m-0 p-0">
                <div class="card-header">
                    Your Celebration Tours
                </div>

                <div class="card-body">


                    <div class="accordion" id="accordionExample">


                        <?PHP
                        $user_id = $current_user_data['user_id'];

                        $currenttour = '';
                        $stmt =  $database->prepare("SELECT * FROM bg_user_tours WHERE user_id = :user_id and status='active' order by calendar_dt desc");
                        $stmt->execute([':user_id' => $user_id]);
                        $tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($tours as $tour) {
                            $companylistoutput = '';



                            if ($currenttour != $tour['calendar_dt']) {
                                $expanded = 'false';
                                $expanded_show = '';
                                $collapsed = 'collapsed';


                                if ($currenttour != '') {
                                    #end the previous accordian

                                    echo '  
</div>
</div>
</div>
';
                                } else {
                                    $expanded = 'true';
                                    $expanded_show = 'show';
                                    $collapsed = 'collapsed';
                                }

                                $currenttour = $tour['calendar_dt'];
                                $formattedDate = date("l, F j, Y", strtotime($tour['calendar_dt']));

                                $tourDate = new DateTime($tour['calendar_dt']);
                                $currentDate = new DateTime();

                                if ($tourDate < $currentDate) {
                                    $showmap = false;
                                } else {
                                    $showmap = true;
                                }

                                echo '
<!-- Tour Accordian -->
<div class="accordion-item p-1">
<h2 class="accordion-header d-flex align-items-center justify-content-between  bg-light">

<button class="accordion-button ' . $collapsed . '  bg-light"  type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $tour['calendar_dt'] . '" aria-expanded="' . $expanded . '" aria-controls="collapse' . $tour['calendar_dt'] . '">
Tour Date: <b class="ps-2"> ' . $formattedDate . '</b>
</button>
';
                                if ($showmap) echo '   <a class="button btn  btn-primary m-2 " href="/myaccount/tour?date=' . $tour['calendar_dt'] . '">Map</a>';

                                echo '
</h2>
<div id="collapse' . $tour['calendar_dt'] . '" class="accordion-collapse collapse ' . $expanded_show . '" data-bs-parent="#accordionExample">
<div class="accordion-body">
';
                            }

                            $item_company = $app->getcompany($tour['company_id']);
                            if (!empty($item_company)) {
                                if (!empty($company['address'])) {
                                    $companyaddress = $item_company['address'] . ', ' . $item_company['city'] . ', ' . $item_company['state'] . '  ' . $item_company['zip_code'];
                                } else {
                                    $companyaddress = $current_user_data['profile_city'] . ', ' . $current_user_data['profile_state'] . '  ' . $current_user_data['profile_zip_code'];
                                }
                                $companylistoutput .= '
<!-- Other locations -->
<div class="sortable_item">
<div class="d-flex align-items-center justify-content-between px-4" data-location="' . $companyaddress . '">
<div class="d-flex align-items-center">
<img src="' . $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']) . '" style="width:32px" alt="" />  
<div class="ms-4">
<div class="small fw-bold">' . $item_company['company_name'] . '</div>
<div class="text-xs text-muted">' . $companyaddress . '</div>
</div>
</div>

</div>
<hr>
</div>
';


                                echo $companylistoutput . '
';
                            }
                        }

                        echo '  
</div>
</div>
</div>
';
                        ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
