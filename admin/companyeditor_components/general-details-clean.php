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
                            <div class="border rounded p-2">
                                <div class="h4 mb-0"><?php echo number_format($company['usage_count'] ?? 0); ?></div>
                                <small class="text-muted">Enrollments</small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="border rounded p-2">
                                <div class="h4 mb-0"><?php echo $company['minage'] ?? 0; ?>-<?php echo $company['maxage'] ?? '99+'; ?></div>
                                <small class="text-muted">Age Range</small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="border rounded p-2">
                                <div class="h4 mb-0"><?php echo ucfirst($company['region_type'] ?? 'National'); ?></div>
                                <small class="text-muted">Region</small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="border rounded p-2">
                                <div class="h4 mb-0">
                                    <?php 
                                    $location_count_sql = "SELECT COUNT(*) FROM bg_company_locations WHERE company_id = :company_id AND status = 'active'";
                                    $loc_stmt = $database->prepare($location_count_sql);
                                    $loc_stmt->execute(['company_id' => $company_id]);
                                    echo $loc_stmt->fetchColumn() ?: 0;
                                    ?>
                                </div>
                                <small class="text-muted">Locations</small>
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
                    <label class="form-label">Main Website</label>
                    <div class="input-group">
                        <input type="url" class="form-control" value="<?php echo htmlspecialchars($company['company_url'] ?? ''); ?>" readonly>
                        <?php if ($company['company_url']): ?>
                        <a href="<?php echo htmlspecialchars($company['company_url']); ?>" target="_blank" class="btn btn-outline-secondary">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Signup URL</label>
                    <div class="input-group">
                        <input type="url" class="form-control" value="<?php echo htmlspecialchars($company['signup_url'] ?? ''); ?>" readonly>
                        <?php if ($company['signup_url']): ?>
                        <a href="<?php echo htmlspecialchars($company['signup_url']); ?>" target="_blank" class="btn btn-outline-secondary">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Info URL</label>
                    <div class="input-group">
                        <input type="url" class="form-control" value="<?php echo htmlspecialchars($company['info_url'] ?? ''); ?>" readonly>
                        <?php if ($company['info_url']): ?>
                        <a href="<?php echo htmlspecialchars($company['info_url']); ?>" target="_blank" class="btn btn-outline-secondary">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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
                    <div class="d-flex align-items-center">
                        <i class="bi bi-<?php echo $platform['icon']; ?> fs-4 me-2"></i>
                        <?php if ($url): ?>
                            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="text-decoration-none">
                                <?php echo $platform['label']; ?>
                                <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                            </a>
                        <?php else: ?>
                            <span class="text-muted"><?php echo $platform['label']; ?> - Not Set</span>
                        <?php endif; ?>
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