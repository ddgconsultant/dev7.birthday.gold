<?php
$addClasses[] = 'Referral';
$addClasses[] = 'TimeClock';
$addClasses[] = 'Charts';
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 



$additionalstyles.='<link rel="stylesheet" href="/public/css/myaccount.css">
<style>
.feature {
width: 90px;  /* Set width */
height: 90px;  /* Set height */
display: flex;
align-items: center;
justify-content: center;
}

.feature i {
font-size: 48px;  /* Increase icon size */
}

.tooltip {
  z-index: 1039 !important;  /* Assuming the modal z-index is 1040 */
}


</style>
';

#include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/header.php'); 


include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');



echo '
<div class="container-xl main-content">
    <!-- Account page navigation-->
';


#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$paymenttag='';
$birthdayprioritytag='';
$titletag='Sign Up!';
#$till=$app->getTimeTilBirthday($current_user_data['birthdate']);
#if ($till['days']==0) {
#$birthdayprioritytag=' Since your birthday is today, you will be prioritized to the front of the line and your registrations will be processed shortly after your selection.  You should be aware, some business do not allow for benefits on same day signups.  We will let you know if you pick any of those. (You\'ll just be early for next year :-)';
#$titletag='Happy Birthday!';
#}


$transferpage=$system->startpostpage();
if (empty($transferpage['message']))
$transferpage['message']=$session->get('force_error_message', '');
$session->unset('force_error_message');  

$referralstats=$referral->stats();
$userlist=$referral->user_list();


#breakpoint($referralstats);
/*
Array
(
    [grand_total] => 10
    [distinct_user_count_total] => 4
    [last_30_days_total] => 0
    [distinct_user_count_last_30_days] => 0
    [confirmed_total] => 7
)
*/


#breakpoint($transferpage);
#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

$totalpayouttoday=0;
foreach($userlist as $userrow){
  if (isset($userrow['today_flag']) && $userrow['today_flag']==1) $totalpayouttoday=$totalpayouttoday+$userrow['referral_payout'];
}
$avatar='/public/images/defaultavatar.png';
$avatarbuttontag='Upload';
if (is_array($current_user_data) && !empty($current_user_data['avatar'])) {
  $avatar = '/' . $current_user_data['avatar'];
  $avatarbuttontag = 'Change';
} else {

}
echo '      
      <div class="row g-3 mt-3 mb-3">
