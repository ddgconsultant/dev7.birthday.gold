<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

/**
 * Unsubscribe form.
 *
 * Three modes:
 *   1. ?e=foo@bar.com&t=<hmac>    — link from a newsletter; immediate
 *                                   suppression (source=user, scope=marketing_only).
 *   2. ?c=<hmac>&e=foo@bar.com    — confirmation link from a pending flow;
 *                                   flips the pending row to active.
 *   3. POST from the form with no token — creates a pending row and emails a
 *                                          confirmation link.
 *
 * Now that Mail::sendmail() actually honors unsubscribe_emails, this endpoint
 * became a denial-of-email-service surface: anyone could silently stop mail
 * for any address by POSTing it. The token/confirmation flow prevents that.
 */


#-------------------------------------------------------------------------------
# HELPERS
#-------------------------------------------------------------------------------
/**
 * Derive an HMAC key from server-side secrets. Uses the database admin
 * password (already a server secret in sitesettings) combined with a
 * per-purpose salt so unsubscribe tokens can't be confused with other HMACs.
 */
function unsub_hmac_key() {
    global $sitesettings;
    $seed = $sitesettings['database_admin']['password'] ?? $sitesettings['database_main']['password'] ?? 'birthday-gold-fallback';
    return hash('sha256', 'unsubscribe|' . $seed, true);
}

function unsub_token_make($email, $purpose = 'unsub') {
    $email = strtolower(trim((string)$email));
    $payload = $purpose . '|' . $email;
    $mac = hash_hmac('sha256', $payload, unsub_hmac_key());
    return substr($mac, 0, 40); // truncate — HMAC-SHA256 is overkill
}

function unsub_token_verify($email, $token, $purpose = 'unsub') {
    if (empty($email) || empty($token)) return false;
    $expected = unsub_token_make($email, $purpose);
    return hash_equals($expected, (string)$token);
}

/**
 * Coarse per-IP rate limit — refuse more than N attempts per 15 minutes.
 * Uses the same bg_ip_lockouts style table only if it exists; otherwise
 * falls back to session-based counting. Fail-open on DB error so legit
 * users aren't blocked by schema drift.
 */
function unsub_rate_limited($ip) {
    global $database;
    if (empty($ip)) return false;

    try {
        $row = $database->getrow(
            "SELECT COUNT(*) AS c FROM session_tracking
              WHERE name = 'unsub_attempt'
                AND ip_address = :ip
                AND create_dt > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            ['ip' => $ip]
        );
        if ($row && (int)$row['c'] >= 10) return true;
    } catch (Exception $ex) {
        // Fallback to session counter if session_tracking table isn't shaped
        // the way we expect. (ex: " . $ex->getMessage() . " - intentionally swallowed)
        unset($ex);
        if (!isset($_SESSION['unsub_attempts'])) $_SESSION['unsub_attempts'] = 0;
        if ($_SESSION['unsub_attempts']++ >= 10) return true;
    }
    return false;
}

function unsub_log_attempt($outcome, $email) {
    session_tracking('unsub_attempt', [
        'outcome' => $outcome,
        'email'   => $email,
    ]);
}


#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$email    = strtolower(trim($_REQUEST['e'] ?? ''));
$token    = $_REQUEST['t'] ?? '';
$confirm  = $_REQUEST['c'] ?? '';
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';

$lastId       = null;   // legacy display flag (set when form flow succeeds)
$suppressed   = false;  // set when we actually suppressed an address
$confirmation_sent = false;
$already_active = false;
$pending_email   = null;


