<?php
/**
 * Test Google Assistant OAuth Flow
 * This simulates the OAuth flow that Google would initiate
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Only allow admin access
if (!$account->checkrole('admin')) {
    die('Admin access required');
}

$pagetitle = 'Test Google Assistant OAuth Flow';

// Google OAuth parameters
$test_params = [
    'client_id' => 'google-assistant-birthday-gold',
    'redirect_uri' => 'https://oauth-redirect.googleusercontent.com/r/birthday-gold-test',
    'state' => 'test_state_' . uniqid(),
    'response_type' => 'code',
    'scope' => 'profile'
];

$oauth_url = 'https://dev7.birthday.gold/myaccount/assistant-oauth.php?' . http_build_query($test_params);

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container mt-4">
    <h1>Test Google Assistant OAuth Flow</h1>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3>Step 1: OAuth Authorization</h3>
        </div>
        <div class="card-body">
            <p>This simulates what happens when a user says "Talk to Birthday Gold" in Google Assistant.</p>
            
            <div class="mb-3">
                <strong>OAuth URL:</strong><br>
                <code style="word-break: break-all;"><?php echo htmlspecialchars($oauth_url); ?></code>
            </div>
            
            <div class="mb-3">
                <strong>Parameters:</strong>
                <ul>
                    <?php foreach ($test_params as $key => $value): ?>
                    <li><code><?php echo htmlspecialchars($key); ?></code>: <?php echo htmlspecialchars($value); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <a href="<?php echo htmlspecialchars($oauth_url); ?>" class="btn btn-primary" target="_blank">
                Start OAuth Flow
            </a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3>Step 2: Token Exchange</h3>
        </div>
        <div class="card-body">
            <p>After authorization, Google will exchange the code for tokens.</p>
            
            <form id="tokenExchangeForm">
                <div class="mb-3">
                    <label for="authCode" class="form-label">Authorization Code (from redirect URL)</label>
                    <input type="text" class="form-control" id="authCode" placeholder="Enter the code from the redirect">
                </div>
                
                <button type="submit" class="btn btn-primary">Exchange for Token</button>
            </form>
            
            <div id="tokenResult" class="mt-3" style="display: none;">
                <h5>Token Response:</h5>
                <pre id="tokenResponse" class="bg-light p-3"></pre>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3>Step 3: Test Webhook</h3>
        </div>
        <div class="card-body">
            <p>Test the webhook with the access token.</p>
            
            <form id="webhookForm">
                <div class="mb-3">
                    <label for="accessToken" class="form-label">Access Token</label>
                    <input type="text" class="form-control" id="accessToken" placeholder="Enter the access token">
                </div>
                
                <div class="mb-3">
                    <label for="intent" class="form-label">Intent</label>
                    <select class="form-select" id="intent">
                        <option value="GetEnrollmentCount">Get Enrollment Count</option>
                        <option value="GetActiveRewards">Get Active Rewards</option>
                        <option value="GetAllocationBalance">Get Allocation Balance</option>
                        <option value="GetAccountStatus">Get Account Status</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Test Webhook</button>
            </form>
            
            <div id="webhookResult" class="mt-3" style="display: none;">
                <h5>Webhook Response:</h5>
                <pre id="webhookResponse" class="bg-light p-3"></pre>
            </div>
        </div>
    </div>
</div>

<script>
// Token exchange form
document.getElementById('tokenExchangeForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const authCode = document.getElementById('authCode').value;
    
    const formData = new URLSearchParams();
    formData.append('grant_type', 'authorization_code');
    formData.append('code', authCode);
    formData.append('client_id', '<?php echo $test_params['client_id']; ?>');
    formData.append('client_secret', 'test-secret'); // In production, this would be secure
    formData.append('redirect_uri', '<?php echo $test_params['redirect_uri']; ?>');
    
    try {
        const response = await fetch('/api/assistant/oauth-token.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
        });
        
        const data = await response.json();
        document.getElementById('tokenResponse').textContent = JSON.stringify(data, null, 2);
        document.getElementById('tokenResult').style.display = 'block';
        
        if (data.access_token) {
            document.getElementById('accessToken').value = data.access_token;
        }
    } catch (error) {
        document.getElementById('tokenResponse').textContent = 'Error: ' + error.message;
        document.getElementById('tokenResult').style.display = 'block';
    }
});

// Webhook test form
document.getElementById('webhookForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const accessToken = document.getElementById('accessToken').value;
    const intent = document.getElementById('intent').value;
    
    const webhookData = {
        user: {
            accessToken: accessToken
        },
        intent: {
            name: intent
        },
        handler: {
            name: 'birthdayGold'
        }
    };
    
    try {
        const response = await fetch('/api/assistant/google/webhook.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Google-Actions-API-Version': '3'
            },
            body: JSON.stringify(webhookData)
        });
        
        const data = await response.json();
        document.getElementById('webhookResponse').textContent = JSON.stringify(data, null, 2);
        document.getElementById('webhookResult').style.display = 'block';
    } catch (error) {
        document.getElementById('webhookResponse').textContent = 'Error: ' + error.message;
        document.getElementById('webhookResult').style.display = 'block';
    }
});
</script>

<?php
$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>