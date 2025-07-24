<?php
// abo_grabage.php - Extract age requirements for birthday rewards
// Part of the Automation Business Onboarding (ABO) system
error_reporting(E_ALL);
ini_set('display_errors', 1);
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get company ID - support both encoded and raw for debugging
$specific_company_id = null;

if (isset($_GET['rawid'])) {
    // Debug mode - use raw ID directly
    $specific_company_id = intval($_GET['rawid']);
} elseif (isset($_GET['id'])) {
    // Production mode - decode the ID
    $encoded_id = $_GET['id'];
    $specific_company_id = $qik->decodeID($encoded_id);
}

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'processor' => 'abo_grabage',
    'processed' => 0,
    'successful' => 0,
    'failed' => 0,
    'errors' => []
];

try {
    // Get companies to process
    if ($specific_company_id) {
        $sql = "SELECT c.*, 
                (SELECT name FROM bg_company_attributes 
                 WHERE company_id = c.company_id 
                 AND type = 'category' 
                 AND status = 'active' 
                 LIMIT 1) as category_name
                FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.company_id = :company_id 
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_grabage'
                AND ca.description IN ('pending', 'error', 'attempted')
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
    } else {
        // Get next company with pending age collection
        $sql = "SELECT c.*, 
                (SELECT name FROM bg_company_attributes 
                 WHERE company_id = c.company_id 
                 AND type = 'category' 
                 AND status = 'active' 
                 LIMIT 1) as category_name
                FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.status = 'approved_pending_data'
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_grabage'
                AND ca.description = 'pending'
                ORDER BY c.create_dt ASC
                LIMIT 1";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $result['message'] = 'No companies pending age requirements collection';
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    $result['processed'] = 1;
    $company_id = $company['company_id'];
    $company_name = $company['company_name'];
    $category_name = $company['category_name'] ?? '';
    
    try {
        $database->beginTransaction();
        
        // Update progress to in_progress
        $progress_sql = "UPDATE bg_company_attributes 
                        SET description = 'in_progress', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grabage'";
        $database->query($progress_sql, ['company_id' => $company_id]);
        
        // Initialize age requirements with defaults
        $age_requirements = [
            'minimum_age' => 13,  // Default minimum
            'maximum_age' => 250, // Default maximum
            'source' => 'default',
            'confidence' => 'low',
            'notes' => []
        ];
        
        // Step 1: Check category-based rules first
        $category_rules = [
            // Adult categories (21+)
            'cannabis' => ['min' => 21, 'max' => 250, 'reason' => 'Cannabis industry standard'],
            'marijuana' => ['min' => 21, 'max' => 250, 'reason' => 'Cannabis industry standard'],
            'dispensary' => ['min' => 21, 'max' => 250, 'reason' => 'Cannabis industry standard'],
            'alcohol' => ['min' => 21, 'max' => 250, 'reason' => 'Alcohol age requirement'],
            'bar' => ['min' => 21, 'max' => 250, 'reason' => 'Bar/nightclub age requirement'],
            'nightclub' => ['min' => 21, 'max' => 250, 'reason' => 'Nightclub age requirement'],
            'wine' => ['min' => 21, 'max' => 250, 'reason' => 'Alcohol age requirement'],
            'brewery' => ['min' => 21, 'max' => 250, 'reason' => 'Alcohol age requirement'],
            
            // Vehicle rental (25+)
            'car rental' => ['min' => 25, 'max' => 250, 'reason' => 'Car rental age requirement'],
            'vehicle rental' => ['min' => 25, 'max' => 250, 'reason' => 'Vehicle rental age requirement'],
            
            // Kids/teens categories
            'kids' => ['min' => 4, 'max' => 16, 'reason' => 'Kids-focused business'],
            'children' => ['min' => 4, 'max' => 16, 'reason' => 'Children-focused business'],
            'toy' => ['min' => 4, 'max' => 16, 'reason' => 'Toy store target demographic'],
            'teen' => ['min' => 13, 'max' => 19, 'reason' => 'Teen-focused business'],
            
            // Health/Fitness (18+ common for supplements)
            'supplement' => ['min' => 18, 'max' => 250, 'reason' => 'Supplement age recommendations'],
            'nutrition' => ['min' => 18, 'max' => 250, 'reason' => 'Nutrition supplement policy'],
            'fitness' => ['min' => 13, 'max' => 250, 'reason' => 'Fitness industry standard'],
            
            // General categories (standard 13+)
            'restaurant' => ['min' => 13, 'max' => 250, 'reason' => 'Standard restaurant policy'],
            'retail' => ['min' => 13, 'max' => 250, 'reason' => 'Standard retail policy'],
            'food' => ['min' => 13, 'max' => 250, 'reason' => 'Standard food service policy'],
        ];
        
        // Check if category matches any rules
        $category_lower = strtolower($category_name);
        foreach ($category_rules as $keyword => $rules) {
            if (strpos($category_lower, $keyword) !== false) {
                $age_requirements['minimum_age'] = $rules['min'];
                $age_requirements['maximum_age'] = $rules['max'];
                $age_requirements['source'] = 'category';
                $age_requirements['confidence'] = 'high';
                $age_requirements['notes'][] = "Category '{$category_name}' matched: {$rules['reason']}";
                break;
            }
        }
        
        // Step 2: Try to get more specific info from website
        $urls_to_check = [];
        
        // Get terms URL if available
        $terms_sql = "SELECT description FROM bg_company_attributes 
                     WHERE company_id = :company_id 
                     AND type = 'url' 
                     AND name = 'terms' 
                     AND `grouping` = 'policies' 
                     AND status = 'active'
                     LIMIT 1";
        $terms_stmt = $database->query($terms_sql, ['company_id' => $company_id]);
        $terms_row = $terms_stmt->fetch(PDO::FETCH_ASSOC);
        if ($terms_row) {
            $urls_to_check['terms'] = $terms_row['description'];
        }
        
        // Get signup URL if available
        if (!empty($company['signup_url'])) {
            $urls_to_check['signup'] = $company['signup_url'];
        }
        
        // Get rewards program URL if available
        $rewards_sql = "SELECT description FROM bg_company_attributes 
                       WHERE company_id = :company_id 
                       AND type = 'data_collection' 
                       AND name = 'program_urls_found' 
                       AND status = 'active'
                       ORDER BY create_dt DESC
                       LIMIT 1";
        $rewards_stmt = $database->query($rewards_sql, ['company_id' => $company_id]);
        $rewards_row = $rewards_stmt->fetch(PDO::FETCH_ASSOC);
        if ($rewards_row) {
            $program_urls = json_decode($rewards_row['description'], true);
            if (!empty($program_urls[0])) {
                $urls_to_check['rewards'] = $program_urls[0];
            }
        }
        
        // Age detection patterns
        $age_patterns = [
            // Minimum age patterns
            '/must\s+be\s+(\d+)\s*(?:\+|years?|or\s+older)/i' => 'minimum',
            '/age\s+(\d+)\s*(?:\+|and\s+(?:over|above|older))/i' => 'minimum',
            '/(\d+)\s*years?\s+(?:of\s+age|old)\s+or\s+older/i' => 'minimum',
            '/minimum\s+age\s*[:=]?\s*(\d+)/i' => 'minimum',
            '/at\s+least\s+(\d+)\s*years?/i' => 'minimum',
            '/(\d+)\+\s*only/i' => 'minimum',
            '/participants?\s+must\s+be\s+(\d+)/i' => 'minimum',
            '/open\s+to\s+(?:individuals?|persons?)\s+(\d+)/i' => 'minimum',
            
            // Maximum age patterns (kids)
            '/(?:children?|kids?)\s+(?:under|below)\s+(\d+)/i' => 'maximum',
            '/ages?\s+\d+\s*-\s*(\d+)/i' => 'maximum',
            '/up\s+to\s+age\s+(\d+)/i' => 'maximum',
            '/(\d+)\s+and\s+under/i' => 'maximum',
            '/maximum\s+age\s*[:=]?\s*(\d+)/i' => 'maximum',
            
            // Age range patterns
            '/ages?\s+(\d+)\s*-\s*(\d+)/i' => 'range',
            '/between\s+(?:ages?\s+)?(\d+)\s+and\s+(\d+)/i' => 'range',
            '/(\d+)\s+to\s+(\d+)\s+years?/i' => 'range',
            
            // Legal age references
            '/legal\s+age/i' => 'legal',
            '/age\s+of\s+majority/i' => 'legal',
            '/adult/i' => 'adult',
            '/minors?\s+(?:not\s+)?(?:allowed|permitted|eligible)/i' => 'minor_restriction'
        ];
        
        $found_ages = [];
        
        foreach ($urls_to_check as $url_type => $url) {
            // Fetch the page
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && !empty($html)) {
                // Extract text content
                $dom = new DOMDocument();
                @$dom->loadHTML($html);
                
                // Remove script and style elements
                $xpath = new DOMXPath($dom);
                foreach ($xpath->query('//script|//style') as $node) {
                    $node->parentNode->removeChild($node);
                }
                
                $text = $dom->textContent;
                $text = preg_replace('/\s+/', ' ', $text);
                
                // Search for age patterns
                foreach ($age_patterns as $pattern => $type) {
                    if (preg_match_all($pattern, $text, $matches)) {
                        foreach ($matches[0] as $i => $match) {
                            if ($type === 'minimum' && isset($matches[1][$i])) {
                                $age = intval($matches[1][$i]);
                                if ($age >= 4 && $age <= 100) {
                                    $found_ages[] = [
                                        'type' => 'minimum',
                                        'age' => $age,
                                        'source' => $url_type,
                                        'context' => $match
                                    ];
                                }
                            } elseif ($type === 'maximum' && isset($matches[1][$i])) {
                                $age = intval($matches[1][$i]);
                                if ($age >= 4 && $age <= 100) {
                                    $found_ages[] = [
                                        'type' => 'maximum',
                                        'age' => $age,
                                        'source' => $url_type,
                                        'context' => $match
                                    ];
                                }
                            } elseif ($type === 'range' && isset($matches[1][$i]) && isset($matches[2][$i])) {
                                $min_age = intval($matches[1][$i]);
                                $max_age = intval($matches[2][$i]);
                                if ($min_age >= 4 && $max_age <= 100 && $min_age < $max_age) {
                                    $found_ages[] = [
                                        'type' => 'range',
                                        'min_age' => $min_age,
                                        'max_age' => $max_age,
                                        'source' => $url_type,
                                        'context' => $match
                                    ];
                                }
                            } elseif ($type === 'legal' || $type === 'adult') {
                                $found_ages[] = [
                                    'type' => $type,
                                    'age' => 18, // Assume 18 for "legal age" or "adult"
                                    'source' => $url_type,
                                    'context' => $match
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        // Process found ages
        if (!empty($found_ages)) {
            // Find the most restrictive minimum age
            $min_ages = [];
            $max_ages = [];
            
            foreach ($found_ages as $found) {
                if ($found['type'] === 'minimum' || $found['type'] === 'legal' || $found['type'] === 'adult') {
                    $min_ages[] = $found['age'];
                    $age_requirements['notes'][] = "Found minimum age {$found['age']} in {$found['source']}: '{$found['context']}'";
                } elseif ($found['type'] === 'maximum') {
                    $max_ages[] = $found['age'];
                    $age_requirements['notes'][] = "Found maximum age {$found['age']} in {$found['source']}: '{$found['context']}'";
                } elseif ($found['type'] === 'range') {
                    $min_ages[] = $found['min_age'];
                    $max_ages[] = $found['max_age'];
                    $age_requirements['notes'][] = "Found age range {$found['min_age']}-{$found['max_age']} in {$found['source']}: '{$found['context']}'";
                }
            }
            
            if (!empty($min_ages)) {
                $age_requirements['minimum_age'] = max($min_ages); // Use the most restrictive
                $age_requirements['source'] = 'website';
                $age_requirements['confidence'] = 'high';
            }
            
            if (!empty($max_ages)) {
                $age_requirements['maximum_age'] = min($max_ages); // Use the most restrictive
                $age_requirements['source'] = 'website';
                $age_requirements['confidence'] = 'high';
            }
        }
        
        // Store the age requirements
        $age_sql = "INSERT INTO bg_company_attributes 
                   (company_id, type, name, description, status, create_dt)
                   VALUES 
                   (:company_id, 'age_requirements', 'birthday_program', :data, 'active', NOW())
                   ON DUPLICATE KEY UPDATE 
                   description = VALUES(description),
                   modify_dt = NOW()";
        $database->query($age_sql, [
            'company_id' => $company_id,
            'data' => json_encode($age_requirements)
        ]);
        
        // Also store simplified min/max values
        $min_sql = "INSERT INTO bg_company_attributes 
                   (company_id, type, name, description, status, create_dt)
                   VALUES 
                   (:company_id, 'requirement', 'minimum_age', :age, 'active', NOW())
                   ON DUPLICATE KEY UPDATE 
                   description = VALUES(description),
                   modify_dt = NOW()";
        $database->query($min_sql, [
            'company_id' => $company_id,
            'age' => $age_requirements['minimum_age']
        ]);
        
        $max_sql = "INSERT INTO bg_company_attributes 
                   (company_id, type, name, description, status, create_dt)
                   VALUES 
                   (:company_id, 'requirement', 'maximum_age', :age, 'active', NOW())
                   ON DUPLICATE KEY UPDATE 
                   description = VALUES(description),
                   modify_dt = NOW()";
        $database->query($max_sql, [
            'company_id' => $company_id,
            'age' => $age_requirements['maximum_age']
        ]);
        
        // Update progress status
        $complete_sql = "UPDATE bg_company_attributes 
                        SET description = 'completed', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grabage'";
        $database->query($complete_sql, ['company_id' => $company_id]);
        
        $database->commit();
        
        $result['successful'] = 1;
        $result['data_collected'] = "Age requirements: {$age_requirements['minimum_age']}-{$age_requirements['maximum_age']} (source: {$age_requirements['source']}, confidence: {$age_requirements['confidence']})";
        $result['age_requirements'] = $age_requirements;
        
    } catch (Exception $e) {
        $database->rollBack();
        
        // Update progress to error
        $error_sql = "UPDATE bg_company_attributes 
                     SET description = 'error', modify_dt = NOW() 
                     WHERE company_id = :company_id 
                     AND type = 'onboarding_progress' 
                     AND name = 'abo_grabage'";
        $database->query($error_sql, ['company_id' => $company_id]);
        
        $result['failed'] = 1;
        $result['errors'][] = "Company $company_id: " . $e->getMessage();
        error_log("ABO grab age error for company $company_id: " . $e->getMessage());
    }
    
    $result['message'] = "Processed {$result['processed']} company: {$result['successful']} successful, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    error_log("ABO grab age fatal error: " . $e->getMessage());
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);