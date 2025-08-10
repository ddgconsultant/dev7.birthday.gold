<?php
$addClasses[] = 'Social';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagelang = 'zxx';
$bodycontentclass = '';
$header_flush = true; // Flush content with header

// Content display mode: 'constrained', 'full-width', 'hybrid'
$content_display_mode = 'hybrid';

// Set up for mobile-first design
$additionalstyles = '
<link rel="stylesheet" href="/public/css/v7/mobile-first.css">
<style>
/* Social Module Specific Styles */
.social-main-content {
    margin-top: 2rem !important; /* Standard margin for spacing */
    padding-top: 0; /* No padding - content starts right after header */
    padding-bottom: 80px; /* Account for bottom nav on mobile */
    min-height: 100vh;
}

/* Override any default spacing from framework */
body .main-content {
    margin-top: 2rem !important;
    padding-top: 0 !important;
}

/* Mobile First - Full width on mobile */
.social-feed {
    width: 100%;
    position: relative;
}

.social-post-container {
    position: relative;
    min-height: calc(100vh - 140px);
}

/* Comments Panel - Mobile */
.comments-panel-mobile {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 60vh;
    background: white;
    z-index: 1040;
    border-top-left-radius: 20px;
    border-top-right-radius: 20px;
    box-shadow: 0 -2px 20px rgba(0,0,0,0.1);
    transform: translateY(100%);
    transition: transform 0.3s ease;
}

.comments-panel-mobile.active {
    display: block;
    transform: translateY(0);
}

