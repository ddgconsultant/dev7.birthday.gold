<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$errormessage = '';

$autologin_days_length = 45;

$userId = $current_user_data['user_id'];

// Prep variables for subject and body templates
$subjectTemplate = 'An invite message for you from {{name}} and Birthday.Gold';

$bodyTemplate = 'Hello,<br><br>' .
                'You\'ve been invited by {{name}} to join Birthday.Gold, the platform that celebrates YOU on your special day! 🎉<br><br>' .
                '<strong>Details of the Invitation:</strong><br>' .
                'Inviter Name: {{name}}<br>' .
                'Inviter Email: {{email}}<br>' .
                'Their Relationship to You: {{relationship}}<br><br>' .
                'At Birthday.Gold, you can receive amazing freebies and rewards from your favorite brands on your birthday! Don\'t miss out on the fun and the gifts waiting for you.<br><br>' .
                '<a href="https://birthday.gold/invitedby?{{referralcode}}" style="color: #fff; background-color: #007bff; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Join Now</a><br><br>' .
                'We look forward to celebrating with you! 🎁<br><br>' .
                'Cheers,<br>' .
                'The Birthday.Gold Team';

function replacePlaceholders($template, $data) {
    foreach ($data as $key => $value) {
        $template = str_replace('{{' . $key . '}}', htmlspecialchars($value), $template);
    }
    return $template;
}

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted() && isset($_REQUEST['referral_code'])) {
    $newReferralCode = $_REQUEST['referral_code'];
    $referralcode = $account->manageReferralCode($current_user_data, 'update', $newReferralCode);

    $message = '<div class="alert alert-success" role="alert"> Referral code updated successfully to ' . htmlspecialchars($referralcode['code']) . '!</div>';

    $transferpage['message'] = $message;
    $transferpage['url'] = '/myaccount/invite';
    $system->endpostpage($transferpage);
    exit;
}

$referralcode = $account->manageReferralCode();

if ($app->formposted() && !empty($_REQUEST['email']) && !empty($_REQUEST['name']) && !empty($_REQUEST['relationship'])) {
    $email = $_REQUEST['email'];
    $name = $_REQUEST['name'];
    $relationship = $_REQUEST['relationship'];
    $description = "Relationship: " . $relationship . ", Email: " . $email;

    $sql = "INSERT INTO `bg_user_attributes` (
        `user_id`, `type`, `name`, `description`, `status`, 
        `create_dt`, `modify_dt`, `rank`, `grouping`, `category`, string_value
    ) VALUES (
        :user_id, 'friend_invite', :name, :description, 'pending', 
        now(), now(), '100', 'invite_form', 'friend_invite', :email
    )";

    $params = [
        ':user_id' => $userId,
        ':name' => $name,
        ':description' => $description,
        ':email' => $email,
    ];

    $stmt = $database->prepare($sql);
    $stmt->execute($params);

    $templateData = [
        'name' => $name,
        'email' => $email,
        'relationship' => $relationship,
        'referralcode' => $referralcode['code']
    ];

    $subject = replacePlaceholders($subjectTemplate, $templateData);
    $body = replacePlaceholders($bodyTemplate, $templateData);

    $messageinput = [
        'from' => [$email, $name],
        'to' => 'CS birthday.gold',
        'toemail' => 'cs@birthday.gold',
        'subject' => $subject,
        'body' => $body,
        'notification' => strip_tags($body)
    ];

    $result = $mail->sendoutsidemessage($messageinput);

    $pagemessage = $result
        ? '<div class="alert alert-success mt-3"> Invitation successfully sent to ' . htmlspecialchars($name) . ' (' . htmlspecialchars($email) . ').</div>'
        : '<div class="alert alert-danger mt-3"> Failed to send the invitation email to ' . htmlspecialchars($name) . ' (' . htmlspecialchars($email) . ').</div>';

    $transferpage['message'] = $pagemessage;
    $transferpage['url'] = '/myaccount/invite';
    $system->endpostpage($transferpage);
    exit;
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);

$additionalstyles = '
<style>
/* Hero Invite Page Styles - Matching Login Design */
* {
    box-sizing: border-box !important;
}

/* Main wrapper */
.invite-wrapper {
    width: 100%;
    max-width: 1200px;
    display: grid;
    grid-template-columns: 1fr 500px;
    gap: 4rem;
    align-items: center;
    padding: 0 2rem;
    margin: 0 auto;
}

/* Welcome content for desktop */
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

/* Card Container */
.invite-container {
    width: 100%;
    max-width: 480px;
    margin: 2rem auto;
}

.invite-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

/* Header Section */
.invite-header {
    text-align: center;
    padding: 2rem 1.5rem 1rem;
}

.invite-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.5rem;
}

.invite-header p {
    font-size: 1rem;
    color: #6c757d;
    margin: 0;
}

