<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// -------------------------------------------------------------------------------
// PREP VARIABLES
// -------------------------------------------------------------------------------
$user_id = $current_user_data['user_id'];
$plandatafeatures = $plandetails_all = $app->plandetail('details_id', $current_user_data['account_product_id']);

$userplan = $current_user_data['account_plan'];
$plandetails = $plandatafeatures;
$birthdate = new DateTime($current_user_data['birthdate']);
if ($birthdate === false) {
    die('Invalid birthdate format');
}
$errormessage = '';
$finaloutput = '';
$birthdate = new DateTime($current_user_data['birthdate']);
$currentYear = (new DateTime())->format('Y');
$birthdate->setDate($currentYear, $birthdate->format('m'), $birthdate->format('d'));

$icalendar_start_date = clone $birthdate;
$icalendar_start_date->modify('-' . $plandetails['celebration_tour_days_before'] . ' days');

$icalendar_end_date = clone $birthdate;
$icalendar_end_date->modify('+' . $plandetails['celebration_tour_days_after'] . ' days');

$icalendar_start_date_str = $icalendar_start_date->format('Y-m-d');
$icalendar_end_date_str = $icalendar_end_date->format('Y-m-d');
$tourlistdates = [];
$stmt = $database->prepare("SELECT * FROM bg_user_tours WHERE user_id = :user_id AND calendar_dt BETWEEN :start_date AND :end_date and status='active'");
$stmt->execute([':user_id' => $user_id, ':start_date' => $icalendar_start_date_str, ':end_date' => $icalendar_end_date_str]);
$tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($tours as $tour) {
    $tourlistdates[] = $tour['calendar_dt'];
}
$tourlistdates = array_unique($tourlistdates);
$showbusinesses = false;
$selectedDate = $_GET['date'] ?? '';

// -------------------------------------------------------------------------------
// CALENDAR MONTH NAVIGATION
// -------------------------------------------------------------------------------
$calendarMonth = $_GET['month'] ?? date('Y-m');
list($calYear, $calMonth) = explode('-', $calendarMonth);
$calendarDate = new DateTime("$calYear-$calMonth-01");

// -------------------------------------------------------------------------------
// SAVE TOUR
// -------------------------------------------------------------------------------
if ($app->formposted()) {
    $tourdt = $_POST['calendar_date'] ?? '';
    $listofcompanies = $_POST['selectedCompanies'] ?? '';
    if (!empty($tourdt) && !empty($listofcompanies)) {
        $startdt = $tourdt . ' 00:00:01';
        $enddt = $tourdt . ' 23:59:59';
        foreach ($listofcompanies as $companyid) {
            $stmt = $database->prepare("insert bg_user_tours (user_id, company_id, calendar_dt, tour_start_dt, tour_end_dt, create_dt, modify_dt, status) 
values (:user_id, :company_id, :calendar_dt, :start_dt, :end_dt,  now(), now(), 'active')");
            $stmt->execute([':user_id' => $user_id, ':company_id' => $companyid, ':calendar_dt' => $tourdt, ':start_dt' => $startdt, ':end_dt' => $enddt]);
        }
        $errormessage = '<div class="alert alert-success">Your tour has been created.</div>';
        $transferpage['url'] = '/myaccount/celebrate';
        $transferpage['message'] = $errormessage;
        $system->endpostpage($transferpage);
        exit;
    }
    $errormessage = '<div class="alert alert-danger">You have to select some '.$website['biznames'].' to add to your tour.</div>';
    $transferpage['url'] = '/myaccount/tour-build-v2';
    $transferpage['message'] = $errormessage;
    $system->endpostpage($transferpage);
    exit;
}

// -------------------------------------------------------------------------------
// RETURN ENROLLED BUSINESSES
// -------------------------------------------------------------------------------
$apptype = $current_user_data['profile_phone_type'];

if (!empty($_GET['date'])) {
    $sql = "SELECT uc.*, c.company_name, c.appgoogle, c.appapple, c.description, c.short_description 
FROM bg_user_companies uc, bg_companies c WHERE uc.company_id=c.company_id 
and user_id = " . $current_user_data['user_id'] . ' and uc.status in ("success", "existing")  order by uc.modify_dt desc ';
    $output = '';

    $stmt = $database->prepare($sql);
    $stmt->execute();
    $companies = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $showbusinesses = true;

        $applink = $display->applink($apptype, $row);
        $appicon = $applink['applink'];
        $qrcode = $applink['qrlink'];

        $date = $_GET['date'];
        $company_id = $row['company_id'];
        $checkEnrollmentQuery = "SELECT * FROM bg_user_tours WHERE calendar_dt = :date AND company_id = :company_id and user_id= " . $current_user_data['user_id'] . "";
        $checkStmt = $database->prepare($checkEnrollmentQuery);
        $checkStmt->execute([':date' => $date, ':company_id' => $company_id]);

        $isChecked = $checkStmt->rowCount() > 0;
        $row['isChecked'] = $isChecked;
        $row['appicon'] = $appicon;
        $row['qrcode'] = $qrcode;
        
        // Get full company data including logo
        $company_data = $app->getcompany($row['company_id']);
        $row['company_logo'] = $company_data['company_logo'] ?? '';
        
        $companies[] = $row;
    }
}

