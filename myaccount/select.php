<?php
/**
 * Redirect from old select URL to new enrollment-picker
 * This maintains backward compatibility for any existing links
 */

header('Location: /myaccount/enrollment-picker', true, 301);
exit;
?>