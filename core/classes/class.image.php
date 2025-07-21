<?php

class image {
    private $system;
    private $database;
    private $config;
    private $tesseractPath;
    private $tempDir;
    private $logFile;
    
    public function __construct($system) {
        $this->system = $system;
        
        // Get database from system if available
        if (isset($system->database)) {
            $this->database = $system->database;
        }
        
        // Load configuration
        $this->loadConfig();
    }
    
    /**
     * Set database connection if needed later
     */
    public function setDatabase($database) {
        $this->database = $database;
    }
    
    /**
     * Load configuration from file or database
     */
    private function loadConfig() {
        $configFile = $_SERVER['DOCUMENT_ROOT'] . '/core/classes/config.image.php';
        
        if (file_exists($configFile)) {
            $this->config = require $configFile;
            $this->tesseractPath = $this->config['tesseract']['path'] ?? '';
            $this->tempDir = $this->config['paths']['temp'] ?? sys_get_temp_dir();
            $this->logFile = $this->config['paths']['logs'] . '/processing.log';
        } else {
            // Default configuration
            $this->config = [
                'tesseract' => ['enabled' => false],
                'nsfw' => ['enabled' => false],
                'paths' => [
                    'temp' => sys_get_temp_dir(),
                    'logs' => $_SERVER['DOCUMENT_ROOT'] . '/logs'
                ]
            ];
            $this->tempDir = sys_get_temp_dir();
        }
    }
    
    /**
     * Check if Tesseract is installed and configured
     */
    public function isTesseractAvailable() {
        if (empty($this->tesseractPath) || !file_exists($this->tesseractPath)) {
            return false;
        }
        
        $cmd = '"' . $this->tesseractPath . '" --version 2>&1';
        $output = shell_exec($cmd);
        
        return !empty($output) && stripos($output, 'tesseract') !== false;
    }
    
    /**
     * Extract text from image using OCR
     */
    public function extractText($imagePath, $options = []) {
        $startTime = microtime(true);
        
        try {
            if (!file_exists($imagePath)) {
                throw new Exception("Image file not found: $imagePath");
            }
            
            if (!$this->isTesseractAvailable()) {
                throw new Exception("Tesseract OCR is not available");
            }
            
            // Preprocess image if requested
            $processedImage = $imagePath;
            if ($options['preprocess'] ?? false) {
                $processedImage = $this->preprocessImage($imagePath, $options);
            }
            
            // Generate output filename
            $outputBase = $this->tempDir . '/ocr_' . uniqid();
            
            // Build command
            $language = $options['language'] ?? 'eng';
            $cmd = sprintf(
                '"%s" "%s" "%s" -l %s',
                $this->tesseractPath,
                $processedImage,
                $outputBase,
                $language
            );
            
            if (isset($options['psm'])) {
                $cmd .= ' --psm ' . intval($options['psm']);
            }
            
            // Execute
            $output = [];
            $returnCode = null;
            exec($cmd . ' 2>&1', $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception("Tesseract failed: " . implode("\n", $output));
            }
            
            // Read result
            $textFile = $outputBase . '.txt';
            if (!file_exists($textFile)) {
                throw new Exception("OCR output not found");
            }
            
            $text = file_get_contents($textFile);
            @unlink($textFile);
            
            if ($processedImage !== $imagePath) {
                @unlink($processedImage);
            }
            
            return [
                'success' => true,
                'text' => $text,
                'confidence' => $this->estimateOCRConfidence($text),
                'processing_time' => microtime(true) - $startTime,
                'language' => $language
            ];
            
        } catch (Exception $e) {
            $this->logError('OCR', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'text' => '',
                'processing_time' => microtime(true) - $startTime
            ];
        }
    }
    
