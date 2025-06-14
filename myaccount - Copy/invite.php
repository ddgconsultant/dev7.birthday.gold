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

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container main-content mt-0 pt-0" data-layout="container">
    <div class="content">
        <?php echo $display->formaterrormessage($transferpagedata['message']); ?>
        <h2 class="mb-3">Invite Your Friends</h2>
        <div class="card bg-success-subtle">
            <div class="card-body text-center py-5">
                <form class="row justify-content-center" action="/myaccount/invite" method="POST">
                    <?php echo $display->inputcsrf_token(); ?>
                    <div class="col-12 col-md-10 row g-2 align-items-center">
                        <label for="name" class="col-sm-4 col-form-label text-start">Their Name:</label>
                        <div class="col-sm-8">
                            <input class="form-control" type="text" id="name" name="name" required />
                        </div>
                    </div>
                    <div class="col-12 col-md-10 row g-2 align-items-center">
                        <label for="relationship" class="col-sm-4 col-form-label text-start">Relationship:</label>
                        <div class="col-sm-8">
                            <select class="form-control" id="relationship" name="relationship" required>
                                <?php echo $display->list_relationships(); ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-10 row g-2 align-items-center">
                        <label for="email" class="col-sm-4 col-form-label text-start">Their Email Address:</label>
                        <div class="col-sm-8">
                            <input class="form-control" type="email" id="email" name="email" required />
                        </div>
                    </div>
                    <div class="col-12 col-md-10 mt-3">
                        <button type="button" class="btn btn-primary px-5" data-bs-toggle="modal" data-bs-target="#previewModal">Preview Invitation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Preview Your Invitation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6><strong>Subject:</strong> <span id="previewSubject"></span></h6>
                <hr>
                <p id="previewBody"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Send Invitation</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const previewButton = document.querySelector('[data-bs-target="#previewModal"]');
        const form = document.querySelector('form[action="/myaccount/invite"]');

        previewButton.addEventListener("click", () => {
            const name = form.querySelector('#name').value;
            const relationship = form.querySelector('#relationship').value;
            const email = form.querySelector('#email').value;

            document.getElementById('previewSubject').textContent = `An invite message for you from ${name} and Birthday.Gold`;
            document.getElementById('previewBody').innerHTML = `
                Hello,<br><br>
                You've been invited by ${name} to join Birthday.Gold!<br><br>
                <strong>Details of the Invitation:</strong><br>
                Inviter Name: ${name}<br>
                Inviter Email: ${email}<br>
                Their Relationship to You: ${relationship}<br><br>
                Join us now and celebrate your special day with amazing rewards!<br><br>
                <a href="https://birthday.gold/invitedby?${'<?php echo $referralcode["code"]; ?>'}">Join Now</a>
            `;
        });
    });
</script>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
