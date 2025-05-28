<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$incident_types = [
    'physical_harm' => 'Physical Harm Event',
    'data_breach' => 'Data Breach Event',
    'credential_breach' => 'Security Credential Breach',
    'system_access' => 'System Access Breach',
    'denial_of_service' => 'Denial of Service Attack'
];

$incident_levels = [
    '1' => 'Level 1 - Minor',
    '2' => 'Level 2 - Moderate',
    '3' => 'Level 3 - Severe'
];

$incident_status = [
    'active' => 'Active',
    'investigating' => 'Under Investigation',
    'contained' => 'Contained',
    'resolved' => 'Resolved',
    'closed' => 'Closed'
];

// Get filter parameters
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
$level_filter = $_GET['level'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

#-------------------------------------------------------------------------------
# BUILD QUERY
#-------------------------------------------------------------------------------
$where_clauses = ["category = 'security' AND type = 'incident'"];
$params = [];

if ($search) {
    $where_clauses[] = "(name LIKE :search OR content LIKE :search)";
    $params['search'] = "%$search%";
}

if ($type_filter) {
    $where_clauses[] = "grouping = :type_filter";
    $params['type_filter'] = $type_filter;
}

if ($level_filter) {
    $where_clauses[] = "content LIKE :level_filter";
    $params['level_filter'] = "%\"level\":\"$level_filter\"%";
}

if ($status_filter) {
    $where_clauses[] = "content LIKE :status_filter";
    $params['status_filter'] = "%\"status\":\"$status_filter\"%";
}

if ($date_from) {
    $where_clauses[] = "create_dt >= :date_from";
    $params['date_from'] = $date_from . ' 00:00:00';
}

if ($date_to) {
    $where_clauses[] = "create_dt <= :date_to";
    $params['date_to'] = $date_to . ' 23:59:59';
}

$where_clause = implode(' AND ', $where_clauses);

#-------------------------------------------------------------------------------
# GET TOTAL COUNT
#-------------------------------------------------------------------------------
$count_sql = "SELECT COUNT(*) as total FROM bg_content WHERE $where_clause";
$stmt = $database->prepare($count_sql);
$stmt->execute($params);
$total_incidents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_incidents / $per_page);

#-------------------------------------------------------------------------------
# GET INCIDENTS
#-------------------------------------------------------------------------------
$sql = "SELECT * FROM bg_content 
        WHERE $where_clause 
        ORDER BY create_dt DESC 
        LIMIT :offset, :per_page";

$params['offset'] = $offset;
$params['per_page'] = $per_page;
        
$stmt = $database->prepare($sql);
$stmt->execute($params);
$incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');

$additionalstyles .= '
<style>
.filter-section {
    background-color: #f8f9fa;
    border-radius: 4px;
    padding: 1rem;
    margin-bottom: 1rem;
}
.filter-section .form-group {
    margin-bottom: 0;
}
.timeline {
    position: relative;
    padding: 20px 0;
}
.timeline-item {
    position: relative;
    padding-left: 40px;
    margin-bottom: 30px;
}
.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    background-color: #007bff;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #007bff;
}
.timeline-content {
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 4px;
}
</style>';

echo '    
<div class="container main-content mt-0 pt-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Security Incident History</h2>
        <a href="/security/report" class="btn btn-sm btn-primary">Report New Incident</a>
    </div>

    <!-- Search and Filters -->
    <div class="filter-section">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" value="' . htmlspecialchars($search) . '" 
                           placeholder="Search incidents...">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                </div>
            </div>
            
            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="">All Types</option>';
                    foreach ($incident_types as $value => $label) {
                        $selected = $type_filter === $value ? ' selected' : '';
                        echo '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . 
                             htmlspecialchars($label) . '</option>';
                    }
echo '          </select>
            </div>
            
            <div class="col-md-2">
                <select name="level" class="form-select">
                    <option value="">All Levels</option>';
                    foreach ($incident_levels as $value => $label) {
                        $selected = $level_filter === $value ? ' selected' : '';
                        echo '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . 
                             htmlspecialchars($label) . '</option>';
                    }