<div class="col-xxl-12 col-xl-12">
  <div class="row g-3">
  <div class="col-12">
  <div class="card bg-transparent-50 overflow-hidden">
      <div class="card-header position-relative">
          <div class="position-relative z-2">
              <div class="row align-items-center">
                  <div class="col-md-8">
                      <h3 class="text-primary mb-1">' . $app->time_based_greeting(null, ',') . $current_user_data['first_name'] . '!</h3>
                      <p>Here’s what happening with your sales today </p>
                      <div class="d-flex py-3">
                          <div class="pe-3">
                              <p class="text-600 fs-10 fw-medium">Today\'s sales numbers</p>
                              <h4 class="text-800 mb-0">' . $referralstats['today_total'] . '</h4>
                          </div>
                          <div class="ps-3">
                              <p class="text-600 fs-10">Today’s total sales commissions*</p>
                              <h4 class="text-800 mb-0">$' . number_format($totalpayouttoday, 2) . ' </h4>
                          </div>
                      </div>
                  </div>
                  <div class="col-md-4 d-flex justify-content-end">
                      <img class="img-fluid rounded-circle" src="' . $avatar . '" alt="" width="180" />
                  </div>
              </div>
          </div>
      </div>
      </div>
      </div>
        ';

    
        if ($account->isdeveloper(20)) {
          echo '
          <div class="col-lg-12">
          <div class="row g-3">
        <div class="card-body p-0 ">
          <ul class="mb-0 list-unstyled list-group font-sans-serif">
            <li class="list-group-item mb-0 rounded-0 py-3 px-x1 list-group-item-warning border-x-0 border-top-0">
              <div class="row flex-between-center">
                <div class="col">
                  <div class="d-flex">
                    <div class="bi bi-circle-fill mt-1 fs-11"></div>
                    <p class="fs-10 ps-2 mb-0"><strong>5 plans</strong> didn’t publish to your Facebook page</p>
                  </div>
                </div>
                <div class="col-auto d-flex align-items-center"><a class="fs-10 fw-medium text-warning-emphasis" href="#!">View products<i class="fas fa-chevron-right ms-1 fs-11"></i></a></div>
              </div>
            </li>
            <li class="list-group-item mb-0 rounded-0 py-3 px-x1 greetings-item text-700 border-x-0 border-top-0">
              <div class="row flex-between-center">
                <div class="col">
                  <div class="d-flex">
                    <div class="bi bi-circle-fill mt-1 fs-11 text-primary"></div>
                    <p class="fs-10 ps-2 mb-0"><strong>7 plans</strong> have payments that need to be captured</p>
                  </div>
                </div>
                <div class="col-auto d-flex align-items-center"><a class="fs-10 fw-medium" href="#!">View payments<i class="fas fa-chevron-right ms-1 fs-11"></i></a></div>
              </div>
            </li>
            <li class="list-group-item mb-0 rounded-0 py-3 px-x1 greetings-item text-700  border-0">
              <div class="row flex-between-center">
                <div class="col">
                  <div class="d-flex">
                    <div class="bi bi-circle-fill mt-1 fs-11 text-primary"></div>
                    <p class="fs-10 ps-2 mb-0"><strong>50+ plans</strong> need to be fulfilled</p>
                  </div>
                </div>
                <div class="col-auto d-flex align-items-center"><a class="fs-10 fw-medium" href="#!">View orders<i class="fas fa-chevron-right ms-1 fs-11"></i></a></div>
              </div>
            </li>
          </ul>
        </div>
    ';
        }



    if ($account->isdeveloper(20)) {
    echo '
    <div class="col-lg-12">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="card h-md-100 ecommerce-card-min-width">
            <div class="card-header pb-0">
              <h6 class="mb-2 mt-0 d-flex align-items-center">Weekly Sales<span class="ms-1 text-400" data-bs-toggle="tooltip" data-bs-placement="top" title="Calculated according to last week\'s sales"><span class="far fa-question-circle" data-fa-transform="shrink-1"></span></span></h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-end">
              <div class="row">
                <div class="col">
                  <p class="font-sans-serif lh-1 mb-1 fs-7">$47K</p><span class="badge badge-subtle-success rounded-pill fs-11">+3.5%</span>
                </div>
                <div class="col-auto ps-0">
                  <div class="echart-bar-weekly-sales h-100 echart-bar-weekly-sales-smaller-width"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card product-share-doughnut-width">
            <div class="card-header pb-0">
              <h6 class="mb-2 mt-0 d-flex align-items-center">Product Share</h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-end">
              <div class="row align-items-end">
                <div class="col">
                  <p class="font-sans-serif lh-1 mb-1 fs-7">34.6%</p><span class="badge badge-subtle-success rounded-pill"><span class="fas fa-caret-up me-1"></span>3.5%</span>
                </div>
                <div class="col-auto ps-0">
                  <canvas class="my-n5" id="marketShareDoughnut" width="112" height="112"></canvas>
                  <p class="mb-0 text-center fs-11 mt-4 text-500">Target: <span class="text-800">55%</span></p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card h-md-100 h-100">
            <div class="card-body">
              <div class="row h-100 justify-content-between g-0">
                <div class="col-5 col-sm-6 col-xxl pe-2">
                  <h6 class="mt-1">Plan Distribution</h6>
                  <div class="fs-11 mt-3">
                    <div class="d-flex flex-between-center mb-1">
                      <div class="d-flex align-items-center"><span class="dot bg-primary"></span><span class="fw-semi-bold">Free</span></div>
                      <div class="d-xxl-none">57%</div>
                    </div>
                    <div class="d-flex flex-between-center mb-1">
                      <div class="d-flex align-items-center"><span class="dot bg-info"></span><span class="fw-semi-bold">Gold</span></div>
                      <div class="d-xxl-none">20%</div>
                    </div>
                    <div class="d-flex flex-between-center mb-1">
                      <div class="d-flex align-items-center"><span class="dot bg-warning"></span><span class="fw-semi-bold">Life</span></div>
                      <div class="d-xxl-none">22%</div>
                    </div>
                  </div>
                </div>
                <div class="col-auto position-relative">
                  <div class="echart-product-share"></div>
                  <div class="position-absolute top-50 start-50 translate-middle text-1100 fs-7">26M</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-header pb-0">
              <h6 class="mb-2 mt-0 d-flex align-items-center">Total Order</h6>
            </div>
            <div class="card-body">
              <div class="row align-items-end">
                <div class="col">
                  <p class="font-sans-serif lh-1 mb-1 fs-7">58.4K</p>
                  <div class="badge badge-subtle-primary rounded-pill fs-11"><span class="fas fa-caret-up me-1"></span>13.6%</div>
                </div>
                <div class="col-auto ps-0">
                  <div class="total-order-ecommerce" data-echarts=\'{"series":[{"type":"line","data":[110,100,250,210,530,480,320,325]}],"grid":{"bottom":"-10px"}}\'></div>
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

    }

    echo '          </div>
    </div>
     </div> 
  ';


