<?php
// Start output buffering to prevent any accidental output
ob_start();

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager.php');

// Admin access is managed by site-controller

// Initialize ProductManager
$productManager = new ProductManager($database, $qik);

// Get current version (default to v7)
$current_version = $_GET['version'] ?? 'v7';

// Handle AJAX requests
if (isset($_POST['ajax_action'])) {
    // Prevent any output before JSON
    ob_clean();
    error_reporting(0);
    ini_set('display_errors', 0);
    
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    switch ($_POST['ajax_action']) {
        case 'save_product':
            try {
                $product_id = intval($_POST['product_id']);
                $data = [
                    'account_name' => trim($_POST['account_name']),
                    'description' => trim($_POST['description']),
                    'price' => intval($_POST['price']),
                    'status' => trim($_POST['status']),
                    'allow_promo' => trim($_POST['allow_promo']),
                    'account_verification' => trim($_POST['account_verification']),
                    'billing_cycle' => trim($_POST['billing_cycle'] ?? 'yearly'),
                    'display_grouping' => trim($_POST['display_grouping'] ?? ''),
                    'display_grouping_status' => trim($_POST['display_grouping_status'] ?? 'inactive')
                ];
                
                $sql = "UPDATE bg_products SET 
                        account_name = :account_name,
                        description = :description,
                        price = :price,
                        billing_cycle = :billing_cycle,
                        status = :status,
                        allow_promo = :allow_promo,
                        account_verification = :account_verification,
                        display_grouping = :display_grouping,
                        display_grouping_status = :display_grouping_status,
                        modify_dt = NOW()
                        WHERE id = :product_id";
                
                $stmt = $database->prepare($sql);
                $data['product_id'] = $product_id;
                $stmt->execute($data);
                
                ob_clean();
                echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'save_feature':
            try {
                $feature_id = intval($_POST['feature_id']);
                $product_id = intval($_POST['product_id']);
                $name = trim($_POST['name']);
                $value = trim($_POST['value']);
                $status = trim($_POST['status']);
                
                if ($feature_id > 0) {
                    // Update existing feature
                    $sql = "UPDATE bg_product_features SET 
                            name = :name,
                            value = :value,
                            status = :status,
                            display_mode = :display_mode
                            WHERE id = :feature_id";
                    
                    $stmt = $database->prepare($sql);
                    $stmt->execute([
                        'name' => $name,
                        'value' => $value,
                        'status' => $status,
                        'display_mode' => $_POST['display_mode'] ?? 'show',
                        'feature_id' => $feature_id
                    ]);
                } else {
                    // Get product info for new feature
                    $product = $productManager->getProduct($product_id);
                    
                    // Insert new feature
                    $sql = "INSERT INTO bg_product_features 
                            (product_id, version, plan, name, value, status, display_mode)
                            VALUES (:product_id, :version, :plan, :name, :value, :status, :display_mode)";
                    
                    $stmt = $database->prepare($sql);
                    $stmt->execute([
                        'product_id' => $product_id,
                        'version' => $product['version'],
                        'plan' => $product['account_plan'],
                        'name' => $name,
                        'value' => $value,
                        'status' => $status,
                        'display_mode' => $_POST['display_mode'] ?? 'show'
                    ]);
                }
                
                ob_clean();
                echo json_encode(['success' => true, 'message' => 'Feature saved successfully']);
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'delete_feature':
            try {
                $feature_id = intval($_POST['feature_id']);
                
                $sql = "DELETE FROM bg_product_features WHERE id = :feature_id";
                $stmt = $database->prepare($sql);
                $stmt->execute(['feature_id' => $feature_id]);
                
                ob_clean();
                echo json_encode(['success' => true, 'message' => 'Feature deleted successfully']);
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'create_product':
            try {
                $data = [
                    'version' => trim($_POST['version']),
                    'account_type' => trim($_POST['account_type']),
                    'account_plan' => trim($_POST['account_plan']),
                    'account_name' => trim($_POST['account_name']),
                    'description' => trim($_POST['description']),
                    'price' => intval($_POST['price']),
                    'status' => trim($_POST['status']),
                    'allow_promo' => trim($_POST['allow_promo']),
                    'account_verification' => trim($_POST['account_verification']),
                    'billing_cycle' => trim($_POST['billing_cycle'] ?? 'yearly'),
                    'redirect_url' => '/createnewaccount.php',
                    'display_grouping' => '',
                    'display_grouping_status' => 'inactive'
                ];
                
                $sql = "INSERT INTO bg_products 
                        (version, account_type, account_plan, account_name, description, 
                         price, billing_cycle, status, allow_promo, account_verification, redirect_url, 
                         display_grouping, display_grouping_status, create_dt, modify_dt)
                        VALUES 
                        (:version, :account_type, :account_plan, :account_name, :description,
                         :price, :billing_cycle, :status, :allow_promo, :account_verification, :redirect_url,
                         :display_grouping, :display_grouping_status, NOW(), NOW())";
                
                $stmt = $database->prepare($sql);
                $stmt->execute($data);
                
                ob_clean();
                echo json_encode(['success' => true, 'message' => 'Product created successfully']);
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
    }
}

// Get all account types (including inactive ones for admin)
$accountTypes = $productManager->getAvailableAccountTypes($current_version, true);

// Get all products for current version (including inactive ones for admin visibility)
$sql = "SELECT * FROM bg_products WHERE version = :version ORDER BY account_type, price";
$stmt = $database->prepare($sql);
$stmt->execute(['version' => $current_version]);
$allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group products by account type
$productsByType = [];
foreach ($allProducts as $product) {
    $productsByType[$product['account_type']][] = $product;
}

// Page configuration
$page_title = "Plan Editor - Admin";
$page_description = "Manage signup plans and pricing";
$header_flush = true; // Flush header to top

// Additional styles
$additionalstyles .= '<link rel="stylesheet" href="/public/css/v7/bg_theme.css">';

$additionalstyles .= '
<style>
/* Hide skip to main content link */
a[href="#main-content"],
.sr-only,
.sr-only-focusable {
    display: none !important;
    visibility: hidden !important;
    position: absolute !important;
    left: -9999px !important;
}

/* Remove all extra spacing after content header */
.content-header-admin {
    margin-bottom: 0 !important;
}

/* Modern Plan Editor Styles */
.main-content {
    min-height: calc(100vh - 200px);
    padding-top: 0 !important;
    padding-bottom: 2rem;
    background: #f8f9fa;
    margin-top: 0 !important;
}

/* Ensure container has minimal top padding */
.main-content .container {
    padding-top: 1rem !important;
}
.plan-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.plan-card:hover {
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.plan-card.grouping-inactive {
    background: #f8f9fa;
    border: 2px dashed #6c757d;
    opacity: 0.8;
}

.grouping-status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 4px;
    margin-left: 0.5rem;
}

.grouping-inactive-badge {
    background: #ffc107;
    color: #000;
}

.plan-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.plan-price {
    font-size: 1.5rem;
    font-weight: 600;
    color: #198754;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.feature-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.feature-item:last-child {
    border-bottom: none;
}

.feature-visibility-icon {
    font-size: 1.1rem;
}

.feature-visibility-icon.show {
    color: #198754; /* Bootstrap success green */
}

.feature-visibility-icon.hide {
    color: #dc3545; /* Bootstrap danger red */
}

.feature-visibility-icon.admin-only {
    color: #0dcaf0; /* Bootstrap info cyan */
}

.plan-id:hover {
    text-decoration: underline;
    color: #0d6efd;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-active {
    background: #d1f3d1;
    color: #0f5132;
}

.status-inactive {
    background: #f8d7da;
    color: #842029;
}

.edit-form {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-top: 1rem;
}

/* Tab navigation with active bottom border - matching loginhistory.php */
.nav-tabs-modern {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
    gap: 0;
    overflow: hidden;
    position: relative;
}

.nav-tab-item {
    flex: 0 0 auto;
    padding: 1rem 2rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
    background: none;
    border-radius: 0;
    position: relative;
}

.nav-tab-item:hover {
    color: #495057;
    text-decoration: none;
    background: #f8f9fa;
}

.nav-tab-item.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd !important;
    background: none;
}

.account-type-section {
    margin-bottom: 3rem;
}

.account-type-header {
    background: #f8f9fa;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.features-section {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
    margin-top: 1rem;
}

.feature-actions {
    display: flex;
    gap: 0.5rem;
}
</style>
';


$bodycontentclass = ''; // This removes the my-4 margin from the row after nav
$header_flush = true; // Ensure header content is flush with admin header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Admin Header Section -->
<div class="content-header-admin">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-list-ul me-3"></i>Plan Editor</h1>
            <p class="lead mb-0">Manage signup plans and pricing for Birthday.Gold</p>
        </div>
    </div>
</div>

<div class="main-content">
<div class="container">
    <!-- Notification Container -->
    <div id="notificationContainer" class="position-fixed top-0 start-50 translate-middle-x" style="z-index: 1050; margin-top: 20px;"></div>
    
    <div class="row">
        <div class="col-12">
            
            <!-- Feature Display Mode Legend -->
            <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                <small>
                    <strong>Feature Visibility:</strong>
                    <span class="ms-3"><i class="bi bi-eye text-success"></i> Visible to users</span>
                    <span class="ms-3"><i class="bi bi-shield-lock text-info"></i> Admin only</span>
                    <span class="ms-3"><i class="bi bi-eye-slash text-danger"></i> Hidden (internal use)</span>
                    <br>
                    <strong>Plan IDs:</strong> Use the ID numbers shown on each plan when creating features that reference other plans (e.g., upgradeable_to: 123)
                </small>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            
            <!-- Version Tabs - Modern Style -->
            <nav class="nav-tabs-modern">
                <a href="?version=v7" class="nav-tab-item <?php echo $current_version == 'v7' ? 'active' : ''; ?>">
                    <i class="bi bi-box-seam me-2"></i>Version 7
                </a>
                <a href="?version=v3" class="nav-tab-item <?php echo $current_version == 'v3' ? 'active' : ''; ?>">
                    <i class="bi bi-box me-2"></i>Version 3
                </a>
                <a href="?version=v2" class="nav-tab-item <?php echo $current_version == 'v2' ? 'active' : ''; ?>">
                    <i class="bi bi-archive me-2"></i>Version 2
                </a>
            </nav>
            
            <!-- Create New Product Button -->
            <div class="mb-4">
                <button class="btn btn-success" onclick="showCreateProductModal()">
                    <i class="bi bi-plus-circle"></i> Create New Plan
                </button>
            </div>
            
            <!-- Products by Account Type -->
            <?php foreach ($accountTypes as $type): 
                // Check if all products in this type are inactive
                $typeProducts = $productsByType[$type['account_type']] ?? [];
                $allInactive = true;
                foreach ($typeProducts as $prod) {
                    if ($prod['display_grouping_status'] === 'active') {
                        $allInactive = false;
                        break;
                    }
                }
            ?>
            <div class="account-type-section">
                <div class="account-type-header <?php echo $allInactive ? 'bg-warning bg-opacity-10' : ''; ?>">
                    <div>
                        <h3 class="mb-0">
                            <i class="bi <?php echo $type['icon'] ?? 'bi-person'; ?>"></i>
                            <?php echo htmlspecialchars($type['display_name'] ?? ''); ?>
                            <?php if ($allInactive): ?>
                                <span class="badge bg-warning text-dark ms-2">All Hidden</span>
                            <?php endif; ?>
                        </h3>
                        <small class="text-muted">
                            <?php echo $type['plan_count']; ?> plan<?php echo $type['plan_count'] != 1 ? 's' : ''; ?>
                        </small>
                    </div>
                </div>
                
                <div class="row">
                    <?php 
                    $typeProducts = $productsByType[$type['account_type']] ?? [];
                    foreach ($typeProducts as $product): 
                        $features = $productManager->getProductFeatures($product['id'], false, 'all');
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="plan-card <?php echo $product['display_grouping_status'] === 'inactive' ? 'grouping-inactive' : ''; ?>" 
                             data-product-id="<?php echo $product['id']; ?>">
                            <div class="plan-header">
                                <h4>
                                    <?php echo htmlspecialchars($product['account_name'] ?? ''); ?>
                                    <small class="text-muted" style="font-size: 0.6em; font-weight: normal;">
                                        (ID: <span class="plan-id" style="cursor: pointer;" onclick="copyToClipboard('<?php echo $product['id']; ?>')" title="Click to copy ID"><?php echo $product['id']; ?></span>)
                                    </small>
                                    <?php if ($product['display_grouping_status'] === 'inactive'): ?>
                                        <span class="grouping-status-badge grouping-inactive-badge" title="Not displayed on signup page">
                                            <i class="bi bi-eye-slash"></i> Hidden
                                        </span>
                                    <?php endif; ?>
                                </h4>
                                <span class="status-badge status-<?php echo $product['status']; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </div>
                            
                            <div class="plan-price mb-3">
                                <?php if ($product['price'] == 0): ?>
                                    FREE
                                <?php else: ?>
                                    $<?php echo number_format($product['price'] / 100, 2); ?>
                                    <?php 
                                    $billing_cycle = $product['billing_cycle'] ?? 'yearly';
                                    switch($billing_cycle) {
                                        case 'monthly': echo '/month'; break;
                                        case 'yearly': echo '/year'; break;
                                        case 'one_time': echo ' (one-time)'; break;
                                        case 'lifetime': echo ' (lifetime)'; break;
                                        default: echo '/' . $billing_cycle;
                                    }
                                    ?>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-muted mb-3">
                                <?php echo htmlspecialchars($product['description'] ?? ''); ?>
                                <br>
                                <small class="text-info">
                                    <i class="bi bi-tag"></i> Plan Code: <code><?php echo htmlspecialchars($product['account_plan'] ?? ''); ?></code>
                                </small>
                            </p>
                            
                            <!-- Features -->
                            <div class="features-section">
                                <h6 class="mb-2">Features:</h6>
                                <ul class="feature-list">
                                    <?php foreach ($features as $feature): 
                                        $displayMode = $feature['display_mode'] ?? 'show';
                                    ?>
                                    <li class="feature-item" 
                                        data-feature-id="<?php echo $feature['id']; ?>"
                                        data-status="<?php echo htmlspecialchars($feature['status'] ?? 'active'); ?>"
                                        data-display-mode="<?php echo htmlspecialchars($displayMode); ?>">
                                        <span>
                                            <?php if ($displayMode === 'hide'): ?>
                                                <i class="bi bi-eye-slash me-1 feature-visibility-icon hide" title="Hidden from users"></i>
                                            <?php elseif ($displayMode === 'admin_only'): ?>
                                                <i class="bi bi-shield-lock me-1 feature-visibility-icon admin-only" title="Admin only"></i>
                                            <?php else: ?>
                                                <i class="bi bi-eye me-1 feature-visibility-icon show" title="Visible to users"></i>
                                            <?php endif; ?>
                                            <strong><?php echo htmlspecialchars($feature['name'] ?? ''); ?>:</strong>
                                            <?php echo htmlspecialchars($feature['value'] ?? ''); ?>
                                        </span>
                                        <div class="feature-actions">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="editFeature(<?php echo $feature['id']; ?>, <?php echo $product['id']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteFeature(<?php echo $feature['id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <button class="btn btn-sm btn-outline-success mt-2" 
                                        onclick="addFeature(<?php echo $product['id']; ?>)">
                                    <i class="bi bi-plus"></i> Add Feature
                                </button>
                            </div>
                            
                            <!-- Actions -->
                            <div class="mt-3">
                                <button class="btn btn-primary btn-sm" 
                                        onclick="editProduct(<?php echo $product['id']; ?>)">
                                    <i class="bi bi-pencil"></i> Edit Plan
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" 
                                        onclick="previewPlan(<?php echo $product['id']; ?>)">
                                    <i class="bi bi-eye"></i> Preview
                                </button>
                                <?php if ($product['display_grouping_status'] === 'inactive'): ?>
                                    <button class="btn btn-warning btn-sm" 
                                            onclick="toggleGroupingStatus(<?php echo $product['id']; ?>, 'active')"
                                            title="Make visible on signup page">
                                        <i class="bi bi-eye"></i> Show
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-outline-warning btn-sm" 
                                            onclick="toggleGroupingStatus(<?php echo $product['id']; ?>, 'inactive')"
                                            title="Hide from signup page">
                                        <i class="bi bi-eye-slash"></i> Hide
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editProductForm">
                    <input type="hidden" id="edit_product_id" name="product_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Plan Name</label>
                                <input type="text" class="form-control" id="edit_account_name" name="account_name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Price (in cents)</label>
                                <input type="number" class="form-control" id="edit_price" name="price" min="0" required>
                                <small class="text-muted">Enter 0 for free plans</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label">Billing Cycle</label>
                                <select class="form-select" id="edit_billing_cycle" name="billing_cycle">
                                    <option value="one_time">One-time</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                    <option value="lifetime">Lifetime</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Allow Promo Codes</label>
                                <select class="form-select" id="edit_allow_promo" name="allow_promo">
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Account Verification</label>
                                <select class="form-select" id="edit_account_verification" name="account_verification">
                                    <option value="required">Required</option>
                                    <option value="notrequired">Not Required</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Display Grouping (optional)</label>
                                <input type="text" class="form-control" id="edit_display_grouping" name="display_grouping">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">
                                    Grouping Status 
                                    <i class="bi bi-info-circle" title="Controls visibility on signup page"></i>
                                </label>
                                <select class="form-select" id="edit_display_grouping_status" name="display_grouping_status">
                                    <option value="active">Active (Visible on Signup)</option>
                                    <option value="inactive">Inactive (Hidden from Signup)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveProduct()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Product Modal -->
<div class="modal fade" id="createProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createProductForm">
                    <input type="hidden" name="version" value="<?php echo $current_version; ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Account Type</label>
                                <select class="form-select" id="create_account_type" name="account_type" required>
                                    <option value="">Select account type...</option>
                                    <?php foreach ($accountTypes as $type): ?>
                                    <option value="<?php echo $type['account_type']; ?>">
                                        <?php echo htmlspecialchars($type['display_name'] ?? ''); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Plan ID</label>
                                <input type="text" class="form-control" id="create_account_plan" name="account_plan" required
                                       placeholder="e.g., gold, premium, lifetime">
                                <small class="text-muted">Lowercase, no spaces</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Plan Name</label>
                                <input type="text" class="form-control" id="create_account_name" name="account_name" required
                                       placeholder="e.g., Gold Membership">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Price (in cents)</label>
                                <input type="number" class="form-control" id="create_price" name="price" min="0" required>
                                <small class="text-muted">Enter 0 for free plans</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label">Billing Cycle</label>
                                <select class="form-select" id="create_billing_cycle" name="billing_cycle">
                                    <option value="one_time">One-time</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly" selected>Yearly</option>
                                    <option value="lifetime">Lifetime</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="create_description" name="description" rows="3"
                                  placeholder="Brief description of the plan"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="create_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Allow Promo Codes</label>
                                <select class="form-select" id="create_allow_promo" name="allow_promo">
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Account Verification</label>
                                <select class="form-select" id="create_account_verification" name="account_verification">
                                    <option value="required">Required</option>
                                    <option value="notrequired">Not Required</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="createProduct()">Create Plan</button>
            </div>
        </div>
    </div>
</div>

<!-- Feature Modal -->
<div class="modal fade" id="featureModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Feature</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="featureForm">
                    <input type="hidden" id="feature_id" name="feature_id">
                    <input type="hidden" id="feature_product_id" name="product_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Feature Name</label>
                        <input type="text" class="form-control" id="feature_name" name="name" required
                               placeholder="e.g., Enrollments, Support">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Feature Value</label>
                        <input type="text" class="form-control" id="feature_value" name="value" required
                               placeholder="e.g., Unlimited, 24/7 Priority">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="feature_status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Display Mode</label>
                        <select class="form-select" id="feature_display_mode" name="display_mode">
                            <option value="show">Show (visible to users)</option>
                            <option value="hide">Hide (internal use only)</option>
                            <option value="admin_only">Admin Only (visible in admin panel)</option>
                        </select>
                        <small class="text-muted">Controls where this feature is displayed</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveFeature()">Save Feature</button>
            </div>
        </div>
    </div>
</div>

<script>
// Product data for JavaScript
const products = <?php echo json_encode($allProducts); ?>;

// Copy to clipboard function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showNotification('ID ' + text + ' copied to clipboard!', 'success');
    }, function(err) {
        // Fallback for older browsers
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showNotification('ID ' + text + ' copied to clipboard!', 'success');
        } catch (err) {
            showNotification('Failed to copy ID', 'danger');
        }
        document.body.removeChild(textArea);
    });
}

