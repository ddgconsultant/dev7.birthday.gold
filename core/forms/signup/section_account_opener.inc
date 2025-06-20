<?php
/**
 * Account Information Section Opener
 * 
 * This file opens the Account Information section that spans multiple subsections.
 * It should be included before credentials and closed after promo_codes.
 */

// No handler logic needed for opener
if (isset($process_handlers) && $process_handlers) {
    return;
}
?>
<!-- Account Information Section (spans multiple subsections) -->
<div class="card mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Account Information</h5>
        <i class="bi bi-info-circle text-muted" 
           style="font-size: 1rem; cursor: pointer;" 
           data-bs-toggle="modal" 
           data-bs-target="#accountInfoModal"
           title="Click for more information"></i>
    </div>
    <div class="card-body">
    
<!-- Account Info Modal -->
<div class="modal fade" id="accountInfoModal" tabindex="-1" aria-labelledby="accountInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountInfoModalLabel">
                    <i class="bi bi-person-circle text-primary me-2"></i>Account Information
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php 
                $account_type = $signup_process['account_type'] ?? 'user';
                
                if ($account_type === 'giftcertificate'): ?>
                    <h6 class="mb-3">Your Account as the Gift Buyer</h6>
                    <p>This section collects your information as the purchaser of the gift certificate. You'll need to provide:</p>
                    <ul>
                        <li><strong>Your Name:</strong> For account creation and purchase records</li>
                        <li><strong>Your Birthday:</strong> Required for all Birthday Gold accounts</li>
                        <li><strong>Contact Method:</strong> Email or phone number for account access and receipts</li>
                        <li><strong>Password:</strong> To secure your account and manage gift certificates</li>
                    </ul>
                    
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Why do I need an account?</strong> Your account allows you to track gift certificate delivery, reprint certificates, and purchase additional gifts in the future.
                    </div>
                    
                <?php elseif ($account_type === 'parental' || $account_type === 'family'): ?>
                    <h6 class="mb-3">Parent/Guardian Account</h6>
                    <p>As the parent or guardian, you'll manage birthday rewards for your family. You'll need:</p>
                    <ul>
                        <li><strong>Your Information:</strong> Name, birthday, and contact details</li>
                        <li><strong>Children's Information:</strong> Names and birthdates of family members</li>
                        <li><strong>Account Security:</strong> Password to protect family information</li>
                    </ul>
                    
                <?php elseif ($account_type === 'business'): ?>
                    <h6 class="mb-3">Business Account Representative</h6>
                    <p>As the business account manager, you'll need to provide:</p>
                    <ul>
                        <li><strong>Your Information:</strong> As the primary account contact</li>
                        <li><strong>Business Details:</strong> Company name and information</li>
                        <li><strong>Account Access:</strong> Email/phone and secure password</li>
                    </ul>
                    
                <?php else: ?>
                    <h6 class="mb-3">Personal Account Information</h6>
                    <p>To create your Birthday Gold account, you'll need to provide:</p>
                    <ul>
                        <li><strong>Full Name:</strong> As you'd like it to appear on birthday rewards</li>
                        <li><strong>Birthday:</strong> So we know when to celebrate with you!</li>
                        <li><strong>Contact Method:</strong> Email or phone number for account access</li>
                        <li><strong>Password:</strong> To keep your account secure</li>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>