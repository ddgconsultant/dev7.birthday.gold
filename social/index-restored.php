<?php
$addClasses[] = 'Social';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check authentication
if (empty($current_user_data['user_id'])) {
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $current_user_data['user_id'];

// Get post ID from URL or show first post
$postId = $_GET['post'] ?? null;

// Get feed type from URL
$feed_type = $_GET['feed'] ?? 'all';
$page = intval($_GET['page'] ?? 1);
$posts_per_page = 1; // Show one post at a time for TikTok style
$offset = ($page - 1) * $posts_per_page;

// Get current post
if ($postId) {
    // Get specific post
    $currentPost = $social->getPost($postId, $user_id);
    if (!$currentPost) {
        // Post not found, redirect to feed
        header('Location: /social/');
        exit;
    }
} else {
    // Get feed
    $feedPosts = $social->getFeed($user_id, $posts_per_page, $offset, $feed_type);
    if (!empty($feedPosts)) {
        $currentPost = $feedPosts[0];
        $postId = $currentPost['post_id'];
    } else {
        $currentPost = null;
        $postId = null;
    }
}

// Get comments for current post
$comments = [];
if ($postId) {
    $comments = $social->getComments($postId, 50, 0);
}

// Get user stats for sidebar
$user_stats = $social->getUserStats($user_id);

$pagelang = 'zxx';
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$additionalstyles .= '
<style>
.main-content { overflow: hidden }
.left-panel { display: flex; flex-direction: column; height: calc(100vh - 75px); border-right: 1px solid #dee2e6; overflow: hidden }
.comments-list { overflow-y: auto; flex-grow: 1; padding: 1rem; visibility: visible }
.comment-item { display: flex; align-items: start; margin-bottom: 1rem }
.comment-item img { width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; object-fit: cover; background: #f0f0f0; }
.comment-body { flex-grow: 1 }
.comment-header { display: flex; justify-content: space-between }
.comment-text { font-size: 0.9rem; margin-bottom: 0.5rem }
.reply-link, .like-info { font-size: 0.8rem }
.comments-list::-webkit-scrollbar { width: 8px }
.comments-list::-webkit-scrollbar-thumb { background-color: #ccc; border-radius: 4px }
.comments-list::-webkit-scrollbar-track { background-color: #f1f1f1 }
.left-panel .action-bar { display: flex; justify-content: center; padding: 10px 0; background-color: #f8f9fa }
.left-panel .action-bar a { color: #000; font-size: 1.5rem }
.left-panel .icon-container { display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 1000px }
.left-panel .icon-container a { display: flex; flex-direction: column; align-items: center; text-decoration: none; color: inherit; font-size: 1.5rem }
.left-panel .icon-title { font-size: 0.7rem; margin-top: 2px; color: #666 }
.monotypenumbers { font-family: "Roboto Mono" }
.error-message { position: fixed; top: 10px; left: 50%; transform: translateX(-50%); background-color: #dc3545; color: white; padding: 10px 20px; border-radius: 5px; z-index: 9999; display: none }

.left-panel .comments-list { display: block; overflow-y: auto }
#large-comments-panel .comments-list { height: calc(100vh - 260px); overflow-y: auto }
.right-panel .chrome-bottom-padding-1{ bottom: 20px; }
.right-panel .chrome-bottom-padding-2{ bottom: 20px; }
.right-panel .chrome-bottom-padding-3{ bottom: 50px; }
.right-panel .chrome-bottom-padding-4{ bottom: 70px; }
.right-panel .chrome-bottom-padding-seekbar{ bottom: 34px; }
.right-panel .chrome-bottom-padding-carousel{ bottom: 30px; }
.right-panel .chrome-bottom-padding-carousel-audio{ bottom: 65px; }

.post-content-container { 
    height: calc(100vh - 100px); 
    display: flex; 
    flex-direction: column; 
    justify-content: center; 
    align-items: center;
    position: relative;
}

.post-actions-sidebar {
    position: absolute;
    right: 20px;
    bottom: 100px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    z-index: 100;
}

.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    transition: transform 0.2s;
}

.action-btn:hover {
    transform: scale(1.1);
}

.action-btn i {
    font-size: 2rem;
    margin-bottom: 5px;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
}

.action-btn span {
    font-size: 0.9rem;
    font-weight: bold;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}

.action-btn.liked i {
    color: #ff0000;
}

.action-btn.bookmarked i {
    color: #ffd700;
}

.post-navigation {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 100%;
    display: flex;
    justify-content: space-between;
    padding: 0 20px;
    pointer-events: none;
}

.post-navigation button {
    pointer-events: auto;
    background: rgba(0,0,0,0.5);
    color: white;
    border: none;
    padding: 15px;
    border-radius: 50%;
    cursor: pointer;
    transition: background 0.3s;
}

.post-navigation button:hover {
    background: rgba(0,0,0,0.8);
}

.post-text-content {
    max-width: 600px;
    padding: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    color: white;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.post-media-content {
    max-width: 90%;
    max-height: 80vh;
}

.post-media-content img {
    max-width: 100%;
    max-height: 80vh;
    object-fit: contain;
    border-radius: 10px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.post-media-content video {
    max-width: 100%;
    max-height: 80vh;
    border-radius: 10px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.no-posts-message {
    text-align: center;
    padding: 50px;
    color: #666;
}

@media (max-width:991.98px) {
    .left-panel { display: none }
    .comment-overlay.active { display: block !important }
    .right-panel .chrome-bottom-padding-1{ bottom: 30px; }
    .right-panel .chrome-bottom-padding-2{ bottom: 50px; }
    .right-panel .chrome-bottom-padding-3{ bottom: 70px; }
    .right-panel .chrome-bottom-padding-4{ bottom: 90px; }
    .right-panel .chrome-bottom-padding-seekbar{ bottom: 65px; }
    .right-panel .chrome-bottom-padding-carousel{ bottom: 145px; }
    .right-panel .chrome-bottom-padding-carousel-audio{ bottom: 145px; }
    .right-panel .post-header img { width: 40px !important; height: 40px !important; border-radius: 50% }
    .right-panel .soundtrack-avatar-icon{ width:40px !important; height:40px !important; }
}

.comment-overlay { 
    display: none; 
    position: fixed; 
    bottom: 0; 
    left: 0; 
    width: 100%; 
    height: calc(50vh - 0px); 
    background-color: rgba(255, 255, 255, 0.98); 
    z-index: 1050; 
    overflow-y: hidden; 
    padding: 1rem; 
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1) 
}
.comment-overlay .close-btn { position: absolute; top: 10px; right: 10px; font-size: 1.5rem; color: #333; cursor: pointer }
.write-comment { background-color: #fff; width: 100%; position: relative; padding: 1rem; border-bottom: 1px solid #dee2e6; flex-shrink: 0 }
.comment-overlay .write-comment { left: 0; width: calc(100% - 20px); padding: 1rem; background-color: #fff; border-bottom: 1px solid #dee2e6 }
#large-comments-panel .comments-list { height: calc(100vh - 260px); overflow-y: auto }
#small-comments-panel .comments-list { height: calc(45vh); overflow-y: auto }
#small-comments-panel .comments-list { overflow: auto !important }
</style>';

?>

<div class="container-fluid main-content p-0 pt-0 mb-0 pt-lg-3">
    <div class="row my-0 py-0">
        
        <!-- Mobile Comment Overlay -->
        <div class="comment-overlay d-lg-none"> 
            <span class="close-btn">&times;</span>
            <div id="small-comments-panel" class="comments-container">
                <div class="hookto comments-list" 
                     data-hook-to-mobile-first="true"
                     data-hook-to="#hookto-large"
                     data-hook-to-position="after"
                     data-hook-to-return="991.98">
                    
                    <!-- Write Comment Section -->
                    <?php include($_SERVER['DOCUMENT_ROOT'] . '/social/components/write-comment.inc'); ?>
                    
                    <!-- Comments List -->
                    <?php if (empty($comments)): ?>
                        <div class="text-center text-muted mt-5">No comments yet</div>
                        <div class="text-center my-4">
                            <i class="bi bi-chat-dots" style="font-size: 3rem; color: #ddd;"></i>
                        </div>
                        <div class="text-center text-muted">Start the conversation!</div>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                        <div class="comment-item" data-comment-id="<?= $comment['post_id'] ?>">
                            <a href="/social/user-profile.php?id=<?= $comment['user_id'] ?>">
                                <?php if (!empty($comment['avatar_url'])): ?>
                                    <img src="<?= htmlspecialchars($comment['avatar_url']) ?>" alt="User Avatar">
                                <?php else: ?>
                                    <img src="/public/avatars/sample_users/placeholder_1.png" alt="User Avatar">
                                <?php endif; ?>
                            </a>
                            <div class="comment-body">
                                <div class="comment-header">
                                    <a href="/social/user-profile.php?id=<?= $comment['user_id'] ?>">
                                        <strong><?= htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']) ?></strong>
                                    </a>
                                </div>
                                <div class="comment-text">
                                    <?= nl2br(htmlspecialchars($comment['content'])) ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted small me-2"><?= $social->formatTimeAgo($comment['created_at']) ?></span>
                                        <a href="#" class="reply-link" onclick="return false;">Reply</a>
                                    </div>
                                    <span class="like-info icon-container-action text-muted" 
                                          data-action="comment-like" 
                                          data-comment-id="<?= $comment['post_id'] ?>"
                                          style="cursor: pointer;">
                                        <i class="bi bi-hand-thumbs-up-fill icon"></i> 
                                        <span class="like-count"><?= $comment['like_count'] ?? 0 ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Left Panel: Comments & Navigation -->
        <div class="col-lg-4 left-panel p-0 m-0">
            <!-- Action Icon Bar -->
            <div class="action-bar m-0 bg-secondary-subtle px-5 py-3">
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
                    <a href="/social/activity" title="Activity">
                        <i class="bi bi-bookmark-fill text-dark"></i>
                        <div class="icon-title">Activity</div>
                    </a>
                    <a href="/social/settings" title="Settings">
                        <i class="bi bi-gear-fill text-dark"></i>
                        <div class="icon-title">Settings</div>
                    </a>
                </div>
            </div>
            
            <hr class="mt-0 pt-0">
            
            <!-- Comments List -->
            <div id="large-comments-panel" class="comments-container ps-2">
                <div id="hookto-large" class="px-1">
                    <!-- Comments will be hooked here on desktop -->
                </div>
            </div>
        </div>
        
        <!-- Right Panel: Post Content -->
        <div class="col-lg-8 pe-lg-4 right-panel">
            <?php if ($currentPost): ?>
            
            <div class="post-content-container">
                <!-- Post Navigation -->
                <div class="post-navigation">
                    <?php if ($page > 1): ?>
                    <button onclick="navigatePost('prev')" title="Previous Post">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>
                    
                    <button onclick="navigatePost('next')" title="Next Post">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                
                <!-- Post Actions Sidebar -->
                <div class="post-actions-sidebar">
                    <button class="action-btn <?= $currentPost['user_liked'] ? 'liked' : '' ?>" 
                            onclick="likePost(<?= $postId ?>)" 
                            id="like-btn-<?= $postId ?>">
                        <i class="bi <?= $currentPost['user_liked'] ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                        <span id="like-count-<?= $postId ?>"><?= $currentPost['like_count'] ?></span>
                    </button>
                    
                    <button class="action-btn d-lg-none" onclick="toggleComments()">
                        <i class="bi bi-chat-dots"></i>
                        <span><?= $currentPost['comment_count'] ?></span>
                    </button>
                    
                    <button class="action-btn" onclick="sharePost(<?= $postId ?>)">
                        <i class="bi bi-share"></i>
                        <span><?= $currentPost['share_count'] ?></span>
                    </button>
                    
                    <button class="action-btn <?= $currentPost['user_bookmarked'] ? 'bookmarked' : '' ?>"
                            onclick="bookmarkPost(<?= $postId ?>)"
                            id="bookmark-btn-<?= $postId ?>">
                        <i class="bi <?= $currentPost['user_bookmarked'] ? 'bi-bookmark-fill' : 'bi-bookmark' ?>"></i>
                        <span>Save</span>
                    </button>
                </div>
                
                <!-- Post Content -->
                <?php 
                // Determine post display type based on media
                $media_urls = !empty($currentPost['media_urls']) ? json_decode($currentPost['media_urls'], true) : [];
                $media_type = $currentPost['media_type'];
                
                if ($media_type == 'image' && !empty($media_urls)): ?>
                    <!-- Image Post -->
                    <div class="post-media-content">
                        <div id="carousel-<?= $postId ?>" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach ($media_urls as $index => $url): ?>
                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                    <img src="<?= htmlspecialchars($url) ?>" alt="Post image">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($media_urls) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?= $postId ?>" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?= $postId ?>" data-bs-slide="next">
                                <span class="carousel-control-next-icon"></span>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($media_type == 'video' && !empty($media_urls)): ?>
                    <!-- Video Post -->
                    <div class="post-media-content">
                        <video controls autoplay muted loop>
                            <source src="<?= htmlspecialchars($media_urls[0]) ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                <?php else: ?>
                    <!-- Text Post -->
                    <div class="post-text-content">
                        <div class="mb-3">
                            <strong><?= htmlspecialchars($currentPost['first_name'] . ' ' . $currentPost['last_name']) ?></strong>
                            <span class="text-white-50 ms-2"><?= $social->formatTimeAgo($currentPost['created_at']) ?></span>
                        </div>
                        <div class="post-text" style="font-size: 1.2rem; line-height: 1.6;">
                            <?= nl2br(htmlspecialchars($currentPost['content'])) ?>
                        </div>
                        <?php if (!empty($currentPost['hashtags'])): ?>
                        <div class="mt-3">
                            <?php 
                            $hashtags = json_decode($currentPost['hashtags'], true);
                            foreach ($hashtags as $tag): ?>
                            <a href="/social/search.php?q=%23<?= urlencode($tag) ?>" class="text-white-50 me-2">
                                #<?= htmlspecialchars($tag) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php else: ?>
            <!-- No Posts Message -->
            <div class="no-posts-message">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h3 class="mt-3">No posts yet</h3>
                <p class="text-muted">Be the first to share something!</p>
                <a href="/social/create" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Create First Post
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Mobile comment overlay toggle
function toggleComments() {
    document.querySelector('.comment-overlay').classList.toggle('active');
}

document.querySelector('.comment-overlay .close-btn').addEventListener('click', function() {
    document.querySelector('.comment-overlay').classList.remove('active');
});

// Post navigation
function navigatePost(direction) {
    const currentPage = <?= $page ?>;
    if (direction === 'prev' && currentPage > 1) {
        window.location.href = '?page=' + (currentPage - 1) + '&feed=<?= $feed_type ?>';
    } else if (direction === 'next') {
        window.location.href = '?page=' + (currentPage + 1) + '&feed=<?= $feed_type ?>';
    }
}

// Like post
async function likePost(postId) {
    try {
        const response = await fetch('/api/social/like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ post_id: postId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const btn = document.getElementById('like-btn-' + postId);
            const countSpan = document.getElementById('like-count-' + postId);
            
            if (data.liked) {
                btn.classList.add('liked');
                btn.querySelector('i').className = 'bi bi-heart-fill';
            } else {
                btn.classList.remove('liked');
                btn.querySelector('i').className = 'bi bi-heart';
            }
            
            countSpan.textContent = data.like_count;
        }
    } catch (error) {
        console.error('Error liking post:', error);
    }
}

// Bookmark post
async function bookmarkPost(postId) {
    try {
        const response = await fetch('/api/social/bookmark.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ post_id: postId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const btn = document.getElementById('bookmark-btn-' + postId);
            
            if (data.bookmarked) {
                btn.classList.add('bookmarked');
                btn.querySelector('i').className = 'bi bi-bookmark-fill';
            } else {
                btn.classList.remove('bookmarked');
                btn.querySelector('i').className = 'bi bi-bookmark';
            }
        }
    } catch (error) {
        console.error('Error bookmarking post:', error);
    }
}

// Share post
function sharePost(postId) {
    // For now, just copy link
    const url = window.location.origin + '/social/?post=' + postId;
    navigator.clipboard.writeText(url).then(() => {
        alert('Link copied to clipboard!');
    });
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
    
    switch(e.key) {
        case 'ArrowLeft':
            navigatePost('prev');
            break;
        case 'ArrowRight':
            navigatePost('next');
            break;
        case 'l':
            if (<?= $postId ? $postId : 'null' ?>) {
                likePost(<?= $postId ? $postId : 'null' ?>);
            }
            break;
        case 'c':
            toggleComments();
            break;
    }
});

// Hook comments to desktop panel on load
document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth >= 992) {
        const mobileComments = document.querySelector('.hookto.comments-list');
        const desktopTarget = document.querySelector('#hookto-large');
        if (mobileComments && desktopTarget) {
            desktopTarget.appendChild(mobileComments);
        }
    }
});

// Handle window resize
let resizeTimer;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
        const mobileComments = document.querySelector('.hookto.comments-list');
        if (window.innerWidth >= 992) {
            const desktopTarget = document.querySelector('#hookto-large');
            if (mobileComments && desktopTarget && mobileComments.parentElement !== desktopTarget) {
                desktopTarget.appendChild(mobileComments);
            }
        } else {
            const mobileTarget = document.querySelector('#small-comments-panel');
            if (mobileComments && mobileTarget && mobileComments.parentElement !== mobileTarget) {
                mobileTarget.appendChild(mobileComments);
            }
        }
    }, 250);
});
</script>

<?php
$display_footertype = 'none';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>