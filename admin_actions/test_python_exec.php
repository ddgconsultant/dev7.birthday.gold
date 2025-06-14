<?php
/**
 * Test script to check if Python can be executed from PHP
 * This helps diagnose cross-platform issues
 */

echo "Python Execution Test\n";
echo "====================\n\n";

// Test different Python commands
$python_commands = ['python3', 'python', 'py', 'python.exe', 'python3.exe'];

foreach ($python_commands as $cmd) {
    echo "Testing: $cmd\n";
    
    $output = [];
    $return_var = -1;
    
    exec("$cmd --version 2>&1", $output, $return_var);
    
    if ($return_var === 0) {
        echo "  ✓ SUCCESS: " . implode(' ', $output) . "\n";
    } else {
        echo "  ✗ FAILED (code: $return_var)\n";
    }
    
    unset($output);
}

echo "\nPHP Environment:\n";
echo "  PHP Version: " . PHP_VERSION . "\n";
echo "  OS: " . PHP_OS . "\n";
echo "  OS Family: " . PHP_OS_FAMILY . "\n";
echo "  Temp Dir: " . sys_get_temp_dir() . "\n";
echo "  Document Root: " . (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'Not set') . "\n";

// Test creating a simple Python script
echo "\nTesting Python script execution:\n";
$test_script = sys_get_temp_dir() . '/test_python.py';
file_put_contents($test_script, "print('Hello from Python!')");

foreach (['python3', 'python', 'py'] as $cmd) {
    $output = [];
    $return_var = -1;
    
    exec("$cmd $test_script 2>&1", $output, $return_var);
    
    if ($return_var === 0) {
        echo "  ✓ $cmd can execute scripts: " . implode(' ', $output) . "\n";
        break;
    }
}

// Clean up
@unlink($test_script);

echo "\n[STATUS: OK]\n";
?>