// -------------------------------------------------------------------------------
// BUILD CALENDAR
// -------------------------------------------------------------------------------
function buildCalendarMonth($year, $month, $tourlistdates, $birthdayDate, $selectedDate, $icalendar_start_date, $icalendar_end_date) {
    $firstDay = new DateTime("$year-$month-01");
    $lastDay = clone $firstDay;
    $lastDay->modify('last day of this month');
    
    $startDayOfWeek = $firstDay->format('w');
    $daysInMonth = $lastDay->format('d');
    
    $prevMonth = clone $firstDay;
    $prevMonth->modify('-1 month');
    $nextMonth = clone $firstDay;
    $nextMonth->modify('+1 month');
    
    $calendar = '<div class="calendar-widget">';
    
    // Navigation
    $calendar .= '<div class="calendar-nav">';
    $calendar .= '<a href="?month=' . $prevMonth->format('Y-m') . '" class="cal-nav-btn"><i class="bi bi-chevron-left"></i></a>';
    $calendar .= '<h4 class="calendar-month-title">' . $firstDay->format('F Y') . '</h4>';
    $calendar .= '<a href="?month=' . $nextMonth->format('Y-m') . '" class="cal-nav-btn"><i class="bi bi-chevron-right"></i></a>';
    $calendar .= '</div>';
    
    // Weekday headers
    $calendar .= '<div class="calendar-grid">';
    $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    foreach ($weekdays as $day) {
        $calendar .= '<div class="calendar-weekday">' . $day . '</div>';
    }
    
    // Empty cells before first day
    for ($i = 0; $i < $startDayOfWeek; $i++) {
        $calendar .= '<div class="calendar-day empty"></div>';
    }
    
    // Days of month
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $currentDate = new DateTime("$year-$month-$day");
        $dateStr = $currentDate->format('Y-m-d');
        $classes = ['calendar-day'];
        $isClickable = true;
        
        // Check if date is within allowed range
        if ($currentDate < $icalendar_start_date || $currentDate > $icalendar_end_date) {
            $classes[] = 'disabled';
            $isClickable = false;
        }
        // Check if it's the birthday
        elseif ($dateStr == $birthdayDate) {
            $classes[] = 'birthday';
        }
        // Check if date is already booked
        elseif (in_array($dateStr, $tourlistdates)) {
            $classes[] = 'booked';
            $isClickable = false;
        }
        // Check if it's the selected date
        elseif ($dateStr == $selectedDate) {
            $classes[] = 'selected';
        }
        // Otherwise it's available
        else {
            $classes[] = 'available';
        }
        
        if ($isClickable) {
            $calendar .= '<a href="?date=' . $dateStr . '" class="' . implode(' ', $classes) . '">';
            $calendar .= '<span>' . $day . '</span>';
            $calendar .= '</a>';
        } else {
            $calendar .= '<div class="' . implode(' ', $classes) . '">';
            $calendar .= '<span>' . $day . '</span>';
            $calendar .= '</div>';
        }
    }
    
    // Empty cells after last day
    $remainingCells = 42 - ($startDayOfWeek + $daysInMonth);
    for ($i = 0; $i < $remainingCells; $i++) {
        $calendar .= '<div class="calendar-day empty"></div>';
    }

    $calendarerase='';
    for ($i = 0; $i < 7; $i++) {
        $calendarerase .= '<div class="calendar-day empty"></div>';
    }
    // Remove all occurrences of $calendarerase from $calendar
