<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Admin only access
if (!$account->isadmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$ip = $_GET['ip'] ?? '';

if (empty($ip)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'IP address required']);
    exit;
}

// Get last 10 lockout history records for this IP
$history_sql = "SELECT
    h.id,
    h.ip,
    h.type,
    h.session_id,
    h.start_dt,
    h.expire_dt,
    h.create_dt,
    h.status,
    h.lockout_minutes,
    CASE
        WHEN h.lockout_minutes >= 99999 THEN 18
        WHEN h.lockout_minutes >= 65536 THEN 17
        WHEN h.lockout_minutes >= 32768 THEN 16
        WHEN h.lockout_minutes >= 16384 THEN 15
        WHEN h.lockout_minutes >= 8192 THEN 14
        WHEN h.lockout_minutes >= 4096 THEN 13
        WHEN h.lockout_minutes >= 2048 THEN 12
        WHEN h.lockout_minutes >= 1024 THEN 11
        WHEN h.lockout_minutes >= 512 THEN 10
        WHEN h.lockout_minutes >= 256 THEN 9
        WHEN h.lockout_minutes >= 128 THEN 8
        WHEN h.lockout_minutes >= 64 THEN 7
        WHEN h.lockout_minutes >= 32 THEN 6
        WHEN h.lockout_minutes >= 16 THEN 5
        WHEN h.lockout_minutes >= 8 THEN 4
        WHEN h.lockout_minutes >= 4 THEN 3
        WHEN h.lockout_minutes >= 2 THEN 2
        ELSE 1
    END as level
FROM bg_lockout_history h
WHERE h.ip = :ip
ORDER BY h.create_dt DESC
LIMIT 10";

$history = $database->query($history_sql, ['ip' => $ip])->fetchAll(PDO::FETCH_ASSOC);

// Get current lockout status
$current_sql = "SELECT
    l.id,
    l.ip,
    l.type,
    l.lockout_level,
    l.total_violations,
    l.first_violation_dt,
    l.last_violation_dt,
    l.expire_dt,
    l.status,
    TIMESTAMPDIFF(MINUTE, NOW(), l.expire_dt) as minutes_remaining
FROM bg_lockout l
WHERE l.ip = :ip
LIMIT 1";

$current = $database->query($current_sql, ['ip' => $ip])->fetch(PDO::FETCH_ASSOC);

// Perform AI-powered web search for IP information
$ai_analysis = '';
try {
    // Use OpenAI API for web search and analysis
    $openai_api_key = getenv('OPENAI_API_KEY') ?: '';

    if (!empty($openai_api_key)) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openai_api_key
        ]);

        $prompt = "Analyze this IP address: {$ip}\n\n";
        $prompt .= "Please provide:\n";
        $prompt .= "1. Geolocation information (country, region, city if available)\n";
        $prompt .= "2. ISP/Organization information\n";
        $prompt .= "3. Whether this IP is known for malicious activity, spam, or is listed on any blocklists\n";
        $prompt .= "4. Any reputation score or threat level information\n";
        $prompt .= "5. Type of IP (residential, datacenter, proxy, VPN, Tor exit node, etc.)\n";
        $prompt .= "6. Any other relevant security information\n\n";
        $prompt .= "Format the response in clear sections with headers. Be concise but informative.";

        $data = [
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a cybersecurity analyst assistant. Provide detailed IP address analysis based on publicly available information and threat intelligence databases.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 800
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $response) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                $ai_analysis = $result['choices'][0]['message']['content'];
            } else {
                $ai_analysis = "Unable to retrieve AI analysis. API response format unexpected.";
            }
        } else {
            $ai_analysis = "Unable to retrieve AI analysis. API returned status code: {$http_code}";
        }
    } else {
        $ai_analysis = "AI analysis unavailable. OpenAI API key not configured.";
    }
} catch (Exception $e) {
    $ai_analysis = "Error performing AI analysis: " . $e->getMessage();
}

// Try to get basic IP info from ip-api.com as fallback
$basic_info = [];
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,proxy,hosting");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['status']) && $data['status'] === 'success') {
            $basic_info = $data;
        }
    }
} catch (Exception $e) {
    // Ignore errors with basic info lookup
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'ip' => $ip,
    'current_lockout' => $current ?: null,
    'history' => $history,
    'ai_analysis' => $ai_analysis,
    'basic_info' => $basic_info
]);
