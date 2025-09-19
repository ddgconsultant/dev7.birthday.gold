<?php
/**
 * Public Preview Business Detail Page
 * Shows business information for newsletter preview testing
 * Does NOT require login - validates admin token instead
 */

// Start session to check for admin preview mode
session_start();

// Initialize preview variables
$admin_preview_mode = false;
$preview_user_id = null;
$company_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate admin preview token
if (isset($_GET['admin_preview']) && isset($_GET['preview_user']) && isset($_GET['token'])) {
    $received_token = $_GET['token'];
    $expected_token = md5('0_PREVIEW_' . date('Y-m-d') . '_BG_ADMIN_SECRET');
    
    if ($received_token === $expected_token) {
        $admin_preview_mode = true;
        $preview_user_id = intval($_GET['preview_user']);
        
        // Store in session
        $_SESSION['admin_preview_mode'] = true;
        $_SESSION['preview_user_id'] = $preview_user_id;
        $_SESSION['preview_token'] = $received_token;
    }
}

// Check session for existing preview mode
if (!$admin_preview_mode && isset($_SESSION['admin_preview_mode'])) {
    $stored_token = $_SESSION['preview_token'] ?? '';
    $expected_token = md5('0_PREVIEW_' . date('Y-m-d') . '_BG_ADMIN_SECRET');
    
    if ($stored_token === $expected_token) {
        $admin_preview_mode = true;
        $preview_user_id = $_SESSION['preview_user_id'] ?? null;
    }
}

// If not in admin preview mode and this is a test request, show error
if (isset($_GET['test']) && isset($_GET['preview']) && !$admin_preview_mode) {
    die('Invalid preview token. This page is only accessible with a valid admin preview token.');
}

// Include minimal requirements (no auth check)
include($_SERVER['DOCUMENT_ROOT'] . '/core/connection.inc');
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-arrays.inc');

if (!$company_id) {
    header('Location: /');
    exit;
}

// Get company details
$company_sql = "SELECT c.*, a.description as logo_filename
                FROM bg_companies c
                LEFT JOIN bg_company_attributes a ON c.company_id = a.company_id 
                    AND a.category = 'company_logos' AND a.grouping = 'primary_logo'
                WHERE c.company_id = :company_id
                AND c.status = 'finalized'";

$company = $database->getrow($company_sql, ['company_id' => $company_id]);

if (!$company) {
    die('Company not found');
}

// Build logo URL
$logo_url = '';
if (!empty($company['logo_filename'])) {
    $logo_url = '//cdn.birthday.gold/public/images/company_images/' . $company['company_id'] . '/' . $company['logo_filename'];
}

// Get preview user info if available
$preview_user = null;
if ($preview_user_id) {
    $user_sql = "SELECT user_id, first_name, last_name, email FROM bg_users WHERE user_id = :user_id";
    $preview_user = $database->getrow($user_sql, ['user_id' => $preview_user_id]);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($company['company_name']) ?> - Preview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .preview-banner {
            background: linear-gradient(135deg, #ff9800 0%, #ff5722 100%);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .company-logo {
            max-width: 200px;
            max-height: 150px;
            object-fit: contain;
        }
        .enroll-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 15px 30px;
            font-size: 1.2rem;
            border-radius: 50px;
            transition: all 0.3s;
        }
        .enroll-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
    </style>
</head>
<body>
    <?php if ($admin_preview_mode): ?>
    <div class="preview-banner">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-0"><i class="bi bi-eye"></i> Newsletter Preview Mode</h5>
                    <small>
                        Testing as: <?= $preview_user ? htmlspecialchars($preview_user['first_name'] . ' ' . $preview_user['last_name']) : 'User #' . $preview_user_id ?>
                        | This is a test preview - no actual enrollment will occur
                    </small>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-white text-dark">TEST MODE</span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <!-- Company Header -->
                        <div class="text-center mb-4">
                            <?php if ($logo_url): ?>
                                <img src="<?= htmlspecialchars($logo_url) ?>" alt="<?= htmlspecialchars($company['company_name']) ?>" class="company-logo mb-3">
                            <?php else: ?>
                                <div class="bg-light p-4 mb-3" style="width: 200px; height: 150px; margin: 0 auto; display: flex; align-items: center; justify-content: center; border-radius: 10px;">
                                    <i class="bi bi-building" style="font-size: 3rem; color: #ccc;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <h1><?= htmlspecialchars($company['company_name']) ?></h1>
                            
                            <?php if ($company['display_category']): ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($company['display_category']) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Birthday Offer -->
                        <?php if ($company['description']): ?>
                        <div class="alert alert-success">
                            <h5><i class="bi bi-gift"></i> Birthday Reward:</h5>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($company['description'])) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Company Details -->
                        <div class="row mb-4">
                            <?php if ($company['website']): ?>
                            <div class="col-md-6 mb-3">
                                <i class="bi bi-globe"></i> 
                                <a href="<?= htmlspecialchars($company['website']) ?>" target="_blank">Visit Website</a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($company['phone']): ?>
                            <div class="col-md-6 mb-3">
                                <i class="bi bi-telephone"></i> 
                                <?= htmlspecialchars($company['phone']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Preview Action Buttons -->
                        <div class="text-center">
                            <?php if ($admin_preview_mode): ?>
                                <div class="alert alert-info mb-4">
                                    <i class="bi bi-info-circle"></i> 
                                    <strong>Test Mode:</strong> This simulates what the user would see. Clicking "Enroll Now" will demonstrate the enrollment flow.
                                </div>
                                
                                <!-- Make it look like the real enrollment button -->
                                <form method="POST" action="/myaccount/enrollment-picker.php" id="enrollForm">
                                    <input type="hidden" name="action" value="enroll_from_detail">
                                    <input type="hidden" name="company_id" value="<?= htmlspecialchars($company_id) ?>">
                                    <input type="hidden" name="company_name" value="<?= htmlspecialchars($company['company_name']) ?>">
                                    <input type="hidden" name="logo_url" value="<?= htmlspecialchars($logo_url ?? '') ?>">
                                    <button type="button" class="enroll-btn" onclick="handleEnrollClick(event)">
                                        <i class="bi bi-plus-circle"></i> Enroll Now
                                    </button>
                                </form>
                                
                                <div id="enrollmentResult" class="mt-4" style="display: none;">
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle-fill"></i> 
                                        <strong>Preview Test Successful!</strong><br>
                                        In production, this would submit to the enrollment-picker and show the confirmation modal.<br>
                                        User <?= $preview_user ? htmlspecialchars($preview_user['first_name']) : '#' . $preview_user_id ?> would be enrolled in <?= htmlspecialchars($company['company_name']) ?>.
                                    </div>
                                    
                                    <div class="mt-3">
                                        <a href="/myaccount/enrollment-picker" class="btn btn-primary">
                                            <i class="bi bi-basket"></i> View Enrollment Picker
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Please use a valid preview link to test enrollment.</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Back to Editor -->
                        <div class="mt-5 text-center">
                            <a href="/myaccount/marketing/newsletter-edit" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Newsletter Editor
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function handleEnrollClick(event) {
        event.preventDefault();
        
        // Show loading
        var btn = event.target.closest('button');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        
        // Simulate enrollment delay
        setTimeout(function() {
            document.getElementById('enrollmentResult').style.display = 'block';
            document.getElementById('enrollForm').style.display = 'none';
        }, 1500);
    }
    </script>
</body>
</html>