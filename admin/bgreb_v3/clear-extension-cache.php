<?php
require_once(__DIR__ . '/../../core/site-controller.php');

// Clear all extension version caches from session
$cleared = 0;
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'bgreb_extension_version_') === 0) {
        unset($_SESSION[$key]);
        $cleared++;
    }
}

// Set success message
$_SESSION['message'] = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Extension version cache cleared successfully (cleared ' . $cleared . ' cached versions)</div>';

// Redirect back to enrollment list
header('Location: /admin/bgreb_v3/enrollment-listv2');
exit;