<?php

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Include any necessary headers, session classes, or configuration files



#-------------------------------------------------------------------------------
# HANDLE FORM ACTIONS
#-------------------------------------------------------------------------------
// Processing form submission
if ($app->formposted()) {
    // Grab form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $social_profile = $_POST['social_profile'];
    $photo_id = $_FILES['photo_id'];

    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($social_profile) || empty($photo_id)) {
        echo '<div class="alert alert-danger">All fields are required. Please fill in all information.</div>';
    } else {
        // File upload logic for the photo ID
        $target_dir = "/uploads/photo_id/";
        $target_file = $target_dir . basename($photo_id["name"]);
        if (move_uploaded_file($photo_id["tmp_name"], $target_file)) {
            // Save form data into the database
            $database->query('INSERT INTO bg_verified_accounts (name, email, phone, address, social_profile, photo_id_path) VALUES (?, ?, ?, ?, ?, ?)', 
                [$name, $email, $phone, $address, $social_profile, $target_file]);

            echo '<div class="alert alert-success">Your application has been submitted. We will review your request.</div>';
        } else {
            echo '<div class="alert alert-danger">Error uploading your photo ID. Please try again.</div>';
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
    <div class="verification-header">
        <h2>Account Verification</h2>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#verificationModal" <?php echo !$canApplyForVerification ? 'disabled' : ''; ?>>
            Apply for Verification
        </button>
    </div>

    <div class="content-panel">
        <p class="lead">Join our trusted community and unlock exclusive rewards by verifying your account. A verified badge shows that you're an authentic member of Birthday.Gold.</p>

        <!-- Progress Section -->
        <div class="progress-section">
            <div class="progress-header">
                <h2 class="section-title mb-0">Pre-Verification Checklist</h2>
                <span class="progress-percentage fs-8">Verification Readiness: <?php echo $progressPercentage; ?>%</span>
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
                    <h3 class="benefit-title">100 Enrollment Credits</h3>
                    <p class="benefit-description">Receive 100 enrollment credits to get started with even more birthday rewards</p>
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
                <form action="/path-to-verification-form-handler.php" method="POST" enctype="multipart/form-data">
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
?>

<?php
$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();