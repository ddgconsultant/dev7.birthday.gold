<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$additionalstyles .= '
    <style>
        .calendar-container {
            flex-grow: 1;
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            grid-auto-rows: minmax(120px, calc((100vh / 6) - 10px)); 
            gap: 1px;
        }

        .calendar-day {
            border: 1px solid #ddd;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 5px;
        }

        .calendar-header {
            background-color: #f8f9fa;
            text-align: center;
            font-weight: bold;
            padding: 5px;
        }

        .calendar-body {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 5px;
            padding: 5px;
            overflow-y: auto;
            max-height: 100%;
        }

        .company-logo {
            max-width: 35px;
            max-height: 35px;
        }

        .calendar-body::-webkit-scrollbar {
            width: 6px;
        }

        .calendar-body::-webkit-scrollbar-thumb {
            background-color: #888;
            border-radius: 10px;
        }

        .calendar-body::-webkit-scrollbar-thumb:hover {
            background-color: #555;
        }

        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
        }

        .calendar-day-name {
            text-align: center;
            height: 20px !important;
            line-height: 20px !important;
            padding: 0 !important;
            margin: 0 !important;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }

        /* New styles for navigation and highlighting */
        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .calendar-nav-btn {
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }

        .calendar-nav-btn:hover {
            background-color: #0056b3;
            color: white;
        }

        .today {
            background-color: #e8f4ff !important;
        }

        .birthday {
            background-color: #ffd700 !important;
        }

        .has-rewards .day-number {
            background-color: #007bff;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-block;
        }
    </style>
';

$calendar = "2024-12";
if (isset($_GET['date']) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $_GET['date'])) {
    $calendar = $_GET['date'];
}
if (!isset($_GET['date'])) {
    $calendar = date('Y-m');
}

$timestamp = strtotime($calendar . "-01");
$monthName = date('F', $timestamp);
$year = date('Y', $timestamp);

// Calculate previous and next month
$prevMonth = date('Y-m', strtotime('-1 month', $timestamp));
$nextMonth = date('Y-m', strtotime('+1 month', $timestamp));

$till = $app->getTimeTilBirthday($current_user_data['birthdate']);
$results = $account->getbusinesslist_rewards($current_user_data, 'list', '"success", "success-btn"', 0, true);

// Modify the results array to set empty expiration_date rewards to today's date
foreach ($results as &$reward) {
    if (empty($reward['expiration_date'])) {
        $reward['availability_from_date'] = date('Y-m-d');
    }
}

?>
<div class="container main-content">
    <div class="calendar-nav">
        <a href="?date=<?php echo $prevMonth; ?>" class="calendar-nav-btn">&larr; Previous Month</a>
        <h1 class="text-center my-4">Your Reward Calendar: <b><?php echo $monthName . ' ' . $year; ?></b></h1>
        <a href="?date=<?php echo $nextMonth; ?>" class="calendar-nav-btn">Next Month &rarr;</a>
    </div>

    <?php
    $daysInMonth = date('t', $timestamp);
    $startDayOfWeek = date('w', $timestamp);
    $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    $today = date('Y-m-d');
    $birthdate = date('m-d', strtotime($current_user_data['birthdate']));

    // Print day names header
    echo '<div class="calendar-header">';
    foreach ($dayNames as $dayName) {
        echo '<div class="calendar-day-name">' . $dayName . '</div>';
    }
    echo '</div>';

    echo '<div class="calendar-container">';
    
    // Print empty cells for days before the start of the month
    for ($i = 0; $i < $startDayOfWeek; $i++) {
        echo '<div class="calendar-day empty"></div>';
    }

    // Print days of the month
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $currentDate = sprintf('%s-%02d', $calendar, $day);
        // Compare just month and day for birthday, ignoring year
        $currentMonthDay = date('m-d', strtotime($currentDate));
        $birthdayMonthDay = date('m-d', strtotime($current_user_data['birthdate']));
        $isBirthdate = ($currentMonthDay == $birthdayMonthDay);
        $isToday = ($today == $currentDate);

        // Check for rewards on this date
        $hasRewards = false;
        foreach ($results as $company) {
            if ($company['availability_from_date'] == $currentDate) {
                $hasRewards = true;
                break;
            }
        }

        // Build CSS classes for the day
        $dayClasses = 'calendar-day';
        if ($isToday) $dayClasses .= ' today';
        if ($isBirthdate) $dayClasses .= ' birthday';
        if ($hasRewards) $dayClasses .= ' has-rewards';

        echo '<div class="' . $dayClasses . '">';
        
        // Create header content
        $headerContent = '<span class="day-number">';
        if ($hasRewards) {
            $headerContent .= '<a href="/myaccount/redeem-list?active=1" class="text-decoration-none" style="color: inherit;">' . $day . '</a>';
        } else {
            $headerContent .= $day;
        }
        $headerContent .= '</span>';

        // Add birthday icon if applicable
        if ($isBirthdate) {
            $headerContent = '<i class="bi bi-cake me-3"></i> ' . $headerContent . ' <i class="bi bi-cake ms-3"></i>';
        }

        echo '<div class="calendar-header">' . $headerContent . '</div>';

        // Print rewards
        echo '<div class="calendar-body">';
        foreach ($results as $company) {
            if ($company['availability_from_date'] == $currentDate) {
                echo '<img src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" 
                     alt="' . htmlspecialchars($company['company_name']) . ' Logo" 
                     class="company-logo" 
                     data-bs-toggle="tooltip" 
                     title="' . htmlspecialchars($company['company_name']) . '">';
            }
        }
        echo '</div>';
        echo '</div>';
    }
    ?>
    </div>
</div>

<?php
$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>