<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Marketing Platforms";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add') {
        $display_name = trim($_POST['display_name']);
        $url = trim($_POST['url']);
        $description = trim($_POST['description']);
        $icon = trim($_POST['icon']);
        $rank = intval($_POST['rank']);
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $credential_notes = trim($_POST['credential_notes'] ?? '');
        
        $credential_id = null;
        
        // Save credentials to AccessManager first if provided
        if (!empty($username) && !empty($password)) {
            global $accessmanager;
            
            // Extract hostname from URL for the host field
            $parsed_url = parse_url($url);
            $host = $parsed_url['host'] ?? $url;
            
            $credential_input = [
                'type' => 'platform_credentials',
                'name' => 'marketing_' . strtolower(str_replace(' ', '_', $display_name)),
                'host' => $host,
                'host_link_type' => 'website',
                'category' => 'marketing',
                'grouping' => 'marketing_platforms',
                'username' => $username,
                'password' => $password,
                'notes' => $credential_notes,
                'datatype' => 'username_password',
                'strength' => ['score' => 0]
            ];
            
            try {
                $credential_id = $accessmanager->create_record($credential_input);
            } catch (Exception $e) {
                // Continue without credentials, will show warning later
            }
        }
        
        $platform_data = [
            'url' => $url,
            'icon' => $icon,
            'credential_id' => $credential_id  // Store direct reference to AccessManager record
        ];
        
        $insert_sql = "INSERT INTO bg_content 
            (name, category, type, display_name, description, tags, `rank`, status, create_dt) 
            VALUES 
            (:name, 'marketing', 'platform_link', :display_name, :description, :tags, :rank, 'active', NOW())";
        
        $name = 'platform_' . time() . '_' . substr(md5($display_name), 0, 8);
        
        $database->query($insert_sql, [
            'name' => $name,
            'display_name' => $display_name,
            'description' => $description,
            'tags' => json_encode($platform_data),
            'rank' => $rank
        ]);
        
        // Show appropriate success message
        if ($credential_id) {
            $system->addmessage('success', 'Platform and credentials added successfully');
        } elseif (!empty($username) && !empty($password)) {
            $system->addmessage('warning', 'Platform added but credentials could not be saved');
        } else {
            $system->addmessage('success', 'Platform added successfully');
        }
        
        header('Location: /staff/marketing/marketing-platforms.php');
        exit;
        
    } elseif ($action == 'delete') {
        $platform_id = intval($_POST['platform_id']);
        $database->query("DELETE FROM bg_content WHERE id = :id AND type = 'platform_link'", ['id' => $platform_id]);
        $system->addmessage('success', 'Platform deleted successfully');
        header('Location: /staff/marketing-platforms.php');
        exit;
    }
}

// Get all platform links
$platforms_sql = "SELECT * FROM bg_content 
                 WHERE category = 'marketing' 
                 AND type = 'platform_link' 
                 ORDER BY COALESCE(`rank`, 50) ASC, display_name ASC";
$platforms = $database->getrows($platforms_sql);

// Get AccessManager credentials for marketing platforms
$credentials_sql = "SELECT d.id, d.name, d.host, d.encrypted_name, d.type, d.create_dt,
                    CASE WHEN d.id IS NOT NULL THEN 1 ELSE 0 END as has_credentials
                    FROM am_datastore d 
                    WHERE d.category = 'marketing' 
                    AND d.type IN ('platform_credentials', 'api_key', 'oauth_token')
                    ORDER BY d.name ASC";
$credentials = $database->getrows($credentials_sql);

