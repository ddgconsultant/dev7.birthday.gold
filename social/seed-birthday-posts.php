<?php
// Script to seed database with birthday-themed posts like the original mockups
$addClasses[] = 'Social';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is logged in
if (empty($current_user_data['user_id'])) {
    die("You must be logged in to run this script\n");
}

$user_id = $current_user_data['user_id'];

// Birthday-themed posts that match the original social platform content
$birthday_posts = [
    [
        'content' => "🎂 Just scored my FREE birthday burger at Red Robin! No purchase necessary - they literally just give you a whole burger for your birthday. This is why I love Birthday Gold - never miss these amazing freebies! #BirthdayRewards #FreeFood #RedRobin",
        'hashtags' => ['BirthdayRewards', 'FreeFood', 'RedRobin'],
        'visibility' => 'public'
    ],
    [
        'content' => "Pro tip: Sign up for Starbucks rewards at least 30 days before your birthday! ☕ They send the free drink reward early and it's good for ANY size, ANY drink. I just got a $8 venti with all the extras! #StarbucksBirthday #BirthdayGold #CoffeeLover",
        'hashtags' => ['StarbucksBirthday', 'BirthdayGold', 'CoffeeLover'],
        'visibility' => 'public'
    ],
    [
        'content' => "My birthday month haul so far:\n✅ Sephora - Free birthday gift set\n✅ Ulta - $10 off coupon + free gift\n✅ Dunkin - Free drink any size\n✅ Jersey Mike's - Free regular sub\n✅ Panera - Free pastry\n✅ Baskin Robbins - Free scoop\n\nStill have 15 more to redeem! Thank you Birthday Gold for keeping me organized! 🎉",
        'hashtags' => ['BirthdayMonth', 'FreeStuff', 'BirthdayHaul'],
        'visibility' => 'public'
    ],
    [
        'content' => "Anyone else strategically save their birthday rewards throughout the year? 😄 I'm using my Panera free pastry today even though my birthday was 3 months ago. Most rewards are good for 30+ days! #BirthdayRewards #SmartShopping",
        'hashtags' => ['BirthdayRewards', 'SmartShopping'],
        'visibility' => 'public'
    ],
    [
        'content' => "HUGE birthday win at The Cheesecake Factory! 🍰 Free slice of ANY cheesecake - I got the $9.95 Godiva chocolate one! Plus they sang happy birthday. Definitely worth the signup! #CheesecakeFactory #BirthdayDessert #WorthIt",
        'hashtags' => ['CheesecakeFactory', 'BirthdayDessert', 'WorthIt'],
        'visibility' => 'public'
    ],
    [
        'content' => "Birthday rewards I didn't know existed until Birthday Gold:\n\n🍕 Blaze Pizza - Free pizza\n🥤 Tropical Smoothie - Free smoothie\n🍔 Habit Burger - Free charburger\n🌮 Rubio's - Free entrée\n🍨 Cold Stone - Buy one get one\n\nSeriously, how did I not know about these?! #HiddenGems #BirthdayFreebies",
        'hashtags' => ['HiddenGems', 'BirthdayFreebies'],
        'visibility' => 'public'
    ],
    [
        'content' => "Birthday month budgeting hack: I saved over $200 this month just from birthday freebies! 💰 That's groceries, coffee, desserts, and meals all covered by birthday programs. This is the way! #BirthdayBudget #FreeFood #MoneyHack",
        'hashtags' => ['BirthdayBudget', 'FreeFood', 'MoneyHack'],
        'visibility' => 'public'
    ],
    [
        'content' => "The ultimate birthday week schedule:\n\nMonday: Free Starbucks ☕\nTuesday: Free Chipotle 🌯\nWednesday: Free Nothing Bundt Cake 🧁\nThursday: Free Jersey Mike's 🥖\nFriday: Free dinner at Benihana 🍱\nWeekend: Shopping with all my birthday discounts! 🛍️",
        'hashtags' => ['BirthdayWeek', 'Planned', 'LivingMyBestLife'],
        'visibility' => 'public'
    ],
    [
        'content' => "PSA: Denny's gives you a FREE Grand Slam breakfast on your birthday! 🥞 No purchase necessary, just show your ID. I go every year - it's tradition now! #DennysBirthday #FreeBreakfast #Tradition",
        'hashtags' => ['DennysBirthday', 'FreeBreakfast', 'Tradition'],
        'visibility' => 'public'
    ],
    [
        'content' => "Just discovered that some birthday rewards stack! 📚 Got my free drink at Dutch Bros AND used my birthday discount at the same visit. Mind = blown 🤯 #BirthdayHacks #StackingRewards",
        'hashtags' => ['BirthdayHacks', 'StackingRewards'],
        'visibility' => 'public'
    ],
    [
        'content' => "Birthday Gold just reminded me about 5 rewards expiring this week! 😱 Almost forgot about my free IHOP stack of pancakes and Firehouse Subs free medium sub. This app is a lifesaver! #BirthdayGold #NeverForget #Reminders",
        'hashtags' => ['BirthdayGold', 'NeverForget', 'Reminders'],
        'visibility' => 'public'
    ],
    [
        'content' => "Kids eat free on their birthday at these places:\n🍕 Chuck E. Cheese\n🍝 Olive Garden (some locations)\n🥘 Texas Roadhouse\n🍔 Red Robin\n\nParents, you're welcome! 😊 #KidsBirthdays #ParentingWin #FreeMeals",
        'hashtags' => ['KidsBirthdays', 'ParentingWin', 'FreeMeals'],
        'visibility' => 'public'
    ],
    [
        'content' => "The best part about having a birthday in January? All the New Year healthy eating resolutions go out the window when you have 30+ free desserts to claim! 🎂😂 #JanuaryBirthday #BirthdayMonth #NoRegrets",
        'hashtags' => ['JanuaryBirthday', 'BirthdayMonth', 'NoRegrets'],
        'visibility' => 'public'
    ],
    [
        'content' => "Turning 30 today and Birthday Gold has made it amazing! 🎉 Started the day with free Starbucks, lunch at Noodles & Company (free bowl!), and ending with free Coldstone ice cream. Age is just a number when everything is free! #Dirty30 #BirthdayVibes",
        'hashtags' => ['Dirty30', 'BirthdayVibes'],
        'visibility' => 'public'
    ],
    [
        'content' => "Hotel birthday perks no one talks about:\n🏨 Marriott - Room upgrade + points\n🏨 Hilton - Free night (some tiers)\n🏨 Hyatt - Bonus points + amenity\n\nPerfect for birthday trips! ✈️ #TravelHacks #BirthdayTravel #HotelPerks",
        'hashtags' => ['TravelHacks', 'BirthdayTravel', 'HotelPerks'],
        'visibility' => 'public'
    ]
];

