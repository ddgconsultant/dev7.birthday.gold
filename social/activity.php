<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page and header includes
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

include($_SERVER['DOCUMENT_ROOT'] . '/social/components/header-nav.inc');

// Sample content generation
$activities = [
    'viewed post' => 'How to celebrate your best birthday ever',
    'commented on' => 'Great tips for planning birthday parties!',
    'created post' => 'My Birthday Celebration Ideas for 2024',
    'bookmarked post' => 'Ultimate Guide to Birthday Rewards',
    'shared post' => 'Top Birthday Destinations for 2024'
];

// Random activity counts
$activityCounts = [
    'Viewed Posts' => rand(5, 15),
    'Comments' => rand(1, 10),
    'Created Posts' => rand(1, 5),
    'Bookmarked Posts' => rand(2, 8),
    'Shared Posts' => rand(1, 5)
];

// Random user generator
$usernames = ['User1', 'User2', 'User3', 'User4', 'User5'];

// Random time ago generator
function generateTimeAgo() {
    $timeOptions = ['2 hours ago', '4 hours ago', '6 hours ago', '1 day ago', '3 days ago'];
    return $timeOptions[array_rand($timeOptions)];
}

// Random date for the right-hand side
function generateShortDate() {
    return date('m-d');
}

?>

<div class="container my-5">
    <!-- Header -->
    <div class="row mb-4">
        <h1>Activity</h1>
    </div>

    <div class="row">
        <!-- Left Panel: Summary of Activity Categories -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Activity Summary</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($activityCounts as $category => $count): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo $category; ?>
                            <span class="badge bg-primary rounded-pill"><?php echo $count; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Right Panel: User Activity Feed -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Your Activity</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php
                        // Loop to generate user activity
                        $numActivities = rand(10, 50); // Number of activities to generate
                        $activityTypes = array_keys($activities); // Get the activity types

                        for ($i = 0; $i < $numActivities; $i++) {
                            $activityType = $activityTypes[array_rand($activityTypes)];
                            $activityText = $activities[$activityType];
                            $username = $usernames[array_rand($usernames)];
                            $avatarNumber = rand(1, 10);
                            $avatarSrc = "/public/avatars/sample_users/placeholder_$avatarNumber.png";
                            $timeAgo = generateTimeAgo();
                            $shortDate = generateShortDate();
                            $likeCount = rand(1, 500); // Random like count

                            echo '
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="d-flex">
                                    <!-- Avatar and Username -->
                                    <img src="' . $avatarSrc . '" class="rounded-circle me-2" style="width: 40px; height: 40px;" alt="User Avatar">
                                    <div>
                                        <strong>' . $username . '</strong>
                                        <div class="small text-muted">' . ucfirst($activityType) . ': ' . $activityText . '</div>
                                    </div>
                                </div>
                                <!-- Likes and Date -->
                                <div class="text-end">
                                    <span class="badge bg-success">' . rand(1, 100) . ' likes</span>
                                    <div class="small text-muted">' . $shortDate . '</div>
                                </div>
                            </li>';
                        }
                        ?>
                    </ul>
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
