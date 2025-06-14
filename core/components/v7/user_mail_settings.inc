<h5 class="mb-0 mt-5">Online Mail Settings</h5>
<p>Manage your email preferences and notifications</p>
<hr class="my-4" />
<strong class="mb-0">Email Notifications</strong>
<p>Select the types of emails you want to receive.</p>
<div class="list-group mb-5 shadow">
    <div class="list-group-item">
        <div class="row align-items-center">
            <div class="col">
                <strong class="mb-0">Promotional Emails</strong>
                <p class="text-muted mb-0">Receive promotional emails about new products, special offers, and discounts directly to your inbox.</p>
            </div>

            <div class="col-auto">
                <div class="form-check form-switch">
                    <input class="form-check-input primary-switch" type="checkbox" id="email_promo" checked data-email="promotional_emails">
                    <label class="form-check-label" for="email_promo"></label>
                </div>
            </div>
        </div>
    </div>
    <div class="list-group-item">
        <div class="row align-items-center">
            <div class="col">
                <strong class="mb-0">Transactional Emails</strong>
                <p class="text-muted mb-0">Receive email receipts, order confirmations, and other transactional messages.</p>
            </div>

            <div class="col-auto">
                <div class="form-check form-switch">
                    <input class="form-check-input primary-switch" type="checkbox" id="email_transactional" checked data-email="transactional_emails">
                    <label class="form-check-label" for="email_transactional"></label>
                </div>
            </div>
        </div>
    </div>
    <div class="list-group-item">
        <div class="row align-items-center">
            <div class="col">
                <strong class="mb-0">Newsletter Subscriptions</strong>
                <p class="text-muted mb-0">Subscribe to our monthly newsletters and stay informed about the latest updates and events.</p>
            </div>

            <div class="col-auto">
                <div class="form-check form-switch">
                    <input class="form-check-input primary-switch" type="checkbox" id="email_newsletter" data-email="newsletter_subscriptions">
                    <label class="form-check-label" for="email_newsletter"></label>
                </div>
            </div>
        </div>
    </div>
</div>

<hr class="my-4" />
<strong class="mb-0">Email Management</strong>
<p>Customize your email delivery options.</p>
<div class="list-group mb-5 shadow">
    <div class="list-group-item">
        <div class="row align-items-center">
            <div class="col">
                <strong class="mb-0">Daily Summary</strong>
                <p class="text-muted mb-0">Receive a daily summary of all important updates and notifications.</p>
            </div>

            <div class="col-auto">
                <div class="form-check form-switch">
                    <input class="form-check-input primary-switch" type="checkbox" id="email_daily_summary" data-email="daily_summary">
                    <label class="form-check-label" for="email_daily_summary"></label>
                </div>
            </div>
        </div>
    </div>
    <div class="list-group-item">
        <div class="row align-items-center">
            <div class="col">
                <strong class="mb-0">Weekly Digest</strong>
                <p class="text-muted mb-0">Opt-in for a weekly digest summarizing the key events and updates from the past week.</p>
            </div>

            <div class="col-auto">
                <div class="form-check form-switch">
                    <input class="form-check-input primary-switch" type="checkbox" id="email_weekly_digest" data-email="weekly_digest">
                    <label class="form-check-label" for="email_weekly_digest"></label>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.primary-switch').forEach(function(switchElement) {
    switchElement.addEventListener('change', function() {
        var emailType = this.dataset.email;
        var isChecked = this.checked ? 1 : 0;

        // Send AJAX request to update the setting in the database
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/myaccount/myaccount_actions/update_mail_settings', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                console.log('Mail setting updated successfully.');
            }
        };
        xhr.send('emailType=' + emailType + '&isChecked=' + isChecked + '&_token=' + '<?php echo $display->inputcsrf_token('tokenonly'); ?>');
    });
});
</script>