echo "<h2>Seeding Birthday-Themed Posts</h2>";
echo "<pre>";
echo "Creating " . count($birthday_posts) . " birthday-themed posts...\n\n";

$created_count = 0;
foreach ($birthday_posts as $index => $post_data) {
    try {
        // Space posts out over time
        $hours_ago = ($index * 4) + rand(1, 3); // Space them 4-7 hours apart
        
        // Create the post
        $post_id = $social->createPost(
            $user_id,
            $post_data['content'],
            null, // media_type
            null, // media_data
            $post_data['visibility'],
            null, // parent_post_id
            !empty($post_data['hashtags']) ? $post_data['hashtags'] : null
        );
        
        // Update the timestamp to spread them out
        $sql = "UPDATE bg_social_posts SET created_at = DATE_SUB(NOW(), INTERVAL :hours HOUR) WHERE post_id = :post_id";
        $database->query($sql, ['hours' => $hours_ago, 'post_id' => $post_id]);
        
        echo "✅ Created post #$post_id: " . substr($post_data['content'], 0, 60) . "...\n";
        
        // Add some random engagement to make it look realistic
        // Random likes (would be from different users in production)
        $like_count = rand(5, 150);
        
        // Add some sample comments
        $sample_comments = [
            "This is so helpful! Thanks for sharing!",
            "I didn't know about this one!",
            "Just tried this and it worked!",
            "Birthday Gold is the best!",
            "Adding this to my list!",
            "Can confirm this works!",
            "Thanks for the tip!",
            "This saved me so much money!",
            "Following for more birthday tips!",
            "Love this community!"
        ];
        
        // Add 0-3 comments per post
        $num_comments = rand(0, 3);
        for ($c = 0; $c < $num_comments; $c++) {
            $comment_text = $sample_comments[array_rand($sample_comments)];
            $social->addComment($post_id, $user_id, $comment_text);
        }
        
        $created_count++;
        
    } catch (Exception $e) {
        echo "❌ Error creating post: " . $e->getMessage() . "\n";
    }
}

echo "\n";
echo "=====================================\n";
echo "✅ Successfully created $created_count birthday-themed posts!\n";
echo "=====================================\n\n";

// Add some variety with different users following each other (simulated)
echo "Setting up some social interactions...\n";

// Get the posts we just created
$recent_posts = $social->getFeed($user_id, 50, 0, 'all');

// Add some likes to random posts (in production these would be from different users)
foreach ($recent_posts as $post) {
    $should_like = rand(0, 100) < 30; // 30% chance to like
    if ($should_like && $post['user_id'] != $user_id) {
        $social->likePost($post['post_id'], $user_id);
    }
    
    $should_bookmark = rand(0, 100) < 20; // 20% chance to bookmark
    if ($should_bookmark) {
        $social->bookmarkPost($post['post_id'], $user_id);
    }
}

echo "✅ Added social interactions\n\n";

echo "</pre>";
echo '<div style="margin: 20px;">';
echo '<a href="/social/" class="btn btn-primary btn-lg">View Social Feed</a> ';
echo '<a href="/social/activity.php" class="btn btn-secondary btn-lg">View Your Activity</a>';
echo '</div>';
?>