<?PHP
$redirect_url = '/admin/business-editor';

// Append query string if present
if (!empty($_SERVER['QUERY_STRING'])) {
    $redirect_url .= '?' . $_SERVER['QUERY_STRING'];
}

// Append fragment if present
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '#') !== false) {
    $fragment = explode('#', $_SERVER['REQUEST_URI'])[1];
    $redirect_url .= '#' . $fragment;
}

header('Location: ' . $redirect_url);
exit;
?>
