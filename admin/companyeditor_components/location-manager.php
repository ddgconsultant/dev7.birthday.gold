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

<div class="container-fluid px-4 py-3">
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
        <div>
            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                <i class="bi bi-plus-circle me-2"></i>Add Location
            </button>
            <button type="button" class="btn btn-secondary" id="refreshLocationsBtn" 
                    data-company-id="<?php echo $company_id; ?>" 
                    title="Run automated location extraction">
                <i class="bi bi-arrow-clockwise me-2"></i>Refresh Locations
            </button>
        </div>
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
                                        <?php 
                                        // Use location_name if available, otherwise fall back to city, state
                                        $display_name = !empty($location['location_name']) 
                                            ? htmlspecialchars($location['location_name'])
                                            : htmlspecialchars($location['city'] . ', ' . $location['state']);
                                        echo $display_name;
                                        ?>
                                    </h5>
                                    <p class="text-muted small mb-0">
                                        <?php echo htmlspecialchars($location['address']); ?>
                                        <?php if (!empty($location['city']) && !empty($location['state'])): ?>
                                            <br><?php echo htmlspecialchars($location['city'] . ', ' . $location['state'] . ' ' . ($location['zip_code'] ?? '')); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-link text-muted" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="/admin/location-editor.php?lid=<?php echo $location['location_id']; ?>&cid=<?php echo $company_id; ?>">
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
                                <?php if (!empty($location['phone_number'])): ?>
                                    <div class="d-flex align-items-center text-muted mb-2">
                                        <i class="bi bi-telephone me-2"></i>
                                        <span><?php echo htmlspecialchars($location['phone_number']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($location['business_hours'])): ?>
                                    <div class="d-flex align-items-start text-muted mb-2">
                                        <i class="bi bi-clock me-2"></i>
                                        <span class="small"><?php echo nl2br(htmlspecialchars($location['business_hours'])); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($location['location_url'])): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-link-45deg me-2"></i>
                                        <a href="<?php echo htmlspecialchars($location['location_url']); ?>" 
                                           target="_blank" 
                                           class="small text-decoration-none">
                                            View Location Page
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($location['is_verified'] == 1): ?>
                                    <div class="d-flex align-items-center text-success mb-2">
                                        <i class="bi bi-check-circle me-2"></i>
                                        <span class="small">Verified Location</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex align-items-center text-muted small">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <span>Source: <?php echo htmlspecialchars($location['source'] ?? 'Manual'); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($location['reward_count'] > 0): ?>
                                <!-- Rewards Badge -->
                                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                                    <span class="text-muted small">
                                        <i class="bi bi-gift me-1"></i>
                                        <?php echo $location['reward_count']; ?> reward<?php echo $location['reward_count'] > 1 ? 's' : ''; ?>
                                    </span>
                                    <a href="#" class="btn btn-sm btn-outline-primary"
                                       data-bs-toggle="modal"
                                       data-bs-target="#manageRewardsModal"
                                       data-location-id="<?php echo $location['location_id']; ?>">
                                        Manage
                                    </a>
                                </div>
                            <?php endif; ?>
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
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Location Name</label>
                        <input type="text" class="form-control" name="location_name" placeholder="e.g., Downtown Austin">
                        <small class="form-text text-muted">Optional - will use City, State if not provided</small>
                    </div>
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
                        <input type="text" class="form-control" name="phone_number" placeholder="555-123-4567">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location URL</label>
                        <input type="url" class="form-control" name="location_url" placeholder="https://example.com/locations/austin">
                        <small class="form-text text-muted">Optional - link to this specific location's page</small>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Refresh Locations button
    const refreshBtn = document.getElementById('refreshLocationsBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            const companyId = this.getAttribute('data-company-id');
            const btn = this;
            const originalHtml = btn.innerHTML;
            
            // Disable button and show loading
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Extracting...';
            
            // Call the location grabber
            fetch(`/admin_actions/abo/abo_grablocations.php?rawid=${companyId}&retrigger=1`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    let message = 'Location extraction completed. ';
                    if (data.locations_summary) {
                        message += `Found: ${data.locations_summary.found}, `;
                        message += `Inserted: ${data.locations_summary.inserted}, `;
                        message += `Updated: ${data.locations_summary.updated}`;
                    }
                    alert(message);
                    
                    // Reload the page if any locations were processed
                    if (data.locations_summary && (data.locations_summary.inserted > 0 || data.locations_summary.updated > 0)) {
                        location.reload();
                    }
                } else {
                    alert('Error: ' + (data.message || 'Location extraction failed'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to extract locations. Please try again.');
            })
            .finally(() => {
                // Re-enable button
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        });
    }
});
</script>