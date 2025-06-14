<?php
$addClasses[] = 'createaccount';
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 





$jstag_openinstructions='';

#-------------------------------------------------------------------------------
# HANDLE THE PROFILE UPDATE ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted() && isset($_REQUEST['profileupdate'])) {


#breakpoint($_REQUEST);

$updatefields = [];


// Step 2: Set checked options to "true".
foreach ($_POST as $formelement => $formvalue) {
if (strpos($formelement, 'input') !== false) {
$columnname = strtolower(str_replace('input', '', $formelement));
$updatefields[$columnname] = trim($formvalue);
}
}

if (!empty($updatefields)) {
$updatefields['email']=strtolower($updatefields['email']);

# breakpoint($updatefields);
$userdata_before=$current_user_data;   unset($userdata_before['modify_dt']); $userdata_beforehash =hash('sha256', serialize($userdata_before));
$account->updateSettings($current_user_data['user_id'], $updatefields);
#breakpoint($updatefields);
$current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');
$userdata_after=$current_user_data;   unset($userdata_after['modify_dt']); $userdata_afterhash =hash('sha256', serialize($userdata_after));
} 
if (
isset( $updatefields['username'] ) && $updatefields['username']!=$userdata_before['username'] ||
isset( $updatefields['email'] ) && $updatefields['email']!=$userdata_before['email']
) {
header('location: /logout?_relogin');
exit;
}
$supressionitem=$extremesupression=false;
$messages=array();



}






#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$headerattribute['additionalcss']='<link rel="stylesheet" href="/public/css/myaccount.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

';
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');
 echo '

<div class="container-xl px-4 mt-4">
    <!-- Account page navigation-->
';
  include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-myaccount.php'); 

### ENSURE THERE IS NO EMAIL / USERNAME COLLISION
$username = (isset($_POST['username']) ? $_POST['username'] : false);
if ($username) $output= $createaccount->isavailable($username);

$accountstats=$account->account_getstats();

$plandetails=$app->plandetail('details');

$userplan=$current_user_data['account_plan'];

$selectsused=($accountstats['business_pending']+$accountstats['business_selected']+$accountstats['business_success']);
$selectsleft=($plandetails[$userplan]['max_business_select']-$selectsused);


$plandata=$app->plandetail('details');
$userplan=$current_user_data['account_plan'];
echo '    
<hr class="mt-0 mb-4">
<div class="container p-0">
        <div class="row">
            <div class="col-lg-8">
               
        
  
       ';
       
       switch ($userplan) {
        case 'free': $daysouttag=$plandetails[$userplan]['celebration_tour_option_tag'].' - Click Here to upgade.'; $daysout=$plandetails[$userplan]['celebration_planning_days']; break;
        case 'gold': $daysouttag=$plandetails[$userplan]['celebration_tour_option_tag']; $daysout=$plandetails[$userplan]['celebration_planning_days']; break;
        case 'life': $daysouttag=$plandetails[$userplan]['celebration_tour_option_tag']; $daysout=$plandetails[$userplan]['celebration_planning_days']; break;
        default: $daysouttag='This feature is not available on the FREE plan - Click Here to upgade.'; $daysout=0; break;
        }  

       $tag1='';

       switch ($plandata[$userplan]['max_business_select']) {
case 0: $tag1=' The free plan does not allow you to enroll in any businesses.'; break;


        default:  $tag1=' Every year you renew you get '.$plandata[$userplan]['max_business_select'].' more.'; break;
       }
   # breakpoint($current_user_data);
       $nextDate = $app->calculateNextOccurrence($current_user_data['birthdate'], $daysout);
      #breakpoint($nextDate);
      # $output['result']
  $outdays=$app->getTimeTilBirthday( $nextDate['date']);

       echo '

  <!-- Payment methods card-->
  <div class="card card-header-actions mb-4">
        <div class="card-header">
            Plan Features
        </div>
        <div class="card-body px-0">
            <!-- Payment method 1-->
            <div class="d-flex align-items-center justify-content-between px-4">
                <div class="d-flex align-items-center">
                     <h1><i class="bi bi-bag-heart"></i></h1>
                    <div class="ms-4">
                    ';

                    switch($userplan) {
                        case 'free' : 
                            echo $tag1;
                            break;
                            default:
                    echo  '<div class="small">You can select up to  '.$plandata[$userplan]['max_business_select'].'  business in your plan.
                        '.$tag1.'</div>
                        <div class="text-xs text-muted">You are using '.$selectsused.' and have '.( $selectsleft < 0 ? 0 : $selectsleft ).' left.</div>
                        ';
                        break;
                        }
                        echo '
                    </div>
                </div>               
            </div>
            <hr>
            <!-- Payment method 2-->
            <div class="d-flex align-items-center justify-content-between px-4">
                <div class="d-flex align-items-center">
                <h1><i class="bi bi-calendar3"></i></h1>
                    <div class="ms-4">
                    <div class="small">Celebration Tour: '.$plandata[$userplan]['celebration_tour_option_tag'].'</div>';
                          
                    switch($userplan) {
                        case 'free' : 
                            echo 
                            '';
                            break;
                            default :
                            echo '                     
                    <div class="text-xs text-muted">You can start your planning in '. $outdays['days'].' '.$qik->plural('day',  $outdays['days']).'</div>
                    ';
                    break;
                        }
                    echo '
                    </div>
                </div>
            </div>
            <hr>
            <!-- Payment method 3-->
            <div class="d-flex align-items-center justify-content-between px-4">
                <div class="d-flex align-items-center">
                <h1><i class="bi bi-alarm"></i></h1>
                    <div class="ms-4">
                    <div class="small">Reminder of upcoming benefits</div>
                    <div class="text-xs text-muted">Don\'t miss out on any freebies!</div>
                    </div>
                </div>
            </div>
            <hr>
            <!-- Payment method 3-->
            <div class="d-flex align-items-center justify-content-between px-4">
                <div class="d-flex align-items-center">
                <h1><i class="bi bi-wechat"></i></h1>
                    <div class="ms-4">
                    <div class="small">Support through '.$plandata[$userplan]['support_tag'].'</div>
                    <div class="text-xs text-muted"><a href="'.$plandata[$userplan]['support_link'].'">Click here to get support now.</a></div>
                    </div>
                </div>
            </div>

        </div>

        </div>


        
        ';
        

