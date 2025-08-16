<?php
include '../core/site-controller.php';
// Birthday.Gold Admin Feedback Dashboard
// This allows administrators to view and analyze member feedback

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
// Check for admin access  
if ($current_user_data['account_admin'] != 'Y') {
    header("Location: /");
    exit;
}

// Initialize variables
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$rating_filter = isset($_GET['rating_filter']) ? $_GET['rating_filter'] : 'all';
$search_term = isset($_GET['search_term']) ? $_GET['search_term'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query based on filters
$where_clauses = [];
$params = [];

// Date range filter
if ($date_filter == 'custom' && !empty($start_date) && !empty($end_date)) {
    $where_clauses[] = "DATE(f.create_dt) BETWEEN :start_date AND :end_date";
    $params['start_date'] = $start_date;
    $params['end_date'] = $end_date;
} elseif ($date_filter == 'last_7_days') {
    $where_clauses[] = "f.create_dt >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($date_filter == 'last_30_days') {
    $where_clauses[] = "f.create_dt >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
} elseif ($date_filter == 'this_month') {
    $where_clauses[] = "MONTH(f.create_dt) = MONTH(CURDATE()) AND YEAR(f.create_dt) = YEAR(CURDATE())";
}

// Rating filter
if ($rating_filter == 'high') {
    $where_clauses[] = "f.overall_rating >= 4";
} elseif ($rating_filter == 'medium') {
    $where_clauses[] = "f.overall_rating IN (2, 3)";
} elseif ($rating_filter == 'low') {
    $where_clauses[] = "f.overall_rating = 1";
}

// Search term
if (!empty($search_term)) {
    $where_clauses[] = "(u.first_name LIKE :search_term OR u.last_name LIKE :search_term OR u.email LIKE :search_term OR f.improvement_feedback LIKE :search_term)";
    $params['search_term'] = '%' . $search_term . '%';
}

// Always include active status
$where_clauses[] = "f.status = 'active'";

// Build complete query
$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM bg_user_feedback f 
              LEFT JOIN bg_users u ON f.user_id = u.user_id 
              $where_sql";
$total_rows = $database->getrow($count_sql, $params)['total'];
$total_pages = ceil($total_rows / $per_page);

// Get feedback data
$sql = "SELECT f.*, u.first_name, u.last_name, u.email, u.birthdate,
        DATE_FORMAT(f.create_dt, '%Y-%m-%d %H:%i') as formatted_create_dt
        FROM bg_user_feedback f
        LEFT JOIN bg_users u ON f.user_id = u.user_id
        $where_sql
        ORDER BY f.create_dt DESC
        LIMIT $offset, $per_page";

$feedback_data = $database->getrows($sql, $params);

// Get summary statistics
$stats_sql = "SELECT 
                COUNT(*) AS total_responses,
                ROUND(AVG(overall_rating), 2) AS avg_overall_rating,
                ROUND(AVG(value_rating), 2) AS avg_value_rating,
                ROUND(AVG(ease_rating), 2) AS avg_ease_rating,
                ROUND(AVG(timeliness_rating), 2) AS avg_timeliness_rating,
                ROUND(AVG(nps_rating), 2) AS avg_nps_rating,
                SUM(CASE WHEN overall_rating >= 4 THEN 1 ELSE 0 END) AS satisfied_users,
                SUM(CASE WHEN overall_rating <= 2 THEN 1 ELSE 0 END) AS unsatisfied_users,
                SUM(CASE WHEN nps_rating >= 9 THEN 1 ELSE 0 END) AS promoters,
                SUM(CASE WHEN nps_rating BETWEEN 7 AND 8 THEN 1 ELSE 0 END) AS passives,
                SUM(CASE WHEN nps_rating <= 6 THEN 1 ELSE 0 END) AS detractors
              FROM bg_user_feedback f
              $where_sql";
$stats = $database->getrow($stats_sql, $params);

// Calculate NPS score
$nps_score = 0;
if ($stats['total_responses'] > 0) {
    $promoter_percent = ($stats['promoters'] / $stats['total_responses']) * 100;
    $detractor_percent = ($stats['detractors'] / $stats['total_responses']) * 100;
    $nps_score = round($promoter_percent - $detractor_percent);
}

// Get recent comments for the sidebar
$comments_sql = "SELECT f.improvement_feedback, u.first_name, u.last_name, 
                 DATE_FORMAT(f.create_dt, '%Y-%m-%d') as comment_date
                 FROM bg_user_feedback f
                 LEFT JOIN bg_users u ON f.user_id = u.user_id
                 WHERE f.improvement_feedback IS NOT NULL AND f.improvement_feedback != '' AND f.status = 'active'
                 ORDER BY f.create_dt DESC
                 LIMIT 5";
$recent_comments = $database->getrows($comments_sql);

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$pagetitle = "Feedback Dashboard";
$additionalstyles = [];
$additionalscripts = ['https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js'];

// Helper function to count ratings in the result set
function countRatings($data, $field, $rating) {
    $count = 0;
    foreach ($data as $item) {
        if (isset($item[$field]) && $item[$field] == $rating) {
            $count++;
        }
    }
    return $count;
}

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Birthday Feedback Dashboard</h2>
            </div>
            
            <!-- Filter Controls -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" action="" class="row g-3">
                        <div class="col-md-3">
                            <label for="date_filter" class="form-label">Date Range</label>
                            <select name="date_filter" id="date_filter" class="form-select" onchange="toggleDateRange()">
                                <option value="all" ' . ($date_filter == 'all' ? 'selected' : '') . '>All Time</option>
                                <option value="last_7_days" ' . ($date_filter == 'last_7_days' ? 'selected' : '') . '>Last 7 Days</option>
                                <option value="last_30_days" ' . ($date_filter == 'last_30_days' ? 'selected' : '') . '>Last 30 Days</option>
                                <option value="this_month" ' . ($date_filter == 'this_month' ? 'selected' : '') . '>This Month</option>
                                <option value="custom" ' . ($date_filter == 'custom' ? 'selected' : '') . '>Custom Range</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2 date-range-inputs" style="' . ($date_filter == 'custom' ? '' : 'display: none;') . '">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="' . $start_date . '">
                        </div>
                        
                        <div class="col-md-2 date-range-inputs" style="' . ($date_filter == 'custom' ? '' : 'display: none;') . '">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="' . $end_date . '">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="rating_filter" class="form-label">Rating</label>
                            <select name="rating_filter" id="rating_filter" class="form-select">
                                <option value="all" ' . ($rating_filter == 'all' ? 'selected' : '') . '>All Ratings</option>
                                <option value="high" ' . ($rating_filter == 'high' ? 'selected' : '') . '>High (4-5)</option>
                                <option value="medium" ' . ($rating_filter == 'medium' ? 'selected' : '') . '>Medium (2-3)</option>
                                <option value="low" ' . ($rating_filter == 'low' ? 'selected' : '') . '>Low (1)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="search_term" class="form-label">Search</label>
                            <input type="text" name="search_term" id="search_term" class="form-control" 
                                   placeholder="Search by name, email, or comments" value="' . htmlspecialchars($search_term) . '">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title">Total Responses</h5>
                            <p class="display-4">' . number_format($stats['total_responses']) . '</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title">Avg. Overall Rating</h5>
                            <p class="display-4">' . number_format($stats['avg_overall_rating'], 1) . ' <small>/ 5</small></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title">NPS Score</h5>
                            <p class="display-4">' . $nps_score . '</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title">Satisfied Users</h5>
                            <p class="display-4">' . ($stats['total_responses'] > 0 ? round(($stats['satisfied_users'] / $stats['total_responses']) * 100) : 0) . '<small>%</small></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Rating Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="ratingDistribution" height="250"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">NPS Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="npsDistribution" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Feedback Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Feedback</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Member</th>
                                    <th>Rating</th>
                                    <th>Rewards Received</th>
                                    <th>NPS</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>';
                            
                            if (count($feedback_data) > 0) {
                                foreach ($feedback_data as $feedback) {
                                    // Generate star rating HTML
                                    $stars = '';
                                    for ($i = 1; $i <= 5; $i++) {
                                        $stars .= '<i class="' . ($i <= $feedback['overall_rating'] ? 'fas' : 'far') . ' fa-star text-warning"></i>';
                                    }
                                    
                                    // Format rewards received
                                    $rewards_status = '';
                                    if ($feedback['rewards_received'] == 'all') {
                                        $rewards_status = '<span class="badge bg-success">All</span>';
                                    } elseif ($feedback['rewards_received'] == 'some') {
                                        $rewards_status = '<span class="badge bg-warning">Some</span>';
                                    } elseif ($feedback['rewards_received'] == 'none') {
                                        $rewards_status = '<span class="badge bg-danger">None</span>';
                                    }
                                    
                                    // Format NPS score with color
                                    $nps_color = '';
                                    if ($feedback['nps_rating'] >= 9) {
                                        $nps_color = 'success';
                                    } elseif ($feedback['nps_rating'] >= 7) {
                                        $nps_color = 'warning';
                                    } else {
                                        $nps_color = 'danger';
                                    }
                                    
                                    echo '
                                    <tr>
                                        <td>' . $feedback['formatted_create_dt'] . '</td>
                                        <td>' . htmlspecialchars($feedback['first_name'] . ' ' . $feedback['last_name']) . '<br>
                                            <small class="text-muted">' . htmlspecialchars($feedback['email']) . '</small>
                                        </td>
                                        <td>' . $stars . '</td>
                                        <td>' . $rewards_status . '</td>
                                        <td><span class="badge bg-' . $nps_color . '">' . $feedback['nps_rating'] . '</span></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#feedbackDetail' . $feedback['feedback_id'] . '">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>';
                                    
                                    // Create the modal for each feedback entry
                                    echo '
                                    <div class="modal fade" id="feedbackDetail' . $feedback['feedback_id'] . '" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Feedback Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <h6>Member</h6>
                                                            <p>' . htmlspecialchars($feedback['first_name'] . ' ' . $feedback['last_name']) . '<br>
                                                               <small>' . htmlspecialchars($feedback['email']) . '</small>
                                                            </p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Submitted</h6>
                                                            <p>' . $feedback['formatted_create_dt'] . '</p>
                                                        </div>
                                                    </div>';
                                                    
                                                    // Display improvement feedback if present
                                                    if (!empty($feedback['improvement_feedback'])) {
                                                        echo '
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <h6>Improvement Suggestions</h6>
                                                                <p class="border p-3 rounded">' . nl2br(htmlspecialchars($feedback['improvement_feedback'])) . '</p>
                                                            </div>
                                                        </div>';
                                                    }
                                                    
                                                echo '
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>';
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center py-3">No feedback data found matching the filters.</td></tr>';
                            }
                            
                            echo '
                            </tbody>
                        </table>
                    </div>
                </div>';
                
                // Pagination
                if ($total_pages > 1) {
                    echo '
                    <div class="card-footer">
                        <nav aria-label="Feedback pagination">
                            <ul class="pagination justify-content-center mb-0">';
                            
                            // Previous page link
                            echo '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '">
                                  <a class="page-link" href="?page=' . ($page - 1) . '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) . '">Previous</a>
                                  </li>';
                            
                            // Page number links
                            for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++) {
                                echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">
                                      <a class="page-link" href="?page=' . $i . '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) . '">' . $i . '</a>
                                      </li>';
                            }
                            
                            // Next page link
                            echo '<li class="page-item ' . ($page >= $total_pages ? 'disabled' : '') . '">
                                  <a class="page-link" href="?page=' . ($page + 1) . '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) . '">Next</a>
                                  </li>';
                            
                            echo '
                            </ul>
                        </nav>
                    </div>';
                }
                
            echo '</div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-3">
            <!-- Recent Comments Widget -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Recent Comments</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">';
                    
                    if (count($recent_comments) > 0) {
                        foreach ($recent_comments as $comment) {
                            $truncated_comment = strlen($comment['improvement_feedback']) > 100 
                                ? substr($comment['improvement_feedback'], 0, 100) . '...' 
                                : $comment['improvement_feedback'];
                                
                            echo '
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold">' . htmlspecialchars($comment['first_name']) . '</span>
                                    <small class="text-muted">' . $comment['comment_date'] . '</small>
                                </div>
                                <p class="mb-0 small">' . htmlspecialchars($truncated_comment) . '</p>
                            </li>';
                        }
                    } else {
                        echo '<li class="list-group-item text-center py-3">No comments yet.</li>';
                    }
                    
                    echo '
                    </ul>
                </div>
            </div>
            
            <!-- Quick Stats Widget -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Breakdown by Category</h5>
                </div>
                <div class="card-body">
                    <h6>Experience Ratings</h6>
                    <ul class="list-unstyled">
                        <li>Value: <span class="fw-bold">' . number_format($stats['avg_value_rating'], 1) . '/5</span></li>
                        <li>Ease: <span class="fw-bold">' . number_format($stats['avg_ease_rating'], 1) . '/5</span></li>
                        <li>Timeliness: <span class="fw-bold">' . number_format($stats['avg_timeliness_rating'], 1) . '/5</span></li>
                    </ul>
                    
                    <h6>NPS Breakdown</h6>
                    <ul class="list-unstyled">
                        <li>Promoters: <span class="fw-bold">' . $stats['promoters'] . '</span></li>
                        <li>Passives: <span class="fw-bold">' . $stats['passives'] . '</span></li>
                        <li>Detractors: <span class="fw-bold">' . $stats['detractors'] . '</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Function to toggle date range inputs
function toggleDateRange() {
    const dateFilter = document.getElementById("date_filter").value;
    const dateRangeInputs = document.querySelectorAll(".date-range-inputs");
    
    dateRangeInputs.forEach(input => {
        if (dateFilter === "custom") {
            input.style.display = "block";
        } else {
            input.style.display = "none";
        }
    });
}

// Charts
document.addEventListener("DOMContentLoaded", function() {
    // Rating distribution chart
    const ratingCtx = document.getElementById("ratingDistribution").getContext("2d");
    const ratingChart = new Chart(ratingCtx, {
        type: "bar",
        data: {
            labels: ["1 ★", "2 ★", "3 ★", "4 ★", "5 ★"],
            datasets: [{
                label: "Overall Rating Distribution",
                data: [
                    ' . countRatings($feedback_data, 'overall_rating', 1) . ',
                    ' . countRatings($feedback_data, 'overall_rating', 2) . ',
                    ' . countRatings($feedback_data, 'overall_rating', 3) . ',
                    ' . countRatings($feedback_data, 'overall_rating', 4) . ',
                    ' . countRatings($feedback_data, 'overall_rating', 5) . '
                ],
                backgroundColor: [
                    "rgba(255, 99, 132, 0.7)",
                    "rgba(255, 159, 64, 0.7)",
                    "rgba(255, 205, 86, 0.7)",
                    "rgba(75, 192, 192, 0.7)",
                    "rgba(54, 162, 235, 0.7)"
                ]
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // NPS distribution chart
    const npsCtx = document.getElementById("npsDistribution").getContext("2d");
    const npsChart = new Chart(npsCtx, {
        type: "doughnut",
        data: {
            labels: ["Promoters (9-10)", "Passives (7-8)", "Detractors (0-6)"],
            datasets: [{
                data: [' . $stats['promoters'] . ', ' . $stats['passives'] . ', ' . $stats['detractors'] . '],
                backgroundColor: [
                    "rgba(75, 192, 192, 0.7)",
                    "rgba(255, 205, 86, 0.7)",
                    "rgba(255, 99, 132, 0.7)"
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: "right"
                }
            }
        }
    });
});
</script>';

include($dir['core_components'] . '/bg_footer.inc');
?>