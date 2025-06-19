<?php 
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Simple test page to demonstrate the modular form system
$test_account_types = ['user', 'parental', 'business'];
$selected_type = $_GET['type'] ?? 'parental';

// Form type mapping
$form_type_map = [
    'family' => 'parental',
    'parental' => 'parental',
    'business' => 'business',
    'user' => 'user'
];

$form_type = $form_type_map[$selected_type] ?? 'user';

// Check if forms exist
$forms_info = [];
foreach ($test_account_types as $type) {
    $mapped_type = $form_type_map[$type] ?? $type;
    $form_path = "/core/forms/signup/form_{$mapped_type}_basic.inc";
    $handler_path = "/core/forms/signup/handler_{$mapped_type}_basic.inc";
    
    $forms_info[$type] = [
        'form_exists' => file_exists($_SERVER['DOCUMENT_ROOT'] . $form_path),
        'handler_exists' => file_exists($_SERVER['DOCUMENT_ROOT'] . $handler_path),
        'form_path' => $form_path,
        'handler_path' => $handler_path
    ];
}

$page_title = "Test Modular Forms - Birthday.Gold";
include($dir['core_components'] . '/bg_pagestart.inc');
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/claudecode/createaccount_styles.css" rel="stylesheet">
    <style>
        .test-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .form-preview {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            background: white;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Modular Form System Test</h1>
        
        <!-- Form Status -->
        <div class="test-info">
            <h3>Available Forms:</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Account Type</th>
                        <th>Form Display</th>
                        <th>Form Handler</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms_info as $type => $info): ?>
                    <tr>
                        <td><?php echo ucfirst($type); ?></td>
                        <td>
                            <?php if ($info['form_exists']): ?>
                                <span class="badge bg-success">✓ Exists</span>
                                <small class="text-muted"><?php echo $info['form_path']; ?></small>
                            <?php else: ?>
                                <span class="badge bg-danger">✗ Missing</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($info['handler_exists']): ?>
                                <span class="badge bg-success">✓ Exists</span>
                            <?php else: ?>
                                <span class="badge bg-danger">✗ Missing</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?type=<?php echo $type; ?>" class="btn btn-sm btn-primary">Load Form</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Form Preview -->
        <div class="form-preview">
            <h3>Currently Viewing: <?php echo ucfirst($selected_type); ?> Account Form</h3>
            <hr>
            
            <?php
            // Initialize variables that forms expect
            $values = $_POST ?: [];
            $errors = [];
            $signup_process = [
                'account_type' => $selected_type,
                'promo_code' => $_GET['promo'] ?? '',
                'referral_code' => $_GET['ref'] ?? ''
            ];
            
            // Include the form
            $form_path = "/core/forms/signup/form_{$form_type}_basic.inc";
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $form_path)) {
                echo '<form method="POST" action="" novalidate>';
                include($_SERVER['DOCUMENT_ROOT'] . $form_path);
                echo '<button type="submit" class="btn btn-primary mt-3">Test Submit</button>';
                echo '</form>';
            } else {
                echo '<div class="alert alert-danger">Form not found: ' . $form_path . '</div>';
            }
            ?>
        </div>
        
        <!-- Links to Full Implementation -->
        <div class="mt-4">
            <h4>Full Implementation Examples:</h4>
            <ul>
                <li><a href="/createnewmodaccount.php" target="_blank">Family Account Example (createnewmodaccount.php)</a></li>
                <li><a href="/createaccount.php" target="_blank">Original Create Account Page</a></li>
            </ul>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/claudecode/createaccount_flow.js"></script>
</body>
</html>