    /**
     * Check image for NSFW content
     */
    public function checkNSFW($imagePath, $options = []) {
        $startTime = microtime(true);
        
        try {
            if (!file_exists($imagePath)) {
                throw new Exception("Image file not found");
            }
            
            $provider = $options['provider'] ?? 'local';
            
            if ($provider === 'local') {
                return $this->checkNSFWLocal($imagePath);
            } else {
                return $this->checkNSFWAPI($imagePath, $provider, $options);
            }
            
        } catch (Exception $e) {
            $this->logError('NSFW', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'is_nsfw' => false,
                'confidence' => 0,
                'processing_time' => microtime(true) - $startTime
            ];
        }
    }
    
    /**
     * Process email images
     */
    public function processEmailImages($emailImages) {
        $results = [];
        
        foreach ($emailImages as $image) {
            $tempFile = $this->tempDir . '/' . uniqid('email_') . '_' . $image['filename'];
            
            // Save to temp file
            file_put_contents($tempFile, $image['data']);
            
            // Process
            $result = [
                'filename' => $image['filename'],
                'mime_type' => $image['mime_type'] ?? '',
                'size' => strlen($image['data']),
                'ocr' => $this->extractText($tempFile, ['preprocess' => true]),
                'nsfw' => $this->checkNSFW($tempFile)
            ];
            
            // Generate warnings
            $result['warnings'] = $this->generateWarnings($result['ocr'], $result['nsfw']);
            
            @unlink($tempFile);
            $results[] = $result;
        }
        
        return $results;
    }
    
    /**
     * Batch process images
     */
    public function batchProcess($imagePaths, $operation = 'ocr', $options = []) {
        $results = [];
        
        foreach ($imagePaths as $path) {
            switch ($operation) {
                case 'ocr':
                    $results[$path] = $this->extractText($path, $options);
                    break;
                    
                case 'nsfw':
                    $results[$path] = $this->checkNSFW($path, $options);
                    break;
                    
                case 'both':
                    $results[$path] = [
                        'ocr' => $this->extractText($path, $options),
                        'nsfw' => $this->checkNSFW($path, $options)
                    ];
                    break;
            }
        }
        
        return $results;
    }
    
    /**
     * Get system information
     */
    public function getSystemInfo() {
        return [
            'os' => $this->detectOS(),
            'php_version' => PHP_VERSION,
            'tesseract_available' => $this->isTesseractAvailable(),
            'tesseract_path' => $this->tesseractPath,
            'tesseract_version' => $this->getTesseractVersion(),
            'temp_dir' => $this->tempDir,
            'extensions' => [
                'gd' => extension_loaded('gd'),
                'imagick' => extension_loaded('imagick'),
                'exif' => extension_loaded('exif')
            ]
        ];
    }
    
    /**
     * Detect operating system
     */
    public function detectOS() {
        if (stripos(PHP_OS, 'WIN') === 0) {
            return 'windows';
        } elseif (stripos(PHP_OS, 'DARWIN') === 0) {
            return 'macos';
        }
        return 'linux';
    }
    
