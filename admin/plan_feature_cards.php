<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Plan Feature Cards Manager";
$message = '';
$error = '';

// Handle form submissions
if ($app->formposted()) {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update_default') {
        // Update default card
        $card_key = $_POST['card_key'] ?? '';
        $card_data = [
            'icon' => $_POST['icon'] ?? '',
            'color' => $_POST['color'] ?? 'primary',
            'title' => $_POST['title'] ?? '',
            'value' => $_POST['value'] ?? '',
            'description' => $_POST['description'] ?? '',
            'plans' => array_filter($_POST['plans'] ?? [])
        ];
        
        if ($card_key) {
            $sql = "UPDATE bg_config SET 
                    config_value = :value,
                    config_data = :data,
                    modify_dt = NOW()
                    WHERE config_type = 'plan_feature_card' 
                    AND config_key = :key";
            
            $stmt = $database->prepare($sql);
            $result = $stmt->execute([
                'value' => $card_data['title'],
                'data' => json_encode($card_data),
                'key' => $card_key
            ]);
            
            if ($result) {
                $message = "Successfully updated card: " . htmlspecialchars($card_key);
            } else {
                $error = "Failed to update card";
            }
        }
    } elseif ($action == 'add_override') {
        // Add/update plan-specific override
        $product_id = intval($_POST['product_id'] ?? 0);
        $card_key = $_POST['card_key'] ?? '';
        $field = $_POST['field'] ?? '';
        $value = $_POST['override_value'] ?? '';
        
        if ($product_id && $card_key && $field) {
            $name = 'card_' . $card_key . '_' . $field;
            
            // Check if override exists
            $sql = "SELECT id FROM bg_product_features WHERE product_id = :product_id AND name = :name";
            $stmt = $database->prepare($sql);
            $stmt->execute(['product_id' => $product_id, 'name' => $name]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing
                $sql = "UPDATE bg_product_features SET value = :value, modify_dt = NOW() WHERE id = :id";
                $stmt = $database->prepare($sql);
                $result = $stmt->execute(['value' => $value, 'id' => $existing['id']]);
            } else {
                // Insert new
                $sql = "INSERT INTO bg_product_features (product_id, name, value, status, create_dt) 
                        VALUES (:product_id, :name, :value, 'active', NOW())";
                $stmt = $database->prepare($sql);
                $result = $stmt->execute([
                    'product_id' => $product_id,
                    'name' => $name,
                    'value' => $value
                ]);
            }
            
            if ($result) {
                $message = "Successfully updated override for Product #$product_id";
            } else {
                $error = "Failed to update override";
            }
        }
    } elseif ($action == 'delete_override') {
        // Delete override
        $override_id = intval($_POST['override_id'] ?? 0);
        
        if ($override_id) {
            $sql = "DELETE FROM bg_product_features WHERE id = :id";
            $stmt = $database->prepare($sql);
            $result = $stmt->execute(['id' => $override_id]);
            
            if ($result) {
                $message = "Successfully deleted override";
            } else {
                $error = "Failed to delete override";
            }
        }
    }
}

// Get all feature cards
$sql = "SELECT * FROM bg_config WHERE config_type = 'plan_feature_card' ORDER BY config_key";
$cards = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Get all products
$sql = "SELECT id, account_name, account_plan, account_type FROM bg_products WHERE status = 'active' ORDER BY id";
$products = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Get all overrides
$sql = "SELECT pf.*, p.account_name, p.account_plan 
        FROM bg_product_features pf 
        JOIN bg_products p ON pf.product_id = p.id 
        WHERE pf.name LIKE 'card_%' 
        AND pf.status = 'active' 
        ORDER BY pf.product_id, pf.name";
$overrides = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Page styles
$additionalstyles = '<style>
.feature-card-preview {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 2rem;
    height: 100%;
    transition: all 0.3s ease;
    border: 1px solid #e2e8f0;
}
.feature-card-preview:hover {
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
.override-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: #ffc107;
    color: #000;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}
</style>';

// Include page template
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Start output
echo '<div class="container mt-4">';
echo '<div class="row mb-4">';
echo '<div class="col">';
echo '<h1><i class="bi bi-grid-3x3-gap"></i> Plan Feature Cards Manager</h1>';
echo '<p class="text-muted">Manage the feature cards displayed on the plan details page</p>';
echo '</div>';
echo '<div class="col-auto">';
echo '<a href="/admin/" class="btn btn-secondary">';
echo '<i class="bi bi-arrow-left"></i> Back to Admin';
echo '</a>';
echo '</div>';
echo '</div>';

// Show messages
if ($message) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '<i class="bi bi-check-circle"></i> ' . htmlspecialchars($message);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
}

