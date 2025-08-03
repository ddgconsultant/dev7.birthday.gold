<?php
/**
 * Quick Test for Google Assistant Integration
 * Tests the complete flow without Google Actions Console
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is admin
if (!$account->checkrole('admin')) {
    die('Admin access required');
}

$current_user_data = $session->get('current_user_data');
$user_id = $current_user_data['user_id'];

// Initialize
$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate_code') {
        // Generate a linking code manually
        include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.assistant.php');
        $assistant = new Assistant($database, $app, $account, $session);
        $result = $assistant->generateLinkingCode('google', 'test-device-' . uniqid());
        
        if ($result) {
            $message = 'Generated linking code: ' . $result['code'];
            $messageType = 'success';
            $_SESSION['test_linking_code'] = $result['raw_code'];
        }
    }
    
    if ($action === 'link_account') {
        include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.assistant.php');
        $assistant = new Assistant($database, $app, $account, $session);
        $code = $_SESSION['test_linking_code'] ?? '';
        
        if ($code) {
            $result = $assistant->verifyLinkingCode($code, $user_id, 'google');
            if ($result['success']) {
                $message = 'Account linked! Access token: ' . substr($result['tokens']['access_token'], 0, 20) . '...';
                $messageType = 'success';
                $_SESSION['test_access_token'] = $result['tokens']['access_token'];
            } else {
                $message = 'Failed to link account';
                $messageType = 'error';
            }
        }
    }
}

$pagetitle = 'Google Assistant Quick Test';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container mt-4">
    <h1>Google Assistant Quick Test</h1>
    
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3>Step 1: Generate Linking Code</h3>
        </div>
        <div class="card-body">
            <p>This simulates what happens when Google Assistant needs to link an account.</p>
            <form method="POST">
                <input type="hidden" name="action" value="generate_code">
                <button type="submit" class="btn btn-primary">Generate Linking Code</button>
            </form>
            
            <?php if (isset($_SESSION['test_linking_code'])): ?>
            <div class="mt-3">
                <strong>Code stored in session:</strong> <?php echo $_SESSION['test_linking_code']; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3>Step 2: Link Account</h3>
        </div>
        <div class="card-body">
            <p>This simulates the user entering the code to link their account.</p>
            <form method="POST">
                <input type="hidden" name="action" value="link_account">
                <button type="submit" class="btn btn-primary" <?php echo !isset($_SESSION['test_linking_code']) ? 'disabled' : ''; ?>>
                    Link Account with Code
                </button>
            </form>
            
            <?php if (isset($_SESSION['test_access_token'])): ?>
            <div class="mt-3">
                <strong>Access token stored:</strong> <?php echo substr($_SESSION['test_access_token'], 0, 20); ?>...
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3>Step 3: Test Voice Commands</h3>
        </div>
        <div class="card-body">
            <p>Test different intents with the linked account.</p>
            
            <div class="row">
                <?php
                $intents = [
                    'GetEnrollmentCount' => 'How many enrollments do I have?',
                    'GetActiveRewards' => 'What rewards do I have?',
                    'GetAllocationBalance' => 'How many allocations left?',
                    'GetAccountStatus' => 'What\'s my account status?'
                ];
                
                foreach ($intents as $intent => $question):
                ?>
                <div class="col-md-6 mb-3">
                    <button class="btn btn-outline-primary w-100 test-intent" 
                            data-intent="<?php echo $intent; ?>"
                            <?php echo !isset($_SESSION['test_access_token']) ? 'disabled' : ''; ?>>
                        <i class="bi bi-mic-fill"></i> <?php echo $question; ?>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div id="responseArea" class="mt-3" style="display: none;">
                <h5>Assistant Response:</h5>
                <div class="alert alert-info">
                    <i class="bi bi-speaker-fill"></i> <span id="responseText"></span>
                </div>
                <pre id="rawResponse" class="bg-light p-3 mt-2"></pre>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.test-intent').forEach(button => {
    button.addEventListener('click', async function() {
        const intent = this.dataset.intent;
        const accessToken = '<?php echo $_SESSION['test_access_token'] ?? ''; ?>';
        
        const requestData = {
            user: {
                accessToken: accessToken
            },
            intent: {
                name: intent
            },
            handler: {
                name: 'birthdayGold'
            },
            session: {
                id: 'test-session-' + Date.now()
            }
        };
        
        try {
            const response = await fetch('/api/assistant/google/webhook.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Google-Actions-API-Version': '3'
                },
                body: JSON.stringify(requestData)
            });
            
            const data = await response.json();
            
            document.getElementById('responseText').textContent = 
                data.prompt?.firstSimple?.speech || 'No response';
            document.getElementById('rawResponse').textContent = 
                JSON.stringify(data, null, 2);
            document.getElementById('responseArea').style.display = 'block';
            
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });
});
</script>

<?php
$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>