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
