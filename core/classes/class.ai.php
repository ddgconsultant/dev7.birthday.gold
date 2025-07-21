<?PHP

class AI {
    private $system;
    private $sitesettings_ai;
    private array $engineConfigs;
    private string $currentEngine;
    private string $currentType;
    private bool $debug = false;  // Debug flag


    
    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function __construct($system, $sitesettings_ai) {
        $this->system = $system;
        $this->sitesettings_ai = $sitesettings_ai;
        
        // Initialize engine configurations based on sitesettings
        $this->initializeEngineConfigs();
        
        // Default to goldie engines
      #  $this->currentEngine = 'openai_goldie';
        $this->currentEngine = 'anthropic_goldie';
        $this->currentType = 'text';
    }

    
    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Set debug mode
    public function setDebug(bool $debug = true): void {
        $this->debug = $debug;
        if ($this->debug) {
            error_log("AI Debug Mode: ENABLED");
        }
    }
    
    // Toggle debug mode
    public function toggleDebug(): void {
        $this->debug = !$this->debug;
        error_log("AI Debug Mode: " . ($this->debug ? "ENABLED" : "DISABLED"));
    }
    
    // Get debug status
    public function isDebug(): bool {
        return $this->debug;
    }
    
    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function debugLog(string $message, $data = null): void {
        if (!$this->debug) return;
        
        $timestamp = date('Y-m-d H:i:s');
        error_log("[$timestamp] AI DEBUG: $message");
        if ($data !== null) {
            error_log("[$timestamp] AI DEBUG DATA: " . print_r($data, true));
        }
    }
    
    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  private function initializeEngineConfigs() {
    $this->debugLog("Initializing engine configurations");
    
    // Find all configured engines by checking for api_key entries
    foreach ($this->sitesettings_ai['ai'] as $engine => $config) {
        if (isset($config['api_key'])) {
            $engineType = $this->determineEngineType($engine);
            
            $this->engineConfigs[$engine] = [
                'api_key' => $config['api_key'],
                'url' => $config['api_url'],
                'model' => $config['model'] ?? null,
                'temperature' => $config['temperature'] ?? 0.7,
                'max_tokens' => $config['max_tokens'] ?? 1024,
                'type' => $engineType,
                'supported_types' => ['text', 'computer-use'], // Add computer-use as supported type
                'headers' => $this->getHeadersConfig($engine),
                'format_data' => $this->getFormatDataConfig($engine)
            ];
            
            $this->debugLog("Configured engine: $engine", [
                'type' => $engineType,
                'model' => $config['model'] ?? 'default',
                'url' => $config['api_url']
            ]);
        }
    }
}

    
    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function determineEngineType(string $engine): string {
        if (strpos($engine, '_image') !== false) {
            return 'image';
        }
        return 'text';
    }


    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function getHeadersConfig(string $engine): callable {
        $configs = [
            'openai' => function($config) {
                return [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $config['api_key']
                ];
            },
            'anthropic' => function($config) {
                $headers = [
                    'Content-Type: application/json',
                    'x-api-key: ' . $config['api_key'],
                    'anthropic-version: 2023-06-01'
                ];
                
                // Add beta header if computer-use type
                if ($this->currentType === 'computer-use') {
                    $headers[] = 'anthropic-beta: computer-use-2024-10-22';
                }
                
                return $headers;
            },
            'gemini' => function($config) {
                return [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $config['api_key']
                ];
            }
        ];

        foreach ($configs as $provider => $config) {
            if (strpos($engine, $provider) === 0) {
                return $config;
            }
        }

        throw new Exception("Unknown provider for engine: $engine");
    }

    
    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  private function getFormatDataConfig(string $engine): callable {
    $configs = [
        'openai' => function($messages, $options, $config) {
            // Ensure numeric values are properly cast to integers
            return array_merge([
                'model' => $config['model'],
                'max_tokens' => intval($config['max_tokens']),
                'temperature' => floatval($config['temperature']),
                'messages' => $messages
            ], $this->sanitizeOptions($options));
        },
        'anthropic' => function($messages, $options, $config) {
            $systemMessage = '';
            $userMessage = '';
            foreach ($messages as $message) {
                if ($message['role'] === 'system') {
                    $systemMessage = $message['content'];
                } elseif ($message['role'] === 'user') {
                    $userMessage = $message['content'];
                }
            }
            
            return array_merge([
                'model' => $config['model'],
                'max_tokens' => intval($config['max_tokens']),
                'temperature' => floatval($config['temperature']),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $systemMessage ? "$systemMessage\n\n$userMessage" : $userMessage
                    ]
                ]
            ], $this->sanitizeOptions($options));
        },
        'gemini' => function($messages, $options, $config) {
            return array_merge([
                'model' => $config['model'],
                'temperature' => floatval($config['temperature']),
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $messages[count($messages)-1]['content']]
                        ]
                    ]
                ]
            ], $this->sanitizeOptions($options));
        }
    ];

    foreach ($configs as $provider => $config) {
        if (strpos($engine, $provider) === 0) {
            return $config;
        }
    }

    throw new Exception("Unknown provider for engine: $engine");
}



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
// Add helper method to sanitize option values
private function sanitizeOptions(array $options): array {
    $sanitized = [];
    foreach ($options as $key => $value) {
        switch ($key) {
            case 'max_tokens':
                $sanitized[$key] = intval($value);
                break;
            case 'temperature':
            case 'top_p':
            case 'presence_penalty':
            case 'frequency_penalty':
                $sanitized[$key] = floatval($value);
                break;
            default:
                $sanitized[$key] = $value;
        }
    }
    return $sanitized;
}



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
private function debugConfig(string $engine='') {
    if (empty($engine)) $engine = $this->currentEngine;
    
    $config = $this->sitesettings_ai['ai'][$engine] ?? [];
    
    if ($this->debug) {
        echo "\n<pre style='background:#f4f4f4; padding:10px; border:1px solid #ddd;'>\n";
        echo "=== AI ENGINE CONFIGURATION ===\n";
        echo "Current Engine: $engine\n";
        echo "Current Type: {$this->currentType}\n";
        echo "Debug Mode: ENABLED\n\n";
        
        echo "Raw Config:\n";
        print_r($config);
        
        if (isset($this->engineConfigs[$engine])) {
            echo "\nProcessed Config:\n";
            $processedConfig = $this->engineConfigs[$engine];
            // Hide API key for security
            $processedConfig['api_key'] = substr($processedConfig['api_key'], 0, 10) . '...';
            print_r($processedConfig);
        }
        
        echo "\nAll Available Engines:\n";
        print_r(array_keys($this->engineConfigs));
        echo "</pre>\n";
    }
    
    return $config;
}
    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function setEngine(string $engine, ?string $type = null): void {
    $this->debugLog("Setting engine to: $engine, type: " . ($type ?? 'default'));
    
    if (!isset($this->engineConfigs[$engine])) {
        throw new Exception("Unknown AI engine: $engine");
    }

    // Default to just 'text' type if no types are configured
    $typeString = $this->sitesettings_ai['ai'][$engine]['types'] ?? 'text';
    $supportedTypes = array_map('trim', explode(',', $typeString));
    
    $this->debugLog("Supported types for $engine", $supportedTypes);
    
    if ($type && !in_array($type, $supportedTypes)) {
        throw new Exception("Engine $engine is not configured for $type processing");
    }

    $this->currentEngine = $engine;
    $this->currentType = $type ?? 'text';
    
    $this->debugLog("Engine set successfully", [
        'engine' => $this->currentEngine,
        'type' => $this->currentType
    ]);
}

    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Rest of the class methods remain largely the same, but use the new config structure
    public function process($messages, array $options = []): array {
        try {
            $config = $this->engineConfigs[$this->currentEngine];
            
            $this->debugLog("Processing request", [
                'engine' => $this->currentEngine,
                'type' => $this->currentType,
                'options' => $options
            ]);
            
            // Transform string input into proper message format
            if (is_string($messages)) {
                $messages = [
                    ['role' => 'system', 'content' => 'You are a helpful assistant that knows everything about the online service birthday.gold located at https://birthday.gold.'],
                    ['role' => 'user', 'content' => $messages]
                ];
            }
            
            $this->debugLog("Messages prepared", $messages);
    
            // Format data according to engine specifications
            $data = $config['format_data']($messages, $options, $config);
            
            $this->debugLog("Formatted data for API", $data);
    
            // Get base headers for the current engine
            $headers = $config['headers']($config);
    
            // Add additional headers if computer-use type
            if ($this->currentType === 'computer-use') {
                $headers[] = 'anthropic-beta: computer-use-2024-10-22';
            }
            
            $this->debugLog("Request headers", $headers);
            $this->debugLog("Request URL", $config['url']);
    
            // Make API request
            $response = $this->system->curlRequest(
                $config['url'],
                $headers,
                $data
            );
            
            $this->debugLog("API Response received", $response);
    
            if (!$response || isset($response['error'])) {
                throw new Exception($response['error'] ?? 'Unknown error occurred');
            }
    
            return $response;
    
        } catch (Exception $e) {
            $this->debugLog("Error in process()", $e->getMessage());
            
            return [
                'error' => $e->getMessage(),
                'decoded' => [
                    'error' => true,
                    'message' => $e->getMessage(),
                    'usage' => $this->getEmptyStats()
                ]
            ];
        }
    }
    

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------

    public function summarizeText(string $text, array $options = []) {
        $this->setEngine('anthropic_goldie', 'text');
        
        // Default options
        $defaultOptions = [
            'max_tokens' => 512,
            'temperature' => 0.5
        ];
        $options = array_merge($defaultOptions, $options);
        
        if ($this->debug) {
            echo "\n<div style='background:#f0f8ff; padding:15px; border:2px solid #4CAF50; margin:10px 0;'>\n";
            echo "<h3 style='margin-top:0; color:#4CAF50;'>📝 Text Summarization Debug</h3>\n";
            
            // Show input text info
            $textLength = strlen($text);
            $wordCount = str_word_count($text);
            echo "<h4>Input Text Analysis:</h4>\n";
            echo "<ul>\n";
            echo "<li><strong>Character count:</strong> " . number_format($textLength) . "</li>\n";
            echo "<li><strong>Word count:</strong> " . number_format($wordCount) . "</li>\n";
            echo "<li><strong>First 200 chars:</strong> <code>" . htmlspecialchars(substr($text, 0, 200)) . "...</code></li>\n";
            echo "</ul>\n";
            
            // Show options
            echo "<h4>Summarization Options:</h4>\n";
            echo "<pre style='background:#e8e8e8; padding:10px;'>" . print_r($options, true) . "</pre>\n";
        }
        
        $prompt = "Please summarize the following text clearly and concisely:\n\n" . $text;
        
        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];
        
        $this->debugLog("Summarization request", [
            'text_length' => strlen($text),
            'word_count' => str_word_count($text),
            'options' => $options
        ]);
        
        if ($this->debug) {
            echo "<h4>Prompt Structure:</h4>\n";
            echo "<pre style='background:#e8e8e8; padding:10px; max-height:200px; overflow-y:auto;'>";
            echo htmlspecialchars(print_r($messages, true));
            echo "</pre>\n";
            
            echo "<h4>Processing...</h4>\n";
            flush(); // Send output to browser immediately
        }
        
        // Process the request
        $startTime = microtime(true);
        $response = $this->process($messages, $options);
        $endTime = microtime(true);
        $processingTime = round(($endTime - $startTime) * 1000, 2);
        
        $this->debugLog("Summarization complete", [
            'processing_time_ms' => $processingTime,
            'has_error' => isset($response['error'])
        ]);
        
        if ($this->debug) {
            echo "<h4>Results:</h4>\n";
            
            if (isset($response['error'])) {
                echo "<p style='color:red;'><strong>❌ Error:</strong> " . htmlspecialchars($response['error']) . "</p>\n";
            } else {
                $normalized = $this->normalizeResponse($response);
                $summaryLength = strlen($normalized['content']);
                $summaryWords = str_word_count($normalized['content']);
                $compressionRatio = round((1 - ($summaryLength / $textLength)) * 100, 1);
                
                echo "<div style='background:#e8ffe8; padding:10px; border-left:4px solid #4CAF50;'>\n";
                echo "<p style='color:green;'><strong>✅ Summary Generated Successfully!</strong></p>\n";
                echo "<p><strong>Summary:</strong></p>\n";
                echo "<blockquote style='background:white; padding:10px; border-left:3px solid #ccc;'>" . 
                     nl2br(htmlspecialchars($normalized['content'])) . "</blockquote>\n";
                echo "</div>\n";
                
                echo "<h4>Summary Statistics:</h4>\n";
                echo "<ul>\n";
                echo "<li><strong>Summary length:</strong> " . number_format($summaryLength) . " characters (" . 
                     number_format($summaryWords) . " words)</li>\n";
                echo "<li><strong>Compression ratio:</strong> {$compressionRatio}% reduction</li>\n";
                echo "<li><strong>Processing time:</strong> {$processingTime}ms</li>\n";
                echo "<li><strong>Tokens used:</strong> ";
                echo "Prompt: {$normalized['usage']['prompt_tokens']}, ";
                echo "Completion: {$normalized['usage']['completion_tokens']}, ";
                echo "Total: {$normalized['usage']['total_tokens']}</li>\n";
                echo "</ul>\n";
                
                // Cost estimation (example rates)
                $costEstimate = $this->estimateCost($normalized['usage']);
                if ($costEstimate) {
                    echo "<p><strong>Estimated cost:</strong> $" . number_format($costEstimate, 4) . "</p>\n";
                }
            }
            
            echo "</div>\n";
        }
       # breakpoint($response);
        // Return the summary content
        $normalized = $this->normalizeResponse($response);
       # $summaryLength = strlen($normalized['content']);
        return $normalized['content'];
    }
    
    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Helper method to estimate API costs (example rates, adjust as needed)
    private function estimateCost(array $usage): float {
        // Example rates for Anthropic Claude (adjust to actual rates)
        $rates = [
            'anthropic_goldie' => [
                'prompt' => 0.008 / 1000,      // $0.008 per 1K tokens
                'completion' => 0.024 / 1000    // $0.024 per 1K tokens
            ],
            'openai_goldie' => [
                'prompt' => 0.005 / 1000,       // $0.005 per 1K tokens
                'completion' => 0.015 / 1000    // $0.015 per 1K tokens
            ]
        ];
        
        if (!isset($rates[$this->currentEngine])) {
            return 0;
        }
        
        $engineRates = $rates[$this->currentEngine];
        $promptCost = $usage['prompt_tokens'] * $engineRates['prompt'];
        $completionCost = $usage['completion_tokens'] * $engineRates['completion'];
        
        return $promptCost + $completionCost;
    }
    

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  private function normalizeResponse(array $response): array {
    // Get the base response data
    $responseData = $response['decoded'] ?? $response;
    
    // Initialize normalized structure
    $normalized = [
        'engine' => $this->currentEngine,
        'model' => $this->engineConfigs[$this->currentEngine]['model'],
        'type' => 'text',
        'content' => '',
        'usage' => [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0
        ]
    ];

    // Handle different API response formats
    if ($this->currentEngine === 'anthropic_goldie') {
        // Handle content - it might be in different formats
        if (isset($responseData['content'])) {
            if (is_array($responseData['content'])) {
                // Extract text from content array
                $textContent = array_reduce($responseData['content'], function($carry, $item) {
                    if (isset($item['text'])) {
                        $carry .= $item['text'] . "\n";
                    }
                    return $carry;
                }, '');
                $normalized['content'] = trim($textContent);
            } else {
                $normalized['content'] = (string)$responseData['content'];
            }
        }
        
        // Handle usage data
        if (isset($responseData['usage'])) {
            $normalized['usage'] = [
                'prompt_tokens' => $responseData['usage']['input_tokens'] ?? 0,
                'completion_tokens' => $responseData['usage']['output_tokens'] ?? 0,
                'total_tokens' => ($responseData['usage']['input_tokens'] ?? 0) + 
                                ($responseData['usage']['output_tokens'] ?? 0)
            ];
        }
    }

    return $normalized;
}




  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function getNormalizedResponse(array $response): array {
    return $this->normalizeResponse($response);
}

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function displayMetrics(array $response): string {
    try {
        $normalizedResponse = $this->normalizeResponse($response);
        
        // Pass the normalized response to the metrics view
        $displayData = [
            'response' => $normalizedResponse,
            'engine' => $this->currentEngine,
            'model' => $this->engineConfigs[$this->currentEngine]['model'],
            'type' => 'text'
        ];
        
        global $dir;
        ob_start();
        extract($displayData);
        include($dir['core_components'] . '/../ai/ai-metrics.php');
        return ob_get_clean();
    } catch (Exception $e) {
        return '<div class="alert alert-danger">Error displaying metrics: ' . 
               htmlspecialchars($e->getMessage()) . '</div>';
    }
}
    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function getEmptyStats(): array {
        return [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
            'cached_tokens' => 0,
            'audio_tokens' => 0,
            'reasoning_tokens' => 0
        ];
    }


    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------

  public function test() {
    if ($this->debug) {
        echo "\n<div style='background:#e8f4f8; padding:15px; border:2px solid #2196F3; margin:10px 0;'>\n";
        echo "<h3 style='margin-top:0; color:#2196F3;'>🤖 AI Test Mode</h3>\n";
        
        // Show current configuration
        $this->debugConfig();
        
        echo "<h4>Running Test...</h4>\n";
    }
    
    $messages = [
        ['role' => 'user', 'content' => 'Hello AI, are you working? Please respond with a brief test message.']
    ];
    
    $testOptions = [
        'max_tokens' => 50,
        'temperature' => 0.5
    ];
    
    if ($this->debug) {
        echo "<p><strong>Test Message:</strong> " . htmlspecialchars($messages[0]['content']) . "</p>\n";
        echo "<p><strong>Test Options:</strong> max_tokens={$testOptions['max_tokens']}, temperature={$testOptions['temperature']}</p>\n";
    }
    
    $response = $this->process($messages, $testOptions);
    
    if ($this->debug) {
        echo "<h4>Test Results:</h4>\n";
        
        if (isset($response['error'])) {
            echo "<p style='color:red;'><strong>❌ Error:</strong> " . htmlspecialchars($response['error']) . "</p>\n";
        } else {
            $normalized = $this->normalizeResponse($response);
            echo "<p style='color:green;'><strong>✅ Success!</strong></p>\n";
            echo "<p><strong>Response:</strong> " . htmlspecialchars($normalized['content']) . "</p>\n";
            echo "<p><strong>Usage:</strong> ";
            echo "Prompt: {$normalized['usage']['prompt_tokens']} tokens, ";
            echo "Completion: {$normalized['usage']['completion_tokens']} tokens, ";
            echo "Total: {$normalized['usage']['total_tokens']} tokens</p>\n";
        }
        
        echo "</div>\n";
    }
    
    return $response;
}


    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getUsageStats(array $response): array {
    $normalized = $this->normalizeResponse($response);
    return $normalized['usage'];
}
}