<?php
$addClasses[] = 'Social';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagelang = 'zxx';
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Get current post from database or URL
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

// Initialize icons array if not set
if (!isset($icons_writecomment)) {
    $icons_writecomment = [
        '<i class="bi bi-chat-dots" style="font-size: 3rem; color: #ccc;"></i>',
        '<i class="bi bi-chat-square-text" style="font-size: 3rem; color: #ccc;"></i>',
        '<i class="bi bi-chat-left-text" style="font-size: 3rem; color: #ccc;"></i>'
    ];
}

$additionalstyles = '
<style>
/* Main layout */
.main-content { 
    overflow: hidden;
    min-height: calc(100vh - 75px);
}

/* Left Panel - Desktop Comments */
.left-panel { 
    display: flex; 
    flex-direction: column; 
    height: calc(100vh - 75px); 
    border-right: 1px solid #dee2e6;  
    overflow: hidden;
    background-color: #fff;
}

/* Action bar */
.action-bar { 
    display: flex; 
    justify-content: center; 
    padding: 10px 0; 
    background-color: #f8f9fa;
    flex-shrink: 0;
}

.action-bar .icon-container { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    width: 100%; 
    max-width: 400px;
    padding: 0 20px;
}

.action-bar a { 
    display: flex; 
    flex-direction: column; 
    align-items: center; 
    text-decoration: none; 
    color: inherit; 
    font-size: 1.5rem;
}

.action-bar .icon-title { 
    font-size: 0.7rem; 
    margin-top: 2px; 
    color: #666;
}

/* Comments container */
.comments-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Write comment section */
.write-comment { 
    background-color: #fff; 
    padding: 1rem; 
    border-bottom: 1px solid #dee2e6; 
    flex-shrink: 0;
}

.write-comment textarea {
    width: 100%;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    padding: 8px 15px;
    resize: none;
    font-size: 0.9rem;
}

/* Comments list */
.comments-list { 
    flex: 1;
    overflow-y: auto; 
    padding: 1rem;
}

.comment-item { 
    display: flex; 
    align-items: start; 
    margin-bottom: 1rem;
    padding: 0.5rem;
    border-radius: 8px;
    transition: background-color 0.2s;
}

.comment-item:hover {
    background-color: #f8f9fa;
}

.comment-item img { 
    width: 40px; 
    height: 40px; 
    border-radius: 50%; 
    margin-right: 10px;
    flex-shrink: 0;
}

.comment-body { 
    flex-grow: 1;
    min-width: 0;
}

.comment-header { 
    display: flex; 
    justify-content: space-between;
    margin-bottom: 0.25rem;
}

.comment-text { 
    font-size: 0.9rem; 
    margin-bottom: 0.5rem;
    word-wrap: break-word;
}

.reply-link, .like-info { 
    font-size: 0.8rem;
}

/* Scrollbar styling */
.comments-list::-webkit-scrollbar { 
    width: 8px;
}

.comments-list::-webkit-scrollbar-thumb { 
    background-color: #ccc; 
    border-radius: 4px;
}

.comments-list::-webkit-scrollbar-track { 
    background-color: #f1f1f1;
}

/* Right panel */
.right-panel {
    position: relative;
    height: calc(100vh - 75px);
    overflow-y: auto;
}

