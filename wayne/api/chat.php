<?php
// Wayne Chatbot - AI API Integration Endpoint

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!$input || !isset($input['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No message provided']);
    exit;
}

$message = $input['message'];
$apiKey = $input['apiKey'] ?? '';
$provider = $input['provider'] ?? 'openai';
$model = $input['model'] ?? 'gpt-3.5-turbo';
$history = $input['history'] ?? [];

// Check if API key is provided
if (empty($apiKey)) {
    echo json_encode([
        'success' => false,
        'error' => 'API key not configured. Please add your API credentials.'
    ]);
    exit;
}

try {
    $response = '';
    
    switch ($provider) {
        case 'openai':
            $response = callOpenAI($message, $apiKey, $model, $history);
            break;
            
        case 'anthropic':
            $response = callAnthropic($message, $apiKey, $model, $history);
            break;
            
        case 'google':
            $response = callGoogle($message, $apiKey, $model, $history);
            break;
            
        default:
            throw new Exception('Unsupported AI provider: ' . $provider);
    }
    
    echo json_encode([
        'success' => true,
        'response' => $response
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'AI service error: ' . $e->getMessage()
    ]);
}

/**
 * Call OpenAI API (GPT models)
 */
function callOpenAI($message, $apiKey, $model, $history) {
    $url = 'https://api.openai.com/v1/chat/completions';
    
    // Build messages array
    $messages = [
        ['role' => 'system', 'content' => 'You are a helpful AI assistant.']
    ];
    
    // Add history
    foreach ($history as $h) {
        if (isset($h['sender']) && isset($h['text'])) {
            $role = $h['sender'] === 'user' ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $h['text']];
        }
    }
    
    // Add current message
    $messages[] = ['role' => 'user', 'content' => $message];
    
    $data = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 500
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $error = json_decode($response, true);
        throw new Exception($error['error']['message'] ?? 'OpenAI API error');
    }
    
    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'] ?? 'No response generated';
}

/**
 * Call Anthropic API (Claude models)
 */
function callAnthropic($message, $apiKey, $model, $history) {
    // Check if cURL is available, otherwise use file_get_contents
    if (!function_exists('curl_init')) {
        return callAnthropicWithFileGetContents($message, $apiKey, $model, $history);
    }
    
    $url = 'https://api.anthropic.com/v1/messages';
    
    // Build messages array - Anthropic requires alternating user/assistant
    $messages = [];
    
    // Add history (skip the initial bot greeting)
    foreach ($history as $h) {
        if (isset($h['sender']) && isset($h['text'])) {
            // Skip the initial greeting messages
            if ($h['text'] === "Hello! I'm your AI assistant. How can I help you today?" || 
                $h['text'] === "Hello! I'm powered by Claude Opus 4. I'm here to help with any questions or tasks you have. What would you like to discuss today?") {
                continue;
            }
            $role = $h['sender'] === 'user' ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $h['text']];
        }
    }
    
    // Add current message
    $messages[] = ['role' => 'user', 'content' => $message];
    
    // Ensure messages start with user (Anthropic requirement)
    if (empty($messages) || $messages[0]['role'] !== 'user') {
        $messages = array_values(array_filter($messages, function($msg) {
            return $msg['role'] === 'user' || $msg['role'] === 'assistant';
        }));
    }
    
    $data = [
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => 2000,
        'system' => 'You are a helpful AI assistant. Respond directly to the user\'s current question without referencing previous unrelated conversations. Be helpful, accurate, and conversational.'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // For development
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception('CURL error: ' . $curlError);
    }
    
    if ($httpCode !== 200) {
        $error = json_decode($response, true);
        $errorMsg = isset($error['error']['message']) ? $error['error']['message'] : 'Anthropic API error (HTTP ' . $httpCode . ')';
        throw new Exception($errorMsg . ' Response: ' . substr($response, 0, 500));
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['content'][0]['text'])) {
        throw new Exception('Invalid response format from Anthropic API');
    }
    
    return $result['content'][0]['text'];
}

/**
 * Fallback method using file_get_contents for Anthropic API
 */
function callAnthropicWithFileGetContents($message, $apiKey, $model, $history) {
    $url = 'https://api.anthropic.com/v1/messages';
    
    // Build messages array
    $messages = [];
    foreach ($history as $h) {
        if (isset($h['sender']) && isset($h['text'])) {
            if ($h['text'] === "Hello! I'm your AI assistant. How can I help you today?" || 
                $h['text'] === "Hello! I'm powered by Claude Opus 4. I'm here to help with any questions or tasks you have. What would you like to discuss today?") {
                continue;
            }
            $role = $h['sender'] === 'user' ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $h['text']];
        }
    }
    $messages[] = ['role' => 'user', 'content' => $message];
    
    $data = [
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => 2000,
        'system' => 'You are a helpful AI assistant. Respond directly to the user\'s current question without referencing previous unrelated conversations. Be helpful, accurate, and conversational.'
    ];
    
    $options = [
        'http' => [
            'header' => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01'
            ],
            'method' => 'POST',
            'content' => json_encode($data),
            'timeout' => 30,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        throw new Exception('Failed to connect to Anthropic API');
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['content'][0]['text'])) {
        if (isset($result['error'])) {
            throw new Exception('Anthropic API error: ' . $result['error']['message']);
        }
        throw new Exception('Invalid response from Anthropic API');
    }
    
    return $result['content'][0]['text'];
}

/**
 * Call Google AI API (Gemini models)
 */
function callGoogle($message, $apiKey, $model, $history) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    
    // Build conversation history
    $contents = [];
    
    // Add history
    foreach ($history as $h) {
        if (isset($h['sender']) && isset($h['text'])) {
            $role = $h['sender'] === 'user' ? 'user' : 'model';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $h['text']]]
            ];
        }
    }
    
    // Add current message
    $contents[] = [
        'role' => 'user',
        'parts' => [['text' => $message]]
    ];
    
    $data = [
        'contents' => $contents
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $error = json_decode($response, true);
        throw new Exception($error['error']['message'] ?? 'Google AI API error');
    }
    
    $result = json_decode($response, true);
    return $result['candidates'][0]['content']['parts'][0]['text'] ?? 'No response generated';
}

// Simple rate limiting (optional)
function checkRateLimit($identifier) {
    // Implement rate limiting if needed
    // For now, return true
    return true;
}

// Log conversation (optional)
function logConversation($message, $response, $provider) {
    // Implement logging if needed
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'response' => $response,
        'provider' => $provider,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Could write to file or database
    // error_log(json_encode($logData));
}