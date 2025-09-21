<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$system->getipaddress('reset');
header('location: /');