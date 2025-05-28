<?php
if (!isset($componentmode) || $componentmode != 'include') {
    require_once $_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php';
}

// Get company id if not already set
if (empty($company_id)) {
    $company_id = $_GET['cid'] ?? null;
}

// First check if there's any location data
$check_sql = "SELECT COUNT(*) as location_count 
              FROM bg_company_locations 
              WHERE company_id = :company_id";
$check_stmt = $database->prepare($check_sql);
$check_stmt->execute(['company_id' => $company_id]);
$has_locations = $check_stmt->fetchColumn() > 0;

// Only fetch locations if they exist
$locations = [];
if ($has_locations) {
    $sql = "SELECT l.*, 
                   COUNT(DISTINCT r.reward_id) as reward_count
            FROM bg_company_locations l
            LEFT JOIN bg_company_rewards r ON l.location_id = r.location_id 
            WHERE l.company_id = :company_id 
            GROUP BY l.location_id
            ORDER BY l.city, l.state";
    
    $stmt = $database->prepare($sql);
    $stmt->execute(['company_id' => $company_id]);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<div class="container-fluid p-4">
    <!-- Header with Add Location Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Locations</h2>
            <p class="text-muted mb-0">
                <?php if ($has_locations): ?>
                    <?php echo count($locations); ?> locations found
                <?php else: ?>
                    No locations set up yet
                <?php endif; ?>
            </p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLocationModal">
            <i class="bi bi-plus-circle me-2"></i>Add First Location
        </button>
    </div>

    <?php if (!$has_locations): ?>
        <!-- Empty State -->
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="bi bi-geo-alt display-1 text-muted"></i>
            </div>
            <h3>No Locations Added Yet</h3>
            <p class="text-muted mb-4">Add your first location to start managing location-specific rewards and settings.</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                <i class="bi bi-plus-circle me-2"></i>Add First Location
            </button>
        </div>
    <?php else: ?>
        <!-- Location Cards -->
        <div class="row">
            <?php foreach ($locations as $location): ?>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <?php echo htmlspecialchars($location['city'] . ', ' . $location['state']); ?>
                                    </h5>
                                    <p class="text-muted small mb-0">
                                        <?php echo htmlspecialchars($location['address']); ?>
                                    </p>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-link text-muted" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" 
                                               data-bs-target="#editLocationModal" 
                                               data-location-id="<?php echo $location['location_id']; ?>">
                                                <i class="bi bi-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        <?php if ($location['reward_count'] > 0): ?>
                                            <li>
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                   data-bs-target="#manageRewardsModal"
                                                   data-location-id="<?php echo $location['location_id']; ?>">
                                                    <i class="bi bi-gift me-2"></i>Manage Rewards 
                                                    <span class="badge bg-secondary ms-2"><?php echo $location['reward_count']; ?></span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>

                            <!-- Location Details -->
                            <div class="mb-3">
                                <?php if (!empty($location['phone'])): ?>
                                    <div class="d-flex align-items-center text-muted mb-2">
                                        <i class="bi bi-telephone me-2"></i>
                                        <span><?php echo htmlspecialchars($location['phone']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add Location Modal -->
<div class="modal fade" id="addLocationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addLocationForm" action="/admin_actions/save_location.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $has_locations ? 'Add Location' : 'Add First Location'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="address" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" name="state" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">ZIP</label>
                                <input type="text" class="form-control" name="zip_code" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" name="phone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Location</button>
                </div>
            </form>
        </div>
    </div>
</div>