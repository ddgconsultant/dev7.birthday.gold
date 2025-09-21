<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page configuration
$pagetitle = 'Import & Invite Friends';
$bodycontentclass = '';

// Handle form submission
$message = '';
$messageType = '';

if ($app->formposted() && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_invites') {
        // Get the contacts from form
        $contactsData = $_POST['contacts'] ?? [];
        $sentCount = 0;
        $skippedCount = 0;
        $referralcode = $account->manageReferralCode();
        
        foreach ($contactsData as $contactStr) {
            $contact = json_decode($contactStr, true);
            if (!$contact || empty($contact['email']) || empty($contact['name'])) {
                $skippedCount++;
                continue;
            }
            
            // Check if already invited
            $checkSql = "SELECT 1 FROM bg_user_attributes 
                         WHERE user_id = :user_id AND type = 'friend_invite' 
                         AND string_value = :email LIMIT 1";
            $checkStmt = $database->prepare($checkSql);
            $checkStmt->execute([
                ':user_id' => $current_user_data['user_id'],
                ':email' => $contact['email']
            ]);
            
            if ($checkStmt->fetch()) {
                $skippedCount++;
                continue; // Already invited
            }
            
            // Insert invite record
            $sql = "INSERT INTO bg_user_attributes (
                user_id, type, name, description, status, 
                create_dt, modify_dt, rank, grouping, category, string_value
            ) VALUES (
                :user_id, 'friend_invite', :name, :description, 'pending', 
                NOW(), NOW(), '100', 'bulk_import', 'friend_invite', :email
            )";
            
            $params = [
                ':user_id' => $current_user_data['user_id'],
                ':name' => $contact['name'],
                ':description' => 'Relationship: Friend, Email: ' . $contact['email'],
                ':email' => $contact['email']
            ];
            
            $stmt = $database->prepare($sql);
            $stmt->execute($params);
            
            // Send email
            $messageinput = [
                'from' => [$current_user_data['email'], $current_user_data['first_name'] . ' ' . $current_user_data['last_name']],
                'to' => 'CS birthday.gold',
                'toemail' => 'cs@birthday.gold',
                'subject' => 'An invite message for you from ' . $current_user_data['first_name'] . ' and Birthday.Gold',
                'body' => 'Hello,<br><br>' .
                          'You\'ve been invited by ' . $current_user_data['first_name'] . ' ' . $current_user_data['last_name'] . 
                          ' to join Birthday.Gold, the platform that celebrates YOU on your special day! 🎉<br><br>' .
                          '<strong>Details of the Invitation:</strong><br>' .
                          'Inviter Name: ' . $current_user_data['first_name'] . ' ' . $current_user_data['last_name'] . '<br>' .
                          'Inviter Email: ' . $current_user_data['email'] . '<br><br>' .
                          'At Birthday.Gold, you can receive amazing freebies and rewards from your favorite brands on your birthday!<br><br>' .
                          '<a href="https://birthday.gold/invitedby?' . $referralcode['code'] . '" style="color: #fff; background-color: #007bff; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Join Now</a><br><br>' .
                          'We look forward to celebrating with you! 🎁<br><br>' .
                          'Cheers,<br>' .
                          'The Birthday.Gold Team',
                'notification' => $contact['name'] . ' has been invited to Birthday.Gold'
            ];
            
            $mail->sendoutsidemessage($messageinput);
            $sentCount++;
        }
        
        if ($sentCount > 0) {
            $message = "Successfully sent $sentCount invitation(s)!";
            if ($skippedCount > 0) {
                $message .= " ($skippedCount already invited)";
            }
            $messageType = 'success';
        } else {
            $message = "No invitations were sent. Contacts may have already been invited.";
            $messageType = 'warning';
        }
    }
}

$additionalstyles = '
<style>
.import-container {
    max-width: 800px;
    margin: 0 auto;
}

