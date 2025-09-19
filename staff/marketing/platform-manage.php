<?php
$addClasses[] = 'accessmanager';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$platform_id = intval($_GET['platform_id'] ?? 0);

if (!$platform_id) {
    header('Location: /staff/marketing/marketing-platforms.php');
    exit;
}

// Get platform details
$platform_sql = "SELECT * FROM bg_content WHERE id = :id AND type = 'platform_link'";
$platform = $database->getrow($platform_sql, ['id' => $platform_id]);

if (!$platform) {
    header('Location: /staff/marketing/marketing-platforms.php');
    exit;
}

$platform_data = json_decode($platform['tags'], true) ?: [];
$pagetitle = $platform['display_name'] . " - Campaign Management";

// Handle campaign actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'manage_credentials') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $credential_notes = trim($_POST['credential_notes'] ?? '');
        $delete_credentials = isset($_POST['delete_credentials']);
        
        if ($delete_credentials && $credential_id) {
            // Delete existing credentials
            global $accessmanager;
            try {
                $accessmanager->delete_record($credential_id);
                
                // Update platform to remove credential reference
                $platform_data['credential_id'] = null;
                $update_sql = "UPDATE bg_content SET tags = :tags WHERE id = :id";
                $database->query($update_sql, [
                    'id' => $platform_id,
                    'tags' => json_encode($platform_data)
                ]);
                
            } catch (Exception $e) {
            }
        } elseif (!empty($username) && !empty($password)) {
            global $accessmanager;
            
            if (!isset($accessmanager) || !is_object($accessmanager)) {
            } else {
                $parsed_url = parse_url($platform_data['url'] ?? '');
                $host = $parsed_url['host'] ?? 'unknown';
                
                $credential_input = [
                    'user_id' => $account->getuser('user_id'),
                    'company_id' => 0,
                    'type' => 'platform_credentials',
                    'data_type' => 'username_password',
                    'name' => 'marketing_' . strtolower(str_replace(' ', '_', $platform['display_name'])),
                    'host' => $host,
                    'username' => $username,
                    'password' => $password,
                    'notes' => $credential_notes,
                    'category' => 'marketing',
                    'grouping' => 'marketing_platforms',
                    'datatype' => 'username_password',
                    'creator_id' => $account->getuser('user_id')
                ];
                
                try {
                    if ($credential_id) {
                        // Update existing credentials
                        $accessmanager->update_record($credential_id, $credential_input);
                    } else {
                        // Create new credentials
                        $credential_id = $accessmanager->create_record($credential_input);
                        
                        // Update platform to reference the new credential
                        $platform_data['credential_id'] = $credential_id;
                        $update_sql = "UPDATE bg_content SET tags = :tags WHERE id = :id";
                        $database->query($update_sql, [
                            'id' => $platform_id,
                            'tags' => json_encode($platform_data)
                        ]);
                    }
                    
                } catch (Exception $e) {
                }
            }
        }
        
        header("Location: /staff/marketing/platform-manage.php?platform_id=" . $platform_id);
        exit;
    }
    
    if ($action == 'edit_platform') {
        $display_name = trim($_POST['display_name']);
        $url = trim($_POST['url']);
        $description = trim($_POST['description']);
        $icon = trim($_POST['icon']);
        $rank = intval($_POST['rank']);
        
        $platform_data['url'] = $url;
        $platform_data['icon'] = $icon;
        
        $update_sql = "UPDATE bg_content 
            SET display_name = :display_name, description = :description, tags = :tags, `rank` = :rank 
            WHERE id = :id AND type = 'platform_link'";
        
        try {
            $database->query($update_sql, [
                'id' => $platform_id,
                'display_name' => $display_name,
                'description' => $description,
                'tags' => json_encode($platform_data),
                'rank' => $rank
            ]);
            
        } catch (Exception $e) {
        }
        
        header("Location: /staff/marketing/platform-manage.php?platform_id=" . $platform_id);
        exit;
    }
    
    if ($action == 'add_campaign') {
        $campaign_name = trim($_POST['campaign_name']);
        $campaign_type = trim($_POST['campaign_type']);
        $budget = floatval($_POST['budget'] ?? 0);
        $status = trim($_POST['status'] ?? 'draft');
        $description = trim($_POST['description'] ?? '');
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        
        $campaign_data = [
            'platform_id' => $platform_id,
            'type' => $campaign_type,
            'budget' => $budget,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'external_id' => null,
            'metrics' => []
        ];
        
        $insert_sql = "INSERT INTO bg_content 
            (name, category, type, display_name, description, tags, status, create_dt) 
            VALUES 
            (:name, 'marketing', 'campaign', :display_name, :description, :tags, :status, NOW())";
        
        $name = 'campaign_' . $platform_id . '_' . time();
        
        try {
            $database->query($insert_sql, [
                'name' => $name,
                'display_name' => $campaign_name,
                'description' => $description,
                'tags' => json_encode($campaign_data),
                'status' => $status
            ]);
            
        } catch (Exception $e) {
        }
        
        header("Location: /staff/marketing/platform-manage.php?platform_id=" . $platform_id);
        exit;
    }
    
    if ($action == 'delete_campaign') {
        $campaign_id = intval($_POST['campaign_id']);
        $database->query("DELETE FROM bg_content WHERE id = :id AND type = 'campaign'", ['id' => $campaign_id]);
        
        header("Location: /staff/marketing/platform-manage.php?platform_id=" . $platform_id);
        exit;
    }
    
    if ($action == 'inactivate_platform') {
        $database->query("UPDATE bg_content SET status = 'inactive' WHERE id = :id AND type = 'platform_link'", ['id' => $platform_id]);
        header('Location: /staff/marketing/marketing-platforms.php');
        exit;
    }
    
    if ($action == 'activate_platform') {
        $database->query("UPDATE bg_content SET status = 'active' WHERE id = :id AND type = 'platform_link'", ['id' => $platform_id]);
        header("Location: /staff/marketing/platform-manage.php?platform_id=" . $platform_id);
        exit;
    }
    
    if ($action == 'delete_platform') {
        // Delete associated campaigns first
        $delete_campaigns_sql = "DELETE FROM bg_content WHERE type = 'campaign' AND JSON_EXTRACT(tags, '$.platform_id') = :platform_id";
        $database->query($delete_campaigns_sql, ['platform_id' => $platform_id]);
        
        // Delete associated credentials if they exist
        if ($credential_id) {
            global $accessmanager;
            try {
                $accessmanager->delete_record($credential_id);
            } catch (Exception $e) {
                // Continue even if credential deletion fails
            }
        }
        
        // Delete the platform itself
        $database->query("DELETE FROM bg_content WHERE id = :id AND type = 'platform_link'", ['id' => $platform_id]);
        
        header('Location: /staff/marketing/marketing-platforms.php');
        exit;
    }
}

