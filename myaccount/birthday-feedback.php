<?php
include '../core/site-controller.php';
// Birthday.Gold Feedback Form
// This allows members to provide feedback about their birthday rewards experience

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------

// Get user data from global variable
$user_id = $current_user_data['user_id'];
$username = $current_user_data['username'];
$first_name = $current_user_data['first_name'];
$last_name = $current_user_data['last_name'];
$email = $current_user_data['email'];
$birthdate = $current_user_data['birthdate'];

// Format birthdate for display
$birthdate_obj = new DateTime($birthdate);
$formatted_birthdate = $birthdate_obj->format('F jS');
$age = date('Y') - $birthdate_obj->format('Y');
$age_suffix = $qik->getOrdinalSuffix($age);

// Get enrolled businesses count for this user
$sql = "SELECT COUNT(*) AS enrolled_count 
        FROM bg_user_enrollments 
        WHERE user_id = :user_id 
        AND status IN ('success', 'success-btn')";
$enrollment_data = $database->getrow($sql, ['user_id' => $user_id]);
$enrolled_businesses_count = $enrollment_data['enrolled_count'];

// Get enrolled businesses for this user
$sql = "SELECT e.user_company_id, c.company_id, c.company_name, c.company_display_name, 
               m.file_location AS logo
        FROM bg_user_enrollments e
        JOIN bg_companies c ON e.company_id = c.company_id
        LEFT JOIN bg_media m ON c.company_id = m.company_id AND m.type = 'logo' AND m.status = 'active'
        WHERE e.user_id = :user_id 
        AND e.status IN ('success', 'success-btn')
        ORDER BY c.company_display_name";
$enrolled_businesses = $database->getrows($sql, ['user_id' => $user_id]);

// Form submission data
$feedback_submitted = false;
$form_errors = [];
$current_page = 1;
$max_pages = 3;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $feedback_submitted = processFormSubmission($database, $user_id, $system);
}

