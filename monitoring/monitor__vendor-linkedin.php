<?php

// --- CONFIG --- //
$targetUrl = 'https://www.linkedin.com/company/birthdaygold';
$expectedKeyword = 'Birthday Gold';
$timeout = 15;
$verifySSL = false; // Set to true if you have proper CA bundles

$userAgents = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:115.0) Gecko/20100101 Firefox/115.0',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 13.4; rv:114.0) Gecko/20100101 Firefox/114.0',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115 Safari/537.36',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 16_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Mobile Safari/604.1',
    'Mozilla/5.0 (Linux; Android 12; SM-G991U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0 Mobile Safari/537.36'
];
$selectedUA = $userAgents[array_rand($userAgents)];

// --- INIT CURL --- //
$ch = curl_init($targetUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT => $selectedUA,
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_SSL_VERIFYPEER => $verifySSL,
    CURLOPT_HEADER => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($response, $headerSize);
$curlError = curl_error($ch);
curl_close($ch);

// --- LOGIC --- //
if ($response === false || !empty($curlError)) {
    http_response_code(500);
    echo "FAIL: CURL error - $curlError";
    exit;
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo "FAIL: HTTP status code $httpCode";
    exit;
}

if (stripos($body, $expectedKeyword) === false) {
    http_response_code(502);
    echo "FAIL: Expected keyword not found";
    exit;
}

// --- SUCCESS --- //
echo "OK: $expectedKeyword page loaded via UA: " . $selectedUA;
