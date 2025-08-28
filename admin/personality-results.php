<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Admin-only access is already handled by site-controller.php

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE  
#-------------------------------------------------------------------------------
$pagetitle = 'Team Personality Results';

// Get all staff personality results  
$results_sql = "SELECT 
    ua.*, 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.create_dt as user_since
FROM bg_user_attributes ua
INNER JOIN bg_users u ON ua.user_id = u.user_id
WHERE ua.type = 'personality_test' 
AND ua.status = 'active'
AND u.status = 'active'
ORDER BY ua.create_dt DESC";

$all_results = $database->getrows($results_sql);

// Calculate team statistics
$color_counts = ['red' => 0, 'blue' => 0, 'green' => 0, 'yellow' => 0];
$total_staff = 0;

foreach ($all_results as $result) {
    $data = json_decode($result['description'], true); // Read JSON from description field
    if (isset($data['primary_color'])) {
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
.color-badge.red {
    background: #ff4444;
    color: white;
}
.color-badge.blue {
    background: #0088ff;
    color: white;
}
.color-badge.green {
    background: #00c851;
    color: white;
}
.color-badge.yellow {
    background: #ffbb33;
    color: #333;
}

.staff-card {
    transition: all 0.3s ease;
    height: 100%;
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

echo '<div class="container mt-4">';

// Team Overview
echo '
<div class="team-overview">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2>Team Composition</h2>
            <p class="mb-3">Understanding your team personality mix helps optimize collaboration and communication</p>';

if ($total_staff > 0) {
    echo '<div class="color-distribution">';
    foreach ($color_counts as $color => $count) {
        if ($count > 0) {
            $percentage = ($count / $total_staff) * 100;
            $bg_color = $color == 'red' ? '#ff4444' : 
                       ($color == 'blue' ? '#0088ff' : 
                       ($color == 'green' ? '#00c851' : '#ffbb33'));
            echo '<div class="color-segment" style="width: ' . $percentage . '%; background: ' . $bg_color . ';">' 
                 . $count . '</div>';
        }
    }
    echo '</div>';
} else {
    echo '<p>No staff have taken the personality test yet.</p>';
}

echo '
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-number text-primary">' . $total_staff . '</div>
                <div class="text-muted">Staff Tested</div>
            </div>
        </div>
    </div>
</div>';

// Statistics Cards
if ($total_staff > 0) {
    echo '
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <span class="color-badge red">Red</span>
                <div class="stat-number text-danger">' . $color_counts['red'] . '</div>
                <div class="text-muted">Directors</div>
                <small>' . ($total_staff > 0 ? round(($color_counts['red'] / $total_staff) * 100, 1) : 0) . '%</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <span class="color-badge blue">Blue</span>
                <div class="stat-number text-primary">' . $color_counts['blue'] . '</div>
                <div class="text-muted">Analysts</div>
                <small>' . ($total_staff > 0 ? round(($color_counts['blue'] / $total_staff) * 100, 1) : 0) . '%</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <span class="color-badge green">Green</span>
                <div class="stat-number text-success">' . $color_counts['green'] . '</div>
                <div class="text-muted">Supporters</div>
                <small>' . ($total_staff > 0 ? round(($color_counts['green'] / $total_staff) * 100, 1) : 0) . '%</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <span class="color-badge yellow">Yellow</span>
                <div class="stat-number text-warning">' . $color_counts['yellow'] . '</div>
                <div class="text-muted">Socializers</div>
                <small>' . ($total_staff > 0 ? round(($color_counts['yellow'] / $total_staff) * 100, 1) : 0) . '%</small>
            </div>
        </div>
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
                    <h6 class="mt-3">Quick Tips:</h6>
                    <ul class="small">
                        <li><strong>Red + Blue:</strong> Great for strategic planning</li>
                        <li><strong>Green + Yellow:</strong> Excellent for team building</li>
                        <li><strong>Blue + Green:</strong> Ideal for quality control</li>
                        <li><strong>Red + Yellow:</strong> Perfect for sales and innovation</li>
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
                    <button class="btn btn-sm btn-outline-danger filter-btn" data-color="red">
                        <span class="color-badge red">Red</span> (' . $color_counts['red'] . ')
                    </button>
                    <button class="btn btn-sm btn-outline-primary filter-btn" data-color="blue">
                        <span class="color-badge blue">Blue</span> (' . $color_counts['blue'] . ')
                    </button>
                    <button class="btn btn-sm btn-outline-success filter-btn" data-color="green">
                        <span class="color-badge green">Green</span> (' . $color_counts['green'] . ')
                    </button>
                    <button class="btn btn-sm btn-outline-warning filter-btn" data-color="yellow">
                        <span class="color-badge yellow">Yellow</span> (' . $color_counts['yellow'] . ')
                    </button>
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
        
        // Profile photo - use default avatar for now
        $photo = '/public/images/default-avatar.png';
        
        echo '
        <div class="col-md-6 col-lg-4 mb-4 staff-card-wrapper" data-color="' . $primary . '">
            <div class="card staff-card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <img src="' . $photo . '" class="profile-photo me-3" alt="Profile">
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
        
        foreach ($data['scores'] as $color => $score) {
            $max = max($data['scores']);
            $width = $max > 0 ? ($score / $max) * 100 : 0;
            $bg = $color == 'red' ? '#ff4444' : 
                 ($color == 'blue' ? '#0088ff' : 
                 ($color == 'green' ? '#00c851' : '#ffbb33'));
            
            echo '
                        <div class="d-flex justify-content-between small mb-1">
                            <span>' . ucfirst($color) . '</span>
                            <span>' . $score . '</span>
                        </div>
                        <div class="progress" style="height: 5px; margin-bottom: 5px;">
                            <div class="progress-bar" style="width: ' . $width . '%; background: ' . $bg . '"></div>
                        </div>';
        }
        
        echo '
                    </div>
                </div>
            </div>
        </div>';
    }
    
    echo '</div>';
    
    // Export options
    echo '
    <div class="card mt-4">
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
        <a href="/staff/personality-test.php" class="btn btn-primary">
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
                labels: ["Red", "Blue", "Green", "Yellow"],
                datasets: [{
                    data: [' . $color_counts['red'] . ', ' . $color_counts['blue'] . ', ' . $color_counts['green'] . ', ' . $color_counts['yellow'] . '],
                    backgroundColor: ["#ff4444", "#0088ff", "#00c851", "#ffbb33"],
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