<?php

$additionalstyles .= '<style>
    /* Right Panel styles */
    .right-panel {
        position: relative;
        height: calc(100vh - 75px); /* Match height of left panel */
        overflow: hidden; /* Prevent horizontal scrolling */
        background-color: #f5f5f5;
    }

    /* Post Overlay */
    .right-panel .post-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        padding: 20px;
        box-sizing: border-box;
        color: white;
        background: rgba(0, 0, 0, 0.5); /* Dark transparent overlay */
        z-index: 1; /* Ensure it stays above the carousel */
    }

    /* Creator Info, Title, and Description */
    .right-panel .post-header {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .right-panel .post-header img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
    }

    .right-panel .post-header .username {
        font-weight: bold;
        font-size: 1.2rem;
    }

    .right-panel .post-header .follow-icon {
        display: flex;
        align-items: center;
        gap: 5px;
        color: white;
    }

    /* Follow and icon pointer */
    .right-panel .follow-icon, 
    .right-panel .icon-container {
        cursor: pointer; /* Ensure pointer on hover for follow and icons */
    }

    .right-panel .follow-icon .follow-count {
        font-size: 0.8rem;
        color: white;
    }

    .right-panel .title {
        margin-top: 10px;
        font-size: 1.5rem;
        font-weight: bold;
    }

    .right-panel .post-description {
        margin-top: 5px;
        font-size: 1rem;
    }

    .right-panel .post-tags {
        margin-top: 5px;
    }

    /* Action Bar (bottom left) */
    .right-panel .post-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        position: absolute;
        bottom: 20px;
        left: 20px;
    }

    .right-panel .post-actions .location-badge {
        background-color: #17a2b8;
        font-size: 0.9rem;
        padding: 5px 10px;
        border-radius: 10px;
    }

    .right-panel .post-actions .icon-bar {
        display: flex;
        gap: 20px;
    }

    .right-panel .post-actions .icon-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        cursor: pointer; /* This ensures the pointer is active */
    }

    .right-panel .post-actions .icon {
        font-size: 1.5rem;
    }

    .right-panel .post-actions .interaction-count {
        font-size: 0.7rem;
        color: white;
        margin-top: 2px;
    }

    /* Error message */
    .error-message {
        position: fixed;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #dc3545;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        z-index: 9999;
        display: none;
    }

    /* Success message color for icons */
    .success {
        color: #28a745 !important;
    }



    /* Carousel dot indicator */
    .carousel-dot-indicator {
        position: absolute;
        bottom: 10px;
        width: 100%;
        display: flex;
        justify-content: center;
        gap: 10px;
    }

  .carousel-dot-indicator .dot {
        width: 12px;
        height: 12px;
        background-color: #fff;
        border-radius: 50%;
        opacity: 0.5;
        cursor: pointer;
    }

.carousel-dot-indicator .dot.active {
        opacity: 1;
        width: 16px;
        height: 16px;
    }

.hidden-overlay {
    visibility: hidden !important; /* Hides the element but preserves the space */
    opacity: 0; /* Smoothly hides the element */
    pointer-events: none; /* Prevents interaction with hidden elements */
    transition: visibility 0s, opacity 0.3s ease; /* Optional: Add a transition effect */
}


.image-count-badge {
    position: absolute;
    bottom: 20px;
    right: 20px;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 1rem;
    z-index: 10;
}


</style>';


?>

<!-- Error Message -->
<div class="error-message" id="errorMessage">Unable to complete the action</div>

<div class="right-panel p-0 m-0">


<!-- Carousel for Images -->
<div id="postCarousel" class="carousel slide h-100" data-bs-ride="carousel">
    <div class="carousel-inner h-100">
        <!-- Slide 1 -->
        <div class="carousel-item active h-100">
            <img src="https://via.placeholder.com/800x600?text=Image+1" class="d-block w-100 h-100" alt="Image 1">
        </div>
        <!-- Slide 2 -->
        <div class="carousel-item h-100">
            <img src="https://via.placeholder.com/800x600?text=Image+2" class="d-block w-100 h-100" alt="Image 2">
        </div>
        <!-- Slide 3 -->
        <div class="carousel-item h-100">
            <img src="https://via.placeholder.com/800x600?text=Image+3" class="d-block w-100 h-100" alt="Image 3">
        </div>
        <!-- Slide 4 -->
        <div class="carousel-item h-100">
            <img src="https://via.placeholder.com/800x600?text=Image+4" class="d-block w-100 h-100" alt="Image 4">
        </div>
    </div>

    <!-- Carousel Controls -->
    <button class="carousel-control-prev" type="button" data-bs-target="#postCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#postCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>

    <!-- Dot Indicator -->
    <div class="carousel-dot-indicator">
        <div class="dot active" data-bs-target="#postCarousel" data-bs-slide-to="0"></div>
        <div class="dot" data-bs-target="#postCarousel" data-bs-slide-to="1"></div>
        <div class="dot" data-bs-target="#postCarousel" data-bs-slide-to="2"></div>
        <div class="dot" data-bs-target="#postCarousel" data-bs-slide-to="3"></div>
    </div>


    <!-- Badge for Image Count -->
    <div class="image-count-badge" id="imageCountBadge">
        [1 / 4]
    </div>
