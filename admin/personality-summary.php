<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Admin-only access is already handled by site-controller.php

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE  
#-------------------------------------------------------------------------------
$pagetitle = 'Personality Summary';

// Get True Colors test results ONLY
$results_sql = "SELECT 
    ua.*, 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.avatar,
    u.create_dt as user_since
FROM bg_user_attributes ua
INNER JOIN bg_users u ON ua.user_id = u.user_id
WHERE ua.type = 'true_colors_test' 
AND ua.status = 'active'
AND u.status = 'active'
ORDER BY ua.create_dt DESC";

$all_results = $database->getrows($results_sql);

// Calculate team statistics for True Colors only - use consistent order
$color_counts = [];
foreach ($true_colors_order as $color) {
    $color_counts[$color] = 0;
}
$total_staff = 0;

foreach ($all_results as $result) {
    $data = json_decode($result['description'], true);
    if (isset($data['primary_color']) && isset($color_counts[$data['primary_color']])) {
        $color_counts[$data['primary_color']]++;
        $total_staff++;
    }
}

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
// No form actions for this page

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$additionalstyles .= '
<style>
.team-overview {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
}
.stat-card {
    text-align: center;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    transition: transform 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-5px);
}
.stat-number {
    font-size: 3rem;
    font-weight: bold;
}

.color-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 12px;
}
.color-badge.orange {
    background: #FF6B35;
    color: white;
}
.color-badge.gold {
    background: #FFD700;
    color: #333;
}
.color-badge.blue {
    background: #4A90E2;
    color: white;
}
.color-badge.green {
    background: #50C878;
    color: white;
}

.staff-card {
    transition: all 0.3s ease;
    height: 100%;
}

.staff-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
}
.staff-card:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
    transform: translateY(-3px);
}
.profile-photo {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}
.filter-btn {
    margin: 5px;
}
.filter-btn.active {
    transform: scale(1.1);
}

.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

