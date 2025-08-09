<?php
// cleanup_page_titles.php - Trim whitespace from page titles
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'updated' => 0
];

try {
    // Get all pages with titles that need trimming
    $sql = "SELECT page_id, page_title 
            FROM bg_company_pages 
            WHERE page_title LIKE '%\n%' 
               OR page_title LIKE '%  %'
               OR page_title != TRIM(page_title)";
    
    $stmt = $database->query($sql);
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result['found'] = count($pages);
    
    foreach ($pages as $page) {
        // Clean the title - remove extra whitespace and newlines
        $clean_title = trim(preg_replace('/\s+/', ' ', $page['page_title']));
        
        // Update the database
        $update_sql = "UPDATE bg_company_pages 
                      SET page_title = :title 
                      WHERE page_id = :page_id";
        
        $database->query($update_sql, [
            'title' => $clean_title,
            'page_id' => $page['page_id']
        ]);
        
        $result['updated']++;
    }
    
    $result['message'] = "Cleaned {$result['updated']} page titles";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);