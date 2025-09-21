 
<?PHP

$outputcontent .= '
<!-- Key Field -->
<div class="floating-label-group">
<input type="search" class="form-control floating-input" id="edit-username" name="username" placeholder=" " value="'.htmlspecialchars($row['encrypted_name'] ?? '').'">
<label for="edit-username" class="floating-label">Key</label>
</div>

<!-- Value Field -->
<div class="floating-label-group password-input-group">
<input type="password" class="form-control floating-input" id="edit-password" name="password" placeholder=" " autocomplete="new-password" value="'.htmlspecialchars($row['encrypted_value'] ?? '').'">
<label for="edit-password" class="floating-label">Value</label>
<button type="button" class="password-toggle-btn" onclick="togglePasswordField(\'edit-password\')">
<i class="bi bi-eye" id="edit-password-toggle-icon"></i>
</button>
</div>
<input type="hidden" id="hiddenFieldId" name="passwordstrength" value="100">
';