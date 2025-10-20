<?php
/**
 * Error Fix Review Page
 * Allows admins to review and approve/reject AI-proposed error fixes
 */

include '../core/site-controller.php';

// Admin access check
if (!$account->isadmin()) {
    header('Location: /');
    exit;
}

// Page setup
$page_title = "Error Fix Review - Birthday.Gold Admin";
$page_description = "Review AI-proposed error fixes";

// Get review token
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $system->addmessage('error', 'Invalid review link - no token provided');
    header('Location: /admin/error-fix-dashboard.php');
    exit;
}

// Fetch fix details
$sql = "SELECT * FROM bg_auto_error_fixes WHERE review_token = :token";
$stmt = $database->query($sql, ['token' => $token]);
$fix = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fix) {
    $system->addmessage('error', 'Fix not found or invalid token');
    header('Location: /admin/error-fix-dashboard.php');
    exit;
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $notes = $_POST['review_notes'] ?? '';

    if ($action === 'approve') {
        $update_sql = "UPDATE bg_auto_error_fixes
                      SET fix_status = 'approved_pending_apply',
                          reviewed_by = :user_id,
                          reviewed_dt = NOW(),
                          review_notes = :notes
                      WHERE fix_id = :fix_id";

        $database->query($update_sql, [
            'user_id' => $account->userid,
            'notes' => $notes,
            'fix_id' => $fix['fix_id']
        ]);

        $system->addmessage('success', 'Fix approved! It will be applied on the next scheduler run.');

        // Reload fix data
        $fix = $database->query($sql, ['token' => $token])->fetch(PDO::FETCH_ASSOC);

    } elseif ($action === 'reject') {
        $update_sql = "UPDATE bg_auto_error_fixes
                      SET fix_status = 'rejected',
                          reviewed_by = :user_id,
                          reviewed_dt = NOW(),
                          review_notes = :notes
                      WHERE fix_id = :fix_id";

        $database->query($update_sql, [
            'user_id' => $account->userid,
            'notes' => $notes,
            'fix_id' => $fix['fix_id']
        ]);

        $system->addmessage('success', 'Fix rejected and will not be applied.');

        // Reload fix data
        $fix = $database->query($sql, ['token' => $token])->fetch(PDO::FETCH_ASSOC);

    } elseif ($action === 'ignore') {
        $update_sql = "UPDATE bg_auto_error_fixes
                      SET fix_status = 'auto_ignored',
                          reviewed_by = :user_id,
                          reviewed_dt = NOW(),
                          review_notes = :notes
                      WHERE fix_id = :fix_id";

        $database->query($update_sql, [
            'user_id' => $account->userid,
            'notes' => $notes,
            'fix_id' => $fix['fix_id']
        ]);

        $system->addmessage('info', 'Fix ignored. This error will be hidden from future reports.');

        // Reload fix data
        $fix = $database->query($sql, ['token' => $token])->fetch(PDO::FETCH_ASSOC);
    }
}

// Determine status badge
$status_badge = '';
switch ($fix['fix_status']) {
    case 'pending_review':
        $status_badge = '<span class="badge bg-warning text-dark">Pending Review</span>';
        break;
    case 'approved_pending_apply':
        $status_badge = '<span class="badge bg-info">Approved - Pending Application</span>';
        break;
    case 'applied':
        $status_badge = '<span class="badge bg-success">Applied</span>';
        break;
    case 'rejected':
        $status_badge = '<span class="badge bg-danger">Rejected</span>';
        break;
    case 'auto_ignored':
        $status_badge = '<span class="badge bg-secondary">Ignored</span>';
        break;
    default:
        $status_badge = '<span class="badge bg-light text-dark">' . htmlspecialchars($fix['fix_status']) . '</span>';
}

// CSS styles
$additionalstyles .= '
<style>
.error-fix-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.info-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.info-card h5 {
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: 150px 1fr;
    gap: 0.75rem;
    align-items: start;
}

.info-grid .label {
    font-weight: 600;
    color: #495057;
}

