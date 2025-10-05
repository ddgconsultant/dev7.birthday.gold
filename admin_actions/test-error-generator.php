<?php
/**
 * Test Error Generator
 * Generates intentional PHP errors to test the Auto Error Fixer system
 *
 * Usage: https://dev7.birthday.gold/admin_actions/test-error-generator.php
 */

echo "Generating test errors...\n\n";

// This will trigger: PHP Warning: Undefined array key
$test_array = ['valid_key' => 'value'];
$bad_value = $test_array['nonexistent_key'];

echo "Test errors generated!\n\n";
echo "Now run the error fixer:\n";
echo "https://dev7.birthday.gold/admin_actions/scheduler--auto-error-fixer.php\n";
