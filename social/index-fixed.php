<?php
$addClasses[] = 'Social';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagelang = 'zxx';
$bodycontentclass = '';

// Set up for mobile-first design
$additionalstyles = '
<link rel="stylesheet" href="/public/css/v7/mobile-first.css">
<style>
/* Social Module Specific Styles */
.social-main-content {
    margin-top: 60px; /* Account for header */
    margin-bottom: 80px; /* Account for bottom nav on mobile */
    min-height: calc(100vh - 140px);
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
    .social-main-content {
        margin-top: 60px;
        margin-bottom: 0;
        display: flex;
        height: calc(100vh - 60px);
    }
    
    /* Left Panel - Comments on Desktop */
    .comments-panel-desktop {
        width: 400px;
        border-right: 1px solid var(--gray-200);
        background: white;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    
    .comments-list-desktop {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }
    
    .comments-input-desktop {
        padding: 1rem;
        border-top: 1px solid var(--gray-200);
        background: white;
    }
    
    /* Right Panel - Content */
    .social-feed {
        flex: 1;
        overflow-y: auto;
    }
    
    .social-post-container {
        min-height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
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
}

/* Comment Styles */
.comment-item {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding: 0.5rem;
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
    gap: 1rem;
    margin-top: 0.5rem;
    font-size: 0.8rem;
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

/* Mobile Comments Button */
.mobile-comments-button {
    position: fixed;
    bottom: 90px;
    right: 20px;
    z-index: 1030;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 12px 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Post Content Area */
.post-content-area {
    padding: 1rem;
}

@media (min-width: 992px) {
    .post-content-area {
        padding: 2rem;
        max-width: 800px;
        margin: 0 auto;
    }
}
</style>
';

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
        <?php include($_SERVER['DOCUMENT_ROOT'] . '/social/components/write-comment.inc'); ?>
        
        <div class="comments-list-desktop">
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
                                <span class="comment-action">Reply</span>
                                <span class="comment-action">
                                    <i class="bi bi-heart"></i> <?php echo $likeCount ?: ''; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="comments-input-desktop">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Add a comment...">
                <button class="btn btn-primary" type="button">
                    <i class="bi bi-send"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Main Feed Content -->
    <div class="social-feed">
        <div class="social-post-container">
            <div class="post-content-area">
                <?php
                // Load appropriate post content
                $postTypes = ['images', 'video', 'text'];
                $post['type'] = $postTypes[array_rand($postTypes)];
                
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
</div>

<script>
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

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const panel = document.getElementById('mobileCommentsPanel');
        if (panel) panel.classList.remove('active');
    }
});
</script>

<?php
// Use social-specific footer with social navigation
include($_SERVER['DOCUMENT_ROOT'] . '/social/components/social-footer.inc');

$app->outputpage();
?>