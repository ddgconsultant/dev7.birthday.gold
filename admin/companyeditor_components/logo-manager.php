<?php
/**
 * Logo Management Component for Company Editor
 * Allows viewing and managing company logos
 */

if (!isset($company_id)) {
    $company_id = $_GET['cid'] ?? null;
    
    if (!isset($company)) {
        $company = $app->getcompanydetails($company_id);
    }
}

// Get all logos for this company
$logo_sql = "SELECT * FROM bg_company_attributes 
             WHERE company_id = :company_id 
             AND category = 'company_logos' 
             AND status = 'active'
             ORDER BY `grouping` DESC, `rank` ASC";
$logo_stmt = $database->prepare($logo_sql);
$logo_stmt->execute(['company_id' => $company_id]);
$logos = $logo_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group logos by their grouping
$logo_groups = [];
foreach ($logos as $logo) {
    $logo_groups[$logo['grouping']][] = $logo;
}

// Get current primary logo
$primary_logo = null;
if (isset($logo_groups['primary_logo']) && !empty($logo_groups['primary_logo'])) {
    $primary_logo = $logo_groups['primary_logo'][0];
}
?>

<style>
.logo-card {
    border: 2px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    height: 100%;
}

.logo-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.logo-card.primary {
    border-color: #198754;
    background-color: rgba(25, 135, 84, 0.05);
}

.logo-card img {
    max-width: 100%;
    max-height: 150px;
    object-fit: contain;
}

.logo-actions {
    margin-top: 0.5rem;
}

.logo-info {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.5rem;
}

.primary-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 10;
}

/* Fetch Results Scrollable Area */
#fetchResultsContent {
    background-color: #f8f9fa;
}

#fetchResultsContent::-webkit-scrollbar {
    width: 8px;
}

#fetchResultsContent::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

#fetchResultsContent::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

#fetchResultsContent::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Logo fetch results styling */
.logo-fetch-results img {
    max-width: 200px;
    height: auto;
    margin: 10px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 5px;
    background: white;
}

/* Lazy loading styles */
img.lazy {
    filter: blur(5px);
    transition: filter 0.3s;
}

img.lazy:not([src]) {
    visibility: hidden;
}

img:not(.lazy) {
    filter: none;
}

.logo-card img {
    min-height: 100px;
    background-color: #f8f9fa;
}
</style>

