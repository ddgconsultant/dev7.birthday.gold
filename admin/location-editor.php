<?php
// location-editor.php - Comprehensive location editor page
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get location ID from URL
$location_id = isset($_GET['lid']) ? intval($_GET['lid']) : null;
$company_id = isset($_GET['cid']) ? intval($_GET['cid']) : null;

if (!$location_id) {
    header('Location: /admin/brands.php');
    exit;
}

// Initialize location array with defaults
$location_data = [
    'location_id' => '',
    'company_id' => '',
    'company_name' => 'Unknown Company',
    'company_display_name' => '',
    'location_name' => '',
    'address' => '',
    'city' => '',
    'state' => '',
    'zip_code' => '',
    'phone_number' => '',
    'status' => 'active',
    'is_verified' => 0,
    'source' => '',
    'create_dt' => date('Y-m-d H:i:s'),
    'modify_dt' => null
];

try {
    // Fetch location details with company info
    $sql = "SELECT l.*, c.company_name, c.company_display_name, c.company_id
            FROM bg_company_locations l
            INNER JOIN bg_companies c ON l.company_id = c.company_id
            WHERE l.location_id = :location_id";

    $stmt = $database->query($sql, ['location_id' => $location_id]);
    $fetched_location = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$fetched_location) {
        session_tracking('location_editor_error', 'Location not found: ' . $location_id);
        
        // For debugging, show error instead of redirecting
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            $pagetitle = "Location Editor - Error";
            include($dir['core_components'] . '/bg_pagestart.inc');
            include($dir['core_components'] . '/bg_header.inc');
            echo '<div class="container my-4">';
            echo '<div class="alert alert-danger">';
            echo '<h4>Location Not Found</h4>';
            echo '<p>The location with ID ' . htmlspecialchars($location_id) . ' was not found in the database.</p>';
            echo '<p>This could mean:</p>';
            echo '<ul>';
            echo '<li>The location ID is incorrect</li>';
            echo '<li>The location has been deleted</li>';
            echo '<li>The location belongs to a different company</li>';
            echo '</ul>';
            echo '<a href="/admin/company-editor-main.php?cid=' . htmlspecialchars($company_id) . '" class="btn btn-primary">Back to Company</a>';
            echo '</div>';
            echo '</div>';
            
            // Output page content
            $app->outputpage();
            
            include($dir['core_components'] . '/bg_footer.inc');
            exit;
        }
        
        header('Location: /admin/brands.php');
        exit;
    }
    
    // Merge fetched data with defaults
    $location_data = array_merge($location_data, $fetched_location);
    
} catch (Exception $e) {
    session_tracking('location_editor_error', 'Database error: ' . $e->getMessage());
    header('Location: /admin/brands.php');
    exit;
}

// Set company_id from location data if not provided
if (!$company_id && isset($location_data['company_id'])) {
    $company_id = intval($location_data['company_id']);
}

// Get location-specific rewards count with error handling
$rewards_count = 0;
try {
    $rewards_sql = "SELECT COUNT(*) as reward_count 
                    FROM bg_company_rewards 
                    WHERE location_id = :location_id 
                    AND status = 'active'";
    $rewards_stmt = $database->query($rewards_sql, ['location_id' => $location_id]);
    $rewards_count = intval($rewards_stmt->fetchColumn());
} catch (Exception $e) {
    // Log error but continue - rewards count is not critical
    session_tracking('location_editor_warning', 'Could not fetch rewards count: ' . $e->getMessage());
}

// Build display name for location with safe fallbacks
$location_display_name = 'Unknown Location';
if (!empty($location_data['location_name'])) {
    $location_display_name = $location_data['location_name'];
} elseif (!empty($location_data['city']) && !empty($location_data['state'])) {
    $location_display_name = $location_data['city'] . ', ' . $location_data['state'];
} elseif (!empty($location_data['city'])) {
    $location_display_name = $location_data['city'];
} elseif (!empty($location_data['address'])) {
    $location_display_name = $location_data['address'];
}

// Safe company name
$company_name = !empty($location_data['company_name']) ? $location_data['company_name'] : 'Unknown Company';