if ($error) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '<i class="bi bi-exclamation-triangle"></i> ' . htmlspecialchars($error);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
}

// Tabs
echo '<ul class="nav nav-tabs mb-4" role="tablist">';
echo '<li class="nav-item">';
echo '<a class="nav-link active" data-bs-toggle="tab" href="#default-cards">';
echo '<i class="bi bi-grid"></i> Default Cards';
echo '</a>';
echo '</li>';
echo '<li class="nav-item">';
echo '<a class="nav-link" data-bs-toggle="tab" href="#plan-overrides">';
echo '<i class="bi bi-sliders"></i> Plan Overrides';
echo '</a>';
echo '</li>';
echo '<li class="nav-item">';
echo '<a class="nav-link" data-bs-toggle="tab" href="#preview">';
echo '<i class="bi bi-eye"></i> Live Preview';
echo '</a>';
echo '</li>';
echo '</ul>';

// Tab Content
echo '<div class="tab-content">';

// Default Cards Tab
echo '<div class="tab-pane fade show active" id="default-cards">';
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5 class="mb-0">Default Feature Cards</h5>';
echo '<small class="text-muted">These are the base cards shown for all plans unless overridden</small>';
echo '</div>';
echo '<div class="card-body">';
echo '<div class="table-responsive">';
echo '<table class="table table-hover">';
echo '<thead>';
echo '<tr>';
echo '<th>Key</th>';
echo '<th>Icon</th>';
echo '<th>Title</th>';
echo '<th>Value</th>';
echo '<th>Description</th>';
echo '<th>Plans</th>';
echo '<th>Actions</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($cards as $card) {
    $data = json_decode($card['config_data'], true);
    echo '<tr>';
    echo '<td><code>' . htmlspecialchars($card['config_key']) . '</code></td>';
    echo '<td>';
    echo '<div class="feature-icon ' . htmlspecialchars($data['color'] ?? 'primary') . '" style="width: 40px; height: 40px; font-size: 1.25rem;">';
    echo '<i class="bi bi-' . htmlspecialchars($data['icon'] ?? 'star') . '"></i>';
    echo '</div>';
    echo '</td>';
    echo '<td>' . htmlspecialchars($data['title'] ?? '') . '</td>';
    echo '<td><strong>' . htmlspecialchars($data['value'] ?? '') . '</strong></td>';
    echo '<td><small>' . htmlspecialchars(substr($data['description'] ?? '', 0, 50)) . '...</small></td>';
    echo '<td>';
    if (!empty($data['plans'])) {
        echo '<span class="badge bg-info">' . implode(', ', $data['plans']) . '</span>';
    } else {
        echo '<span class="badge bg-secondary">All Plans</span>';
    }
    echo '</td>';
    echo '<td>';
    echo '<button class="btn btn-sm btn-primary" onclick="editCard(\'' . htmlspecialchars($card['config_key']) . '\')">';
    echo '<i class="bi bi-pencil"></i>';
    echo '</button>';
    echo '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Plan Overrides Tab
echo '<div class="tab-pane fade" id="plan-overrides">';
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5 class="mb-0">Plan-Specific Overrides</h5>';
echo '<small class="text-muted">Override default card values for specific plans</small>';
echo '</div>';
echo '<div class="card-body">';

// Add Override Form
echo '<div class="bg-light p-3 rounded mb-4">';
echo '<h6>Add/Update Override</h6>';
echo '<form method="POST" class="row g-3">';
echo $display->inputcsrf_token();
echo '<input type="hidden" name="action" value="add_override">';

echo '<div class="col-md-3">';
echo '<label class="form-label">Product</label>';
echo '<select name="product_id" class="form-select" required>';
echo '<option value="">Select Product...</option>';
foreach ($products as $product) {
    echo '<option value="' . $product['id'] . '">';
    echo htmlspecialchars($product['account_name'] ?? $product['account_plan']) . ' (ID: ' . $product['id'] . ')';
    echo '</option>';
}
echo '</select>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<label class="form-label">Card</label>';
echo '<select name="card_key" class="form-select" required>';
echo '<option value="">Select Card...</option>';
foreach ($cards as $card) {
    echo '<option value="' . htmlspecialchars($card['config_key']) . '">';
    echo htmlspecialchars($card['config_key']);
    echo '</option>';
}
echo '</select>';
echo '</div>';

