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
        <h1 class="mb-4">Your Celebration Tours</h1>
        
        <div class="row">
            
            <?PHP
            $user_id = $current_user_data['user_id'];
            $currentDate = new DateTime();
            
            // Get all tours
            $stmt = $database->prepare("SELECT * FROM bg_user_tours WHERE user_id = :user_id and status='active' order by calendar_dt desc");
            $stmt->execute([':user_id' => $user_id]);
            $all_tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Separate tours into upcoming and past
            $upcoming_tours = [];
            $past_tours = [];
            
            foreach ($all_tours as $tour) {
                $tourDate = new DateTime($tour['calendar_dt']);
                if ($tourDate >= $currentDate) {
                    $upcoming_tours[] = $tour;
                } else {
                    $past_tours[] = $tour;
                }
            }
            
            // Count tours by date
            $upcoming_dates = [];
            $past_dates = [];
            
            foreach ($upcoming_tours as $tour) {
                if (!isset($upcoming_dates[$tour['calendar_dt']])) {
                    $upcoming_dates[$tour['calendar_dt']] = 0;
                }
                $upcoming_dates[$tour['calendar_dt']]++;
            }
            
            foreach ($past_tours as $tour) {
                if (!isset($past_dates[$tour['calendar_dt']])) {
                    $past_dates[$tour['calendar_dt']] = 0;
                }
                $past_dates[$tour['calendar_dt']]++;
            }
            
            // Debug: Show what we're counting
            if (isset($_GET['debug'])) {
                echo '<pre>Total tours: ' . count($all_tours) . '</pre>';
                echo '<pre>Upcoming tours: ' . count($upcoming_tours) . '</pre>';
                echo '<pre>Past tours: ' . count($past_tours) . '</pre>';
                echo '<pre>Unique upcoming dates: ' . count($upcoming_dates) . '</pre>';
                echo '<pre>Unique past dates: ' . count($past_dates) . '</pre>';
                echo '<pre>Upcoming dates detail: ' . print_r($upcoming_dates, true) . '</pre>';
                echo '<pre>Past dates detail: ' . print_r($past_dates, true) . '</pre>';
            }
            ?>
            
            <!-- Upcoming Tours -->
            <div class="card m-0 p-0 mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Upcoming Tours <span class="badge bg-primary"><?php echo count($upcoming_dates); ?></span></h5>
                </div>

                <div class="card-body">
                    <?php if (empty($upcoming_tours)) { ?>
                        <p class="text-muted">No upcoming tours scheduled.</p>
                    <?php } else { ?>
                        <div class="accordion" id="accordionUpcoming">
                            <?PHP
                            $first_upcoming = true;
                            $processed_dates = [];
                            
                            foreach ($upcoming_dates as $date => $count) {
                                $expanded = $first_upcoming ? 'true' : 'false';
                                $expanded_show = $first_upcoming ? 'show' : '';
                                $collapsed = $first_upcoming ? '' : 'collapsed';
                                $first_upcoming = false;
                                
                                $formattedDate = date("l, F j, Y", strtotime($date));
                                
                                echo '
                                <div class="accordion-item p-1">
                                    <h2 class="accordion-header d-flex align-items-center justify-content-between bg-light">
                                        <button class="accordion-button ' . $collapsed . ' bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUpcoming' . $date . '" aria-expanded="' . $expanded . '" aria-controls="collapseUpcoming' . $date . '">
                                            Tour Date: <b class="ps-2">' . $formattedDate . '</b> <span class="badge bg-secondary ms-2">' . $count . ' businesses</span>
                                        </button>
                                        <a class="button btn btn-primary m-2" href="/myaccount/tour?date=' . $date . '">Map</a>
                                    </h2>
                                    <div id="collapseUpcoming' . $date . '" class="accordion-collapse collapse ' . $expanded_show . '" data-bs-parent="#accordionUpcoming">
                                        <div class="accordion-body">';
                                
                                // Display ALL companies for this date
                                foreach ($upcoming_tours as $tour) {
                                    if ($tour['calendar_dt'] == $date) {
                                        $item_company = $app->getcompany($tour['company_id']);
                                        if (!empty($item_company)) {
                                            if (!empty($item_company['address'])) {
                                                $companyaddress = $item_company['address'] . ', ' . $item_company['city'] . ', ' . $item_company['state'] . '  ' . $item_company['zip_code'];
                                            } else {
                                                $companyaddress = $current_user_data['profile_city'] . ', ' . $current_user_data['profile_state'] . '  ' . $current_user_data['profile_zip_code'];
                                            }
                                            
                                            echo '
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
                                            </div>';
                                        }
                                    }
                                }
                                
                                echo '</div></div></div>';
                            }
                            ?>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <!-- Past Tours -->
            <div class="card m-0 p-0">
                <div class="card-header">
                    <h5 class="mb-0">Past Tours <span class="badge bg-secondary"><?php echo count($past_dates); ?></span></h5>
                </div>

                <div class="card-body">
                    <?php if (empty($past_tours)) { ?>
                        <p class="text-muted">No past tours.</p>
                    <?php } else { ?>
                        <div class="accordion" id="accordionPast">
                            <?PHP
                            foreach ($past_dates as $date => $count) {
                                $expanded = 'false';
                                $expanded_show = '';
                                $collapsed = 'collapsed';
                                
                                $formattedDate = date("l, F j, Y", strtotime($date));
                                
                                echo '
                                <div class="accordion-item p-1">
                                    <h2 class="accordion-header d-flex align-items-center justify-content-between bg-light">
                                        <button class="accordion-button ' . $collapsed . ' bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePast' . $date . '" aria-expanded="' . $expanded . '" aria-controls="collapsePast' . $date . '">
                                            Tour Date: <b class="ps-2">' . $formattedDate . '</b> <span class="badge bg-secondary ms-2">' . $count . ' businesses</span>
                                        </button>
                                    </h2>
                                    <div id="collapsePast' . $date . '" class="accordion-collapse collapse ' . $expanded_show . '" data-bs-parent="#accordionPast">
                                        <div class="accordion-body">';
                                
                                // Display ALL companies for this date
                                foreach ($past_tours as $tour) {
                                    if ($tour['calendar_dt'] == $date) {
                                        $item_company = $app->getcompany($tour['company_id']);
                                        if (!empty($item_company)) {
                                            if (!empty($item_company['address'])) {
                                                $companyaddress = $item_company['address'] . ', ' . $item_company['city'] . ', ' . $item_company['state'] . '  ' . $item_company['zip_code'];
                                            } else {
                                                $companyaddress = $current_user_data['profile_city'] . ', ' . $current_user_data['profile_state'] . '  ' . $current_user_data['profile_zip_code'];
                                            }
                                            
                                            echo '
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
                                            </div>';
                                        }
                                    }
                                }
                                
                                echo '</div></div></div>';
                            }
                            ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