.contact-entry {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.contact-entry input[type="checkbox"] {
    flex-shrink: 0;
}

.contact-info {
    flex: 1;
}

.contact-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.contact-email {
    font-size: 0.875rem;
    color: #6c757d;
}

.add-more-btn {
    margin-top: 1rem;
}

.preview-section {
    display: none;
    margin-top: 2rem;
}

.preview-section.show {
    display: block;
}

.empty-inputs {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}
</style>
';

$additionalscripts = '
<script>
let contactIndex = 3; // Start with 3 rows

function addContactRow() {
    const container = document.getElementById("contactInputs");
    const newRow = document.createElement("div");
    newRow.className = "row mb-2 contact-row";
    newRow.innerHTML = `
        <div class="col-md-5">
            <input type="text" class="form-control" name="names[]" placeholder="Name">
        </div>
        <div class="col-md-5">
            <input type="email" class="form-control" name="emails[]" placeholder="Email">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeContactRow(this)">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    `;
    container.appendChild(newRow);
    contactIndex++;
}

function removeContactRow(button) {
    button.closest(".contact-row").remove();
}

function previewContacts() {
    const names = document.querySelectorAll(\'input[name="names[]"]\');
    const emails = document.querySelectorAll(\'input[name="emails[]"]\');
    const previewList = document.getElementById("previewList");
    const previewSection = document.getElementById("previewSection");
    
    previewList.innerHTML = "";
    let hasValidContacts = false;
    
    for (let i = 0; i < names.length; i++) {
        const name = names[i].value.trim();
        const email = emails[i].value.trim();
        
        if (name && email && email.includes("@")) {
            hasValidContacts = true;
            const contactData = JSON.stringify({name: name, email: email});
            
            const entry = document.createElement("div");
            entry.className = "contact-entry";
            entry.innerHTML = `
                <input type="checkbox" class="form-check-input" name="contacts[]" value="${escapeHtml(contactData)}" checked>
                <div class="contact-info">
                    <div class="contact-name">${escapeHtml(name)}</div>
                    <div class="contact-email">${escapeHtml(email)}</div>
                </div>
            `;
            previewList.appendChild(entry);
        }
    }
    
    if (hasValidContacts) {
        previewSection.classList.add("show");
        document.getElementById("entrySection").style.display = "none";
    } else {
        alert("Please enter at least one valid contact with both name and email.");
    }
}

function backToEntry() {
    document.getElementById("previewSection").classList.remove("show");
    document.getElementById("entrySection").style.display = "block";
}

function escapeHtml(text) {
    const map = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        \'"\': "&quot;",
        "\'": "&#039;"
    };
    return text.replace(/[&<>"\']/g, m => map[m]);
}

// CSV Upload
function triggerCSVUpload() {
    document.getElementById("csvFile").click();
}

function handleCSVUpload(input) {
    const file = input.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const text = e.target.result;
        parseCSV(text);
    };
    reader.readAsText(file);
}

function parseCSV(text) {
    const lines = text.split("\\n");
    const contacts = [];
    
    // Clear existing inputs
    const container = document.getElementById("contactInputs");
    container.innerHTML = "";
    
    // Parse CSV and create inputs
    let addedCount = 0;
    for (let i = 0; i < lines.length && addedCount < 50; i++) {
        const line = lines[i].trim();
        if (!line) continue;
        
        const parts = line.split(",").map(p => p.trim());
        let name = "";
        let email = "";
        
        // Try to identify name and email
        parts.forEach(part => {
            if (part.includes("@") && !email) {
                email = part.replace(/["\']]/g, "");
            } else if (!name && part && !part.match(/^\d+$/)) {
                name = part.replace(/["\']]/g, "");
            }
        });
        
        if (name || email) {
            const newRow = document.createElement("div");
            newRow.className = "row mb-2 contact-row";
            newRow.innerHTML = `
                <div class="col-md-5">
                    <input type="text" class="form-control" name="names[]" placeholder="Name" value="${escapeHtml(name)}">
                </div>
                <div class="col-md-5">
                    <input type="email" class="form-control" name="emails[]" placeholder="Email" value="${escapeHtml(email)}">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeContactRow(this)">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
            addedCount++;
        }
    }
    
    if (addedCount > 0) {
        alert(`Imported ${addedCount} contacts from CSV. Please review and correct any errors.`);
    } else {
        alert("No valid contacts found in the CSV file.");
    }
}
</script>
';

// Display page
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-upload me-3"></i>Import & Invite Friends</h1>
            <p class="lead mb-0">Add multiple friends at once to send Birthday Gold invitations</p>
        </div>
    </div>
</div>

<div class="container my-5 pt-5">
    <div class="import-container">
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>-fill me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="/myaccount/friends-connect.php">
            <?php echo $display->input_csrftoken(); ?>
            <input type="hidden" name="action" value="send_invites">
            
            <!-- Entry Section -->
            <div id="entrySection">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Add Contacts</h5>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="triggerCSVUpload()">
                                <i class="bi bi-file-earmark-arrow-up me-1"></i>Upload CSV
                            </button>
                            <input type="file" id="csvFile" accept=".csv" style="display: none;" onchange="handleCSVUpload(this)">
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">Enter your friends' names and email addresses below. You can also upload a CSV file.</p>
                        
                        <div id="contactInputs">
                            <!-- Initial rows -->
                            <div class="row mb-2 contact-row">
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="names[]" placeholder="Name">
                                </div>
                                <div class="col-md-5">
                                    <input type="email" class="form-control" name="emails[]" placeholder="Email">
                                </div>
                                <div class="col-md-2"></div>
                            </div>
                            <div class="row mb-2 contact-row">
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="names[]" placeholder="Name">
                                </div>
                                <div class="col-md-5">
                                    <input type="email" class="form-control" name="emails[]" placeholder="Email">
                                </div>
                                <div class="col-md-2"></div>
                            </div>
                            <div class="row mb-2 contact-row">
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="names[]" placeholder="Name">
                                </div>
                                <div class="col-md-5">
                                    <input type="email" class="form-control" name="emails[]" placeholder="Email">
                                </div>
                                <div class="col-md-2"></div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-outline-secondary btn-sm add-more-btn" onclick="addContactRow()">
                            <i class="bi bi-plus-circle me-1"></i>Add More
                        </button>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="/myaccount/friends-list" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Back
                            </a>
                            <button type="button" class="btn btn-primary" onclick="previewContacts()">
                                <i class="bi bi-eye me-1"></i>Preview & Send
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Preview Section -->
            <div id="previewSection" class="preview-section">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Review Contacts</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Select the contacts you want to invite:</p>
                        <div id="previewList">
                            <!-- Contacts will be inserted here -->
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" onclick="backToEntry()">
                                <i class="bi bi-arrow-left me-1"></i>Back to Edit
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-1"></i>Send Invitations
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Privacy notice -->
        <div class="mt-5 text-center text-muted small">
            <i class="bi bi-shield-check me-1"></i>
            Your contacts are only used to send invitations and for your tracking purposes. No other information/messages will be sent to them.
        </div>
        
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>