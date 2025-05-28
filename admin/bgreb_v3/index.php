<?PHP

// Include the site-controller.php file
$dir['base']=$BASEDIR=__DIR__."/../.." ?? $_SERVER['DOCUMENT_ROOT'];
require_once ($BASEDIR.'/core/site-controller.php');

echo date('r');
