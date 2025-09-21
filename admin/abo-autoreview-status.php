<?php
/**
 * ABO Auto Review Status Monitor
 * Shows results of automatic business submission reviews
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Admin check
if (!$account->checkrole('admin')) {
    header('Location: /myaccount');
    exit;
}

$pagetitle = "ABO Auto Review Status";

// Get filter parameters
$filter_decision = $_GET['decision'] ?? 'all';
$limit = intval($_GET['limit'] ?? 50);

// Build query
$sql = "SELECT 
        c.company_id,
        c.company_name,
        c.company_url,
        c.signup_url,
        c.status,
        c.create_dt,
        ca_review.value as review_results,
        ca_approval.description as approval_status,
        ca_approval.create_dt as review_date
        FROM bg_companies c
        LEFT JOIN bg_company_attributes ca_review 
            ON c.company_id = ca_review.company_id 
            AND ca_review.type = 'auto_review' 
            AND ca_review.name = 'results'
        LEFT JOIN bg_company_attributes ca_approval
            ON c.company_id = ca_approval.company_id 
            AND ca_approval.type = 'auto_review' 
            AND ca_approval.name = 'approval'
        WHERE c.source = 'user_recommendation'
        AND (ca_review.value IS NOT NULL OR ca_approval.value IS NOT NULL)";

$params = [];

if ($filter_decision !== 'all') {
    if ($filter_decision === 'auto_approved') {
        $sql .= " AND ca_approval.description = 'auto_approved'";
    } else {
        $sql .= " AND (ca_approval.description IS NULL OR ca_approval.description != 'auto_approved')";
    }
}

$sql .= " ORDER BY c.create_dt DESC LIMIT :limit";

$stmt = $database->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts
$count_sql = "SELECT 
              COUNT(*) as total,
              SUM(CASE WHEN ca_approval.description = 'auto_approved' THEN 1 ELSE 0 END) as auto_approved,
              SUM(CASE WHEN ca_approval.description IS NULL OR ca_approval.description != 'auto_approved' THEN 1 ELSE 0 END) as manual_review
              FROM bg_companies c
              LEFT JOIN bg_company_attributes ca_approval
                  ON c.company_id = ca_approval.company_id 
                  AND ca_approval.type = 'auto_review' 
                  AND ca_approval.name = 'approval'
              WHERE c.source = 'user_recommendation'
              AND c.create_dt >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

$counts = $database->query($count_sql)->fetch(PDO::FETCH_ASSOC);

// Additional styles
$additionalstyles = '
<style>
.review-status {
    max-width: 1400px;
    margin: 0 auto;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1.5rem;
    text-align: center;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.875rem;
    text-transform: uppercase;
}

.confidence-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.875rem;
    font-weight: 600;
}

.confidence-high {
    background: #d4edda;
    color: #155724;
}

.confidence-medium {
    background: #fff3cd;
    color: #856404;
}

.confidence-low {
    background: #f8d7da;
    color: #721c24;
}

.check-details {
    font-size: 0.875rem;
    color: #6c757d;
}

.check-passed {
    color: #28a745;
}

.check-failed {
    color: #dc3545;
}

.filter-tabs {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #dee2e6;
}

.filter-tab {
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
}

.filter-tab:hover {
    color: #495057;
}

.filter-tab.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
}
</style>';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container mt-4">
    <div class="review-status">
        <h1 class="mb-4">ABO Auto Review Status</h1>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($counts['total']); ?></div>
                <div class="stat-label">Total Reviews (30d)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-success"><?php echo number_format($counts['auto_approved']); ?></div>
                <div class="stat-label">Auto Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-warning"><?php echo number_format($counts['manual_review']); ?></div>
                <div class="stat-label">Manual Review</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-info">
                    <?php 
                    $approval_rate = $counts['total'] > 0 ? round(($counts['auto_approved'] / $counts['total']) * 100) : 0;
                    echo $approval_rate . '%';
                    ?>
                </div>
                <div class="stat-label">Approval Rate</div>
            </div>
        </div>
        
        <!-- Filter tabs -->
        <div class="filter-tabs">
            <a href="?decision=all" class="filter-tab <?php echo $filter_decision === 'all' ? 'active' : ''; ?>">
                All Reviews
            </a>
            <a href="?decision=auto_approved" class="filter-tab <?php echo $filter_decision === 'auto_approved' ? 'active' : ''; ?>">
                Auto Approved
            </a>
            <a href="?decision=manual_review" class="filter-tab <?php echo $filter_decision === 'manual_review' ? 'active' : ''; ?>">
                Manual Review
            </a>
        </div>
        
        <!-- Results table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>URLs</th>
                                <th>Confidence</th>
                                <th>Checks</th>
                                <th>Decision</th>
                                <th>Reviewed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $review): 
                                $review_data = json_decode($review['review_results'], true);
                                $confidence = $review_data['confidence_score'] ?? 0;
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($review['company_name']); ?></strong><br>
                                    <small class="text-muted">ID: <?php echo $review['company_id']; ?></small>
                                </td>
                                <td>
                                    <small>
                                        <a href="<?php echo htmlspecialchars($review['company_url']); ?>" target="_blank" class="text-decoration-none">
                                            <i class="bi bi-house-door"></i> Home
                                        </a><br>
                                        <a href="<?php echo htmlspecialchars($review['signup_url']); ?>" target="_blank" class="text-decoration-none">
                                            <i class="bi bi-person-plus"></i> Signup
                                        </a>
                                    </small>
                                </td>
                                <td>
                                    <?php
                                    $confidence_class = 'confidence-low';
                                    if ($confidence >= 85) $confidence_class = 'confidence-high';
                                    elseif ($confidence >= 60) $confidence_class = 'confidence-medium';
                                    ?>
                                    <span class="confidence-badge <?php echo $confidence_class; ?>">
                                        <?php echo $confidence; ?>%
                                    </span>
                                </td>
                                <td>
                                    <div class="check-details">
                                        <?php if (isset($review_data['checks'])): ?>
                                            <?php foreach ($review_data['checks'] as $check_name => $check): ?>
                                                <span class="<?php echo ($check['score'] > 0) ? 'check-passed' : 'check-failed'; ?>">
                                                    <?php echo ucwords(str_replace('_', ' ', $check_name)); ?>: 
                                                    <?php echo $check['score']; ?>/<?php echo $check['max_score']; ?>
                                                </span><br>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($review['approval_status'] === 'auto_approved'): ?>
                                        <span class="badge bg-success">Auto Approved</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Manual Review</span>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-muted">
                                        Status: <?php echo $review['status']; ?>
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        <?php echo date('M d, Y', strtotime($review['review_date'] ?? $review['create_dt'])); ?><br>
                                        <?php echo date('g:i A', strtotime($review['review_date'] ?? $review['create_dt'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <a href="/admin/businesseditor?id=<?php echo $qik->encodeID($review['company_id']); ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                    <?php if ($review['approval_status'] !== 'auto_approved' && $review['status'] === 'pending_review'): ?>
                                    <button class="btn btn-sm btn-outline-success" 
                                            onclick="approveCompany(<?php echo $review['company_id']; ?>)">
                                        Approve
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function approveCompany(companyId) {
    if (confirm('Approve this company for ABO processing?')) {
        // This would make an AJAX call to approve the company
        window.location.href = '/admin/businesseditor_components/approve.php?company_id=' + companyId;
    }
}
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>