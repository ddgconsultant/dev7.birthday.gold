# Claude Code Authentication Documentation

## Overview
This document describes the Claude Code authentication bypass system implemented for development testing purposes. This system allows Claude Code to access protected pages without real user credentials in the development environment.

## Implementation Details

### 1. Authentication Bypass in site-controller.php
Located at `/core/site-controller.php` starting at line 499:

```php
// Claude Code authentication bypass
$claudebypass = false;
if (isset($_SERVER['HTTP_X_CLAUDE_CODE_KEY']) && $mode == 'dev') {
    $claude_key = $_SERVER['HTTP_X_CLAUDE_CODE_KEY'];
    if (isset($sitesettings['app']['CLAUDE_CODE_AUTH_KEY']) && $claude_key == $sitesettings['app']['CLAUDE_CODE_AUTH_KEY']) {
        $claudebypass = true;
        session_tracking('claude_code_access', ['ip' => $client_ip, 'uri' => $_SERVER['REQUEST_URI'], 'time' => date('Y-m-d H:i:s')]);
    }
}
```

### 2. Configuration
The authentication key is stored in `/ENV_CONFIGS/config-main-dev7.inc`:
```ini
CLAUDE_CODE_AUTH_KEY="CldCd_DevAuth_2025_Xk9Pq3mR7vT"
```

### 3. Test User Data
When Claude Code authentication is active, a test user is created with the following properties:
- User ID: 999999
- Username: claude_code_test
- Email: claude@birthday.gold
- Role: admin
- Status: active

## Usage

### HTTP Header Authentication
To authenticate as Claude Code, include the following HTTP header in your requests:
```
X-Claude-Code-Key: CldCd_DevAuth_2025_Xk9Pq3mR7vT
```

### Example Usage

#### cURL Command:
```bash
curl -H "X-Claude-Code-Key: CldCd_DevAuth_2025_Xk9Pq3mR7vT" https://dev7.birthday.gold/admin/index.php
```

#### Python requests:
```python
import requests

headers = {
    'X-Claude-Code-Key': 'CldCd_DevAuth_2025_Xk9Pq3mR7vT'
}
response = requests.get('https://dev7.birthday.gold/admin/index.php', headers=headers)
```

#### JavaScript fetch:
```javascript
fetch('https://dev7.birthday.gold/admin/index.php', {
    headers: {
        'X-Claude-Code-Key': 'CldCd_DevAuth_2025_Xk9Pq3mR7vT'
    }
});
```

## Security Considerations

1. **Development Only**: This authentication bypass only works when `$mode == 'dev'` in site-controller.php
2. **Session Tracking**: All Claude Code access is logged via the `session_tracking()` function
3. **Rate Limiting**: Standard rate limiting (40 req/sec, 150 req/min) still applies
4. **No Production Access**: This bypass will not work in production environments

## Testing

A test page is available at `/admin/test-claude-auth.php` to verify the authentication is working correctly.

## Permissions

When authenticated via Claude Code:
- ✅ Admin access granted
- ✅ Staff access granted  
- ✅ Can access all protected directories (/admin/, /myaccount/, /staff/)
- ✅ Full user permissions for testing

## Audit Trail

All Claude Code authentication attempts are logged in the `bg_sessiontracking` table with:
- IP address
- Request URI
- Timestamp
- Action type: 'claude_code_access'

## Troubleshooting

If authentication is not working:
1. Verify you're in development mode (`$mode == 'dev'`)
2. Check the authentication key matches exactly
3. Ensure the header name is exactly `X-Claude-Code-Key`
4. Check session tracking logs for authentication attempts
5. Verify the config file is being loaded correctly

## Future Enhancements

Potential improvements for production use:
1. Add IP whitelist restriction
2. Implement time-based token rotation
3. Add specific permission scoping
4. Create dedicated API endpoints for Claude Code
5. Add webhook notifications for access events