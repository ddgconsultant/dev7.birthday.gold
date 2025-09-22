<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
// Handle AJAX requests for infinite scroll
if (isset($_GET['ajax']) && $_GET['ajax'] == 'load_more') {
    header('Content-Type: application/json');
    
    $offset = intval($_GET['offset'] ?? 0);
    $limit = intval($_GET['limit'] ?? 32);
    $max_companies = 100; // Maximum total companies to show
    
    // Check if we've already reached the max
    if ($offset >= $max_companies) {
        echo json_encode([
            'html' => '',
            'hasMore' => false,
            'loaded' => 0
        ]);
        exit;
    }
    
    // Adjust limit if it would exceed max
    if ($offset + $limit > $max_companies) {
        $limit = $max_companies - $offset;
    }
    
    // Get only the companies we need (more efficient)
    // Instead of getting all 100 and slicing, get just what we need plus a buffer
    $fetch_limit = min($offset + $limit + 32, $max_companies); // Get current batch + next batch
    $all_companies = $app->getFeaturedCompanies($fetch_limit, '!!alphabetical!!'); 
    $companies_chunk = array_slice($all_companies, $offset, $limit);
    
    $html = '';
    foreach ($companies_chunk as $index => $item_company) {
        $imgSrc = $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']);
        $actualIndex = $offset + $index;
        
        $html .= '<div class="col-6 col-md-4 col-lg-3 col-xl-5cols logo-item mb-3" data-index="' . $actualIndex . '">
<div class="card h-100">
<div class="logo-image-wrapper d-flex align-items-center justify-content-center" style="min-height: 150px; background: #f8f9fa;">
<img class="img-fluid lazy-image" 
     data-src="' . $imgSrc . '" 
     src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect width=\'100\' height=\'100\' fill=\'%23f8f9fa\'/%3E%3C/svg%3E"
     loading="lazy" 
     alt="' . htmlspecialchars($item_company['company_name']) . ' logo"
     style="width: 100%; height: auto; object-fit: contain;">
</div>
<div class="card-body">
<h5 class="card-title">' . $item_company['company_name'] . '</h5>
</div>
</div>
</div>';
    }
    
    // Check if there are more companies to load (up to max of 100)
    $hasMore = ($offset + count($companies_chunk)) < min(count($all_companies), $max_companies);
    
    echo json_encode([
        'html' => $html,
        'hasMore' => $hasMore,
        'loaded' => count($companies_chunk),
        'totalLoaded' => $offset + count($companies_chunk),
        'maxReached' => ($offset + count($companies_chunk)) >= $max_companies
    ]);
    exit;
}

$enablesearch=false;

// Initial load - only load first 32 companies (out of max 100)
$initial_limit = 32;
$max_companies_display = 100;
$loop_companies = $app->getFeaturedCompanies($initial_limit, '!!alphabetical!!');

// Debug: Check how many companies are actually returned
error_log('[DISCOVER] Initial companies loaded: ' . count($loop_companies) . ' (requested: ' . $initial_limit . ')');



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
    $imgSrc = $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']);

    echo '<div class="col-6 col-md-4 col-lg-3 col-xl-5cols logo-item mb-3" data-index="' . $index . '">
<div class="card h-100">
<div class="logo-image-wrapper d-flex align-items-center justify-content-center" style="min-height: 150px; background: #f8f9fa;">
<img class="img-fluid"
     src="' . $imgSrc . '"
     loading="lazy"
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
// Add loading indicator and scroll trigger for infinite scroll
echo '
<!-- Invisible trigger element for infinite scroll -->
<div id="scroll-trigger" style="height: 1px; margin-bottom: 100px;"></div>

<div class="container" id="loading-indicator" style="display: none;">
    <div class="text-center mx-auto mb-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading more companies...</span>
        </div>
        <p class="mt-2">Loading more companies...</p>
    </div>
</div>
<div class="container" id="end-message" style="display: none;">
    <div class="text-center mx-auto mb-5">
        <p class="text-muted">You have reached the end of the list!</p>
    </div>
</div>
';

// Add CTA for non-logged in users
if (empty($current_user_data['user_id'])) {
    echo '
<style>
.gold-shiny-btn {
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #FFD700 100%);
    border: 2px solid #FFD700;
    color: #000;
    font-weight: bold;
    text-shadow: 0 1px 2px rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.5);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.gold-shiny-btn:before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    transition: left 0.5s ease;
}

.gold-shiny-btn:hover {
    background: linear-gradient(135deg, #FFA500 0%, #FFD700 50%, #FFA500 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 215, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.5);
    color: #000;
}

.gold-shiny-btn:hover:before {
    left: 100%;
}

.green-border-card {
    border: 3px solid #28a745 !important;
    box-shadow: 0 0 20px rgba(40, 167, 69, 0.15);
}
</style>
<div class="container" id="signup-cta" style="display: none;">
    <div class="text-center mx-auto mb-5 py-5">
        <div class="card green-border-card" style="max-width: 600px; margin: 0 auto;">
            <div class="card-body p-5">
                <h3 class="mb-3">Ready to Start Collecting Birthday Rewards?</h3>
                <p class="lead mb-4">Join thousands of members who never miss their birthday freebies!</p>
                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                        <span>Automatic enrollment in ' . $website['numberofbiz'] . '+ programs</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                        <span>Never miss a birthday reward again</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                        <span>Free to join, no credit card required</span>
                    </div>
                </div>
                <a href="/signup" class="btn gold-shiny-btn btn-lg px-5 py-3" style="border-radius: 50px;">
                    Sign Up Free <i class="bi bi-arrow-right ms-2"></i>
                </a>
                <p class="mt-3 mb-0 text-muted small">Already have an account? <a href="/login">Log in</a></p>
            </div>
        </div>
    </div>
</div>
';
}