// TIMECLOCK
$hours= $timeclock->report_hours($current_user_data['user_id']);
echo '
<div class="col-xl-12 mb-3">
  <div class="card">
  <div class="card-header pb-0">
    <h6 class="mb-2 mt-0 d-flex align-items-center">Time Clock</h6>
  </div>
    <div class="card-body py-3">
      <div class="row g-0">
        <div class="col-6 col-md-4 border-200 border-bottom border-end pb-4">
           <p class="font-sans-serif lh-1 mt-3 mb-1 fs-7" id="current-time"></p>
           <p class="pb-1 text-700">(birthday.gold HQ [MST])</p>
        
';
?>
<script>
function updateTime() {
  const now = new Date();
  const options = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
  const timeString = now.toLocaleDateString('en-US', options) + ' - <b>' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true }) + '</b>';
  document.getElementById('current-time').innerHTML = timeString;
}

updateTime(); // Call once on page load to set initial time
setInterval(updateTime, 60000); // Then update every minute
</script>

<?PHP 

          if (!$timeclock->is_clocked_in($current_user_data['user_id'])) {
            echo '
            <a type="button" class="btn btn-primary  btn-sm px-3 me-2" href="/staff/clockin">Clock In</a>
            ';
            } else  {
                echo '
            <a type="button" class="btn btn-warning  btn-sm px-3 me-2" href="/staff/clockout">Clock Out: ['.$qik->plural2($hours['day'], 'hour', '_nbsp').']</a>
            ';
            }


            echo '<br><h6 class="fs-9 mt-3"><a href="/staff/timecards">TimeCards</a></h6>
            ';
         $userattribute=   $account->get_user_attribute($current_user_data['user_id'], 'hourly_pay_rate');
    
      #   breakpoint($userattribute);
echo '
       
        </div>

        <div class="col-6 col-md-4 border-200 border-bottom border-end pt-4 pb-md-0 ps-3">
        <h6 class="pb-1 text-700">Estimated PayPeriod </h6>
        <p class="font-sans-serif lh-1 mb-1 fs-7">'.$qik->plural2($hours['payperiod'], 'hour').' </p>
   ';
   if (!empty($userattribute['description'])) { 
    $paycheck = floor($userattribute['description'] * $hours['payperiod'] * 100) / 100;
    echo '    <div class="d-flex align-items-center">
      <h6 class="fs-10 text-500 mb-0">$'. number_format($paycheck, 2, '.', '') .' </h6>
      </div>
      ';
}

          echo '
      </div>


        <div class="col-6 col-md-4 border-200 border-bottom border-end pt-4 pb-md-0 ps-3">
          <h6 class="pb-1 text-700">Today </h6>
          <p class="font-sans-serif lh-1 mb-1 fs-7">'.$qik->plural2($hours['day'], 'hour').' </p>

        </div>
        <div class="col-6 col-md-4 border-200 border-bottom border-end pt-4 pb-md-0 ps-3">
          <h6 class="pb-1 text-700">This Week </h6>
          <p class="font-sans-serif lh-1 mb-1 fs-7">'.$qik->plural2($hours['week'], 'hour').' </p>
       
        </div>
     



        <div class="col-6 col-md-4 border-200 border-bottom border-end pt-4 pb-md-0 ps-3">
          <h6 class="pb-1 text-700">This Month </h6>
          <p class="font-sans-serif lh-1 mb-1 fs-7">'.$qik->plural2($hours['month'], 'hour').' </p>
        
        </div>
        <div class="col-6 col-md-4 border-200 border-bottom border-end pt-4 pb-md-0 ps-3">
          <h6 class="pb-1 text-700">Year To Day </h6>
          <p class="font-sans-serif lh-1 mb-1 fs-7">'.$qik->plural2($hours['year'], 'hour').' </p>
          
        </div>
      </div>
    </div>
  </div>
