<?php
header('location: /myaccount/businessselect'); exit;
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');




$submitpagename='/myaccount/select';

$wizardmode=false;
$wizard['section']='enrollment';
$wizard['step']=3;
if ($current_user_data['account_plan']!='free') {
if (!empty($current_user_data['enrollment_mode']) && $current_user_data['enrollment_mode']=='wizard'){ 
$wizardmode=true;
$wizard['section']='enrollment';
$wizard['step']=3;
} 
}



#-------------------------------------------------------------------------------
# HANDLE AJAX CALL
#-------------------------------------------------------------------------------
if ($app->formposted() && isset($_POST['cid']) && isset($_POST['type']) && $_POST['type']=='ajax') {
$company_id = $_POST['cid'];

// Assuming you have a function that fetches comment based on company_id
$results = $app->getcompanydetails($company_id);

$comment = '<div class="h4 fw-bold">'.$results['company_name'].'</div><div><p>'.$results['description'].'</p></div>';
echo $comment;
exit;
}



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$current_user_data=$session->get('current_user_data');
$current_user_data=$account->getuserdata($current_user_data['user_id'], 'user_id');
$alive=$app->calculateage($current_user_data['birthdate']);
$accountstats=$account->account_getstats();
$plandetails=$app->plandetail('details');

$userplan=$current_user_data['account_plan'];

$selectsused=($accountstats['business_pending']+$accountstats['business_selected']+$accountstats['business_success']);
$selectsleft=($plandetails[$userplan]['max_business_select']-$selectsused);

$selectionList=array();

$initialcount = $selectsused;
$planlimit = $plandetails[$userplan]['max_business_select'];
#breakpoint($plandetails);
#exit;

$headerattribute['additionalcss'] = '
<!-- Bootstrap CSS -->

<!-- Fontawesome Sytle CSS -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="/public/css/discover.css">';


