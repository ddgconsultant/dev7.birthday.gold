<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# TEST CLAUDE CODE AUTHENTICATION
#-------------------------------------------------------------------------------
$page_title = "Claude Code Authentication Test";

// Check if Claude bypass is active
$claude_active = isset($_SESSION['claude_code_session']) && $_SESSION['claude_code_session'] === true;

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container mt-5">
    <h1>Claude Code Authentication Test</h1>
    
    <div class="card mt-4">
        <div class="card-header">
            <h5>Authentication Status</h5>
        </div>
        <div class="card-body">
            <?php if ($claude_active): ?>
                <div class="alert alert-success" role="alert">
                    <h4 class="alert-heading">✅ Claude Code Authentication Active!</h4>
                    <p>The Claude Code authentication bypass is working correctly.</p>
                    <hr>
                    <p class="mb-0">You are authenticated as: <strong><?php echo $current_user_data['user_username']; ?></strong></p>
                </div>
            <?php else: ?>
                <div class="alert alert-danger" role="alert">
                    <h4 class="alert-heading">❌ Claude Code Authentication Not Active</h4>
                    <p>The Claude Code authentication bypass is not active. Make sure you're sending the correct header.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h5>Current User Data</h5>
        </div>
        <div class="card-body">
            <?php if (isset($current_user_data) && is_array($current_user_data)): ?>
                <table class="table table-bordered">
                    <tbody>
                        <?php foreach ($current_user_data as $key => $value): ?>
                            <tr>
                                <th><?php echo htmlspecialchars($key); ?></th>
                                <td><?php echo htmlspecialchars($value); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No user data available.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h5>How to Use Claude Code Authentication</h5>
        </div>
        <div class="card-body">
            <p>To authenticate as Claude Code, send an HTTP request with the following header:</p>
            <pre class="bg-light p-3"><code>X-Claude-Code-Key: CldCd_DevAuth_2025_Xk9Pq3mR7vT</code></pre>
            
            <h6 class="mt-3">Example cURL command:</h6>
            <pre class="bg-light p-3"><code>curl -H "X-Claude-Code-Key: CldCd_DevAuth_2025_Xk9Pq3mR7vT" https://dev7.birthday.gold/admin/test-claude-auth.php</code></pre>
            
            <h6 class="mt-3">Example with fetch in JavaScript:</h6>
            <pre class="bg-light p-3"><code>fetch('https://dev7.birthday.gold/admin/test-claude-auth.php', {
    headers: {
        'X-Claude-Code-Key': 'CldCd_DevAuth_2025_Xk9Pq3mR7vT'
    }
});</code></pre>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h5>Permission Tests</h5>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Permission</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Is Admin</td>
                        <td><?php echo $account->isadmin() ? '<span class="text-success">✅ Yes</span>' : '<span class="text-danger">❌ No</span>'; ?></td>
                    </tr>
                    <tr>
                        <td>Is Staff</td>
                        <td><?php echo $account->isstaff() ? '<span class="text-success">✅ Yes</span>' : '<span class="text-danger">❌ No</span>'; ?></td>
                    </tr>
                    <tr>
                        <td>Is Active</td>
                        <td><?php echo $account->isactive() ? '<span class="text-success">✅ Yes</span>' : '<span class="text-danger">❌ No</span>'; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>