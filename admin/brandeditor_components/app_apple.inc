<?php
$iframeUrl = $apple_targetUrl;
$iframeAllowed = true; // Default to iframe allowed

// Try using cURL to check the headers of the URL
$ch = curl_init($iframeUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true); // Only fetch headers
curl_setopt($ch, CURLOPT_HEADER, true); // Return the headers
curl_setopt($ch, CURLOPT_TIMEOUT, 5);   // Timeout in seconds
$headers = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check for security headers
if ($httpCode >= 400) {
    $iframeAllowed = false; // The site is unreachable or responded with an error
} else {
    // Check if the X-Frame-Options header is set to DENY or SAMEORIGIN
    if (stripos($headers, 'X-Frame-Options: DENY') !== false || stripos($headers, 'X-Frame-Options: SAMEORIGIN') !== false) {
        $iframeAllowed = false;
    }

    // Check if the Content-Security-Policy header contains 'frame-ancestors none' or 'frame-ancestors self'
    if (stripos($headers, 'Content-Security-Policy') !== false && preg_match('/frame-ancestors (\'none\'|self|none)/i', $headers)) {
        $iframeAllowed = false;
    }
}

if ($iframeAllowed) {
    // If the iframe is allowed, show it
    echo '    <iframe sandbox="allow-scripts allow-same-origin" src="iframeproxy.php?url=' . urlencode($iframeUrl) . '" ' . $iframestyletagnoscale . '></iframe>
';
} else {
    // Fallback: Fetch and display the contents of the site or show an error message
    $siteContent = @file_get_contents($iframeUrl);

    if ($siteContent) {
        // Display the content fetched from the site
        echo '<section><div>' . $siteContent . '</div></section>';
    } else {
        // Error fallback if content can't be fetched
        echo '<div class="alert alert-warning">The site <strong>' . htmlspecialchars($iframeUrl) . '</strong> does not allow embedding. Please visit directly: <a href="' . htmlspecialchars($iframeUrl) . '" target="_blank">' . htmlspecialchars($iframeUrl) . '</a></div>';
    }
}