</div>



<!-- Post Content Overlay Wrapper -->
<div class="post-overlay-wrapper">
    <div class="post-overlay"> <!-- Keep the overlay intact -->
        <!-- Creator Info and Title -->
        <div class="post-header post-overlay-vtoggle"> <!-- No overlay-item class needed -->
            <img src="https://via.placeholder.com/50" alt="Creator Avatar">
            <span class="username">CreatorUsername</span>
            <div class="follow-icon" data-action="post-follow">
                <i class="bi bi-person-plus"></i> 
                <span class="follow-count">1.5K</span>
            </div>
        </div>

        <!-- Title and Description -->
        <h2 class="title post-overlay-vtoggle">Title of the Post</h2>
        <div class="post-description post-overlay-vtoggle">
            This is a short description of the post. It gives a brief summary of what the post is about.
        </div>

        <!-- Tags -->
        <div class="post-tags post-overlay-vtoggle">
            <span class="badge bg-primary">#Tag1</span>
            <span class="badge bg-secondary">#Tag2</span>
            <span class="badge bg-success">#Tag3</span>
        </div>

        <!-- Bottom Left Actions -->
        <div class="post-actions">
            <span class="location-badge post-overlay-vtoggle">Location: City, Country</span>
            <div class="icon-bar">
                   <!-- This icon should NOT be hidden -->
                   <div class="icon-container " data-action="post-hide">
                    <i class="bi bi-eye-slash-fill icon"></i> <!-- Hide Overlay -->
                </div>

                <div class="icon-container icon-container-action post-overlay-vtoggle" data-action="post-like">
                    <i class="bi bi-hand-thumbs-up icon"></i>
                    <span class="interaction-count">3K</span>
                </div>
                <div class="icon-container icon-container-action post-overlay-vtoggle" data-action="post-bookmark">
                    <i class="bi bi-bookmark-fill icon"></i>
                    <span class="interaction-count">10</span>
                </div>
             
                <div class="icon-container icon-container-action post-overlay-vtoggle" data-action="post-share">
                    <i class="bi bi-share-fill icon"></i>
                    <span class="interaction-count">2.5K</span>
                </div>
            </div>
        </div>
    </div>
</div>



    </div>
</div>



<script>
// Initialize Bootstrap carousel
var myCarousel = document.querySelector('#postCarousel');
var carousel = new bootstrap.Carousel(myCarousel, {
    interval: false,  // No auto-slide
    wrap: true
});


</script>

<script>


// Update the image count badge dynamically
$(document).ready(function() {
    const totalSlides = $('#postCarousel .carousel-item').length;
    $('#imageCountBadge').text(`[1 / ${totalSlides}]`);

    $('#postCarousel').on('slid.bs.carousel', function (e) {
        const currentIndex = $(e.relatedTarget).index() + 1;
        $('#imageCountBadge').text(`[${currentIndex} / ${totalSlides}]`);
    });
});



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
        const postId = '<?php echo $qik->encodeId($post["id"]??''); ?>';
        const csrfToken = '<?php echo $display->inputcsrf_token("tokenonly"); ?>';
        
        // Return mock failed post to trigger error
        return $.post(`/social/components/${action}`, { PID: postId, csrf_token: csrfToken, undo })
            .fail(showError); // Fails since endpoints don't exist yet
    }

    // Handle icon clicks
    $('.icon-container-action').on('click', function () {
        const action = $(this).data('action');
        const icon = $(this).find('.icon');
        const isActive = icon.hasClass('success');
        const undo = isActive ? true : false;

        // Toggle icon state and post action
        postAction(action, undo).done(function () {
            if (undo) {
                icon.removeClass('success');
            } else {
                icon.addClass('success');
            }
        });
    });


// Handle hide/show elements with the "post-overlay-vtoggle" class
$('[data-action="post-hide"]').on('click', function () {
    // Toggle visibility of all elements with the "post-overlay-vtoggle" class
    $('.post-overlay-vtoggle').each(function () {
        $(this).toggleClass('hidden-overlay'); // Toggle the hidden-overlay class on each element
    });

    // Toggle the icon between bi-eye-slash and bi-eye
    const icon = $(this).find('i');
    if (icon.hasClass('bi-eye-slash-fill')) {
        icon.removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
    } else {
        icon.removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
    }
});




    // Modal for sharing
    $('[data-action="post-share"]').on('click', function () {
        // Logic for opening a modal for sharing options
        alert('Share options modal goes here');
    });
</script>