// Page setup
$pagetitle = "Location Editor - " . htmlspecialchars($location_display_name) . " | " . htmlspecialchars($company_name);

// Additional styles
$additionalstyles = '
<style>
/* Navigation styling similar to company editor */
.nav-pills .nav-link {
    color: #6c757d;
    background: #f8f9fa;
    margin-bottom: 0.5rem;
    border-radius: 0.25rem;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    transition: all 0.2s;
    text-align: left;
    justify-content: flex-start;
    width: 100%;
}

.nav-pills .nav-link:hover {
    background: #e9ecef;
    color: #495057;
}

.nav-pills .nav-link.active {
    background-color: var(--bs-primary);
    color: white;
}

.nav-pills .nav-link .bi {
    font-size: 1.1rem;
}

.tab-content {
    background: white;
    border-radius: 0.5rem;
    min-height: 500px;
}

.content-section {
    padding: 1.25rem;
}

/* Location header styling */
.location-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
}

.location-meta {
    display: flex;
    gap: 2rem;
    margin-top: 1rem;
}

.location-meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.breadcrumb-nav {
    background: #f8f9fa;
    padding: 1rem 0;
}

.breadcrumb {
    margin-bottom: 0;
}
</style>';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Breadcrumb Navigation -->
<div class="breadcrumb-nav">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/admin/">Admin</a></li>
                <li class="breadcrumb-item"><a href="/admin/brands">Brands</a></li>
                <li class="breadcrumb-item"><a href="/admin/company-editor-main.php?cid=<?php echo htmlspecialchars($company_id); ?>">
                    <?php echo htmlspecialchars($company_name); ?>
                </a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($location_display_name); ?></li>
            </ol>
        </nav>
    </div>
</div>

