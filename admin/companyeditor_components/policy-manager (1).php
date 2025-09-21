<?php
if (!isset($componentmode) || $componentmode != 'include') {
    require_once $_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php';
}

// Get company id if not already set
if (empty($company_id)) {
    $company_id = $_GET['cid'] ?? null;
}

// Get active policies for this company
$sql = "SELECT p.*, a.description AS url 
        FROM bg_company_policies p
        LEFT JOIN bg_company_attributes a ON a.company_id = p.company_id 
            AND a.type = 'url' 
            AND a.name = p.policy_type
            AND a.grouping = 'policies'
            AND a.status = 'active'
        WHERE p.company_id = :company_id 
        ORDER BY p.policy_type";
$stmt = $database->prepare($sql);
$stmt->execute(['company_id' => $company_id]);
$policies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add required styles
$additionalstyles .= '
<style>
.policy-card {
    transition: all 0.2s ease-in-out;
}

.policy-card:hover {
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}

.verification-badge {
    transition: all 0.3s ease-in-out;
}

.verification-badge.verifying {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.policy-history {
    max-height: 300px;
    overflow-y: auto;
}

.truncate-hash {
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>';
?>

<div class="container-fluid px-4 py-5">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Policy Management</h2>
            <p class="text-muted mb-0"><?php echo count($policies); ?> policies tracked</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPolicyModal">
            <i class="bi bi-plus me-2"></i>Add Policy
        </button>
    </div>

    <!-- Policy Cards -->
    <div class="row g-4">
        <?php foreach ($policies as $policy): ?>
            <div class="col-md-6">
                <div class="card policy-card" data-policy-id="<?php echo $policy['policy_id']; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h3 class="h5 mb-1"><?php echo htmlspecialchars($policy['policy_name']); ?></h3>
                                <p class="text-muted small mb-0">Version <?php echo $policy['version']; ?></p>
                            </div>
                            <div class="d-flex gap-2">
                                <?php if ($policy['status'] === 'verified'): ?>
                                    <span class="badge bg-success verification-badge">
                                        <i class="bi bi-check-circle me-1"></i>Verified
                                    </span>
                                <?php elseif ($policy['status'] === 'changed'): ?>
                                    <span class="badge bg-warning verification-badge">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Changed
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary verification-badge">
                                        <i class="bi bi-question-circle me-1"></i>Unverified
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Policy URL -->
                        <div class="mb-3">
                            <label class="form-label small mb-1">Policy URL</label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control form-control-sm" 
                                       value="<?php echo htmlspecialchars($policy['url'] ?? ''); ?>"
                                       readonly>
                                <button class="btn btn-outline-secondary btn-sm" type="button">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Content Hash -->
                        <div class="mb-3">
                            <label class="form-label small mb-1">Content Hash</label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control form-control-sm font-monospace" 
                                       value="<?php echo htmlspecialchars($policy['content_hash']); ?>"
                                       readonly>
                                <button class="btn btn-outline-secondary btn-sm copy-hash" type="button">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex gap-2">
                            <button type="button" 
                                    class="btn btn-outline-primary btn-sm verify-policy"
                                    data-policy-id="<?php echo $policy['policy_id']; ?>">
                                <i class="bi bi-arrow-repeat me-1"></i>Verify Now
                            </button>
                            <button type="button" 
                                    class="btn btn-outline-secondary btn-sm view-history"
                                    data-policy-id="<?php echo $policy['policy_id']; ?>">
                                <i class="bi bi-clock-history me-1"></i>History
                            </button>
                            <button type="button" 
                                    class="btn btn-outline-secondary btn-sm archive-policy"
                                    data-policy-id="<?php echo $policy['policy_id']; ?>">
                                <i class="bi bi-archive me-1"></i>Archive
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add/Edit Policy Modal -->
<div class="modal fade" id="policyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="policyForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="policyModalTitle">Add Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="policy_id" id="policyId">
                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Policy Type</label>
                        <select class="form-select" name="policy_type" required>
                            <option value="">Select type...</option>
                            <option value="terms">Terms of Service</option>
                            <option value="privacy">Privacy Policy</option>
                            <option value="cookies">Cookie Policy</option>
                            <option value="refund">Refund Policy</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Policy Name</label>
                        <input type="text" class="form-control" name="policy_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Policy URL</label>
                        <input type="url" class="form-control" name="policy_url" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Policy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Policy History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Policy Version History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="policy-history">
                    <div class="list-group list-group-flush" id="historyList">
                        <!-- History items will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include policy manager scripts -->
<script src="/admin/companyeditor_components/policy-manager-scripts.js"></script>
