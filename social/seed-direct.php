<?php
// Direct database seeding - bypass the Social class
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "<h2>Direct Database Seeding</h2><pre>";

// Use a default user ID of 1 for now
$user_id = 1;

// Birthday posts from the original mockups
$posts = [
    "🎂 Just scored my FREE birthday burger at Red Robin! No purchase necessary!",
    "Pro tip: Sign up for Starbucks rewards 30 days before your birthday ☕",
    "Birthday month haul: FREE items from Sephora, Ulta, Dunkin, Jersey Mike's 🎉",
    "Cheesecake Factory = FREE slice of cheesecake for your birthday! 🍰",
    "Denny's gives you a FREE Grand Slam on your birthday! 🥞"
];

foreach ($posts as $i => $content) {
    // Direct SQL insert - no media_type column
    $sql = "INSERT INTO bg_social_posts 
            (user_id, content, post_type, visibility, status, created_at) 
            VALUES 
            (?, ?, 'post', 'public', 'active', DATE_SUB(NOW(), INTERVAL ? HOUR))";
    
    try {
        $stmt = $database->connection->prepare($sql);
        $stmt->execute([$user_id, $content, $i * 3]);
        echo "✅ Inserted: " . substr($content, 0, 50) . "...\n";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\nDone! Visit /social/ to see the posts.</pre>";
?>