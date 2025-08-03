<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$pagetitle = "Manage Referral Code";
$errormessage = '';
$successmessage = '';

$userId = $current_user_data['user_id'];

// Get current referral code
$referralcode = $account->manageReferralCode();

// Get user ID from referral code
$sql = "SELECT user_id FROM bg_user_attributes 
        WHERE type = 'referral_code' 
        AND name = 'code' 
        AND description = :code 
        AND status = 'active'";
$stmt = $database->query($sql, ['code' => $referralcode['code']]);
$referrer_data = $stmt->fetch(PDO::FETCH_ASSOC);
$referrer_id = $referrer_data ? $referrer_data['user_id'] : $userId;

// Get referral stats
$sql = "SELECT COUNT(*) as total_referrals 
        FROM referrals 
        WHERE referrer_id = :user_id";
$stmt = $database->query($sql, ['user_id' => $referrer_id]);
$referral_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent referrals
$sql = "SELECT u.first_name, u.last_name, u.create_dt 
        FROM referrals r
        JOIN bg_users u ON r.referred_id = u.user_id
        WHERE r.referrer_id = :user_id 
        ORDER BY u.create_dt DESC 
        LIMIT 5";
$stmt = $database->query($sql, ['user_id' => $referrer_id]);
$recent_referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    
    // Generate new random code
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'generate') {
        $newCode = $account->generateReferralCode($current_user_data);
        $referralcode = $account->manageReferralCode($current_user_data, 'update', $newCode);
        
        $successmessage = '<div class="alert alert-success" role="alert">
            <i class="bi bi-check-circle me-2"></i>New referral code generated: <strong>' . htmlspecialchars($referralcode['code']) . '</strong>
        </div>';
        
        $transferpage['message'] = $successmessage;
        $transferpage['url'] = '/myaccount/referralcode';
        $system->endpostpage($transferpage);
        exit;
    }
    
    // Update with custom code
    if (isset($_REQUEST['custom_code']) && !empty($_REQUEST['custom_code'])) {
        $customCode = strtoupper(trim($_REQUEST['custom_code']));
        
        // Validate custom code
        if (strlen($customCode) < 4) {
            $errormessage = '<div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>Referral code must be at least 4 characters long.
            </div>';
        } elseif (!preg_match('/^[A-Z0-9]+$/', $customCode)) {
            $errormessage = '<div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>Referral code can only contain letters and numbers.
            </div>';
        } else {
            // Check if code is already in use
            $sql = "SELECT user_id FROM bg_user_attributes 
                    WHERE type = 'referral_code' 
                    AND name = 'code' 
                    AND description = :code 
                    AND user_id != :user_id";
            $stmt = $database->query($sql, ['code' => $customCode, 'user_id' => $userId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                $errormessage = '<div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>This referral code is already taken. Please choose another.
                </div>';
            } else {
                // Update referral code
                $referralcode = $account->manageReferralCode($current_user_data, 'update', $customCode);
                
                $successmessage = '<div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle me-2"></i>Referral code updated to: <strong>' . htmlspecialchars($referralcode['code']) . '</strong>
                </div>';
                
                $transferpage['message'] = $successmessage;
                $transferpage['url'] = '/myaccount/referralcode';
                $system->endpostpage($transferpage);
                exit;
            }
        }
    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);

$additionalstyles = '
<style>
/* Referral Code Display */
.referral-code-display {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--bs-primary);
    font-family: monospace;
    letter-spacing: 4px;
    user-select: all;
    cursor: pointer;
    padding: 1rem;
    background: rgba(0,123,255,0.05);
    border: 2px dashed var(--bs-primary);
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.referral-code-display:hover {
    background: rgba(0,123,255,0.1);
    transform: scale(1.02);
}

/* Stats Cards */
.stat-card {
    background: var(--bs-light);
    border-radius: 0.5rem;
    padding: 1.5rem;
    text-align: center;
    height: 100%;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--bs-primary);
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Form Styling */
.custom-code-input {
    text-transform: uppercase;
    font-family: monospace;
    letter-spacing: 2px;
    font-size: 1.25rem;
}

/* Recent Referrals */
.referral-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #dee2e6;
}

.referral-item:last-child {
    border-bottom: none;
}

