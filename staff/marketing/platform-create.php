<?php
$addClasses[] = 'accessmanager';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Create New Marketing Platform";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'create') {
        $display_name = trim($_POST['display_name']);
        $url = trim($_POST['url']);
        $description = trim($_POST['description']);
        $icon = trim($_POST['icon']);
        $rank = intval($_POST['rank']);
        $platform_type = trim($_POST['platform_type']);
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $credential_notes = trim($_POST['credential_notes'] ?? '');
        $api_key = trim($_POST['api_key'] ?? '');
        $api_secret = trim($_POST['api_secret'] ?? '');
        
        $credential_id = null;
        
        // Save credentials to AccessManager first if provided
        if (!empty($username) && !empty($password)) {
            global $accessmanager;
            
            if (isset($accessmanager) && is_object($accessmanager)) {
                $parsed_url = parse_url($url);
                $host = $parsed_url['host'] ?? $url;
                
                $credential_input = [
                    'user_id' => $account->getuser('user_id'),
                    'company_id' => 0,
                    'type' => 'platform_credentials',
                    'data_type' => 'username_password',
                    'name' => 'marketing_' . strtolower(str_replace(' ', '_', $display_name)),
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
                    $credential_id = $accessmanager->create_record($credential_input);
                } catch (Exception $e) {
                    // Continue without credentials, will show warning later
                }
            }
        }
        
        // Handle API credentials separately if provided
        $api_credential_id = null;
        if (!empty($api_key)) {
            global $accessmanager;
            
            if (isset($accessmanager) && is_object($accessmanager)) {
                $parsed_url = parse_url($url);
                $host = $parsed_url['host'] ?? $url;
                
                $api_credential_input = [
                    'user_id' => $account->getuser('user_id'),
                    'company_id' => 0,
                    'type' => 'api_credentials',
                    'data_type' => 'api_key',
                    'name' => 'api_' . strtolower(str_replace(' ', '_', $display_name)),
                    'host' => $host,
                    'username' => $api_key,
                    'password' => $api_secret,
                    'notes' => 'API credentials for ' . $display_name,
                    'category' => 'marketing',
                    'grouping' => 'marketing_api',
                    'datatype' => 'api_key',
                    'creator_id' => $account->getuser('user_id')
                ];
                
                try {
                    $api_credential_id = $accessmanager->create_record($api_credential_input);
                } catch (Exception $e) {
                    // Continue without API credentials
                }
            }
        }
        
        $platform_data = [
            'url' => $url,
            'icon' => $icon,
            'platform_type' => $platform_type,
            'credential_id' => $credential_id,
            'api_credential_id' => $api_credential_id,
            'features' => [
                'campaigns' => true,
                'analytics' => true,
                'audiences' => false,
                'automation' => false
            ]
        ];
        
        $insert_sql = "INSERT INTO bg_content 
            (name, category, type, display_name, description, tags, `rank`, status, create_dt) 
            VALUES 
            (:name, 'marketing', 'platform_link', :display_name, :description, :tags, :rank, 'active', NOW())";
        
        $name = 'platform_' . time() . '_' . substr(md5($display_name), 0, 8);
        
        try {
            $database->query($insert_sql, [
                'name' => $name,
                'display_name' => $display_name,
                'description' => $description,
                'tags' => json_encode($platform_data),
                'rank' => $rank
            ]);
            
            
            header('Location: /staff/marketing/marketing-platforms.php');
            exit;
            
        } catch (Exception $e) {
        }
    }
}

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
    padding-bottom: 50px !important;
}
.feature-toggle {
    cursor: pointer;
    transition: all 0.2s ease;
}
.feature-toggle:hover {
    transform: scale(1.1);
}
.credential-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="bi bi-plus-circle"></i> Create New Marketing Platform</h1>
        <p class="lead">Add a new platform with advanced configuration</p>
    </div>
</div>';

include('../includes/marketing-nav.php');

