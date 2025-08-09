<?php
/**
 * Seed Social Module with Birthday-Themed Content
 * This script creates sample posts, comments, and interactions
 * for testing the social module functionality
 */

$addClasses[] = 'Social';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is logged in
if (empty($current_user_data['user_id'])) {
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $current_user_data['user_id'];
$pagetitle = "Seed Birthday Content";

include $dir['core_components'] . '/bg_pagestart.inc';
include $dir['core_components'] . '/bg_header.inc';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h2>Seed Birthday Content for Social Module</h2>
        </div>
        <div class="card-body">
            
<?php
// Birthday-themed posts from the original mockups
$birthday_posts = [
    [
        'content' => "🎂 Just scored my FREE birthday burger at Red Robin! No purchase necessary! Best part? They sang happy birthday and gave me ice cream too! #BirthdayFreebies #RedRobin",
        'media_type' => 'image',
        'hashtags' => ['BirthdayFreebies', 'RedRobin', 'FreeBurger']
    ],
    [
        'content' => "Pro tip: Sign up for Starbucks rewards 30 days before your birthday for a free drink! ☕ Any size, any drink, all the customizations you want! Just used mine for a $12 venti drink with 8 add-ons 😂 #StarbucksBirthday",
        'media_type' => null,
        'hashtags' => ['StarbucksBirthday', 'FreeStarbucks', 'BirthdayRewards']
    ],
    [
        'content' => "Birthday month haul: FREE items from Sephora, Ulta, Dunkin, Jersey Mike's, and Baskin Robbins! 🎉 Total value saved: $87! Who says birthdays are just for kids? #BirthdayMonth #Freebies",
        'media_type' => 'image',
        'hashtags' => ['BirthdayMonth', 'Freebies', 'BirthdayHaul']
    ],
    [
        'content' => "The Cheesecake Factory gives you a FREE slice of cheesecake for your birthday! 🍰 No purchase required, just show your ID. Got the Oreo Dream Extreme and it was AMAZING!",
        'media_type' => 'image',
        'hashtags' => ['CheesecakeFactory', 'FreeCheesecake', 'BirthdayDessert']
    ],
    [
        'content' => "Denny's = FREE Grand Slam on your birthday! No purchase needed! 🥞 Pancakes, eggs, bacon, and sausage all for FREE. Just show your ID!",
        'media_type' => null,
        'hashtags' => ['Dennys', 'FreeGrandSlam', 'BirthdayBreakfast']
    ],
    [
        'content' => "BIRTHDAY WEEK RECAP: Collected 23 birthday freebies worth $156! 🎁 Birthday Gold made it so easy to track everything. Already planning next year's route! What's your favorite birthday freebie?",
        'media_type' => null,
        'hashtags' => ['BirthdayGold', 'BirthdayWeek', 'FreebieRecap']
    ],
    [
        'content' => "Just discovered Crumbl Cookie gives you a FREE cookie during your birthday week! 🍪 No purchase necessary. The chocolate chip was still warm and gooey!",
        'media_type' => 'image',
        'hashtags' => ['CrumblCookie', 'FreeCookie', 'BirthdayTreats']
    ],
    [
        'content' => "Birthday hack: Create a dedicated email just for birthday signups! 📧 That way all your birthday rewards are in one place and you don't miss any! Started mine last year and got 47 freebies!",
        'media_type' => null,
        'hashtags' => ['BirthdayHack', 'LifeHack', 'FreebieStrategy']
    ],
    [
        'content' => "Buffalo Wild Wings birthday bonus: FREE snack-size wings! 🍗 Perfect appetizer before hitting up other birthday spots. Plus they put your name on the TV screens!",
        'media_type' => 'image',
        'hashtags' => ['BWW', 'FreeWings', 'BirthdayBonus']
    ],
    [
        'content' => "Reminder: Most birthday rewards require signing up 30+ days in advance! ⏰ Set yourself a reminder now for next year. Future you will thank you! #PlanAhead",
        'media_type' => null,
        'hashtags' => ['PlanAhead', 'BirthdayPlanning', 'ProTip']
    ]
];

// Sample comments for posts
$sample_comments = [
    "This is amazing! I need to try this!",
    "Thanks for sharing! Adding this to my birthday list 🎉",
    "I got this last year and it was awesome!",
    "Don't forget to tip your server even though it's free!",
    "How far in advance did you have to sign up?",
    "Does this work at all locations?",
    "Just signed up because of your post! Thanks!",
    "Birthday twins! I'm going there tomorrow!",
    "The app makes it so easy to redeem!",
    "Pro tip: Go early to avoid the crowds",
    "This saved my broke college student life 😂",
    "Following for more birthday deals!",
    "My birthday is next month, perfect timing!",
    "Can confirm this works! Just got mine yesterday",
    "Make sure to bring your ID, they do check!"
];

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<h4>Creating Birthday Posts...</h4>";
echo "<ul>";

// Create posts
foreach ($birthday_posts as $index => $post_data) {
    try {
        // Create the post
        $post_id = $social->createPost(
            $user_id,
            $post_data['content'],
            $post_data['media_type'],
            null, // media_data
            'public',
            null, // parent_post_id
            !empty($post_data['hashtags']) ? json_encode($post_data['hashtags']) : null
        );
        
        if ($post_id) {
            $success_count++;
            echo "<li class='text-success'>✅ Created post: " . htmlspecialchars(substr($post_data['content'], 0, 50)) . "...</li>";
            
            // Add 2-4 random comments to each post
            $num_comments = rand(2, 4);
            $used_comments = [];
            
            for ($i = 0; $i < $num_comments; $i++) {
                // Pick a random comment that hasn't been used for this post
                do {
                    $comment_index = array_rand($sample_comments);
                } while (in_array($comment_index, $used_comments));
                
                $used_comments[] = $comment_index;
                
                // Add comment from a "different user" (using same user for now in dev)
                try {
                    $comment_id = $social->addComment(
                        $post_id,
                        $user_id,
                        $sample_comments[$comment_index]
                    );
                    
                    // Randomly like some comments
                    if (rand(0, 1) === 1) {
                        $social->likePost($comment_id, $user_id);
                    }
                } catch (Exception $e) {
                    // Silently continue if comment fails
                }
            }
            
            // Add random likes to the post (3-15 likes)
            $like_count = rand(3, 15);
            for ($j = 0; $j < $like_count; $j++) {
                try {
                    // In production, these would be from different users
                    // For now, we'll just increment the like count in the database
                    $database->query(
                        "UPDATE bg_social_posts SET like_count = like_count + 1 WHERE post_id = :post_id",
                        ['post_id' => $post_id]
                    );
                } catch (Exception $e) {
                    // Continue on error
                }
            }
            
            // Add random view count
            $view_count = rand(20, 200);
            $database->query(
                "UPDATE bg_social_posts SET view_count = :view_count WHERE post_id = :post_id",
                ['view_count' => $view_count, 'post_id' => $post_id]
            );
            
        } else {
            throw new Exception("Failed to create post");
        }
        
    } catch (Exception $e) {
        $error_count++;
        $errors[] = $e->getMessage();
        echo "<li class='text-danger'>❌ Error creating post: " . $e->getMessage() . "</li>";
    }
    
    // Add delay to spread out creation times
    usleep(100000); // 0.1 second delay
}

echo "</ul>";

// Summary
echo "<div class='alert alert-info mt-4'>";
echo "<h5>Seeding Complete!</h5>";
echo "<p>✅ Successfully created: <strong>$success_count posts</strong></p>";
if ($error_count > 0) {
    echo "<p>❌ Errors encountered: <strong>$error_count</strong></p>";
    if (!empty($errors)) {
        echo "<details><summary>View errors</summary><pre>" . htmlspecialchars(print_r($errors, true)) . "</pre></details>";
    }
}
echo "</div>";

// Get stats
$stats = $database->getrow("
    SELECT 
        COUNT(*) as total_posts,
        SUM(like_count) as total_likes,
        SUM(comment_count) as total_comments,
        SUM(view_count) as total_views
    FROM bg_social_posts 
    WHERE status = 'active' AND post_type = 'post'
");

echo "<div class='card mt-3'>";
echo "<div class='card-body'>";
echo "<h5>Current Social Module Stats</h5>";
echo "<ul>";
echo "<li>Total Posts: <strong>" . ($stats['total_posts'] ?? 0) . "</strong></li>";
echo "<li>Total Likes: <strong>" . ($stats['total_likes'] ?? 0) . "</strong></li>";
echo "<li>Total Comments: <strong>" . ($stats['total_comments'] ?? 0) . "</strong></li>";
echo "<li>Total Views: <strong>" . ($stats['total_views'] ?? 0) . "</strong></li>";
echo "</ul>";
echo "</div>";
echo "</div>";
?>

            <div class="mt-4">
                <a href="/social/" class="btn btn-primary btn-lg">View Social Feed</a>
                <a href="/social/seed-birthday-content.php" class="btn btn-secondary">Run Again</a>
            </div>
        </div>
    </div>
</div>

<?php
include $dir['core_components'] . '/bg_footer.inc';

$app->outputpage();