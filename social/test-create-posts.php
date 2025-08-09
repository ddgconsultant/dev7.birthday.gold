<?php
// Test script to create sample social posts
$addClasses[] = 'Social';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is logged in
if (empty($current_user_data['user_id'])) {
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $current_user_data['user_id'];

// Sample posts data
$sample_posts = [
    [
        'content' => "Just got a free birthday dessert at The Cheesecake Factory! 🎂 Their birthday program is amazing - you get a complimentary slice of any cheesecake. Definitely worth signing up! #BirthdayRewards #Cheesecake",
        'hashtags' => ['BirthdayRewards', 'Cheesecake'],
        'visibility' => 'public'
    ],
    [
        'content' => "Pro tip: Sign up for birthday programs at least a month before your birthday. Some companies send rewards early! Just got my Starbucks birthday reward and my birthday isn't for 2 weeks. ☕ #BirthdayGold #FreeCoffee",
        'hashtags' => ['BirthdayGold', 'FreeCoffee'],
        'visibility' => 'public'
    ],
    [
        'content' => "Birthday month haul so far:\n✅ Sephora - Free gift\n✅ Ulta - $10 off coupon\n✅ Dunkin - Free drink\n✅ Jersey Mike's - Free sub\n\nStill have 15 more to redeem! #BirthdayMonth #FreeStuff",
        'hashtags' => ['BirthdayMonth', 'FreeStuff'],
        'visibility' => 'public'
    ],
    [
        'content' => "Anyone else save their birthday rewards throughout the year? I'm using my Panera free pastry today even though my birthday was 3 months ago 😄",
        'hashtags' => [],
        'visibility' => 'public'
    ],
    [
        'content' => "New discovery: Red Robin gives you a FREE burger for your birthday! No purchase necessary. Just added it to my Birthday Gold enrollments. 🍔 #BirthdayFreebies",
        'hashtags' => ['BirthdayFreebies'],
        'visibility' => 'public'
    ]
];

echo "<h2>Creating Sample Posts</h2>";
echo "<pre>";
echo "Creating sample posts for user $user_id...\n\n";

foreach ($sample_posts as $index => $post_data) {
    // Create the post using Social class
    try {
        $post_id = $social->createPost(
            $user_id,
            $post_data['content'],
            null, // media_type
            null, // media_data
            $post_data['visibility'],
            null, // parent_post_id
            !empty($post_data['hashtags']) ? $post_data['hashtags'] : null
        );
        
        echo "✅ Created post #$post_id: " . substr($post_data['content'], 0, 50) . "...\n";
        
        // Add some interaction activity to make it look realistic
        // Add a timestamp offset to space them out
        $sql = "UPDATE bg_social_posts SET created_at = DATE_SUB(NOW(), INTERVAL :offset HOUR) WHERE post_id = :post_id";
        $database->query($sql, ['offset' => ($index * 3), 'post_id' => $post_id]);
        
    } catch (Exception $e) {
        echo "❌ Error creating post: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Sample posts created successfully!\n";
echo "</pre>";
echo '<a href="/social/" class="btn btn-primary">View Social Feed</a>';
?>