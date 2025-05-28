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
    /* Max height calculated based on the number of grid rows (e.g., 6) */
   grid-auto-rows: minmax(120px, calc((100vh / 6) - 10px)); 
     gap: 1px; /* Small gap between the day boxes */
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
            overflow-y: auto; /* Enable vertical scrolling */
            max-height: 100%; /* Ensure content stays within bounds */
        }
        .company-logo {
            max-width: 35px;
            max-height: 35px;
        }


            /* Style the scrollbar */
        .calendar-body::-webkit-scrollbar {
            width: 6px; /* Thin scrollbar */
        }

        .calendar-body::-webkit-scrollbar-thumb {
            background-color: #888; /* Color of the scrollbar */
            border-radius: 10px; /* Round the scrollbar handle */
        }

        .calendar-body::-webkit-scrollbar-thumb:hover {
            background-color: #555; /* Darker color on hover */
        }
.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr); /* Ensure 7 equal-width columns */
    gap: 1px; /* Small gap between the day names */
    background: #f8f9fa; /* Light background for the header */
    border-bottom: 1px solid #ddd; /* Optional separator */
}
.calendar-day-name {
    text-align: center;
    height: 20px !important; /* Fix the height */
    line-height: 20px !important; /* Match line height to height */
    padding: 0 !important; /* Remove any padding */
    margin: 0 !important; /* Remove any margin */
    font-size: 14px; /* Optional: control font size */
    display: flex;
    align-items: center;
    justify-content: center; /* Center text */
    background: #f8f9fa; /* Optional: light gray background for clarity */
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
$monthName = date('F', $timestamp); // Full month name, e.g., "September"
$year = date('Y', $timestamp); // Year, e.g., "2024"



$till=$app->getTimeTilBirthday($current_user_data['birthdate']);
$results = $account->getbusinesslist_rewards($current_user_data, 'list', '"success", "success-btn"', 0, true);
#breakpoint($results);   
/*
if (empty($rewards)) {
    echo '
    <div class="col-12 text-center">
        <p class="lead text-muted">You currently have no rewards.</p>
    </div>
    ';
} else {
    foreach ($results as $company) {

*/
// Example current user data
#$current_user_data = [    'birthdate' => '2024-09-15', ];
#breakpoint($till);
?>
<div class="container main-content">
    <h1 class="text-center my-4">Your Reward Calendar: <b><?php echo $monthName . ' ' . $year; ?></b></h1>
  
        <?php
        $daysInMonth = date('t', $timestamp);
        $startDayOfWeek = date('w', $timestamp);
        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        // Print day names header
        echo '<div class="calendar-header ">';   
        foreach ($dayNames as $dayName) {
            echo '<div class="calendar-day-name">' . $dayName . '</div>';      }
echo '</div>';


            echo '  <div class="calendar-container">';
        // Print empty cells for days before the start of the month
        for ($i = 0; $i < $startDayOfWeek; $i++) {
            echo '<div class="calendar-day empty"></div>';
        }

        // Print days of the month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = sprintf('%s-%02d', $calendar, $day);
            $isBirthdate = ($till['date'] == $currentDate);

            // Check for rewards on this date
            $hasRewards = false;
            foreach ($results as $company) {
                if ($company['availability_from_date'] == $currentDate) {
                    $hasRewards = true;
                    break;
                }
            }

            // Start calendar day cell
            echo '<div class="calendar-day">';
            
            // Apply secondary background if it's the user's birthdate
            $headerClass = 'calendar-header';
            if ($isBirthdate) {
                $headerClass .= ' bg-secondary text-white"  data-bs-toggle="tooltip" title="Your Birthday!!';
            }

            // Create header link if rewards are available
            $headerContent = $day;
            if ($hasRewards) {
                $headerContent = '<a href="redeem?day=' . $currentDate . '" class="text-decoration-none text-dark">' . $headerContent . '</a>';
            }

            // Add cake icon if it's the user's birthdate
            if ($isBirthdate) {
                $headerContent = '<i class="bi bi-cake me-3"></i> ' . $headerContent . ' <i class="bi bi-cake ms-3"></i>';
            }

            // Print header
            echo '<div class="' . $headerClass . '">' . $headerContent . '</div>';

            // Print rewards
            echo '<div class="calendar-body">';
            foreach ($results as $company) {
                if ($company['availability_from_date'] == $currentDate) {
                    echo '<img src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" alt="' . htmlspecialchars($company['company_name']) . ' Logo" class="company-logo" data-bs-toggle="tooltip" title="' . htmlspecialchars($company['company_name']) . '">';
                }
            }
            echo '</div>';

            // End calendar day cell
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
