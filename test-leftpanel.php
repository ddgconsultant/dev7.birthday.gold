<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check login
if (!$account->isloggedin()) {
    header('Location: /login');
    exit;
}

$pagetitle = "Test Left Panel";
$bodycontentclass = '';
$additionalstyles = '';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container-fluid mt-4">
    <div class="row">
        <?php
        // Include the left panel component
        $lefpanelcontent = [];
        $lefpanelcontent['body_class'] = 'container-fluid';
        $lefpanelcontent['panel_class'] = 'col-md-3';
        $lefpanelcontent['prepanel'] = '';
        $lefpanelcontent['postpanel'] = '';
        
        include($dir['core_components_v7'] . '/bg_user_leftpanel.inc');
        ?>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <h2>Test Content Area</h2>
                    <p>This is a test page to view the updated left panel design.</p>
                    <p>The left panel should now have:</p>
                    <ul>
                        <li>A clean card design with rounded corners</li>
                        <li>Better section separation with borders</li>
                        <li>Improved header styling</li>
                        <li>Consistent spacing and alignment</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>