// Show notification function
function showNotification(message, type = 'success') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    const container = document.getElementById('notificationContainer');
    container.innerHTML = alertHtml;
    
    // Auto-dismiss after 7 seconds
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 7000);
}

// Get product by ID
function getProductById(productId) {
    return products.find(p => p.id == productId);
}

// Edit product
function editProduct(productId) {
    const product = getProductById(productId);
    if (!product) return;
    
    // Helper function to safely trim values
    const trimValue = (value) => {
        if (value === null || value === undefined) return '';
        return String(value).trim();
    };
    
    // Populate form with trimmed values
    document.getElementById('edit_product_id').value = product.id;
    document.getElementById('edit_account_name').value = trimValue(product.account_name);
    document.getElementById('edit_price').value = product.price;
    document.getElementById('edit_description').value = trimValue(product.description);
    document.getElementById('edit_status').value = trimValue(product.status);
    document.getElementById('edit_allow_promo').value = trimValue(product.allow_promo);
    document.getElementById('edit_account_verification').value = trimValue(product.account_verification);
    document.getElementById('edit_billing_cycle').value = trimValue(product.billing_cycle) || 'yearly';
    document.getElementById('edit_display_grouping').value = trimValue(product.display_grouping);
    document.getElementById('edit_display_grouping_status').value = trimValue(product.display_grouping_status) || 'inactive';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
    modal.show();
}

