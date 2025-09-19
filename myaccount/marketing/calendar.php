<?php
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Marketing Calendar";

// Get user's company context
$company_id = $current_user_data['company_id'] ?? 99;

// Handle consultant company switching via session
$active_company_id = $_SESSION['active_company_id'] ?? $company_id;

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

// Handle multi-company calendar view for staff/consultants
$view_mode = $_GET['view'] ?? 'single';
$company_filter = [];

if ($view_mode == 'all' && ($account->isstaff() || count($consultant_companies) > 1)) {
    // Get all companies user has access to
    if ($account->isstaff()) {
        $all_companies_sql = "SELECT DISTINCT company_id FROM bg_company_locations WHERE company_id > 0
                             UNION SELECT 0 as company_id
                             ORDER BY company_id ASC";
        $accessible_companies = $database->getrows($all_companies_sql);
    } else {
        $accessible_companies = $consultant_companies;
    }
    
    foreach ($accessible_companies as $comp) {
        $company_filter[] = $comp['company_id'];
    }
} else {
    // Single company view
    $company_filter = [$active_company_id];
}

// Get campaigns for this month - simplified query
$company_ids = implode(',', array_map('intval', $company_filter));
$campaigns_sql = "SELECT c.*, 
                         CASE WHEN c.company_id = 0 THEN 'Birthday Gold' 
                              ELSE CONCAT('Company #', c.company_id) END as company_name
                 FROM mk_campaigns c
                 WHERE c.company_id IN ($company_ids)
                 AND (start_date BETWEEN '$first_day' AND '$last_day' 
                      OR end_date BETWEEN '$first_day' AND '$last_day'
                      OR (start_date <= '$first_day' AND (end_date >= '$last_day' OR end_date IS NULL)))
                 ORDER BY start_date ASC";

$campaigns = $database->getrows($campaigns_sql);

// Get marketing activities - simplified query
$activities_sql = "SELECT a.*,
                          CASE WHEN a.company_id = 0 THEN 'Birthday Gold' 
                               ELSE CONCAT('Company #', a.company_id) END as company_name
                  FROM mk_activities a
                  WHERE a.company_id IN ($company_ids)
                  AND activity_date BETWEEN '$first_day 00:00:00' AND '$last_day 23:59:59'
                  ORDER BY activity_date ASC";

$activities = $database->getrows($activities_sql);

// Organize campaigns by date
$campaigns_by_date = [];
$activities_by_date = [];

foreach ($campaigns as $campaign) {
    // Add to start date
    if ($campaign['start_date']) {
        $start_date = $campaign['start_date'];
        if (!isset($campaigns_by_date[$start_date])) {
            $campaigns_by_date[$start_date] = [];
        }
        $campaigns_by_date[$start_date][] = [
            'id' => $campaign['campaign_id'],
            'name' => $campaign['campaign_name'],
            'type' => 'start',
            'status' => $campaign['status']
        ];
    }
    
    // Add to end date if exists
    if ($campaign['end_date']) {
        $end_date = $campaign['end_date'];
        if (!isset($campaigns_by_date[$end_date])) {
            $campaigns_by_date[$end_date] = [];
        }
        $campaigns_by_date[$end_date][] = [
            'id' => $campaign['campaign_id'],
            'name' => $campaign['campaign_name'],
            'type' => 'end',
            'status' => $campaign['status']
        ];
    }
}

// Organize activities by date
foreach ($activities as $activity) {
    $activity_date = date('Y-m-d', strtotime($activity['activity_date']));
    if (!isset($activities_by_date[$activity_date])) {
        $activities_by_date[$activity_date] = [];
    }
    $metadata = json_decode($activity['metadata'], true) ?: [];
    // Add encoded_id if campaign_id exists in metadata
    if (isset($metadata['campaign_id'])) {
        $metadata['encoded_id'] = $qik->encodeId($metadata['campaign_id']);
    }
    
    $activities_by_date[$activity_date][] = [
        'id' => $activity['activity_id'],
        'title' => $activity['activity_title'],
        'description' => $activity['activity_description'],
        'activity_type' => $activity['activity_type'],
        'metadata' => $metadata
    ];
}

$additionalstyles = '
<style>
body {
    margin-bottom: 100px !important;
}

