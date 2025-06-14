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


$additionalstyles.="
<style>
.flip-card {
background-color: transparent;
width: 100%;
height: 300px; /* Set a fixed height for the cards */
perspective: 1000px;
}

.flip-card-inner {
position: relative;
width: 100%;
height: 100%;
text-align: center;
transition: transform 0.6s;
transform-style: preserve-3d;
}

.flip-card:hover .flip-card-inner,
.flip-card.flipped .flip-card-inner {
transform: rotateY(180deg);
}

.flip-card-front, .flip-card-back {
position: absolute;
width: 100%;
height: 100%;
-webkit-backface-visibility: hidden;
backface-visibility: hidden;
border-radius: 15px;
overflow: hidden;
display: flex;
flex-direction: column;
justify-content: center;
align-items: center;
}

.flip-card-front {
background-color: #fff;
}

.flip-card-back {
background-color: #f8f9fa;
transform: rotateY(180deg);
}

.scaled-card-text {
    font-size: 1rem;
    line-height: 1.4;
    text-align: justify;
    overflow-wrap: break-word;
}

.scaled-card-text.shrink {
    font-size: clamp(0.8rem, 1vw, 1rem) !important;
    line-height: 1.2 !important;
}

</style>
";

        

$results = $account->getbusinesslist_rewards($current_user_data, 'card', '"success", "success-btn"', 10, true);
if (!empty($results)) {
$show_rewards = true;
} else {
$show_rewards = false;
}

    echo '
<div class="container main-content">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="mb-0">🎉 Redeem Your Rewards 🎉
         <!--     <span class="badge rounded-pill bg-success fs-3"></span>-->
        </h1>'.
        ($show_rewards ? '<a href="/myaccount/redeem-list" class="btn btn-primary">View All Rewards</a>' : '') . '
    </div>
'.($show_rewards ? '<p class="">Click on a reward to see how to redeem it!</p>' : '').'


    <div class="row">
';


 
    if (!$show_rewards) {
        echo '
        <div class="col-12 text-center">
            <p class="h3 mt-5 text-muted">You currently have no rewards<br>
            as of '. date('l, F j, Y g:i A').'</p>
        </div></div>
        ';
    } else {
        foreach ($results as $company) {
            // Determine if the reward is available now or in the future
            $availability_tag = $app->getAvailabilityTag($company['availability_from_date'], $company['expiration_date']);

    
            $scaled_class = (strlen($company['redeem_instructions']) > 200) ? 'shrink' : ''; // Adjust based on length or any other condition
            echo '
            <!--  Flip Card ' . $company['company_name'] . ' -->
            <div class="col-md-6 mb-5">
                <div class="flip-card">
                    <div class="flip-card-inner">
                        <!-- Front of the card -->
                        <div class="flip-card-front card shadow-lg">
                            <div class="card-img-top position-relative">
                                ' . $availability_tag['availability']. '
                                <img src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" alt="' . htmlspecialchars($company['company_name']) . ' Logo" style="height: 150px; object-fit: cover;">
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">' . $company['company_name'] . '</h5>
                                <p class="card-text text-success"><strong>' . ucfirst($company['spinner_description'] ?? 'Enjoy your ' . $company['category'] . ' reward') . '</strong></p>
                            </div>
                        </div>
                        <!-- Back of the card -->
                        <div class="flip-card-back card shadow-lg h-100">
                            <div class="card-body d-flex flex-column container">
                                <h5 class="card-title bg-info-subtle mb-4 mt-0 py-2">How to Redeem</h5>
                                <p class="card-text"><span class="scaled-card-text ' . $scaled_class . '">' . $company['redeem_instructions'] . '</span></p>
                                <div class="mt-auto d-flex justify-content-between">
                                   '.$app->mapsearchlink($company, $current_user_data).'
                                    <a href="/myaccount/redeem-details?id=' .$qik->encodeId($company['reward_id']) . '" class="btn btn-lg btn-success d-flex align-items-center">
                                            ' . $availability_tag['redeembuttontext']. '
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            ';
            
        }
    
        echo '
        </div>
    
        <div class="text-center mt-5">
            <a href="/myaccount/redeem-list" class="btn btn-primary btn-lg px-5 py-3">
                <i class="fas fa-list"></i> View All Your Rewards
            </a>
        </div>
        ';
    }
    
?>
</div></div></div></div>
<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();