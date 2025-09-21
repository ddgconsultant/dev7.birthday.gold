<?php
/**
 * Preview Landing Page
 * Shows confirmation that test/preview tracking links are working
 */
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$type = $_GET['type'] ?? '';
$company_id = $_GET['company'] ?? 0;
$test = $_GET['test'] ?? 0;
$preview = $_GET['preview'] ?? 0;

$pagetitle = "Newsletter Preview Test";
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body text-center p-5">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    
                    <h2 class="mt-4 mb-3">Preview Link Test Successful!</h2>
                    
                    <div class="alert alert-info">
                        <strong>Test Mode Active</strong><br>
                        This is a preview/test tracking link. In production, this would:
                    </div>
                    
                    <ul class="text-start mb-4">
                        <?php if ($type === 'cta' && $company_id > 0): ?>
                            <li>Track the CTA click for Company ID: <?= htmlspecialchars($company_id) ?></li>
                            <li>Redirect to the company's enrollment page</li>
                            <li>Record analytics for the email campaign</li>
                        <?php elseif ($type === 'unsubscribe'): ?>
                            <li>Track the unsubscribe request</li>
                            <li>Redirect to the unsubscribe confirmation page</li>
                        <?php elseif ($type === 'view'): ?>
                            <li>Track the "view in browser" click</li>
                            <li>Display the full newsletter in the browser</li>
                        <?php else: ?>
                            <li>Track the click event</li>
                            <li>Redirect to the appropriate destination</li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="p-3 bg-light rounded mb-4">
                        <h5>Debug Information:</h5>
                        <small class="text-muted">
                            Type: <?= htmlspecialchars($type ?: 'none') ?><br>
                            Company: <?= htmlspecialchars($company_id ?: 'none') ?><br>
                            Mode: TEST-PREVIEW<br>
                            User: Test User (ID: 0)
                        </small>
                    </div>
                    
                    <?php if ($company_id > 0): ?>
                        <a href="/myaccount/business-detail.php?id=<?= htmlspecialchars($company_id) ?>" 
                           class="btn btn-primary">
                            <i class="bi bi-building"></i> View Company Details
                        </a>
                    <?php endif; ?>
                    
                    <a href="/myaccount/marketing/newsletter-edit" 
                       class="btn btn-secondary ms-2">
                        <i class="bi bi-arrow-left"></i> Back to Newsletter Editor
                    </a>
                </div>
            </div>
            
            <div class="text-center mt-4 text-muted">
                <small>
                    <i class="bi bi-info-circle"></i> 
                    Test clicks are logged separately and do not affect production analytics.
                </small>
            </div>
        </div>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>