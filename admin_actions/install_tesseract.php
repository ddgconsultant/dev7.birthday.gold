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
$os = $image->detectOS();
$currentPath = $image->findTesseract();
$installationLog = [];
$url = '/admin_actions/install_tesseract.php';

// Get any previous messages
$transferpage = $system->startpostpage();
if (empty($transferpage['message']))
    $transferpage['message'] = $session->get('force_error_message', '');
$session->unset('force_error_message');

#-------------------------------------------------------------------------------
# HANDLE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    $action = $_POST['action'] ?? '';
    $message = '';
    $messageType = 'info';
    
    switch ($action) {
        case 'auto_install':
            $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Starting automated installation process'];
            
            // Step 1: Download Tesseract installer (Windows only)
            if ($os === 'windows') {
                $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Detected Windows OS'];
                
                // Create temp directory
                $tempDir = $_SERVER['DOCUMENT_ROOT'] . '/temp/installers';
                if (!is_dir($tempDir)) {
                    if (mkdir($tempDir, 0755, true)) {
                        $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Created temp directory: ' . $tempDir, 'status' => 'success'];
                    } else {
                        $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Failed to create temp directory', 'status' => 'error'];
                    }
                }
                
                
                // Check if installer already exists
                $installerPath = $tempDir . '/tesseract-installer.exe';
                
                if (!file_exists($installerPath)) {
                    // Download Tesseract installer
                    $installerUrl = 'https://digi.bib.uni-mannheim.de/tesseract/tesseract-ocr-w64-setup-5.3.3.20231005.exe';
                    $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Downloading Tesseract installer from ' . $installerUrl];
                    
                    $downloadResult = downloadFile($installerUrl, $installerPath);

                    if ($downloadResult['success']) {
                        $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Download successful (' . formatBytes($downloadResult['size']) . ')', 'status' => 'success'];
                        $message .= '<div class="alert alert-success">Tesseract installer downloaded successfully!</div>';
                        
                        // ADD NEW CODE HERE (starting line 51):
                        // Try silent installation
                        $installCmd = '"' . $installerPath . '" /S /D=C:\Program Files\Tesseract-OCR';
                        $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Attempting silent installation...', 'status' => 'info'];
                        
                        // Clear any output buffer
                        ob_start();
                        exec($installCmd . ' 2>&1', $output, $returnCode);
                        ob_end_clean();
                        
                        if ($returnCode === 0) {
                            $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Silent installation completed successfully', 'status' => 'success'];
                            $message .= '<div class="alert alert-success">Tesseract installed successfully!</div>';
                            
                            // Wait a moment for installation to complete
                            sleep(2);
                            
                            // Try to find Tesseract again
                            $tesseractPath = $image->findTesseract();
                            if ($tesseractPath) {
                                $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Tesseract verified at: ' . $tesseractPath, 'status' => 'success'];
                            }
                        } else {
                            $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Silent installation failed (return code: ' . $returnCode . ')', 'status' => 'warning'];
                            if (!empty($output)) {
                                $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Installation output: ' . implode(' ', $output), 'status' => 'info'];
                            }
                        }

                    } else {
                        $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Download failed: ' . $downloadResult['error'], 'status' => 'error'];
                        $messageType = 'danger';
                    }
                } else {
                    $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Installer already exists at: ' . $installerPath, 'status' => 'info'];
                }
            }
            




            
            // Step 2: Create required directories
            $directories = [
                $_SERVER['DOCUMENT_ROOT'] . '/temp/image_processing' => 'Image processing temp directory',
                $_SERVER['DOCUMENT_ROOT'] . '/logs/image_processing' => 'Image processing logs directory',
                $_SERVER['DOCUMENT_ROOT'] . '/models/nsfw' => 'NSFW models directory'
            ];
            
            foreach ($directories as $dir => $description) {
                if (!is_dir($dir)) {
                    if (mkdir($dir, 0755, true)) {
                        $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Created: ' . $description, 'status' => 'success'];
                    } else {
                        $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Failed to create: ' . $description, 'status' => 'error'];
                    }
                } else {
                    $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Already exists: ' . $description, 'status' => 'info'];
                }
            }
            
            // Step 3: Check for Tesseract
            $tesseractPath = $image->findTesseract();
            if ($tesseractPath) {
                $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Tesseract found at: ' . $tesseractPath, 'status' => 'success'];
                
                // Step 4: Generate configuration
                if ($image->generateConfig($tesseractPath)) {
                    $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Configuration file generated successfully', 'status' => 'success'];
                    $message .= '<div class="alert alert-success">Configuration file generated successfully!</div>';
                    
                    // Step 5: Test Tesseract
                    $testResult = testTesseract($tesseractPath);
                    if ($testResult['success']) {
                        $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Tesseract test successful', 'status' => 'success'];
                        $installationLog[] = ['time' => date('H:i:s'), 'action' => 'OCR test result: "' . $testResult['text'] . '"', 'status' => 'info'];
                        $message .= '<div class="alert alert-success">Tesseract test successful! OCR Result: "' . $testResult['text'] . '"</div>';
                    } else {
                        $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Tesseract test failed: ' . $testResult['error'], 'status' => 'error'];
                        $message .= '<div class="alert alert-warning">Tesseract test failed: ' . $testResult['error'] . '</div>';
                    }
                } else {
                    $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Failed to generate configuration file', 'status' => 'error'];
                    $message .= '<div class="alert alert-danger">Failed to generate configuration file</div>';
                }
            } else {
                $installationLog[] = ['time' => date('H:i:s'), 'action' => 'Tesseract not found - manual installation required', 'status' => 'warning'];
                if (file_exists($installerPath)) {
                    $message .= '<div class="alert alert-warning"><strong>Manual Installation Required</strong><br>';
                    $message .= 'Installer downloaded to: <code>' . $installerPath . '</code><br>';
                    $message .= 'Please run the installer and return to complete setup.</div>';
                }
            }
            
            // Store log in session for display after redirect
            $session->set('installation_log', json_encode($installationLog));
            
            $transferpage['message'] = $message;
            $transferpage['url'] = $url;
            $system->endpostpage($transferpage);
            exit;
            break;
            
        case 'generate_configxxxOLD':
            $tesseractPath = $_POST['tesseract_path'] ?? '';
            if ($image->generateConfig($tesseractPath)) {
                $message = '<div class="alert alert-success">Configuration file generated successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to generate configuration file</div>';
            }
            
            $transferpage['message'] = $message;
            $transferpage['url'] = $url;
            $system->endpostpage($transferpage);
            exit;
            break;

            case 'generate_config':
                $tesseractPath = $_POST['tesseract_path'] ?? '';
                
                // If empty, try to find it
                if (empty($tesseractPath)) {
                    $tesseractPath = $image->findTesseract();
                }
                
                if ($image->generateConfig($tesseractPath)) {
                    $results['config'] = 'Configuration file generated successfully';
                } else {
                    $results['config'] = 'Failed to generate configuration file';
                }
                break;
            
        default:
            $transferpage['message'] = '<div class="alert alert-warning">Invalid action requested</div>';
            $transferpage['url'] = $url;
            $system->endpostpage($transferpage);
            exit;
    }
}

