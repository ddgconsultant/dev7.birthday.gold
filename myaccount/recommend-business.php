<?php
// recommend-business.php - User-facing page to submit business recommendations

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$pagetitle = "Recommend a Business";
$messages = array();
$success_message = '';

// Retrieve any messages
$transferpagedata = $system->startpostpage();

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
// Handle form submission
if ($app->formposted()) {
    // Get form data
    $business_name = trim($_POST['business_name'] ?? '');
    $home_url = trim($_POST['home_url'] ?? '');
    $signup_url = trim($_POST['signup_url'] ?? '');
    
    // Validate required fields
    if (empty($business_name) || empty($home_url) || empty($signup_url)) {
        $messages[] = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Please fill in all required fields</div>';
    } elseif (!filter_var($home_url, FILTER_VALIDATE_URL) || !filter_var($signup_url, FILTER_VALIDATE_URL)) {
        $messages[] = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Please enter valid URLs</div>';
    } else {
        // Normalize URLs for comparison - remove protocol and trailing slash
        $normalized_home_url = preg_replace('#^https?://#', '', $home_url);
        $normalized_home_url = rtrim($normalized_home_url, '/');
        
        $normalized_signup_url = preg_replace('#^https?://#', '', $signup_url);
        $normalized_signup_url = rtrim($normalized_signup_url, '/');
        
        // Extract domain from home URL for storing
        $parsed_url = parse_url($home_url);
        $domain = $parsed_url['host'] ?? '';
        $domain = preg_replace('/^www\./', '', $domain);
        
        try {
            // Begin transaction
            $database->beginTransaction();
            
            // Check if company already exists - check company name, home URL, or signup URL
            $check_sql = "SELECT company_id, company_name, status FROM bg_companies 
                          WHERE company_name = :name 
                          OR TRIM(TRAILING '/' FROM REPLACE(REPLACE(company_url, 'https://', ''), 'http://', '')) = :normalized_home_url
                          OR TRIM(TRAILING '/' FROM REPLACE(REPLACE(signup_url, 'https://', ''), 'http://', '')) = :normalized_signup_url
                          LIMIT 1";
            
            $check_stmt = $database->query($check_sql, [
                'name' => $business_name,
                'normalized_home_url' => $normalized_home_url,
                'normalized_signup_url' => $normalized_signup_url
            ]);
            
            if ($existing = $check_stmt->fetch(PDO::FETCH_ASSOC)) {
                $database->rollBack();
                
                // Show consistent message for all duplicates
                $messages[] = '<div class="alert alert-info"><i class="bi bi-info-circle"></i> We already know about this business.</div>';
            } else {
                // Insert new company with 'submitted' status
                $insert_sql = "INSERT INTO bg_companies 
                               (parent_company_name, company_name, company_display_name, 
                                company_url, signup_url, email_domain, bgrab_domain,
                                status, company_status, source, create_dt)
                               VALUES 
                               (:parent_name, :company_name, :display_name,
                                :home_url, :signup_url, :email_domain, :bgrab_domain,
                                'submitted', 'submitted', 'user_recommendation', NOW())";
                
                $insert_params = [
                    'parent_name' => $business_name,
                    'company_name' => $business_name,
                    'display_name' => $business_name,
                    'home_url' => $home_url,
                    'signup_url' => $signup_url,
                    'email_domain' => $domain,
                    'bgrab_domain' => $domain
                ];
                
                $database->query($insert_sql, $insert_params);
                $company_id = $database->lastInsertId();
                
                // Store submitter information in bg_company_attributes
                $attr_sql = "INSERT INTO bg_company_attributes 
                             (company_id, type, name, description, status, create_dt)
                             VALUES 
                             (:company_id, 'metadata', 'submitted_by_user_id', :user_id, 'active', NOW())";
                
                $database->query($attr_sql, [
                    'company_id' => $company_id,
                    'user_id' => $current_user_data['user_id']
                ]);
                
                // Store submission timestamp
                $time_sql = "INSERT INTO bg_company_attributes 
                             (company_id, type, name, description, status, create_dt)
                             VALUES 
                             (:company_id, 'metadata', 'submission_timestamp', :timestamp, 'active', NOW())";
                
                $database->query($time_sql, [
                    'company_id' => $company_id,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                
                // Store user's email for reference
                $email_sql = "INSERT INTO bg_company_attributes 
                              (company_id, type, name, description, status, create_dt)
                              VALUES 
                              (:company_id, 'metadata', 'submitter_email', :email, 'active', NOW())";
                
                $database->query($email_sql, [
                    'company_id' => $company_id,
                    'email' => $current_user_data['email'] ?? ''
                ]);
                
                // Award 1 free enrollment to the user
                $reward_sql = "INSERT INTO bg_user_attributes 
                               (user_id, type, name, description, value, status, create_dt)
                               VALUES 
                               (:user_id, 'recommendation_reward', 'free_enrollment_credit', 
                                CONCAT('Recommendation reward for ', :company_name), 
                                '1', 'active', NOW())";
                
                $database->query($reward_sql, [
                    'user_id' => $current_user_data['user_id'],
                    'company_name' => $business_name
                ]);
                
                // Update user's enrollment allocation count
                $update_allocations_sql = "UPDATE bg_users 
                                          SET allocations = allocations + 1,
                                              modify_dt = NOW()
                                          WHERE user_id = :user_id";
                
                $database->query($update_allocations_sql, [
                    'user_id' => $current_user_data['user_id']
                ]);
                
                // Track the reward in company attributes for future bonus rewards
                $track_reward_sql = "INSERT INTO bg_company_attributes 
                                    (company_id, type, name, description, value, status, create_dt)
                                    VALUES 
                                    (:company_id, 'recommendation_tracking', 'initial_reward_granted', 
                                     :user_id, '1', 'active', NOW())";
                
                $database->query($track_reward_sql, [
                    'company_id' => $company_id,
                    'user_id' => $current_user_data['user_id']
                ]);
                
                // Commit transaction
                $database->commit();
                
                // Success message with reward notification
                $success_message = '<div class="alert alert-success">
                    <h5 class="alert-heading"><i class="bi bi-check-circle"></i> Success!</h5>
                    <p>Thank you for your recommendation! We will review it shortly.</p>
                    <hr>
                    <p class="mb-0"><i class="bi bi-gift-fill"></i> <strong>You\'ve earned 1 free enrollment!</strong> It has been added to your account.</p>
                </div>';
                
                // Clear the form
                $_POST = array();
            }
            
        } catch (Exception $e) {
            $database->rollBack();
            error_log("Business recommendation error: " . $e->getMessage());
            $messages[] = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> An error occurred while submitting your recommendation</div>';
        }
    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodyclass='class="recommend-business"';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$additionalstyles .= '
<style>
/* Minimal custom CSS - using Bootstrap utilities where possible */

/* Custom utilities that Bootstrap doesn\'t provide */
.shadow-soft { box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); }
.shadow-lg-strong { box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }

.badge-primary-light {
    background: rgba(13, 110, 253, 0.1);
    color: var(--bs-primary);
}

/* Remove backgrounds for transparent layout */
body.recommend-business,
.recommend-business #bodyContentWrapper,
.recommend-business .pt-3,
.recommend-business .container {
    background: none !important;
}

/* Enhanced form controls */
.form-control-lg-custom {
    border-width: 2px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-control-lg-custom:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
}

/* Feature icon */
.feature-icon-custom {
    width: 48px;
    height: 48px;
    background: rgba(13, 110, 253, 0.1);
}

.feature-icon-custom i {
    font-size: 1.25rem;
}

/* Login-style header */
.recommend-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.5rem;
}

.recommend-header p {
    font-size: 1rem;
    margin: 0;
}

/* Welcome content styles to match login */
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

/* Grid layouts for desktop */
@media (min-width: 992px) {
    .hero-grid {
        display: grid;
        grid-template-columns: 1fr 500px;
        gap: 4rem;
    }
    
    .feature-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    
    .shadow-lg-strong { 
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1) !important; 
    }
}

/* Tablet & Desktop adjustments */
@media (min-width: 768px) {
    .recommend-header h1 {
        font-size: 2rem;
    }
}

@media (min-width: 1200px) {
    .hero-grid { gap: 6rem; }
    
    .welcome-content h2 {
        font-size: 3rem;
    }
}

/* Mobile adjustments */
@media (max-width: 991px) {
    .hero-grid { 
        display: block !important; 
        padding: 1rem !important;
    }
}
</style>';

?>


<?php
// Display messages
if (!empty($success_message)) {
    echo '<div class="container mt-3">' . $success_message . '</div>';
}
if (!empty($messages)) {
    echo '<div class="container mt-3">';
    foreach ($messages as $message) {
        echo $message;
    }
    echo '</div>';
}

// Check if Learn More is requested
$show_learn_more = isset($_GET['learnmore']);

echo '
<div class="pt-3">';

if ($show_learn_more) {
    // Display Learn More content WITHOUT hero wrapper
    echo '
    <div class="container-fluid pb-5" style="max-width: 1200px;">
        <div class="text-end mb-3">
            <a href="/myaccount/recommend-business" class="btn btn-primary">Submit a Recommendation</a>
        </div>
        <div class="bg-white rounded-3 shadow-soft overflow-hidden">
            <div class="p-5">
                <h2 class="text-center mb-4">Business Recommendation Rewards Program</h2>
                
                <div class="alert alert-success mb-4">
                    <h5 class="alert-heading"><i class="bi bi-gift-fill me-2"></i>Earn Free Enrollments!</h5>
                    <p class="mb-0">Help us grow our directory and get rewarded for your contributions.</p>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-primary">
                            <div class="card-body text-center">
                                <i class="bi bi-1-circle-fill text-primary" style="font-size: 3rem;"></i>
                                <h4 class="mt-3">1 Free Enrollment</h4>
                                <p class="text-black">Earned immediately when you submit a valid business recommendation</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-success">
                            <div class="card-body text-center">
                                <i class="bi bi-2-circle-fill text-success" style="font-size: 3rem;"></i>
                                <h4 class="mt-3">2 Additional Free Enrollments</h4>
                                <p class="text-black">Earned when your recommended business is approved and another member enrolls</p>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="mb-3"><i class="bi bi-check2-circle me-2"></i>How It Works</h3>
                <ol class="mb-4">
                    <li class="mb-2"><strong>Submit a Business:</strong> Provide the business name, website, and their birthday rewards signup page</li>
                    <li class="mb-2"><strong>Get Instant Reward:</strong> Receive 1 free enrollment credit immediately upon submission</li>
                    <li class="mb-2"><strong>We Review:</strong> Our team verifies the business offers birthday rewards</li>
                    <li class="mb-2"><strong>Business Goes Live:</strong> Once processed and approved, the business appears in our directory</li>
                    <li class="mb-2"><strong>Earn Bonus Rewards:</strong> When another member enrolls in your recommended business, you get 2 more free enrollments!</li>
                </ol>

                <h3 class="mb-3"><i class="bi bi-shield-check me-2"></i>Rules & Guidelines</h3>
                <ul class="mb-4">
                    <li class="mb-2">Business must offer legitimate birthday rewards or perks</li>
                    <li class="mb-2">Cannot be a business already in our directory</li>
                    <li class="mb-2">Must provide accurate website and signup information</li>
                    <li class="mb-2">You cannot be the first person to enroll (to earn the 2 bonus enrollments)</li>
                    <li class="mb-2">Free enrollments are added to your account automatically</li>
                    <li class="mb-2">There is no limit to how many businesses you can recommend - however any misrepresentation or manipulation may result in forfeiture and blocking to not allow you to recommend future businesses</li>
                </ul>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Pro Tip:</strong> Look for local restaurants, retail stores, and service businesses in your area that offer birthday specials but might not be widely known!
                </div>

                <div class="text-center mt-4">
                    <a href="/myaccount/recommend-business" class="btn btn-primary btn-lg" style="border-radius: 50px;">
                        <i class="bi bi-plus-circle me-2"></i>Submit a Recommendation
                    </a>
                </div>
            </div>
        </div>
    </div>';
} else {
    // Display the form WITH hero wrapper for desktop
    echo '
    <div class="container-fluid hero-grid align-items-center px-lg-4 py-3" style="max-width: 1200px; margin: 0 auto;">
        <!-- Welcome content for desktop -->
        <div class="welcome-content d-none d-lg-block">
            <h2>Help Us Grow & <span>Earn Rewards</span></h2>
            <p>Know a business that offers birthday rewards? Share it with our community and earn free enrollments!</p>
            
            <div class="feature-grid">
                <div class="d-flex align-items-start gap-3">
                    <div class="feature-icon-custom rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 text-primary">
                        <i class="bi bi-gift-fill"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Instant Reward</h3>
                        <p>Get 1 free enrollment immediately upon submission</p>
                    </div>
                </div>
                
                <div class="d-flex align-items-start gap-3">
                    <div class="feature-icon-custom rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 text-primary">
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Bonus Rewards</h3>
                        <p>Earn 2 more when someone enrolls in your recommendation</p>
                    </div>
                </div>
                
                <div class="d-flex align-items-start gap-3">
                    <div class="feature-icon-custom rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 text-primary">
                        <i class="bi bi-shield-fill-check"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Quality Verified</h3>
                        <p>We review every submission to ensure legitimacy</p>
                    </div>
                </div>
                
                <div class="d-flex align-items-start gap-3">
                    <div class="feature-icon-custom rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 text-primary">
                        <i class="bi bi-building-fill"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Local & National</h3>
                        <p>Recommend any business offering birthday perks</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="w-100" style="max-width: 480px; margin: 0 auto;">
            <div class="text-end mb-3">
                <a href="/myaccount/recommend-business?learnmore" class="btn btn-outline-secondary btn-sm">Learn More</a>
            </div>
            <div class="bg-white rounded-3 shadow-soft overflow-hidden shadow-lg-strong">
                <div class="recommend-header text-center pt-4 pt-md-5 pb-3 px-4 px-md-5">
                    <div class="badge badge-primary-light d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill small fw-medium mb-3">
                        <i class="bi bi-building-add fs-6"></i>
                        <span>Business Recommendation</span>
                    </div>
                    <h1>Recommend a Business</h1>
                    <p class="text-muted">Help us grow our directory and earn rewards</p>
                </div>
                <div class="px-4 px-md-5 pb-4 pb-md-5">
                    <form method="post" action="/myaccount/recommend-business.php">
                        ' . $display->inputcsrf_token() . '
                        
                        <div class="mb-3 mt-4">
                            <label for="businessName" class="form-label fw-bold">Business Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg-custom rounded-2" id="businessName" name="business_name" value="' . htmlspecialchars($_POST['business_name'] ?? '') . '" required>
                            <div class="small text-muted mt-1">Enter the official business name (e.g., "Starbucks", "Target", "Olive Garden")</div>
                        </div>

                        <div class="mb-3 mt-5">
                            <label for="homeUrl" class="form-label fw-bold">Business Website (Home Page) <span class="text-danger">*</span></label>
                            <input type="url" class="form-control form-control-lg-custom rounded-2" id="homeUrl" name="home_url" value="' . htmlspecialchars($_POST['home_url'] ?? '') . '" placeholder="https://" required>
                            <div class="small text-muted fst-italic">Example: https://www.starbucks.com</div>
                            <div class="small text-muted mt-1">The main website URL for the business</div>
                        </div>

                        <div class="mb-3 mt-5">
                            <label for="signupUrl" class="form-label fw-bold">Birthday Rewards Sign-Up Page <span class="text-danger">*</span></label>
                            <input type="url" class="form-control form-control-lg-custom rounded-2" id="signupUrl" name="signup_url" value="' . htmlspecialchars($_POST['signup_url'] ?? '') . '" placeholder="https://" required>
                            <div class="small text-muted fst-italic">Example: https://www.starbucks.com/rewards</div>
                            <div class="small text-muted mt-1">The specific page where customers can sign up for birthday rewards</div>
                        </div>

                        <div class="alert alert-info d-lg-none" role="alert">
                            <i class="bi bi-gift-fill me-2"></i>
                            <strong>Earn Rewards!</strong> You\'ll receive 1 free enrollment immediately upon submission, and 2 more when another member enrolls in your recommended business.
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg" style="border-radius: 50px;">Submit Recommendation</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="mt-3 text-center text-muted">
                <small>
                    <i class="bi bi-shield-check me-1"></i>
                    We verify all submissions to ensure quality and accuracy
                </small>
            </div>
        </div><!-- End w-100 -->
    </div><!-- End hero-grid -->';
}

echo '
</div><!-- End pt-3 -->';

$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>