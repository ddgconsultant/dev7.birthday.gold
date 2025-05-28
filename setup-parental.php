<?php 
$addClasses[]='Convert';
$addClasses[] = 'createaccount';
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');



#-------------------------------------------------------------------------------
# PROTECT ACCIDENTIAL USAGE
#-------------------------------------------------------------------------------
$allowcontinue=true;
if ($account->isadmin()) { $allowcontinue=true;}
if ($account->isimpersonator()) { $allowcontinue=true;}
if ($app->formposted()) { $allowcontinue=true;}
if((isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'validate-account') !== false)) { $allowcontinue=true;}

if(!$allowcontinue) {
    header('Location: /myaccount');
    exit;
}



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$paymenttag='';
$birthdayprioritytag='';
$displaytype='generated'; 
$done=false;
$recipient='';
$message='';

$current_user_data=$userregistrationdata=$session->get('userregistrationdata');
#$current_user_data['user_id']=125;
if (!empty($current_user_data['user_id']))
$current_user_data=$account->getuserdata($current_user_data['user_id'], 'user_id', ['pending', 'active', 'validated']);

$personcount=0;
$planfee= [20,20,15,15,10,10];
$plancost=[60,80,95,110,120,130];
$maxnumberofminors=6;

#-------------------------------------------------------------------------------
# HANDLE ACTIONS
#-------------------------------------------------------------------------------

