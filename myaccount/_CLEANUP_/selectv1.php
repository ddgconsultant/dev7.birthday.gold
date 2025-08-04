<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');




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
$accountstats=$account->account_getstats();
$plandetails=$app->plandetail('details');

$userplan=$current_user_data['account_plan'];

$selectsused=($accountstats['business_pending']+$accountstats['business_selected']+$accountstats['business_success']);
$selectsleft=($plandetails[$userplan]['max_business_select']-$selectsused);

$selectionList=array();

$initialcount = $selectsused;
$planlimit = $plandetails[$userplan]['max_business_select'];

$headerattribute['additionalcss'] = '
<!-- Bootstrap CSS -->

<!-- Fontawesome Sytle CSS -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="/public/css/discover.css">';




#-------------------------------------------------------------------------------
# HANDLE THE PROFILE UPDATE ATTEMPT
#-------------------------------------------------------------------------------

if ($app->formposted()) {
    if (isset($_POST['selectionlist']) && is_array($_POST['selectionlist'])) {
        $selectionList = $_POST['selectionlist'];
$session->set('goldmine_selectionList', $selectionList);
#-------------------------------------------------------------------------------
# RECORD THE SELECTION
#-------------------------------------------------------------------------------
if (isset($_POST['confirmed'])) {
    $user_id=$current_user_data['user_id'];
    $rowsInserted=0;
    // Prepare the SQL statement with placeholders for a single row
    $stmt = $database->prepare("INSERT INTO bg_user_companies (user_id, company_id, create_dt, modify_dt, `status`) VALUES (:user_id, :value, now(), now(), 'selected')");

    // Insert multiple rows using individual queries
    foreach ($selectionList as $value) {
        // Bind parameters for each iteration
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':value', $value);

        // Execute the query for each row
        $stmt->execute();

        // Increment the number of rows inserted
        $rowsInserted += $stmt->rowCount();
    }
#breakpoint( $rowsInserted );
    // Redirect after all inserts are done
    header('location: /myaccount/goldmine');
    exit;
} else {


#-------------------------------------------------------------------------------
# DISPLAY THE SELECTION CONFIRMATION
#-------------------------------------------------------------------------------
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
    include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');
    echo '
    <div class="container py-6">
    <div class="container>
        <div class="row">
            <div class="col-12 mb-5 text-center justify-content-center">
            <i class="bi bi-exclamation-triangle display-4 text-primary"></i>
                <h3 class="display-3">Please Confirm Your '.$counter.' '.$qik->plural('Selection', $counter).'</h1>
                </div>
                <div>
                <p class="mt-5 mb-5">'.$output.'</p>
                </div>
               <div class="row mt-5 text-center justify-content-center">
               <div class="col-6">
               <a class="btn btn-danger py-3 px-5" href="/myaccount/select">No. Take me back to change them</a>
            </div>
               <div class="col-6">
                <form action="/myaccount/select" method="post">                
'.$display->inputcsrf_token().'
<input type="hidden" name="confirmed" value="Y">
'.$listoutput.'
<button type="submit" name="submit_button_confirmed" class="btn btn-success py-3 px-5">Yes! I Want These</button>
                </form>
                </div>
               
                
                </div>
        </div>
    </div>
</div>
';
    

include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footer.php'); 
exit;

    }
    }

}






#-------------------------------------------------------------------------------
# DISPLAY THE BUSINESS SELECTION PAGE
#-------------------------------------------------------------------------------
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');

$selectionList=$session->get('goldmine_selectionList', '');
if ($selectionList=='') $selectionList=array();

echo '
<section class="quiz_section" id="quizeSection">
<form action="/myaccount/select" method="post">
'.$display->inputcsrf_token().'
<div class="container">
<div class="row">
<div class="col-sm-12">
';

echo '

<div class="quiz_backBtn_progressBar mt-3 mb-5">
<div class="row">
    <div class="col-9 mb-3">
    <h3>Plan Limit: <small>( <span id="count-display">' . $initialcount . '</span> of ' . $planlimit . ' this year )</small></h3>
    </div>
    <div class="col-3 mb-2 pb-0 d-flex justify-content-end">
  
    <!-- Submit button -->
    <button type="submit" name="submit_button_top" class="btn btn-success py-2 px-5">Save My Selection</button>

    </div>
