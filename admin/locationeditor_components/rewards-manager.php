<?php
if (!isset($componentmode) || $componentmode != 'include') {
    require_once $_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php';
}

// Location data should be available from parent page
if (!isset($location) || !is_array($location) || !isset($location_id)) {
    echo '<div class="alert alert-danger">Location data not available</div>';
    exit;
}

// Fetch location-specific rewards
$sql = "SELECT r.*
        FROM bg_company_rewards r
        WHERE r.location_id = :location_id
        ORDER BY r.reward_type, r.reward_name";

$stmt = $database->query($sql, ['location_id' => $location_id]);
$rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get company-wide rewards that could be overridden
$company_sql = "SELECT r.*
                FROM bg_company_rewards r
                WHERE r.company_id = :company_id
                AND r.location_id IS NULL
                ORDER BY r.reward_type, r.reward_name";

$company_stmt = $database->query($company_sql, ['company_id' => $company_id]);
$company_rewards = $company_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Location-Specific Rewards</h3>
            <p class="text-muted mb-0">
                Manage rewards specific to this location. These override company-wide rewards.
            </p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLocationRewardModal">
            <i class="bi bi-plus-circle me-2"></i>Add Reward
        </button>
    </div>

    <?php if (empty($rewards)): ?>
        <!-- Empty State -->
        <div class="text-center py-5">
            <i class="bi bi-gift display-1 text-muted mb-3"></i>
            <h4>No Location-Specific Rewards</h4>
            <p class="text-muted mb-4">
                This location inherits all rewards from the main company profile.<br>
                Add location-specific rewards to override or supplement company rewards.
            </p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLocationRewardModal">
                <i class="bi bi-plus-circle me-2"></i>Add First Reward
            </button>
        </div>
    <?php else: ?>
        <!-- Rewards List -->
        <div class="row">
            <?php foreach ($rewards as $reward): ?>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($reward['reward_name']); ?></h5>
                                    <p class="text-muted small mb-2">
                                        <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($reward['reward_type'] ?? 'Unknown Type'); ?>
                                    </p>
                                    <?php if (!empty($reward['reward_description'])): ?>
                                        <p class="mb-2"><?php echo htmlspecialchars($reward['reward_description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="editLocationReward(<?php echo $reward['reward_id']; ?>)">
                                                <i class="bi bi-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" onclick="deleteLocationReward(<?php echo $reward['reward_id']; ?>)">
                                                <i class="bi bi-trash me-2"></i>Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <span class="badge bg-<?php echo $reward['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($reward['status']); ?>
                                </span>
                                <?php if ($reward['reward_type'] === 'APP'): ?>
                                    <span class="badge bg-info">
                                        <i class="bi bi-phone me-1"></i>App Only
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Company Rewards Section -->
    <?php if (!empty($company_rewards)): ?>
        <div class="mt-5">
            <h4 class="mb-3">Company-Wide Rewards</h4>
            <p class="text-muted mb-3">These rewards apply to all locations unless overridden.</p>
            
            <div class="list-group">
                <?php foreach ($company_rewards as $reward): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($reward['reward_name']); ?></h6>
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($reward['reward_type'] ?? 'Unknown Type'); ?>
                                    <?php if ($reward['status'] !== 'active'): ?>
                                        <span class="badge bg-secondary ms-2"><?php echo ucfirst($reward['status']); ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="overrideCompanyReward(<?php echo $reward['reward_id']; ?>)">
                                <i class="bi bi-geo-alt me-1"></i>Override for Location
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Location Reward Modal -->
<div class="modal fade" id="addLocationRewardModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addLocationRewardForm" action="/admin/ajax/save-location-reward.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Location Reward</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="location_id" value="<?php echo $location_id; ?>">
                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Reward Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="reward_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reward Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="reward_type" required>
                            <option value="">Select Type</option>
                            <option value="BIRTHDAY">Birthday Reward</option>
                            <option value="ANNIVERSARY">Anniversary Reward</option>
                            <option value="APP">App-Only Reward</option>
                            <option value="SIGNUP">Sign-up Bonus</option>
                            <option value="SPECIAL">Special Offer</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="reward_description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Reward</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editLocationReward(rewardId) {
    // Implementation for editing rewards
    alert('Edit functionality would be implemented here for reward ID: ' + rewardId);
}

function deleteLocationReward(rewardId) {
    if (confirm('Are you sure you want to delete this location-specific reward?')) {
        // Implementation for deleting rewards
        alert('Delete functionality would be implemented here for reward ID: ' + rewardId);
    }
}

function overrideCompanyReward(rewardId) {
    // Implementation for overriding company rewards
    alert('Override functionality would be implemented here for reward ID: ' + rewardId);
}

// Handle form submission
document.getElementById('addLocationRewardForm').addEventListener('submit', function(e) {
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
            alert('Error: ' + (data.message || 'Failed to save reward'));
        }
    })
    .catch(error => {
        alert('Error saving reward: ' + error.message);
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});
</script>