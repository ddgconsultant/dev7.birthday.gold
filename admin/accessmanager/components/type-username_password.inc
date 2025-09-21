 
<?PHP

$outputcontent .= '
<!-- Login URL Field -->
<div class="floating-label-group">
<input type="url" class="form-control floating-input" id="edit-host" name="host" placeholder=" " value="'.$row['host'].'">
<label for="edit-host" class="floating-label">Login URL</label>
</div>

<!-- Username Field -->
<div class="floating-label-group">
<input type="search" class="form-control floating-input" id="edit-username" name="username" placeholder=" " value="'.htmlspecialchars($row['encrypted_name']).'">
<label for="edit-username" class="floating-label">Username</label>
</div>

<!-- Password Field -->
<div class="floating-label-group password-input-group">
<input type="password" class="form-control floating-input" id="edit-password" name="password" placeholder=" " autocomplete="new-password" value="'.htmlspecialchars($row['encrypted_value']).'">
<label for="edit-password" class="floating-label">Password</label>
<button type="button" class="password-toggle-btn" onclick="togglePasswordField(\'edit-password\')">
<i class="bi bi-eye" id="edit-password-toggle-icon"></i>
</button>
</div>

<!-- Password Strength Indicator -->
<div class="mb-3" id="password-strength-container">
<label for="passwordStrengthIndicator" class="form-label">Password Strength</label>
<input type="hidden" id="hiddenFieldId" name="passwordstrength" value="">
<div id="passwordStrengthIndicator" class="progress">
<div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
</div>
</div>
';