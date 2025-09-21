 
<?PHP

$outputcontent .= '
<!-- SSH Key Fields -->
<div class="mb-3">
<label for="edit-publickey" class="form-label">SSH Public Key</label>
<textarea class="form-control" id="edit-publickey" name="username" placeholder="Enter SSH Public Key" rows="5">'.htmlspecialchars($row['encrypted_name']).'</textarea>
</div>

<div class="mb-3">
<label for="edit-privatekey" class="form-label">SSH Private Key</label>
<textarea class="form-control" id="edit-privatekey" name="password" placeholder="Enter SSH Private Key" rows="5">'.htmlspecialchars($row['encrypted_value']).'</textarea>
</div>

<input type="hidden" id="hiddenFieldId" name="passwordstrength" value="100">
';