echo '<div class="col-md-2">';
echo '<label class="form-label">Field</label>';
echo '<select name="field" class="form-select" required>';
echo '<option value="value">Value</option>';
echo '<option value="title">Title</option>';
echo '<option value="description">Description</option>';
echo '</select>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<label class="form-label">Override Value</label>';
echo '<input type="text" name="override_value" class="form-control" required>';
echo '</div>';

echo '<div class="col-md-1">';
echo '<label class="form-label">&nbsp;</label>';
echo '<button type="submit" class="btn btn-success w-100">';
echo '<i class="bi bi-plus"></i> Add';
echo '</button>';
echo '</div>';

echo '</form>';
echo '</div>';

// Existing Overrides
echo '<div class="table-responsive">';
echo '<table class="table table-hover">';
echo '<thead>';
echo '<tr>';
echo '<th>Product</th>';
echo '<th>Card</th>';
echo '<th>Field</th>';
echo '<th>Override Value</th>';
echo '<th>Actions</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($overrides as $override) {
    // Parse the name to get card key and field
    if (preg_match('/^card_(.+)_(.+)$/', $override['name'], $matches)) {
        $card_key = $matches[1];
        $field = $matches[2];
        
        echo '<tr>';
        echo '<td>';
        echo '<strong>' . htmlspecialchars($override['account_name'] ?? $override['account_plan']) . '</strong>';
        echo '<br><small class="text-muted">ID: ' . $override['product_id'] . '</small>';
        echo '</td>';
        echo '<td><code>' . htmlspecialchars($card_key) . '</code></td>';
        echo '<td><span class="badge bg-info">' . htmlspecialchars($field) . '</span></td>';
        echo '<td>' . htmlspecialchars($override['value']) . '</td>';
        echo '<td>';
        echo '<form method="POST" style="display: inline;">';
        echo $display->inputcsrf_token();
        echo '<input type="hidden" name="action" value="delete_override">';
        echo '<input type="hidden" name="override_id" value="' . $override['id'] . '">';
        echo '<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'Delete this override?\')">';
        echo '<i class="bi bi-trash"></i>';
        echo '</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
}

echo '</tbody>';
echo '</table>';
echo '</div>';

echo '</div>';
echo '</div>';
echo '</div>';

// Preview Tab
echo '<div class="tab-pane fade" id="preview">';
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5 class="mb-0">Live Preview</h5>';
echo '<small class="text-muted">See how the cards will appear on the plan details page</small>';
echo '</div>';
echo '<div class="card-body">';
echo '<div class="row g-4">';