<!-- Location Header -->
<div class="location-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h2 mb-0"><?php echo htmlspecialchars($location_display_name); ?></h1>
                <p class="lead mb-0 mt-2"><?php echo htmlspecialchars($company_name); ?></p>
                <div class="location-meta">
                    <?php if (!empty($location_data['address'])): ?>
                    <div class="location-meta-item">
                        <i class="bi bi-geo-alt"></i>
                        <span><?php echo htmlspecialchars($location_data['address']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($location_data['phone_number'])): ?>
                    <div class="location-meta-item">
                        <i class="bi bi-telephone"></i>
                        <span><?php echo htmlspecialchars($location_data['phone_number']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="/admin/company-editor-main.php?cid=<?php echo htmlspecialchars($company_id); ?>#locations" 
                   class="btn btn-light">
                    <i class="bi bi-arrow-left me-2"></i>Back to Company
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container my-4">
    <div class="row">
        <!-- Left Navigation -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Location Settings</h5>
                    <div class="nav flex-column nav-pills" role="tablist">
                        <button class="nav-link active" id="details-tab" data-bs-toggle="pill" 
                                data-bs-target="#details" type="button" role="tab">
                            <i class="bi bi-info-circle me-2"></i>Basic Details
                        </button>
                        
                        <button class="nav-link" id="rewards-tab" data-bs-toggle="pill" 
                                data-bs-target="#rewards" type="button" role="tab">
                            <i class="bi bi-gift me-2"></i>Rewards
                            <?php if ($rewards_count > 0): ?>
                                <span class="badge bg-secondary ms-auto"><?php echo $rewards_count; ?></span>
                            <?php endif; ?>
                        </button>
                        
                        <button class="nav-link" id="hours-tab" data-bs-toggle="pill" 
                                data-bs-target="#hours" type="button" role="tab">
                            <i class="bi bi-clock me-2"></i>Business Hours
                        </button>
                        
                        <button class="nav-link" id="contact-tab" data-bs-toggle="pill" 
                                data-bs-target="#contact" type="button" role="tab">
                            <i class="bi bi-person me-2"></i>Contact Info
                        </button>
                        
                        <button class="nav-link" id="enrollment-tab" data-bs-toggle="pill" 
                                data-bs-target="#enrollment" type="button" role="tab">
                            <i class="bi bi-pencil-square me-2"></i>Enrollment Settings
                        </button>
                        
                        <button class="nav-link" id="policies-tab" data-bs-toggle="pill" 
                                data-bs-target="#policies" type="button" role="tab">
                            <i class="bi bi-file-text me-2"></i>Policies
                        </button>
                        
                        <button class="nav-link" id="advanced-tab" data-bs-toggle="pill" 
                                data-bs-target="#advanced" type="button" role="tab">
                            <i class="bi bi-gear me-2"></i>Advanced
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Content Area -->
        <div class="col-md-9">
            <div class="tab-content">
                <!-- Basic Details Tab -->
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <div class="content-section">
                        <?php 
                        $componentmode = 'include';
                        
                        // Make location data available to components with expected variable name
                        $location = $location_data;
                        
                        $component_file = $_SERVER['DOCUMENT_ROOT'] . '/admin/locationeditor_components/basic-details.php';
                        if (file_exists($component_file)) {
                            include($component_file); 
                        } else {
                            echo '<div class="alert alert-warning">Basic details component not found.</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Rewards Tab -->
                <div class="tab-pane fade" id="rewards" role="tabpanel">
                    <div class="content-section">
                        <?php 
                        $componentmode = 'include';
                        $location = $location_data; // Make location data available to component
                        $component_file = $_SERVER['DOCUMENT_ROOT'] . '/admin/locationeditor_components/rewards-manager.php';
                        if (file_exists($component_file)) {
                            include($component_file); 
                        } else {
                            echo '<div class="alert alert-warning">Rewards manager component not found.</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Business Hours Tab -->
                <div class="tab-pane fade" id="hours" role="tabpanel">
                    <div class="content-section">
                        <?php 
                        $componentmode = 'include';
                        $location = $location_data; // Make location data available to component
                        $component_file = $_SERVER['DOCUMENT_ROOT'] . '/admin/locationeditor_components/business-hours.php';
                        if (file_exists($component_file)) {
                            include($component_file); 
                        } else {
                            echo '<div class="alert alert-warning">Business hours component not found.</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Contact Info Tab -->
                <div class="tab-pane fade" id="contact" role="tabpanel">
                    <div class="content-section">
                        <?php 
                        $componentmode = 'include';
                        $location = $location_data; // Make location data available to component
                        $component_file = $_SERVER['DOCUMENT_ROOT'] . '/admin/locationeditor_components/contact-info.php';
                        if (file_exists($component_file)) {
                            include($component_file); 
                        } else {
                            echo '<div class="alert alert-warning">Contact info component not found.</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Enrollment Settings Tab -->
                <div class="tab-pane fade" id="enrollment" role="tabpanel">
                    <div class="content-section">
                        <?php 
                        $componentmode = 'include';
                        $location = $location_data; // Make location data available to component
                        $component_file = $_SERVER['DOCUMENT_ROOT'] . '/admin/locationeditor_components/enrollment-settings.php';
                        if (file_exists($component_file)) {
                            include($component_file); 
                        } else {
                            echo '<div class="alert alert-warning">Enrollment settings component not found.</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Policies Tab -->
                <div class="tab-pane fade" id="policies" role="tabpanel">
                    <div class="content-section">
                        <?php 
                        $componentmode = 'include';
                        $location = $location_data; // Make location data available to component
                        $component_file = $_SERVER['DOCUMENT_ROOT'] . '/admin/locationeditor_components/policies.php';
                        if (file_exists($component_file)) {
                            include($component_file); 
                        } else {
                            echo '<div class="alert alert-warning">Policies component not found.</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Advanced Tab -->
                <div class="tab-pane fade" id="advanced" role="tabpanel">
                    <div class="content-section">
                        <?php 
                        $componentmode = 'include';
                        $location = $location_data; // Make location data available to component
                        $component_file = $_SERVER['DOCUMENT_ROOT'] . '/admin/locationeditor_components/advanced-settings.php';
                        if (file_exists($component_file)) {
                            include($component_file); 
                        } else {
                            echo '<div class="alert alert-warning">Advanced settings component not found.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Output page content

include($dir['core_components'] . '/bg_footer.inc'); 

$app->outputpage();
?>