';
if ($account->isdeveloper(20)) {
                 
  // SALES TOTALS CHART
  echo '
  <div class="card mt-3">
    <div class="card-header">
      <div class="row flex-between-center g-0">
        <div class="col-auto">
          <h6 class="mb-0">Total Sales</h6>
        </div>
        <div class="col-auto d-flex">
          <div class="form-check mb-0 d-flex">
            <input class="form-check-input form-check-input-primary" id="ecommerceLastMonth" type="checkbox" checked="checked" />
            <label class="form-check-label ps-2 fs-11 text-600 mb-0" for="ecommerceLastMonth">Last Month<span class="text-1100 d-none d-md-inline">: $32,502.00</span></label>
          </div>
          <div class="form-check mb-0 d-flex ps-0 ps-md-3">
            <input class="form-check-input ms-2 form-check-input-warning opacity-75" id="ecommercePrevYear" type="checkbox" checked="checked" />
            <label class="form-check-label ps-2 fs-11 text-600 mb-0" for="ecommercePrevYear">Prev Year<span class="text-1100 d-none d-md-inline">: $46,018.00</span></label>
          </div>
        </div>
        <div class="col-auto">
          <div class="dropdown font-sans-serif btn-reveal-trigger">
            <button class="btn btn-link text-600 btn-sm dropdown-toggle dropdown-caret-none btn-reveal" type="button" id="dropdown-total-sales-ecomm" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span class="bi bi-three-dots fs-11"></span></button>
            <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="dropdown-total-sales-ecomm"><a class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a>
              <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="card-body pe-xxl-0">
      <!-- Find the JS file for the following chart at: src/js/charts/echarts/total-sales-ecommerce.js-->
      <!-- If you are not using gulp based workflow, you can find the transpiled code at: public/assets/js/theme.js-->
      <div class="echart-line-total-sales-ecommerce"   data-echart-responsive="true" data-options=\'{"optionOne":"ecommerceLastMonth","optionTwo":"ecommercePrevYear"}\'></div>
    </div>
  </div>
</div>

';
#$chartjsoutput= $charts->chart1();

/* 
$chartJS = $charts->generateEChart(
'totalSalesEcommerce', 
['2019-01-05', '2019-01-06', '2019-01-07', '2019-01-08', '2019-01-09', '2019-01-10', '2019-01-11', '2019-01-12', '2019-01-13', '2019-01-14', '2019-01-15', '2019-01-16'],
[99, 99, 60, 80, 65, 90, 130, 90, 30, 40, 30, 70], 
[110, 30, 40, 50, 80, 70, 50, 40, 110, 90, 60, 60] 
);

// Print the JavaScript code in a script tag in your HTML

echo '<script src="https://cdn.jsdelivr.net/npm/echarts/dist/echarts.min.js"></script>';
echo $chartJS; */
}

