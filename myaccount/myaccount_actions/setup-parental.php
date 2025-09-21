<?php 
$addClasses[]='Convert';
$addClasses[] = 'createaccount';
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');



#-------------------------------------------------------------------------------
# PROTECT ACCIDENTIAL USAGE
#-------------------------------------------------------------------------------
$allowcontinue=false;
if ($account->isadmin()) { $allowcontinue=true;}
if ($account->isimpersonator()) { $allowcontinue=true;}
if ($app->formposted()) { $allowcontinue=true;}
if((isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'welcome') !== false)) { $allowcontinue=true;}

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

$current_user_data=$session->get('current_user_data');




#-------------------------------------------------------------------------------
# HANDLE ACTIONS
#-------------------------------------------------------------------------------

// Determine the action based on the query string
if ($app->formposted()) {

    $personcount=0;
    $planfee=[20,20,15,15,10,10];
$plancost=[20,40,55,70,80,90];
// Get user_id to use as feature_parent_id
$feature_parent_id = $current_user_data['user_id']; 

for ($i = 1; $i <= 6; $i++) {
    if (isset($_POST["first_name$i"]) && isset($_POST["last_name$i"]) && isset($_POST["birthdate$i"])) {
        $first_name = $_POST["first_name$i"];
        $last_name =$_POST["last_name$i"];
        $birthday = $_POST["birthdate$i"];

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

':account_cost'=>$planfee[$personcount],

':account_type'=>'minor',

':hashed_password' => $current_user_data['password'],
':feature_email'=>strtolower($username.'@mybdaygold.com'),
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
       (feature_parent_id,  first_name,  last_name,  birthdate, username, account_plan, status, account_cost, account_type,  password,   feature_email, email, city, state, zip_code, profile_city, profile_state, profile_zip_code  ) 
VALUES (:feature_parent_id, :first_name, :last_name, :birthday, :username, :account_plan, :status, :account_cost, :account_type,  :hashed_password, :feature_email, :email, :city, :state, :zip_code, :city2, :state2, :zip_code2)";
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
$pay=$plancost[$personcount];
echo $plancost[$personcount];
#echo 'done'; exit;
header('location: /checkout-parental');
exit;
}





#-------------------------------------------------------------------------------
# CREATE CODE
#-------------------------------------------------------------------------------

    // Couldn't generate gift code
   $displaytype='input'; # echo "<h2>An error occurred while generating the gift certificate code. Please try again.</h2>";
  


#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$additionaljs= '';
$headerattribute['additionalcss']='<link rel="stylesheet" href="/public/css/myaccount.css">
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
</style>
';

include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/header.php'); 

$displaytype='xx';
switch ($displaytype) {

##---------------------------------------------------------
case 'xx':
    

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
  echo '
  <!-- Parental Account Start -->
  <div class="container-xxl py-5">
    <div class="container text-center">
      <div class="row justify-content-center">
        <div class="col-12">
  <!-- iCon by oNlineWebFonts.Com --> <img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gU3ZnIFZlY3RvciBJY29ucyA6IGh0dHA6Ly93d3cub25saW5ld2ViZm9udHMuY29tL2ljb24gLS0+DQo8IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPg0KPHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgMjU2IDI1NiIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAwIDAgMjU2IDI1NiIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+DQo8bWV0YWRhdGE+IFN2ZyBWZWN0b3IgSWNvbnMgOiBodHRwOi8vd3d3Lm9ubGluZXdlYmZvbnRzLmNvbS9pY29uIDwvbWV0YWRhdGE+DQo8Zz48Zz48Zz48cGF0aCBmaWxsPSIjMDAwMDAwIiBkPSJNOTkuOSwxMS43Yy0xOC44LDQuOC00NS43LDIwLjktNTguOSwzNWMtNi42LDcuMi0xNi40LDIyLjEtMjEuNSwzMy4yYy04LjcsMTguMi05LjYsMjIuNy05LjYsNDguN2MwLDI2LjksMC42LDI5LjYsMTEuMSw1MS4xYzEzLjQsMjcuMiwzMC44LDQzLjksNTguNSw1Ny4xYzE4LjIsOC43LDIyLjcsOS42LDQ4LjQsOS42YzI1LjcsMCwzMC4yLTAuOSw0OC40LTkuNmMyNy44LTEzLjEsNDUuMS0yOS45LDU4LjYtNTcuMWMxMC41LTIxLjUsMTEuMS0yNC4yLDExLjEtNTEuNHMtMC42LTI5LjktMTEuMS01MS40QzIxNC42LDM2LjIsMTc4LjgsMTIsMTM0LjYsOS45QzEyMiw5LjMsMTA2LjUsMTAuMiw5OS45LDExLjd6IE0xNDYuMiw0Ny4zYzYsNiw4LjcsMTEuNyw4LjcsMTguMmMwLDYuNi0yLjcsMTIuMi04LjcsMTguMmMtMTEuOSwxMS43LTI0LjUsMTEuNy0zNi40LDBjLTYtNi04LjctMTEuNy04LjctMTguMmMwLTYuNiwyLjctMTIuMiw4LjctMTguMkMxMjEuNywzNS42LDEzNC4zLDM1LjYsMTQ2LjIsNDcuM3ogTTE1NC45LDExNS4xYzguNywyLjcsMTkuNyw4LjcsMjQuNSwxMy4xYzguMSw4LjEsOC40LDkuNiw4LjQsMzcuM2MwLDI2LjMtMC42LDI5LjMtNi42LDM0LjdjLTkuMyw3LjUtMTAuNSw3LjItMTIuNS0zLjNjLTMuMy0xNy4zLTM5LjEtMjYuNi02My0xNi43Yy01LjcsMi40LTEwLjUsNi0xMC41LDcuNWMwLDMuMywxMy43LDcuOCwzNS44LDExLjdjMTQsMi40LDE5LjcsMTAuNSwxMS40LDE1LjhjLTEwLjUsNi45LTQ1LjQtMC4zLTYzLjktMTIuOGwtMTAuMi02Ljl2LTI5YzAtMjUuNywwLjYtMjkuOSw2LjktMzYuN2M3LjgtOS4zLDM0LjQtMTkuMSw1MS43LTE5LjRDMTMzLjQsMTEwLjMsMTQ1LjksMTEyLjQsMTU0LjksMTE1LjF6Ii8+PHBhdGggZmlsbD0iIzAwMDAwMCIgZD0iTTExNC45LDEzNC44Yy0yLjcsMy42LTQuOCw5LTQuOCwxMS45YzAsNy4yLDEwLjUsMTcuMywxNy45LDE3LjNjNy44LDAsMTcuOS0xMC4yLDE3LjktMTcuOWMwLTguNC0xMC41LTE3LjktMTkuMS0xNy45QzEyMi42LDEyOC4yLDExNy4yLDEzMS4yLDExNC45LDEzNC44eiIvPjwvZz48L2c+PC9nPg0KPC9zdmc+" width="64" height="64">        <h1>Parental Account</h1>
          <h4 >' . $current_user_data['first_name'] . ', this is where you add your people to your account to manage.</h4>
          <p class="mb-4">The people you add need to be 16 years old or younger.</p>
        </div>
      </div>
  ';  

    echo '
              
    <div class="row justify-content-center">
    <div class="col-12">
            <div class="col-lg-12 mb-5 account-type-card" data-target="#individual">
                <div class="card bg-light border-0 h-100">
                    <div class="card-body text-center p-4 p-lg-5 pt-0 pt-lg-0">
                        <div class="feature bg-dark bg-gradient text-white rounded-3 mb-4 mt-n4"><i class="bi bi-person"></i></div>
                             
                        <form method="post" action="/myaccount/setup-parental">
                        ' . $display->inputcsrf_token() . '
       ';
       
       echo '
         <div id="person-container">
           <div class="person-group input-group mb-2"> 
            <input type="text" class="form-control" name="first_name1" placeholder="First Name" aria-label="First Name">
             <input type="text"class="form-control"  name="last_name1" placeholder="Last Name" aria-label="Last Name">
             <input type="date" class="form-control"  name="birthdate1" placeholder="Birthdate" aria-label="Birthdate">
             <button type="button"  class="btn btn-sm button" style="visibility: hidden"><i class="bi bi-dash-circle-fill text-danger h4"></i></button>
           </div>
         </div>
         <button type="button" class="btn btn-sm button btn-primary" onclick="addPerson()">Add Person</button>
         <p>Person Count: <span id="person-count">1</span></p>
         <p>Total Cost: <span id="total-cost">$20</span></p>
         <button type="submit"  class="btn button btn-success px-5">Submit</button>
       </form>
       
       



        </div>
    
    </div>
          

';


break;


}

echo '
</div></div></div></div></div>
';

$footerattribute['postfooter'] = '
<script>
  let personCount = 1;

  function addPerson() {
    if(personCount < 6) {
      personCount++;
      const container = document.createElement("div");
      container.setAttribute("class", "person-group input-group mb-2");
      container.innerHTML = `
        <input type="text" class="form-control" name="first_name${personCount}" placeholder="First Name" aria-label="First Name">
        <input type="text" class="form-control" name="last_name${personCount}" placeholder="Last Name" aria-label="Last Name">
        <input type="date" class="form-control" name="birthdate${personCount}" placeholder="Birthdate" aria-label="Birthdate">
        <button type="button"  class="btn btn-sm button"  onclick="removePerson(this)"><i class="bi bi-dash-circle-fill text-danger h4"></i></button>
      `;
      document.getElementById("person-container").appendChild(container);
    }
    calculateCost();
  }

  function removePerson(button) {
    button.parentElement.remove();
    personCount--;
    calculateCost();
  }

  function calculateCost() {
    let cost = 0;
    if(personCount >= 1) cost += 20;
    if(personCount >= 2) cost += 20;
    if(personCount >= 3) cost += 15;
    if(personCount >= 4) cost += 15;
    if(personCount >= 5) cost += 10;
    if(personCount >= 6) cost += 10;
    document.getElementById("person-count").textContent = personCount;
    document.getElementById("total-cost").textContent = "$" + cost;
  }
</script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="/public/js/myaccount.js" language="javascript"></script>
';

include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footer.php'); 