<?php
// Password Strength Indicator
?>

<div class="mb-3">
    <label for="password" class="form-label">Password</label>
    <input type="password" id="password" class="form-control" placeholder="Enter your password" oninput="checkPasswordStrength()" />
    <div class="progress mt-2">
        <div id="passwordStrengthBar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    <small id="passwordStrengthMessage" class="form-text"></small>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    checkPasswordStrength(); // Evaluate the field on document load
});

function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthMessage = document.getElementById('passwordStrengthMessage');
    let strength = 0;

    // Calculate password strength
    if (password.length >= 8) strength += 1; // Minimum length
    if (/[A-Z]/.test(password)) strength += 1; // Uppercase letter
    if (/[a-z]/.test(password)) strength += 1; // Lowercase letter
    if (/[0-9]/.test(password)) strength += 1; // Numeric digit
    if (/[^A-Za-z0-9]/.test(password)) strength += 1; // Special character

    // Update progress bar and message
    switch (strength) {
        case 0:
            strengthBar.style.width = "0%";
            strengthBar.className = "progress-bar bg-danger";
            strengthMessage.textContent = "";
            break;
        case 1:
        case 2:
            strengthBar.style.width = "33%";
            strengthBar.className = "progress-bar bg-danger";
            strengthMessage.textContent = "Weak";
            break;
        case 3:
        case 4:
            strengthBar.style.width = "66%";
            strengthBar.className = "progress-bar bg-warning";
            strengthMessage.textContent = "Medium";
            break;
        case 5:
            strengthBar.style.width = "100%";
            strengthBar.className = "progress-bar bg-success";
            strengthMessage.textContent = "Strong";
            break;
    }
}
</script>
