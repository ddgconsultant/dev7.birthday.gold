<?PHP
$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set JSON header
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'suggestions' => [],
    'message' => ''
];

try {
    // Get search query
    $query = isset($_POST['query']) ? trim($_POST['query']) : '';
    
    if (empty($query)) {
        throw new Exception('No search query provided');
    }
    
    // Define available help topics with descriptions and keywords
    $helpTopics = [
        [
            'title' => 'How to Add Your Birthday',
            'url' => '/faq#how-do-i-add-my-birthday',
            'description' => 'Learn how to add or update your birthday information in your profile',
            'keywords' => ['birthday', 'add birthday', 'update birthday', 'profile', 'date of birth']
        ],
        [
            'title' => 'Managing Enrollments',
            'url' => '/faq#how-do-i-manage-enrollments',
            'description' => 'Control which birthday programs you are enrolled in',
            'keywords' => ['enrollment', 'programs', 'rewards', 'manage', 'opt out', 'unsubscribe']
        ],
        [
            'title' => 'Account Settings',
            'url' => '/myaccount/settings',
            'description' => 'Update your account information, email, and preferences',
            'keywords' => ['settings', 'account', 'email', 'password', 'preferences', 'profile']
        ],
        [
            'title' => 'Billing and Subscription',
            'url' => '/myaccount/billing',
            'description' => 'Manage your subscription, payment methods, and billing history',
            'keywords' => ['billing', 'payment', 'subscription', 'cancel', 'upgrade', 'card', 'invoice']
        ],
        [
            'title' => 'Privacy and Data',
            'url' => '/legalhub/privacy',
            'description' => 'Learn about how we protect your data and your privacy rights',
            'keywords' => ['privacy', 'data', 'security', 'information', 'rights', 'gdpr']
        ],
        [
            'title' => 'Technical Issues',
            'url' => '/contact',
            'description' => 'Get help with login problems, errors, or technical difficulties',
            'keywords' => ['error', 'problem', 'issue', 'login', 'password', 'technical', 'bug', 'not working']
        ],
        [
            'title' => 'Business Partnership',
            'url' => '/business/partner',
            'description' => 'Learn how businesses can partner with Birthday Gold',
            'keywords' => ['business', 'partner', 'merchant', 'restaurant', 'store', 'partnership']
        ],
        [
            'title' => 'Reward Redemption',
            'url' => '/myaccount/rewards',
            'description' => 'View and redeem your birthday rewards and special offers',
            'keywords' => ['redeem', 'reward', 'coupon', 'offer', 'use', 'claim', 'voucher']
        ]
    ];
    
    // Use AI to understand user intent and find best matches
    $ai->setEngine('anthropic_goldie', 'text');
    
    // Create context for AI
    $topicsContext = array_map(function($topic) {
        return sprintf(
            "- %s: %s (keywords: %s)",
            $topic['title'],
            $topic['description'],
            implode(', ', $topic['keywords'])
        );
    }, $helpTopics);
    
    $aiPrompt = "You are a help search assistant for Birthday Gold. A user is searching for: \"$query\"

Available help topics:
" . implode("\n", $topicsContext) . "

Based on the user's search query, identify the TOP 3 most relevant help topics. Consider:
1. Direct keyword matches
2. Semantic understanding of what the user is asking
3. Common variations and typos

Return ONLY a JSON array of the indices (0-based) of the top 3 most relevant topics in order of relevance.
Example: [2, 5, 1]

If the query doesn't match any topics well, return an empty array: []";

    // Process with AI
    $aiResponse = $ai->process($aiPrompt, [
        'temperature' => 0.3,
        'max_tokens' => 50
    ]);
    
    $normalizedResponse = $ai->getNormalizedResponse($aiResponse);
    $aiContent = trim($normalizedResponse['content']);
    
    // Parse AI response
    $matches = [];
    if (preg_match('/\[[\d,\s]*\]/', $aiContent, $jsonMatch)) {
        $indices = json_decode($jsonMatch[0], true);
        if (is_array($indices)) {
            foreach ($indices as $index) {
                if (isset($helpTopics[$index])) {
                    $matches[] = $helpTopics[$index];
                }
            }
        }
    }
    
    // If AI didn't find good matches, fall back to keyword search
    if (empty($matches)) {
        $queryLower = strtolower($query);
        foreach ($helpTopics as $topic) {
            $titleMatch = stripos($topic['title'], $query) !== false;
            $descMatch = stripos($topic['description'], $query) !== false;
            $keywordMatch = false;
            
            foreach ($topic['keywords'] as $keyword) {
                if (stripos($keyword, $queryLower) !== false || stripos($queryLower, $keyword) !== false) {
                    $keywordMatch = true;
                    break;
                }
            }
            
            if ($titleMatch || $descMatch || $keywordMatch) {
                $matches[] = $topic;
                if (count($matches) >= 3) break;
            }
        }
    }
    
    // Track the search
    session_tracking('help-ai-search', [
        'query' => $query,
        'results_count' => count($matches),
        'ai_used' => true,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    $response['success'] = true;
    $response['suggestions'] = $matches;
    $response['message'] = empty($matches) ? 'No results found. Try different keywords or contact support.' : '';
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Search error: ' . $e->getMessage();
    
    // Log error
    error_log('Help AI search error: ' . $e->getMessage());
}

echo json_encode($response);