#-------------------------------------------------------------------------------
# MODE 2: confirmation link handler (GET)
#-------------------------------------------------------------------------------
if (!empty($confirm) && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if (unsub_token_verify($email, $confirm, 'confirm')) {
        // Flip pending → active. Token is deterministic per email so we don't
        // need to store it server-side.
        $user_row = $database->getrow(
            "SELECT user_id FROM bg_users WHERE LOWER(email) = :email LIMIT 1",
            ['email' => $email]
        );
        $user_id = $user_row ? $user_row['user_id'] : null;

        $sql = "INSERT INTO unsubscribe_emails (email, user_id, source, scope, reason, status, unsubscribed_at)
                VALUES (:email, :user_id, 'user', 'marketing_only', 'confirmed via email link', 'active', NOW())
                ON DUPLICATE KEY UPDATE
                    status    = 'active',
                    source    = 'user',
                    scope     = 'marketing_only',
                    reason    = IFNULL(reason, 'confirmed via email link'),
                    modify_dt = NOW()";
        $database->query($sql, ['email' => $email, 'user_id' => $user_id]);
        $suppressed = true;
        $lastId = 1;
        unsub_log_attempt('confirmed', $email);
    } else {
        unsub_log_attempt('confirm_token_invalid', $email);
    }
}


#-------------------------------------------------------------------------------
# MODE 1: signed unsubscribe link from newsletter (?t=...)
#-------------------------------------------------------------------------------
elseif (!empty($token) && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if (unsub_token_verify($email, $token, 'unsub')) {
        $user_row = $database->getrow(
            "SELECT user_id FROM bg_users WHERE LOWER(email) = :email LIMIT 1",
            ['email' => $email]
        );
        $user_id = $user_row ? $user_row['user_id'] : null;

        $sql = "INSERT INTO unsubscribe_emails (email, user_id, source, scope, reason, status, unsubscribed_at)
                VALUES (:email, :user_id, 'user', 'marketing_only', 'unsubscribe link', 'active', NOW())
                ON DUPLICATE KEY UPDATE
                    status    = 'active',
                    source    = 'user',
                    scope     = 'marketing_only',
                    reason    = IFNULL(reason, 'unsubscribe link'),
                    modify_dt = NOW()";
        $database->query($sql, ['email' => $email, 'user_id' => $user_id]);
        $suppressed = true;
        $lastId = 1;
        unsub_log_attempt('token_link', $email);
    } else {
        unsub_log_attempt('unsub_token_invalid', $email);
    }
}


#-------------------------------------------------------------------------------
# MODE 3: manual form POST — send a confirmation email
#-------------------------------------------------------------------------------
elseif ($formdata = $app->formposted()) {
    $email = strtolower(trim($_REQUEST['email'] ?? $email));

    if (unsub_rate_limited($client_ip)) {
        unsub_log_attempt('rate_limited', $email);
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Store as pending; real suppression only happens after the user
        // clicks the confirmation link we email them. This prevents an
        // attacker from silently stopping mail for arbitrary addresses.
        $user_row = $database->getrow(
            "SELECT user_id, first_name FROM bg_users WHERE LOWER(email) = :email LIMIT 1",
            ['email' => $email]
        );
        $user_id = $user_row ? $user_row['user_id'] : null;

        try {
            $database->query(
                "INSERT INTO unsubscribe_emails (email, user_id, source, scope, reason, status, unsubscribed_at)
                   VALUES (:email, :user_id, 'user', 'marketing_only', 'pending email confirmation', 'pending', NOW())
                 ON DUPLICATE KEY UPDATE
                     status    = IF(status = 'active', 'active', 'pending'),
                     modify_dt = NOW()",
                ['email' => $email, 'user_id' => $user_id]
            );

            // Check whether this row is already 'active' — don't send a
            // pointless confirmation email if they're already suppressed.
            $row = $database->getrow(
                "SELECT status FROM unsubscribe_emails WHERE email = :email LIMIT 1",
                ['email' => $email]
            );
            if ($row && $row['status'] === 'active') {
                $already_active = true;
                $pending_email = $email;
                unsub_log_attempt('already_active', $email);
            } else {
                // Send confirmation email. Mail::sendmail() won't block this
                // since there's no *active* suppression row yet — the row we
                // just wrote is in status='pending'.
                $confirm_token = unsub_token_make($email, 'confirm');
                $confirm_url = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'birthday.gold')
                             . '/unsubscribe?e=' . urlencode($email) . '&c=' . $confirm_token;

                $body = '<p>Hello' . ($user_row ? ', ' . htmlspecialchars($user_row['first_name']) : '') . ',</p>
                         <p>We received a request to unsubscribe <b>' . htmlspecialchars($email) . '</b>
                         from our marketing emails. Click the button below to confirm.</p>
                         <p>' . $mail->emailbutton('Confirm Unsubscribe', $confirm_url) . '</p>
                         <p>If the button doesn\'t work, paste this URL into your browser:</p>
                         <pre>' . htmlspecialchars($confirm_url) . '</pre>
                         <p>If you did not request this, you can safely ignore this email — we won\'t change anything.</p>';

                $mail->sendmail([
                    'to'       => [$email, $user_row ? $user_row['first_name'] : $email],
                    'subject'  => 'Please confirm your birthday.gold unsubscribe',
                    'body'     => $body,
                    // Important: transactional so this email is NOT blocked
                    // by any in-flight 'pending' row we just wrote, and so
                    // it bypasses marketing_only suppressions for addresses
                    // that are already partially opted-out.
                    'category' => 'transactional',
                    'donottrack' => true,
                ]);

                $confirmation_sent = true;
                $pending_email = $email;
                unsub_log_attempt('confirmation_sent', $email);
            }
        } catch (Exception $e) {
            unsub_log_attempt('db_error', $email);
        }
    }
}



    #-------------------------------------------------------------------------------
    # DISPLAY PAGE
    #-------------------------------------------------------------------------------
    $headerattribute['additionalcss'] = '';

    include($dir['core_components'] . '/bg_pagestart.inc');
    include($dir['core_components'] . '/bg_header.inc');
    echo '


    <!-- Unsubscribe Start -->
    <div class="container  main-content ">

        <div class="container py-5 text-center card">
            <div class="row justify-content-center">
                <div class="col">
