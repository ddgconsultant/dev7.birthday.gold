<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Initialize Marketing class
$marketing = new Marketing($database, $qik, $mail);

$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pagetitle = "Newsletter Preview";

if (!$campaign_id) {
    header('Location: /staff/manage-newsletter/index.php');
    exit;
}

// Handle AJAX preview requests
if (isset($_POST['action']) && $_POST['action'] == 'generate_preview') {
    header('Content-Type: application/json');
    
    // Optional criteria override for testing different user types
    $override_criteria = [];
    if (!empty($_POST['test_criteria'])) {
        $test_criteria = json_decode($_POST['test_criteria'], true);
        if ($test_criteria) {
            $override_criteria = $test_criteria;
        }
    }
    
    $preview_result = $marketing->generateCampaignPreview($campaign_id, $override_criteria);
    
    if ($preview_result['success']) {
        // Add additional info for the preview
        $preview_result['timestamp'] = date('F j, Y g:i:s A');
        
        // Get CTA category options for testing
        $categories_sql = "SELECT DISTINCT company_category FROM bg_companies WHERE status = 'active' ORDER BY company_category";
        $categories = $database->getrows($categories_sql);
        $preview_result['available_categories'] = array_column($categories, 'company_category');
    }
    
    echo json_encode($preview_result);
    exit;
}

// Get campaign details
$campaign_sql = "SELECT * FROM bg_newsletter_campaigns WHERE campaign_id = :campaign_id";
$campaign = $database->getrow($campaign_sql, ['campaign_id' => $campaign_id]);

if (!$campaign) {
    header('Location: /staff/manage-newsletter/index.php');
    exit;
}

// Get available CTA categories
$categories_sql = "SELECT DISTINCT company_category FROM bg_companies WHERE status = 'active' ORDER BY company_category";
$categories = $database->getrows($categories_sql);

include($dir['blade'] . '/staff-header.inc');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="bi bi-eye"></i> Newsletter Preview</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/staff/manage-newsletter/index.php">Campaigns</a></li>
                            <li class="breadcrumb-item active">Preview: <?= htmlspecialchars($campaign['title']) ?></li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <button class="btn btn-success" onclick="generatePreview()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh Preview
                    </button>
                    <a href="/staff/newsletter-edit.php?id=<?= $campaign_id ?>" class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edit Campaign
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Left Column - Preview Controls -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-sliders"></i> Preview Settings</h5>
                        </div>
                        <div class="card-body">
                            <!-- Campaign Info -->
                            <div class="mb-4">
                                <h6>Campaign Information</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Title:</strong></td>
                                            <td><?= htmlspecialchars($campaign['title']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Subject:</strong></td>
                                            <td><?= htmlspecialchars($campaign['subject']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>CTA Category:</strong></td>
                                            <td>
                                                <?php if ($campaign['cta_category']): ?>
                                                    <span class="badge bg-info"><?= htmlspecialchars($campaign['cta_category']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">None</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'draft' => 'bg-secondary',
                                                    'scheduled' => 'bg-warning',
                                                    'queued' => 'bg-warning',
                                                    'sending' => 'bg-primary',
                                                    'completed' => 'bg-success',
                                                    'paused' => 'bg-info',
                                                    'cancelled' => 'bg-danger'
                                                ][$campaign['status']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?= $status_class ?>"><?= ucfirst($campaign['status']) ?></span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Test Criteria -->
                            <div class="mb-4">
                                <h6>Test with Specific User Type</h6>
                                <p class="text-muted small">Override recipient criteria to test different scenarios</p>
                                
                                <div class="mb-3">
                                    <label class="form-label">Test CTA Category</label>
                                    <select class="form-select" id="test_category">
                                        <option value="">Use Campaign Default</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat['company_category']) ?>"
                                                <?= $cat['company_category'] == $campaign['cta_category'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['company_category']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Test Birth Month</label>
                                    <select class="form-select" id="test_birth_month">
                                        <option value="">Any Month</option>
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?= $i ?>"><?= date('F', mktime(0, 0, 0, $i, 1)) ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <label class="form-label">Min Age</label>
                                        <input type="number" class="form-control" id="test_min_age" placeholder="18" min="13" max="100">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Max Age</label>
                                        <input type="number" class="form-control" id="test_max_age" placeholder="65" min="13" max="100">
                                    </div>
                                </div>
                            </div>

                            <!-- Generate Preview Button -->
                            <button class="btn btn-primary w-100" onclick="generatePreview()" id="preview-btn">
                                <i class="bi bi-play-circle"></i> Generate Preview
                            </button>

                            <!-- Sample User Info (populated after preview) -->
                            <div id="sample-user-info" class="mt-4" style="display: none;">
                                <h6>Sample User Data</h6>
                                <div id="user-details" class="small"></div>
                            </div>

                            <!-- CTA Business Info (populated after preview) -->
                            <div id="cta-business-info" class="mt-4" style="display: none;">
                                <h6>CTA Businesses</h6>
                                <div id="business-details" class="small"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Preview Display -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-envelope"></i> Email Preview</h5>
                            <div>
                                <button class="btn btn-sm btn-outline-secondary" onclick="togglePreviewMode()">
                                    <i class="bi bi-phone"></i> Mobile View
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="openPreviewInNewTab()">
                                    <i class="bi bi-box-arrow-up-right"></i> Full Screen
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div id="preview-loading" class="text-center p-5" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Generating preview...</span>
                                </div>
                                <p class="mt-3 text-muted">Generating personalized preview...</p>
                            </div>
                            
                            <div id="preview-error" class="alert alert-danger m-3" style="display: none;">
                                <i class="bi bi-exclamation-triangle"></i> 
                                <span id="error-message">Unable to generate preview</span>
                            </div>
                            
                            <div id="preview-container" class="position-relative">
                                <iframe id="preview-frame" 
                                        style="width: 100%; min-height: 600px; border: none; background: #f8f9fa;"
                                        srcdoc="<div style='padding: 40px; text-align: center; color: #666;'>
                                                    <i class='bi bi-envelope' style='font-size: 3rem; margin-bottom: 20px; display: block;'></i>
                                                    <h4>Click 'Generate Preview' to see how your newsletter will look</h4>
                                                    <p>This will show personalized content with actual user data and available CTA businesses.</p>
                                                </div>">
                                </iframe>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle"></i> 
                                        Preview uses a random user matching your campaign criteria
                                    </small>
                                </div>
                                <div class="col-md-6 text-end">
                                    <small id="preview-timestamp" class="text-muted"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPreviewData = null;
let isMobileView = false;

function generatePreview() {
    const btn = document.getElementById('preview-btn');
    const loading = document.getElementById('preview-loading');
    const error = document.getElementById('preview-error');
    const frame = document.getElementById('preview-frame');
    
    // Show loading state
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Generating...';
    loading.style.display = 'block';
    error.style.display = 'none';
    
    // Collect test criteria
    const testCriteria = {};
    
    const testCategory = document.getElementById('test_category').value;
    const testBirthMonth = document.getElementById('test_birth_month').value;
    const testMinAge = document.getElementById('test_min_age').value;
    const testMaxAge = document.getElementById('test_max_age').value;
    
    if (testCategory) testCriteria.cta_category_override = testCategory;
    if (testBirthMonth) testCriteria.birth_month = testBirthMonth;
    if (testMinAge || testMaxAge) {
        testCriteria.age_range = {};
        if (testMinAge) testCriteria.age_range.min = parseInt(testMinAge);
        if (testMaxAge) testCriteria.age_range.max = parseInt(testMaxAge);
    }
    
    // Make AJAX request
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'generate_preview',
            test_criteria: Object.keys(testCriteria).length > 0 ? JSON.stringify(testCriteria) : ''
        })
    })
    .then(response => response.json())
    .then(data => {
        loading.style.display = 'none';
        
        if (data.success) {
            currentPreviewData = data;
            
            // Update preview frame
            frame.srcdoc = data.preview_html;
            
            // Update sample user info
            updateSampleUserInfo(data.sample_user);
            
            // Update CTA business info
            updateCTABusinessInfo(data.cta_businesses);
            
            // Update timestamp
            document.getElementById('preview-timestamp').textContent = 'Last updated: ' + data.timestamp;
            
        } else {
            error.style.display = 'block';
            document.getElementById('error-message').textContent = data.error;
        }
    })
    .catch(err => {
        loading.style.display = 'none';
        error.style.display = 'block';
        document.getElementById('error-message').textContent = 'Network error: ' + err.message;
        console.error('Preview error:', err);
    })
    .finally(() => {
        // Reset button
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh Preview';
    });
}

