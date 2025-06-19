<?php
/**
 * Name and Birthday Form Module
 * Common fields used by all account types
 * 
 * Expected variables:
 * - $account_type: 'user', 'family', or 'business'
 * - $_POST: form submission data
 * - $errors: array of validation errors
 */

// Ensure we have access to the months array
$months = [
    '01' => '01 - January', '02' => '02 - February', '03' => '03 - March',
    '04' => '04 - April', '05' => '05 - May', '06' => '06 - June',
    '07' => '07 - July', '08' => '08 - August', '09' => '09 - September',
    '10' => '10 - October', '11' => '11 - November', '12' => '12 - December'
];
?>

<!-- Name Section -->
<div class="form-section">
    <h5 class="section-title">
        <?php 
        if ($account_type == 'business') {
            echo 'Business Information';
        } elseif ($account_type == 'family') {
            echo 'Parent/Guardian Information';
        } else {
            echo 'Your Name and Birthday';
        }
        ?>
    </h5>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="firstname" class="form-label">
                <?php echo ($account_type == 'business') ? 'Contact ' : ''; ?>First Name <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" id="firstname" name="firstname" 
                   value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>">
        </div>
        
        <div class="col-md-6 mb-3">
            <label for="lastname" class="form-label">
                <?php echo ($account_type == 'business') ? 'Contact ' : ''; ?>Last Name <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" id="lastname" name="lastname" 
                   value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>">
        </div>
    </div>
  
    <div class="row">
        <div class="col-md-12">
            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
            <div class="d-flex gap-2" style="max-width: 400px;">
                <div class="flex-fill">
                    <select class="form-control" id="birth_month" name="birth_month">
                        <option value="">Month</option>
                        <?php
                        $selected_month = $_POST['birth_month'] ?? '';
                        if (empty($selected_month) && !empty($_POST['birthday'])) {
                            $selected_month = date('m', strtotime($_POST['birthday']));
                        }
                        foreach ($months as $value => $label) {
                            $selected = ($selected_month == $value) ? 'selected' : '';
                            echo "<option value=\"$value\" $selected>$label</option>";
                        }
                        ?>
                    </select>
                </div>
                <div style="width: 95px;">
                    <select class="form-control" id="birth_day" name="birth_day">
                        <option value="">Day</option>
                        <?php
                        $selected_day = $_POST['birth_day'] ?? '';
                        if (empty($selected_day) && !empty($_POST['birthday'])) {
                            $selected_day = date('d', strtotime($_POST['birthday']));
                        }
                        for ($i = 1; $i <= 31; $i++) {
                            $day = str_pad($i, 2, '0', STR_PAD_LEFT);
                            $selected = ($selected_day == $day) ? 'selected' : '';
                            echo "<option value=\"$day\" $selected>$i</option>";
                        }
                        ?>
                    </select>
                </div>
                <div style="width: 120px;">
                    <select class="form-control" id="birth_year" name="birth_year">
                        <option value="">Year</option>
                        <?php
                        $current_year = date('Y');
                        $selected_year = $_POST['birth_year'] ?? '';
                        if (empty($selected_year) && !empty($_POST['birthday'])) {
                            $selected_year = date('Y', strtotime($_POST['birthday']));
                        }
                        $min_age_year = $current_year - 13;
                        for ($i = $current_year; $i >= 1900; $i--) {
                            $selected = ($selected_year == $i) ? 'selected' : '';
                            $class = ($i > $min_age_year) ? 'class="text-danger"' : '';
                            echo "<option value=\"$i\" $selected $class>$i</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <small class="text-muted">
                <?php if ($account_type == 'family'): ?>
                    Parent/Guardian birth date for account verification
                <?php else: ?>
                    We'll use this to notify you of birthday rewards
                <?php endif; ?>
            </small>
            <!-- Hidden field for combined birthday value -->
            <input type="hidden" id="birthday" name="birthday" value="<?php echo htmlspecialchars($_POST['birthday'] ?? ''); ?>">
        </div>
        <div class="col-md-6">
            <!-- Empty column for balance on larger screens -->
            <input type="hidden" id="altContact" name="alt_contact" value="">
        </div>
    </div>
</div>