// YOUR STATS
    echo '
      <div class="row  mt-3">
  <div class="col-xl d-flex">
  <div class="card radius-10 w-100">
    <div class="card-body">
      <div class="d-flex align-items-center">
        <div>
        <h5 class="mb-1">Your Stats</h5>
            <p class="mb-0 font-13 text-secondary"><i class="bi bi-calendar3"></i> in last 30 days revenue</p>
          </div>
          <div class="dropdown ms-auto d-none">
            <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown">	<i class="bi bi-three-dots font-22  text-option"></i>
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="javascript:;">Action</a>
              </li>
              <li><a class="dropdown-item" href="javascript:;">Another action</a>
              </li>
              <li>
                 <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item" href="javascript:;">Something else here</a>
              </li>
            </ul>
          </div>
        </div>
        ';


    
        /*
        $results = [
          'grand_total' => $resultTotal['grand_total'],
          'distinct_user_count_total' => $resultTotal['distinct_user_count_total'],
          'last_30_days_total' => $resultLast30Days['last_30_days_total'],
          'distinct_user_count_last_30_days' => $resultLast30Days['distinct_user_count_last_30_days']
      ];
*/

        echo '
        <div class="row row-cols-1 row-cols-sm-3 mt-4">
          <div class="col">
            <div>
              <p class="mb-0 text-secondary">Registered Revenue</p>
              <h4 class="my-1">$'.number_format($referralstats['grand_total'], 2).'</h4>
              <p class="mb-0 font-13 text-success"><i class="bi bi-caret-up-fill align-middle"></i>$'.number_format($referralstats['last_30_days_total'], 2).' Since last month</p>
            </div>
          </div>
          <div class="col">
            <div>
              <p class="mb-0 text-secondary">Confirmed Revenue</p>
              <h4 class="my-1">$'.number_format($referralstats['confirmed_total'], 2).'</h4>
              <p class="mb-0 font-13 text-success"><i class="bi bi-caret-up-fill align-middle"></i>12.3% Since last month</p>
            </div>
          </div>
          <div class="col">
            <div>
              <p class="mb-0 text-secondary">Total Customers</p>
              <h4 class="my-1">'.number_format($referralstats['distinct_user_count_total'], 0).'</h4>
              <p class="mb-0 font-13 text-danger"><i class="bi bi-caret-down-fill align-middle"></i>2.4% Since last month</p>
            </div>
          </div>
        </div>
        <div id="chart4"></div>
      </div>
    </div>
  </div>
  </div>
';



$sql = "SELECT
            t.`status`, 
            COUNT(*) AS count, 
            SUM(t.revenue) AS total_revenue
        FROM
            bg_transactions AS t
        INNER JOIN (
            SELECT DISTINCT name
            FROM bg_user_attributes
            WHERE type = 'referred' AND user_id = :salesrep_userid and `status`='A'
        ) AS ua
        ON 
            t.user_id = ua.name
        GROUP BY
            t.`status`";

