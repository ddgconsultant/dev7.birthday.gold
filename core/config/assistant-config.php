<?php
/**
 * Voice Assistant Configuration
 * Handles different environments (dev, production)
 */

// Determine current environment
$current_host = $_SERVER['HTTP_HOST'] ?? 'dev7.birthday.gold';

// Environment-specific configurations
$assistant_environments = [
    // Development
    'dev7.birthday.gold' => [
        'google' => [
            'client_id' => 'google-assistant-birthday-gold-dev',
            'client_secret' => 'dev-secret-change-me',
            'project_id' => 'birthday-gold-dev',
        ],
        'alexa' => [
            'client_id' => 'amzn1.application-oa2-client.dev',
            'client_secret' => 'dev-secret-change-me',
            'skill_id' => 'amzn1.ask.skill.dev',
        ],
        'oauth' => [
            'auth_endpoint' => 'https://dev7.birthday.gold/myaccount/assistant-oauth.php',
            'token_endpoint' => 'https://dev7.birthday.gold/api/assistant/oauth-token.php',
        ],
        'webhooks' => [
            'google' => 'https://dev7.birthday.gold/api/assistant/google/webhook.php',
            'alexa' => 'https://dev7.birthday.gold/api/assistant/alexa/webhook.php',
        ],
        'allowed_redirect_uris' => [
            // Google OAuth redirects
            'https://oauth-redirect.googleusercontent.com/r/birthday-gold-dev',
            // Alexa OAuth redirects  
            'https://pitangui.amazon.com/api/skill/link/M1234567890DEV',
            'https://layla.amazon.com/api/skill/link/M1234567890DEV',
            'https://alexa.amazon.co.jp/api/skill/link/M1234567890DEV',
        ]
    ],
    
    // Production
    'birthday.gold' => [
        'google' => [
            'client_id' => 'google-assistant-birthday-gold',
            'client_secret' => 'prod-secret-change-me',
            'project_id' => 'birthday-gold',
        ],
        'alexa' => [
            'client_id' => 'amzn1.application-oa2-client.prod',
            'client_secret' => 'prod-secret-change-me', 
            'skill_id' => 'amzn1.ask.skill.prod',
        ],
        'oauth' => [
            'auth_endpoint' => 'https://birthday.gold/myaccount/assistant-oauth.php',
            'token_endpoint' => 'https://birthday.gold/api/assistant/oauth-token.php',
        ],
        'webhooks' => [
            'google' => 'https://birthday.gold/api/assistant/google/webhook.php',
            'alexa' => 'https://birthday.gold/api/assistant/alexa/webhook.php',
        ],
        'allowed_redirect_uris' => [
            // Google OAuth redirects
            'https://oauth-redirect.googleusercontent.com/r/birthday-gold',
            // Alexa OAuth redirects
            'https://pitangui.amazon.com/api/skill/link/M1234567890',
            'https://layla.amazon.com/api/skill/link/M1234567890',
            'https://alexa.amazon.co.jp/api/skill/link/M1234567890',
        ]
    ],
    
    // Local development with ngrok
    'localhost' => [
        'google' => [
            'client_id' => 'google-assistant-birthday-gold-local',
            'client_secret' => 'local-secret',
            'project_id' => 'birthday-gold-local',
        ],
        'alexa' => [
            'client_id' => 'amzn1.application-oa2-client.local',
            'client_secret' => 'local-secret',
            'skill_id' => 'amzn1.ask.skill.local',
        ],
        'oauth' => [
            // Update these when you run ngrok
            'auth_endpoint' => 'https://YOUR-NGROK-ID.ngrok.io/myaccount/assistant-oauth.php',
            'token_endpoint' => 'https://YOUR-NGROK-ID.ngrok.io/api/assistant/oauth-token.php',
        ],
        'webhooks' => [
            'google' => 'https://YOUR-NGROK-ID.ngrok.io/api/assistant/google/webhook.php',
            'alexa' => 'https://YOUR-NGROK-ID.ngrok.io/api/assistant/alexa/webhook.php',
        ],
        'allowed_redirect_uris' => [
            'https://oauth-redirect.googleusercontent.com/r/birthday-gold-local',
        ]
    ]
];

// Get configuration for current environment
$assistant_config = $assistant_environments[$current_host] ?? $assistant_environments['dev7.birthday.gold'];

// Make config globally available
define('ASSISTANT_CONFIG', $assistant_config);

// Helper function to check if redirect URI is allowed
function isAllowedRedirectUri($uri) {
    global $assistant_config;
    
    foreach ($assistant_config['allowed_redirect_uris'] as $allowed) {
        if (strpos($uri, $allowed) === 0) {
            return true;
        }
    }
    
    return false;
}

// Helper function to get client secret
function getAssistantClientSecret($platform, $client_id) {
    global $assistant_config;
    
    if (isset($assistant_config[$platform]) && 
        $assistant_config[$platform]['client_id'] === $client_id) {
        return $assistant_config[$platform]['client_secret'];
    }
    
    return null;
}
?>