    /**
     * Find Tesseract executable
     */
    public function findTesseract() {
        $os = $this->detectOS();
        
        // Common paths to check
        $paths = [
            '/usr/bin/tesseract',
            '/usr/local/bin/tesseract',
            '/opt/homebrew/bin/tesseract',
            'C:/Program Files/Tesseract-OCR/tesseract.exe',
            'C:/Program Files (x86)/Tesseract-OCR/tesseract.exe',
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // Try system command
        $cmd = $os === 'windows' ? 'where tesseract 2>nul' : 'which tesseract 2>/dev/null';
        $output = shell_exec($cmd);
        if ($output) {
            return trim($output);
        }
        
        return null;
    }
    
    /**
     * Get Tesseract version
     */
    public function getTesseractVersion() {
        if (!$this->tesseractPath) {
            return 'Not installed';
        }
        
        $cmd = '"' . $this->tesseractPath . '" --version 2>&1';
        $output = shell_exec($cmd);
        
        if (preg_match('/tesseract\s+(\d+\.\d+\.\d+)/i', $output, $matches)) {
            return $matches[1];
        }
        
        return 'Unknown';
    }
    
    /**
     * Get available languages
     */
    public function getAvailableLanguages() {
        if (!$this->isTesseractAvailable()) {
            return [];
        }
        
        $cmd = '"' . $this->tesseractPath . '" --list-langs 2>&1';
        $output = shell_exec($cmd);
        
        if ($output) {
            $lines = explode("\n", trim($output));
            return array_slice($lines, 1); // Skip header
        }
        
        return [];
    }
    
    /**
     * Generate configuration file
     */
    public function generateConfig($tesseractPath = null) {
        if (!$tesseractPath) {
            $tesseractPath = $this->findTesseract();
        }
        
        $config = [
            'tesseract' => [
                'enabled' => !empty($tesseractPath),
                'path' => $tesseractPath ?: '',
                'version' => $tesseractPath ? $this->getTesseractVersion() : '',
                'languages' => ['eng'],
                'default_language' => 'eng'
            ],
            'nsfw' => [
                'enabled' => true,
                'providers' => [
                    'local' => [
                        'enabled' => extension_loaded('gd') || extension_loaded('imagick'),
                        'threshold' => 0.7
                    ],
                    'api' => [
                        'enabled' => false,
                        'provider' => '',
                        'api_key' => ''
                    ]
                ]
            ],
            'paths' => [
                'temp' => $_SERVER['DOCUMENT_ROOT'] . '/temp/image_processing',
                'logs' => $_SERVER['DOCUMENT_ROOT'] . '/logs/image_processing'
            ],
            'processing' => [
                'max_file_size' => 10 * 1024 * 1024,
                'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
            ],
            'system' => [
                'os' => $this->detectOS(),
                'php_version' => PHP_VERSION,
                'generated' => date('Y-m-d H:i:s')
            ]
        ];
        
        $configContent = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        $configFile = $_SERVER['DOCUMENT_ROOT'] . '/core/classes/config.image.php';
        
        return file_put_contents($configFile, $configContent) !== false;
    }
    
    /**
     * Local NSFW checking implementation
     */
    private function checkNSFWLocal($imagePath) {
        $startTime = microtime(true);
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            throw new Exception("Invalid image file");
        }
        
        // Load image based on type
        $image = null;
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($imagePath);
                break;
        }
        
        if (!$image) {
            throw new Exception("Could not load image");
        }
        
        // Analyze
        $analysis = $this->analyzeImageContent($image, [
            0 => (int)$imageInfo[0],
            1 => (int)$imageInfo[1],
            'mime' => $imageInfo['mime']
        ]);
        $nsfwScore = $this->calculateNSFWScore($analysis);
        
        imagedestroy($image);
        
        $threshold = $this->config['nsfw']['providers']['local']['threshold'] ?? 0.7;
        
