<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 






#-------------------------------------------------------------------------------
# HANDLE THE PROFILE UPDATE ATTEMPT   -- this is an AJAX POST
#-------------------------------------------------------------------------------
if ($app->formposted() ) {


// Check if 'userid' is set in POST request
if (isset($_POST['userid'])) {
    $userId = $_POST['userid'];
$columns='`user_id`, `type`, `name`, `description`, `status`, `create_dt`, `modify_dt`, `rank`, `grouping`, `category`, `start_dt`';
  $sqls = []; // Initialize an array to store SQL statements

    // Define your SQL insert statements
    if (isset($_POST['senior_flag']) && $_POST['senior_flag']==1) {
    $sqls[]= "INSERT INTO `bg_user_attributes` (".$columns.") VALUES (:user_id, NULL, 'commissioned_consultant', 'senior', 'A', now(), now(), NULL, NULL, 'commissioned_consultant', now())";
    $sqls[]= "INSERT INTO `bg_user_attributes` (".$columns.") VALUES (:user_id, NULL, 'referral_feature', 'allow_cash', 'A', now(), now(), NULL, NULL, 'commissioned_consultant', now())";
    $sqls[]= "INSERT INTO `bg_user_attributes` (".$columns.") VALUES (:user_id, NULL, 'referral_feature', 'allow_enroll_familyaccount', 'A', now(), now(), NULL, NULL, 'commissioned_consultant', now())";
    $sqls[]= "INSERT INTO `bg_user_attributes` (".$columns.") VALUES (:user_id, NULL, 'referral_feature', 'allow_enroll_businessaccount', 'A', now(), now(), NULL, NULL, 'commissioned_consultant', now())";
   } else {
    $sqls[]= "INSERT INTO `bg_user_attributes` (".$columns.") VALUES (:user_id, NULL, 'commissioned_consultant', 'basic', 'A', now(), now(), NULL, NULL, 'commissioned_consultant', now())";
    }

   
$sqls[]= "INSERT INTO `bg_user_attributes` (".$columns.") VALUES (:user_id, 'referral_payout', 'giftcertificate|life', '4.00', 'A', now(), now(), NULL, NULL, 'commissioned_consultant', now())";
$sqls[]= "INSERT INTO `bg_user_attributes` (".$columns.") VALUES (:user_id, 'referral_payout', 'parental|life', '4.00', 'A', now(), now(), NULL, NULL, 'commissioned_consultant', now())";
$sqls[]= "INSERT INTO `bg_user_attributes` (".$columns.") VALUES (:user_id, 'referral_payout', 'user|gold', '3.00', 'A', now(), now(), NULL, NULL, 'commissioned_consultant', now())";
$sqls[]= "INSERT INTO `bg_user_attributes` (".$columns.") VALUES (:user_id, 'referral_payout', 'user|life', '4.00', 'A', now(), now(), NULL, NULL, 'commissioned_consultant', now())";



    // Prepare and execute each SQL statement
    foreach ($sqls as $sql) {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    echo "Records inserted successfully.";
} else {
    echo "User ID not provided.";
}
}



$headerattribute['additionalcss']='<link rel="stylesheet" href="/public/css/myaccount.css">';
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');

?>
<div class="container-xl px-4 mt-4 flex-grow-1">
    <!-- Account page navigation-->

    <?PHP  include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-myaccount.php'); 


   // Fetch pending users from bg_user_companies
   $query = "SELECT     u.user_id,     u.first_name,     u.last_name,     u.email    From     bg_users u ";

$result = $database->query($query);
$users = $result->fetchAll();

if ($users) {
$userlistoutput='';
$usercount= $enrollmentcount=0;

$linkbase='https://bgrab.birthday.gold/startregistration?sid='.session_id().'&aid='.$current_user_data['user_id'].'&uid=';
foreach ($users as $user) {
    $usercount++;
    $enrollmentcount=($enrollmentcount+$user['company_count']) ;

 $userlistoutput.= "<div class='row my-2'>";
 $userlistoutput.= "<div class='col-md-5'><span class=' fw-bold'>" . $user['first_name'] . " " . $user['last_name'] . '</span><br><small>'. $user['email'].'<br>uid='. $user['user_id']."</small></div>";
 $userlistoutput.= "<div class='col-md-2 text-center '>" . $user['formatted_birthdate'] .'<br>'.$user['formatted_birthyear']. "</div>";
 $userlistoutput.= "<div class='col-md-3'>TOTAL: " . $user['company_count'] ."<BR>Pending: ". $user['selected_count']."<br>Completed: ".  $user['success_count'] . "</div>";
 
 if ($user['hours_until_enrollment']==0) {
 $userlistoutput.= "<div class='col-md-2'><a target='_userregistration' href='".$linkbase.''.$user['user_id']."' class='btn btn-success'>Start Enrolling</a></div>";
 } else {
    $userlistoutput.= "<div class='col-md-2 bg-warning'>Delayed ".$user['hours_until_enrollment']." hrs.</div>";
    
 }

 $userlistoutput.= "<div>".$user['company_list']."</div>";

 
 $userlistoutput.= "</div>";


}
} else {
echo "<p>No pending users found.</p>";
}




    echo '
    <hr class="mt-0 mb-4">
    <div class="row col-12">



    <div class="col-4">
    <div class="row">



    <div class="col mb-4">
    <!-- Billing card 2-->
    <div class="card h-100 border-start-lg border-start-secondary">
        <div class="card-body">
            <div class="small text-muted">Users Pending</div>
            <div class="h3">'.$usercount.'</div>
           
        </div>
    </div>
    </div>

<div class="col mb-4">
    <!-- Billing card 3-->
    <div class="card h-100 border-start-lg border-start-success">
        <div class="card-body">
            <div class="small text-muted">Enrollments Pending</div>
            <div class="h3 d-flex align-items-center">'.$enrollmentcount.'</div>
           
        </div>
    </div>


    </div>
    </div>
    </div>




        <div class="col-xl-8">
        <div class="row">
     
        

        <div class="col-12 mb-4">
            <!-- Billing card 3-->
            <div class="card h-100 border-start-lg border-start-success">
                <div class="card-body">
                    <div class="small text-muted">List of Enrollments To Process</div>


                    <div class="row">
                   '.
                   $userlistoutput;
   
   

      echo '                
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

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.php');
