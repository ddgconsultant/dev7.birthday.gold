<?php
$addClasses[] = 'productmanager_promo';

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');
#include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager.php');
#if (file_exists($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager_promo.php')) {
#    include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager_promo.php');
#}

// Admin access is managed by site-controller

// Initialize ProductManager for promo validation
#$productManager = new ProductManager($database, $qik);

#-------------------------------------------------------------------------------
# HANDLE AJAX REQUESTS
#-------------------------------------------------------------------------------
if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
    // Prevent any output before JSON
    ob_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        switch ($action) {
            case 'list':
                // Get all promo codes with usage count
                $sql = "SELECT p.*, 
                        (SELECT COUNT(*) FROM bg_user_attributes 
                         WHERE type = 'promo_used' AND string_value = p.code) as times_used
                        FROM bg_promocodes p 
                        ORDER BY p.status DESC, p.create_dt DESC";
                
                $stmt = $database->prepare($sql);
                $stmt->execute();
                $promos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format dates and calculate status
                foreach ($promos as &$promo) {
                    $promo['is_active'] = $promo['status'] === 'active';
                    
                    // Check date validity
                    if (!empty($promo['start_dt']) && strtotime($promo['start_dt']) > time()) {
                        $promo['date_status'] = 'future';
                    } elseif (!empty($promo['end_dt']) && strtotime($promo['end_dt']) < time()) {
                        $promo['date_status'] = 'expired';
                    } else {
                        $promo['date_status'] = 'current';
                    }
                    
                    // Check usage limit
                    if ($promo['limit_count'] > 0 && $promo['times_used'] >= $promo['limit_count']) {
                        $promo['usage_status'] = 'limit_reached';
                    } else {
                        $promo['usage_status'] = 'available';
                    }
                }
                
                ob_clean();
                echo json_encode(['success' => true, 'promos' => $promos]);
                break;
                
            case 'get':
                $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
                $sql = "SELECT * FROM bg_promocodes WHERE id = ?";
                $stmt = $database->prepare($sql);
                $stmt->execute([$id]);
                $promo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($promo) {
                    // Get products that allow this promo
                    $sql = "SELECT p.id, p.account_name, p.account_type 
                            FROM bg_products p
                            LEFT JOIN bg_product_features pf ON p.id = pf.product_id 
                                AND pf.name = 'allowed_promos'
                            WHERE p.version = 'v3' 
                            AND (
                                (p.allow_promo = 'yes' AND (pf.value IS NULL OR pf.value = 'all'))
                                OR pf.value LIKE ?
                            )";
                    $stmt = $database->prepare($sql);
                    $stmt->execute(['%' . $promo['code'] . '%']);
                    $promo['allowed_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                ob_clean();
                echo json_encode(['success' => true, 'promo' => $promo]);
                break;
                
            case 'save':
                $id = intval($_POST['id'] ?? 0);
                $data = [
                    'code' => strtoupper(trim($_POST['code'] ?? '')),
                    'description' => trim($_POST['description'] ?? ''),
                    'discountmethod' => trim($_POST['discountmethod'] ?? 'percentage'),
                    'amount' => intval($_POST['amount'] ?? 0),
                    'limit_count' => intval($_POST['limit_count'] ?? 0),
                    'start_dt' => $_POST['start_dt'] ?: null,
                    'end_dt' => $_POST['end_dt'] ?: null,
                    'successmessage' => trim($_POST['successmessage'] ?? ''),
                    'status' => trim($_POST['status'] ?? 'active')
                ];
                
                // Validate code format
                if (!preg_match('/^[A-Z0-9_-]+$/', $data['code'])) {
                    throw new Exception('Invalid code format. Use only letters, numbers, dash and underscore.');
                }
                
                // Validate discount amount
                if ($data['discountmethod'] === 'percentage' && ($data['amount'] < 0 || $data['amount'] > 100)) {
                    throw new Exception('Percentage must be between 0 and 100.');
                }
                
                if ($id > 0) {
                    // Update existing
                    $sql = "UPDATE bg_promocodes SET 
                            code = ?,
                            description = ?,
                            discountmethod = ?,
                            amount = ?,
                            limit_count = ?,
                            start_dt = ?,
                            end_dt = ?,
                            successmessage = ?,
                            status = ?,
                            modify_dt = NOW()
                            WHERE id = ?";
                    
                    $params = [
                        $data['code'],
                        $data['description'],
                        $data['discountmethod'],
                        $data['amount'],
                        $data['limit_count'],
                        $data['start_dt'],
                        $data['end_dt'],
                        $data['successmessage'],
                        $data['status'],
                        $id
                    ];
                    
                    $stmt = $database->prepare($sql);
                    $stmt->execute($params);
                } else {
                    // Check if code already exists
                    $stmt = $database->prepare("SELECT id FROM bg_promocodes WHERE code = ?");
                    $stmt->execute([$data['code']]);
                    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($existing) {
                        throw new Exception('This promo code already exists.');
                    }
                    
                    // Insert new
                    $sql = "INSERT INTO bg_promocodes 
                            (code, description, discountmethod, amount, limit_count, 
                             start_dt, end_dt, successmessage, status, tracking_count, create_dt)
                            VALUES 
                            (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())";
                    
                    $params = [
                        $data['code'],
                        $data['description'],
                        $data['discountmethod'],
                        $data['amount'],
                        $data['limit_count'],
                        $data['start_dt'],
                        $data['end_dt'],
                        $data['successmessage'],
                        $data['status']
                    ];
                    
                    $stmt = $database->prepare($sql);
                    $stmt->execute($params);
                }
                
                ob_clean();
                echo json_encode(['success' => true, 'message' => 'Promo code saved successfully']);
                break;
                
            case 'delete':
                $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
                
                // Soft delete by setting status to deleted
                $sql = "UPDATE bg_promocodes SET status = 'deleted', modify_dt = NOW() WHERE id = ?";
                $stmt = $database->prepare($sql);
                $stmt->execute([$id]);
                
                ob_clean();
                echo json_encode(['success' => true, 'message' => 'Promo code deleted']);
                break;
                
            case 'test':
                $code = trim($_GET['code'] ?? $_POST['code'] ?? '');
                $product_id = intval($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
                
                // Use ProductManagerPromo if available, otherwise ProductManager
               # if (class_exists('ProductManagerPromo')) {
              #      $promoManager = new ProductManagerPromo($database, $qik);
             #       $result = $promoManager->validatePromoCode($code, $product_id);
           #     } else {
                    $result = $productManager->validatePromoCode($code, $product_id);
           #     }
                
                ob_clean();
                echo json_encode($result);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    exit;
}

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------

// Get ALL promo codes with usage count
$sql = "SELECT DISTINCT p.*, 
        (SELECT COUNT(*) FROM bg_user_attributes 
         WHERE type = 'promo_used' AND string_value = p.code) as times_used
        FROM bg_promocodes p 
        WHERE p.status != 'deleted'
        ORDER BY p.status DESC, p.id DESC";
$stmt = $database->prepare($sql);
$stmt->execute();
$promos = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Format promo data for display
foreach ($promos as $key => $promo) {
    $promos[$key]['is_active'] = $promo['status'] === 'active';
    
    // Check date validity
    if (!empty($promo['start_dt']) && strtotime($promo['start_dt']) > time()) {
        $promos[$key]['date_status'] = 'future';
    } elseif (!empty($promo['end_dt']) && strtotime($promo['end_dt']) < time()) {
        $promos[$key]['date_status'] = 'expired';
    } else {
        $promos[$key]['date_status'] = 'current';
    }
    
    // Check usage limit
    if ($promo['limit_count'] > 0 && $promo['times_used'] >= $promo['limit_count']) {
        $promos[$key]['usage_status'] = 'limit_reached';
    } else {
        $promos[$key]['usage_status'] = 'available';
    }
}

// Get products for testing with their promo features
$sql = "SELECT p.id, p.account_name, p.account_type, p.price, p.allow_promo,
        pf.value as allowed_promos
        FROM bg_products p
        LEFT JOIN bg_product_features pf ON p.id = pf.product_id 
            AND pf.name = 'allowed_promos' AND pf.status = 'active'
        WHERE p.version = 'v3' 
        ORDER BY p.account_type, p.price";
$stmt = $database->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page configuration
$page_title = "Promo Code Manager";
$page_description = "Create and manage promotional codes for your products";
$bodycontentclass = '';
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

/* Main card styling */
.main-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

/* Promo-specific styles */
.promo-code {
    font-family: "Courier New", monospace;
    font-weight: bold;
    font-size: 1.1em;
    color: #0d6efd;
}

.promo-actions {
    white-space: nowrap;
}

/* Action buttons */
.btn-action {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 6px;
    transition: all 0.2s ease;
    min-width: 36px;
}

.btn-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-action i {
    font-size: 1rem;
}

/* Table styling */
.promo-table {
    font-size: 0.9rem;
}

.discount-badge {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
}

.status-badge {
    font-size: 0.75rem;
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
}

/* Test section styling */
.test-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-top: 2rem;
}

/* Discount method tabs */
.discount-method-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.discount-method-tab {
    flex: 1;
    padding: 0.75rem;
    text-align: center;
    border: 2px solid var(--bs-gray-300);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.discount-method-tab.active {
    background-color: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
}

.discount-method-tab:hover:not(.active) {
    background-color: var(--bs-gray-100);
}

/* Notification animation */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    animation: slideIn 0.3s ease;
    max-width: 400px;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Date status indicators */
.date-indicator {
    font-size: 0.75rem;
    font-weight: normal;
}

.date-future { color: #0dcaf0; }
.date-expired { color: #dc3545; }
.date-current { color: #198754; }

/* Usage indicator */
.usage-indicator {
    font-size: 0.8rem;
}

/* Responsive table font size */
@media (max-width: 767px) {
    .table {
        font-size: 0.85rem;
    }
    
    .promo-actions .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}
</style>
';


#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Admin Header Section
echo '
<!-- Admin Header Section -->
<div class="content-header-admin">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-tags me-3"></i>Promo Code Manager</h1>
            <p class="lead mb-0">Create and manage promotional codes for your products</p>
        </div>
    </div>
</div>

<div class="my-5 pt-5">
    <div class="container mt-4">
        <!-- Main Content Card -->
        <div class="card main-card">
            <div class="card-header bg-primary">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="mb-0 text-white fw-bold">
                            <i class="bi bi-tags me-3"></i> Active Promo Codes
                        </h4>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-light btn-sm" onclick="createPromo()">
                            <i class="bi bi-plus-circle"></i> Create New Promo
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card-body my-2">
                
                <!-- Promo codes table -->
                <div class="table-responsive">
                    <table class="table table-hover promo-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Discount</th>
                                <th>Valid Dates</th>
                                <th>Usage</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="promoTableBody">';

if (empty($promos)) {
    echo '
                            <tr>
                                <td colspan="7" class="text-center">No promo codes found</td>
                            </tr>';
} else {
    foreach ($promos as $key => $promo) {
        // Format discount
        $discount = '';
        if ($promo['discountmethod'] === 'percentage' || $promo['discountmethod'] === 'count') {
            $discount = '<span class="badge bg-info discount-badge">' . $promo['amount'] . '% OFF</span>';
        } else if ($promo['discountmethod'] === 'amount') {
            $discount = '<span class="badge bg-info discount-badge">$' . number_format($promo['amount'] / 100, 2) . ' OFF</span>';
        }
        
        // Format dates
        $dateRange = '';
        if ($promo['start_dt'] || $promo['end_dt']) {
            $start = $promo['start_dt'] ? date('n/j/Y', strtotime($promo['start_dt'])) : 'Now';
            $end = $promo['end_dt'] ? date('n/j/Y', strtotime($promo['end_dt'])) : 'Never';
            $dateRange = $start . ' - ' . $end;
            
            if ($promo['date_status'] === 'future') {
                $dateRange .= ' <span class="date-indicator date-future">(Future)</span>';
            } else if ($promo['date_status'] === 'expired') {
                $dateRange .= ' <span class="date-indicator date-expired">(Expired)</span>';
            }
        } else {
            $dateRange = '<span class="text-muted">Always valid</span>';
        }
        
        // Format usage
        $usage = '';
        if ($promo['limit_count'] > 0) {
            $percent = ($promo['times_used'] / $promo['limit_count']) * 100;
            $barColor = $percent >= 90 ? 'danger' : ($percent >= 70 ? 'warning' : 'success');
            
            $usage = '
                <div class="usage-indicator">
                    <div>' . $promo['times_used'] . ' / ' . $promo['limit_count'] . '</div>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-' . $barColor . '" style="width: ' . $percent . '%"></div>
                    </div>
                </div>';
        } else {
            $usage = '<span class="text-muted">' . $promo['times_used'] . ' uses</span>';
        }
        
        // Status badge
        $statusBadge = '';
        if ($promo['status'] === 'active' && $promo['date_status'] === 'current' && $promo['usage_status'] === 'available') {
            $statusBadge = '<span class="badge bg-success status-badge">Active</span>';
        } else if ($promo['status'] === 'inactive') {
            $statusBadge = '<span class="badge bg-secondary status-badge">Inactive</span>';
        } else if ($promo['date_status'] === 'expired') {
            $statusBadge = '<span class="badge bg-danger status-badge">Expired</span>';
        } else if ($promo['date_status'] === 'future') {
            $statusBadge = '<span class="badge bg-info status-badge">Scheduled</span>';
        } else if ($promo['usage_status'] === 'limit_reached') {
            $statusBadge = '<span class="badge bg-warning status-badge">Limit Reached</span>';
        } else if ($promo['status'] === 'deleted') {
            $statusBadge = '<span class="badge bg-dark status-badge">Deleted</span>';
        }
        
        echo '
                            <tr>
                                <td><span class="promo-code">' . htmlspecialchars($promo['code']) . '</span> <small class="text-muted">(#' . $promo['id'] . ')</small></td>
                                <td>' . (empty($promo['description']) ? '<span class="text-muted">No description</span>' : htmlspecialchars($promo['description'])) . '</td>
                                <td>' . $discount . '</td>
                                <td>' . $dateRange . '</td>
                                <td>' . $usage . '</td>
                                <td>' . $statusBadge . '</td>
                                <td class="promo-actions">
                                    <button class="btn btn-action btn-outline-primary" onclick="editPromo(' . $promo['id'] . ')" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-action btn-outline-danger" onclick="deletePromo(' . $promo['id'] . ', \'' . htmlspecialchars($promo['code']) . '\')" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>';
    }
}

echo '
                        </tbody>
                    </table>
                </div>
                
                <!-- Test Promo Section -->
                <div class="test-section">
                    <h5 class="mb-3">Test Promo Code</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Promo Code</label>
                            <input type="text" class="form-control" id="testPromoCode" placeholder="Enter code to test">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Test with Product</label>
                            <select class="form-control" id="testProductId">
                                <option value="">Select a product...</option>';

foreach ($products as $product) {
    echo '
                                <option value="' . $product['id'] . '">
                                    ' . htmlspecialchars($product['account_name']) . ' 
                                    ($' . number_format($product['price'] / 100, 2) . ')';
    
    if ($product['allow_promo'] !== 'yes') {
        echo ' [No Promos]';
    } elseif (!empty($product['allowed_promos']) && $product['allowed_promos'] !== 'all') {
        echo ' [Specific Promos: ' . htmlspecialchars($product['allowed_promos']) . ']';
    }
    
    echo '
                                </option>';
}

echo '
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn btn-primary" onclick="testPromo()">
                                <i class="bi bi-play-circle"></i> Test Validation
                            </button>
                        </div>
                    </div>
                    <div id="testResult" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Promo Modal -->
<div class="modal fade" id="promoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="promoModalTitle">Create Promo Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="promoForm">
                    <input type="hidden" id="promoId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Promo Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="promoCode" name="code" required>
                            <small class="text-muted">Letters, numbers, dash and underscore only</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-control" id="promoStatus" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" id="promoDescription" name="description" 
                                   placeholder="Internal description of this promo code">
                        </div>
                        
                        <!-- Discount Method Selection -->
                        <div class="col-12">
                            <label class="form-label">Discount Type</label>
                            <div class="discount-method-tabs">
                                <div class="discount-method-tab active" data-method="percentage">
                                    <i class="bi bi-percent"></i>
                                    <div>Percentage Off</div>
                                </div>
                                <div class="discount-method-tab" data-method="amount">
                                    <i class="bi bi-currency-dollar"></i>
                                    <div>Fixed Amount Off</div>
                                </div>
                            </div>
                            <input type="hidden" id="promoDiscountMethod" name="discountmethod" value="percentage">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">
                                <span id="discountLabel">Discount Percentage</span> <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="promoAmount" name="amount" required min="0">
                                <span class="input-group-text" id="discountSuffix">%</span>
                            </div>
                            <small class="text-muted" id="discountHelp">Enter percentage (0-100)</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Usage Limit</label>
                            <input type="number" class="form-control" id="promoLimit" name="limit_count" min="0" value="0">
                            <small class="text-muted">0 = unlimited uses</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="datetime-local" class="form-control" id="promoStartDate" name="start_dt">
                            <small class="text-muted">Leave empty for immediate activation</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">End Date</label>
                            <input type="datetime-local" class="form-control" id="promoEndDate" name="end_dt">
                            <small class="text-muted">Leave empty for no expiration</small>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Success Message</label>
                            <textarea class="form-control" id="promoSuccessMessage" name="successmessage" rows="2"
                                      placeholder="Message shown when promo is applied successfully"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="savePromo()">Save Promo Code</button>
            </div>
        </div>
    </div>
</div>';

$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
?>

<script>
// Simple inline JavaScript - no escaping issues
let promoModal;

function createPromo() {
    document.getElementById('promoForm').reset();
    document.getElementById('promoId').value = '';
    document.getElementById('promoModalTitle').textContent = 'Create Promo Code';
    promoModal.show();
}

function editPromo(id) {
    fetch('promo_editor.php?ajax=1&action=get&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const promo = data.promo;
                document.getElementById('promoId').value = promo.id;
                document.getElementById('promoCode').value = promo.code;
                document.getElementById('promoDescription').value = promo.description || '';
                document.getElementById('promoAmount').value = promo.amount || 0;
                document.getElementById('promoLimit').value = promo.limit_count || 0;
                document.getElementById('promoSuccessMessage').value = promo.successmessage || '';
                document.getElementById('promoStatus').value = promo.status || 'active';
                
                // Set dates if they exist
                if (promo.start_dt) {
                    document.getElementById('promoStartDate').value = promo.start_dt.slice(0, 16);
                }
                if (promo.end_dt) {
                    document.getElementById('promoEndDate').value = promo.end_dt.slice(0, 16);
                }
                
                document.getElementById('promoModalTitle').textContent = 'Edit Promo Code';
                promoModal.show();
            }
        });
}

function savePromo() {
    const form = document.getElementById('promoForm');
    const formData = new FormData(form);
    
    fetch('promo_editor.php?ajax=1&action=save', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function deletePromo(id, code) {
    if (confirm('Delete promo code "' + code + '"?')) {
        fetch('promo_editor.php?ajax=1&action=delete&id=' + id, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            }
        });
    }
}

function testPromo() {
    const code = document.getElementById('testPromoCode').value;
    const productId = document.getElementById('testProductId').value;
    
    if (!code || !productId) {
        alert('Please enter a code and select a product');
        return;
    }
    
    fetch('promo_editor.php?ajax=1&action=test&code=' + code + '&product_id=' + productId)
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('testResult');
            if (data.valid) {
                resultDiv.innerHTML = '<div class="alert alert-success">Valid! ' + (data.message || '') + '</div>';
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger">Invalid: ' + (data.message || 'Unknown error') + '</div>';
            }
        });
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    promoModal = new bootstrap.Modal(document.getElementById('promoModal'));
    
    // Handle discount method tabs
    document.querySelectorAll('.discount-method-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.discount-method-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            const method = this.dataset.method;
            document.getElementById('promoDiscountMethod').value = method;
            
            if (method === 'percentage') {
                document.getElementById('discountLabel').textContent = 'Discount Percentage';
                document.getElementById('discountSuffix').textContent = '%';
                document.getElementById('discountHelp').textContent = 'Enter percentage (0-100)';
                document.getElementById('promoAmount').max = 100;
            } else {
                document.getElementById('discountLabel').textContent = 'Discount Amount';
                document.getElementById('discountSuffix').textContent = '¢';
                document.getElementById('discountHelp').textContent = 'Enter amount in cents (e.g., 500 = $5.00)';
                document.getElementById('promoAmount').removeAttribute('max');
            }
        });
    });
    
    // Auto-uppercase code input
    const codeInput = document.getElementById('promoCode');
    if (codeInput) {
        codeInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
});
</script>

<?php
$app->outputpage();
?>