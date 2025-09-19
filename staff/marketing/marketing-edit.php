<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get available platforms
$platforms_sql = "SELECT id, display_name, tags FROM bg_content 
                  WHERE category = 'marketing' AND type = 'platform_link' AND status = 'active'
                  ORDER BY display_name ASC";
$available_platforms = $database->getrows($platforms_sql);

$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$campaign = null;
$campaign_data = [];

// If editing, load existing campaign
if ($campaign_id > 0) {
    $campaign_sql = "SELECT * FROM bg_content WHERE id = :id AND category = 'marketing' AND type = 'campaign'";
    $campaign = $database->getrow($campaign_sql, ['id' => $campaign_id]);
    
    if (!$campaign) {
        header('Location: /staff/marketing-campaigns.php');
        exit;
    }
    
    $campaign_data = json_decode($campaign['tags'], true) ?: [];
    $pagetitle = "Edit Campaign: " . $campaign['display_name'];
} else {
    $pagetitle = "Create New Campaign";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $display_name = trim($_POST['display_name']);
    $description = trim($_POST['description']);
    $content = $_POST['content'];
    $publish_dt = $_POST['start_date'] . ' ' . $_POST['start_time'] . ':00';
    $expire_dt = !empty($_POST['end_date']) ? $_POST['end_date'] . ' ' . $_POST['end_time'] . ':00' : null;
    $status = $_POST['status'];
    
    // Build campaign data array
    $campaign_data = [
        'platforms' => array_filter(array_map('trim', explode(',', $_POST['platforms']))),
        'budget' => floatval($_POST['budget']),
        'budget_type' => $_POST['budget_type'],
        'target_audience' => $_POST['target_audience'],
        'goals' => $_POST['goals'],
        'notes' => $_POST['notes'],
        'assets' => json_decode($_POST['assets_data'], true) ?: [],
        'metrics' => [
            'impressions_goal' => intval($_POST['impressions_goal']),
            'clicks_goal' => intval($_POST['clicks_goal']),
            'conversions_goal' => intval($_POST['conversions_goal'])
        ],
        'created_by' => $current_user_data['user_id'],
        'last_modified_by' => $current_user_data['user_id']
    ];
    
    $tags_json = json_encode($campaign_data);
    
    if ($campaign_id > 0) {
        // Update existing campaign
        $update_sql = "UPDATE bg_content SET 
            display_name = :display_name,
            description = :description,
            content = :content,
            tags = :tags,
            publish_dt = :publish_dt,
            expire_dt = :expire_dt,
            status = :status,
            modify_dt = NOW()
            WHERE id = :id";
        
        $database->query($update_sql, [
            'display_name' => $display_name,
            'description' => $description,
            'content' => $content,
            'tags' => $tags_json,
            'publish_dt' => $publish_dt,
            'expire_dt' => $expire_dt,
            'status' => $status,
            'id' => $campaign_id
        ]);
        
        $system->addmessage('success', 'Campaign updated successfully');
    } else {
        // Create new campaign
        $insert_sql = "INSERT INTO bg_content 
            (name, category, type, display_name, description, content, tags, publish_dt, expire_dt, status, create_dt) 
            VALUES 
            (:name, 'marketing', 'campaign', :display_name, :description, :content, :tags, :publish_dt, :expire_dt, :status, NOW())";
        
        $name = 'campaign_' . time() . '_' . substr(md5($display_name), 0, 8);
        
        $database->query($insert_sql, [
            'name' => $name,
            'display_name' => $display_name,
            'description' => $description,
            'content' => $content,
            'tags' => $tags_json,
            'publish_dt' => $publish_dt,
            'expire_dt' => $expire_dt,
            'status' => $status
        ]);
        
        $campaign_id = $database->lastInsertId();
        $system->addmessage('success', 'Campaign created successfully');
    }
    
    header('Location: /staff/marketing-campaigns.php');
    exit;
}

// Get TinyMCE API key
$tinymce_api_key = '';
if (isset($sitesettings['tinymce']['api_key'])) {
    $tinymce_api_key = $sitesettings['tinymce']['api_key'];
} elseif (isset($sitesettings['tinymce_api_key'])) {
    $tinymce_api_key = $sitesettings['tinymce_api_key'];
} elseif (defined('TINYMCE_API_KEY')) {
    $tinymce_api_key = TINYMCE_API_KEY;
}