#-------------------------------------------------------------------------------
# DISPLAY PROFILE SECTION
#-------------------------------------------------------------------------------

$till=$app->getTimeTilBirthday($current_user_data['birthdate']);
$astrosign=$app->getastrosign($current_user_data['birthdate']);
$astroicon=$app->getZodiacInfo($astrosign);
$state=$current_user_data['state'];
if (empty($state) && !empty($client_locationdata['regionName'])) {
    $state= $client_locationdata['regionName'];
    }
$avatar='/public/images/defaultavatar.png';
if (!empty($current_user_data['avatar'])) $avatar='/'.$current_user_data['avatar'];


echo '
<!-- Account details card-->
<div class="card mb-4">
<div class="card-header d-flex justify-content-between">
<span>birthday.gold Account Details</span>
<!-- Button trigger modal
<button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#instructionsModal">
Instructions
</button>  -->
</div>
<div class="card-body">
<form id="profileupdateForm" method="post">
'.$display->inputcsrf_token().'
<input name="profileupdate" type="hidden" value="1">


<div class="row gx-3 mb-3">
<div><h4 class="fw-bold">Account Personal Details:</h4></div>


</div>






<!-- Form Row-->
<div class="row gx-3 mb-3">
<!-- Form Group (title)-->
<div class="col-md-2">
<label class="small mb-1" for="inputtitle">Title</label>
<select name="inputtitle" class="form-control custom-select select-form-background">
'. $display->list_title($current_user_data['title']).'
</select>
</div>

<!-- Form Group (first name)-->
<div class="col-md-4">
<label class="small mb-1" for="inputfirst_name">First name</label>
<input class="form-control" name="inputfirst_name"  id="inputfirst_name" type="text" placeholder="Enter your first name" value="'.$current_user_data['first_name'].'">
</div>
<!-- Form Group (middle name)-->
<div class="col-md-2">
<label class="small mb-1" for="inputmiddle_name">Middle name</label>
<input class="form-control" name="inputmiddle_name"  id="inputmiddle_name" type="text" placeholder="" value="'.$current_user_data['middle_name'].'">
</div>
<!-- Form Group (last name)-->
<div class="col-md-4">
<label class="small mb-1" for="inputlast_name">Last name</label>
<input class="form-control" name="inputlast_name" id="inputlast_name" type="text" placeholder="Last name" value="'.$current_user_data['last_name'].'">
</div>
</div>

