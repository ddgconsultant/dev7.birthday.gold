<?PHP
// Redirect to new marketing directory location
$redirect_url = '/staff/marketing/newsletter-edit.php';
if (!empty($_SERVER['QUERY_STRING'])) {
    $redirect_url .= '?' . $_SERVER['QUERY_STRING'];
}
header('Location: ' . $redirect_url);
exit;
?>