        return [
            'success' => true,
            'is_nsfw' => $nsfwScore >= $threshold,
            'confidence' => $nsfwScore,
            'analysis' => $analysis,
            'provider' => 'local',
            'processing_time' => microtime(true) - $startTime
        ];
    }
    
    /**
     * API-based NSFW checking
     */
    private function checkNSFWAPI($imagePath, $provider, $options) {
        // Placeholder for API implementation
        // This would call external APIs like Sightengine, Clarifai, etc.
        return [
            'success' => false,
            'error' => 'API provider not configured',
            'is_nsfw' => false,
            'confidence' => 0,
            'provider' => $provider
        ];
    }
    
    /**
     * Analyze image content
     */
    private function analyzeImageContent($image, $imageInfo) {
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        $skinPixels = 0;
        $totalPixels = 0;
        // Cast sampleRate to int
        $sampleRate = (int)max(1, floor(min($width, $height) / 200));
        
        $skinClusters = [];
        
        for ($x = 0; $x < $width; $x += $sampleRate) {
            for ($y = 0; $y < $height; $y += $sampleRate) {
                // Cast coordinates to int for imagecolorat
                $rgb = imagecolorat($image, (int)$x, (int)$y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                
                if ($this->isExtendedSkinTone($r, $g, $b)) {
                    $skinPixels++;
                    
                    // Use intdiv for color quantization
                    $colorKey = sprintf("%d-%d-%d", 
                        intdiv($r, 10) * 10,
                        intdiv($g, 10) * 10,
                        intdiv($b, 10) * 10
                    );
                    $skinClusters[$colorKey] = ($skinClusters[$colorKey] ?? 0) + 1;
                }
                $totalPixels++;
            }
        }
        
        $skinRatio = $totalPixels > 0 ? $skinPixels / $totalPixels : 0;
        $clusterDiversity = count($skinClusters);
        
        // Calculate flesh tone dominance
        $fleshDominance = 0;
        if ($clusterDiversity > 0 && $totalPixels > 0) {
            $maxCluster = max($skinClusters);
            $fleshDominance = $maxCluster / $totalPixels;
        }
        
        // Additional analysis
        $edgeComplexity = $this->calculateEdgeComplexity($image, $width, $height);
        $colorDiversity = $this->calculateColorDiversity($image, $width, $height);
        
        return [
            'skin_ratio' => $skinRatio,
            'flesh_dominance' => $fleshDominance,
            'cluster_diversity' => $clusterDiversity,
            'edge_complexity' => $edgeComplexity,
            'color_diversity' => $colorDiversity,
            'dimensions' => ['width' => $width, 'height' => $height],
            'aspect_ratio' => $width / max($height, 1)
        ];
    }
    
    /**
     * Extended skin tone detection with multiple color space checks
     */
    private function isExtendedSkinTone($r, $g, $b) {
        // RGB checks
        $rgbCheck = (
            $r > 95 && $g > 40 && $b > 20 &&
            max($r, $g, $b) - min($r, $g, $b) > 15 &&
            abs($r - $g) > 15 &&
            $r > $g && $r > $b
        );
        
        // YCbCr color space check (more reliable for skin)
        $y = 0.299 * $r + 0.587 * $g + 0.114 * $b;
        $cb = 128 - 0.168736 * $r - 0.331264 * $g + 0.5 * $b;
        $cr = 128 + 0.5 * $r - 0.418688 * $g - 0.081312 * $b;
        
        $ycbcrCheck = (
            $cb >= 77 && $cb <= 127 &&
            $cr >= 133 && $cr <= 173
        );
        
        // HSV check
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $diff = $max - $min;
        
        if ($diff > 0) {
            $h = 0;
            if ($max == $r) {
                $h = round(60 * fmod((($g - $b) / $diff), 6));
            } elseif ($max == $g) {
                $h = round(60 * ((($b - $r) / $diff) + 2));
            } else {
                $h = round(60 * ((($r - $g) / $diff) + 4));
            }
            
            if ($h < 0) {
                $h += 360;
            }

            $s = $max == 0 ? 0 : $diff / $max;
            $v = $max / 255;
            
            // Skin tones typically have hue 0-50 or 340-360
            $hsvCheck = (
                (($h >= 0 && $h <= 50) || $h >= 340) &&
                $s >= 0.15 && $s <= 0.8 &&
                $v >= 0.2
            );
            
            return $rgbCheck || $ycbcrCheck || $hsvCheck;
        }
        
        return $rgbCheck || $ycbcrCheck;
    }
    
    /**
     * Check if RGB is skin tone
     */
    private function isSkinTone($r, $g, $b) {
        return (
            $r > 95 && $g > 40 && $b > 20 &&
            max($r, $g, $b) - min($r, $g, $b) > 15 &&
            abs($r - $g) > 15 &&
            $r > $g && $r > $b
        );
    }
    
    /**
     * Calculate NSFW score
     */
    private function calculateNSFWScore($analysis) {
        $score = 0;
        
        // High skin ratio is primary indicator
        if ($analysis['skin_ratio'] > 0.3) {
            $score += 0.3 + (0.3 * min($analysis['skin_ratio'], 0.8));
        }
        
        // Flesh dominance (large continuous areas)
        if ($analysis['flesh_dominance'] > 0.1) {
            $score += 0.2 * min(1.0, $analysis['flesh_dominance'] / 0.3);
        }
        
        // Low edge complexity with high skin ratio
        if ($analysis['skin_ratio'] > 0.2 && $analysis['edge_complexity'] < 0.3) {
            $score += 0.2;
        }
        
        // Specific aspect ratios
        $aspectRatio = $analysis['aspect_ratio'];
        if (($aspectRatio > 0.5 && $aspectRatio < 0.8) || 
            ($aspectRatio > 1.2 && $aspectRatio < 1.8)) {
            $score += 0.1;
        }
        
        // Low color diversity with high skin ratio
        if ($analysis['skin_ratio'] > 0.25 && $analysis['color_diversity'] < 0.4) {
            $score += 0.2;
        }
        
        // Boost score if very high skin ratio
        if ($analysis['skin_ratio'] > 0.5) {
            $score *= 1.5;
        }
        
        return min(1.0, $score);
    }
    
    /**
     * Preprocess image for OCR
     */
    private function preprocessImage($imagePath, $options) {
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return $imagePath;
        }
        
        // Load image
        $image = null;
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                break;
            default:
                return $imagePath;
        }
        
        if (!$image) {
            return $imagePath;
        }
        
        // Apply filters
        imagefilter($image, IMG_FILTER_GRAYSCALE);
        if ($options['enhance_quality'] ?? false) {
            imagefilter($image, IMG_FILTER_CONTRAST, -20);
            imagefilter($image, IMG_FILTER_BRIGHTNESS, 10);
        }
        
        // Save
        $processedPath = $this->tempDir . '/' . uniqid('processed_') . '.png';
        imagepng($image, $processedPath);
        imagedestroy($image);
        
        return $processedPath;
    }
    
    /**
     * Estimate OCR confidence
     */
    private function estimateOCRConfidence($text) {
        if (empty($text)) {
            return 0.0;
        }
        
        $length = strlen($text);
        $alphaCount = preg_match_all('/[a-zA-Z]/', $text, $alphaMatches);
        $digitCount = preg_match_all('/[0-9]/', $text, $digitMatches);
        
        $alphaRatio = $alphaCount / max($length, 1);
        $wordCount = str_word_count($text);
        
        $score = 0;
        
        if ($alphaRatio > 0.5) $score += 0.4;
        if ($wordCount > 0) $score += 0.3;
        if ($digitCount / $length < 0.5) $score += 0.3;
        
        return min(1.0, $score);
    }
    
    /**
     * Generate warnings
     */
    private function generateWarnings($ocrResult, $nsfwResult) {
        $warnings = [];
        
        if ($nsfwResult['is_nsfw'] ?? false) {
            $warnings[] = [
                'type' => 'nsfw',
                'severity' => 'high',
                'message' => 'Image contains potentially inappropriate content',
                'confidence' => $nsfwResult['confidence']
            ];
        }
        
        if (($ocrResult['confidence'] ?? 0) < 0.5 && !empty($ocrResult['text'])) {
            $warnings[] = [
                'type' => 'ocr_quality',
                'severity' => 'medium',
                'message' => 'OCR confidence is low, text may be inaccurate',
                'confidence' => $ocrResult['confidence']
            ];
        }
        
        return $warnings;
    }
    
    /**
     * Log error
     */
    private function logError($type, $message) {
        // Simple error logging
        $logEntry = date('Y-m-d H:i:s') . " [$type] $message\n";
        
        $logDir = dirname($this->logFile ?: 'logs/image.log');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        @file_put_contents($this->logFile ?: 'logs/image.log', $logEntry, FILE_APPEND);
    }


    /**
 * Calculate edge complexity of an image
 * Higher values indicate more edges/details in the image
 * @param resource $image GD image resource
 * @param int $width Image width
 * @param int $height Image height
 * @return float Edge complexity score between 0 and 1
 */
