<?php
$addClasses[] = 'mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$userid=20;
$userid=2372;


$results=$mail->getMessageList($userid);
breakpoint($results);