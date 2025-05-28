<?php
$additionaljavascript = '';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
// Birthday.Gold Feedback Form
// This allows members to provide feedback about their birthday rewards experience

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
// Check for user login and redirect if not logged in

// Instead of manually creating these variables, leverage the user_getaccountdetails.inc file
// which populates all the necessary account details
include_once($dir['core_components'] . '/user_getaccountdetails.inc');

// Get user data from the already loaded $component_user_data in user_getaccountdetails.inc
$user_id = $current_user_data['user_id'];
$username = $current_user_data['username'];
$first_name = $current_user_data['first_name'];
$last_name = $current_user_data['last_name'];
$email = $current_user_data['email'];
$birthdate = $current_user_data['birthdate'];

// Format birthdate for display - using existing $alive variable from user_getaccountdetails.inc
$birthdate_obj = new DateTime($birthdate);
$formatted_birthdate = $birthdate_obj->format('F jS');
$age = $alive['years']; // Use the already calculated age from user_getaccountdetails.inc

// Use plural2 function which is already used throughout the codebase
$age_suffix = $qik->plural2($age, "year");
$age_suffix = str_replace(" years", "", $age_suffix);
$age_suffix = str_replace(" year", "", $age_suffix);

// Get enrolled businesses count for this user - use the already calculated count
// from user_getaccountdetails.inc if available
if (isset($businessoutput['counts']['success'])) {
    $enrolled_businesses_count = $businessoutput['counts']['success'];
} else {
    // Fallback to database query if not available
    $sql = "SELECT COUNT(*) AS enrolled_count 
            FROM bg_user_enrollments 
            WHERE user_id = :user_id 
            AND status IN ('success', 'success-btn')";
    $stmt = $database->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $enrollment_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $enrolled_businesses_count = $enrollment_data['enrolled_count'];
}

// Get enrolled businesses for this user
$sql = "SELECT e.user_company_id, c.company_id, c.company_name, c.company_display_name, 
               m.file_location AS logo
        FROM bg_user_enrollments e
        JOIN bg_companies c ON e.company_id = c.company_id
        LEFT JOIN bg_media m ON c.company_id = m.company_id AND m.type = 'logo' AND m.status = 'active'
        WHERE e.user_id = :user_id 
        AND e.status IN ('success', 'success-btn')
        ORDER BY c.company_display_name";
$stmt = $database->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$enrolled_businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Form submission data
$feedback_submitted = false;
$form_errors = [];
$current_page = 1;
$max_pages = 3;

// Process form submission
if ($app->formposted()) {
    $feedback_submitted = processFormSubmission($database, $user_id, $app);
}

