<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include site controller - handles authentication/authorization
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Staff access is handled by site-controller.php
// This page is in /staff/ directory - accessible to staff and admin users

$pagetitle = "Legal Policy Editor";

// Use Bootstrap 5 utilities instead of custom CSS where possible
$additionalstyles .= '
<style>
/* TinyMCE Editor adjustments - cannot be done with Bootstrap utilities */
.tox-tinymce {
    border: 1px solid #ced4da !important;
    border-radius: 0.25rem !important;
}
</style>
';

// Add TinyMCE script
$additionalscripts .= '
<script src="/public/js/tinymce.min.js"></script>
<script>
window.addEventListener("load", function() {
    if (typeof tinymce !== "undefined") {
        tinymce.init({
            selector: "#content",
            height: 500,
            menubar: true,
            plugins: [
                "lists", "link", "charmap", 
                "searchreplace", "code", "fullscreen",
                "table", "help", "wordcount"
            ],
            toolbar: "undo redo | formatselect | " +
                "bold italic underline strikethrough | alignleft aligncenter " +
                "alignright alignjustify | bullist numlist outdent indent | " +
                "removeformat | link table | code fullscreen",
            content_style: "body { font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; font-size: 14px; line-height: 1.6; }",
            branding: false,
            base_url: "/public/js",
            setup: function(editor) {
                editor.on("change", function() {
                    tinymce.triggerSave();
                });
            }
        });
    }
});
</script>
';

// Get policy ID from URL
$policy_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Handle new policy creation
    if ($action === 'create') {
        $name = $_POST['name'] ?? '';
        $display_name = $_POST['display_name'] ?? '';
        $description = $_POST['description'] ?? '';
        $content = $_POST['content'] ?? '';
        $category = $_POST['category'] ?? 'legal';
        $type = $_POST['type'] ?? 'policy';
        $grouping = $_POST['grouping'] ?? null;
        $review_period = intval($_POST['review_period'] ?? 180);
        
        // Check if name already exists
        $check_sql = "SELECT id FROM bg_content WHERE name = :name AND status = 'active'";
        $existing = $database->getrow($check_sql, ['name' => $name]);
        
        if ($existing) {
            $message = "A policy with this name already exists.";
            $message_type = 'danger';
        } else {
            $tags = json_encode(['review_period' => $review_period]);
            
            $insert_sql = "INSERT INTO bg_content 
                          (name, category, type, `grouping`, display_name, description, content, tags, version, status, create_dt, modify_dt, publish_dt)
                          VALUES (:name, :category, :type, :grouping, :display_name, :description, :content, :tags, '1.0', 'active', NOW(), NOW(), NOW())";
            
            $database->query($insert_sql, [
                'name' => $name,
                'category' => $category,
                'type' => $type,
                'grouping' => $grouping,
                'display_name' => $display_name,
                'description' => $description,
                'content' => $content,
                'tags' => $tags
            ]);
            
            $new_id = $database->lastInsertId();
            header("Location: /staff/redirect_legalpolicyeditor.php?id=$new_id&msg=created");
            exit;
        }
    }
    
    // Handle existing policy update
    if ($policy_id > 0 && $action === 'update') {
        $display_name = $_POST['display_name'] ?? '';
        $description = $_POST['description'] ?? '';
        $content = $_POST['content'] ?? '';
        $category = $_POST['category'] ?? '';
        $type = $_POST['type'] ?? '';
        $review_period = intval($_POST['review_period'] ?? 0);
        
        // Get existing tags and update review_period
        $existing_tags = json_decode($_POST['existing_tags'] ?? '{}', true);
        $existing_tags['review_period'] = $review_period;
        $tags_json = json_encode($existing_tags);
        
        // Check if content has actually changed
        $check_sql = "SELECT content FROM bg_content WHERE id = :id";
        $result = $database->getrow($check_sql, ['id' => $policy_id]);
        $old_content = $result ? $result['content'] : null;
        
        if ($old_content !== $content) {
            // Content changed - create new version
            $expire_sql = "UPDATE bg_content SET status = 'replaced', expire_dt = NOW() WHERE id = :id";
            $database->query($expire_sql, ['id' => $policy_id]);
            
            $version_sql = "SELECT version FROM bg_content WHERE id = :id";
            $version_result = $database->getrow($version_sql, ['id' => $policy_id]);
            $current_version = $version_result ? $version_result['version'] : '1.0';
            
            $version_parts = explode('.', $current_version ?: '1.0');
            $major = intval($version_parts[0]);
            $minor = intval($version_parts[1] ?? 0) + 1;
            $new_version = "$major.$minor";
            
            $insert_sql = "INSERT INTO bg_content 
                          (name, category, type, `grouping`, display_name, description, content, tags, version, status, create_dt, modify_dt, publish_dt)
                          SELECT name, :category, :type, `grouping`, :display_name, :description, :content, :tags, :version, 'active', NOW(), NOW(), NOW()
                          FROM bg_content WHERE id = :id";
            
            $database->query($insert_sql, [
                'category' => $category,
                'type' => $type,
                'display_name' => $display_name,
                'description' => $description,
                'content' => $content,
                'tags' => $tags_json,
                'version' => $new_version,
                'id' => $policy_id
            ]);
            
            $new_id = $database->lastInsertId();
            header("Location: /staff/redirect_legalpolicyeditor.php?id=$new_id&msg=updated");
            exit;
        } else {
            // No content change - just update metadata
            $update_sql = "UPDATE bg_content SET 
                          display_name = :display_name,
                          description = :description,
                          category = :category,
                          type = :type,
                          tags = :tags,
                          modify_dt = NOW()
                          WHERE id = :id";
            
            $database->query($update_sql, [
                'display_name' => $display_name,
                'description' => $description,
                'category' => $category,
                'type' => $type,
                'tags' => $tags_json,
                'id' => $policy_id
            ]);
            
            $message = "Policy metadata updated and review timer reset.";
            $message_type = 'info';
        }
    } elseif ($action === 'review_only') {
        // Mark as reviewed
        $update_sql = "UPDATE bg_content SET modify_dt = NOW() WHERE id = :id";
        $database->query($update_sql, ['id' => $policy_id]);
        
        $message = "Policy marked as reviewed. Review timer has been reset.";
        $message_type = 'success';
    }
}

