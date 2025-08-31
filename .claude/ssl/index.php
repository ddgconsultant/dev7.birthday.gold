<?php
// Simple directory listing for SSL certificate downloads
$files = glob('*.{crt,key}', GLOB_BRACE);
echo "<h3>SSL Certificate Files</h3>\n";
echo "<ul>\n";
foreach ($files as $file) {
    echo "<li><a href=\"$file\">$file</a></li>\n";
}
echo "</ul>\n";
?>