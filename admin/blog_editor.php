<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
// admin access controlled by site-controller.php

#-------------------------------------------------------------------------------
# HANDLE FORM SUBMISSIONS
#-------------------------------------------------------------------------------
$success_message = '';
$error_message = '';

if (isset($_POST['action']) && $_POST['action'] == 'add_post') {
    $display_name = trim($_POST['display_name'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $read_time = intval($_POST['read_time'] ?? 5);
    $status = $_POST['status'] ?? 'draft';
    $rank = isset($_POST['featured']) ? 10 : 50; // Featured posts get rank 10
    $grouping = $_POST['grouping'] ?? 'general';
    
    // Auto-generate slug if empty
    if (empty($name)) {
        $name = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $display_name)));
    }
    
    // Add read time to tags if not already present
    if (!preg_match('/\d+\s*min\s*read/i', $tags)) {
        $tags = trim($tags . ', ' . $read_time . ' min read');
    }
    
    $sql = "INSERT INTO bg_content (name, category, type, grouping, display_name, description, content, tags, rank, status, publish_dt, create_dt, modify_dt) 
            VALUES (?, 'blog', 'post', ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())";
    
    $stmt = $database->prepare($sql);
    if ($stmt->execute([$name, $grouping, $display_name, $description, $content, $tags, $rank, $status])) {
        $success_message = "Blog post added successfully!";
    } else {
        $error_message = "Error adding blog post.";
    }
}

