<?php
error_reporting(0);
ini_set('display_errors', 0);

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

ob_clean();
header('Content-Type: application/json');

// Authentication is already handled by site-controller.php
// If we reach here, user is authenticated

$company_id = $_GET['company_id'] ?? '';

if (empty($company_id)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Missing company ID']);
    exit;
}

if (!isset($database)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection not available']);
    exit;
}

try {
    $query = "SELECT * FROM bg_companies WHERE company_id = :company_id";
    $stmt = $database->prepare($query);
    $stmt->execute([':company_id' => $company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

if (!$company) {
    echo json_encode(['success' => false, 'message' => 'Company not found']);
    exit;
}

$logoUrl = '';
if (!empty($company['company_logo'])) {
    if (isset($display) && method_exists($display, 'companyimage')) {
        $logoUrl = $display->companyimage($company['company_id'] . '/' . $company['company_logo']);
    } else {
        $cdnUrl = $website['cdnurl'] ?? 'files.birthday.gold';
        $logoUrl = 'https://' . $cdnUrl . '/companies/' . $company['company_id'] . '/' . $company['company_logo'];
    }
}

$response = [
    'success' => true,
    'company' => [
        'name' => $company['company_name'] ?? '',
        'logo' => $logoUrl,
        'description' => $company['description'] ?? '',
        'reward' => $company['description'] ?? '',
        'phone' => $company['phone'] ?? '',
        'website' => $company['website'] ?? '',
        'address' => $company['address'] ?? '',
        'city' => $company['city'] ?? '',
        'state' => $company['state'] ?? '',
        'zip' => $company['zip_code'] ?? '',
        'hours' => [],
        'notes' => $company['notes'] ?? ''
    ]
];

ob_clean();
echo json_encode($response);
exit;