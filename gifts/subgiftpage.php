<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------

// Get the category and slug from the URL (e.g., ?category=birthday&slug=birthday-gifts-for-boyfriend)
$category = isset($_GET['category']) ? $_GET['category'] : 'birthday';
$slug = isset($_GET['slug']) ? $_GET['slug'] : 'birthday-gifts-for-boyfriend';

// Fetch content for the specific page
$sql = "SELECT * FROM bg_content WHERE category = :category AND label = :slug";
$stmt = $database->prepare($sql);
$stmt->execute([':category' => $category, ':slug' => $slug]);
$content_results = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if content exists
if (!$content_results) {
    echo '<h1>Content Not Found</h1>';
    exit();
}

// Fetch sections for this page (e.g., Best 3 Picks, Unique, Cute, etc.)
$section_sql = "SELECT * FROM bg_content WHERE category = :category AND grouping = :slug";
$section_stmt = $database->prepare($section_sql);
$section_stmt->execute([':category' => $category, ':slug' => $slug]);
$sections = $section_stmt->fetchAll(PDO::FETCH_ASSOC);

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Add any additional styles for this page
$additionalstyles .= '
<style>
    .section-list {
        padding-left: 20px;
    }
    .section-list li {
        margin-bottom: 10px;
    }
</style>
';

// Begin outputting the page content
echo '
<div class="container">
    <h1>' . htmlspecialchars($content_results['display_name']) . '</h1>
    <p>' . htmlspecialchars($content_results['description']) . '</p>
';

// Loop through sections and display them
echo '<div class="sections">';
foreach ($sections as $section) {
    echo '
    <div class="section">
        <h2>' . htmlspecialchars($section['display_name']) . '</h2>
        <p>' . htmlspecialchars($section['description']) . '</p>
        <div>' . $section['content'] . '</div>
    </div>
    ';
}
echo '</div>';

echo '</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
