<?PHP
class ai {
    private $config;
  
    private function __construct($config = []) {
        $this->config = $this->processConfig($config);
    }

}
// class.ai.php
class AIX {
    private $config;
    private static $instance = null;
    
    private const DEFAULT_MODELS = [
        'anthropic_text' => 'claude-3-sonnet-20240229',
        'anthropic_code' => 'claude-3-opus-20240229',
        'anthropic_image' => 'claude-3-opus-20240229',
        'openai_text' => 'gpt-4',
        'openai_code' => 'gpt-4-turbo',
        'openai_image' => 'dall-e-3'
    ];
    
    private const REQUIRED_SETTINGS = [
        'api_key',
        'api_url'
    ];
    
    private const DEFAULT_SETTINGS = [
        'temperature' => 0.7,
        'max_tokens' => 1024
    ];
    
    private function __construct($config = []) {
        $this->config = $this->processConfig($config);
    }
    
    public static function getInstance($config = []): AI {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    private function processConfig($rawConfig): array {
        $processed = [];
        
        foreach ($rawConfig as $engine => $settings) {
            if (!is_array($settings)) continue;
            
            // Validate required settings
            foreach (self::REQUIRED_SETTINGS as $required) {
                if (!isset($settings[$required])) {
                    error_log("AI Configuration Error: Missing required setting '$required' for engine '$engine'");
                    continue 2; // Skip this engine
                }
            }
            
            // Start with default settings
            $processed[$engine] = self::DEFAULT_SETTINGS;
            
            // Add engine-specific model default if needed
            if (!isset($settings['model']) && isset(self::DEFAULT_MODELS[$engine])) {
                $settings['model'] = self::DEFAULT_MODELS[$engine];
            }
            
            // Merge provided settings
            $processed[$engine] = array_merge($processed[$engine], $settings);
            
            // Add engine type for internal use
            $processed[$engine]['engine_type'] = explode('_', $engine)[1] ?? 'text';
        }
        
        if (empty($processed)) {
            error_log("AI Configuration Error: No valid engine configurations found");
        }
        
        return $processed;
    }
    
    public function ask($prompt, $engine, $options = []): array {
        if (!isset($this->config[$engine])) {
            throw new Exception("Engine not configured: $engine");
        }
        
        $engine_config = $this->config[$engine];
        
        // Merge runtime options with engine config
        $config = array_merge($engine_config, $options);
        
        try {
            return $this->makeRequest(
                $config['api_url'],
                $this->buildHeaders($engine, $config),
                $this->buildPayload($engine, $prompt, $config)
            );
        } catch (Exception $e) {
            error_log("AI Request Error ($engine): " . $e->getMessage());
            throw $e;
        }
    }

    public function listEngines(string $type = null): array {
        if ($type === null) {
            return array_keys($this->config);
        }
        
        return array_keys(array_filter($this->config, function($config) use ($type) {
            return $config['engine_type'] === $type;
        }));
    }

    public function getEngineConfig($engine): ?array {
        return $this->config[$engine] ?? null;
    }
}
