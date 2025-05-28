 
<?PHP

$outputcontent .= '
<!-- Login URL Field -->
<div class="mb-3">
<label for="edit-host" class="form-label">Login URL</label>
<input type="url" class="form-control" id="edit-host" name="host" value="'.$row['host'].'">
</div>

<!-- Username Field -->
<div class="mb-3">
<label for="edit-username" class="form-label">Username</label>
<input type="search" class="form-control" id="edit-username" name="username" placeholder="Enter username" value="'.htmlspecialchars($row['encrypted_name']).'">
</div>

<!-- Password Field -->
<div class="mb-3">
<label for="edit-password" class="form-label">Password</label>
<input type="password" class="form-control" id="edit-password" name="password" placeholder="Enter password" autocomplete="new-password" value="'.htmlspecialchars($row['encrypted_value']).'">
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