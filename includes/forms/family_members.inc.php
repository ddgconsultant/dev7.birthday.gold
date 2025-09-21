<?php
/**
 * Family Members Form Module
 * Child information fields for family accounts
 * 
 * Expected variables:
 * - $_POST: form submission data
 * - $errors: array of validation errors
 * - $months: array of month options (inherited from name_birthday.inc.php)
 */
?>

<!-- Family Account - Children Information -->
<div class="form-section">
    <h5 class="section-title">Children Information</h5>
    
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        You can add up to 4 children to your family account. Additional children can be added after registration.
    </div>
    
    <div id="childrenContainer">
        <!-- Dynamic children fields will be added here -->
        <?php
        // If we have existing child data from a failed submission, pre-populate
        if (!empty($_POST['child_firstname']) && is_array($_POST['child_firstname'])) {
            $child_count = count($_POST['child_firstname']);
            for ($i = 0; $i < $child_count; $i++) {
                $child_num = $i + 1;
                ?>
                <div class="child-entry mb-3 p-3 border rounded">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Child <?php echo $child_num; ?></h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-child" onclick="removeChild(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <input type="text" class="form-control" name="child_firstname[]" 
                                   placeholder="First Name" 
                                   value="<?php echo htmlspecialchars($_POST['child_firstname'][$i] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-2">
                            <input type="text" class="form-control" name="child_lastname[]" 
                                   placeholder="Last Name"
                                   value="<?php echo htmlspecialchars($_POST['child_lastname'][$i] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <label class="form-label small">Child's Birthday</label>
                            <div class="d-flex gap-2">
                                <select class="form-control" name="child_month[]">
                                    <option value="">Month</option>
                                    <?php 
                                    $selected_child_month = $_POST['child_month'][$i] ?? '';
                                    foreach ($months as $value => $label): 
                                    ?>
                                    <option value="<?php echo $value; ?>" <?php echo ($selected_child_month == $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select class="form-control" name="child_day[]" style="width: 80px;">
                                    <option value="">Day</option>
                                    <?php 
                                    $selected_child_day = $_POST['child_day'][$i] ?? '';
                                    for ($d = 1; $d <= 31; $d++): 
                                        $day_val = str_pad($d, 2, '0', STR_PAD_LEFT);
                                    ?>
                                    <option value="<?php echo $day_val; ?>" <?php echo ($selected_child_day == $day_val) ? 'selected' : ''; ?>><?php echo $d; ?></option>
                                    <?php endfor; ?>
                                </select>
                                <select class="form-control" name="child_year[]" style="width: 90px;">
                                    <option value="">Year</option>
                                    <?php 
                                    $selected_child_year = $_POST['child_year'][$i] ?? '';
                                    $child_current_year = date('Y');
                                    for ($y = $child_current_year; $y >= $child_current_year - 18; $y--): 
                                    ?>
                                    <option value="<?php echo $y; ?>" <?php echo ($selected_child_year == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>
    
    <button type="button" class="btn btn-outline-primary btn-sm" id="addChildBtn">
        <i class="bi bi-plus-circle me-2"></i>Add Child
    </button>
</div>

<!-- JavaScript for dynamic child fields -->
<script>
// Only initialize if we haven't already
if (typeof window.familyMembersInitialized === 'undefined') {
    window.familyMembersInitialized = true;
    
    document.addEventListener('DOMContentLoaded', function() {
        const childrenContainer = document.getElementById('childrenContainer');
        const addChildBtn = document.getElementById('addChildBtn');
        let childCount = childrenContainer ? childrenContainer.querySelectorAll('.child-entry').length : 0;
        const maxChildren = 4;
        
        // Update add button state based on existing children
        if (childCount >= maxChildren && addChildBtn) {
            addChildBtn.disabled = true;
            addChildBtn.textContent = 'Maximum children added';
        }
        
        function addChildField() {
            if (childCount >= maxChildren) {
                alert('You can add up to 4 children during registration. Additional children can be added later.');
                return;
            }
            
            childCount++;
            const childDiv = document.createElement('div');
            childDiv.className = 'child-entry mb-3 p-3 border rounded';
            childDiv.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Child ${childCount}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-child" onclick="removeChild(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <input type="text" class="form-control" name="child_firstname[]" placeholder="First Name">
                    </div>
                    <div class="col-md-6 mb-2">
                        <input type="text" class="form-control" name="child_lastname[]" placeholder="Last Name">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <label class="form-label small">Child's Birthday</label>
                        <div class="d-flex gap-2">
                            <select class="form-control" name="child_month[]">
                                <option value="">Month</option>
                                <?php foreach ($months as $value => $label): ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="form-control" name="child_day[]" style="width: 80px;">
                                <option value="">Day</option>
                                <?php for ($i = 1; $i <= 31; $i++): ?>
                                <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                            <select class="form-control" name="child_year[]" style="width: 90px;">
                                <option value="">Year</option>
                                <?php 
                                $child_current_year = date('Y');
                                for ($i = $child_current_year; $i >= $child_current_year - 18; $i--): 
                                ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
            `;
            
            childrenContainer.appendChild(childDiv);
            
            if (childCount >= maxChildren) {
                addChildBtn.disabled = true;
                addChildBtn.textContent = 'Maximum children added';
            }
        }
        
        window.removeChild = function(btn) {
            btn.closest('.child-entry').remove();
            childCount--;
            
            // Re-enable add button if under limit
            if (childCount < maxChildren && addChildBtn) {
                addChildBtn.disabled = false;
                addChildBtn.innerHTML = '<i class="bi bi-plus-circle me-2"></i>Add Child';
            }
            
            // Renumber remaining children
            const entries = childrenContainer.querySelectorAll('.child-entry');
            entries.forEach((entry, index) => {
                entry.querySelector('h6').textContent = `Child ${index + 1}`;
            });
        }
        
        if (addChildBtn) {
            addChildBtn.addEventListener('click', addChildField);
        }
    });
}
</script>