<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);


error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

echo $test;
echo "hello world";
echo '<hr>';
echo 'display_errors: ' . ini_get('display_errors') . "\n";
echo 'error_reporting: ' . error_reporting() . "\n";