if (empty($tinymce_api_key)) {
    $tinymce_api_key = 'no-api-key';
}

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
    padding-bottom: 50px !important;
}

.asset-preview {
    position: relative;
    border: 2px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 0.5rem;
    margin-bottom: 1rem;
}

.asset-preview img, .asset-preview video {
    max-width: 100%;
    height: auto;
    max-height: 200px;
}

.asset-remove {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(220, 53, 69, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    cursor: pointer;
}

.platform-tag {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    margin: 0.25rem;
    background: #e9ecef;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.platform-tag .remove-platform {
    margin-left: 0.5rem;
    cursor: pointer;
    color: #dc3545;
}

#assetDropZone {
    border: 2px dashed #dee2e6;
    border-radius: 0.5rem;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

#assetDropZone:hover, #assetDropZone.dragover {
    border-color: #0d6efd;
    background: #f0f8ff;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="bi bi-megaphone-fill"></i> Marketing Campaign Manager</h1>
        <p class="lead">' . ($campaign ? 'Edit Campaign' : 'Create New Campaign') . '</p>
    </div>
</div>';

include('../includes/marketing-nav.php');

echo '
<div class="container mt-4 mb-5 pb-5">
    <form method="POST" id="campaignForm">
        <input type="hidden" id="assets_data" name="assets_data" value="' . htmlspecialchars(json_encode($campaign_data['assets'] ?? [])) . '">
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Campaign Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="display_name" class="form-label">Campaign Title *</label>
                            <input type="text" class="form-control" id="display_name" name="display_name" 
                                   value="' . ($campaign ? htmlspecialchars($campaign['display_name']) : '') . '" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Brief Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" maxlength="500">' . ($campaign ? htmlspecialchars($campaign['description']) : '') . '</textarea>
                            <small class="text-muted">Maximum 500 characters</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date *</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="' . ($campaign ? date('Y-m-d', strtotime($campaign['publish_dt'])) : date('Y-m-d')) . '" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" 
                                       value="' . ($campaign ? date('H:i', strtotime($campaign['publish_dt'])) : '09:00') . '">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="' . (($campaign && $campaign['expire_dt']) ? date('Y-m-d', strtotime($campaign['expire_dt'])) : '') . '">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" 
                                       value="' . (($campaign && $campaign['expire_dt']) ? date('H:i', strtotime($campaign['expire_dt'])) : '23:59') . '">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="inactive"' . (($campaign && $campaign['status'] == 'inactive') ? ' selected' : '') . '>Draft</option>
                                <option value="active"' . ((!$campaign || $campaign['status'] == 'active') ? ' selected' : '') . '>Active</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Campaign Content</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="content" class="form-label">Full Campaign Details</label>
                            <textarea class="form-control" id="content" name="content" rows="10">' . ($campaign ? htmlspecialchars($campaign['content']) : '') . '</textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Media Assets</h5>
                    </div>
                    <div class="card-body">
                        <div id="assetDropZone">
                            <i class="bi bi-cloud-upload fa-3x mb-3 text-muted"></i>
                            <p class="mb-0">Drag and drop files here or click to browse</p>
                            <small class="text-muted">Supports images, videos, PDFs (Max 10MB per file)</small>
                            <input type="file" id="assetUpload" multiple accept="image/*,video/*,.pdf" style="display: none;">
                        </div>
                        
                        <div id="assetPreview" class="mt-3">';

if (!empty($campaign_data['assets'])) {
    foreach ($campaign_data['assets'] as $index => $asset) {
        echo '
                            <div class="asset-preview" data-index="' . $index . '">';
        
        if (strpos($asset['type'], 'image') !== false) {
            echo '
                                <img src="' . htmlspecialchars($asset['url']) . '" alt="Asset">';
        } elseif (strpos($asset['type'], 'video') !== false) {
            echo '
                                <video controls><source src="' . htmlspecialchars($asset['url']) . '"></video>';
        } else {
            echo '
                                <div class="p-3">
                                    <i class="bi bi-file-pdf-fill fa-2x"></i>
                                    <p>' . htmlspecialchars($asset['name']) . '</p>
                                </div>';
        }
        
        echo '
                                <button type="button" class="asset-remove" onclick="removeAsset(' . $index . ')">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>';
    }
}

echo '
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Platforms</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Where are you launching this campaign?</label>
                            <div class="mb-2">
                                <small class="text-muted">Select from available platforms:</small>
                            </div>';

// Show available platforms as clickable tokens
foreach ($available_platforms as $platform) {
    $platform_data = json_decode($platform['tags'], true) ?: [];
    $icon = $platform_data['icon'] ?? 'bi bi-link';
    
    echo '
                            <button type="button" class="btn btn-outline-primary btn-sm me-2 mb-2 platform-selector" 
                                    data-platform="' . htmlspecialchars($platform['display_name']) . '"
                                    onclick="addPlatformToken(\'' . addslashes($platform['display_name']) . '\')">
                                <i class="' . $icon . ' me-1"></i>' . htmlspecialchars($platform['display_name']) . '
                            </button>';
}

echo '
                            <div class="mt-3">
                                <label class="form-label">Selected Platforms:</label>
                            </div>
                        </div>
                        <div id="platformTags">';

if (!empty($campaign_data['platforms'])) {
    foreach ($campaign_data['platforms'] as $platform) {
        echo '
                            <span class="platform-tag">
                                ' . htmlspecialchars($platform) . '
                                <span class="remove-platform" onclick="removePlatform(this)">×</span>
                            </span>';
    }
}

echo '
                        </div>
                        <input type="hidden" id="platforms" name="platforms" 
                               value="' . htmlspecialchars(implode(',', $campaign_data['platforms'] ?? [])) . '">
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Budget</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="budget" class="form-label">Budget Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="budget" name="budget" step="0.01"
                                       value="' . ($campaign_data['budget'] ?? '') . '">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="budget_type" class="form-label">Budget Type</label>
                            <select class="form-select" id="budget_type" name="budget_type">
                                <option value="total"' . ((($campaign_data['budget_type'] ?? '') == 'total') ? ' selected' : '') . '>Total Campaign</option>
                                <option value="daily"' . ((($campaign_data['budget_type'] ?? '') == 'daily') ? ' selected' : '') . '>Daily</option>
                                <option value="weekly"' . ((($campaign_data['budget_type'] ?? '') == 'weekly') ? ' selected' : '') . '>Weekly</option>
                                <option value="monthly"' . ((($campaign_data['budget_type'] ?? '') == 'monthly') ? ' selected' : '') . '>Monthly</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Goals & Metrics</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="goals" class="form-label">Campaign Goals</label>
                            <textarea class="form-control" id="goals" name="goals" rows="3">' . htmlspecialchars($campaign_data['goals'] ?? '') . '</textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="impressions_goal" class="form-label">Impressions Goal</label>
                            <input type="number" class="form-control" id="impressions_goal" name="impressions_goal" 
                                   value="' . ($campaign_data['metrics']['impressions_goal'] ?? '') . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="clicks_goal" class="form-label">Clicks Goal</label>
                            <input type="number" class="form-control" id="clicks_goal" name="clicks_goal" 
                                   value="' . ($campaign_data['metrics']['clicks_goal'] ?? '') . '">
                        </div>
                        
                        <div class="mb-3">
                            <label for="conversions_goal" class="form-label">Conversions Goal</label>
                            <input type="number" class="form-control" id="conversions_goal" name="conversions_goal" 
                                   value="' . ($campaign_data['metrics']['conversions_goal'] ?? '') . '">
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Additional Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="target_audience" class="form-label">Target Audience</label>
                            <textarea class="form-control" id="target_audience" name="target_audience" rows="3">' . htmlspecialchars($campaign_data['target_audience'] ?? '') . '</textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Internal Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4">' . htmlspecialchars($campaign_data['notes'] ?? '') . '</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <a href="/staff/marketing-campaigns" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Cancel
                    </a>
                    <div>
                        <button type="submit" name="action" value="save" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Campaign
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.tiny.cloud/1/' . htmlspecialchars($tinymce_api_key) . '/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: "#content",
    height: 400,
    menubar: true,
    plugins: [
        "advlist", "autolink", "lists", "link", "image", "charmap", "preview",
        "anchor", "searchreplace", "visualblocks", "code", "fullscreen",
        "insertdatetime", "media", "table", "help", "wordcount"
    ],
    toolbar: "undo redo | formatselect | bold italic forecolor backcolor | " +
        "alignleft aligncenter alignright alignjustify | " +
        "bullist numlist outdent indent | link image media | " +
        "removeformat | code fullscreen preview | help",
    content_style: "body { font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; font-size: 14px; line-height: 1.6; }",
    branding: false,
    promotion: false,
    setup: function(editor) {
        editor.on("change", function() {
            editor.save();
        });
    }
});

