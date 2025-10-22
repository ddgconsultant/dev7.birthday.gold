<?php
include '../core/site-controller.php';

// Admin access check
if (!$account->isadmin()) {
    header('Location: /');
    exit;
}

// Handle approval/rejection actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $attribute_id = intval($_POST['attribute_id']);
    $action = $_POST['action'];
    $notes = $_POST['notes'] ?? '';
    
    if ($action == 'approve') {
        // Get claim details
        $sql = "SELECT ca.*, c.company_name 
                FROM bg_company_attributes ca
                JOIN bg_companies c ON ca.company_id = c.company_id
                WHERE ca.attribute_id = :attribute_id";
        $claim_record = $database->get_row($sql, ['attribute_id' => $attribute_id]);
        $claim = json_decode($claim_record['value'], true);
        
        if ($claim_record) {
            // Update claim status to active
            $claim['reviewed_by'] = $current_user_data['user_id'];
            $claim['review_date'] = date('Y-m-d H:i:s');
            $claim['notes'] = $notes;
            
            $sql = "UPDATE bg_company_attributes 
                    SET status = 'active',
                        value = :value,
                        modify_dt = NOW()
                    WHERE attribute_id = :attribute_id";
            $database->query($sql, [
                'value' => json_encode($claim),
                'attribute_id' => $attribute_id
            ]);
            
            // Add to user_attributes to link business to user
            $sql = "INSERT INTO bg_user_attributes 
                    (user_id, attribute_type, attribute_name, attribute_value, create_dt)
                    VALUES 
                    (:user_id, 'owned_business', :company_id, :company_name, NOW())";
            $database->query($sql, [
                'user_id' => $claim['user_id'],
                'company_id' => $claim_record['company_id'],
                'company_name' => $claim_record['company_name']
            ]);
            
            // Add ownership record (separate from claim)
            $sql = "INSERT INTO bg_company_attributes 
                    (company_id, type, name, value, description, status, create_dt)
                    VALUES 
                    (:company_id, 'ownership', :name, :value, :description, 'active', NOW())";
            $database->query($sql, [
                'company_id' => $claim_record['company_id'],
                'name' => 'owner_' . $claim['user_id'],
                'value' => json_encode([
                    'user_id' => $claim['user_id'],
                    'name' => $claim['user_name'],
                    'email' => $claim['user_email'],
                    'role' => 'owner',
                    'approved_date' => date('Y-m-d H:i:s'),
                    'approved_by' => $current_user_data['user_id']
                ]),
                'description' => 'Business owner: ' . $claim['user_name']
            ]);
            
            // Send email notification to user
            $subject = "Business Ownership Approved - " . $claim_record['company_name'];
            $message = "Dear {$claim['user_name']},\n\n";
            $message .= "Your ownership claim for {$claim_record['company_name']} has been approved.\n";
            $message .= "You can now access business marketing tools and analytics in your account.\n\n";
            $message .= "Best regards,\nBirthday.Gold Team";
            
            // Use your email sending method
            $system->sendemail($claim['user_email'], $subject, $message);
            
            $system->addmessage('success', 'Business ownership approved successfully.');
        }
        
    } elseif ($action == 'reject') {
        // Get claim details first
        $sql = "SELECT ca.*, c.company_name 
                FROM bg_company_attributes ca
                JOIN bg_companies c ON ca.company_id = c.company_id
                WHERE ca.attribute_id = :attribute_id";
        $claim_record = $database->get_row($sql, ['attribute_id' => $attribute_id]);
        $claim = json_decode($claim_record['value'], true);
        
        // Update claim status to rejected
        $claim['reviewed_by'] = $current_user_data['user_id'];
        $claim['review_date'] = date('Y-m-d H:i:s');
        $claim['notes'] = $notes;
        
        $sql = "UPDATE bg_company_attributes 
                SET status = 'rejected',
                    value = :value,
                    modify_dt = NOW()
                WHERE attribute_id = :attribute_id";
        $database->query($sql, [
            'value' => json_encode($claim),
            'attribute_id' => $attribute_id
        ]);
        
        if ($claim_record) {
            // Send rejection email
            $subject = "Business Ownership Claim Update - " . $claim_record['company_name'];
            $message = "Dear {$claim['user_name']},\n\n";
            $message .= "After reviewing your ownership claim for {$claim_record['company_name']}, ";
            $message .= "we need additional information or documentation.\n\n";
            if ($notes) {
                $message .= "Admin notes: {$notes}\n\n";
            }
            $message .= "Please contact support if you have questions.\n\n";
            $message .= "Best regards,\nBirthday.Gold Team";
            
            $system->sendemail($claim['user_email'], $subject, $message);
        }
        
        $system->addmessage('info', 'Business ownership claim rejected.');
    }
    
    header('Location: /admin/business-claims.php');
    exit;
}

// Get pending claims
$sql = "SELECT ca.attribute_id, ca.company_id, ca.value, ca.create_dt as claim_date,
        c.company_name, c.company_url
        FROM bg_company_attributes ca
        JOIN bg_companies c ON ca.company_id = c.company_id
        WHERE ca.type = 'ownership_claim'
        AND ca.status = 'pending'
        ORDER BY ca.create_dt DESC";
