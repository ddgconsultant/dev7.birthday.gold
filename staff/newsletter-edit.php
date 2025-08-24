<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Create/Edit Newsletter Campaign";
$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$campaign = null;

// If editing, load existing campaign
if ($campaign_id > 0) {
    $campaign_sql = "SELECT * FROM bg_newsletter_campaigns WHERE campaign_id = :campaign_id";
    $campaign = $database->getrow($campaign_sql, ['campaign_id' => $campaign_id]);
    
    if (!$campaign) {
        header('Location: /staff/newsletter-list.php');
        exit;
    }
    
    $pagetitle = "Edit Campaign: " . $campaign['title'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $subject = trim($_POST['subject']);
    $body_html = $_POST['body_html'];
    $cta_category = $_POST['cta_category'];
    $send_dt = $_POST['send_date'] . ' ' . $_POST['send_time'] . ':00';
    $status = $_POST['action'] == 'schedule' ? 'scheduled' : 'draft';
    
    if ($campaign_id > 0) {
        // Update existing campaign
        $update_sql = "UPDATE bg_newsletter_campaigns SET 
            title = :title,
            subject = :subject,
            body_html = :body_html,
            cta_category = :cta_category,
            send_dt = :send_dt,
            status = :status
            WHERE campaign_id = :campaign_id";
        
        $database->query($update_sql, [
            'title' => $title,
            'subject' => $subject,
            'body_html' => $body_html,
            'cta_category' => $cta_category,
            'send_dt' => $send_dt,
            'status' => $status,
            'campaign_id' => $campaign_id
        ]);
        
        $system->addmessage('success', 'Campaign updated successfully!');
    } else {
        // Create new campaign
        $insert_sql = "INSERT INTO bg_newsletter_campaigns 
            (title, subject, body_html, cta_category, send_dt, status, created_by, created_dt) 
            VALUES 
            (:title, :subject, :body_html, :cta_category, :send_dt, :status, :created_by, NOW())";
        
        $database->query($insert_sql, [
            'title' => $title,
            'subject' => $subject,
            'body_html' => $body_html,
            'cta_category' => $cta_category,
            'send_dt' => $send_dt,
            'status' => $status,
            'created_by' => $account->getuser('user_id')
        ]);
        
        $campaign_id = $database->lastInsertId();
        
        // If scheduled, populate the queue
        if ($status == 'scheduled') {
            // Get all active users who are not unsubscribed
            $users_sql = "SELECT user_id FROM bg_users 
                         WHERE status = 'active' 
                         AND user_id NOT IN (SELECT user_id FROM bg_unsubscribes)";
            $users = $database->getrows($users_sql);
            
            // Populate queue
            foreach ($users as $user) {
                $queue_sql = "INSERT INTO bg_newsletter_queue 
                             (campaign_id, user_id, scheduled_dt, status) 
                             VALUES 
                             (:campaign_id, :user_id, :scheduled_dt, 'pending')";
                
                $database->query($queue_sql, [
                    'campaign_id' => $campaign_id,
                    'user_id' => $user['user_id'],
                    'scheduled_dt' => $send_dt
                ]);
            }
        }
        
        $system->addmessage('success', 'Campaign created successfully!');
    }
    
    header('Location: /staff/newsletter-list.php');
    exit;
}

// Get categories for dropdown
$categories = ['pizza', 'coffee', 'beauty', 'retail', 'restaurant', 'entertainment', 'health', 'other'];

// Get TinyMCE API key - following same pattern as legal-policy-editor.php
$tinymce_api_key = '';
if (isset($sitesettings['tinymce']['api_key'])) {
    $tinymce_api_key = $sitesettings['tinymce']['api_key'];
} elseif (isset($sitesettings['tinymce_api_key'])) {
    $tinymce_api_key = $sitesettings['tinymce_api_key'];
} elseif (defined('TINYMCE_API_KEY')) {
    $tinymce_api_key = TINYMCE_API_KEY;
}

// Fallback to no-api-key if not configured
if (empty($tinymce_api_key)) {
    $tinymce_api_key = 'no-api-key';
}

// Add styles for bottom margin
$additionalstyles = '
<style>
body { 
    margin-bottom: 100px !important; 
    padding-bottom: 50px !important; 
}

.tox-tinymce {
    border: 1px solid #ced4da !important;
    border-radius: 0.25rem !important;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="fas fa-envelope"></i> Newsletter System</h1>
        <p class="lead">Design and schedule your newsletter</p>
    </div>
</div>';

// Include navigation
include('includes/newsletter-nav.php');

echo '
<div class="container mt-4">
    
    <form method="POST" id="campaignForm">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Campaign Details</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Campaign Title</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="' . ($campaign ? htmlspecialchars($campaign['title']) : '') . '" required>
                        <small class="text-muted">Internal reference only - not shown to recipients</small>
                    </div>
                    <div class="col-md-6">
                        <label for="subject" class="form-label">Email Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" 
                               value="' . ($campaign ? htmlspecialchars($campaign['subject']) : '') . '" required>
                        <small class="text-muted">Available placeholders: [[first_name]], [[city]], [[birthday_month]]</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="cta_category" class="form-label">CTA Category</label>
                        <select class="form-select" id="cta_category" name="cta_category" required>
                            <option value="">Select a category...</option>';

foreach ($categories as $cat) {
    $selected = ($campaign && $campaign['cta_category'] == $cat) ? ' selected' : '';
    echo '
                            <option value="' . $cat . '"' . $selected . '>' . ucfirst($cat) . '</option>';
}

echo '
                        </select>
                        <small class="text-muted">This determines which brands are shown in the CTA block</small>
                    </div>
                    <div class="col-md-3">
                        <label for="send_date" class="form-label">Send Date</label>
                        <input type="date" class="form-control" id="send_date" name="send_date" 
                               value="' . ($campaign ? date('Y-m-d', strtotime($campaign['send_dt'])) : date('Y-m-d')) . '" required>
                    </div>
                    <div class="col-md-3">
                        <label for="send_time" class="form-label">Send Time</label>
                        <input type="time" class="form-control" id="send_time" name="send_time" 
                               value="' . ($campaign ? date('H:i', strtotime($campaign['send_dt'])) : '09:00') . '" required>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Email Content</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="body_html" class="form-label">Email Body</label>
                    <textarea class="form-control" id="body_html" name="body_html" rows="15">' . 
                    ($campaign ? htmlspecialchars($campaign['body_html']) : '') . '</textarea>
                    <div class="mt-2">
                        <small class="text-muted">
                            <strong>Available Placeholders:</strong><br>
                            [[first_name]] - User first name<br>
                            [[city]] - User city<br>
                            [[birthday_month]] - User birthday month<br>
                            [[CTA_BLOCK]] - This will be replaced with personalized brand recommendations
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <button type="submit" name="action" value="draft" class="btn btn-secondary">
                            <i class="fas fa-save"></i> Save as Draft
                        </button>
                        <button type="submit" name="action" value="schedule" class="btn btn-primary">
                            <i class="fas fa-clock"></i> Schedule Campaign
                        </button>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-outline-primary" onclick="previewEmail()">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="sendTestEmail()">
                            <i class="fas fa-envelope"></i> Send Test
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Email Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- TinyMCE with API Key -->
<script src="https://cdn.tiny.cloud/1/' . htmlspecialchars($tinymce_api_key) . '/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
// Initialize TinyMCE following legal-policy-editor pattern
tinymce.init({
    selector: "#body_html",
    height: 500,
    menubar: true,
    plugins: [
        "advlist", "autolink", "lists", "link", "image", "charmap", "preview",
        "anchor", "searchreplace", "visualblocks", "code", "fullscreen",
        "insertdatetime", "media", "table", "help", "wordcount", "emoticons",
        "autoresize", "directionality", "pagebreak", "nonbreaking", "template"
    ],
    toolbar: "undo redo | formatselect | bold italic forecolor backcolor | " +
        "alignleft aligncenter alignright alignjustify | " +
        "bullist numlist outdent indent | link image media | " +
        "removeformat | code fullscreen preview | help",
    content_style: "body { font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; font-size: 14px; line-height: 1.6; }",
    branding: false,
    promotion: false,
    relative_urls: false,
    remove_script_host: false,
    convert_urls: true,
    image_advtab: true,
    autoresize_bottom_margin: 50,
    autoresize_max_height: 800,
    autoresize_min_height: 400,
    toolbar_mode: "sliding",
    contextmenu: "link image table",
    quickbars_selection_toolbar: "bold italic | quicklink h2 h3 blockquote",
    setup: function(editor) {
        editor.on("change", function() {
            editor.save();
        });
    }
});

function previewEmail() {
    var content = tinymce.get("body_html").getContent();
    var subject = $("#subject").val();
    
    // Simple placeholder replacement for preview
    content = content.replace(/\[\[first_name\]\]/g, "John");
    content = content.replace(/\[\[city\]\]/g, "Seattle");
    content = content.replace(/\[\[birthday_month\]\]/g, "January");
    content = content.replace(/\[\[CTA_BLOCK\]\]/g, 
        "<div style=\"border: 2px dashed #ccc; padding: 20px; margin: 20px 0; text-align: center;\">" +
        "<strong>CTA BLOCK</strong><br>Personalized brand recommendations will appear here</div>");
    
    $("#previewContent").html(
        "<div class=\"mb-3\"><strong>Subject:</strong> " + subject + "</div>" +
        "<hr>" +
        "<div>" + content + "</div>"
    );
    
    var modal = new bootstrap.Modal(document.getElementById("previewModal"));
    modal.show();
}

function sendTestEmail() {
    var email = prompt("Enter email address to send test to:");
    if (email) {
        $.post("/staff/ajax/newsletter-test.php", {
            campaign_id: ' . $campaign_id . ',
            test_email: email,
            subject: $("#subject").val(),
            body: tinymce.get("body_html").getContent(),
            category: $("#cta_category").val()
        }, function(response) {
            if (response.success) {
                alert("Test email sent successfully!");
            } else {
                alert("Error: " + response.message);
            }
        }, "json");
    }
}
</script>';

include($dir['core_components'] . '/bg_footer.inc');

$app->outputpage();
?>