let assets = ' . json_encode($campaign_data['assets'] ?? []) . ';

const dropZone = document.getElementById("assetDropZone");
const fileInput = document.getElementById("assetUpload");

dropZone.addEventListener("click", () => fileInput.click());

dropZone.addEventListener("dragover", (e) => {
    e.preventDefault();
    dropZone.classList.add("dragover");
});

dropZone.addEventListener("dragleave", () => {
    dropZone.classList.remove("dragover");
});

dropZone.addEventListener("drop", (e) => {
    e.preventDefault();
    dropZone.classList.remove("dragover");
    handleFiles(e.dataTransfer.files);
});

fileInput.addEventListener("change", (e) => {
    handleFiles(e.target.files);
});

function handleFiles(files) {
    Array.from(files).forEach(file => {
        if (file.size > 10 * 1024 * 1024) {
            alert(file.name + " is too large. Maximum size is 10MB.");
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const asset = {
                name: file.name,
                type: file.type,
                size: file.size,
                url: e.target.result
            };
            assets.push(asset);
            updateAssetPreview();
            updateAssetsData();
        };
        reader.readAsDataURL(file);
    });
}

function updateAssetPreview() {
    const preview = document.getElementById("assetPreview");
    preview.innerHTML = "";
    
    assets.forEach((asset, index) => {
        const div = document.createElement("div");
        div.className = "asset-preview";
        div.dataset.index = index;
        
        let content = "";
        if (asset.type.startsWith("image")) {
            content = `<img src="${asset.url}" alt="Asset">`;
        } else if (asset.type.startsWith("video")) {
            content = `<video controls><source src="${asset.url}"></video>`;
        } else {
            content = `<div class="p-3"><i class="bi bi-file-pdf-fill fa-2x"></i><p>${asset.name}</p></div>`;
        }
        
        div.innerHTML = content + `
            <button type="button" class="asset-remove" onclick="removeAsset(${index})">
                <i class="bi bi-x-lg"></i>
            </button>
        `;
        
        preview.appendChild(div);
    });
}

