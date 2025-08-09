<?php
$addClasses[] = 'Social';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Site-controller handles authentication and provides $current_user_data
// If not logged in, $current_user_data will be empty
if (empty($current_user_data['user_id'])) {
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $current_user_data['user_id'];

// Get feed type from URL parameter
$feed_type = $_GET['feed'] ?? 'all';
$current_page = intval($_GET['page'] ?? 1);
$posts_per_page = 20;
$offset = ($current_page - 1) * $posts_per_page;

// Fetch posts from API
$posts = $social->getFeed($user_id, $posts_per_page, $offset, $feed_type);

// Get user stats for sidebar
$user_stats = $social->getUserStats($user_id);

// Get notifications count
$sql = "SELECT COUNT(*) as unread_count FROM bg_social_notifications WHERE user_id = :user_id AND is_read = 0";
$result = $database->getrow($sql, ['user_id' => $user_id]);
$unread_notifications = $result['unread_count'] ?? 0;

$pagetitle = 'Social Feed - Birthday Gold';
$bodycontentclass = 'social-feed-page';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<style>
.social-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
.feed-nav { background: white; border-radius: 10px; padding: 15px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.feed-nav-tabs { display: flex; gap: 10px; }
.feed-nav-tab { padding: 8px 16px; border-radius: 20px; text-decoration: none; color: #666; transition: all 0.3s; }
.feed-nav-tab:hover { background: #f0f0f0; }
.feed-nav-tab.active { background: #007bff; color: white; }

.social-layout { display: flex; gap: 20px; }
.social-sidebar { flex: 0 0 280px; }
.social-feed { flex: 1; min-width: 0; }

.user-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.user-card-avatar { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 15px; display: block; }
.user-card-name { text-align: center; font-size: 1.2em; font-weight: bold; margin-bottom: 5px; }
.user-card-username { text-align: center; color: #666; margin-bottom: 15px; }
.user-card-stats { display: flex; justify-content: space-around; padding-top: 15px; border-top: 1px solid #eee; }
.stat-item { text-align: center; }
.stat-value { font-size: 1.2em; font-weight: bold; display: block; }
.stat-label { font-size: 0.9em; color: #666; }

.create-post-box { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.create-post-input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 25px; resize: none; }
.create-post-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; }
.post-options { display: flex; gap: 15px; }
.post-option-btn { background: none; border: none; color: #666; cursor: pointer; display: flex; align-items: center; gap: 5px; }
.post-option-btn:hover { color: #007bff; }

.post-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.post-header { display: flex; align-items: center; margin-bottom: 15px; }
.post-avatar { width: 48px; height: 48px; border-radius: 50%; margin-right: 12px; }
.post-user-info { flex: 1; }
.post-username { font-weight: bold; color: #333; text-decoration: none; }
.post-username:hover { text-decoration: underline; }
.post-time { font-size: 0.9em; color: #666; }
.post-menu { position: relative; }
.post-menu-btn { background: none; border: none; color: #666; cursor: pointer; padding: 5px; }

.post-content { margin-bottom: 15px; line-height: 1.5; }
.post-media { margin: 15px -20px; }
.post-media img { width: 100%; height: auto; }
.post-media video { width: 100%; height: auto; }

.post-actions { display: flex; gap: 20px; padding-top: 15px; border-top: 1px solid #eee; }
.post-action-btn { background: none; border: none; color: #666; cursor: pointer; display: flex; align-items: center; gap: 5px; padding: 5px 10px; border-radius: 5px; transition: all 0.3s; }
.post-action-btn:hover { background: #f0f0f0; }
.post-action-btn.liked { color: #e74c3c; }
.post-action-btn.bookmarked { color: #f39c12; }

.comments-section { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
.comment { display: flex; margin-bottom: 15px; }
.comment-avatar { width: 36px; height: 36px; border-radius: 50%; margin-right: 10px; }
.comment-content { flex: 1; background: #f8f9fa; padding: 10px; border-radius: 10px; }
.comment-username { font-weight: bold; margin-bottom: 5px; }
.comment-text { font-size: 0.95em; }
.comment-actions { margin-top: 5px; font-size: 0.85em; }
.comment-action { color: #666; margin-right: 15px; cursor: pointer; }
.comment-action:hover { text-decoration: underline; }

.write-comment { display: flex; align-items: center; margin-top: 15px; }
.write-comment input { flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 20px; margin-left: 10px; }

.empty-state { text-align: center; padding: 60px 20px; color: #666; }
.empty-state i { font-size: 4em; margin-bottom: 20px; color: #ddd; }

@media (max-width: 768px) {
    .social-layout { flex-direction: column; }
    .social-sidebar { flex: 1; }
    .user-card { display: none; }
    .trending-card { display: none; }
}
</style>

<div class="social-container">
    <!-- Feed Navigation -->
    <div class="feed-nav">
        <div class="feed-nav-tabs">
            <a href="?feed=all" class="feed-nav-tab <?php echo $feed_type === 'all' ? 'active' : ''; ?>">
                <i class="bi bi-house"></i> All Posts
            </a>
            <a href="?feed=following" class="feed-nav-tab <?php echo $feed_type === 'following' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i> Following
            </a>
            <a href="?feed=trending" class="feed-nav-tab <?php echo $feed_type === 'trending' ? 'active' : ''; ?>">
                <i class="bi bi-fire"></i> Trending
            </a>
        </div>
    </div>

    <div class="social-layout">
        <!-- Left Sidebar -->
        <div class="social-sidebar">
            <!-- User Card -->
            <div class="user-card">
                <?php
                $user_avatar = $database->getrow("SELECT description FROM bg_user_attributes WHERE user_id = :user_id AND type = 'profile_image' AND name = 'avatar' AND status = 'active' AND category = 'primary' LIMIT 1", ['user_id' => $user_id]);
                $avatar_url = $user_avatar['description'] ?? '/avatars/' . $user_id . '.png';
                ?>
                <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Avatar" class="user-card-avatar">
                <div class="user-card-name"><?php echo htmlspecialchars($current_user_data['first_name'] . ' ' . $current_user_data['last_name']); ?></div>
                <div class="user-card-username">@<?php echo htmlspecialchars($current_user_data['username']); ?></div>
                
                <div class="user-card-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($user_stats['post_count']); ?></span>
                        <span class="stat-label">Posts</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($user_stats['follower_count']); ?></span>
                        <span class="stat-label">Followers</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($user_stats['following_count']); ?></span>
                        <span class="stat-label">Following</span>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="user-card">
                <h5 style="margin-bottom: 15px;">Quick Links</h5>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="/social/create.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Create Post
                    </a>
                    <a href="/social/activity.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-activity"></i> Your Activity
                    </a>
                    <a href="/social/bookmarks.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-bookmark"></i> Bookmarks
                    </a>
                    <a href="/social/notifications.php" class="btn btn-outline-secondary btn-sm position-relative">
                        <i class="bi bi-bell"></i> Notifications
                        <?php if ($unread_notifications > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $unread_notifications; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Feed -->
        <div class="social-feed">
            <!-- Create Post Box -->
            <div class="create-post-box">
                <form id="quickPostForm">
                    <textarea class="create-post-input" placeholder="Share your birthday celebration or tips..." rows="3"></textarea>
                    <div class="create-post-actions">
                        <div class="post-options">
                            <button type="button" class="post-option-btn" onclick="document.getElementById('mediaInput').click()">
                                <i class="bi bi-image"></i> Photo
                            </button>
                            <button type="button" class="post-option-btn">
                                <i class="bi bi-play-circle"></i> Video
                            </button>
                            <button type="button" class="post-option-btn">
                                <i class="bi bi-emoji-smile"></i> Emoji
                            </button>
                            <input type="file" id="mediaInput" hidden accept="image/*,video/*">
                        </div>
                        <button type="submit" class="btn btn-primary">Post</button>
                    </div>
                </form>
            </div>

            <!-- Posts Feed -->
            <div id="postsFeed">
                <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h3>No posts yet</h3>
                    <p>Be the first to share something!</p>
                    <a href="/social/create.php" class="btn btn-primary mt-3">Create First Post</a>
                </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                    <div class="post-card" data-post-id="<?php echo $post['post_id']; ?>">
                        <!-- Post Header -->
                        <div class="post-header">
                            <img src="<?php echo htmlspecialchars($post['avatar_url'] ?? '/avatars/default.png'); ?>" alt="Avatar" class="post-avatar">
                            <div class="post-user-info">
                                <a href="/social/user-profile.php?id=<?php echo $post['user_id']; ?>" class="post-username">
                                    <?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?>
                                </a>
                                <div class="post-time">
                                    <?php echo $social->formatTimeAgo($post['created_at']); ?>
                                    <?php if ($post['visibility'] !== 'public'): ?>
                                    · <i class="bi bi-<?php echo $post['visibility'] === 'friends' ? 'people' : 'lock'; ?>"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($post['user_id'] == $user_id): ?>
                            <div class="post-menu">
                                <button class="post-menu-btn" onclick="togglePostMenu(<?php echo $post['post_id']; ?>)">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Post Content -->
                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                            
                            <?php if (!empty($post['hashtags'])): ?>
                            <div class="post-hashtags mt-2">
                                <?php 
                                $hashtags = json_decode($post['hashtags'], true);
                                if ($hashtags):
                                    foreach ($hashtags as $hashtag): ?>
                                    <a href="/social/search.php?q=<?php echo urlencode($hashtag); ?>" class="text-primary">
                                        #<?php echo htmlspecialchars($hashtag); ?>
                                    </a>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Post Media -->
                        <?php if (!empty($post['media_urls'])): ?>
                        <div class="post-media">
                            <?php 
                            $media = json_decode($post['media_urls'], true);
                            if ($media && is_array($media)):
                                foreach ($media as $item): ?>
                                <?php if (strpos($item['type'] ?? '', 'image') !== false): ?>
                                    <img src="<?php echo htmlspecialchars($item['url']); ?>" alt="Post media">
                                <?php elseif (strpos($item['type'] ?? '', 'video') !== false): ?>
                                    <video controls>
                                        <source src="<?php echo htmlspecialchars($item['url']); ?>" type="<?php echo htmlspecialchars($item['type']); ?>">
                                    </video>
                                <?php endif; ?>
                                <?php endforeach;
                            endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Post Actions -->
                        <div class="post-actions">
                            <button class="post-action-btn like-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" 
                                    onclick="toggleLike(<?php echo $post['post_id']; ?>)">
                                <i class="bi bi-heart<?php echo $post['user_liked'] ? '-fill' : ''; ?>"></i>
                                <span class="like-count"><?php echo $post['like_count']; ?></span>
                            </button>
                            
                            <button class="post-action-btn" onclick="toggleComments(<?php echo $post['post_id']; ?>)">
                                <i class="bi bi-chat"></i>
                                <span><?php echo $post['comment_count']; ?></span>
                            </button>
                            
                            <button class="post-action-btn" onclick="sharePost(<?php echo $post['post_id']; ?>)">
                                <i class="bi bi-share"></i>
                                <span><?php echo $post['share_count']; ?></span>
                            </button>
                            
                            <button class="post-action-btn bookmark-btn <?php echo $post['user_bookmarked'] ? 'bookmarked' : ''; ?>" 
                                    onclick="toggleBookmark(<?php echo $post['post_id']; ?>)">
                                <i class="bi bi-bookmark<?php echo $post['user_bookmarked'] ? '-fill' : ''; ?>"></i>
                            </button>
                        </div>

                        <!-- Comments Section (Hidden by default) -->
                        <div class="comments-section" id="comments-<?php echo $post['post_id']; ?>" style="display: none;">
                            <div class="comments-list"></div>
                            <div class="write-comment">
                                <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Your avatar" class="comment-avatar">
                                <input type="text" placeholder="Write a comment..." 
                                       onkeypress="if(event.key==='Enter') postComment(<?php echo $post['post_id']; ?>, this.value, this)">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <?php if (count($posts) >= $posts_per_page): ?>
                    <div class="text-center my-4">
                        <button class="btn btn-outline-primary" onclick="loadMorePosts()">
                            Load More Posts
                        </button>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Post interactions
async function toggleLike(postId) {
    try {
        const response = await fetch('/api/social/like.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `type=post&id=${postId}`
        });
        
        const data = await response.json();
        if (data.success) {
            const btn = document.querySelector(`[data-post-id="${postId}"] .like-btn`);
            const icon = btn.querySelector('i');
            const count = btn.querySelector('.like-count');
            
            if (data.data.liked) {
                btn.classList.add('liked');
                icon.className = 'bi bi-heart-fill';
            } else {
                btn.classList.remove('liked');
                icon.className = 'bi bi-heart';
            }
            count.textContent = data.data.like_count;
        }
    } catch (error) {
        console.error('Error liking post:', error);
    }
}

async function toggleBookmark(postId) {
    try {
        const response = await fetch('/api/social/bookmark.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `post_id=${postId}`
        });
        
        const data = await response.json();
        if (data.success) {
            const btn = document.querySelector(`[data-post-id="${postId}"] .bookmark-btn`);
            const icon = btn.querySelector('i');
            
            if (data.data.bookmarked) {
                btn.classList.add('bookmarked');
                icon.className = 'bi bi-bookmark-fill';
            } else {
                btn.classList.remove('bookmarked');
                icon.className = 'bi bi-bookmark';
            }
        }
    } catch (error) {
        console.error('Error bookmarking post:', error);
    }
}

async function toggleComments(postId) {
    const commentsSection = document.getElementById(`comments-${postId}`);
    
    if (commentsSection.style.display === 'none') {
        commentsSection.style.display = 'block';
        
        // Load comments if not already loaded
        if (!commentsSection.dataset.loaded) {
            await loadComments(postId);
            commentsSection.dataset.loaded = 'true';
        }
    } else {
        commentsSection.style.display = 'none';
    }
}

async function loadComments(postId) {
    try {
        const response = await fetch(`/api/social/comment.php?post_id=${postId}`);
        const data = await response.json();
        
        if (data.success) {
            const commentsList = document.querySelector(`#comments-${postId} .comments-list`);
            commentsList.innerHTML = '';
            
            data.data.comments.forEach(comment => {
                const commentHtml = `
                    <div class="comment">
                        <img src="${comment.avatar_url || '/avatars/default.png'}" alt="Avatar" class="comment-avatar">
                        <div class="comment-content">
                            <div class="comment-username">${comment.first_name} ${comment.last_name}</div>
                            <div class="comment-text">${comment.comment_text}</div>
                            <div class="comment-actions">
                                <span class="comment-action" onclick="likeComment(${comment.comment_id})">
                                    Like (${comment.like_count || 0})
                                </span>
                                <span class="comment-action" onclick="replyToComment(${comment.comment_id})">
                                    Reply
                                </span>
                                <span class="text-muted">${formatTimeAgo(comment.created_at)}</span>
                            </div>
                        </div>
                    </div>
                `;
                commentsList.innerHTML += commentHtml;
            });
        }
    } catch (error) {
        console.error('Error loading comments:', error);
    }
}

async function postComment(postId, text, input) {
    if (!text.trim()) return;
    
    try {
        const response = await fetch('/api/social/comment.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `post_id=${postId}&comment_text=${encodeURIComponent(text)}`
        });
        
        const data = await response.json();
        if (data.success) {
            input.value = '';
            await loadComments(postId);
            
            // Update comment count
            const commentBtn = document.querySelector(`[data-post-id="${postId}"] .post-actions button:nth-child(2) span`);
            commentBtn.textContent = parseInt(commentBtn.textContent) + 1;
        }
    } catch (error) {
        console.error('Error posting comment:', error);
    }
}

// Quick post form
document.getElementById('quickPostForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const content = e.target.querySelector('textarea').value;
    if (!content.trim()) return;
    
    try {
        const response = await fetch('/api/social/post.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=create&content=${encodeURIComponent(content)}&visibility=public`
        });
        
        const data = await response.json();
        if (data.success) {
            // Reload page to show new post
            window.location.reload();
        }
    } catch (error) {
        console.error('Error creating post:', error);
        alert('Failed to create post. Please try again.');
    }
});

// Helper function to format time ago
function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + 'd ago';
    
    return date.toLocaleDateString();
}

// Load more posts
let currentPage = <?php echo $current_page; ?>;
async function loadMorePosts() {
    currentPage++;
    window.location.href = `?feed=<?php echo $feed_type; ?>&page=${currentPage}`;
}
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();