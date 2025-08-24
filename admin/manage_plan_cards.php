<?php
/**
 * Manage Plan Feature Cards
 * 
 * This script helps manage the plan feature cards shown on plan-details.php
 * - Default cards are stored in bg_config with config_type = 'plan_feature_card'
 * - Plan-specific overrides are stored in bg_product_features
 * 
 * Usage:
 * - View all cards: manage_plan_cards.php
 * - Add override: manage_plan_cards.php?action=add_override&product_id=1&card=brands&field=value&value=5%20Brands
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Admin only - site-controller handles authentication
if (!$account->isadmin()) {
    header('Location: /admin');
    exit();
}

$action = $_GET['action'] ?? 'view';
$selected_plan = $_GET['selected_plan'] ?? '';

// Page setup for Birthday Gold template
$pagetitle = "Plan Feature Cards Manager";

// Add custom styles (additionalstyles is a string, use .= to concatenate)
$additionalstyles .= '<style>
.feature-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 2rem;
    height: 100%;
    transition: all 0.3s ease;
    border: 1px solid #e2e8f0;
}
.feature-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-color: #cbd5e0;
}
.feature-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin-bottom: 1.5rem;
}
.feature-icon.primary { background: #2d3748; color: #FFD700; }
.feature-icon.success { background: #1a1a1a; color: #48bb78; }
.feature-icon.info { background: #2d3748; color: #4299e1; }
.feature-icon.warning { background: #1a1a1a; color: #FFD700; }
.feature-icon.danger { background: #2d3748; color: #f56565; }
.feature-icon.dark { background: #1a1a1a; color: #a0aec0; }
.feature-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #2d3748;
}
.feature-value {
    font-size: 1.75rem;
    font-weight: 800;
    color: #1a1a1a;
    margin-bottom: 1rem;
}
.feature-description {
    color: #718096;
    line-height: 1.6;
    font-size: 0.95rem;
}
.override-cell:hover {
    background-color: #f0f0f0 !important;
    transition: background-color 0.2s;
}
.override-cell {
    transition: background-color 0.2s;
}
.override-cell.selected {
    background-color: #ffc107 !important;
    border: 2px solid #ff9800;
}
</style>';

// Handle actions
if ($action == 'add_override' && isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $card_key = $_GET['card'] ?? '';
    $field = $_GET['field'] ?? '';
    $value = $_GET['value'] ?? '';
    
    if ($card_key && $field) {
        $name = 'card_' . $card_key . '_' . $field;
        
        // Get product name for display
        $product_sql = "SELECT account_name, account_plan FROM bg_products WHERE id = :product_id";
        $product_stmt = $database->prepare($product_sql);
        $product_stmt->execute(['product_id' => $product_id]);
        $product_info = $product_stmt->fetch(PDO::FETCH_ASSOC);
        $product_display = htmlspecialchars($product_info['account_name'] ?? $product_info['account_plan'] ?? 'Unknown') . ' (ID: ' . $product_id . ')';
        
        // Check if this is a reset to default request
        if ($value === '__RESET_TO_DEFAULT__') {
            // Delete the override to reset to default
            $sql = "DELETE FROM bg_product_features WHERE product_id = :product_id AND name = :name";
            $stmt = $database->prepare($sql);
            $stmt->execute(['product_id' => $product_id, 'name' => $name]);
            $success_message = '<div class="alert alert-info">Reset to default for <strong>' . $product_display . '</strong>: ' . htmlspecialchars($card_key) . ' ' . htmlspecialchars($field) . '</div>';
        } else {
            // Check if override exists
            $sql = "SELECT id FROM bg_product_features WHERE product_id = :product_id AND name = :name";
            $stmt = $database->prepare($sql);
            $stmt->execute(['product_id' => $product_id, 'name' => $name]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing
                $sql = "UPDATE bg_product_features SET value = :value, modify_dt = NOW() WHERE id = :id";
                $stmt = $database->prepare($sql);
                $stmt->execute(['value' => $value, 'id' => $existing['id']]);
                $success_message = '<div class="alert alert-success">Updated override for <strong>' . $product_display . '</strong>: ' . htmlspecialchars($card_key) . ' ' . htmlspecialchars($field) . ' = "' . htmlspecialchars($value) . '"</div>';
            } else {
                // Insert new
                $sql = "INSERT INTO bg_product_features (product_id, name, value, status, create_dt) VALUES (:product_id, :name, :value, 'active', NOW())";
                $stmt = $database->prepare($sql);
                $stmt->execute(['product_id' => $product_id, 'name' => $name, 'value' => $value]);
                $success_message = '<div class="alert alert-success">Added override for <strong>' . $product_display . '</strong>: ' . htmlspecialchars($card_key) . ' ' . htmlspecialchars($field) . ' = "' . htmlspecialchars($value) . '"</div>';
            }
        }
    }
}

// Handle create/edit card action
if (($action == 'create_card' || $action == 'edit_card') && isset($_POST['card_key'])) {
    $card_key = $_POST['card_key'];
    $title = $_POST['title'] ?? '';
    $value = $_POST['value'] ?? '';
    $description = $_POST['description'] ?? '';
    $icon = $_POST['icon'] ?? 'bi-star';
    $icon_color = $_POST['icon_color'] ?? 'primary';
    $display_order = intval($_POST['display_order'] ?? 99);
    $plans = $_POST['plans'] ?? [];
    
    $config_data = [
        'title' => $title,
        'value' => $value,
        'description' => $description,
        'icon' => $icon,
        'icon_color' => $icon_color
    ];
    
    if (!empty($plans)) {
        $config_data['plans'] = $plans;
    }
    
    $sql = "INSERT INTO bg_config (config_type, config_key, config_value, config_data, display_order, status) 
            VALUES ('plan_feature_card', :key, :value, :data, :order, 'active')
            ON DUPLICATE KEY UPDATE 
            config_value = VALUES(config_value),
            config_data = VALUES(config_data),
            display_order = VALUES(display_order)";
    
    $stmt = $database->prepare($sql);
    $stmt->execute([
        'key' => $card_key,
        'value' => $title,
        'data' => json_encode($config_data),
        'order' => $display_order
    ]);
    
    $message = ($action == 'create_card') ? 'Card created successfully!' : 'Card updated successfully!';
    $success_message = '<div class="alert alert-success">' . $message . '</div>';
}

// Handle delete card action
if ($action == 'delete_card' && isset($_GET['card_key'])) {
    $card_key = $_GET['card_key'];
    
    $sql = "UPDATE bg_config SET status = 'inactive' WHERE config_type = 'plan_feature_card' AND config_key = :key";
    $stmt = $database->prepare($sql);
    $stmt->execute(['key' => $card_key]);
    
    $success_message = '<div class="alert alert-warning">Card "' . htmlspecialchars($card_key) . '" has been deactivated.</div>';
}

// Include Birthday Gold header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Admin content header
echo '<div class="content-header-admin">';
echo '<div class="container">';
echo '<h1>Plan Feature Cards Manager</h1>';
echo '<p class="lead">Manage default cards and plan-specific overrides for the plan details page</p>';
echo '</div>';
echo '</div>';

echo '<div class="container mt-4">';

// Display any success/error messages
if (isset($success_message)) {
    echo $success_message;
}

// Get all products first (needed for dropdown and overrides)
$sql = "SELECT id, account_name, account_plan, account_type, version FROM bg_products WHERE status = 'active' ORDER BY id";
$products = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Add Create New Card button and plan filter dropdown as the title
echo '<div class="d-flex justify-content-between align-items-center mt-4 mb-3">';
echo '<div class="d-flex align-items-center">';
echo '<select id="planFilter" class="form-select form-select-lg me-3" style="min-width: 350px;" onchange="filterCardsByPlan(this.value)">';
echo '<option value="default"' . ($selected_plan === '' || $selected_plan === 'default' ? ' selected' : '') . '>Default Cards (from bg_config)</option>';
echo '<option value="" disabled>──────────────────────</option>';
echo '<optgroup label="User/Individual Plans">';
foreach ($products as $product) {
    if (strtolower($product['account_type'] ?? '') == 'user') {
        $display_name = !empty($product['account_name']) ? htmlspecialchars($product['account_name']) : strtoupper($product['account_plan'] ?? 'UNNAMED');
        $version = htmlspecialchars($product['version'] ?? 'N/A');
        $plan_type = htmlspecialchars($product['account_plan'] ?? '');
        $selected = ($selected_plan == $product['id']) ? ' selected' : '';
        echo '<option value="' . $product['id'] . '"' . $selected . '>' . $display_name . ' (' . $version . ' - ID: ' . $product['id'] . ')</option>';
    }
}
echo '</optgroup>';
echo '<optgroup label="Parental Plans">';
foreach ($products as $product) {
    if (strtolower($product['account_type'] ?? '') == 'parental') {
        $display_name = !empty($product['account_name']) ? htmlspecialchars($product['account_name']) : strtoupper($product['account_plan'] ?? 'UNNAMED') . ' (Parental)';
        $version = htmlspecialchars($product['version'] ?? 'N/A');
        $plan_type = htmlspecialchars($product['account_plan'] ?? '');
        $selected = ($selected_plan == $product['id']) ? ' selected' : '';
        echo '<option value="' . $product['id'] . '"' . $selected . '>' . $display_name . ' (' . $version . ' - ID: ' . $product['id'] . ')</option>';
    }
}
echo '</optgroup>';
echo '<optgroup label="Business Plans">';
foreach ($products as $product) {
    if (strtolower($product['account_type'] ?? '') == 'business') {
        $display_name = !empty($product['account_name']) ? htmlspecialchars($product['account_name']) : strtoupper($product['account_plan'] ?? 'UNNAMED') . ' (Business)';
        $version = htmlspecialchars($product['version'] ?? 'N/A');
        $plan_type = htmlspecialchars($product['account_plan'] ?? '');
        $selected = ($selected_plan == $product['id']) ? ' selected' : '';
        echo '<option value="' . $product['id'] . '"' . $selected . '>' . $display_name . ' (' . $version . ' - ID: ' . $product['id'] . ')</option>';
    }
}
echo '</optgroup>';
echo '</select>';
echo '<span class="text-muted">Select a plan to see cards with overrides applied</span>';
echo '</div>';
echo '<div>';
echo '<a href="/admin/manage_plan_cards.php" class="btn btn-outline-secondary me-2" title="Reset to default view">';
echo '<i class="bi bi-arrow-clockwise me-2"></i>Reset View</a>';
echo '<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createCardModal">';
echo '<i class="bi bi-plus-circle me-2"></i>Create New Card</button>';
echo '</div>';
echo '</div>';

$sql = "SELECT config_key, config_data, display_order FROM bg_config WHERE config_type = 'plan_feature_card' AND status = 'active' ORDER BY display_order";
$cards = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Get all overrides for JavaScript
$all_overrides = [];
foreach ($products as $product) {
    $sql = "SELECT name, value FROM bg_product_features WHERE product_id = :product_id AND name LIKE 'card_%' AND status = 'active'";
    $stmt = $database->prepare($sql);
    $stmt->execute(['product_id' => $product['id']]);
    $overrides = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $all_overrides[$product['id']] = $overrides;
}

echo '<div class="row g-4" id="cardsContainer">';
foreach ($cards as $index => $card) {
    $data = json_decode($card['config_data'], true);
    echo '<div class="col-lg-4 col-md-6">';
    echo '<div class="feature-card ">';
    
    // Header row with Key, Order, and action buttons all on same line
    echo '<div class="d-flex justify-content-between align-items-center pb-2 border-bottom mb-3">';
    echo '<div>';
    echo '<span class="badge bg-info me-2">Key: ' . htmlspecialchars($card['config_key']) . '</span>';
    echo '<span class="badge bg-secondary">Order: ' . $card['display_order'] . '</span>';
    echo '</div>';
    echo '<div>';
    echo '<button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editCard(' . htmlspecialchars(json_encode($card['config_key'])) . ', ' . htmlspecialchars(json_encode($data)) . ', ' . $card['display_order'] . ')" title="Edit">';
    echo '<i class="bi bi-pencil"></i></button>';
    echo '<a href="?action=delete_card&card_key=' . urlencode($card['config_key']) . '" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Are you sure you want to deactivate this card?\')" title="Delete">';
    echo '<i class="bi bi-trash"></i></a>';
    echo '</div>';
    echo '</div>';
    
    // Feature icon
    echo '<div class="feature-icon ' . htmlspecialchars($data['icon_color'] ?? 'primary') . '">';
    $iconClass = htmlspecialchars($data['icon'] ?? 'bi-star');
    // Ensure the bi class is present for Bootstrap Icons
    if (strpos($iconClass, 'bi-') === 0 && strpos($iconClass, 'bi ') !== 0) {
        $iconClass = 'bi ' . $iconClass;
    }
    echo '<i class="' . $iconClass . '"></i>';
    echo '</div>';
    
    // Title and value
    echo '<h3 class="feature-title">' . htmlspecialchars($data['title'] ?? '') . '</h3>';
    echo '<div class="feature-value">' . htmlspecialchars($data['value'] ?? '') . '</div>';
    echo '<p class="feature-description">' . ($data['description'] ?? '') . '</p>';
    
    // Show plan restrictions if any
    if (isset($data['plans']) && is_array($data['plans'])) {
        echo '<div class="alert alert-warning py-1 px-2 small mt-3">';
        echo '<strong>Limited to:</strong> ' . implode(', ', $data['plans']);
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
}
echo '</div>';

// Display plan overrides
echo '<h2 class="mt-4">Plan-Specific Overrides</h2>';
echo '<p>These override the default card values for specific plans.</p>';

echo '<div class="table-responsive bg-white rounded shadow-sm p-3">';
echo '<table class="table table-bordered">';
echo '<thead><tr><th>Product</th>';
foreach ($cards as $card) {
    echo '<th>' . htmlspecialchars($card['config_key']) . '</th>';
}
echo '</tr></thead><tbody>';

foreach ($products as $product) {
    echo '<tr>';
    $version = htmlspecialchars($product['version'] ?? 'N/A');
    $plan_type = htmlspecialchars($product['account_plan'] ?? '');
    $display_name = !empty($product['account_name']) ? htmlspecialchars($product['account_name']) : strtoupper($product['account_plan'] ?? 'UNNAMED');
    echo '<td><strong>' . $display_name . '</strong><br><small>' . $version . ' - ' . $plan_type . '<br>ID: ' . $product['id'] . '</small></td>';
    
    // Get product account type - we already have it from the main query
    $account_type = strtolower($product['account_type'] ?? 'user');
    $plan_name = strtolower($product['account_plan'] ?? '');
    
    // Get overrides for this product
    $sql = "SELECT name, value FROM bg_product_features WHERE product_id = :product_id AND name LIKE 'card_%' AND status = 'active'";
    $stmt = $database->prepare($sql);
    $stmt->execute(['product_id' => $product['id']]);
    $overrides = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    foreach ($cards as $card) {
        // Add clickable cell with data attributes
        echo '<td class="small override-cell" style="cursor: pointer;" ';
        echo 'data-product-id="' . $product['id'] . '" ';
        echo 'data-card-key="' . htmlspecialchars($card['config_key']) . '" ';
        echo 'onclick="populateOverrideForm(' . $product['id'] . ', \'' . htmlspecialchars($card['config_key']) . '\', event)">';
        
        // Check if this card applies to this plan
        $card_data = json_decode($card['config_data'], true);
        $card_applies = true;
        
        if (isset($card_data['plans']) && is_array($card_data['plans'])) {
            // This card has a plan whitelist
            $card_applies = false;
            foreach ($card_data['plans'] as $allowed_plan) {
                if (stripos($allowed_plan, $account_type) !== false || 
                    stripos($plan_name, $allowed_plan) !== false) {
                    $card_applies = true;
                    break;
                }
            }
        }
        
        if (!$card_applies) {
            // Card does not apply to this plan type
            echo '<span class="text-muted">-</span>';
        } else {
            // Card applies, check for overrides
            $has_override = false;
            
            // Check for overrides for this card
            $prefix = 'card_' . $card['config_key'] . '_';
            
            // First check if card is hidden for this plan
            if (isset($overrides[$prefix . 'status']) && $overrides[$prefix . 'status'] === 'hidden') {
                echo '<div class="text-danger"><i class="bi bi-eye-slash"></i> <strong>HIDDEN</strong></div>';
                echo '<a href="?action=add_override&product_id=' . $product['id'] . '&card=' . $card['config_key'] . '&field=status&value=__RESET_TO_DEFAULT__&selected_plan=' . $selected_plan . '" ';
                echo 'class="text-success small" title="Unhide this card" onclick="return confirm(\'Unhide this card for this plan?\')">Show Card</a>';
                $has_override = true;
            } else {
                // Show other overrides
                foreach (['value', 'title', 'description', 'icon', 'icon_color', 'status'] as $field) {
                if (isset($overrides[$prefix . $field])) {
                    echo '<div class="mb-1 d-flex justify-content-between align-items-center" data-field="' . $field . '" data-value="' . htmlspecialchars($overrides[$prefix . $field]) . '">';
                    echo '<div>';
                    echo '<span class="badge bg-warning text-dark">' . $field . ':</span> ';
                    echo htmlspecialchars(substr($overrides[$prefix . $field], 0, 40));
                    if (strlen($overrides[$prefix . $field]) > 40) echo '...';
                    echo '</div>';
                    echo '<a href="?action=add_override&product_id=' . $product['id'] . '&card=' . $card['config_key'] . '&field=' . $field . '&value=__RESET_TO_DEFAULT__&selected_plan=' . $selected_plan . '" ';
                    echo 'class="text-danger" style="text-decoration: none; font-weight: bold; font-size: 18px;" title="Reset to default" onclick="return confirm(\'Reset this override to default?\')">×</a>';
                    echo '</div>';
                    $has_override = true;
                }
            }
            }
            
            if (!$has_override) {
                echo '<span class="text-muted">Using defaults</span>';
            }
        }
        
        echo '</td>';
    }
    echo '</tr>';
}
echo '</tbody></table>';
echo '</div>';

// Quick add form
echo '<h2 class="mt-4">Quick Add Override</h2>';
echo '<form method="get" class="row g-3" id="quickAddForm">';
echo '<input type="hidden" name="action" value="add_override">';
echo '<input type="hidden" name="selected_plan" id="selected_plan_input" value="">';

echo '<div class="col-md-2">';
echo '<label class="form-label">Product</label>';
echo '<select name="product_id" class="form-select" required>';
echo '<option value="">Select...</option>';
foreach ($products as $product) {
    $display_name = !empty($product['account_name']) ? htmlspecialchars($product['account_name']) : strtoupper($product['account_plan'] ?? 'UNNAMED');
    $plan_type = htmlspecialchars($product['account_plan'] ?? '');
    $account_type = htmlspecialchars($product['account_type'] ?? '');
    $version = htmlspecialchars($product['version'] ?? 'N/A');
    echo '<option value="' . $product['id'] . '">' . $display_name . ' (' . $version . ' - ' . $account_type . ' - ID: ' . $product['id'] . ')</option>';
}
echo '</select>';
echo '</div>';

echo '<div class="col-md-2">';
echo '<label class="form-label">Card</label>';
echo '<select name="card" class="form-select" required>';
echo '<option value="">Select...</option>';
foreach ($cards as $card) {
    echo '<option value="' . htmlspecialchars($card['config_key']) . '">' . htmlspecialchars($card['config_key']) . '</option>';
}
echo '</select>';
echo '</div>';

echo '<div class="col-md-2">';
echo '<label class="form-label">Field</label>';
echo '<select name="field" class="form-select" required>';
echo '<option value="">Select...</option>';
echo '<option value="title">Title</option>';
echo '<option value="value">Value</option>';
echo '<option value="description">Description</option>';
echo '<option value="icon">Icon</option>';
echo '<option value="icon_color">Icon Color</option>';
echo '<option value="status">Status (active/hidden)</option>';
echo '</select>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<label class="form-label">New Value <small id="html_note" class="text-muted" style="display:none;">(HTML allowed)</small></label>';
echo '<input type="text" name="value" class="form-control" id="override_value" placeholder="For status: hidden or active" required>';
echo '<small id="html_help" class="form-text text-muted" style="display:none;">HTML allowed for links, e.g., &lt;a href="/myaccount/upgrade"&gt;Upgrade Now&lt;/a&gt;</small>';
echo '</div>';

echo '<div class="col-md-2">';
echo '<label class="form-label">&nbsp;</label>';
echo '<button type="submit" class="btn btn-primary d-block w-100">Add Override</button>';
echo '</div>';

echo '<div class="col-md-1">';
echo '<label class="form-label">&nbsp;</label>';
echo '<button type="button" class="btn btn-warning d-block w-100" onclick="resetToDefault()" title="Reset selected field to default value">Reset</button>';
echo '</div>';

echo '</form>';

// Instructions
echo '<div class="alert alert-info mt-4">';
echo '<h5>How it works:</h5>';
echo '<ul>';
echo '<li>Default cards are defined in bg_config and apply to all plans</li>';
echo '<li>Plan-specific overrides in bg_product_features override any field of any card</li>';
echo '<li>Override naming format: card_{card_key}_{field} (e.g., card_brands_value)</li>';
echo '<li>Common overrides: Free plans limit brands, Premium plans upgrade support</li>';
echo '</ul>';
echo '</div>';

// Create Card Modal
echo '
<!-- Create Card Modal -->
<div class="modal fade" id="createCardModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Feature Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="?action=create_card">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Card Key (unique identifier)</label>
                                <input type="text" name="card_key" class="form-control" required 
                                    pattern="[a-z_]+" title="Lowercase letters and underscores only"
                                    placeholder="e.g., analytics, api_access">
                                <small class="text-muted">Lowercase, no spaces, underscores allowed</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Display Order</label>
                                <input type="number" name="display_order" class="form-control" value="99" min="1" max="999">
                                <small class="text-muted">Lower numbers appear first</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g., Advanced Analytics">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Value (short display value)</label>
                        <input type="text" name="value" class="form-control" required placeholder="e.g., Real-time">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description <small class="text-muted">(HTML allowed)</small></label>
                        <textarea name="description" class="form-control" rows="3" required 
                            placeholder="Detailed description of what this feature provides..."></textarea>
                        <small class="form-text text-muted">You can use HTML for links, e.g., &lt;a href="/myaccount/upgrade"&gt;Upgrade Now&lt;/a&gt;</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Icon (Bootstrap Icons class)</label>
                                <input type="text" name="icon" class="form-control" value="bi-star" 
                                    placeholder="e.g., bi-graph-up, bi-shield-check">
                                <small class="text-muted"><a href="https://icons.getbootstrap.com/" target="_blank">Browse icons</a></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Icon Color</label>
                                <select name="icon_color" class="form-select">
                                    <option value="primary">Primary (Blue/Gold)</option>
                                    <option value="success">Success (Green)</option>
                                    <option value="warning">Warning (Yellow)</option>
                                    <option value="danger">Danger (Red)</option>
                                    <option value="info">Info (Light Blue)</option>
                                    <option value="dark">Dark (Gray)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Plan Restrictions (optional)</label>
                        <div class="form-text mb-2">Leave unchecked to show on all plans, or select specific plan types:</div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="plans[]" value="individual" id="plan_individual">
                                    <label class="form-check-label" for="plan_individual">Individual Plans</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="plans[]" value="family" id="plan_family">
                                    <label class="form-check-label" for="plan_family">Family Plans</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="plans[]" value="business" id="plan_business">
                                    <label class="form-check-label" for="plan_business">Business Plans</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="plans[]" value="gift" id="plan_gift">
                                    <label class="form-check-label" for="plan_gift">Gift Plans</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="plans[]" value="parental" id="plan_parental">
                                    <label class="form-check-label" for="plan_parental">Parental Plans</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="plans[]" value="lifetime" id="plan_lifetime">
                                    <label class="form-check-label" for="plan_lifetime">Lifetime Plans</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Card</button>
                </div>
            </form>
        </div>
    </div>
</div>';

// Edit Card Modal (similar to create but for editing)
echo '
<!-- Edit Card Modal -->
<div class="modal fade" id="editCardModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Feature Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="?action=edit_card">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Card Key (cannot be changed)</label>
                                <input type="text" name="card_key" id="edit_card_key" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Display Order</label>
                                <input type="number" name="display_order" id="edit_display_order" class="form-control" min="1" max="999">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Value (short display value)</label>
                        <input type="text" name="value" id="edit_value" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description <small class="text-muted">(HTML allowed)</small></label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
                        <small class="form-text text-muted">You can use HTML for links, e.g., &lt;a href="/myaccount/upgrade"&gt;Upgrade Now&lt;/a&gt;</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Icon (Bootstrap Icons class)</label>
                                <input type="text" name="icon" id="edit_icon" class="form-control">
                                <small class="text-muted"><a href="https://icons.getbootstrap.com/" target="_blank">Browse icons</a></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Icon Color</label>
                                <select name="icon_color" id="edit_icon_color" class="form-select">
                                    <option value="primary">Primary (Blue/Gold)</option>
                                    <option value="success">Success (Green)</option>
                                    <option value="warning">Warning (Yellow)</option>
                                    <option value="danger">Danger (Red)</option>
                                    <option value="info">Info (Light Blue)</option>
                                    <option value="dark">Dark (Gray)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Plan Restrictions (optional)</label>
                        <div class="form-text mb-2">Leave unchecked to show on all plans, or select specific plan types:</div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input edit-plan-check" type="checkbox" name="plans[]" value="individual" id="edit_plan_individual">
                                    <label class="form-check-label" for="edit_plan_individual">Individual Plans</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input edit-plan-check" type="checkbox" name="plans[]" value="family" id="edit_plan_family">
                                    <label class="form-check-label" for="edit_plan_family">Family Plans</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input edit-plan-check" type="checkbox" name="plans[]" value="business" id="edit_plan_business">
                                    <label class="form-check-label" for="edit_plan_business">Business Plans</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input edit-plan-check" type="checkbox" name="plans[]" value="gift" id="edit_plan_gift">
                                    <label class="form-check-label" for="edit_plan_gift">Gift Plans</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input edit-plan-check" type="checkbox" name="plans[]" value="parental" id="edit_plan_parental">
                                    <label class="form-check-label" for="edit_plan_parental">Parental Plans</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input edit-plan-check" type="checkbox" name="plans[]" value="lifetime" id="edit_plan_lifetime">
                                    <label class="form-check-label" for="edit_plan_lifetime">Lifetime Plans</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Card</button>
                </div>
            </form>
        </div>
    </div>
</div>';

// Bootstrap JS is already included in bg_pagestart.inc

// Add JavaScript for edit functionality
echo '
<script>
function editCard(cardKey, data, displayOrder) {
    // Set form values
    document.getElementById("edit_card_key").value = cardKey;
    document.getElementById("edit_display_order").value = displayOrder;
    document.getElementById("edit_title").value = data.title || "";
    document.getElementById("edit_value").value = data.value || "";
    document.getElementById("edit_description").value = data.description || "";
    document.getElementById("edit_icon").value = data.icon || "bi-star";
    document.getElementById("edit_icon_color").value = data.icon_color || "primary";
    
    // Clear all plan checkboxes first
    document.querySelectorAll(".edit-plan-check").forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Check appropriate plan checkboxes
    if (data.plans && Array.isArray(data.plans)) {
        data.plans.forEach(plan => {
            const checkbox = document.getElementById("edit_plan_" + plan);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    }
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById("editCardModal"));
    modal.show();
}
</script>
';

// Pass PHP data to JavaScript
echo '<script>
var allOverrides = ' . json_encode($all_overrides) . ';
var defaultCards = ' . json_encode($cards) . ';
var products = ' . json_encode($products) . ';

// Function to filter cards by selected plan
function filterCardsByPlan(productId) {
    // Update the hidden field for form submission
    const hiddenInput = document.getElementById(\'selected_plan_input\');
    if (hiddenInput) {
        hiddenInput.value = productId;
    }
    
    const container = document.getElementById(\'cardsContainer\');
    if (!container) {
        console.error(\'cardsContainer not found\');
        return;
    }
    
    // Clear the container
    container.innerHTML = \'\';
    
    // If default selected, show original cards
    if (productId === \'default\' || !productId) {
        defaultCards.forEach((card, index) => {
            const data = typeof card.config_data === \'string\' ? JSON.parse(card.config_data) : card.config_data;
            const cardHtml = createCardHtml(card.config_key, data, card.display_order);
            container.innerHTML += cardHtml;
        });
    } else {
        // Show cards with overrides applied for the selected plan
        const overrides = allOverrides[productId] || {};
        
        // Find the selected product to get its plan type
        const selectedProduct = products.find(p => p.id == productId);
        if (!selectedProduct) {
            console.error(\'Product not found:\', productId);
            return;
        }
        
        // Get the plan type (account_type) and plan name
        const planType = (selectedProduct.account_type || \'user\').toLowerCase();
        const planName = (selectedProduct.account_plan || \'\').toLowerCase();
        
        defaultCards.forEach((card, index) => {
            const data = typeof card.config_data === \'string\' ? JSON.parse(card.config_data) : card.config_data;
            const cardKey = card.config_key;
            
            // Check if card is hidden for this plan
            const overrideKey = \'card_\' + cardKey;
            if (overrides[overrideKey + \'_status\'] === \'hidden\') {
                return; // Skip this card - it is hidden for this plan
            }
            
            // Check if this card has plan restrictions
            if (data.plans && Array.isArray(data.plans) && data.plans.length > 0) {
                // Card has whitelist - check if current plan type is allowed
                const allowedPlans = data.plans.map(p => p.toLowerCase());
                
                // Check various plan type mappings
                let isPlanAllowed = false;
                
                // Check direct plan type match
                if (allowedPlans.includes(planType)) {
                    isPlanAllowed = true;
                }
                // Check if user type should match individual
                else if (planType === \'user\' && allowedPlans.includes(\'individual\')) {
                    isPlanAllowed = true;
                }
                // Check if individual should match user
                else if (planType === \'individual\' && allowedPlans.includes(\'user\')) {
                    isPlanAllowed = true;
                }
                // Check specific plan names (like lifetime, gift, etc.)
                else if (allowedPlans.includes(planName)) {
                    isPlanAllowed = true;
                }
                // Check for family/parental equivalence
                else if ((planType === \'parental\' && allowedPlans.includes(\'family\')) ||
                         (planType === \'family\' && allowedPlans.includes(\'parental\'))) {
                    isPlanAllowed = true;
                }
                
                // Skip this card if plan is not allowed
                if (!isPlanAllowed) {
                    return; // Continue to next card
                }
            }
            // If no plan restrictions, card shows for all plans
            
            // Clone the data to avoid modifying original
            const overriddenData = {...data};
            
            // Apply overrides if they exist (except status which is for control)
            if (overrides[overrideKey + \'_value\']) {
                overriddenData.value = overrides[overrideKey + \'_value\'];
            }
            if (overrides[overrideKey + \'_title\']) {
                overriddenData.title = overrides[overrideKey + \'_title\'];
            }
            if (overrides[overrideKey + \'_description\']) {
                overriddenData.description = overrides[overrideKey + \'_description\'];
            }
            if (overrides[overrideKey + \'_icon\']) {
                overriddenData.icon = overrides[overrideKey + \'_icon\'];
            }
            if (overrides[overrideKey + \'_icon_color\']) {
                overriddenData.icon_color = overrides[overrideKey + \'_icon_color\'];
            }
            
            // Create the card HTML
            let cardHtml = createCardHtml(cardKey, overriddenData, card.display_order);
            
            // Add override indicator if this card has overrides
            if (Object.keys(overrides).some(key => key.startsWith(overrideKey))) {
                // Replace the feature-card class to add border warning
                cardHtml = cardHtml.replace(\'<div class="feature-card "\', \'<div class="feature-card border border-warning border-2"\');
                // Add warning text before closing divs
                cardHtml = cardHtml.replace(\'</div></div>\', \'<div class="text-warning small mt-2"><i class="bi bi-exclamation-triangle"></i> Overridden for this plan</div></div></div>\');
            }
            container.innerHTML += cardHtml;
        });
    }
}

// Helper function to create card HTML
function createCardHtml(cardKey, data, displayOrder) {
    let iconClass = data.icon || \'bi-star\';
    // Ensure the bi class is present for Bootstrap Icons
    if (iconClass.startsWith(\'bi-\') && !iconClass.startsWith(\'bi \')) {
        iconClass = \'bi \' + iconClass;
    }
    const iconColor = data.icon_color || \'primary\';
    
    let html = \'<div class="col-lg-4 col-md-6">\';
    html += \'<div class="feature-card ">\';
    
    // Header row with Key, Order, and action buttons all on same line
    html += \'<div class="d-flex justify-content-between align-items-center pb-2 border-bottom mb-3">\';
    html += \'<div>\';
    html += \'<span class="badge bg-info me-2">Key: \' + cardKey + \'</span>\';
    html += \'<span class="badge bg-secondary">Order: \' + displayOrder + \'</span>\';
    html += \'</div>\';
    html += \'<div>\';
    html += \'<button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editCard(\\\'\' + cardKey + \'\\\', \' + JSON.stringify(data).replace(/"/g, \'&quot;\') + \', \' + displayOrder + \')" title="Edit">\';
    html += \'<i class="bi bi-pencil"></i></button>\';
    html += \'<a href="?action=delete_card&card_key=\' + cardKey + \'" class="btn btn-sm btn-outline-danger" onclick="return confirm(\\\'Are you sure you want to deactivate this card?\\\')" title="Delete">\';
    html += \'<i class="bi bi-trash"></i></a>\';
    html += \'</div>\';
    html += \'</div>\';
    
    // Feature icon
    html += \'<div class="feature-icon \' + iconColor + \'">\';
    html += \'<i class="\' + iconClass + \'"></i>\';
    html += \'</div>\';
    
    // Title and value
    html += \'<h3 class="feature-title">\' + (data.title || \'\') + \'</h3>\';
    html += \'<div class="feature-value">\' + (data.value || \'\') + \'</div>\';
    html += \'<p class="feature-description">\' + (data.description || \'\') + \'</p>\';
    
    // Show plan restrictions if any
    if (data.plans && Array.isArray(data.plans)) {
        html += \'<div class="alert alert-warning py-1 px-2 small mt-3">\';
        html += \'<strong>Limited to:</strong> \' + data.plans.join(\', \');
        html += \'</div>\';
    }
    
    html += \'</div>\';
    html += \'</div>\';
    
    return html;
}

// Show/hide HTML note based on field selection
document.querySelector(\'select[name="field"]\').addEventListener(\'change\', function() {
    const htmlNote = document.getElementById(\'html_note\');
    const htmlHelp = document.getElementById(\'html_help\');
    if (this.value === \'description\') {
        htmlNote.style.display = \'inline\';
        htmlHelp.style.display = \'block\';
    } else {
        htmlNote.style.display = \'none\';
        htmlHelp.style.display = \'none\';
    }
});

// Function to populate Quick Add Override form when clicking on table cells
function populateOverrideForm(productId, cardKey, event) {
    // Remove any previously selected cells
    document.querySelectorAll(\'.override-cell.selected\').forEach(cell => {
        cell.classList.remove(\'selected\');
    });
    
    // Highlight the clicked cell
    event.target.closest(\'td\').classList.add(\'selected\');
    
    // Set the product dropdown
    const productSelect = document.querySelector(\'select[name="product_id"]\');
    if (productSelect) {
        productSelect.value = productId;
    }
    
    // Set the card dropdown
    const cardSelect = document.querySelector(\'select[name="card"]\');
    if (cardSelect) {
        cardSelect.value = cardKey;
    }
    
    // Scroll to the Quick Add Override form
    const formSection = document.querySelector(\'h2:contains("Quick Add Override")\') || 
                       Array.from(document.querySelectorAll(\'h2\')).find(h => h.textContent.includes(\'Quick Add Override\'));
    if (formSection) {
        formSection.scrollIntoView({ behavior: \'smooth\', block: \'start\' });
    }
    
    // Highlight the form briefly to show it was updated
    const form = document.querySelector(\'form[method="get"]\');
    if (form) {
        form.style.backgroundColor = \'#ffffcc\';
        setTimeout(() => {
            form.style.transition = \'background-color 0.5s\';
            form.style.backgroundColor = \'\';
        }, 100);
    }
    
    // Focus on the field dropdown
    const fieldSelect = document.querySelector(\'select[name="field"]\');
    if (fieldSelect) {
        fieldSelect.focus();
    }
}

// Function to reset a field to default value
function resetToDefault() {
    const form = document.getElementById(\'quickAddForm\');
    const productSelect = form.querySelector(\'select[name="product_id"]\');
    const cardSelect = form.querySelector(\'select[name="card"]\');
    const fieldSelect = form.querySelector(\'select[name="field"]\');
    
    if (!productSelect.value) {
        alert(\'Please select a Product first\');
        return;
    }
    if (!cardSelect.value) {
        alert(\'Please select a Card first\');
        return;
    }
    if (!fieldSelect.value) {
        alert(\'Please select a Field first\');
        return;
    }
    
    if (confirm(\'Reset \' + cardSelect.value + \' \' + fieldSelect.value + \' to default for this product?\')) {
        // Set the special reset value
        const valueInput = document.getElementById(\'override_value\');
        valueInput.value = \'__RESET_TO_DEFAULT__\';
        valueInput.required = false; // Temporarily remove required
        
        // Submit the form
        form.submit();
    }
}

// On page load, trigger the filter if there is a selected plan
document.addEventListener(\'DOMContentLoaded\', function() {
    const planFilter = document.getElementById(\'planFilter\');
    if (planFilter && planFilter.value && planFilter.value !== \'default\') {
        filterCardsByPlan(planFilter.value);
    }
});
</script>';

echo '</div>'; // Close container

// Include Birthday Gold footer
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>