$output= $display->backtotop();
$additionalstyles.=$output['style'];
echo $output['content'];
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

// Add infinite scroll and lazy loading script
echo '
<script>
// Infinite scroll variables
let isLoading = false;
let hasMore = true;
let currentOffset = ' . $initial_limit . ';
const loadLimit = 32;
const maxCompanies = 100; // Maximum companies to display
let totalLoaded = ' . $initial_limit . ';
let imageObserver; // Declare at higher scope for reuse
let spinnerTimeout; // Declare for spinner delay

document.addEventListener("DOMContentLoaded", function() {
    // No custom lazy loading needed - browser handles it natively
    
    // Infinite scroll implementation
    function loadMoreCompanies() {
        if (isLoading || !hasMore || currentOffset >= maxCompanies) {
            console.log("Skipping load - isLoading:", isLoading, "hasMore:", hasMore, "currentOffset:", currentOffset, "maxCompanies:", maxCompanies);
            return;
        }
        
        console.log("Loading more companies from offset:", currentOffset, "Max limit:", maxCompanies);
        isLoading = true;
        // Only show spinner if the request takes more than 100ms
        let spinnerTimeout = setTimeout(() => {
            document.getElementById("loading-indicator").style.display = "block";
        }, 100);
        
        fetch(`?ajax=load_more&offset=${currentOffset}&limit=${loadLimit}`)
            .then(response => response.json())
            .then(data => {
                console.log("Received data:", data);
                const gallery = document.getElementById("logoGallery");
                
                // Create temporary container to parse HTML
                const tempDiv = document.createElement("div");
                tempDiv.innerHTML = data.html;
                
                // Append new items to gallery
                while (tempDiv.firstChild) {
                    gallery.appendChild(tempDiv.firstChild);
                }
                
                // Update lazy loading for new images
                const newLazyImages = gallery.querySelectorAll("img[data-src]:not(.loaded)");
                if ("loading" in HTMLImageElement.prototype) {
                    newLazyImages.forEach(img => {
                        img.src = img.dataset.src;
                        img.removeAttribute("data-src");
                    });
                } else if (typeof imageObserver !== "undefined") {
                    newLazyImages.forEach(img => {
                        imageObserver.observe(img);
                    });
                }
                
                currentOffset += data.loaded;
                totalLoaded = data.totalLoaded || currentOffset;
                hasMore = data.hasMore;
                
                if (!hasMore || data.maxReached) {
                    // Update end message based on whether we hit the max
                    const endMessage = document.getElementById("end-message");
                    if (data.maxReached || totalLoaded >= maxCompanies) {
                        <?php if ($account->isactive()) { ?>
                        endMessage.innerHTML = `<div class="text-center mx-auto mb-5">
                            <p class="text-muted">Showing first ${maxCompanies} <?php echo $website["biznames"]; ?>.</p>
                            <a href="/enrollment-picker" class="btn btn-primary btn-lg mt-3" style="border-radius: 50px; padding: 12px 32px;">Pick From All <?php echo ucfirst($website["biznames"]); ?></a>
                        </div>`;
                        <?php } else { ?>
                        endMessage.innerHTML = `<div class="text-center mx-auto mb-5">
                            <p class="text-muted">Showing first ${maxCompanies} <?php echo $website["biznames"]; ?>.</p>
                            <a href="/signup" class="btn btn-warning btn-lg mt-3" style="border-radius: 50px; padding: 12px 32px;">Sign Up to View All <?php echo ucfirst($website["biznames"]); ?></a>
                        </div>`;
                        <?php } ?>
                    } else {
                        endMessage.innerHTML = `<div class="text-center mx-auto mb-5">
                            <p class="text-muted">You have reached the end of the list!</p>
                        </div>`;
                    }
                    endMessage.style.display = "block";
                    
                    // Show signup CTA for non-logged in users
                    const signupCTA = document.getElementById("signup-cta");
                    if (signupCTA) {
                        signupCTA.style.display = "block";
                        // Removed delay for faster display
                    }
                    
                    // Hide the scroll trigger so it does not keep firing
                    const trigger = document.getElementById("scroll-trigger");
                    if (trigger) trigger.style.display = "none";
                    console.log("No more companies to load. Total loaded:", totalLoaded);
                }
                
                isLoading = false;
                clearTimeout(spinnerTimeout);
                document.getElementById("loading-indicator").style.display = "none";
                console.log("Load complete. New offset:", currentOffset, "Total loaded:", totalLoaded, "Has more:", hasMore);
            })
            .catch(error => {
                console.error("Error loading more companies:", error);
                isLoading = false;
                clearTimeout(spinnerTimeout);
                document.getElementById("loading-indicator").style.display = "none";
            });
    }
    
    // Set up intersection observer for infinite scroll trigger
    const scrollObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting && !isLoading && hasMore) {
                loadMoreCompanies();
            }
        });
    }, {
        rootMargin: "500px" // Start loading 500px before reaching the trigger (much earlier)
    });
    
    // Observe the scroll trigger element
    const scrollTriggerElement = document.getElementById("scroll-trigger");
    if (scrollTriggerElement) {
        scrollObserver.observe(scrollTriggerElement);
        console.log("Infinite scroll initialized. Observing scroll trigger element.");
    } else {
        console.error("Scroll trigger element not found!")
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

/* 5 column layout for XL screens */
@media (min-width: 1200px) {
    .col-xl-5cols {
        flex: 0 0 20%;
        max-width: 20%;
    }
}
</style>
';

include($dir['core_components'] . '/bg_footer.inc');
      $app->outputpage();
