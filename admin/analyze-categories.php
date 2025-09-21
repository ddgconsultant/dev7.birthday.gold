<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is authorized
if (!isset($current_user_data['user_id']) || $current_user_data['access_level'] < 90) {
    die("Unauthorized");
}

echo "<h2>Category Analysis</h2>";

// 1. Check distinct categories
$categories = $database->getrows("
    SELECT display_category, COUNT(*) as count 
    FROM bg_companies 
    WHERE status = 'finalized' 
    GROUP BY display_category 
    ORDER BY count DESC
");

echo "<h3>Categories in Database:</h3>";
echo "<table border='1'>";
echo "<tr><th>Category</th><th>Count</th></tr>";
foreach ($categories as $cat) {
    $display = $cat['display_category'] ?: '(NULL/empty)';
    echo "<tr><td>" . htmlspecialchars($display) . "</td><td>" . $cat['count'] . "</td></tr>";
}
echo "</table>";

// 2. Check if we're getting wrong categories
echo "<h3>Testing 'Food' Category Query:</h3>";
$food_companies = $database->getrows("
    SELECT company_id, company_name, display_category 
    FROM bg_companies 
    WHERE status = 'finalized' 
    AND display_category = 'Food'
    LIMIT 10
");

if (empty($food_companies)) {
    echo "<p>No companies with display_category = 'Food'</p>";
    
    // Check case-insensitive
    $food_companies = $database->getrows("
        SELECT company_id, company_name, display_category 
        FROM bg_companies 
        WHERE status = 'finalized' 
        AND LOWER(display_category) LIKE '%food%'
        LIMIT 10
    ");
    
    if (!empty($food_companies)) {
        echo "<p>Found with case-insensitive search:</p>";
    }
}

if (!empty($food_companies)) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Category</th></tr>";
    foreach ($food_companies as $comp) {
        echo "<tr>";
        echo "<td>" . $comp['company_id'] . "</td>";
        echo "<td>" . htmlspecialchars($comp['company_name']) . "</td>";
        echo "<td>" . htmlspecialchars($comp['display_category']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Check Target specifically
echo "<h3>Target Company Check:</h3>";
$target = $database->getrow("
    SELECT company_id, company_name, display_category, description
    FROM bg_companies
    WHERE company_name LIKE '%Target%'
    AND status = 'finalized'
");

if ($target) {
    echo "<p>Target (ID: " . $target['company_id'] . ") has category: " . htmlspecialchars($target['display_category'] ?: '(none)') . "</p>";
}

// 4. Suggest solution
echo "<h3>Proposed Solutions:</h3>";
echo "<ol>";
echo "<li><strong>Better Category Mapping:</strong> Create a category mapping table that groups related categories (e.g., 'restaurant', 'pizza', 'coffee' all map to 'Food')</li>";
echo "<li><strong>AI Selection (Premium):</strong> Use AI to select contextually appropriate companies based on message content</li>";
echo "<li><strong>Fallback Logic:</strong> If no companies match exact category, don't fall back to random companies</li>";
echo "<li><strong>Multi-Category Support:</strong> Allow companies to have multiple categories</li>";
echo "</ol>";
?>