/* Badge */
.invite-badge {
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

.invite-badge i {
    font-size: 1rem;
}

/* Form Section */
.invite-body {
    padding: 0 1.5rem 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

/* Input Fields */
.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    transition: all 0.2s ease;
    background: white;
    color: #212529;
}

.form-control:focus {
    outline: none;
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.form-control::placeholder {
    color: #adb5bd;
}

/* Preview Button */
.btn-preview {
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

.btn-preview:hover:not(:disabled) {
    background: #0b5ed7;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
}

.btn-preview:active {
    transform: translateY(0);
}

/* Referral Code Section */
.referral-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-top: 2rem;
    text-align: center;
}

.referral-section h3 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.referral-code {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--bs-primary);
    margin-bottom: 1rem;
    font-family: monospace;
}

.referral-section p {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0;
}

/* Responsive */
@media (max-width: 992px) {
    .invite-wrapper {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .welcome-content {
        display: none;
    }
    
    .invite-container {
        margin: 0 auto;
    }
}

@media (max-width: 768px) {
    .invite-container {
        max-width: 100%;
        margin: 1rem auto;
    }
    
    .invite-header {
        padding: 1.5rem 1rem 0.75rem;
    }
    
    .invite-header h1 {
        font-size: 1.5rem;
    }
    
    .invite-body {
        padding: 0 1rem 1.5rem;
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="main-content">
    <!-- Desktop wrapper for side-by-side layout -->
    <div class="invite-wrapper">
        <!-- Welcome content - Desktop only -->
        <div class="welcome-content d-none d-lg-block">
            <h2>Share the <span>Birthday Love</span></h2>
            <p>Invite your friends and family to join Birthday.Gold and help them never miss their special birthday rewards!</p>
            
            <div class="feature-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Grow Together</h3>
                        <p>Build your birthday celebration community</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-gift-fill"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Share Rewards</h3>
                        <p>Help friends discover amazing birthday deals</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-envelope-heart-fill"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Personal Touch</h3>
                        <p>Send personalized invitations they will love</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-link-45deg"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Easy Sharing</h3>
                        <p>Your unique referral link makes it simple</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Invite Card -->
        <div class="invite-container mb-md-5">
            <div class="invite-card">
                <!-- Header Section -->
                <div class="invite-header">
                    <div class="invite-badge">
                        <i class="bi bi-envelope-plus-fill"></i>
                        <span>Send Invitation</span>
                    </div>
                    <h1>Invite a Friend</h1>
                    <p>Fill in their details to send a personalized invitation</p>
                </div>
                
                <!-- Form Section -->
                <div class="invite-body">
                    <?php if (!empty($transferpagedata['message'])): ?>
                        <div class="alert-container">
                            <?php echo $transferpagedata['message']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="/myaccount/invite" id="inviteForm">
                        <?php echo $display->inputcsrf_token(); ?>
                        
                        <div class="form-group">
                            <label class="form-label" for="name">Their Name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter friend's name" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="relationship">Relationship</label>
                            <select class="form-control" id="relationship" name="relationship" required>
                                <?php echo $display->list_relationships(); ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Their Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="friend@example.com" required>
                        </div>
                        
                        <button type="button" class="btn-preview" data-bs-toggle="modal" data-bs-target="#previewModal">
                            Preview Invitation
                        </button>
                    </form>
                    
                    <!-- Referral Code Section -->
                    <div class="referral-section">
                        <h3>Your Referral Code</h3>
                        <div class="referral-code"><?php echo htmlspecialchars($referralcode['code']); ?></div>
                        <p>Share this code with friends for direct sign-up</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Preview Your Invitation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6><strong>Subject:</strong> <span id="previewSubject"></span></h6>
                <hr>
                <div id="previewBody"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" form="inviteForm">Send Invitation</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const previewButton = document.querySelector('[data-bs-target="#previewModal"]');
    const form = document.querySelector('#inviteForm');
    
    previewButton.addEventListener("click", () => {
        const name = form.querySelector('#name').value;
        const relationship = form.querySelector('#relationship').value;
        const email = form.querySelector('#email').value;
        
        if (!name || !relationship || !email) {
            alert('Please fill in all fields before previewing');
            return;
        }
        
        document.getElementById('previewSubject').textContent = `An invite message for you from ${name} and Birthday.Gold`;
        document.getElementById('previewBody').innerHTML = `
            Hello,<br><br>
            You've been invited by ${name} to join Birthday.Gold, the platform that celebrates YOU on your special day! 🎉<br><br>
            <strong>Details of the Invitation:</strong><br>
            Inviter Name: ${name}<br>
            Inviter Email: ${email}<br>
            Their Relationship to You: ${relationship}<br><br>
            At Birthday.Gold, you can receive amazing freebies and rewards from your favorite brands on your birthday! Don't miss out on the fun and the gifts waiting for you.<br><br>
            <a href="https://birthday.gold/invitedby?<?php echo $referralcode["code"]; ?>" style="color: #fff; background-color: #007bff; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Join Now</a><br><br>
            We look forward to celebrating with you! 🎁<br><br>
            Cheers,<br>
            The Birthday.Gold Team
        `;
    });
});
</script>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>