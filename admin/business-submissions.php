<?php
// business-submissions.php - Admin page to review submitted business recommendations
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Business Submissions";
$messages = array();

// Get any messages from session
$transferpagedata = $system->startpostpage();

// Get filter
$filter_status = $_GET['status'] ?? 'all';

// Build query
$sql = "SELECT c.*, 
        (SELECT description FROM bg_company_attributes WHERE company_id = c.company_id AND name = 'submitted_by_user_id' LIMIT 1) as submitted_by_user_id,
        (SELECT description FROM bg_company_attributes WHERE company_id = c.company_id AND name = 'submitter_email' LIMIT 1) as submitter_email,
        (SELECT description FROM bg_company_attributes WHERE company_id = c.company_id AND name = 'submission_notes' LIMIT 1) as submission_notes,
        (SELECT description FROM bg_company_attributes WHERE company_id = c.company_id AND name = 'possible_duplicate_id' LIMIT 1) as possible_duplicate_id
        FROM bg_companies c 
        WHERE 1=1 ";

$params = [];

if ($filter_status !== 'all') {
    $sql .= " AND c.status = :status ";
    $params['status'] = $filter_status;
} else {
    $sql .= " AND c.status IN ('submitted', 'pending_review', 'approved_pending_data', 'rejected') ";
}

$sql .= " ORDER BY c.create_dt DESC LIMIT 100";

