<?php
if (!isset($company_id)) {
    $company_id = $_GET['cid'] ?? null;
    
    // Fetch company details if not already available
    if (!isset($company)) {
        $company = $app->getcompanydetails($company_id);
    }
}

// Ensure we have basic company data
$company_name = $company['company_name'] ?? 'Unknown Company';
$company_display_name = $company['company_display_name'] ?? $company_name;
$company_joined = date('F d, Y', strtotime($company['create_dt'] ?? 'now'));
$company_status = $company['status'] ?? 'unknown';

// Social media links
$social_links = [
    'facebook' => $company['facebook'] ?? '',
    'twitter' => $company['twitter'] ?? '',
    'instagram' => $company['instagram'] ?? '',
    'tiktok' => $company['tiktok'] ?? ''
];

// Status badge color mapping
$status_colors = [
    'active' => 'success',
    'inactive' => 'danger',
    'pending' => 'warning',
    'finalized' => 'primary'
];
$status_color = $status_colors[$company_status] ?? 'secondary';
?>

<div class="container-fluid px-4">
    <!-- Company Overview Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <!-- Logo Column -->
                <div class="col-md-3 text-center">
                    <img 
                        src="<?php echo $display->companyimage($company['company_id'] . '/' . $company['company_logo']); ?>" 
                        class="img-fluid rounded mb-3" 
                        style="max-height: 150px;" 
                        alt="<?php echo htmlspecialchars($company_name); ?> Logo"
                    >
                    <div class="d-grid gap-2">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadLogoModal">
                            <i class="bi bi-upload me-2"></i>Update Logo
                        </button>
                    </div>
                </div>
                
                <!-- Details Column -->
                <div class="col-md-9">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="mb-0"><?php echo htmlspecialchars($company_name); ?></h2>
                            <?php if ($company_display_name !== $company_name): ?>
                            <p class="text-muted mb-0">Display Name: <?php echo htmlspecialchars($company_display_name); ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-<?php echo $status_color; ?>"><?php echo ucfirst($company_status); ?></span>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Joined:</strong> <?php echo $company_joined; ?></p>
                            <p class="mb-1"><strong>Category:</strong> <?php echo htmlspecialchars($company['category'] ?? 'N/A'); ?></p>
                            <p class="mb-1"><strong>ID:</strong> <?php echo $company_id; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1">
                                <strong>Age Range:</strong> 
                                <?php echo ($company['minage'] ?? 0) . ' - ' . ($company['maxage'] ?? 'No Limit'); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Region:</strong> 
                                <?php echo htmlspecialchars($company['region_type'] ?? 'National'); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Usage Count:</strong> 
                                <?php echo number_format($company['usage_count'] ?? 0); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="card bg-light">
                        <div class="card-body py-2">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <a href="<?php echo htmlspecialchars($company['company_url'] ?? '#'); ?>" 
                                       target="_blank" 
                                       class="text-decoration-none d-flex align-items-center">
                                        <i class="bi bi-globe me-2"></i>
                                        Website
                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="<?php echo htmlspecialchars($company['signup_url'] ?? '#'); ?>" 
                                       target="_blank"
                                       class="text-decoration-none d-flex align-items-center">
                                        <i class="bi bi-person-plus me-2"></i>
                                        Sign Up
                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="<?php echo htmlspecialchars($company['info_url'] ?? '#'); ?>" 
                                       target="_blank"
                                       class="text-decoration-none d-flex align-items-center">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Info
                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ABO Progress Section -->
    <div class="content-header-admin mb-4">
        <h3 class="mb-0">Automation Business Onboarding (ABO) Progress</h3>
    </div>
    
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
    $abo_stmt = $database->query($abo_sql, ['company_id' => $company_id]);
    $abo_progress = $abo_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get collected data
    $collected_sql = "SELECT c.* FROM bg_companies c WHERE company_id = :company_id";
    $collected_stmt = $database->query($collected_sql, ['company_id' => $company_id]);
    $collected_data = $collected_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Status colors
    $abo_status_colors = [
        'pending' => 'secondary',
        'in_progress' => 'info',
        'completed' => 'success',
        'attempted' => 'warning',
        'error' => 'danger'
    ];
    ?>
    
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <!-- Progress Overview -->
                <div class="col-md-12 mb-3">
                    <?php
                    $total_processors = count($abo_progress);
                    $completed = 0;
                    $attempted = 0;
                    foreach ($abo_progress as $proc) {
                        if ($proc['status'] === 'completed') $completed++;
                        elseif ($proc['status'] === 'attempted') $attempted++;
                    }
                    $progress_percent = $total_processors > 0 ? round(($completed / $total_processors) * 100) : 0;
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><strong><?php echo $completed; ?></strong> of <?php echo $total_processors; ?> completed</span>
                        <span><?php echo $progress_percent; ?>%</span>
                    </div>
                    <div class="progress mb-3" style="height: 25px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $progress_percent; ?>%">
                            <?php echo $progress_percent; ?>%
                        </div>
                        <?php if ($attempted > 0): 
                            $attempted_percent = round(($attempted / $total_processors) * 100);
                        ?>
                        <div class="progress-bar bg-warning" style="width: <?php echo $attempted_percent; ?>%">
                            <?php echo $attempted; ?> attempted
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Collected Data Display -->
                <div class="col-md-12">
                    <h5 class="mb-3">Collected Data</h5>
                    <div class="row g-3">
                        <!-- Apps -->
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2"><i class="bi bi-phone me-2"></i>Mobile Apps</h6>
                                <?php if (!empty($collected_data['appgoogle'])): ?>
                                    <a href="<?php echo htmlspecialchars($collected_data['appgoogle']); ?>" target="_blank" class="d-block text-decoration-none mb-2">
                                        <i class="bi bi-google me-2"></i>Google Play App
                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                    </a>
                                <?php else: ?>
                                    <p class="text-muted mb-2"><i class="bi bi-google me-2"></i>Google Play: <em>Pending</em></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($collected_data['appapple'])): ?>
                                    <a href="<?php echo htmlspecialchars($collected_data['appapple']); ?>" target="_blank" class="d-block text-decoration-none">
                                        <i class="bi bi-apple me-2"></i>Apple App Store
                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                    </a>
                                <?php else: ?>
                                    <p class="text-muted mb-0"><i class="bi bi-apple me-2"></i>Apple App: <em>Pending</em></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Social Media -->
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2"><i class="bi bi-share me-2"></i>Social Media</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <?php if (!empty($collected_data['facebook'])): ?>
                                            <a href="<?php echo htmlspecialchars($collected_data['facebook']); ?>" target="_blank" class="d-block text-decoration-none mb-2">
                                                <i class="bi bi-facebook me-1"></i>Facebook
                                            </a>
                                        <?php else: ?>
                                            <p class="text-muted mb-2"><i class="bi bi-facebook me-1"></i><em>Pending</em></p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($collected_data['instagram'])): ?>
                                            <a href="<?php echo htmlspecialchars($collected_data['instagram']); ?>" target="_blank" class="d-block text-decoration-none">
                                                <i class="bi bi-instagram me-1"></i>Instagram
                                            </a>
                                        <?php else: ?>
                                            <p class="text-muted mb-0"><i class="bi bi-instagram me-1"></i><em>Pending</em></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-6">
                                        <?php if (!empty($collected_data['twitter'])): ?>
                                            <a href="<?php echo htmlspecialchars($collected_data['twitter']); ?>" target="_blank" class="d-block text-decoration-none mb-2">
                                                <i class="bi bi-twitter me-1"></i>Twitter
                                            </a>
                                        <?php else: ?>
                                            <p class="text-muted mb-2"><i class="bi bi-twitter me-1"></i><em>Pending</em></p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($collected_data['tiktok'])): ?>
                                            <a href="<?php echo htmlspecialchars($collected_data['tiktok']); ?>" target="_blank" class="d-block text-decoration-none">
                                                <i class="bi bi-tiktok me-1"></i>TikTok
                                            </a>
                                        <?php else: ?>
                                            <p class="text-muted mb-0"><i class="bi bi-tiktok me-1"></i><em>Pending</em></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Images -->
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2"><i class="bi bi-image me-2"></i>Company Images</h6>
                                <?php
                                // Check for images
                                $image_count_sql = "SELECT COUNT(*) as total FROM bg_company_attributes 
                                                   WHERE company_id = :company_id AND type = 'image' 
                                                   AND category = 'company_logos'";
                                $count_stmt = $database->query($image_count_sql, ['company_id' => $company_id]);
                                $image_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                
                                $image_sql = "SELECT name, description FROM bg_company_attributes 
                                            WHERE company_id = :company_id AND type = 'image' 
                                            AND category = 'company_logos'
                                            ORDER BY create_dt DESC LIMIT 3";
                                $image_stmt = $database->query($image_sql, ['company_id' => $company_id]);
                                $images = $image_stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (!empty($images)): ?>
                                    <p class="mb-1"><strong><?php echo $image_count; ?></strong> images collected</p>
                                    <small class="text-muted">
                                    <?php 
                                    $image_types = array_column($images, 'name');
                                    echo implode(', ', array_slice($image_types, 0, 3));
                                    if (count($image_types) > 3) echo '...';
                                    ?>
                                    </small>
                                <?php else: ?>
                                    <p class="text-muted mb-0"><em>No images collected yet</em></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Metadata -->
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2"><i class="bi bi-file-text me-2"></i>Metadata</h6>
                                <?php
                                // Check for metadata
                                $meta_count_sql = "SELECT COUNT(*) as total FROM bg_company_attributes 
                                                  WHERE company_id = :company_id AND type = 'metadata'";
                                $meta_count_stmt = $database->query($meta_count_sql, ['company_id' => $company_id]);
                                $meta_count = $meta_count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                
                                $meta_sql = "SELECT name FROM bg_company_attributes 
                                           WHERE company_id = :company_id AND type = 'metadata' 
                                           LIMIT 5";
                                $meta_stmt = $database->query($meta_sql, ['company_id' => $company_id]);
                                $metadata = $meta_stmt->fetchAll(PDO::FETCH_COLUMN);
                                
                                if (!empty($metadata)): ?>
                                    <p class="mb-1"><strong><?php echo $meta_count; ?></strong> fields extracted</p>
                                    <small class="text-muted">
                                    <?php 
                                    echo implode(', ', array_slice($metadata, 0, 3));
                                    if (count($metadata) > 3) echo '...';
                                    ?>
                                    </small>
                                <?php else: ?>
                                    <p class="text-muted mb-0"><em>No metadata collected yet</em></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Processor Status Table -->
                <div class="col-md-12 mt-4">
                    <h5 class="mb-3">Processor Status</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Processor</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($abo_progress as $proc): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($proc['display_name'] ?? $proc['processor']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $abo_status_colors[$proc['status']] ?? 'secondary'; ?>">
                                            <?php echo ucfirst($proc['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo !empty($proc['modify_dt']) ? date('M d, Y g:i A', strtotime($proc['modify_dt'])) : 'Never'; ?>
                                    </td>
                                    <td>
                                        <a href="/admin/abo-status.php?status=active" target="_blank" class="btn btn-sm btn-outline-primary">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Information Cards Row -->
    <div class="row g-4 mb-4">
        <!-- Social Media Card -->
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-share me-2"></i>Social Media
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($social_links as $platform => $url): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-<?php echo $platform; ?> me-3"></i>
                                    <?php echo ucfirst($platform); ?>
                                </div>
                                <?php if ($url): ?>
                                    <a href="<?php echo htmlspecialchars($url); ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-primary">
                                        View <i class="bi bi-box-arrow-up-right ms-1"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark">Not Connected</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Card -->
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>Quick Stats
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <h6 class="text-muted mb-2">Active Rewards</h6>
                                <h3 class="mb-0">
                                    <?php 
                                    // Fetch active rewards count
                                    $stmt = $database->prepare("SELECT COUNT(*) FROM bg_company_rewards WHERE company_id = ? AND status = 'active'");
                                    $stmt->execute([$company_id]);
                                    echo $stmt->fetchColumn() ?? 0;
                                    ?>
                                </h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <h6 class="text-muted mb-2">Locations</h6>
                                <h3 class="mb-0">
                                    <?php 
                                    // Fetch locations count
                                    $stmt = $database->prepare("SELECT COUNT(*) FROM bg_company_locations WHERE company_id = ? AND status = 'active'");
                                    $stmt->execute([$company_id]);
                                    echo $stmt->fetchColumn() ?? 0;
                                    ?>
                                </h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <h6 class="text-muted mb-2">Policies</h6>
                                <h3 class="mb-0">
                                    <?php 
                                    // Fetch active policies count
                                    $stmt = $database->prepare("SELECT COUNT(*) FROM bg_company_terms_tracking WHERE company_id = ? AND status = 'active'");
                                    $stmt->execute([$company_id]);
                                    echo $stmt->fetchColumn() ?? 0;
                                    ?>
                                </h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <h6 class="text-muted mb-2">Last Updated</h6>
                                <h3 class="mb-0 small">
                                    <?php echo date('M d, Y', strtotime($company['modify_dt'] ?? 'now')); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Logo Upload Modal -->
<div class="modal fade" id="uploadLogoModal" tabindex="-1" aria-labelledby="uploadLogoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadLogoModalLabel">Upload Company Logo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="logoUploadForm" action="/admin_actions/upload_logo.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                    <div class="mb-3">
                        <label for="logoFile" class="form-label">Select Logo Image</label>
                        <input type="file" class="form-control" id="logoFile" name="logo" accept="image/*" required>
                        <div class="form-text">Recommended size: 400x400px. Max file size: 2MB.</div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Upload Logo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle logo upload form submission
    const logoForm = document.getElementById('logoUploadForm');
    if (logoForm) {
        logoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Upload failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Upload failed. Please try again.');
            });
        });
    }
});
</script>
