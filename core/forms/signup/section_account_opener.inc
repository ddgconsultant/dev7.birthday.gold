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
                    <h6 class="mb-3">Gift Buyer Account Setup</h6>
                    <p>Set up your account to manage gift certificates:</p>
                    
                    <h6 class="mt-3">Login Credentials</h6>
                    <ul>
                        <li><strong>Email or Phone:</strong> For account access and gift receipts</li>
                        <li><strong>Password:</strong> To secure your gift management dashboard</li>
                    </ul>
                    
                    <h6 class="mt-3">Optional</h6>
                    <ul>
                        <li><strong>Promo Code:</strong> Apply any discount codes you have</li>
                        <li><strong>Referral:</strong> Credit the person who told you about us</li>
                    </ul>
                    
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-gift me-2"></i>
                        <strong>Note:</strong> After creating your account, you'll enter the gift recipient's details on the next page.
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
                    <h6 class="mb-3">Your Account Setup</h6>
                    <p>This section covers your login credentials and account preferences.</p>
                    
                    <div class="alert alert-success mb-3">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>Flexible Access:</strong> These can all be changed by you at anytime, and you can use your phone number, email address or username to log into your account.
                    </div>
                    
                    <h6 class="mt-3">Login Information</h6>
                    <ul>
                        <li><strong>Email or Phone:</strong> Choose which to use as your login method</li>
                        <li><strong>Password:</strong> Create a secure password (8+ characters)</li>
                    </ul>
                    
                    <h6 class="mt-3">Optional Features</h6>
                    <ul>
                        <li><strong>Promo Code:</strong> If you have a discount code, apply it here</li>
                        <li><strong>Referral Code:</strong> Enter if someone referred you to Birthday Gold</li>
                    </ul>
                    
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-lightbulb me-2"></i>
                        <strong>Tip:</strong> You can use either email or phone to log in. Choose whichever you prefer!
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>