$calendar = str_replace($calendarerase, '', $calendar);

    
    $calendar .= '</div>'; // Close grid
    $calendar .= '</div>'; // Close widget
    
    return $calendar;
}

// -------------------------------------------------------------------------------
// DISPLAY PAGE SETUP
// -------------------------------------------------------------------------------
$userbirthdate = $current_user_data['birthdate'];
$birthdates = $account->getBirthdates($userbirthdate, $plandetails);

$today = new DateTime();
$birthDate = new DateTime($userbirthdate);
$birthDateThisYear = new DateTime($today->format('Y') . '-' . $birthDate->format('m-d'));
$birthDateNextYear = new DateTime(($today->format('Y') + 1) . '-' . $birthDate->format('m-d'));

if ($today < $birthDateThisYear) {
    $interval = $today->diff($birthDateThisYear);
    $currentAge = $today->format('Y') - $birthDate->format('Y') - 1;
} else {
    $interval = $today->diff($birthDateNextYear);
    $currentAge = $today->format('Y') - $birthDate->format('Y');
}

$daysUntilNextBirthday = $interval->days;

if ($daysUntilNextBirthday === 0) {
    $tag = "Happy Birthday, today you turned {$currentAge}!";
} elseif ($daysUntilNextBirthday > 335) {
    $tag = "On {$birthDateThisYear->format('l, F dS, Y')}, you turned {$currentAge}.";
} elseif ($daysUntilNextBirthday <= 30 && $today > $birthDateThisYear) {
    $tag = "On {$birthDateThisYear->format('l, F dS, Y')}, you just turned {$currentAge}.";
} elseif ($daysUntilNextBirthday <= 395) {
    $newAge = $currentAge + 1;
    $tag = "On {$birthDateNextYear->format('l, F dS, Y')}, you'll be {$newAge}.";
} else {
    $tag = 'Error';
}

// -------------------------------------------------------------------------------
// START PAGE
// -------------------------------------------------------------------------------
$transferpage = $system->startpostpage();
$additionalstyles = '
<style>
/* Tour Builder V2 Styles */
.tour-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.tour-card .card-content {
    max-height: 1000px;
    transition: max-height 0.5s ease-in-out, opacity 0.3s ease-in-out, transform 0.3s ease;
    overflow: hidden;
    transform-origin: top;
}

.tour-card.collapsed .card-content {
    max-height: 0;
    opacity: 0;
    transform: scaleY(0);
}

/* Hide legend when collapsed */
.tour-card.collapsed .calendar-legend {
    display: none;
}

/* Keep selected date visible in header */
.step-header .selected-date-info {
    font-size: 0.9rem;
    margin-left: 0.5rem;
    font-weight: 400;
    display: inline-block;
}

.step-header {
    background: #28a745;
    color: white;
    padding: 1rem 1.5rem;
    margin: -2rem -2rem 1.5rem -2rem;
    border-radius: 12px 12px 0 0;
    text-align: center;
    cursor: pointer;
    position: relative;
    transition: all 0.3s ease;
}

.step-header.collapsible:hover {
    background: #218838;
}