// Determine the action based on the query string
if ($app->formposted()) {

// Get user_id to use as feature_parent_id
$feature_parent_id = $current_user_data['user_id']; 

    // Delete all existing minor records for the user
    $sqlDelete = "DELETE FROM bg_users WHERE feature_parent_id = :feature_parent_id AND account_type = 'minor' and `status`='pending'";
    $stmtDelete = $database->prepare($sqlDelete);
    $stmtDelete->execute([':feature_parent_id'=> $feature_parent_id]);

for ($i = 1; $i <= $maxnumberofminors; $i++) {
     // Create an array of the expected field names for this iteration
     $fieldnames = [
      'date' => "birthdate$i", 
      'year' => "year$i", 
      'month' => "month$i", 
      'day' => "day$i"
  ];
  
  // Call the function, passing the $_POST array and the $fieldnames array
  $birthday = $app->getformdate($_POST, $fieldnames);


  // add non-blank minors
  if (!empty($_POST["first_name$i"]) && !empty($_POST["last_name$i"]) && !empty( $birthday)) {
      $first_name = $_POST["first_name$i"];
      $last_name =$_POST["last_name$i"];

### GENERATE USERNAME
$username = $createaccount->generate_username($first_name, $last_name, $birthday);

$params=[
':feature_parent_id'=>$current_user_data['user_id'],
':first_name'=>$first_name ,
':last_name'=>$last_name ,
':birthday'=>$birthday,
':username'=>$username,
':account_plan'=>'life',
':status'=>'pending',

':account_cost'=>($planfee[$personcount]*100),

':account_type'=>'minor',

':hashed_password' => $current_user_data['password'],
':feature_email'=>strtolower($username.'@mybdaygold.com'),
':profile_email'=>strtolower($username.'@mybdaygold.com'),
':email'=>strtolower($username.'@mybdaygold.com'),
## account location
':city' => $current_user_data['city'],
':state' => $current_user_data['state'],
':zip_code' => $current_user_data['zip_code'],
## profile location
':city2' => $current_user_data['city'],
':state2' => $current_user_data['state'],
':zip_code2' => $current_user_data['zip_code'],
];


// Insert the data into the database
$sql = "INSERT INTO bg_users 
       (feature_parent_id,  first_name,  last_name,  birthdate, username, account_plan, status, account_cost, account_type,  password, feature_email, profile_email, email, city, state, zip_code, profile_city, profile_state, profile_zip_code  ) 
VALUES (:feature_parent_id, :first_name, :last_name, :birthday, :username, :account_plan, :status, :account_cost, :account_type, :hashed_password, :feature_email, :profile_email, :email, :city, :state, :zip_code, :city2, :state2, :zip_code2)";


$stmt=$database->prepare($sql);
if ($stmt->execute($params) === TRUE) {
            $personcount++;
session_tracking('minor_record_created', $sql.print_r($params,1));
    #        echo "New record created successfully";
        } else {
            
session_tracking('minor_record_failed', $sql.print_r($params,1));
         #   echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}
#$plancost=[2000,4000,5500,7000,8000,9000];
$pay=($plancost[$personcount]*100);

$session->set('current_user_data', $current_user_data);
header('location: /checkout-parental');
exit;
}



#-------------------------------------------------------------------------------
# CREATE CODE
#-------------------------------------------------------------------------------

    // Couldn't generate gift code
   $displaytype='input'; # echo "<h2>An error occurred while generating the gift certificate code. Please try again.</h2>";
  


#-------------------------------------------------------------------------------
# SETUP DISPLAY REQUIRED VARAIABLES AND FUNCTIONS
#-------------------------------------------------------------------------------

$formtype='form-floating';
$visibilitytag='hidden';
$minor_count=1;
$minor_record=[];
function generate_minorcontent($minor_record){
  global $display;
$minoroutput= '
<!-- ================================================================================================== -->
<div id="person-container-minor'.$minor_record['local_counter'].'"  class="minor-container">
  <div class="person-group  input-group p-0 m-0 mb-2 flex-wrap custom-wrap"> 
  <div class="col-9 col-md-11 mb-2 mt-md-3 fw-bold d-md-inline text-start text-md-center accounttype order-1 order-md-0 d-flex align-items-center">Minor #'.$minor_record['local_counter'].'</div>
  <div class="col-3 col-md-1 order-2 ms-auto order-md-4 d-flex align-items-center" style="visibility: '.$minor_record['local_visibilitytag'].'">
  <button type="button"  onclick="removePerson(this)" class="btn btn-sm button ms-auto remove-btn"  '.$display->tooltip('Click to remove minor').'>
        <i class="bi bi-dash-circle-fill text-danger h4"></i>
      </button>
    </div>
    <div class="col-12 col-md order-3 order-md-1">
      <div class="'.$minor_record['local_formtype'].'">
        <input type="text" class="form-control" name="first_name'.$minor_record['local_counter'].'" value="'.$minor_record['first_name'].'" placeholder="First Name" aria-label="First Name">
        <label for="first_name'.$minor_record['local_counter'].'">First name</label>
      </div>
    </div>
    <div class="col-12 col-md order-4 order-md-2">
      <div class="'.$minor_record['local_formtype'].'">
        <input type="text" class="form-control" name="last_name'.$minor_record['local_counter'].'"  value="'.$minor_record['last_name'].'"  placeholder="Last Name" aria-label="Last Name">
        <label for="last_name'.$minor_record['local_counter'].'">Last name</label>
      </div>
    </div>

    ';

  $birthday= $minor_record['birthdate'];
    $options['value']=$birthday;
    $options['minyears'] = 16;
    $options['divclass']=''.$minor_record['local_formtype'].'';
    $options['divformtypeclass']='form-floating';
    $options['labelclass']='';
    $options['nochangetag']='';
    $options['birthday_label']='';
    $options['enableapplelabels']=true;
    $options['forceapple']=true;    
    $fieldnames=['date'=>'birthday1', 'year'=>'year'.$minor_record['local_counter'].'', 'month'=>'month'.$minor_record['local_counter'].'', 'day'=>'day'.$minor_record['local_counter'].'' ];
    $dobdateelement=$display->input_datefield($fieldnames, $options);
    $minoroutput.= '
    <!-- DOB input -->
      <div class="col-12 col-md order-5 order-md-3">    
    '. $dobdateelement.'
    </div>
    </div>
    </div>  
';
return $minoroutput;
}

$additionaljs= '';
$additionalstyles.='
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
    .accounttype{
      width: 100px;
    }

    @media (max-width: 767.98px) { /* Bootstrap\'s breakpoint for small screens */
      .custom-wrap .col-12 {
        flex: 0 0 100%;
        max-width: 100%;
      }
    }
    .tooltip {
      z-index: 1039 !important;  /* Assuming the modal z-index is 1040 */
      visibility: hidden;
  }
  
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


$displaytype='xx';
switch ($displaytype) {

##---------------------------------------------------------
default:
    

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

echo '
<!-- Parental Account Start -->
<div class="container main-content py-5">
  <div class="container text-center">
    <div class="row justify-content-center">
      <div class="col-12">
<!-- iCon by oNlineWebFonts.Com --> <img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gU3ZnIFZlY3RvciBJY29ucyA6IGh0dHA6Ly93d3cub25saW5ld2ViZm9udHMuY29tL2ljb24gLS0+DQo8IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPg0KPHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgMjU2IDI1NiIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAwIDAgMjU2IDI1NiIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+DQo8bWV0YWRhdGE+IFN2ZyBWZWN0b3IgSWNvbnMgOiBodHRwOi8vd3d3Lm9ubGluZXdlYmZvbnRzLmNvbS9pY29uIDwvbWV0YWRhdGE+DQo8Zz48Zz48Zz48cGF0aCBmaWxsPSIjMDAwMDAwIiBkPSJNOTkuOSwxMS43Yy0xOC44LDQuOC00NS43LDIwLjktNTguOSwzNWMtNi42LDcuMi0xNi40LDIyLjEtMjEuNSwzMy4yYy04LjcsMTguMi05LjYsMjIuNy05LjYsNDguN2MwLDI2LjksMC42LDI5LjYsMTEuMSw1MS4xYzEzLjQsMjcuMiwzMC44LDQzLjksNTguNSw1Ny4xYzE4LjIsOC43LDIyLjcsOS42LDQ4LjQsOS42YzI1LjcsMCwzMC4yLTAuOSw0OC40LTkuNmMyNy44LTEzLjEsNDUuMS0yOS45LDU4LjYtNTcuMWMxMC41LTIxLjUsMTEuMS0yNC4yLDExLjEtNTEuNHMtMC42LTI5LjktMTEuMS01MS40QzIxNC42LDM2LjIsMTc4LjgsMTIsMTM0LjYsOS45QzEyMiw5LjMsMTA2LjUsMTAuMiw5OS45LDExLjd6IE0xNDYuMiw0Ny4zYzYsNiw4LjcsMTEuNyw4LjcsMTguMmMwLDYuNi0yLjcsMTIuMi04LjcsMTguMmMtMTEuOSwxMS43LTI0LjUsMTEuNy0zNi40LDBjLTYtNi04LjctMTEuNy04LjctMTguMmMwLTYuNiwyLjctMTIuMiw4LjctMTguMkMxMjEuNywzNS42LDEzNC4zLDM1LjYsMTQ2LjIsNDcuM3ogTTE1NC45LDExNS4xYzguNywyLjcsMTkuNyw4LjcsMjQuNSwxMy4xYzguMSw4LjEsOC40LDkuNiw4LjQsMzcuM2MwLDI2LjMtMC42LDI5LjMtNi42LDM0LjdjLTkuMyw3LjUtMTAuNSw3LjItMTIuNS0zLjNjLTMuMy0xNy4zLTM5LjEtMjYuNi02My0xNi43Yy01LjcsMi40LTEwLjUsNi0xMC41LDcuNWMwLDMuMywxMy43LDcuOCwzNS44LDExLjdjMTQsMi40LDE5LjcsMTAuNSwxMS40LDE1LjhjLTEwLjUsNi45LTQ1LjQtMC4zLTYzLjktMTIuOGwtMTAuMi02Ljl2LTI5YzAtMjUuNywwLjYtMjkuOSw2LjktMzYuN2M3LjgtOS4zLDM0LjQtMTkuMSw1MS43LTE5LjRDMTMzLjQsMTEwLjMsMTQ1LjksMTEyLjQsMTU0LjksMTE1LjF6Ii8+PHBhdGggZmlsbD0iIzAwMDAwMCIgZD0iTTExNC45LDEzNC44Yy0yLjcsMy42LTQuOCw5LTQuOCwxMS45YzAsNy4yLDEwLjUsMTcuMywxNy45LDE3LjNjNy44LDAsMTcuOS0xMC4yLDE3LjktMTcuOWMwLTguNC0xMC41LTE3LjktMTkuMS0xNy45QzEyMi42LDEyOC4yLDExNy4yLDEzMS4yLDExNC45LDEzNC44eiIvPjwvZz48L2c+PC9nPg0KPC9zdmc+" width="64" height="64">
        <h1>Parental Account</h1>
        <h4 >' . $current_user_data['first_name'] . ', this is where you add your minors to your account to manage.</h4>
        <p class="mb-4">The minors need to be 16 years old or younger.</p>
      </div>
    </div>
    </div>
';  



echo '        
<div class="container-xxl p-0 m-0">   
<div class="row justify-content-center p-0 m-0">
<div class="col-12  p-0 m-0">
  <div class="col-lg-12 mb-5 account-type-card  p-0 m-0" data-target="#individual">
      <div class="card bg-light border-0 h-100">
          <div class="card-body text-center p-4 p-lg-5 pt-0 pt-lg-0">
              <div class="feature bg-dark bg-gradient text-white rounded-3 mb-4 mt-n4"><i class="bi bi-person"></i></div>
                    
              <form method="post" action="/setup-parental">
              '.$display->inputcsrf_token().'
  ';


## LIST PARENT ACCOUNT
echo '
<div id="person-container">
<div class="person-group input-group p-0 m-0 mb-2 flex-wrap custom-wrap"> 
  <div class="col-11 col-md-11 fw-bold d-md-inline text-start text-md-center accounttype order-1 order-md-0 d-flex align-items-center">Parent</div>
 
  <div class="col-1 col-md-1 order-2 ms-auto order-md-4 d-flex align-items-center" style="visibility: hidden">
  <button type="button" class="btn btn-sm button ms-auto"><i class="bi bi-dash-circle-fill text-danger h4"></i></button>
</div>

<div class="col-12 col-md order-3 order-md-1"> <input type="text" class="form-control fw-bold" disabled read-only name="first_name0" placeholder="First Name" value="'.$current_user_data['first_name'].'" aria-label="First Name"></div>
<div class="col-12 col-md order-4 order-md-2"> <input type="text" class="form-control fw-bold" disabled read-only name="last_name0" placeholder="Last Name"  value="'.$current_user_data['last_name'].'" aria-label="Last Name"></div>
<div class="col-12 col-md order-5 order-md-3"> <input type="date" class="form-control fw-bold" disabled read-only name="birthdate0" placeholder="Birthdate" value="'.$current_user_data['birthdate'].'"  aria-label="Birthdate"></div>
  </div>
</div>
';


#-------------------------------------------------------------------------------
# retrieve EXISTING MINORS  (basically having to recover a partial setup) -- and then add the next blank if able
#-------------------------------------------------------------------------------
#$current_user_data['user_id']=134;
$sql='select * from bg_users where account_type="minor" and feature_parent_id="'.$current_user_data['user_id'].'"';
$minorcontent='';
$stmt=$database->prepare($sql);
$stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (!empty($results)) {
foreach ($results as $minor_record) {


if ($minor_count>1) $visibilitytag='visible';
$minor_record['local_visibilitytag']=$visibilitytag;
$minor_record['local_counter']=$minor_count;
$minor_record['local_formtype']=$formtype;
$minor_record['lastentrytag']='';
$minorcontent.=generate_minorcontent($minor_record);
$minor_count++;
}
}
if ($minor_count<$maxnumberofminors) {
$minor_record['first_name']='';
$minor_record['last_name']='';
$minor_record['birthdate']='';
$minor_record['local_visibilitytag']=$visibilitytag;
$minor_record['local_counter']=$minor_count;
$minor_record['local_formtype']=$formtype;
$minor_record['lastentrytag']='last-person-container';
$minorcontent.=generate_minorcontent($minor_record);
}

     
echo '<div class="all-minor-rows">
'. $minorcontent.'
</div>
';





## NAV BUTTONS
echo '
  <button type="button" id="addPersonButton" class="btn btn-sm button btn-primary" onclick="addPerson()">Add Person</button>
<hr class="mt-5">
  <div class="row align-items-center">
  <!-- Number of Minors -->
  <div class="col-lg-4 col-md-12 text-lg-start text-center mb-2 mb-lg-0">
    <p class="h4">Number of Minors: <span id="person-count">'.$minor_count.'</span></p>
  </div>
  
  <!-- Total Cost -->
  <div class="col-lg-4 col-md-12 text-lg-start text-center mb-2 mb-lg-0">
    <p class="h4">
    <a href="#" class="secondary" data-bs-toggle="modal" data-bs-target="#parentalModal">
    <i class="bi bi-question-square-fill" '.$display->tooltip('Click to view Pricing').'></i></a>
    Total Cost: <span id="total-cost">$'. $plancost[($minor_count-1)].'</span></p>
  </div>

  <!-- Submit Button -->
  <div class="col-lg-4 col-md-12 text-lg-end text-center">
    <button type="submit" class="btn button btn-success px-5 mx-5">Submit</button>
  </div>
</div>


</form>

  
    </div>

';


break;
}




echo '
</div></div></div></div></div>




<!-- Modal for Parental Account -->
<div class="modal fade" id="parentalModal" tabindex="-1" aria-labelledby="parentalModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="parentalModalLabel">Parental Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <h2>Parental Account Cost:</h4>
      <div class="px-4">
        <p>Unlock a lifetime of benefits with an amazing deal! Here\'s the cost breakdown:</p>
        
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th scope="col">Account Type</th>
              <th scope="col">Cost per Account</th>
              <th scope="col">Lifetime Plan</th>
              <th scope="col" class="d-none d-md-table-cell">Features and Benefits</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Your Account</td>
              <td>$40</td>
              <td>Yes</td>
              <td class="d-none d-md-table-cell">All</td>
            </tr>
            <tr>
              <td>First Two Child Accounts</td>
              <td>$20 each</td>
              <td>Yes</td>
              <td class="d-none d-md-table-cell">All</td>
            </tr>
            <tr>
              <td>Next Two Child Accounts</td>
              <td>$15 each</td>
              <td>Yes</td>
              <td class="d-none d-md-table-cell">All</td>
            </tr>
            <tr>
              <td>Additional Child Accounts</td>
              <td>$10 each</td>
              <td>Yes</td>
              <td class="d-none d-md-table-cell">All</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="4" class="fw-bold text-success">The most you\'ll pay for six children is $130.00 ever (including your account).</td>
            </tr>
          </tfoot>
        </table>
        
        <p class="mt-4">
          <strong>Why this is a great deal:</strong><br>
          - The more accounts you add, the more you save per account. It\'s a win-win!<br>
          - All accounts are lifetime members, meaning you pay once and enjoy forever.<br>
          - With complete access* to all features and benefits, the value simply can\'t be beaten.
        </p>
      </div>
      
  </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

';

$footerattribute['postfooter'] = '
<script>
let personCount = '.$minor_count.';

function addPerson() {
  const minorContainers = document.querySelectorAll(".minor-container");
  const rows = minorContainers.length;
  console.log("Initial count:", rows);

  if (rows > 0) {
    const lastMinorContainer = minorContainers[rows - 1];

    console.log("lastMinorContainer:", lastMinorContainer.innerHTML);

    const newMinorContainer = lastMinorContainer.cloneNode(true);

    console.log("newMinorContainer:", newMinorContainer.innerHTML);
    personCount=rows+1
    //personCount++;
    newMinorContainer.id = `person-container-minor${personCount}`;
    newMinorContainer.querySelector(".accounttype").textContent = `Minor #${personCount}`;

    // Update fields in the clone
    newMinorContainer.querySelectorAll("input").forEach(input => {
      const baseName = input.name.replace(/\d+$/, ""); // remove trailing digits
      input.name = baseName + personCount;
      input.id = baseName + personCount;
      input.value = "";
    });

    newMinorContainer.querySelectorAll("label").forEach(label => {
      const baseFor = label.getAttribute("for").replace(/\d+$/, ""); // remove trailing digits
      label.setAttribute("for", baseFor + personCount);
    });

    const allMinorRows = document.querySelector(".all-minor-rows");

    allMinorRows.appendChild(newMinorContainer);

    console.log("Final count:", document.querySelectorAll(".minor-container").length);
    
    // Recalculate costs and reinitialize tooltips
    calculateCost();
    initializeTooltips();
  }
}



function removePerson(button) {
  const parentContainer = button.closest(".minor-container");

  // Remove all Bootstrap 5 tooltips
  document.querySelectorAll(\'[data-bs-toggle="tooltip"]\').forEach(el => {
    const tooltip = bootstrap.Tooltip.getInstance(el);
    if (tooltip) {
      tooltip.dispose();
    }
  });


  parentContainer.remove();
  
  // Update person count
  personCount--;
  calculateCost();
}



function calculateCost() {
  let cost = 40;
  if(personCount >= 1) cost += 20;
  if(personCount >= 2) cost += 20;
  if(personCount >= 3) cost += 15;
  if(personCount >= 4) cost += 15;
  if(personCount >= 5) cost += 10;
  if(personCount >= 6) cost += 10;
  document.getElementById("person-count").textContent = personCount;
  document.getElementById("total-cost").textContent = "$" + cost;

  if  (personCount >= '.$maxnumberofminors.') {
    // Change the button text to "Account limit reached"
    const addButton = document.getElementById("addPersonButton");
    addButton.disabled = true;
  addButton.textContent = "Account limit reached";
    addButton.classList.remove("btn-primary");
    addButton.classList.add("btn-secondary");
  } else {
    // Change the button text to "Add Person"
    const addButton = document.getElementById("addPersonButton");
    addButton.disabled = false;
  addButton.textContent = "Add Person";
    addButton.classList.remove("btn-secondary");
    addButton.classList.add("btn-primary");
  }
}



</script>
'.$display->tooltip('-js-').'
<script src="/public/js/myaccount.js" language="javascript"></script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