// Create a lookup array for credentials by platform name/host
$credential_lookup = [];
foreach ($credentials as $cred) {
    $credential_lookup[$cred['host']] = $cred;
    $credential_lookup[$cred['name']] = $cred;
}

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
    padding-bottom: 50px !important;
}
.btn-link[data-bs-toggle="collapse"]:not(.collapsed) .bi-chevron-right {
    transform: rotate(90deg);
}
.btn-link[data-bs-toggle="collapse"] .bi-chevron-right {
    transition: transform 0.2s ease;
}
.table td, .table th {
    padding: 1rem 0.75rem;
    vertical-align: middle;
}
.table tbody tr:hover {
    background-color: #e3f2fd !important;
}
.table tbody tr {
    transition: background-color 0.2s ease;
}
.table tbody tr:hover a {
    text-decoration: underline;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="bi bi-link-45deg"></i> Marketing Platforms</h1>
        <p class="lead">Manage quick access links to marketing platforms</p>
    </div>
</div>';

include('../includes/marketing-nav.php');

echo '
<div class="container mt-4 mb-5 pb-5">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Platform Links</h5>
                    <a href="/staff/marketing/platform-create.php" class="btn btn-primary">
                        <i class="bi bi-plus"></i> Create Platform
                    </a>
                </div>
                <div class="card-body">';

if (empty($platforms)) {
    echo '
                    <p class="text-muted">No platform links configured. Add your first platform using the form.</p>';
} else {
    echo '
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th width="40">Icon</th>
                                    <th>Platform <small class="text-muted">(click to manage)</small></th>
                                    <th>Description</th>
                                    <th width="100">Credentials</th>
                                    <th width="80">Order</th>
                                </tr>
                            </thead>
                            <tbody>';
    
    foreach ($platforms as $platform) {
        $platform_data = json_decode($platform['tags'], true) ?: [];
        echo '
                                <tr>
                                    <td class="text-center">';
        
        if (!empty($platform_data['icon'])) {
            echo '
                                        <i class="' . htmlspecialchars($platform_data['icon']) . ' fa-lg"></i>';
        } else {
            echo '
                                        <i class="bi bi-link"></i>';
        }
        
        echo '
                                    </td>
                                    <td>
                                        <a href="/staff/marketing/platform-manage.php?platform_id=' . $platform['id'] . '" class="text-decoration-none">
                                            <strong>' . htmlspecialchars($platform['display_name']) . '</strong>
                                        </a><br>
                                        <small class="text-muted">
                                            <a href="' . htmlspecialchars($platform_data['url'] ?? '#') . '" target="_blank" class="text-muted">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                                ' . htmlspecialchars(substr($platform_data['url'] ?? '', 0, 40)) . 
                                                (strlen($platform_data['url'] ?? '') > 40 ? '...' : '') . '
                                            </a>
                                        </small>
                                    </td>
                                    <td>
                                        <small>' . htmlspecialchars($platform['description']) . '</small>
                                    </td>
                                    <td class="text-center">';
        
        // Check for credentials using direct reference
        $credential_id = $platform_data['credential_id'] ?? null;
        $has_credentials = false;
        
        if ($credential_id) {
            // Direct reference exists, verify it's still valid
            foreach ($credentials as $cred) {
                if ($cred['id'] == $credential_id) {
                    $has_credentials = true;
                    break;
                }
            }
        } else {
            // Fallback: Check for credentials using platform URL or name (for existing platforms)
            $platform_url = $platform_data['url'] ?? '';
            $platform_name = $platform['display_name'];
            
            foreach ($credentials as $cred) {
                if (strpos($platform_url, $cred['host']) !== false || 
                    stripos($platform_name, $cred['name']) !== false ||
                    stripos($cred['name'], $platform_name) !== false) {
                    $has_credentials = true;
                    break;
                }
            }
        }
        
        if ($has_credentials) {
            echo '<span class="badge bg-success"><i class="bi bi-key"></i> Valid</span>';
        } else {
            echo '<span class="badge bg-warning"><i class="bi bi-exclamation-triangle"></i> Missing</span>';
        }
        
        echo '
                                    </td>
                                    <td class="text-center">
                                        ' . $platform['rank'] . '
                                    </td>
                                </tr>';
    }
    
    echo '
                            </tbody>
                        </table>
                    </div>';
}

echo '
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <button class="btn btn-link p-0 text-decoration-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#commonPlatforms" aria-expanded="false">
                            <i class="bi bi-chevron-right"></i> Common Marketing Platforms
                        </button>
                    </h5>
                </div>
                <div class="collapse" id="commonPlatforms">
                    <div class="card-body">
                    <div class="row small">
                        <div class="col-md-6">
                            <h6>Social Media</h6>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-facebook text-primary"></i> Facebook Ads Manager</li>
                                <li><i class="bi bi-instagram text-danger"></i> Instagram Business</li>
                                <li><i class="bi bi-twitter text-info"></i> Twitter Ads</li>
                                <li><i class="bi bi-linkedin text-primary"></i> LinkedIn Campaign Manager</li>
                                <li><i class="bi bi-tiktok"></i> TikTok Ads Manager</li>
                                <li><i class="bi bi-youtube text-danger"></i> YouTube Studio</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Advertising & Analytics</h6>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-google text-primary"></i> Google Ads</li>
                                <li><i class="bi bi-graph-up text-success"></i> Google Analytics</li>
                                <li><i class="bi bi-envelope-fill text-info"></i> Mailchimp</li>
                                <li><i class="bi bi-envelope-fill text-warning"></i> Constant Contact</li>
                                <li><i class="bi bi-megaphone-fill text-danger"></i> HubSpot</li>
                                <li><i class="bi bi-bar-chart-fill text-primary"></i> Hootsuite</li>
                            </ul>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>