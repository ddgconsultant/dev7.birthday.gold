<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$submitpagename = '/myaccount/select';
$submitpagename = '/myaccount/businessselect-list';


$enroll_label_default ="";
$enroll_label_active ="Remove" ;

$have_label_default ="";
$have_label_active ="Have";

$resultsize = 300;
$page = 1;
$limittag = $resultsize . ',' . $page;
$sortorder = 'name';
$wizardmode = false;
$wizard['section'] = 'enrollment';
$wizard['step'] = 3;
if ($current_user_data['account_plan'] != 'free') {
  if (!empty($current_user_data['enrollment_mode']) && $current_user_data['enrollment_mode'] == 'wizard') {
    $wizardmode = true;
    $wizard['section'] = 'enrollment';
    $wizard['step'] = 3;
  }
}



#-------------------------------------------------------------------------------
# HANDLE AJAX CALL
#-------------------------------------------------------------------------------
if ($app->formposted() && isset($_POST['cid']) && isset($_POST['type']) && $_POST['type'] == 'ajax') {
  $company_id = $_POST['cid'];

  // Assuming you have a function that fetches comment based on company_id
  $results = $app->getcompanydetails($company_id);

  $comment = '<div class="h4 fw-bold">' . $results['company_name'] . '</div><div><p>' . $results['description'] . '</p></div>';
  echo $comment;
  exit;
}



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$current_user_data = $session->get('current_user_data');
$current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');
$alive = $app->calculateage($current_user_data['birthdate']);
$accountstats = $account->account_getstats();
#$plandetails = $app->plandetail('details');
$plandatafeatures=$app->plandetail('details_id', $current_user_data['account_product_id']);

$userplan = $current_user_data['account_plan'];

$selectsused = ($accountstats['business_pending'] + $accountstats['business_selected'] + $accountstats['business_success']);
$selectsleft = ($plandatafeatures['max_business_select'] - $selectsused);

$selectionList = array();

$initialcount = $selectsused;
$planlimit = $plandatafeatures['max_business_select'];



$headerattribute['additionalcss'] = '
<!-- Bootstrap CSS -->

<!-- Fontawesome Sytle CSS -->
<!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">  -->
<link rel="stylesheet" href="/public/css/discover.css">

<style>
.existingButton.border-success {
background-color: inherit;
border-color: #28a745 !important; /* Success color */
}

.bi-heart.text-danger {
color: #dc3545 !important; /* Danger color */
}

.bi-heart-fill.text-danger {
color: #dc3545 !important; /* Danger color */
}
</style>
';

