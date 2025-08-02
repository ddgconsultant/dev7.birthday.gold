<?php
// Test Enrollment Process Page - For testing ABO form field mapping without database pollution
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# TEST MODE CONFIGURATION
#-------------------------------------------------------------------------------
$TEST_MODE = true;
$test_results = [];

#-------------------------------------------------------------------------------
# PREP VARIABLES & VALIDATION
#-------------------------------------------------------------------------------

$errormessage = '';
$show_error_page = false;

// Get parameters
$bid = isset($_REQUEST['bid']) ? $qik->decodeId($_REQUEST['bid']) : null;
$test_user_id = isset($_REQUEST['test_user_id']) ? intval($_REQUEST['test_user_id']) : null;

// Always use user_id = 20 for test data as requested
$test_user_id = 20;

// Generate random suffix for email and username to prevent collisions
$random_suffix = strtoupper(substr(md5(uniqid(rand(), true)), 0, 5));

// Fetch actual user data for testing
$test_user_data = $account->getuserdata($test_user_id, 'user_id');

if (!$test_user_data) {
    $errormessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error:</strong> User ID ' . $test_user_id . ' not found.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

$admin_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');

#-------------------------------------------------------------------------------
# HANDLE FORM ACTIONS IN TEST MODE
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    $action = $_REQUEST['action'] ?? null;
    
    if ($action === 'test_enrollment') {
        $test_results['form_posted'] = true;
        $test_results['action'] = $action;
        $test_results['business_id'] = $bid;
        $test_results['timestamp'] = date('Y-m-d H:i:s');
        
        // Simulate success without database write
        $errormessage = '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>TEST MODE:</strong> Enrollment would have been processed successfully. No data was saved.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
}

#-------------------------------------------------------------------------------
# GET BUSINESS LIST FOR TESTING
#-------------------------------------------------------------------------------

// Fetch companies that have form field mappings for testing
$sql = "SELECT DISTINCT c.company_id, c.company_name, c.company_display_name, c.signup_url, 
        c.description, c.status as company_status,
        COUNT(DISTINCT ffm.mapping_id) as mapping_count,
        GROUP_CONCAT(DISTINCT ffm.user_field_name ORDER BY ffm.user_field_name SEPARATOR ', ') as mapped_fields,
        GROUP_CONCAT(DISTINCT CONCAT(CASE WHEN ca.name IS NOT NULL THEN CONCAT('**', ca.name, '**: ') ELSE '' END, 
        COALESCE(ca.description, ''), CASE WHEN ca.grouping IS NOT NULL THEN CONCAT(' [', ca.grouping, ']') ELSE '' END) 
        ORDER BY COALESCE(ca.rank, '999999') ASC, ca.create_dt ASC SEPARATOR '\n') as enrollment_hints
        FROM bg_companies AS c
        INNER JOIN bg_form_field_mappings AS ffm ON c.company_id = ffm.company_id 
            AND ffm.version_status = 'active' AND ffm.status = 'active'
        LEFT JOIN bg_company_attributes AS ca ON c.company_id = ca.company_id 
            AND ca.type = 'enroller-hint' AND ca.status = 'active'
        WHERE c.signup_url IS NOT NULL 
        AND c.signup_url != ''
        AND c.signup_url != 'APP ONLY'
        GROUP BY c.company_id
        HAVING mapping_count > 0
        ORDER BY c.company_name ASC";

$stmt = $database->prepare($sql);
$stmt->execute();
$available_companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if we have companies with mappings
$has_companies_with_mappings = !empty($available_companies);

// Get specific business details if selected
$business = null;
if (!empty($bid)) {
    // Get company details with enrollment hints
    $sql = "SELECT c.*, 
            GROUP_CONCAT(DISTINCT CONCAT(CASE WHEN ca.name IS NOT NULL THEN CONCAT('**', ca.name, '**: ') ELSE '' END, 
            COALESCE(ca.description, ''), CASE WHEN ca.grouping IS NOT NULL THEN CONCAT(' [', ca.grouping, ']') ELSE '' END) 
            ORDER BY COALESCE(ca.rank, '999999') ASC, ca.create_dt ASC SEPARATOR '\n') as enrollment_hints
            FROM bg_companies AS c
            LEFT JOIN bg_company_attributes AS ca ON c.company_id = ca.company_id 
                AND ca.type = 'enroller-hint' AND ca.status = 'active'
            WHERE c.company_id = :company_id
            GROUP BY c.company_id";
    
    $stmt = $database->prepare($sql);
    $stmt->execute(['company_id' => $bid]);
    $business = $stmt->fetch(PDO::FETCH_ASSOC);
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = ''; // This removes the my-4 margin from the row after nav
$header_flush = true; // Ensure header content is flush with admin header

// Admin header styles
$additionalstyles = '
<style>
/* Ensure content header is flush with navbar */
.content-header-admin {
    margin-top: 0 !important;
}

/* Remove the row div spacing after navbar */
.navbar + .row {
    margin: 0 !important;
    padding: 0 !important;
    height: 0 !important;
}

/* Force admin header to be flush */
.navbar + .row + .content-header-admin {
    margin-top: 0 !important;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="content-header-admin no-rounded-corners">
    <div class="container">
        <h1 class="mt-3">Test Enrollment Process</h1>
        <p class="lead mb-4">Test ABO form field mapping without database pollution</p>
    </div>
</div>

<?php
echo '<section class="main-content">
<div class="container mt-4">
';

// Header buttons
echo '
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="alert alert-warning mb-0">
        <i class="bi bi-exclamation-triangle"></i> <strong>Test Mode Active:</strong> No data will be saved to the database.
    </div>
    <div>
        <a href="/admin/bgreb_v3/enrollment-listv2" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Real Enrollments
        </a>
    </div>
</div>

<div class="row">
';

// Display any messages
if (!empty($errormessage)) {
    echo '<div class="col-12">' . $errormessage . '</div>';
}

echo '</div><div class="row">';

// Left Panel - Company List
echo '
<div class="col-md-3">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Select Company to Test</h5>
        </div>
        <div class="card-body p-0">
            <div class="p-2 border-bottom">
                <input type="text" class="form-control" id="companySearch" placeholder="Search companies..." onkeyup="filterCompanies()">
            </div>
            <div class="list-group list-group-flush" id="companyList" style="max-height: 550px; overflow-y: auto;">';

if (empty($available_companies)) {
    echo '
    <div class="p-4 text-center text-muted">
        <i class="bi bi-info-circle display-4"></i>
        <p class="mt-3">No companies with form field mappings found.</p>
        <small>Companies need to have active form field mappings to test enrollment.</small>
    </div>';
} else {
    foreach ($available_companies as $company) {
    $active_class = ($bid == $company['company_id']) ? 'active' : '';
    $status_badge_class = '';
    switch($company['company_status']) {
        case 'finalized':
            $status_badge_class = 'bg-success';
            break;
        case 'active':
            $status_badge_class = 'bg-primary';
            break;
        case 'pending':
            $status_badge_class = 'bg-warning';
            break;
        case 'inactive':
            $status_badge_class = 'bg-secondary';
            break;
        default:
            $status_badge_class = 'bg-dark';
    }
    
    echo '
    <a href="?bid=' . $qik->encodeId($company['company_id']) . '" 
       class="list-group-item list-group-item-action company-item ' . $active_class . '"
       data-company-name="' . htmlspecialchars(strtolower($company['company_display_name'])) . '"
       data-company-id="' . htmlspecialchars(strtolower($company['company_id'])) . '">
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <h6 class="mb-1">' . htmlspecialchars($company['company_display_name']) . '</h6>
                <small class="text-muted d-block">' . htmlspecialchars($company['company_id']) . '</small>
                <small class="text-success">' . $company['mapping_count'] . ' field mappings</small>
            </div>
            <span class="badge ' . $status_badge_class . ' rounded-pill">' . 
                htmlspecialchars($company['company_status']) . '</span>
        </div>
    </a>';
    }
}

echo '
            </div>
        </div>
    </div>
</div>';

// Right Panel - Test Interface
echo '<div class="col-md-9">';

if (empty($bid)) {
    // No company selected
    echo '
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-arrow-left-circle display-1 text-muted"></i>
            <h3 class="mt-3">Select a Company to Test</h3>
            <p class="text-muted">Choose a company from the list to test its enrollment form mapping</p>
        </div>
    </div>';
} else if (!$business) {
    // Company ID provided but not found
    echo '
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-exclamation-triangle display-1 text-danger"></i>
            <h3 class="mt-3">Company Not Found</h3>
            <p class="text-muted">The selected company could not be found or does not have form field mappings.</p>
            <a href="?" class="btn btn-primary">Back to Company List</a>
        </div>
    </div>';
} else {
    // Company selected - show test interface
    echo '
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">Testing: ' . htmlspecialchars($business['company_display_name'] ?? 'Unknown Company') . '</h5>
        </div>
        <div class="card-body">';
    
    // Company details
    echo '
        <div class="row mb-4">
            <div class="col-md-8">
                <h6>Signup URL:</h6>
                <p class="text-break"><a href="' . htmlspecialchars($business['signup_url'] ?? '#') . '" target="_blank">' . 
                    htmlspecialchars($business['signup_url'] ?? 'No URL') . '</a></p>
            </div>
            <div class="col-md-4 text-center">
                <img src="' . $display->companyimage($business['company_id'] ?? 0) . '" 
                     alt="' . htmlspecialchars($business['company_display_name'] ?? 'Unknown') . '" 
                     class="img-fluid" style="max-height: 100px;">
            </div>
        </div>';
    
    // Test user profile
    echo '
        <h6 class="border-bottom pb-2 mb-3">Test User Profile</h6>
        <div class="alert alert-warning mb-3">
            <strong>Test Mode:</strong> The email will be replaced with a unique @birthday-gold.xyz address to prevent accidental enrollments.
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr><td><strong>User ID:</strong></td><td>' . htmlspecialchars($test_user_data['user_id']) . '</td></tr>
                    <tr><td><strong>Name:</strong></td><td>' . htmlspecialchars($test_user_data['profile_first_name'] . ' ' . $test_user_data['profile_last_name']) . '</td></tr>
                    <tr><td><strong>Real Email:</strong></td><td><s class="text-muted">' . htmlspecialchars($test_user_data['profile_email']) . '</s></td></tr>
                    <tr><td><strong>Test Email:</strong></td><td class="text-success">test-' . $test_user_data['user_id'] . '-' . $random_suffix . '@birthday-gold.xyz</td></tr>
                    <tr><td><strong>Phone:</strong></td><td>' . htmlspecialchars($test_user_data['profile_phone_number']) . '</td></tr>
                    <tr><td><strong>Birthdate:</strong></td><td>' . htmlspecialchars($test_user_data['birthdate']) . '</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr><td><strong>Address:</strong></td><td>' . htmlspecialchars($test_user_data['profile_mailing_address']) . '</td></tr>
                    <tr><td><strong>City:</strong></td><td>' . htmlspecialchars($test_user_data['profile_city']) . '</td></tr>
                    <tr><td><strong>State:</strong></td><td>' . htmlspecialchars($test_user_data['profile_state']) . '</td></tr>
                    <tr><td><strong>ZIP:</strong></td><td>' . htmlspecialchars($test_user_data['profile_zip_code']) . '</td></tr>
                    <tr><td><strong>Username:</strong></td><td class="text-success">testuser_' . $test_user_data['user_id'] . '_' . $random_suffix . '</td></tr>
                    <tr><td><strong>Password:</strong></td><td class="text-success">TestPass123!</td></tr>
                </table>
            </div>
        </div>';
    
    // Show mapped fields information
    echo '
        <h6 class="border-bottom pb-2 mb-3">Form Field Mappings</h6>';
    
    // Get the mapped fields for this company
    $mapping_sql = "SELECT user_field_name, website_field_name, fieldformattype 
                    FROM bg_form_field_mappings 
                    WHERE company_id = :company_id 
                    AND version_status = 'active' 
                    AND status = 'active'
                    ORDER BY user_field_name";
    $stmt = $database->prepare($mapping_sql);
    $stmt->execute(['company_id' => $bid]);
    $field_mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($field_mappings)) {
        echo '<div class="table-responsive mb-3">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>User Field</th>
                        <th>Website Field</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($field_mappings as $mapping) {
            echo '<tr>
                <td><code>' . htmlspecialchars($mapping['user_field_name']) . '</code></td>
                <td><code>' . htmlspecialchars($mapping['website_field_name']) . '</code></td>
                <td><small>' . htmlspecialchars($mapping['fieldformattype'] ?: 'text') . '</small></td>
            </tr>';
        }
        echo '</tbody></table></div>';
    }
    
    // Enrollment hints if available
    if (!empty($business['enrollment_hints'])) {
        echo '
        <h6 class="border-bottom pb-2 mb-3">Enrollment Hints</h6>
        <div class="alert alert-info">
            ' . nl2br(htmlspecialchars($business['enrollment_hints'])) . '
        </div>';
    }
    
    // Test actions
    echo '
        <div class="d-grid gap-2">
            <button class="btn btn-primary btn-lg" onclick="openEnrollmentWindow(\'' . 
                htmlspecialchars($business['signup_url'] ?? '') . '\', \'' . 
                $test_user_data['user_id'] . '\', \'' . 
                $admin_user_data['user_id'] . '\', \'' . 
                ($business['company_id'] ?? $bid) . '\', \'' . 
                $random_suffix . '\')">
                <i class="bi bi-window"></i> Open Enrollment Form in New Window
            </button>
            
            <div class="alert alert-info mt-3">
                <h6 class="alert-heading">Testing Instructions:</h6>
                <ol class="mb-0">
                    <li>Make sure the BGRAB Chrome Extension is installed and active</li>
                    <li>Click the button above to open the enrollment form</li>
                    <li>The BGRAB Chrome Extension should detect the form and offer to fill it</li>
                    <li>Check if the form fields are mapped correctly</li>
                    <li>You can submit the form on the business site - it will not affect our database</li>
                    <li>Use the buttons below to simulate recording the result</li>
                </ol>
                <hr>
                <small class="text-muted">
                    <strong>Technical Note:</strong> For testing, you can verify the enrollment data at:<br>
                    <code>/api/test-enrollment-data.php?uid=[userId]&aid=[adminId]&cid=[companyId]</code><br>
                    This endpoint returns test data with randomized @birthday-gold.xyz emails to prevent collisions.<br>
                    <strong>Random Suffix:</strong> ' . $random_suffix . '<br>
                    <strong>Note:</strong> The Chrome Extension may need to be configured to use this test endpoint.
                </small>
            </div>
            
            <form method="post" action="' . $_SERVER['PHP_SELF'] . '" class="mt-3">
                ' . $display->input_csrftoken() . '
                <input type="hidden" name="action" value="test_enrollment">
                <input type="hidden" name="bid" value="' . $qik->encodeId($business['company_id']) . '">
                
                <div class="row">
                    <div class="col-md-6">
                        <button type="submit" name="result" value="success" class="btn btn-success w-100">
                            <i class="bi bi-check-circle"></i> Simulate Success
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button type="submit" name="result" value="failure" class="btn btn-danger w-100">
                            <i class="bi bi-x-circle"></i> Simulate Failure
                        </button>
                    </div>
                </div>
            </form>
        </div>';
    
    echo '
        </div>
    </div>';
    
    // Debug information
    if (!empty($test_results)) {
        echo '
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h6 class="card-title mb-0">Debug Information</h6>
            </div>
            <div class="card-body">
                <pre>' . htmlspecialchars(json_encode($test_results, JSON_PRETTY_PRINT)) . '</pre>
            </div>
        </div>';
    }
}

echo '</div>'; // End right panel

echo '
</div>
</div>
</section>';

// Add JavaScript for test enrollment
echo '
<script>
// Company search function
function filterCompanies() {
    const searchInput = document.getElementById("companySearch");
    const filter = searchInput.value.toLowerCase();
    const companyItems = document.getElementsByClassName("company-item");
    
    for (let i = 0; i < companyItems.length; i++) {
        const item = companyItems[i];
        const companyName = item.getAttribute("data-company-name");
        const companyId = item.getAttribute("data-company-id");
        
        if (companyName.includes(filter) || companyId.includes(filter)) {
            item.style.display = "";
        } else {
            item.style.display = "none";
        }
    }
}

function openEnrollmentWindow(signupUrl, userId, aid, bid, randomSuffix) {
    console.log("openEnrollmentWindow called with:", {signupUrl, userId, aid, bid, randomSuffix});
    
    // Ensure we have valid parameters
    if (!signupUrl || !userId || !aid) {
        console.error("Missing required parameters:", {signupUrl, userId, aid, bid, randomSuffix});
        return;
    }
    
    try {
        // Use uid=-1 to trigger test mode, pass real user ID as real_uid parameter
        const testUserId = -1;
        
        // First process the user data so extension has it with test mode trigger
        processUser(testUserId, aid, bid, randomSuffix);
        
        // For test mode, use uid=-1 which bgr_getprocessdetails.php will recognize as test mode
        const extensionDataUrl = "/admin/bgreb_v3/bgr_getprocessdetails.php?type=bgrab&uid=-1&aid=" + aid + "&cid=" + bid + "&suffix=" + randomSuffix;
        console.log("Extension will fetch data from:", extensionDataUrl);
        console.log("Test mode activated with uid=-1");
        
        // Then open in specific window named "enrollerwindow"
        const enrollWindow = window.open(signupUrl, "enrollerwindow", 
            "width=1024,height=1200,toolbar=yes,scrollbars=yes,menubar=yes,resizable=yes,status=yes");
            
        // Ensure window opened successfully
        if (enrollWindow) {
            console.log("Window opened successfully");
            enrollWindow.focus();
        } else {
            console.error("Popup was blocked");
            alert("Please allow popups for this site to enroll in rewards programs.");
        }
    } catch (error) {
        console.error("Error opening enrollment window:", error);
    }
}

// Include the processUser function if not already loaded
if (typeof processUser === "undefined") {
    function processUser(userId, aid, bid, randomSuffix) {
        console.log("TEST MODE: processUser called with:", { userId, aid, bid, randomSuffix });
        
        userId = parseInt(userId, 10);
        aid = parseInt(aid, 10);
        bid = parseInt(bid, 10);
        
        const event = new CustomEvent("processUser", {
            detail: {
                userId: userId,  // This will be -1 for test mode
                aid: aid,
                bid: bid,
                mode: "desktop",
                testMode: true,
                randomSuffix: randomSuffix
            },
            bubbles: true
        });
        
        console.log("TEST MODE: Dispatching event with uid=-1 for test mode:", event);
        document.dispatchEvent(event);
    }
}
</script>';

$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();