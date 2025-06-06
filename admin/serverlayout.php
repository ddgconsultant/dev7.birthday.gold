<?PHP
$securityoverride_referrer='https://bd.gold/';

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');



// Read the contents of the file
$fileContents = file_get_contents('serverlayout.txt');

// Escape special HTML characters to prevent XSS attacks
$escapedContents = htmlspecialchars($fileContents);

echo '<div class="main-content fluid-container ">
<div class="d-flex justify-content-center">
<div class="fluid-container">';


$search=['@startuml', '@enduml'];
$umlCode=trim(str_replace($search, '', $fileContents));


function encodep($text) {
$data = utf8_encode($text);
$compressed = gzdeflate($data, 9);
return encode64($compressed);
}

function encode6bit($b) {
if ($b < 10) {
return chr(48 + $b);
}
$b -= 10;
if ($b < 26) {
return chr(65 + $b);
}
$b -= 26;
if ($b < 26) {
return chr(97 + $b);
}
$b -= 26;
if ($b == 0) {
return '-';
}
if ($b == 1) {
return '_';
}
return '?';
}

function append3bytes($b1, $b2, $b3) {
$c1 = $b1 >> 2;
$c2 = (($b1 & 0x3) << 4) | ($b2 >> 4);
$c3 = (($b2 & 0xF) << 2) | ($b3 >> 6);
$c4 = $b3 & 0x3F;
$r = "";
$r .= encode6bit($c1 & 0x3F);
$r .= encode6bit($c2 & 0x3F);
$r .= encode6bit($c3 & 0x3F);
$r .= encode6bit($c4 & 0x3F);
return $r;
}

function encode64($c) {
$str = "";
$len = strlen($c);
for ($i = 0; $i < $len; $i+=3) {
if ($i+2==$len) {
$str .= append3bytes(ord(substr($c, $i, 1)), ord(substr($c, $i+1, 1)), 0);
} else if ($i+1==$len) {
$str .= append3bytes(ord(substr($c, $i, 1)), 0, 0);
} else {
$str .= append3bytes(ord(substr($c, $i, 1)), ord(substr($c, $i+1, 1)),
ord(substr($c, $i+2, 1)));
}
}
return $str;
}


$encode = encodep($umlCode);
// Print or use the Base64 encoded compressed string
$encodedContents=$encode;
$link='http://www.plantuml.com/plantuml/png/';
$url=$link.$encodedContents;


echo '<a href="' . $url . '" target="_blank">';
echo '<img src="' . $url . '" alt="Diagram" class="img-fluid" />';
echo '</a>';


// Display the rest of the content within a <pre> tag
echo '<div class="container"><pre class="mt-3 border border-1 rounded p-3" style=" white-space: pre-wrap;">' . $escapedContents . '</pre>
</div>';


echo '</div></div></div>';


$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
