<?php
// Check bg_company_attributes table structure
$_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
require_once($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "<h3>Checking bg_company_attributes table structure</h3>";

// Get column information
$columns = $database->getrows("SHOW COLUMNS FROM bg_company_attributes");

echo "<h4>Columns in bg_company_attributes:</h4>";
echo "<ul>";
foreach ($columns as $col) {
    echo "<li>" . $col['Field'] . " - " . $col['Type'] . "</li>";
}
echo "</ul>";

// Get sample data
echo "<h4>Sample data from bg_company_attributes (first 5 rows with category='company_logos'):</h4>";
$sample = $database->getrows("
    SELECT * 
    FROM bg_company_attributes 
    WHERE category = 'company_logos'
    LIMIT 5
");

echo "<pre>";
print_r($sample);
echo "</pre>";

// Count logos
echo "<h4>Logo counts:</h4>";
$counts = $database->getrow("
    SELECT COUNT(*) as total,
           COUNT(DISTINCT company_id) as unique_companies
    FROM bg_company_attributes
    WHERE category = 'company_logos'
");

echo "Total logo records: " . $counts['total'] . "<br>";
echo "Unique companies with logos: " . $counts['unique_companies'] . "<br>";
?>