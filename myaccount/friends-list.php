<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page configuration
$pagetitle = 'My Friends & Invites';
$bodycontentclass = '';
$additionalstyles = '
<style>
/* Modern minimal design for friends list */
.friends-container {
    max-width: 1000px;
    margin: 0 auto;
}

/* Tab navigation with active bottom border */
.nav-tabs-modern {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
    gap: 0;
    overflow: hidden;
    position: relative;
    align-items: center;
}

.nav-tab-item {
    flex: 0 0 auto;
    padding: 1rem 2rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
    background: none;
    border-radius: 0;
    position: relative;
    cursor: pointer;
}

.nav-tab-item:hover {
    color: #495057;
    text-decoration: none;
    background: #f8f9fa;
}

.nav-tab-item.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd !important;
    background: none;
}

/* Action buttons aligned to the right */
.nav-actions {
    margin-left: auto;
    display: flex;
    gap: 0.5rem;
    padding: 0 1rem;
}

.nav-actions .btn {
    font-size: 0.875rem;
}

/* Friend cards */
.friend-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.2s ease;
    position: relative;
}

.friend-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-1px);
}

.friend-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
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

.search-box {
    max-width: 400px;
    margin: 0 auto 1rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .nav-tab-item {
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
    }
    
    .nav-actions {
        display: none;
    }
    
    .friend-card {
        padding: 0.875rem;
    }
    
    .stats-card {
        margin-bottom: 0.75rem;
    }
}

/* Mobile action buttons */
.mobile-action-buttons {
    display: none;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .mobile-action-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }
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
        formData.append("_token", document.querySelector(\'[name="_token"]\').value);
        
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
    const tabs = document.querySelectorAll(".nav-tab-item[data-filter]");
    
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
    
    // Update URL without reload
    const url = new URL(window.location);
    if (status === "all") {
        url.searchParams.delete("view");
    } else {
        url.searchParams.set("view", status);
    }
    window.history.pushState({}, "", url);
    
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

// Initialize on load
document.addEventListener("DOMContentLoaded", function() {
    // Check URL for view parameter
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get("view") || "all";
    if (view !== "all") {
        filterFriends(view);
    }
});
</script>
';

// Get current view from URL
$view = $_GET['view'] ?? 'all';

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
        
        $templateData = [
            'name' => $current_user_data['first_name'] . ' ' . $current_user_data['last_name'],
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
            'from' => [$current_user_data['email'], $current_user_data['first_name'] . ' ' . $current_user_data['last_name']],
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
        u.first_name AS friend_firstname,
        u.last_name AS friend_lastname,
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
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-people-fill me-3"></i>My Friends & Invites</h1>
            <p class="lead mb-0">Track your invitations and see which friends have joined Birthday.Gold</p>
            <?php if ($totalInvites > 3): ?>
            <div class="search-box mt-4">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search by name or email..." onkeyup="searchFriends()">
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container my-5 pt-5">
    <div class="friends-container">
        
        <!-- Tab navigation with action buttons -->
        <nav class="nav-tabs-modern">
            <a href="javascript:void(0)" onclick="filterFriends('all')" class="nav-tab-item <?php echo $view === 'all' ? 'active' : ''; ?>" data-filter="all">
                <i class="bi bi-people me-2"></i>All Friends (<?php echo $totalInvites; ?>)
            </a>
            <a href="javascript:void(0)" onclick="filterFriends('joined')" class="nav-tab-item <?php echo $view === 'joined' ? 'active' : ''; ?>" data-filter="joined">
                <i class="bi bi-check-circle me-2"></i>Joined (<?php echo $joinedCount; ?>)
            </a>
            <a href="javascript:void(0)" onclick="filterFriends('pending')" class="nav-tab-item <?php echo $view === 'pending' ? 'active' : ''; ?>" data-filter="pending">
                <i class="bi bi-clock me-2"></i>Pending (<?php echo $pendingCount; ?>)
            </a>
            
            <!-- Action buttons aligned to the right (desktop only) -->
            <div class="nav-actions">
                <a href="/myaccount/invite" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>Send Invite
                </a>
                <a href="/myaccount/invite-history" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-clock-history me-1"></i>History
                </a>
            </div>
        </nav>
        
        <!-- Mobile action buttons -->
        <div class="mobile-action-buttons">
            <a href="/myaccount/invite" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Invite
            </a>
            <a href="/myaccount/invite-history" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-clock-history me-1"></i>History
            </a>
        </div>

        <?php if ($totalInvites > 0): ?>
            <!-- Statistics Row -->
            <div class="row mb-4">
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $totalInvites; ?></div>
                        <div class="text-muted small">Total Invites</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-number text-success"><?php echo $joinedCount; ?></div>
                        <div class="text-muted small">Joined</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-number text-warning"><?php echo $pendingCount; ?></div>
                        <div class="text-muted small">Pending</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-number text-primary"><?php echo $joinedCount > 0 ? round(($joinedCount / $totalInvites) * 100) : 0; ?>%</div>
                        <div class="text-muted small">Success Rate</div>
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
                                    <h6 class="mb-1 friend-name"><?php echo htmlspecialchars($invite['invitee_name']); ?></h6>
                                    <p class="text-muted mb-1 small friend-email">
                                        <i class="bi bi-envelope me-1"></i>
                                        <?php echo htmlspecialchars($invite['invitee_email']); ?>
                                    </p>
                                    <p class="text-muted mb-0 small">
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
                                            • Resent <?php echo $invite['resend_count']; ?> time(s)
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
                        <p class="text-muted mb-4">Start building your Birthday.Gold community by inviting friends and family!</p>
                        <a href="/myaccount/invite" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Send Your First Invite
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Hidden CSRF token for AJAX requests -->
<?php echo $display->input_csrftoken(); ?>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>