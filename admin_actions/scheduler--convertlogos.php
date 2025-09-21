<?php
/**
 * Logo Converter Scheduler Script
 * Processes any logos placed in the CONVERT folder
 * Can be triggered via HTTP request (e.g., from Uptime Kuma)
 */

// Include the site controller for proper initialization
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Configuration
$convert_dir = $_SERVER['DOCUMENT_ROOT'] . '/public/assets/img/logos/CONVERT';
$log_file = $convert_dir . '/conversion.log';
$venv_dir = sys_get_temp_dir() . '/logo_converter_env';
$max_execution_time = 300; // 5 minutes max

// Set execution time limit
set_time_limit($max_execution_time);

// Start output buffering
ob_start();

// Function to write to log file
function writeLog($message, $log_file) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Check if called via HTTP or CLI
$is_http = php_sapi_name() !== 'cli';

// HTTP Response header
if ($is_http) {
    header('Content-Type: text/plain');
}

echo "Logo Converter Scheduler\n";
echo "========================\n\n";

// Check if CONVERT directory exists
if (!is_dir($convert_dir)) {
    $error = "ERROR: Convert directory does not exist: $convert_dir";
    echo $error . "\n";
    writeLog($error, $log_file);
    http_response_code(500);
    exit(1);
}

// Write start to log
writeLog("=======================================", $log_file);
writeLog("Running logo converter via PHP scheduler", $log_file);

// Check for images to process
$image_extensions = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'];
$files_to_process = [];

foreach (scandir($convert_dir) as $file) {
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($extension, $image_extensions)) {
        $files_to_process[] = $file;
    }
}

$file_count = count($files_to_process);
echo "Found $file_count image(s) to process\n";
writeLog("Found $file_count image(s) to process", $log_file);

if ($file_count == 0) {
    echo "No images to convert. Exiting.\n";
    writeLog("No images to convert", $log_file);
    http_response_code(200);
    exit(0);
}

// List files
echo "\nFiles to process:\n";
foreach ($files_to_process as $file) {
    echo "  - $file\n";
    writeLog("  - $file", $log_file);
}

// Check Python availability
$python_commands = ['python3', 'python', 'py'];
$python_executable = null;

foreach ($python_commands as $cmd) {
    exec("$cmd --version 2>&1", $output, $return_var);
    if ($return_var === 0) {
        $python_executable = $cmd;
        echo "Found Python: $cmd\n";
        writeLog("Found Python: $cmd", $log_file);
        break;
    }
}

if (!$python_executable) {
    $error = "Python is not available. Please install Python or use manual conversion.";
    echo "ERROR: $error\n";
    writeLog("ERROR: $error", $log_file);
    if ($is_http) {
        http_response_code(500);
    }
    echo "\n\n[STATUS: ERROR]";
    exit(1);
}

// Initialize python_venv with the found executable
$python_venv = $python_executable;

// Setup virtual environment if needed
if (!is_dir($venv_dir)) {
    echo "\nSetting up Python virtual environment...\n";
    writeLog("Creating virtual environment at $venv_dir", $log_file);
    
    // Try different pip commands based on OS
    $pip_cmd = (PHP_OS_FAMILY === 'Windows') ? "$venv_dir\\Scripts\\pip" : "$venv_dir/bin/pip";
    $python_venv = (PHP_OS_FAMILY === 'Windows') ? "$venv_dir\\Scripts\\python" : "$venv_dir/bin/python";
    
    $setup_commands = [
        "$python_executable -m venv $venv_dir 2>&1",
        "$pip_cmd install Pillow 2>&1",
        "$pip_cmd install cairosvg 2>&1"  // For SVG support
    ];
    
    foreach ($setup_commands as $cmd) {
        $output = [];
        $return_var = 0;
        exec($cmd, $output, $return_var);
        
        if ($return_var !== 0) {
            // Try without venv as fallback
            echo "Virtual environment setup failed, trying system Python...\n";
            writeLog("Virtual environment setup failed, using system Python", $log_file);
            $python_venv = $python_executable;
            break;
        }
    }
    
    if ($return_var === 0) {
        echo "Virtual environment setup complete.\n";
        writeLog("Virtual environment setup complete", $log_file);
    }
} else {
    // Use existing venv
    $python_venv = (PHP_OS_FAMILY === 'Windows') ? "$venv_dir\\Scripts\\python" : "$venv_dir/bin/python";
}

// Run the converter
echo "\nRunning logo converter...\n";
writeLog("Executing converter script", $log_file);

$converter_script = $convert_dir . '/convert_logos.py';

// Check if converter script exists
if (!file_exists($converter_script)) {
    $error = "Converter script not found: $converter_script";
    echo "ERROR: $error\n";
    writeLog("ERROR: $error", $log_file);
    if ($is_http) {
        http_response_code(500);
    }
    echo "\n\n[STATUS: ERROR]";
    exit(1);
}

// Execute the converter - use the python executable we found/configured
$command = "$python_venv $converter_script 2>&1";
$output = [];
$return_var = 0;

exec($command, $output, $return_var);

// Display output
echo "Converter output:\n";
echo str_repeat('-', 50) . "\n";
foreach ($output as $line) {
    echo $line . "\n";
    writeLog("CONVERTER: $line", $log_file);
}
echo str_repeat('-', 50) . "\n";

// Check execution status
if ($return_var === 0) {
    echo "\nConversion completed successfully!\n";
    writeLog("Conversion completed successfully", $log_file);
    
    // Count processed files
    $done_dir = $convert_dir . '/DONE';
    $processed_count = 0;
    if (is_dir($done_dir)) {
        $processed_files = array_diff(scandir($done_dir), ['.', '..']);
        $processed_count = count($processed_files);
    }
    
    echo "Total files in DONE folder: $processed_count\n";
    writeLog("Total files in DONE folder: $processed_count", $log_file);
    
    if ($is_http) {
        http_response_code(200);
    }
    $status = "OK";
} else {
    echo "\nERROR: Conversion failed with exit code: $return_var\n";
    writeLog("ERROR: Conversion failed with exit code: $return_var", $log_file);
    
    if ($is_http) {
        http_response_code(500);
    }
    $status = "ERROR";
}

// Write completion to log
writeLog("Scheduler completed", $log_file);
writeLog("=======================================", $log_file);

// End output buffering and send response
$response = ob_get_clean();
echo $response;

// Add simple status for Uptime Kuma monitoring
echo "\n\n[STATUS: $status]";

// Optional: Send notification or update database
// $notify->send('Logo conversion completed', $response);

?>