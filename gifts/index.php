<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------

// Get the category and slug from the URL parameters, defaulting to 'birthday'
$category = isset($_GET['category']) ? $_GET['category'] : 'birthday';
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

// Prepare SQL query to fetch content based on category and optional slug
$sql = "SELECT * FROM bg_content WHERE category = :category";
$params = [':category' => $category];

if (!empty($slug)) {
    $sql .= " AND label = :slug";
    $params[':slug'] = $slug;
}

$stmt = $database->prepare($sql);
$stmt->execute($params);
$content_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if content is found
if (empty($content_results)) {
    echo '<h1>Content Not Found</h1>';
    exit();
}

$content = $content_results[0]; // Fetch the first result for the given category/slug

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Optionally add additional styles for this page
$additionalstyles .= '
<style>
    .gift-list {
        padding-left: 20px;
    }
    .gift-list li {
        margin-bottom: 10px;
    }
</style>
';

// Begin outputting the page content
echo '
<div class="container">
    <h1>' . htmlspecialchars($content['display_name']) . '</h1>
    <p>' . htmlspecialchars($content['description']) . '</p>
    <div class="content-body">
        ' . $content['content'] . '
    </div>
';

// Fetch subcategories for navigation (e.g., "For Him", "For Her")
$sub_stmt = $database->prepare("SELECT * FROM bg_content WHERE category = :category AND type = 'subcategory'");
$sub_stmt->execute([':category' => $category]);
$subcategories = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($subcategories)) {
    echo '
    <h3>Explore More Gift Ideas:</h3>
    <ul class="gift-list">
    ';
    foreach ($subcategories as $subcategory) {
        echo '
            <li>
                <a href="/page.php?category=' . htmlspecialchars($category) . '&slug=' . htmlspecialchars($subcategory['label']) . '">
                    ' . htmlspecialchars($subcategory['display_name']) . '
                </a>
            </li>
        ';
    }
    echo '
    </ul>
    ';
}

// Fetch reviews link for the category (e.g., "See all reviews")
$reviews_stmt = $database->prepare("SELECT * FROM bg_content WHERE category = :category AND type = 'reviews'");
$reviews_stmt->execute([':category' => $category]);
$review_link = $reviews_stmt->fetch(PDO::FETCH_ASSOC);

if ($review_link) {
    echo '
    <h3>See All Reviews:</h3>
    <a href="/page.php?category=' . htmlspecialchars($category) . '&slug=' . htmlspecialchars($review_link['label']) . '">
        ' . htmlspecialchars($review_link['display_name']) . '
    </a>
    ';
}

echo '
</div>
';

// Include the footer and output the page
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