// Get campaigns for this platform
$campaigns_sql = "SELECT * FROM bg_content 
                 WHERE category = 'marketing' 
                 AND type = 'campaign' 
                 AND JSON_EXTRACT(tags, '$.platform_id') = :platform_id
                 ORDER BY create_dt DESC";
$campaigns = $database->getrows($campaigns_sql, ['platform_id' => $platform_id]);

// Get credential info for this platform
$credential_id = $platform_data['credential_id'] ?? null;
$credential_info = null;

if ($credential_id) {
    $cred_sql = "SELECT id, name, host, type, create_dt, notes FROM am_datastore WHERE id = :id";
    $credential_info = $database->getrow($cred_sql, ['id' => $credential_id]);
}

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
    padding-bottom: 50px !important;
}
.platform-header {
    background: #f8f9fa;
    border: 3px solid #0d6efd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.1);
}
.campaign-card {
    transition: transform 0.2s;
}
.campaign-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>
';

$addClasses[] = 'accessmanager';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="' . ($platform_data['icon'] ?? 'bi bi-link') . ' me-3"></i>' . htmlspecialchars($platform['display_name']) . '</h1>
        <p class="lead">Campaign Management & Analytics</p>
    </div>
</div>';

include('../includes/marketing-nav.php');

echo '
<div class="container mt-4 mb-5 pb-5">
    <div class="row mb-3">
        <div class="col-12 text-end">
            <a href="/staff/marketing/marketing-platforms.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Platforms
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12 mb-4">
            <div class="platform-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-1"><i class="' . ($platform_data['icon'] ?? 'bi bi-link') . ' me-3"></i>' . htmlspecialchars($platform['display_name']) . '</h3>
                        <p class="mb-2">' . htmlspecialchars($platform['description']) . '</p>
                        <div class="btn-group">
                            <a href="' . htmlspecialchars($platform_data['url'] ?? '#') . '" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-box-arrow-up-right me-2"></i>Open Platform
                            </a>
                            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editPlatformModal">
                                <i class="bi bi-pencil me-2"></i>Edit Platform
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">';

