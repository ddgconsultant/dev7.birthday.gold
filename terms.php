<?PHP
$newLocation = '/legalhub/terms';
if (!empty($_SERVER['QUERY_STRING'])) {
    $newLocation .= '?' . $_SERVER['QUERY_STRING'];
}
header('location: ' . $newLocation);
exit;
