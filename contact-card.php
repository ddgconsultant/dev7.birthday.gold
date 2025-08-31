<?php
/**
 * Serve Birthday.Gold vCard contact card with proper MIME type
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-arrays.inc');

// Function to get Base64-encoded image with size check and line wrapping
function getBase64Image($image_url, $max_size_kb = 5) {
    // Download image
    $image_data = file_get_contents($image_url);
    if (!$image_data) {
        return null;
    }
    
    // Check file size - if too large, don't embed
    $size_kb = strlen($image_data) / 1024;
    if ($size_kb > $max_size_kb) {
        error_log("vCard logo too large: {$size_kb}KB (max: {$max_size_kb}KB)");
        return null;
    }
    
    // Encode as Base64
    $base64 = base64_encode($image_data);
    
    // Line wrap at 76 characters for vCard compliance
    $wrapped = chunk_split($base64, 76, "\r\n");
    
    // Remove final line break and ensure clean formatting
    $cleaned = rtrim($wrapped, "\r\n");
    
    error_log("vCard logo encoded: {$size_kb}KB, " . strlen($base64) . " base64 chars");
    return $cleaned;
}

// Generate vCard content using site configuration
function generateBirthdayGoldVCard() {
    global $website, $bg_phonenumbers;
    
    // Use vanity numbers with letters for branding
    $sms_number_letters = "223-200-GOLD"; // SMS with letters  
    $sms_number_digits = "223-200-4653";  // SMS digits only
    $tollfree_letters = "877-BDGOLD-2";   // Toll-free with letters
    $tollfree_digits = "877-234-6532";    // Toll-free digits only
    
    // Use site configuration for dynamic data
    $org_name = "Birthday.Gold";
    $support_email = "support@birthday.gold";
    $website_url = "https://birthday.gold";
    $logo_url = "https://cdn.birthday.gold/public/images/logo/bg_icon.png";
    
    // X-ABLabel approach - dialable numbers + vanity labels
    $vcard = "BEGIN:VCARD\r\n";
    $vcard .= "VERSION:3.0\r\n";
    $vcard .= "N:;BIRTHDAY.GOLD;;;\r\n";
    $vcard .= "FN:BIRTHDAY.GOLD\r\n";
    $vcard .= "ORG:{$org_name}\r\n";
    
    // Try forcing both vanity numbers with different approaches (no country code)
    $vcard .= "TEL;TYPE=CELL,PREF:{$sms_number_letters}\r\n";
    $vcard .= "TEL;TYPE=VOICE,WORK:{$tollfree_letters}\r\n";
    
    // Alternative: try without TYPE, just with custom labels
    $vcard .= "TEL;X-ABLabel=Text-Alt:{$sms_number_letters}\r\n";
    $vcard .= "TEL;X-ABLabel=Voice-Alt:{$tollfree_letters}\r\n";
    
    $vcard .= "EMAIL:{$support_email}\r\n";
    $vcard .= "URL:{$website_url}\r\n";
    $vcard .= "PHOTO;VALUE=URI:{$logo_url}\r\n";
    $vcard .= "END:VCARD\r\n";
    
    return $vcard;
}

// Generate vCard content first
$vcard_content = generateBirthdayGoldVCard();

// Set proper headers for vCard (SMS-compatible)
header('Content-Type: text/x-vcard; charset=utf-8');
header('Content-Disposition: attachment; filename="Birthday-Gold-Support.vcf"');
header('Content-Length: ' . strlen($vcard_content));
header('Cache-Control: private, no-cache');
header('Pragma: no-cache');

// Output vCard
echo $vcard_content;
?>