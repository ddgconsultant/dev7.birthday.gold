<?php
// Include site controller - handles authentication/authorization
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Note: Authentication and role checking handled by site-controller.php
// This page is in /admin/ directory which should be protected

$pagetitle = "Legal Policy Editor";
$additionalstyles = '
<style>
/* Hide skip to main content link */
.sr-only, .sr-only-focusable:not(:focus) {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0,0,0,0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}

/* Add bottom margin to body */
body { 
    margin-bottom: 100px !important; 
    padding-bottom: 50px !important; 
}

/* TinyMCE Editor adjustments */
.tox-tinymce {
    border: 1px solid #ced4da !important;
    border-radius: 0.25rem !important;
}
</style>
';

// Add TinyMCE script - using local version
$additionalscripts = '
<script src="/public/js/tinymce.min.js"></script>
<script>
// Initialize TinyMCE when page loads
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
                // Ensure the form submits the content
                editor.on("change", function() {
                    tinymce.triggerSave();
                });
            }
        });
    } else {
        console.error("TinyMCE not loaded");
    }
});
</script>
';

// Get policy ID from URL
$policy_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$message_type = '';

#-------------------------------------------------------------------------------
# HANDLE FORM SUBMISSION
#-------------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $policy_id > 0) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        // Update existing policy
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
        $old_content = $database->query($check_sql, ['id' => $policy_id])->fetchColumn();
        
        if ($old_content !== $content) {
            // Content has changed - create new version and expire old one
            
            // First, expire the current version
            $expire_sql = "UPDATE bg_content SET 
                          status = 'replaced',
                          expire_dt = NOW()
                          WHERE id = :id";
            $database->query($expire_sql, ['id' => $policy_id]);
            
            // Get the current version number
            $version_sql = "SELECT version FROM bg_content WHERE id = :id";
            $current_version = $database->query($version_sql, ['id' => $policy_id])->fetchColumn();
            
            // Increment version
            $version_parts = explode('.', $current_version ?: '1.0');
            $major = intval($version_parts[0]);
            $minor = intval($version_parts[1] ?? 0) + 1;
            $new_version = "$major.$minor";
            
            // Insert new version
            $insert_sql = "INSERT INTO bg_content 
                          (name, category, type, grouping, display_name, description, content, tags, version, status, create_dt, modify_dt, publish_dt)
                          SELECT name, :category, :type, grouping, :display_name, :description, :content, :tags, :version, 'active', NOW(), NOW(), NOW()
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
            $message = "Policy updated successfully. New version $new_version created. Review timer has been reset.";
            $message_type = 'success';
            
            // Redirect to the new version
            header("Location: /admin/legal-policy-editor.php?id=$new_id&msg=updated");
            exit;
            
        } else {
            // No content change - just update metadata and reset review timer
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
            
            $message = "Policy metadata updated and review timer reset. No content changes detected.";
            $message_type = 'info';
        }
        
    } elseif ($action === 'review_only') {
        // Just mark as reviewed (update modify_dt only)
        $update_sql = "UPDATE bg_content SET modify_dt = NOW() WHERE id = :id";
        $database->query($update_sql, ['id' => $policy_id]);
        
        $message = "Policy marked as reviewed. Review timer has been reset.";
        $message_type = 'success';
    }
}

// Check for message from redirect
if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
    $message = "Policy updated successfully.";
    $message_type = 'success';
}

#-------------------------------------------------------------------------------
# FETCH POLICY DATA
#-------------------------------------------------------------------------------

