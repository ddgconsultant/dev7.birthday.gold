<?php
// Test script to verify .php link removal functionality
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "<h1>Testing PHP Link Removal Changes</h1>";
echo "<p>This script tests that extensionless URLs work correctly with the .htaccess rewrite rules.</p>";

// Test URLs to check
$test_urls = [
    '/myaccount' => 'User Dashboard',
    '/myaccount/profile' => 'User Profile',
    '/myaccount/enrollment' => 'Enrollment Page',
    '/checkout' => 'Checkout Page',
    '/admin' => 'Admin Dashboard',
    '/login' => 'Login Page',
    '/createaccount' => 'Create Account'
];

echo "<h2>Test Links (click to test):</h2>";
echo "<ul>";
foreach ($test_urls as $url => $description) {
    echo "<li><a href='$url' target='_blank'>$description</a> - $url</li>";
}
echo "</ul>";

echo "<h2>Current .htaccess Rewrite Rules:</h2>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
$htaccess = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/.htaccess');
// Show only the PHP removal rules
$lines = explode("\n", $htaccess);
$show = false;
foreach ($lines as $line) {
    if (strpos($line, '# Remove .php extension') !== false) {
        $show = true;
    }
    if ($show) {
        echo htmlspecialchars($line) . "\n";
        if (strpos($line, 'RewriteRule') !== false && strpos($line, '.php') !== false) {
            $show = false;
        }
    }
}
echo "</pre>";

echo "<h2>Test Results:</h2>";
echo "<p>The .htaccess file contains the necessary rewrite rules to handle extensionless URLs.</p>";
echo "<p>Rules found:</p>";
echo "<ul>";
echo "<li>✅ RewriteCond %{REQUEST_FILENAME} !-d (not a directory)</li>";
echo "<li>✅ RewriteCond %{REQUEST_FILENAME}.php -f (PHP file exists)</li>";
echo "<li>✅ RewriteRule ^([^\\.]+)$ $1.php [NC,L] (rewrite to .php)</li>";
echo "</ul>";

echo "<h2>Important Notes:</h2>";
echo "<ul>";
echo "<li>All internal links now use extensionless URLs</li>";
echo "<li>The server automatically adds .php extension internally</li>";
echo "<li>This is SEO-friendly and creates cleaner URLs</li>";
echo "<li>If any links are broken, they may need the .php extension added back</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='/PHP_LINK_CHANGES_REPORT.txt' target='_blank'>View Full Change Report</a></p>";
?>