switch ($userplan) {

case 'free':

$plan_objectdisplaytag='d-none';
$plan_pagetitle='  <h1 class="quiz_title m-0 mt-3 mb-3 pt-0">Browse Businesses</h1>';
$plan_buttonlink='detail';
$plan_pagetip='<hr>';

break;


default:

$plan_objectdisplaytag='d-block';
$plan_pagetitle='<h1 class="quiz_title m-0 mt-5 mb-3 pt-0">Select Businesses To Enroll</h1>';

if (!empty($wizardmode)) {
$plan_pagetitle='<h3 class="quiz_title m-0 mt-2 mb-2 pt-0">'."Let's Get you Enrolled!</h3>
<p>Add businesses to your list and we'll enroll.  It's that simple.<br>If you already have account with any business click the ".'
<span class="existingButton btn-sm btn-secondary">Existing</span>'." button and we'll track those benefits for you too.</p>";


}
$plan_buttonlink='addtogallery';
$plan_pagetip=$display->formaterrormessage('
<div class="alert alert-info alert-dismissible fade show" role="alert">
<b>Tip: Add a few businesses each day.</b> You don\'t have to use them all at once.<br>
Some businesses require you to click confirmation messages to complete your enrollment. By spacing it out, you won\'t get flooded with messages.
<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>');

break;
}

$lists=['existinglist'=>'existing', 'selectionlist'=>'selected'];



#-------------------------------------------------------------------------------
# HANDLE THE FORM SUBMISSION ATTEMPT
#-------------------------------------------------------------------------------

if ($app->formposted()) {
if (isset($_POST['selectionlist']) && (is_array($_POST['selectionlist']) || is_array($_POST['existinglist']))) {


foreach ($lists as $processlist=>$processtype) {
  if (!empty($_POST[$processlist])) {
    $listdata[$processlist]=$_POST[$processlist];    
    $$processlist=$listdata[$processlist];
$session->set('goldmine_'.str_replace('list', 'List', $processlist), $listdata[$processlist]);
  }
//     $listdata=
 
//   $selectionList = $_POST['selectionlist'];
// $selectionList = $_POST['existinglist'];
// $session->set('goldmine_selectionList', $selectionList );
// $session->set('goldmine_existingList', $existingList);
}



#-------------------------------------------------------------------------------
# RECORD THE SELECTION
#-------------------------------------------------------------------------------
if (isset($_POST['confirmed'])) {
# $user_id=$current_user_data['user_id'];
$finalcount=0;
foreach ($lists as $processlist=>$processtype) {
$listdata[$processlist]=$_POST[$processlist];   
$rowsInserted=0;
// Prepare the SQL statement with placeholders for a single row
$stmt = $database->prepare("INSERT INTO bg_user_companies (user_id, company_id, create_dt, modify_dt, `status`) VALUES (:user_id, :value, now(), now(), '".$processtype."')");

// Insert multiple rows using individual queries
foreach ($listdata[$processlist] as $value) {
// Bind parameters for each iteration
$stmt->bindParam(':user_id', $current_user_data['user_id']);
$stmt->bindParam(':value', $value);
$stmt->execute();  // execute for each row
$rowsInserted += $stmt->rowCount();  // Increment the number of rows inserted
}
$finalinsertedcount[$processlist]=$rowsInserted;
$finalcount+=$finalinsertedcount[$processlist];
}

if ($finalcount>0) {
/// update the enrollment_mode to normal since they have completed the steps:
$updatefields['enrollment_mode']='normal';
$account->updateSettings($current_user_data['user_id'], $updatefields);
$current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');
}
#breakpoint( $rowsInserted );
// Redirect after all inserts are done
header('location: /myaccount/enrollment');
exit;
} else {



#-------------------------------------------------------------------------------
# DISPLAY THE SELECTION CONFIRMATION
#-------------------------------------------------------------------------------
$lists = ['existinglist' => 'existing', 'selectionlist' => 'selected'];
$finalOutput = [];

foreach ($lists as $processlist => $processtype) {
  $finalOutput[$processlist]['output']='';
  $finalOutput[$processlist]['listoutput']='';
  $finalOutput[$processlist]['counter']=0;

    if (!empty($_POST[$processlist])) {
      $currentList = $_POST[$processlist];
  #  $counter = count($currentList);

    $placeholders = array_map(function ($companyId, $index) {
        return ":company_id_$index";
    }, $currentList, array_keys($currentList));

    $sql = "SELECT * FROM bg_companies WHERE company_id IN (" . implode(',', $placeholders) . ")";
    $stmt = $database->prepare($sql);

    if ($stmt) {
        foreach ($currentList as $index => $companyId) {
            $paramName = ":company_id_$index";
            $stmt->bindValue($paramName, $companyId);
        }
        $stmt->execute();

        $output = '<ul>';
        $listoutput = '';
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $isChecked = in_array($row['company_id'], $currentList) ? 'checked' : '';
            $apponlytag = '';

            if ($processtype === 'selected') {
                if ($row['signup_url'] == $website['apponlytag']) {
                    $apponlytag = '<p class="text-danger">This is an APP ONLY enrollment. We\'ll send you a link to download their app and you can sign up for their program.</p>';
                }
                $output .= '<li class="m-2"><B>' . $row['company_name'] . ':</b> ' . $row['description'] . $apponlytag . '</li>';
            } else {
                $output .= '<li class="m-2"><B>' . $row['company_name'] . '</b></li>';
            }

            $listoutput .= '<input type="hidden" name="' . $processlist . '[]" value="' . htmlentities($row['company_id']) . '" ' . $isChecked . '>';
        }
        $output .= '</ul>';
         $finalOutput[$processlist]['output'] = $output;
        $finalOutput[$processlist]['listoutput'] = $listoutput;
    }
    $finalOutput[$processlist]['counter'] = count($currentList);
  }
}

// Your HTML and other code here, using $finalOutput as needed


/*
$counter=count($selectionList);
// Create an array of named placeholders
$placeholders = array_map(function ($companyId, $index) {
return ":company_id_$index";
}, $selectionList, array_keys($selectionList));

// Build the prepared statement with the IN clause using named placeholders
$sql = "SELECT * FROM bg_companies WHERE company_id IN (" . implode(',', $placeholders) . ")";

// Prepare the statement
$stmt = $database->prepare($sql);

if ($stmt) {
// Bind the values to the named placeholders
foreach ($selectionList as $index => $companyId) {
$paramName = ":company_id_$index";
$stmt->bindValue($paramName, $companyId);
}
$stmt->execute();

// Fetch and process the company records
$output = '<ul>'; 
$listoutput='';
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
// Check if the company_id is in the $selectionList array
$isChecked = in_array($row['company_id'], $selectionList) ? 'checked' : '';
$apponlytag='';

// Output information for each company in LI tags
if ($row['signup_url']==$website['apponlytag']) $apponlytag='<p class="text-danger">This is a APP ONLY enrollment.  We\'ll send you a link to download their app and you can sign up for their program.</p>';

$output .= '<li class="m-2"><B>' . $row['company_name'] . ':</b> ' . $row['description'] . $apponlytag.'</li>';
$listoutput.='<input type="hidden" name="selectionlist[]" value="'.htmlentities($row['company_id']).'" ' . $isChecked . '>';
}




$output .= '</ul>';
}    

*/



#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');
echo '
<div class="container py-6">
<div class="container">
<div class="row">
<div class="col-12  text-center justify-content-center">
<picture>
<source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f44d/512.webp" type="image/webp">
<img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f44d/512.gif" alt="👍" width="64" height="64">
</picture>
<h2>Please Confirm Your '.$finalOutput['selectionlist']['counter'].' Enrollment '.$qik->plural('Selection', $finalOutput['selectionlist']['counter']).'</h2>
</div>
<div class="bg-secondary-subtle p-3 mb-3">
<h6>These are the ones we will enroll you in:</h6>
<p class="">'.$finalOutput['selectionlist']['output'].'</p>
</div>
';

if ($finalOutput['existinglist']['listoutput']>0) {
  echo '
<div class="border-1 border-black bg-light p-3">
<h6>These are the ones you are are saying you already have existing accounts to:</h6>
'.$finalOutput['existinglist']['output'] .'
</div>
';
}
echo '
<div class="row mt-5 text-center justify-content-center">
<div class="col-6">
<a class="btn btn-danger py-3 px-5" href="'.$submitpagename.'">No. Take me back to change them.</a>
</div>
<div class="col-6">
<form action="'.$submitpagename.'" method="post" id="confirmationform">                
'.$display->inputcsrf_token().'
<input type="hidden" name="confirmed" value="Y">
'.$finalOutput['selectionlist']['listoutput'].'
'.$finalOutput['existinglist']['listoutput'].'
<button type="submit" id="submit-button" name="submit_button_confirmed" class="btn btn-success py-3 px-5">Yes! I Want These.</button>


</form>
</div>


</div>
</div>
</div>
</div>
';
# echo print_r($_REQUEST['existinglist'], 1);

include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footer.php'); 
exit;

}
}

}



