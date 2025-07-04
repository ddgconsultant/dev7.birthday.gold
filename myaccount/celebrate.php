<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$accountstats = $account->account_getstats();
$plandatafeatures = $app->plandetail('details_id', $current_user_data['account_product_id']);
$userplan = $current_user_data['account_plan'];
$user_id = $current_user_data['user_id'];
$userbirthdate = $current_user_data['birthdate'];
$birthdates = $account->getBirthdates($userbirthdate, $plandatafeatures);

$selectsused = ($accountstats['business_pending'] + $accountstats['business_selected'] + $accountstats['business_success']);
$selectsleft = ($plandatafeatures['max_business_select'] - ($selectsused) + $accountstats['business_removed']);

$addresslongtag = $display->formataddress();
$errormessage = '';
$selectist = $session->get('goldmine_selectionList', '');

// Initialize birthdate for calendar calculations
$birthdate = new DateTime($userbirthdate);
$currentYear = (new DateTime())->format('Y');
$birthdate->setDate($currentYear, $birthdate->format('m'), $birthdate->format('d'));

// Get enrollments data
$enrollments = $account->getEnrollments($current_user_data['user_id'], 'active');

// Get tour dates and other data needed for hero section
$daysouttag = $plandatafeatures['celebration_tour_option_tag'];
$daysout = $plandatafeatures['celebration_planning_days'];
$nextDate = $app->calculateNextOccurrence($userbirthdate, $daysout);

// Get tour dates for calendar
$icalendar_start_date = clone $birthdate;
$icalendar_start_date->modify('-' . $plandatafeatures['celebration_tour_days_before'] . ' days');

$icalendar_end_date = clone $birthdate;
$icalendar_end_date->modify('+' . $plandatafeatures['celebration_tour_days_after'] . ' days');

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

if ($selectist != '') {
    $count = count($selectist);
    $errormessage = '<div class="alert alert-info">Your selection has been successfully recorded. 
You will receive an automated email to let you know when our system starts to process your ' . $qik->plural('enrollment', $count) . '</div>';
    $session->unset('goldmine_selectionList');
}
$transferpage['message'] = $errormessage;

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$transferpage = $system->startpostpage();
$bodycontentclass = '';

// Modified page setup - no profile header for clean modern look
$pagetitle = 'Celebration Tours';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Reuse login page styles
$additionalstyles .= '
<style>
/* Reuse login page styles for consistency */
.login-wrapper {
    width: 100%;
    max-width: 1200px;
    display: grid;
    grid-template-columns: 1fr 500px;
    gap: 4rem;
    align-items: center;
    padding: 0 2rem;
    margin: 0 auto;
}

.welcome-content {
    color: #212529;
}

.welcome-content h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    line-height: 1.2;
}

.welcome-content h2 span {
    color: var(--bs-primary);
}

.welcome-content p {
    font-size: 1.25rem;
    color: #6c757d;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.feature-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.feature-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    background: var(--bs-secondary);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bs-primary);
    font-size: 1.25rem;
}

.feature-text h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.25rem;
}

.feature-text p {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0;
    line-height: 1.4;
}

.login-container {
    max-width: 480px;
    margin: 0;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.login-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.login-header {
    text-align: center;
    padding: 3rem 2rem 1.5rem;
}

.login-header h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.5rem;
}

.login-header p {
    font-size: 1rem;
    color: #6c757d;
    margin: 0;
}

.login-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #e8f5e8;
    color: var(--bs-primary);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.login-badge i {
    font-size: 1rem;
}

.login-body {
    padding: 0 2rem 3rem;
}

