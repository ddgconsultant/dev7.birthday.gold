<?php
// Staff Birthday Viewer - Pretty JSON display
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Staff access is handled by site-controller.php
$pagetitle = "Staff Birthday Viewer";

// Fetch birthday data
$json_url = "https://dev7.birthday.gold/admin_actions/scheduler--staffbirthdayalerter.php?listall=1&json=1";
$json_data = file_get_contents($json_url);
$birthday_data = json_decode($json_data, true);

$additionalstyles = '
<style>
/* Hide skip link */
.sr-only, .sr-only-focusable:not(:focus) {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0,0,0,0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}

body { 
    margin-bottom: 100px !important; 
}

.birthday-card {
    border-left: 4px solid #6c757d;
    transition: transform 0.2s;
    background: #f8f9fa;
}
.birthday-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.birthday-today {
    border-left-color: #dc3545;
    background: #fff5f5;
}
.birthday-week {
    border-left-color: #ffc107;
    background: #fffbf0;
}
.birthday-month {
    border-left-color: #28a745;
    background: #f0fff4;
}

.json-viewer {
    background: #f4f4f4;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    max-height: 600px;
    overflow-y: auto;
}

.month-section {
    margin-bottom: 2rem;
}

.birthday-icon {
    font-size: 2rem;
    margin-right: 10px;
}

.age-badge {
    background: #6c757d;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.85rem;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Staff Header Section -->
<div class="content-header-staff">
    <div class="container text-center">
        <h1><i class="fas fa-birthday-cake"></i> Staff Birthday Viewer</h1>
        <p class="lead">Complete staff birthday information dashboard</p>
        <div class="stats">
            <div class="stat-item">
                <span class="stat-number"><?= $birthday_data['statistics']['total_staff'] ?></span>
                <span class="stat-label">Total Staff</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= $birthday_data['statistics']['birthdays_today'] ?></span>
                <span class="stat-label">Today</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= $birthday_data['statistics']['birthdays_this_week'] ?></span>
                <span class="stat-label">This Week</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= $birthday_data['statistics']['birthdays_next_30_days'] ?></span>
                <span class="stat-label">Next 30 Days</span>
            </div>
        </div>
    </div>
</div>

<div class="container my-4">
    <!-- Action Buttons -->
    <div class="row mb-4">
        <div class="col-12 text-end">
            <button class="btn btn-primary" onclick="toggleJSON()">
                <i class="fas fa-code"></i> Toggle JSON
            </button>
            <a href="/staff/" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>


    <!-- Staff Birthday Cards -->
    <div class="row mb-4">
        <div class="col-12">
            <h3>All Staff Birthdays</h3>
        </div>
        <?php foreach ($birthday_data['staff_members'] as $staff): ?>
            <?php 
            $card_class = 'birthday-card';
            if ($staff['days_until'] == 0) {
                $card_class .= ' birthday-today';
            } elseif ($staff['days_until'] <= 7) {
                $card_class .= ' birthday-week';
            } elseif ($staff['days_until'] <= 30) {
                $card_class .= ' birthday-month';
            }
            // Otherwise stays gray (default birthday-card)
            ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card <?= $card_class ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <span class="birthday-icon">
                                <?php if ($staff['days_until'] == 0): ?>
                                    🎂
                                <?php elseif ($staff['days_until'] <= 7): ?>
                                    ⚠️
                                <?php elseif ($staff['days_until'] <= 30): ?>
                                    ✅
                                <?php else: ?>
                                    📅
                                <?php endif; ?>
                            </span>
                            <div class="flex-grow-1">
                                <h5 class="mb-1">
                                    <?= htmlspecialchars($staff['name']) ?>
                                    <span class="age-badge ms-2"><?= isset($staff['current_age_display']) ? $staff['current_age_display'] : $staff['current_age'] . ' years' ?></span>
                                </h5>
                                <p class="mb-1 text-muted">
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($staff['username']) ?>
                                    | <i class="fas fa-id-badge"></i> ID: <?= $staff['user_id'] ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-calendar"></i> <?= $staff['birthdate'] ?>
                                    (<?= $staff['birthdate_full'] ?>)
                                </p>
                                <p class="mb-0">
                                    <?php 
                                    $turning_display = isset($staff['turning_age_display']) ? $staff['turning_age_display'] : $staff['turning_age'] . ' years';
                                    ?>
                                    <?php if ($staff['days_until'] == 0): ?>
                                        <span class="badge bg-danger">TODAY! Turning <?= $turning_display ?></span>
                                    <?php elseif ($staff['days_until'] == 1): ?>
                                        <span class="badge bg-warning">Tomorrow - Turning <?= $turning_display ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">In <?= $staff['days_until'] ?> days - Turning <?= $turning_display ?></span>
                                    <?php endif; ?>
                                </p>
                                <small class="text-muted">
                                    Next: <?= date('M d, Y', strtotime($staff['next_birthday'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Birthdays by Month -->
    <div class="row mb-4">
        <div class="col-12">
            <h3>Birthdays by Month</h3>
            <div class="accordion" id="monthAccordion">
                <?php 
                $months = ['January', 'February', 'March', 'April', 'May', 'June', 
                          'July', 'August', 'September', 'October', 'November', 'December'];
                foreach ($months as $index => $month): 
                    if (isset($birthday_data['by_month'][$month])):
                ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#month<?= $index ?>">
                                <?= $month ?> (<?= count($birthday_data['by_month'][$month]) ?> birthdays)
                            </button>
                        </h2>
                        <div id="month<?= $index ?>" class="accordion-collapse collapse" data-bs-parent="#monthAccordion">
                            <div class="accordion-body">
                                <ul class="list-unstyled">
                                    <?php foreach ($birthday_data['by_month'][$month] as $person): ?>
                                        <li>
                                            <i class="fas fa-birthday-cake text-primary"></i>
                                            <?= htmlspecialchars($person['name']) ?> - <?= $month ?> <?= $person['day'] ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
    </div>

    <!-- JSON Viewer (Hidden by default) -->
    <div class="row mb-4" id="jsonViewer" style="display: none;">
        <div class="col-12">
            <h3>Raw JSON Data</h3>
            <pre class="json-viewer"><?= json_encode($birthday_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
        </div>
    </div>

    <!-- API Links -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">API Endpoints</h5>
                </div>
                <div class="card-body">
                    <p>Access birthday data programmatically:</p>
                    <ul>
                        <li>
                            <code>
                                <a href="/admin_actions/scheduler--staffbirthdayalerter.php?listall=1&json=1" target="_blank">
                                    /admin_actions/scheduler--staffbirthdayalerter.php?listall=1&json=1
                                </a>
                            </code> - All staff birthdays in JSON
                        </li>
                        <li>
                            <code>
                                <a href="/admin_actions/scheduler--staffbirthdayalerter.php?debug=1" target="_blank">
                                    /admin_actions/scheduler--staffbirthdayalerter.php?debug=1
                                </a>
                            </code> - Debug mode (text output)
                        </li>
                        <li>
                            <code>
                                <a href="/admin_actions/scheduler--staffbirthdayalerter.php?listall=1" target="_blank">
                                    /admin_actions/scheduler--staffbirthdayalerter.php?listall=1
                                </a>
                            </code> - Text table format
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleJSON() {
    const viewer = document.getElementById('jsonViewer');
    if (viewer.style.display === 'none') {
        viewer.style.display = 'block';
    } else {
        viewer.style.display = 'none';
    }
}
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>