.calendar-container {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.calendar-header {
    background: #f8f9fa;
    color: #333;
    padding: 1rem;
    font-weight: 600;
    text-align: center;
    border-bottom: 1px solid #dee2e6;
}

.calendar-nav {
    background: #ffffff;
    border-bottom: 1px solid #dee2e6;
    padding: 0.75rem 1rem;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    width: 100%;
    table-layout: fixed;
}

.calendar-day-header {
    background: #6c757d;
    color: white;
    padding: 0.75rem 0.5rem;
    text-align: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.calendar-day {
    min-height: 160px;
    width: 100%;
    border-right: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
    padding: 0.5rem;
    position: relative;
    overflow: hidden;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
}

.calendar-day:nth-child(7n) {
    border-right: none;
}

.calendar-day.today {
    background: #fff3cd;
}

.calendar-day.other-month {
    background: #f8f9fa;
    color: #6c757d;
}

.calendar-day-number {
    font-weight: 600;
    margin-bottom: 0.5rem;
    flex-shrink: 0;
}

.calendar-events-container {
    flex: 1;
    overflow: hidden;
}

.view-all-link {
    font-size: 0.7rem;
    color: #0d6efd;
    text-decoration: none;
    font-weight: 500;
    padding: 0.1rem 0.3rem;
    border-radius: 0.2rem;
    background: rgba(13, 110, 253, 0.1);
    text-align: center;
    display: block;
    margin-top: 0.2rem;
}

.view-all-link:hover {
    background: rgba(13, 110, 253, 0.2);
    color: #0d6efd;
    text-decoration: none;
}

.calendar-event {
    font-size: 0.75rem;
    padding: 0.2rem 0.4rem;
    margin-bottom: 0.25rem;
    border-radius: 0.2rem;
    cursor: pointer;
    max-width: 100%;
    word-wrap: break-word;
    line-height: 1.2;
    display: block;
}

.calendar-event.start {
    background: #d1ecf1;
    border-left: 3px solid #0c5460;
}

.calendar-event.end {
    background: #f8d7da;
    border-left: 3px solid #721c24;
}

.calendar-event.activity {
    background: #e2f4ff;
    border-left: 3px solid #0066cc;
}

.calendar-event.platform_created {
    background: #f0f9ff;
    border-left: 3px solid #0ea5e9;
}

.calendar-event.campaign_created {
    background: #fefce8;
    border-left: 3px solid #eab308;
}

.calendar-event.campaign_launched {
    background: #f0fdf4;
    border-left: 3px solid #22c55e;
}

.calendar-event:hover {
    opacity: 0.8;
}

.clickable-date {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.clickable-date:hover {
    background-color: #f8f9fa;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-dark">
    <div class="container text-center">
        <h1><i class="bi bi-calendar me-3"></i>Marketing Calendar</h1>
        <p class="lead">Track your marketing activities and campaign timelines</p>';

// Show company context
if ($active_company_id == 0) {
    echo '
        <div class="badge bg-primary fs-6 mt-2">
            <i class="bi bi-building me-1"></i>Birthday Gold (Internal Marketing)
        </div>';
} else {
    echo '
        <div class="badge bg-info fs-6 mt-2">
            <i class="bi bi-building me-1"></i>Company ID: ' . $active_company_id . '
        </div>';
}

echo '
    </div>
</div>';

// Include marketing tab navigation
include('nav.inc.php');

echo '
<div class="container mb-5">';

// Add view mode switcher for consultants/staff
if ($account->isstaff() || count($consultant_companies) > 1) {
    echo '
    <div class="row mb-3">
        <div class="col-12 text-end">
            <div class="btn-group" role="group">
                <a href="?view=single&month=' . $month . '" class="btn btn-' . ($view_mode == 'single' ? 'primary' : 'outline-primary') . ' btn-sm">
                    <i class="bi bi-building me-1"></i>Single Company
                </a>
                <a href="?view=all&month=' . $month . '" class="btn btn-' . ($view_mode == 'all' ? 'primary' : 'outline-primary') . ' btn-sm">
                    <i class="bi bi-buildings me-1"></i>All Companies
                </a>
            </div>
        </div>
    </div>';
}

echo '
    <div class="row">
        <div class="col-12">
            <div class="calendar-container">
                <div class="calendar-nav d-flex justify-content-between align-items-center">
                    <a href="?month=' . $prev_month . '" class="btn btn-outline-secondary">
                        <i class="bi bi-chevron-left"></i> ' . date('M Y', strtotime($prev_month . '-01')) . '
                    </a>
                    <h4 class="mb-0">' . date('F Y', $month_time) . '</h4>
                    <a href="?month=' . $next_month . '" class="btn btn-outline-secondary">
                        ' . date('M Y', strtotime($next_month . '-01')) . ' <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
                
                <div class="calendar-grid">
                    <div class="calendar-day-header">Sunday</div>
                    <div class="calendar-day-header">Monday</div>
                    <div class="calendar-day-header">Tuesday</div>
                    <div class="calendar-day-header">Wednesday</div>
                    <div class="calendar-day-header">Thursday</div>
                    <div class="calendar-day-header">Friday</div>
                    <div class="calendar-day-header">Saturday</div>';

// Add empty cells for days before month starts
for ($i = 0; $i < $first_weekday; $i++) {
    echo '<div class="calendar-day other-month"></div>';
}

// Add days of the month
for ($day = 1; $day <= $days_in_month; $day++) {
    $current_date = $month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
    $is_today = $current_date == date('Y-m-d');
    
    $has_events = isset($campaigns_by_date[$current_date]) || isset($activities_by_date[$current_date]);
    $click_class = $has_events ? ' clickable-date' : '';
    
    // Collect all events for this day
    $all_events = [];
    
    // Add campaigns
    if (isset($campaigns_by_date[$current_date])) {
        foreach ($campaigns_by_date[$current_date] as $event) {
            $all_events[] = [
                'type' => 'campaign',
                'class' => $event['type'],
                'content' => ($event['type'] == 'start' ? '▶ ' : '■ ') . htmlspecialchars($event['name']),
                'tooltip' => htmlspecialchars($event['name'])
            ];
        }
    }
    
    // Add activities
    if (isset($activities_by_date[$current_date])) {
        foreach ($activities_by_date[$current_date] as $activity) {
            $activity_icon = '';
            $activity_class = 'activity';
            
            switch ($activity['activity_type']) {
                case 'platform_created':
                    $activity_icon = '🔗 ';
                    $activity_class .= ' platform_created';
                    break;
                case 'campaign_created':
                    $activity_icon = '📝 ';
                    $activity_class .= ' campaign_created';
                    break;
                case 'campaign_launched':
                    $activity_icon = '🚀 ';
                    $activity_class .= ' campaign_launched';
                    break;
                default:
                    $activity_icon = '📅 ';
            }
            
            // Show company badge for multi-company view
            $company_badge = '';
            if ($view_mode == 'all' && count($company_filter) > 1) {
                if (isset($activity['company_name'])) {
                    $company_name = $activity['company_name'];
                } else {
                    // Decode metadata if it's from bg_content table
                    $metadata = is_string($activity['metadata']) ? json_decode($activity['metadata'], true) : $activity['metadata'];
                    $metadata = $metadata ?: [];
                    $company_id = $metadata['company_id'] ?? 99;
                    $company_name = $company_id == 99 ? 'Birthday Gold' : 'Company #' . $company_id;
                }
                $company_badge = ' (' . $company_name . ')';
            }
            
            $all_events[] = [
                'type' => 'activity',
                'class' => $activity_class,
                'content' => $activity_icon . htmlspecialchars($activity['title']) . $company_badge,
                'tooltip' => htmlspecialchars($activity['title'] . $company_badge . ' - ' . $activity['description'])
            ];
        }
    }
    
    echo '
                    <div class="calendar-day' . ($is_today ? ' today' : '') . $click_class . '" 
                         data-date="' . $current_date . '" 
                         ' . ($has_events ? 'onclick="showDateActivities(\'' . $current_date . '\')"' : '') . '>
                        <div class="calendar-day-number">' . $day . '</div>
                        <div class="calendar-events-container">';
    
    // Show first 3 events
    for ($i = 0; $i < min(3, count($all_events)); $i++) {
        $event = $all_events[$i];
        echo '
                            <div class="calendar-event ' . $event['class'] . '" 
                                 data-bs-toggle="tooltip" 
                                 data-bs-placement="top" 
                                 title="' . $event['tooltip'] . '">
                                <span class="text-truncate d-block">
                                    ' . $event['content'] . '
                                </span>
                            </div>';
    }
    
    // Show "view all" if more than 3 events
    if (count($all_events) > 3) {
        echo '
                            <a href="#" class="view-all-link" onclick="showDateActivities(\'' . $current_date . '\'); return false;">
                                +' . (count($all_events) - 3) . ' more • view all
                            </a>';
    }
    
    echo '
                        </div>
                    </div>';
}

echo '
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="list-group no-border">
                        <a href="/myaccount/marketing/campaign-create.php" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-plus me-2"></i>Create Campaign</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/myaccount/marketing/campaigns.php" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-megaphone me-2"></i>View Campaigns</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/myaccount/marketing/platforms.php" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-link me-2"></i>Manage Platforms</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/myaccount/marketing/reports.php" class="list-group-item-action d-flex justify-content-between align-items-center py-2">
                            <div><i class="bi bi-graph-up me-2"></i>Performance Reports</div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Legend</h5>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="d-flex align-items-center mb-2">
                            <div class="calendar-event start me-2" style="width: 20px;">▶</div>
                            Campaign Start
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="calendar-event end me-2" style="width: 20px;">■</div>
                            Campaign End
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="calendar-event platform_created me-2" style="width: 20px;">🔗</div>
                            Platform Created
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="calendar-event campaign_created me-2" style="width: 20px;">📝</div>
                            Campaign Created
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="calendar-event campaign_launched me-2" style="width: 20px;">🚀</div>
                            Campaign Launched
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();

// Prepare activities data for JavaScript
$activities_json = [];
foreach ($activities_by_date as $date => $date_activities) {
    $activities_json[$date] = $date_activities;
}
foreach ($campaigns_by_date as $date => $date_campaigns) {
    if (!isset($activities_json[$date])) {
        $activities_json[$date] = [];
    }
    // Convert campaigns to activity format
    foreach ($date_campaigns as $campaign) {
        $activities_json[$date][] = [
            'title' => ($campaign['type'] == 'start' ? 'Campaign Start: ' : 'Campaign End: ') . $campaign['name'],
            'description' => 'Campaign ' . ($campaign['type'] == 'start' ? 'begins' : 'ends'),
            'activity_type' => 'campaign_' . $campaign['type'],
            'metadata' => ['status' => $campaign['status'], 'campaign_id' => $campaign['id'], 'encoded_id' => $qik->encodeId($campaign['id'])]
        ];
    }
}
?>

<!-- Date Activities Modal -->
<div class="modal fade" id="dateActivitiesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Marketing Activities - <span id="modalDateTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modalActivitiesContent">
                    <!-- Activities will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
const activitiesData = <?php echo json_encode($activities_json); ?>;

function showDateActivities(dateStr) {
    const activities = activitiesData[dateStr] || [];
    const modalTitle = document.getElementById('modalDateTitle');
    const modalContent = document.getElementById('modalActivitiesContent');
    
    // Format date for display
    const date = new Date(dateStr + 'T00:00:00');
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    modalTitle.textContent = date.toLocaleDateString('en-US', options);
    
    if (activities.length === 0) {
        modalContent.innerHTML = '<div class="text-center text-muted"><i class="bi bi-calendar-x display-4"></i><p class="mt-3">No activities on this date</p></div>';
    } else {
        let content = '<div class="list-group">';
        
        activities.forEach(function(activity, index) {
            let activityIcon = '📅';
            let activityColor = 'primary';
            
            switch (activity.activity_type) {
                case 'platform_created':
                    activityIcon = '🔗';
                    activityColor = 'info';
                    break;
                case 'campaign_created':
                    activityIcon = '📝';
                    activityColor = 'warning';
                    break;
                case 'campaign_launched':
                case 'campaign_start':
                    activityIcon = '🚀';
                    activityColor = 'success';
                    break;
                case 'campaign_end':
                    activityIcon = '🏁';
                    activityColor = 'danger';
                    break;
            }
            
            // Check if this is a campaign-related activity and has campaign_id
            let titleContent = activity.title;
            if (activity.metadata && activity.metadata.campaign_id) {
                // Use pre-encoded ID if available, otherwise use the raw ID
                const campaignId = activity.metadata.encoded_id || activity.metadata.campaign_id;
                titleContent = `<a href="/myaccount/marketing/campaign-edit.php?id=${campaignId}" class="text-decoration-none">${activity.title}</a>`;
            }
            
            content += `
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">
                            <span class="badge bg-${activityColor} me-2">${activityIcon}</span>
                            ${titleContent}
                        </h6>
                        <small class="text-muted">${activity.activity_type.replace('_', ' ')}</small>
                    </div>
                    <p class="mb-1 text-muted">${activity.description}</p>
                </div>
            `;
        });
        
        content += '</div>';
        modalContent.innerHTML = content;
    }
    
    // Show the modal
    const modalElement = document.getElementById('dateActivitiesModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    modal.show();
}

// Initialize Bootstrap tooltips when page loads
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Ensure proper modal cleanup
    const modalElement = document.getElementById('dateActivitiesModal');
    modalElement.addEventListener('hidden.bs.modal', function () {
        // Force remove any lingering backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        
        // Ensure body classes are cleaned up
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });
});
</script>