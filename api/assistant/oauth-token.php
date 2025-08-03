<?php
/**
 * OAuth2 Token Exchange Endpoint
 * Exchanges authorization codes for access tokens
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.assistant.php');

// Include assistant configuration
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/core/config/assistant-config.php')) {
    include_once($_SERVER['DOCUMENT_ROOT'] . '/core/config/assistant-config.php');
}

header('Content-Type: application/json');

// Get POST parameters
$grant_type = $_POST['grant_type'] ?? '';
$code = $_POST['code'] ?? '';
$client_id = $_POST['client_id'] ?? '';
$client_secret = $_POST['client_secret'] ?? '';
$redirect_uri = $_POST['redirect_uri'] ?? '';
$refresh_token = $_POST['refresh_token'] ?? '';

// Initialize response
$response = [];

if ($grant_type === 'authorization_code' && !empty($code)) {
    // Exchange authorization code for tokens using bg_validations
    $sql = "SELECT * FROM bg_validations 
            WHERE validation_code = :code 
            AND validation_type = 'oauth_code'
            AND validation_rawdata LIKE :client_pattern
            AND status = 'pending'
            AND expire_dt > NOW()
            LIMIT 1";
    
    $result = $database->getrow($sql, [
        ':code' => $code,
        ':client_pattern' => '%client_id:' . $client_id . '%'
    ]);
    
    if ($result) {
        // Extract platform from rawdata
        $rawdata = json_decode($result['validation_rawdata'], true);
        $platform = $rawdata['platform'] ?? 'unknown';
        
        // Mark code as used
        $updateSql = "UPDATE bg_validations 
                     SET status = 'used', 
                         validation_dt = NOW(),
                         modify_dt = NOW()
                     WHERE validation_id = :validation_id";
        $database->query($updateSql, [':validation_id' => $result['validation_id']]);
        
        // Create tokens
        $assistant = new Assistant($database, $app, $account, $session);
        $tokens = $assistant->createAssistantTokens(
            $result['user_id'], 
            $platform, 
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
        
        $response = [
            'access_token' => $tokens['access_token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokens['expires_in'],
            'refresh_token' => $tokens['refresh_token']
        ];
        
        // Log successful token exchange
        $app->session_tracking('assistant_token_exchanged', [
            'user_id' => $result['user_id'],
            'platform' => $platform
        ]);
    } else {
        http_response_code(400);
        $response = [
            'error' => 'invalid_grant',
            'error_description' => 'Invalid authorization code'
        ];
    }
} elseif ($grant_type === 'refresh_token' && !empty($refresh_token)) {
    // Refresh access token
    $sql = "SELECT * FROM bg_assistant_tokens 
            WHERE refresh_token = :refresh_token
            LIMIT 1";
    
    $result = $database->getrow($sql, [':refresh_token' => $refresh_token]);
    
    if ($result) {
        // Generate new access token
        $newAccessToken = bin2hex(random_bytes(32));
        
        // Update token
        $updateSql = "UPDATE bg_assistant_tokens 
                     SET access_token = :access_token,
                         expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY),
                         last_used = NOW()
                     WHERE token_id = :token_id";
        
        $database->query($updateSql, [
            ':access_token' => $newAccessToken,
            ':token_id' => $result['token_id']
        ]);
        
        $response = [
            'access_token' => $newAccessToken,
            'token_type' => 'Bearer',
            'expires_in' => 2592000,
            'refresh_token' => $refresh_token
        ];
    } else {
        http_response_code(400);
        $response = [
            'error' => 'invalid_grant',
            'error_description' => 'Invalid refresh token'
        ];
    }
} else {
    http_response_code(400);
    $response = [
        'error' => 'unsupported_grant_type',
        'error_description' => 'Grant type not supported'
    ];
}

echo json_encode($response);
?>