$pending_claims_raw = $database->get_rows($sql);

// Parse claim data
$pending_claims = [];
foreach ($pending_claims_raw as $claim_raw) {
    $claim_data = json_decode($claim_raw['value'], true);
    $pending_claims[] = array_merge($claim_raw, $claim_data);
}

// Get recent processed claims
$sql = "SELECT ca.attribute_id, ca.company_id, ca.value, ca.status, ca.modify_dt,
        c.company_name, c.company_url
        FROM bg_company_attributes ca
        JOIN bg_companies c ON ca.company_id = c.company_id
        WHERE ca.type = 'ownership_claim'
        AND ca.status IN ('active', 'rejected')
        ORDER BY ca.modify_dt DESC
        LIMIT 20";
$processed_claims_raw = $database->get_rows($sql);

// Parse claim data and get admin names
$processed_claims = [];
foreach ($processed_claims_raw as $claim_raw) {
    $claim_data = json_decode($claim_raw['value'], true);
    $processed_claim = array_merge($claim_raw, $claim_data);
    
    // Get admin name if reviewed
    if (!empty($claim_data['reviewed_by'])) {
        $admin = $database->get_row("SELECT firstname, lastname FROM bg_users WHERE user_id = :id", 
                                    ['id' => $claim_data['reviewed_by']]);
        $processed_claim['admin_firstname'] = $admin['firstname'] ?? '';
        $processed_claim['admin_lastname'] = $admin['lastname'] ?? '';
    }
    
    $processed_claims[] = $processed_claim;
}

$pagetitle = "Business Ownership Claims";
$additionalstyles = [];
$additionalscripts = [];

include $installpath . 'core/components/v3/bg_pagestart.inc';
include $installpath . 'core/components/v3/bg_header.inc';
?>

<div class="container mt-4">
    <h1>Business Ownership Claims</h1>
    
    <!-- Pending Claims -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Pending Claims (<?php echo count($pending_claims); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($pending_claims)): ?>
                <p class="text-muted">No pending business ownership claims.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Business</th>
                                <th>Proof</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_claims as $claim): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($claim['claim_date'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($claim['user_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($claim['user_email']); ?></small><br>
                                        <small class="text-muted">User ID: <?php echo $claim['user_id']; ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($claim['company_name']); ?></strong><br>
                                        <small><a href="<?php echo htmlspecialchars($claim['company_url']); ?>" target="_blank">
                                            <?php echo htmlspecialchars($claim['company_url']); ?>
                                        </a></small>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" 
                                                data-bs-target="#claimModal<?php echo $claim['attribute_id']; ?>">
                                            View Details
                                        </button>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $session->get_csrf_token(); ?>">
                                            <input type="hidden" name="attribute_id" value="<?php echo $claim['attribute_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="bi bi-check-circle"></i> Approve
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                                data-bs-target="#rejectModal<?php echo $claim['attribute_id']; ?>">
                                            <i class="bi bi-x-circle"></i> Reject
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Details Modal -->
                                <div class="modal fade" id="claimModal<?php echo $claim['attribute_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Ownership Claim Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <h6>Proof of Ownership:</h6>
                                                <p><?php echo nl2br(htmlspecialchars($claim['ownership_proof'])); ?></p>
                                                
                                                <h6>Contact Information:</h6>
                                                <p><?php echo nl2br(htmlspecialchars($claim['contact_info'])); ?></p>
                                                
                                                <?php if ($claim['proof_document']): ?>
                                                    <h6>Uploaded Document:</h6>
                                                    <a href="/uploads/business_claims/<?php echo $claim['proof_document']; ?>" 
                                                       target="_blank" class="btn btn-sm btn-secondary">
                                                        <i class="bi bi-file-earmark"></i> View Document
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Reject Modal -->
                                <div class="modal fade" id="rejectModal<?php echo $claim['attribute_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reject Ownership Claim</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $session->get_csrf_token(); ?>">
                                                    <input type="hidden" name="attribute_id" value="<?php echo $claim['attribute_id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    
                                                    <div class="mb-3">
                                                        <label for="notes<?php echo $claim['attribute_id']; ?>" class="form-label">
                                                            Reason for Rejection (will be sent to user)
                                                        </label>
                                                        <textarea class="form-control" id="notes<?php echo $claim['attribute_id']; ?>" 
                                                                  name="notes" rows="3" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Reject Claim</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Processed Claims -->
    <div class="card">
        <div class="card-header">
            <h5>Recently Processed Claims</h5>
        </div>
        <div class="card-body">
            <?php if (empty($processed_claims)): ?>
                <p class="text-muted">No processed claims yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Review Date</th>
                                <th>User</th>
                                <th>Business</th>
                                <th>Status</th>
                                <th>Reviewed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($processed_claims as $claim): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($claim['review_date'] ?? $claim['modify_dt'])); ?></td>
                                    <td><?php echo htmlspecialchars($claim['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($claim['company_name']); ?></td>
                                    <td>
                                        <?php if ($claim['status'] == 'active'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($claim['admin_firstname'] . ' ' . $claim['admin_lastname']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include $installpath . 'core/components/v3/bg_footer.inc';
?>