<?php
if (!isset($componentmode) || $componentmode != 'include') {
    require_once $_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php';
}

// Location data should be available from parent page
if (!isset($location) || !is_array($location) || !isset($location_id)) {
    echo '<div class="alert alert-danger">Location data not available</div>';
    exit;
}

// Get location attributes - check if table exists first
$attributes = [];
try {
    $attributes_sql = "SELECT * FROM bg_location_attributes 
                      WHERE location_id = :location_id 
                      ORDER BY type, name";
    $attributes_stmt = $database->prepare($attributes_sql);
    $attributes_stmt->execute(['location_id' => $location_id]);
    $attributes = $attributes_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table doesn't exist, use empty array
    $attributes = [];
}

// Group attributes by type
$grouped_attributes = [];
foreach ($attributes as $attr) {
    $grouped_attributes[$attr['type']][] = $attr;
}
?>

<div class="container-fluid px-0">
    <h3 class="mb-4">Advanced Settings</h3>
    
    <!-- Location Status & Visibility -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Status & Visibility</h5>
        </div>
        <div class="card-body">
            <form id="statusForm" method="POST" action="/admin/ajax/save-location-status.php">
                <input type="hidden" name="location_id" value="<?php echo $location_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Location Status</label>
                            <select class="form-select" name="status">
                                <option value="active" <?php echo ($location['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($location['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="closed" <?php echo ($location['status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Permanently Closed</option>
                                <option value="seasonal" <?php echo ($location['status'] ?? '') === 'seasonal' ? 'selected' : ''; ?>>Seasonal</option>
                                <option value="coming_soon" <?php echo ($location['status'] ?? '') === 'coming_soon' ? 'selected' : ''; ?>>Coming Soon</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Visibility</label>
                            <select class="form-select" name="visibility">
                                <option value="public" <?php echo ($location['visibility'] ?? 'public') === 'public' ? 'selected' : ''; ?>>Public - Show on all searches</option>
                                <option value="hidden" <?php echo ($location['visibility'] ?? '') === 'hidden' ? 'selected' : ''; ?>>Hidden - Do not show in searches</option>
                                <option value="members_only" <?php echo ($location['visibility'] ?? '') === 'members_only' ? 'selected' : ''; ?>>Members Only</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="isTestLocation" 
                           name="is_test_location" value="1"
                           <?php echo ($location['is_test_location'] ?? 0) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="isTestLocation">
                        Mark as Test Location
                        <small class="d-block text-muted">Test locations are excluded from reports and analytics</small>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Update Status
                </button>
            </form>
        </div>
    </div>

    <!-- Location Attributes -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Custom Attributes</h5>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAttributeModal">
                <i class="bi bi-plus-circle me-1"></i>Add Attribute
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($attributes)): ?>
                <p class="text-muted mb-0">No custom attributes defined for this location.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Value</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attributes as $attr): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($attr['type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($attr['name']); ?></td>
                                    <td><?php echo htmlspecialchars($attr['description'] ?? $attr['value'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $attr['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($attr['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-link text-danger" onclick="deleteAttribute(<?php echo $attr['attribute_id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Integration Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Integration Settings</h5>
        </div>
        <div class="card-body">
            <form id="integrationForm" method="POST" action="/admin/ajax/save-location-integration.php">
                <input type="hidden" name="location_id" value="<?php echo $location_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                
                <div class="mb-3">
                    <label class="form-label">External Location ID</label>
                    <input type="text" class="form-control" name="external_id" 
                           value="<?php echo htmlspecialchars($location['external_id'] ?? ''); ?>"
                           placeholder="ID from POS or other system">
                    <small class="form-text text-muted">
                        Used for syncing with external systems
                    </small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">API Endpoint Override</label>
                    <input type="url" class="form-control" name="api_endpoint" 
                           value="<?php echo htmlspecialchars($location['api_endpoint'] ?? ''); ?>"
                           placeholder="https://api.example.com/location/12345">
                    <small class="form-text text-muted">
                        Location-specific API endpoint if different from company default
                    </small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Webhook URL</label>
                    <input type="url" class="form-control" name="webhook_url" 
                           value="<?php echo htmlspecialchars($location['webhook_url'] ?? ''); ?>"
                           placeholder="https://example.com/webhooks/location">
                    <small class="form-text text-muted">
                        Receive notifications about enrollments at this location
                    </small>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Save Integration Settings
                </button>
            </form>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="card border-danger">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Danger Zone</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h6>Delete Location</h6>
                    <p class="text-muted mb-0">
                        Permanently delete this location and all associated data. This action cannot be undone.
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-danger" onclick="confirmDeleteLocation()">
                        <i class="bi bi-trash me-2"></i>Delete Location
                    </button>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row">
                <div class="col-md-8">
                    <h6>Merge with Another Location</h6>
                    <p class="text-muted mb-0">
                        Merge this location with another location from the same company.
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#mergeLocationModal">
                        <i class="bi bi-arrows-collapse me-2"></i>Merge Location
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Attribute Modal -->
<div class="modal fade" id="addAttributeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addAttributeForm" action="/admin/ajax/add-location-attribute.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Custom Attribute</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="location_id" value="<?php echo $location_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="type" required
                               placeholder="e.g., feature, service, amenity">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required
                               placeholder="e.g., parking_available, drive_thru">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Value</label>
                        <input type="text" class="form-control" name="value"
                               placeholder="e.g., yes, 50, enabled">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"
                                  placeholder="Additional details about this attribute"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Attribute</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Merge Location Modal -->
<div class="modal fade" id="mergeLocationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="mergeLocationForm" action="/admin/ajax/merge-locations.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Merge Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        This will merge all data from this location into the selected location. This action cannot be undone.
                    </div>
                    
                    <input type="hidden" name="source_location_id" value="<?php echo $location_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Merge Into Location <span class="text-danger">*</span></label>
                        <select class="form-select" name="target_location_id" required>
                            <option value="">Select Target Location</option>
                            <?php
                            try {
                                // Get other locations for this company
                                $other_locations_sql = "SELECT location_id, location_name, city, state 
                                                      FROM bg_company_locations 
                                                      WHERE company_id = :company_id 
                                                      AND location_id != :current_location_id
                                                      ORDER BY city, state";
                                $other_stmt = $database->prepare($other_locations_sql);
                                $other_stmt->execute([
                                    'company_id' => $company_id,
                                    'current_location_id' => $location_id
                                ]);
                                
                                while ($other = $other_stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $display = !empty($other['location_name']) 
                                        ? $other['location_name'] 
                                        : $other['city'] . ', ' . $other['state'];
                                    echo '<option value="' . $other['location_id'] . '">' . htmlspecialchars($display) . '</option>';
                                }
                            } catch (Exception $e) {
                                // Error getting other locations
                                echo '<option value="">Error loading locations</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Merge</label>
                        <textarea class="form-control" name="merge_reason" rows="2" required
                                  placeholder="e.g., Duplicate entry, Locations consolidated"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Merge Locations</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle form submissions
document.getElementById('statusForm').addEventListener('submit', handleFormSubmit);
document.getElementById('integrationForm').addEventListener('submit', handleFormSubmit);
document.getElementById('addAttributeForm').addEventListener('submit', handleFormSubmit);
document.getElementById('mergeLocationForm').addEventListener('submit', handleMergeSubmit);

function handleFormSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (form.id === 'addAttributeForm') {
                location.reload();
            } else {
                showAlert('success', data.message || 'Settings saved successfully!');
            }
        } else {
            throw new Error(data.message || 'Failed to save settings');
        }
    })
    .catch(error => {
        showAlert('danger', error.message);
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function handleMergeSubmit(e) {
    e.preventDefault();
    
    if (!confirm('Are you absolutely sure you want to merge these locations? This cannot be undone.')) {
        return;
    }
    
    handleFormSubmit(e);
}

function showAlert(type, message) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show mt-3`;
    alert.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.container-fluid').insertBefore(alert, document.querySelector('.container-fluid').firstChild);
    setTimeout(() => alert.remove(), 3000);
}

function deleteAttribute(attributeId) {
    if (confirm('Delete this attribute?')) {
        // Implementation for deleting attributes
        fetch('/admin/ajax/delete-location-attribute.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `attribute_id=${attributeId}&csrf_token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to delete attribute'));
            }
        });
    }
}

function confirmDeleteLocation() {
    const locationName = '<?php echo addslashes($location_display_name); ?>';
    if (confirm(`Are you absolutely sure you want to delete the location "${locationName}"?\n\nThis will permanently delete:\n- All location data\n- Location-specific rewards\n- All associated records\n\nThis action CANNOT be undone.`)) {
        if (prompt('Type DELETE to confirm:') === 'DELETE') {
            // Implementation for deleting location
            fetch('/admin/ajax/delete-location.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `location_id=<?php echo $location_id; ?>&csrf_token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '/admin/company-editor-main.php?cid=<?php echo $company_id; ?>';
                } else {
                    alert('Error: ' + (data.message || 'Failed to delete location'));
                }
            });
        }
    }
}
</script>