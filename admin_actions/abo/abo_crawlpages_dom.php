<?php
// abo_crawlpages_dom.php - DOM-based website crawler to extract ALL links
// This uses PHP's DOMDocument to parse HTML directly instead of relying on AI
// Created: 2025-01-31

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'task_processed' => false,
    'company_id' => null,
    'task_name' => 'abo_crawlpages_dom',
    'errors' => [],
    'company_name' => null,
    'pages_discovered' => 0,
    'pages_crawled' => 0
];

// Enable debug mode
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';
$debug_output = [];

if ($debug) {
    $debug_output[] = ['timestamp' => date('Y-m-d H:i:s'), 'message' => 'Debug mode enabled'];
}

try {
    // Check for specific company ID
    $specific_company_id = null;
    if (isset($_REQUEST['rawid'])) {
        $specific_company_id = intval($_REQUEST['rawid']);
    } elseif (isset($_REQUEST['id'])) {
        $specific_company_id = $qik->decrypt($_REQUEST['id']);
    }
    
    if ($specific_company_id) {
        // Process specific company
        $sql = "SELECT * FROM bg_companies WHERE company_id = :company_id";
        $stmt = $database->prepare($sql);
        $stmt->execute(['company_id' => $specific_company_id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Get next company needing crawling
        $sql = "SELECT c.* FROM bg_companies c 
                LEFT JOIN bg_company_attributes ca ON c.company_id = ca.company_id 
                    AND ca.type = 'onboarding_progress' 
                    AND ca.name = 'abo_crawlpages_dom'
                WHERE c.status IN ('approved_pending_data', 'active')
                    AND (ca.description IS NULL OR ca.description = 'pending')
                    AND c.company_url IS NOT NULL
                ORDER BY c.modify_dt DESC
                LIMIT 1";
        $stmt = $database->prepare($sql);
        $stmt->execute();
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$company) {
        $result['message'] = 'No companies pending crawling';
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    $result['company_id'] = $company['company_id'];
    $result['company_name'] = $company['company_name'];
    $result['task_processed'] = true;
    
    // Update status to in_progress
    $progress_sql = "INSERT INTO bg_company_attributes 
                    (company_id, type, name, description, status, create_dt, modify_dt)
                    VALUES (:company_id, 'onboarding_progress', 'abo_crawlpages_dom', 'in_progress', 'active', NOW(), NOW())
                    ON DUPLICATE KEY UPDATE description = 'in_progress', modify_dt = NOW()";
    $database->query($progress_sql, ['company_id' => $company['company_id']]);
    
    $base_url = rtrim($company['company_url'], '/');
    $parsed_base = parse_url($base_url);
    $base_host = $parsed_base['host'];
    $base_scheme = $parsed_base['scheme'] ?? 'https';
    
    // Function to fetch and parse HTML
    function fetchAndParse($url, $debug = false) {
        global $debug_output;
        
        if ($debug) {
            $debug_output[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'Fetching: ' . $url
            ];
        }
        
        // Use direct curl instead of system method
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Cache-Control: no-cache'
        ]);
        
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($debug) {
            $debug_output[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'CURL response',
                'http_code' => $http_code,
                'content_type' => $content_type,
                'size' => strlen($html),
                'error' => $error ?: null
            ];
        }
        
        if (empty($html)) {
            return false;
        }
        
        return $html;
    }
    
    // Function to extract all links from HTML
    function extractLinks($html, $base_url, $debug = false) {
        global $debug_output;
        
        $links = [];
        
        if ($debug) {
            // Check what we got
            $debug_output[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'HTML length: ' . strlen($html) . ' bytes'
            ];
            
            // Check for common indicators
            if (strpos($html, '<a ') !== false || strpos($html, '<a>') !== false) {
                $debug_output[] = ['message' => 'HTML contains <a> tags'];
            }
            if (strpos($html, 'href=') !== false) {
                $debug_output[] = ['message' => 'HTML contains href attributes'];
            }
            
            // Show first 500 chars of HTML
            $debug_output[] = [
                'message' => 'HTML preview',
                'content' => substr($html, 0, 500)
            ];
        }
        
        // Try regex extraction first as backup
        preg_match_all('/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is', $html, $matches);
        
        if ($debug && !empty($matches[2])) {
            $debug_output[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'Regex found ' . count($matches[2]) . ' links',
                'sample_links' => array_slice($matches[2], 0, 5)
            ];
        }
        
        // Add regex-found links
        if (!empty($matches[2])) {
            for ($i = 0; $i < count($matches[2]); $i++) {
                $links[] = [
                    'href' => $matches[2][$i],
                    'text' => trim(preg_replace('/\s+/', ' ', strip_tags($matches[3][$i]))),
                    'title' => '',
                    'class' => '',
                    'in_nav' => false
                ];
            }
        }
        
        // Also try simpler href extraction
        preg_match_all('/href=["\']([^"\']+)["\']/', $html, $href_matches);
        if ($debug && !empty($href_matches[1])) {
            $debug_output[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'Simple href regex found ' . count($href_matches[1]) . ' URLs',
                'sample_urls' => array_slice($href_matches[1], 0, 10)
            ];
        }
        
        // Suppress warnings from malformed HTML
        libxml_use_internal_errors(true);
        
        // Create DOM document - try without flags first
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        
        // Extract all anchor tags
        $anchors = $dom->getElementsByTagName('a');
        
        if ($debug) {
            $debug_output[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'DOM found ' . $anchors->length . ' anchor tags'
            ];
        }
        
        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            $text = trim(preg_replace('/\s+/', ' ', $anchor->textContent));
            
            // Skip empty hrefs
            if (empty($href)) {
                continue;
            }
            
            // Get additional context
            $title = $anchor->getAttribute('title');
            $class = $anchor->getAttribute('class');
            $id = $anchor->getAttribute('id');
            
            // Check if link is in navigation
            $parent = $anchor->parentNode;
            $in_nav = false;
            while ($parent && $parent->nodeType === XML_ELEMENT_NODE) {
                $tag = strtolower($parent->tagName);
                $parent_class = $parent->getAttribute('class');
                $parent_id = $parent->getAttribute('id');
                
                if ($tag === 'nav' || 
                    strpos($parent_class, 'nav') !== false || 
                    strpos($parent_class, 'menu') !== false ||
                    strpos($parent_id, 'nav') !== false ||
                    strpos($parent_id, 'menu') !== false) {
                    $in_nav = true;
                    break;
                }
                $parent = $parent->parentNode;
            }
            
            $links[] = [
                'href' => $href,
                'text' => $text,
                'title' => $title,
                'class' => $class,
                'in_nav' => $in_nav
            ];
        }
        
        // Also look for links in onclick attributes
        $xpath = new DOMXPath($dom);
        $elements_with_onclick = $xpath->query('//*[@onclick]');
        
        foreach ($elements_with_onclick as $element) {
            $onclick = $element->getAttribute('onclick');
            // Look for location.href or window.location patterns
            if (preg_match('/(?:location\.href|window\.location)\s*=\s*["\']([^"\']+)["\']/', $onclick, $matches)) {
                $links[] = [
                    'href' => $matches[1],
                    'text' => trim(preg_replace('/\s+/', ' ', $element->textContent)),
                    'title' => '',
                    'class' => $element->getAttribute('class'),
                    'in_nav' => false
                ];
            }
        }
        
        libxml_clear_errors();
        
        return $links;
    }
    
    // Function to categorize page based on URL and text
    function categorizePage($url, $text) {
        $url_lower = strtolower($url);
        $text_lower = strtolower($text);
        
        // Phone numbers
        if (strpos($url, 'tel:') === 0) {
            return ['type' => 'phone', 'confidence' => 1.0];
        }
        
        // Email addresses
        if (strpos($url, 'mailto:') === 0) {
            return ['type' => 'email', 'confidence' => 1.0];
        }
        
        // Check URL patterns
        if (preg_match('/\/(location|store|find-?us|where|branch|outlet)s?(?:[\/\-\.]|$)/i', $url)) {
            return ['type' => 'locations', 'confidence' => 0.95];
        }
        if (preg_match('/\/(menu|product|item|catalog|shop|order)s?(?:[\/\-\.]|$)/i', $url)) {
            return ['type' => 'menu', 'confidence' => 0.90];
        }
        if (preg_match('/\/(about|story|history|who-?we-?are|our-?company)(?:[\/\-\.]|$)/i', $url)) {
            return ['type' => 'about', 'confidence' => 0.95];
        }
        if (preg_match('/\/(contact|support|help|customer-?service)(?:[\/\-\.]|$)/i', $url)) {
            return ['type' => 'contact', 'confidence' => 0.95];
        }
        if (preg_match('/\/(hour|time|schedule|when-?open)s?(?:[\/\-\.]|$)/i', $url)) {
            return ['type' => 'hours', 'confidence' => 0.95];
        }
        if (preg_match('/\/(signup|sign-?up|register|join|create-?account|reward|loyalty|club|member)s?(?:[\/\-\.]|$)/i', $url)) {
            return ['type' => 'signup', 'confidence' => 0.90];
        }
        if (preg_match('/\/(blog|news|article|post)s?(?:[\/\-\.]|$)/i', $url)) {
            return ['type' => 'blog', 'confidence' => 0.85];
        }
        if (preg_match('/\/(career|job|employment|work-?with-?us|hiring)s?(?:[\/\-\.]|$)/i', $url)) {
            return ['type' => 'careers', 'confidence' => 0.90];
        }
        if (preg_match('/\/(gift-?card|gift|card)s?(?:[\/\-\.]|$)/i', $url)) {
            return ['type' => 'giftcards', 'confidence' => 0.85];
        }
        
        // Check link text patterns
        if (preg_match('/location|store|find us|where/i', $text)) {
            return ['type' => 'locations', 'confidence' => 0.80];
        }
        if (preg_match('/menu|order|product/i', $text)) {
            return ['type' => 'menu', 'confidence' => 0.75];
        }
        if (preg_match('/about|story|who we are/i', $text)) {
            return ['type' => 'about', 'confidence' => 0.80];
        }
        if (preg_match('/contact|support|help/i', $text)) {
            return ['type' => 'contact', 'confidence' => 0.80];
        }
        if (preg_match('/hour|time|open/i', $text)) {
            return ['type' => 'hours', 'confidence' => 0.75];
        }
        if (preg_match('/sign ?up|register|join|reward|member/i', $text)) {
            return ['type' => 'signup', 'confidence' => 0.75];
        }
        
        // Special pages
        if ($url === '/' || $text_lower === 'home') {
            return ['type' => 'homepage', 'confidence' => 1.0];
        }
        if (preg_match('/\/(privacy|terms|policy|legal)/i', $url)) {
            return ['type' => 'legal', 'confidence' => 0.90];
        }
        if (preg_match('/\/cart|\/checkout|\/basket/i', $url)) {
            return ['type' => 'cart', 'confidence' => 0.90];
        }
        
        return ['type' => 'other', 'confidence' => 0.5];
    }
    
    // Function to normalize URL
    function normalizeUrl($url, $base_url) {
        // Handle special protocols
        if (preg_match('/^(tel:|mailto:|javascript:|#)/', $url)) {
            return $url;
        }
        
        // Already absolute URL
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }
        
        $parsed_base = parse_url($base_url);
        $base_scheme = $parsed_base['scheme'] ?? 'https';
        $base_host = $parsed_base['host'];
        $base_path = $parsed_base['path'] ?? '/';
        
        // Protocol-relative URL
        if (strpos($url, '//') === 0) {
            return $base_scheme . ':' . $url;
        }
        
        // Absolute path
        if (strpos($url, '/') === 0) {
            return $base_scheme . '://' . $base_host . $url;
        }
        
        // Relative path
        $base_dir = dirname($base_path);
        if ($base_dir === '/') {
            return $base_scheme . '://' . $base_host . '/' . $url;
        }
        return $base_scheme . '://' . $base_host . $base_dir . '/' . $url;
    }
    
    // Fetch and parse homepage
    $html = fetchAndParse($base_url, $debug);
    
    if (!$html) {
        throw new Exception('Failed to fetch homepage');
    }
    
    // Save HTML for debugging (optional)
    if ($debug) {
        // Use Windows temp directory
        $temp_dir = sys_get_temp_dir();
        $debug_file = $temp_dir . '/crawl_debug_' . $company['company_id'] . '.html';
        file_put_contents($debug_file, $html);
        $debug_output[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => 'HTML saved to: ' . $debug_file,
            'file_size' => filesize($debug_file) . ' bytes'
        ];
    }
    
    // Extract all links
    $all_links = extractLinks($html, $base_url, $debug);
    
    if ($debug) {
        $debug_output[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => 'Extracted ' . count($all_links) . ' total links from homepage'
        ];
    }
    
    // Store homepage
    $homepage_sql = "INSERT INTO bg_company_pages 
                   (company_id, url, page_type, page_title, confidence_score, 
                    crawl_processor, crawl_depth, status, create_dt)
                   VALUES 
                   (:company_id, :url, 'homepage', :title, 1.00, 
                    'abo_crawlpages_dom', 0, 'active', NOW())
                   ON DUPLICATE KEY UPDATE 
                   page_title = VALUES(page_title),
                   modify_dt = NOW()";
    
    // Extract title
    preg_match('/<title>(.*?)<\/title>/is', $html, $title_matches);
    $page_title = isset($title_matches[1]) ? html_entity_decode(trim($title_matches[1])) : $company['company_name'];
    
    $database->query($homepage_sql, [
        'company_id' => $company['company_id'],
        'url' => $base_url,
        'title' => $page_title
    ]);
    
    $result['pages_discovered']++;
    
    // Process all discovered links
    $seen_urls = [$base_url => true];
    $pages_to_crawl = [];
    
    foreach ($all_links as $link) {
        $href = $link['href'];
        
        // Skip empty or anchor-only links
        if (empty($href) || $href === '#') {
            continue;
        }
        
        // Skip javascript: links
        if (strpos($href, 'javascript:') === 0) {
            continue;
        }
        
        // Normalize URL
        $full_url = normalizeUrl($href, $base_url);
        
        // Skip if already seen
        if (isset($seen_urls[$full_url])) {
            continue;
        }
        
        $seen_urls[$full_url] = true;
        
        // For non-special URLs, check if same domain
        if (!preg_match('/^(tel:|mailto:)/', $full_url)) {
            $parsed_url = parse_url($full_url);
            $url_host = $parsed_url['host'] ?? '';
            
            if ($url_host !== $base_host && !empty($url_host)) {
                continue; // Skip external links
            }
        }
        
        // Categorize the page
        $categorization = categorizePage($full_url, $link['text']);
        
        // Store in database
        try {
            $page_sql = "INSERT INTO bg_company_pages 
                       (company_id, url, page_type, page_title, confidence_score,
                        crawl_processor, crawl_depth, parent_url, status, create_dt)
                       VALUES 
                       (:company_id, :url, :page_type, :page_title, :confidence,
                        'abo_crawlpages_dom', 1, :parent_url, 'active', NOW())
                       ON DUPLICATE KEY UPDATE 
                       page_type = IF(confidence_score < VALUES(confidence_score), VALUES(page_type), page_type),
                       confidence_score = IF(confidence_score < VALUES(confidence_score), VALUES(confidence_score), confidence_score),
                       page_title = IF(page_title IS NULL OR page_title = '', VALUES(page_title), page_title),
                       modify_dt = NOW()";
            
            $database->query($page_sql, [
                'company_id' => $company['company_id'],
                'url' => $full_url,
                'page_type' => $categorization['type'],
                'page_title' => !empty($link['text']) ? trim(preg_replace('/\s+/', ' ', $link['text'])) : trim($link['title']),
                'confidence' => $categorization['confidence'],
                'parent_url' => $base_url
            ]);
            
            $result['pages_discovered']++;
            
            // Add to crawl queue if it's an important page type and not a special URL
            if (in_array($categorization['type'], ['locations', 'menu', 'hours', 'signup', 'contact']) && 
                !preg_match('/^(tel:|mailto:)/', $full_url)) {
                $pages_to_crawl[] = $full_url;
            }
            
        } catch (PDOException $e) {
            if ($e->getCode() != '23000') { // Ignore duplicate key errors
                throw $e;
            }
        }
    }
    
    // Crawl important pages (depth 2) - limit to prevent timeout
    $max_crawl = min(5, count($pages_to_crawl));
    for ($i = 0; $i < $max_crawl; $i++) {
        $crawl_url = $pages_to_crawl[$i];
        
        if ($debug) {
            $debug_output[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'Crawling depth 2: ' . $crawl_url
            ];
        }
        
        $page_html = fetchAndParse($crawl_url, false);
        if ($page_html) {
            $page_links = extractLinks($page_html, $crawl_url, false);
            
            foreach ($page_links as $link) {
                $href = $link['href'];
                if (empty($href) || $href === '#' || strpos($href, 'javascript:') === 0) {
                    continue;
                }
                
                $full_url = normalizeUrl($href, $crawl_url);
                
                if (isset($seen_urls[$full_url])) {
                    continue;
                }
                
                $seen_urls[$full_url] = true;
                
                // Check domain for non-special URLs
                if (!preg_match('/^(tel:|mailto:)/', $full_url)) {
                    $parsed_url = parse_url($full_url);
                    $url_host = $parsed_url['host'] ?? '';
                    
                    if ($url_host !== $base_host && !empty($url_host)) {
                        continue;
                    }
                }
                
                $categorization = categorizePage($full_url, $link['text']);
                
                try {
                    $page_sql = "INSERT INTO bg_company_pages 
                               (company_id, url, page_type, page_title, confidence_score,
                                crawl_processor, crawl_depth, parent_url, status, create_dt)
                               VALUES 
                               (:company_id, :url, :page_type, :page_title, :confidence,
                                'abo_crawlpages_dom', 2, :parent_url, 'active', NOW())
                               ON DUPLICATE KEY UPDATE 
                               page_type = IF(confidence_score < VALUES(confidence_score), VALUES(page_type), page_type),
                               confidence_score = IF(confidence_score < VALUES(confidence_score), VALUES(confidence_score), confidence_score),
                               modify_dt = NOW()";
                    
                    $database->query($page_sql, [
                        'company_id' => $company['company_id'],
                        'url' => $full_url,
                        'page_type' => $categorization['type'],
                        'page_title' => !empty($link['text']) ? trim(preg_replace('/\s+/', ' ', $link['text'])) : null,
                        'confidence' => $categorization['confidence'],
                        'parent_url' => $crawl_url
                    ]);
                    
                    $result['pages_discovered']++;
                    
                } catch (PDOException $e) {
                    if ($e->getCode() != '23000') {
                        throw $e;
                    }
                }
            }
            
            $result['pages_crawled']++;
        }
    }
    
    // Update task status
    $progress_sql = "UPDATE bg_company_attributes 
                    SET description = 'completed', modify_dt = NOW()
                    WHERE company_id = :company_id 
                    AND type = 'onboarding_progress' 
                    AND name = 'abo_crawlpages_dom'";
    $database->query($progress_sql, ['company_id' => $company['company_id']]);
    
    $result['message'] = sprintf('Discovered %d pages, crawled %d pages', 
                                 $result['pages_discovered'], 
                                 $result['pages_crawled'] + 1);
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    
    // Update task status to error
    if (!empty($company['company_id'])) {
        $progress_sql = "UPDATE bg_company_attributes 
                        SET description = 'error', modify_dt = NOW()
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_crawlpages_dom'";
        $database->query($progress_sql, ['company_id' => $company['company_id']]);
    }
}

// Add debug output if enabled
if ($debug && !empty($debug_output)) {
    $result['debug'] = $debug_output;
}

// Output result
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);