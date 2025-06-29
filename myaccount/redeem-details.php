<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------





$bodycontentclass = '';

$additionalstyles .= '
<style>
/* Reward details page styles */
.reward-detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.reward-detail-title {
    font-size: 2.5rem;
    font-weight: 400;
    color: #212529;
    margin: 0;
}

.reward-card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border-radius: 0.5rem;
}

.reward-logo {
    max-height: 150px;
    object-fit: contain;
}

.info-badge {
    font-size: 1rem;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
}

.info-section h5 {
    font-size: 1rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.redeem-section {
    background-color: #f8f9fa;
    padding: 2rem;
    border-radius: 0.5rem;
    margin-bottom: 2rem;
}

@media (max-width: 767px) {
    .reward-detail-title {
        font-size: 2rem;
    }
    
    .row.g-5 {
        --bs-gutter-x: 1rem;
        --bs-gutter-y: 2rem;
    }
}
</style>
';

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
        <div class="col-md-9 col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 style="font-size: 2.5rem; font-weight: 400;">Reward Details</h1>
                <a href="/myaccount/redeem" class="btn btn-primary">Back to Rewards</a>
            </div>

            <div class="row mt-0">
                <div class="col mt-0">
                    <div class="card reward-card">
                        <div class="card-body pt-4 pb-5">
                            <div class="text-center mb-4">
                                ' . $availability_tag['availability']. '
                                <img src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" alt="' . htmlspecialchars($company['company_name']) . ' Logo" class="reward-logo mb-3">
                                <h2 class="h1 mb-2">' . htmlspecialchars($company['company_name']) . '</h2>
                                <p class="text-muted fs-5">' . htmlspecialchars($company['spinner_description'] ?? 'Enjoy your ' . $company['category'] . ' reward') . '</p>
                            </div>
                            <hr class="my-4">
                            <div class="row g-5">
                                <div class="col-md-8">
                                    <div class="redeem-section">
                                        <h3 class="mb-3">How to Redeem</h3>
                                        <p class="mb-0">' . nl2br(htmlspecialchars($company['redeem_instructions'])) . '</p>
                                    </div>
                                </div>
                                <div class="col-md-4 info-section">
                                    <h5>Expiration Date</h5>';
                                    
                                    if (!empty($company['expiration_date'])) {
                                        $expiration_date = new DateTime($company['expiration_date']);
                                        echo '<p class="badge bg-primary text-white">' . $expiration_date->format('M j, Y') . '</p>';
                                    } else {
                                        echo '<p class="badge bg-light text-white">Never</p>';
                                    }
    
                                    echo '<h5 class="mt-4">Reward Value</h5>';
                                    if (!empty($company['reward_value'])) {
                                        echo '<p class="badge bg-success text-white">$' . number_format($company['reward_value'], 2) . '</p>';
                                    } else {
                                        echo '<p class="badge bg-secondary text-white">N/A</p>';
                                    }
    
                                    echo '<h5 class="mt-4">Cash Value</h5>';
                                    if (!empty($company['cash_value'])) {
                                        echo '<p class="badge bg-success text-white">$' . number_format($company['cash_value'], 2) . '</p>';
                                    } else {
                                        echo '<p class="badge bg-secondary text-white">N/A</p>';
                                    }
    
                                    echo '<h5 class="mt-4">Requirements</h5>';
                                    if (!empty($company['requirements'])) {
                                        echo '<p class="text-muted">' . nl2br(htmlspecialchars($company['requirements'])) . '</p>';
                                    } else {
                                        echo '<p class="text-muted">None</p>';
                                    }
    
                                    echo '<h5 class="mt-4">Age</h5>';
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
                                <a href="' . htmlspecialchars($company['info_url']) . '" target="_blank" class="btn btn-success btn-lg">
                                    <i class="bi bi-globe me-2"></i> Redeem Now
                                </a>
                              '.$app->mapsearchlink($company, $current_user_data, 'googlefindlocation').'
                    
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div> <!-- close col-md-9 -->
        </div> <!-- close row -->
        </div> <!-- close container -->
        </div> <!-- close main-content -->';
    
    } else {
        // Reward not found
        echo '<div class="col-md-9 col-lg-9"><div class="alert alert-danger text-center my-5">Reward not found.</div></div>
        </div> <!-- close row -->
        </div> <!-- close container -->
        </div> <!-- close main-content -->';
    }
} else {
    // Invalid ID
    echo '<div class="col-md-9 col-lg-9"><div class="alert alert-danger text-center my-5">Invalid reward ID: '.$reward_id.'</div></div>
    </div> <!-- close row -->
    </div> <!-- close container -->
    </div> <!-- close main-content -->';
}
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