$stmt = $database->query($sql, $params);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count by status
$status_counts = $database->query("
    SELECT status, COUNT(*) as count 
    FROM bg_companies 
    WHERE status IN ('submitted', 'pending_review', 'approved_pending_data', 'rejected', 'active')
    AND source = 'user_recommendation'
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$bodycontentclass = '';
$header_flush = true;

// Additional styles for modern tabs
$additionalstyles = '
<style>
/* Modern minimal design for business submissions */
.submissions-container {
    max-width: 1200px;
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

.badge-count {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    line-height: 1;
    border-radius: 10px;
    margin-left: 0.5rem;
}

.badge-count.warning {
    background-color: #ffc107;
    color: #000;
}

.badge-count.info {
    background-color: #0dcaf0;
    color: #000;
}

.badge-count.danger {
    background-color: #dc3545;
    color: #fff;
}

.badge-count.secondary {
    background-color: #6c757d;
    color: #fff;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="content-header-admin no-rounded-corners">
    <div class="container">
        <h1 class="mt-3">Business Submissions</h1>
        <p class="lead mb-4">Review and manage user-submitted business recommendations</p>
    </div>
</div>

<div class="main-content py-4 py-md-5 bg-light">
    <div class="container">
        <?php
        // Display messages
        if (!empty($messages)) {
            echo '<div class="mb-4">';
            foreach ($messages as $message) {
                echo $message;
            }
            echo '</div>';
        }
        ?>

        <div class="submissions-container">
            <!-- Modern Tab Navigation -->
            <nav class="nav-tabs-modern">
                <a href="?status=all" class="nav-tab-item <?php echo $filter_status === 'all' ? 'active' : ''; ?>">
                    <i class="bi bi-list-ul me-2"></i>All
                    <span class="badge-count secondary"><?php echo array_sum($status_counts); ?></span>
                </a>
                
                <a href="?status=submitted" class="nav-tab-item <?php echo $filter_status === 'submitted' ? 'active' : ''; ?>">
                    <i class="bi bi-inbox me-2"></i>New Submissions
                    <?php if (($status_counts['submitted'] ?? 0) > 0): ?>
                    <span class="badge-count warning"><?php echo $status_counts['submitted']; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="?status=pending_review" class="nav-tab-item <?php echo $filter_status === 'pending_review' ? 'active' : ''; ?>">
                    <i class="bi bi-hourglass-split me-2"></i>Pending Review
                    <?php if (($status_counts['pending_review'] ?? 0) > 0): ?>
                    <span class="badge-count info"><?php echo $status_counts['pending_review']; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="?status=approved_pending_data" class="nav-tab-item <?php echo $filter_status === 'approved_pending_data' ? 'active' : ''; ?>">
                    <i class="bi bi-clock-history me-2"></i>Collecting Data
                    <?php if (($status_counts['approved_pending_data'] ?? 0) > 0): ?>
                    <span class="badge-count info"><?php echo $status_counts['approved_pending_data']; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="?status=rejected" class="nav-tab-item <?php echo $filter_status === 'rejected' ? 'active' : ''; ?>">
                    <i class="bi bi-x-circle me-2"></i>Rejected
                    <?php if (($status_counts['rejected'] ?? 0) > 0): ?>
                    <span class="badge-count danger"><?php echo $status_counts['rejected']; ?></span>
                    <?php endif; ?>
                </a>
            </nav>

    <?php if (empty($submissions)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No submissions found with the selected filter.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Business Name</th>
                        <th>URLs</th>
                        <th>Submitted By</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($submission['company_name']); ?></strong>
                                <?php if ($submission['possible_duplicate_id']): ?>
                                    <br><small class="text-warning">
                                        <i class="bi bi-exclamation-triangle"></i> 
                                        Possible duplicate of ID: <?php echo $submission['possible_duplicate_id']; ?>
                                    </small>
                                <?php endif; ?>
                                <?php if ($submission['category']): ?>
                                    <br><small class="text-muted">Category: <?php echo htmlspecialchars($submission['category']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo htmlspecialchars($submission['company_url']); ?>" target="_blank" class="text-decoration-none">
                                    <i class="bi bi-house-door"></i> Home
                                </a><br>
                                <a href="<?php echo htmlspecialchars($submission['signup_url']); ?>" target="_blank" class="text-decoration-none">
                                    <i class="bi bi-gift"></i> Signup
                                </a>
                            </td>
                            <td>
                                <?php if ($submission['submitter_email']): ?>
                                    <small>
                                        <?php echo htmlspecialchars($submission['submitter_email']); ?><br>
                                        User ID: <?php echo $submission['submitted_by_user_id']; ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">Unknown</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?php echo date('M j, Y', strtotime($submission['create_dt'])); ?></small>
                            </td>
                            <td>
                                <?php
                                $status_class = [
                                    'submitted' => 'warning',
                                    'pending_review' => 'info',
                                    'approved_pending_data' => 'primary',
                                    'active' => 'success',
                                    'rejected' => 'danger'
                                ][$submission['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $status_class; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $submission['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="/admin/company-editor-main?cid=<?php echo $submission['company_id']; ?>" 
                                       class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    
                                    <?php if ($submission['status'] !== 'active'): ?>
                                        <form method="post" action="/admin/business-action.php" class="d-inline">
                                            <?php echo $display->inputcsrf_token(); ?>
                                            <input type="hidden" name="company_id" value="<?php echo $submission['company_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="redirect_status" value="<?php echo htmlspecialchars($filter_status); ?>">
                                            <button type="submit" class="btn btn-outline-success" title="Approve">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($submission['status'] !== 'rejected'): ?>
                                        <form method="post" action="/admin/business-action.php" class="d-inline">
                                            <?php echo $display->inputcsrf_token(); ?>
                                            <input type="hidden" name="company_id" value="<?php echo $submission['company_id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="redirect_status" value="<?php echo htmlspecialchars($filter_status); ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Reject">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($submission['submission_notes']): ?>
                                    <button class="btn btn-sm btn-link p-0 mt-1" type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#notes-<?php echo $submission['company_id']; ?>">
                                        <small>View Notes</small>
                                    </button>
                                    <div class="collapse mt-2" id="notes-<?php echo $submission['company_id']; ?>">
                                        <div class="card card-body small">
                                            <?php echo nl2br(htmlspecialchars($submission['submission_notes'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="mt-4">
        <div class="alert alert-info">
            <h6>Processing Information:</h6>
            <ul class="mb-0">
                <li><strong>New Submissions:</strong> User-submitted businesses awaiting initial processing</li>
                <li><strong>Pending Review:</strong> Processed by scheduler, needs manual review</li>
                <li><strong>Collecting Data:</strong> Approved businesses having additional data collected by scheduler</li>
                <li><strong>Active:</strong> Fully processed and active in the directory</li>
                <li><strong>Rejected:</strong> Submissions that were not suitable for the directory</li>
            </ul>
        </div>
    </div>
        </div> <!-- Close submissions-container -->
    </div> <!-- Close container -->
</div> <!-- Close main-content -->


<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
