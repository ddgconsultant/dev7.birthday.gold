<?php
if (!isset($componentmode) || $componentmode != 'include') {
    require_once $_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php';
}

// Location data should be available from parent page
if (!isset($location) || !is_array($location) || !isset($location_id)) {
    echo '<div class="alert alert-danger">Location data not available</div>';
    exit;
}

// Fetch location contacts - check if table exists first
$contacts = [];
try {
    $sql = "SELECT * FROM bg_location_contacts 
            WHERE location_id = :location_id 
            ORDER BY is_primary DESC, contact_name ASC";
    
    $stmt = $database->query($sql, ['location_id' => $location_id]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table doesn't exist, use empty array
    $contacts = [];
}
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Contact Information</h3>
            <p class="text-muted mb-0">Manage contacts for this location</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addContactModal">
            <i class="bi bi-plus-circle me-2"></i>Add Contact
        </button>
    </div>

    <!-- Main Contact Information -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Primary Contact Details</h5>
        </div>
        <div class="card-body">
            <form id="primaryContactForm" method="POST" action="/admin/ajax/save-location-contact.php">
                <input type="hidden" name="location_id" value="<?php echo $location_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <input type="hidden" name="is_primary" value="1">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Main Phone</label>
                            <input type="tel" class="form-control" name="phone_number" 
                                   value="<?php echo htmlspecialchars($location['phone_number'] ?? ''); ?>"
                                   placeholder="555-123-4567">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Fax Number</label>
                            <input type="tel" class="form-control" name="fax_number" 
                                   value="<?php echo htmlspecialchars($location['fax_number'] ?? ''); ?>"
                                   placeholder="555-123-4568">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">General Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo htmlspecialchars($location['email'] ?? ''); ?>"
                                   placeholder="location@example.com">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Customer Service Email</label>
                            <input type="email" class="form-control" name="customer_service_email" 
                                   value="<?php echo htmlspecialchars($location['customer_service_email'] ?? ''); ?>"
                                   placeholder="service@example.com">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Update Primary Contact
                </button>
            </form>
        </div>
    </div>

    <!-- Additional Contacts -->
    <?php if (empty($contacts)): ?>
        <div class="text-center py-5 bg-light rounded">
            <i class="bi bi-person-lines-fill display-1 text-muted mb-3"></i>
            <h5>No Additional Contacts</h5>
            <p class="text-muted mb-4">Add managers, supervisors, or other key contacts for this location.</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addContactModal">
                <i class="bi bi-plus-circle me-2"></i>Add First Contact
            </button>
        </div>
    <?php else: ?>
        <h4 class="mb-3">Additional Contacts</h4>
        <div class="row">
            <?php foreach ($contacts as $contact): ?>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <?php echo htmlspecialchars($contact['contact_name']); ?>
                                        <?php if ($contact['is_primary']): ?>
                                            <span class="badge bg-primary ms-2">Primary</span>
                                        <?php endif; ?>
                                    </h5>
                                    <?php if (!empty($contact['contact_title'])): ?>
                                        <p class="text-muted mb-2"><?php echo htmlspecialchars($contact['contact_title']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="editContact(<?php echo $contact['contact_id']; ?>)">
                                                <i class="bi bi-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        <?php if (!$contact['is_primary']): ?>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="makePrimary(<?php echo $contact['contact_id']; ?>)">
                                                    <i class="bi bi-star me-2"></i>Make Primary
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" onclick="deleteContact(<?php echo $contact['contact_id']; ?>)">
                                                <i class="bi bi-trash me-2"></i>Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="contact-details">
                                <?php if (!empty($contact['phone'])): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-telephone me-2 text-muted"></i>
                                        <a href="tel:<?php echo htmlspecialchars($contact['phone']); ?>">
                                            <?php echo htmlspecialchars($contact['phone']); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($contact['email'])): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-envelope me-2 text-muted"></i>
                                        <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>">
                                            <?php echo htmlspecialchars($contact['email']); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($contact['department'])): ?>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-building me-2 text-muted"></i>
                                        <span><?php echo htmlspecialchars($contact['department']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($contact['notes'])): ?>
                                <div class="mt-3 pt-3 border-top">
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($contact['notes'])); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Social Media & Online Presence -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Online Presence</h5>
        </div>
        <div class="card-body">
            <form id="socialMediaForm" method="POST" action="/admin/ajax/save-location-social.php">
                <input type="hidden" name="location_id" value="<?php echo $location_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Website</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                <input type="url" class="form-control" name="website" 
                                       value="<?php echo htmlspecialchars($location['location_url'] ?? ''); ?>"
                                       placeholder="https://example.com">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Facebook Page</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-facebook"></i></span>
                                <input type="url" class="form-control" name="facebook" 
                                       value="<?php echo htmlspecialchars($location['facebook_url'] ?? ''); ?>"
                                       placeholder="https://facebook.com/page">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Twitter/X Handle</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-twitter"></i></span>
                                <input type="text" class="form-control" name="twitter" 
                                       value="<?php echo htmlspecialchars($location['twitter_handle'] ?? ''); ?>"
                                       placeholder="@handle">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Instagram</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-instagram"></i></span>
                                <input type="text" class="form-control" name="instagram" 
                                       value="<?php echo htmlspecialchars($location['instagram_handle'] ?? ''); ?>"
                                       placeholder="@handle">
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Save Online Presence
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Add Contact Modal -->
<div class="modal fade" id="addContactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addContactForm" action="/admin/ajax/add-location-contact.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="location_id" value="<?php echo $location_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="contact_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Title/Position</label>
                        <input type="text" class="form-control" name="contact_title" 
                               placeholder="e.g., Store Manager">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" name="department" 
                               placeholder="e.g., Operations">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" 
                               placeholder="555-123-4567">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" 
                               placeholder="contact@example.com">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" 
                                  placeholder="Any additional information"></textarea>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_primary" id="isPrimary" value="1">
                        <label class="form-check-label" for="isPrimary">
                            Set as primary contact
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Form submissions
document.getElementById('primaryContactForm').addEventListener('submit', handleFormSubmit);
document.getElementById('socialMediaForm').addEventListener('submit', handleFormSubmit);
document.getElementById('addContactForm').addEventListener('submit', handleFormSubmit);

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
            if (form.id === 'addContactForm') {
                location.reload();
            } else {
                showAlert('success', data.message || 'Saved successfully!');
            }
        } else {
            throw new Error(data.message || 'Failed to save');
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

function editContact(contactId) {
    // Implementation for editing contacts
    alert('Edit functionality would be implemented here for contact ID: ' + contactId);
}

function makePrimary(contactId) {
    if (confirm('Make this the primary contact for the location?')) {
        // Implementation for making contact primary
        alert('Make primary functionality would be implemented here for contact ID: ' + contactId);
    }
}

function deleteContact(contactId) {
    if (confirm('Are you sure you want to delete this contact?')) {
        // Implementation for deleting contacts
        alert('Delete functionality would be implemented here for contact ID: ' + contactId);
    }
}
</script>