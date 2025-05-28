<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



$jstag_openinstructions = '';
#-------------------------------------------------------------------------------
# HANDLE FIRST PROFILE VISIT
#-------------------------------------------------------------------------------
$response = $account->getUserAttribute($current_user_data['user_id'], 'first_profile_visit');
if (!$response) {
    $input = [
        'name' => 'first_profile_visit',
        'description' => date('Y-m-d H:i:s')
    ];
    $response = $account->setUserAttribute($current_user_data['user_id'], $input);
    $jstag_openinstructions = "$('#instructionsModal').modal('show');";
}


#-------------------------------------------------------------------------------
# USER NOT ALLOWED THIS FEATURE
#-------------------------------------------------------------------------------
$feature_email = $app->getProductFeatures($current_user_data['account_product_id'], 'feature_email');
if (!$feature_email) {
// Feature email is not enabled
session_tracking('feature_email already set - forwarding to enrollment');
header('location: /myaccount/enrollment');
exit;
}


#-------------------------------------------------------------------------------
# FEATURE EMAIL ALREADY ENABLED
#-------------------------------------------------------------------------------
if (!empty($current_user_data['feature_email'])) {
    session_tracking('feature_email already set - forwarding to enrollment');
    header('location: /myaccount/enrollment');
    exit;
}


#-------------------------------------------------------------------------------
# HANDLE THE PROFILE UPDATE ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    #breakpoint($_REQUEST);
    $emailoption = $_POST['emailOption'] ?? '';
    switch ($emailoption) {

            // ------------------------
        case 'bginbox':
            $inputEmail = $emailoption = $_POST['inputEmail'] ?? '';
            if (!empty($inputEmail)) {
                if (strpos($inputEmail, '@mybdaygold.com') !== true) $inputEmail .= '@mybdaygold.com';

                $updatefields =  [
                    'feature_email' => $inputEmail,
                    'profile_email' => $inputEmail,
                ];
                $userid = $current_user_data['user_id'];
                $result =   $account->updateSettings($userid, $updatefields);

                session_tracking('successful bginbox post - forwarding to enrollment');
                header('location: /myaccount/enrollment');
                exit;
            }
            break;

            // ------------------------
        case 'ownemail':
            session_tracking('successful ownemail post - forwarding to enrollment');
            header('location: /myaccount/enrollment');
            exit;
    }

    $transferpage['url'] = '/myaccount/createemail';
    $transferpage['message'] = $errormessage = '<div class="alert alert-danger">Hmmm... something odd happened.</div>';
    $qik->endpostpage($transferpage);
    exit;
}





#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------
$additionalstyles.= '
<link rel="stylesheet" href="/public/css/myaccount.css">

<style>
.feature {
width: 100px;  /* Set width */
height: 100px;  /* Set height */
display: flex;
align-items: center;
justify-content: center;
}

.feature i {
font-size: 48px;  /* Increase icon size */
}
.xhigh{
    height: 100px;  /* Set height */
}
</style>

<style>
  @media (max-width: 576px) {
    .small-screen-icon .feature {
      transform: scale(0.6); /* Scales the icon to 60% of its original size */
      font-size: 0.6em; /* Reduces the font size inside the icon if needed */
    }
  }
</style>


<style>
  @media (max-width: 576px) {
    #emailChoiceForm .form-control,
    #emailChoiceForm .input-group-text,
    #emailChoiceForm .btn {
      font-size: 0.875rem; /* Adjust font size for form controls and buttons */
      padding: 0.375rem 0.75rem; /* Adjust padding for form controls and buttons */
    }

    #emailChoiceForm .input-group-text {
      font-size: 0.65rem; /* Make the "@mybdaygold.com" text smaller */
      padding: 0.25rem 0.5rem; /* Adjust padding to make it more compact */
    }

    #emailChoiceForm .spinner-border {
      width: 1.5rem;
      height: 1.5rem;
      border-width: 0.2em;
    }

    #emailChoiceForm .btn.btn-primary,
    #emailChoiceForm .btn.btn-outline-secondary {
      padding: 0.375rem 0.75rem; /* Adjust button padding */
    }

    #emailChoiceForm #availability {
      font-size: 0.75rem; /* Adjust font size for availability text */
    }

    #emailChoiceForm .card-footer .form-check-label,
    #emailChoiceForm .card-footer .btn {
      font-size: 0.875rem; /* Adjust font size for labels and buttons in the card footer */
      padding: 0.375rem 0.75rem; /* Adjust padding for labels and buttons in the card footer */
    }
  }


