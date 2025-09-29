<?php
// AJAX endpoint to increment banner dismissal counter
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize dismissal counter if not set
if (!isset($_SESSION['bdgold_banner_dismissals'])) {
    $_SESSION['bdgold_banner_dismissals'] = 0;
}

// Increment the counter
$_SESSION['bdgold_banner_dismissals']++;

// Record the current page view as the last dismissal page
$_SESSION['bdgold_last_dismissal_page'] = $_SESSION['bdgold_page_views'] ?? 1;

// Return success
header('Content-Type: application/json');
echo json_encode(['success' => true, 'dismissals' => $_SESSION['bdgold_banner_dismissals']]);
?>