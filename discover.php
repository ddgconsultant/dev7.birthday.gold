<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$showmore=false;
$listlimit=64;
if (isset($_GET['more'])) {
    $showmore=true;
$listlimit=128;
}
$enablesearch=false;

$loop_companies = $app->getFeaturedCompanies($listlimit, '!!alphabetical!!');



#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
echo '
<!-- Hero Section -->
<div class="content-header-dark">
    <div class="container">
        <h1>Discover <span class="highlight">'.$website['numberofbiz'].'+ '.ucfirst($website['biznames']).'</span></h1>
        <p class="lead">Pick Your Favorites and We\'ll Handle the Enrollment!</p>';

    // Conditionally display signup button only if user is not logged in
    if (!$account->isactive()) {
        echo '<a href="/signup" class="btn btn-warning btn-lg mt-3" style="position: relative; z-index: 10; border-radius: 50px; padding: 12px 32px;">Sign Up to See Them All!</a>';
    }

echo '
    </div>
</div>

<div class="container mt-5">';


if ($enablesearch)
{        echo '
        <div class="row">
            <input type="text" id="searchBar" class="form-control mb-4" placeholder="Search '.ucfirst($website['biznames']).'...">
        </div>
        ';
}
    echo     '
        <div class="row" id="logoGallery">
';


foreach ($loop_companies as $index => $item_company) {
    // Load first 8 images immediately, lazy load the rest
    $isEager = $index < 8;
    $imgSrc = $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']);
    
    echo '<div class="col-6 col-md-4 col-lg-3 logo-item mb-3">
<div class="card h-100">
<div class="logo-image-wrapper d-flex align-items-center justify-content-center" style="min-height: 150px; background: #f8f9fa;">
<img class="img-fluid lazy-image" 
     ' . ($isEager ? 'src="' . $imgSrc . '"' : 'data-src="' . $imgSrc . '" src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect width=\'100\' height=\'100\' fill=\'%23f8f9fa\'/%3E%3C/svg%3E"') . ' 
     loading="' . ($isEager ? 'eager' : 'lazy') . '" 
     alt="' . htmlspecialchars($item_company['company_name']) . ' logo"
     style="width: 100%; height: auto; object-fit: contain;">
</div>
<div class="card-body">
<h5 class="card-title">' . $item_company['company_name'] . '</h5>
</div>
</div>
</div>';
}
?>

</div>
</div>
<?php
$output= $display->backtotop();
$additionalstyles.=$output['style'];
echo $output['content'];

if (!$showmore) 
echo '       <div class="container">
        <div class="text-center mx-auto mb-5">
           <a href="'.$_SERVER['PHP_SELF'].'?more" class="btn btn-primary btn-lg" style="border-radius: 50px; padding: 12px 32px;">Load More!</a>
           </div>
</div>
  ';
if ($enablesearch)
{        echo "

<script>
    $(document).ready(function() {
        $('#searchBar').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#logoGallery .logo-item').filter(function() {
                $(this).toggle($(this).find('.card-title').text().toLowerCase().includes(value));
            });
        });
    });
</script>
";
}

// Add lazy loading script
echo '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Check if native lazy loading is supported
    if ("loading" in HTMLImageElement.prototype) {
        // Native lazy loading is supported, just load data-src images
        const lazyImages = document.querySelectorAll("img[data-src]");
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute("data-src");
        });
    } else {
        // Fallback for browsers that do not support native lazy loading
        const lazyImages = document.querySelectorAll("img[data-src]");
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute("data-src");
                    img.classList.add("loaded");
                    imageObserver.unobserve(img);
                }
            });
        }, {
            rootMargin: "50px 0px" // Start loading 50px before the image enters viewport
        });

        lazyImages.forEach(function(img) {
            imageObserver.observe(img);
        });
    }
});

// Add smooth fade-in effect
</script>

<style>
.lazy-image {
    transition: opacity 0.3s ease-in-out;
}
.lazy-image:not(.loaded) {
    opacity: 0.8;
}
.lazy-image.loaded {
    opacity: 1;
}
.logo-image-wrapper {
    overflow: hidden;
}
.logo-item .card {
    height: 100%;
    transition: transform 0.2s ease-in-out;
}
.logo-item .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>
';

include($dir['core_components'] . '/bg_footer.inc');
      $app->outputpage();
