<?php
if (!isset($componentmode) || $componentmode != 'include') {
    require_once $_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php';
}

// Get company id if not already set
if (empty($company_id)) {
    $company_id = $_REQUEST['cid'] ?? null;
}

// Get reward categories from reference table
$sql = "SELECT type, name FROM bg_ref_reward_categories";
$stmt = $database->prepare($sql);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get company locations

// Get company locations
$sql = "SELECT 
            location_id,
            address,
            city,
            state,
            zip_code,
            country,
            phone_number,
            business_hours,
            is_verified,
            status,
            CONCAT(
                CASE 
                    WHEN city IS NOT NULL AND state IS NOT NULL THEN CONCAT(address, ', ', city, ', ', state)
                    WHEN city IS NOT NULL THEN CONCAT(address, ', ', city)
                    ELSE address
                END,
                CASE 
                    WHEN zip_code IS NOT NULL THEN CONCAT(' ', zip_code)
                    ELSE ''
                END
            ) as location_name
        FROM bg_company_locations 
        WHERE company_id = :company_id 
        AND status = 'active'
        ORDER BY city, address";
// First get locations
$sql = "SELECT location_id, 
        CONCAT(address, ', ', city, ', ', state, ' ', zip_code) as location_name,
        address, city, state, zip_code 
        FROM bg_company_locations 
        WHERE company_id = :company_id 
        AND status = 'active'
        ORDER BY city, address";
$stmt = $database->prepare($sql);
$stmt->execute(['company_id' => $company_id]);
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get company rewards with their locations - using address instead of location_name
$sql = "SELECT r.*, l.address as location_name, l.city, l.state 
        FROM bg_company_rewards r
        LEFT JOIN bg_company_locations l ON r.location_id = l.location_id
        WHERE r.company_id = :company_id
        ORDER BY r.location_id, r.reward_name";
$stmt = $database->prepare($sql);
$stmt->execute(['company_id' => $company_id]);
$rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize rewards by type
$companyRewards = array_filter($rewards, fn($r) => empty($r['location_id']));
$locationRewards = array_filter($rewards, fn($r) => !empty($r['location_id']));

// Add required styles
$additionalstyles .= '
<style>
/* Modern Tab Navigation - matching plan_editor.php */
.nav-tabs-modern {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 0;
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

.reward-card {
    transition: all 0.2s ease-in-out;
}

.reward-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}

/* Override for standard nav-tabs to prevent conflicts */
.nav-tabs:not(.nav-tabs-modern) .nav-link {
    position: relative;
    border: none;
    color: #6B7280;
    padding: 1rem 1.5rem;
}

.nav-tabs:not(.nav-tabs-modern) .nav-link.active {
    color: #2563EB;
    background: none;
    border-bottom: 2px solid #2563EB;
}

.nav-tabs:not(.nav-tabs-modern) .nav-link:hover:not(.active) {
    border-bottom: 2px solid #E5E7EB;
}

.text-counter {
    position: absolute;
    right: 10px;
    bottom: 5px;
    font-size: 0.75rem;
    color: #6B7280;
}

.reward-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
}

.reward-badge.physical {
    background-color: #E0F2FE;
    color: #0369A1;
}

.reward-badge.cash {
    background-color: #ECFDF5;
    color: #047857;
}

.reward-badge.points {
    background-color: #FEF3C7;
    color: #B45309;
}

.textarea-container {
    position: relative;
}

.character-count {
    position: absolute;
    right: 8px;
    bottom: 8px;
    font-size: 0.75rem;
    color: #6B7280;
}
</style>
';

