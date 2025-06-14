<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Assuming $user_plan is retrieved from the user's session or database
// Example: $user_plan = 'gold';


#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------

$plans = ['free', 'gold', 'lifetime'];
$user_plan = 'gold'; // This should be dynamically set based on the actual user's plan

$outputm='';



#-------------------------------------------------------------------------------
# HANDLE THE PROFILE UPDATE ATTEMPT
#-------------------------------------------------------------------------------

if ($app->formposted()) {
   if (isset($_POST['feature_id'])) {
    $feature_id = $_POST['feature_id'];
    $feature_value = $_POST['feature_value'];
    
    // Update the database with the new value
    $sql = 'UPDATE bg_product_features SET value = :value WHERE id = :id';
    $stmt = $database->prepare($sql);
    $stmt->execute(['value' => $feature_value, 'id' => $feature_id]);
    
    // Optionally, reload the page to reflect changes or handle success messages
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}
}


#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------

$additionalstyles .= '
<style>
.hover-card {
    perspective: 1000px;
}

.hover-card .card {
    width: 99.5%;
    height: 200px; /* Set a fixed height for the cards */
    transition: transform 0.6s;
    transform-style: preserve-3d;
    position: relative;
}

.hover-card:hover .card {
    transform: rotateY(180deg);
}

