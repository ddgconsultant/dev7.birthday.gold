<?PHP
header('location: /myaccount/enrollment-picker'); exit;


include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');












#-------------------------------------------------------------------------------
# PREP VARIABLES AND SETUP
#-------------------------------------------------------------------------------
// Determine view type from URL parameter
$view_type = 'list'; // Default view

// Check if 'grid' or 'list' exists in the query string
if (isset($_SERVER['QUERY_STRING'])) {
    if (strpos($_SERVER['QUERY_STRING'], 'grid') !== false) {
        $view_type = 'grid';
    } elseif (strpos($_SERVER['QUERY_STRING'], 'list') !== false) {
        $view_type = 'list';
    }
}

$submitpagename = '/myaccount/select';
$submitpagename = '/myaccount/businessselect-'.$view_type;
$submitpagename = '/myaccount/businessselect';


$enroll_label_default ="Enroll Me";
$enroll_label_active ="Remove" ;

$have_label_default ="I Have This";
$have_label_active ="I Have This";


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

#breakpoint($current_user_data['account_product_id']) ;


$additionalstyles.= '
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
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header3.inc');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<!--- START PAGE CONTENT -->
<div class="container main-content">
  <div class="row">
    <div class="content">
      <div class="row g-3">
      <h3 class="bg-success text-white px-3 py-2">Pick the '.$website['biznames'].' you want.</h3>
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
            <a class="btn btn-link btn-sm " href="/myaccount/businessselect?grid" data-bs-toggle="tooltip" data-bs-placement="top" title="Grid View">
                <i class="bi bi-grid-3x3-gap-fill fs-8 ' . ($view_type == 'grid' ? 'text-success' : 'text-400 hover-700') . '" data-fa-transform="down-1"></i>
            </a>
            <a class="btn btn-link btn-sm px-1 " href="/myaccount/businessselect?list" data-bs-toggle="tooltip" data-bs-placement="top" title="List View">
                <i class="bi bi-list-task fs-8 ' . ($view_type == 'list' ? 'text-success' : 'text-400 hover-700') . '" data-fa-transform="down-1"></i>
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
        ' . $display->inputcsrf_token() . '
        <input type="hidden" name="view" value="'.$view_type.'">';



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



echo '<div class="container-xl  m-0 p-0 row  mt-4">
<!-- Account page navigation-->
';

if ((!empty($wizardmode))) {
  include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-myaccount.php');
}



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

$content = '<div class="row m-0 p-0" data-grid>
   
';
$itemcounter = 0;
$menus = '';
$counter['total'] = 0;
$counter['record'] = 0;
$counter['display'] = 0;
$counter['rewards'] = 0;



#-------------------------------------------------------------------------------
# GET THE ACTUAL LAYOUT DISPLAYED
#-------------------------------------------------------------------------------
include($_SERVER['DOCUMENT_ROOT'] . '/myaccount/module_enrollment/layout-'.$view_type.'.inc');

#-------------------------------------------------------------------------------
# END
#-------------------------------------------------------------------------------




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
</div>
</div>
</div>
</main>
<!-- ===============================================-->
<!--    End of Main Content-->
<!-- ===============================================-->



<?PHP

#- = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = 
include($_SERVER['DOCUMENT_ROOT'] . '/myaccount/module_enrollment/js_progressbar.inc');
$footerattribute['postfooter'] = $include_local_output;
$pagetype='list';
$suppress_jslibrary=true;
include($_SERVER['DOCUMENT_ROOT'] . '/myaccount/module_enrollment/js_buttonactions.inc');
#- = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = 

$footerattribute['bottomfooter']=$footerattribute['postfooter'];

$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();