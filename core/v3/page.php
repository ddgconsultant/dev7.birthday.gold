<?PHP
$nosessiontracking=true;
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include('x_pagestart.inc');
include('x_header.inc');
include('x_userprofileheader.inc');
include('x_userleftpanel.inc');
include('x_usercontent.inc');

$footer_setting['type']='min';
include('x_footer.inc');


// Search and replace content before sending it to the client
$content = ob_get_clean();
$search=[";\r\n", "  "];
$replace=["; ", " "];
$content = str_replace('</head>',str_replace($search, $replace, $additionalstyles).'</head>', $content);
echo $content;

// End output buffering
ob_end_flush();