/* Share Link */
.share-link {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 0.375rem;
    font-family: monospace;
    font-size: 0.875rem;
    word-break: break-all;
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
                <h1 class="mb-3"><i class="bi bi-ticket-perforated me-3"></i>Referral Code</h1>
                <p class="lead mb-0">Manage your personal referral code and track your referrals</p>
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
            
            <!-- Current Referral Code -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Your Referral Code</h5>
                </div>
                <div class="card-body text-center">
                    <div class="referral-code-display mb-3" id="referralCode" onclick="copyReferralCode()">
                        <?php echo htmlspecialchars($referralcode['code']); ?>
                    </div>
                    <button type="button" class="btn btn-outline-primary" onclick="copyReferralCode()">
                        <i class="bi bi-clipboard" id="copyIcon"></i> Copy Code
                    </button>
                    
                    <div class="share-link mt-4">
                        <strong>Share Link:</strong><br>
                        <span id="shareLink">https://birthday.gold/invitedby?<?php echo htmlspecialchars($referralcode['code']); ?></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="copyShareLink()">
                            <i class="bi bi-link-45deg"></i> Copy Link
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Stats Row -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $referral_stats['total_referrals'] ?? 0; ?></div>
                        <div class="stat-label">Total Referrals</div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($recent_referrals); ?></div>
                        <div class="stat-label">Recent Sign-ups</div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="stat-number">
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <div class="stat-label">Active Code</div>
                    </div>
                </div>
            </div>
            
            <!-- Update Referral Code -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Update Referral Code</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/myaccount/referralcode" class="mb-4">
                        <?php echo $display->inputcsrf_token(); ?>
                        <input type="hidden" name="action" value="generate">
                        
                        <div class="text-center mb-4">
                            <h6>Generate Random Code</h6>
                            <p class="text-muted">Let us create a unique code for you</p>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-shuffle me-2"></i>Generate New Code
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <form method="POST" action="/myaccount/referralcode">
                        <?php echo $display->inputcsrf_token(); ?>
                        
                        <div class="text-center">
                            <h6>Create Custom Code</h6>
                            <p class="text-muted">Choose your own memorable code (minimum 4 characters)</p>
                            
                            <div class="row justify-content-center">
                                <div class="col-md-6">
                                    <div class="input-group mb-3">
                                        <input type="text" 
                                               class="form-control custom-code-input" 
                                               id="custom_code" 
                                               name="custom_code" 
                                               placeholder="MYCODE" 
                                               maxlength="20"
                                               pattern="[A-Za-z0-9]+"
                                               title="Letters and numbers only"
                                               required>
                                        <button class="btn btn-primary" type="submit">
                                            <i class="bi bi-check-lg me-1"></i>Update
                                        </button>
                                    </div>
                                    <small class="text-muted">Letters and numbers only, no spaces</small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Recent Referrals -->
            <?php if (!empty($recent_referrals)): ?>
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Recent Referrals</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($recent_referrals as $referral): ?>
                    <div class="referral-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($referral['first_name'] . ' ' . $referral['last_name']); ?></strong>
                                <br>
                                <small class="text-muted">
                                    Joined <?php echo date('M j, Y', strtotime($referral['create_dt'])); ?>
                                </small>
                            </div>
                            <div>
                                <span class="badge bg-success">Active</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<script>
// Copy referral code
function copyReferralCode() {
    const referralCode = document.getElementById('referralCode').textContent.trim();
    
    navigator.clipboard.writeText(referralCode).then(() => {
        const copyIcon = document.getElementById('copyIcon');
        copyIcon.classList.remove('bi-clipboard');
        copyIcon.classList.add('bi-check-lg');
        
        // Flash the code display
        const codeDisplay = document.getElementById('referralCode');
        codeDisplay.style.background = 'rgba(40,167,69,0.1)';
        codeDisplay.style.borderColor = '#28a745';
        
        setTimeout(() => {
            copyIcon.classList.remove('bi-check-lg');
            copyIcon.classList.add('bi-clipboard');
            codeDisplay.style.background = '';
            codeDisplay.style.borderColor = '';
        }, 2000);
    });
}

// Copy share link
function copyShareLink() {
    const shareLink = document.getElementById('shareLink').textContent;
    
    navigator.clipboard.writeText(shareLink).then(() => {
        // Show success feedback
        const button = event.target.closest('button');
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
        button.classList.add('btn-success');
        button.classList.remove('btn-outline-secondary');
        
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-secondary');
        }, 2000);
    });
}

// Auto-uppercase custom code input
document.getElementById('custom_code').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});
</script>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>