#-------------------------------------------------------------------------------
# HANDLE FORM SUBMISSION
#-------------------------------------------------------------------------------
function processFormSubmission($database, $user_id, $app) {
    // If we're submitting the full form (last page)
    if (isset($_POST['submit_feedback'])) {
        try {
            // Start transaction
            $database->beginTransaction();
            
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
                    
            $stmt = $database->prepare($sql);
            $stmt->execute([
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
            $stmt = $database->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $attribute = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($attribute) {
                // Update existing attribute
                $new_limit = $attribute['value'] + 1;
                
                $sql = "UPDATE bg_user_attributes 
                        SET value = :new_limit, modify_dt = NOW() 
                        WHERE attribute_id = :attribute_id";
                $stmt = $database->prepare($sql);
                $stmt->execute([
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
                $stmt = $database->prepare($sql);
                $stmt->execute([
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
            $stmt = $database->prepare($sql);
            $stmt->execute([
                'user_id' => $user_id,
                'title' => $notification_title,
                'message' => $notification_message
            ]);
            
            // Commit transaction
            $database->commit();
            
            // Return success
            return true;
            
        } catch (Exception $e) {
            // Roll back transaction and log error
            $database->rollBack();
            $app->logError("Failed to process feedback submission: " . $e->getMessage());
            return false;
        }
    } elseif (isset($_POST['current_page'])) {
        // Just navigating between pages, return false to continue showing the form
        return false;
    }
    
    return false;
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = 'bg-light';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Additional styles for the feedback form
$additionalstyles .= '
<style>
.feedback-survey {
    min-height: 100vh;
    background-color: #f9f7f2;
}

.header {
    background-color: #f8d568;
    padding: 20px 0;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.logo {
    max-width: 200px;
    height: auto;
}

.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px 20px;
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

h1, h2, h3 {
    color: #d4af37;
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
    transition: color 0.2s;
}

.star.active {
    color: #ffc107;
}

.toggle-switches .form-check {
    transition: background-color 0.3s;
}

.business-card {
    height: 100%;
    transition: box-shadow 0.3s;
}

.business-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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

.success-icon {
    margin-bottom: 20px;
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
</style>
';

// Add the custom JavaScript for the form
$additionaljavascript .= '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Initialize the form page navigation
    let currentPage = ' . (isset($_POST["current_page"]) ? intval($_POST["current_page"]) : 1) . ';
    const maxPages = 3;
    
    // Set the initial active page
    document.querySelector(".page-" + currentPage).classList.add("active");
    
    // Navigation buttons
    const nextBtn = document.querySelector(".btn-next");
    const prevBtn = document.querySelector(".btn-prev");
    const submitBtn = document.querySelector(".btn-submit");
    
    if (nextBtn) {
        nextBtn.addEventListener("click", function() {
            if (currentPage < maxPages) {
                document.querySelector(".page-" + currentPage).classList.remove("active");
                currentPage++;
                document.querySelector(".page-" + currentPage).classList.add("active");
                document.getElementById("current_page").value = currentPage;
                updateNavigation();
            }
        });
    }
    
    if (prevBtn) {
        prevBtn.addEventListener("click", function() {
            if (currentPage > 1) {
                document.querySelector(".page-" + currentPage).classList.remove("active");
                currentPage--;
                document.querySelector(".page-" + currentPage).classList.add("active");
                document.getElementById("current_page").value = currentPage;
                updateNavigation();
            }
        });
    }
    
    function updateNavigation() {
        // Update the display of navigation buttons
        if (prevBtn) {
            prevBtn.style.visibility = currentPage === 1 ? "hidden" : "visible";
        }
        
        if (nextBtn) {
            nextBtn.style.display = currentPage === maxPages ? "none" : "block";
        }
        
        if (submitBtn) {
            submitBtn.style.display = currentPage === maxPages ? "block" : "none";
        }
        
        // Update progress indicator
        updateProgressIndicator();
    }
    
    function updateProgressIndicator() {
        const progressSlices = document.querySelectorAll(".cake-slice");
        progressSlices.forEach((slice, index) => {
            if (index + 1 < currentPage) {
                slice.classList.add("bg-warning");
                slice.classList.remove("bg-warning-subtle", "bg-light");
                const checkIcon = document.createElement("i");
                checkIcon.className = "fas fa-check text-white";
                // Clear existing content and add the icon
                slice.innerHTML = "";
                slice.appendChild(checkIcon);
            } else if (index + 1 === currentPage) {
                slice.classList.add("bg-warning-subtle");
                slice.classList.remove("bg-warning", "bg-light");
                slice.innerHTML = "";
            } else {
                slice.classList.add("bg-light");
                slice.classList.remove("bg-warning", "bg-warning-subtle");
                slice.innerHTML = "";
            }
        });
        
        document.querySelector(".progress-page-indicator").textContent = "Page " + currentPage + " of " + maxPages;
    }
    
    // Initialize progress indicator
    updateProgressIndicator();
    
    // Star rating functionality
    const starContainers = document.querySelectorAll(".star-rating");
    starContainers.forEach(container => {
        const stars = container.querySelectorAll(".star");
        const ratingInput = container.nextElementSibling;
        
        stars.forEach((star, index) => {
            star.addEventListener("click", () => {
                const rating = index + 1;
                ratingInput.value = rating;
                
                // Reset all stars
                stars.forEach(s => {
                    s.classList.remove("active");
                    s.innerHTML = "&#9734;"; // Empty star
                });
                
                // Fill stars up to the selected one
                for (let i = 0; i < rating; i++) {
                    stars[i].classList.add("active");
                    stars[i].innerHTML = "&#9733;"; // Filled star
                }
                
                // Show rating feedback message if it exists
                const feedbackContainer = container.parentElement.querySelector(".rating-feedback");
                if (feedbackContainer) {
                    const feedbackMessages = feedbackContainer.querySelectorAll(".feedback-message");
                    feedbackMessages.forEach(msg => msg.style.display = "none");
                    const activeFeedback = feedbackContainer.querySelector(`.feedback-message[data-rating="${rating}"]`);
                    if (activeFeedback) {
                        activeFeedback.style.display = "block";
                    }
                }
            });
        });
    });
    
    // Business toggle functionality
    const businessToggles = document.querySelectorAll(".business-toggle");
    businessToggles.forEach(toggle => {
        toggle.addEventListener("change", function() {
            const businessId = this.value;
            const hiddenInput = document.getElementById("businesses_received");
            let selectedBusinesses = hiddenInput.value ? JSON.parse(hiddenInput.value) : [];
            
            if (this.checked) {
                if (!selectedBusinesses.includes(businessId)) {
                    selectedBusinesses.push(businessId);
                }
            } else {
                selectedBusinesses = selectedBusinesses.filter(id => id !== businessId);
            }
            
            hiddenInput.value = JSON.stringify(selectedBusinesses);
            
            // Update the card styling
            const card = this.closest(".business-card");
            if (card) {
                if (this.checked) {
                    card.classList.add("border-success");
                } else {
                    card.classList.remove("border-success");
                }
            }
        });
    });
    
    // NPS slider functionality
    const npsSlider = document.getElementById("nps_rating_slider");
    if (npsSlider) {
        const npsValueDisplay = document.getElementById("nps_value_display");
        const npsInput = document.getElementById("nps_rating");
        const npsFeedback = document.getElementById("nps_feedback");
        
        npsSlider.addEventListener("input", function() {
            const value = this.value;
            npsInput.value = value;
            npsValueDisplay.textContent = value;
            
            // Update emoji
            let emoji = "😐";
            if (value >= 9) emoji = "🤩";
            else if (value >= 7) emoji = "😊";
            else if (value >= 5) emoji = "🙂";
            else if (value >= 3) emoji = "😐";
            else emoji = "😔";
            
            document.getElementById("nps_emoji").textContent = emoji;
            
            // Update feedback message
            let feedbackMsg = "";
            if (value >= 9) feedbackMsg = "Wow! We\'re thrilled you love Birthday.Gold! 🌟";
            else if (value >= 7) feedbackMsg = "Great! Thanks for your positive feedback.";
            else if (value >= 5) feedbackMsg = "Thanks for your feedback. We\'ll keep improving!";
            else feedbackMsg = "We appreciate your honesty. We\'ll work hard to improve your experience.";
            
            npsFeedback.textContent = feedbackMsg;
        });
    }
    
    // Character counter for textareas
    const textareas = document.querySelectorAll("textarea[maxlength]");
    textareas.forEach(textarea => {
        const countDisplay = textarea.nextElementSibling.querySelector(".char-count");
        const feedbackDisplay = textarea.nextElementSibling.querySelector(".char-feedback");
        
        textarea.addEventListener("input", function() {
            const remainingChars = this.maxLength - this.value.length;
            countDisplay.textContent = this.value.length + "/" + this.maxLength;
            
            // Update feedback message based on length
            let feedbackMsg = "";
            if (this.value.length >= 300) feedbackMsg = "This is gold! Thank you for such detailed feedback! 🎂";
            else if (this.value.length >= 200) feedbackMsg = "Loving this feedback! So helpful!";
            else if (this.value.length >= 100) feedbackMsg = "You\'re on a roll! 🌟";
            else if (this.value.length >= 50) feedbackMsg = "Great start! Tell us more...";
            else if (this.value.length >= 1) feedbackMsg = "We\'re all ears!";
            
            feedbackDisplay.textContent = feedbackMsg;
        });
    });
});
</script>
';

if ($feedback_submitted) {
    // Show thank you page
    echo '
    <div class="feedback-survey">
        <div class="container">
            <div class="thank-you-page">
                <div class="success-message">
                    <div class="success-icon">
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
                            <a href="/myaccount/dashboard" class="btn btn-outline-secondary px-4">View My Dashboard</a>
                            <a href="/myaccount/enroll" class="btn btn-warning px-4">Enroll With a New Business</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
} else {
    // Show the feedback form
    echo '
    <div class="feedback-survey main-content">
        <div class="container pt-5 mt-5">
            <div class="survey-container">
                <form id="feedbackForm" method="post" action="">
                    <input type="hidden" id="current_page" name="current_page" value="' . $current_page . '">
                    
                    <!-- Page 1: Overall Experience -->
                    <div class="page page-1">
                        <div class="header-section">
                            <h2 class="mb-3">Hey ' . htmlspecialchars($first_name) . '! How was your birthday?</h2>
                            <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                                <span class="cake-icon">🎂</span>
                                <p class="mb-0">You celebrated your <span class="fw-bold">' . $age . '</span> birthday on <span class="fw-bold">' . $formatted_birthdate . '</span>!</p>
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
                                    <span class="star">&#9734;</span>
                                    <span class="star">&#9734;</span>
                                    <span class="star">&#9734;</span>
                                    <span class="star">&#9734;</span>
                                    <span class="star">&#9734;</span>
                                </div>
                                <input type="hidden" name="overall_rating" value="0">
                                
                                <div class="rating-feedback text-center mt-2">
                                    <div class="feedback-message" data-rating="1" style="display: none;">We\'re sorry to hear that! We\'ll work to improve.</div>
                                    <div class="feedback-message" data-rating="2" style="display: none;">Thanks for the honest feedback.</div>
                                    <div class="feedback-message" data-rating="3" style="display: none;">Glad you had a decent experience!</div>
                                    <div class="feedback-message" data-rating="4" style="display: none;">That\'s great to hear! Thank you!</div>
                                    <div class="feedback-message" data-rating="5" style="display: none;">Wow! We\'re thrilled you had an amazing birthday experience! 🎉</div>
                                </div>
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
                                            Didn\'t receive any
                                            <i class="fas fa-times text-danger"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="question-block">
                                <h3>Which businesses sent you birthday rewards?</h3>
                                <p class="enrolled-count text-center mb-3">
                                    You\'re enrolled with <span class="fw-bold">' . $enrolled_businesses_count . '</span> businesses
                                </p>
                                
                                <div class="row g-3">
                                    ';
                                    
                                    // Display enrolled businesses
                                    foreach ($enrolled_businesses as $business) {
                                        $logo_url = !empty($business['logo']) ? $business['logo'] : '/images/placeholder-logo.png';
                                        $business_name = !empty($business['company_display_name']) ? $business['company_display_name'] : $business['company_name'];
                                        
                                        echo '
                                        <div class="col-md-4 col-sm-6">
                                            <div class="business-card border rounded-3 h-100">
                                                <div class="p-2 bg-light rounded-top text-center">
                                                    <img src="' . htmlspecialchars($logo_url) . '" alt="' . htmlspecialchars($business_name) . ' Logo" class="img-fluid" style="height: 80px; object-fit: contain;">
                                                </div>
                                                <h5 class="text-center py-2 border-bottom">' . htmlspecialchars($business_name) . '</h5>
                                                <div class="p-2 d-flex align-items-center justify-content-center gap-2">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input business-toggle" type="checkbox" value="' . $business['company_id'] . '" id="business-' . $business['company_id'] . '">
                                                        <label class="form-check-label" for="business-' . $business['company_id'] . '">
                                                            Received reward
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>';
                                    }
                                    
                                    echo '
                                </div>
                                <input type="hidden" id="businesses_received" name="businesses_received" value="[]">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Page 2: Reward Quality -->
                    <div class="page page-2">
                        <div class="header-section">
                            <h2 class="mb-3">What did you think of your rewards?</h2>
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <i class="fas fa-gift text-warning" style="font-size: 20px;"></i>
                                <p class="mb-0">You\'re making progress! Just a bit more to earn your extra enrollment slot.</p>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="rating-card p-3 bg-white rounded-3 shadow-sm h-100">
                                        <h3 class="text-center">Value of Rewards</h3>
                                        <p class="text-center text-muted small">Were the rewards worth your time?</p>
                                        <div class="star-rating">
                                            <span class="star">&#9734;</span>
                                            <span class="star">&#9734;</span>
                                            <span class="star">&#9734;</span>
                                            <span class="star">&#9734;</span>
                                            <span class="star">&#9734;</span>
                                        </div>
                                        <input type="hidden" name="value_rating" value="0">
                                        
                                        <div class="rating-feedback text-center mt-2">
                                            <div class="feedback-message" data-rating="1" style="display: none;">Not valuable</div>
                                            <div class="feedback-message" data-rating="2" style="display: none;">Somewhat valuable</div>
                                            <div class="feedback-message" data-rating="3" style="display: none;">Moderately valuable</div>
                                            <div class="feedback-message" data-rating="4" style="display: none;">Quite valuable</div>
                                            <div class="feedback-message" data-rating="5" style="display: none;">Extremely valuable</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="rating-card p-3 bg-white rounded-3 shadow-sm h-100">
                                        <h3 class="text-center">Ease of Redemption</h3>
                                        <p class="text-center text-muted small">How simple was it to claim your rewards?</p>
                                        <div class="star-rating">
                                            <span class="star">&#9734;</span>
                                            <span class="star">&#9734;</span>
                                            <span class="star">&#9734;</span>
                                            <span class="star">&#9734;</span>
                                            <span class="star">&#9734;</span>
                                        </div>
                                        <input type="hidden" name="ease_rating" value="0">
                                        
                                        <div class="rating-feedback text-center mt-2">
                                            <div class="feedback-message" data-rating="1" style="display: none;">Very difficult</div>
                                            <div class="feedback-message" data-rating="2" style="display: none;">Somewhat difficult</div>
                                            <div class="feedback-message" data-rating="3" style="display: none;">Neutral</div>
                                            <div class="feedback-message" data-rating="4" style="display: none;">Fairly easy</div>
                                            <div class="feedback-message" data-rating="5" style="display: none;">Very easy</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="rating-card p-3 bg-white rounded-3 shadow-sm h-100">
                                        <h3 class="text-center">Timeliness</h3>
                                        <p class="text-center text-muted small">Did rewards arrive when expected?</p>
                                        <div class="star-rating">
                                            <span class="star">&#9734;</span>
                                            <span class="star">&#9734;</span>
                                            <span class="star">&#9734;</span>
                                            <span class="star">&#9734;</span>
                                            <span class="star">&#9734;</span>
                                        </div>
                                        <input type="hidden" name="timeliness_rating" value="0">
                                        
                                        <div class="rating-feedback text-center mt-2">
                                            <div class="feedback-message" data-rating="1" style="display: none;">Very late</div>
                                            <div class="feedback-message" data-rating="2" style="display: none;">Somewhat late</div>
                                            <div class="feedback-message" data-rating="3" style="display: none;">On time</div>
                                            <div class="feedback-message" data-rating="4" style="display: none;">Good timing</div>
                                            <div class="feedback-message" data-rating="5" style="display: none;">Perfect timing</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="question-block">
                                <h3>Which was your BEST reward experience?</h3>
                                
                                <div class="business-dropdown-container mt-3">
                                    <select class="form-select mb-3" name="best_business" id="best_business">
                                        <option value="">Select a business</option>';
                                        
                                        // Display dropdown options for businesses
                                        foreach ($enrolled_businesses as $business) {
                                            $business_name = !empty($business['company_display_name']) ? $business['company_display_name'] : $business['company_name'];
                                            echo '<option value="' . $business['company_id'] . '">' . htmlspecialchars($business_name) . '</option>';
                                        }
                                        
                                        echo '
                                    </select>
                                    
                                    <div class="reward-details">
                                        <textarea class="form-control" name="best_reward_feedback" id="best_reward_feedback" 
                                                  placeholder="What made this reward special? (optional)" maxlength="200" rows="3"></textarea>
                                        <div class="d-flex justify-content-between mt-2">
                                            <small class="text-muted char-count">0/200 characters</small>
                                            <small class="text-warning char-feedback"></small>
                                        </div>
                                    </div>
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
                                <p class="mb-0">You\'re almost done! Finish this page to get your extra enrollment slot.</p>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <div class="question-block">
                                <h3>How can we improve your birthday rewards experience?</h3>
                                
                                <div class="text-feedback-container mt-3">
                                    <textarea class="form-control" name="improvement_feedback" id="improvement_feedback" 
                                              placeholder="Your ideas and suggestions help us make Birthday.Gold better for you..." 
                                              maxlength="500" rows="5"></textarea>
                                    
                                    <div class="d-flex justify-content-between mt-2">
                                        <small class="text-muted char-count">0/500 characters</small>
                                        <small class="text-warning char-feedback"></small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="question-block">
                                <h3>How likely are you to recommend Birthday.Gold to friends?</h3>
                                
                                <div class="nps-slider-container mt-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small">Not likely</span>
                                        <span class="text-muted small">Very likely</span>
                                    </div>
                                    
                                    <input type="range" class="form-range" id="nps_rating_slider" 
                                           min="0" max="10" step="1" value="8">
                                    <input type="hidden" name="nps_rating" id="nps_rating" value="8">
                                    
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
                                    
                                    <div class="nps-selected-value text-center mb-2">
                                        <h4>Your rating: <span id="nps_value_display">8</span> <span id="nps_emoji">😊</span></h4>
                                    </div>
                                    
                                    <div class="nps-message text-center text-warning" id="nps_feedback">
                                        Great! Thanks for your positive feedback.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="submit-section text-center mt-4">
                                <div class="final-cta mb-3">
                                    <h4>Thank you for your feedback!</h4>
                                    <p>Your insights help us make Birthday.Gold better for everyone.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Navigation Buttons -->
                    <div class="navigation-buttons">
                        <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2 btn-prev" style="visibility: hidden;">
                            <i class="fas fa-chevron-left"></i> Back
                        </button>
                        
                        <div class="progress-container">
                            <div class="progress-cake">
                                <div class="cake-slice rounded-circle bg-warning-subtle border border-warning"></div>
                                <div class="cake-slice rounded-circle bg-light border"></div>
                                <div class="cake-slice rounded-circle bg-light border"></div>
                            </div>
                            <small class="text-muted progress-page-indicator">Page 1 of 3</small>
                        </div>
                        
                        <button type="button" class="btn btn-warning d-flex align-items-center gap-2 btn-next">
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                        
                        <button type="submit" name="submit_feedback" class="btn btn-warning d-flex align-items-center gap-2 btn-submit" style="display: none;">
                            <i class="fas fa-gift"></i> Submit & Get Reward
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>';
}

// Display the footer
$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();