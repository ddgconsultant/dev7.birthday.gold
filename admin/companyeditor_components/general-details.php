<?php
/**
 * Clean General Details Component for Company Editor
 * Focused on essential company information with proper layout
 */

if (!isset($company_id)) {
    $company_id = $_GET['cid'] ?? null;
    
    if (!isset($company)) {
        $company = $app->getcompanydetails($company_id);
    }
}

// Ensure we have basic company data
$company_name = $company['company_name'] ?? 'Unknown Company';
$company_display_name = $company['company_display_name'] ?? $company_name;
$company_joined = date('F d, Y', strtotime($company['create_dt'] ?? 'now'));
$company_status = $company['status'] ?? 'unknown';

// Status badge color mapping
$status_colors = [
    'active' => 'success',
    'inactive' => 'danger',
    'pending' => 'warning',
    'pending_review' => 'warning',
    'approved_pending_data' => 'info',
    'pending_final_review' => 'primary',
    'finalized' => 'primary',
    'rejected' => 'danger',
    'submitted' => 'secondary'
];
$status_color = $status_colors[$company_status] ?? 'secondary';
?>

<style>
/* Visual status indicators */
.border-success {
    border-color: #198754 !important;
}
.border-danger {
    border-color: #dc3545 !important;
}
.bg-success.bg-opacity-10 {
    background-color: rgba(25, 135, 84, 0.1) !important;
}
.bg-danger.bg-opacity-10 {
    background-color: rgba(220, 53, 69, 0.1) !important;
}
/* Quick stats visual indicators */
.stat-box-success {
    border-color: #198754 !important;
    background-color: rgba(25, 135, 84, 0.05) !important;
}
.stat-box-warning {
    border-color: #ffc107 !important;
    background-color: rgba(255, 193, 7, 0.05) !important;
}
.stat-box-danger {
    border-color: #dc3545 !important;
    background-color: rgba(220, 53, 69, 0.05) !important;
}

/* Inline edit styles */
.editable-field {
    position: relative;
    transition: all 0.2s ease;
}

.editable-field .view-mode {
    cursor: pointer;
}

.editable-field:hover .view-mode:not(.no-hover) {
    background-color: rgba(0, 123, 255, 0.05);
    border-radius: 4px;
}

.clickable-field {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    transition: background-color 0.2s ease;
}

.clickable-field:hover {
    background-color: rgba(0, 123, 255, 0.08);
    cursor: pointer;
}

.edit-mode {
    display: none;
}

.editing .view-mode {
    display: none;
}

.editing .edit-mode {
    display: block;
}

.edit-actions {
    margin-top: 5px;
}

.field-saving {
    opacity: 0.6;
    pointer-events: none;
}