if ($credential_info) {
    echo '
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#viewCredentialsModal">
                            <i class="bi bi-key me-2"></i>View Credentials
                        </button>';
} else {
    echo '
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#credentialsModal">
                            <i class="bi bi-plus me-2"></i>Add Credentials
                        </button>';
}

echo '
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Campaigns</h5>
                    <a href="/staff/marketing/campaign-create.php?platform_id=' . $platform_id . '" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus me-2"></i>Create Campaign
                    </a>
                </div>
                <div class="card-body">';

if (empty($campaigns)) {
    echo '
                    <div class="text-center py-4">
                        <i class="bi bi-megaphone display-4 text-muted"></i>
                        <h5 class="mt-3 text-muted">No campaigns yet</h5>
                        <p class="text-muted">Create your first campaign to get started</p>
                        <a href="/staff/marketing/campaign-create.php?platform_id=' . $platform_id . '" class="btn btn-primary">
                            <i class="bi bi-plus me-2"></i>Create Campaign
                        </a>
                    </div>';
} else {
    echo '
                    <div class="row">';
    
    foreach ($campaigns as $campaign) {
        $campaign_data = json_decode($campaign['tags'], true) ?: [];
        $status_badge = '';
        $status_icon = '';
        
        switch ($campaign['status']) {
            case 'active':
                $status_badge = 'bg-success';
                $status_icon = 'bi-play-fill';
                break;
            case 'paused':
                $status_badge = 'bg-warning';
                $status_icon = 'bi-pause-fill';
                break;
            case 'completed':
                $status_badge = 'bg-info';
                $status_icon = 'bi-check-circle';
                break;
            default:
                $status_badge = 'bg-secondary';
                $status_icon = 'bi-circle';
        }
        
        echo '
                        <div class="col-md-6 mb-3">
                            <div class="card campaign-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">' . htmlspecialchars($campaign['display_name']) . '</h6>
                                        <span class="badge ' . $status_badge . '">
                                            <i class="' . $status_icon . '"></i> ' . ucfirst($campaign['status']) . '
                                        </span>
                                    </div>
                                    <p class="card-text text-muted small">' . htmlspecialchars($campaign['description']) . '</p>
                                    <div class="row text-center small">
                                        <div class="col-4">
                                            <strong>' . ($campaign_data['type'] ?? 'N/A') . '</strong><br>
                                            <small class="text-muted">Type</small>
                                        </div>
                                        <div class="col-4">
                                            <strong>$' . number_format($campaign_data['budget'] ?? 0) . '</strong><br>
                                            <small class="text-muted">Budget</small>
                                        </div>
                                        <div class="col-4">
                                            <strong>' . date('M j', strtotime($campaign['create_dt'])) . '</strong><br>
                                            <small class="text-muted">Created</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="btn-group w-100" role="group">
                                        <button class="btn btn-outline-primary btn-sm" onclick="editCampaign(' . $campaign['id'] . ')">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="btn btn-outline-info btn-sm" onclick="viewAnalytics(' . $campaign['id'] . ')">
                                            <i class="bi bi-bar-chart"></i> Analytics
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm(\'Delete this campaign?\');">
                                            <input type="hidden" name="action" value="delete_campaign">
                                            <input type="hidden" name="campaign_id" value="' . $campaign['id'] . '">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>';
    }
    
    echo '
                    </div>';
}

echo '
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Platform Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="' . htmlspecialchars($platform_data['url'] ?? '#') . '" target="_blank" class="btn btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right me-2"></i>Open Platform
                        </a>';

if ($credential_info) {
    echo '
                        <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewCredentialsModal">
                            <i class="bi bi-eye me-2"></i>View Credentials
                        </button>
                        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#credentialsModal">
                            <i class="bi bi-key me-2"></i>Edit Credentials
                        </button>';
} else {
    echo '
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#credentialsModal">
                            <i class="bi bi-key me-2"></i>Add Credentials
                        </button>';
}

echo '
                        ' . ($platform['status'] == 'active' ? '
                        <form method="POST" onsubmit="return confirm(\'Inactivate this platform?\');">
                            <input type="hidden" name="action" value="inactivate_platform">
                            <button type="submit" class="btn btn-outline-warning w-100">
                                <i class="bi bi-pause me-2"></i>Inactivate Platform
                            </button>
                        </form>' : '
                        <form method="POST">
                            <input type="hidden" name="action" value="activate_platform">
                            <button type="submit" class="btn btn-outline-success w-100">
                                <i class="bi bi-play me-2"></i>Activate Platform
                            </button>
                        </form>') . '
                        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deletePlatformModal">
                            <i class="bi bi-trash me-2"></i>Delete Platform
                        </button>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Quick Stats</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary">' . count($campaigns) . '</h4>
                            <small class="text-muted">Total Campaigns</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success">' . count(array_filter($campaigns, function($c) { return $c['status'] == 'active'; })) . '</h4>
                            <small class="text-muted">Active Campaigns</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
function editCampaign(campaignId) {
    // TODO: Implement campaign editing
    alert("Campaign editing coming soon!");
}

function viewAnalytics(campaignId) {
    // TODO: Implement analytics view
    alert("Campaign analytics coming soon!");
}

function viewCredentials() {
    // Handled by modal now
}

function addCredentials() {
    // Handled by modal now
}

function togglePassword(inputId) {
    // Will be moved outside PHP
}

function copyToClipboard(text) {
    // Will be moved outside PHP
}
</script>

<!-- Edit Platform Modal -->
<div class="modal fade" id="editPlatformModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Platform</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_platform">
                    
                    <div class="mb-3">
                        <label for="edit_display_name" class="form-label">Platform Name *</label>
                        <input type="text" class="form-control" id="edit_display_name" name="display_name" 
                               value="' . htmlspecialchars($platform['display_name']) . '" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_url" class="form-label">URL *</label>
                        <input type="url" class="form-control" id="edit_url" name="url" 
                               value="' . htmlspecialchars($platform_data['url'] ?? '') . '" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="edit_description" name="description" 
                               value="' . htmlspecialchars($platform['description']) . '" maxlength="100">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_icon" class="form-label">Icon Class</label>
                        <input type="text" class="form-control" id="edit_icon" name="icon" 
                               value="' . htmlspecialchars($platform_data['icon'] ?? '') . '" placeholder="bi bi-facebook">
                        <small class="text-muted">
                            <a href="https://icons.getbootstrap.com/" target="_blank">Browse Bootstrap icons</a>
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_rank" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="edit_rank" name="rank" 
                               value="' . $platform['rank'] . '" min="0" max="999">
                        <small class="text-muted">Lower numbers appear first</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Platform</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Credentials Modal -->
<div class="modal fade" id="viewCredentialsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">View Credentials</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">';

if ($credential_info) {
    // Get full credential record with encrypted fields and kipath
    global $accessmanager;
    
    $full_cred_sql = "SELECT id, encrypted_name, encrypted_value, kipath, notes 
                      FROM am_datastore 
                      WHERE id = :id";
    $full_credential = $database->getrow($full_cred_sql, ['id' => $credential_id]);
    
    try {
        // Use AccessManager's decrypt_wki method like the admin interface
        if (isset($accessmanager) && is_object($accessmanager) && $full_credential) {
            // Debug: Check what we have
            $username = $accessmanager->decrypt_wki($full_credential['encrypted_name'], $full_credential['kipath']);
            $password = $accessmanager->decrypt_wki($full_credential['encrypted_value'], $full_credential['kipath']);
            
            // Handle empty password field
            if (empty($password)) {
                $password = '[Empty password field]';
            }
            
            if ($full_credential['notes']) {
                // Notes might not be encrypted - try decryption first, fallback to raw
                try {
                    $decrypted_notes = $accessmanager->decrypt_wki($full_credential['notes'], $full_credential['kipath']);
                    // If decryption results in empty or same value, use raw notes
                    if (empty($decrypted_notes) || $decrypted_notes === $full_credential['notes']) {
                        $decrypted_notes = $full_credential['notes'];
                    }
                } catch (Exception $e) {
                    // If decryption fails, use raw notes (they might not be encrypted)
                    $decrypted_notes = $full_credential['notes'];
                }
            } else {
                $decrypted_notes = '';
            }
        } else {
            // Show debug info
            $debug_info = 'AccessManager: ' . (isset($accessmanager) ? 'exists' : 'missing') . 
                         ', Object: ' . (isset($accessmanager) && is_object($accessmanager) ? 'yes' : 'no') . 
                         ', Credential: ' . ($full_credential ? 'found' : 'missing');
            $username = '[Debug: ' . $debug_info . ']';
            $password = '[Debug: ' . $debug_info . ']';
            $decrypted_notes = '';
        }
    } catch (Exception $e) {
        $username = '[Decryption failed]';
        $password = '[Decryption failed]';
        $decrypted_notes = 'Error: ' . $e->getMessage();
    }
    
    echo '
                <div class="alert alert-warning">
                    <i class="bi bi-shield-exclamation"></i> 
                    <strong>Secure Information:</strong> Handle credentials responsibly
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><strong>Platform:</strong></label>
                    <div class="form-control-plaintext">' . htmlspecialchars($credential_info['name']) . '</div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><strong>Host:</strong></label>
                    <div class="form-control-plaintext">' . htmlspecialchars($credential_info['host']) . '</div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><strong>Username/Email:</strong></label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="' . htmlspecialchars($username) . '" readonly>
                        <button type="button" class="btn btn-outline-secondary" onclick="copyToClipboard(this.previousElementSibling.value)">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><strong>Password:</strong></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="viewPassword" value="' . htmlspecialchars($password) . '" readonly>
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword(\'viewPassword\')">
                            <i class="bi bi-eye" id="viewPasswordIcon"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="copyToClipboard(document.getElementById(\'viewPassword\').value)">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>';
                
    echo '
                <div class="mb-3">
                    <label class="form-label"><strong>Notes:</strong></label>
                    <div class="form-control-plaintext">' . (!empty($decrypted_notes) ? nl2br(htmlspecialchars($decrypted_notes)) : '<em class="text-muted">No notes</em>') . '</div>
                </div>';
    
    echo '
                <div class="mb-3">
                    <label class="form-label"><strong>Created:</strong></label>
                    <div class="form-control-plaintext">' . date('M j, Y g:i A', strtotime($credential_info['create_dt'])) . '</div>
                </div>';
} else {
    echo '
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    No credentials stored for this platform.
                </div>';
}

echo '
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#credentialsModal">
                    <i class="bi bi-pencil"></i> Edit Credentials
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Credentials Management Modal -->
<div class="modal fade" id="credentialsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">' . ($credential_info ? 'Manage' : 'Add') . ' Credentials</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="manage_credentials">';

if ($credential_info) {
    echo '
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Current credentials:</strong> ' . htmlspecialchars($credential_info['name']) . '<br>
                        <small>Created: ' . date('M j, Y', strtotime($credential_info['create_dt'])) . '</small>
                    </div>';
}

echo '
                    <div class="mb-3">
                        <label for="username" class="form-label">Username/Email</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Enter login username or email">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter login password">
                    </div>
                    
                    <div class="mb-3">
                        <label for="credential_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="credential_notes" name="credential_notes" rows="2" 
                                  placeholder="Additional notes about these credentials">' . htmlspecialchars($credential_info['notes'] ?? '') . '</textarea>
                    </div>';

if ($credential_info) {
    echo '
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="delete_credentials" name="delete_credentials">
                        <label class="form-check-label text-danger" for="delete_credentials">
                            <i class="bi bi-trash"></i> Delete existing credentials
                        </label>
                    </div>';
}

echo '
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">' . ($credential_info ? 'Update' : 'Save') . ' Credentials</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Platform Modal -->
<div class="modal fade" id="deletePlatformModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Delete Platform</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Warning:</strong> This action cannot be undone.
                </div>
                
                <p>You are about to delete <strong>' . htmlspecialchars($platform['display_name']) . '</strong> and all associated data:</p>
                
                <ul class="text-muted">
                    <li>' . count($campaigns) . ' campaign(s) will be deleted</li>';

if ($credential_info) {
    echo '                    <li>Stored credentials will be deleted from AccessManager</li>';
}

echo '                    <li>Platform configuration and settings will be lost</li>
                </ul>
                
                <p class="mb-0">Type <strong>DELETE</strong> to confirm:</p>
                <input type="text" class="form-control mt-2" id="deleteConfirm" placeholder="Type DELETE to confirm">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_platform">
                    <button type="submit" class="btn btn-danger" id="deleteButton" disabled onclick="return validateDelete()">
                        <i class="bi bi-trash me-2"></i>Delete Platform
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById("deleteConfirm").addEventListener("input", function() {
    const deleteButton = document.getElementById("deleteButton");
    if (this.value === "DELETE") {
        deleteButton.disabled = false;
    } else {
        deleteButton.disabled = true;
    }
});

function validateDelete() {
    return document.getElementById("deleteConfirm").value === "DELETE";
}
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>

<script>
function togglePassword(inputId) {
    const passwordInput = document.getElementById(inputId);
    const icon = document.getElementById(inputId + 'Icon');
    
    if (passwordInput && icon) {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            passwordInput.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        console.log('Copied to clipboard');
    });
}
</script>