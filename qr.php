<?php
require_once('vendor/autoload.php');

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

if(isset($_GET['i'])) {
    $text = urldecode($_GET['i']);

    $qrCode = new QrCode($text);
    
    $writer = new PngWriter();

    // Generate QR code as PNG binary string
    $pngData = $writer->write($qrCode)->getString();

    // Output the image directly
    header('Content-Type: image/png');
    echo $pngData;
}
