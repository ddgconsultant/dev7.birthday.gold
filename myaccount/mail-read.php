<?php
// mail-read.php
$addClasses[] = 'mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$message_id_encoded = isset($_GET['id']) ? $_GET['id'] : '';
$mailserver_encoded = isset($_GET['server']) ? $_GET['server'] : '';

// Decode the message ID
try {
    $message_id = $message_id_encoded ? $qik->decodeId($message_id_encoded) : 0;
} catch (Exception $e) {
    $errormessage = '<div class="alert alert-danger">Invalid message link. Please return to your inbox and try again.</div>';
    $transferpage['url'] = '/myaccount/mail-box';
    $transferpage['message'] = $errormessage;
    $system->endpostpage($transferpage);
}

// Get the mail server (no decoding needed - it's a string)
$mailserver = $mailserver_encoded ? $mailserver_encoded : null;

// Validate server parameter if provided
if ($mailserver && !preg_match('/^[a-zA-Z0-9\.\-]+$/', $mailserver)) {
    $errormessage = '<div class="alert alert-danger">Invalid server parameter. Please return to your inbox and try again.</div>';
    $transferpage['url'] = '/myaccount/mail-box';
    $transferpage['message'] = $errormessage;
    $system->endpostpage($transferpage);
}

if (!$message_id) {
    $errormessage = '<div class="alert alert-danger">Invalid message ID. Please try again.</div>';
    $transferpage['url'] = '/myaccount/mail-box';
    $transferpage['message'] = $errormessage;
    $system->endpostpage($transferpage);
}

$message = $mail->getmessage($message_id, $mailserver);
if (!$message) {
    $errormessage = '<div class="alert alert-warning">Unable to load message. The message may have been deleted or you may not have permission to view it.</div>';
    $transferpage['url'] = '/myaccount/mail-box';
    $transferpage['message'] = $errormessage;
    $system->endpostpage($transferpage);
}

// Get company info if available
$company = !empty($message['company_id']) ? $app->getcompany($message['company_id']) : null;

// Build "Back to Inbox" URL with stored filter state
$back_to_inbox_url = '/myaccount/mail-box';
if (!empty($_SESSION['mail_box_state'])) {
    $params = [];
    if (!empty($_SESSION['mail_box_state']['sort']) && $_SESSION['mail_box_state']['sort'] !== 'date') {
        $params[] = 'sort=' . urlencode($_SESSION['mail_box_state']['sort']);
    }
    if (!empty($_SESSION['mail_box_state']['order']) && $_SESSION['mail_box_state']['order'] !== 'desc') {
        $params[] = 'order=' . urlencode($_SESSION['mail_box_state']['order']);
    }
    if (!empty($_SESSION['mail_box_state']['search'])) {
        $params[] = 'search=' . urlencode($_SESSION['mail_box_state']['search']);
    }
    if (!empty($_SESSION['mail_box_state']['company'])) {
        $params[] = 'company=' . urlencode($_SESSION['mail_box_state']['company']);
    }
    if (!empty($_SESSION['mail_box_state']['page']) && $_SESSION['mail_box_state']['page'] > 1) {
        $params[] = 'page=' . $_SESSION['mail_box_state']['page'];
    }
    
    if (!empty($params)) {
        $back_to_inbox_url .= '?' . implode('&', $params);
    }
}

$pagetitle = "Message - " . ($message['subject'] ?? 'Birthday Gold Mail');

