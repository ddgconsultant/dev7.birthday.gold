<?php
/**
 * Redirect to legal policy editor
 * This ensures consistent access path for legal policy editor
 * 
 * The actual implementation is in /staff/legal-policy-editor.php
 * Created: 2025-08-25
 */

// Preserve query string if present
$query_string = $_SERVER['QUERY_STRING'] ?? '';
$redirect_url = '/staff/legal-policy-editor.php';

if (!empty($query_string)) {
    $redirect_url .= '?' . $query_string;
}

// Perform 302 redirect (temporary - in case we need to change destination)
header("Location: $redirect_url", true, 302);
exit;