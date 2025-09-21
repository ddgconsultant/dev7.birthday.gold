<?php
// Quick seed script - simplified version
$addClasses[] = 'Social';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is logged in
if (empty($current_user_data['user_id'])) {
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $current_user_data['user_id'];

// Simple birthday posts
$posts = [
    "🎂 Just scored my FREE birthday burger at Red Robin! No purchase necessary!",
    "Pro tip: Sign up for Starbucks rewards 30 days before your birthday for a free drink! ☕",
    "Birthday month haul: Sephora, Ulta, Dunkin, Jersey Mike's - all FREE! 🎉",
    "The Cheesecake Factory gives you a FREE slice for your birthday! 🍰",
    "Denny's = FREE Grand Slam on your birthday! No purchase needed! 🥞"
];

// Use direct SQL to avoid any column issues
foreach ($posts as $index => $content) {
    $hours_ago = $index * 3;
    
    $sql = "INSERT INTO bg_social_posts 
            (user_id, content, post_type, visibility, status, created_at) 
            VALUES 
            (:user_id, :content, 'post', 'public', 'active', DATE_SUB(NOW(), INTERVAL :hours HOUR))";
    
    try {
        $database->query($sql, [
            'user_id' => $user_id,
            'content' => $content,
            'hours' => $hours_ago
        ]);
        echo "✅ Created post: " . substr($content, 0, 50) . "...<br>";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}

echo '<br><a href="/social/" class="btn btn-primary">View Social Feed</a>';
?>