echo '          </select>
            </div>
            
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>';
                    foreach ($incident_status as $value => $label) {
                        $selected = $status_filter === $value ? ' selected' : '';
                        echo '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . 
                             htmlspecialchars($label) . '</option>';
                    }
echo '          </select>
            </div>
            
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="collapse" 
                        data-bs-target="#advancedFilters">
                    Advanced Filters
                </button>
            </div>
            
            <div class="collapse mt-3" id="advancedFilters">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" class="form-control" name="date_from" value="' . htmlspecialchars($date_from) . '">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" class="form-control" name="date_to" value="' . htmlspecialchars($date_to) . '">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <a href="?" class="btn btn-outline-secondary">Clear Filters</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Incident ID</th>
                            <th>Type</th>
                            <th>Level</th>
                            <th>Status</th>
                            <th>Reported</th>
                            <th>Last Updated</th>
                            <th>Version</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';
                    
foreach ($incidents as $incident) {
    $content = json_decode($incident['content'], true);
    echo '<tr>
            <td>' . htmlspecialchars($content['incident_id']) . '</td>
            <td>' . htmlspecialchars($incident_types[$content['type']] ?? $content['type']) . '</td>
            <td>Level ' . htmlspecialchars($content['level']) . '</td>
            <td>' . htmlspecialchars(ucfirst($content['status'])) . '</td>
            <td>' . date('Y-m-d H:i', strtotime($content['report_timestamp'])) . '</td>
            <td>' . date('Y-m-d H:i', strtotime($incident['modify_dt'] ?? $incident['create_dt'])) . '</td>
            <td>' . htmlspecialchars($incident['version']) . '</td>
            <td>
                <a href="/security/incident/' . htmlspecialchars($content['incident_id']) . '" 
                   class="btn btn-sm btn-outline-primary">View</a>
                <a href="/security/incident/' . htmlspecialchars($content['incident_id']) . '/edit" 
                   class="btn btn-sm btn-outline-secondary">Edit</a>
            </td>
          </tr>';
}

if (empty($incidents)) {
    echo '<tr><td colspan="8" class="text-center">No incidents found matching your criteria.</td></tr>';
}

echo '          </tbody>
                </table>
            </div>';

// Pagination
if ($total_pages > 1) {
    $current_params = $_GET;
    unset($current_params['page']);
    $query_string = http_build_query($current_params);
    $base_url = '?' . ($query_string ? $query_string . '&' : '');
    
    echo '<div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted">
                Showing ' . (($page - 1) * $per_page + 1) . ' to ' . 
                min($page * $per_page, $total_incidents) . ' of ' . $total_incidents . ' incidents
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination mb-0">';
    
    // Previous page
    if ($page > 1) {
        echo '<li class="page-item">
                <a class="page-link" href="' . $base_url . 'page=' . ($page - 1) . '">Previous</a>
              </li>';
    }
    
    // Page numbers
    for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++) {
        echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                <a class="page-link" href="' . $base_url . 'page=' . $i . '">' . $i . '</a>
              </li>';
    }
    
    // Next page
    if ($page < $total_pages) {
        echo '<li class="page-item">
                <a class="page-link" href="' . $base_url . 'page=' . ($page + 1) . '">Next</a>
              </li>';
    }
    
    echo '    </ul>
            </nav>
          </div>';
}

echo '  </div>
    </div>
</div>';

$additionalscripts .= '
<script>
$(document).ready(function() {
    // Auto-submit form when select fields change
    $("select[name=type], select[name=level], select[name=status]").change(function() {
        $(this).closest("form").submit();
    });
    
    // Initialize any date pickers
    if(typeof $.fn.datepicker !== "undefined") {
        $("input[type=date]").datepicker({
            format: "yyyy-mm-dd",
            autoclose: true
        });
    }
});
</script>';

$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