echo '
<div class="container mt-4 mb-5 pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Platform Configuration</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create">
                        
                        <h6 class="text-primary mb-3"><i class="bi bi-info-circle"></i> Basic Information</h6>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="display_name" class="form-label">Platform Name *</label>
                                    <input type="text" class="form-control" id="display_name" name="display_name" required>
                                    <small class="text-muted">e.g., Facebook Ads Manager, Google Analytics</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="rank" class="form-label">Display Order</label>
                                    <input type="number" class="form-control" id="rank" name="rank" value="50" min="0" max="999">
                                    <small class="text-muted">Lower = higher priority</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="url" class="form-label">Platform URL *</label>
                            <input type="url" class="form-control" id="url" name="url" required>
                            <small class="text-muted">Full URL including https://</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="platform_type" class="form-label">Platform Type *</label>
                                    <select class="form-control" id="platform_type" name="platform_type" required>
                                        <option value="">Select type...</option>
                                        <option value="social_media">Social Media</option>
                                        <option value="advertising">Advertising</option>
                                        <option value="email_marketing">Email Marketing</option>
                                        <option value="analytics">Analytics</option>
                                        <option value="automation">Marketing Automation</option>
                                        <option value="content_management">Content Management</option>
                                        <option value="crm">CRM</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="icon" class="form-label">Icon Class</label>
                                    <input type="text" class="form-control" id="icon" name="icon" placeholder="bi bi-facebook">
                                    <small class="text-muted">
                                        <a href="https://icons.getbootstrap.com/" target="_blank">Browse Bootstrap icons</a>
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2" maxlength="200"></textarea>
                            <small class="text-muted">Brief description of the platform and its purpose</small>
                        </div>
                        
                        <div class="credential-section">
                            <h6 class="text-success mb-3"><i class="bi bi-key"></i> Login Credentials (Optional)</h6>
                            <p class="text-muted small">Store platform login credentials securely using AccessManager</p>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username/Email</label>
                                        <input type="text" class="form-control" id="username" name="username">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="password" name="password">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="credential_notes" class="form-label">Credential Notes</label>
                                <textarea class="form-control" id="credential_notes" name="credential_notes" rows="2"></textarea>
                                <small class="text-muted">Additional notes about these credentials</small>
                            </div>
                        </div>
                        
                        <div class="credential-section">
                            <h6 class="text-warning mb-3"><i class="bi bi-code"></i> API Credentials (Optional)</h6>
                            <p class="text-muted small">Store API keys for automated platform integration</p>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="api_key" class="form-label">API Key</label>
                                        <input type="text" class="form-control" id="api_key" name="api_key">
                                        <small class="text-muted">Public API key or client ID</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="api_secret" class="form-label">API Secret</label>
                                        <input type="password" class="form-control" id="api_secret" name="api_secret">
                                        <small class="text-muted">Private API secret or client secret</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/staff/marketing/marketing-platforms" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-plus-circle"></i> Create Platform
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Platform Types</h5>
                </div>
                <div class="card-body">
                    <div class="small">
                        <h6>Social Media</h6>
                        <ul class="list-unstyled text-muted">
                            <li><i class="bi bi-facebook"></i> Facebook, Instagram</li>
                            <li><i class="bi bi-twitter"></i> Twitter/X</li>
                            <li><i class="bi bi-linkedin"></i> LinkedIn</li>
                            <li><i class="bi bi-youtube"></i> YouTube, TikTok</li>
                        </ul>
                        
                        <h6>Advertising</h6>
                        <ul class="list-unstyled text-muted">
                            <li><i class="bi bi-google"></i> Google Ads</li>
                            <li><i class="bi bi-facebook"></i> Meta Ads</li>
                            <li><i class="bi bi-microsoft"></i> Microsoft Ads</li>
                        </ul>
                        
                        <h6>Email & Automation</h6>
                        <ul class="list-unstyled text-muted">
                            <li><i class="bi bi-envelope"></i> Mailchimp</li>
                            <li><i class="bi bi-envelope-check"></i> Constant Contact</li>
                            <li><i class="bi bi-gear"></i> HubSpot, Marketo</li>
                        </ul>
                        
                        <h6>Analytics</h6>
                        <ul class="list-unstyled text-muted">
                            <li><i class="bi bi-bar-chart"></i> Google Analytics</li>
                            <li><i class="bi bi-graph-up"></i> Adobe Analytics</li>
                            <li><i class="bi bi-pie-chart"></i> Mixpanel</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Security Notes</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-shield-check"></i>
                        <strong>Secure Storage:</strong> All credentials are encrypted and stored securely using AccessManager.
                    </div>
                    <ul class="small text-muted">
                        <li>Login credentials for manual platform access</li>
                        <li>API credentials for automated integrations</li>
                        <li>Both types stored separately for security</li>
                        <li>Access controlled by staff permissions</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>