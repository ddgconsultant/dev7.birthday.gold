<?php
$addClasses[] = 'mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get CSRF token
$csrfToken = $display->input_csrftoken('tokenonly');

// Sample encoded message ID (you can replace with a real one)
$sampleMessageId = 'DLVR6S-OVMNO-VQTMO-QWTM6M';
$sampleServer = 'march01:1072';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Bulk Action API</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Test Bulk Action API</h1>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Sample URL to test bulk-action.php</h5>
                
                <div class="mb-3">
                    <label class="form-label">CSRF Token:</label>
                    <code><?php echo htmlspecialchars($csrfToken); ?></code>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Sample Request Body:</label>
                    <pre><code>{
    "_token": "<?php echo htmlspecialchars($csrfToken); ?>",
    "action": "mark-unread",
    "messageIds": ["<?php echo htmlspecialchars($sampleMessageId); ?>"],
    "server": "<?php echo htmlspecialchars($sampleServer); ?>"
}</code></pre>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">cURL Command:</label>
                    <pre><code>curl -X POST https://dev7.birthday.gold/api/messages/bulk-action.php \
  -H "Content-Type: application/json" \
  -H "Cookie: <?php echo htmlspecialchars($_SERVER['HTTP_COOKIE'] ?? ''); ?>" \
  -d '{
    "_token": "<?php echo htmlspecialchars($csrfToken); ?>",
    "action": "mark-unread",
    "messageIds": ["<?php echo htmlspecialchars($sampleMessageId); ?>"],
    "server": "<?php echo htmlspecialchars($sampleServer); ?>"
  }'</code></pre>
                </div>
                
                <button class="btn btn-primary" onclick="testApi()">Test API Call</button>
                
                <div id="result" class="mt-3"></div>
            </div>
        </div>
    </div>
    
    <script>
    async function testApi() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<div class="alert alert-info">Testing...</div>';
        
        try {
            const response = await fetch('/api/messages/bulk-action-v2.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    _token: '<?php echo $csrfToken; ?>',
                    action: 'mark-unread',
                    messageIds: ['<?php echo $sampleMessageId; ?>'],
                    server: '<?php echo $sampleServer; ?>'
                })
            });
            
            const text = await response.text();
            let result;
            
            try {
                result = JSON.parse(text);
            } catch (e) {
                // Show raw response if not JSON
                resultDiv.innerHTML = '<div class="alert alert-danger"><strong>Raw Response:</strong><pre>' + text.substring(0, 1000) + '</pre></div>';
                return;
            }
            
            if (result.success) {
                resultDiv.innerHTML = '<div class="alert alert-success"><strong>Success!</strong><pre>' + JSON.stringify(result, null, 2) + '</pre></div>';
            } else {
                resultDiv.innerHTML = '<div class="alert alert-warning"><strong>API Error:</strong><pre>' + JSON.stringify(result, null, 2) + '</pre></div>';
            }
        } catch (error) {
            resultDiv.innerHTML = '<div class="alert alert-danger"><strong>Error:</strong> ' + error.message + '</div>';
        }
    }
    </script>
</body>
</html>