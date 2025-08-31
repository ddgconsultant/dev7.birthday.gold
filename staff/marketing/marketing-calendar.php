<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Marketing Calendar";

// Get current month or specified month
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$month_time = strtotime($month . '-01');
$prev_month = date('Y-m', strtotime('-1 month', $month_time));
$next_month = date('Y-m', strtotime('+1 month', $month_time));

// Get first and last day of the month
$first_day = date('Y-m-01', $month_time);
$last_day = date('Y-m-t', $month_time);
$days_in_month = date('t', $month_time);
$first_weekday = date('w', strtotime($first_day));

// Get campaigns for this month
$campaigns_sql = "SELECT * FROM bg_content 
                 WHERE category = 'marketing' 
                 AND type = 'campaign'
                 AND (
                     (publish_dt >= :start AND publish_dt <= :end) 
                     OR (expire_dt >= :start2 AND expire_dt <= :end2)
                     OR (publish_dt <= :start3 AND (expire_dt >= :end3 OR expire_dt IS NULL))
                 )
                 ORDER BY publish_dt ASC";

$params = [
    'start' => $first_day, 
    'end' => $last_day . ' 23:59:59',
    'start2' => $first_day,
    'end2' => $last_day . ' 23:59:59',
    'start3' => $first_day,
    'end3' => $last_day . ' 23:59:59'
];

$campaigns = $database->getrows($campaigns_sql, $params);

// Organize campaigns by date
$campaigns_by_date = [];
foreach ($campaigns as $campaign) {
    $campaign_data = json_decode($campaign['tags'], true) ?: [];
    
    // Add to start date
    $start_date = date('Y-m-d', strtotime($campaign['publish_dt']));
    if (!isset($campaigns_by_date[$start_date])) {
        $campaigns_by_date[$start_date] = [];
    }
    $campaigns_by_date[$start_date][] = [
        'id' => $campaign['id'],
        'name' => $campaign['display_name'],
        'type' => 'start',
        'status' => $campaign['status'],
        'platforms' => $campaign_data['platforms'] ?? []
    ];
    
    // Add to end date if exists
    if ($campaign['expire_dt']) {
        $end_date = date('Y-m-d', strtotime($campaign['expire_dt']));
        if (!isset($campaigns_by_date[$end_date])) {
            $campaigns_by_date[$end_date] = [];
        }
        $campaigns_by_date[$end_date][] = [
            'id' => $campaign['id'],
            'name' => $campaign['display_name'],
            'type' => 'end',
            'status' => $campaign['status'],
            'platforms' => $campaign_data['platforms'] ?? []
        ];
    }
}

// Get upcoming campaigns
$upcoming_sql = "SELECT * FROM bg_content 
                WHERE category = 'marketing' 
                AND type = 'campaign'
                AND publish_dt > NOW()
                ORDER BY publish_dt ASC
                LIMIT 5";

$upcoming = $database->getrows($upcoming_sql);

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
    padding-bottom: 50px !important;
}

.calendar-container {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.calendar-header {
    background: var(--bs-secondary);
    color: #333;
    padding: 1.5rem;
    text-align: center;
}

.calendar-header h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.calendar-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
}

.calendar-nav a {
    color: #333;
    text-decoration: none;
    padding: 0.5rem 1rem;
    background: rgba(0,0,0,0.1);
    border-radius: 0.25rem;
    transition: all 0.3s;
    font-weight: 600;
}

.calendar-nav a:hover {
    background: rgba(0,0,0,0.2);
    color: #000;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    border-top: 1px solid #dee2e6;
}

.calendar-day-header {
    padding: 1rem;
    text-align: center;
    font-weight: 600;
    background: #f8f9fa;
    border-right: 1px solid #dee2e6;
    border-bottom: 2px solid #dee2e6;
}

.calendar-day-header:last-child {
    border-right: none;
}

.calendar-day {
    min-height: 120px;
    padding: 0.5rem;
    border-right: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
    background: white;
    position: relative;
}

.calendar-day:last-child {
    border-right: none;
}

.calendar-day:hover {
    background: #f8f9fa;
}

.calendar-day.other-month {
    background: #fafbfc;
    color: #adb5bd;
}

.calendar-day.today {
    background: #fff3cd;
}