$policy = null;
if ($policy_id > 0) {
    $sql = "SELECT * FROM bg_content WHERE id = :id";
    $policy = $database->query($sql, ['id' => $policy_id])->fetch(PDO::FETCH_ASSOC);
    
    if ($policy) {
        $tags = json_decode($policy['tags'], true) ?: [];
        $review_period = $tags['review_period'] ?? 180; // Default to 180 days
        
        // If no review period is set, update the database with default
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
?>

<div class="container my-5 pt-3">
    <div class="row">
        <div class="col-12">
            <h1>Legal Policy Editor</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type === 'success' ? 'success' : ($message_type === 'info' ? 'info' : 'warning') ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!$policy): ?>
                <div class="alert alert-warning">
                    <h4>No Policy Selected</h4>
                    <p>Please select a policy to edit from your review reminder notification.</p>
                    <a href="/admin/" class="btn btn-primary">Back to Admin</a>
                </div>
            <?php else: ?>
                
                <!-- Policy Status Card -->
                <div class="card mb-4">
                    <div class="card-header bg-<?= $days_until_review < 0 ? 'danger' : ($days_until_review <= 7 ? 'warning' : 'info') ?> text-white">
                        <h5 class="mb-0">Review Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Policy:</strong> <?= htmlspecialchars($policy['display_name'] ?: $policy['name']) ?>
                            </div>
                            <div class="col-md-2">
                                <strong>Version:</strong> <?= htmlspecialchars($policy['version'] ?: '1.0') ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Last Reviewed:</strong> <?= date('M d, Y', strtotime($policy['modify_dt'])) ?>
                            </div>
                            <div class="col-md-2">
                                <strong>Days Since Review:</strong> <?= $days_since_modified ?>
                            </div>
                            <div class="col-md-2">
                                <strong>Review Due:</strong> 
                                <?php if ($days_until_review < 0): ?>
                                    <span class="text-danger">Overdue by <?= abs($days_until_review) ?> days</span>
                                <?php else: ?>
                                    <span class="text-<?= $days_until_review <= 7 ? 'warning' : 'success' ?>">In <?= $days_until_review ?> days</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Policy Edit Form -->
                <form method="POST" action="">
                    <input type="hidden" name="existing_tags" value="<?= htmlspecialchars(json_encode($tags)) ?>">
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Edit Policy</h5>
                        </div>
                        <div class="card-body">
                            
                            <!-- Metadata Fields -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="display_name" class="form-label">Display Name</label>
                                    <input type="text" class="form-control" id="display_name" name="display_name" 
                                           value="<?= htmlspecialchars($policy['display_name'] ?: '') ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-control" id="category" name="category" required>
                                        <option value="Policies" <?= $policy['category'] === 'Policies' ? 'selected' : '' ?>>Policies</option>
                                        <option value="Legal" <?= $policy['category'] === 'Legal' ? 'selected' : '' ?>>Legal</option>
                                        <option value="Compliance" <?= $policy['category'] === 'Compliance' ? 'selected' : '' ?>>Compliance</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="type" class="form-label">Type</label>
                                    <input type="text" class="form-control" id="type" name="type" 
                                           value="<?= htmlspecialchars($policy['type'] ?: '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-9">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" class="form-control" id="description" name="description" 
                                           value="<?= htmlspecialchars($policy['description'] ?: '') ?>" maxlength="500">
                                </div>
                                <div class="col-md-3">
                                    <label for="review_period" class="form-label">Review Period (days)</label>
                                    <input type="number" class="form-control" id="review_period" name="review_period" 
                                           value="<?= $review_period ?>" min="1" max="365" required>
                                    <small class="text-muted">How often this policy should be reviewed</small>
                                </div>
                            </div>
                            
                            <!-- Content Field -->
                            <div class="mb-3">
                                <label for="content" class="form-label">Policy Content</label>
                                <textarea class="form-control" id="content" name="content" rows="20" required><?= htmlspecialchars($policy['content'] ?: '') ?></textarea>
                                <small class="text-muted">Modifying content will create a new version. Metadata changes only will not create a new version.</small>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between">
                                <div>
                                    <button type="submit" name="action" value="update" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                    <button type="submit" name="action" value="review_only" class="btn btn-success">
                                        <i class="fas fa-check"></i> Mark as Reviewed (No Changes)
                                    </button>
                                </div>
                                <div>
                                    <a href="/admin/" class="btn btn-secondary">Cancel</a>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </form>
                
                <!-- Version History -->
                <?php
                $history_sql = "SELECT id, version, modify_dt, status 
                               FROM bg_content 
                               WHERE name = :name 
                               ORDER BY id DESC 
                               LIMIT 10";
                $history = $database->query($history_sql, ['name' => $policy['name']])->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <?php if (count($history) > 1): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Version History</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Version</th>
                                    <th>Modified</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $version): ?>
                                <tr <?= $version['id'] == $policy_id ? 'class="table-active"' : '' ?>>
                                    <td><?= htmlspecialchars($version['version'] ?: '1.0') ?></td>
                                    <td><?= date('M d, Y g:i A', strtotime($version['modify_dt'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $version['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= htmlspecialchars($version['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($version['id'] != $policy_id): ?>
                                        <a href="?id=<?= $version['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        <?php else: ?>
                                        <span class="badge bg-primary">Current</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
?>

<?php
// Get TinyMCE API key from sitesettings
// Domains are configured at: https://www.tiny.cloud/my-account/domains/
// The key should be in sitesettings as: $sitesettings['tinymce']['api_key']
$tinymce_api_key = '';

// Try different possible locations for the API key
if (isset($sitesettings['tinymce']['api_key'])) {
    $tinymce_api_key = $sitesettings['tinymce']['api_key'];
} elseif (isset($sitesettings['tinymce_api_key'])) {
    $tinymce_api_key = $sitesettings['tinymce_api_key'];
} elseif (defined('TINYMCE_API_KEY')) {
    $tinymce_api_key = TINYMCE_API_KEY;
}

// For initial setup - you need to add your API key here or in config
if (empty($tinymce_api_key)) {
    // TODO: Add your TinyMCE API key to sitesettings config file
    // Get your key from: https://www.tiny.cloud/my-account/api-keys/
    $tinymce_api_key = 'no-api-key'; // Replace with your actual API key
}

// Debug: Show API key status
if (isset($_GET['debug'])) {
    echo "<!-- TinyMCE API Key: " . (!empty($tinymce_api_key) ? substr($tinymce_api_key, 0, 10) . "..." : "Not configured") . " -->\n";
}

// Only use fallback if absolutely no key
if (false): // Disabled CKEditor fallback for now since you have TinyMCE account 
?>
<!-- Fallback to CKEditor 5 (free, no API key required) -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    ClassicEditor
        .create(document.querySelector('#content'), {
            height: '500px',
            toolbar: {
                items: [
                    'heading', '|',
                    'bold', 'italic', 'underline', 'strikethrough', '|',
                    'link', 'bulletedList', 'numberedList', '|',
                    'outdent', 'indent', '|',
                    'blockQuote', 'insertTable', '|',
                    'undo', 'redo', '|',
                    'sourceEditing'
                ]
            },
            table: {
                contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
            }
        })
        .then(editor => {
            // Update textarea on change
            editor.model.document.on('change:data', () => {
                document.querySelector('#content').value = editor.getData();
            });
        })
        .catch(error => {
            console.error('CKEditor initialization error:', error);
        });
});
</script>

<?php else: ?>
<!-- TinyMCE with API Key -->
<script src="https://cdn.tiny.cloud/1/<?= htmlspecialchars($tinymce_api_key) ?>/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
// Initialize TinyMCE with full features
document.addEventListener("DOMContentLoaded", function() {
    tinymce.init({
        selector: '#content',
        height: 500,
        menubar: true,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount', 'codesample'
        ],
        toolbar: 'undo redo | blocks | ' +
            'bold italic underline strikethrough | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'link image media | forecolor backcolor | ' +
            'removeformat | table | code codesample | fullscreen help',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; line-height: 1.6; }',
        branding: false,
        promotion: false,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: true,
        // Advanced features
        image_advtab: true,
        table_toolbar: 'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol',
        table_appearance_options: true,
        table_sizing_mode: 'responsive',
        // Save changes back to textarea
        setup: function(editor) {
            editor.on('change', function() {
                tinymce.triggerSave();
            });
        }
    });
});
</script>
<?php endif; ?>

<?php
$app->outputpage();
?>