// Retrieve installation log from session if available
$storedLog = $session->get('installation_log', '');
if (!empty($storedLog)) {
    $installationLog = json_decode($storedLog, true);
    $session->unset('installation_log');
}

#-------------------------------------------------------------------------------
# HELPER FUNCTIONS
#-------------------------------------------------------------------------------
function downloadFile($url, $destination) {
    $fp = fopen($destination, 'w+');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    fclose($fp);
    
    if ($result === false) {
        @unlink($destination);
        return ['success' => false, 'error' => $error];
    }
    
    return ['success' => true, 'size' => filesize($destination)];
}

function testTesseract($tesseractPath) {
    // Create test image
    $testImage = sys_get_temp_dir() . '/test_ocr_' . uniqid() . '.png';
    $img = imagecreate(200, 50);
    $bg = imagecolorallocate($img, 255, 255, 255);
    $textColor = imagecolorallocate($img, 0, 0, 0);
    imagestring($img, 5, 10, 15, "Birthday.Gold", $textColor);
    imagepng($img, $testImage);
    imagedestroy($img);
    
    // Run OCR
    $outputFile = sys_get_temp_dir() . '/test_ocr_' . uniqid();
    $cmd = '"' . $tesseractPath . '" "' . $testImage . '" "' . $outputFile . '" 2>&1';
    exec($cmd, $output, $returnCode);
    
    $result = ['success' => false];
    if ($returnCode === 0 && file_exists($outputFile . '.txt')) {
        $text = trim(file_get_contents($outputFile . '.txt'));
        $result = ['success' => true, 'text' => $text];
        @unlink($outputFile . '.txt');
    } else {
        $result['error'] = implode(' ', $output);
    }
    
    @unlink($testImage);
    return $result;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}


