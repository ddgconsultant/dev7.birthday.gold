<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page configuration
$pagetitle = 'Celebration Tours';

$additionalstyles = '
<style>
@media print {
    body * {
        display: none;
    }

    #printContainer {
        display: block;
    }
}

.tour-container {
    max-width: 1200px;
    margin: 0 auto;
}

.accordion-button {
    font-weight: 500;
}

.accordion-button.collapsed {
    background-color: transparent;
}

.sortable_item img {
    border-radius: 4px;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-map me-3"></i>Your Celebration Tours</h1>
            <p class="lead mb-0">Plan your birthday celebration route and visit all your enrolled businesses</p>
        </div>
    </div>
</div>

<div class="container my-5 pt-5">
    <div class="tour-container">
        
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
            
            // Display upcoming tours section
            echo '
            <!-- Upcoming Tours -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Tour Management</h5>
                <a href="/myaccount/tour" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Build A Tour
                </a>
            </div>
            <div class="card m-0 p-0 mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Upcoming Tours <span class="badge bg-primary">' . count($upcoming_dates) . '</span></h5>
                </div>

                <div class="card-body">';
                    
            if (empty($upcoming_tours)) {
                echo '<p class="text-muted">No upcoming tours scheduled.</p>';
            } else {
                echo '<div class="accordion" id="accordionUpcoming">';
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
                            
                echo '</div>'; // Close accordion
            }
            
            echo '</div></div>'; // Close card-body and card
            
            // Display past tours section
            echo '
            <!-- Past Tours -->
            <div class="card m-0 p-0">
                <div class="card-header">
                    <h5 class="mb-0">Past Tours <span class="badge bg-secondary">' . count($past_dates) . '</span></h5>
                </div>

                <div class="card-body">';
                    
            if (empty($past_tours)) {
                echo '<p class="text-muted">No past tours.</p>';
            } else {
                echo '<div class="accordion" id="accordionPast">';
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
                            
                echo '</div>'; // Close accordion
            }
            
            echo '</div></div>'; // Close card-body and card
            
            echo '</div>'; // Close row
            ?>
        </div>
    </div>
</div>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
