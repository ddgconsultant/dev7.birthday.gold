<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check admin access
if (!$account->checkrole('admin')) {
    header('Location: /login');
    exit();
}

// Page metadata
$pagedata['pagetitle'] = 'Partner Applications - Admin';

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_date = $_GET['date'] ?? '';

// Additional styles
$additionalstyles = '
<style>
.application-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.2s ease;
}

.application-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

.status-applied {
    background: #e3f2fd;
    color: #1976d2;
}

.status-reviewing {
    background: #fff3cd;
    color: #856404;
}

.status-approved {
    background: #d4edda;
    color: #155724;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.status-active {
    background: #d1ecf1;
    color: #0c5460;
}

.attribute-list {
    margin-top: 1rem;
}

.attribute-item {
    display: flex;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.attribute-name {
    font-weight: 600;
    width: 150px;
    flex-shrink: 0;
}

.attribute-value {
    flex: 1;
    color: #666;
}

.filter-section {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container mt-4">
    <h1>Partner Applications</h1>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="applied" <?php echo $filter_status == 'applied' ? 'selected' : ''; ?>>Applied</option>
                    <option value="reviewing" <?php echo $filter_status == 'reviewing' ? 'selected' : ''; ?>>Reviewing</option>
                    <option value="approved" <?php echo $filter_status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $filter_status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="active" <?php echo $filter_status == 'active' ? 'selected' : ''; ?>>Active</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date" class="form-label">Date Range</label>
                <select class="form-select" id="date" name="date">
                    <option value="">All Time</option>
                    <option value="today" <?php echo $filter_date == 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="week" <?php echo $filter_date == 'week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="month" <?php echo $filter_date == 'month' ? 'selected' : ''; ?>>This Month</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="/admin/partner-applications" class="btn btn-secondary ms-2">Reset</a>
            </div>
        </form>
    </div>
    
    <?php
    // Build query
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM bg_company_attributes WHERE company_id = c.company_id AND type = 'partner_application') as attr_count
            FROM bg_companies c 
            WHERE c.source = 'partner_application'";
    
    $params = [];
    
    // Add status filter
    if ($filter_status != 'all') {
        $sql .= " AND c.company_status = :status";
        $params['status'] = $filter_status;
    }
    
    // Add date filter
    if ($filter_date) {
        switch ($filter_date) {
            case 'today':
                $sql .= " AND DATE(c.create_dt) = CURDATE()";
                break;
            case 'week':
                $sql .= " AND c.create_dt >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $sql .= " AND c.create_dt >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }
    }
    
    $sql .= " ORDER BY c.create_dt DESC";
    
    $applications = $database->getrows($sql, $params);
    
    if (empty($applications)) {
        echo '<div class="alert alert-info">No partner applications found matching your criteria.</div>';
    } else {
        foreach ($applications as $app) {
            // Get attributes for this company
            $attr_sql = "SELECT * FROM bg_company_attributes 
                        WHERE company_id = :company_id 
                        AND type = 'partner_application' 
                        ORDER BY name";
            $attributes = $database->getrows($attr_sql, ['company_id' => $app['company_id']]);
            
            // Create attribute lookup
            $attr_lookup = [];
            foreach ($attributes as $attr) {
                $attr_lookup[$attr['name']] = $attr['description'];
            }
            ?>
            
            <div class="application-card">
                <div class="row">
                    <div class="col-md-8">
                        <h3><?php echo htmlspecialchars($app['company_display_name']); ?></h3>
                        <p class="text-muted mb-2">
                            <i class="bi bi-calendar me-2"></i>Applied: <?php echo date('M d, Y g:i A', strtotime($app['create_dt'])); ?>
                            <span class="ms-3"><i class="bi bi-hash me-1"></i>ID: <?php echo $app['company_id']; ?></span>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="status-badge status-<?php echo $app['company_status']; ?>">
                            <?php echo ucfirst($app['company_status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="attribute-list">
                    <?php if (isset($attr_lookup['contact_name'])): ?>
                        <div class="attribute-item">
                            <div class="attribute-name">Contact:</div>
                            <div class="attribute-value"><?php echo htmlspecialchars($attr_lookup['contact_name']); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($attr_lookup['contact_email'])): ?>
                        <div class="attribute-item">
                            <div class="attribute-name">Email:</div>
                            <div class="attribute-value">
                                <a href="mailto:<?php echo htmlspecialchars($attr_lookup['contact_email']); ?>">
                                    <?php echo htmlspecialchars($attr_lookup['contact_email']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($attr_lookup['contact_phone'])): ?>
                        <div class="attribute-item">
                            <div class="attribute-name">Phone:</div>
                            <div class="attribute-value"><?php echo htmlspecialchars($attr_lookup['contact_phone']); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($app['company_url']): ?>
                        <div class="attribute-item">
                            <div class="attribute-name">Website:</div>
                            <div class="attribute-value">
                                <a href="<?php echo htmlspecialchars($app['company_url']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($app['company_url']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($attr_lookup['birthday_offer'])): ?>
                        <div class="attribute-item">
                            <div class="attribute-name">Birthday Offer:</div>
                            <div class="attribute-value"><?php echo nl2br(htmlspecialchars($attr_lookup['birthday_offer'])); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($attr_lookup['additional_info']) && $attr_lookup['additional_info']): ?>
                        <div class="attribute-item">
                            <div class="attribute-name">Additional Info:</div>
                            <div class="attribute-value"><?php echo nl2br(htmlspecialchars($attr_lookup['additional_info'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="mt-3">
                    <a href="/admin/companies.php?id=<?php echo $app['company_id']; ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-eye me-1"></i>View Details
                    </a>
                    <button class="btn btn-sm btn-success ms-2" onclick="updateStatus(<?php echo $app['company_id']; ?>, 'approved')">
                        <i class="bi bi-check-circle me-1"></i>Approve
                    </button>
                    <button class="btn btn-sm btn-warning ms-2" onclick="updateStatus(<?php echo $app['company_id']; ?>, 'reviewing')">
                        <i class="bi bi-clock me-1"></i>Review
                    </button>
                    <button class="btn btn-sm btn-danger ms-2" onclick="updateStatus(<?php echo $app['company_id']; ?>, 'rejected')">
                        <i class="bi bi-x-circle me-1"></i>Reject
                    </button>
                </div>
            </div>
            
            <?php
        }
    }
    ?>
</div>

<script>
function updateStatus(companyId, newStatus) {
    if (confirm('Are you sure you want to change the status to ' + newStatus + '?')) {
        // In a real implementation, this would make an AJAX call to update the status
        alert('Status update functionality would be implemented here');
        // window.location.href = '/admin/update-partner-status.php?id=' + companyId + '&status=' + newStatus;
    }
}
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>