/* Mobile comment overlay */
.comment-overlay { 
    display: none; 
    position: fixed; 
    bottom: 0; 
    left: 0; 
    width: 100%; 
    height: 50vh;
    background-color: rgba(255, 255, 255, 0.98); 
    z-index: 1050; 
    overflow-y: auto;
    padding: 1rem; 
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.comment-overlay.active { 
    display: block !important;
}

.comment-overlay .close-btn { 
    position: absolute; 
    top: 10px; 
    right: 10px; 
    font-size: 1.5rem; 
    color: #333; 
    cursor: pointer;
    z-index: 1;
}

/* No comments state */
.no-comments-message {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 200px;
    padding: 2rem;
    text-align: center;
}

.no-comments-message .icon {
    margin-bottom: 1rem;
}

/* Mobile responsive */
@media (max-width: 991.98px) {
    .left-panel { 
        display: none;
    }
    
    .right-panel {
        height: 100vh;
    }
}

/* Ensure proper spacing */
.monotypenumbers { 
    font-family: "Roboto Mono", monospace;
}

/* Keyboard shortcuts modal */
kbd {
    padding: 5px 10px;
    background-color: #f7f7f7;
    border-radius: 3px;
    font-size: 1rem;
}
</style>
';

?>

<div class="container-fluid main-content p-0 pt-0 mb-0 pt-lg-3">
    <div class="row my-0 py-0">
        
        <!-- Mobile Comment Overlay -->
        <div class="comment-overlay d-lg-none" id="mobile-comments"> 
            <span class="close-btn" onclick="toggleMobileComments()">&times;</span>
            <div class="comments-container">
                <?php include($_SERVER['DOCUMENT_ROOT'] . '/social/components/write-comment.inc'); ?>
                <div class="comments-list" id="mobile-comments-list">
                    <!-- Comments will be duplicated here for mobile -->
                </div>
            </div>
        </div>
        
        <!-- Left Panel: Desktop Comments -->
        <div class="col-lg-4 left-panel p-0 m-0">
            
            <!-- Action Icon Bar -->
            <div class="action-bar">
                <div class="icon-container">
                    <a href="/social/" title="Home">
                        <i class="bi bi-house-door-fill text-dark"></i>
                        <div class="icon-title">Home</div>
                    </a>
                    <a href="/social/search" title="Search">
                        <i class="bi bi-search text-dark"></i>
                        <div class="icon-title">Search</div>
                    </a>
                    <a href="/social/create" title="Create Post">
                        <i class="bi bi-plus-circle-fill text-dark"></i>
                        <div class="icon-title">Create</div>
                    </a>
                    <a href="/social/activity" title="Bookmarks & Activity">
                        <i class="bi bi-bookmark-fill text-dark"></i>
                        <div class="icon-title">Activity</div>
                    </a>
                    <a href="/social/settings" title="Settings">
                        <i class="bi bi-gear-fill text-dark"></i>
                        <div class="icon-title">Settings</div>
                    </a>
                </div>
            </div>
            
            <hr class="mt-0 mb-0">
            
            <!-- Comments Container -->
            <div class="comments-container">
                <?php include($_SERVER['DOCUMENT_ROOT'] . '/social/components/write-comment.inc'); ?>
                
                <div class="comments-list" id="desktop-comments-list">
                    <?php if ($numComments == 0): ?>
                        <div class="no-comments-message">
                            <div class="icon">
                                <?php echo $icons_writecomment[array_rand($icons_writecomment)]; ?>
                            </div>
                            <div class="text-muted">No comments yet</div>
                            <div class="text-muted small mt-2">Start the conversation!</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): 
                            $avatarSrc = !empty($comment['avatar_url']) ? $comment['avatar_url'] : "/public/avatars/sample_users/placeholder_1.png";
                            $commentText = htmlspecialchars($comment['content']);
                            $likeCount = $comment['like_count'] ?? 0;
                            $username = htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']);
                            $timeAgo = $social->formatTimeAgo($comment['created_at']);
                        ?>
                            <div class="comment-item">
                                <a href="/social/user-profile?user=<?php echo $comment['user_id']; ?>">
                                    <img src="<?php echo $avatarSrc; ?>" alt="User Avatar">
                                </a>
                                <div class="comment-body">
                                    <div class="comment-header">
                                        <a href="/social/user-profile?user=<?php echo $comment['user_id']; ?>" class="text-decoration-none text-dark">
                                            <strong><?php echo $username; ?></strong>
                                        </a>
                                    </div>
                                    <div class="comment-text">
                                        <?php echo $commentText; ?>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="text-muted small me-2"><?php echo $timeAgo; ?></span>
                                            <a href="#" class="reply-link text-decoration-none">Reply</a>
                                        </div>
                                        <span class="like-info text-muted">
                                            <i class="bi bi-hand-thumbs-up"></i> <?php echo $likeCount; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Panel: Main Content -->
        <div class="col-lg-8 right-panel pe-lg-4">
            <?php
            // Randomly select post type for display
            $postTypes = ['images', 'images_audio', 'video', 'text', 'text_audio'];
            $post['type'] = $postTypes[array_rand($postTypes)];
            
            // Include appropriate content template
            $contentFile = $_SERVER['DOCUMENT_ROOT'] . '/social/components/postcontent-' . $post['type'] . '.inc';
            if (file_exists($contentFile)) {
                include($contentFile);
            } else {
                // Fallback to text content
                include($_SERVER['DOCUMENT_ROOT'] . '/social/components/postcontent-text.inc');
            }
            ?>
            
            <!-- Mobile Comments Button -->
            <button class="btn btn-primary d-lg-none position-fixed" 
                    style="bottom: 20px; right: 20px; z-index: 1000;"
                    onclick="toggleMobileComments()">
                <i class="bi bi-chat-dots"></i> Comments (<?php echo $numComments; ?>)
            </button>
        </div>
    </div>
</div>

<script>
// Toggle mobile comments overlay
function toggleMobileComments() {
    const overlay = document.getElementById('mobile-comments');
    overlay.classList.toggle('active');
    
    // Copy comments to mobile view if needed
    if (overlay.classList.contains('active')) {
        const desktopComments = document.getElementById('desktop-comments-list').innerHTML;
        document.getElementById('mobile-comments-list').innerHTML = desktopComments;
    }
}

// Handle comment text expansion
document.addEventListener('DOMContentLoaded', function() {
    // Close mobile overlay when clicking outside
    document.getElementById('mobile-comments').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // L for like
    if (e.key === 'l' || e.key === 'L') {
        // Trigger like action
    }
    // M for mute
    if (e.key === 'm' || e.key === 'M') {
        // Toggle mute
    }
    // Arrow keys for navigation
    if (e.key === 'ArrowUp') {
        // Previous post
    }
    if (e.key === 'ArrowDown') {
        // Next post
    }
});
</script>

<?php
include($_SERVER['DOCUMENT_ROOT'] . '/social/components/js-scrolling.inc');

$display_footertype = 'none';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>