.step-header .collapse-icon {
    position: absolute;
    right: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    transition: transform 0.3s ease;
    font-size: 1.2rem;
}

.step-header.collapsed .collapse-icon {
    transform: translateY(-50%) rotate(180deg);
}

.step-number {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    line-height: 32px;
    text-align: center;
    font-weight: bold;
    margin-right: 0.5rem;
    font-size: 1rem;
}

.step-title {
    display: inline;
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

/* Calendar Widget */
.calendar-widget {
    max-width: 500px;
    margin: 0 auto;
}

.calendar-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.cal-nav-btn {
    color: #28a745;
    font-size: 1.5rem;
    text-decoration: none;
    padding: 0.5rem;
    transition: all 0.2s ease;
}

.cal-nav-btn:hover {
    color: #218838;
    transform: scale(1.2);
}

.calendar-month-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.25rem;
}

.calendar-weekday {
    text-align: center;
    font-weight: 600;
    font-size: 0.875rem;
    color: #6c757d;
    padding: 0.75rem 0.5rem;
}

.calendar-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    color: #212529;
    transition: all 0.2s ease;
    position: relative;
    min-height: 45px;
}

.calendar-day.empty {
    cursor: default;
}

.calendar-day.available {
    background: white;
    border: 2px solid #dee2e6;
}

.calendar-day.available:hover {
    background: #e7f3e9;
    border-color: #28a745;
    transform: scale(1.05);
}

.calendar-day.selected {
    background: #28a745;
    color: white;
    border: 2px solid #28a745;
    font-weight: bold;
    animation: pulse 0.5s ease;
}

/* Keep selected date highlighted even when calendar is collapsed */
.calendar-day.selected:after {
    content: "✓";
    position: absolute;
    top: 2px;
    right: 2px;
    font-size: 0.75rem;
}

.calendar-day.booked {
    background: #dc3545;
    color: white;
    border: 2px solid #dc3545;
    cursor: not-allowed;
}

.calendar-day.birthday {
    background: #ffc107;
    color: #000;
    border: 2px solid #ffc107;
    font-weight: bold;
}

.calendar-day.birthday::after {
    content: "🎂";
    position: absolute;
    top: 2px;
    right: 2px;
    font-size: 0.75rem;
}

.calendar-day.disabled {
    background: #f8f9fa;
    color: #adb5bd;
    border: 2px solid #e9ecef;
    cursor: not-allowed;
}

.calendar-day.disabled:hover {
    transform: none;
}

/* Calendar legend */
.calendar-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 1rem;
    font-size: 0.75rem;
    justify-content: center;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    border: 1px solid #dee2e6;
}

.legend-color.available {
    background: white;
}

.legend-color.selected {
    background: #28a745;
}

.legend-color.booked {
    background: #dc3545;
}

.legend-color.birthday {
    background: #ffc107;
}

/* Date info */
.date-info {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
    text-align: center;
}

.date-info .birthday {
    color: #28a745;
    font-weight: 600;
}

/* Company list */
.company-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    margin-bottom: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.company-item:hover {
    background: #e9ecef;
    transform: translateY(-4px);
}

.company-item.selected {
    background: #d4edda;
    border-color: #28a745;
}

.company-checkbox {
    margin-right: 1rem;
}

.company-checkbox label {
    cursor: pointer;
    font-size: 1.5rem;
    margin: 0;
}

.company-info {
    flex: 1;
}

.company-logo {
    width: 48px;
    height: 48px;
    object-fit: contain;
    margin-right: 1rem;
    border-radius: 8px;
    background: #fff;
    padding: 4px;
}

.company-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.company-description {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0;
}

.company-apps {
    display: flex;
    gap: 0.5rem;
}

