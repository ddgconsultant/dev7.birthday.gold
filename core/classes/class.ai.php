<?PHP

class AI {
    private $system;
    private $sitesettings_ai;
    private array $engineConfigs;
    private string $currentEngine;
    private string $currentType;


    
    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function __construct($system, $sitesettings_ai) {
        $this->system = $system;
        $this->sitesettings_ai = $sitesettings_ai;
        
        // Initialize engine configurations based on sitesettings
        $this->initializeEngineConfigs();
        
        // Default to goldie engines
        $this->currentEngine = 'openai_goldie';
        $this->currentType = 'text';
    }

    
    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  private function initializeEngineConfigs() {
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
private function debugConfig(string $engine) {
    error_log("Engine Config for $engine: " . print_r($this->sitesettings_ai['ai'][$engine] ?? [], true));
}
    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function setEngine(string $engine, ?string $type = null): void {
    if (!isset($this->engineConfigs[$engine])) {
        throw new Exception("Unknown AI engine: $engine");
    }

    // Default to just 'text' type if no types are configured
    $typeString = $this->sitesettings_ai['ai'][$engine]['types'] ?? 'text';
    $supportedTypes = array_map('trim', explode(',', $typeString));
    
    // Debug line to see what we're working with
    // error_log("Engine: $engine, Type: $type, Supported Types: " . print_r($supportedTypes, true));
    
    if ($type && !in_array($type, $supportedTypes)) {
        throw new Exception("Engine $engine is not configured for $type processing");
    }

    $this->currentEngine = $engine;
    $this->currentType = $type ?? 'text';
}

    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Rest of the class methods remain largely the same, but use the new config structure
    public function process($messages, array $options = []): array {
        try {
            $config = $this->engineConfigs[$this->currentEngine];
            
            // Transform string input into proper message format
            if (is_string($messages)) {
                $messages = [
                    ['role' => 'system', 'content' => 'You are a helpful assistant that knows everything about the online service birthday.gold located at https://birthday.gold.'],
                    ['role' => 'user', 'content' => $messages]
                ];
            }
    
            // Format data according to engine specifications
            $data = $config['format_data']($messages, $options, $config);
    
            // Get base headers for the current engine
            $headers = $config['headers']($config);
    
            // Add additional headers if computer-use type
            if ($this->currentType === 'computer-use') {
                $headers[] = 'anthropic-beta: computer-use-2024-10-22';
            }
    
            // Make API request
            $response = $this->system->curlRequest(
                $config['url'],
                $headers,
                $data
            );
    
            if (!$response || isset($response['error'])) {
                throw new Exception($response['error'] ?? 'Unknown error occurred');
            }
    
            return $response;
    
        } catch (Exception $e) {
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
  public function getUsageStats(array $response): array {
    $normalized = $this->normalizeResponse($response);
    return $normalized['usage'];
}
}