<div class="row gx-3 mb-3">
<!-- Form Group (gender)-->
<div class="col-md-4">
<label class="small mb-1" for="inputgender">Gender</label>                      
<select name="inputgender" class="form-control custom-select select-form-background">
'. $display->list_gender($current_user_data['gender']).'
</select>
</div>
</div>

';



$passgenerator='';

echo '
<hr class="mt-4 mb-4">
<!-- Contact Details -------------------------------------------------------------------------------------------- -->
<div class="row gx-3 mb-3">
<div><h4 class="fw-bold">User Account Details:</h4></div>
<small class="mb-2">Used to log into birthday.gold</small>
<!-- Form Group (username)-->
<div class="col-md-6">
<label class="small mb-1" for="inputUsername">Username  <span id="availability"></span></label>
<div class="input-group mb-3">
<input type="text" class="form-control" name="inputUsername" id="inputUsername" placeholder="Enter your username" value="'.$current_user_data['username'].'">
<button class="btn btn-outline-secondary ms-0 p-1" type="button" id="checkButton">Check</button>
</div>
</div>


<!-- Form Group (email)-->
<div class="col-md-6">
<label class="small mb-1" for="inputemail">Email Address</label>
<input class="form-control" name="inputemail" id="inputemail" type="email" placeholder="Enter your email address" value="'.$current_user_data['email'].'">
</div>



<!-- Form Group (phone_number)-->
<div class="col-md-6">
<label class="small mb-1" for="inputphone_number">Mobile Phone Number</label>
<input class="form-control" name="inputphone_number" id="inputphone_number" type="tel" placeholder="Enter your mobile number" value="'.$current_user_data['phone_number'].'">
</div>
</div>







<hr class="mt-4 mb-4">
<!-- Mailing Address -------------------------------------------------------------------------------------------- -->
<!-- Form Row-->
<div class="row gx-3 mb-3">
<div><h4 class="fw-bold">Mailing Address:</h4></div>
<small class="mb-2">Used for birthday.gold gifts.</small>
<!-- Form Group (organization name)-->
<div class="col-md-12">
<input class="form-control" name="inputmailing_address"  id="inputmailing_address" type="text" placeholder="Mailing Address" value="'.$current_user_data['mailing_address'].'">
</div>

</div>
<!-- Form Row        -->
<div class="row gx-3 mb-3">

<!-- Form Group (location)-->
<div class="col-md-4">
<input class="form-control" name="inputCity"  id="inputCity" type="text" placeholder="City" value="'.$current_user_data['city'].'">
</div>


<!-- Form Group (organization name)-->
<div class="col-md-4">
<select name="inputState" class="form-control custom-select select-form-background">
'. $display->list_state($state).'
</select>

</div>
<!-- Form Group (location)-->
<div class="col-md-4">
<input class="form-control"  name="inputzip_code"  id="inputzip_code" type="text" placeholder="Zipcode" value="'.$current_user_data['zip_code'].'">
</div>
</div>

';



echo '</div>
<div class="m-5 text-center">
<!-- Save changes button-->
<button class="btn btn-success px-5" type="submit">Save Changes</button>
</div>
</form>
</div>
';




#-------------------------------------------------------------------------------
# DISPLAY SOCIAL NETWORK SECTION
#-------------------------------------------------------------------------------

echo '
<!-- SOCIAL NETWORK ACCOUNTS -------------------------------------------------------------------------------------------- -->
<div class="card mb-4 d-none">
<div class="card-header d-flex justify-content-between">
<span>Your Social Media Networks</span>
<!-- Button trigger modal
<button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#instructionsModal">
Instructions
</button>  -->
</div>
<div class="card-body">
<form id="profileupdateForm" method="post">
'.$display->inputcsrf_token().'
<input name="profileupdate" type="hidden" value="1">


<div class="row gx-3 mb-3">
<small class="mb-2">Used to allow you to auto post your photos/videos</small>

<!-- Form Group (username)-->
<div class="col-md-6">
<label class="small mb-1" for="inputUsername">Username  <span id="availability"></span></label>
<input type="text" class="form-control" name="inputUsername" id="inputUsername" placeholder="Enter your username" value="'.$current_user_data['username'].'">
</div>



<!-- Form Group (email)-->
<div class="col-md-6">
<label class="small mb-1" for="inputemail">Email Address</label>
<input class="form-control" name="inputemail" id="inputemail" type="email" placeholder="Enter your email address" value="'.$current_user_data['email'].'">
</div>



