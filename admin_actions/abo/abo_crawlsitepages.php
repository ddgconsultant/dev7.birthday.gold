<?php
// abo_crawlsitepages.php - AIRTOP-powered website crawler to discover and categorize pages
// Created: 2025-01-31

$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'task_processed' => false,
    'company_id' => null,
    'task_name' => 'abo_crawlsitepages',
    'errors' => [],
    'company_name' => null,
    'pages_discovered' => 0,
    'pages_categorized' => 0
];

// Enable debug mode
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';
$debug_output = [];

if ($debug) {
    $debug_output[] = ['timestamp' => date('Y-m-d H:i:s'), 'message' => 'Debug mode enabled'];
}

try {
    // Check for specific company ID (debug mode)
    $specific_company_id = null;
    if (isset($_REQUEST['rawid'])) {
        $specific_company_id = intval($_REQUEST['rawid']);
    } elseif (isset($_REQUEST['id'])) {
        $specific_company_id = $qik->decrypt($_REQUEST['id']);
    }
    
    if ($specific_company_id) {
        // Process specific company
        $sql = "SELECT c.*, ca.description as task_status 
                FROM bg_companies c
                LEFT JOIN bg_company_attributes ca ON c.company_id = ca.company_id 
                    AND ca.type = 'onboarding_progress' 
                    AND ca.name = 'abo_crawlsitepages'
                WHERE c.company_id = :company_id";
        $stmt = $database->prepare($sql);
        $stmt->execute(['company_id' => $specific_company_id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Get next company needing site crawling
        $sql = "SELECT c.*, ca.description as task_status 
                FROM bg_companies c
                LEFT JOIN bg_company_attributes ca ON c.company_id = ca.company_id 
                    AND ca.type = 'onboarding_progress' 
                    AND ca.name = 'abo_crawlsitepages'
                WHERE c.status IN ('approved_pending_data', 'active')
                    AND (ca.description IS NULL OR ca.description = 'pending' OR ca.description = 'error')
                    AND c.company_url IS NOT NULL
                ORDER BY c.modify_dt DESC
                LIMIT 1";
        $stmt = $database->prepare($sql);
        $stmt->execute();
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$company) {
        $result['message'] = 'No companies pending site crawling';
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
                    VALUES (:company_id, 'onboarding_progress', 'abo_crawlsitepages', 'in_progress', 'active', NOW(), NOW())
                    ON DUPLICATE KEY UPDATE description = 'in_progress', modify_dt = NOW()";
    $database->query($progress_sql, ['company_id' => $company['company_id']]);
    
    // Initialize AIRTOP
    $airtopApiUrl = 'https://api.airtop.ai/api/v1/';
    $airtopApiKey = $sitesettings_ai['airtop']['apikey'] ?? '';
    
    if (empty($airtopApiKey)) {
        throw new Exception('AIRTOP API key not configured');
    }
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $airtopApiKey
    ];
    
    // Create AIRTOP session
    if ($debug) {
        $debug_output[] = ['timestamp' => date('Y-m-d H:i:s'), 'message' => 'Creating AIRTOP session...'];
    }
    
    $sessionResponse = $system->curlRequest(
        $airtopApiUrl . 'sessions',
        $headers,
        [],
        'POST'
    );
    
    if (!isset($sessionResponse['decoded']['data']['id'])) {
        throw new Exception('Failed to create AIRTOP session');
    }
    
    $sessionId = $sessionResponse['decoded']['data']['id'];
    
    if ($debug) {
        $debug_output[] = ['timestamp' => date('Y-m-d H:i:s'), 'message' => 'Session created: ' . $sessionId];
    }
    
    // Wait for session to be ready
    $maxWaitTime = 30;
    $waitInterval = 2;
    $sessionReady = false;
    
    for ($i = 0; $i < ($maxWaitTime / $waitInterval); $i++) {
        sleep($waitInterval);
        
        $sessionStatusResponse = $system->curlRequest(
            $airtopApiUrl . 'sessions/' . $sessionId,
            $headers,
            [],
            'GET'
        );
        
        if (isset($sessionStatusResponse['decoded']['data']['status'])) {
            $status = $sessionStatusResponse['decoded']['data']['status'];
            if (in_array($status, ['active', 'ready', 'running'])) {
                $sessionReady = true;
                if ($debug) {
                    $debug_output[] = [
                        'timestamp' => date('Y-m-d H:i:s'),
                        'message' => 'Session ready after ' . (($i + 1) * $waitInterval) . ' seconds'
                    ];
                }
                break;
            }
        }
    }
    
    if (!$sessionReady) {
        throw new Exception('Session failed to become ready');
    }
    
    try {
        $base_url = rtrim($company['company_url'], '/');
        $discovered_pages = [];
        
        // Start with homepage
        if ($debug) {
            $debug_output[] = ['timestamp' => date('Y-m-d H:i:s'), 'message' => 'Crawling homepage: ' . $base_url];
        }
        
        // Create window for homepage
        $windowResponse = $system->curlRequest(
            $airtopApiUrl . 'sessions/' . $sessionId . '/windows',
            $headers,
            ['url' => $base_url],
            'POST'
        );
        
        if (!isset($windowResponse['decoded']['data']['windowId'])) {
            throw new Exception('Failed to create window for homepage');
        }
        
        $windowId = $windowResponse['decoded']['data']['windowId'];
        
        // Wait for page to load
        sleep(5);
        
        // Query the page for navigation links and page structure
        $crawl_prompt = "Extract ALL navigation links from this page, focusing on:
                        1. Top navigation/header menu (MOST IMPORTANT - check for 'Home', 'About', 'Locations', 'Menu', etc.)
                        2. Mobile menu/hamburger menu items
                        3. Footer links
                        4. Any dropdown or submenu items
                        
                        Run this JavaScript to get ALL links:
                        const allLinks = Array.from(document.querySelectorAll('a[href]')).map(a => {
                            const href = a.getAttribute('href');
                            const text = a.textContent.trim();
                            // Get parent nav element if exists
                            const inNav = a.closest('nav, header, [class*=\"nav\"], [class*=\"menu\"], [id*=\"nav\"], [id*=\"menu\"]');
                            return {
                                url: href,
                                text: text,
                                inNavigation: !!inNav
                            };
                        }).filter(link => 
                            link.url && 
                            !link.url.startsWith('#') && 
                            !link.url.startsWith('javascript:') &&
                            link.text.length > 0
                        );
                        
                        Return the EXACT href values as they appear in the HTML.
                        
                        IMPORTANT: 
                        - Include ALL links: regular pages, tel:, mailto:, everything
                        - Include ALL menu items: Home, About, Menu, Locations, Contact, etc.
                        - Return paths exactly as they appear
                        - Include phone numbers (tel:) and emails (mailto:)
                        
                        Categorize each page as one of these types:
                        - locations (store locations, find us, where to buy)
                        - hours (hours of operation, when we're open)
                        - contact (contact us, get in touch, support)
                        - signup (sign up, register, join, create account, rewards)
                        - menu (menu, products, offerings)
                        - about (about us, our story, history)
                        - careers (careers, jobs, work with us)
                        - faq (FAQ, help, questions)
                        - deals (deals, promotions, offers, specials)
                        - events (events, calendar, happenings)
                        - other (anything else)
                        
                        Also extract the page title and meta description.
                        
                        Return as JSON:
                        {
                            \"current_page\": {
                                \"title\": \"page title\",
                                \"meta_description\": \"meta description\"
                            },
                            \"links\": [
                                {
                                    \"url\": \"/exact/href/value\",
                                    \"text\": \"Link Text\",
                                    \"page_type\": \"locations\",
                                    \"confidence\": 0.95
                                }
                            ]
                        }";
        
        $queryResponse = $system->curlRequest(
            $airtopApiUrl . 'sessions/' . $sessionId . '/windows/' . $windowId . '/page-query',
            $headers,
            ['prompt' => $crawl_prompt],
            'POST'
        );
        
        if (isset($queryResponse['decoded']['data']['modelResponse'])) {
            $aiResponse = $queryResponse['decoded']['data']['modelResponse'];
            $crawl_data = json_decode($aiResponse, true);
            
            if ($debug) {
                $debug_output[] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'message' => 'Found ' . count($crawl_data['links'] ?? []) . ' links on homepage'
                ];
            }
            
            // Store homepage info
            $homepage_sql = "INSERT INTO bg_company_pages 
                           (company_id, url, page_type, page_title, meta_description, 
                            confidence_score, crawl_processor, crawl_depth, status, create_dt)
                           VALUES 
                           (:company_id, :url, 'homepage', :title, :meta_desc, 
                            1.00, 'abo_crawlsitepages', 0, 'active', NOW())
                           ON DUPLICATE KEY UPDATE 
                           page_title = VALUES(page_title),
                           meta_description = VALUES(meta_description),
                           modify_dt = NOW()";
            
            $database->query($homepage_sql, [
                'company_id' => $company['company_id'],
                'url' => $base_url,
                'title' => $crawl_data['current_page']['title'] ?? null,
                'meta_desc' => $crawl_data['current_page']['meta_description'] ?? null
            ]);
            
            $result['pages_discovered']++;
            
            // Process discovered links
            if (!empty($crawl_data['links'])) {
                foreach ($crawl_data['links'] as $link) {
                    // Skip empty URLs or anchors
                    if (empty($link['url']) || $link['url'] === '#') {
                        continue;
                    }
                    
                    // Skip duplicate home page (/ when we already have base_url)
                    if ($link['url'] === '/' || $link['url'] === '') {
                        continue;
                    }
                    
                    // Keep tel: and mailto: but categorize them properly
                    $is_tel = strpos($link['url'], 'tel:') === 0;
                    $is_mailto = strpos($link['url'], 'mailto:') === 0;
                    
                    // Skip javascript: and pure anchors
                    if (preg_match('/^(javascript:|#)/', $link['url'])) {
                        if ($debug) {
                            $debug_output[] = [
                                'timestamp' => date('Y-m-d H:i:s'),
                                'message' => 'Skipping javascript/anchor URL: ' . $link['url']
                            ];
                        }
                        continue;
                    }
                    
                    // Skip if URL looks like an AIRTOP session ID (random string)
                    if (preg_match('/^[a-zA-Z0-9]{6,12}$/', trim($link['url'], '/'))) {
                        if ($debug) {
                            $debug_output[] = [
                                'timestamp' => date('Y-m-d H:i:s'),
                                'message' => 'Skipping AIRTOP session URL: ' . $link['url']
                            ];
                        }
                        continue;
                    }
                    
                    // Build full URL (but keep tel: and mailto: as-is)
                    $link_url = $link['url'];
                    
                    if (!$is_tel && !$is_mailto) {
                        if (strpos($link_url, 'http') !== 0) {
                            // Relative URL - make it absolute
                            if (strpos($link_url, '/') === 0) {
                                // Absolute path
                                $parsed = parse_url($base_url);
                                $link_url = $parsed['scheme'] . '://' . $parsed['host'] . $link_url;
                            } else {
                                // Relative path
                                $link_url = $base_url . '/' . $link_url;
                            }
                        }
                        
                        // Only process URLs from the same domain (skip external links)
                        $base_host = parse_url($base_url, PHP_URL_HOST);
                        $link_host = parse_url($link_url, PHP_URL_HOST);
                        
                        if ($link_host !== $base_host) {
                            continue; // Skip external links
                        }
                    }
                    
                    // Additional check: Skip URLs that have random-looking paths
                    $path = parse_url($link_url, PHP_URL_PATH);
                    if ($path && preg_match('/\/[a-zA-Z0-9]{6,12}$/', $path)) {
                        if ($debug) {
                            $debug_output[] = [
                                'timestamp' => date('Y-m-d H:i:s'),
                                'message' => 'Skipping URL with session-like path: ' . $link_url
                            ];
                        }
                        continue;
                    }
                    
                    // Better page type detection based on URL and text
                    $page_type = $link['page_type'] ?? 'other';
                    $confidence = $link['confidence'] ?? 0.5;
                    $link_text_lower = strtolower($link['text'] ?? '');
                    $url_lower = strtolower($link_url);
                    
                    // Special handling for tel: and mailto:
                    if ($is_tel) {
                        $page_type = 'phone';
                        $confidence = 1.0;
                    } elseif ($is_mailto) {
                        $page_type = 'email';
                        $confidence = 1.0;
                    } elseif (strpos($link_text_lower, 'location') !== false || strpos($url_lower, '/location') !== false) {
                        $page_type = 'locations';
                        $confidence = 0.95;
                    } elseif (strpos($link_text_lower, 'menu') !== false || strpos($url_lower, '/menu') !== false) {
                        $page_type = 'menu';
                        $confidence = 0.95;
                    } elseif (strpos($link_text_lower, 'about') !== false || strpos($url_lower, '/about') !== false) {
                        $page_type = 'about';
                        $confidence = 0.95;
                    } elseif (strpos($link_text_lower, 'contact') !== false || strpos($url_lower, '/contact') !== false) {
                        $page_type = 'contact';
                        $confidence = 0.95;
                    } elseif (strpos($link_text_lower, 'hour') !== false || strpos($url_lower, '/hour') !== false) {
                        $page_type = 'hours';
                        $confidence = 0.95;
                    } elseif ($link_text_lower === 'home' || $link_url === $base_url || $link_url === '/') {
                        $page_type = 'homepage';
                        $confidence = 1.0;
                    }
                    
                    // Store discovered page
                    $page_sql = "INSERT INTO bg_company_pages 
                               (company_id, url, page_type, page_title, 
                                confidence_score, crawl_processor, crawl_depth, parent_url, status, create_dt)
                               VALUES 
                               (:company_id, :url, :page_type, :page_title, 
                                :confidence, 'abo_crawlsitepages', 1, :parent_url, 'active', NOW())
                               ON DUPLICATE KEY UPDATE 
                               page_type = IF(confidence_score < VALUES(confidence_score), VALUES(page_type), page_type),
                               confidence_score = IF(confidence_score < VALUES(confidence_score), VALUES(confidence_score), confidence_score),
                               page_title = IF(page_title IS NULL OR page_title = '', VALUES(page_title), page_title),
                               modify_dt = NOW()";
                    
                    try {
                        $database->query($page_sql, [
                            'company_id' => $company['company_id'],
                            'url' => $link_url,
                            'page_type' => $page_type,
                            'page_title' => $link['text'] ?? null,
                            'confidence' => $confidence,
                            'parent_url' => $base_url
                        ]);
                        
                        $result['pages_discovered']++;
                        
                        if (!empty($link['page_type']) && $link['page_type'] !== 'other') {
                            $result['pages_categorized']++;
                        }
                    } catch (PDOException $e) {
                        // Ignore duplicate key errors
                        if ($e->getCode() != '23000') {
                            throw $e;
                        }
                    }
                }
            }
        }
        
        // Close window
        if (isset($windowId)) {
            $system->curlRequest(
                $airtopApiUrl . 'sessions/' . $sessionId . '/windows/' . $windowId,
                $headers,
                [],
                'DELETE'
            );
        }
        
        // Now crawl high-priority pages for better categorization
        $priority_pages_sql = "SELECT * FROM bg_company_pages 
                              WHERE company_id = :company_id 
                              AND page_type IN ('locations', 'hours', 'contact', 'signup', 'menu', 'deals')
                              AND crawl_depth = 1
                              LIMIT 5";
        $stmt = $database->query($priority_pages_sql, ['company_id' => $company['company_id']]);
        $priority_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($priority_pages as $page) {
            if ($debug) {
                $debug_output[] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'message' => 'Deep crawling priority page: ' . $page['url']
                ];
            }
            
            // Create window for this page
            $windowResponse = $system->curlRequest(
                $airtopApiUrl . 'sessions/' . $sessionId . '/windows',
                $headers,
                ['url' => $page['url']],
                'POST'
            );
            
            if (isset($windowResponse['decoded']['data']['windowId'])) {
                $windowId = $windowResponse['decoded']['data']['windowId'];
                sleep(3);
                
                // Query for meta information and confirmation of page type
                $verify_prompt = "Analyze this page and determine:
                                1. The page title
                                2. Meta description
                                3. What type of content this page contains (locations, hours, contact, signup, menu, deals, etc.)
                                4. Extract any specific relevant data (like business hours if it's an hours page)
                                
                                Return as JSON with structure:
                                {
                                    \"title\": \"page title\",
                                    \"meta_description\": \"meta description\",
                                    \"confirmed_type\": \"page type\",
                                    \"confidence\": 0.95,
                                    \"relevant_data\": {}
                                }";
                
                $verifyResponse = $system->curlRequest(
                    $airtopApiUrl . 'sessions/' . $sessionId . '/windows/' . $windowId . '/page-query',
                    $headers,
                    ['prompt' => $verify_prompt],
                    'POST'
                );
                
                if (isset($verifyResponse['decoded']['data']['modelResponse'])) {
                    $verify_data = json_decode($verifyResponse['decoded']['data']['modelResponse'], true);
                    
                    // Update page with verified information
                    $update_sql = "UPDATE bg_company_pages 
                                 SET page_title = :title,
                                     meta_description = :meta_desc,
                                     page_type = :page_type,
                                     confidence_score = :confidence,
                                     modify_dt = NOW()
                                 WHERE page_id = :page_id";
                    
                    $database->query($update_sql, [
                        'title' => $verify_data['title'] ?? $page['page_title'],
                        'meta_desc' => $verify_data['meta_description'] ?? null,
                        'page_type' => $verify_data['confirmed_type'] ?? $page['page_type'],
                        'confidence' => $verify_data['confidence'] ?? $page['confidence_score'],
                        'page_id' => $page['page_id']
                    ]);
                }
                
                // Close window
                $system->curlRequest(
                    $airtopApiUrl . 'sessions/' . $sessionId . '/windows/' . $windowId,
                    $headers,
                    [],
                    'DELETE'
                );
            }
        }
        
    } finally {
        // Always close the AIRTOP session
        if (isset($sessionId)) {
            $system->curlRequest(
                $airtopApiUrl . 'sessions/' . $sessionId,
                $headers,
                [],
                'DELETE'
            );
        }
    }
    
    // Update task status
    $progress_sql = "UPDATE bg_company_attributes 
                    SET description = 'completed', modify_dt = NOW()
                    WHERE company_id = :company_id 
                    AND type = 'onboarding_progress' 
                    AND name = 'abo_crawlsitepages'";
    $database->query($progress_sql, ['company_id' => $company['company_id']]);
    
    $result['message'] = sprintf('Discovered %d pages, categorized %d', 
                                 $result['pages_discovered'], 
                                 $result['pages_categorized']);
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    
    // Update task status to error
    if (!empty($company['company_id'])) {
        $progress_sql = "UPDATE bg_company_attributes 
                        SET description = 'error', modify_dt = NOW()
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_crawlsitepages'";
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