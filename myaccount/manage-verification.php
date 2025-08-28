<?php

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Include any necessary headers, session classes, or configuration files

// Check if user already has a verification application
$existing_application = null;
$user_id = $current_user_data['user_id'] ?? 0;

if ($user_id > 0) {
    // Check for existing verification application in bg_user_attributes
    $sql = "SELECT * FROM bg_user_attributes 
            WHERE user_id = ? 
            AND attribute_type = 'verification_application' 
            AND status = 'active'
            ORDER BY create_dt DESC 
            LIMIT 1";
    $existing_application = $database->getrow($sql, [$user_id]);
    
    // Parse the JSON data if exists
    if ($existing_application && !empty($existing_application['attribute_value'])) {
        $app_data = json_decode($existing_application['attribute_value'], true);
        if ($app_data) {
            $existing_application = array_merge($existing_application, $app_data);
            $existing_application['created_at'] = $existing_application['create_dt'];
        }
    }
}

#-------------------------------------------------------------------------------
# HANDLE FORM ACTIONS
#-------------------------------------------------------------------------------
// Variables to store messages
$submission_success = false;
$submission_message = '';

// Processing form submission (only if no existing application)
if ($app->formposted() && empty($existing_application)) {
    // Grab form data
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $social_profile = $_POST['social_profile'] ?? '';
    $photo_id = $_FILES['photo_id'] ?? null;

    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($social_profile) || empty($photo_id['name'])) {
        $submission_message = 'All fields are required. Please fill in all information.';
    } else {
        // File upload logic for the photo ID
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/photo_id/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($photo_id["name"], PATHINFO_EXTENSION);
        $unique_filename = 'verification_' . $current_user_data['user_id'] . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $unique_filename;
        
        if (move_uploaded_file($photo_id["tmp_name"], $target_file)) {
            // Save form data into bg_user_attributes as JSON
            $verification_data = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'social_profile' => $social_profile,
                'photo_id_path' => '/uploads/photo_id/' . $unique_filename,
                'status' => 'pending',
                'submitted_date' => date('Y-m-d H:i:s')
            ];
            
            // Insert into bg_user_attributes
            $sql = 'INSERT INTO bg_user_attributes (user_id, attribute_type, attribute_name, attribute_value, status, create_dt) 
                    VALUES (?, ?, ?, ?, ?, NOW())';
            $database->query($sql, [
                $current_user_data['user_id'],
                'verification_application',
                'verification_request',
                json_encode($verification_data),
                'active'
            ]);

            $submission_success = true;
            $submission_message = 'Your verification application has been successfully submitted! Our team will review your application within 3-5 business days. You will receive an email notification once the review is complete.';
            
            // Update the existing_application variable so the page shows the right status
            $existing_application = $verification_data;
            $existing_application['created_at'] = date('Y-m-d H:i:s');
        } else {
            $submission_message = 'Error uploading your photo ID. Please try again. Make sure the file is under 5MB and is a valid image format (JPG, PNG, GIF).';
        }
    }
}




#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass='';

$additionalstyles .= '
<style>
/* Modern Verification Page Styles - Integrated with BG Template */
.verification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.verification-header h2 {
    margin: 0;
}