.calendar-day-number {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.calendar-event {
    font-size: 0.75rem;
    padding: 0.2rem 0.4rem;
    margin-bottom: 0.25rem;
    border-radius: 0.2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
}

.calendar-event.start {
    background: #d1ecf1;
    border-left: 3px solid #0c5460;
}

.calendar-event.end {
    background: #f8d7da;
    border-left: 3px solid #721c24;
}

.calendar-event:hover {
    opacity: 0.8;
}

.upcoming-campaigns {
    border-left: 4px solid #667eea;
}

.campaign-item {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    transition: background 0.2s;
}

.campaign-item:hover {
    background: #f8f9fa;
}

.campaign-item:last-child {
    border-bottom: none;
}

.legend {
    display: flex;
    gap: 2rem;
    justify-content: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 0.5rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.legend-box {
    width: 20px;
    height: 20px;
    border-radius: 0.2rem;
}

.legend-box.start {
    background: #d1ecf1;
    border-left: 3px solid #0c5460;
}

.legend-box.end {
    background: #f8d7da;
    border-left: 3px solid #721c24;
}

@media (max-width: 768px) {
    .calendar-day {
        min-height: 80px;
        padding: 0.25rem;
    }
    
    .calendar-event {
        font-size: 0.65rem;
    }
    
    .calendar-day-header {
        padding: 0.5rem;
        font-size: 0.875rem;
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="content-header-staff compact">
    <div class="container text-center">
        <h1><i class="fas fa-calendar-alt"></i> Marketing Calendar</h1>
        <p class="lead">Campaign schedule overview</p>
    </div>
</div>

<?php include('../includes/marketing-nav.php'); ?>

<div class="container mt-4 mb-5 pb-5">
    <div class="row">
        <div class="col-lg-9">
            <!-- Calendar -->
            <div class="calendar-container mb-4">
                <div class="calendar-header">
                    <h3><?= date('F Y', $month_time) ?></h3>
                    <div class="calendar-nav">
                        <a href="?month=<?= $prev_month ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <a href="?month=<?= date('Y-m') ?>">Today</a>
                        <a href="?month=<?= $next_month ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="calendar-grid">
                    <!-- Day headers -->
                    <div class="calendar-day-header">Sun</div>
                    <div class="calendar-day-header">Mon</div>
                    <div class="calendar-day-header">Tue</div>
                    <div class="calendar-day-header">Wed</div>
                    <div class="calendar-day-header">Thu</div>
                    <div class="calendar-day-header">Fri</div>
                    <div class="calendar-day-header">Sat</div>
                    
                    <?php
                    // Add empty cells for days before month starts
                    for ($i = 0; $i < $first_weekday; $i++) {
                        $prev_month_day = date('j', strtotime("-" . ($first_weekday - $i) . " days", $month_time));
                        echo '<div class="calendar-day other-month">';
                        echo '<div class="calendar-day-number">' . $prev_month_day . '</div>';
                        echo '</div>';
                    }
                    
                    // Add days of the month
                    for ($day = 1; $day <= $days_in_month; $day++) {
                        $current_date = $month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                        $is_today = $current_date == date('Y-m-d');
                        
                        echo '<div class="calendar-day' . ($is_today ? ' today' : '') . '">';
                        echo '<div class="calendar-day-number">' . $day . '</div>';
                        
                        // Add campaigns for this day
                        if (isset($campaigns_by_date[$current_date])) {
                            foreach ($campaigns_by_date[$current_date] as $event) {
                                echo '<div class="calendar-event ' . $event['type'] . '" 
                                      onclick="window.location.href=\'/staff/marketing-view.php?id=' . $event['id'] . '\'" 
                                      title="' . htmlspecialchars($event['name']) . '">';
                                echo ($event['type'] == 'start' ? '▶ ' : '■ ') . htmlspecialchars($event['name']);
                                echo '</div>';
                            }
                        }
                        
                        echo '</div>';
                    }
                    
                    // Add empty cells for days after month ends
                    $last_weekday = date('w', strtotime($last_day));
                    for ($i = $last_weekday + 1, $next_day = 1; $i <= 6; $i++, $next_day++) {
                        echo '<div class="calendar-day other-month">';
                        echo '<div class="calendar-day-number">' . $next_day . '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Legend -->
            <div class="legend mb-4">
                <div class="legend-item">
                    <div class="legend-box start"></div>
                    <span>Campaign Start</span>
                </div>
                <div class="legend-item">
                    <div class="legend-box end"></div>
                    <span>Campaign End</span>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0 text-white">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <a href="/staff/marketing-edit.php" class="btn btn-success w-100 mb-2">
                        <i class="fas fa-plus"></i> New Campaign
                    </a>
                    <a href="/staff/marketing-campaigns.php" class="btn btn-outline-primary w-100">
                        <i class="fas fa-list"></i> All Campaigns
                    </a>
                </div>
            </div>
            
            <!-- Upcoming Campaigns -->
            <div class="card upcoming-campaigns">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-clock"></i> Upcoming Campaigns</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($upcoming)): ?>
                        <p class="text-muted p-3 mb-0">No upcoming campaigns</p>
                    <?php else: ?>
                        <?php foreach ($upcoming as $campaign): ?>
                            <?php $campaign_data = json_decode($campaign['tags'], true) ?: []; ?>
                            <div class="campaign-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?= htmlspecialchars($campaign['display_name']) ?></strong>
                                        <div class="small text-muted">
                                            <i class="fas fa-calendar"></i> 
                                            <?= date('M j, Y', strtotime($campaign['publish_dt'])) ?>
                                        </div>
                                        <?php if (!empty($campaign_data['platforms'])): ?>
                                            <div class="mt-1">
                                                <?php foreach ($campaign_data['platforms'] as $platform): ?>
                                                    <span class="badge bg-light text-dark" style="font-size: 0.7rem;">
                                                        <?= htmlspecialchars($platform) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <a href="/staff/marketing-view.php?id=<?= $campaign['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Month Stats -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-bar"></i> This Month</h6>
                </div>
                <div class="card-body">
                    <?php
                    $month_campaigns = 0;
                    $month_budget = 0;
                    foreach ($campaigns as $campaign) {
                        $month_campaigns++;
                        $campaign_data = json_decode($campaign['tags'], true) ?: [];
                        $month_budget += $campaign_data['budget'] ?? 0;
                    }
                    ?>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Campaigns:</span>
                            <strong><?= $month_campaigns ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Total Budget:</span>
                            <strong>$<?= number_format($month_budget, 0) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>