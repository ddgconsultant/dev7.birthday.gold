    <h5 class="mb-0 mt-5">Notifications Settings</h5>
    <p>Select notifications you want to receive</p>
    <hr class="my-4" />
    <strong class="mb-0">Security</strong>
    <p>Control security alerts you will be notified about.</p>
    <div class="list-group mb-5 shadow">
        <div class="list-group-item">
            <div class="row align-items-center">
                <div class="col">
                    <strong class="mb-0">Unusual Activity Notifications</strong>
                    <p class="text-muted mb-0">Get alerts when unusual activity is detected on your account, helping you to quickly respond to potential security threats.</p>
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
    <strong class="mb-0">System</strong>
    <p>Please enable system alerts you will get.</p>
    <div class="list-group mb-5 shadow">
        <div class="list-group-item">
            <div class="row align-items-center">
                <div class="col">
                    <strong class="mb-0">Notify me about new features and updates</strong>
                    <p class="text-muted mb-0">Receive notifications about new features, enhancements, and important updates to keep your experience up-to-date.</p>
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
                    <strong class="mb-0">Notify me by email for the latest news</strong>
                    <p class="text-muted mb-0">Stay updated with the latest news and updates by receiving email notifications directly to your inbox.</p>
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
                    <strong class="mb-0">Notify me about tips on using my account</strong>
                    <p class="text-muted mb-0">Receive helpful tips and guidance on how to effectively use your account features and settings.</p>
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
