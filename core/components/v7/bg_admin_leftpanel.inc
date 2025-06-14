<!-- ========================================================================================================================================================== -->
<!-- START OF ADMINLEFTPANEL -->
<!-- ========================================================================================================================================================== -->


<?PHP
function generateStatusList($title, $url, $includeKeys = [], $hideKeys = [], $excludeKeys = [], $ulId = '')
{
    $chartcontent = @file_get_contents($url);

    if ($chartcontent === false) {
        // Tooltip for "file_get_contents" failure
        return '<ul id="' . htmlspecialchars($ulId) . '" class="list-unstyled list-userdetail">
<li>
<div class="title-wrapper">
' . htmlspecialchars($title) . '
<div class="title-line"></div>
<i class="bi bi-exclamation-triangle text-danger" title="Unable to fetch data from the provided URL: ' . htmlspecialchars($url) . '"></i>
</div>
</li>
</ul>';
    }

    $data = json_decode($chartcontent, true);

    if ($data === null || !is_array($data)) {
        // Tooltip for "json_decode" failure
        return '<ul id="' . htmlspecialchars($ulId) . '" class="list-unstyled list-userdetail">
<li>
<div class="title-wrapper">
' . htmlspecialchars($title) . '
<div class="title-line"></div>
<i class="bi bi-exclamation-triangle text-warning" title="Invalid JSON format received from the URL: ' . htmlspecialchars($url) . '"></i>
</div>
</li>
</ul>';
    }


    $output = '<ul id="' . $ulId . '" class="list-unstyled list-userdetail">
<li>
<div class="title-wrapper">
' . $title . '
<div class="title-line"></div>';

    $grandTotal = 0;

    if (count($hideKeys) > 0) {
        $output .= '<button class="toggle-btn" onclick="show_keys(\'' . $ulId . '\')"><i class="bi bi-caret-up-fill"></i></button>';
    }

    $output .= '</div>
</li>';

    foreach ($data as $item) {
        $itemstatus=$item['status']??''; 
        
        if (isset($item['count'])) {
            $status = $itemstatus !== '' ? ucfirst($itemstatus) : 'Grand Total';

            // Skip this item if the status is in the excludeKeys array
            if (!empty($excludeKeys) && in_array($itemstatus, $excludeKeys)) {
                continue;
            }

            // Skip this item if the status is not in the includeKeys array (if includeKeys is not empty)
            if (!empty($includeKeys) && !in_array($itemstatus, $includeKeys)) {
                continue;
            }

            $count = (int)$item['count'];

            if ($itemstatus !== '') {
                $grandTotal += $count;
            }

            $icon = '';
            switch ($itemstatus) {
                case 'active':
                case 'finalized':
                    $icon = '<i class="bi bi-check-circle"></i>';
                    break;
                case 'giftlock':
                    $icon = '<i class="bi bi-lock"></i>';
                    break;
                case 'pending':
                    $icon = '<i class="bi bi-hourglass-split"></i>';
                    break;
                case 'validated':
                    $icon = '<i class="bi bi-check"></i>';
                    break;
                case 'success':
                    $icon = '<i class="bi bi-trophy"></i>';
                    break;
                case '':
                    $icon = '<i class="bi bi-calculator"></i>';
                    break;
                default:
                    $icon = '<i class="bi bi-info-circle"></i>';
                    break;
            }

            // Apply 'd-none' and 'hidden-item' class only if the status is in $hideKeys
            $liClass = in_array($itemstatus, $hideKeys) ? ' class="d-none hidden-item"' : '';

            $output .= '<li' . $liClass . ' data-status="' . htmlspecialchars($itemstatus) . '">' . $icon . ' ' . $status . ': <span class="placeholder">' . number_format($count) . '</span></li>';
        }
    }

    $output .= '</ul>';
    return $output;
}


include_once($dir['core_components'] . '/user_getaccountdetails.inc');


if (empty($useraccountdetails)) {
    header('Location: /login.php');
    exit;
}
$lefpanelcontent['body_class'] = $lefpanelcontent['body_class'] ?? 'container-fluid mt-4';
$lefpanelcontent['panel_class'] = $lefpanelcontent['panel_class'] ?? 'col-md-2 col-lg-2';