private function calculateEdgeComplexity($image, $width, $height) {
    $edgeCount = 0;
    $totalChecks = 0;
    
    // Cast sampleRate to int
    $sampleRate = (int)max(2, floor(min($width, $height) / 100));
    
    for ($x = $sampleRate; $x < $width - $sampleRate; $x += $sampleRate) {
        for ($y = $sampleRate; $y < $height - $sampleRate; $y += $sampleRate) {
            // Cast all coordinates to int
            $center = imagecolorat($image, (int)$x, (int)$y);
            $centerGray = $this->rgbToGrayscale($center);
            
            // Cast coordinates for neighboring pixels
            $left = imagecolorat($image, (int)($x - 1), (int)$y);
            $right = imagecolorat($image, (int)($x + 1), (int)$y);
            $gradientX = abs($this->rgbToGrayscale($right) - $this->rgbToGrayscale($left));
            
            $top = imagecolorat($image, (int)$x, (int)($y - 1));
            $bottom = imagecolorat($image, (int)$x, (int)($y + 1));
            $gradientY = abs($this->rgbToGrayscale($bottom) - $this->rgbToGrayscale($top));
            
            $edgeMagnitude = sqrt($gradientX * $gradientX + $gradientY * $gradientY);
            
            if ($edgeMagnitude > 30) {
                $edgeCount++;
            }
            
            $totalChecks++;
        }
    }
    
    $edgeDensity = $totalChecks > 0 ? $edgeCount / $totalChecks : 0;
    return min(1.0, $edgeDensity * 2.5);
}