// Check for message from redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'updated') {
        $message = "Policy updated successfully.";
        $message_type = 'success';
    } elseif ($_GET['msg'] === 'created') {
        $message = "Policy created successfully.";
        $message_type = 'success';
    }
}

// Fetch policy data if ID provided
$policy = null;
if ($policy_id > 0) {
    $sql = "SELECT * FROM bg_content WHERE id = :id";
    $policy = $database->getrow($sql, ['id' => $policy_id]);
    
    if ($policy) {
        $tags = json_decode($policy['tags'], true) ?: [];
        $review_period = $tags['review_period'] ?? 180;
        
        // Set default review period if not set
        if (!isset($tags['review_period'])) {
            $tags['review_period'] = 180;
            $update_default_sql = "UPDATE bg_content SET tags = :tags WHERE id = :id";
            $database->query($update_default_sql, [
                'tags' => json_encode($tags),
                'id' => $policy_id
            ]);
        }
        
        // Calculate review status
        $modify_date = new DateTime($policy['modify_dt']);
        $current_date = new DateTime();
        $days_since_modified = $current_date->diff($modify_date)->days;
        $days_until_review = $review_period - $days_since_modified;
    }
}

// Include page components
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Staff header section
echo '
<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="bi bi-shield-check"></i> Legal Policy Editor</h1>
        <p class="lead">Manage legal policies, terms, and privacy documents</p>
    </div>
</div>';

// Start page output - single PHP block with echo statements
echo '<div class="container my-4">';
echo '<div class="row">';
echo '<div class="col-12">';

