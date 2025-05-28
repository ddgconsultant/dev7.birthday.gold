<?php



if (empty($pagemode)) $pagemode = 'localized';
switch ($pagemode) {
    case 'core':
   #     echo 'ugh here';
           $addClasses[] = 'Api';
        $dir['base'] = 'W:/BIRTHDAY_SERVER/dev6.birthday.gold/';
        include($dir['base'] . '/core/site-controller.php');
        break;
    case 'localized':
   #     echo '.im here';
        $dir['base'] = 'W:/BIRTHDAY_SERVER/dev6.birthday.gold';
        include($dir['base'] . '/core/classes/class.api.php');
        $api = new Api();
        break;
}
