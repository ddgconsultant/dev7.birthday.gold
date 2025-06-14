<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');

$results = $user_reward_results;
$showExpired = !isset($_GET['active']) || $_GET['active'] !== '1';

// Separate rewards into current and future
$current_rewards = [];
$future_rewards = [];
$today = new DateTime();

foreach ($results as $reward) {
    $availability_date = new DateTime($reward['availability_from_date']);
    if ($availability_date <= $today) {
        $current_rewards[] = $reward;
    } else {
        $future_rewards[] = $reward;
    }
}

echo '
<div class="container main-content">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="mb-0">Your Rewards 
            <span class="badge rounded-pill bg-success fs-3 align-middle" style="vertical-align: baseline; margin-bottom: 3px;">' . count($current_rewards) . '</span>
        </h1>
        <a href="/myaccount/redeem" class="btn btn-primary">View Latest Rewards</a>
    </div>

    <!-- Currently Available Rewards -->
    <div class="mt-5">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Currently Available Rewards</h2>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="toggleExpired" ' . ($showExpired ? 'checked' : '') . '>
                <label class="form-check-label" for="toggleExpired">Show Expired</label>
            </div>
        </div>

        <div class="row fw-bold py-2 bg-light border-bottom mt-3">
            <div class="col-4">Reward</div>
            <div class="col-5">Description</div>
            <div class="col-2">Expiration Date</div>
            <div class="col-1 text-center">Action</div>
        </div>';

// Display current rewards
foreach ($current_rewards as $company) {
    if (!empty($company['expiration_date'])) {
        $expiration_date = new DateTime($company['expiration_date']);
        $expiratationdate_tag = $expiration_date->format('M j, Y');
    } else {
        $expiratationdate_tag = '<span class="badge bg-light" style="color:#999">Never</span>';
    }
    
    $availability_tag = $app->getAvailabilityTag($company['availability_from_date'], $company['expiration_date']);
    $is_expired = $availability_tag['flag'] == 'expired';
    
    echo '
    <div class="row py-2 border-bottom reward-row" data-expired="' . ($is_expired ? 'true' : 'false') . '">
        <div class="col-4">
            <img src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" alt="' . htmlspecialchars($company['company_name']) . ' Logo" style="height: 40px;" class="me-3">
            <span class="fw-bold">' . htmlspecialchars($company['company_name']) . '</span>
        </div>
        <div class="col-5">' . (!empty($company['reward_name']) ? htmlspecialchars($company['reward_name']) : '<span class="text-muted">Enjoy your '.$company['category'].' reward' .'</span>') . '</div>
        <div class="col-2 text-center">' . (!empty($availability_tag['expiration']) ? $availability_tag['expiration'] : $expiratationdate_tag) . '</div>
        <div class="col-1 text-center">';
    
    if (!$is_expired) {
        echo '<a href="/myaccount/redeem-details?id=' . $qik->encodeId($company['reward_id']) . '" class="btn btn-primary btn-sm">Redeem</a>';
    }
    echo '</div></div>';
}

// Coming Soon section
if (!empty($future_rewards)) {
    echo '
    <div class="mt-5">
        <h2>Coming Soon</h2>
        <div class="row fw-bold py-2 bg-light border-bottom mt-3">
            <div class="col-4">Reward</div>
            <div class="col-5">Description</div>
            <div class="col-3">Available Starting</div>
        </div>';

    foreach ($future_rewards as $company) {
        $availability_date = new DateTime($company['availability_from_date']);
        echo '
        <div class="row py-2 border-bottom">
            <div class="col-4">
                <img src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" alt="' . htmlspecialchars($company['company_name']) . ' Logo" style="height: 40px;" class="me-3">
                <span class="fw-bold">' . htmlspecialchars($company['company_name']) . '</span>
            </div>
            <div class="col-5">' . (!empty($company['reward_name']) ? htmlspecialchars($company['reward_name']) : '<span class="text-muted">Enjoy your '.$company['category'].' reward' .'</span>') . '</div>
            <div class="col-3">' . $availability_date->format('M j, Y') . '</div>
        </div>';
    }
    echo '</div>';
}
?>
    <div class="text-center mt-4">
        <a href="/myaccount/redeem" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>
</div></div></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var showExpired = <?php echo $showExpired ? 'true' : 'false' ?>;
    var rows = document.querySelectorAll('.reward-row');
    
    // Initial visibility setup
    rows.forEach(function(row) {
        if (row.getAttribute('data-expired') === 'true' && !showExpired) {
            row.style.display = 'none';
        }
    });
    
    // Toggle event handler
    document.getElementById('toggleExpired').addEventListener('change', function() {
        var showExpired = this.checked;
        var rows = document.querySelectorAll('.reward-row');
        
        // Update URL without refreshing page
        var url = new URL(window.location.href);
        if (!showExpired) {
            url.searchParams.set('active', '1');
        } else {
            url.searchParams.delete('active');
        }
        window.history.pushState({}, '', url);

        // Update visibility
        rows.forEach(function(row) {
            if (row.getAttribute('data-expired') === 'true') {
                row.style.display = showExpired ? '' : 'none';
            }
        });
    });
});
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>