// Display message if exists
if ($message) {
    $alert_class = $message_type === 'success' ? 'success' : ($message_type === 'info' ? 'info' : 'danger');
    echo '<div class="alert alert-' . $alert_class . ' alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($message);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

// Display policy list or edit form
if (!$policy) {
    // Fetch all policies
    $policies_sql = "SELECT id, name, display_name, category, type, version, modify_dt, status, tags, `grouping`
                     FROM bg_content 
                     WHERE (category = 'legal' OR category = 'Policies' OR `grouping` = 'legal') AND status = 'active' 
                     ORDER BY category, display_name";
    $all_policies = $database->getrows($policies_sql);
    
    // Calculate review status for each policy
    foreach ($all_policies as &$pol) {
        $tags = json_decode($pol['tags'] ?? '{}', true);
        $review_period = $tags['review_period'] ?? 180;
        $modify_date = new DateTime($pol['modify_dt']);
        $current_date = new DateTime();
        $days_since = $current_date->diff($modify_date)->days;
        $days_until = $review_period - $days_since;
        $pol['days_until_review'] = $days_until;
        $pol['review_status'] = $days_until < 0 ? 'overdue' : ($days_until <= 7 ? 'soon' : 'ok');
    }
    
    // Display policies list card
    echo '<div class="card">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<h4 class="mb-0">Legal Policies Management</h4>';
    echo '<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPolicyModal">';
    echo '<i class="fas fa-plus me-2"></i>Create New Policy';
    echo '</button>';
    echo '</div>';
    echo '<div class="card-body">';
    
    if (!empty($all_policies)) {
        echo '<div class="table-responsive">';
        echo '<table class="table table-hover">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Policy Name</th>';
        echo '<th>Category</th>';
        echo '<th>Type</th>';
        echo '<th>Version</th>';
        echo '<th>Last Reviewed</th>';
        echo '<th>Review Status</th>';
        echo '<th>Action</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($all_policies as $pol) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($pol['display_name'] ?: $pol['name']) . '</td>';
            echo '<td>' . htmlspecialchars($pol['category'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($pol['type'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($pol['version'] ?: '1.0') . '</td>';
            echo '<td>' . date('M d, Y', strtotime($pol['modify_dt'])) . '</td>';
            echo '<td>';
            
            if ($pol['review_status'] === 'overdue') {
                echo '<span class="badge bg-danger">Overdue by ' . abs($pol['days_until_review']) . ' days</span>';
            } elseif ($pol['review_status'] === 'soon') {
                echo '<span class="badge bg-warning text-dark">Due in ' . $pol['days_until_review'] . ' days</span>';
            } else {
                echo '<span class="badge bg-success">OK (' . $pol['days_until_review'] . ' days)</span>';
            }
            
            echo '</td>';
            echo '<td>';
            echo '<a href="?id=' . $pol['id'] . '" class="btn btn-sm btn-primary">';
            echo '<i class="fas fa-edit me-1"></i>Edit';
            echo '</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    } else {
        echo '<p class="text-center text-muted py-5">No policies found. Click "Create New Policy" to add one.</p>';
    }
    
    echo '</div>';
    echo '</div>';
    
    // Create Policy Modal
    echo '<div class="modal fade" id="createPolicyModal" tabindex="-1">';
    echo '<div class="modal-dialog modal-lg">';
    echo '<div class="modal-content">';
    echo '<form method="POST" action="">';
    echo '<input type="hidden" name="action" value="create">';
    
    echo '<div class="modal-header">';
    echo '<h5 class="modal-title">Create New Policy</h5>';
    echo '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
    echo '</div>';
    
    echo '<div class="modal-body">';
    echo '<div class="row g-3">';
    
    echo '<div class="col-md-6">';
    echo '<label for="new_name" class="form-label">Internal Name</label>';
    echo '<input type="text" class="form-control" id="new_name" name="name" required>';
    echo '<small class="text-muted">System identifier (no spaces)</small>';
    echo '</div>';
    
    echo '<div class="col-md-6">';
    echo '<label for="new_display_name" class="form-label">Display Name</label>';
    echo '<input type="text" class="form-control" id="new_display_name" name="display_name" required>';
    echo '</div>';
    
    echo '<div class="col-md-6">';
    echo '<label for="new_category" class="form-label">Category</label>';
    echo '<select class="form-control" id="new_category" name="category" required>';
    echo '<option value="Policies">Policies</option>';
    echo '<option value="Terms">Terms</option>';
    echo '<option value="Agreements">Agreements</option>';
    echo '<option value="Notices">Notices</option>';
    echo '</select>';
    echo '</div>';
    
    echo '<div class="col-md-6">';
    echo '<label for="new_type" class="form-label">Type</label>';
    echo '<input type="text" class="form-control" id="new_type" name="type" value="legal" required>';
    echo '</div>';
    
    echo '<div class="col-12">';
    echo '<label for="new_description" class="form-label">Description</label>';
    echo '<textarea class="form-control" id="new_description" name="description" rows="2"></textarea>';
    echo '</div>';
    
    echo '<div class="col-md-6">';
    echo '<label for="new_review_period" class="form-label">Review Period (days)</label>';
    echo '<input type="number" class="form-control" id="new_review_period" name="review_period" value="180" min="30" max="365">';
    echo '</div>';
    
    echo '<div class="col-md-6">';
    echo '<label for="new_grouping" class="form-label">Grouping</label>';
    echo '<input type="text" class="form-control" id="new_grouping" name="grouping" value="legal" readonly>';
    echo '</div>';
    
    echo '<div class="col-12">';
    echo '<label for="new_content" class="form-label">Content</label>';
    echo '<textarea class="form-control" id="new_content" name="content" rows="10" required></textarea>';
    echo '</div>';
    
    echo '</div>';
    echo '</div>';
    
    echo '<div class="modal-footer">';
    echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>';
    echo '<button type="submit" class="btn btn-primary">Create Policy</button>';
    echo '</div>';
    
    echo '</form>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
} else {
    // Display edit form for existing policy
    $header_class = $days_until_review < 0 ? 'danger' : ($days_until_review <= 7 ? 'warning' : 'info');
    
    echo '<div class="card">';
    echo '<div class="card-header bg-' . $header_class . ' text-white">';
    echo '<h4 class="mb-0">Edit Policy</h4>';
    echo '<div class="row mt-3">';
    echo '<div class="col-md-6">';
    echo '<strong>Policy:</strong> ' . htmlspecialchars($policy['display_name'] ?: $policy['name']);
    echo '</div>';
    echo '<div class="col-md-2">';
    echo '<strong>Version:</strong> ' . htmlspecialchars($policy['version'] ?: '1.0');
    echo '</div>';
    echo '<div class="col-md-4">';
    echo '<strong>Last Reviewed:</strong> ' . date('M d, Y', strtotime($policy['modify_dt']));
    echo '</div>';
    echo '</div>';
    
    if ($days_until_review < 0) {
        echo '<div class="alert alert-danger mt-3 mb-0">';
        echo '<i class="fas fa-exclamation-triangle me-2"></i>';
        echo 'This policy is <strong>overdue</strong> for review by ' . abs($days_until_review) . ' days!';
        echo '</div>';
    } elseif ($days_until_review <= 7) {
        echo '<div class="alert alert-warning mt-3 mb-0">';
        echo '<i class="fas fa-clock me-2"></i>';
        echo 'This policy needs review in <strong>' . $days_until_review . ' days</strong>.';
        echo '</div>';
    }
    
    echo '</div>';
    
    echo '<div class="card-body">';
    echo '<form method="POST" action="">';
    echo '<input type="hidden" name="policy_id" value="' . $policy_id . '">';
    echo '<input type="hidden" name="action" value="update">';
    echo '<input type="hidden" name="existing_tags" value="' . htmlspecialchars(json_encode($tags)) . '">';
    
    echo '<div class="row g-3 mb-3">';
    
    echo '<div class="col-md-6">';
    echo '<label for="display_name" class="form-label">Display Name</label>';
    echo '<input type="text" class="form-control" id="display_name" name="display_name" value="' . htmlspecialchars($policy['display_name'] ?: '') . '" required>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<label for="category" class="form-label">Category</label>';
    echo '<select class="form-control" id="category" name="category" required>';
    $categories = ['Policies', 'Terms', 'Agreements', 'Notices'];
    foreach ($categories as $cat) {
        $selected = $policy['category'] === $cat ? ' selected' : '';
        echo '<option value="' . $cat . '"' . $selected . '>' . $cat . '</option>';
    }
    echo '</select>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<label for="type" class="form-label">Type</label>';
    echo '<input type="text" class="form-control" id="type" name="type" value="' . htmlspecialchars($policy['type'] ?: 'legal') . '" required>';
    echo '</div>';
    
    echo '<div class="col-md-9">';
    echo '<label for="description" class="form-label">Description</label>';
    echo '<textarea class="form-control" id="description" name="description" rows="2">' . htmlspecialchars($policy['description'] ?: '') . '</textarea>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<label for="review_period" class="form-label">Review Period (days)</label>';
    echo '<input type="number" class="form-control" id="review_period" name="review_period" value="' . $review_period . '" min="30" max="365">';
    echo '</div>';
    
    echo '</div>';
    
    echo '<div class="mb-3">';
    echo '<label for="content" class="form-label">Policy Content</label>';
    echo '<textarea class="form-control" id="content" name="content" rows="15">' . htmlspecialchars($policy['content'] ?: '') . '</textarea>';
    echo '</div>';
    
    echo '<div class="d-flex justify-content-between">';
    echo '<div>';
    echo '<a href="/staff/redirect_legalpolicyeditor.php" class="btn btn-secondary">';
    echo '<i class="fas fa-arrow-left me-2"></i>Back to List';
    echo '</a>';
    echo '</div>';
    echo '<div>';
    echo '<button type="submit" name="action" value="review_only" class="btn btn-info me-2">';
    echo '<i class="fas fa-check me-2"></i>Mark as Reviewed (No Changes)';
    echo '</button>';
    echo '<button type="submit" name="action" value="update" class="btn btn-primary">';
    echo '<i class="fas fa-save me-2"></i>Save Changes';
    echo '</button>';
    echo '</div>';
    echo '</div>';
    
    echo '</form>';
    
    // Show version history if available
    $history_sql = "SELECT id, version, modify_dt, status 
                   FROM bg_content 
                   WHERE name = :name 
                   ORDER BY id DESC 
                   LIMIT 10";
    $history = $database->getrows($history_sql, ['name' => $policy['name']]);
    
    if (count($history) > 1) {
        echo '<div class="mt-5">';
        echo '<h5>Version History</h5>';
        echo '<div class="table-responsive">';
        echo '<table class="table table-sm">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Version</th>';
        echo '<th>Modified</th>';
        echo '<th>Status</th>';
        echo '<th>Action</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($history as $hist) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($hist['version'] ?: '1.0') . '</td>';
            echo '<td>' . date('M d, Y H:i', strtotime($hist['modify_dt'])) . '</td>';
            echo '<td>';
            if ($hist['status'] === 'active') {
                echo '<span class="badge bg-success">Active</span>';
            } else {
                echo '<span class="badge bg-secondary">' . htmlspecialchars($hist['status']) . '</span>';
            }
            echo '</td>';
            echo '<td>';
            if ($hist['id'] != $policy_id) {
                echo '<a href="?id=' . $hist['id'] . '" class="btn btn-sm btn-outline-primary">View</a>';
            } else {
                echo '<span class="text-muted">Current</span>';
            }
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    
    // Add TinyMCE initialization for edit form
    $additionalscripts .= '
    <script>
    window.addEventListener("load", function() {
        if (typeof tinymce !== "undefined") {
            tinymce.init({
                selector: "#content",
                height: 500,
                menubar: true,
                plugins: [
                    "lists", "link", "charmap", 
                    "searchreplace", "code", "fullscreen",
                    "table", "help", "wordcount"
                ],
                toolbar: "undo redo | formatselect | " +
                    "bold italic underline strikethrough | alignleft aligncenter " +
                    "alignright alignjustify | bullist numlist outdent indent | " +
                    "removeformat | link table | code fullscreen",
                branding: false,
                base_url: "/public/js",
                setup: function(editor) {
                    editor.on("change", function() {
                        tinymce.triggerSave();
                    });
                }
            });
        }
    });
    </script>
    ';
}

// Close container
echo '</div>'; // col-12
echo '</div>'; // row
echo '</div>'; // container

// Output footer
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>