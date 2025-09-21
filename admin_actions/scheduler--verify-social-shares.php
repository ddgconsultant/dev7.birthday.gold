<?php
/**
 * Social Media Share Verification Scheduler
 * Processes pending social media posts to verify #birthdaygold hashtag
 * Runs every 2 minutes via cron
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Initialize allocation manager
include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.allocationmanager.php');
$allocationManager = new AllocationManager($database);

class SocialShareVerifier {
    private $database;
    private $allocationManager;
    private $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:115.0) Gecko/20100101 Firefox/115.0'
    ];
    
    public function __construct($database, $allocationManager) {
        $this->database = $database;
        $this->allocationManager = $allocationManager;
    }
    
    public function processPendingShares() {
        // Get pending social share verifications from bg_user_allocations (max 20 per batch to avoid timeout)
        $pending = $this->database->getrows(
            "SELECT * FROM bg_user_allocations 
             WHERE allocation_type = 'social_share' 
             AND status = 'pending' 
             ORDER BY created_at ASC 
             LIMIT 20"
        );
        
        if (empty($pending)) {
            echo "No pending social shares to verify\n";
            return;
        }
        
        echo "Processing " . count($pending) . " pending social shares\n";
        
        foreach ($pending as $share) {
            // Parse the stored JSON data from allocation_comment
            $data = json_decode($share['allocation_comment'], true);
            $post_url = $data['url'] ?? '';
            $platform = $share['reference_type'] ?? $data['platform'] ?? 'unknown';
            
            echo "Verifying allocation ID {$share['allocation_id']} from user {$share['user_id']}...\n";
            
            $result = $this->verifyPost($post_url, $platform);
            
            if ($result['verified']) {
                echo "  ✓ Hashtag verified! Activating allocation...\n";
                
                // Update the allocation to active with 1 credit
                $data['hashtag_verified'] = true;
                $data['verified_at'] = date('Y-m-d H:i:s');
                $data['verification_result'] = $result;
                
                $this->database->query(
                    "UPDATE bg_user_allocations 
                     SET status = 'active', 
                         amount = 1,
                         allocation_comment = :comment,
                         first_used_at = NOW()
                     WHERE allocation_id = :id",
                    [
                        'comment' => json_encode($data),
                        'id' => $share['allocation_id']
                    ]
                );
                
                echo "  ✓ Allocation ID {$share['allocation_id']} activated with 1 credit\n";
            } else {
                echo "  ✗ Verification failed: {$result['reason']}\n";
                
                // Mark as failed
                $data['verification_result'] = $result;
                $data['failed_at'] = date('Y-m-d H:i:s');
                
                $this->database->query(
                    "UPDATE bg_user_allocations 
                     SET status = 'failed',
                         allocation_comment = :comment
                     WHERE allocation_id = :id",
                    [
                        'comment' => json_encode($data),
                        'id' => $share['allocation_id']
                    ]
                );
            }
            
            // Small delay between verifications to avoid rate limiting
            usleep(500000); // 0.5 second delay
        }
        
        echo "Social share verification completed\n";
    }
    
    private function verifyPost($url, $platform) {
        // Use cURL to fetch the page content
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => 'gzip, deflate',
            CURLOPT_HTTPHEADER => [
                'User-Agent: ' . $this->userAgents[array_rand($this->userAgents)],
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Referer: https://birthday.gold/'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false || !empty($error)) {
            return ['verified' => false, 'reason' => 'Network error: ' . $error];
        }
        
        if ($httpCode === 404) {
            return ['verified' => false, 'reason' => 'Post not found or has been deleted'];
        }
        
        // Check for hashtag in response (case-insensitive)
        $hashtags = ['#birthdaygold', '#BirthdayGold', '#BIRTHDAYGOLD'];
        foreach ($hashtags as $hashtag) {
            if (stripos($response, $hashtag) !== false) {
                return [
                    'verified' => true,
                    'hashtag' => $hashtag,
                    'snippet' => $this->extractSnippet($response, $hashtag)
                ];
            }
        }
        
        // Platform-specific checks if general search fails
        return $this->platformSpecificCheck($platform, $url, $response);
    }
    
    private function platformSpecificCheck($platform, $url, $html) {
        // Try to find hashtag in meta tags or structured data
        
        // Twitter/X - Check meta tags
        if ($platform === 'twitter') {
            // Check Open Graph description
            if (preg_match('/<meta\s+property="og:description"\s+content="([^"]*)"[^>]*>/i', $html, $matches)) {
                if (stripos($matches[1], '#birthdaygold') !== false) {
                    return ['verified' => true, 'snippet' => html_entity_decode($matches[1])];
                }
            }
            // Check Twitter card description
            if (preg_match('/<meta\s+name="twitter:description"\s+content="([^"]*)"[^>]*>/i', $html, $matches)) {
                if (stripos($matches[1], '#birthdaygold') !== false) {
                    return ['verified' => true, 'snippet' => html_entity_decode($matches[1])];
                }
            }
        }
        
        // Instagram - Check meta tags
        if ($platform === 'instagram') {
            // Instagram puts post content in meta tags
            if (preg_match('/<meta\s+property="og:title"\s+content="([^"]*)"[^>]*>/i', $html, $matches)) {
                if (stripos($matches[1], '#birthdaygold') !== false) {
                    return ['verified' => true, 'snippet' => html_entity_decode($matches[1])];
                }
            }
            if (preg_match('/<meta\s+name="description"\s+content="([^"]*)"[^>]*>/i', $html, $matches)) {
                if (stripos($matches[1], '#birthdaygold') !== false) {
                    return ['verified' => true, 'snippet' => html_entity_decode($matches[1])];
                }
            }
        }
        
        // Facebook - Check in JSON or meta tags
        if ($platform === 'facebook') {
            // Facebook often includes post text in JSON
            if (preg_match('/"text":"([^"]*#birthdaygold[^"]*)"/i', $html, $matches)) {
                $text = json_decode('"' . $matches[1] . '"');
                return ['verified' => true, 'snippet' => $text];
            }
            // Check Open Graph
            if (preg_match('/<meta\s+property="og:description"\s+content="([^"]*)"[^>]*>/i', $html, $matches)) {
                if (stripos($matches[1], '#birthdaygold') !== false) {
                    return ['verified' => true, 'snippet' => html_entity_decode($matches[1])];
                }
            }
        }
        
        // TikTok - Check in title or JSON
        if ($platform === 'tiktok') {
            // TikTok puts description in various places
            if (preg_match('/<title>([^<]*#birthdaygold[^<]*)<\/title>/i', $html, $matches)) {
                return ['verified' => true, 'snippet' => html_entity_decode($matches[1])];
            }
            if (preg_match('/"desc":"([^"]*#birthdaygold[^"]*)"/i', $html, $matches)) {
                return ['verified' => true, 'snippet' => json_decode('"' . $matches[1] . '"')];
            }
        }
        
        return ['verified' => false, 'reason' => 'Hashtag #birthdaygold not found in post'];
    }
    
    private function extractSnippet($html, $hashtag) {
        $pos = stripos($html, $hashtag);
        if ($pos === false) return '';
        
        // Try to get clean text around hashtag
        $start = max(0, $pos - 100);
        $snippet = substr($html, $start, 250);
        
        // Clean HTML tags and entities
        $snippet = strip_tags($snippet);
        $snippet = html_entity_decode($snippet);
        $snippet = preg_replace('/\s+/', ' ', $snippet);
        
        // Trim to reasonable length
        if (strlen($snippet) > 200) {
            $snippet = substr($snippet, 0, 200) . '...';
        }
        
        return trim($snippet);
    }
    
}

// Run the verifier
$verifier = new SocialShareVerifier($database, $allocationManager);
$verifier->processPendingShares();

echo "OK: Social share verification scheduler completed\n";
?>