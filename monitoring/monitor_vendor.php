<?php

// === CONFIG BLOCK === //
$verifySSL = false; // Set true in production w/ CA cert
$timeout = 15;

$userAgents = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:115.0) Gecko/20100101 Firefox/115.0',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 13.4; rv:114.0) Gecko/20100101 Firefox/114.0',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115 Safari/537.36',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 16_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Mobile Safari/604.1',
    'Mozilla/5.0 (Linux; Android 12; SM-G991U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0 Mobile Safari/537.36'
];
$selectedUA = $userAgents[array_rand($userAgents)];

$check = isset($_GET['check']) ? strtolower(trim($_GET['check'])) : '';
$expectedHttpCode = 200; // Default expected code
$finalStatus = '';

switch ($check) {
    case 'mysql':
        $targetUrl = 'https://dev.mysql.com/downloads/mysql/';
        $expectedKeyword = 'MySQL Community Server';
        break;

    case 'x':
    case 'twitter':
        $targetUrl = 'https://x.com/';
        $expectedKeyword = 'X Corp';
        break;

    case 'twitter-account':
        $targetUrl = 'https://x.com/birthday_gold';
        $expectedKeyword = '@birthday_gold';
        break;

    case 'fb':
    case 'facebook':
        $targetUrl = 'https://www.facebook.com/';
        $expectedKeyword = 'Meta';
        break;

    case 'facebook-account':
        $targetUrl = 'https://www.facebook.com/birthdaygold';
        $expectedKeyword = 'Birthday Gold';
        break;

    case 'instagram':
        $targetUrl = 'https://www.instagram.com/';
        $expectedKeyword = 'Birthday Gold';
        break;

    case 'instagram-account':
        $targetUrl = 'https://www.instagram.com/birthday_gold/';
        $expectedKeyword = 'birthday_gold';
        break;

    case 'namecheap':
        $targetUrl = 'https://www.namecheap.com/';
        $expectedKeyword = 'Just a moment';
        $expectedHttpCode = 403; // Accept 403 if keyword is present
        break;

        case 'claude-web':
            $targetUrl = 'https://claude.ai/login';
            $expectedKeyword = 'challenge-error-text';            
        $expectedHttpCode = 403; // Accept 403 if keyword is present
            break;
    
    
        
    case '':
        http_response_code(400);
        echo 'FAIL: Missing ?check parameter.';
        exit;

    default:
        $customScript = __DIR__ . '/monitor_vendor-' . basename($check) . '.php';
        if (file_exists($customScript)) {
            include $customScript;
            exit;
        }
        http_response_code(404);
        echo "FAIL: Unknown or unsupported check: $check";
        exit;
}

$ch = curl_init($targetUrl);
$ch = curl_init($targetUrl);
curl_setopt_array($ch, [
    CURLOPT_ENCODING => 'gzip, deflate',  // Only request supported encodings
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_SSL_VERIFYPEER => $verifySSL,
    CURLOPT_HEADER => true,
    CURLOPT_HTTPHEADER => [
        'User-Agent: ' . $selectedUA,
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Connection: keep-alive',
        'Referer: https://birthday.gold/',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$curlError = curl_error($ch);
curl_close($ch);

$body = ($response !== false) ? substr($response, $headerSize) : '';

// Try to decode content manually if encoding error
if (strpos(strtolower($curlError), 'unrecognized content encoding') !== false && empty($body)) {
    $body = $curlOutput;
    $curlError = '';
}
curl_close($ch);

$debug = isset($_GET['debug']) ? $_GET['debug'] : '0';

if ($response === false || (!empty($curlError) && stripos($curlError, 'unrecognized content encoding') === false)) {
    http_response_code(500);
    $finalStatus = "FAIL: [$check] CURL error - $curlError";
} elseif ($httpCode !== 200 && $httpCode !== $expectedHttpCode) {
    http_response_code($httpCode);
    $finalStatus = "FAIL: [$check] Unexpected HTTP status code $httpCode";
} elseif (stripos(trim($body), $expectedKeyword) === false) {
    http_response_code(502);
    $finalStatus = "FAIL: [$check] Expected keyword not found";
} else {
    $finalStatus = "OK: [$check] monitor passed using UA: $selectedUA";
}

if ($debug === '1' || $debug === '2') {
    header('Content-Type: text/plain');
    echo "== DEBUG MODE ==\n";
    echo "Target URL: $targetUrl\n";
    echo "User-Agent: $selectedUA\n";
    echo "HTTP Code: $httpCode\n";
    echo "Expected Keyword: $expectedKeyword\n";
    echo "CURL Error: " . ($curlError ?: 'None') . "\n\n";
    echo "$finalStatus\n\n";
    
    if ($debug === '1') {
        echo "Response Body (truncated to 2000 chars):\n";
        echo substr($body, 0, 2000);
    } else { // debug=2
        echo "Response Body (FULL):\n";
        echo "Length: " . strlen($body) . " bytes\n";
        echo "=====================================\n";
        echo $body;
        echo "\n=====================================\n";
        
        // Also show if keyword was found and where
        $keywordPos = stripos($body, $expectedKeyword);
        if ($keywordPos !== false) {
            echo "\nKeyword '$expectedKeyword' found at position: $keywordPos\n";
            echo "Context: " . substr($body, max(0, $keywordPos - 50), 150) . "\n";
        } else {
            echo "\nKeyword '$expectedKeyword' NOT FOUND in response body\n";
        }
    }
    exit;
}
echo $finalStatus;