/* Progress Section */
.progress-section {
    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2.5rem;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.progress-title {
    font-size: 1.25rem;
    font-weight: 600;
}

.progress-percentage {
    font-size: 1.5rem;
    font-weight: 700;
    color: #f59e0b;
}

.progress-bar {
    background: #e2e8f0;
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 2rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.checklist {
    display: grid;
    gap: 1rem;
}

.checklist-item {
    background: white;
    border-radius: 8px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.2s;
    border: 1px solid #e2e8f0;
}

.checklist-item:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.checklist-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.2rem;
}

.checklist-icon.completed {
    background: #10b981;
    color: white;
}

.checklist-icon.pending {
    background: #e2e8f0;
    color: #a0aec0;
}

.checklist-content {
    flex: 1;
}

.checklist-title {
    font-weight: 600;
    margin-bottom: 2px;
    font-size: 1.1rem;
}

.checklist-description {
    font-size: 0.875rem;
    color: #718096;
}

.checklist-action {
    color: #f59e0b;
    font-weight: 500;
    font-size: 0.875rem;
    text-decoration: none;
    transition: color 0.2s;
    white-space: nowrap;
}

.checklist-action:hover {
    color: #d97706;
}

/* Benefits Section */
.section {
    margin-bottom: 3rem;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.benefit-card {
    background: #f7fafc;
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.2s;
}

.benefit-card:hover {
    background: #edf2f7;
    transform: translateY(-2px);
}

.benefit-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 1rem;
    color: white;
}

.benefit-title {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.benefit-description {
    font-size: 0.875rem;
    color: #718096;
}

/* CTA Button - removed, using Bootstrap btn-success */

/* FAQ Section */
.faq-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 2.5rem;
    margin-top: 3rem;
}

.faq-header {
    font-size: 2rem;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 2rem;
    text-align: center;
    position: relative;
    padding-bottom: 1rem;
}

.faq-header:after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 4px;
    background: var(--bs-primary);
    border-radius: 2px;
}

.faq-item {
    padding: 1.5rem 0;
}

.faq-item:last-child {
    padding-bottom: 0;
}