.card-footer {
    padding: 15px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.card-body p {
    margin-bottom: 10px; /* Ensure space between paragraphs */
}


</style>

';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$paymenttag = '';
$birthdayprioritytag = '';
$titletag = 'Pick An Email';

$emailaddress = '';
if (empty($emailaddress)) {

    $emailaddress = strtolower($current_user_data['username']);
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
echo '
<!-- Welcome Start -->
<div class="container main-content px-1 ">
  <div class="container text-center ">
    <div class="row justify-content-center">
      <div class="col-12">
        <picture>
          <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f914/512.webp" type="image/webp">
          <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f914/512.gif" alt="Thinking Face Emoji" width="64" height="64">
        </picture>
        <h1>' . $titletag . '</h1>
        <h3>' . $current_user_data['first_name'] . ', this is an important decision.</h3>
        <p class="mb-5">Changing this in the future is extremely difficult and can lead to loss of enrolled benefits.</p>
      </div>
    </div>
';


echo '
    <div class="container">
      <div class="row ">';

// MYBDAYGOLD EMAIL OPTION
echo '
    <!-- MyBDAYGOLD Email Option -->
        <div class="col-lg-6 mb-5 account-type-card" data-target="#BGEMAIL">
          <div class="card bg-light border-0 h-100 d-flex flex-column">
            <div class="card-body text-center flex-grow-1 d-flex flex-column">

                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0 small-screen-icon">
                        <div class="feature bg-dark bg-gradient text-white rounded-3"><i class="bi bi-envelope-paper-heart"></i></div>
                    </div>
                    <div class="flex-grow-1 d-flex justify-content-center">
                        <h2 class="fs-5 fs-md-4 fw-bold mb-0">BDAY Gold Inbox</h2>
                    </div>
                </div>

                <p class="px-2 fs-10 fs-md-7 flex-grow-1">We provide a managed email address. This email becomes your login for all your enrollments. Reduce clutter in your personal email inbox.</p>

                <a href="#" class="secondary fw-bold emailownshow fs-9 mb-3" data-bs-toggle="modal" data-bs-target="#BGINBOxModal">Learn More <i class="bi bi-info-circle-fill ms-2"></i></a>

                <form id="emailChoiceForm" method="post" action="/myaccount/createemail" class="mt-4">
                    ' . $display->inputcsrf_token() . '
                    <div class="input-group mb-3">
                        <input type="text" class="form-control px-1 px-md-3" name="inputEmail" id="inputEmail" placeholder="Choose your Email Address" value="' . $emailaddress . '">
                        <span class="input-group-text px-1 px-md-3">@mybdaygold.com</span>
                        <button class="btn btn-outline-secondary px-1 px-md-3" type="button" id="checkButton"><i class="bi bi-check-lg"></i></button>
                    </div>
                    <div class="spinner-border text-primary d-none" id="spinner" role="status">
                        <span class="visually-hidden">Checking...</span>
                    </div>
                    <p id="availability" class="mt-2"></p>
                    <button class="btn btn-primary d-none" type="submit" id="bgEmailSubmit">Select</button>
                </form>
            </div>
            <div class="card-footer bg-light">
                <div>
                    <input type="hidden" name="emailOption" value="bginbox">
                    <input type="radio" class="btn-check d-none" name="emailOption" value="bginbox" id="bgInboxOptionRadio" autocomplete="off">
                    <label class="form-check-label btn btn-primary  px-3 emailbgshow" for="bgInboxOption" id="bgInboxOption">I want this!</label>
                    <button type="submit" class="btn btn-primary d-none px-5 emailbghide" id="submitButton">Submit</button>
                </div>
            </div>
          </div>
        </div>
';



// USE-MY-OWN EMAIL OPTION
echo '
        <!-- Use-My-Own Email Option -->
        <div class="col-lg-6 mb-5 account-type-card" data-target="#OWN">
          <div class="card bg-light border-0 h-100 d-flex flex-column">
            <div class="card-body text-center flex-grow-1 d-flex flex-column">

                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0 small-screen-icon">
                        <div class="feature bg-dark bg-gradient text-white rounded-3"><i class="bi bi-envelope-at"></i></div>
                    </div>
                    <div class="flex-grow-1 d-flex justify-content-center">
                        <h2 class="fs-5 fs-md-4 fw-bold mb-0">My Own Email</h2>
                    </div>
                </div>

                    <div class="flex-grow-1 d-flex flex-column justify-content-center">
                    <p class="px-2 fs-10 fs-md-7">Use your own email address for your enrollments.</p>
                    <p class="px-2 fs-10 fs-md-7">Miss out on using one of our coolest features.</p>
                    <p class="px-2 fs-10 fs-md-7">You manage all the marketing emails.</p>
                    <div class="mb-3">
                        <a href="#" class="secondary fw-bold emailownshow fs-9" data-bs-toggle="modal" data-bs-target="#OWNModal">Learn More <i class="bi bi-info-circle-fill ms-2"></i></a>
                    </div>
                </div>
                
            </div>
            <div class="card-footer bg-light">
                <div>
                    <form id="emailChoiceForm2" method="post" action="/myaccount/createemail">
                        ' . $display->inputcsrf_token() . '
                        <input type="hidden" name="emailOption" value="ownemail">
                        <input type="radio" class="btn-check d-none" name="emailOption" value="ownemail" id="ownEmailOptionRadio" autocomplete="off">
                        <label class="form-check-label btn btn-primary px-3 emailownshow" for="ownEmailOption" id="ownEmailOption">I\'ll use my own.</label>
                        <button type="submit" class="btn btn-primary d-none px-5 emailownhide" id="submitButton2">Yes I\'m Sure</button>
                    </form>
                </div>
            </div>
          </div>
        </div>
             
';

echo '  </div>       </div>';


// MODALS FOR LEARN MORE CONTENT
?>
<!-- BGINBOx Modal -->
<div class="modal modal-lg fade" id="BGINBOxModal" tabindex="-1" aria-labelledby="BGINBOxModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="BGINBOxModalLabel">Learn More About BDay GOLD Inbox</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-4 my-4">
                <h2 class="pb-4">Why Choose BDayGold.com Email?</h2>
                <ul class="text-start">
                    <li class="list-item"><strong>Streamlined Inbox:</strong> Keep birthday and marketing emails separate from your personal inbox.</li>
                    <li class="list-item"><strong>Summarized Emails:</strong> Get quick overviews of your incoming mails, saving you time.</li>
                    <li class="list-item"><strong>Exclusive Perks:</strong> Unlock VIP experiences and unique celebrations.</li>
                    <li class="list-item"><strong>Personalized Itinerary:</strong> Easily create and share your birthday celebration plans.</li>
                    <li class="list-item"><strong>Data Security:</strong> Protect your personal data with a separate email address.</li>
                    <li class="list-item"><strong>Centralized Platform:</strong> Manage all birthday rewards and deals from one place.</li>
                    <li class="list-item"><strong>Exclusive Freebies:</strong> Discover freebies and coupons to make your day special.</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- OWN Modal -->
<div class="modal modal-lg fade" id="OWNModal" tabindex="-1" aria-labelledby="OWNModalLabel" aria-hidden="true">
    <div class="modal-dialog ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="OWNModalLabel">Why Stick with Your Personal Email?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body my-4">
            <ul class="text-start">
                    <li class="list-item"><strong>Familiarity:</strong> Continue using a platform you're accustomed to, without the learning curve of a new service.</li>
                    <li class="list-item"><strong>Consolidated Inbox:</strong> Keep all your emails, including birthday offers, in one place for ease of access.</li>
                    <li class="list-item"><strong>No Setup Required:</strong> Avoid the hassle of setting up and maintaining another email account.</li>
                    <li class="list-item"><strong>Personal Touch:</strong> Receive birthday wishes and deals directly to your personal inbox, adding a personal touch to the celebrations.</li>
                    <li class="list-item"><strong>Unified Contacts:</strong> Easily share birthday deals and offers with contacts already saved in your personal email account.</li>
                    <li class="list-item"><strong>Direct Notifications:</strong> Get timely notifications without the need to check a separate email account regularly.</li>
                    <li class="list-item"><strong>Less Complexity:</strong> Simplify your digital life by minimizing the number of platforms you use.</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
</div>

</div>
<?PHP

$footerattribute['postfooter'] = '

<script>
    $("#bgInboxOption").click(function(){
        $(".emailbghide").removeClass("d-none").addClass("d-block");
        $(".emailbgshow").removeClass("d-block").addClass("d-none");

        $(".emailownshow").removeClass("d-none").addClass("d-block");
        $(".emailownhide").removeClass("d-block").addClass("d-none");
        var username = $("#inputEmail").val();
        $("#availability").html("checking..").css("color", "blue");
        $("#spinner").removeClass("d-none");  // Show the spinner
        $.post(\'/helper_checkavailability\', {type: "f.email", username: username, _token: "' . $display->inputcsrf_token('tokenonly') . '"}, function(data){
            $("#spinner").addClass("d-none");  // Hide the spinner
            if(data == "1"){
                $("#availability").html("Available").css("color", "green");
            }
            else{
                $("#availability").html("Not Available").css("color", "red");
            }
        });
    });

    $("#ownEmailOption").click(function(){
        $(".emailownhide").removeClass("d-none").addClass("d-block");
        $(".emailownshow").removeClass("d-block").addClass("d-none");

         $(".emailbgshow").removeClass("d-none").addClass("d-block");
        $(".emailbghide").removeClass("d-block").addClass("d-none");
  
    });
    

    $("#checkButton").click(function(){
        var username = $("#inputEmail").val();
        $("#availability").html("checking..").css("color", "blue");
        $("#spinner").removeClass("d-none");  // Show the spinner
        $.post(\'/helper_checkavailability\', {type: "f.email", username: username, _token: "' . $display->inputcsrf_token('tokenonly') . '"}, function(data){
            $("#spinner").addClass("d-none");  // Hide the spinner
            if(data == "1"){
                $("#availability").html("Available").css("color", "green");
            }
            else{
                $("#availability").html("Not Available").css("color", "red");
            }
        });
    });


var typingTimer; // Timer identifier
var doneTypingInterval = 700;  // timing in millisecond

// On keyup, start the countdown
$("#inputEmail").keyup(function() {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(doneTyping, doneTypingInterval);
});

// On keydown, clear the countdown 
$("#inputEmail").keydown(function() {
    clearTimeout(typingTimer);
});

// User has finished typing
function doneTyping() {
    checkAvailability();
}

$("#checkButton").click(function(){
    checkAvailability();
});

    function checkAvailability() {
        var username = $("#inputEmail").val();
        $("#availability").html("checking..").css("color", "blue");
        $("#spinner").removeClass("d-none");  // Show the spinner
        $.post("/helper_checkavailability", {type: "f.email", username: username, _token: "' . $display->inputcsrf_token('tokenonly') . '"}, function(data){
            $("#spinner").addClass("d-none");  // Hide the spinner
            if(data == "1"){
                $("#availability").html("Available").css("color", "green");
            }
            else{
                $("#availability").html("Not Available").css("color", "red");
            }
        });
    }
</script>
';

$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