// Save product
function saveProduct() {
    const form = document.getElementById('editProductForm');
    const formData = new FormData(form);
    formData.append('ajax_action', 'save_product');
    
    fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showNotification(data.message, 'success');
                location.reload();
            } else {
                showNotification('Error: ' + data.message, 'danger');
            }
        } catch (e) {
            console.error('Response:', text);
            showNotification('Server error: Invalid response format', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error saving product: ' + error.message, 'danger');
    });
}

// Show create product modal
function showCreateProductModal() {
    document.getElementById('createProductForm').reset();
    const modal = new bootstrap.Modal(document.getElementById('createProductModal'));
    modal.show();
}

// Create product
function createProduct() {
    const form = document.getElementById('createProductForm');
    const formData = new FormData(form);
    formData.append('ajax_action', 'create_product');
    
    fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showNotification(data.message, 'success');
                location.reload();
            } else {
                showNotification('Error: ' + data.message, 'danger');
            }
        } catch (e) {
            console.error('Response:', text);
            showNotification('Server error: Invalid response format', 'danger');
        }
    })
    .catch(error => {
        showNotification('Error creating product: ' + error, 'danger');
    });
}

// Edit feature
function editFeature(featureId, productId) {
    // Get feature data from DOM
    const featureItem = document.querySelector(`[data-feature-id="${featureId}"]`);
    const featureText = featureItem.querySelector('span').innerHTML;
    const matches = featureText.match(/<strong>(.*?):<\/strong>\s*(.*)/);
    
    if (matches) {
        // Helper function to safely trim values
        const trimValue = (value) => {
            if (value === null || value === undefined) return '';
            return String(value).trim();
        };
        
        document.getElementById('feature_id').value = featureId;
        document.getElementById('feature_product_id').value = productId;
        document.getElementById('feature_name').value = trimValue(matches[1]);
        
        // Extract the actual feature value (remove any HTML tags that might be present)
        let featureValue = matches[2];
        // Remove any HTML tags like icons or spans
        featureValue = featureValue.replace(/<[^>]*>/g, '').trim();
        document.getElementById('feature_value').value = featureValue;
        
        document.getElementById('feature_status').value = trimValue(featureItem.dataset.status) || 'active';
        document.getElementById('feature_display_mode').value = trimValue(featureItem.dataset.displayMode) || 'show';
        
        const modal = new bootstrap.Modal(document.getElementById('featureModal'));
        modal.show();
    }
}