function removeAsset(index) {
    assets.splice(index, 1);
    updateAssetPreview();
    updateAssetsData();
}

function updateAssetsData() {
    document.getElementById("assets_data").value = JSON.stringify(assets);
}

function addPlatform() {
    const input = document.getElementById("platformInput");
    const platform = input.value.trim();
    
    if (platform) {
        const tagsDiv = document.getElementById("platformTags");
        const tag = document.createElement("span");
        tag.className = "platform-tag";
        tag.innerHTML = `${platform} <span class="remove-platform" onclick="removePlatform(this)">×</span>`;
        tagsDiv.appendChild(tag);
        
        input.value = "";
        updatePlatforms();
    }
}

function removePlatform(element) {
    const platformName = element.parentElement.textContent.replace('×', '').trim();
    element.parentElement.remove();
    updatePlatforms();
    
    // Re-enable the platform selector button
    const button = document.querySelector(`[data-platform="${platformName}"]`);
    if (button) {
        button.disabled = false;
        button.classList.add('btn-outline-primary');
        button.classList.remove('btn-secondary');
    }
}

function updatePlatforms() {
    const tags = document.querySelectorAll(".platform-tag");
    const platforms = Array.from(tags).map(tag => tag.textContent.replace("×", "").trim());
    document.getElementById("platforms").value = platforms.join(",");
}

document.getElementById("platformInput").addEventListener("keypress", function(e) {
    if (e.key === "Enter") {
        e.preventDefault();
        addPlatform();
    }
});
</script>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>