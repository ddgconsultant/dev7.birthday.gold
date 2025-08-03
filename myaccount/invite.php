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
/* Referral Code Section */
.referral-section {
    background: transparent;
    padding: 1rem 0;
    text-align: center;
}

.referral-code {
    font-size: 2rem;
    font-weight: 700;
    color: var(--bs-primary);
    font-family: monospace;
    letter-spacing: 3px;
    user-select: all;
    cursor: pointer;
}

.referral-section p {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0;
    margin-top: 1rem;
}

/* Copy button hover effect */
.btn-outline-primary:hover .bi-clipboard {
    transform: scale(1.1);
    transition: transform 0.2s ease;
}

/* Feature icons */
.feature-icon {
    font-size: 3rem;
    color: var(--bs-primary);
    margin-bottom: 1rem;
}

/* Form styling */
.form-floating label {
    color: #6c757d;
}

.form-floating > .form-control:focus ~ label,
.form-floating > .form-control:not(:placeholder-shown) ~ label {
    color: var(--bs-primary);
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="mb-3"><i class="bi bi-envelope-heart me-3"></i>Invite Friends</h1>
                <p class="lead mb-0">Share the Birthday Love - Help friends discover amazing birthday rewards!</p>
            </div>
            <div class="col-auto">
                <a href="/myaccount/friends-list" class="btn btn-outline-light">
                    <i class="bi bi-people me-2"></i>View Friends List
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            
            <?php if (!empty($transferpagedata['message'])): ?>
                <?php echo $transferpagedata['message']; ?>
            <?php endif; ?>
            
            <!-- Main Invite Card -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Send an Invitation</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/myaccount/invite" id="inviteForm">
                        <?php echo $display->inputcsrf_token(); ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="name" name="name" placeholder="Friend's Name" required>
                                    <label for="name">Their Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" id="relationship" name="relationship" required>
                                        <?php echo $display->list_relationships(); ?>
                                    </select>
                                    <label for="relationship">Relationship</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" placeholder="friend@example.com" required>
                                <label for="email">Their Email Address</label>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#previewModal">
                                <i class="bi bi-eye me-2"></i>Preview Invitation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Referral Code Card -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Your Referral Code</h5>
                </div>
                <div class="card-body text-center">
                    <div class="referral-section">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="referral-code me-3" id="referralCode"><?php echo htmlspecialchars($referralcode['code']); ?></div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyReferralCode()" title="Copy code">
                                <i class="bi bi-clipboard" id="copyIcon"></i>
                            </button>
                        </div>
                        <p>Share this code with friends for direct sign-up</p>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Friends who use your code will get special benefits when they sign up!
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Why Invite Section -->
            <div class="row text-center">
                <div class="col-md-3 col-6 mb-4">
                    <div class="feature-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h6>Grow Together</h6>
                    <p class="small text-muted">Build your birthday celebration community</p>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="feature-icon">
                        <i class="bi bi-gift-fill"></i>
                    </div>
                    <h6>Share Rewards</h6>
                    <p class="small text-muted">Help friends discover birthday deals</p>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="feature-icon">
                        <i class="bi bi-envelope-heart-fill"></i>
                    </div>
                    <h6>Personal Touch</h6>
                    <p class="small text-muted">Send personalized invitations</p>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="feature-icon">
                        <i class="bi bi-link-45deg"></i>
                    </div>
                    <h6>Easy Sharing</h6>
                    <p class="small text-muted">Your unique link makes it simple</p>
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
// Copy referral code function
function copyReferralCode() {
    const referralCode = document.getElementById('referralCode').textContent;
    const copyIcon = document.getElementById('copyIcon');
    
    // Copy to clipboard
    navigator.clipboard.writeText(referralCode).then(() => {
        // Change icon to checkmark
        copyIcon.classList.remove('bi-clipboard');
        copyIcon.classList.add('bi-check-lg');
        
        // Show success message (optional)
        const button = copyIcon.parentElement;
        button.classList.remove('btn-outline-primary');
        button.classList.add('btn-success');
        
        // Reset after 2 seconds
        setTimeout(() => {
            copyIcon.classList.remove('bi-check-lg');
            copyIcon.classList.add('bi-clipboard');
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-primary');
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy: ', err);
        alert('Failed to copy code. Please select and copy manually.');
    });
}

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