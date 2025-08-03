    <h6 class="mb-0 text-muted">Security</h6>
    <p>Control security alerts you will be notified about.</p>
    <div class="list-group mb-5 shadow">
        <div class="list-group-item">
            <div class="row align-items-center">
                <div class="col">
                    <div class="mb-0 fw-bold">Unusual Activity Notifications</div>
                    <p class="text-muted mb-0 small">Get alerts when unusual activity is detected on your account, helping you to quickly respond to potential security threats.</p>
                </div>

                <div class="col-auto">
                    <div class="form-check form-switch">
                        <input class="form-check-input primary-switch" type="checkbox" id="alert1" checked data-alert="unusual_activity">
                        <label class="form-check-label" for="alert1"></label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <hr class="my-4" />
    <h6 class="mb-0 text-muted">System</h6>
    <p>Please enable system alerts you will get.</p>
    <div class="list-group mb-5 shadow">
        <div class="list-group-item">
            <div class="row align-items-center">
                <div class="col">
                    <div class="mb-0 fw-bold">Notify me about new features and updates</div>
                    <p class="text-muted mb-0 small">Receive notifications about new features, enhancements, and important updates to keep your experience up-to-date.</p>
                </div>

                <div class="col-auto">
                    <div class="form-check form-switch">
                        <input class="form-check-input primary-switch" type="checkbox" id="alert3" checked data-alert="new_features">
                        <label class="form-check-label" for="alert3"></label>
                    </div>
                </div>
            </div>
        </div>
        <div class="list-group-item">
            <div class="row align-items-center">
                <div class="col">
                    <div class="mb-0 fw-bold">Notify me by email for the latest news</div>
                    <p class="text-muted mb-0 small">Stay updated with the latest news and updates by receiving email notifications directly to your inbox.</p>
                </div>

                <div class="col-auto">
                    <div class="form-check form-switch">
                        <input class="form-check-input primary-switch" type="checkbox" id="alert4" checked data-alert="email_news">
                        <label class="form-check-label" for="alert4"></label>
                    </div>
                </div>
            </div>
        </div>
        <div class="list-group-item">
            <div class="row align-items-center">
                <div class="col">
                    <div class="mb-0 fw-bold">Notify me about tips on using my account</div>
                    <p class="text-muted mb-0 small">Receive helpful tips and guidance on how to effectively use your account features and settings.</p>
                </div>

                <div class="col-auto">
                    <div class="form-check form-switch">
                        <input class="form-check-input primary-switch" type="checkbox" id="alert5" data-alert="account_tips">
                        <label class="form-check-label" for="alert5"></label>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
document.querySelectorAll('.primary-switch').forEach(function(switchElement) {
    switchElement.addEventListener('change', function() {
        var alertType = this.dataset.alert;
        var isChecked = this.checked ? 1 : 0;

        // Send AJAX request to update the setting in the database
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/myaccount/myaccount_actions/update_notification_settings', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                console.log('Notification setting updated successfully.');
            }
        };
        xhr.send('alertType=' + alertType + '&isChecked=' + isChecked + '&_token=' + '<?php echo $display->inputcsrf_token('tokenonly'); ?>');
    });
});
</script>