.faq-question {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.faq-answer {
    color: #718096;
    font-size: 0.9375rem;
}

/* Integration with existing styles */
.content-panel {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Override hanging-indent styles for checklist items */
.checklist-item.hanging-indent {
    padding: 1rem 1.25rem;
    margin-bottom: 0;
}

.checklist-item .title-and-button {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}

.checklist-item .title-and-button strong {
    font-size: 1.1rem !important;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
// Get actual user verification progress data
// $profilecompletion is set in bg_user_profileheader.inc from user_getaccountdetails.inc
$profileComplete = ($profilecompletion['required_percentage'] == 100);
$hasAvatar = ($account->getUserAttribute($current_user_data['user_id'], 'avatar') != null);
$hasVerification = ($account->getUserAttribute($current_user_data['user_id'], 'verification_id') != null);
#$postCount = $social->posts();
$postCount = 8; // Placeholder - would get actual count from social system
$hasEnoughPosts = ($postCount > 5);

// Calculate overall progress
$totalRequirements = 4;
$completedRequirements = 0;
if ($profileComplete) $completedRequirements++;
if ($hasAvatar) $completedRequirements++;
if ($hasVerification) $completedRequirements++;
if ($hasEnoughPosts) $completedRequirements++;
$progressPercentage = round(($completedRequirements / $totalRequirements) * 100);
$canApplyForVerification = ($completedRequirements === $totalRequirements);



?>

<div class="container my-5 pt-5">
    <?php if (!empty($submission_message)): ?>
        <?php if ($submission_success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <h4 class="alert-heading"><i class="bi bi-check-circle-fill me-2"></i>Application Submitted Successfully!</h4>
                <p><?php echo htmlspecialchars($submission_message); ?></p>
                <hr>
                <p class="mb-0">
                    <strong>What happens next:</strong><br>
                    • Our team will review your application<br>
                    • You'll receive email updates on your status<br>
                    • Continue using Birthday.Gold while we review<br>
                    • Check back here to see your verification status
                </p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php else: ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Submission Error</h4>
                <p><?php echo htmlspecialchars($submission_message); ?></p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="verification-header">
        <h2>Account Verification</h2>
        <?php if (!$submission_success && empty($existing_application)): ?>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#verificationModal" <?php echo !$canApplyForVerification ? 'disabled' : ''; ?>>
            Apply for Verification
        </button>
        <?php endif; ?>
    </div>

    <div class="content-panel">
        
        <?php if (!empty($existing_application)): ?>
            <?php 
            $status = $existing_application['status'] ?? 'pending';
            $submitted_date = date('F j, Y', strtotime($existing_application['created_at']));
            ?>
            
            <?php if ($status === 'pending' || $status === 'under_review'): ?>
                <!-- Application Under Review -->
                <div class="alert alert-warning d-flex align-items-start" role="alert">
                    <i class="bi bi-hourglass-split fs-1 me-3"></i>
                    <div class="flex-grow-1">
                        <h4 class="alert-heading">Verification Application Under Review</h4>
                        <p>Great news! Your verification application has been received and is currently being reviewed by our team.</p>
                        
                        <div class="mt-3 p-3 bg-white rounded">
                            <h6 class="text-dark mb-3">Application Details:</h6>
                            <ul class="list-unstyled mb-0">
                                <li><i class="bi bi-calendar-check text-success me-2"></i><strong>Submitted:</strong> <?php echo $submitted_date; ?></li>
                                <li><i class="bi bi-clock-history text-info me-2"></i><strong>Status:</strong> Under Review</li>
                                <li><i class="bi bi-timer text-warning me-2"></i><strong>Expected Review Time:</strong> 3-5 business days</li>
                            </ul>
                        </div>
                        
                        <hr>
                        <p class="mb-0 small">
                            <strong>What happens next:</strong> Our verification team is carefully reviewing your application. You'll receive an email notification once the review is complete. In the meantime, continue enjoying Birthday.Gold and earning rewards!
                        </p>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="mt-4">
                    <h5 class="mb-3">Verification Progress</h5>
                    <div class="progress" style="height: 30px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 65%">
                            <span class="fw-bold">Review in Progress</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-muted">Application Submitted</small>
                        <small class="text-muted">Under Review</small>
                        <small class="text-muted">Decision</small>
                    </div>
                </div>
                
            <?php elseif ($status === 'approved' || $status === 'verified'): ?>
                <!-- Verified Status -->
                <div class="alert alert-success d-flex align-items-start" role="alert">
                    <i class="bi bi-patch-check-fill fs-1 me-3"></i>
                    <div>
                        <h4 class="alert-heading">Congratulations! You're Verified!</h4>
                        <p>Your account has been successfully verified. You now have access to exclusive verified member benefits.</p>
                        <hr>
                        <p class="mb-0">Your verified badge is now visible on your profile, and you've received your 20 enrollment allocations!</p>
                    </div>
                </div>
                
            <?php elseif ($status === 'rejected'): ?>
                <!-- Rejected Status -->
                <div class="alert alert-danger d-flex align-items-start" role="alert">
                    <i class="bi bi-x-circle-fill fs-1 me-3"></i>
                    <div>
                        <h4 class="alert-heading">Verification Not Approved</h4>
                        <p>Unfortunately, we couldn't verify your account at this time. Please ensure all requirements are met and try again.</p>
                        <hr>
                        <p class="mb-0">If you have questions, please contact our support team for assistance.</p>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- No Application Yet -->
            <p class="lead">Join our trusted community and unlock exclusive rewards by verifying your account. A verified badge shows that you're an authentic member of Birthday.Gold.</p>

            <!-- Reward Callout -->
            <div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="bi bi-patch-check-fill fs-1 me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1">Earn 20 Enrollment Allocations!</h5>
                    <p class="mb-0">Get verified and receive 20 allocations immediately - our biggest reward for account verification.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Progress Section -->
        <div class="progress-section">
            <div class="progress-header">
                <h2 class="section-title mb-0">Pre-Verification Checklist</h2>
                <span class="progress-percentage fs-8">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#statusModal" class="text-decoration-none text-warning d-inline-flex align-items-center">
                        Verification Readiness: <?php echo $progressPercentage; ?>%
                        <i class="bi bi-info-circle ms-2" style="font-size: 1rem;"></i>
                    </a>
                </span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $progressPercentage; ?>%"></div>
            </div>
            <div class="checklist">
                <!-- Profile Details Check -->
                <div class="checklist-item hanging-indent">
                    <div class="checklist-icon <?php echo $profileComplete ? 'completed' : 'pending'; ?>">
                        <i class="bi bi-<?php echo $profileComplete ? 'check' : 'circle'; ?>"></i>
                    </div>
                    <div class="checklist-content">
                        <div class="title-and-button">
                            <div>
                                <div class="checklist-title">Profile Details</div>
                                <div class="checklist-description">
                                    <?php echo $profileComplete 
                                        ? 'Your profile is complete with your full name, phone number, and email address. Thank you!'
                                        : 'Please complete your profile by adding your full name, phone number, and a valid email address.'; ?>
                                </div>
                            </div>
                            <?php if (!$profileComplete): ?>
                                <a href="/myaccount/profile" class="btn btn-sm btn-danger">Complete this task</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Profile Picture Check -->
                <div class="checklist-item hanging-indent">
                    <div class="checklist-icon <?php echo $hasAvatar ? 'completed' : 'pending'; ?>">
                        <i class="bi bi-<?php echo $hasAvatar ? 'check' : 'circle'; ?>"></i>
                    </div>
                    <div class="checklist-content">
                        <div class="title-and-button">
                            <div>
                                <div class="checklist-title">Profile Picture</div>
                                <div class="checklist-description">
                                    <?php echo $hasAvatar 
                                        ? 'You have successfully uploaded a profile picture. Looking good!'
                                        : 'Please upload a profile picture to complete your verification.'; ?>
                                </div>
                            </div>
                            <?php if (!$hasAvatar): ?>
                                <a href="/myaccount/profile-pic" class="btn btn-sm btn-danger">Complete this task</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Identity Verification Check -->
                <div class="checklist-item hanging-indent">
                    <div class="checklist-icon <?php echo $hasVerification ? 'completed' : 'pending'; ?>">
                        <i class="bi bi-<?php echo $hasVerification ? 'check' : 'circle'; ?>"></i>
                    </div>
                    <div class="checklist-content">
                        <div class="title-and-button">
                            <div>
                                <div class="checklist-title">Authenticity</div>
                                <div class="checklist-description">
                                    <?php echo $hasVerification 
                                        ? 'Your identity has been successfully verified with your government-issued ID. Thank you!'
                                        : 'You need to provide valid identification to verify your identity, such as a government-issued ID (passport, driver\'s license, etc.).'; ?>
                                </div>
                            </div>
                            <?php if (!$hasVerification): ?>
                                <a href="/myaccount/profile-verificationid" class="btn btn-sm btn-danger">Complete this task</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Community Activity Check -->
                <div class="checklist-item hanging-indent">
                    <div class="checklist-icon <?php echo $hasEnoughPosts ? 'completed' : 'pending'; ?>">
                        <i class="bi bi-<?php echo $hasEnoughPosts ? 'check' : 'circle'; ?>"></i>
                    </div>
                    <div class="checklist-content">
                        <div class="title-and-button">
                            <div>
                                <div class="checklist-title">Activity</div>
                                <div class="checklist-description">
                                    <?php echo $hasEnoughPosts 
                                        ? "You're an active member of our community with {$postCount} engaging posts. Keep it up!"
                                        : "You need to be more active in our community. Currently you have ".$qik->plural2($postCount, 'post')." - make at least 5 engaging posts to complete this requirement."; ?>
                                </div>
                            </div>
                            <?php if (!$hasEnoughPosts): ?>
                                <a href="/social" class="btn btn-sm btn-danger">Complete this task</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div style="text-align: center; margin-top: 2rem;">
                <?php if ($canApplyForVerification): ?>
                    <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#verificationModal">
                        <span>Apply for Verification</span>
                        <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                <?php else: ?>
                    <p style="font-size: 1rem; color: #718096;">Complete all requirements above to apply for verification</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Benefits Section -->
        <section class="section">
            <h2 class="section-title">Benefits of Being Verified</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon bg-warning">
                        <i class="bi bi-gift-fill"></i>
                    </div>
                    <h3 class="benefit-title">Exclusive Birthday Rewards</h3>
                    <p class="benefit-description">Get priority access to premium birthday rewards, promotions, and special events from our partner brands</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon bg-info">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h3 class="benefit-title">Enhanced Trust & Visibility</h3>
                    <p class="benefit-description">A verified badge builds credibility and makes your profile appear more prominently to brands</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon bg-success">
                        <i class="bi bi-coin"></i>
                    </div>
                    <h3 class="benefit-title">20 Enrollment Allocations</h3>
                    <p class="benefit-description">Receive 20 enrollment allocations immediately upon verification to unlock more birthday rewards</p>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="faq-section">
            <h2 class="faq-header">Frequently Asked Questions</h2>
            <div class="faq-item">
                <h3 class="faq-question">How to Apply for Verification</h3>
                <p class="faq-answer">If you believe you qualify for verification, you can apply through the form by clicking the "Apply for Verification" button above. The application process will require you to submit personal information such as your full name, email address, and a government-issued photo ID for verification purposes.</p>
            </div>
            <div class="faq-item">
                <h3 class="faq-question">What Happens After Verification?</h3>
                <p class="faq-answer">After your account is verified, a special badge will be displayed on your profile, indicating that you are an authentic user. This badge signals to brands and other users that you have been vetted by Birthday.Gold and are eligible for premium offers and rewards.</p>
            </div>
            <div class="faq-item">
                <h3 class="faq-question">Maintaining Your Verified Status</h3>
                <p class="faq-answer">To maintain your verified status, you should continue to follow our platform's terms of service and guidelines. Birthday.Gold reserves the right to remove verification from accounts that engage in suspicious activity or violate our policies.</p>
            </div>
            <div class="faq-item">
                <h3 class="faq-question">How long does the verification process take?</h3>
                <p class="faq-answer">We typically process verification applications within a few business days. You will receive an email notification once your application has been reviewed.</p>
            </div>
            <div class="faq-item">
                <h3 class="faq-question">Can I lose my verification status?</h3>
                <p class="faq-answer">Yes, accounts that engage in suspicious behavior or violate Birthday.Gold's guidelines may have their verification revoked.</p>
            </div>
            <div class="faq-item">
                <h3 class="faq-question">What information is required for verification?</h3>
                <p class="faq-answer">We require basic identification such as your full name, email address, and a government-issued photo ID. This helps us confirm your identity and protect the integrity of our platform.</p>
            </div>
        </section>
    </div>
</div>

<?PHP
// Modal Form
echo '
 <!-- Modal Form -->
<div class="modal modal-lg fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="verificationModalLabel">Apply for Verification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-5">
                <form action="/myaccount/manage-verification" method="POST" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <label for="name" class="col-sm-3 col-form-label text-end">Full Name:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="name" name="name"  value="'.$current_user_data['first_name'].' '.$current_user_data['last_name'].'"  required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="email" class="col-sm-3 col-form-label text-end">Email Address:</label>
                        <div class="col-sm-9">
                            <input type="email" class="form-control" id="email" name="email" value="'.$current_user_data['email'].'" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="phone" class="col-sm-3 col-form-label text-end">Phone Number:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="phone" name="phone"  value="'.$current_user_data['phone_number'].'" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="address" class="col-sm-3 col-form-label text-end">Home Address:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="address" name="address"  value="'.$current_user_data['mailing_address'].'"  required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="social_profile" class="col-sm-3 col-form-label text-end">Social Media Profile:</label>
                        <div class="col-sm-9">
                            <input type="url" class="form-control" id="social_profile" name="social_profile" placeholder="https://your-social-media.com/profile" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="photo_id" class="col-sm-3 col-form-label text-end">Upload Photo ID:</label>
                        <div class="col-sm-9">
                            <input type="file" class="form-control" id="photo_id" name="photo_id" accept="image/*" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-9 offset-sm-3">
                            <button type="submit" class="btn btn-primary">Submit Application</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
';

// Get verification status - this would normally come from database
// For now, simulating different statuses based on progress
$verificationStatus = 'not_applied'; // Possible values: not_applied, under_review, approved, denied, pending_resubmission
$reviewStartDate = null;
$reviewNotes = '';

// Simulate status based on current state
if ($canApplyForVerification && isset($_SESSION['verification_submitted'])) {
    $verificationStatus = 'under_review';
    $reviewStartDate = date('F j, Y', strtotime('-2 days'));
} elseif ($account->isverified()) {
    $verificationStatus = 'approved';
}

// Dynamic Status Modal
echo '
<!-- Dynamic Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="statusModalLabel">
                    <i class="bi bi-patch-check me-2"></i>Verification Status
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">';

// Different content based on verification status
switch ($verificationStatus) {
    case 'under_review':
        echo '
                <div class="text-center mb-4">
                    <div class="spinner-border text-warning mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h4 class="text-warning">Application Under Review</h4>
                    <p class="text-muted">Submitted on ' . $reviewStartDate . '</p>
                </div>
                
                <div class="alert alert-info" role="alert">
                    <h6 class="alert-heading"><i class="bi bi-clock-history me-2"></i>Your verification is being processed</h6>
                    <p class="mb-2">Our team is currently reviewing your verification application. This process typically takes 3-5 business days.</p>
                    <hr>
                    <p class="mb-0 small">You will receive an email notification once the review is complete. In the meantime, continue using Birthday Gold and earning rewards!</p>
                </div>
                
                <div class="progress mb-3" style="height: 30px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 65%">
                        <span class="fw-bold">Review in Progress</span>
                    </div>
                </div>
                
                <h6 class="mb-3">What happens next?</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Application received and queued for review</li>
                    <li class="mb-2"><i class="bi bi-arrow-right-circle text-warning me-2"></i>Identity verification in progress</li>
                    <li class="mb-2"><i class="bi bi-circle text-muted me-2"></i>Final approval pending</li>
                    <li class="mb-2"><i class="bi bi-circle text-muted me-2"></i>Email notification upon completion</li>
                </ul>';
        break;
        
    case 'approved':
        echo '
                <div class="text-center mb-4">
                    <i class="bi bi-patch-check-fill text-success" style="font-size: 5rem;"></i>
                    <h4 class="text-success mt-3">Congratulations! You are Verified</h4>
                    <p class="text-muted">Approved on ' . date('F j, Y', strtotime('-1 week')) . '</p>
                </div>
                
                <div class="alert alert-success" role="alert">
                    <h6 class="alert-heading"><i class="bi bi-check-circle-fill me-2"></i>Your account has been verified!</h6>
                    <p class="mb-2">You now have access to exclusive verified member benefits, including premium birthday rewards and priority support.</p>
                    <hr>
                    <p class="mb-0 small">Your verified badge is now visible on your profile. Thank you for being a trusted member of Birthday Gold!</p>
                </div>
                
                <h6 class="mb-3">Your Verified Benefits:</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-gift-fill text-warning me-3 fs-4"></i>
                            <div>
                                <h6 class="mb-1">Premium Rewards</h6>
                                <p class="text-muted small mb-0">Access to exclusive birthday offers</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-lightning-fill text-info me-3 fs-4"></i>
                            <div>
                                <h6 class="mb-1">Priority Support</h6>
                                <p class="text-muted small mb-0">Get help faster with dedicated support</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-patch-check-fill text-success me-3 fs-4"></i>
                            <div>
                                <h6 class="mb-1">Verified Badge</h6>
                                <p class="text-muted small mb-0">Show your trusted status to brands</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-coin text-primary me-3 fs-4"></i>
                            <div>
                                <h6 class="mb-1">100 Bonus Credits</h6>
                                <p class="text-muted small mb-0">Already added to your account</p>
                            </div>
                        </div>
                    </div>
                </div>';
        break;
        
    case 'denied':
        echo '
                <div class="text-center mb-4">
                    <i class="bi bi-x-circle-fill text-danger" style="font-size: 5rem;"></i>
                    <h4 class="text-danger mt-3">Verification Not Approved</h4>
                    <p class="text-muted">Reviewed on ' . date('F j, Y', strtotime('-3 days')) . '</p>
                </div>
                
                <div class="alert alert-danger" role="alert">
                    <h6 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>We could not verify your account</h6>
                    <p class="mb-2">Unfortunately, we were unable to approve your verification request at this time.</p>
                    <hr>
                    <p class="mb-0 small">Please review the requirements below and resubmit your application with the requested information.</p>
                </div>
                
                <h6 class="mb-3">Reason for denial:</h6>
                <div class="bg-light p-3 rounded mb-3">
                    <p class="mb-0"><i class="bi bi-info-circle me-2"></i>The identification document provided was unclear or did not match the profile information. Please ensure your ID is clearly visible and matches your account details.</p>
                </div>
                
                <h6 class="mb-3">Next steps:</h6>
                <ol>
                    <li class="mb-2">Update your profile information to match your government ID</li>
                    <li class="mb-2">Take a clear photo of your ID (ensure all text is readable)</li>
                    <li class="mb-2">Resubmit your verification application</li>
                </ol>
                
                <div class="text-center mt-4">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#verificationModal">
                        <i class="bi bi-arrow-repeat me-2"></i>Reapply for Verification
                    </button>
                </div>';
        break;
        
    default: // not_applied
        echo '
                <div class="text-center mb-4">
                    <i class="bi bi-person-badge" style="font-size: 5rem; color: #6c757d;"></i>
                    <h4 class="mt-3">Verification Status: Not Applied</h4>
                    <p class="text-muted">You have not yet submitted a verification application</p>
                </div>
                
                <div class="alert alert-secondary" role="alert">
                    <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Ready to get verified?</h6>
                    <p class="mb-2">Complete the requirements below to unlock the ability to apply for account verification.</p>
                    <hr>
                    <p class="mb-0 small">Verified members enjoy exclusive benefits including premium rewards, priority support, and increased trust from partner brands.</p>
                </div>
                
                <h6 class="mb-3">Current Progress: ' . $progressPercentage . '% Complete</h6>
                <div class="progress mb-4" style="height: 25px;">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: ' . $progressPercentage . '%">
                        ' . $completedRequirements . ' of 4 requirements
                    </div>
                </div>
                
                <h6 class="mb-3">Requirements checklist:</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="bi bi-' . ($profileComplete ? 'check-circle-fill text-success' : 'circle text-muted') . ' me-2"></i>
                        Complete your profile information
                        ' . (!$profileComplete ? '<a href="/myaccount/profile" class="ms-2 small">Complete now</a>' : '') . '
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-' . ($hasAvatar ? 'check-circle-fill text-success' : 'circle text-muted') . ' me-2"></i>
                        Upload a profile picture
                        ' . (!$hasAvatar ? '<a href="/myaccount/profile-pic" class="ms-2 small">Upload now</a>' : '') . '
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-' . ($hasVerification ? 'check-circle-fill text-success' : 'circle text-muted') . ' me-2"></i>
                        Verify your identity with ID
                        ' . (!$hasVerification ? '<a href="/myaccount/profile-verificationid" class="ms-2 small">Verify now</a>' : '') . '
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-' . ($hasEnoughPosts ? 'check-circle-fill text-success' : 'circle text-muted') . ' me-2"></i>
                        Be active in the community (5+ posts)
                        ' . (!$hasEnoughPosts ? '<a href="/social" class="ms-2 small">Create posts</a>' : '') . '
                    </li>
                </ul>';
                
        if ($canApplyForVerification) {
            echo '
                <div class="text-center mt-4">
                    <button type="button" class="btn btn-success btn-lg" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#verificationModal">
                        <i class="bi bi-patch-check me-2"></i>Apply for Verification Now
                    </button>
                </div>';
        }
        break;
}

echo '
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Additional styles for the status modal */
.timeline-icon {
    transition: all 0.3s ease;
}

.timeline-container {
    position: relative;
    padding-left: 20px;
}

.timeline-container::before {
    content: "";
    position: absolute;
    left: 20px;
    top: 50px;
    bottom: 20px;
    width: 2px;
    background: #e9ecef;
}

/* Make the percentage clickable and prominent */
.progress-percentage a {
    transition: all 0.2s ease;
}

.progress-percentage a:hover {
    text-decoration: none !important;
    transform: scale(1.05);
    filter: brightness(1.2);
}

.progress-percentage a i {
    transition: transform 0.2s ease;
}

.progress-percentage a:hover i {
    transform: scale(1.2);
}
</style>
';
?>

<?php
$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();