if (isset($_POST['action']) && $_POST['action'] == 'update_post') {
    $id = intval($_POST['post_id'] ?? 0);
    $display_name = trim($_POST['display_name'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $read_time = intval($_POST['read_time'] ?? 5);
    $status = $_POST['status'] ?? 'draft';
    $rank = isset($_POST['featured']) ? 10 : 50;
    $grouping = $_POST['grouping'] ?? 'general';
    
    // Add read time to tags if not already present
    if (!preg_match('/\d+\s*min\s*read/i', $tags)) {
        $tags = trim($tags . ', ' . $read_time . ' min read');
    }
    
    $sql = "UPDATE bg_content SET name=?, grouping=?, display_name=?, description=?, content=?, tags=?, rank=?, status=?, modify_dt=NOW() WHERE id=?";
    
    $stmt = $database->prepare($sql);
    if ($stmt->execute([$name, $grouping, $display_name, $description, $content, $tags, $rank, $status, $id])) {
        $success_message = "Blog post updated successfully!";
    } else {
        $error_message = "Error updating blog post.";
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "DELETE FROM bg_content WHERE id=? AND category='blog' AND type='post'";
    $stmt = $database->prepare($sql);
    if ($stmt->execute([$id])) {
        $success_message = "Blog post deleted successfully!";
    } else {
        $error_message = "Error deleting blog post.";
    }
}

#-------------------------------------------------------------------------------
# GET EXISTING POSTS
#-------------------------------------------------------------------------------
$posts_sql = "SELECT * FROM bg_content 
              WHERE category='blog' AND type='post' 
              ORDER BY create_dt DESC";
$posts_result = $database->query($posts_sql);
$existing_posts = $posts_result->fetchAll(PDO::FETCH_ASSOC);

// Process posts to add featured and read_time info
foreach ($existing_posts as &$post) {
    $post['featured'] = ($post['rank'] <= 10) ? 1 : 0;
    
    // Extract read time from tags
    if (preg_match('/(\d+)\s*min\s*read/i', $post['tags'], $matches)) {
        $post['read_time'] = $matches[1];
    } else {
        $post['read_time'] = 5;
    }
}

// Get post for editing
$edit_post = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $edit_sql = "SELECT * FROM bg_content WHERE id=? AND category='blog' AND type='post'";
    $edit_stmt = $database->prepare($edit_sql);
    $edit_stmt->execute([$edit_id]);
    $edit_post = $edit_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Add featured and read_time info
    if ($edit_post) {
        $edit_post['featured'] = ($edit_post['rank'] <= 10) ? 1 : 0;
        
        if (preg_match('/(\d+)\s*min\s*read/i', $edit_post['tags'], $matches)) {
            $edit_post['read_time'] = $matches[1];
        } else {
            $edit_post['read_time'] = 5;
        }
    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$additionalstyles .= '
<style>
.cms-container { max-width: 1200px; margin: 0 auto; }
.form-section { background: #f8f9fa; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; }
.content-preview { border: 1px solid #ddd; padding: 1rem; background: white; border-radius: 4px; }
.post-table { font-size: 0.9rem; }
.status-active { color: #28a745; }
.status-draft { color: #ffc107; }
.status-archived { color: #6c757d; }
.featured-star { color: #ffc107; }
</style>
';

echo '<div class="container main-content cms-container">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Blog Content Management</h2>
    <div>
      <a href="/blog" class="btn btn-sm btn-outline-secondary me-2">View Blog</a>
      <a href="/admin" class="btn btn-sm btn-outline-secondary">Admin Home</a>
    </div>
  </div>';

// Display messages
if (!empty($success_message)) {
    echo '<div class="alert alert-success alert-dismissible fade show">
            ' . htmlspecialchars($success_message) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}
if (!empty($error_message)) {
    echo '<div class="alert alert-danger alert-dismissible fade show">
            ' . htmlspecialchars($error_message) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

echo '<div class="row">
    <div class="col-lg-9">
      <div class="form-section">
        <h3>' . ($edit_post ? 'Edit Blog Post' : 'Add New Blog Post') . '</h3>
        <form method="POST" id="blogForm">
          <input type="hidden" name="action" value="' . ($edit_post ? 'update_post' : 'add_post') . '">
          ' . ($edit_post ? '<input type="hidden" name="post_id" value="' . $edit_post['id'] . '">' : '') . '
          
          <div class="row">
            <div class="col-md-9">
              <div class="mb-3">
                <label class="form-label">Post Title (display_name) *</label>
                <input type="text" class="form-control" name="display_name" value="' . htmlspecialchars($edit_post['display_name'] ?? '') . '" required>
                <div class="form-text">This will be displayed as the main heading</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label">Read Time (minutes)</label>
                <input type="number" class="form-control" name="read_time" value="' . ($edit_post['read_time'] ?? 5) . '" min="1" max="30">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-8">
              <div class="mb-3">
                <label class="form-label">URL Slug (name)</label>
                <input type="text" class="form-control" name="name" value="' . htmlspecialchars($edit_post['name'] ?? '') . '">
                <div class="form-text">Leave blank to auto-generate from title. Use hyphens, no spaces.</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Category (grouping)</label>
                <select class="form-control" name="grouping">
                  <option value="general"' . (($edit_post['grouping'] ?? 'general') == 'general' ? ' selected' : '') . '>General</option>
                  <option value="deals"' . (($edit_post['grouping'] ?? '') == 'deals' ? ' selected' : '') . '>Deals & Freebies</option>
                  <option value="guides"' . (($edit_post['grouping'] ?? '') == 'guides' ? ' selected' : '') . '>How-To Guides</option>
                  <option value="seasonal"' . (($edit_post['grouping'] ?? '') == 'seasonal' ? ' selected' : '') . '>Seasonal</option>
                  <option value="tips"' . (($edit_post['grouping'] ?? '') == 'tips' ? ' selected' : '') . '>Tips & Tricks</option>
                </select>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Meta Description</label>
            <textarea class="form-control" name="description" rows="2" maxlength="160">' . htmlspecialchars($edit_post['description'] ?? '') . '</textarea>
            <div class="form-text">160 characters max - used for search results and excerpts</div>
          </div>

          <div class="mb-3">
            <label class="form-label">SEO Keywords & Tags</label>
            <input type="text" class="form-control" name="tags" value="' . htmlspecialchars($edit_post['tags'] ?? '') . '">
            <div class="form-text">Comma-separated keywords for SEO</div>
          </div>

          <div class="mb-4">
            <label class="form-label">Blog Content (HTML) *</label>
            <textarea class="form-control" name="content" rows="25" required style="font-family: monospace; font-size: 14px;">' . htmlspecialchars($edit_post['content'] ?? '') . '</textarea>
            <div class="form-text">
              <strong>HTML Tips:</strong> Use &lt;h3&gt; for section headings, &lt;p&gt; for paragraphs, &lt;ul&gt;&lt;li&gt; for lists, &lt;strong&gt; for bold text.
              <br><strong>CSS Classes Available:</strong> .alert .alert-success, .alert-info, .text-primary, .text-success, .fw-bold
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-control" name="status">
                  <option value="draft"' . (($edit_post['status'] ?? 'draft') == 'draft' ? ' selected' : '') . '>Draft</option>
                  <option value="active"' . (($edit_post['status'] ?? '') == 'active' ? ' selected' : '') . '>Published</option>
                  <option value="archived"' . (($edit_post['status'] ?? '') == 'archived' ? ' selected' : '') . '>Archived</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <div class="form-check mt-4">
                  <input class="form-check-input" type="checkbox" name="featured" ' . (($edit_post['featured'] ?? 0) ? 'checked' : '') . '>
                  <label class="form-check-label">Featured Post (rank 10)</label>
                </div>
              </div>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">' . ($edit_post ? 'Update Post' : 'Add Post') . '</button>
            ' . ($edit_post ? '<a href="/blog/admin" class="btn btn-secondary">Cancel Edit</a>' : '') . '
            <button type="button" class="btn btn-outline-info" onclick="previewContent()">Preview Content</button>
          </div>
        </form>
      </div>
    </div>
    
    <div class="col-lg-3">
      <div class="form-section">
        <h4>Content Guidelines</h4>
        <ul class="list-unstyled small">
          <li><strong>✅ Structure:</strong> Use &lt;h3&gt; for sections</li>
          <li><strong>✅ Paragraphs:</strong> Wrap text in &lt;p&gt; tags</li>
          <li><strong>✅ Lists:</strong> Use &lt;ul&gt;&lt;li&gt; for bullet points</li>
          <li><strong>✅ Emphasis:</strong> &lt;strong&gt; for bold text</li>
          <li><strong>✅ Links:</strong> &lt;a href="/blog/other-post"&gt;Link Text&lt;/a&gt;</li>
        </ul>
        
        <h5 class="mt-4">Available CSS Classes:</h5>
        <ul class="list-unstyled small">
          <li><code>.alert .alert-success</code> - Green highlight box</li>
          <li><code>.alert .alert-info</code> - Blue info box</li>
          <li><code>.text-primary</code> - Primary color text</li>
          <li><code>.fw-bold</code> - Bold text</li>
          <li><code>.lead</code> - Larger intro paragraph</li>
        </ul>
        
        <h5 class="mt-4">Field Mapping:</h5>
        <ul class="list-unstyled small">
          <li><strong>name:</strong> URL slug</li>
          <li><strong>display_name:</strong> Post title</li>
          <li><strong>grouping:</strong> Blog category</li>
          <li><strong>description:</strong> Meta description</li>
          <li><strong>content:</strong> HTML content</li>
          <li><strong>tags:</strong> SEO keywords</li>
          <li><strong>rank:</strong> 10=featured, 50=normal</li>
        </ul>
      </div>
      
      <div id="contentPreview" class="form-section" style="display:none;">
        <h4>Content Preview</h4>
        <div class="content-preview" id="previewArea"></div>
      </div>
    </div>
  </div>';

// Existing Posts Table
echo '<div class="form-section">
    <h3>Existing Blog Posts</h3>
    <div class="table-responsive">
      <table class="table table-striped post-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Slug</th>
            <th>Category</th>
            <th>Status</th>
            <th>Featured</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>';

foreach ($existing_posts as $post) {
    $status_class = 'status-' . $post['status'];
    echo '<tr>
            <td>
              <strong>' . htmlspecialchars($post['display_name']) . '</strong>
              <br><small class="text-muted">' . ($post['read_time'] ?: 5) . ' min read</small>
            </td>
            <td><code>' . htmlspecialchars($post['name']) . '</code></td>
            <td><span class="badge bg-secondary">' . ucfirst($post['grouping']) . '</span></td>
            <td><span class="' . $status_class . '">' . ucfirst($post['status']) . '</span></td>
            <td>' . ($post['featured'] ? '<span class="featured-star">⭐ Yes</span>' : 'No') . '</td>
            <td>' . date('M j, Y', strtotime($post['create_dt'])) . '</td>
            <td>
              <div class="btn-group btn-group-sm">
                <a href="/blog/' . $post['name'] . '" class="btn btn-outline-primary btn-sm" target="_blank">View</a>
                <a href="?action=edit&id=' . $post['id'] . '" class="btn btn-outline-secondary btn-sm">Edit</a>
                <a href="?action=delete&id=' . $post['id'] . '" class="btn btn-outline-danger btn-sm" 
                   onclick="return confirm(\'Delete this post?\')">Delete</a>
              </div>
            </td>
          </tr>';
}

echo '    </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function previewContent() {
    const content = document.querySelector(\'textarea[name="content"]\').value;
    const previewDiv = document.getElementById(\'contentPreview\');
    const previewArea = document.getElementById(\'previewArea\');
    
    previewArea.innerHTML = content;
    previewDiv.style.display = \'block\';
    previewArea.scrollIntoView({ behavior: \'smooth\' });
}
</script>';

$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>