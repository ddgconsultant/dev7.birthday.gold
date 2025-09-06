<?php
// Test page for CTA block debugging
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


// Test parameters
$test_category = isset($_GET['category']) ? $_GET['category'] : 'restaurant';
$test_mode = isset($_GET['mode']) ? $_GET['mode'] : 'inclusive';
$test_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $current_user_data['user_id'];

// Page header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container mt-4">
    <h1>CTA Block Test & Debug Page</h1>
    
    <!-- Test Controls -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Test Parameters</h3>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="Food" <?= $test_category == 'Food' ? 'selected' : '' ?>>Food</option>
                        <option value="Retail" <?= $test_category == 'Retail' ? 'selected' : '' ?>>Retail</option>
                        <option value="restaurant" <?= $test_category == 'restaurant' ? 'selected' : '' ?>>Restaurant (legacy)</option>
                        <option value="pizza" <?= $test_category == 'pizza' ? 'selected' : '' ?>>Pizza</option>
                        <option value="coffee" <?= $test_category == 'coffee' ? 'selected' : '' ?>>Coffee</option>
                        <option value="beauty" <?= $test_category == 'beauty' ? 'selected' : '' ?>>Beauty</option>
                        <option value="entertainment" <?= $test_category == 'entertainment' ? 'selected' : '' ?>>Entertainment</option>
                        <option value="health" <?= $test_category == 'health' ? 'selected' : '' ?>>Health</option>
                        <option value="other" <?= $test_category == 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mode</label>
                    <select name="mode" class="form-select">
                        <option value="inclusive" <?= $test_mode == 'inclusive' ? 'selected' : '' ?>>Inclusive (User's brands)</option>
                        <option value="exclusive" <?= $test_mode == 'exclusive' ? 'selected' : '' ?>>Exclusive (Other brands)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">User ID</label>
                    <input type="number" name="user_id" class="form-control" value="<?= $test_user_id ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">Test</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Database Analysis -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Database Analysis</h3>
        </div>
        <div class="card-body">
            <?php
            // Check what categories exist in database
            $category_check = $database->getrows("
                SELECT display_category, COUNT(*) as count 
                FROM bg_companies 
                WHERE status = 'finalized' 
                GROUP BY display_category 
                ORDER BY count DESC
            ");
            
            echo '<h5>Available Categories in Database:</h5>';
            echo '<table class="table table-sm">';
            echo '<thead><tr><th>Category</th><th>Count</th></tr></thead>';
            echo '<tbody>';
            foreach ($category_check as $cat) {
                $cat_display = $cat['display_category'] ?: '(NULL/Empty)';
                echo '<tr><td>' . htmlspecialchars($cat_display) . '</td><td>' . $cat['count'] . '</td></tr>';
            }
            echo '</tbody></table>';
            
            // Check total finalized companies
            $total_companies = $database->getrow("SELECT COUNT(*) as count FROM bg_companies WHERE status = 'finalized'");
            echo '<p><strong>Total Finalized Companies:</strong> ' . $total_companies['count'] . '</p>';
            
            // Check if bg_company_attributes table has logo data
            try {
                $logo_check = $database->getrow("
                    SELECT COUNT(*) as total,
                           COUNT(DISTINCT company_id) as companies_with_logos
                    FROM bg_company_attributes
                    WHERE category = 'company_logos' 
                    AND `grouping` = 'primary_logo'
                ");
                echo '<p><strong>Logo Data Stats:</strong><br>';
                echo 'Total Logo Records: ' . $logo_check['total'] . '<br>';
                echo 'Companies with Logos: ' . $logo_check['companies_with_logos'] . '</p>';
            } catch (Exception $e) {
                echo '<p class="text-danger">Logo data check failed: ' . $e->getMessage() . '</p>';
            }
            ?>
        </div>
    </div>

    <!-- User Analysis -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>User Analysis (ID: <?= $test_user_id ?>)</h3>
        </div>
        <div class="card-body">
            <?php
            // Get user data
            $user_data = $database->getrow("
                SELECT u.user_id, u.first_name, u.last_name, u.email, u.city, u.state, u.birthdate,
                       GROUP_CONCAT(DISTINCT e.company_id) as enrolled_company_ids
                FROM bg_users u
                LEFT JOIN bg_user_enrollments e ON u.user_id = e.user_id AND e.status = 'success'
                WHERE u.user_id = :user_id
                GROUP BY u.user_id
            ", ['user_id' => $test_user_id]);
            
            if ($user_data) {
                echo '<p><strong>User:</strong> ' . htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']) . ' (' . htmlspecialchars($user_data['email']) . ')</p>';
                echo '<p><strong>Location:</strong> ' . htmlspecialchars($user_data['city'] . ', ' . $user_data['state']) . '</p>';
                echo '<p><strong>Birthdate:</strong> ' . htmlspecialchars($user_data['birthdate']) . '</p>';
                echo '<p><strong>Enrolled Company IDs:</strong> ' . ($user_data['enrolled_company_ids'] ?: 'None') . '</p>';
                
                // If user has enrollments, show company details
                if ($user_data['enrolled_company_ids']) {
                    $company_ids = explode(',', $user_data['enrolled_company_ids']);
                    $placeholders = array_map(function($i) { return ':id_' . $i; }, array_keys($company_ids));
                    $params = array_combine(
                        array_map(function($i) { return 'id_' . $i; }, array_keys($company_ids)),
                        $company_ids
                    );
                    
                    $enrolled_companies = $database->getrows("
                        SELECT company_id, company_name, display_category, status
                        FROM bg_companies
                        WHERE company_id IN (" . implode(',', $placeholders) . ")
                    ", $params);
                    
                    echo '<h5>User\'s Enrolled Companies:</h5>';
                    echo '<table class="table table-sm">';
                    echo '<thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Status</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($enrolled_companies as $comp) {
                        echo '<tr>';
                        echo '<td>' . $comp['company_id'] . '</td>';
                        echo '<td>' . htmlspecialchars($comp['company_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($comp['display_category'] ?: '(none)') . '</td>';
                        echo '<td>' . $comp['status'] . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                }
            } else {
                echo '<p class="text-danger">User not found!</p>';
            }
            
            $userEnrollments = !empty($user_data['enrolled_company_ids']) 
                ? explode(',', $user_data['enrolled_company_ids']) 
                : [];
            ?>
        </div>
    </div>

    <!-- Marketing Class Test -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Marketing Class getCompaniesForCTA() Test</h3>
        </div>
        <div class="card-body">
            <?php
            echo '<p><strong>Testing with:</strong><br>';
            echo 'Category: ' . $test_category . '<br>';
            echo 'Mode: ' . $test_mode . '<br>';
            echo 'User Enrollments: ' . (!empty($userEnrollments) ? implode(', ', $userEnrollments) : 'None') . '</p>';
            
            // Call the marketing class method
            $companies = $marketing->getCompaniesForCTA(
                $test_category,
                $test_mode,
                $userEnrollments,
                4
            );
            
            echo '<h5>Result: ' . count($companies) . ' companies returned</h5>';
            
            if (!empty($companies)) {
                echo '<div class="row">';
                foreach ($companies as $company) {
                    echo '<div class="col-md-3 mb-3">';
                    echo '<div class="card">';
                    echo '<div class="card-body">';
                    echo '<h6>' . htmlspecialchars($company['company_name']) . '</h6>';
                    echo '<p class="small">ID: ' . $company['company_id'] . '</p>';
                    echo '<p class="small">Offer: ' . htmlspecialchars(($company['offer_text'] ?? $company['description'] ?? '(none)')) . '</p>';
                    echo '<p class="small">Logo: ' . ($company['logo'] ? 'Yes' : 'No') . '</p>';
                    if ($company['logo']) {
                        echo '<img src="' . htmlspecialchars($company['logo']) . '" style="max-width: 100%; max-height: 100px;" alt="Logo">';
                    }
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<p class="text-warning">No companies returned!</p>';
            }
            ?>
        </div>
    </div>

    <!-- Direct SQL Tests -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Direct SQL Tests</h3>
        </div>
        <div class="card-body">
            <?php
            // Test 1: Simple query for category
            echo '<h5>Test 1: Simple Category Query</h5>';
            $sql1 = "SELECT company_id, company_name, display_category 
                     FROM bg_companies 
                     WHERE status = 'finalized' 
                     AND display_category = :category 
                     LIMIT 5";
            
            echo '<pre class="bg-light p-2">SQL: ' . htmlspecialchars($sql1) . '</pre>';
            echo '<p>Parameters: category = ' . $test_category . '</p>';
            
            try {
                $result1 = $database->getrows($sql1, ['category' => $test_category]);
                echo '<p>Results: ' . count($result1) . ' rows</p>';
                if (!empty($result1)) {
                    echo '<ul>';
                    foreach ($result1 as $row) {
                        echo '<li>' . $row['company_id'] . ' - ' . htmlspecialchars($row['company_name']) . ' (' . htmlspecialchars($row['display_category']) . ')</li>';
                    }
                    echo '</ul>';
                }
            } catch (Exception $e) {
                echo '<p class="text-danger">Error: ' . $e->getMessage() . '</p>';
            }
            
            // Test 2: Query with logo join from bg_company_attributes
            echo '<h5>Test 2: Query with Logo Join from bg_company_attributes</h5>';
            $sql2 = "SELECT c.company_id, c.company_name, c.display_category,
                            a.description as logo_filename
                     FROM bg_companies c
                     LEFT JOIN bg_company_attributes a ON c.company_id = a.company_id 
                         AND a.category = 'company_logos' AND a.`grouping` = 'primary_logo'
                     WHERE c.status = 'finalized'
                     AND c.display_category = :category
                     LIMIT 5";
            
            echo '<pre class="bg-light p-2">SQL: ' . htmlspecialchars($sql2) . '</pre>';
            
            try {
                $result2 = $database->getrows($sql2, ['category' => $test_category]);
                echo '<p>Results: ' . count($result2) . ' rows</p>';
                if (!empty($result2)) {
                    echo '<table class="table table-sm">';
                    echo '<thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Logo File</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($result2 as $row) {
                        echo '<tr>';
                        echo '<td>' . $row['company_id'] . '</td>';
                        echo '<td>' . htmlspecialchars($row['company_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['display_category']) . '</td>';
                        echo '<td>' . ($row['logo_filename'] ? substr($row['logo_filename'], 0, 30) . '...' : 'No') . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                }
            } catch (Exception $e) {
                echo '<p class="text-danger">Error: ' . $e->getMessage() . '</p>';
            }
            
            // Test 3: Get ANY companies (no category filter)
            echo '<h5>Test 3: Get ANY Active Companies (No Category Filter)</h5>';
            $sql3 = "SELECT company_id, company_name, display_category, description
                     FROM bg_companies 
                     WHERE status = 'finalized'
                     ORDER BY RAND()
                     LIMIT 10";
            
            echo '<pre class="bg-light p-2">SQL: ' . htmlspecialchars($sql3) . '</pre>';
            
            try {
                $result3 = $database->getrows($sql3);
                echo '<p>Results: ' . count($result3) . ' rows</p>';
                if (!empty($result3)) {
                    echo '<table class="table table-sm">';
                    echo '<thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Offer</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($result3 as $row) {
                        echo '<tr>';
                        echo '<td>' . $row['company_id'] . '</td>';
                        echo '<td>' . htmlspecialchars($row['company_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['display_category'] ?: '(none)') . '</td>';
                        echo '<td>' . htmlspecialchars(substr($row['description'] ?: '(none)', 0, 50)) . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<p class="text-danger">No active companies found at all!</p>';
                }
            } catch (Exception $e) {
                echo '<p class="text-danger">Error: ' . $e->getMessage() . '</p>';
            }
            ?>
        </div>
    </div>

    <!-- AJAX Test -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>AJAX Endpoint Test</h3>
        </div>
        <div class="card-body">
            <button id="testAjax" class="btn btn-info">Test AJAX Call</button>
            <div id="ajaxResult" class="mt-3"></div>
        </div>
    </div>
</div>

<script>
document.getElementById('testAjax').addEventListener('click', function() {
    const tokens = [
        {"type": "all", "label": "All Users", "value": "all"}
    ];
    
    const requestData = {
        tokens: JSON.stringify(tokens),
        process: 'single',
        cta_category: '<?= $test_category ?>',
        cta_mode: '<?= $test_mode ?>',
        debug: 'true'
    };
    
    console.log('Sending AJAX request:', requestData);
    
    fetch('/myaccount/marketing/ajax/newsletter-recipients-count.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(requestData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('AJAX Response:', data);
        document.getElementById('ajaxResult').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        document.getElementById('ajaxResult').innerHTML = '<p class="text-danger">Error: ' + error.message + '</p>';
    });
});
</script>

<?php

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