foreach ($cards as $card) {
    $data = json_decode($card['config_data'], true);
    echo '<div class="col-md-6 col-lg-4">';
    echo '<div class="feature-card-preview">';
    echo '<div class="feature-icon ' . htmlspecialchars($data['color'] ?? 'primary') . '">';
    echo '<i class="bi bi-' . htmlspecialchars($data['icon'] ?? 'star') . '"></i>';
    echo '</div>';
    echo '<h3 class="feature-title">' . htmlspecialchars($data['title'] ?? '') . '</h3>';
    echo '<div class="feature-value">' . htmlspecialchars($data['value'] ?? '') . '</div>';
    echo '<p class="feature-description">' . htmlspecialchars($data['description'] ?? '') . '</p>';
    
    if (!empty($data['plans'])) {
        echo '<div class="mt-3">';
        echo '<small class="text-muted">';
        echo '<i class="bi bi-info-circle"></i> Only for: ' . implode(', ', $data['plans']);
        echo '</small>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
}

echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>'; // tab-content
echo '</div>'; // container

// Edit Card Modal
echo '<div class="modal fade" id="editCardModal" tabindex="-1">';
echo '<div class="modal-dialog modal-lg">';
echo '<div class="modal-content">';
echo '<form method="POST">';
echo $display->inputcsrf_token();
echo '<input type="hidden" name="action" value="update_default">';
echo '<input type="hidden" name="card_key" id="edit_card_key">';

echo '<div class="modal-header">';
echo '<h5 class="modal-title">Edit Feature Card</h5>';
echo '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
echo '</div>';

echo '<div class="modal-body">';
echo '<div class="row g-3">';

echo '<div class="col-md-6">';
echo '<label class="form-label">Icon (Bootstrap Icon name)</label>';
echo '<input type="text" name="icon" id="edit_icon" class="form-control" placeholder="e.g., star, gift, trophy">';
echo '<small class="text-muted">See <a href="https://icons.getbootstrap.com/" target="_blank">Bootstrap Icons</a></small>';
echo '</div>';

echo '<div class="col-md-6">';
echo '<label class="form-label">Color Theme</label>';
echo '<select name="color" id="edit_color" class="form-select">';
echo '<option value="primary">Primary (Gold)</option>';
echo '<option value="success">Success (Green)</option>';
echo '<option value="info">Info (Blue)</option>';
echo '<option value="warning">Warning (Yellow)</option>';
echo '<option value="danger">Danger (Red)</option>';
echo '<option value="dark">Dark (Gray)</option>';
echo '</select>';
echo '</div>';

echo '<div class="col-12">';
echo '<label class="form-label">Title</label>';
echo '<input type="text" name="title" id="edit_title" class="form-control" required>';
echo '</div>';

echo '<div class="col-12">';
echo '<label class="form-label">Value (Large Text)</label>';
echo '<input type="text" name="value" id="edit_value" class="form-control" required>';
echo '</div>';

echo '<div class="col-12">';
echo '<label class="form-label">Description</label>';
echo '<textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>';
echo '</div>';

echo '<div class="col-12">';
echo '<label class="form-label">Limit to Specific Plans (leave empty for all plans)</label>';
echo '<div id="plan_checkboxes">';
echo '<div class="form-check form-check-inline">';
echo '<input class="form-check-input" type="checkbox" name="plans[]" value="individual" id="plan_individual">';
echo '<label class="form-check-label" for="plan_individual">Individual</label>';
echo '</div>';
echo '<div class="form-check form-check-inline">';
echo '<input class="form-check-input" type="checkbox" name="plans[]" value="parental" id="plan_parental">';
echo '<label class="form-check-label" for="plan_parental">Parental</label>';
echo '</div>';
echo '<div class="form-check form-check-inline">';
echo '<input class="form-check-input" type="checkbox" name="plans[]" value="business" id="plan_business">';
echo '<label class="form-check-label" for="plan_business">Business</label>';
echo '</div>';
echo '<div class="form-check form-check-inline">';
echo '<input class="form-check-input" type="checkbox" name="plans[]" value="free" id="plan_free">';
echo '<label class="form-check-label" for="plan_free">Free</label>';
echo '</div>';
echo '<div class="form-check form-check-inline">';
echo '<input class="form-check-input" type="checkbox" name="plans[]" value="plus" id="plan_plus">';
echo '<label class="form-check-label" for="plan_plus">Plus</label>';
echo '</div>';
echo '<div class="form-check form-check-inline">';
echo '<input class="form-check-input" type="checkbox" name="plans[]" value="premium" id="plan_premium">';
echo '<label class="form-check-label" for="plan_premium">Premium</label>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>';
echo '</div>';

echo '<div class="modal-footer">';
echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>';
echo '<button type="submit" class="btn btn-primary">Save Changes</button>';
echo '</div>';

echo '</form>';
echo '</div>';
echo '</div>';
echo '</div>';

// JavaScript
echo '<script>';

// Store card data for editing
$cardDataArray = [];
foreach ($cards as $card) {
    $cardDataArray[$card['config_key']] = json_decode($card['config_data'], true);
}

echo 'const cardData = ' . json_encode($cardDataArray) . ';';

echo '
function editCard(key) {
    const data = cardData[key] || {};
    
    // Set form values
    document.getElementById("edit_card_key").value = key;
    document.getElementById("edit_icon").value = data.icon || "";
    document.getElementById("edit_color").value = data.color || "primary";
    document.getElementById("edit_title").value = data.title || "";
    document.getElementById("edit_value").value = data.value || "";
    document.getElementById("edit_description").value = data.description || "";
    
    // Clear all plan checkboxes
    document.querySelectorAll("#plan_checkboxes input[type=\"checkbox\"]").forEach(cb => {
        cb.checked = false;
    });
    
    // Check appropriate plan boxes
    if (data.plans && Array.isArray(data.plans)) {
        data.plans.forEach(plan => {
            const checkbox = document.getElementById("plan_" + plan.toLowerCase());
            if (checkbox) checkbox.checked = true;
        });
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById("editCardModal"));
    modal.show();
}
';

echo '</script>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();