/* Sidebar */
.info-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.info-card h4 {
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

.quota-progress {
    background: #e9ecef;
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.quota-progress-bar {
    background: #28a745;
    height: 100%;
    transition: width 0.3s ease;
}

.selected-list {
    max-height: 200px;
    overflow-y: auto;
}

.selected-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
    font-size: 0.875rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.selected-item .remove-btn {
    color: #dc3545;
    cursor: pointer;
    font-size: 1rem;
    padding: 0 0.25rem;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.selected-item .remove-btn:hover {
    opacity: 1;
}

.selected-item:last-child {
    border-bottom: none;
}

/* Save button */
.save-btn {
    width: 100%;
    padding: 1rem;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.save-btn:hover:not(:disabled) {
    background: #218838;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.save-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

/* Inactive section */
.inactive-section {
    opacity: 0.5;
    pointer-events: none;
}

/* Select all container */
.select-all-container {
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.select-all-container label {
    cursor: pointer;
}

.select-all-container label:hover i {
    color: #28a745 !important;
}

.qrcode {
    display: none;
}

/* Mobile responsive */
@media (max-width: 576px) {
    .calendar-widget {
        max-width: 100%;
    }
    
    .calendar-day {
        font-size: 0.9rem;
        min-height: 35px;
    }
}
</style>
';

$bodycontentclass = '';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');

echo '
<div class="container" style="margin-top: 70px;">
    <div class="row">
        <div class="col-12">
            <h1 class="fw-bold mb-2">Build Your Birthday Tour</h1>
            <p class="text-muted mb-5">' . $tag . '</p>
        </div>
    </div>
    
    <form name="myTourForm" id="myTourForm" action="/myaccount/tour-build-v2" method="POST">
    ' . $display->inputcsrf_token() . '
    <input type="hidden" name="calendar_date" id="calendar_date" value="' . $selectedDate . '">
    
    <!-- Main Content Row -->
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8 mb-4">
            
            <!-- Step 1: Date Selection -->
            <div class="tour-card" id="dateCard">
                <div class="step-header collapsible" id="dateStepHeader">
                    <span class="step-number">1</span>
                    <span class="step-title" id="dateStepTitle">Select a date for your tour</span>
                    <span class="selected-date-info" id="selectedDateInfo"></span>
                    <i class="bi bi-chevron-down collapse-icon"></i>
                    

    <div class="calendar-legend" id="calendarLegend">
                    <div class="legend-item">
                        <div class="legend-color available"></div>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color selected"></div>
                        <span>Selected</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color booked"></div>
                        <span>Already Booked</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color birthday"></div>
                        <span>Your Birthday</span>
                    </div>
                </div>

                </div>
                
                <div class="card-content">
                ' . buildCalendarMonth($calYear, $calMonth, $tourlistdates, $birthdates['recent'], $selectedDate, $icalendar_start_date, $icalendar_end_date) . '
                </div>
            
            </div>
            
            <!-- Step 2: Company Selection -->
            <div class="tour-card ' . (empty($selectedDate) ? 'inactive-section' : '') . '">
                <div class="step-header">
                    <span class="step-number">2</span>
                    <span class="step-title">Select the ' . $website['biznames'] . ' you want to visit</span>
                </div>
                ';

if (!empty($selectedDate)) {
    if ($showbusinesses && !empty($companies)) {
        echo '
                <div class="select-all-container mb-3">
                    <div class="d-flex align-items-center">
                        <input class="form-check-input d-none" type="checkbox" id="selectAllCheckbox">
                        <label class="form-check-label h3 mb-0 me-3" for="selectAllCheckbox">
                            <i class="bi bi-square text-muted" id="selectAllIcon"></i>
                        </label>
                        <span class="fw-bold">All ' . ucfirst($website['biznames']) . '</span>
                    </div>
                </div>
                
                <div class="py-2" style="max-height: 600px; overflow-y: auto; overflow-x: hidden;">';
        
        foreach ($companies as $company) {
            $iconHTML = $company['isChecked']
                ? '<i class="bi bi-check-square-fill text-success"></i>'
                : '<i class="bi bi-square text-muted"></i>';
            
            echo '
                    <div class="company-item ' . ($company['isChecked'] ? 'selected' : '') . '" data-company-id="' . $company['company_id'] . '">
                        <div class="company-checkbox">
                            <input class="form-check-input addcompany d-none" type="checkbox" 
                                   name="addcompany' . $company['company_id'] . '" 
                                   value="' . $company['company_id'] . '"  
                                   id="addcompany' . $company['company_id'] . '" 
                                   data-company="' . htmlspecialchars($company['company_name']) . '"' . 
                                   ($company['isChecked'] ? ' checked' : '') . '>
                            <label class="form-check-label" for="addcompany' . $company['company_id'] . '">
                                ' . $iconHTML . '
                            </label>
                        </div>
                                        <img src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" 
                             class="company-logo" 
                             alt="' . htmlspecialchars($company['company_name']) . '">
                        <div class="company-info">
                            <div class="company-name">' . htmlspecialchars($company['company_name']) . '</div>
                            <p class="company-description">' . htmlspecialchars($company['short_description'] ?? $company['description']) . '</p>
                        </div>
                        <div class="company-apps">
                            <span class="app-links" style="display: none;">' . $company['appicon'] . '</span>
                            <span class="qr-codes" style="display: none;">' . $company['qrcode'] . '</span>
                        </div>
                    </div>';
        }
        
        echo '
                </div>';
    } else {
        echo '
                <div class="text-center py-5">
                    <i class="bi bi-inbox h1 text-muted"></i>
                    <p>No enrolled ' . $website['biznames'] . ' found to create tour.</p>
                </div>';
    }
} else {
    echo '
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Please select a date first to see available ' . $website['biznames'] . '.
                </div>';
}

echo '
            </div>
            
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Tour Info -->
            <div class="info-card">
                <h4>Tour Information</h4>
                <div class="mb-3">
                    <strong>Selected Date:</strong><br>
                    <span id="selectedDateDisplay">' . 
                    (!empty($selectedDate) ? date('l, F j, Y', strtotime($selectedDate)) : 'None selected') . 
                    '</span>
                </div>
                <div>
                    <strong>Tour Quota:</strong>
                    <div class="quota-progress">
                        <div class="quota-progress-bar" style="width: ' . 
                        (count($tourlistdates) / $plandetails['celebration_max_tour_count'] * 100) . '%"></div>
                    </div>
                    <div class="text-muted small">
                        ' . count($tourlistdates) . ' of ' . $plandetails['celebration_max_tour_count'] . ' tours used
                    </div>
                </div>
            </div>
            
            <!-- Selected Companies -->
            <div class="info-card" id="selectedCard" style="' . (empty($selectedDate) ? 'display: none;' : '') . '">
                <h4>Selected ' . ucfirst($website['biznames']) . ' (<span id="companyCount">0</span>)</h4>
                <div class="selected-list" id="selectedList"></div>
            </div>
            
            <!-- Save Button -->
            <button type="submit" class="save-btn mb-5" id="saveTourBtn" disabled>
                <i class="bi bi-check-circle me-2"></i>Save Tour
            </button>
        </div>
    </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const selectedCompanies = [];
    const saveTourBtn = document.getElementById("saveTourBtn");
    const companyCount = document.getElementById("companyCount");
    const selectedList = document.getElementById("selectedList");
    const selectedCard = document.getElementById("selectedCard");
    let hasUnsavedChanges = false;
    const originalSelections = [];
    
    // Calendar collapse functionality
    const dateCard = document.getElementById("dateCard");
    const dateStepHeader = document.getElementById("dateStepHeader");
    const dateStepTitle = document.getElementById("dateStepTitle");
    const selectedDateInfo = document.getElementById("selectedDateInfo");
    const selectedDate = document.getElementById("calendar_date").value;
    
    function updateDateDisplay() {
        const dateRangeText = \'Dates available: ' . $birthdates['planstart_shortformatted'] . ' to ' . $birthdates['planend_shortformatted'] . '\';
        
        if (dateCard.classList.contains("collapsed")) {
            // When collapsed
            if (selectedDate) {
                const dateObj = new Date(selectedDate);
                const options = { weekday: \'long\', year: \'numeric\', month: \'long\', day: \'numeric\' };
                dateStepTitle.textContent = \'Your Tour Date\';
                selectedDateInfo.innerHTML = \': <strong>\' + dateObj.toLocaleDateString(\'en-US\', options) + \'</strong>\';
            }
        } else {
            // When open
            if (selectedDate) {
                const dateObj = new Date(selectedDate);
                const options = { weekday: \'long\', year: \'numeric\', month: \'long\', day: \'numeric\' };
                dateStepTitle.textContent = \'Change Your Tour Date\';
                selectedDateInfo.innerHTML = \': <strong>\' + dateObj.toLocaleDateString(\'en-US\', options) + \'</strong>\';
            } else {
                dateStepTitle.textContent = \'Select a date for your tour\';
                selectedDateInfo.innerHTML = \': \' + dateRangeText;
            }
        }
    }
    
    // Auto-collapse calendar if date is already selected
    if (selectedDate) {
        // Use setTimeout to ensure smooth animation on page load
        setTimeout(function() {
            dateCard.classList.add("collapsed");
            dateStepHeader.classList.add("collapsed");
            document.getElementById("calendarLegend").style.display = "none";
            updateDateDisplay();
        }, 100);
    }
    
    // Toggle calendar on header click with smooth animation
    dateStepHeader.addEventListener("click", function() {
        // Toggle the collapsed state
        dateCard.classList.toggle("collapsed");
        dateStepHeader.classList.toggle("collapsed");
        
        // Move legend in/out of step header based on state
        const legend = document.getElementById("calendarLegend");
        if (dateCard.classList.contains("collapsed")) {
            // Hide legend when collapsed
            legend.style.display = "none";
        } else {
            // Show legend when expanded
            setTimeout(function() {
                legend.style.display = "flex";
            }, 300);
        }
        
        // Update the date display after animation starts
        setTimeout(updateDateDisplay, 100);
    });
    
    // Update display when page loads if no date selected
    if (!selectedDate) {
        updateDateDisplay();
    }
    
    const checkboxes = document.querySelectorAll(".addcompany");
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener("change", handleCompanyToggle);
        
        if (checkbox.checked) {
            const companyName = checkbox.getAttribute("data-company");
            const companyId = checkbox.value;
            selectedCompanies.push({ id: companyId, name: companyName });
            originalSelections.push(companyId);
        }
    });
    
    const companyItems = document.querySelectorAll(".company-item");
    companyItems.forEach(item => {
        item.addEventListener("click", function(e) {
            if (!e.target.closest(".company-apps")) {
                const checkbox = this.querySelector(".addcompany");
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event("change"));
            }
        });
    });
    
    function handleCompanyToggle(event) {
        const checkbox = event.target;
        const companyId = checkbox.value;
        const companyName = checkbox.getAttribute("data-company");
        const companyItem = checkbox.closest(".company-item");
        const label = checkbox.nextElementSibling;
        
        // Track changes
        const wasOriginallySelected = originalSelections.includes(companyId);
        const isNowSelected = checkbox.checked;
        if ((wasOriginallySelected && !isNowSelected) || (!wasOriginallySelected && isNowSelected)) {
            hasUnsavedChanges = true;
        }
        
        if (checkbox.checked) {
            if (!selectedCompanies.find(c => c.id === companyId)) {
                selectedCompanies.push({ id: companyId, name: companyName });
            }
            companyItem.classList.add("selected");
            label.innerHTML = \'<i class="bi bi-check-square-fill text-success"></i>\';
            
            if (!document.querySelector(`input[name="selectedCompanies[]"][value="${companyId}"]`)) {
                const hiddenInput = document.createElement("input");
                hiddenInput.type = "hidden";
                hiddenInput.name = "selectedCompanies[]";
                hiddenInput.value = companyId;
                document.getElementById("myTourForm").appendChild(hiddenInput);
            }
        } else {
            const index = selectedCompanies.findIndex(c => c.id === companyId);
            if (index > -1) {
                selectedCompanies.splice(index, 1);
            }
            companyItem.classList.remove("selected");
            label.innerHTML = \'<i class="bi bi-square text-muted"></i>\';
            
            const hiddenInput = document.querySelector(`input[name="selectedCompanies[]"][value="${companyId}"]`);
            if (hiddenInput) {
                hiddenInput.remove();
            }
        }
        
        updateSelectedDisplay();
    }
    
    function updateSelectedDisplay() {
        companyCount.textContent = selectedCompanies.length;
        saveTourBtn.disabled = selectedCompanies.length === 0;
        
        selectedList.innerHTML = "";
        selectedCompanies.forEach(company => {
            const item = document.createElement("div");
            item.className = "selected-item";
            
            const nameSpan = document.createElement("span");
            nameSpan.textContent = company.name;
            
            const removeBtn = document.createElement("span");
            removeBtn.className = "remove-btn";
            removeBtn.innerHTML = \'<i class="bi bi-x-circle-fill"></i>\';
            removeBtn.onclick = function() {
                // Find and uncheck the checkbox
                const checkbox = document.querySelector(`input[value="${company.id}"]`);
                if (checkbox) {
                    checkbox.checked = false;
                    checkbox.dispatchEvent(new Event("change"));
                }
            };
            
            item.appendChild(nameSpan);
            item.appendChild(removeBtn);
            selectedList.appendChild(item);
        });
        
        if (selectedCompanies.length > 0) {
            selectedCard.style.display = "block";
        }
    }
    
    // Select All functionality
    const selectAllCheckbox = document.getElementById("selectAllCheckbox");
    if (selectAllCheckbox) {
        // Set initial state based on whether all are checked
        function updateSelectAllState() {
            const allCheckboxes = document.querySelectorAll(".addcompany");
            const checkedCheckboxes = document.querySelectorAll(".addcompany:checked");
            const selectAllIcon = document.getElementById("selectAllIcon");
            
            if (allCheckboxes.length === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.disabled = true;
                selectAllIcon.className = "bi bi-square text-muted";
            } else {
                selectAllCheckbox.disabled = false;
                if (allCheckboxes.length === checkedCheckboxes.length && checkedCheckboxes.length > 0) {
                    selectAllCheckbox.checked = true;
                    selectAllIcon.className = "bi bi-check-square-fill text-success";
                } else if (checkedCheckboxes.length > 0) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = true;
                    selectAllIcon.className = "bi bi-dash-square-fill text-primary";
                } else {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                    selectAllIcon.className = "bi bi-square text-muted";
                }
            }
        }
        
        // Handle select all toggle
        selectAllCheckbox.addEventListener("change", function() {
            const isChecked = this.checked;
            const allCheckboxes = document.querySelectorAll(".addcompany");
            const selectAllIcon = document.getElementById("selectAllIcon");
            
            // Update icon
            if (isChecked) {
                selectAllIcon.className = "bi bi-check-square-fill text-success";
            } else {
                selectAllIcon.className = "bi bi-square text-muted";
            }
            
            allCheckboxes.forEach(checkbox => {
                if (checkbox.checked !== isChecked) {
                    checkbox.checked = isChecked;
                    checkbox.dispatchEvent(new Event("change"));
                }
            });
        });
        
        // Update select all state when individual checkboxes change
        document.addEventListener("change", function(e) {
            if (e.target.classList.contains("addcompany")) {
                updateSelectAllState();
            }
        });
        
        // Initial state
        updateSelectAllState();
    }
    
    updateSelectedDisplay();
});
</script>
';

if (!empty($transferpage['message'])) {
    echo $display->formaterrormessage($transferpage['message']);
}

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();