// Add v7 theme CSS and custom styles
$additionalstyles = '<link rel="stylesheet" href="/public/css/v7/bg_theme.css">
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
        width: 64px;
        height: 64px;
        object-fit: cover;
        border-radius: 8px;
    }
    .message-frame {
        width: 100%;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        min-height: 500px;
        display: block;
    }
    
    /* Allow iframe to expand to content height */
    .security-card-body {
        padding: 1.5rem;
        overflow: visible;
    }
    
    /* Remove any height restrictions */
    .message-frame {
        height: auto;
        min-height: 500px;
    }
    
    /* Security Card Styles */
    .security-card {
        background: white;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 0;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .security-card-header {
        padding: 1.5rem;
        background: #e9ecef;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: nowrap;
        gap: 1rem;
    }
    
    .security-card-icon {
        font-size: 2rem;
        margin-right: 1rem;
        color: #495057;
    }
    
    .security-card-title {
        display: flex;
        align-items: center;
        margin: 0;
        flex-shrink: 1;
        min-width: 0;
    }
    
    .security-card-title h3 {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
        color: #212529;
        white-space: nowrap;
    }
    
    .security-card-body {
        padding: 1.5rem;
    }
    
    .message-meta {
        color: #6c757d;
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }
    
    .message-actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
</style>';

$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-envelope-open me-3"></i>Message</h1>
            <p class="lead mb-0">View your birthday reward message</p>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="container">
        <!-- Message Actions Bar -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="<?php echo htmlspecialchars($back_to_inbox_url); ?>" class="btn btn-outline-secondary" style="border-radius: 25px;">
                <i class="bi bi-arrow-left me-2"></i>Back to Inbox
            </a>
            <div class="message-actions">
                <button class="btn btn-primary" style="border-radius: 25px;" id="mark-unread-btn"
                        data-message-id="<?php echo htmlspecialchars($message_id_encoded); ?>"
                        data-server="<?php echo htmlspecialchars($mailserver_encoded); ?>">
                    <i class="bi bi-envelope me-2"></i>Mark as Unread
                </button>
                <button class="btn btn-danger" style="border-radius: 25px;" id="delete-message" 
                        data-message-id="<?php echo htmlspecialchars($message_id_encoded); ?>"
                        data-server="<?php echo htmlspecialchars($mailserver_encoded); ?>">
                    <i class="bi bi-trash me-2"></i>Delete
                </button>
            </div>
        </div>
        
        <!-- Message Card -->
        <div class="security-card">
            <div class="security-card-header">
                <div class="security-card-title">
                    <?php if (!empty($company['company_logo'])): ?>
                        <img src="<?php echo $display->companyimage($company['company_id'] . '/' . $company['company_logo']); ?>" 
                             class="company-logo me-3" alt="Company Logo">
                    <?php else: ?>
                        <div class="company-logo bg-secondary d-flex align-items-center justify-content-center me-3">
                            <i class="bi bi-cake text-white fs-2"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h3><?php echo htmlspecialchars($company['company_display_name'] ?? 'Birthday Reward Provider'); ?></h3>
                        <div class="message-meta mt-1">
                            <?php echo $display->formatdate($message['create_dt'], 'F j, Y g:i A'); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="security-card-body">
                <h4 class="mb-3 fw-bold"><?php echo htmlspecialchars($message['subject']); ?></h4>
                <?php
                /*
                <!-- Message Body -->
                <div class="message-body">
                <?php echo $message['body']; ?>
                </div>
                */
                echo '
                <!-- Message Body in iframe -->
                <iframe class="message-frame" id="message-iframe" srcdoc="' . htmlspecialchars($message['body']) . '" onload="resizeIframe(this)"></iframe>
                ';
                ?>
            </div>
        </div>
    </div>
</div>


<script>
// Store CSRF token for AJAX requests
const csrfToken = '<?php echo $display->input_csrftoken('tokenonly'); ?>';

// Function to resize iframe to content height
function resizeIframe(iframe) {
    try {
        // Wait for content to load
        setTimeout(function() {
            if (iframe.contentDocument && iframe.contentDocument.body) {
                // Add some padding to account for margins
                const contentHeight = iframe.contentDocument.body.scrollHeight + 20;
                iframe.style.height = contentHeight + 'px';
            }
        }, 100);
    } catch (e) {
        console.log('Unable to resize iframe:', e);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Also try to resize iframe after DOM is ready
    const iframe = document.getElementById('message-iframe');
    if (iframe) {
        resizeIframe(iframe);
    }
    
    // Mark as unread handler
    document.getElementById('mark-unread-btn').addEventListener('click', async function() {
        const messageId = this.dataset.messageId;
        const server = this.dataset.server;
        
        try {
            const response = await fetch('/api/messages/bulk-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    _token: csrfToken,
                    action: 'mark-unread',
                    messageIds: [messageId],
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
            showErrorModal('An error occurred while marking the message as unread: ' + error.message);
        }
    });
    
    // Delete message handler
    document.getElementById('delete-message').addEventListener('click', function() {
        const messageId = this.dataset.messageId;
        const server = this.dataset.server;
        
        // Create and show confirmation modal
        const modalHtml = `
            <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this message?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('deleteConfirmModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        modal.show();
        
        // Handle confirm button
        document.getElementById('confirmDelete').addEventListener('click', async function() {
            modal.hide();
            
            try {
                const response = await fetch('/api/messages/delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        _token: csrfToken,
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
                showErrorModal('An error occurred while deleting the message: ' + error.message);
            }
        });
        
        // Clean up modal after it's hidden
        document.getElementById('deleteConfirmModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    });
});

// Function to show error modal
function showErrorModal(message) {
    const modalHtml = `
        <div class="modal fade" id="errorModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Error</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            ${message}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('errorModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('errorModal'));
    modal.show();
    
    // Clean up after modal is hidden
    document.getElementById('errorModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}
</script>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>