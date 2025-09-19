<?php
/**
 * Business Detail Page
 * Shows comprehensive information about a business/company
 * Allows users to enroll or manage their enrollment
 * 
 * Supports admin preview mode for testing newsletter CTAs
 */

$addClasses[] = 'enrollment';
$addClasses[] = 'business-detail';

// Check for preview mode BEFORE site-controller
session_start();
$is_preview_mode = isset($_SESSION['is_preview_mode']) && $_SESSION['is_preview_mode'];
$preview_user_name = $_SESSION['preview_user_name'] ?? '';

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get company ID from URL
$company_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$company_id) {
    header('Location: /myaccount/enrollment-picker.php');
    exit;
}

// Get company details
$company = $database->getrow("
    SELECT c.*, 
           a.description as logo_filename
    FROM bg_companies c
    LEFT JOIN bg_company_attributes a ON c.company_id = a.company_id 
        AND a.category = 'company_logos' AND a.`grouping` = 'primary_logo'
    WHERE c.company_id = :company_id
    AND c.status = 'finalized'
", ['company_id' => $company_id]);

if (!$company) {
    header('Location: /myaccount/enrollment-picker.php?error=notfound');
    exit;
}

// Check if user is already enrolled
$enrollment = null;
if (isset($current_user_data['user_id'])) {
    $enrollment = $database->getrow("
        SELECT * FROM bg_user_enrollments 
        WHERE user_id = :user_id 
        AND company_id = :company_id
        AND status = 'success'
    ", [
        'user_id' => $current_user_data['user_id'],
        'company_id' => $company_id
    ]);
}

// Get all company locations
$locations = $database->getrows("
    SELECT * FROM bg_company_locations 
    WHERE company_id = :company_id 
    AND status = 'active'
    ORDER BY city, state
", ['company_id' => $company_id]);

// Get company attributes (policies, terms, etc.)
$attributes = $database->getrows("
    SELECT * FROM bg_company_attributes 
    WHERE company_id = :company_id
    AND category != 'company_logos'
    ORDER BY category, `grouping`
", ['company_id' => $company_id]);

// Build logo URL (needed before POST handling)
$logo_url = null;
if (!empty($company['logo_filename'])) {
    $logo_url = '//cdn.birthday.gold/public/images/company_images/' . $company['company_id'] . '/' . $company['logo_filename'];
}

// Handle unenrollment only (enrollment now goes directly to enrollment-picker.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($current_user_data['user_id'])) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'unenroll' && $enrollment) {
        // Handle unenrollment directly here
        $database->query("
            UPDATE bg_user_enrollments 
            SET status = 'removed', 
                modify_dt = NOW() 
            WHERE user_id = :user_id 
            AND company_id = :company_id
        ", [
            'user_id' => $current_user_data['user_id'],
            'company_id' => $company_id
        ]);
        
        $enrollment = null;
        $success_message = "Successfully unenrolled from " . htmlspecialchars($company['company_name']) . ".";
    }
}