</div>
<div class="row  mb-0 pb-0">
    <div class=" mb-0 pb-0">
    <div class="progress">
        <div class="progress-bar progress-bar-striped" role="progressbar" style="width: 0%" aria-valuenow="' . $initialcount . '" aria-valuemin="0" aria-valuemax="' . $planlimit . '"></div>
    </div>
    </div>
</div>
</div>
    <div class="alert alert-danger" role="alert" id="planlimitalert"  style="display: none;">
    You cannot select any more items. You are at your plan limit.
    </div>
';


    echo '

<div class="col-sm-12 mb-5">
<div class="mb-5 text-center">
<div id="commentary">
<h1 class="quiz_title m-0 mb-3 pt-0" >Select Your Businesses</h1>
'.$display->formaterrormessage('<div class="alert alert-info" role="alert"><b>Tip: Make a few selections a day.</b> You don\'t have to use them all at once.<br>Some businesses require you to click confirmation messages.  By spacing it out, you won\'t get flooded with messages.</div>').'
</div></div>
</div>';
/*
<small>After you save your selection, they will be moved into a "pending" state and our system will automatically schedule the enrollment process.<br>
You won\'t be able to remove items from your selection at that point.  Please make your selections carefully.</small>
</div>
'.$display->formaterrormessage('<div class="alert alert-info" role="alert"><b>Tip: Make a few selections a day.</b> You don\'t have to use them all at once.<br>Some businesses require you to click confirmation messages.  By spacing it out, you won\'t get flooded with messages.</div>').'
</div>

</div>
';
*/

echo '
<div class="mt-3 d-flex align-items-start">
<div class="nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
';

$categories = ['Food', 'Beverage', 'Beauty', 'Retail', 'Other', 'Suppressed'];
$content = [];
$itemcounter = 0;
$menus='';
foreach ($categories as $category) {
    $activetag = $showtag = '';
    if ($category == 'Food') {
        $activetag = 'active';
        $showtag = 'show';
    }
    $catname = strtolower($category);


    $content[$category] = '
<!-- --------------------------------- START OF BUTTON SECTION [' . $category . ']------------------------------------------>
<div class="tab-pane fade ' . $showtag . ' ' . $activetag . '" id="v-pills-' . $catname . '" role="tabpanel" aria-labelledby="v-pills-' . $catname . '-tab"><p>{{categorycount}} Companies / <span id="' . $catname . 'count">0</span> selected</p>
<div class="col-sm-12">
<div class="quiz_content_area" id="listofcompanies">
';

    $itemsperrow = 3;
    $itemrowcount = 0;
    $categorycompanycount=0;
    
    $records = $app->getSelectionCompanies(255, $category);

if (count($records)>0) {
    $menus.= '
    <button class="nav-link ' . $activetag . ' fw-bold" type="button" role="tab" id="v-pills-' . $catname . '-tab"  data-bs-toggle="pill" data-bs-target="#v-pills-' . $catname . '"   aria-controls="v-pills-' . $catname . '" aria-selected="true">' . $category . '</button>
    ';
}
    foreach ($records as $item_company) {
        $categorycompanycount++;
        $itemcounter++;
        $closeddiv=false;
        if ($itemrowcount == 0) {
            $content[$category] .= '  <div class="row">';
        }
        $itemrowcount++;

        $isChecked = in_array($item_company['company_id'], $selectionList) ? 'checked' : '';


        $content[$category] .= '

<!------------- COMPANY START ---->
<div class="col-3">
<div class="quiz_card_area">
<input class="quiz_checkbox" type="checkbox" name="selectionlist[]" id="' . $item_company['company_id'] . '" value="' . $item_company['company_id'] . '" ' . $isChecked . '  />
<div class="single_quiz_card">
<div class="quiz_card_content">
<div class="quiz_card_icon">
    <div class="">
    <img class="img-fluid" src="' . $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']) . '" alt="">
    </div>
</div><!-- end of quiz_card_media -->

<div class="quiz_card_title">
        <h3><i class="bi bi-pencil-square" aria-hidden="true"></i> ' . $item_company['company_name'] . '</h3>
</div><!-- end of quiz_card_title -->
</div><!-- end of quiz_card_content -->
</div><!-- end of single_quiz_card -->
</div><!-- end of quiz_card_area -->
</div><!-- end of ' . $itemcounter . '  -->
<!------------- COMPANY END ---->
';


        if ($itemrowcount > $itemsperrow) {
            $closeddiv=true;
            $itemrowcount = 0;
            $content[$category] .= ' </div> <!--- END ROW ---> 

';
        }
    }

    if (!$closeddiv)    $content[$category] .=  ' </div> <!--- END GROUP (AKA ROW)--->';

    $content[$category] .=  '   
    </div></div></div> <!--- END CATEGORY --->';

$content[$category]=str_replace('{{categorycount}}', $categorycompanycount, $content[$category]);

}

