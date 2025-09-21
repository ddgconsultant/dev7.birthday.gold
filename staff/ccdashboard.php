<?php
$addClasses[] = 'Referral';
$addClasses[] = 'TimeClock';
$addClasses[] = 'Charts';
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 



$additionalstyles.='<link rel="stylesheet" href="/public/css/myaccount.css">
<style>
.feature {
    width: 90px;
    height: 90px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.feature i {
    font-size: 48px;
}

.tooltip {
    z-index: 1039 !important;
}

/* Improved dashboard layout */
.main-content {
    padding: 2rem 0;
}

.card {
    margin-bottom: 1.5rem;
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    transition: box-shadow 0.3s ease;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    border: none;
}

.bg-transparent-50 {
    background-color: rgba(255,255,255,0.95) !important;
}

/* Stats styling */
.text-primary {
    color: #667eea !important;
}

h3.text-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Table improvements */
.table {
    margin-bottom: 0;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

/* Badge improvements */
.badge {
    padding: 0.5em 1em;
    font-weight: 500;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .main-content {
        padding: 1rem 0;
    }
    
    .card {
        margin-bottom: 1rem;
    }
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

// Ensure referralstats is an array with default values
if (!is_array($referralstats)) {
    $referralstats = [];
}

// Set default values for missing keys
$default_stats = [
    'grand_total' => 0,
    'distinct_user_count_total' => 0,
    'last_30_days_total' => 0,
    'distinct_user_count_last_30_days' => 0,
    'confirmed_total' => 0,
    'today_total' => 0
];

foreach ($default_stats as $key => $default_value) {
    if (!isset($referralstats[$key])) {
        $referralstats[$key] = $default_value;
    }
}

// Ensure userlist is an array
if (!is_array($userlist)) {
    $userlist = [];
}


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
  // Ensure $userrow is an array before accessing it
  if (is_array($userrow) && isset($userrow['today_flag']) && $userrow['today_flag']==1) {
    $totalpayouttoday = $totalpayouttoday + ($userrow['referral_payout'] ?? 0);
  }
}
$avatar='/public/images/defaultavatar.png';
$avatarbuttontag='Upload';
if (is_array($current_user_data) && !empty($current_user_data['avatar'])) {
  $avatar = '/' . $current_user_data['avatar'];
  $avatarbuttontag = 'Change';
} else {

}
echo '
<div class="container-fluid">
    <div class="row g-3 mt-2">
        <div class="col-12">
            <div class="card bg-transparent-50 overflow-hidden shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="text-primary mb-2">' . $app->time_based_greeting(null, ', ') . $current_user_data['first_name'] . '!</h3>
                            <p class="text-muted mb-3">Here\'s what\'s happening with your sales today</p>
                            <div class="row">
                                <div class="col-sm-6 col-lg-4 mb-3">
                                    <div class="d-flex flex-column">
                                        <small class="text-muted fw-medium">Today\'s Sales</small>
                                        <h4 class="mb-0 text-dark">' . $referralstats['today_total'] . '</h4>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4 mb-3">
                                    <div class="d-flex flex-column">
                                        <small class="text-muted fw-medium">Today\'s Commissions</small>
                                        <h4 class="mb-0 text-success">$' . number_format($totalpayouttoday, 2) . '</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center text-md-end">
                            <img class="rounded-circle shadow" src="' . $avatar . '" alt="Profile" width="150" height="150" style="object-fit: cover;" />
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
    </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-0">Your Sales Statistics</h5>
                            <small class="text-white-50"><i class="bi bi-calendar3 me-1"></i>Last 30 days revenue</small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
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
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="border-start border-4 border-primary ps-3">
                                <small class="text-muted d-block">Registered Revenue</small>
                                <h4 class="mb-1">$'.number_format($referralstats['grand_total'], 2).'</h4>
                                <span class="badge bg-success-subtle text-success">
                                    <i class="bi bi-arrow-up me-1"></i>$'.number_format($referralstats['last_30_days_total'], 2).'
                                </span>
                                <small class="text-muted ms-2">vs last month</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border-start border-4 border-warning ps-3">
                                <small class="text-muted d-block">Confirmed Revenue</small>
                                <h4 class="mb-1">$'.number_format($referralstats['confirmed_total'], 2).'</h4>
                                <span class="badge bg-success-subtle text-success">
                                    <i class="bi bi-arrow-up me-1"></i>12.3%
                                </span>
                                <small class="text-muted ms-2">vs last month</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border-start border-4 border-info ps-3">
                                <small class="text-muted d-block">Total Customers</small>
                                <h4 class="mb-1">'.number_format($referralstats['distinct_user_count_total'], 0).'</h4>
                                <span class="badge bg-danger-subtle text-danger">
                                    <i class="bi bi-arrow-down me-1"></i>2.4%
                                </span>
                                <small class="text-muted ms-2">vs last month</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
';



$sql = "SELECT
            t.`transaction_status` AS status, 
            COUNT(*) AS count, 
            SUM(t.amount) AS total_revenue
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
            t.`transaction_status`";

$stmt = $database->prepare($sql);
$stmt->execute(['salesrep_userid' =>$current_user_data['user_id']]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize status tracking arrays with default values
$status_counts = [
    'Active' => 0,
    'Pending' => 0,
    'Paid' => 0,
    'Reviewing' => 0
];

$status_revenue_sums = [
    'Active' => 0,
    'Pending' => 0,
    'Paid' => 0,
    'Reviewing' => 0
];

// Initialize variables for display
$payout_total = array_sum(array_column($results, 'total_revenue'));
$payout_so_far = 0;  // Calculate based on status conditions below

 
foreach ($results as $row) {
    // Map the status to our expected values
    $status = ucfirst(strtolower($row['status'] ?? ''));
    
    // Only update if it's a recognized status
    if (isset($status_counts[$status])) {
        $status_counts[$status] = $row['count'];
        $status_revenue_sums[$status] = $row['total_revenue'];
    }
    
    if (in_array($status, ['Active', 'Pending', 'Paid'])) {
        $payout_so_far += $row['total_revenue'];
    }
}

/* // Prepare percentages for progress bar
$progress_percentages = array_map(function ($status) use ($payout_total) {
    return $payout_total > 0 ? round(($status / $payout_total) * 100, 2) : 0;
}, $status_counts); */

// Prepare percentages for the progress bar based on revenue
$progress_percentages = [];
foreach ($status_counts as $status => $count) {
    $progress_percentages[$status] = $payout_total > 0 ? round(($count / $payout_total) * 100, 2) : 0;
}

// Ensure all required statuses have a percentage
$required_statuses = ['Active', 'Pending', 'Paid', 'Reviewing'];
foreach ($required_statuses as $status) {
    if (!isset($progress_percentages[$status])) {
        $progress_percentages[$status] = 0;
    }
}

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
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-0">Transaction History</h5>
                            <small class="text-white-50"><i class="bi bi-clock-history me-1"></i>Recent referral transactions</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="Transaction-History">
                            <thead>
                                <tr>
                                    <th class="ps-4">Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>';

#breakpoint($userlist);
          foreach($userlist as $userrow){
            // Skip if $userrow is not an array
            if (!is_array($userrow)) {
                continue;
            }
            
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
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <img src="'.$avatar.'" class="rounded-circle me-3" width="40" height="40" alt="Avatar" style="object-fit: cover;" />
                                            <div>
                                                <h6 class="mb-0">'.($userrow['first_name']??'').' '.($userrow['last_name']??'').'</h6>
                                                <small class="text-muted">ID: #'.($userrow['user_id']??'').'</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-nowrap">'.($userrow['create_dt'] ? date('M d, Y', strtotime($userrow['create_dt'])) : '-').'</span>
                                    </td>
                                    <td>
                                        <strong>$'.number_format(($userrow['referral_payout']??0), 2).'</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-'.$statuscolor.'">'.(($userrow['referral_status']??'') ? ucwords(($userrow['referral_status']??'')) : 'Unknown').'</span>
                                    </td>
                                </tr>
';

          }

            echo '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> <!-- End container-fluid -->

<div class="my-5 py-5"></div>

';

#echo $chartjsoutput;
$footerattribute['rawfooter']=true;


include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();

