<?php

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Assuming the session holds the list of post IDs in $postlist
$postlist = isset($_SESSION['postlist']) ? $_SESSION['postlist'] : [];
$currentPostId = isset($_SESSION['currentpost']['id']) ? $_SESSION['currentpost']['id'] : null;

// Ensure we have a valid postlist and current post ID
if (empty($postlist) || $currentPostId === null) {
    // Redirect to a default page or an error page if something is wrong
    header('Location: /social/');
    exit();
}

// Find the current post's index in the post list
$currentPostIndex = array_search($currentPostId, $postlist);

// Ensure the current post exists in the list
if ($currentPostIndex === false) {
    // Redirect to an error or default page if current post ID is not found
    header('Location: /social/');
    exit();
}

// Get the navigation action (next or previous)
$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : 'next';

// Determine the next or previous post ID based on the action
if ($action === 'next' && $currentPostIndex < count($postlist) - 1) {
    // Move to the next post
    $nextPostId = $postlist[$currentPostIndex + 1];
    header('Location: /social/?i=' . $nextPostId);
    exit();
} elseif ($action === 'prev' && $currentPostIndex > 0) {
    // Move to the previous post
    $prevPostId = $postlist[$currentPostIndex - 1];
    header('Location: /social/?i=' . $prevPostId);
    exit();
} else {
    // No more posts in the desired direction, stay on the same post
    header('Location: /social/?i=' . $currentPostId);
    exit();
}