function updateSampleUserInfo(user) {
    const container = document.getElementById('sample-user-info');
    const details = document.getElementById('user-details');
    
    if (user) {
        details.innerHTML = `
            <div class="mb-2"><strong>Name:</strong> ${user.first_name} ${user.last_name}</div>
            <div class="mb-2"><strong>Email:</strong> ${user.email}</div>
            <div class="mb-2"><strong>Location:</strong> ${user.city}, ${user.state}</div>
            <div class="mb-2"><strong>Birth Month:</strong> ${user.birth_month ? new Date(0, user.birth_month - 1).toLocaleString('default', { month: 'long' }) : 'Not set'}</div>
            <div class="text-muted"><small>User ID: ${user.user_id}</small></div>
        `;
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
}

function updateCTABusinessInfo(businesses) {
    const container = document.getElementById('cta-business-info');
    const details = document.getElementById('business-details');
    
    if (businesses && businesses.length > 0) {
        let html = '';
        businesses.forEach((business, index) => {
            html += `
                <div class="mb-2 pb-2 ${index < businesses.length - 1 ? 'border-bottom' : ''}">
                    <strong>${business.company_display_name}</strong><br>
                    <small class="text-muted">${business.reward_description || 'Special offer'}</small>
                </div>
            `;
        });
        details.innerHTML = html;
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
}

function togglePreviewMode() {
    const frame = document.getElementById('preview-frame');
    isMobileView = !isMobileView;
    
    if (isMobileView) {
        frame.style.width = '375px';
        frame.style.margin = '0 auto';
        frame.style.display = 'block';
    } else {
        frame.style.width = '100%';
        frame.style.margin = '';
        frame.style.display = 'block';
    }
}

function openPreviewInNewTab() {
    if (currentPreviewData && currentPreviewData.preview_html) {
        const newWindow = window.open();
        newWindow.document.write(currentPreviewData.preview_html);
        newWindow.document.close();
    } else {
        alert('Please generate a preview first');
    }
}

// Auto-generate preview on page load
document.addEventListener('DOMContentLoaded', function() {
    generatePreview();
});

// Add spin animation for loading spinner
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .spin { animation: spin 1s linear infinite; }
`;
document.head.appendChild(style);
</script>

<?php include($dir['blade'] . '/staff-footer.inc'); ?>