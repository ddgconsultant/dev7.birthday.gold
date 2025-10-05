<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


#-------------------------------------------------------------------------------
# PAGE SETUP
#-------------------------------------------------------------------------------
$page_title = "Add Marketing Access - Admin - Birthday.Gold";
$page_description = "Grant marketing access to users";

$success_message = '';
$error_message = '';
$user_info = null;

#-------------------------------------------------------------------------------
# HANDLE FORM SUBMISSION
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
        $target_user_id = intval($_POST['user_id']);

        // First, verify the user exists
        $check_sql = "SELECT user_id, user_firstname, user_lastname, user_type FROM bg_users WHERE user_id = :user_id";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
        $check_stmt->execute();
        $user_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_data) {
            // Check if user already has access to company 99
            $existing_sql = "SELECT COUNT(*) as count FROM mk_company_access
                           WHERE user_id = :user_id AND company_id = 99 AND status = 'active'";
            $existing_stmt = $pdo->prepare($existing_sql);
            $existing_stmt->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
            $existing_stmt->execute();
            $existing = $existing_stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing['count'] > 0) {
                $error_message = "User #{$target_user_id} ({$user_data['user_firstname']} {$user_data['user_lastname']}) already has marketing access.";
            } else {
                // Insert the marketing access record
                $insert_sql = "INSERT INTO mk_company_access
                              (user_id, company_id, access_type, permissions, grant_by, status, start_date, create_dt)
                              VALUES
                              (:user_id, 99, 'staff_admin', :permissions, :grant_by, 'active', CURDATE(), NOW())";

                $permissions = json_encode(["platforms", "campaigns", "analytics", "support"]);
                $grant_by = $current_user_data['user_id'];

                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
                $insert_stmt->bindParam(':permissions', $permissions, PDO::PARAM_STR);
                $insert_stmt->bindParam(':grant_by', $grant_by, PDO::PARAM_INT);

                if ($insert_stmt->execute()) {
                    $success_message = "Marketing access successfully granted to User #{$target_user_id} ({$user_data['user_firstname']} {$user_data['user_lastname']}).";
                } else {
                    $error_message = "Database error: Unable to grant marketing access.";
                }
            }
        } else {
            $error_message = "User ID #{$target_user_id} not found in the system.";
        }
    } else {
        $error_message = "Please enter a valid User ID.";
    }
}

#-------------------------------------------------------------------------------
# HANDLE "GRANT ACCESS" BUTTON (Full Setup)
#-------------------------------------------------------------------------------
if ($app->formposted() && isset($_POST['grant_full_access']) && !empty($_POST['user_id'])) {
    $target_user_id = intval($_POST['user_id']);

    // Verify user exists
    $check_sql = "SELECT user_id, user_firstname, user_lastname, user_type FROM bg_users WHERE user_id = :user_id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
    $check_stmt->execute();
    $user_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        try {
            $pdo->beginTransaction();

            // 1. Add mk_company_access record for company 99
            $check_existing = "SELECT COUNT(*) as count FROM mk_company_access
                             WHERE user_id = :user_id AND company_id = 99 AND status = 'active'";
            $stmt_check = $pdo->prepare($check_existing);
            $stmt_check->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
            $stmt_check->execute();
            $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($existing['count'] == 0) {
                $insert_access = "INSERT INTO mk_company_access
                                (user_id, company_id, access_type, permissions, grant_by, status, start_date, create_dt)
                                VALUES
                                (:user_id, 99, 'staff_admin', :permissions, :grant_by, 'active', CURDATE(), NOW())";

                $permissions = json_encode(["platforms", "campaigns", "analytics", "support"]);
                $grant_by = $current_user_data['user_id'];

                $stmt_access = $pdo->prepare($insert_access);
                $stmt_access->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
                $stmt_access->bindParam(':permissions', $permissions, PDO::PARAM_STR);
                $stmt_access->bindParam(':grant_by', $grant_by, PDO::PARAM_INT);
                $stmt_access->execute();
            }

            $pdo->commit();
            $success_message = "Marketing access successfully granted to User #{$target_user_id} ({$user_data['user_firstname']} {$user_data['user_lastname']}). Company access record created.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Transaction failed: " . $e->getMessage();
        }
    } else {
        $error_message = "User ID #{$target_user_id} not found in the system.";
    }
}

#-------------------------------------------------------------------------------
# LOOKUP USER (AJAX-style preview)
#-------------------------------------------------------------------------------
if (isset($_GET['lookup']) && isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $lookup_user_id = intval($_GET['user_id']);

    $lookup_sql = "SELECT u.user_id, u.user_firstname, u.user_lastname, u.user_type, u.is_staff, u.is_admin,
                   (SELECT COUNT(DISTINCT a.company_id)
                    FROM mk_company_access a, bg_companies c
                    WHERE a.company_id = c.company_id
                    AND c.company_status = 'active'
                    AND a.user_id = u.user_id
                    AND a.status = 'active') as company_count
                   FROM bg_users u
                   WHERE u.user_id = :user_id";

    $lookup_stmt = $pdo->prepare($lookup_sql);
    $lookup_stmt->bindParam(':user_id', $lookup_user_id, PDO::PARAM_INT);
    $lookup_stmt->execute();
    $user_info = $lookup_stmt->fetch(PDO::FETCH_ASSOC);
}

