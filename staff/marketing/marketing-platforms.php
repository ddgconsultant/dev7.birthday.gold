<?php
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
        
        $platform_data = [
            'url' => $url,
            'icon' => $icon
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
        
        $system->addmessage('success', 'Platform added successfully!');
        header('Location: /staff/marketing-platforms.php');
        exit;
        
    } elseif ($action == 'delete') {
        $platform_id = intval($_POST['platform_id']);
        $database->query("DELETE FROM bg_content WHERE id = :id AND type = 'platform_link'", ['id' => $platform_id]);
        $system->addmessage('success', 'Platform deleted successfully!');
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

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
    padding-bottom: 50px !important;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="fas fa-link"></i> Marketing Platforms</h1>
        <p class="lead">Manage quick access links to marketing platforms</p>
    </div>
</div>

<?php include('../includes/marketing-nav.php'); ?>

<div class="container mt-4 mb-5 pb-5">
    <div class="row">
        <div class="col-lg-8">
            <!-- Platform List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Platform Links</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($platforms)): ?>
                        <p class="text-muted">No platform links configured. Add your first platform using the form.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="40">Icon</th>
                                        <th>Platform</th>
                                        <th>Description</th>
                                        <th width="80">Order</th>
                                        <th width="100">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($platforms as $platform): ?>
                                        <?php $platform_data = json_decode($platform['tags'], true) ?: []; ?>
                                        <tr>
                                            <td class="text-center">
                                                <?php if (!empty($platform_data['icon'])): ?>
                                                    <i class="<?= htmlspecialchars($platform_data['icon']) ?> fa-lg"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-link"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($platform['display_name']) ?></strong><br>
                                                <small class="text-muted">
                                                    <a href="<?= htmlspecialchars($platform_data['url'] ?? '#') ?>" target="_blank">
                                                        <?= htmlspecialchars(substr($platform_data['url'] ?? '', 0, 50)) ?>
                                                        <?= strlen($platform_data['url'] ?? '') > 50 ? '...' : '' ?>
                                                    </a>
                                                </small>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($platform['description']) ?></small>
                                            </td>
                                            <td class="text-center">
                                                <?= $platform['rank'] ?>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Delete this platform?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="platform_id" value="<?= $platform['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Common Platforms Reference -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Common Marketing Platforms</h5>
                </div>
                <div class="card-body">
                    <div class="row small">
                        <div class="col-md-6">
                            <h6>Social Media</h6>
                            <ul class="list-unstyled">
                                <li><i class="fab fa-facebook text-primary"></i> Facebook Ads Manager</li>
                                <li><i class="fab fa-instagram text-danger"></i> Instagram Business</li>
                                <li><i class="fab fa-twitter text-info"></i> Twitter Ads</li>
                                <li><i class="fab fa-linkedin text-primary"></i> LinkedIn Campaign Manager</li>
                                <li><i class="fab fa-tiktok"></i> TikTok Ads Manager</li>
                                <li><i class="fab fa-youtube text-danger"></i> YouTube Studio</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Advertising & Analytics</h6>
                            <ul class="list-unstyled">
                                <li><i class="fab fa-google text-primary"></i> Google Ads</li>
                                <li><i class="fas fa-chart-line text-success"></i> Google Analytics</li>
                                <li><i class="fas fa-mail-bulk text-info"></i> Mailchimp</li>
                                <li><i class="fas fa-envelope text-warning"></i> Constant Contact</li>
                                <li><i class="fas fa-bullhorn text-danger"></i> HubSpot</li>
                                <li><i class="fas fa-chart-bar text-primary"></i> Hootsuite</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Add New Platform -->
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 text-white">Add New Platform</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="display_name" class="form-label">Platform Name *</label>
                            <input type="text" class="form-control" id="display_name" name="display_name" required>
                            <small class="text-muted">e.g., Facebook Ads Manager</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="url" class="form-label">URL *</label>
                            <input type="url" class="form-control" id="url" name="url" required>
                            <small class="text-muted">Full URL including https://</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description" maxlength="100">
                            <small class="text-muted">Brief description (optional)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="icon" class="form-label">Icon Class</label>
                            <input type="text" class="form-control" id="icon" name="icon" placeholder="fab fa-facebook">
                            <small class="text-muted">
                                FontAwesome icon class<br>
                                <a href="https://fontawesome.com/icons" target="_blank">Browse icons</a>
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="rank" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="rank" name="rank" value="50" min="0" max="999">
                            <small class="text-muted">Lower numbers appear first</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus"></i> Add Platform
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>