.card-front, .card-back {
    position: absolute;
    width: 100%;
    height: 100%; /* Ensure the back face has the same height as the card */
    backface-visibility: hidden;
    border-radius: 15px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.card-front {
    background-color: #fff;
}

.card-back {
    background-color: #ddd !important;
    transform: rotateY(180deg);
}


</style>
';


$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');

echo '    
<div class="container  main-content pt-0 mt-0 mb-5">
    <div class="row">
        <div class="col">
        ';
     
// Display only the Lifetime plan details

$plandatafeatures=$app->plandetail('detailsfull_id', $current_user_data['account_product_id']);
#breakpoint($plandatafeatures);
function editbuttons($front=[], $back=[]) {
    global $account , $outputm;
    if (!$account->isadmin()) {
        return '';
    }
    $outputx = '<div class="text-end d-flex g-2 ms-auto">';

   # $outputm='';
    if (!empty($front['id'])) {
        $outputx .= '<button type="button" class="btn btn-sm btn-primary fs-12 m-0 me-2 p-0 px-1" data-bs-toggle="modal" data-bs-target="#editModal'.$front['id'].'">Edit Front</button>';

        $outputm .= generateModal($front);
    }
    if (!empty($back['id'])) {
       $outputx .= '<button type="button" class="btn btn-sm btn-primary fs-12 m-0 p-0 px-1" data-bs-toggle="modal" data-bs-target="#editModal'.$back['id'].'">Edit Back</button>';

       
       $outputm .= generateModal($back);
    }
    $outputx.='</div>';
   # breakpoint('<pre>'.$outputm.'</pre>');
    return $outputx.'';

}

function generateModal($item) {
    global $display;
    return '
    <!-- ============================================================================================================================================================= -->
    <div class="modal modal-lg fade" id="editModal'.$item['id'].'" tabindex="-1"  role="dialog" aria-labelledby="editModalLabel'.$item['id'].'" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel'.$item['id'].'">'.$item['title'].'</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="">
                    '.$display->inputcsrf_token().'
                        <input type="hidden" name="feature_id" value="'.$item['id'].'">
                        <div class="mb-3">
                            <label for="featureValue'.$item['id'].'" class="form-label">Edit Value</label>
                            <!-- Pre-populate the input field with the current value -->
                              <textarea class="form-control" id="featureValue'.$item['id'].'" name="feature_value" rows="4">'.htmlspecialchars($item['value']).'</textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    ';
}





echo '
<div class="container pt-0 mt-0 pb-5 mb-5" style="overflow: hidden;">
    <div class="mb-3">
        <h2 class="text-primary">Your Current Plan: <strong>'.$userplanname.'</strong></h2>
    </div>
    <div class="row gy-4">
        <!-- Card 1: Plan Cost -->
        <div class="col-md-6">
            <div class="hover-card">
                <div class="card">
                    <!-- Card Front -->
                    <div class="card-front card-body d-flex flex-column justify-content-center text-center bg-gradient-primary">
                        <i class="bi bi-currency-dollar fa-3x mb-3 "></i>
                        <h5 class="card-title ">Plan Cost</h5>
                        <p class="card-text "><strong>'.$plandatafeatures['plan_pricetag']['value'].'</strong></p>
                    </div>
                    <!-- Card Back -->
                    <div class="card-back p-4 text-center bg-white">
                        <p class="card-text">'.$plandatafeatures['plan_pricedescription']['value'].'</p>
                        '.editbuttons($plandatafeatures['plan_pricetag'], $plandatafeatures['plan_pricedescription']).'
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 2: Brands Registered -->
        <div class="col-md-6">
            <div class="hover-card">
                <div class="card">
                    <!-- Card Front -->
                    <div class="card-front card-body d-flex flex-column justify-content-center text-center bg-gradient-success">
                        <i class="bi bi-tags fa-3x mb-3 "></i>
                        <h5 class="card-title ">Brands Registered</h5>
                        <p class="card-text "><strong>'.$plandatafeatures['max_business_select_tag']['value'].'</strong></p>
                    </div>
                    <!-- Card Back -->
                    <div class="card-back p-4 text-center bg-white">
                        <p class="card-text">'.$plandatafeatures['max_business_select_description']['value'].'</p>
                         '.editbuttons($plandatafeatures['max_business_select_tag'], $plandatafeatures['max_business_select_description']).'
                     </div>
                </div>
            </div>
        </div>
        <!-- Card 3: Planning Tools -->
        <div class="col-md-6">
            <div class="hover-card">
                <div class="card">
                    <!-- Card Front -->
                    <div class="card-front card-body d-flex flex-column justify-content-center text-center bg-gradient-info">
                        <i class="bi bi-map fa-3x mb-3 "></i>
                        <h5 class="card-title ">Planning Tools</h5>
                        <p class="card-text ">Comprehensive Tools</p>
                    </div>
                    <!-- Card Back -->
                    <div class="card-back p-4 text-center bg-white">
                        <p class="card-text">We make celebrating easy! We can produce a Celebration Tour Schedule for you complete with a Tour Map that lays out the times and route for you to make the most of your special day. What\'s more, you can plan a birthday month-long tour. We can lay out all the brands you are signed up for day by day and hour by hour.</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 4: Alerts & Reminders -->
        <div class="col-md-6">
            <div class="hover-card">
                <div class="card">
                    <!-- Card Front -->
                    <div class="card-front card-body d-flex flex-column justify-content-center text-center bg-gradient-warning">
                        <i class="bi bi-bell fa-3x mb-3 "></i>
                        <h5 class="card-title ">Alerts & Reminders</h5>
                        <p class="card-text ">Never Miss Out!</p>
                    </div>
                    <!-- Card Back -->
                    <div class="card-back p-4 text-center bg-white">
                        <p class="card-text">We know you don\'t want to miss out on the best birthday freebies so we will send you reminders during your birthday month.</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 5: Support -->
        <div class="col-md-6">
            <div class="hover-card">
                <div class="card">
                    <!-- Card Front -->
                    <div class="card-front card-body d-flex flex-column justify-content-center text-center bg-gradient-danger">
                        <i class="bi bi-envelope fa-3x mb-3 "></i>
                        <h5 class="card-title ">Support</h5>
                        <p class="card-text "><strong>Email</strong></p>
                    </div>
                    <!-- Card Back -->
                    <div class="card-back p-4 text-center bg-white">
                        <p class="card-text">Email support available for your queries and assistance.</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 6: Social Celebration -->
        <div class="col-md-6">
            <div class="hover-card">
                <div class="card">
                    <!-- Card Front -->
                    <div class="card-front card-body d-flex flex-column justify-content-center text-center bg-gradient-secondary">
                        <i class="bi bi-people fa-3x mb-3 "></i>
                        <h5 class="card-title ">Social Celebration</h5>
                        <p class="card-text ">Connect & Celebrate</p>
                    </div>
                    <!-- Card Back -->
                    <div class="card-back p-4 text-center bg-white">
                        <p class="card-text">This future feature will allow you to connect with other users on their celebration tour and make it a big live video party.</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 7: Contests -->
        <div class="col-md-12 d-none">
            <div class="hover-card">
                <div class="card">
                    <!-- Card Front -->
                    <div class="card-front card-body d-flex flex-column justify-content-center text-center bg-gradient-dark">
                        <i class="bi bi-trophy fa-3x mb-3 "></i>
                        <h5 class="card-title ">Contests</h5>
                        <p class="card-text ">Weekly Prizes</p>
                    </div>
                    <!-- Card Back -->
                    <div class="card-back p-4 text-center bg-white">
                        <p class="card-text">We are so excited about this upcoming feature! The Gold Pot will be a weekly drawing where you are automatically entered to win the random Gold Pot Giveaway. The nice thing is, you are only competing against other "Lifetime Users" who have birthdays during the same month as you. Automatically earn double entries when it is your birthday week. Gold Pot prizes range anywhere from $50 - $500.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
</div>
</div>
</div>
</div>
</div>
';
echo $outputm;

$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