#-------------------------------------------------------------------------------
# DISPLAY THE BUSINESS SELECTION PAGE
#-------------------------------------------------------------------------------
$containercolumns=12;
$columnsize=20;
$bccolumns_sm=12;
$bccolumns_md=4;
$bccolumns_lg=3;



#-------------------------------------------------------------------------------
# OUTPUT THE PAGE
#-------------------------------------------------------------------------------
$headerattribute['additionalcss']='<link rel="stylesheet" href="/public/css/myaccount.css">
<!-- Google Font -->
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;600&display=swap" rel="stylesheet">



<!-- DataGrid library CSS -->
<link href="/public/css/myaccount-datagrid.min.css" rel="stylesheet" />

<!-- Demo page CSS -->
<link href="/public/css/myaccount-select.css" rel="stylesheet" />


<style>
.img-responsive {
max-width: 100% !important;
height: auto !important;
}

.col-lg-2-4 {
flex: 0 0 '.$columnsize.'%;
max-width: '.$columnsize.'%;
}


.business-card {
transition: none !important;
animation: none !important;
}

.selected-card {
border: 2px solid #f39c12; /* Customize the border color as needed */
}

.btn-disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

span.btn-sm {
  cursor: pointer;
}

</style>';
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');

echo '<div class="container-xl px-4 mt-4">
<!-- Account page navigation-->
';


if ($userplan=='free' || (!empty($wizardmode))) {
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-myaccount.php'); 
}



# BUILD THE TITLE BAR
#-------------------------------------------------------------------------------
$title_content = '
<div class="row">
<div class="col-sm-12">
<div class="text-center">
<div id="commentary">
'.$plan_pagetitle.'
' .$plan_pagetip. '
</div>
</div>
</div>
</div>';



# BUILD THE PROGRESS BAR
#-------------------------------------------------------------------------------
$progressbar_content= '
<div class="row '.$plan_objectdisplaytag.'">
<div class="quiz_backBtn_progressBar mt-4 mb-3">
<div class="row">
<div class="col-9 mb-3">

<!-- For big displays (hidden on screens smaller than medium) -->
<h3 class="d-none d-md-block">Plan Limit: <small class="fw-normal">( <span id="count-display">' . $initialcount . '</span> of ' . $planlimit . ' enrollments this year )</small></h3>

<!-- For mobile displays (hidden on screens medium and larger) -->
<h5 class="d-md-none">Plan: <small class="fw-normal">( <span id="count-display2">' . $initialcount . '</span>/' . $planlimit . ' limit)</small></h5>

</div>


<div class="col-3 mb-2 pb-0 d-flex justify-content-end">
<!-- Submit button -->
<!-- For big displays (hidden on screens smaller than medium) -->

<input type="submit" value="Save List"  id="submit-button"  name="submit_top_desktop" class="btn btn-success py-2 px-5 d-none d-lg-block">

