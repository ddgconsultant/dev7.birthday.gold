<?PHP

$addClasses[] = 'TimeClock';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

if (!empty($current_user_data['user_id'])){
    $timeclock->clock_out($current_user_data['user_id']);
}
$referrer = $_SERVER['HTTP_REFERER'] ?? '/myaccount/';
header('Location: ' . $referrer);
