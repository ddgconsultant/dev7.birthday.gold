<?php
$addClasses[] = 'Social';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check authentication
if (empty($current_user_data['user_id'])) {
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $current_user_data['user_id'];

// Get search query if provided
$search_query = $_GET['q'] ?? '';
$search_type = $_GET['type'] ?? 'posts'; // posts or users

// Perform search if query exists
$search_results = [];
$user_results = [];
if (!empty($search_query)) {
    if ($search_type == 'users') {
        $user_results = $social->searchUsers($search_query, 20, 0);
    } else {
        $search_results = $social->searchPosts($search_query, 20, 0);
    }
}

// Page setup
$pagetitle = 'Search - Birthday Gold Social';
$bodycontentclass = 'social-search-page';
$header_flush = true; // Flush content with header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
// Removed old header-nav.inc - using mobile nav at bottom instead
?>

<style>
/* Remove top margin/padding - flush with header */
.main-content {
    margin-top: 0 !important;
    padding-top: 0 !important;
}
body .main-content {
    margin-top: 0 !important;
    padding-top: 0 !important;
}

.search-container { max-width: 800px; margin: 0 auto; padding: 20px; }
.search-tabs { margin-bottom: 20px; }
.search-result { background: white; border-radius: 10px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.2s; }
.search-result:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
.user-result { display: flex; align-items: center; }
.user-avatar { width: 60px; height: 60px; border-radius: 50%; margin-right: 15px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; }
.user-info { flex: 1; }
.user-stats { display: flex; gap: 20px; margin-top: 5px; }
.user-stat { font-size: 0.9rem; color: #666; }
.post-result { border-left: 4px solid #007bff; }
.post-meta { display: flex; align-items: center; margin-bottom: 10px; }
.post-avatar { width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; background: #f0f0f0; }
.post-author { font-weight: bold; }
.post-time { color: #666; font-size: 0.9rem; margin-left: auto; }
.post-content { margin: 10px 0; }
.post-actions { display: flex; gap: 15px; margin-top: 10px; }
.post-action { color: #666; font-size: 0.9rem; }
.no-results { text-align: center; padding: 50px; color: #666; }
.search-filters { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
</style>

<div class="search-container">
    <h1 class="mb-4"><i class="bi bi-search"></i> Search</h1>
    
    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="">
                <div class="input-group">
                    <input type="text" 
                           class="form-control form-control-lg" 
                           name="q" 
                           placeholder="Search for posts or users..." 
                           value="<?= htmlspecialchars($search_query) ?>"
                           autofocus>
                    <select name="type" class="form-select" style="max-width: 150px;">
                        <option value="posts" <?= $search_type == 'posts' ? 'selected' : '' ?>>Posts</option>
                        <option value="users" <?= $search_type == 'users' ? 'selected' : '' ?>>Users</option>
                    </select>
                    <button class="btn btn-primary btn-lg" type="submit">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (!empty($search_query)): ?>
    <!-- Search Results -->
    <div class="search-results">
        <h4 class="mb-3">
            <?php if ($search_type == 'users'): ?>
                Users matching "<?= htmlspecialchars($search_query) ?>"
            <?php else: ?>
                Posts matching "<?= htmlspecialchars($search_query) ?>"
            <?php endif; ?>
        </h4>
        
        <?php if ($search_type == 'users' && !empty($user_results)): ?>
            <!-- User Results -->
            <?php foreach ($user_results as $user): ?>
            <div class="search-result user-result">
                <div class="user-avatar">
                    <?php if (!empty($user['avatar_url'])): ?>
                        <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    <?php else: ?>
                        <i class="bi bi-person-circle" style="font-size: 30px; color: #999;"></i>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <div>
                        <a href="/social/user-profile.php?id=<?= $user['user_id'] ?>" class="text-decoration-none">
                            <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                        </a>
                        <?php if (!empty($user['username'])): ?>
                            <span class="text-muted">@<?= htmlspecialchars($user['username']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="user-stats">
                        <span class="user-stat">
                            <i class="bi bi-file-post"></i> <?= $user['post_count'] ?> posts
                        </span>
                        <span class="user-stat">
                            <i class="bi bi-people"></i> <?= $user['follower_count'] ?> followers
                        </span>
                    </div>
                    <?php if ($user['user_id'] != $user_id): ?>
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="followUser(<?= $user['user_id'] ?>)">
                        <i class="bi bi-person-plus"></i> Follow
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
        <?php elseif ($search_type == 'posts' && !empty($search_results)): ?>
            <!-- Post Results -->
            <?php foreach ($search_results as $post): ?>
            <div class="search-result post-result">
                <div class="post-meta">
                    <div class="post-avatar">
                        <?php if (!empty($post['avatar_url'])): ?>
                            <img src="<?= htmlspecialchars($post['avatar_url']) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <i class="bi bi-person-circle" style="font-size: 25px; color: #999;"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="post-author"><?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?></div>
                        <?php if (!empty($post['username'])): ?>
                            <small class="text-muted">@<?= htmlspecialchars($post['username']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="post-time">
                        <?= $social->formatTimeAgo($post['created_at']) ?>
                    </div>
                </div>
                <div class="post-content">
                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                </div>
                <div class="post-actions">
                    <span class="post-action">
                        <i class="bi bi-heart"></i> <?= $post['like_count'] ?> likes
                    </span>
                    <span class="post-action">
                        <i class="bi bi-chat"></i> <?= $post['comment_count'] ?> comments
                    </span>
                    <a href="/social/post.php?id=<?= $post['post_id'] ?>" class="post-action text-decoration-none">
                        <i class="bi bi-arrow-right-circle"></i> View Post
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            
        <?php else: ?>
            <!-- No Results -->
            <div class="no-results">
                <i class="bi bi-search display-1 text-muted"></i>
                <p class="mt-3">No <?= $search_type ?> found matching your search.</p>
                <p class="text-muted">Try different keywords or check your spelling.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php else: ?>
    <!-- Popular/Trending Section (shown when no search) -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-fire"></i> Trending Posts</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $trending = $social->getTrendingPosts(24, 5);
                    if (!empty($trending)):
                        foreach ($trending as $post): ?>
                        <div class="mb-3 pb-3 border-bottom">
                            <small class="text-muted">
                                By <?= htmlspecialchars($post['first_name']) ?>
                            </small>
                            <div class="text-truncate">
                                <?= htmlspecialchars(substr($post['content'], 0, 100)) ?>...
                            </div>
                            <a href="/social/post.php?id=<?= $post['post_id'] ?>" class="small">View →</a>
                        </div>
                        <?php endforeach; 
                    else: ?>
                        <p class="text-muted">No trending posts yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-hash"></i> Popular Topics</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="?q=%23BirthdayRewards&type=posts" class="btn btn-sm btn-outline-primary">#BirthdayRewards</a>
                        <a href="?q=%23FreeBirthday&type=posts" class="btn btn-sm btn-outline-primary">#FreeBirthday</a>
                        <a href="?q=%23BirthdayGold&type=posts" class="btn btn-sm btn-outline-primary">#BirthdayGold</a>
                        <a href="?q=%23BirthdayMonth&type=posts" class="btn btn-sm btn-outline-primary">#BirthdayMonth</a>
                        <a href="?q=%23BirthdayFreebies&type=posts" class="btn btn-sm btn-outline-primary">#BirthdayFreebies</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function followUser(userId) {
    // TODO: Implement follow functionality via AJAX
    alert('Follow functionality coming soon!');
}
</script>

<?php
// Include social mobile navigation
include($_SERVER['DOCUMENT_ROOT'] . '/social/components/social-nav-mobile.inc');

$display_footertype = 'none';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>