.btn-login {
    width: 100%;
    padding: 0.875rem 1.5rem;
    font-size: 1rem;
    font-weight: 600;
    background: var(--bs-primary);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.btn-login:hover:not(:disabled) {
    background: #0b5ed7;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
}

.btn-login:disabled {
    background: #6c757d;
    cursor: not-allowed;
    opacity: 0.65;
}

/* Additional styles for tour buttons */
.tour-button {
    margin: 0.25rem;
    min-width: 200px;
}

/* Flash animation for selection card */
@keyframes flash {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.flash {
    animation: flash 1s ease-in-out;
}

/* Mobile Styles */
@media (max-width: 991px) {
    .login-wrapper {
        display: block;
        padding: 1rem;
    }
    
    .welcome-content {
        margin-bottom: 2rem;
        text-align: center;
    }
    
    .welcome-content h2 {
        font-size: 2rem;
    }
    
    .welcome-content p {
        font-size: 1.1rem;
    }
    
    .feature-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .feature-item {
        justify-content: center;
    }
    
    .login-container {
        margin: 2rem auto;
    }
}
</style>';

#-------------------------------------------------------------------------------
# DISPLAY PAGE CONTENT
#-------------------------------------------------------------------------------
?>

<div class="main-content">
    <!-- Desktop wrapper for side-by-side layout -->
    <div class="login-wrapper">
        <!-- Welcome content - All devices -->
        <div class="welcome-content">
            <?php if (!$birthdates['birthday_in_plan']) { ?>
                <h2>Your Birthday is <span>Coming Soon</span></h2>
                <p><?php echo $daysouttag; ?></p>
                
                <div class="alert alert-warning">
                    <i class="bi bi-calendar-event me-2"></i>
                    <?php if ($birthdates['recent'] == $birthdates['next']) { ?>
                        Your birthday: <strong><?php echo $birthdates['recent_longformatted']; ?></strong>
                    <?php } else { ?>
                        Your next birthday: <strong><?php echo $birthdates['recent_longformatted']; ?></strong>
                    <?php } ?>
                </div>
                
                <p class="text-success">
                    <i class="bi bi-clock-history me-2"></i>
                    Check back on <strong><?php echo $nextDate['long_date']; ?></strong> to start planning your celebration tours!
                </p>
            <?php } else { ?>
                <h2><i class="bi bi-balloon-fill text-primary"></i> It's <span>Celebration</span> Time!</h2>
                <p><?php echo $daysouttag; ?></p>
                
                <div class="alert alert-success">
                    <i class="bi bi-calendar-check me-2"></i>
                    <?php if ($birthdates['recent'] == $birthdates['next']) { ?>
                        Your birthday is on: <strong><?php echo $birthdates['recent_longformatted']; ?></strong>
                    <?php } else { ?>
                        Your next birthday is: <strong><?php echo $birthdates['recent_longformatted']; ?></strong>
                    <?php } ?>
                </div>
                
                <!-- Learn More button for mobile -->
                <div class="d-lg-none text-center mb-3">
                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#featuresModal">
                        <i class="bi bi-info-circle me-2"></i>Learn More
                    </button>
                </div>
                
                <!-- Features grid - hidden on mobile by default, always visible on desktop -->
                <div class="feature-grid d-none d-lg-grid">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="feature-text">
                            <h3>Smart Planning</h3>
                            <p>Organize your celebration tours efficiently</p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div class="feature-text">
                            <h3>Route Optimization</h3>
                            <p>Get the best routes for your tours</p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-gift"></i>
                        </div>
                        <div class="feature-text">
                            <h3>Track Rewards</h3>
                            <p>Monitor all your birthday rewards</p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-bell"></i>
                        </div>
                        <div class="feature-text">
                            <h3>Reminders</h3>
                            <p>Never miss a celebration opportunity</p>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
        
        <!-- Main Content Card -->
        <div class="login-container mb-md-5">
            <?php echo $display->formaterrormessage($transferpage['message']); ?>
            
            <div class="login-card" <?php if ($selectsleft > 0) echo 'id="selectionCard"'; ?>>
                <!-- Header Section -->
                <div class="login-header">
                    <div class="login-badge">
                        <i class="bi bi-balloon-fill"></i>
                        <span>Celebration Tours</span>
                    </div>
                    <h1>Tour Management</h1>
                    <p>Build and manage your birthday celebration tours</p>
                </div>
                
                <!-- Body Section -->
                <div class="login-body">
                    <?php
                    ### DETERMINE BUILD AVAILABILITY
                    $buildable = false;
                    $tag = '';
                    $profilecompletion = $account->profilecompletionratio($current_user_data);
                    
                    if ($birthdates['birthday_in_plan']) $buildable = true;
                    if (empty($profilecompletion['required_percentage'])) $buildable = false;
                    if (!empty($profilecompletion['required_percentage']) && $profilecompletion['required_percentage'] < 100) {
                        $buildable = false;
                        $tag = '<p class="mt-3"><a href="/myaccount/profile"><small><i class="bi bi-exclamation-triangle-fill text-danger"></i> You need to complete your profile.</small></a></p>';
                    }
                    
                    if ($buildable) {
                        echo '<a class="btn btn-login mb-3" href="/myaccount/tour-build">Build A Celebration Tour</a>';
                    } else {
                        echo '<button class="btn btn-login mb-3" disabled>Build A Tour (unavailable)</button>' . $tag;
                    }
                    ?>
                    
                    <div class="row">
                        <div class="col-6">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Enrollments</span>
                        <span class="badge rounded-pill bg-secondary"><?php echo $enrollments['count'] ?? 0; ?></span>
                    </div>
                    </div><div class="col-6">   
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Tours</span>
                        <span class="badge rounded-pill bg-secondary"><?php echo count($tourlistdates ?? []); ?> of <?php echo $plandatafeatures['celebration_max_tour_count']; ?></span>
                    </div>
                    </div></div>
                   
                </div>
         <hr>
            
            <!-- Your Tours Card -->
            <?php if ($birthdates['birthday_in_plan'] && count($tourlistdates) > 0) { ?>
        
                    <div class="login-header">
                       
                        <h1>Upcoming Scheduled Tours</h1>
                        <p class="text-muted mb-3">You have <?php echo count($tourlistdates); ?> tour<?php echo count($tourlistdates) > 1 ? 's' : ''; ?> scheduled</p>
                        </div>
                    <div class="login-body">
                        <div class="d-flex flex-column align-items-center">
                            <?php foreach ($tourlistdates as $tourDate) { 
                                $tourDateTime = new DateTime($tourDate);
                                $displayDate = $tourDateTime->format('l, F j');
                            ?>
                                <a href="/myaccount/tour?date=<?php echo $tourDate; ?>" 
                                   class="btn btn-primary tour-button">
                                    <i class="bi bi-calendar-check-fill me-2"></i>
                                    <?php echo $displayDate; ?>
                                </a>
                            <?php } ?>
                        </div>
                        
                    
                        <div class="text-end mt-3">
                        <a class="icon-link icon-link-hover" href="/myaccount/tour-list">
                            View Tour List <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
            <?php } ?>
            
            <?php
            // Check if any required fields are missing
            $requiredFields = array(
                'mailing_address' => 'Address',
                'city' => 'City',
                'state' => 'State',
                'zip_code' => 'Zip'
            );
            $missingFields = array();
            
            foreach ($requiredFields as $field => $label) {
                if (empty($current_user_data[$field])) {
                    $missingFields[] = $label;
                }
            }
            
            // If there are missing fields, display the alert message
            if (!empty($missingFields)) {
                echo '<div class="alert alert-danger mt-4" role="alert">';
                echo 'The "Celebration Tour" feature requires your account details to be provided:';
                echo '<ul>';
                foreach ($missingFields as $field) {
                    echo '<li>' . $field . '</li>';
                }
                echo '</ul>';
                echo '<div class="d-flex justify-content-end m-2">';
                echo '<a href="/myaccount/account" class="btn btn-dark">Complete Account Details</a>';
                echo '</div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>

<!-- Features Modal for Mobile -->
<div class="modal fade" id="featuresModal" tabindex="-1" aria-labelledby="featuresModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="featuresModalLabel">
                    <i class="bi bi-balloon-fill text-primary me-2"></i>Celebration Tour Features
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 col-sm-6 mb-3">
                        <div class="d-flex align-items-start">
                            <div class="feature-icon me-3">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div class="feature-text">
                                <h6 class="fw-bold mb-1">Smart Planning</h6>
                                <p class="mb-0 small">Organize your celebration tours efficiently</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-sm-6 mb-3">
                        <div class="d-flex align-items-start">
                            <div class="feature-icon me-3">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                            <div class="feature-text">
                                <h6 class="fw-bold mb-1">Route Optimization</h6>
                                <p class="mb-0 small">Get the best routes for your tours</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-sm-6 mb-3">
                        <div class="d-flex align-items-start">
                            <div class="feature-icon me-3">
                                <i class="bi bi-gift"></i>
                            </div>
                            <div class="feature-text">
                                <h6 class="fw-bold mb-1">Track Rewards</h6>
                                <p class="mb-0 small">Monitor all your birthday rewards</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-sm-6">
                        <div class="d-flex align-items-start">
                            <div class="feature-icon me-3">
                                <i class="bi bi-bell"></i>
                            </div>
                            <div class="feature-text">
                                <h6 class="fw-bold mb-1">Reminders</h6>
                                <p class="mb-0 small">Never miss a celebration opportunity</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
if ($selectsleft > 0) {
    $footerattribute['postfooter'] = '
<script>
// Function to apply the flash effect
function applyFlashEffect() {
    const selectionCard = document.getElementById("selectionCard");
    if (selectionCard) {
        selectionCard.classList.add("flash");
        setTimeout(() => {
            selectionCard.classList.remove("flash");
        }, 1000);
    }
}

// Call the function to apply the flash effect
applyFlashEffect();
</script>
';
}

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();