#-------------------------------------------------------------------------------
# HANDLE FORM SUBMISSION
#-------------------------------------------------------------------------------
function processFormSubmission($database, $user_id, $system) {
    try {
        // Start transaction
        $database->query("START TRANSACTION");
        
        // Get form data
        $overall_rating = isset($_POST['overall_rating']) ? intval($_POST['overall_rating']) : 0;
        $rewards_received = isset($_POST['rewards_received']) ? $_POST['rewards_received'] : '';
        $businesses_received = isset($_POST['businesses_received']) ? $_POST['businesses_received'] : [];
        $value_rating = isset($_POST['value_rating']) ? intval($_POST['value_rating']) : 0;
        $ease_rating = isset($_POST['ease_rating']) ? intval($_POST['ease_rating']) : 0;
        $timeliness_rating = isset($_POST['timeliness_rating']) ? intval($_POST['timeliness_rating']) : 0;
        $best_business = isset($_POST['best_business']) ? intval($_POST['best_business']) : 0;
        $best_reward_feedback = isset($_POST['best_reward_feedback']) ? $_POST['best_reward_feedback'] : '';
        $improvement_feedback = isset($_POST['improvement_feedback']) ? $_POST['improvement_feedback'] : '';
        $nps_rating = isset($_POST['nps_rating']) ? intval($_POST['nps_rating']) : 0;
        
        // Prepare JSON data for businesses_received
        $businesses_json = json_encode($businesses_received);
        
        // Insert the feedback into the database
        $sql = "INSERT INTO bg_user_feedback (
                    user_id, overall_rating, rewards_received, businesses_received, 
                    value_rating, ease_rating, timeliness_rating, best_business, 
                    best_reward_feedback, improvement_feedback, nps_rating, 
                    create_dt, status
                ) VALUES (
                    :user_id, :overall_rating, :rewards_received, :businesses_received,
                    :value_rating, :ease_rating, :timeliness_rating, :best_business, 
                    :best_reward_feedback, :improvement_feedback, :nps_rating,
                    NOW(), 'active'
                )";
                
        $database->query($sql, [
            'user_id' => $user_id,
            'overall_rating' => $overall_rating,
            'rewards_received' => $rewards_received,
            'businesses_received' => $businesses_json,
            'value_rating' => $value_rating,
            'ease_rating' => $ease_rating,
            'timeliness_rating' => $timeliness_rating,
            'best_business' => $best_business,
            'best_reward_feedback' => $best_reward_feedback,
            'improvement_feedback' => $improvement_feedback,
            'nps_rating' => $nps_rating
        ]);
        
        // Give the user an extra enrollment slot as a reward
        // First, get the current attribute value
        $sql = "SELECT attribute_id, value FROM bg_user_attributes 
                WHERE user_id = :user_id AND name = 'enrollment_limit' AND status = 'active'";
        $attribute = $database->getrow($sql, ['user_id' => $user_id]);
        
        if ($attribute) {
            // Update existing attribute
            $new_limit = $attribute['value'] + 1;
            
            $sql = "UPDATE bg_user_attributes 
                    SET value = :new_limit, modify_dt = NOW() 
                    WHERE attribute_id = :attribute_id";
            $database->query($sql, [
                'new_limit' => $new_limit,
                'attribute_id' => $attribute['attribute_id']
            ]);
        } else {
            // Create new attribute with default + 1
            $default_limit = 10; // Default enrollment limit
            $new_limit = $default_limit + 1;
            
            $sql = "INSERT INTO bg_user_attributes 
                    (user_id, type, name, value, status, create_dt, modify_dt) 
                    VALUES (:user_id, 'system', 'enrollment_limit', :new_limit, 'active', NOW(), NOW())";
            $database->query($sql, [
                'user_id' => $user_id,
                'new_limit' => $new_limit
            ]);
        }
        
        // Add notification about extra enrollment slot
        $notification_title = "Extra Enrollment Slot Added!";
        $notification_message = "Thank you for providing feedback about your birthday rewards experience. As a token of our appreciation, we've added an extra enrollment slot to your account!";
        
        $sql = "INSERT INTO bg_user_notifications (
                    user_id, type, title, message, status, create_dt, modify_dt, 
                    alert_class, priority, category
                ) VALUES (
                    :user_id, 'system_notification', :title, :message, 'unread', 
                    NOW(), NOW(), 'success', 'normal', 'feedback_reward'
                )";
        $database->query($sql, [
            'user_id' => $user_id,
            'title' => $notification_title,
            'message' => $notification_message
        ]);
        
        // Commit transaction
        $database->query("COMMIT");
        
        // Add success message
        $system->addmessage('success', 'Thank you for your feedback! Your extra enrollment slot has been added.');
        
        // Return success
        return true;
        
    } catch (Exception $e) {
        // Roll back transaction and log error
        $database->query("ROLLBACK");
        error_log("Failed to process feedback submission: " . $e->getMessage());
        $system->addmessage('error', 'There was an error submitting your feedback. Please try again.');
        return false;
    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$pagetitle = "Birthday Feedback";

// Add the custom styles for the feedback form directly as a string
$additionalstyles .= '
<style>
.feedback-survey {
    min-height: 100vh;
    background-color: #f9f7f2;
}

.survey-container {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-top: 30px;
    margin-bottom: 30px;
    overflow: hidden;
}

.page {
    display: none;
}

.page.active {
    display: block;
}

.header-section {
    text-align: center;
    padding: 25px;
    background-color: #fff9e6;
}

h1, h2 {
    color: #d4af37;
}

h3 {
    color: #000000;
}

.cake-icon {
    font-size: 1.5rem;
}

.incentive-callout {
    border: 2px dashed #f8d568;
    border-radius: 8px;
    padding: 15px;
    background-color: white;
    max-width: 500px;
    margin: 20px auto;
}

.question-block {
    margin-bottom: 30px;
    padding: 20px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.star-rating {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin: 15px 0;
}

.star {
    color: #ddd;
    font-size: 30px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-block;
}

.star.active {
    color: #ffc107;
}

.business-card {
    height: 100%;
    transition: all 0.3s ease;
    background-color: white;
}

.business-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.form-check {
    transition: all 0.2s ease;
}

.toggle-switches .form-check:hover {
    transform: translateX(2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.rating-card {
    height: 100%;
}

.progress-container {
    text-align: center;
    margin-bottom: 20px;
}

.progress-cake {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 8px;
}

.cake-slice {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.3s;
}

.thank-you-page {
    text-align: center;
    padding: 40px 20px;
}

.success-message {
    padding: 30px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.reward-confirmation {
    padding: 20px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
    margin: 20px 0;
}

.gift-box-animation {
    font-size: 48px;
    margin-bottom: 15px;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.navigation-buttons {
    padding: 20px;
    background-color: #f8f8f8;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.btn-submit {
    font-size: 1.1rem;
    font-weight: 600;
    animation: pulse 2s infinite;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
}

.btn-submit:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4);
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}


.skip-link, 
.screen-reader-text,
a[href="#main"] { 
    display: none !important; 
}
</style>';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
echo '<div class="container my-5">';
if ($feedback_submitted) {
    // Show thank you page
    echo '
    <div class="feedback-survey">
        <div class="container" style="max-width: 800px;">
            <div class="thank-you-page">
                <div class="success-message">
                    <div class="success-icon mb-4">
                        <span class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="fas fa-check" style="font-size: 30px;"></i>
                        </span>
                    </div>
                    
                    <h2 class="mb-3">Thank You, ' . htmlspecialchars($first_name) . '!</h2>
                    <p class="text-muted mb-4">Your feedback has been submitted successfully.</p>
                    
                    <div class="reward-confirmation mb-4 py-4 border-top border-bottom">
                        <div class="gift-box-animation">
                            <i class="fas fa-gift text-warning"></i>
                        </div>
                        <h3 class="text-warning mb-3">Your extra enrollment slot has been added!</h3>
                        <p>You now have <span class="fw-bold">' . ($enrolled_businesses_count + 1) . '</span> total enrollment slots.</p>
                    </div>
                    
                    <div class="next-steps">
                        <p>What would you like to do next?</p>
                        <div class="d-flex flex-wrap justify-content-center gap-3 mt-3">
                            <a href="/myaccount/" class="btn btn-outline-secondary px-4">View My Dashboard</a>
                            <a href="/myaccount/enrollment.php" class="btn btn-warning px-4">Enroll With a New Business</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
} else {
    // Show the feedback form
    echo '
    <div class="feedback-survey">
        <div class="container" style="max-width: 800px;">
            <div class="survey-container">
                <form id="feedbackForm" method="post" action="">
                    <input type="hidden" id="current_page" name="current_page" value="' . $current_page . '">
                    
                    <!-- Page 1: Overall Experience -->
                    <div class="page page-1 active">
                        <div class="header-section">
                            <h2 class="mb-3">Hey ' . htmlspecialchars($first_name) . '! How was your birthday?</h2>
                            <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                                <span class="cake-icon">🎂</span>
                                <p class="mb-0">You celebrated your <span class="fw-bold">' . $age . $age_suffix . '</span> birthday on <span class="fw-bold">' . $formatted_birthdate . '</span>!</p>
                            </div>
                            <div class="incentive-callout">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="fas fa-gift text-warning" style="font-size: 28px;"></i>
                                    <p class="mb-0">Complete this quick feedback to get <strong>1 extra enrollment slot</strong> added to your account immediately!</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-center gap-2 mt-3 text-muted">
                                <i class="far fa-clock" style="font-size: 16px;"></i>
                                <p class="mb-0 small">This will only take about 2 minutes</p>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <div class="question-block">
                                <h3>How was your overall birthday rewards experience?</h3>
                                <div class="star-rating">
                                    <span class="star" data-rating="1">&#9734;</span>
                                    <span class="star" data-rating="2">&#9734;</span>
                                    <span class="star" data-rating="3">&#9734;</span>
                                    <span class="star" data-rating="4">&#9734;</span>
                                    <span class="star" data-rating="5">&#9734;</span>
                                </div>
                                <input type="hidden" name="overall_rating" id="overall_rating" value="0">
                            </div>
                            
                            <div class="question-block">
                                <h3>Did you receive your birthday rewards?</h3>
                                <div class="toggle-switches mt-3">
                                    <div class="form-check p-3 mb-2 rounded-3 border">
                                        <input class="form-check-input" type="radio" name="rewards_received" id="receivedAll" value="all">
                                        <label class="form-check-label d-flex align-items-center gap-2" for="receivedAll">
                                            Yes, from all businesses
                                            <i class="fas fa-check text-success"></i>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check p-3 mb-2 rounded-3 border">
                                        <input class="form-check-input" type="radio" name="rewards_received" id="receivedSome" value="some">
                                        <label class="form-check-label d-flex align-items-center gap-2" for="receivedSome">
                                            From some, not all
                                            <i class="fas fa-exclamation-triangle text-warning"></i>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check p-3 rounded-3 border">
                                        <input class="form-check-input" type="radio" name="rewards_received" id="receivedNone" value="none">
                                        <label class="form-check-label d-flex align-items-center gap-2" for="receivedNone">
                                            Did not receive any
                                            <i class="fas fa-times text-danger"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Page 2: Reward Quality -->
                    <div class="page page-2">
                        <div class="header-section">
                            <h2 class="mb-3">What did you think of your rewards?</h2>
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <i class="fas fa-gift text-warning" style="font-size: 20px;"></i>
                                <p class="mb-0">You are making progress! Just a bit more to earn your extra enrollment slot.</p>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="rating-card p-3 bg-white rounded-3 shadow-sm h-100">
                                        <h3 class="text-center">Value of Rewards</h3>
                                        <p class="text-center text-muted small">Were the rewards worth your time?</p>
                                        <div class="star-rating" data-field="value_rating">
                                            <span class="star" data-rating="1">&#9734;</span>
                                            <span class="star" data-rating="2">&#9734;</span>
                                            <span class="star" data-rating="3">&#9734;</span>
                                            <span class="star" data-rating="4">&#9734;</span>
                                            <span class="star" data-rating="5">&#9734;</span>
                                        </div>
                                        <input type="hidden" name="value_rating" id="value_rating" value="0">
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="rating-card p-3 bg-white rounded-3 shadow-sm h-100">
                                        <h3 class="text-center">Ease of Redemption</h3>
                                        <p class="text-center text-muted small">How simple was it to claim your rewards?</p>
                                        <div class="star-rating" data-field="ease_rating">
                                            <span class="star" data-rating="1">&#9734;</span>
                                            <span class="star" data-rating="2">&#9734;</span>
                                            <span class="star" data-rating="3">&#9734;</span>
                                            <span class="star" data-rating="4">&#9734;</span>
                                            <span class="star" data-rating="5">&#9734;</span>
                                        </div>
                                        <input type="hidden" name="ease_rating" id="ease_rating" value="0">
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="rating-card p-3 bg-white rounded-3 shadow-sm h-100">
                                        <h3 class="text-center">Timeliness</h3>
                                        <p class="text-center text-muted small">Did rewards arrive when expected?</p>
                                        <div class="star-rating" data-field="timeliness_rating">
                                            <span class="star" data-rating="1">&#9734;</span>
                                            <span class="star" data-rating="2">&#9734;</span>
                                            <span class="star" data-rating="3">&#9734;</span>
                                            <span class="star" data-rating="4">&#9734;</span>
                                            <span class="star" data-rating="5">&#9734;</span>
                                        </div>
                                        <input type="hidden" name="timeliness_rating" id="timeliness_rating" value="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="question-block">
                                <h3>Which businesses sent you birthday rewards?</h3>
                                <p class="text-muted">Check all that apply</p>
                                <div class="row g-3">';
                                
                                // Display enrolled businesses as checkboxes
                                foreach ($enrolled_businesses as $business) {
                                    $logo_url = !empty($business['logo']) ? $business['logo'] : '/public/images/placeholder-logo.png';
                                    $business_name = !empty($business['company_display_name']) ? $business['company_display_name'] : $business['company_name'];
                                    
                                    echo '
                                    <div class="col-md-4 col-sm-6">
                                        <div class="business-card border rounded-3 h-100 p-3">
                                            <div class="form-check">
                                                <input class="form-check-input business-toggle" type="checkbox" 
                                                       name="businesses_received[]" value="' . $business['company_id'] . '" 
                                                       id="business-' . $business['company_id'] . '">
                                                <label class="form-check-label" for="business-' . $business['company_id'] . '">
                                                    ' . htmlspecialchars($business_name) . '
                                                </label>
                                            </div>
                                        </div>
                                    </div>';
                                }
                                
                                echo '
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Page 3: Improvements & Loyalty -->
                    <div class="page page-3">
                        <div class="header-section">
                            <h2 class="mb-3">Help us make your next birthday even better!</h2>
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <i class="fas fa-gift text-warning" style="font-size: 20px;"></i>
                                <p class="mb-0">You are almost done! Finish this page to get your extra enrollment slot.</p>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <div class="question-block">
                                <h3>Which was your BEST reward experience?</h3>
                                <select class="form-select mb-3" name="best_business" id="best_business">
                                    <option value="">Select a business</option>';
                                    
                                    // Display dropdown options for businesses
                                    foreach ($enrolled_businesses as $business) {
                                        $business_name = !empty($business['company_display_name']) ? $business['company_display_name'] : $business['company_name'];
                                        echo '<option value="' . $business['company_id'] . '">' . htmlspecialchars($business_name) . '</option>';
                                    }
                                    
                                    echo '
                                </select>
                                
                                <textarea class="form-control" name="best_reward_feedback" id="best_reward_feedback" 
                                          placeholder="What made this reward special? (optional)" maxlength="200" rows="3"></textarea>
                            </div>
                            
                            <div class="question-block">
                                <h3>How can we improve your birthday rewards experience?</h3>
                                <textarea class="form-control" name="improvement_feedback" id="improvement_feedback" 
                                          placeholder="Your ideas and suggestions help us make Birthday.Gold better for you..." 
                                          maxlength="500" rows="5"></textarea>
                            </div>
                            
                            <div class="question-block">
                                <h3>How likely are you to recommend Birthday.Gold to friends?</h3>
                                
                                <div class="nps-slider-container mt-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small">Not likely</span>
                                        <span class="text-muted small">Very likely</span>
                                    </div>
                                    
                                    <input type="range" class="form-range" id="nps_rating_slider" 
                                           name="nps_rating" min="0" max="10" step="1" value="8">
                                    
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="text-muted small">0</span>
                                        <span class="text-muted small">1</span>
                                        <span class="text-muted small">2</span>
                                        <span class="text-muted small">3</span>
                                        <span class="text-muted small">4</span>
                                        <span class="text-muted small">5</span>
                                        <span class="text-muted small">6</span>
                                        <span class="text-muted small">7</span>
                                        <span class="text-muted small">8</span>
                                        <span class="text-muted small">9</span>
                                        <span class="text-muted small">10</span>
                                    </div>
                                    
                                    <div class="text-center">
                                        <h4>Your rating: <span id="nps_value_display">8</span></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Navigation Buttons -->
                    <div class="navigation-buttons">
                        <div class="d-flex align-items-center" style="width: 150px;">
                            <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2 btn-prev" style="display: none;">
                                <i class="fas fa-chevron-left"></i> Back
                            </button>
                        </div>
                        
                        <div class="progress-container">
                            <div class="progress-cake">
                                <div class="cake-slice rounded-circle bg-warning-subtle border border-warning"></div>
                                <div class="cake-slice rounded-circle bg-light border"></div>
                                <div class="cake-slice rounded-circle bg-light border"></div>
                            </div>
                            <small class="text-muted progress-page-indicator">Page 1 of 3</small>
                        </div>
                        
                        <div class="d-flex justify-content-end" style="min-width: 200px;">
                            <button type="button" class="btn btn-warning d-flex align-items-center gap-2 btn-next">
                                Next <i class="fas fa-chevron-right"></i>
                            </button>
                            
                            <button type="submit" name="submit_feedback" class="btn btn-warning btn-lg d-flex align-items-center gap-2 btn-submit px-4" style="display: none;">
                                <i class="fas fa-gift"></i> Submit & Get Your Reward
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>';
}
// Add the JavaScript directly to the page
echo '<script src="/public/js/feedback-form.js"></script>';
include($dir['core_components'] . '/bg_footer.inc');

$app->outputpage();
?>