// Output the styles
echo $additionalstyles;
?>
<div class="container-fluid px-4 py-3">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Reward Management</h2>
            <p class="text-muted mb-0">
                <?php echo count($rewards); ?> total rewards (<?php echo count($companyRewards); ?> company-wide, 
                <?php echo count($locationRewards); ?> location-specific)
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="createReward()">
            <i class="bi bi-plus-lg me-2"></i>Add New Reward
        </button>
    </div>

    <!-- Modern Navigation Tabs -->
    <div class="card">
        <div class="card-header bg-white border-0 pt-2 pb-0">
            <nav class="nav-tabs-modern">
                <a class="nav-tab-item active" data-bs-toggle="tab" href="#companyRewards">
                    <i class="bi bi-building me-2"></i>Company Rewards
                </a>
                <a class="nav-tab-item" data-bs-toggle="tab" href="#locationRewards">
                    <i class="bi bi-geo-alt me-2"></i>Location Rewards
                </a>
            </nav>
        </div>

        <div class="card-body pt-3">
            <div class="tab-content">
                <!-- Company Rewards Tab -->
                <div class="tab-pane fade show active" id="companyRewards">
                    <?php if (empty($companyRewards)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-gift text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">No company-wide rewards defined yet</p>
                            <button type="button" class="btn btn-primary mt-3" onclick="createReward()">
                                Add First Reward
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($companyRewards as $reward): ?>
                                <div class="col-md-6">
                                    <div class="card reward-card h-100" data-reward-id="<?php echo $reward['reward_id']; ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="card-title mb-1">
                                                        <?php echo htmlspecialchars($reward['reward_name'] ?? ''); ?>
                                                    </h5>
                                                    <span class="reward-badge <?php echo $reward['reward_type']; ?>">
                                                        <?php echo ucfirst($reward['reward_type']); ?>
                                                    </span>
                                                </div>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="editReward(<?php echo $reward['reward_id']; ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteReward(<?php echo $reward['reward_id']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <p class="card-text text-muted mb-3">
                                                <?php echo htmlspecialchars($reward['reward_description_short'] ?? ''); ?>
                                            </p>

                                            <?php if ($reward['reward_value'] || $reward['cash_value']): ?>
                                                <div class="d-flex gap-3">
                                                    <?php if ($reward['reward_value']): ?>
                                                        <div>
                                                            <small class="text-muted">Reward Value</small>
                                                            <div class="fw-bold">
                                                                $<?php echo number_format($reward['reward_value'], 2); ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($reward['cash_value']): ?>
                                                        <div>
                                                            <small class="text-muted">Cash Value</small>
                                                            <div class="fw-bold">
                                                                $<?php echo number_format($reward['cash_value'], 2); ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($reward['minage'] || $reward['maxage'] || $reward['mindaysstart']): ?>
                                            <div class="card-footer bg-light">
                                                <div class="d-flex gap-3">
                                                    <?php if ($reward['minage'] || $reward['maxage']): ?>
                                                        <small class="text-muted">
                                                            Age: <?php echo $reward['minage'] ?: '0'; ?> - 
                                                            <?php echo $reward['maxage'] ?: '∞'; ?> years
                                                        </small>
                                                    <?php endif; ?>
                                                    <?php if ($reward['mindaysstart']): ?>
                                                        <small class="text-muted">
                                                            Starts: <?php echo $reward['mindaysstart']; ?> days before
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Location Rewards Tab -->
                <div class="tab-pane fade" id="locationRewards">
                    <?php if (empty($locations) && empty($locationRewards)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-geo-alt text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">No locations defined yet</p>
                            <button type="button" class="btn btn-primary mt-3" onclick="switchToLocationsTab()">
                                Manage Locations
                            </button>
                        </div>
                    <?php elseif (empty($locations) && !empty($locationRewards)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">Location rewards exist but location data is missing</p>
                            <p class="text-muted">Please update location information</p>
                            <button type="button" class="btn btn-primary mt-3" onclick="switchToLocationsTab()">
                                Manage Locations
                            </button>
                        </div>
                    <?php elseif (!empty($locations) && empty($locationRewards)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-gift text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">No location-specific rewards defined</p>
                            <p class="text-muted">Location rewards can be added to individual locations</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($locations as $location): ?>
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="bi bi-geo-alt me-2"></i>
                                        <?php echo htmlspecialchars($location['location_name'] ?? ''); ?>
                                    </h5>
                                    <button type="button" class="btn btn-sm btn-primary" 
                                            onclick="createReward(<?php echo $location['location_id']; ?>)">
                                        Add Location Reward
                                    </button>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    $locationRewardsList = array_filter(
                                        $locationRewards, 
                                        fn($r) => $r['location_id'] === $location['location_id']
                                    );
                                    
                                    if (empty($locationRewardsList)): 
                                    ?>
                                        <p class="text-muted text-center py-4">
                                            No rewards defined for this location
                                        </p>
                                    <?php else: ?>
                                        <div class="row g-4">
                                            <?php foreach ($locationRewardsList as $reward): ?>
                                                <div class="col-md-6">
                                                    <!-- Reward card markup - same as company rewards -->
                                                    <div class="card reward-card h-100" 
                                                         data-reward-id="<?php echo $reward['reward_id']; ?>">
                                                        <!-- Card content here - same structure as company rewards -->
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reward Editor Modal -->
<div class="modal fade" id="rewardEditorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="rewardForm" method="post" action="/admin_actions/save_reward.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="rewardModalTitle">Add New Reward</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                    <input type="hidden" name="reward_id" id="reward_id">
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Reward Name</label>
                            <input type="text" class="form-control" name="reward_name" id="reward_name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reward Type</label>
                            <select class="form-select" name="reward_type" id="reward_type" required>
                                <option value="physical">Physical</option>
                                <option value="cash">Cash</option>
                                <option value="points">Points</option>
                                <option value="APP">App Only</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Short Description</label>
                        <textarea class="form-control" name="reward_description_short" id="reward_description_short" rows="2" maxlength="200"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Full Description</label>
                        <textarea class="form-control" name="reward_description_long" id="reward_description_long" rows="4"></textarea>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Reward Value ($)</label>
                            <input type="number" class="form-control" name="reward_value" id="reward_value" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cash Value ($)</label>
                            <input type="number" class="form-control" name="cash_value" id="cash_value" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Location</label>
                            <select class="form-select" name="location_id" id="location_id">
                                <option value="">Company-wide</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo $location['location_id']; ?>">
                                        <?php echo htmlspecialchars($location['location_name'] ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Min Age</label>
                            <input type="number" class="form-control" name="minage" id="minage" min="0" max="120">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Max Age</label>
                            <input type="number" class="form-control" name="maxage" id="maxage" min="0" max="150">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Days Before Birthday</label>
                            <input type="number" class="form-control" name="mindaysstart" id="mindaysstart" min="0" max="365">
                        </div>
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
// Store rewards data for easy access
const rewardsData = <?php echo json_encode(array_values($rewards), JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
console.log('rewardsData loaded:', rewardsData.length, 'rewards');

function createReward(locationId = null) {
    // Clear the form
    document.getElementById('rewardForm').reset();
    document.getElementById('reward_id').value = '';
    document.getElementById('rewardModalTitle').textContent = 'Add New Reward';
    
    // If location ID provided, pre-select it
    if (locationId) {
        document.getElementById('location_id').value = locationId;
    }
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('rewardEditorModal'));
    modal.show();
}

function editReward(rewardId) {
    console.log('editReward called with ID:', rewardId, 'type:', typeof rewardId);
    console.log('Searching in', rewardsData.length, 'rewards');

    // Find the reward data - convert both to string for comparison
    const reward = rewardsData.find(r => String(r.reward_id) === String(rewardId));

    if (!reward) {
        console.error('Reward not found:', rewardId);
        console.log('Available reward IDs:', rewardsData.map(r => r.reward_id));
        alert('Reward data not found. Please refresh the page and try again.');
        return;
    }

    console.log('Found reward - FULL OBJECT:', JSON.stringify(reward, null, 2));

    const modalEl = document.getElementById('rewardEditorModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    // Update modal title
    document.getElementById('rewardModalTitle').textContent = 'Edit Reward';

    // Remove any existing event listener to prevent duplicates
    modalEl.removeEventListener('shown.bs.modal', modalEl._populateHandler);

    // Create handler function to populate form AFTER modal is fully shown
    modalEl._populateHandler = function() {
        console.log('Modal shown - now populating form fields');

        // Use ?? (nullish coalescing) instead of || to preserve 0 values
        document.getElementById('reward_id').value = reward.reward_id ?? '';
        document.getElementById('reward_name').value = reward.reward_name ?? '';
        document.getElementById('reward_type').value = reward.reward_type ?? 'physical';
        document.getElementById('reward_description_short').value = reward.reward_description_short ?? '';
        document.getElementById('reward_description_long').value = reward.reward_description_long ?? '';
        document.getElementById('reward_value').value = reward.reward_value ?? '';
        document.getElementById('cash_value').value = reward.cash_value ?? '';
        document.getElementById('location_id').value = reward.location_id ?? '';
        document.getElementById('minage').value = reward.minage ?? '';
        document.getElementById('maxage').value = reward.maxage ?? '';
        document.getElementById('mindaysstart').value = reward.mindaysstart ?? '';

        console.log('Form populated with values:', {
            reward_id: document.getElementById('reward_id').value,
            reward_name: document.getElementById('reward_name').value,
            reward_type: document.getElementById('reward_type').value,
            reward_description_short: document.getElementById('reward_description_short').value,
            reward_description_long: document.getElementById('reward_description_long').value
        });

        // Remove listener after populating (one-time use)
        modalEl.removeEventListener('shown.bs.modal', modalEl._populateHandler);
    };

    // Add listener for when modal is fully shown
    modalEl.addEventListener('shown.bs.modal', modalEl._populateHandler);

    // Show the modal - form will be populated after it's visible
    modal.show();
}

function deleteReward(rewardId) {
    if (confirm('Are you sure you want to delete this reward?')) {
        // Submit deletion request
        window.location.href = '/admin_actions/delete_reward.php?reward_id=' + rewardId + '&company_id=<?php echo $company_id; ?>';
    }
}

function switchToLocationsTab() {
    // Find the parent company editor page locations tab
    const locationsTab = window.parent.document.querySelector('#locations-tab');
    if (locationsTab) {
        locationsTab.click();
    }
}
</script>
