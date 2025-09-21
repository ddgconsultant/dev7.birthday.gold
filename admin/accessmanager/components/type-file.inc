<?PHP

$outputcontent .= '
<!-- File Fields -->
<div class="floating-label-group">
<input type="text" class="form-control floating-input" id="edit-file-title" name="file_title" placeholder=" " value="'.htmlspecialchars($row['encrypted_name'] ?? '').'">
<label for="edit-file-title" class="floating-label">File Title</label>
</div>

<div class="mb-3">
<label for="edit-file-upload" class="form-label">Upload File</label>
<input type="file" class="form-control" id="edit-file-upload" name="file_upload">
</div>
<input type="hidden" id="hiddenFieldId" name="passwordstrength" value="100">
';