.team-insights {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.no-results {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 12px;
}

.color-distribution {
    display: flex;
    height: 40px;
    border-radius: 20px;
    overflow: hidden;
    margin: 20px 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.color-segment {
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    transition: all 0.3s ease;
}
.color-segment:hover {
    transform: scale(1.05);
    z-index: 1;
}
</style>';

// JavaScript will be added inline after content loads
$additionalscripts = '';

$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Admin header
echo '
<div class="content-header-staff">
    <div class="container text-center">
        <h1><i class="fas fa-users"></i> Team Personality Results</h1>
        <p class="lead">View and analyze staff personality test results</p>
    </div>
</div>';

echo '<div class="container mt-4 mb-5">';

// Team Overview with consistent header card
echo '
<div class="row mb-3">
    <div class="col-12 text-end">
        <a href="/admin/" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Admin
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card" style="background: #f8f9fa; border: 3px solid #0d6efd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(13, 110, 253, 0.1);">
            <h3 class="mb-1"><i class="bi bi-palette"></i> Team Composition <span class="badge rounded-pill bg-secondary ms-2">' . $total_staff . ' Tested</span></h3>
            <p class="mb-2">Understanding your team personality mix helps optimize collaboration and communication</p>
            <small class="text-muted">True Colors personality assessment results</small>
        </div>
    </div>
</div>';


// Statistics Cards - use consistent order
if ($total_staff > 0) {
    echo '
    <div class="row mb-4">';
    
    foreach ($true_colors_order as $color) {
        $color_info = $true_colors[$color];
        $count = $color_counts[$color];
        $percentage = $total_staff > 0 ? round(($count / $total_staff) * 100, 1) : 0;
        
        echo '
        <div class="col-md-3">
            <div class="stat-card">
                <span class="color-badge ' . $color . '">' . $color_info['name'] . '</span>
                <div class="stat-number" style="color: ' . $color_info['color'] . '">' . $count . '</div>
                <div class="text-muted">' . explode(' ', $color_info['title'])[0] . '</div>
                <small>' . $percentage . '%</small>
            </div>
        </div>';
    }
    
    echo '
    </div>';
    
    // Chart and Insights
    echo '
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Distribution Chart</h5>
                    <div class="chart-container">
                        <canvas id="colorChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Team Insights</h5>';
    
    // Determine team balance
    $max_percentage = $total_staff > 0 ? (max($color_counts) / $total_staff) * 100 : 0;
    $min_percentage = $total_staff > 0 ? (min($color_counts) / $total_staff) * 100 : 0;
    $balance = $max_percentage - $min_percentage;
    
    if ($balance < 30) {
        echo '<div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <strong>Well Balanced Team!</strong><br>
            Your team has a good mix of personality types, which promotes diverse perspectives and balanced decision-making.
        </div>';
    } else {
        $dominant = array_search(max($color_counts), $color_counts);
        $lacking = array_search(min($color_counts), $color_counts);
        echo '<div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <strong>Team Imbalance Detected</strong><br>
            Your team is heavy on <span class="color-badge ' . $dominant . '">' . $dominant . '</span> personalities 
            but lacking <span class="color-badge ' . $lacking . '">' . $lacking . '</span> types. 
            Consider this when hiring or forming project teams.
        </div>';
    }
    
    echo '
                    <h6 class="mt-3">True Colors Team Combinations:</h6>
                    <ul class="small">
                        <li><strong>Orange + Gold:</strong> Great for project execution with structure</li>
                        <li><strong>Blue + Green:</strong> Excellent for collaborative analysis</li>
                        <li><strong>Orange + Blue:</strong> Ideal for innovative team leadership</li>
                        <li><strong>Gold + Green:</strong> Perfect for systematic quality control</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>';
    
    // Filters and Search
    echo '
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-3">Filter by Color</h5>
                    <button class="btn btn-sm btn-secondary filter-btn active" data-color="all">
                        All (<span id="total-count">' . $total_staff . '</span>)
                    </button>
';
                    
    foreach ($true_colors_order as $color) {
        $color_info = $true_colors[$color];
        $count = $color_counts[$color];
        $text_color = $color == 'gold' ? '#333' : 'white';
        
        echo '
                    <button class="btn btn-sm filter-btn" data-color="' . $color . '" style="background: ' . $color_info['color'] . '; color: ' . $text_color . '; border: none;">
                        ' . $color_info['name'] . ' (' . $count . ')
                    </button>';
    }
    
    echo '
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="search-staff" placeholder="Search by name...">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <p class="text-muted mb-3">Showing <span id="showing-count">' . $total_staff . '</span> of ' . $total_staff . ' staff members</p>';
    
    // Staff Cards
    echo '<div class="row">';
    
    foreach ($all_results as $result) {
        $data = json_decode($result['description'], true); // Read JSON from description field
        $primary = $data['primary_color'];
        $secondary = $data['secondary_color'];
        $test_date = date('M j, Y', strtotime($data['test_date']));
        
        // Use avatar from database or fallback to initials
        $avatar_colors = ['orange' => '#FF6B35', 'gold' => '#FFD700', 'blue' => '#4A90E2', 'green' => '#50C878'];
        $avatar_bg = $avatar_colors[$primary] ?? '#6c757d';
        
        echo '
        <div class="col-md-6 col-lg-4 mb-4 staff-card-wrapper" data-color="' . $primary . '">
            <a href="/admin/personality-results.php?user_id=' . $result['user_id'] . '" class="card staff-card text-decoration-none" style="display: block; color: inherit;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">';
                    
        if (!empty($result['avatar'])) {
            echo '
                        <img src="' . htmlspecialchars($result['avatar']) . '" class="avatar-photo me-3" alt="Profile" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">';
        } else {
            $initials = substr($result['first_name'], 0, 1) . substr($result['last_name'], 0, 1);
            echo '
                        <div class="avatar-circle me-3" style="background: ' . $avatar_bg . '; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2rem;">
                            ' . $initials . '
                        </div>';
        }
        
        echo '
                        <div>
                            <h6 class="mb-1 staff-name">' . htmlspecialchars($result['first_name'] . ' ' . $result['last_name']) . '</h6>
                            <small class="text-muted">' . htmlspecialchars($result['email']) . '</small>
                        </div>
                    </div>
                    <div class="mb-2">
                        <span class="color-badge ' . $primary . '">' . $primary . '</span>
                        <span class="badge bg-light text-dark">Secondary: ' . ucfirst($secondary) . '</span>
                    </div>
                    <div class="small text-muted">
                        <i class="fas fa-calendar"></i> Tested: ' . $test_date . '<br>
                        <i class="fas fa-user-clock"></i> Member since: ' . date('M Y', strtotime($result['user_since'])) . '
                    </div>
                    
                    <!-- Color scores -->
                    <div class="mt-3">';
        
        $color_map = [
            'orange' => '#FF6B35',
            'gold' => '#FFD700', 
            'blue' => '#4A90E2',
            'green' => '#50C878'
        ];
        
        foreach ($true_colors_order as $color) {
            $score = $data['scores'][$color] ?? 0;
            $max = max($data['scores']);
            $width = $max > 0 ? ($score / $max) * 100 : 0;
            $color_info = $true_colors[$color];
            
            echo '
                        <div class="d-flex justify-content-between small mb-1">
                            <span>' . $color_info['name'] . '</span>
                            <span>' . $score . '</span>
                        </div>
                        <div class="progress" style="height: 5px; margin-bottom: 5px;">
                            <div class="progress-bar" style="width: ' . $width . '%; background: ' . $color_info['color'] . '"></div>
                        </div>';
        }
        
        echo '
                    </div>
                </div>
            </a>
        </div>';
    }
    
    echo '</div>';
    
    // Export options
    echo '
    <div class="card mt-4 mb-5">
        <div class="card-body text-center">
            <h5>Export Options</h5>
            <button onclick="exportCSV()" class="btn btn-primary">
                <i class="fas fa-file-csv"></i> Export to CSV
            </button>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>';
    
} else {
    // No results yet
    echo '
    <div class="no-results">
        <i class="fas fa-clipboard-list" style="font-size: 4rem; color: #dee2e6;"></i>
        <h3 class="mt-3">No Results Yet</h3>
        <p class="text-muted">Staff members have not taken the personality test yet.</p>
        <a href="/staff/personality-test" class="btn btn-primary">
            <i class="fas fa-arrow-right"></i> Take the Test
        </a>
    </div>';
}

echo '</div>'; // Close container

// Add JavaScript with Chart.js
echo '
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    // Filter functionality
    $(".filter-btn").click(function() {
        const color = $(this).data("color");
        
        if (color === "all") {
            $(".staff-card-wrapper").show();
            $(".filter-btn").removeClass("active");
            $(this).addClass("active");
        } else {
            $(".staff-card-wrapper").hide();
            $(".staff-card-wrapper[data-color=" + color + "]").show();
            $(".filter-btn").removeClass("active");
            $(this).addClass("active");
        }
        
        updateCount();
    });
    
    function updateCount() {
        const visible = $(".staff-card-wrapper:visible").length;
        $("#showing-count").text(visible);
    }
    
    // Search functionality
    $("#search-staff").on("keyup", function() {
        const value = $(this).val().toLowerCase();
        $(".staff-card-wrapper").each(function() {
            const name = $(this).find(".staff-name").text().toLowerCase();
            $(this).toggle(name.indexOf(value) > -1);
        });
        updateCount();
    });
    
    // Chart initialization
    const ctx = document.getElementById("colorChart");
    if (ctx) {
        new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: ["Orange", "Gold", "Blue", "Green"],
                datasets: [{
                    data: [' . $color_counts['orange'] . ', ' . $color_counts['gold'] . ', ' . $color_counts['blue'] . ', ' . $color_counts['green'] . '],
                    backgroundColor: ["#FF6B35", "#FFD700", "#4A90E2", "#50C878"],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            padding: 20,
                            font: {
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || "";
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ": " + value + " (" + percentage + "%)";
                            }
                        }
                    }
                }
            }
        });
    }
});

function exportCSV() {
    const data = ' . json_encode($all_results) . ';
    let csv = "Name,Email,Primary Color,Secondary Color,Test Date\\n";
    
    data.forEach(row => {
        const parsed = JSON.parse(row.description);
        csv += `"${row.first_name} ${row.last_name}","${row.email}","${parsed.primary_color}","${parsed.secondary_color}","${parsed.test_date}"\\n`;
    });
    
    const blob = new Blob([csv], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "personality-results.csv";
    a.click();
}
</script>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>