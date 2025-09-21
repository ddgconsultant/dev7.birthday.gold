<?php
// check_pages.php - Check what pages are stored for a company
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : 6271;

// Get all pages for this company
$sql = "SELECT * FROM bg_company_pages 
        WHERE company_id = :company_id 
        ORDER BY page_type, confidence_score DESC";

$stmt = $database->query($sql, ['company_id' => $company_id]);
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = [
    'company_id' => $company_id,
    'total_pages' => count($pages),
    'pages_by_type' => [],
    'all_pages' => []
];

// Group by type
foreach ($pages as $page) {
    $type = $page['page_type'];
    if (!isset($result['pages_by_type'][$type])) {
        $result['pages_by_type'][$type] = [];
    }
    $result['pages_by_type'][$type][] = [
        'url' => $page['url'],
        'title' => $page['page_title'],
        'confidence' => $page['confidence_score'],
        'processor' => $page['crawl_processor']
    ];
    
    $result['all_pages'][] = [
        'url' => $page['url'],
        'type' => $page['page_type'],
        'title' => $page['page_title'],
        'confidence' => $page['confidence_score']
    ];
}

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);