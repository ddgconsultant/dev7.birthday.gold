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
$sql = "SELECT location_id, address, city, state, zip_code 
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
.reward-card {
    transition: all 0.2s ease-in-out;
}

.reward-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}

.nav-tabs .nav-link {
    position: relative;
    border: none;
    color: #6B7280;
    padding: 1rem 1.5rem;
}

.nav-tabs .nav-link.active {
    color: #2563EB;
    background: none;
    border-bottom: 2px solid #2563EB;
}

.nav-tabs .nav-link:hover:not(.active) {
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
</style>';
?>

<div class="container-fluid px-4 py-5">
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

    <!-- Navigation Tabs -->
    <div class="card">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#companyRewards">
                        <i class="bi bi-building me-2"></i>Company Rewards
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#locationRewards">
                        <i class="bi bi-geo-alt me-2"></i>Location Rewards
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body">
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
                                                        <?php echo htmlspecialchars($reward['reward_name']); ?>
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
                                                <?php echo htmlspecialchars($reward['reward_description_short']); ?>
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
                    <?php if (empty($locations)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-geo-alt text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">No locations defined yet</p>
                            <a href="/admin/locations" class="btn btn-primary mt-3">
                                Manage Locations
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($locations as $location): ?>
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="bi bi-geo-alt me-2"></i>
                                        <?php echo htmlspecialchars($location['location_name']); ?>
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
                <!-- Modal content here - form fields for editing rewards -->
            </form>
        </div>
    </div>
</div>