#-------------------------------------------------------------------------------
# CHECK SYSTEM
#-------------------------------------------------------------------------------
$systemInfo = $image->getSystemInfo();
$tesseractFound = !empty($currentPath);

// Ensure systemInfo is an array and has expected structure
if (!is_array($systemInfo)) {
    $systemInfo = [
        'extensions' => [
            'gd' => extension_loaded('gd'),
            'imagick' => extension_loaded('imagick'),
            'exif' => extension_loaded('exif')
        ]
    ];
}

// Ensure extensions key exists and is an array
if (!isset($systemInfo['extensions']) || !is_array($systemInfo['extensions'])) {
    $systemInfo['extensions'] = [
        'gd' => extension_loaded('gd'),
        'imagick' => extension_loaded('imagick'),
        'exif' => extension_loaded('exif')
    ];
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
#$bodycontentclass = 'admin-install';

$testingoutput='';
include($dir['core_components'] . '/bg_pagestart.inc');

include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="container main-content mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1>Install Tesseract OCR</h1>';

// Display installation log if available
if (!empty($installationLog)) {
    echo '
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Installation Log</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th width="100">Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>';
    
    foreach ($installationLog as $log) {
        // Ensure $log is an array
        if (!is_array($log)) {
            // If it's a string, convert it to array format
            $log = ['time' => date('H:i:s'), 'action' => (string)$log];
        }
        
        $statusClass = '';
        if (isset($log['status'])) {
            switch ($log['status']) {
                case 'success': $statusClass = 'text-success'; break;
                case 'error': $statusClass = 'text-danger'; break;
                case 'warning': $statusClass = 'text-warning'; break;
                case 'info': $statusClass = 'text-info'; break;
            }
        }
        
        // Ensure required keys exist
        $time = isset($log['time']) ? $log['time'] : date('H:i:s');
        $action = isset($log['action']) ? $log['action'] : 'Unknown action';
        
        echo '
                            <tr>
                                <td class="text-muted">' . htmlspecialchars($time) . '</td>
                                <td class="' . $statusClass . '">' . htmlspecialchars($action) . '</td>
                            </tr>';
    }
    
    echo '
                        </tbody>
                    </table>
                </div>
            </div>';
}

// Show installer download path if available
if (!empty($results['installer_path']) && file_exists($results['installer_path'])) {
    echo '
            <div class="alert alert-warning">
                <h5>Manual Installation Required</h5>
                <p>The Tesseract installer has been downloaded to:</p>
                <code>' . $results['installer_path'] . '</code>
                <ol class="mt-3">
                    <li>Navigate to the file in Windows Explorer</li>
                    <li>Run the installer as Administrator</li>
                    <li>Use default installation path: C:\Program Files\Tesseract-OCR\</li>
                    <li>After installation, refresh this page and click "Run Auto Install" again</li>
                </ol>
            </div>';
}

echo '
            <div class="card mb-4">
                <div class="card-header">
                    <h5>System Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Operating System:</strong></td>
                            <td>' . $os . '</td>
                        </tr>
                        <tr>
                            <td><strong>PHP Version:</strong></td>
                            <td>' . PHP_VERSION . '</td>
                        </tr>
                        <tr>
                            <td><strong>Tesseract Status:</strong></td>
                            <td>' . ($tesseractFound ? '<span class="text-success">Found</span>' : '<span class="text-danger">Not Found</span>') . '</td>
                        </tr>';

                        if ($tesseractFound) {
                            $languages = $image->getAvailableLanguages();
                            $languageDisplay = 'None found';
                            
                            if (is_array($languages) && !empty($languages)) {
                                $languageDisplay = implode(', ', array_slice($languages, 0, 10));
                                if (count($languages) > 10) {
                                    $languageDisplay .= ' (and ' . (count($languages) - 10) . ' more)';
                                }
                            } elseif (is_string($languages) && !empty($languages)) {
                                $languageDisplay = $languages;
                            }
                            
                            echo '
                                                <tr>
                                                    <td><strong>Tesseract Path:</strong></td>
                                                    <td>' . $currentPath . '</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Tesseract Version:</strong></td>
                                                    <td>' . $image->getTesseractVersion() . '</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Available Languages:</strong></td>
                                                    <td>' . $languageDisplay . '</td>
                                                </tr>';
                        }

echo '
                    </table>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Automated Installation</h5>
                </div>
                <div class="card-body">
                    <p>Click the button below to automatically:</p>
                    <ul>
                        <li>Download Tesseract installer (Windows)</li>
                        <li>Create required directories</li>
                        <li>Generate configuration file</li>
                        <li>Test the installation</li>
                    </ul>
                    
                    <form method="POST">
                        ' . $display->inputcsrf_token() . '
                        <input type="hidden" name="action" value="auto_install">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-download"></i> Run Auto Install
                        </button>
                    </form>
                </div>
            </div>';

if (!$tesseractFound) {
    echo '
            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <h5>Manual Installation Instructions</h5>
                </div>
                <div class="card-body">';
    
    switch ($os) {
        case 'windows':
            echo '
                    <h6>Windows Installation:</h6>
                    <ol>
                        <li>Download: <a href="https://github.com/UB-Mannheim/tesseract/wiki" target="_blank">Tesseract for Windows</a></li>
                        <li>Run installer as Administrator</li>
                        <li>Install to: C:\Program Files\Tesseract-OCR\</li>
                        <li>After installation, click "Run Auto Install" above</li>
                    </ol>';
            break;
            
        case 'linux':
            echo '
                    <h6>Linux Installation:</h6>
                    <pre class="bg-light p-3">
# Ubuntu/Debian:
sudo apt-get update
sudo apt-get install tesseract-ocr tesseract-ocr-eng

# CentOS/RHEL:
sudo yum install epel-release
sudo yum install tesseract
                    </pre>';
            break;
    }
    
    echo '
                </div>
            </div>';
}

echo '
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Manual Configuration</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        ' . $display->inputcsrf_token() . '
                        <input type="hidden" name="action" value="generate_config">
                        
                        <div class="mb-3">
                            <label class="form-label">Tesseract Path (optional)</label>
                            <input type="text" name="tesseract_path" class="form-control" 
                                   value="' . ($currentPath ?: '') . '"
                                   placeholder="Leave empty to auto-detect">
                            <small class="form-text text-muted">
                                Common paths: C:\Program Files\Tesseract-OCR\tesseract.exe (Windows), /usr/bin/tesseract (Linux)
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-secondary">Generate Configuration Only</button>
                    </form>';

if (!empty($results['config'])) {
    echo '
                    <div class="alert alert-info mt-3">
                        ' . $results['config'] . '
                    </div>';
}

echo '
                </div>
            </div>
';
echo '
            <div class="card">
                <div class="card-header">
                    <h5>PHP Extensions</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>GD Library:</td>
                            <td>' . ((isset($systemInfo['extensions']['gd']) && $systemInfo['extensions']['gd']) ? '<span class="text-success">Installed</span>' : '<span class="text-danger">Not Installed (Required)</span>') . '</td>
                        </tr>
                        <tr>
                            <td>ImageMagick:</td>
                            <td>' . ((isset($systemInfo['extensions']['imagick']) && $systemInfo['extensions']['imagick']) ? '<span class="text-success">Installed</span>' : '<span class="text-muted">Not Installed (Optional)</span>') . '</td>
                        </tr>
                        <tr>
                            <td>EXIF:</td>
                            <td>' . ((isset($systemInfo['extensions']['exif']) && $systemInfo['extensions']['exif']) ? '<span class="text-success">Installed</span>' : '<span class="text-muted">Not Installed (Optional)</span>') . '</td>
                        </tr>
                        <tr>
                            <td>cURL:</td>
                            <td>' . (extension_loaded('curl') ? '<span class="text-success">Installed</span>' : '<span class="text-danger">Not Installed (Required)</span>') . '</td>
                        </tr>
                    </table>
                </div>
            </div>';
            
            echo '
            
        </div>
    </div>
</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();