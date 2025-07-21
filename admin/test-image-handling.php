<?php
$addClasses[] = 'image';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Admin access only
if (!$account->isadmin()) {
    die('Access denied');
}

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$results = [];
$testImage = '';

#-------------------------------------------------------------------------------
# HANDLE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    if ($_POST['action'] === 'test_ocr' && !empty($_FILES['test_image'])) {
        // Handle upload
        $uploadedFile = $_FILES['test_image'];
        $tempPath = sys_get_temp_dir() . '/' . uniqid('test_') . '_' . $uploadedFile['name'];
        
        if (move_uploaded_file($uploadedFile['tmp_name'], $tempPath)) {
            // Test OCR
            $results['ocr'] = $image->extractText($tempPath, [
                'preprocess' => isset($_POST['preprocess']),
                'enhance_quality' => isset($_POST['enhance_quality'])
            ]);
            
            // Test NSFW
            $results['nsfw'] = $image->checkNSFW($tempPath);
            
            // Cleanup
            @unlink($tempPath);
        }
    } elseif ($_POST['action'] === 'create_test_image') {
        // Create test image
        $testImagePath = sys_get_temp_dir() . '/test_ocr.png';
        $img = imagecreate(400, 100);
        $bg = imagecolorallocate($img, 255, 255, 255);
        $textColor = imagecolorallocate($img, 0, 0, 0);
        imagestring($img, 5, 50, 40, "Hello Birthday Gold OCR Test!", $textColor);
        imagepng($img, $testImagePath);
        imagedestroy($img);
        
        // Test it
        $results['ocr'] = $image->extractText($testImagePath);
        $results['test_image'] = base64_encode(file_get_contents($testImagePath));
        
        @unlink($testImagePath);
    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = 'admin-test';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="container main-content mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1>Test Image Processing</h1>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>System Status</h5>
                </div>
                <div class="card-body">';

$systemInfo = $image->getSystemInfo();

echo '
                    <p><strong>Tesseract Available:</strong> ' . 
                    ($systemInfo['tesseract_available'] ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>') . '</p>';

if ($systemInfo['tesseract_available']) {
    $languages = $image->getAvailableLanguages();
    echo '
                    <p><strong>Available Languages:</strong> ' . implode(', ', array_slice($languages, 0, 5)) . 
                    (count($languages) > 5 ? ' and ' . (count($languages) - 5) . ' more' : '') . '</p>';
}

echo '
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Quick Test</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        ' . $display->inputcsrf_token() . '
                        <input type="hidden" name="action" value="create_test_image">
                        <button type="submit" class="btn btn-primary">Create and Test Sample Image</button>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Upload Test</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        ' . $display->inputcsrf_token() . '
                        <input type="hidden" name="action" value="test_ocr">
                        
                        <div class="mb-3">
                            <label class="form-label">Select Image</label>
                            <input type="file" name="test_image" class="form-control" accept="image/*" required>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="preprocess" id="preprocess" class="form-check-input" checked>
                                <label for="preprocess" class="form-check-label">Preprocess image</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="enhance_quality" id="enhance_quality" class="form-check-input">
                                <label for="enhance_quality" class="form-check-label">Enhance quality</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Test Image</button>
                    </form>
                </div>
            </div>';

// Display results
if (!empty($results)) {
    echo '
            <div class="card">
                <div class="card-header">
                    <h5>Test Results</h5>
                </div>
                <div class="card-body">';
    
    // Show test image if created
    if (!empty($results['test_image'])) {
        echo '
                    <div class="mb-3">
                        <h6>Test Image:</h6>
                        <img src="data:image/png;base64,' . $results['test_image'] . '" class="img-fluid border">
                    </div>';
    }
    
    // OCR Results
    if (!empty($results['ocr'])) {
        echo '
                    <h6>OCR Results:</h6>
                    <div class="alert ' . ($results['ocr']['success'] ? 'alert-success' : 'alert-danger') . '">
                        <strong>Status:</strong> ' . ($results['ocr']['success'] ? 'Success' : 'Failed') . '<br>';
        
        if ($results['ocr']['success']) {
            echo '
                        <strong>Text Found:</strong><br>
                        <pre>' . htmlspecialchars($results['ocr']['text']) . '</pre>
                        <strong>Confidence:</strong> ' . round($results['ocr']['confidence'] * 100) . '%<br>
                        <strong>Processing Time:</strong> ' . round($results['ocr']['processing_time'], 3) . ' seconds';
        } else {
            echo '
                        <strong>Error:</strong> ' . $results['ocr']['error'];
        }
        
        echo '
                    </div>';
    }
    
    // NSFW Results
    if (!empty($results['nsfw'])) {
        echo '
                    <h6>NSFW Check Results:</h6>
                    <div class="alert ' . ($results['nsfw']['is_nsfw'] ? 'alert-danger' : 'alert-info') . '">
                        <strong>NSFW Detected:</strong> ' . ($results['nsfw']['is_nsfw'] ? 'Yes' : 'No') . '<br>
                        <strong>Confidence:</strong> ' . round($results['nsfw']['confidence'] * 100) . '%<br>
                        <strong>Provider:</strong> ' . ($results['nsfw']['provider'] ?? 'local') . '<br>
                        <strong>Processing Time:</strong> ' . round($results['nsfw']['processing_time'], 3) . ' seconds
                    </div>';
    }
    
    echo '
                </div>
            </div>';
}

echo '
        </div>
    </div>
</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();