.success-indicator {
    color: #198754;
    display: none;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Tooltip hint */
.edit-hint {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    background: #333;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
}

.editable-field:hover .edit-hint {
    opacity: 0.9;
}

/* Make URL inputs clickable */
.editable-field .input-group input[readonly] {
    cursor: pointer;
    background-color: white;
}

.editable-field .input-group input[readonly]:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Social media field hover */
.editable-field .view-mode > div.d-flex {
    cursor: pointer;
    transition: all 0.2s ease;
}

.editable-field .view-mode > div.d-flex:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<div class="company-details-section">
    <!-- Company Header Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <!-- Logo Column -->
                <div class="col-md-2 text-center">
                    <img src="<?php echo $display->companyimage($company_id . '/' . $company['company_logo']); ?>" 
                         class="img-fluid rounded mb-3" 
                         style="max-width: 120px; max-height: 120px;" 
                         alt="<?php echo htmlspecialchars($company_name); ?> Logo"
                         onerror="this.onerror=null; this.src='/public/images/placeholder-logo.svg'">
                    <div>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadLogoModal">
                            <i class="bi bi-upload"></i> Update
                        </button>
                    </div>
                </div>
                
                <!-- Company Info Column -->
                <div class="col-md-10">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="mb-1"><?php echo htmlspecialchars($company_name); ?></h2>
                            <?php if ($company_display_name !== $company_name): ?>
                            <p class="text-muted mb-1">Display Name: <?php echo htmlspecialchars($company_display_name); ?></p>
                            <?php endif; ?>
                            <p class="text-muted mb-0">
                                <span class="me-3"><i class="bi bi-hash"></i> ID: <?php echo $company_id; ?></span>
                                <span class="me-3"><i class="bi bi-calendar"></i> Joined: <?php echo $company_joined; ?></span>
                                <span><i class="bi bi-folder"></i> <?php echo ucfirst($company['category'] ?? 'Uncategorized'); ?></span>
                            </p>
                        </div>
                        <div>
                            <span class="badge bg-<?php echo $status_color; ?> fs-6">
                                <?php echo ucwords(str_replace('_', ' ', $company_status)); ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="row g-3 text-center">
                        <div class="col">
                            <?php 
                            $enrollment_count = $company['usage_count'] ?? 0;
                            $enrollment_class = $enrollment_count > 0 ? 'stat-box-success' : 'stat-box-warning';
                            ?>
                            <div class="border rounded p-2 <?php echo $enrollment_class; ?>">
                                <div class="h4 mb-0"><?php echo number_format($enrollment_count); ?></div>
                                <small class="text-muted">Enrollments</small>
                                <?php if ($enrollment_count > 0): ?>
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 0.8rem;"></i>
                                <?php else: ?>
                                    <i class="bi bi-exclamation-circle-fill text-warning" style="font-size: 0.8rem;"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col">
                            <?php 
                            // Get age range using centralized function
                            $age_data = $app->getagerange($company_id);
                            $age_set = ($age_data['source'] !== 'default' && $age_data['confidence'] !== 'low');
                            $age_class = $age_set ? 'stat-box-success' : 'stat-box-danger';
                            ?>
                            <div class="editable-field age-range-field" data-field="age_range">
                                <div class="view-mode border rounded p-2 <?php echo $age_class; ?>" title="Source: <?php echo $age_data['source']; ?>, Confidence: <?php echo $age_data['confidence']; ?>. Click to edit" style="cursor: pointer;">
                                    <div class="h4 mb-0"><span class="age-range-display"><?php echo $age_data['minimum_age']; ?>-<?php echo $age_data['maximum_age']; ?></span></div>
                                    <small class="text-muted">Age Range</small>
                                    <?php if ($age_set): ?>
                                        <i class="bi bi-check-circle-fill text-success" style="font-size: 0.8rem;"></i>
                                    <?php else: ?>
                                        <i class="bi bi-x-circle-fill text-danger" style="font-size: 0.8rem;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="edit-mode">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="number" class="form-control form-control-sm min-age-input" 
                                                   value="<?php echo $age_data['minimum_age']; ?>" 
                                                   min="0" max="120" placeholder="Min">
                                        </div>
                                        <div class="col-6">
                                            <input type="number" class="form-control form-control-sm max-age-input" 
                                                   value="<?php echo $age_data['maximum_age']; ?>" 
                                                   min="0" max="120" placeholder="Max">
                                        </div>
                                    </div>
                                    <div class="edit-actions">
                                        <button class="btn btn-sm btn-primary save-age-btn">Save</button>
                                        <button class="btn btn-sm btn-secondary cancel-btn ms-1">Cancel</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <?php 
                            $region_set = !empty($company['region_type']);
                            $region_class = $region_set ? 'stat-box-success' : 'stat-box-danger';
                            ?>
                            <div class="border rounded p-2 <?php echo $region_class; ?>">
                                <div class="h4 mb-0"><?php echo ucfirst($company['region_type'] ?? 'National'); ?></div>
                                <small class="text-muted">Region</small>
                                <?php if ($region_set): ?>
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 0.8rem;"></i>
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill text-danger" style="font-size: 0.8rem;"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col">
                            <?php 
                            $location_count_sql = "SELECT COUNT(*) FROM bg_company_locations WHERE company_id = :company_id AND status = 'active'";
                            $loc_stmt = $database->prepare($location_count_sql);
                            $loc_stmt->execute(['company_id' => $company_id]);
                            $location_count = $loc_stmt->fetchColumn() ?: 0;
                            $location_class = $location_count > 0 ? 'stat-box-success' : 'stat-box-warning';
                            ?>
                            <div class="border rounded p-2 <?php echo $location_class; ?>">
                                <div class="h4 mb-0"><?php echo $location_count; ?></div>
                                <small class="text-muted">Locations</small>
                                <?php if ($location_count > 0): ?>
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 0.8rem;"></i>
                                <?php else: ?>
                                    <i class="bi bi-exclamation-circle-fill text-warning" style="font-size: 0.8rem;"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Details -->
                    <div class="mt-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Company Name</label>
                                <div class="editable-field" data-field="company_name">
                                    <div class="view-mode clickable-field">
                                        <span class="field-value"><?php echo htmlspecialchars($company_name); ?></span>
                                    </div>
                                    <div class="edit-mode">
                                        <input type="text" class="form-control field-input" value="<?php echo htmlspecialchars($company_name); ?>">
                                        <div class="edit-actions">
                                            <button class="btn btn-sm btn-primary save-btn">Save</button>
                                            <button class="btn btn-sm btn-secondary cancel-btn ms-1">Cancel</button>
                                            <span class="success-indicator ms-2"><i class="bi bi-check-circle-fill"></i> Saved</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Display Name</label>
                                <div class="editable-field" data-field="company_display_name">
                                    <div class="view-mode clickable-field">
                                        <span class="field-value"><?php echo htmlspecialchars($company_display_name); ?></span>
                                    </div>
                                    <div class="edit-mode">
                                        <input type="text" class="form-control field-input" value="<?php echo htmlspecialchars($company_display_name); ?>">
                                        <div class="edit-actions">
                                            <button class="btn btn-sm btn-primary save-btn">Save</button>
                                            <button class="btn btn-sm btn-secondary cancel-btn ms-1">Cancel</button>
                                            <span class="success-indicator ms-2"><i class="bi bi-check-circle-fill"></i> Saved</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Category</label>
                                <div class="editable-field" data-field="category">
                                    <div class="view-mode clickable-field">
                                        <span class="field-value"><?php echo ucfirst($company['category'] ?? 'Uncategorized'); ?></span>
                                    </div>
                                    <div class="edit-mode">
                                        <select class="form-select field-input">
                                            <option value="">Select Category</option>
                                            <option value="restaurant" <?php echo ($company['category'] ?? '') == 'restaurant' ? 'selected' : ''; ?>>Restaurant</option>
                                            <option value="retail" <?php echo ($company['category'] ?? '') == 'retail' ? 'selected' : ''; ?>>Retail</option>
                                            <option value="entertainment" <?php echo ($company['category'] ?? '') == 'entertainment' ? 'selected' : ''; ?>>Entertainment</option>
                                            <option value="services" <?php echo ($company['category'] ?? '') == 'services' ? 'selected' : ''; ?>>Services</option>
                                            <option value="health" <?php echo ($company['category'] ?? '') == 'health' ? 'selected' : ''; ?>>Health</option>
                                            <option value="beauty" <?php echo ($company['category'] ?? '') == 'beauty' ? 'selected' : ''; ?>>Beauty</option>
                                            <option value="automotive" <?php echo ($company['category'] ?? '') == 'automotive' ? 'selected' : ''; ?>>Automotive</option>
                                            <option value="other" <?php echo ($company['category'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <div class="edit-actions">
                                            <button class="btn btn-sm btn-primary save-btn">Save</button>
                                            <button class="btn btn-sm btn-secondary cancel-btn ms-1">Cancel</button>
                                            <span class="success-indicator ms-2"><i class="bi bi-check-circle-fill"></i> Saved</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small mb-1">Region Type</label>
                                <div class="editable-field" data-field="region_type">
                                    <div class="view-mode clickable-field">
                                        <span class="field-value"><?php echo ucfirst($company['region_type'] ?? 'National'); ?></span>
                                    </div>
                                    <div class="edit-mode">
                                        <select class="form-select field-input">
                                            <option value="national" <?php echo ($company['region_type'] ?? 'national') == 'national' ? 'selected' : ''; ?>>National</option>
                                            <option value="regional" <?php echo ($company['region_type'] ?? '') == 'regional' ? 'selected' : ''; ?>>Regional</option>
                                            <option value="local" <?php echo ($company['region_type'] ?? '') == 'local' ? 'selected' : ''; ?>>Local</option>
                                            <option value="international" <?php echo ($company['region_type'] ?? '') == 'international' ? 'selected' : ''; ?>>International</option>
                                        </select>
                                        <div class="edit-actions">
                                            <button class="btn btn-sm btn-primary save-btn">Save</button>
                                            <button class="btn btn-sm btn-secondary cancel-btn ms-1">Cancel</button>
                                            <span class="success-indicator ms-2"><i class="bi bi-check-circle-fill"></i> Saved</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- URLs Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Company URLs</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">
                        Main Website
                        <?php if ($company['company_url']): ?>
                            <i class="bi bi-check-circle-fill text-success ms-1" title="Set"></i>
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill text-danger ms-1" title="Not Set"></i>
                        <?php endif; ?>
                    </label>
                    <div class="editable-field" data-field="company_url">
                        <div class="view-mode">
                            <div class="input-group">
                                <input type="url" class="form-control <?php echo $company['company_url'] ? 'border-success' : 'border-danger'; ?>" value="<?php echo htmlspecialchars($company['company_url'] ?? ''); ?>" readonly>
                                <?php if ($company['company_url']): ?>
                                <a href="<?php echo htmlspecialchars($company['company_url']); ?>" target="_blank" class="btn btn-outline-success">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="edit-mode">
                            <input type="url" class="form-control field-input" value="<?php echo htmlspecialchars($company['company_url'] ?? ''); ?>" placeholder="https://example.com">
                            <div class="edit-actions">
                                <button class="btn btn-sm btn-primary save-btn">Save</button>
                                <button class="btn btn-sm btn-secondary cancel-btn ms-1">Cancel</button>
                                <span class="success-indicator ms-2"><i class="bi bi-check-circle-fill"></i> Saved</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">
                        Signup URL
                        <?php if ($company['signup_url']): ?>
                            <i class="bi bi-check-circle-fill text-success ms-1" title="Set"></i>
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill text-danger ms-1" title="Not Set"></i>
                        <?php endif; ?>
                    </label>
                    <div class="editable-field" data-field="signup_url">
                        <div class="view-mode">
                            <div class="input-group">
                                <input type="url" class="form-control <?php echo $company['signup_url'] ? 'border-success' : 'border-danger'; ?>" value="<?php echo htmlspecialchars($company['signup_url'] ?? ''); ?>" readonly>
                                <?php if ($company['signup_url']): ?>
                                <a href="<?php echo htmlspecialchars($company['signup_url']); ?>" target="_blank" class="btn btn-outline-success">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="edit-mode">
                            <input type="url" class="form-control field-input" value="<?php echo htmlspecialchars($company['signup_url'] ?? ''); ?>" placeholder="https://example.com/signup">
                            <div class="edit-actions">
                                <button class="btn btn-sm btn-primary save-btn">Save</button>
                                <button class="btn btn-sm btn-secondary cancel-btn ms-1">Cancel</button>
                                <span class="success-indicator ms-2"><i class="bi bi-check-circle-fill"></i> Saved</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">
                        Info URL
                        <?php if ($company['info_url']): ?>
                            <i class="bi bi-check-circle-fill text-success ms-1" title="Set"></i>
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill text-danger ms-1" title="Not Set"></i>
                        <?php endif; ?>
                    </label>
                    <div class="editable-field" data-field="info_url">
                        <div class="view-mode">
                            <div class="input-group">
                                <input type="url" class="form-control <?php echo $company['info_url'] ? 'border-success' : 'border-danger'; ?>" value="<?php echo htmlspecialchars($company['info_url'] ?? ''); ?>" readonly>
                                <?php if ($company['info_url']): ?>
                                <a href="<?php echo htmlspecialchars($company['info_url']); ?>" target="_blank" class="btn btn-outline-success">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="edit-mode">
                            <input type="url" class="form-control field-input" value="<?php echo htmlspecialchars($company['info_url'] ?? ''); ?>" placeholder="https://example.com/info">
                            <div class="edit-actions">
                                <button class="btn btn-sm btn-primary save-btn">Save</button>
                                <button class="btn btn-sm btn-secondary cancel-btn ms-1">Cancel</button>
                                <span class="success-indicator ms-2"><i class="bi bi-check-circle-fill"></i> Saved</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Apps Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Mobile Applications</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-google-play me-1"></i>Google Play Store
                        <?php if (!empty($company['appgoogle'])): ?>
                            <i class="bi bi-check-circle-fill text-success ms-1" title="Available"></i>
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill text-danger ms-1" title="Not Available"></i>
                        <?php endif; ?>
                    </label>
                    <div class="editable-field" data-field="appgoogle">
                        <div class="view-mode">
                            <div class="input-group">
                                <input type="url" class="form-control <?php echo !empty($company['appgoogle']) ? 'border-success' : 'border-danger'; ?>" 
                                       value="<?php echo htmlspecialchars($company['appgoogle'] ?? ''); ?>" 
                                       placeholder="No Google Play app URL" readonly>
                                <?php if (!empty($company['appgoogle'])): ?>
                                <a href="<?php echo htmlspecialchars($company['appgoogle']); ?>" target="_blank" class="btn btn-outline-success">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="edit-mode">
                            <input type="url" class="form-control field-input" value="<?php echo htmlspecialchars($company['appgoogle'] ?? ''); ?>" 
                                   placeholder="https://play.google.com/store/apps/details?id=...">
                            <div class="edit-actions">
                                <button class="btn btn-sm btn-primary save-btn">Save</button>
                                <button class="btn btn-sm btn-secondary cancel-btn ms-1">Cancel</button>
                                <span class="success-indicator ms-2"><i class="bi bi-check-circle-fill"></i> Saved</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-apple me-1"></i>Apple App Store
                        <?php if (!empty($company['appapple'])): ?>
                            <i class="bi bi-check-circle-fill text-success ms-1" title="Available"></i>
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill text-danger ms-1" title="Not Available"></i>
                        <?php endif; ?>
                    </label>
                    <div class="editable-field" data-field="appapple">
                        <div class="view-mode">
                            <div class="input-group">
                                <input type="url" class="form-control <?php echo !empty($company['appapple']) ? 'border-success' : 'border-danger'; ?>" 
                                       value="<?php echo htmlspecialchars($company['appapple'] ?? ''); ?>" 
                                       placeholder="No Apple App Store URL" readonly>
                                <?php if (!empty($company['appapple'])): ?>
                                <a href="<?php echo htmlspecialchars($company['appapple']); ?>" target="_blank" class="btn btn-outline-success">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="edit-mode">
                            <input type="url" class="form-control field-input" value="<?php echo htmlspecialchars($company['appapple'] ?? ''); ?>" 
                                   placeholder="https://apps.apple.com/us/app/...">
                            <div class="edit-actions">
                                <button class="btn btn-sm btn-primary save-btn">Save</button>
                                <button class="btn btn-sm btn-secondary cancel-btn ms-1">Cancel</button>
                                <span class="success-indicator ms-2"><i class="bi bi-check-circle-fill"></i> Saved</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (empty($company['appgoogle']) && empty($company['appapple'])): ?>
            <div class="text-center mt-3">
                <small class="text-muted">No mobile applications available for this company</small>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Social Media Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Social Media Presence</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php 
                $social_platforms = [
                    'facebook' => ['icon' => 'facebook', 'label' => 'Facebook'],
                    'twitter' => ['icon' => 'twitter', 'label' => 'Twitter'],
                    'instagram' => ['icon' => 'instagram', 'label' => 'Instagram'],
                    'tiktok' => ['icon' => 'tiktok', 'label' => 'TikTok'],
                    'youtube' => ['icon' => 'youtube', 'label' => 'YouTube'],
                    'linkedin' => ['icon' => 'linkedin', 'label' => 'LinkedIn']
                ];
                
                foreach ($social_platforms as $key => $platform): 
                    $url = $company[$key] ?? '';
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="editable-field" data-field="<?php echo $key; ?>">
                        <div class="view-mode">
                            <div class="d-flex align-items-center p-2 rounded <?php echo $url ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10'; ?> position-relative">
                                <i class="bi bi-<?php echo $platform['icon']; ?> fs-4 me-2 <?php echo $url ? 'text-success' : 'text-danger'; ?>"></i>
                                <?php if ($url): ?>
                                    <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="text-decoration-none text-success fw-medium">
                                        <?php echo $platform['label']; ?>
                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                    </a>
                                    <i class="bi bi-check-circle-fill text-success ms-auto" title="Connected"></i>
                                <?php else: ?>
                                    <span class="text-danger"><?php echo $platform['label']; ?></span>
                                    <small class="text-muted ms-2">Not Set</small>
                                    <i class="bi bi-x-circle-fill text-danger ms-auto" title="Not Connected"></i>
                                <?php endif; ?>
                                            </div>
                        </div>
                        <div class="edit-mode">
                            <input type="url" class="form-control field-input" value="<?php echo htmlspecialchars($url); ?>" 
                                   placeholder="Enter <?php echo $platform['label']; ?> URL">
                            <div class="edit-actions">
                                <button class="btn btn-sm btn-primary save-btn">Save</button>
                                <button class="btn btn-sm btn-secondary cancel-btn ms-1">Cancel</button>
                                <span class="success-indicator ms-2"><i class="bi bi-check-circle-fill"></i> Saved</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ABO Progress Section -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Automated Business Onboarding (ABO) Progress</h5>
        </div>
        <div class="card-body">
            <?php
            // Get ABO progress
            $abo_sql = "SELECT ca.name as processor, ca.description as status, ca.modify_dt,
                        c.config_value as display_name, 
                        JSON_UNQUOTE(JSON_EXTRACT(c.config_data, '$.description')) as config_description
                        FROM bg_company_attributes ca
                        LEFT JOIN bg_config c ON c.config_key COLLATE utf8mb4_unicode_ci = ca.name COLLATE utf8mb4_unicode_ci 
                            AND c.config_type = 'automation_processor'
                        WHERE ca.company_id = :company_id 
                        AND ca.type = 'onboarding_progress'
                        AND ca.status = 'active'
                        ORDER BY c.display_order";
            $abo_stmt = $database->prepare($abo_sql);
            $abo_stmt->execute(['company_id' => $company_id]);
            $abo_progress = $abo_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($abo_progress): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Processor</th>
                                <th width="120">Status</th>
                                <th width="180">Last Updated</th>
                                <th width="100">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($abo_progress as $progress): 
                                $status_badge_class = [
                                    'completed' => 'success',
                                    'in_progress' => 'primary',
                                    'error' => 'danger',
                                    'attempted' => 'warning',
                                    'pending' => 'secondary',
                                    'skipped' => 'info'
                                ];
                                $badge_class = $status_badge_class[$progress['status']] ?? 'secondary';
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($progress['display_name'] ?? $progress['processor']); ?></strong>
                                    <?php if ($progress['config_description']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($progress['config_description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $badge_class; ?>">
                                        <?php echo ucfirst($progress['status']); ?>
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    <?php echo $progress['modify_dt'] ? date('M j, g:i A', strtotime($progress['modify_dt'])) : '-'; ?>
                                </td>
                                <td>
                                    <?php if ($progress['status'] !== 'completed' && $progress['status'] !== 'in_progress'): ?>
                                    <a href="/admin_actions/abo/<?php echo str_replace('abo_', 'abo_', $progress['processor']); ?>.php?rawid=<?php echo $company_id; ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-play"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">No ABO progress data available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize inline editing
    initializeInlineEditing();
    
    function initializeInlineEditing() {
        // Handle click-to-edit for clickable fields
        $('.editable-field .clickable-field').on('click', function() {
            var field = $(this).closest('.editable-field');
            enterEditMode(field);
        });
        
        // Handle click-to-edit for input groups (URLs)
        $('.editable-field .input-group input[readonly]').on('click', function() {
            var field = $(this).closest('.editable-field');
            enterEditMode(field);
        });
        
        // Handle click-to-edit for social media fields
        $('.editable-field .view-mode > div.d-flex').on('click', function() {
            var field = $(this).closest('.editable-field');
            enterEditMode(field);
        });
        
        // Handle click-to-edit for age range
        $('.age-range-field .view-mode').on('click', function() {
            var field = $(this).closest('.editable-field');
            enterEditMode(field);
        });
        
        // Handle cancel button
        $(document).on('click', '.cancel-btn', function() {
            var field = $(this).closest('.editable-field');
            exitEditMode(field);
        });
        
        // Handle save button
        $(document).on('click', '.save-btn', function() {
            var field = $(this).closest('.editable-field');
            saveField(field);
        });
        
        // Handle save age button
        $(document).on('click', '.save-age-btn', function() {
            var field = $(this).closest('.editable-field');
            saveAgeRange(field);
        });
        
        // Handle enter key in edit mode
        $(document).on('keypress', '.field-input', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                var field = $(this).closest('.editable-field');
                saveField(field);
            }
        });
        
        // Handle escape key in edit mode
        $(document).on('keyup', '.field-input, .min-age-input, .max-age-input', function(e) {
            if (e.which === 27) {
                var field = $(this).closest('.editable-field');
                exitEditMode(field);
            }
        });
    }
    
    function enterEditMode(field) {
        field.addClass('editing');
        field.find('.field-input').focus().select();
    }
    
    function exitEditMode(field) {
        field.removeClass('editing');
        // Reset input value to original
        var originalValue = field.find('.field-value').text();
        field.find('.field-input').val(originalValue);
    }
    
    function saveField(field) {
        var fieldName = field.data('field');
        var value = field.find('.field-input').val();
        var companyId = <?php echo $company_id; ?>;
        
        // Add saving state
        field.addClass('field-saving');
        
        $.ajax({
            url: '/admin_actions/save_company_field.php',
            method: 'POST',
            data: {
                company_id: companyId,
                field: fieldName,
                value: value
            },
            success: function(response) {
                if (response.success) {
                    // Update the display value
                    updateFieldDisplay(field, fieldName, value);
                    
                    // Show success indicator
                    field.find('.success-indicator').show();
                    setTimeout(function() {
                        field.find('.success-indicator').fadeOut();
                    }, 2000);
                    
                    // Exit edit mode
                    field.removeClass('editing');
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error saving field. Please try again.');
            },
            complete: function() {
                field.removeClass('field-saving');
            }
        });
    }
    
    function updateFieldDisplay(field, fieldName, value) {
        // Update the view mode display
        if (fieldName === 'category' || fieldName === 'region_type') {
            // For select fields, get the text of selected option
            var displayText = field.find('.field-input option:selected').text();
            field.find('.field-value').text(displayText);
        } else if (fieldName.includes('url') || fieldName === 'appgoogle' || fieldName === 'appapple') {
            // For URL fields, update the input and link
            field.find('.view-mode input').val(value);
            if (value) {
                field.find('.view-mode input').removeClass('border-danger').addClass('border-success');
                // Update or create the link button
                var linkBtn = field.find('.view-mode a.btn-outline-success');
                if (linkBtn.length) {
                    linkBtn.attr('href', value);
                } else {
                    // Create new link button
                    field.find('.edit-trigger').before('<a href="' + value + '" target="_blank" class="btn btn-outline-success"><i class="bi bi-box-arrow-up-right"></i></a>');
                }
            } else {
                field.find('.view-mode input').removeClass('border-success').addClass('border-danger');
                field.find('.view-mode a.btn-outline-success').remove();
            }
        } else if (['facebook', 'twitter', 'instagram', 'tiktok', 'youtube', 'linkedin'].includes(fieldName)) {
            // For social media fields, update the display
            var socialContainer = field.find('.view-mode > div');
            if (value) {
                // Change to success state
                socialContainer.removeClass('bg-danger').addClass('bg-success');
                socialContainer.find('.bi').first().removeClass('text-danger').addClass('text-success');
                
                // Update or create link
                var existingLink = socialContainer.find('a');
                if (existingLink.length) {
                    existingLink.attr('href', value);
                } else {
                    // Replace the "Not Set" text with a link
                    var platformLabel = socialContainer.find('.text-danger').text();
                    socialContainer.find('.text-danger, .text-muted').remove();
                    socialContainer.find('.bi').first().after('<a href="' + value + '" target="_blank" class="text-decoration-none text-success fw-medium">' + platformLabel + '<i class="bi bi-box-arrow-up-right ms-1 small"></i></a>');
                }
                
                // Update icon
                socialContainer.find('.bi-x-circle-fill').removeClass('bi-x-circle-fill text-danger').addClass('bi-check-circle-fill text-success');
            } else {
                // Change to danger state
                socialContainer.removeClass('bg-success').addClass('bg-danger');
                socialContainer.find('.bi').first().removeClass('text-success').addClass('text-danger');
                
                // Remove link and add "Not Set" text
                var platformLabel = socialContainer.find('a').text().replace(/\s*$/, '');
                socialContainer.find('a').remove();
                socialContainer.find('.bi').first().after('<span class="text-danger">' + platformLabel + '</span><small class="text-muted ms-2">Not Set</small>');
                
                // Update icon
                socialContainer.find('.bi-check-circle-fill').removeClass('bi-check-circle-fill text-success').addClass('bi-x-circle-fill text-danger');
            }
        } else {
            // For text fields
            field.find('.field-value').text(value);
        }
    }
    
    function saveAgeRange(field) {
        var minAge = field.find('.min-age-input').val();
        var maxAge = field.find('.max-age-input').val();
        var companyId = <?php echo $company_id; ?>;
        
        // Validate ages
        if (parseInt(minAge) > parseInt(maxAge)) {
            alert('Minimum age cannot be greater than maximum age');
            return;
        }
        
        // Add saving state
        field.addClass('field-saving');
        
        // Save both ages
        var promises = [
            $.ajax({
                url: '/admin_actions/save_company_field.php',
                method: 'POST',
                data: {
                    company_id: companyId,
                    field: 'minimum_age',
                    value: minAge
                }
            }),
            $.ajax({
                url: '/admin_actions/save_company_field.php',
                method: 'POST',
                data: {
                    company_id: companyId,
                    field: 'maximum_age',
                    value: maxAge
                }
            })
        ];
        
        Promise.all(promises).then(function(results) {
            // Check if both succeeded
            if (results[0].success && results[1].success) {
                // Update display
                field.find('.age-range-display').text(minAge + '-' + maxAge);
                
                // Update styling
                field.find('.view-mode').removeClass('stat-box-danger').addClass('stat-box-success');
                field.find('.bi-x-circle-fill').removeClass('bi-x-circle-fill text-danger').addClass('bi-check-circle-fill text-success');
                
                // Show success indicator
                field.append('<span class="success-indicator ms-2"><i class="bi bi-check-circle-fill"></i> Saved</span>');
                setTimeout(function() {
                    field.find('.success-indicator').fadeOut(function() {
                        $(this).remove();
                    });
                }, 2000);
                
                // Exit edit mode
                field.removeClass('editing');
            } else {
                alert('Error saving age range');
            }
        }).catch(function() {
            alert('Error saving age range. Please try again.');
        }).finally(function() {
            field.removeClass('field-saving');
        });
    }
});
</script>

<!-- Logo Upload Modal -->
<div class="modal fade" id="uploadLogoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Company Logo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Logo upload functionality coming soon...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>