// Add feature
function addFeature(productId) {
    document.getElementById('feature_id').value = '0';
    document.getElementById('feature_product_id').value = productId;
    document.getElementById('featureForm').reset();
    document.getElementById('feature_product_id').value = productId;
    
    // Set default display_mode to 'show'
    document.getElementById('feature_display_mode').value = 'show';
    
    const modal = new bootstrap.Modal(document.getElementById('featureModal'));
    modal.show();
}

// Auto-set display mode based on feature name
document.getElementById('feature_name').addEventListener('change', function() {
    const hiddenFeatures = ['allow_promo', 'account_verification', 'redirect_url', 'display_grouping', 'display_grouping_status'];
    const adminOnlyFeatures = ['setup_fee', 'activation_fee', 'processing_note', 'admin_override'];
    
    const featureName = this.value.toLowerCase().replace(/\s+/g, '_');
    
    if (hiddenFeatures.includes(featureName)) {
        document.getElementById('feature_display_mode').value = 'hide';
    } else if (adminOnlyFeatures.includes(featureName)) {
        document.getElementById('feature_display_mode').value = 'admin_only';
    }
});

// Save feature
function saveFeature() {
    const form = document.getElementById('featureForm');
    const formData = new FormData(form);
    formData.append('ajax_action', 'save_feature');
    
    fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showNotification(data.message, 'success');
                location.reload();
            } else {
                showNotification('Error: ' + data.message, 'danger');
            }
        } catch (e) {
            console.error('Response:', text);
            showNotification('Server error: Invalid response format', 'danger');
        }
    })
    .catch(error => {
        showNotification('Error saving feature: ' + error, 'danger');
    });
}