if (!isset($lefpanelcontent['prepanel'])) $lefpanelcontent['prepanel'] = '';
if (!isset($lefpanelcontent['postpanel'])) $lefpanelcontent['postpanel'] = '';
$additionalstyles .= "
<style >
/* ADMINLEFTPANEL styles ======================================== */
.main-content { flex: 1; overflow-y: auto; padding: 20px; }
.border-right { border-right: 1px solid lightgrey; }
.title-wrapper { display: flex; align-items: center; margin-bottom: 5px;     /* Adjust to your needs */ }
.title-wrapper h4 { margin: 0; white-space: nowrap; padding-right: 10px;     /* Adjust space between text and line */ }
.title-wrapper .title-line { flex-grow: 1; height: 1px; background-color: #000;     /* Adjust color of the line */ }
.list-unstyled.list-userdetail li { padding: 5px 0; font-size: .9rem;     /* Adjust font size */ }
.list-unstyled.list-userdetail li i { margin-left: 10px;     /* Add right margin to icons */ margin-right: 5px;     /* Add right margin to icons */ }
.placeholder { background: none !important;     /* Remove the background */ display: inline-block;     /* Ensure it displays correctly inline */ margin-left: 5px; cursor: text !important; }
a:hover .placeholder { cursor: pointer !important;     /* Change the cursor to pointer on hover */ }
.toggle-btn { background: none; border: none; font-size: 1.2rem; margin-left: 5px; cursor: pointer; display: flex; align-items: center; }
.toggle-btn i { font-size: 1.2rem;     /* Adjust the size of the caret icon */ }
</style >
";



echo '
<!-- ======================================================== -->
<!-- START OF COLUMN -->
<!-- ======================================================== -->
<!-- Main Content -->
<div class="main-content">
<div class="' . $lefpanelcontent['body_class'] . ' random-text">
<div class="row">

<div class="' . $lefpanelcontent['panel_class'] . ' d-none d-md-block border-right">
' . $lefpanelcontent['prepanel'] . '
';



//  USERS STATUS PANEL
$title = ' <a href="/admin/user-list" class="text-decoration-none"><h4>Users</h4></a>';
$url = 'https://metabase.birthdaygold.cloud/public/question/5f008ba2-7c9c-4ea4-b083-c561c8e9c26d.json';
echo ' ' . generateStatusList($title, $url, [], [], [],  'statusList1') . ' 
';



//  ENROLLMENTS STATUS PANEL
$title = '<a href="/admin/bgrab/" class="text-decoration-none"><h4>Enrollments</h4></a>';
$url = 'https://metabase.birthdaygold.cloud/public/question/eaff3cc2-8b41-4057-8fed-67bebdd95d15.json';
echo generateStatusList($title, $url, [], ['removed', 'existing'], ['testing'], 'enrollmentlist1');



//  BRANDS STATUS PANEL
$title = '  <a href="/admin/brands" class="text-decoration-none"><h4>'.ucfirst($website['biznames']).'</h4></a>';
$url = 'https://metabase.birthdaygold.cloud/public/question/8026f1ce-5443-4057-9e45-852aff320abb.json';
echo '  ' . generateStatusList(
    $title,
    $url,
    [],
    ['duplicate', 'finalized_fotp', 'finalized_otp_bgm', 'new2', 'ng_toocomplex', 'otprequired', 'toocomplex'],
    ['testing'],
    'brandslist1'
) . '
';


echo '
<ul class="list-unstyled list-userdetail">
<li>
<div class="title-wrapper">
<a href="/myaccount/profile" class="text-decoration-none"><h4>Systems</h4></a>
<div class="title-line"></div>
</div>
</li>
';



// Define the monitor IDs and corresponding names that you want to check
$monitors = [
    22 => 'Back End Systems',
    3 => 'Front End Systems',
    1 => 'Infrastructure Systems',
    23 => 'Support Services',
    24 => 'Security of Systems',
    11 => 'Vendor Services'
];

$monitor_statuses = []; // Array to store statuses
$cache_time = 600; // Cache duration in seconds (10 minutes)

foreach ($monitors as $monitor_id => $monitor_name) {
    // Check if the monitor status is cached in the session
    $cache_key = 'monitor_status_' . $monitor_id;
    $cached_data = $session->get($cache_key);

    if ($cached_data && (time() - $cached_data['timestamp']) < $cache_time && $cached_data['status'] !== 'unknown') {
        // Use the cached status if it's still valid
        $monitor_statuses[$monitor_id] = $cached_data['status'];
    } else {
        // Mark the monitor status as unknown initially
        $monitor_statuses[$monitor_id] = 'unknown';
    }
}

// Begin outputting the HTML list items

foreach ($monitors as $monitor_id => $monitor_name) {
    // Determine the icon based on the status (initially 'unknown')
    $icon = 'bi bi-stopwatch text-muted';

    // Output the monitor status with the appropriate icon
    echo '<li data-monitor-id="' . $monitor_id . '" title="' . $monitor_id . '">' . htmlspecialchars($monitor_name) . ': <i class="' . $icon . '"></i></li>';
}


echo '
</ul>
<ul class="list-unstyled list-userdetail  d-none d-xxl-block">
<li>
<div class="title-wrapper">
<a href="#" class="text-decoration-none"><h4>Statistics</h4></a>
<div class="title-line"></div>
</div>
</li>
';
echo ' <li><a class="align-items-start mb-2" href="/myaccount/loginhistory"><i class="bi bi-clock-history"></i> Last Login: <span class="placeholder">' . $lastloginmessage['message'] . '</span></a></li>
';
echo '     <li><i class="bi bi-bar-chart-line"></i> Login Count: <span class="placeholder">' . $logincount . '</span></li>
<li><i class="bi bi-bar-chart-line"></i> <a href="https://metabase.birthdaygold.cloud/public/dashboard/ca5d9a12-c375-4988-979a-9bbab096451c" target="cgwindow">Charts and Graphs</a></li>

';


echo '
</ul>
';

echo $lefpanelcontent['postpanel'];
echo '
</div>

';
?>

<!-- ========================================================================================================================================================== -->
<!-- ADMINLEFTPANEL SCRIPTS -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusIcons = {
            'up': 'bi bi-arrow-up-circle-fill text-success',
            'down': 'bi bi-arrow-down-square-fill text-danger',
            'pending': 'bi bi-hourglass-split text-warning',
            'paused': 'bi bi-pause-btn-fill text-secondary',
            'unknown': 'bi bi-question-diamond text-muted'
        };

        function updateMonitorStatus(monitorId) {
            fetch('https://april21.birthday.gold:5443/get_monitor_status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: monitorId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    const status = data.status ? data.status.toLowerCase() : 'unknown';
                    const iconClass = statusIcons[status] || statusIcons['unknown'];

                    const monitorElement = document.querySelector('li[data-monitor-id="' + monitorId + '"] i');
                    if (monitorElement) {
                        monitorElement.className = iconClass;
                    }
                })
                .catch(error => {
                    console.error('Error fetching monitor status:', error);
                });
        }

        function refreshAllStatuses() {
            document.querySelectorAll('li[data-monitor-id]').forEach(item => {
                const monitorId = item.getAttribute('data-monitor-id');
                updateMonitorStatus(monitorId);
            });
        }

        // Initial status update
        refreshAllStatuses();

        // Periodic refresh every 10 minutes (600000 milliseconds)
        setInterval(refreshAllStatuses, 600000);
    });
</script>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        function show_keys(ulId) {
            const ulElement = document.getElementById(ulId);
            if (!ulElement) return;

            const toggleBtn = ulElement.querySelector('.toggle-btn i');
            const hiddenItems = ulElement.querySelectorAll('li.hidden-item');

            // Check if the first hidden item is currently hidden
            const isHidden = hiddenItems.length > 0 && hiddenItems[0].classList.contains('d-none');

            // Toggle visibility of only the hidden items
            hiddenItems.forEach(function(li) {
                if (isHidden) {
                    li.classList.remove('d-none');
                } else {
                    li.classList.add('d-none');
                }
            });

            // Update the icon state
            if (isHidden) {
                toggleBtn.classList.remove('bi-caret-up-fill');
                toggleBtn.classList.add('bi-caret-down-fill');
            } else {
                toggleBtn.classList.remove('bi-caret-down-fill');
                toggleBtn.classList.add('bi-caret-up-fill');
            }
        }

        // Attach the show_keys function to the window so it can be called inline
        window.show_keys = show_keys;
    });
</script>


<?PHP
echo '

<!-- ========================================================================================================================================================== -->
<!-- END OF USERLEFTPANEL -->
<!-- ========================================================================================================================================================== -->
';
