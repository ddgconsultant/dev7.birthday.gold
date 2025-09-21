<?php
// mail-read.php
$addClasses[] = 'mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$message_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mailserver = isset($_GET['server']) ? $_GET['server'] : null;

if (!$message_id) {
    header('Location: /myaccount/mail-box');
    exit;
}

$message = $mail->getmessage($message_id, $mailserver);
if (!$message) {
    header('Location: /myaccount/mail-box');
    exit;
}

// Get company info if available
$company = !empty($message['company_id']) ? $app->getcompany($message['company_id']) : null;

$additionalstyles .= '
    <style>
        .message-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .message-body {
            white-space: pre-wrap;
            font-family: inherit;
        }
        .company-logo {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 4px;
        }
       .message-frame {
            width: 100%;
            border: none;
            height: calc(100vh - 600px); /* Adjust this value based on your header heights */
            display: block;
        }
        .card-body {
            height: calc(100vh - 600px); /* Make card body fill available space */
            display: flex;
            flex-direction: column;
        }
    </style>';

$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');
?>

<div class="container main-content mt-0 pt-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Message</h2>
        <div>
            <a href="/myaccount/mail-box" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to Inbox
            </a>
            <button class="btn btn-outline-danger" id="delete-message" 
            data-message-id="<?php echo $message_id; ?>"
            data-server="<?php echo $mailserver; ?>">
                <i class="bi bi-trash"></i> Delete
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Message Header -->
            <div class="message-header p-3 mb-4">
                <div class="d-flex align-items-center mb-3">
                    <?php if (!empty($company['company_logo'])): ?>
                        <img src="<?php echo $display->companyimage($company['company_id'] . '/' . $company['company_logo']); ?>" 
                             class="company-logo me-3" alt="Company Logo">
                    <?php else: ?>
                        <div class="company-logo bg-secondary d-flex align-items-center justify-content-center me-3">
                            <i class="bi bi-building text-white"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h5 class="mb-1"><?php echo ($company['company_display_name'] ?? 'Unknown Sender'); ?></h5>
                        <div class="text-muted">
                            <?php echo $display->formatdate($message['create_dt'], 'F j, Y g:i A'); ?>
                        </div>
                    </div>
                </div>
                <h4 class="mb-0"><?php echo $message['subject']; ?></h4>
            </div>
<?
/*
            <!-- Message Body -->
            <div class="message-body">
            <?php echo $message['body']; ?>
            </div>
*/
echo '
<!-- Message Body in iframe -->
<iframe class="message-frame" srcdoc="' . htmlspecialchars($message['body']) . '"></iframe>
';
?>
        </div>
    </div>
</div>


</div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete message handler
    document.getElementById('delete-message').addEventListener('click', async function() {
        if (!confirm('Are you sure you want to delete this message?')) {
            return;
        }

        const messageId = this.dataset.messageId;
        const server = this.dataset.server;

        try {
            const response = await fetch('/api/messages/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    messageId: messageId,
                    server: server
                })
            });

            if (!response.ok) throw new Error('Network response was not ok');
            
            const result = await response.json();
            
            if (result.success) {
                window.location.href = '/myaccount/mail-box';
            } else {
                throw new Error(result.message || 'Unknown error occurred');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while deleting the message');
        }
    });
});
</script>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>