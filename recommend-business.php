<?php
// Redirect to the new location in myaccount
header("Location: /myaccount/recommend-business" . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit;
?>