<!-- For mobile displays (hidden on screens medium and larger) -->
<input type="submit" value="Save List"  id="submit-button" name="submit_top_mobile" class="btn btn-success py-2 px-3 d-lg-none">

</div>
</div>
    <div class="row mb-0 pb-0">
        <div class="col mb-0 pb-0">
            <div class="progress">
                <div class="progress-bar bg-dark progress-bar-striped" role="progressbar" style="width: 0%" aria-valuenow="' . $initialcount . '" aria-valuemin="0" aria-valuemax="' . $planlimit . '"></div>
            </div>
        </div>
    </div>
 <div class="row mt-3">
        <div class="col">
            <div class="alert alert-danger" role="alert" id="planlimitalert" style="display: none;">
                You cannot select any more items. You are at your plan limit.
            </div>
        </div>
    </div>
</div>
';
#echo $progressbar_content;



# BUILD THE FILTER
#-------------------------------------------------------------------------------
$search_content='
<!-- text filter control -->
<div class="d-md-none col-12 mb-3">
<input type="text" value="" class="form-control mr-3" placeholder="Search by Name" data-grid-control="text-filter" data-path=".product-name" />
</div>
<div class="d-none d-md-block col-8 mb-3">
<input type="text" value="" class="form-control mr-3" placeholder="Search by Business Name" data-grid-control="text-filter" data-path=".product-name" />
</div>
';


$sort_content='
<!-- sort control -->
<div class="col-4  d-none d-md-block mb-3">
<select data-grid-control="sort" class="form-control">
<option data-path=".company-name" data-direction="asc" data-type="text" selected>Sort A-Z</option>
<option data-path=".company-name" data-direction="desc" data-type="text">Sort Z-A</option>
<option data-path=".popular-count" data-direction="desc" data-type="number">Sort most popular</option>
<option data-path=".popular-count" data-direction="asc" data-type="number">Sort least popular</option>
<option data-path=".star-rating" data-direction="desc" data-type="number">Sort highest ratings</option>
<option data-path=".star-rating" data-direction="asc" data-type="number">Sort lowest ratings</option>
</select>
</div>
';



# BUILD THE PAGINATION
#-------------------------------------------------------------------------------
$pagination_content = '
<!-- pagination start -->
<div class="col-12 d-none d-md-flex align-items-start align-items-md-center justify-content-between flex-wrap text-dark">

<!-- pages number label -->
<div data-grid-control="label" data-type="pagination-pages" class="pagination-label text-dark mb-3"></div>

<!-- pagination control -->
<nav aria-label="pagination" data-grid-control="pagination" class="text-dark mb-3"></nav>

<!-- number of items per page -->
<select class="form-control page-size-control mb-3" data-grid-control="page-size">
<option value="24">24 items per page</option>
<option value="48">48 items per page</option>
<option value="96">96 items per page</option>
<option value="Infinity">all</option>
</select>

<!-- reset all controls -->
<button type="button" data-grid-control="reset-button" class="btn btn-secondary btn-sm d-none d-lg-block mb-3 mx-3 px-3">Reset</button>
</div>
<!-- pagination end -->
';



# SET UP CATEGOIES
#-------------------------------------------------------------------------------
$categories = ['All', 'Food', 'Beverage', 'Beauty', 'Retail', 'Other', 'Sponsored', 'Suppressed'];
$benefits = ['On Enrollment', 'All Month', 'Week Of', 'On Birthday Only'];



# BUILD CATEGORY FILTER
#-------------------------------------------------------------------------------
$categoryfilter_content = ' <div class="btn-group flex-wrap mb-2" role="group" aria-label="Business Categories">';
foreach ($categories as $item) {
if($item=='Food') {
$active = 'active';
$buttonClass = 'btn-secondary'; // Filled in for active button
$checked = 'checked';
} else {
$active = '';
$buttonClass = 'btn-outline-secondary'; // Outlined for inactive buttons
$checked = '';
}
$itemlower = strtolower($item);
$itemlowerdg='.'.$itemlower;
if ($item=='All') $itemlowerdg='';
$categoryfilter_content .= '
<label class="btn btn-sm '.$buttonClass.' '.$active.' me-3">
<input type="radio" class="btn-check" name="options" id="'.$itemlower.'-rb1" data-id="'.$itemlower.'" data-grid-control="radio-filter" data-path="'.$itemlowerdg.'" '.$checked.' autocomplete="off"> 
'.$item.' </label>
';
}
$categoryfilter_content .= '</div>';



