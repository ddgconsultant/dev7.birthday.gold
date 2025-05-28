<?PHP
//overlay.inc
    $followCount = rand(2, 100000);

    $additionalstylex='
    <style>
    @media (max-width: 991.98px) {
    
    .post-overlay-wrapper .username,
    .post-overlay-wrapper .follow-count,
    .post-overlay-wrapper .title,
    .post-overlay-wrapper .post-description,
    .post-overlay-wrapper .post-tags .badge,
    .post-overlay-wrapper .location-badge,
    .post-overlay-wrapper .icon-container .interaction-count {
        font-size: 87%; /* Reduce font size by 13% */
    }

    .post-overlay-wrapper img {
        width: 87%; /* Reduce image size */
    }

}
</style>
';
$additionalstyles.='

<style>
/* Popup Styling */
.bookmark-popup {
    position: absolute;
    bottom: 120%; /* Adjust to position it above the icon */
    left: 0;
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); /* Retain the box shadow for the popup */
    padding: 10px;
    z-index: 1000;
    width: 180px;
    opacity: .95;
    visibility: visible;
    transition: opacity 0.3s ease;
}

/* Arrow pointing to the icon */
.bookmark-popup::before {
    content: "";
    position: absolute;
    top: 100%;
    left: 56%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
}

.bookmark-popup .popup-content {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.bookmark-popup .category-list {
    list-style: none;
    padding: 0;
}

.bookmark-popup .category-list li {
    margin: 10px 0; /* Increase spacing between items */
    font-size: 1rem;
    color: #333;
    cursor: pointer;
    text-shadow: none; /* Ensure no drop shadow is applied to the text */
        padding-left: 0;  /* Remove padding */
    margin-left: -30px !important;  /* Move further left */
}

.bookmark-popup .category-list li:hover {
   font-weight: bold;
    color: black;
text-underline-offset: 2px;
    text-decoration: underline;
}

.bookmark-popup h3 {
    font-size: 1.2rem;
    color: var(--bs-primary); /* Use Bootstrap primary color */
    text-shadow: none; /* Remove drop shadow from the heading */
    margin-bottom: 10px; /* Add some margin below the title */
    padding-top: 0; /* Reduce padding to move title higher */
    margin-left: -50px !important; /* Move the title further left */
    border-bottom: 1px solid black; /* Add a border below the title */
}



.bookmark-popup .add-category-btn {
    position: absolute;
    top: 8px;
    right: 12px;
    cursor: pointer;
    font-size: 1.2rem;
     color: #555; /* Ensure the close button "X" is black and visible */
    text-shadow: none;
    padding: 0px;
}

.bookmark-popup .add-category-btn:hover {
    color: var(--bs-success);
}



.bookmark-popup.hidden-overlay {
    visibility: hidden;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease, visibility 0s 0.3s;
}

.bookmark-popup.visible {
    visibility: visible;
    opacity: 1;
    pointer-events: all;
    transition: opacity 0.3s ease;
}
</style>
';

echo '
    <!-- Post Content Overlay Wrapper -->
    <div class="post-overlay-wrapper ">
        <div class="post-overlay"> <!-- Keep the overlay intact -->
            <!-- Creator Info and Title -->
            <div class="post-header post-overlay-vtoggle"> <!-- No overlay-item class needed -->
                <a href="/social/user-profile?">
                    <img src="/public/avatars/sample1.png" alt="Creator Avatar">
                <span class="username text-white text-shadow">CreatorUsername</span>
                </a>
                <div class="follow-icon icon-container icon-container-action" data-action="post-follow">
                    <i class="bi bi-person-plus-fill icon text-shadow"></i>
                    <span class="follow-count text-shadow">'.$qik->formatShortNumber($followCount, 1).'</span>
                </div>
            </div>
';

$post_overlaytitle = $qik->generateLoremIpsum((rand(3, 5)), 'words');
$post_overlaydesc = $qik->generateLoremIpsum((rand(3, 30)), 'words');

    
echo '
            <!-- Title and Description -->
            <h2 class="title text-secondary post-overlay-vtoggle pb-0 mb-0 text-shadow">'.$post_overlaytitle.'</h2>
            <div class="post-description post-overlay-vtoggle text-shadow">
                '.$post_overlaydesc .'
            </div>

            <!-- Tags -->
            <div class="post-tags post-overlay-vtoggle">';

        

// Define the possible Bootstrap classes for badge colors
$badgeClasses = ['bg-primary', 'bg-secondary', 'bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'bg-dark'];

// Generate a random number of tags (between 1 and 4)
$numTags = rand(1, 4);

for ($i = 1; $i <= $numTags; $i++) {
    $randomTag = '#' . $qik->generateLoremIpsum(rand(1, 1), 'words'); // Generate a random word for the tag
    $randomClass = $badgeClasses[array_rand($badgeClasses)]; // Pick a random badge color class
    echo '<span class="badge ' . $randomClass . '">' . $randomTag . '</span> ';
}



///  ------------------------------------------------------------------------------------------------------
$categories = ['Favorites', 'Watch Later', 'Important'];
#$categories = [];

$hasbookmarkcategories = !empty($categories); // Determine if user has bookmark categories


// Bookmark icon content
if ($hasbookmarkcategories) {
    $bookmarkiconcontent = '
         <div id="bookmarkIcon" class="icon-container post-overlay-vtoggle" onclick="toggleBookmarkMenu()">
            <i class="bi bi-bookmark-fill icon" id="bookmarkiconx" ></i>
            <span class="interaction-count">'.$qik->formatShortNumber(rand(1, 10000)).'</span>
        </div>

    <div id="bookmarkMenu" class="bookmark-popup hidden-overlay">
        <div class="popup-content">
            <!-- Add Bookmark Category Button -->
            <span class="add-category-btn" onclick="addBookmarkCategory()"><i class="bi bi-plus-circle-dotted"></i></span>
            <h3>Categories</h3>
            <ul class="category-list">';

   



    foreach ($categories as $category) {
        $bookmarkiconcontent .= '<li class="category-item" data-category="' . $category . '" onclick="saveToUniqueCategory(\'' . $category . '\', this)">
        <i class="bi bi-journal-bookmark-fill me-2"></i>' . $category . '</li>';
        
        }

    $bookmarkiconcontent .= '
            </ul>
        </div>
    </div>';


    $bookmarkiconcontent_JS = '
$(document).ready(function() {
    // Toggle visibility of the bookmark menu
    function uniqueToggleBookmarkMenu() {
        var bookmarkMenu = $("#bookmarkMenu");

        if (bookmarkMenu.length) {
            if (bookmarkMenu.hasClass("hidden-overlay")) {
                bookmarkMenu.removeClass("hidden-overlay").addClass("visible");
            } else {
                bookmarkMenu.removeClass("visible").addClass("hidden-overlay");
            }
        } else {
            console.error("Bookmark menu not found");
        }
    }

     // Handle saving to a specific category
    function saveToUniqueCategory(category, element) {
        const postId = "' . $postId . '";
        const csrfToken = "' . $display->inputcsrf_token("tokenonly") . '";

        // AJAX request to save the bookmark
        $.post("/social/components/post-bookmark.php", {
            category: category,
            postid: postId,
            csrf_token: csrfToken
        })
        .done(function(response) {
            console.log("Bookmark saved successfully:", response);

            // Directly reference the icon by ID and add the success class
            var icon = $("#bookmarkiconx");
            icon.addClass("text-success");
            
            // Close the menu after a successful save
            $("#bookmarkMenu").removeClass("visible").addClass("hidden-overlay");

            // Update the UI to indicate that the bookmark has been saved
            $(element).html("<i class=\"bi bi-bookmark-x-fill me-2 text-success\"></i>" + category);
        })
        .fail(function(error) {
            console.error("Error saving bookmark:", error);
        });
    }

    // Event handler for bookmark icon click to toggle the category menu
    $("#bookmarkIcon").on("click", function() {
        uniqueToggleBookmarkMenu();
    });

    // Event handler for selecting a bookmark category
    $(".category-item").on("click", function() {
        // Retrieve the category from the data-category attribute
        const category = $(this).data("category");
        saveToUniqueCategory(category, this);
    });
});
';

} 
 else {
   // If no categories exist, only display the bookmark icon without popup or menu
   $bookmarkiconcontent = '
   <div class="icon-container icon-container-action post-overlay-vtoggle" data-action="post-bookmark">
       <i class="bi bi-bookmark-fill icon"></i>
       <span class="interaction-count">'.$qik->formatShortNumber(rand(1,10000)).'</span>
   </div>';

    // JavaScript for users who don't have categories
    $bookmarkiconcontent_JS= '';
 
}

                
                echo '
                </div>

            <!-- Bottom Left Actions -->
            <div class="post-actions chrome-bottom-padding-2">
                <a href="/social/location?">
                <span class="location-badge post-overlay-vtoggle"><i class="bi bi-geo-alt-fill"></i> Location: City, Country</span>
                </a>
                <div class="icon-bar text-shadow">
                    <!-- This icon should NOT be hidden -->
                    <div class="icon-container " data-action="post-hide">
                        <i class="bi bi-eye-slash-fill icon"></i> <!-- Hide Overlay -->
                    </div>

                    <div class="icon-container icon-container-action post-overlay-vtoggle" data-action="post-like">
                        <i class="bi bi-hand-thumbs-up-fill icon"></i>
                        <span class="interaction-count">'.$qik->formatShortNumber(rand(1,10000)).'</span>
                    </div>
                    ';

                    echo $bookmarkiconcontent;
                  
echo '
                    <div class="icon-container icon-container-action post-overlay-vtoggle" data-action="post-share">
                        <i class="bi bi-share-fill icon"></i>
                        <span class="interaction-count">'.$qik->formatShortNumber(rand(1,10000)).'</span>
                    </div>
';

echo '
<div class="icon-container d-lg-none comment-toggle-icon post-overlay-vtoggle" data-action="show-comments">
    <i class="bi bi-chat-dots-fill  icon"></i>
    <span class="interaction-count">'.$qik->formatShortNumber(rand(1,10000)).'</span>
</div>
';

echo '
                </div>
            </div>
        </div>
    </div>
    ';



echo "<script>
$(document).ready(function() {
    // Toggle visibility of the comment overlay on icon click
    $('.comment-toggle-icon').on('click', function() {
        console.log('Comment toggle icon clicked');
        $('.comment-overlay').toggleClass('active'); // Toggle the active class to show/hide overlay
        console.log('Comment overlay toggle triggered');
    });

    // Close the overlay on close button click
    $('.close-btn').on('click', function() {
        console.log('Close button clicked');
        $('.comment-overlay').removeClass('active'); // Hide the overlay by removing 'active' class
        console.log('Comment overlay hidden');
    });
});
</script>
";

echo '
<script>
'. $bookmarkiconcontent_JS.'
</script>
';
?>
<script>
                $(document).ready(function() {
        // Show error message
        function showError() {
            const errorMessage = document.getElementById('errorMessage');
            errorMessage.style.display = 'block';
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 3000);
        }

        // AJAX call function
        function postAction(action, undo = false) {
            const postId = '<?php echo $qik->encodeId($post["id"] ?? ''); ?>';
            const csrfToken = '<?php echo $display->inputcsrf_token("tokenonly"); ?>';

            return $.post(`/social/components/${action}`, {
                    PID: postId,
                    csrf_token: csrfToken,
                    undo
                })
                .fail(showError); // Show error message on fail
        }

        // Handle icon clicks for follow, like, bookmark, and share actions
        $('.icon-container-action').on('click', function() {
            const action = $(this).data('action');
            const icon = $(this).find('.icon');
            const isActive = icon.hasClass('text-success');
            const undo = isActive ? true : false;

            // Toggle icon state and post action
            postAction(action, undo).done(function() {
                if (undo) {
                    icon.removeClass('text-success');
                } else {
                    icon.addClass('text-success');
                }
            });
        });

        // Handle hide/show elements with the "post-overlay-vtoggle" class
        $('[data-action="post-hide"]').on('click', function() {
            $('.post-overlay-vtoggle').toggleClass('hidden-overlay'); // Toggle hidden class on elements

            const icon = $(this).find('i');
            if (icon.hasClass('bi-eye-slash-fill')) {
                icon.removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
            } else {
                icon.removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
            }
        });

        // Modal for sharing
        $('[data-action="post-share"]').on('click', function() {
            alert('Share options modal goes here');
        });

    });
    


</script>

    <script>
document.addEventListener('keydown', function(event) {
    switch (event.key) {
        case 'ArrowUp': // Go to previous post
            goToPreviousPost();
            break;
        case 'ArrowDown': // Go to next post
            goToNextPost();
            break;
        case 'L': // Like post
        case 'l':
            likePost();
            break;
        case 'M': // Mute/unmute sound
        case 'm':
            toggleMute();
            break;
    }
});

// Example functions for each keyboard shortcut action
function goToPreviousPost() {
    console.log("Going to previous post...");
    // Add the logic here to load the previous post (e.g., AJAX or page navigation)
}

function goToNextPost() {
    console.log("Going to next post...");
    // Add the logic here to load the next post
}

function likePost() {
    console.log("Liking the post...");
    // Add logic here to like the post (e.g., API request or JS action)
}

function toggleMute() {
    var videoElement = document.getElementById('bg_multimediacomponent');
    if (videoElement) {
        videoElement.muted = !videoElement.muted;
        console.log(videoElement.muted ? "Muted" : "Unmuted");
    }
}
</script>