/**
 * Calculate color diversity of an image
 * Higher values indicate more varied colors
 * @param resource $image GD image resource
 * @param int $width Image width
 * @param int $height Image height
 * @return float Color diversity score between 0 and 1
 */
private function calculateColorDiversity($image, $width, $height) {
    $colorBuckets = [];
    $totalPixels = 0;
    
    // Cast sampleRate to int
    $sampleRate = (int)max(3, floor(min($width, $height) / 80));
    
    for ($x = 0; $x < $width; $x += $sampleRate) {
        for ($y = 0; $y < $height; $y += $sampleRate) {
            // Cast coordinates to int
            $rgb = imagecolorat($image, (int)$x, (int)$y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            // Use intdiv for quantization
            $bucketR = intdiv($r, 32) * 32;
            $bucketG = intdiv($g, 32) * 32;
            $bucketB = intdiv($b, 32) * 32;
            
            $bucketKey = sprintf("%d-%d-%d", $bucketR, $bucketG, $bucketB);
            $colorBuckets[$bucketKey] = ($colorBuckets[$bucketKey] ?? 0) + 1;
            $totalPixels++;
        }
    }
    
    if ($totalPixels == 0) {
        return 0;
    }
    
    // Calculate diversity metrics
    $uniqueColors = count($colorBuckets);
    $colorEntropy = 0;
    
    // Calculate Shannon entropy for color distribution
    foreach ($colorBuckets as $count) {
        $probability = $count / $totalPixels;
        if ($probability > 0) {
            $colorEntropy -= $probability * log($probability, 2);
        }
    }
    
    // Normalize entropy (max entropy for 512 buckets is log2(512) = 9)
    $normalizedEntropy = $colorEntropy / 9;
    
    // Calculate dominance of most common color
    $maxColorCount = max($colorBuckets);
    $dominance = $maxColorCount / $totalPixels;
    
    // Combine metrics for final diversity score
    // High entropy and low dominance = high diversity
    $diversityScore = ($normalizedEntropy * 0.7) + ((1 - $dominance) * 0.3);
    
    return min(1.0, $diversityScore);
}

/**
 * Convert RGB value to grayscale
 * @param int $rgb RGB color value from imagecolorat
 * @return int Grayscale value (0-255)
 */
private function rgbToGrayscale($rgb) {
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;
    
    // Use standard luminance formula
    return (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
}
}