<picture>
<source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f622/512.webp" type="image/webp">
  <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f622/512.gif" alt="😢" width="64" height="64">
</picture>

';
if ($suppressed) {
    echo '
    <h1 class="display-1">You\'ve Been Unsubscribed</h1>
    <p class="mb-4">We are sorry to see you go!</p>
    <p class="mt-6">Please know that our system sometimes sends messages that are already scheduled to be delivered.</p>
    <p class="">Trust that we will not send you newsletters, announcements, and promotional offers email after 24 hours from now.</p>
';

} elseif ($confirmation_sent) {
    echo '
    <h1 class="display-1">Check Your Email</h1>
    <p class="mb-4">We sent a confirmation link to <b>' . htmlspecialchars($pending_email) . '</b>.</p>
    <p class="">Click the link in that email to finish unsubscribing. If you don\'t see it, check your spam folder.</p>
';

} elseif ($already_active) {
    echo '
    <h1 class="display-1">You\'re Already Unsubscribed</h1>
    <p class="mb-4"><b>' . htmlspecialchars($pending_email) . '</b> is already on our suppression list. No further action is needed.</p>
';

} else {
echo '
                    <h1 class="display-1">Unsubscribe</h1>
                    <h1 class="mb-4">from Our Emails</h1>
                    <p class="mb-4">We are sorry to see you go! If you unsubscribe, you will stop receiving newsletters, announcements, and promotional offers emails from us.</p>
                    <p class="mb-4">If you have an active account on our site, we will still send you account management related message only.</p>
                    <p class="mb-4"><em>We\'ll email you a confirmation link to make sure this is really you.</em></p>
                    <div class="row justify-content-center">
                     <div class="col-6">
                        <form method="post" id="mainform" action="/unsubscribe">
                        ' . $display->inputcsrf_token() . '
                        <div class="mb-3">
                            <label for="emailInput" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="emailInput" name="email" placeholder="Enter your email" required value="' . htmlspecialchars($email) . '">
                        </div>
                        <div class="">
                            <button type="submit" id="mainsubmit" class="btn btn-secondary">Send Confirmation Link</button>
                        </div>
                        </form>
                    </div>
                    </div>
';
}
echo '
                </div>
            </div>

        </div>
          <a class="btn btn-primary my-5 py- px-5" href="/">Go Back To Home</a>
    </div>

';


echo $display->submitbuttoncolorjs('mainform');
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
