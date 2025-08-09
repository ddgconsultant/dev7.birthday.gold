<?php
// cleanup_bad_pages.php - Remove AIRTOP session URLs from bg_company_pages
// Created: 2025-01-31

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'removed_count' => 0,
    'bad_urls' => []
];

try {
    // Find URLs that look like AIRTOP session IDs (but NOT tel: or mailto: which are valid)
    $sql = "SELECT * FROM bg_company_pages 
            WHERE (url REGEXP '/[a-zA-Z0-9]{6,12}$' 
                   AND url NOT LIKE 'tel:%' 
                   AND url NOT LIKE 'mailto:%')
            OR url REGEXP '^https?://[^/]+/[a-zA-Z0-9]{6,12}$'";
    
    $stmt = $database->query($sql);
    $bad_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($bad_pages)) {
        foreach ($bad_pages as $page) {
            $result['bad_urls'][] = [
                'page_id' => $page['page_id'],
                'company_id' => $page['company_id'],
                'url' => $page['url'],
                'page_type' => $page['page_type']
            ];
        }
        
        // Delete the bad URLs
        $page_ids = array_column($bad_pages, 'page_id');
        $placeholders = implode(',', array_fill(0, count($page_ids), '?'));
        
        $delete_sql = "DELETE FROM bg_company_pages WHERE page_id IN ($placeholders)";
        $stmt = $database->prepare($delete_sql);
        $stmt->execute($page_ids);
        
        $result['removed_count'] = count($bad_pages);
        $result['message'] = "Removed {$result['removed_count']} bad URLs";
    } else {
        $result['message'] = 'No bad URLs found';
    }
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);