<div class="logo-manager-section">
    <!-- Actions Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Company Logos</h4>
        <div>
            <?php 
            // Check if company has Apple App URL
            $hasAppleAppUrl = !empty($company['appapple']);
            ?>
            <button class="btn btn-primary" 
                    onclick="fetchNewLogos()" 
                    <?php echo !$hasAppleAppUrl ? 'disabled' : ''; ?>
                    <?php echo !$hasAppleAppUrl ? 'title="Apple App URL is required to fetch logos"' : ''; ?>>
                <i class="bi bi-cloud-download me-2"></i>Fetch New Logos
            </button>
            <button class="btn btn-outline-secondary ms-2" onclick="uploadLogo()">
                <i class="bi bi-upload me-2"></i>Upload Logo
            </button>
        </div>
    </div>

    <?php if (!$hasAppleAppUrl): ?>
    <div class="alert alert-info mb-3">
        <i class="bi bi-info-circle me-2"></i>To fetch logos from the Apple App Store, please add an Apple App URL in the General Details tab.
    </div>
    <?php endif; ?>

    <?php if ($primary_logo): ?>
    <!-- Current Primary Logo -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Current Primary Logo</h5>
        </div>
        <div class="card-body text-center">
            <img src="<?php echo $display->companyimage($company_id . '/' . $primary_logo['description']); ?>" 
                 alt="Primary Logo" 
                 loading="lazy"
                 style="max-height: 200px; max-width: 100%;">
            <div class="mt-3">
                <p class="text-muted mb-1">Filename: <?php echo htmlspecialchars($primary_logo['description']); ?></p>
                <p class="text-muted mb-0">Set on: <?php echo $primary_logo['modify_dt'] ? date('M j, Y', strtotime($primary_logo['modify_dt'])) : 'Unknown'; ?></p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>No primary logo set for this company.
    </div>
    <?php endif; ?>

    <!-- All Available Logos -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Available Logos</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($logos)): ?>
                <div class="row g-3">
                    <?php foreach ($logos as $logo): 
                        $is_primary = $logo['grouping'] === 'primary_logo';
                    ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="logo-card position-relative <?php echo $is_primary ? 'primary' : ''; ?>" 
                             data-logo-id="<?php echo $logo['attribute_id']; ?>">
                            <?php if ($is_primary): ?>
                                <span class="badge bg-success primary-badge">Primary</span>
                            <?php endif; ?>
                            
                            <img src="/public/images/placeholder-logo.svg" 
                                 data-src="<?php echo $display->companyimage($company_id . '/' . $logo['description']); ?>" 
                                 alt="Logo <?php echo htmlspecialchars($logo['name']); ?>"
                                 loading="lazy"
                                 class="lazy"
                                 onerror="this.onerror=null; this.src='/public/images/placeholder-logo.svg';">
                            
                            <div class="logo-info">
                                <p class="mb-1"><strong><?php echo htmlspecialchars($logo['name']); ?></strong></p>
                                <p class="mb-0 text-truncate"><?php echo htmlspecialchars($logo['description']); ?></p>
                            </div>
                            
                            <div class="logo-actions">
                                <?php if (!$is_primary): ?>
                                <button class="btn btn-sm btn-success" onclick="setPrimaryLogo(<?php echo $logo['attribute_id']; ?>)">
                                    <i class="bi bi-star me-1"></i>Set as Primary
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-danger" onclick="deleteLogo(<?php echo $logo['attribute_id']; ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted text-center mb-0">No logos found for this company.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Fetch Logos Modal -->
<div class="modal fade" id="fetchLogosModal" tabindex="-1" aria-labelledby="fetchLogosModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fetchLogosModalLabel">Fetch Logos from Apple App Store</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>This will fetch logos from the company's Apple App Store page.</p>
                <p class="text-muted">The process will download available logo images and save them to the company's logo collection.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="executeFetchLogos()">Fetch Logos</button>
            </div>
        </div>
    </div>
</div>

<!-- Fetch Progress Modal -->
<div class="modal fade" id="fetchProgressModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Fetching Logos...</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center" id="fetchLoadingSection">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Please wait while we fetch logos from the Apple App Store...</p>
                </div>
                <div id="fetchResults" class="mt-3" style="display:none;">
                    <h6>Results:</h6>
                    <div id="fetchResultsContent" class="small" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.25rem; padding: 1rem;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="display:none;" id="closeButton">Close</button>
                <button type="button" class="btn btn-primary" onclick="location.reload()" style="display:none;" id="refreshButton">Refresh Page</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Logo Confirmation Modal -->
<div class="modal fade" id="deleteLogoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Delete Logo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bi bi-trash text-danger" style="font-size: 3rem;"></i>
                </div>
                <h6>Are you sure you want to delete this logo?</h6>
                <div id="deleteLogoInfo" class="mt-3">
                    <!-- Logo info will be populated here -->
                </div>
                <div class="alert alert-warning mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note:</strong> This action will mark the logo as inactive. The image will be permanently removed from our servers after 120 days.
                </div>
                <div class="text-muted small">
                    <p class="mb-1"><strong>Deletion Timeline:</strong></p>
                    <ul class="mb-0">
                        <li>Day 1-59: Logo is hidden but can be restored</li>
                        <li>Day 60: First deletion warning sent</li>
                        <li>Day 90: Final deletion warning sent</li>
                        <li>Day 120: Logo permanently deleted</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteLogo">
                    <i class="bi bi-trash me-2"></i>Delete Logo
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function fetchNewLogos() {
    var modal = new bootstrap.Modal(document.getElementById('fetchLogosModal'));
    modal.show();
}

function executeFetchLogos() {
    // Close the confirmation modal
    bootstrap.Modal.getInstance(document.getElementById('fetchLogosModal')).hide();
    
    // Show progress modal
    var progressModal = new bootstrap.Modal(document.getElementById('fetchProgressModal'));
    progressModal.show();
    
    // Execute the fetch via AJAX
    $.ajax({
        url: '/admin_actions/abo/grab_logos.php',
        method: 'GET',
        data: {
            cid: <?php echo $company_id; ?>
        },
        success: function(response) {
            $('#fetchLoadingSection').hide();
            $('#fetchResults').show();
            $('#fetchResultsContent').html(response);
            $('#refreshButton').show();
            $('#closeButton').show();
            // Update modal title
            $('.modal-title', '#fetchProgressModal').text('Logo Fetch Results');
        },
        error: function(xhr, status, error) {
            $('#fetchLoadingSection').hide();
            $('#fetchResults').show();
            $('#fetchResultsContent').html('<div class="alert alert-danger">Error: ' + error + '</div>');
            $('#refreshButton').show();
            $('#closeButton').show();
            // Update modal title
            $('.modal-title', '#fetchProgressModal').text('Logo Fetch Error');
        }
    });
}

function setPrimaryLogo(logoId) {
    if (confirm('Set this logo as the primary logo?')) {
        $.ajax({
            url: '/admin_actions/set_primary_logo.php',
            method: 'POST',
            data: {
                company_id: <?php echo $company_id; ?>,
                logo_id: logoId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error setting primary logo. Please try again.');
            }
        });
    }
}

var pendingDeleteLogoId = null;

function deleteLogo(logoId) {
    // Store the logo ID
    pendingDeleteLogoId = logoId;
    
    // Find the logo card to get info
    var logoCard = $('[data-logo-id="' + logoId + '"]');
    var logoName = logoCard.find('.logo-info strong').text();
    var logoFilename = logoCard.find('.logo-info .text-truncate').text();
    var logoImg = logoCard.find('img').attr('data-src') || logoCard.find('img').attr('src');
    
    // Populate modal with logo info
    var infoHtml = '<div class="d-flex align-items-center">';
    infoHtml += '<img src="' + logoImg + '" style="max-width: 100px; max-height: 100px; margin-right: 1rem;" class="border rounded">';
    infoHtml += '<div>';
    infoHtml += '<p class="mb-1"><strong>Name:</strong> ' + logoName + '</p>';
    infoHtml += '<p class="mb-0"><strong>File:</strong> ' + logoFilename + '</p>';
    infoHtml += '</div>';
    infoHtml += '</div>';
    
    $('#deleteLogoInfo').html(infoHtml);
    
    // Show modal
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteLogoModal'));
    deleteModal.show();
}

// Handle delete confirmation
$('#confirmDeleteLogo').on('click', function() {
    if (pendingDeleteLogoId) {
        // Show loading state
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Deleting...');
        
        $.ajax({
            url: '/admin_actions/delete_logo.php',
            method: 'POST',
            data: {
                logo_id: pendingDeleteLogoId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    // Hide modal and show error
                    bootstrap.Modal.getInstance(document.getElementById('deleteLogoModal')).hide();
                    alert('Error: ' + response.message);
                    
                    // Reset button
                    $('#confirmDeleteLogo').prop('disabled', false).html('<i class="bi bi-trash me-2"></i>Delete Logo');
                }
            },
            error: function() {
                // Hide modal and show error
                bootstrap.Modal.getInstance(document.getElementById('deleteLogoModal')).hide();
                alert('Error deleting logo. Please try again.');
                
                // Reset button
                $('#confirmDeleteLogo').prop('disabled', false).html('<i class="bi bi-trash me-2"></i>Delete Logo');
            }
        });
    }
});

function uploadLogo() {
    alert('Upload functionality coming soon. For now, use the "Fetch New Logos" feature.');
}
// Lazy loading implementation
document.addEventListener('DOMContentLoaded', function() {
    // Native lazy loading fallback using Intersection Observer
    const lazyImages = document.querySelectorAll('img.lazy');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const image = entry.target;
                    image.src = image.dataset.src;
                    image.classList.remove('lazy');
                    imageObserver.unobserve(image);
                }
            });
        });

        lazyImages.forEach(function(image) {
            imageObserver.observe(image);
        });
    } else {
        // Fallback for browsers that don't support Intersection Observer
        lazyImages.forEach(function(image) {
            image.src = image.dataset.src;
            image.classList.remove('lazy');
        });
    }
});
</script>