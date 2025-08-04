<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Debug variables that affect header class
echo "<pre>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "Current Page (from parsing): " . basename($_SERVER['SCRIPT_NAME'], '.php') . "\n";
echo "Current Path: " . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . "\n";
echo "\n";

// Check the condition
$currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = $_SERVER['SCRIPT_NAME'];

$isHomePage = ($currentPage === 'index' || 
              $currentPage === '' || 
              $currentPath === '/' || 
              $currentPath === '/index' ||
              $currentPath === '/index.php' ||
              $scriptName === '/index.php');

echo "Is Home Page: " . ($isHomePage ? 'YES' : 'NO') . "\n";
echo "</pre>";
?>