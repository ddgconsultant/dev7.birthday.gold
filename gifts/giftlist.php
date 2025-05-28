<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------

// Fetch gift items for the unique or cute gifts for boyfriend section
$sql = "SELECT * FROM bg_content WHERE category = 'birthday' AND `grouping` IN ('unique-gifts-boyfriend', 'cute-gifts-boyfriend') ORDER BY `rank` ASC";
$stmt = $database->prepare($sql);
$stmt->execute();
$gifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Begin outputting the page content
echo '<div class="container"><div class="gift-section">';

$currentGift = null;
foreach ($gifts as $gift) {
    if ($currentGift !== $gift['name']) {
        // Close the previous gift item if this is a new gift
        if ($currentGift !== null) {
            echo '</div></div>'; // Close product-card-horizontal-content-wrap and product-card-horizontal-body
        }

        // Open a new gift item with updated structure
        echo '
        <div class="product-card-horizontal">
          <div class="product-card-horizontal-body">
            <div class="product-card-horizontal-image-wrap">
        ';
        $currentGift = $gift['name'];
    }

    // Output the appropriate content (image, detail, or buy link)
    if ($gift['display_name'] === 'image') {
        echo $gift['description']; // Image tag is in description
        echo '</div><div class="product-card-horizontal-content-wrap">';
    } elseif ($gift['display_name'] === 'detail') {
        echo '<h3 class="product-card-horizontal-title">' . htmlspecialchars($currentGift) . '</h3>';
        echo '<p class="product-card-horizontal-blurb">' . htmlspecialchars($gift['description']) . '</p>';
    } elseif ($gift['display_name'] === 'buylink') {
        echo '
        <a href="' . htmlspecialchars($gift['description']) . '" target="_blank" rel="noopener" class="product-card-horizontal-link">
          <div class="btn-product product-card-horizontal-button">
            <span>Buy Now</span>
          </div>
        </a>';
    }
}

// Close the last product card div
if ($currentGift !== null) {
    echo '</div></div>'; // Close product-card-horizontal-content-wrap and product-card-horizontal-body
}

echo '</div></div>'; // Close gift-section and container

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
