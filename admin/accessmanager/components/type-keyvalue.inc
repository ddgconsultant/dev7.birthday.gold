 
<?PHP

$outputcontent .= '
<!-- Key Field -->
<div class="mb-3">
<label for="edit-username" class="form-label">Key</label>
<input type="search" class="form-control" id="edit-username" name="username" placeholder="Enter Key">
</div>

<!-- Value Field -->
<div class="mb-3">
<label for="edit-password" class="form-label">Value</label>
<input type="password" class="form-control" id="edit-password" name="password" placeholder="Enter Value" autocomplete="new-password">
</div>
<input type="hidden" id="hiddenFieldId" name="passwordstrength" value="100">
';