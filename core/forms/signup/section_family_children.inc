<?php
/**
 * Family Children Section with Handler
 * 
 * This section handles adding and validating children for family/parental accounts.
 * Uses the existing Birthday Gold UI patterns
 * 
 * Expected variables:
 * - $values: Array of form values
 * - $errors: Array of validation errors
 * - $signup_process: Session data for signup process
 * - $process_handlers: Boolean flag indicating if handlers should process
 */

#-------------------------------------------------------------------------------
# HANDLER CODE - Process validation if flag is set
#-------------------------------------------------------------------------------
if (isset($process_handlers) && $process_handlers) {
    // Only validate children if this is a family account
    $account_type = $signup_process['account_type'] ?? 'user';
    if ($account_type !== 'parental' && $account_type !== 'family') {
        return;
    }
    
    // Check if children array exists
    if (!isset($values['children']) || !is_array($values['children'])) {
        $values['children'] = [];
    }
    
    // Family accounts must have at least one child
    if (empty($values['children'])) {
        $errors['children'] = 'Please add at least one child for a family account';
        return;
    }
    
    // Validate each child
    foreach ($values['children'] as $index => $child) {
        // First name validation
        if (empty($child['firstname'])) {
            $errors["children.$index.firstname"] = 'Child\'s first name is required';
        } elseif (!preg_match('/^[a-zA-Z\s\'-]+$/', $child['firstname'])) {
            $errors["children.$index.firstname"] = 'Child\'s first name contains invalid characters';
        }
        
        // Last name validation
        if (empty($child['lastname'])) {
            $errors["children.$index.lastname"] = 'Child\'s last name is required';
        } elseif (!preg_match('/^[a-zA-Z\s\'-]+$/', $child['lastname'])) {
            $errors["children.$index.lastname"] = 'Child\'s last name contains invalid characters';
        }
        
        // Birthday validation
        if (empty($child['birthday'])) {
            $errors["children.$index.birthday"] = 'Child\'s birthday is required';
        } else {
            $birthdate = DateTime::createFromFormat('Y-m-d', $child['birthday']);
            if (!$birthdate) {
                $errors["children.$index.birthday"] = 'Please enter a valid date';
            } else {
                $age = $birthdate->diff(new DateTime())->y;
                $months = $birthdate->diff(new DateTime())->m;
                
                // Check if child is under 18
                if ($age >= 18) {
                    $errors["children.$index.birthday"] = 'Children must be under 18 years old';
                } 
                // Check if birthday is in the future
                elseif ($birthdate > new DateTime()) {
                    $errors["children.$index.birthday"] = 'Birthday cannot be in the future';
                }
                // Optional: Check minimum age (e.g., at least 1 year old)
                elseif ($age < 1 && $months < 12) {
                    $errors["children.$index.birthday"] = 'Child must be at least 1 year old';
                }
            }
        }
    }
    
    // Check for duplicate children (same name and birthday)
    $childrenHashes = [];
    foreach ($values['children'] as $index => $child) {
        if (!empty($child['firstname']) && !empty($child['lastname']) && !empty($child['birthday'])) {
            $hash = strtolower($child['firstname'] . $child['lastname'] . $child['birthday']);
            if (in_array($hash, $childrenHashes)) {
                $errors["children.$index.firstname"] = 'Duplicate child detected';
            } else {
                $childrenHashes[] = $hash;
            }
        }
    }
    
    // Exit if only processing handlers
    return;
}