# BUILD BENEFIT FILTER
#-------------------------------------------------------------------------------
$benefitsfilter_content='<div class="btn-group  flex-wrap" role="group" aria-label="Benefit Offerings">';
foreach ($benefits as $item){
$active = '';
$buttonClass = 'btn-outline-secondary'; // Outlined for inactive buttons
$checked = '';
$itemlower=str_replace(' ', '_', strtolower($item));
/* $benefitsfilter_content.='
<div class="form-check me-5">
<input class="form-check-input" type="checkbox" id="'.$itemlower.'-cb" data-id="'.$itemlower.'" data-grid-control="checkbox-filter" data-path=".'.$itemlower.'" />
<label class="form-check-label" for="'.$itemlower.'-cb">'.$item.'</label>
</div>
';
*/
$benefitsfilter_content .= '
<label class="btn btn-sm '.$buttonClass.' '.$active.' me-3">
<input type="radio" class="btn-check" name="options" id="'.$itemlower.'-cb" data-id="'.$itemlower.'" data-grid-control="radio-filter" data-path=".'.$itemlower.'" > 
'.$item.' </label>
';
}
$benefitsfilter_content .= '
<label class="btn btn-sm btn-outline-secondary ms-3">
<input type="radio" class="btn-check" name="options" id="apponly-cb" data-id="apponly" data-grid-control="radio-filter" data-path=".apponly" >App Only</label>
';

$benefitsfilter_content .= '</div>';





$selectionList=$session->get('goldmine_selectionList', []);
$existingList=$session->get('goldmine_existingList', []);



#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# BUILD THE LIST OF BUSINESS CARDS
#-------------------------------------------------------------------------------
$content ='<div class="row" data-grid>';
$itemcounter = 0;
$menus='';


#-------------------------------------------------------------------------------
/// create the selection exclusions -- profile related
$listoptions = array();
if (isset($alive['year']) && $alive['year'] !== '') {
    $listoptions['age'] = $alive['year'];
}
if (isset($current_user_data['statecode']) && $current_user_data['statecode'] !== '') {
    $listoptions['region'] = $current_user_data['statecode'];
}


#-------------------------------------------------------------------------------
foreach ($categories as $category) {
$categorylower=strtolower($category);

$records = $app->getSelectionCompanies(500, $category, null, $listoptions);

$uniqueRecords = array_values(array_unique(array_map(function($record) {
return serialize($record);
}, $records)));

$uniqueRecords = array_map(function($record) {
return unserialize($record);
}, $uniqueRecords);

$categorycompanycount = count($uniqueRecords);

foreach ($uniqueRecords as $item_company) {



## -------------------------------------------------------------------------------
## -- start excluding companies that hit restrictions (ie: age, allergies)
#breakpoint($item);
if (($item_company['minage']> $alive['years']) || ($item_company['maxage']< $alive['years']))  continue; ## SKIP COMPANY BASED ON AGE LIMIT



## -------------------------------------------------------------------------------
$isChecked = in_array($item_company['company_id'], $selectionList) ? 'checked' : '';
$isExisting = in_array($item_company['company_id'], $existingList) ? 'checked' : '';


if ($current_user_data['account_type']=='admin')
$setuplink='<a href="//bgrab.birthday.gold/companysetup?cid='.$item_company['company_id'].'" target="_companysetup"><i class="bi bi-gear text-secondary me-2"></i></a>';
else $setuplink='';



$apponlyicon='';
if ($item_company['signup_url']==$website['apponlytag'])  $apponlyicon='<i class="fa-solid fa-mobile-screen-button fa-fw text-danger apponly me-2"></i>';


#$benefits = ['On Enrollment', 'All Month', 'Week Of', 'On Birthday Only'];
$randomBenefit = $benefits[array_rand($benefits)];
$benefitClass = str_replace(' ', '_', strtolower($randomBenefit));
switch($randomBenefit){
case 'On Enrollment': $benefiticon='<i class="bi bi-calendar-check '.$benefitClass.'"></i>'; break;
case 'All Month': $benefiticon='<i class="bi bi-calendar3 '.$benefitClass.'"></i>'; break;
case 'Week Of': $benefiticon='<i class="bi bi-calendar-week '.$benefitClass.'"></i>'; break;
case 'On Birthday Only': $benefiticon='<i class="bi bi-calendar-event '.$benefitClass.'"></i>'; break;
}



$content.= '

<!------------- COMPANY START ---->
<div class="row d-flex align-items-stretch">
<div class="col-'.$bccolumns_sm.' col-md-'.$bccolumns_md.' col-lg-'.$bccolumns_lg.' mb-4 business-card" data-grid-item>

<div class="card ' . ($isChecked ? 'selected-card' : '') . '  h-100">
<img src="'. $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']).'" class="card-img-top img-responsive" alt="" />
<div class="card-body d-flex flex-column">
<h6 class="card-title">
'.$setuplink.'<span class="product-name">' . $item_company['company_name'] . '</span>
</h6> 
<!--     <span class="badge badge-info on-sale">Sponsored</span> -->
<p class="card-text business-descriptionx"><small> ' . $item_company['description'] . '</small></p>

<!--          <p class="card-text sale-price">$33.99</p>   
<p class="card-text '.$benefitClass.'"><small class="text-muted">'.$randomBenefit.'</small></p> -->
<!--   <p class="card-text sold"><small class="text-muted">253 sold</small></p>                 -->
<div class="mt-auto">
';
//=============================
switch ($userplan) {
  ##------------------------------------------------------
  case 'free':
  $content.= '  <a href="'.$item_company['info_url'].'" target="_company" class="btn btn-primary">Visit Website <i class="bi bi-box-arrow-up-right"></i></a>';
  $existingbtn='';
  break;
  ##------------------------------------------------------
  default:
  $content.= '  <a href="#" class="btn btn-primary add-to-gallery" data-company-id="' . $item_company['company_id'] . '"><i class="bi bi-plus"></i> Add to List</a>';
  $existingbtn='
  <div>
  <span class="existingButton btn-sm btn-secondary" data-company-id="' . $item_company['company_id'] . '">Existing</span>
  <input class="existingCheckbox quiz_checkbox d-none" type="checkbox" name="existinglist[]" value="' . $item_company['company_id'] . '" ' . $isExisting . '>      
  </div>';
  break;
  }
//=============================
$content.= ' <input class="quiz_checkbox d-none" type="checkbox" name="selectionlist[]" id="' . $item_company['company_id'] . '" value="' . $item_company['company_id'] . '" ' . $isChecked . '  />
</div>
</div>

<div class="card-footer tags d-flex justify-content-between">
<div>
'.$apponlyicon.'
'.$benefiticon.' <a href="#" class="text-muted '.$categorylower.' ms-2" data-grid-control="button-filter" data-id="'.$categorylower.'" data-path=".'.$categorylower.'">'.$category.'</a>
<span class="card-text popular-count me-2">'.$item_company['usage_count'].'</span><span class="card-text star-rating"><i class="fas fa-star text-primary"></i> 5.0</span>
</div>
'.$existingbtn.'
</div>


</div>
</div>
<!-- end of ' . $itemcounter . '  -->
</div>
<!------------- COMPANY END ---->

';

}
}