</div>
';



echo '</div>
<div class="m-5 text-center">
<!-- Save changes button-->
<button class="btn btn-success px-5" type="submit">Save Changes</button>
</div>
</form>
</div>

';







#-------------------------------------------------------------------------------
# DISPLAY RIGHT COLUMN SECTION
#-------------------------------------------------------------------------------
echo '

        </div>  <!-- end left column -->

';
        

echo '            <div class="col-lg-4"> <!-- start right column -->';
        

echo '    <div class="row">

<div class="col-12 mb-4">

<!-- Billing card 1-->
<div class="card h-100 border-start-lg border-start-primary">
    <div class="card-body">
        <div class="small text-muted">Your Plan</div>
        <div class="h3">'.$plandata[$userplan]['displayname'].'</div>';
        if ($plandata[$userplan]['upgradeable']==1) {
        echo '
        <a class="text-arrow-icon small" href="/myaccount/upgrade"> '.$plandata[$userplan]['upgradeable_tag'].'   </a>
        ';
}
        echo '
    </div>
</div>
</div>



';

if ($current_user_data['account_plan']=='gold') {
    echo '
    <div class="col-12 mb-4">
<!-- Billing card 2-->
<div class="card h-100 border-start-lg border-start-secondary">
    <div class="card-body">
    ';


    if ($plandata[$userplan]['renewable']==1) {
        $next_year_date = date("F j, Y", strtotime('+1 year', strtotime($current_user_data['create_dt'])));
        echo '
    <div class="small text-muted">Your account will auto-renew</div>
    <div class="row">
    <div class="col-lg-7">
       
    <div class="h3">'. $next_year_date.'
</div>

<!--  <a class="text-arrow-icon small text-secondary" href="/myaccount/billinghistory">  View billing history </a> -->
<!--  <a class="text-arrow-icon small text-danger" href="/cancelplan">  Cancel Plan </a> -->
    </div>
        

<div class="col-lg-5">
<div class="d-flex align-items-center">
<!-- <i class="bi bi-credit-card h3"></i>-->
<div class="ms-4">
<div class="small">xxxx</div>
<div class="text-xs text-muted">##/####</div>
</div>
</div>
</div>
</div>
';
} elseif ($current_user_data['account_plan']=='life') {
echo 'Yea! you have the best plan.';
}


echo '
</div>
</div>
</div>
';
}
/*

        <hr class="mt-4 mb-4">
        <!-- Contact Details -------------------------------------------------------------------------------------------- -->
        <div class="row gx-3 mb-3">
        <div><h4 class="fw-bold">Change Password Security:</h4></div>
     

        <!-- Form Group (password)  -->
        <div class="col-md-6">
        <label class="small mb-1" for="inputpassword">Old Password '.$passgenerator.'</label>
        <div class="input-group">
        <input class="form-control" name="inputpassword" id="input_password" type="password" placeholder="Enter your Password" value=""  autocomplete="new-password">
        <button class="btn btn-outline-secondary custom-button"  id="togglePassword" type="button">Show</button>
        </div>
        </div>
        
        
        <!-- Form Group (password)  -->
        <div class="col-md-6">
        <label class="small mb-1" for="inputpassword">New Password '.$passgenerator.'</label>
        <div class="input-group">
        <input class="form-control" name="inputpassword" id="input_password" type="password" placeholder="Enter your Password" value=""  autocomplete="new-password">
        <button class="btn btn-outline-secondary custom-button"  id="togglePassword" type="button">Show</button>
        </div>
        </div>
        */


/*
        echo '

            <!-- Form Group (username)-->
            <div >
<label class="small mb-1" for="inputUsername">Username  <span id="availability"></span></label>
<div class="input-group mb-3">
<input type="text" class="form-control" name="inputUsername" id="inputUsername" placeholder="Enter your username" value="'.$current_user_data['username'].'">
<button class="btn btn-outline-secondary ms-0 p-1" type="button" id="checkButton">Check</button>
</div>


           

                <!-- Two factor authentication card-->
                <div class="card mb-4">
                    <div class="card-header">Two-Factor Authentication</div>
                    <div class="card-body">
                        <p>Add more security to your account by enabling two-factor authentication. We will send you a text message to verify your login attempts on unrecognized devices and browsers.</p>
                        <form>
                        
                            <div class="mt-3">
                                <label class="small mb-1" for="twoFactorSMS">SMS Number</label>
                                <div class="input-group mb-3">
        <input type="text" class="form-control" name="dyn_twoFactorSMS" id="twoFactorSMS" placeholder="Enter your mobile number" value="">
        <button class="btn btn-outline-secondary ms-0 p-1" type="button" id="checkButton">Enroll</button>
    </div>

                            </div>
                        </form>
                    </div>
                </div>

                */




        echo '
        <div class="col-12 mb-4">
        <!-- Change password card-->
        <div class="card mb-4">
                <div class="card-header">Change Password</div>
                <div class="card-body">
                    <form action="changepassword" method="post">                           