#-------------------------------------------------------------------------------
# PAGE STYLES
#-------------------------------------------------------------------------------
$additionalstyles .= '
<style>
/* Modern Admin Dashboard Styles */
body {
    padding: 0 !important;
    margin: 0 !important;
}

.navbar {
    margin-bottom: 0 !important;
}

.content-header-admin {
    margin-top: 0 !important;
}

.form-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.user-preview {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-top: 1rem;
}

.badge-role {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
}

.alert {
    border-radius: 8px;
    padding: 1rem 1.5rem;
}

.btn-primary {
    border-radius: 8px;
    padding: 0.75rem 2rem;
    font-weight: 600;
}

.form-control {
    border-radius: 8px;
    padding: 0.75rem 1rem;
}

.form-label {
    font-weight: 600;
    margin-bottom: 0.5rem;
}
</style>
';


#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="content-header-admin no-rounded-corners">
    <div class="container">
        <h1 class="mt-3">
            <i class="bi bi-megaphone-fill me-2"></i>
            Add Marketing Access
        </h1>
        <p class="lead mb-4">Grant users access to the Marketing section</p>
    </div>
</div>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- Success/Error Messages -->
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Main Form Card -->
            <div class="form-card">
                <form method="POST" action="/admin/marketing-add-access.php" id="marketingAccessForm">
                    <div class="mb-4">
                        <label for="user_id" class="form-label">User ID</label>
                        <div class="input-group">
                            <input type="number"
                                   class="form-control"
                                   id="user_id"
                                   name="user_id"
                                   placeholder="Enter user ID (e.g., 1282)"
                                   required
                                   min="1"
                                   value="<?php echo isset($_GET['user_id']) ? htmlspecialchars($_GET['user_id']) : ''; ?>">
                            <button class="btn btn-outline-secondary" type="button" id="lookupBtn">
                                <i class="bi bi-search me-1"></i> Lookup
                            </button>
                        </div>
                        <div class="form-text">Enter the numeric User ID to grant marketing access</div>
                    </div>

                    <!-- User Preview (shown after lookup) -->
                    <?php if ($user_info): ?>
                    <div class="user-preview">
                        <h5 class="mb-3">
                            <i class="bi bi-person-circle me-2"></i>
                            User Information
                        </h5>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <strong>User ID:</strong> #<?php echo htmlspecialchars($user_info['user_id']); ?>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>Name:</strong> <?php echo htmlspecialchars($user_info['user_firstname'] . ' ' . $user_info['user_lastname']); ?>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>Account Type:</strong>
                                <span class="badge bg-info"><?php echo htmlspecialchars($user_info['user_type']); ?></span>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>Roles:</strong>
                                <?php if ($user_info['is_admin']): ?>
                                    <span class="badge bg-danger me-1">Admin</span>
                                <?php endif; ?>
                                <?php if ($user_info['is_staff']): ?>
                                    <span class="badge bg-warning">Staff</span>
                                <?php endif; ?>
                                <?php if (!$user_info['is_admin'] && !$user_info['is_staff']): ?>
                                    <span class="badge bg-secondary">User</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-12 mb-2 mt-2">
                                <strong>Current Marketing Access:</strong>
                                <?php if ($user_info['company_count'] > 0): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Has access (<?php echo $user_info['company_count']; ?> companies)
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle me-1"></i>
                                        No marketing access
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Submit Button -->
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" name="grant_full_access" value="1" class="btn btn-primary btn-lg">
                            <i class="bi bi-megaphone-fill me-2"></i>
                            Grant Marketing Access
                        </button>
                    </div>

                    <!-- CSRF Token -->
                    <?php echo $display->inputcsrf_token(); ?>
                </form>
            </div>

            <!-- Info Card -->
            <div class="card mt-4 border-info">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="bi bi-info-circle-fill text-info me-2"></i>
                        What This Does
                    </h6>
                    <p class="card-text small mb-2">
                        This tool adds a record to the <code>mk_company_access</code> table, linking the user to
                        company #99 (Birthday Gold Rewards Partner) with <code>staff_admin</code> access type and permissions:
                        platforms, campaigns, analytics, support.
                    </p>
                    <p class="card-text small mb-0">
                        <strong>Result:</strong> The "Marketing" link will appear in the user's account dropdown menu in the header.
                    </p>
                </div>
            </div>

            <!-- Back Link -->
            <div class="mt-4">
                <a href="/admin/" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>
                    Back to Admin Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// User lookup functionality
document.getElementById('lookupBtn').addEventListener('click', function() {
    const userId = document.getElementById('user_id').value;
    if (userId && userId > 0) {
        window.location.href = '/admin/marketing-add-access.php?lookup=1&user_id=' + encodeURIComponent(userId);
    } else {
        alert('Please enter a valid User ID');
    }
});

// Allow Enter key to trigger lookup
document.getElementById('user_id').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('lookupBtn').click();
    }
});
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>