$stmt = $database->prepare($sql);
$stmt->execute(['salesrep_userid' =>$current_user_data['user_id']]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

 $status_revenue_sums = $bg_sales_trackingstatus ;


// Initialize variables for display
#$payout_total = array_sum(array_column($results, 'total_revenue'));
#$payout_total = array_sum($status_revenue_sums);
$payout_total = array_sum(array_column($results, 'total_revenue'));

$payout_so_far = 0;  // Calculate based on status conditions below
 $status_counts = $bg_sales_trackingstatus ;

 
foreach ($results as $row) {
    $status_counts[$row['status']] = $row['count'];
    if (in_array($row['status'], ['Active', 'Pending', 'Paid'])) {
        $payout_so_far += $row['total_revenue'];
    }
}

/* // Prepare percentages for progress bar
$progress_percentages = array_map(function ($status) use ($payout_total) {
    return $payout_total > 0 ? round(($status / $payout_total) * 100, 2) : 0;
}, $status_counts); */

// Prepare percentages for the progress bar based on revenue
$progress_percentages = array_map(function ($count) use ($payout_total) {
  // Define the factor to multiply the percentages by to increase visibility
  $factor = 100;
  return $payout_total > 0 ? round(($count / $payout_total) * 100 * $factor, 2) : 0;
}, $status_counts);

echo '
<div class="row mt-3">
<div class="col-lg-6 col-xl-7 col-xxl-8 mb-3 pe-lg-2 mb-3">
  <div class="card h-lg-100">
    <div class="card-body d-flex align-items-center">
      <div class="w-100">
        <h6 class="mb-3 text-800">Payout Schedule <strong class="text-1100">$' . number_format($payout_so_far, 2) . ' </strong>of $' . number_format($payout_total, 2) . '</h6>
        <div class="progress-stacked mb-3 rounded-3" style="height: 10px;">
          <div class="progress" style="width: ' . $progress_percentages['Active'] . '%;" role="progressbar" aria-valuenow="' . $progress_percentages['Active'] . '" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar bg-progress-gradient border-end border-100 border-2"></div>
          </div>
          <div class="progress" style="width: ' . $progress_percentages['Pending'] . '%;" role="progressbar" aria-valuenow="' . $progress_percentages['Pending'] . '" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar bg-info border-end border-100 border-2"></div>
          </div>
          <div class="progress" style="width: ' . $progress_percentages['Paid'] . '%;" role="progressbar" aria-valuenow="' . $progress_percentages['Paid'] . '" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar bg-success border-end border-100 border-2"></div>
          </div>
          <div class="progress" style="width: ' . $progress_percentages['Reviewing'] . '%;" role="progressbar" aria-valuenow="' . $progress_percentages['Reviewing'] . '" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar bg-200"></div>
          </div>
        </div>
';

echo '
<div class="row fs-10 fw-semi-bold text-500 g-0">
    <div class="col-auto d-flex align-items-center pe-3">
        <span class="dot bg-primary"></span>
        <span>Active </span>
        <span class="d-none d-md-inline-block d-lg-none d-xxl-inline-block">(' . $status_counts['Active'] . ')</span>
    </div>
    <div class="col-auto d-flex align-items-center pe-3">
        <span class="dot bg-info"></span>
        <span>Pending </span>
        <span class="d-none d-md-inline-block d-lg-none d-xxl-inline-block">(' . $status_counts['Pending'] . ')</span>
    </div>
    <div class="col-auto d-flex align-items-center pe-3">
        <span class="dot bg-success"></span>
        <span>Paid </span>
        <span class="d-none d-md-inline-block d-lg-none d-xxl-inline-block">(' . $status_counts['Paid'] . ')</span>
    </div>
    <div class="col-auto d-flex align-items-center">
        <span class="dot bg-200"></span>
        <span>Reviewing </span>
        <span class="d-none d-md-inline-block d-lg-none d-xxl-inline-block">(' . $status_counts['Reviewing'] . ')</span>
    </div>
</div>
';

echo '
        

      </div>
    </div>
  </div>
</div>
<div class="col-lg-6 col-xl-5 col-xxl-4 mb-3 ps-lg-2">
  <div class="card h-lg-100">
    <div class="bg-holder bg-card" style="background-image:url(/public/assets/img/icons/spot-illustrations/corner-1.png);">
    </div>
    <!--/.bg-holder-->

    <div class="card-body position-relative ">
        <div class="row h-100 justify-content-between g-0">
          <div class="col-5 col-sm-6 col-xxl pe-2">
            <h6 class="mt-1">Plan Breakdown</h6>
            <div class="fs-11 mt-3">
              <div class="d-flex flex-between-center mb-1">
                <div class="d-flex align-items-center"><span class="dot bg-primary"></span><span class="fw-semi-bold">Free</span></div>
                <div class="d-xxl-none">33%</div>
              </div>
              <div class="d-flex flex-between-center mb-1">
                <div class="d-flex align-items-center"><span class="dot bg-info"></span><span class="fw-semi-bold">Gold</span></div>
                <div class="d-xxl-none">29%</div>
              </div>
              <div class="d-flex flex-between-center mb-1">
                <div class="d-flex align-items-center"><span class="dot bg-300"></span><span class="fw-semi-bold">Life</span></div>
                <div class="d-xxl-none">20%</div>
              </div>
            </div>
          </div>
          <div class="col-auto position-relative">
            <div class="echart-market-share"></div>
            <div class="position-absolute top-50 start-50 translate-middle text-1100 fs-7">26M</div>
          </div>
        </div></div>


  </div>
</div>
</div>

';

echo '

<script src="/public/assets/vendors/echarts/echarts.min.js"></script>
<script src="/public/assets/vendors/fontawesome/all.min.js"></script>
<script src="/public/assets/vendors/lodash/lodash.min.js"></script>
';




// TRANSACTION HISTORY
echo '
  <div class="row mt-3">
  <div class="col-xl d-flex">
  <div class="card radius-10 w-100">
    <div class="card-body">
      <div class="d-flex align-items-center">
        <div>
          <h5 class="mb-1">Transaction History</h5>
          <p class="mb-0 font-13 text-secondary"><i class="bi bi-calendar3"></i> in last 30 days revenue</p>
        </div>
        <div class="dropdown ms-auto d-none">
          <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown">	<i class="bx bx-dots-horizontal-rounded font-22 text-option"></i>
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="javascript:;">Action</a>
            </li>
            <li><a class="dropdown-item" href="javascript:;">Another action</a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="javascript:;">Something else here</a>
            </li>
          </ul>
        </div>
      </div>
      <div class="table-responsive mt-4">
        <table class="table align-middle mb-0 table-hover" id="Transaction-History">
          <thead class="table-light">
            <tr>
              <th>Customer Name</th>
              <th>Date & Time</th>
              <th>Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>';

#breakpoint($userlist);
          foreach($userlist as $userrow){
            $statuscolor='secondary';
            if (isset($userrow['referral_status'])) {
    switch($userrow['referral_status']) {
      case 'pending': $statuscolor='info'; break;
      case 'active': $statuscolor='primary'; break;
      case 'paid': $statuscolor='success'; break;
      default: $statuscolor='secondary'; break;
    }
  }
    
$avatar='/public/images/defaultavatar.png';
  if (!empty($userrow['avatar'])) { $avatar='/'.$userrow['avatar']; }

  echo '
  <tr>
      <td>
          <div class="d-flex align-items-center">
              <div class="">
                  <img src="'.$avatar.'" class="rounded-circle" width="46" height="46" alt="" />
              </div>
              <div class="ms-2">
                  <h6 class="mb-1 font-14">'.($userrow['first_name']??'').' '.($userrow['last_name']??'').'</h6>
                  <p class="mb-0 font-13 text-secondary">Id #'.($userrow['user_id']??'').'</p>
              </div>
          </div>
      </td>
      <td>'.($userrow['create_dt']??'').'</td>
      <td>$'.number_format(($userrow['referral_payout']??0), 2).'</td>
      <td>
          <div class="badge rounded-pill bg-'.$statuscolor.' w-50 p-2">'.(($userrow['referral_status']??'') ? ucwords(($userrow['referral_status']??'')) : '').'</div>
      </td>
  </tr>
';

          }
          /*
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="">
                    <img src="assets/images/avatars/avatar-2.png" class="rounded-circle" width="46" height="46" alt="" />
                  </div>
                  <div class="ms-2">
                    <h6 class="mb-1 font-14">Payment from Pauline Bird</h6>
                    <p class="mb-0 font-13 text-secondary">Refrence Id #9653248</p>
                  </div>
                </div>
              </td>
              <td>Jan 12, 2021</td>
              <td>+566.00</td>
              <td>
                <div class="badge rounded-pill bg-info text-dark w-100">In Progress</div>
              </td>
            </tr>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="">
                    <img src="assets/images/avatars/avatar-3.png" class="rounded-circle" width="46" height="46" alt="" />
                  </div>
                  <div class="ms-2">
                    <h6 class="mb-1 font-14">Payment from Ralph Alva</h6>
                    <p class="mb-0 font-13 text-secondary">Refrence Id #7689524</p>
                  </div>
                </div>
              </td>
              <td>Jan 14, 2021</td>
              <td>+636.00</td>
              <td>
                <div class="badge rounded-pill bg-danger w-100">Declined</div>
              </td>
            </tr>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="">
                    <img src="assets/images/avatars/avatar-4.png" class="rounded-circle" width="46" height="46" alt="" />
                  </div>
                  <div class="ms-2">
                    <h6 class="mb-1 font-14">Payment from John Roman</h6>
                    <p class="mb-0 font-13 text-secondary">Refrence Id #8335884</p>
                  </div>
                </div>
              </td>
              <td>Jan 15, 2021</td>
              <td>+246.00</td>
              <td>
                <div class="badge rounded-pill bg-success w-100">Completed</div>
              </td>
            </tr>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="">
                    <img src="assets/images/avatars/avatar-7.png" class="rounded-circle" width="46" height="46" alt="" />
                  </div>
                  <div class="ms-2">
                    <h6 class="mb-1 font-14">Payment from David Buckley</h6>
                    <p class="mb-0 font-13 text-secondary">Refrence Id #7865986</p>
                  </div>
                </div>
              </td>
              <td>Jan 16, 2021</td>
              <td>+876.00</td>
              <td>
                <div class="badge rounded-pill bg-info text-dark w-100">In Progress</div>
              </td>
            </tr>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="">
                    <img src="assets/images/avatars/avatar-8.png" class="rounded-circle" width="46" height="46" alt="" />
                  </div>
                  <div class="ms-2">
                    <h6 class="mb-1 font-14">Payment from Lewis Cruz</h6>
                    <p class="mb-0 font-13 text-secondary">Refrence Id #8576420</p>
                  </div>
                </div>
              </td>
              <td>Jan 18, 2021</td>
              <td>+536.00</td>
              <td>
                <div class="badge rounded-pill bg-success w-100">Completed</div>
              </td>
            </tr>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="">
                    <img src="assets/images/avatars/avatar-9.png" class="rounded-circle" width="46" height="46" alt="" />
                  </div>
                  <div class="ms-2">
                    <h6 class="mb-1 font-14">Payment from James Caviness</h6>
                    <p class="mb-0 font-13 text-secondary">Refrence Id #3775420</p>
                  </div>
                </div>
              </td>
              <td>Jan 18, 2021</td>
              <td>+536.00</td>
              <td>
                <div class="badge rounded-pill bg-success w-100">Completed</div>
              </td>
            </tr>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="">
                    <img src="assets/images/avatars/avatar-10.png" class="rounded-circle" width="46" height="46" alt="" />
                  </div>
                  <div class="ms-2">
                    <h6 class="mb-1 font-14">Payment from Peter Costanzo</h6>
                    <p class="mb-0 font-13 text-secondary">Refrence Id #3768920</p>
                  </div>
                </div>
              </td>
              <td>Jan 19, 2021</td>
              <td>+536.00</td>
              <td>
                <div class="badge rounded-pill bg-success w-100">Completed</div>
              </td>
            </tr>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="">
                    <img src="assets/images/avatars/avatar-11.png" class="rounded-circle" width="46" height="46" alt="" />
                  </div>
                  <div class="ms-2">
                    <h6 class="mb-1 font-14">Payment from Johnny Seitz</h6>
                    <p class="mb-0 font-13 text-secondary">Refrence Id #9673520</p>
                  </div>
                </div>
              </td>
              <td>Jan 20, 2021</td>
              <td>+86.00</td>
              <td>
                <div class="badge rounded-pill bg-danger w-100">Declined</div>
              </td>
            </tr>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="">
                    <img src="assets/images/avatars/avatar-12.png" class="rounded-circle" width="46" height="46" alt="" />
                  </div>
                  <div class="ms-2">
                    <h6 class="mb-1 font-14">Payment from Lewis Cruz</h6>
                    <p class="mb-0 font-13 text-secondary">Refrence Id #8576420</p>
                  </div>
                </div>
              </td>
              <td>Jan 18, 2021</td>
              <td>+536.00</td>
              <td>
                <div class="badge rounded-pill bg-success w-100">Completed</div>
              </td>
            </tr>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="">
                    <img src="assets/images/avatars/avatar-13.png" class="rounded-circle" width="46" height="46" alt="" />
                  </div>
                  <div class="ms-2">
                    <h6 class="mb-1 font-14">Payment from David Buckley</h6>
                    <p class="mb-0 font-13 text-secondary">Refrence Id #8576420</p>
                  </div>
                </div>
              </td>
              <td>Jan 22, 2021</td>
              <td>+854.00</td>
              <td>
                <div class="badge rounded-pill bg-info text-dark w-100">In Progress</div>
              </td>
            </tr>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="">
                    <img src="assets/images/avatars/avatar-14.png" class="rounded-circle" width="46" height="46" alt="" />
                  </div>
                  <div class="ms-2">
                    <h6 class="mb-1 font-14">Payment from Thomas Wheeler</h6>
                    <p class="mb-0 font-13 text-secondary">Refrence Id #4278620</p>
                  </div>
                </div>
              </td>
              <td>Jan 18, 2021</td>
              <td>+536.00</td>
              <td>
                <div class="badge rounded-pill bg-success w-100">Completed</div>
              </td>
            </tr>
            */

            echo '
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

</div>


</div>

</div>
<div class="my-5 py-5"></div>

';

#echo $chartjsoutput;
$footerattribute['rawfooter']=true;


include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();

