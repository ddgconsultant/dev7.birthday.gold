<?php 
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.createaccount.php');
include($_SERVER['DOCUMENT_ROOT'].'/claudecode/class.productmanager_promo.php');

// Initialize ProductManager with promo support
$productManager = new ProductManagerPromo($database, $qik);
$createaccount = new createaccount($database, $session);

#-------------------------------------------------------------------------------
# SIMULATE FAMILY ACCOUNT SIGNUP DATA
#-------------------------------------------------------------------------------
// For demo purposes, we'll simulate a family account selection
$signup_process = [
    'account_type' => 'parental',  // Changed from 'family' to 'parental'
    'account_plan' => 'family_premium',
    'account_plan_id' => 4, // Assuming family premium is ID 4
    'account_cost' => 1999, // $19.99
    'plandata' => [
        'name' => 'Family Premium',
        'features' => []
    ]
];

// In production, this would come from session
// $signup_process = $session->get('signup_process_data', []);

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$account_type = $signup_process['account_type'] ?? 'user';
$account_plan = $signup_process['account_plan'] ?? '';
$account_cost = $signup_process['account_cost'] ?? 0;

// Initialize variables for form display
$values = [];
$errors = [];
$processed = [];

#-------------------------------------------------------------------------------
# HANDLE FORM SUBMISSION
#-------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_form'])) {
    // Map display account types to form names
    $form_type_map = [
        'family' => 'parental',
        'parental' => 'parental',
        'business' => 'business',
        'user' => 'user'
    ];
    $form_type = $form_type_map[$account_type] ?? 'user';
    
    // Include the appropriate handler based on account type
    $handler_path = "/core/forms/signup/handler_{$form_type}_basic.inc";
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $handler_path)) {
        include($_SERVER['DOCUMENT_ROOT'] . $handler_path);
        
        // The handler will populate $errors and $processed arrays
        if (empty($errors)) {
            // Success - create account
            // In production, this would create the account and redirect
            $success_message = "Account successfully validated! (In production, this would create the account)";
            
            // For demo, show the processed data
            $demo_processed_data = $processed;
        } else {
            // Errors found - redisplay form with errors
            $values = $_POST; // Preserve form values
        }
    } else {
        $errors['system'] = "Form handler not found for account type: {$account_type}";
    }
} else {
    // Initial form display - check for promo/referral codes
    $values['promo_code'] = $_GET['promo'] ?? '';
    $values['referral_code'] = $_GET['ref'] ?? '';
}

#-------------------------------------------------------------------------------
# PAGE CONFIGURATION
#-------------------------------------------------------------------------------
$page_title = "Create Family Account - Birthday.Gold";
$page_description = "Sign up for a Birthday Gold family account";

#-------------------------------------------------------------------------------
# ADDITIONAL STYLES AND SCRIPTS
#-------------------------------------------------------------------------------
$additionalstyles = '
<link href="/claudecode/createaccount_styles.css" rel="stylesheet">
<style>
/* Additional styles specific to family form */
.child-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.child-card .remove-child {
    float: right;
    color: #dc3545;
    cursor: pointer;
}

.add-child-btn {
    border: 2px dashed #198754;
    background: #f8f9fa;
    color: #198754;
    padding: 1rem;
    text-align: center;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.add-child-btn:hover {
    background: #e8f5e8;
    border-color: #157347;
}
</style>
';

$additionalscripts = '
<script src="/claudecode/createaccount_flow.js"></script>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container main-content">
    <!-- Header -->
    <div class="header">
        <h1>Create Your Family Account</h1>
        <p>Manage birthday rewards for your entire family</p>
    </div>

    <!-- Progress Indicator -->
    <div class="progress-container">
        <div class="progress-steps">
            <div class="step-indicator completed">
                <i class="bi bi-check-circle-fill"></i>
                <span>Choose Plan</span>
            </div>
            <div class="step-indicator active">
                <i class="bi bi-person-circle"></i>
                <span>Account Details</span>
            </div>
            <div class="step-indicator">
                <i class="bi bi-credit-card"></i>
                <span>Payment</span>
            </div>
        </div>
    </div>

    <!-- Display any messages -->
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <h4 class="alert-heading">Success!</h4>
            <p><?php echo htmlspecialchars($success_message); ?></p>
            <?php if (!empty($demo_processed_data)): ?>
                <hr>
                <h5>Processed Data:</h5>
                <pre><?php echo htmlspecialchars(print_r($demo_processed_data, true)); ?></pre>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Please correct the following errors:</h4>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Plan Summary -->
    <div class="plan-summary mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4 class="mb-0"><?php echo htmlspecialchars($signup_process['plandata']['name'] ?? 'Family Premium'); ?></h4>
                <p class="text-muted mb-0">Manage birthday rewards for your entire family</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="plan-price" id="displayPrice">
                    $<?php echo number_format($account_cost / 100, 2); ?>
                </div>
                <small class="text-muted">per year</small>
            </div>
        </div>
    </div>

    <!-- Main Form -->
    <form id="detailsForm" method="POST" action="" novalidate>
        <input type="hidden" name="submit_form" value="1">
        <input type="hidden" name="account_type" value="<?php echo htmlspecialchars($account_type); ?>">
        
        <?php
        // Map display account types to form names
        $form_type_map = [
            'family' => 'parental',
            'parental' => 'parental',
            'business' => 'business',
            'user' => 'user'
        ];
        $form_type = $form_type_map[$account_type] ?? 'user';
        
        // Include the appropriate form display based on account type
        $form_path = "/core/forms/signup/form_{$form_type}_basic.inc";
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $form_path)) {
            // The form will use $values for pre-filling and $errors for validation display
            include($_SERVER['DOCUMENT_ROOT'] . $form_path);
        } else {
            // Fallback error message
            echo '<div class="alert alert-danger">Form template not found for account type: ' . htmlspecialchars($account_type) . '</div>';
        }
        ?>
        
        <!-- Form Navigation -->
        <div class="step-nav mt-4">
            <a href="/signup.php" class="btn btn-secondary-custom">
                <i class="bi bi-arrow-left me-2"></i>Back
            </a>
            <button type="submit" class="btn btn-primary-custom" id="continueBtn">
                Continue to Payment
                <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
    </form>
</div>

<!-- JavaScript for dynamic behavior -->
<script>
// Page-specific data for JavaScript
const pageData = {
    ajaxUrl: '/helper_checkavailability.php',
    originalPrice: <?php echo $account_cost; ?>,
    accountType: '<?php echo $account_type; ?>'
};

// Initialize any family-specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // This will be handled by the form's own JavaScript
    console.log('Family account form loaded');
});
</script>


<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>