<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 



#-------------------------------------------------------------------------------
# GET TOUR
#-------------------------------------------------------------------------------
// Query to check if a record exists in bg_user_tours for the given date and company_id
// Check if 'date' parameter exists in the query string
if (isset($_GET['date'])) {
    $date = $_GET['date'];
} else {
    // Handle the case where 'date' is not provided
    $date = date('Y-m-d'); // Format: YYYY-MM-DD
}



$checkEnrollmentQuery = "SELECT * FROM bg_user_tours WHERE calendar_dt = :date AND user_id= ".$current_user_data['user_id']."";
$stmt = $database->prepare($checkEnrollmentQuery);
$stmt->execute([':date'=> $date]);
$companies= $stmt->fetchAll(PDO::FETCH_ASSOC);
$i=0;
$listofcompanies=[];
foreach ($companies as $item_company) {  
    $company_data = $app->getcompany($item_company['company_id']);    
    // Merging $item_company and $company_data without overwriting
    $listofcompanies[] = $item_company + ['data' => $company_data];
    $i++;
}


#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$additionalstyles.='<link rel="stylesheet" href="/public/css/myaccount.css">
<style>
    @media print {
        body * {
            display: none;
        }

        #printContainer {
            display: block;
        }
    }
</style>';


$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');


/* 
echo '<hr class="mt-0 mb-4">
<div class="container">
<div class="row">'.
$display->formaterrormessage($errormessage);



<div class="container">
    <!-- Display the tour date and list of selected businesses -->
    <?php foreach ($business_details as $day => $details): ?>
        <h3><?php echo ucfirst(str_replace('_', ' ', $day)); ?></h3>
        <ul class="list-group">
            <?php foreach ($details as $detail): ?>
                <li class="list-group-item"><?php echo $detail['company_name']; ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
</div> */


// Assuming $date contains a valid date string
$dateObject = new DateTime($date);
$formattedDate = $dateObject->format('l, F j, Y');


$output='   
<div class="container main-content">
    <div class="row">
        <div class="col-lg-8 mb-4 mt-5">
            <!-- DATE card -->
            <div class="card h-100 border-start-lg border-start-primary">
                <div class="card-body">
                    <div class="small text-muted">Your Tour:</div>
                    <div class="h3 my-3">'.$formattedDate .'</div>
                     Consists of '.ucfirst($qik->plural2(count($companies), $website['bizname'])).'
                    </a>
                </div>
            </div>
        </div>
       ';


       echo $output;
       ?>
        <div class="col-lg-4 mb-4">
            <!-- ACTIONS card 3-->
            <div class="card h-100 border-start-lg border-start-success">
                <div class="card-body">
                    <div class="small text-muted mb-4">Actions</div>
                    <!--
                    <a href="#" class="btn button"><i class="bi bi-printer-fill"></i> Print Map</a>
                    <a href="#" class="btn button"><i class="bi bi-printer-fill"></i> Print Steps</a> -->

                     <!-- Print button -->
    <div class="text-center">
        <button class="btn btn-primary print_map" onclick="printContent()">Download PDF</button>
    </div>
                </div>
            </div>
        </div>
    </div>

    <?PHP
    echo '
    <hr class="mt-0 mb-4">
    <div class="row">
    <div class="col-lg-12 mb-4 ">
    <!-- CELBRATION TOUR COMPANIES card-->
    <div class="card card-header-actions mb-4 p-0  ">
        <div class="card-header  d-flex align-items-center justify-content-between ">
            <h2>
            Celebration Tour
            </h2>
            <div>
            <a href="/myaccount/tour-build?date='.$date.'" class="btn btn-sm btn-primary" type="button">Add More '.ucfirst($website['biznames']).'</a>
        </div>
        </div>
';

$homeaddress=''.$current_user_data['profile_mailing_address'].', '.$current_user_data['profile_city'].', '.$current_user_data['profile_state'].'  '.$current_user_data['profile_zip_code'].'';
echo '        
    <div class="card-body" id="sortable">
        <!-- Home location -->
        <div class="d-flex align-items-center justify-content-between px-4" data-location="'.$homeaddress.'">
            <div class="d-flex align-items-center">
            <i class="bi bi-house-fill"></i>
                <div class="ms-4">
                    <div class="small">Your Home</div>
                    <div class="text-xs text-muted">'.$homeaddress.'</div>
                </div>
            </div>
            <div class="ms-4 small">
                <a href="#!">Change Location</a>
            </div>
        </div>
        <hr>
';



## GET LIST OF BUSINESSES ENROLLED:
#$companies=$account->getgoldlist($current_user_data['user_id'], "'active', 'success', 'existing'");
#breakpoint($companies['sql']);
$companylistoutput='';

foreach ($listofcompanies as $item_companyrow) {
    $item_company=  $item_companyrow['data'];
## LOOP THROUGH ENROLLMENT LIST
if (!empty($item_company)) {
if (!empty($item_company['address'])) {
$companyaddress=$item_company['address'].', '.$item_company['city'].', '.$item_company['state'].'  '.$item_company['zip_code'];
} else {
$companyaddress=$current_user_data['profile_city'].', '.$current_user_data['profile_state'].'  '.$current_user_data['profile_zip_code'];
}
$companylistoutput.= '
<!-- Other locations -->
<div class="sortable_item">
    <div class="d-flex align-items-center justify-content-between px-4" data-location="'.$companyaddress.'">
        <div class="d-flex align-items-center">
        <img src="'. $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']).'" style="width:32px" alt="" />  
            <div class="ms-4">
                <div class="small fw-bold">'.$item_company['company_name'].'</div>
                <div class="text-xs text-muted">'.$companyaddress.'</div>
            </div>
        </div>
        <div class="ms-4 small">
            <div class="badge bg-light text-dark me-3 d-none">Closest Location</div>
            <a href="#!" class="pick-location d-none">Pick Different Location</a>
            <div class="btn btn-sm sortable_item_handle"><i class="bi bi-list h3"></i></div>
        </div>
    </div>
    <hr>
</div>
';
}
}
echo $companylistoutput;

?>
    
<!-- Draw new map -->
<div style="text-align: center; margin-bottom: 20px;">
    <button class="btn btn-secondary draw_map" id="draw_map" style="display: none;" onclick="DrawNewMap()">Draw New Map</button>
</div>
</div>
</div>
</div>

</div>


    <div class="row">
        <div class="col-lg-4 mb-4">
            <!-- STEPS CARD-->
            <div class="card h-100 border-start-lg border-start-secondary">
          <!-- Show directions in a panel -->
        <!-- <div id="directions-panel"></div> -->
        <div id="directions-panel"></div>
    </div>
    </div>


    <!-- MAP card-->
    <div class="col-lg-8 mb-8">
    <div class="card mb-4" id="printContainer">
        <div class="card-header">Map and Direction</div>
      
        <div class="card card-header-actions mb-4">
            <!-- Existing content -->
            <div class="card-body p-0">
                <!-- Map will be displayed here -->
                <div id="google_map" style="height: 800px;"></div>
            </div>
        </div>
    </div>
   
</div>
</div>

<script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script>
<script src="/public/js/mappingengine.js"></script>


</div>   </div>   </div>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