'.$display->inputcsrf_token().'
<input name="returnto" type="hidden" value="/changepassword">
                        <!-- Form Group (current password)-->
                        <div class="mb-3">
                            <label class="small mb-1" for="inputcurrentPassword">Current Password</label>
                            <input class="form-control" name="inputcurrentPassword" id="inputcurrentPassword" type="password" placeholder="Enter current password">
                        </div>
                        <!-- Form Group (new password)-->
                        <div class="mb-3">
                            <label class="small mb-1" for="inputnewPassword">New Password</label>
                            <input class="form-control"  name="inputnewPassword" id="newPassword" type="password" placeholder="Enter new password">
                        </div>
                        <!-- Form Group (confirm password)-->
                        <div class="mb-3">
                            <label class="small mb-1" for="inputconfirmPassword">Confirm Password</label>
                            <input class="form-control"  name="inputconfirmPassword"  id="inputconfirmPassword" type="password" placeholder="Confirm new password">
                        </div>
                        <button class="btn btn-primary" type="submit">Save</button>
                    </form>
                </div>
                </div>
                </div>
                ';


echo '<!-- Delete account card-->
<div class="col-12 mb-4">
    <div class="card mb-4">
        <div class="card-header">Delete Account</div>
        <div class="card-body">
            <p>Deleting your birthday.gold account is a permanent action and cannot be undone. If you are sure you want to delete your account, click the link below.</p>
            <button class="btn btn-danger-soft text-danger" type="button" data-toggle="modal" data-target="#deleteAccountModal">I understand, delete my account</button>
        </div>
    </div>
</div>
';


#-------------------------------------------------------------------------------
# CONFIRMATION MODAL DIALOG
#------------------------------------------------------------------------------- 
echo '<!-- Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Are You Sure?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="/myaccount/deleteaccount.php" method="POST">
            '.$display->inputcsrf_token().'
                <div class="modal-body">
                    <p>Type "delete" in the field below to confirm.</p>
                    <input type="text" name="deleteConfirm" id="deleteConfirm" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">No, I changed my mind</button>
                    <button type="submit" class="btn btn-danger" id="confirmDelete">Yes, delete my account</button>
                </div>
            </form>
        </div>
    </div>
</div>
';
$content="
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const confirmDeleteButton = document.getElementById('confirmDelete');
        const deleteConfirmField = document.getElementById('deleteConfirm');

        confirmDeleteButton.addEventListener('click', function(event) {
            if (deleteConfirmField.value !== 'delete') {
                event.preventDefault();
                alert('You must type \"delete\" to proceed.');
            }
        });
    });
</script>
";






    echo '
</div>






</div>

</div>


</div>
</div>
';
        
$footerattribute['postfooter']='
<script>
$(document).ready(function() {
$("#uploadBtn").click(function() {
$("#imageUpload").click();
});

$("#imageUpload").change(function() {
$("#uploadForm").submit();
});

'.$jstag_openinstructions.'
});
</script>
<script src="/public/js/myaccount-profile.js?'.date('Ymdis').'" language="javascript"></script>

<script>
$("#checkButton").click(function(){
    var username = $("#inputUsername").val();
    $.post(\'/helper_checkavailability\', {username: username, _token: "'.$display->inputcsrf_token('tokenonly').'"}, function(data){
        if(data == "1"){
            $("#availability").html("Available").css("color", "green");
        }
        else{
            $("#availability").html("Not Available").css("color", "red");
        }
    });
  });
  </script>
'.$content. '  <script src="/public/js/passwordhelper.js" language="javascript"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.min.js" integrity="sha384-Rx+T1VzGupg4BHQYs2gCW9It+akI2MM/mndMCy36UVfodzcJcF0GGLxZIzObiEfa" crossorigin="anonymous"></script>
';


include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.php');