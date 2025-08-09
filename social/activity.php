<?php
$addClasses[] = 'Social';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check authentication  
if (empty($current_user_data['user_id'])) {
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $current_user_data['user_id'];

// Get user activity from database
$activities = $social->getUserActivity($user_id, 50, 0);

// Get user stats for summary
$user_stats = $social->getUserStats($user_id);

// Activity type configurations
$activity_types = [
    'created_post' => ['icon' => 'bi-pencil-square', 'color' => 'primary', 'text' => 'created a post'],
    'liked_post' => ['icon' => 'bi-heart-fill', 'color' => 'danger', 'text' => 'liked a post'],
    'commented' => ['icon' => 'bi-chat-fill', 'color' => 'info', 'text' => 'commented on a post'],
    'shared_post' => ['icon' => 'bi-share-fill', 'color' => 'success', 'text' => 'shared a post'],
    'followed_user' => ['icon' => 'bi-person-plus-fill', 'color' => 'secondary', 'text' => 'followed someone'],
    'bookmarked_post' => ['icon' => 'bi-bookmark-fill', 'color' => 'warning', 'text' => 'bookmarked a post'],
    'deleted_post' => ['icon' => 'bi-trash', 'color' => 'dark', 'text' => 'deleted a post']
];

// Page and header includes
$pagetitle = 'Activity - Birthday Gold Social';
$bodycontentclass = 'social-activity-page';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

include($_SERVER['DOCUMENT_ROOT'] . '/social/components/header-nav.inc');
?>

<style>
.activity-item {
    display: flex;
    align-items: start;
    padding: 15px;
    border-bottom: 1px solid #eee;
    transition: background-color 0.2s;
}
.activity-item:hover {
    background-color: #f8f9fa;
}
.activity-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f0f0;
    border-radius: 50%;
    margin-right: 15px;
    flex-shrink: 0;
}
.activity-content {
    flex: 1;
}
.activity-time {
    margin-top: 5px;
}
</style>

<div class="container my-5">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col">
            <h1><i class="bi bi-activity"></i> Your Activity</h1>
        </div>
    </div>

    <div class="row">
        <!-- Left Panel: Summary Stats -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Activity Summary</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-file-post"></i> Posts Created</span>
                            <span class="badge bg-primary rounded-pill"><?= $user_stats['post_count'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-people"></i> Followers</span>
                            <span class="badge bg-info rounded-pill"><?= $user_stats['follower_count'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-person-check"></i> Following</span>
                            <span class="badge bg-success rounded-pill"><?= $user_stats['following_count'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-heart"></i> Likes Received</span>
                            <span class="badge bg-danger rounded-pill"><?= $user_stats['likes_received'] ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body text-center">
                    <h6 class="card-title">Quick Actions</h6>
                    <a href="/social/create.php" class="btn btn-primary btn-sm w-100 mb-2">
                        <i class="bi bi-plus-circle"></i> Create Post
                    </a>
                    <a href="/social/" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-house"></i> Back to Feed
                    </a>
                </div>
            </div>
        </div>

        <!-- Right Panel: Activity Feed -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Activity</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($activities)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <p class="text-muted mt-3">No activity yet. Start by creating your first post!</p>
                        <a href="/social/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Create Your First Post
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($activities as $activity): 
                            $type_config = $activity_types[$activity['activity_type']] ?? [
                                'icon' => 'bi-circle',
                                'color' => 'secondary', 
                                'text' => $activity['activity_type']
                            ];
                        ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="bi <?= $type_config['icon'] ?> text-<?= $type_config['color'] ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div>
                                    You <strong><?= $type_config['text'] ?></strong>
                                    <?php if ($activity['related_id']): ?>
                                        <?php if (in_array($activity['activity_type'], ['created_post', 'liked_post', 'commented', 'shared_post', 'bookmarked_post'])): ?>
                                            <a href="/social/post.php?id=<?= $activity['related_id'] ?>" class="text-decoration-none">
                                                <span class="text-muted">#<?= $activity['related_id'] ?></span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">#<?= $activity['related_id'] ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-time text-muted small">
                                    <i class="bi bi-clock"></i> <?= $social->formatTimeAgo($activity['created_at']) ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Footer includes
$display_footertype = 'none';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>