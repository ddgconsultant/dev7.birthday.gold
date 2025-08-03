<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page configuration
$pagetitle = 'My Friends & Invites';
$bodycontentclass = '';
$additionalstyles = '
<style>
/* Friends List Styles */
.friend-card {
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.125);
    margin-bottom: 1rem;
}

.friend-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.friend-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    font-weight: bold;
}

.status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
}

.status-joined {
    background-color: #d4edda;
    color: #155724;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-expired {
    background-color: #f8d7da;
    color: #721c24;
}

.stats-card {
    text-align: center;
    padding: 1.5rem;
    border-radius: 10px;
    background: #f8f9fa;
    margin-bottom: 1rem;
}

.stats-number {
    font-size: 2rem;
    font-weight: bold;
    color: var(--bs-primary);
}

.resend-btn {
    font-size: 0.875rem;
    padding: 0.25rem 1rem;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-state-icon {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 1rem;
}

.filter-tabs .nav-link {
    color: #6c757d;
    border: none;
    border-bottom: 2px solid transparent;
    border-radius: 0;
    padding: 0.5rem 1rem;
}

.filter-tabs .nav-link.active {
    color: var(--bs-primary);
    background: none;
    border-bottom-color: var(--bs-primary);
}

.search-box {
    max-width: 400px;
}
</style>
';

$additionalscripts = '
<script>
function resendInvite(inviteId, email, name) {
    if (confirm(`Resend invitation to ${name}?`)) {
        // Create form data
        const formData = new FormData();
        formData.append("action", "resend");
        formData.append("invite_id", inviteId);
        formData.append("email", email);
        formData.append("name", name);
        formData.append("csrf_token", document.querySelector(\'[name="csrf_token"]\').value);
        
        // Send AJAX request
        fetch("/myaccount/friends-list.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(() => {
            location.reload();
        })
        .catch(error => {
            alert("Failed to resend invitation. Please try again.");
        });
    }
}

// Filter functionality
function filterFriends(status) {
    const cards = document.querySelectorAll(".friend-card");
    const tabs = document.querySelectorAll(".filter-tabs .nav-link");
    
    // Update active tab
    tabs.forEach(tab => tab.classList.remove("active"));
    document.querySelector(`[data-filter="${status}"]`).classList.add("active");
    
    // Show/hide cards
    cards.forEach(card => {
        if (status === "all" || card.dataset.status === status) {
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
    });
    
    // Update counts
    updateCounts();
}

// Search functionality
function searchFriends() {
    const searchTerm = document.getElementById("searchInput").value.toLowerCase();
    const cards = document.querySelectorAll(".friend-card");
    
    cards.forEach(card => {
        const name = card.querySelector(".friend-name").textContent.toLowerCase();
        const email = card.querySelector(".friend-email").textContent.toLowerCase();
        
        if (name.includes(searchTerm) || email.includes(searchTerm)) {
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
    });
}

function updateCounts() {
    const visibleCards = document.querySelectorAll(".friend-card:not([style*=\"display: none\"])");
    document.getElementById("visibleCount").textContent = visibleCards.length;
}
</script>
';

// Handle resend action
if ($app->formposted() && isset($_POST['action']) && $_POST['action'] === 'resend') {
    $invite_id = $_POST['invite_id'] ?? '';
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? '';
    
    // Get the invite details from database
    $sql = "SELECT description FROM bg_user_attributes 
            WHERE user_id = :user_id AND type = 'friend_invite' 
            AND create_dt = :invite_id LIMIT 1";
    
    $stmt = $database->prepare($sql);
    $stmt->execute([
        ':user_id' => $current_user_data['user_id'],
        ':invite_id' => $invite_id
    ]);
    $invite = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($invite) {
        // Extract relationship from description
        preg_match('/Relationship: ([^,]+)/', $invite['description'], $matches);
        $relationship = $matches[1] ?? 'Friend';
        
        // Get referral code
        $referralcode = $account->manageReferralCode();
        
        // Prepare email
        $subjectTemplate = 'An invite message for you from {{name}} and Birthday.Gold';
        $bodyTemplate = 'Hello,<br><br>' .
                        'You\'ve been invited by {{name}} to join Birthday.Gold, the platform that celebrates YOU on your special day! <�<br><br>' .
                        '<strong>Details of the Invitation:</strong><br>' .
                        'Inviter Name: {{name}}<br>' .
                        'Inviter Email: {{email}}<br>' .
                        'Their Relationship to You: {{relationship}}<br><br>' .
                        'At Birthday.Gold, you can receive amazing freebies and rewards from your favorite brands on your birthday! Don\'t miss out on the fun and the gifts waiting for you.<br><br>' .
                        '<a href="https://birthday.gold/invitedby?{{referralcode}}" style="color: #fff; background-color: #007bff; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Join Now</a><br><br>' .
                        'We look forward to celebrating with you! <�<br><br>' .
                        'Cheers,<br>' .
                        'The Birthday.Gold Team';
        
        $templateData = [
            'name' => $current_user_data['firstname'] . ' ' . $current_user_data['lastname'],
            'email' => $current_user_data['email'],
            'relationship' => $relationship,
            'referralcode' => $referralcode['code']
        ];
        
        // Replace placeholders
        foreach ($templateData as $key => $value) {
            $subjectTemplate = str_replace('{{' . $key . '}}', htmlspecialchars($value), $subjectTemplate);
            $bodyTemplate = str_replace('{{' . $key . '}}', htmlspecialchars($value), $bodyTemplate);
        }
        
        $messageinput = [
            'from' => [$current_user_data['email'], $current_user_data['firstname'] . ' ' . $current_user_data['lastname']],
            'to' => 'CS birthday.gold',
            'toemail' => 'cs@birthday.gold',
            'subject' => $subjectTemplate,
            'body' => $bodyTemplate,
            'notification' => strip_tags($bodyTemplate)
        ];
        
        $result = $mail->sendoutsidemessage($messageinput);
        
        // Update invite status
        $updateSql = "UPDATE bg_user_attributes 
                      SET modify_dt = NOW(), value = value + 1 
                      WHERE user_id = :user_id AND type = 'friend_invite' 
                      AND create_dt = :invite_id";
        
        $updateStmt = $database->prepare($updateSql);
        $updateStmt->execute([
            ':user_id' => $current_user_data['user_id'],
            ':invite_id' => $invite_id
        ]);
    }
    
    exit; // AJAX response
}

// Fetch invite history with friend status
$sql = "
    SELECT 
        ua.name AS invitee_name,
        ua.string_value AS invitee_email,
        ua.description,
        ua.status,
        ua.create_dt AS sent_dt,
        ua.modify_dt AS last_resent_dt,
        COALESCE(ua.value, 0) AS resend_count,
        u.user_id AS friend_user_id,
        u.firstname AS friend_firstname,
        u.lastname AS friend_lastname,
        u.create_dt AS friend_joined_dt
    FROM bg_user_attributes ua
    LEFT JOIN bg_users u ON u.email = ua.string_value
    WHERE ua.user_id = :user_id 
    AND ua.type = 'friend_invite'
    ORDER BY ua.create_dt DESC
";

$stmt = $database->prepare($sql);
$stmt->execute([':user_id' => $current_user_data['user_id']]);
$invites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$totalInvites = count($invites);
$joinedCount = 0;
$pendingCount = 0;

foreach ($invites as $invite) {
    if ($invite['friend_user_id']) {
        $joinedCount++;
    } else {
        $pendingCount++;
    }
}

// Get referral code
$referralcode = $account->manageReferralCode();

// Display page
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header -->
<div class="content-header-dark">
    <div class="container">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="mb-3"><i class="bi bi-people-fill me-3"></i>My Friends & Invites</h1>
                <p class="lead mb-0">Track your invitations and see which friends have joined Birthday Gold</p>
            </div>
            <div class="col-auto">
                <a href="/myaccount/invite" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Send New Invite
                </a>
                <a href="/myaccount/invite-history" class="btn btn-outline-primary ms-2">
                    <i class="bi bi-clock-history me-2"></i>View History
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <!-- Statistics Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number"><?php echo $totalInvites; ?></div>
                <div class="text-muted">Total Invites Sent</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number text-success"><?php echo $joinedCount; ?></div>
                <div class="text-muted">Friends Joined</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number text-warning"><?php echo $pendingCount; ?></div>
                <div class="text-muted">Pending Invites</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number text-primary"><?php echo $joinedCount > 0 ? round(($joinedCount / $totalInvites) * 100) : 0; ?>%</div>
                <div class="text-muted">Success Rate</div>
            </div>
        </div>
    </div>

    <!-- Referral Code Card -->
    <div class="card mb-4">
        <div class="card-body text-center py-4">
            <h5 class="mb-3">Your Referral Code</h5>
            <div class="d-flex align-items-center justify-content-center">
                <div class="referral-code me-3" style="font-size: 2rem; font-weight: 700; color: var(--bs-primary); font-family: monospace; letter-spacing: 3px;">
                    <?php echo htmlspecialchars($referralcode['code']); ?>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($referralcode['code']); ?>')">
                    <i class="bi bi-clipboard"></i> Copy
                </button>
            </div>
            <p class="text-muted mt-3 mb-0">Share this code with friends for direct sign-up</p>
        </div>
    </div>

    <?php if ($totalInvites > 0): ?>
        <!-- Filter and Search -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <ul class="nav filter-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" href="javascript:void(0)" data-filter="all" onclick="filterFriends('all')">
                                    All (<span id="allCount"><?php echo $totalInvites; ?></span>)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="javascript:void(0)" data-filter="joined" onclick="filterFriends('joined')">
                                    Joined (<span id="joinedCount"><?php echo $joinedCount; ?></span>)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="javascript:void(0)" data-filter="pending" onclick="filterFriends('pending')">
                                    Pending (<span id="pendingCount"><?php echo $pendingCount; ?></span>)
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <div class="search-box ms-auto">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="searchInput" placeholder="Search by name or email..." onkeyup="searchFriends()">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Friends List -->
        <div class="friends-list">
            <div class="mb-3 text-muted">
                Showing <span id="visibleCount"><?php echo $totalInvites; ?></span> friend(s)
            </div>
            
            <?php foreach ($invites as $invite): 
                $hasJoined = !empty($invite['friend_user_id']);
                $status = $hasJoined ? 'joined' : 'pending';
                $initials = strtoupper(substr($invite['invitee_name'], 0, 1));
                
                // Extract relationship from description
                preg_match('/Relationship: ([^,]+)/', $invite['description'], $matches);
                $relationship = $matches[1] ?? 'Friend';
            ?>
                <div class="card friend-card" data-status="<?php echo $status; ?>">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="friend-avatar">
                                    <?php echo $initials; ?>
                                </div>
                            </div>
                            <div class="col">
                                <h5 class="mb-1 friend-name"><?php echo htmlspecialchars($invite['invitee_name']); ?></h5>
                                <p class="text-muted mb-1 friend-email">
                                    <i class="bi bi-envelope me-1"></i>
                                    <?php echo htmlspecialchars($invite['invitee_email']); ?>
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="bi bi-heart me-1"></i>
                                    <?php echo htmlspecialchars($relationship); ?>
                                </p>
                            </div>
                            <div class="col-auto text-center">
                                <?php if ($hasJoined): ?>
                                    <span class="status-badge status-joined">
                                        <i class="bi bi-check-circle me-1"></i>Joined
                                    </span>
                                    <div class="text-muted small mt-2">
                                        <?php echo date('M j, Y', strtotime($invite['friend_joined_dt'])); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="status-badge status-pending">
                                        <i class="bi bi-clock me-1"></i>Pending
                                    </span>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-primary resend-btn" 
                                                onclick="resendInvite('<?php echo $invite['sent_dt']; ?>', '<?php echo htmlspecialchars($invite['invitee_email']); ?>', '<?php echo htmlspecialchars($invite['invitee_name']); ?>')">
                                            <i class="bi bi-arrow-repeat me-1"></i>Resend
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col">
                                <small class="text-muted">
                                    <i class="bi bi-calendar me-1"></i>
                                    Invited: <?php echo date('F j, Y', strtotime($invite['sent_dt'])); ?>
                                    <?php if ($invite['resend_count'] > 0): ?>
                                        " Resent <?php echo $invite['resend_count']; ?> time(s)
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
    <?php else: ?>
        <!-- Empty State -->
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h4>No invites sent yet</h4>
                    <p class="text-muted mb-4">Start building your Birthday Gold community by inviting friends and family!</p>
                    <a href="/myaccount/invite" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Send Your First Invite
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Hidden CSRF token for AJAX requests -->
<input type="hidden" name="csrf_token" value="<?php echo $system->generatecsrf_token(); ?>">

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>