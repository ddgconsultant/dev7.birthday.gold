<?php
// Add post-specific styles dynamically to the page
$additionalstyles .= '<style>
        /* Right Panel styles */
        .right-panel {
            position: relative;
            height: 100vh;
            overflow: hidden;
            background-color: #f5f5f5;
        }

        /* Post Overlay */
        .post-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px;
            color: white;
            background: rgba(0, 0, 0, 0.5); /* Dark transparent overlay */
        }

        .post-header {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .post-header .title {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .post-header .creator-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .post-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
        }

        .post-header .username {
            font-weight: bold;
            font-size: 1.2rem;
        }

        .post-header .follow-icon {
            cursor: pointer;
            color: white;
        }

        .post-description {
            margin-top: 10px;
            font-size: 1rem;
        }

        .post-tags {
            margin-top: 10px;
        }

        /* Action Bar (bottom left) */
        .post-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .post-actions .icon {
            font-size: 1.5rem;
            cursor: pointer;
        }

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
    </style>';
?>

<div class="right-panel p-0 m-0">
    <!-- Carousel for Images -->
    <div id="postCarousel" class="carousel slide h-100" data-bs-ride="false">
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
    </div>

    <!-- Post Content Overlay -->
    <div class="post-overlay">
        <!-- Top Left Content -->
        <div class="post-header">
            <h2 class="title">Title of the Post</h2>
            <div class="creator-info">
                <img src="https://via.placeholder.com/50" alt="Creator Avatar">
                <span class="username">CreatorUsername</span>
                <i class="bi bi-person-plus follow-icon"></i> <!-- Follow Icon next to username -->
            </div>
        </div>

        <div class="post-description">
            This is a short description of the post. It gives a brief summary of what the post is about.
        </div>

        <div class="post-tags">
            <span class="badge bg-primary">#Tag1</span>
            <span class="badge bg-secondary">#Tag2</span>
            <span class="badge bg-success">#Tag3</span>
        </div>

        <!-- Bottom Left Actions -->
        <div class="post-actions">
            <i class="bi bi-geo-alt-fill icon"></i> <!-- Location -->
            <i class="bi bi-hand-thumbs-up icon"></i> <!-- Like -->
            <i class="bi bi-bookmark-fill icon"></i> <!-- Bookmark -->
            <i class="bi bi-eye-slash-fill icon"></i> <!-- Hide Overlay -->
            <i class="bi bi-share-fill icon"></i> <!-- Share -->
        </div>
    </div>
</div>

<script>
    // Ensure the carousel doesn't auto-rotate
    var postCarousel = new bootstrap.Carousel(document.getElementById('postCarousel'), {
        interval: false,  // No auto-slide
        wrap: true
    });

    // Update carousel dots when the slide changes
    document.querySelectorAll('.carousel').forEach(carousel => {
        carousel.addEventListener('slid.bs.carousel', function () {
            const activeIndex = this.querySelector('.carousel-inner .active').getAttribute('data-bs-slide-to');
            document.querySelectorAll('.carousel-dot-indicator .dot').forEach(dot => {
                dot.classList.remove('active');
            });
            document.querySelectorAll('.carousel-dot-indicator .dot')[activeIndex].classList.add('active');
        });
    });
</script>
