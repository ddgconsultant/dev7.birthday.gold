<?php
if (!isset($componentmode) || $componentmode != 'include') {
    require_once $_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php';
}

// Location data should be available from parent page
if (!isset($location) || !is_array($location) || !isset($location_id)) {
    echo '<div class="alert alert-danger">Location data not available</div>';
    exit;
}

// Get location policies - check if table exists first
$location_policies = [];
try {
    $policies_sql = "SELECT * FROM bg_location_policies 
                    WHERE location_id = :location_id 
                    ORDER BY policy_type, create_dt DESC";
    $policies_stmt = $database->prepare($policies_sql);
    $policies_stmt->execute(['location_id' => $location_id]);
    $location_policies = $policies_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table doesn't exist, use empty array
    $location_policies = [];
}

// Get company policies that can be overridden - check if table exists first
$company_policies = [];
try {
    $company_policies_sql = "SELECT * FROM bg_company_policies 
                            WHERE company_id = :company_id 
                            AND status = 'active'
                            ORDER BY policy_type";
    $company_stmt = $database->prepare($company_policies_sql);
    $company_stmt->execute(['company_id' => $company_id]);
    $company_policies = $company_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table doesn't exist, use empty array
    $company_policies = [];
}

// Policy types
$policy_types = [
    'return' => ['name' => 'Return Policy', 'icon' => 'bi-arrow-return-left'],
    'refund' => ['name' => 'Refund Policy', 'icon' => 'bi-cash-coin'],
    'exchange' => ['name' => 'Exchange Policy', 'icon' => 'bi-arrow-left-right'],
    'warranty' => ['name' => 'Warranty Policy', 'icon' => 'bi-shield-check'],
    'price_match' => ['name' => 'Price Match Policy', 'icon' => 'bi-tag'],
    'rewards_terms' => ['name' => 'Rewards Terms', 'icon' => 'bi-gift'],
    'privacy' => ['name' => 'Privacy Policy', 'icon' => 'bi-lock'],
    'terms' => ['name' => 'Terms of Service', 'icon' => 'bi-file-text'],
    'custom' => ['name' => 'Custom Policy', 'icon' => 'bi-file-plus']
];
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Location Policies</h3>
            <p class="text-muted mb-0">
                Override company policies or add location-specific policies
            </p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPolicyModal">
            <i class="bi bi-plus-circle me-2"></i>Add Policy
        </button>
    </div>

    <!-- Active Location Policies -->
    <?php if (empty($location_policies)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No location-specific policies defined. This location uses all company-wide policies.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($location_policies as $policy): ?>
                <?php $type_info = $policy_types[$policy['policy_type']] ?? $policy_types['custom']; ?>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <i class="<?php echo $type_info['icon']; ?> me-2"></i>
                                        <?php echo htmlspecialchars($policy['policy_title'] ?? $type_info['name']); ?>
                                    </h5>
                                    <p class="text-muted small mb-0">
                                        Type: <?php echo $type_info['name']; ?>
                                        <?php if ($policy['overrides_company']): ?>
                                            <span class="badge bg-warning ms-2">Overrides Company Policy</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="editPolicy(<?php echo $policy['policy_id']; ?>)">
                                                <i class="bi bi-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="viewPolicy(<?php echo $policy['policy_id']; ?>)">
                                                <i class="bi bi-eye me-2"></i>View Full Text
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" onclick="deletePolicy(<?php echo $policy['policy_id']; ?>)">
                                                <i class="bi bi-trash me-2"></i>Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <?php if (!empty($policy['summary'])): ?>
                                <p class="card-text mt-2"><?php echo htmlspecialchars($policy['summary']); ?></p>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <small class="text-muted">
                                    <?php if ($policy['effective_date']): ?>
                                        Effective: <?php echo date('M j, Y', strtotime($policy['effective_date'])); ?>
                                    <?php else: ?>
                                        Effective immediately
                                    <?php endif; ?>
                                </small>
                                <span class="badge bg-<?php echo $policy['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($policy['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Company Policies Section -->
    <div class="mt-5">
        <h4 class="mb-3">Company-Wide Policies</h4>
        <p class="text-muted mb-3">
            These policies apply to this location unless overridden above.
        </p>
        
        <?php if (empty($company_policies)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                No company-wide policies are currently defined.
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($company_policies as $policy): ?>
                    <?php 
                    $type_info = $policy_types[$policy['policy_type']] ?? $policy_types['custom'];
                    $is_overridden = false;
                    foreach ($location_policies as $loc_policy) {
                        if ($loc_policy['policy_type'] === $policy['policy_type'] && $loc_policy['overrides_company']) {
                            $is_overridden = true;
                            break;
                        }
                    }
                    ?>
                    <div class="list-group-item <?php echo $is_overridden ? 'list-group-item-light' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">
                                    <i class="<?php echo $type_info['icon']; ?> me-2"></i>
                                    <?php echo htmlspecialchars($policy['policy_title'] ?? $type_info['name']); ?>
                                    <?php if ($is_overridden): ?>
                                        <span class="badge bg-secondary ms-2">Overridden</span>
                                    <?php endif; ?>
                                </h6>
                                <?php if (!empty($policy['summary'])): ?>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($policy['summary']); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if (!$is_overridden): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="overridePolicy('<?php echo $policy['policy_type']; ?>', '<?php echo htmlspecialchars($policy['policy_title'] ?? $type_info['name'], ENT_QUOTES); ?>')">
                                    <i class="bi bi-geo-alt me-1"></i>Override for Location
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Policy Templates -->
    <div class="mt-5">
        <h4 class="mb-3">Quick Add Policy Templates</h4>
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-clock display-4 text-muted mb-2"></i>
                        <h6>Extended Return Period</h6>
                        <p class="small text-muted">90-day return policy for this location</p>
                        <button class="btn btn-sm btn-outline-primary" onclick="addTemplate('extended_return')">
                            Use Template
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-percent display-4 text-muted mb-2"></i>
                        <h6>Holiday Return Policy</h6>
                        <p class="small text-muted">Special holiday return extension</p>
                        <button class="btn btn-sm btn-outline-primary" onclick="addTemplate('holiday_return')">
                            Use Template
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-shield-check display-4 text-muted mb-2"></i>
                        <h6>Extended Warranty</h6>
                        <p class="small text-muted">Location-specific warranty terms</p>
                        <button class="btn btn-sm btn-outline-primary" onclick="addTemplate('extended_warranty')">
                            Use Template
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Policy Modal -->
<div class="modal fade" id="addPolicyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addPolicyForm" action="/admin/ajax/save-location-policy.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Location Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="location_id" value="<?php echo $location_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Policy Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="policy_type" id="policyType" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($policy_types as $key => $type): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $type['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Policy Title</label>
                                <input type="text" class="form-control" name="policy_title" id="policyTitle"
                                       placeholder="Auto-filled based on type">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Summary</label>
                        <textarea class="form-control" name="summary" rows="2" 
                                  placeholder="Brief description of the policy"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Full Policy Text <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="policy_text" rows="6" required
                                  placeholder="Enter the complete policy text"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Effective Date</label>
                                <input type="date" class="form-control" name="effective_date">
                                <small class="form-text text-muted">Leave blank for immediate effect</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active">Active</option>
                                    <option value="draft">Draft</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="overridesCompany" 
                               name="overrides_company" value="1">
                        <label class="form-check-label" for="overridesCompany">
                            This policy overrides the company-wide policy of the same type
                        </label>
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

<script>
// Auto-fill policy title based on type
document.getElementById('policyType').addEventListener('change', function() {
    const policyTypes = <?php echo json_encode($policy_types); ?>;
    const titleField = document.getElementById('policyTitle');
    
    if (this.value && policyTypes[this.value]) {
        titleField.value = policyTypes[this.value].name;
    }
});

// Handle form submission
document.getElementById('addPolicyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            throw new Error(data.message || 'Failed to save policy');
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

function editPolicy(policyId) {
    // Implementation for editing policies
    alert('Edit functionality would be implemented here for policy ID: ' + policyId);
}

function viewPolicy(policyId) {
    // Implementation for viewing full policy text
    alert('View functionality would be implemented here for policy ID: ' + policyId);
}

function deletePolicy(policyId) {
    if (confirm('Are you sure you want to delete this location policy?')) {
        // Implementation for deleting policies
        alert('Delete functionality would be implemented here for policy ID: ' + policyId);
    }
}

function overridePolicy(policyType, policyTitle) {
    // Pre-fill the add policy modal with override settings
    document.getElementById('policyType').value = policyType;
    document.getElementById('policyTitle').value = policyTitle + ' (Location Override)';
    document.getElementById('overridesCompany').checked = true;
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('addPolicyModal'));
    modal.show();
}

function addTemplate(templateType) {
    const templates = {
        'extended_return': {
            type: 'return',
            title: 'Extended 90-Day Return Policy',
            summary: 'This location offers an extended 90-day return period',
            text: 'Items purchased at this location may be returned within 90 days of purchase for a full refund or exchange. Original receipt required. Items must be in original condition with all tags attached.'
        },
        'holiday_return': {
            type: 'return',
            title: 'Holiday Return Extension',
            summary: 'Extended returns for holiday purchases',
            text: 'Items purchased between November 1 and December 31 may be returned until January 31 of the following year. Standard return conditions apply.'
        },
        'extended_warranty': {
            type: 'warranty',
            title: 'Extended Location Warranty',
            summary: 'Additional warranty coverage at this location',
            text: 'This location offers an extended 2-year warranty on all purchases. This is in addition to any manufacturer warranty. Coverage includes defects in materials and workmanship.'
        }
    };
    
    const template = templates[templateType];
    if (template) {
        document.getElementById('policyType').value = template.type;
        document.getElementById('policyTitle').value = template.title;
        document.querySelector('textarea[name="summary"]').value = template.summary;
        document.querySelector('textarea[name="policy_text"]').value = template.text;
        
        const modal = new bootstrap.Modal(document.getElementById('addPolicyModal'));
        modal.show();
    }
}
</script>