.comments-panel-header {
    padding: 1rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.comments-list-mobile {
    height: calc(60vh - 120px);
    overflow-y: auto;
    padding: 1rem;
}

.comments-input-mobile {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1rem;
    background: white;
    border-top: 1px solid var(--gray-200);
}

/* Desktop Layout - Split Panel */
@media (min-width: 992px) {
    /* Hide main body scrollbar on desktop */
    html, body {
        overflow: hidden !important;
        height: 100vh !important;
        max-height: 100vh !important;
    }
    
    .social-main-content {
        margin-top: 2rem !important;
        padding-top: 0;
        padding-bottom: 0;
        display: flex;
        height: calc(100vh - 2rem);
        overflow: hidden;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
    }
    
    /* Left Panel - Comments on Desktop */
    .comments-panel-desktop {
        width: 500px;
        border-right: 1px solid var(--gray-200);
        background: white;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    
    /* Navigation at top of left panel */
    .social-nav-desktop {
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 1rem 0.5rem;
        margin-top: 30px;
        background: var(--gray-50, #f8f9fa);
        border-bottom: 1px solid var(--gray-200, #dee2e6);
    }
    
    .social-nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
        padding: 0.5rem;
        border-radius: 8px;
        text-decoration: none;
        color: var(--gray-700, #495057);
        font-size: 0.75rem;
        transition: all 0.2s;
    }
    
    .social-nav-item:hover {
        background: var(--gray-100, #f1f3f4);
        color: var(--primary, #007bff);
    }
    
    .social-nav-item.active {
        color: var(--primary, #007bff);
    }
    
    .social-nav-item i {
        font-size: 1.25rem;
    }
    
    .social-nav-item span {
        font-size: 0.7rem;
    }
    
    .comments-list-desktop {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }
    
    /* Right Panel - Content */
    .social-feed {
        flex: 1;
        overflow: hidden;
        background-color: #757575; /* Dark grey background to match content */
    }
    
    .social-post-container {
        min-height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #757575; /* Ensure container also has the background */
    }
    
    /* Hide mobile comments on desktop */
    .comments-panel-mobile {
        display: none !important;
    }
    
    .mobile-comments-button {
        display: none !important;
    }
}

/* Hide desktop panel on mobile */
@media (max-width: 991.98px) {
    .comments-panel-desktop {
        display: none !important;
    }
    
    .social-nav-desktop {
        display: none !important;
    }
}

/* Comment Styles */
.comment-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 0.75rem;
    border-radius: 8px;
    transition: background 0.2s;
}

.comment-item:hover {
    background: var(--gray-50);
}

.comment-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    flex-shrink: 0;
}

.comment-content {
    flex: 1;
    min-width: 0;
}

.comment-author {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.comment-text {
    font-size: 0.9rem;
    line-height: 1.4;
    word-wrap: break-word;
}

.comment-actions {
    display: flex;
    gap: 2rem;
    margin-top: 0.75rem;
    font-size: 0.85rem;
    color: var(--gray-600);
}

.comment-action {
    cursor: pointer;
    transition: color 0.2s;
}

.comment-action:hover {
    color: var(--primary);
}

/* No Comments State */
.no-comments {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    color: var(--gray-600);
    text-align: center;
    padding: 2rem;
}

.no-comments-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Mobile Bottom Navigation */
.social-nav-mobile {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-top: 1px solid var(--gray-200, #dee2e6);
    box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
    z-index: 1020;
    padding: 0.5rem 0;
}

@media (max-width: 991.98px) {
    .social-nav-mobile {
        display: flex;
        justify-content: space-around;
        align-items: center;
    }
}

.social-nav-mobile-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem;
    text-decoration: none;
    color: var(--gray-600, #6c757d);
    font-size: 0.75rem;
    transition: all 0.2s;
}

.social-nav-mobile-item:hover {
    color: var(--primary, #007bff);
}

.social-nav-mobile-item.active {
    color: var(--primary, #007bff);
}

.social-nav-mobile-item i {
    font-size: 1.25rem;
}

.social-nav-mobile-item span {
    font-size: 0.65rem;
}

/* Mobile Comments Button */
.mobile-comments-button {
    position: fixed;
    bottom: 90px;
    right: 20px;
    z-index: 1030;
    background: var(--primary, #007bff);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 12px 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Loading Placeholder */
.loading-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 400px;
    width: 100%;
    background: transparent;
}

/* Post Content Area */
.post-content-area {
    padding: 1rem;
    width: 100%;
    position: relative;
    height: 100%;
}

/* Make right-panel positioned for absolute children */
.right-panel {
    position: relative !important;
}

/* Position overlay elements at bottom */
.post-actions {
    position: absolute !important;
    bottom: 20px;
    left: 20px;
    z-index: 10;
}

.right-panel .chrome-bottom-padding-1 {
    position: absolute;
    bottom: 20px;
}

.right-panel .chrome-bottom-padding-2 {
    position: absolute;
    bottom: 20px;
}

.right-panel .chrome-bottom-padding-3 {
    position: absolute;
    bottom: 50px;
}

.right-panel .chrome-bottom-padding-4 {
    position: absolute;
    bottom: 70px;
}

.right-panel .chrome-bottom-padding-seekbar {
    position: absolute;
    bottom: 34px;
}

.right-panel .chrome-bottom-padding-carousel {
    position: absolute;
    bottom: 30px;
}

</style>
';

// Generate display mode specific CSS
switch($content_display_mode) {
    case 'full-width':
        // Full width - content fills entire right panel
        $additionalstyles .= '
<style>
@media (min-width: 992px) {
    .post-content-area {
        padding: 2rem;
        width: 100%;
        max-width: 100%;
        margin: 0;
        position: relative;
        height: 100%;
    }
}
</style>';
        break;
        
    case 'constrained':
        // Constrained - always 800px max regardless of content
        $additionalstyles .= '
<style>
@media (min-width: 992px) {
    .post-content-area {
        padding: 2rem;
        max-width: 800px;
        margin: 0 auto;
        position: relative;
        height: 100%;
    }
}
</style>';
        break;
        
    case 'hybrid':
    default:
        // Hybrid - full width for images/video, constrained for text
        $additionalstyles .= '
<style>
@media (min-width: 992px) {
    .post-content-area {
        padding: 2rem;
        width: 100%;
        margin: 0 auto;
        position: relative;
        height: 100%;
    }
    
    /* Constrain text content */
    .post-content-area .text-post-content {
        max-width: 800px;
        margin: 0 auto;
    }
    
    /* Full width for media content */
    .post-content-area .media-post-content,
    .post-content-area .right-panel {
        width: 100%;
        max-width: 100%;
    }
}
</style>';
        break;
}

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Get current post from database
$postId = $_GET['post'] ?? null;
$page = intval($_GET['page'] ?? 1);
$user_id = $current_user_data['user_id'] ?? null;

// Get a post from the feed
$currentPost = null;
if ($user_id) {
    $posts = $social->getFeed($user_id, 1, $page - 1, 'all');
    if (!empty($posts)) {
        $currentPost = $posts[0];
        $postId = $currentPost['post_id'];
    }
}

// Get comments for the current post
$comments = [];
if ($postId && isset($social)) {
    $comments = $social->getComments($postId, 50, 0);
}
$numComments = count($comments);

// If no real comments, generate random ones for UI testing
if ($numComments == 0) {
    $numComments = rand(5, 20);
    $sampleComments = [
        "Love this birthday deal!",
        "Thanks for sharing this freebie!",
        "Just signed up, cannot wait for my birthday!",
        "This is amazing, got my free cake last month",
        "Birthday freebies are the best!",
        "Wish more places did this",
        "My birthday is next week, perfect timing!",
        "Has anyone tried this? Is it legit?",
        "Confirmed - this works! Got mine yesterday",
        "Birthday month is the best month 🎂",
        "Free stuff just for being born? Yes please!",
        "This made my birthday extra special",
        "Pro tip: Sign up a month before your birthday",
        "Thanks Birthday Gold for finding these!",
        "Another one for my birthday list!"
    ];
    
    $sampleUsers = ["Sarah", "Mike", "Jessica", "David", "Emily", "Chris", "Amanda", "Ryan", "Lisa", "John"];
    
    for ($i = 0; $i < $numComments; $i++) {
        $comments[] = [
            'content' => $sampleComments[array_rand($sampleComments)],
            'first_name' => $sampleUsers[array_rand($sampleUsers)],
            'last_name' => chr(rand(65, 90)) . '.',
            'avatar_url' => "/public/avatars/sample_users/placeholder_" . rand(1, 10) . ".png",
            'like_count' => rand(0, 50),
            'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 72) . ' hours'))
        ];
    }
}

// Initialize icons if not set
if (!isset($icons_writecomment)) {
    $icons_writecomment = [
        '<i class="bi bi-chat-dots"></i>',
        '<i class="bi bi-chat-square-text"></i>',
        '<i class="bi bi-chat-left-text"></i>'
    ];
}
?>

<div class="social-main-content">
    
    <!-- Desktop Comments Panel (Left Side) -->
    <div class="comments-panel-desktop">
        <!-- Navigation Bar at Top of Left Panel (Desktop Only) -->
        <div class="social-nav-desktop">
            <a href="/social/" class="social-nav-item<?php echo (basename($_SERVER['PHP_SELF']) === 'index.php' ? ' active' : ''); ?>">
                <i class="bi bi-house-door-fill"></i>
                <span>Home</span>
            </a>
            <a href="/social/search" class="social-nav-item">
                <i class="bi bi-search"></i>
                <span>Search</span>
            </a>
            <a href="/social/create" class="social-nav-item">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Create</span>
            </a>
            <a href="/social/activity" class="social-nav-item">
                <i class="bi bi-bookmark-fill"></i>
                <span>Activity</span>
            </a>
            <a href="/social/settings" class="social-nav-item">
                <i class="bi bi-gear-fill"></i>
                <span>Settings</span>
            </a>
        </div>
        
        <div class="comments-list-desktop">
            <?php include($_SERVER['DOCUMENT_ROOT'] . '/social/components/write-comment.inc'); ?>
            
            <?php if ($numComments == 0): ?>
                <div class="no-comments">
                    <div class="no-comments-icon">
                        <?php echo $icons_writecomment[array_rand($icons_writecomment)]; ?>
                    </div>
                    <div>No comments yet</div>
                    <small class="text-muted">Be the first to comment!</small>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): 
                    $avatarSrc = !empty($comment['avatar_url']) ? $comment['avatar_url'] : "/public/avatars/sample_users/placeholder_1.png";
                    $username = htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']);
                    $commentText = htmlspecialchars($comment['content']);
                    $timeAgo = $social->formatTimeAgo($comment['created_at']);
                    $likeCount = $comment['like_count'] ?? 0;
                ?>
                    <div class="comment-item">
                        <img src="<?php echo $avatarSrc; ?>" alt="Avatar" class="comment-avatar">
                        <div class="comment-content">
                            <div class="comment-author"><?php echo $username; ?></div>
                            <div class="comment-text"><?php echo $commentText; ?></div>
                            <div class="comment-actions">
                                <span class="comment-action"><?php echo $timeAgo; ?></span>
                                <span class="comment-action" onclick="toggleReply(this)">Reply</span>
                                <span class="comment-action">
                                    <i class="bi bi-heart"></i> <?php echo $likeCount ?: ''; ?>
                                </span>
                            </div>
                            <div class="comment-reply-input" style="display: none; margin-top: 0.75rem;">
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-sm" placeholder="Write a reply...">
                                    <button class="btn btn-primary btn-sm" type="button">
                                        <i class="bi bi-send"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Main Feed Content -->
    <div class="social-feed">
        <div class="social-post-container">
            <!-- Loading Placeholder -->
            <div class="loading-placeholder" id="loadingPlaceholder">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading content...</p>
            </div>
            
            <div class="post-content-area" id="postContentArea" style="display: none;">
                <?php
                // Load appropriate post content
                $postTypes = ['images', 'video', 'text'];
                $post['type'] = $postTypes[array_rand($postTypes)];
                
                // Add wrapper class based on content type for hybrid mode
                $contentClass = ($post['type'] === 'text') ? 'text-post-content' : 'media-post-content';
                echo '<div class="' . $contentClass . '" data-content-type="' . $post['type'] . '">';
                
                $contentFile = $_SERVER['DOCUMENT_ROOT'] . '/social/components/postcontent-' . $post['type'] . '.inc';
                if (file_exists($contentFile)) {
                    include($contentFile);
                } else {
                    // Default content
                    echo '<div class="text-center">';
                    echo '<h2>Welcome to Birthday Gold Social</h2>';
                    echo '<p>Share your birthday experiences!</p>';
                    echo '</div>';
                }
                
                echo '</div>'; // Close content wrapper
                ?>
            </div>
        </div>
    </div>
    
    <!-- Mobile Comments Panel (Bottom Sheet) -->
    <div class="comments-panel-mobile" id="mobileCommentsPanel">
        <div class="comments-panel-header">
            <h5 class="mb-0">Comments</h5>
            <button type="button" class="btn-close" onclick="toggleMobileComments()"></button>
        </div>
        
        <div class="comments-list-mobile">
            <!-- Comments will be duplicated here for mobile -->
        </div>
        
        <div class="comments-input-mobile">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Add a comment...">
                <button class="btn btn-primary" type="button">
                    <i class="bi bi-send"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Mobile Comments Button -->
    <button class="btn mobile-comments-button" onclick="toggleMobileComments()">
        <i class="bi bi-chat-dots"></i>
        <span><?php echo $numComments; ?></span>
    </button>
    
    <!-- Mobile Bottom Navigation -->
    <nav class="social-nav-mobile">
        <a href="/social/" class="social-nav-mobile-item<?php echo (basename($_SERVER['PHP_SELF']) === 'index.php' ? ' active' : ''); ?>">
            <i class="bi bi-house-door-fill"></i>
            <span>Home</span>
        </a>
        <a href="/social/search" class="social-nav-mobile-item">
            <i class="bi bi-search"></i>
            <span>Search</span>
        </a>
        <a href="/social/create" class="social-nav-mobile-item">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Create</span>
        </a>
        <a href="/social/activity" class="social-nav-mobile-item">
            <i class="bi bi-bookmark-fill"></i>
            <span>Activity</span>
        </a>
        <a href="/social/settings" class="social-nav-mobile-item">
            <i class="bi bi-gear-fill"></i>
            <span>Settings</span>
        </a>
    </nav>
</div>

<script>
// Show content when page is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Hide loading placeholder and show content
    setTimeout(function() {
        const loadingPlaceholder = document.getElementById('loadingPlaceholder');
        const postContentArea = document.getElementById('postContentArea');
        
        if (loadingPlaceholder && postContentArea) {
            loadingPlaceholder.style.display = 'none';
            postContentArea.style.display = 'block';
        }
    }, 500); // Small delay to ensure content is ready
});

function toggleReply(element) {
    const commentContent = element.closest('.comment-content');
    const replyInput = commentContent.querySelector('.comment-reply-input');
    
    // Hide all other reply inputs
    document.querySelectorAll('.comment-reply-input').forEach(input => {
        if (input !== replyInput) {
            input.style.display = 'none';
        }
    });
    
    // Toggle this reply input
    if (replyInput.style.display === 'none') {
        replyInput.style.display = 'block';
        replyInput.querySelector('input').focus();
    } else {
        replyInput.style.display = 'none';
    }
}

function toggleMobileComments() {
    const panel = document.getElementById('mobileCommentsPanel');
    panel.classList.toggle('active');
    
    // Copy desktop comments to mobile panel when opening
    if (panel.classList.contains('active')) {
        const desktopComments = document.querySelector('.comments-list-desktop');
        const mobileComments = document.querySelector('.comments-list-mobile');
        if (desktopComments && mobileComments) {
            mobileComments.innerHTML = desktopComments.innerHTML;
        }
    }
}

// Close mobile comments when clicking outside
document.addEventListener('click', function(e) {
    const panel = document.getElementById('mobileCommentsPanel');
    if (panel && panel.classList.contains('active')) {
        if (!panel.contains(e.target) && !e.target.closest('.mobile-comments-button')) {
            panel.classList.remove('active');
        }
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape closes comments
    if (e.key === 'Escape') {
        const panel = document.getElementById('mobileCommentsPanel');
        if (panel) panel.classList.remove('active');
    }
    
    // C for comments
    if (e.key === 'c' || e.key === 'C') {
        if (!e.target.matches('input, textarea')) {
            e.preventDefault();
            toggleMobileComments();
        }
    }
});
</script>

<?php
// Include improved scrolling/navigation script
include($_SERVER['DOCUMENT_ROOT'] . '/social/components/js-scrolling-improved.inc');

// Include share modal
include($_SERVER['DOCUMENT_ROOT'] . '/social/components/share-modal.inc');
?>

<?php
// No footer - we have our own navigation
$display_footertype = 'none';
include($dir['core_components'] . '/bg_footer.inc');

$app->outputpage();
?>