// Page title
$page_title = htmlspecialchars($company['company_name']) . ' - Birthday Rewards';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Show preview mode banner if in preview mode
if ($is_preview_mode): ?>
<div class="alert alert-warning mb-0" style="border-radius: 0; border-left: 5px solid #ff9800;">
    <div class="container">
        <strong><i class="bi bi-eye"></i> Newsletter Preview Mode Active</strong><br>
        Testing as: <?= htmlspecialchars($preview_user_name ?: 'User #' . ($current_user_data['user_id'] ?? '0')) ?><br>
        <small>This is a test preview session for Birthday Gold admins. Actions are simulated and not saved.</small>
        
        <?php if (isset($_GET['test']) && isset($_GET['preview'])): ?>
        <div class="mt-2">
            <span class="badge bg-info">Newsletter CTA Test Click</span>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="container mt-4">
    <!-- Back to enrollment picker -->
    <div class="mb-3">
        <a href="/myaccount/enrollment-picker" class="btn btn-link">
            <i class="bi bi-arrow-left"></i> Back to All Businesses
        </a>
    </div>
    
    <?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $success_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <!-- Company Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center">
                    <?php if ($logo_url): ?>
                        <img src="<?= htmlspecialchars($logo_url) ?>" 
                             alt="<?= htmlspecialchars($company['company_name']) ?>" 
                             class="img-fluid mb-3" 
                             style="max-height: 150px;">
                    <?php else: ?>
                        <div class="bg-light p-4 mb-3 rounded">
                            <i class="bi bi-building" style="font-size: 4rem; color: #ccc;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($current_user_data['user_id'])): ?>
                        <?php if ($enrollment): ?>
                            <form method="POST" class="mb-3">
                                <button type="submit" name="action" value="unenroll" class="btn btn-danger btn-lg w-100">
                                    <i class="bi bi-x-circle"></i> Unenroll
                                </button>
                                <small class="text-muted d-block mt-2">
                                    Enrolled since <?= date('M j, Y', strtotime($enrollment['create_dt'])) ?>
                                </small>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="/myaccount/enrollment-picker.php" class="mb-3">
                                <input type="hidden" name="action" value="enroll_from_detail">
                                <input type="hidden" name="company_id" value="<?= htmlspecialchars($company_id) ?>">
                                <input type="hidden" name="company_name" value="<?= htmlspecialchars($company['company_name']) ?>">
                                <input type="hidden" name="logo_url" value="<?= htmlspecialchars($logo_url ?? '') ?>">
                                <button type="submit" class="btn btn-success btn-lg w-100">
                                    <i class="bi bi-plus-circle"></i> Enroll Now
                                </button>
                                <small class="text-muted d-block mt-2">
                                    Join to receive birthday rewards!
                                </small>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-person-circle"></i> Login to Enroll
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-9">
                    <h1 class="mb-3">
                        <?= htmlspecialchars($company['company_name']) ?>
                        <?php if ($enrollment): ?>
                            <span class="badge bg-success ms-2">Enrolled</span>
                        <?php endif; ?>
                    </h1>
                    
                    <?php if ($company['display_category']): ?>
                        <p class="text-muted">
                            <i class="bi bi-tag"></i> <?= htmlspecialchars($company['display_category']) ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($company['description']): ?>
                        <div class="alert alert-info">
                            <h5><i class="bi bi-gift"></i> Birthday Offer:</h5>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($company['description'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($company['website'])): ?>
                        <p>
                            <i class="bi bi-globe"></i> 
                            <a href="<?= htmlspecialchars($company['website']) ?>" target="_blank" rel="noopener">
                                Visit Website
                            </a>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($company['phone'])): ?>
                        <p>
                            <i class="bi bi-telephone"></i> 
                            <a href="tel:<?= htmlspecialchars($company['phone']) ?>">
                                <?= htmlspecialchars($company['phone']) ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Locations -->
    <?php if (!empty($locations)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h3><i class="bi bi-geo-alt"></i> Locations</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($locations as $location): ?>
                <div class="col-md-6 mb-3">
                    <div class="border rounded p-3">
                        <?php if (isset($location['is_primary']) && $location['is_primary']): ?>
                            <span class="badge bg-primary mb-2">Primary Location</span>
                        <?php endif; ?>
                        
                        <h5><?= htmlspecialchars(($location['location_name'] ?? '') ?: $company['company_name']) ?></h5>
                        
                        <?php if (!empty($location['address'])): ?>
                            <p class="mb-1">
                                <i class="bi bi-geo"></i>
                                <?= htmlspecialchars($location['address']) ?><br>
                                <?= htmlspecialchars($location['city'] ?? '') ?>, 
                                <?= htmlspecialchars($location['state'] ?? '') ?> 
                                <?= htmlspecialchars($location['zip'] ?? '') ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['phone'])): ?>
                            <p class="mb-1">
                                <i class="bi bi-telephone"></i>
                                <a href="tel:<?= htmlspecialchars($location['phone']) ?>">
                                    <?= htmlspecialchars($location['phone']) ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['hours'])): ?>
                            <p class="mb-0">
                                <i class="bi bi-clock"></i>
                                <?= htmlspecialchars($location['hours']) ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['address'])): ?>
                            <div class="mt-2">
                                <a href="https://maps.google.com/?q=<?= urlencode($location['address'] . ' ' . ($location['city'] ?? '') . ' ' . ($location['state'] ?? '') . ' ' . ($location['zip'] ?? '')) ?>" 
                                   target="_blank" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-map"></i> Get Directions
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Additional Information -->
    <?php 
    $policies = array_filter($attributes, function($a) { return $a['category'] === 'policies'; });
    $terms = array_filter($attributes, function($a) { return $a['category'] === 'terms'; });
    $other = array_filter($attributes, function($a) { return !in_array($a['category'], ['policies', 'terms', 'company_logos']); });
    ?>
    
    <?php if (!empty($policies) || !empty($terms)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h3><i class="bi bi-info-circle"></i> Terms & Policies</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($terms)): ?>
                <h5>Terms & Conditions</h5>
                <ul>
                    <?php foreach ($terms as $term): ?>
                        <li><?= htmlspecialchars($term['description']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <?php if (!empty($policies)): ?>
                <h5>Policies</h5>
                <ul>
                    <?php foreach ($policies as $policy): ?>
                        <li>
                            <?php if ($policy['name']): ?>
                                <strong><?= htmlspecialchars($policy['name']) ?>:</strong>
                            <?php endif; ?>
                            <?= htmlspecialchars($policy['description']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- How to Redeem -->
    <div class="card mb-4">
        <div class="card-header">
            <h3><i class="bi bi-question-circle"></i> How to Redeem Your Birthday Reward</h3>
        </div>
        <div class="card-body">
            <ol>
                <li>Make sure you're enrolled in <?= htmlspecialchars($company['company_name']) ?>'s birthday program</li>
                <li>Wait for your birthday month to arrive</li>
                <li>Check your email for your birthday reward notification</li>
                <li>Visit any <?= htmlspecialchars($company['company_name']) ?> location</li>
                <li>Present your birthday reward email or mention you're a Birthday Gold member</li>
                <li>Enjoy your special birthday treat!</li>
            </ol>
            
            <?php if (!empty($company['redemption_instructions'])): ?>
                <div class="alert alert-warning">
                    <strong>Special Instructions:</strong><br>
                    <?= nl2br(htmlspecialchars($company['redemption_instructions'])) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Share -->
    <div class="card mb-4">
        <div class="card-body text-center">
            <h5>Share this Birthday Reward</h5>
            <div class="btn-group" role="group">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('https://birthday.gold/myaccount/business-detail.php?id=' . $company_id) ?>" 
                   target="_blank" 
                   class="btn btn-outline-primary">
                    <i class="bi bi-facebook"></i> Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?text=<?= urlencode('Check out this birthday reward from ' . $company['company_name'] . '!') ?>&url=<?= urlencode('https://birthday.gold/myaccount/business-detail.php?id=' . $company_id) ?>" 
                   target="_blank" 
                   class="btn btn-outline-info">
                    <i class="bi bi-twitter"></i> Twitter
                </a>
                <a href="mailto:?subject=<?= urlencode('Birthday Reward from ' . $company['company_name']) ?>&body=<?= urlencode('Check out this birthday reward: https://birthday.gold/myaccount/business-detail.php?id=' . $company_id) ?>" 
                   class="btn btn-outline-secondary">
                    <i class="bi bi-envelope"></i> Email
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.business-detail .card {
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.business-detail .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px 10px 0 0;
}

.business-detail .btn-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    border: none;
}

.business-detail .btn-danger {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    border: none;
}
</style>

<?php
// Auto-submit form removed - enrollment now posts directly to enrollment-picker.php

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>