.code-comparison {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.code-block {
    background: #1e1e1e;
    color: #d4d4d4;
    border-radius: 8px;
    padding: 1rem;
    font-family: "Consolas", "Monaco", "Courier New", monospace;
    font-size: 0.875rem;
    line-height: 1.6;
    overflow-x: auto;
    white-space: pre;
    margin-bottom: 1rem;
}

.code-block.original {
    border-left: 4px solid #dc3545;
}

.code-block.fixed {
    border-left: 4px solid #28a745;
}

.code-line {
    display: block;
}

.code-line.highlight {
    background: rgba(255, 193, 7, 0.2);
    margin: 0 -1rem;
    padding: 0 1rem;
}

.code-line-marker {
    color: #ff6b6b;
    font-weight: bold;
    margin-right: 0.5rem;
}

.action-buttons {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    position: sticky;
    bottom: 1rem;
    z-index: 100;
}

.action-buttons .btn-group {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.action-buttons .btn {
    flex: 1;
    min-width: 150px;
}

.diff-indicator {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-right: 0.5rem;
}

.diff-removed {
    background: #ffe5e5;
    color: #c92a2a;
}

.diff-added {
    background: #e5ffe5;
    color: #2a9d2a;
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }

    .action-buttons .btn {
        min-width: 100%;
    }
}
</style>';

// Include header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Page header
echo '
<div class="content-header-admin bg-primary text-white py-4">
    <div class="container-fluid">
        <h1 class="h2 mb-0">
            <i class="bi bi-bug-fill"></i> Error Fix Review
        </h1>
        <p class="mb-0">Review AI-proposed fix for automated error correction</p>
    </div>
</div>

<div class="error-fix-container">';

// Status at top
echo '
    <div class="info-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">Fix ID #' . $fix['fix_id'] . '</h4>
                <small class="text-muted">Token: ' . htmlspecialchars(substr($fix['review_token'], 0, 16)) . '...</small>
            </div>
            <div>
                ' . $status_badge . '
            </div>
        </div>
    </div>';

// Error details
echo '
    <div class="info-card">
        <h5><i class="bi bi-exclamation-triangle-fill text-danger"></i> Error Details</h5>
        <div class="info-grid">
            <div class="label">File:</div>
            <div><code>' . htmlspecialchars($fix['error_file']) . ':' . $fix['error_line'] . '</code></div>

            <div class="label">Type:</div>
            <div><span class="badge bg-danger">' . htmlspecialchars($fix['error_type']) . '</span></div>

            <div class="label">Message:</div>
            <div>' . htmlspecialchars($fix['error_message']) . '</div>

            <div class="label">First Seen:</div>
            <div>' . date('M j, Y g:i A', strtotime($fix['first_seen'])) . '</div>

            <div class="label">Last Seen:</div>
            <div>' . date('M j, Y g:i A', strtotime($fix['last_seen'])) . '</div>

            <div class="label">Occurrences:</div>
            <div><span class="badge bg-warning text-dark">' . $fix['occurrence_count'] . ' times</span></div>
        </div>
    </div>';

// AI analysis
echo '
    <div class="info-card">
        <h5><i class="bi bi-robot text-primary"></i> AI Analysis</h5>
        <div class="info-grid">
            <div class="label">Confidence:</div>
            <div>
                <div class="progress" style="height: 25px; width: 200px;">
                    <div class="progress-bar ' . ($fix['ai_confidence'] >= 90 ? 'bg-success' : ($fix['ai_confidence'] >= 75 ? 'bg-info' : 'bg-warning')) . '"
                         role="progressbar" style="width: ' . $fix['ai_confidence'] . '%">
                        ' . $fix['ai_confidence'] . '%
                    </div>
                </div>
            </div>

            <div class="label">Fix Type:</div>
            <div><span class="badge bg-info">' . htmlspecialchars($fix['ai_fix_type'] ?? '') . '</span></div>

            <div class="label">AI Model:</div>
            <div><small class="text-muted">' . htmlspecialchars($fix['ai_model'] ?? '') . '</small></div>

            <div class="label">Analyzed:</div>
            <div>' . date('M j, Y g:i A', strtotime($fix['ai_analyzed_dt'])) . '</div>

            <div class="label">Explanation:</div>
            <div>' . nl2br(htmlspecialchars($fix['ai_explanation'] ?? '')) . '</div>
        </div>
    </div>';

// Code comparison
echo '
    <div class="code-comparison">
        <h5><i class="bi bi-code-square text-success"></i> Code Comparison</h5>

        <h6 class="mt-3"><span class="diff-indicator diff-removed">-</span>Original Code (Lines ' . $fix['line_start'] . '-' . $fix['line_end'] . ')</h6>
        <div class="code-block original">' . htmlspecialchars($fix['original_code'] ?? '') . '</div>

        <h6 class="mt-4"><span class="diff-indicator diff-added">+</span>Proposed Fix</h6>
        <div class="code-block fixed">' . htmlspecialchars($fix['proposed_fix'] ?? '') . '</div>
    </div>';

// Full context (expandable)
if (!empty($fix['error_context'])) {
    echo '
    <div class="info-card">
        <h5><i class="bi bi-file-text"></i> Full Context</h5>
        <details>
            <summary style="cursor: pointer; color: #0d6efd;">Click to expand (20 lines of context)</summary>
            <div class="code-block mt-2">' . htmlspecialchars($fix['error_context']) . '</div>
        </details>
    </div>';
}

// Review history (if reviewed)
if (!empty($fix['reviewed_by'])) {
    // Get reviewer name
    $reviewer_sql = "SELECT user_firstname, user_lastname FROM bg_users WHERE user_id = :user_id";
    $reviewer = $database->query($reviewer_sql, ['user_id' => $fix['reviewed_by']])->fetch(PDO::FETCH_ASSOC);

    echo '
    <div class="info-card">
        <h5><i class="bi bi-person-check-fill text-success"></i> Review History</h5>
        <div class="info-grid">
            <div class="label">Reviewed By:</div>
            <div>' . htmlspecialchars($reviewer['user_firstname'] . ' ' . $reviewer['user_lastname']) . ' (ID: ' . $fix['reviewed_by'] . ')</div>

            <div class="label">Reviewed On:</div>
            <div>' . date('M j, Y g:i A', strtotime($fix['reviewed_dt'])) . '</div>';

    if (!empty($fix['review_notes'])) {
        echo '
            <div class="label">Review Notes:</div>
            <div>' . nl2br(htmlspecialchars($fix['review_notes'])) . '</div>';
    }

    echo '
        </div>
    </div>';
}

// Applied info (if applied)
if ($fix['fix_status'] === 'applied' && !empty($fix['applied_dt'])) {
    echo '
    <div class="info-card">
        <h5><i class="bi bi-check-circle-fill text-success"></i> Application Info</h5>
        <div class="info-grid">
            <div class="label">Applied On:</div>
            <div>' . date('M j, Y g:i A', strtotime($fix['applied_dt'])) . '</div>

            <div class="label">Applied By:</div>
            <div>' . htmlspecialchars($fix['applied_by'] ?? '') . '</div>

            <div class="label">Git Commit:</div>
            <div><code>' . htmlspecialchars($fix['git_commit_hash'] ?? '') . '</code></div>

            <div class="label">Syntax Check:</div>
            <div>' . ($fix['syntax_check_passed'] ? '<span class="badge bg-success">Passed</span>' : '<span class="badge bg-danger">Failed</span>') . '</div>
        </div>
    </div>';
}

// Action buttons (only if pending review)
if ($fix['fix_status'] === 'pending_review') {
    echo '
    <div class="action-buttons">
        <h5 class="mb-3"><i class="bi bi-hand-thumbs-up"></i> Actions</h5>

        <form method="POST" id="reviewForm">
            <div class="mb-3">
                <label for="review_notes" class="form-label">Review Notes (Optional)</label>
                <textarea class="form-control" id="review_notes" name="review_notes" rows="3"
                          placeholder="Add any notes about your decision..."></textarea>
            </div>

            <div class="btn-group">
                <button type="submit" name="action" value="approve" class="btn btn-success btn-lg">
                    <i class="bi bi-check-circle"></i> Approve Fix
                </button>
                <button type="submit" name="action" value="reject" class="btn btn-danger btn-lg">
                    <i class="bi bi-x-circle"></i> Reject Fix
                </button>
                <button type="submit" name="action" value="ignore" class="btn btn-secondary btn-lg">
                    <i class="bi bi-eye-slash"></i> Ignore Error
                </button>
            </div>

            <div class="mt-3 small text-muted">
                <strong>Approve:</strong> Fix will be applied on next scheduler run<br>
                <strong>Reject:</strong> Fix will be marked rejected and not applied<br>
                <strong>Ignore:</strong> Hide this error from future reports
            </div>
        </form>
    </div>';
}

// Back button
echo '
    <div class="text-center mb-4">
        <a href="/admin/error-fix-dashboard.php" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

</div>';

// Include footer
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