switch ($userplan) {
  case 'free':
    $plan_objectdisplaytag = 'd-none';
    $plan_pagetitle = '  <h1 class="quiz_title m-0 mt-3 mb-3 pt-0">Browse '.ucfirst($website['biznames']).'</h1>';
    $plan_buttonlink = 'detail';
    $plan_pagetip = '<hr>';
    break;
  default:
    $plan_objectdisplaytag = 'd-block';
    $plan_pagetitle = '<h1 class="quiz_title m-0 mt-5 mb-3 pt-0">Select '.ucfirst($website['biznames']).' To Enroll</h1>';

    if (!empty($wizardmode)) {
      $plan_pagetitle = '<h3 class="quiz_title m-0 mt-2 mb-2 pt-0">' . "Let's Get you Enrolled!</h3>
<p>Add business to your list and we'll enroll.  It's that simple.<br>If you already have account with any business click the " . '
<span class="existingButton btn-sm btn-secondary">Existing</span>' . " button and we'll track those benefits for you too.</p>";
    }
    $plan_buttonlink = 'addtogallery';
    $plan_pagetip = $display->formaterrormessage('
<div class="alert alert-info alert-dismissible fade show" role="alert">
<b>Tip: Add a few businesses each day.</b> You don\'t have to use them all at once.<br>
Some businesses require you to click confirmation messages to complete your enrollment. By spacing it out, you won\'t get flooded with messages.
<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>');

    break;
}

$lists = ['existinglist' => 'existing', 'selectionlist' => 'selected'];

$get_rewardcategories = $app->get_rewardcategories();
$rewardiconlist = $get_rewardcategories[1];

list($rewardCategoriesData, $iconList) = $app->get_rewardcategories([], 'extended');



$containercolumns = 12;
$columnsize = 20;
$bccolumns_sm = 12;
$bccolumns_md = 4;
$bccolumns_lg = 3;


#- = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = 
include($_SERVER['DOCUMENT_ROOT'] . '/myaccount/module_enrollment/component_filter.inc');
include($_SERVER['DOCUMENT_ROOT'] . '/myaccount/module_enrollment/page_components.inc');
#- = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = 



#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# HANDLE THE FORM POSTING ATTEMPT
#-------------------------------------------------------------------------------
#- = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = 
include($_SERVER['DOCUMENT_ROOT'] . '/myaccount/module_enrollment/form_postselection.inc');
#- = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = 

// handle search request    
if ($app->formposted() && !empty($_REQUEST['search'])) {
}


// handle sort request    
if ($app->formposted()) {
}






#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
#ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/core/' . $website['ui_version'] . '/header3.inc');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<!--- START PAGE CONTENT -->
<div class="container">
<div class="row">
<div class="content">
<div class="row g-3">
';



# FILTER PANEL COLUMN
#-------------------------------------------------------------------------------
echo $filterpanel_content;   ## FROM COMPONENT_FILTER





# BUSINESS LIST COLUMN
#-------------------------------------------------------------------------------
echo '
<div class="col-xxl-10 col-xl-9">
<div class="card mb-3">
<div class="card-header d-flex justify-content-between">
<h5 class="mb-0 mt-1 flex-grow-1 ">'.ucfirst($website['biznames']).'</h5>
<div class="badge-container">
<span class="badge badge-subtle-secondary text-white ">   ' . $titleplantag . '</span>
</div>
</div>
<div class="card-body pt-0 pt-md-3">
<div class="row g-3 align-items-center">
<div class="col-auto d-xl-none">
<button class="btn btn-sm p-0 btn-link " type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas" aria-controls="filterOffcanvas"><span class="bi bi-funnel-fill fs-9 text-700"></span></button>
</div>
<div class="col">';
echo '<div>';


# PROGRESS BAR
#-------------------------------------------------------------------------------
echo $progressbar_content;   ## FROM PAGE_COMPONENTS





# ACTION ROW
#-------------------------------------------------------------------------------
echo '<div class="container mx-0 px-0">
<div class="row align-items-center mx-0 px-0">
<div class="col-md-8 mx-0 px-0">
';



# SEARCH BAR
#-------------------------------------------------------------------------------

# echo $searchbar_content;   ## FROM PAGE_COMPONENTS
echo '   <input type="text" id="searchBar" class="form-control mb-4" placeholder="Search company...">';

echo '
</div>
';



# SORT BY BAR
#-------------------------------------------------------------------------------
/*
echo '
<div class="col me-3 me-sm-0 p-0">
<div class="row g-0 g-md-3 justify-content-end">
<div class="col-auto">
<form class="row gx-2" method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="sortresult" id="sortresultform" >
' . $display->inputcsrf_token() . '
';


echo '
<div class="col-auto d-none d-lg-block"><small class="fw-semi-bold">Sort by:</small></div>
<div class="col-auto">
<select name="sort" id="sortterm" class="form-select form-select-sm" aria-label="Bulk actions">
';
echo $display->list_sortorder($sortorder);
echo '
</select>
</div>
</div>
</form>
<script>
$(document).ready(function() {
$("#sortterm").change(function() {
$("#sortresultform").submit();
});
});
</script>
</div>
</div>
</div>
';
*/



# VIEW MODE BAR
#-------------------------------------------------------------------------------
echo '
<div class="col-md-4 text-end">
<div class="d-flex justify-content-end align-items-center">
<small class="fw-semi-bold d-none d-lg-block lh-1">View:</small>
<div class="d-flex">
<a class="btn btn-link btn-sm text-400 hover-700" href="/myaccount/businessselect-grid" data-bs-toggle="tooltip" data-bs-placement="top" title="Grid View">
<i class="bi bi-grid-3x3-gap-fill fs-8" data-fa-transform="down-1"></i>
</a>
<a class="btn btn-link btn-sm px-1 text-700" href="/myaccount/businessselect-list" data-bs-toggle="tooltip" data-bs-placement="top" title="List View">
<i class="bi bi-list-task fs-8" data-fa-transform="down-1"></i>
</a>
</div>
</div>
</div>
';
echo '
</div>
</div>
</div>
</div>';



# SELECTION FORM
#-------------------------------------------------------------------------------
echo '<form action="' . $submitpagename . '" method="POST" id="selectionform"  name="selectionform">  
' . $display->inputcsrf_token() . ' ';



# YES SUBMIT BUTTON
#-------------------------------------------------------------------------------
echo '
<div class="col-auto text-end">
<button type="submit" id="submit-button-top" name="submit_button_confirmed"  value="top" class="btn btn-sm btn-success py-3 px-5">Yes! I Want These</button>
</div>
';


echo '</div>';
##### END OF HEADER



#-------------------------------------------------------------------------------
# OUTPUT THE PAGE
#-------------------------------------------------------------------------------
$headerattribute['additionalcss'] = '<link rel="stylesheet" href="/public/css/myaccount.css">
';

#- = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = 
include($_SERVER['DOCUMENT_ROOT'] . '/myaccount/module_enrollment/css_stylesheet.inc');
#- = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = 



echo '<div class="container-xl  m-0 p-0  mt-4">
<!-- Account page navigation-->

<div class="row mb-3 g-3">
';
/*
if ($userplan == 'free' || (!empty($wizardmode))) {
  include($_SERVER['DOCUMENT_ROOT'] . '/core/' . $website['ui_version'] . '/nav-myaccount.php');
}
*/


# GRAB AND DISPLAY BUSINESSES
#-------------------------------------------------------------------------------
$categories = ['All', 'Food', 'Beverage', 'Beauty', 'Retail', 'Other', 'Sponsored', 'Suppressed'];
$benefits = ['On Enrollment', 'All Month', 'Week Of', 'On Birthday Only'];

$categoryfilter_content = ' <div class="btn-group flex-wrap mb-2" role="group" aria-label="Business Categories">';
foreach ($categories as $item) {
  if ($item == 'Food') {
    $active = 'active';
    $buttonClass = 'btn-secondary';
    $checked = 'checked';
  } else {
    $active = '';
    $buttonClass = 'btn-outline-secondary';
    $checked = '';
  }
  $itemlower = strtolower($item);
  $itemlowerdg = '.' . $itemlower;
  if ($item == 'All') $itemlowerdg = '';
  $categoryfilter_content .= '
<label class="btn btn-sm ' . $buttonClass . ' ' . $active . ' me-3">
<input type="radio" class="btn-check" name="options" id="' . $itemlower . '-rb1" data-id="' . $itemlower . '" data-grid-control="radio-filter" data-path="' . $itemlowerdg . '" ' . $checked . ' autocomplete="off"> 
' . $item . ' </label>
';
}
$categoryfilter_content .= '</div>';

$benefitsfilter_content = '<div class="btn-group  flex-wrap" role="group" aria-label="Benefit Offerings">';
foreach ($benefits as $item) {
  $active = '';
  $buttonClass = 'btn-outline-secondary';
  $checked = '';
  $itemlower = str_replace(' ', '_', strtolower($item));
  $benefitsfilter_content .= '
<label class="btn btn-sm ' . $buttonClass . ' ' . $active . ' me-3">
<input type="radio" class="btn-check" name="options" id="' . $itemlower . '-cb" data-id="' . $itemlower . '" data-grid-control="radio-filter" data-path=".' . $itemlower . '" > 
' . $item . ' </label>
';
}
$benefitsfilter_content .= '
<label class="btn btn-sm btn-outline-secondary ms-3">
<input type="radio" class="btn-check" name="options" id="apponly-cb" data-id="apponly" data-grid-control="radio-filter" data-path=".apponly" >App Only</label>
';

$benefitsfilter_content .= '</div>';

$selectionList = $session->get('goldmine_selectionList', []);
$existingList = $session->get('goldmine_existingList', []);

$content = '

<div class="row mb-3 g-3">
';
$itemcounter = 0;
$menus = '';
$counter['total'] = 0;
$counter['record'] = 0;
$counter['display'] = 0;
$counter['rewards'] = 0;

foreach ($categories as $category) {
  if ($counter['total'] > $resultsize) continue;
  $categorylower = strtolower($category);

  $records = $app->getSelectionCompanies(500, $category);

  $uniqueRecords = array_values(array_unique(array_map(function ($record) {
    return serialize($record);
  }, $records)));

  $uniqueRecords = array_map(function ($record) {
    return unserialize($record);
  }, $uniqueRecords);

  $categorycompanycount = count($uniqueRecords);
  $counter['total'] += $categorycompanycount;

  foreach ($uniqueRecords as $item_company) {
    $counter['record']++;
    flush();

    if (($item_company['minage'] > $alive['years']) || ($item_company['maxage'] < $alive['years'])) continue;
    $counter['display']++;
    if ($counter['display'] > $resultsize) continue;

    $isChecked = in_array($item_company['company_id'], $selectionList) ? 'checked' : '';
    $isExisting = in_array($item_company['company_id'], $existingList) ? 'checked' : '';

    if ($account->isadmin())
    $setuplink = '<a href="/admin/brands?cid=' . $item_company['company_id'] . '" target="companysetup">
<i class="bi bi-gear text-secondary ms-2 pb-3 fs-5"></i></a>';
  else $setuplink = '';

    $apponlyicon = '';
    if ($item_company['signup_url'] == $website['apponlytag'])  $apponlyicon = '<i class="bi bi-phone-fill text-danger apponly me-2"  data-bs-toggle="tooltip" data-bs-placement="top" title="You can only enroll by installing their app"></i>';

    $randomBenefit = $benefits[array_rand($benefits)];
    $benefitClass = str_replace(' ', '_', strtolower($randomBenefit));
    switch ($randomBenefit) {
      case 'On Enrollment':
        $benefiticon = '<i class="bi bi-calendar-check ' . $benefitClass . '" data-bs-toggle="tooltip" data-bs-placement="top" title="You get a reward on enrollment"></i>';
        break;
      case 'All Month':
        $benefiticon = '<i class="bi bi-calendar3 ' . $benefitClass . '" data-bs-toggle="tooltip" data-bs-placement="top" title="You get a reward during the full month of your birthday"></i>';
        break;
      case 'Week Of':
        $benefiticon = '<i class="bi bi-calendar-week ' . $benefitClass . '" data-bs-toggle="tooltip" data-bs-placement="top" title="You get a reward on the week of your birthday"></i>';
        break;
      case 'On Birthday Only':
        $benefiticon = '<i class="bi bi-calendar-event ' . $benefitClass . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Reward given only on your actual birthday"></i>';
        break;
    }

    $query = "SELECT * FROM bg_company_rewards WHERE company_id= ? and `status`='active'";
    $stmt = $database->prepare($query);
    $stmt->execute([$item_company['company_id']]);
    $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rowcnt = count($rewards);
    $totalvalue = 0;

    foreach ($rewardiconlist as $icon) {
      $rewardicon[$icon] = '';
    }

    if ($rewards) {
      foreach ($rewards as $reward) {
        $counter['rewards']++;
        $totalvalue = $totalvalue + $reward["cash_value"];
        switch ($reward["category"]) {
          case 'enrollment':
            $rewardicon[$reward["category"]] = '<span class="badge rounded-pill bg-primary p-2 pe-3"><i class="bi bi-pen me-1 fs-10"></i><span>Enrollment</span></span>';
            break;
          case 'birthday':
            $rewardicon[$reward["category"]] = '<span class="badge rounded-pill bg-warning p-2 pe-3"><i class="bi bi-cake-fill me-1 fs-10" ></i><span>Birthday</span></span>';
            break;
          case 'enrollment_anniversary':
            $rewardicon[$reward["category"]] = '<span class="badge rounded-pill bg-success p-2 pe-3"><i class="bi bi-calendar-heart me-1 fs-10" ></i><span>Annual Member</span></span>';
            break;
          case 'wedding_anniversary':
            $rewardicon[$reward["category"]] = '<span class="badge rounded-pill bg-info p-2 pe-3"><i class="bi bi-gem me-1 fs-10" ></i><span>Wedding</span></span>';
            break;
          case 'honor':
            $rewardicon[$reward["category"]] = '<span class="badge rounded-pill bg-secondary p-2 pe-3"><i class="bi bi-award-fill me-1 fs-10"></i><span>Honor</span></span>';
            break;
        }
      }
    }

    $randomRating = mt_rand(32, 49) / 10;


    $companylink = $item_company['info_url'];
    if ($item_company['signup_url'] == $website['apponlytag'])  $companylink = $item_company['company_url'];
    $companylink='/brand-details?cid='.$qik->encodeId($item_company['company_id']);
   

    switch ($rowcnt) {
      case 1:
        $valuetag = 'Est. Reward Value';
        break;
      default:
        $valuetag = 'Est. Total Value';
        break;
    }


    echo '


<!-- ============================================================================================================================= -->
<!-- ============================================================================================================================= -->

<article class="col-md-6 col-xxl-4"  id="businesscard-' . $item_company['company_id'] . '" 
data-rewards="birthday" data-categories="' . $categorylower . ' drink" data-rating="' . $randomRating . '" 
data-types="' . ($item_company['signup_url'] == $website['apponlytag'] ? 'true' : 'false') . '" 
data-companyname="' . htmlspecialchars($item_company['company_name']) . ' 
data-more="' . (
    (($item_company['region_type'] ?? 'national') === ($current_user_data['profile_city'] ?? '') ? 'localbrands ' : '') .
    ((isset($bg_systemdata_states[$current_user_data['profile_state']]) && in_array($bg_systemdata_states[$current_user_data['profile_state']], explode(',', $item_company['region_type'] ?? 'national'))) ? 'statewidebrands ' : '') .
    ((($item_company['region_type'] ?? 'national') === 'national') ? 'nationalbrands' : '')
) . '"
    

<div class="card h-100 overflow-hidden">
<div class="card-body p-0 d-flex flex-column justify-content-between">
<div>
<div class="hoverbox text-center"><a class="text-decoration-none" href="/public/assets/video/beach.mp4" data-gallery="attachment-bg">
<img class="w-100 h-100 object-fit-cover" src="' . $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']) . '"  alt="" /></a>
<div class="hoverbox-content flex-center pe-none bg-holder overlay overlay-2"><img class="z-1"  src="' . $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']) . '" width="60" alt="" /></div>
</div>
<div class="p-3">
<h5 class="mb-2"><a href="' . $companylink . '" target="_signup">' . trim($item_company['company_name']) . '</a>' . $setuplink . '</h5>
<h5 class="fs-9">


<!-- ====== ICONS --------------------------  -->
<div class="d-flex gap-2 flex-wrap mb-3">      
';

    foreach ($rewardiconlist as $icon) {
      if (!empty($rewardicon[$icon])) {
        echo $rewardicon[$icon] . '
';
      }
    }

    echo '</div>
<!-- ====== END ICONS --------------------------  -->

';
    echo '

</h5>
</div>
</div>
<div class="row g-0 mb-3 align-items-end">
<div class="col ps-3">
<p class="fw-bold">' . $item_company['spinner_description'] . '</p>

<p class="mb-0 fs-10 text-800">' . $display->starrating($randomRating, 'tooltip|' . rand(10, 9999)) . '
' . $apponlyicon . '  ' . $benefiticon . ' </p>
</div>
<div class="col-auto pe-3">
';



// HAVE button (existing account button)
echo '<button type="button" class="btn btn-md btn-falcon-default hover-danger fs-10 text-600 have-button" 
data-company-id="' . $item_company['company_id'] . '" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" 
title="Click if you already have an existing account with <br>' . htmlspecialchars($item_company['company_name']) . '">
<span class="bi bi-heart">'.$have_label_default.'</span>
</button>
';

// ENROLL button (add-to-gallery button)
if ($enablesignupbutton) {
  $signupbutton = '<button type="button" class="btn btn-md btn-primary fs-10 enroll-button" data-company-id="' . $item_company['company_id'] . '">';
} else {
  $signupbutton = '<button type="button" class="btn btn-md btn-secondary fs-10 enroll-button" disabled data-company-id="' . $item_company['company_id'] . '">';
}

echo $signupbutton . '<span class="bi bi-plus-circle"></span><span class="d-none d-lg-inline enroll-label">'.$enroll_label_default.'</span></button>';



    echo '</div>
</div>
</div>
';


    echo '       
<input class="existingCheckbox quiz_checkbox d-none" type="checkbox" name="existinglist[]" value="' . $item_company['company_id'] . '" ' . $isExisting . '>   
<input class="quiz_checkbox d-none" type="checkbox" name="selectionlist[]" id="' . $item_company['company_id'] . '" value="' . $item_company['company_id'] . '" ' . $isChecked . '  />
</article>
';





    /*
switch ($userplan) {
case 'free':
$addbutton = '  <a href="' . $item_company['info_url'] . '" target="_company" class="btn btn-primary">Visit Website <i class="bi bi-box-arrow-up-right"></i></a>';
$existingbtn = '';
break;
default:
$addbutton = '  <a href="#" class="btn btn-primary add-to-gallery" data-company-id="' . $item_company['company_id'] . '"><i class="bi bi-plus"></i> Create An Account</a>';
$existingbtn = '
<div>
<span class="existingButton btn-sm btn-secondary" data-company-id="' . $item_company['company_id'] . '">I Have An Account</span>
<input class="existingCheckbox quiz_checkbox d-none" type="checkbox" name="existinglist[]" value="' . $item_company['company_id'] . '" ' . $isExisting . '>      
</div>';
break;
}
$content .= ' <input class="quiz_checkbox d-none" type="checkbox" name="selectionlist[]" id="' . $item_company['company_id'] . '" value="' . $item_company['company_id'] . '" ' . $isChecked . '  />
</div>
</div>
';
$content .= '
<div class="card-footer tags d-flex justify-content-between">
<div>
' . $apponlyicon . '
' . $benefiticon . ' 
<a href="#" class="text-muted ' . $categorylower . ' ms-2" data-grid-control="button-filter" data-id="' . $categorylower . '" data-path=".' . $categorylower . '">' . $category . '</a>
<span class="card-text popular-count me-2">' . $item_company['usage_count'] . '</span>
<span class="card-text star-rating"><i class="bi bi-star text-primary"></i> 5.0</span>
</div>
<div>
<strong class="px-2">Enrollment:</strong>
<div class="btn-group" role="group" aria-label="Basic radio toggle button group" data-company-id="' . $item_company['company_id'] . '">
<input type="radio" class="btn-check" name="btnradio' . $item_company['company_id'] . '" id="btnradio1_' . $item_company['company_id'] . '" autocomplete="off" checked>
<label class="btn btn-outline-secondary" for="btnradio1_' . $item_company['company_id'] . '">None</label>
<input type="radio" class="btn-check" name="btnradio' . $item_company['company_id'] . '" id="btnradio2_' . $item_company['company_id'] . '" autocomplete="off">
<label class="btn btn-outline-warning" for="btnradio2_' . $item_company['company_id'] . '">Existing</label>
<input type="radio" class="btn-check" name="btnradio' . $item_company['company_id'] . '" id="btnradio3_' . $item_company['company_id'] . '" autocomplete="off">
<label class="btn btn-outline-success" for="btnradio3_' . $item_company['company_id'] . '">Add</label>
</div>
</div>
</div>
</div>
</li>
';
*/
  }
  ## END BUSINESS ARTICLE LOOP


}
## END ALL BUSINESSES LOOP



# BUSINESS COLUMN FOOTER
#-------------------------------------------------------------------------------
echo '
<div class="card">
<div class="card-footer">
<div class="row g-3 d-flex justify-content-center align-items-center justify-content-md-between">
';


echo '
<div class="col-auto">
<button type="submit" id="submit-button-buttom" name="submit_button_confirmed" value="bottom" class="btn btn-success py-3 px-5">Yes! I Want These</button>
</form>
</div>
';

echo '
</div>
</div>
</div>
</div>
</div>
</div>
</div>
';

?>

</div>

</main>
<!-- ===============================================-->
<!--    End of Main Content-->
<!-- ===============================================-->

<?PHP

#- = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = 
include($_SERVER['DOCUMENT_ROOT'] . '/myaccount/module_enrollment/js_progressbar.inc');
$footerattribute['postfooter'] = $include_local_output;
$pagetype = 'grid';
$suppress_jslibrary = true;
include($_SERVER['DOCUMENT_ROOT'] . '/myaccount/module_enrollment/js_buttonactions.inc');
#- = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = 



$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