// Delete feature
function deleteFeature(featureId) {
    if (!confirm('Are you sure you want to delete this feature?')) return;
    
    const formData = new FormData();
    formData.append('ajax_action', 'delete_feature');
    formData.append('feature_id', featureId);
    
    fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showNotification(data.message, 'success');
                location.reload();
            } else {
                showNotification('Error: ' + data.message, 'danger');
            }
        } catch (e) {
            console.error('Response:', text);
            showNotification('Server error: Invalid response format', 'danger');
        }
    })
    .catch(error => {
        showNotification('Error deleting feature: ' + error, 'danger');
    });
}

// Preview plan
function previewPlan(productId) {
    const product = getProductById(productId);
    if (!product) return;
    
    // Open signup page with specific plan in new tab
    window.open(`/signup.php?type=${product.account_type}&plan=${product.account_plan}`, '_blank');
}

// Toggle grouping status quickly
function toggleGroupingStatus(productId, newStatus) {
    const product = getProductById(productId);
    if (!product) return;
    
    const formData = new FormData();
    formData.append('ajax_action', 'save_product');
    formData.append('product_id', productId);
    formData.append('account_name', product.account_name);
    formData.append('description', product.description || '');
    formData.append('price', product.price);
    formData.append('status', product.status);
    formData.append('allow_promo', product.allow_promo);
    formData.append('account_verification', product.account_verification);
    formData.append('billing_cycle', product.billing_cycle || 'yearly');
    formData.append('display_grouping', product.display_grouping || '');
    formData.append('display_grouping_status', newStatus);
    
    fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showNotification(
                    newStatus === 'active' 
                        ? 'Plan is now visible on signup page' 
                        : 'Plan is now hidden from signup page', 
                    'success'
                );
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error: ' + data.message, 'danger');
            }
        } catch (e) {
            console.error('Response:', text);
            showNotification('Server error: Invalid response format', 'danger');
        }
    })
    .catch(error => {
        showNotification('Error updating plan visibility: ' + error.message, 'danger');
    });
}
</script>

<?php
$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>