# DISPLAY THE TOP SECTION 
#-------------------------------------------------------------------------------
echo '
<div class="container bg-white py-2">
';



echo '
<form action="'.$submitpagename.'" method="post"  id="selectionform">    

'.$display->inputcsrf_token().'
<!--    TITLE START --->
'.
$title_content.'

<!--    PROGRESS BAR START --->
'.
$progressbar_content.'
';



# DISPLAY THE FILTERS SECTION 
#-------------------------------------------------------------------------------
## put it in collapsed container
echo '
<!--    FILTER ACCORDIAN START --->
<div class="accordion" id="accordionFilter">
<div class="accordion-item">
<h2 class="accordion-header" id="headingOne">
<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
<i class="bi bi-caret-down-fill"></i> Filter 
</button>
</h2>
<div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionFilter">
<div class="accordion-body">
';



echo '
<!-- top controls -->
<div class="row">
<!-- "Filter by title" and "Sort" on one top row -->
'.$search_content.
'
'.$sort_content.
'  
'.$pagination_content.
'

<!--  filters on line 1 -->
<div class="row">
<div class="col-12">
<!-- <div class="cb-filters-1"> -->
<span class="fw-bold me-3">Categories: </span>
'.$categoryfilter_content.'
<!-- </div> -->
</div>
</div>
';


echo '
<!-- Free shipping/on sale on line 2 -->
<div class="row">
<div class="col-12 d-none d-md-block  d-flex flex-row flex-wrap align-items-center">
<span class="fw-bold me-3">Benefit Offering: </span>
'.$benefitsfilter_content.'

</div>
</div>

';

echo '
</div>
';

echo '
</div>
</div>
</div>
</div>  <!-- end filters -->
';



# DISPLAY THE LIST OF BUSINESS CARDS
#-------------------------------------------------------------------------------
echo '
<section class="quiz_section" id="quizeSection">
<hr>
<div class="container">
<div class="row mt-3">
<div class="col-sm-'.$containercolumns.'">
';

echo  $content;

echo '  
</div>
</div>
</div>
';

echo '
<div class="text-center pt-1 pb-1">
<!-- Submit button -->