echo $menus;
echo '

</div> <!--- endof buttons -->
';

echo '  
<div class="tab-content" id="v-pills-tabContent">
';

echo implode("\n", $content);

echo '  
</div>
</div>
</div>
';

echo '
<div class="text-center pt-1 pb-1">
<!-- Submit button -->
<button type="submit"  name="submit_button_bottom" class="btn btn-success btn-block py-2 px-5">
Save My Selection
</button>
</div>
</form>
</section>
';





$footerattribute['postfooter'] = '
<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

';
$footerattribute['postfooter'] = "
<script>
const progressBar = document.querySelector('.progress-bar');
const checkboxes = document.querySelectorAll('.quiz_checkbox');
let initialCount = {$initialcount}; // Initial checked count
const alertDiv = document.getElementById('planlimitalert'); // Get the alert div
const countDisplay = document.getElementById('count-display'); // Get the count display element

const updateProgress = () => {
let checkedCount = Array.from(checkboxes).filter(box => box.checked).length;

let totalSelections = checkedCount + initialCount;
const percentage = Math.min(100 * totalSelections / {$planlimit}, 100);

progressBar.style.width = `\${percentage}%`;
countDisplay.textContent = `\${totalSelections}`; // Update count display

if(totalSelections >= {$planlimit}) {
Array.from(checkboxes).forEach(box => {
if(!box.checked){
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
}

const categories = {
    food: document.getElementById('foodcount'),
    beverage: document.getElementById('beveragecount'), 
    beauty: document.getElementById('beautycount'),
    retail: document.getElementById('retailcount'),
    other: document.getElementById('othercount'),
    suppressed: document.getElementById('suppressedcount')
  };
  
  function updateCategoryCount(category) {
    const checkboxes = document.querySelectorAll(`#v-pills-\${category} .quiz_checkbox`);
    const count = Array.from(checkboxes).filter(box => box.checked).length;
    categories[category].textContent = count;
  }
  
  Array.from(checkboxes).forEach(box => {
    box.addEventListener('click', () => {
      const category = box.closest('.tab-pane').id.split('-')[2]; 
      updateCategoryCount(category);
      updateProgress();
    });
  });
  
  // Initial count
  Object.keys(categories).forEach(updateCategoryCount);


Array.from(checkboxes).forEach(box => {
box.addEventListener('click', updateProgress); 
});

// Initial setup
updateProgress(); 
alertDiv.style.display = 'none'; // Make sure alert div is initially hidden


$(document).ready(function() {
    // When any company checkbox is clicked
    $('.quiz_checkbox').click(function() {
        var companyId = $(this).val();

        $.ajax({
            type: 'POST',
            url: '/myaccount/select.php',
            data: { _token: '".$display->inputcsrf_token('tokenonly')."', type: 'ajax', cid: companyId },
            success: function(comment) {
                // Update the header text based on the response comment
                $('#commentary').html(comment);
            }
        });
    });
});


</script>";


$session->unset('goldmine_selectionList');
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.php');
