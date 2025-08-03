# Voice Assistant Development Setup Guide

## The Challenge

Google Assistant and Amazon Alexa require:
- Publicly accessible HTTPS endpoints
- Valid SSL certificates
- Consistent URLs that don't change

Your dev environment at `dev7.birthday.gold` needs to be accessible from the internet.

## Solutions for Development

### Option 1: Use ngrok (Recommended for Testing)

1. **Install ngrok**: https://ngrok.com/
2. **Create a tunnel to your dev server**:
   ```bash
   ngrok http https://dev7.birthday.gold
   ```
3. **You'll get a public URL like**: `https://abc123.ngrok.io`
4. **Update your code temporarily** to handle the ngrok domain

### Option 2: Make dev7.birthday.gold Public

If dev7.birthday.gold is already publicly accessible:

1. **Verify it's accessible**:
   ```bash
   curl https://dev7.birthday.gold/api/assistant/webhook.php
   ```

2. **Update the OAuth URLs in Google/Alexa consoles**:
   - Auth URL: `https://dev7.birthday.gold/myaccount/assistant-oauth.php`
   - Token URL: `https://dev7.birthday.gold/api/assistant/oauth-token.php`
   - Webhook: `https://dev7.birthday.gold/api/assistant/google/webhook.php`

### Option 3: Use a Reverse Proxy

Set up a production subdomain that proxies to dev:
- `voice-dev.birthday.gold` → `dev7.birthday.gold`

## Configuration Updates Needed

### 1. Update OAuth Redirect URIs

Edit `/myaccount/assistant-oauth.php` to accept Google's redirect URI:

```php
// Add to the validation section
$allowed_redirect_uris = [
    'https://oauth-redirect.googleusercontent.com/r/YOUR_PROJECT_ID',
    'https://pitangui.amazon.com/api/skill/link/YOUR_SKILL_ID',
    'https://layla.amazon.com/api/skill/link/YOUR_SKILL_ID',
    'https://alexa.amazon.co.jp/api/skill/link/YOUR_SKILL_ID'
];
```

### 2. Update CORS Headers

Add to your webhook endpoints:

```php
// Add these headers for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Google-Actions-API-Version');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
```

### 3. SSL Certificate Requirements

Both Google and Alexa require valid SSL certificates:
- Self-signed certificates won't work
- Use Let's Encrypt for free valid certificates
- Or use ngrok which provides SSL automatically

## Development Workflow

### For Google Assistant:

1. **Create a test project** in Actions Console
2. **Set OAuth endpoints** to your dev URLs
3. **Use Actions Simulator** for testing (doesn't require publication)
4. **Share with test users** via email (up to 20 users)

### For Alexa:

1. **Create development skill** in Alexa Developer Console
2. **Enable for testing** in your account
3. **Use Alexa Simulator** or your own devices
4. **Beta test** with up to 500 users without publishing

## Environment-Specific Configuration

Create a configuration file for environment switching:

```php
// /core/config/assistant-config.php
<?php
$env = $_SERVER['HTTP_HOST'] ?? 'dev7.birthday.gold';

$assistant_config = [
    'dev7.birthday.gold' => [
        'google_client_id' => 'google-assistant-birthday-gold-dev',
        'alexa_client_id' => 'amzn1.application-oa2-client.dev123',
        'base_url' => 'https://dev7.birthday.gold'
    ],
    'birthday.gold' => [
        'google_client_id' => 'google-assistant-birthday-gold',
        'alexa_client_id' => 'amzn1.application-oa2-client.prod123',
        'base_url' => 'https://birthday.gold'
    ]
];

define('ASSISTANT_CONFIG', $assistant_config[$env] ?? $assistant_config['dev7.birthday.gold']);
```

## Testing Without Publishing

### Google Assistant:
1. In Actions Console, go to **Test**
2. Enable testing for your account
3. Test on any device logged into your Google account
4. Say "Talk to my test app" (it adds "test" automatically)

### Alexa:
1. In Alexa Developer Console, go to **Test**
2. Set to "Development" stage
3. Enable testing on your account
4. The skill appears in your Alexa app automatically

## Quick Development Test

Use the included test tools with your dev URL:

```bash
# Test OAuth flow
https://dev7.birthday.gold/admin_actions/test-google-oauth.php

# Quick test without external services
https://dev7.birthday.gold/admin_actions/google-assistant-quicktest.php
```

## Important Notes

1. **Don't use production credentials in dev**
2. **Keep separate Google Projects** for dev/prod
3. **Use different skill names** (e.g., "Birthday Gold Dev")
4. **Test accounts only** - don't invite real users to dev
5. **Update URLs** when moving to production

## Moving to Production

When ready to deploy:

1. Create new Google Action with production URLs
2. Create new Alexa Skill with production URLs
3. Update client IDs in your code
4. Submit for certification
5. Keep dev environment for future testing