<input type="submit" value="Save List"  id="submit-button"  name="submit_bottom"  class="btn btn-success btn-block py-2 px-5 '.$plan_objectdisplaytag.'">

'.$pagination_content.'
</div>
</form>
</section>
';


echo '  
</div>
</div>
';



# END THE PAGE
#-------------------------------------------------------------------------------
$footerattribute['postfooter'] = '
<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<!-- Bootstrap Script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script>
';


$footerattribute['postfooter'] = "
<script>
// //////////////////////////////////////////////////// PROGRESS BAR ---------------------------------------------------
const progressBar = document.querySelector('.progress-bar');
const checkboxes = document.querySelectorAll('.quiz_checkbox');
let initialCount = {$initialcount}; // Initial checked count
const alertDiv = document.getElementById('planlimitalert'); // Get the alert div
const countDisplay = document.getElementById('count-display'); // Get the count display element
const countDisplay2 = document.getElementById('count-display2'); // Get the count display element

const updateProgress = () => {
let checkedCount = Array.from(checkboxes).filter(box => box.checked).length;
let totalSelections = checkedCount + initialCount;
const percentage = Math.min(100 * totalSelections / {$planlimit}, 100);

progressBar.style.width = `\${percentage}%`;
countDisplay.textContent = `\${totalSelections}`; // Update count display
countDisplay2.textContent = `\${totalSelections}`; // Update count display

if (totalSelections >= {$planlimit}) {
Array.from(checkboxes).forEach(box => {
if (!box.checked) {
box.disabled = true;
}
});
alertDiv.style.display = 'block'; // Show the alert div
} else {
Array.from(checkboxes).forEach(box => {
box.disabled = false;
});
alertDiv.style.display = 'none'; // Hide the alert div
}
};

// Initialize the progress bar
updateProgress();

// Attach event listeners to checkboxes
checkboxes.forEach(checkbox => {
checkbox.addEventListener('change', updateProgress);
});



// //////////////////////////////////////////////////// SET MAX CARD HEIGHT ---------------------------------------------------
window.addEventListener('load', function() {
let maxHeight = 0;
const descriptions = document.querySelectorAll('.business-description');

// Find the maximum height
descriptions.forEach(description => {
const height = description.clientHeight;
if (height > maxHeight) maxHeight = height;
});

// Apply the maximum height to all descriptions
descriptions.forEach(description => {
description.style.minHeight = maxHeight + 'px';
});
});



