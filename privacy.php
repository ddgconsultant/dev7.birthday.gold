<?PHP
$newLocation = '/legalhub/privacy';
if (!empty($_SERVER['QUERY_STRING'])) {
    $newLocation .= '?' . $_SERVER['QUERY_STRING'];
}
header('location: ' . $newLocation);
exit;
