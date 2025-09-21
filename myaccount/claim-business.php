<?php
include '../core/site-controller.php';

// Check if user has business account
$user_data = $account->getuser();
if ($user_data['account_type'] != 'business') {
    $system->addmessage('error', 'You need a business account to claim a business. Please upgrade your account first.');
    header('Location: /myaccount/upgrade-account.php');
    exit;
}

// Get list of unclaimed businesses
$sql = "SELECT c.company_id, c.company_name, c.company_url, c.status
        FROM bg_companies c
        WHERE c.company_id NOT IN (
            SELECT company_id FROM bg_company_attributes 
            WHERE type = 'ownership_claim' 
            AND status IN ('active', 'pending')
        )
        AND c.status = 'active'
        ORDER BY c.company_name ASC";
$available_businesses = $database->get_rows($sql);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_id = intval($_POST['company_id']);
    $ownership_proof = $_POST['ownership_proof'];
    $contact_info = $_POST['contact_info'];
    
    // Validate inputs
    if (!$company_id || !$ownership_proof || !$contact_info) {
        $system->addmessage('error', 'Please fill in all required fields.');
    } else {
        // Check if company exists and is unclaimed
        $sql = "SELECT c.company_id 
                FROM bg_companies c
                WHERE c.company_id = :company_id
                AND c.company_id NOT IN (
                    SELECT company_id FROM bg_company_attributes 
                    WHERE type = 'ownership_claim' 
                    AND status IN ('active', 'pending')
                )";
        $valid = $database->get_row($sql, ['company_id' => $company_id]);
        
        if (!$valid) {
            $system->addmessage('error', 'This business is not available for claiming.');
        } else {
            // Handle file upload if provided
            $uploaded_file = null;
            if (!empty($_FILES['proof_document']['name'])) {
                $upload_dir = '../uploads/business_claims/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_ext = pathinfo($_FILES['proof_document']['name'], PATHINFO_EXTENSION);
                $file_name = $user_data['user_id'] . '_' . $company_id . '_' . time() . '.' . $file_ext;
                $upload_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['proof_document']['tmp_name'], $upload_path)) {
                    $uploaded_file = $file_name;
                }
            }
            
            // Create ownership claim record in bg_company_attributes
            $claim_data = [
                'user_id' => $user_data['user_id'],
                'user_name' => $user_data['firstname'] . ' ' . $user_data['lastname'],
                'user_email' => $user_data['email'],
                'ownership_proof' => $ownership_proof,
                'contact_info' => $contact_info,
                'proof_document' => $uploaded_file,
                'claim_date' => date('Y-m-d H:i:s'),
                'reviewed_by' => null,
                'review_date' => null,
                'notes' => null
            ];
            
            $sql = "INSERT INTO bg_company_attributes 
                    (company_id, type, name, value, description, status, create_dt)
                    VALUES 
                    (:company_id, 'ownership_claim', :name, :value, :description, 'pending', NOW())";
            
            $params = [
                'company_id' => $company_id,
                'name' => 'claim_' . $user_data['user_id'] . '_' . time(),
                'value' => json_encode($claim_data),
                'description' => 'Ownership claim by ' . $user_data['firstname'] . ' ' . $user_data['lastname']
            ];
            
            $database->query($sql, $params);
            
            // Send notification to admin
            $company = $database->get_row("SELECT company_name FROM bg_companies WHERE company_id = :id", ['id' => $company_id]);
            $admin_message = "New business ownership claim:\n";
            $admin_message .= "User: {$user_data['firstname']} {$user_data['lastname']} ({$user_data['email']})\n";
            $admin_message .= "Business: {$company['company_name']}\n";
            $admin_message .= "Review at: https://dev7.birthday.gold/admin/business-claims.php";
            
            // Log admin notification (implement your notification method here)
            $system->send_admin_notification('Business Ownership Claim', $admin_message);
            
            $system->addmessage('success', 'Your ownership claim has been submitted for review. We will notify you once it has been processed.');
            header('Location: /myaccount/');
            exit;
        }
    }
}

$pagetitle = "Claim Business Ownership";
$additionalstyles = [];
$additionalscripts = [];

include $installpath . 'core/components/v3/bg_pagestart.inc';
include $installpath . 'core/components/v3/bg_header.inc';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="card">
                <div class="card-header">
                    <h4>Claim Business Ownership</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        If you are the owner or authorized representative of a business in our system, 
                        you can claim ownership to access marketing tools and analytics for your business.
                    </p>
                    
                    <?php if (empty($available_businesses)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            No businesses are currently available for claiming. 
                            If you cannot find your business, please contact support.
                        </div>
                    <?php else: ?>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo $session->get_csrf_token(); ?>">
                            
                            <div class="mb-3">
                                <label for="company_id" class="form-label">Select Business <span class="text-danger">*</span></label>
                                <select class="form-control" id="company_id" name="company_id" required>
                                    <option value="">-- Select a business --</option>
                                    <?php foreach ($available_businesses as $business): ?>
                                        <option value="<?php echo $business['company_id']; ?>">
                                            <?php echo htmlspecialchars($business['company_name']); ?>
                                            <?php if ($business['company_url']): ?>
                                                (<?php echo htmlspecialchars($business['company_url']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ownership_proof" class="form-label">
                                    Proof of Ownership <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="ownership_proof" name="ownership_proof" rows="4" required
                                          placeholder="Please describe your role and provide details that verify your ownership or authority over this business. Include your position, how long you have been with the company, and any relevant information."></textarea>
                                <small class="text-muted">
                                    Examples: Business registration number, tax ID, domain ownership, corporate email address, etc.
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="contact_info" class="form-label">
                                    Business Contact Information <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="contact_info" name="contact_info" rows="3" required
                                          placeholder="Provide your business email, phone number, and any other contact information we can use to verify your claim."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="proof_document" class="form-label">
                                    Upload Supporting Documentation (Optional)
                                </label>
                                <input type="file" class="form-control" id="proof_document" name="proof_document" 
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <small class="text-muted">
                                    You may upload business license, articles of incorporation, or other official documents. 
                                    Max file size: 5MB. Accepted formats: PDF, JPG, PNG, DOC, DOCX
                                </small>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <strong>Important:</strong> False claims of business ownership may result in account suspension. 
                                All claims are reviewed by our admin team and may require additional verification.
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit Ownership Claim
                                </button>
                                <a href="/myaccount/" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include $installpath . 'core/components/v3/bg_footer.inc';
?>