// //////////////////////////////////////////////////// ADD TO LIST / EXISTING BUTTONS ---------------------------------------------------
document.addEventListener('DOMContentLoaded', function() {
  const checkboxes = document.querySelectorAll('.quiz_checkbox');
  let initialCount = {$initialcount}; // Initial checked count

  document.querySelectorAll('.add-to-gallery').forEach(function(addButton) {
    addButton.addEventListener('click', function(event) {
      const companyId = addButton.getAttribute('data-company-id');
      const checkbox = document.getElementById(companyId);
      const existingButton = document.querySelector(`.existingButton[data-company-id=\"\${companyId}\"]`);

      let checkedCount = Array.from(checkboxes).filter(box => box.checked).length;
      let totalSelections = checkedCount + initialCount;

      if (totalSelections < {$planlimit} || checkbox.checked) {
        checkbox.checked = !checkbox.checked;
      }

      if (checkbox.checked) {
        addButton.innerHTML = '<i class=\"fas fa-minus\"></i> Remove';
        addButton.classList.remove('btn-primary');
        addButton.classList.add('btn-secondary');

        existingButton.disabled = true;
        existingButton.classList.add('btn-disabled');
      } else {
        addButton.innerHTML = '<i class=\"bi bi-plus\"></i> Add to List';
        addButton.classList.remove('btn-secondary');
        addButton.classList.add('btn-primary');

        existingButton.disabled = false;
        existingButton.classList.remove('btn-disabled');
      }

      event.preventDefault();
      updateProgress();
      return false;
    });
  });

  const existingButtons = document.querySelectorAll('.existingButton');

  existingButtons.forEach(function(existingButton) {
    existingButton.addEventListener('click', function() {
      const companyId = existingButton.getAttribute('data-company-id');
      const existingCheckbox = document.querySelector(`input[name=\"existinglist[]\"][value=\"\${companyId}\"]`);
      const addButton = document.querySelector(`.add-to-gallery[data-company-id=\"\${companyId}\"]`);

      existingCheckbox.checked = !existingCheckbox.checked;

      if (existingCheckbox.checked) {
        existingButton.classList.remove('btn-secondary');
        existingButton.classList.add('btn-success');

        addButton.disabled = true;
        addButton.classList.add('btn-disabled');
      } else {
        existingButton.classList.remove('btn-success');
        existingButton.classList.add('btn-secondary');

        addButton.disabled = false;
        addButton.classList.remove('btn-disabled');
      }
    });
  });
});
";
/*


// //////////////////////////////////////////////////// ADD TO LIST BUTTONS ---------------------------------------------------
document.addEventListener('DOMContentLoaded', function() {
const checkboxes = document.querySelectorAll('.quiz_checkbox');
let initialCount = {$initialcount}; // Initial checked count

document.querySelectorAll('.add-to-gallery').forEach(function(button) {
button.addEventListener('click', function(event) {
var companyId = button.getAttribute('data-company-id');
var checkbox = document.getElementById(companyId);

let checkedCount = Array.from(checkboxes).filter(box => box.checked).length;
let totalSelections = checkedCount + initialCount;

// Only toggle the checkbox if below the plan limit or if unchecking
if (totalSelections < {$planlimit} || checkbox.checked) {
checkbox.checked = !checkbox.checked;
}

console.log(`Button clicked for company ID: \${companyId}. Checkbox state: \${checkbox.checked ? 'Checked' : 'Unchecked'}`); // Log the company ID and checkbox state

// Updates the button text and appearance based on the checkbox state
if (checkbox.checked) {
button.innerHTML = '<i class=\"fas fa-minus\"></i> Remove';
button.classList.remove('btn-primary');
button.classList.add('btn-secondary');

// Add the selected-card class to the card
checkbox.closest('.card').classList.add('selected-card');
} else {
button.innerHTML = '<i class=\"bi bi-plus\"></i> Add to List';
button.classList.remove('btn-secondary');
button.classList.add('btn-primary');

// Remove the selected-card class from the card
checkbox.closest('.card').classList.remove('selected-card');
}

event.preventDefault(); // Prevents the default link action

updateProgress(); // Update the progress bar
return false; // Additional measure to prevent the default action
});
});
});


// //////////////////////////////////////////////////// EXISTING ACCOUNT BUTTONS ---------------------------------------------------
document.addEventListener('DOMContentLoaded', function() {
const existingButtons = document.querySelectorAll('.existingButton');

existingButtons.forEach(function(existingButton) {
const companyId = existingButton.getAttribute('data-company-id');
const existingCheckbox = document.querySelector(`input[name=\"existinglist[]\"][value=\"\${companyId}\"]`);

existingButton.addEventListener('click', function() {
// Toggle checkbox state
existingCheckbox.checked = !existingCheckbox.checked;

// Update button color
if (existingCheckbox.checked) {
existingButton.classList.remove('btn-secondary');
existingButton.classList.add('btn-success');
} else {
existingButton.classList.remove('btn-success');
existingButton.classList.add('btn-primary');
}
});
});
});

*/


$footerattribute['postfooter'] .= "
// //////////////////////////////////////////////////// FILTER BUTTONS ---------------------------------------------------
document.querySelectorAll('.btn-group .btn-check').forEach(function(radioInput) {
radioInput.addEventListener('change', function(event) {
var parentGroup = event.target.closest('.btn-group');

parentGroup.querySelectorAll('.btn').forEach(function(button) {
button.classList.remove('active');
button.classList.remove('btn-secondary'); // Remove filled class
button.classList.add('btn-outline-secondary'); // Add outlined class
});

var parentButton = event.target.closest('.btn');

if (event.target.checked) {
parentButton.classList.add('active');
parentButton.classList.remove('btn-outline-secondary'); // Remove outlined class
parentButton.classList.add('btn-secondary'); // Add filled class
}
});
});

</script>
<script src='/public/js/myaccount.js'></script>


<!-- // //////////////////////////////////////////////////// REQUIRED DATA GRID STUFF --------------------------------------------------- -->
<!-- DataGrid library JavaScript -->  </script>
<script src='/public/js/myaccount-datagrid.js'></script>

<script>
datagrid({
currentPage: 0,
pageSize: 24,
pagesRange: 5,
});
</script>


<!-- // //////////////////////////////////////////////////// FILTER STUFF ---------------------------------------------------  -->
<script>
$(document).ready(function() {
$('#accordionFilter').on('hide.bs.collapse', function(e) {
$(e.target).prev('.accordion-header').find('.bi').removeClass('bi-caret-down-fill').addClass('bi-caret-up-fill');
});

$('#accordionFilter').on('show.bs.collapse', function(e) {
$(e.target).prev('.accordion-header').find('.bi').removeClass('bi-caret-up-fill').addClass('bi-caret-down-fill');
});
});
</script>
";



$session->unset('goldmine_selectionList');
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.php');
