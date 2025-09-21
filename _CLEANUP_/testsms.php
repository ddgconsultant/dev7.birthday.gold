<?php
$addClasses[] = 'telegramsmsservice';
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SMS Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>SMS Test</h2>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Mobile Number</label>
            <input type="tel" class="form-control" name="mobile" value="303-307-0500" required>
        </div>
        <button type="submit" class="btn btn-primary">Send Test SMS</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $mobile = $_POST['mobile'] ?? '';
        $cleanMobile = preg_replace('/[^0-9]/', '', $mobile);
        
        if (!empty($cleanMobile)) {
            $otp = sprintf('%06d', mt_rand(0, 999999));
            $message = "Birthday.Gold Test OTP: {$otp}";
            
            try {
                $result = $telegramsmsservice->sendSMS($cleanMobile, $message);
                
                if ($result) {
                    echo "<div class='alert alert-success mt-3'>SMS sent successfully. OTP: {$otp}</div>";
                } else {
                    echo "<div class='alert alert-danger mt-3'>Failed to send SMS</div>";
                }
            } catch (Exception $e) {
                echo "<div class='alert alert-danger mt-3'>Error: " . $e->getMessage() . "</div>";
            }
        }
    }
    ?>
</div>
</body>
</html>