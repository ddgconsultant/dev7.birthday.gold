<?php
/**
 * Test Social Module Functionality
 */

$addClasses[] = 'Social';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set content type to JSON for API-like response
header('Content-Type: application/json');

$response = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
];

try {
    // Test 1: Check if Social class is loaded
    if (class_exists('Social')) {
        $response['tests']['class_loaded'] = [
            'status' => 'pass',
            'message' => 'Social class loaded successfully'
        ];
    } else {
        $response['tests']['class_loaded'] = [
            'status' => 'fail',
            'message' => 'Social class not found'
        ];
    }
    
    // Test 2: Check if social object is instantiated
    if (isset($social) && is_object($social)) {
        $response['tests']['object_created'] = [
            'status' => 'pass',
            'message' => 'Social object instantiated'
        ];
    } else {
        $response['tests']['object_created'] = [
            'status' => 'fail',
            'message' => 'Social object not created'
        ];
    }
    
    // Test 3: Check database tables
    $tables = ['bg_social_posts', 'bg_social_interactions', 'bg_social_follows', 'bg_social_notifications', 'bg_social_activity'];
    $tables_exist = [];
    
    foreach ($tables as $table) {
        $check = $database->query("SHOW TABLES LIKE '$table'");
        if ($check && $check->rowCount() > 0) {
            $tables_exist[$table] = true;
        } else {
            $tables_exist[$table] = false;
        }
    }
    
    $response['tests']['database_tables'] = [
        'status' => count(array_filter($tables_exist)) === count($tables) ? 'pass' : 'partial',
        'message' => 'Database tables check',
        'details' => $tables_exist
    ];
    
    // Test 4: Get post count
    $post_count = $database->getrow("SELECT COUNT(*) as count FROM bg_social_posts WHERE status = 'active' AND post_type = 'post'");
    $response['tests']['post_count'] = [
        'status' => 'info',
        'message' => 'Total active posts',
        'count' => $post_count['count'] ?? 0
    ];
    
    // Test 5: Try to get feed (if user is logged in)
    if (!empty($current_user_data['user_id'])) {
        try {
            $feed = $social->getFeed($current_user_data['user_id'], 5, 0, 'all');
            $response['tests']['feed_retrieval'] = [
                'status' => 'pass',
                'message' => 'Feed retrieved successfully',
                'posts_returned' => count($feed)
            ];
        } catch (Exception $e) {
            $response['tests']['feed_retrieval'] = [
                'status' => 'fail',
                'message' => 'Failed to retrieve feed',
                'error' => $e->getMessage()
            ];
        }
    } else {
        $response['tests']['feed_retrieval'] = [
            'status' => 'skip',
            'message' => 'User not logged in - skipping feed test'
        ];
    }
    
    // Test 6: Check trending posts
    try {
        $trending = $social->getTrendingPosts(24, 5);
        $response['tests']['trending_posts'] = [
            'status' => 'pass',
            'message' => 'Trending posts retrieved',
            'count' => count($trending)
        ];
    } catch (Exception $e) {
        $response['tests']['trending_posts'] = [
            'status' => 'fail',
            'message' => 'Failed to get trending posts',
            'error' => $e->getMessage()
        ];
    }
    
    // Summary
    $passed = 0;
    $failed = 0;
    $skipped = 0;
    
    foreach ($response['tests'] as $test) {
        if ($test['status'] === 'pass') $passed++;
        elseif ($test['status'] === 'fail') $failed++;
        elseif ($test['status'] === 'skip') $skipped++;
    }
    
    $response['summary'] = [
        'total_tests' => count($response['tests']),
        'passed' => $passed,
        'failed' => $failed,
        'skipped' => $skipped
    ];
    
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['error'] = $e->getMessage();
}

// Output JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
?>