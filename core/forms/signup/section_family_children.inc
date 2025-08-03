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
<div class="card mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Children Information</h5>
        <i class="bi bi-info-circle text-muted" 
           style="font-size: 1rem; cursor: pointer;" 
           data-bs-toggle="modal" 
           data-bs-target="#childrenInfoModal"
           title="Click for more information"></i>
    </div>
    <div class="card-body">
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
            
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" 
                               class="form-control <?php echo !empty($errors["children_{$index}_firstname"]) ? 'is-invalid' : ''; ?>" 
                               name="children[<?php echo $index; ?>][firstname]" 
                               placeholder="First Name"
                               value="<?php echo htmlspecialchars($child['firstname'] ?? ''); ?>" 
                               required>
                        <label>First Name <span class="text-danger">*</span></label>
                        <?php if (!empty($errors["children_{$index}_firstname"])): ?>
                            <div class="invalid-feedback"><?php echo $errors["children_{$index}_firstname"]; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" 
                               class="form-control <?php echo !empty($errors["children_{$index}_lastname"]) ? 'is-invalid' : ''; ?>" 
                               name="children[<?php echo $index; ?>][lastname]" 
                               placeholder="Last Name"
                               value="<?php echo htmlspecialchars($child['lastname'] ?? ''); ?>" 
                               required>
                        <label>Last Name <span class="text-danger">*</span></label>
                        <?php if (!empty($errors["children_{$index}_lastname"])): ?>
                            <div class="invalid-feedback"><?php echo $errors["children_{$index}_lastname"]; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="date" 
                               class="form-control <?php echo !empty($errors["children_{$index}_birthday"]) ? 'is-invalid' : ''; ?>" 
                               name="children[<?php echo $index; ?>][birthday]" 
                               placeholder="Birthday"
                               value="<?php echo htmlspecialchars($child['birthday'] ?? ''); ?>" 
                               max="<?php echo date('Y-m-d'); ?>"
                               required>
                        <label>Birthday <span class="text-danger">*</span></label>
                        <?php if (!empty($errors["children_{$index}_birthday"])): ?>
                            <div class="invalid-feedback"><?php echo $errors["children_{$index}_birthday"]; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <button type="button" class="btn btn-outline-primary" id="add-child">
        <i class="bi bi-plus-circle"></i> Add Another Child
    </button>
    </div><!-- End card-body -->
</div><!-- End card -->

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

<!-- Children Info Modal -->
<div class="modal fade" id="childrenInfoModal" tabindex="-1" aria-labelledby="childrenInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="childrenInfoModalLabel">
                    <i class="bi bi-people text-primary me-2"></i>Children Information
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-3">Adding Your Children</h6>
                <p>For family accounts, you can add your children to enroll them in birthday reward programs. Each child will receive their own birthday rewards\!</p>
                
                <h6 class="mt-3">What We Need</h6>
                <ul>
                    <li><strong>Child&apos;s Name:</strong> First and last name for each child</li>
                    <li><strong>Birthday:</strong> So we know when to celebrate with them</li>
                    <li><strong>Age Requirements:</strong> Children must be under 18 years old</li>
                </ul>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note:</strong> You can add multiple children and manage their birthday rewards from your parent account.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>