#-------------------------------------------------------------------------------
# DISPLAY CODE - Show the form section
#-------------------------------------------------------------------------------
// Get children data from values
$children = $values['children'] ?? [];
?>
<div class="mb-4">
    <h4 class="mb-3">Children Information</h4>
    <p class="text-muted">Add your children to enroll them in birthday programs</p>
    
    <div id="children-container">
        <?php 
        $children = $values['children'] ?? [];
        if (empty($children)) {
            $children = [['firstname' => '', 'lastname' => '', 'birthday' => '']];
        }
        
        foreach ($children as $index => $child): 
        ?>
        <div class="child-entry border rounded p-3 mb-3" data-child-index="<?php echo $index; ?>">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Child <?php echo $index + 1; ?></h5>
                <?php if ($index > 0): ?>
                <button type="button" class="btn btn-sm btn-outline-danger remove-child">
                    <i class="bi bi-trash"></i> Remove
                </button>
                <?php endif; ?>
            </div>
            
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">First Name</label>
                    <input type="text" 
                           class="form-control" 
                           name="children[<?php echo $index; ?>][firstname]" 
                           value="<?php echo htmlspecialchars($child['firstname'] ?? ''); ?>" 
                           required>
                    <?php if (!empty($errors["children_{$index}_firstname"])): ?>
                        <div class="text-danger small mt-1"><?php echo $errors["children_{$index}_firstname"]; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Last Name</label>
                    <input type="text" 
                           class="form-control" 
                           name="children[<?php echo $index; ?>][lastname]" 
                           value="<?php echo htmlspecialchars($child['lastname'] ?? ''); ?>" 
                           required>
                    <?php if (!empty($errors["children_{$index}_lastname"])): ?>
                        <div class="text-danger small mt-1"><?php echo $errors["children_{$index}_lastname"]; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Birthday</label>
                    <input type="date" 
                           class="form-control" 
                           name="children[<?php echo $index; ?>][birthday]" 
                           value="<?php echo htmlspecialchars($child['birthday'] ?? ''); ?>" 
                           max="<?php echo date('Y-m-d'); ?>"
                           required>
                    <?php if (!empty($errors["children_{$index}_birthday"])): ?>
                        <div class="text-danger small mt-1"><?php echo $errors["children_{$index}_birthday"]; ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <button type="button" class="btn btn-outline-primary" id="add-child">
        <i class="bi bi-plus-circle"></i> Add Another Child
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('children-container');
    const addButton = document.getElementById('add-child');
    
    let childCount = <?php echo count($children); ?>;
    
    addButton.addEventListener('click', function() {
        const newIndex = childCount;
        const newChild = document.createElement('div');
        newChild.className = 'child-entry border rounded p-3 mb-3';
        newChild.dataset.childIndex = newIndex;
        
        newChild.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Child ${newIndex + 1}</h5>
                <button type="button" class="btn btn-sm btn-outline-danger remove-child">
                    <i class="bi bi-trash"></i> Remove
                </button>
            </div>
            
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">First Name</label>
                    <input type="text" 
                           class="form-control" 
                           name="children[${newIndex}][firstname]" 
                           required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Last Name</label>
                    <input type="text" 
                           class="form-control" 
                           name="children[${newIndex}][lastname]" 
                           required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Birthday</label>
                    <input type="date" 
                           class="form-control" 
                           name="children[${newIndex}][birthday]" 
                           max="${new Date().toISOString().split('T')[0]}"
                           required>
                </div>
            </div>
        `;
        
        container.appendChild(newChild);
        childCount++;
        
        // Add event listener to new remove button
        newChild.querySelector('.remove-child').addEventListener('click', function() {
            newChild.remove();
            updateChildNumbers();
        });
    });
    
    // Add event listeners to existing remove buttons
    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-child')) {
            e.target.closest('.child-entry').remove();
            updateChildNumbers();
        }
    });
    
    function updateChildNumbers() {
        const children = container.querySelectorAll('.child-entry');
        children.forEach((child, index) => {
            child.querySelector('h5').textContent = `Child ${index + 1}`;
            child.dataset.childIndex = index;
            
            // Update input names
            child.querySelectorAll('input').forEach(input => {
                const name = input.name;
                input.name = name.replace(/children\[\d+\]/, `children[${index}]`);
            });
        });
        childCount = children.length;
    }
});
</script>