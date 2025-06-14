<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------





$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');

// Fetch the reward ID from the URL

$reward_id = $_REQUEST['id']?? 0;
$reward_id=$qik->decodeId($reward_id ) ;

#breakpoint($reward_id);
if ($reward_id > 0) {
    // Fetch reward details from the database
 #   $reward = $account->getRewardDetails($reward_id);

    $results = $account->getbusinesslist_rewards($current_user_data, 'detail', '"success", "success-btn"', $reward_id, true);
    

    if ($results) {
        $company = $results[0];
        $availability_tag = $app->getAvailabilityTag($company['availability_from_date'], $company['expiration_date']);

        $search_address = $current_user_data['profile_mailing_address'] . ', ' . 
                          $current_user_data['profile_city'] . ', ' . 
                          $current_user_data['profile_state'] . ' ' . 
                          $current_user_data['profile_zip_code'];
    
        echo '
        <div class="container main-content mt-0 pt-0">
<div class="mt-0 pt-0 d-flex justify-content-between align-items-center">
    <h1>Reward Details</h1>
    <div>
        <a href="'.$_SERVER['HTTP_REFERER'].'" class="btn btn-primary mb-3">Return to List</a>
    </div>
</div>

            <div class="row mt-0">
                <div class="col mt-0">
                    <div class="card">
                        <div class="card-body pt-3 pb-5">
                            <div class="text-center">
                                       ' . $availability_tag['availability']. '
                  
                                <img src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" alt="' . htmlspecialchars($company['company_name']) . ' Logo" class="img-fluid mb-4" style="max-height: 200px;">
                                <h1 class="display-4 text-primary">' . htmlspecialchars($company['company_name']) . '</h1>
                                <p class="lead">' . htmlspecialchars($company['spinner_description'] ?? 'Enjoy your ' . $company['category'] . ' reward') . '</p>
                            </div>
                            <hr>
                            <div class="row g-5">
                                <div class="col-9">
                                    <h3 class="text-success">How to Redeem</h3>
                                    <p>' . nl2br(htmlspecialchars($company['redeem_instructions'])) . '</p>
                                </div>
                                <div class="col-3">
                                    <h5 class="text-info">Expiration Date</h5>';
                                    
                                    if (!empty($company['expiration_date'])) {
                                        $expiration_date = new DateTime($company['expiration_date']);
                                        echo '<p class="badge bg-primary text-white">' . $expiration_date->format('M j, Y') . '</p>';
                                    } else {
                                        echo '<p class="badge bg-light text-white">Never</p>';
                                    }
    
                                    echo '<h5 class="text-info mt-4">Reward Value</h5>';
                                    if (!empty($company['reward_value'])) {
                                        echo '<p class="badge bg-success text-white">$' . number_format($company['reward_value'], 2) . '</p>';
                                    } else {
                                        echo '<p class="badge bg-secondary text-white">N/A</p>';
                                    }
    
                                    echo '<h5 class="text-info mt-4">Cash Value</h5>';
                                    if (!empty($company['cash_value'])) {
                                        echo '<p class="badge bg-success text-white">$' . number_format($company['cash_value'], 2) . '</p>';
                                    } else {
                                        echo '<p class="badge bg-secondary text-white">N/A</p>';
                                    }
    
                                    echo '<h5 class="text-info mt-4">Requirements</h5>';
                                    if (!empty($company['requirements'])) {
                                        echo '<p class="text-muted">' . nl2br(htmlspecialchars($company['requirements'])) . '</p>';
                                    } else {
                                        echo '<p class="text-muted">None</p>';
                                    }
    
                                    echo '<h5 class="text-info mt-4">Age</h5>';
                                    if (!empty($company['minage']) || !empty($company['maxage'])) {
                                        echo '<p class="text-muted">';
                                        echo !empty($company['minage']) ? 'Min Age: ' . intval($company['minage']) : '';
                                        echo !empty($company['maxage']) && $company['maxage'] != 150 ? '<br>Max Age: ' . intval($company['maxage']) : '';
                                        echo '</p>';
                                    } else {
                                        echo '<p class="text-muted">No Age Restrictions</p>';
                                    }
    
        echo '                  </div>
                            </div>
                            <div class="text-center my-5 pt-5">
                                <a href="' . htmlspecialchars($company['info_url']) . '" target="_blank" class="btn btn-warning btn-lg px-5 py-3">
                                    <i class="bi bi-globe me-2"></i> Visit Website
                                </a>
                              '.$app->mapsearchlink($company, $current_user_data, 'googlefindlocation').'
                    
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
    

    
    } else {
        // Reward not found
        echo '<div class="container my-5"><div class="alert alert-danger text-center">Reward not found.</div></div>';
    }
} else {
    // Invalid ID
    echo '<div class="container my-5"><div class="alert alert-danger text-center">Invalid reward ID: '.$reward_id.'</div></div>';
}
echo '        </div>
            </div>
        </div>';
?>
<script>
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
</script>
<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
