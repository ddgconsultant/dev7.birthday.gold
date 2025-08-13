<?php
// Wayne Project - Example API Endpoint

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Simple router based on request method
$method = $_SERVER['REQUEST_METHOD'];
$response = [];

// Example data store (in production, this would be a database)
$exampleData = [
    'users' => [
        ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
        ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com']
    ],
    'projects' => [
        ['id' => 1, 'title' => 'Wayne Project', 'status' => 'active'],
        ['id' => 2, 'title' => 'Test Project', 'status' => 'completed'],
        ['id' => 3, 'title' => 'Demo Project', 'status' => 'pending']
    ]
];

// Parse request path
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Get the resource type (users, projects, etc.)
$resource = isset($pathParts[2]) ? $pathParts[2] : 'info';

try {
    switch ($method) {
        case 'GET':
            // Handle GET requests
            if ($resource === 'info') {
                $response = [
                    'success' => true,
                    'message' => 'Wayne API v1.0',
                    'endpoints' => [
                        '/api/users' => 'Get all users',
                        '/api/projects' => 'Get all projects',
                        '/api/status' => 'Get API status'
                    ]
                ];
            } elseif ($resource === 'users') {
                $response = [
                    'success' => true,
                    'data' => $exampleData['users'],
                    'count' => count($exampleData['users'])
                ];
            } elseif ($resource === 'projects') {
                $response = [
                    'success' => true,
                    'data' => $exampleData['projects'],
                    'count' => count($exampleData['projects'])
                ];
            } elseif ($resource === 'status') {
                $response = [
                    'success' => true,
                    'status' => 'operational',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'version' => '1.0.0'
                ];
            } else {
                http_response_code(404);
                $response = [
                    'success' => false,
                    'error' => 'Resource not found'
                ];
            }
            break;
            
        case 'POST':
            // Handle POST requests
            $input = json_decode(file_get_contents('php://input'), true);
            
            if ($resource === 'users' || $resource === 'projects') {
                // Simulate creating a new resource
                $response = [
                    'success' => true,
                    'message' => ucfirst(substr($resource, 0, -1)) . ' created successfully',
                    'data' => array_merge(['id' => rand(100, 999)], $input ?: [])
                ];
            } else {
                http_response_code(400);
                $response = [
                    'success' => false,
                    'error' => 'Invalid resource for POST'
                ];
            }
            break;
            
        case 'PUT':
            // Handle PUT requests
            $input = json_decode(file_get_contents('php://input'), true);
            $response = [
                'success' => true,
                'message' => 'Resource updated successfully',
                'data' => $input
            ];
            break;
            
        case 'DELETE':
            // Handle DELETE requests
            $response = [
                'success' => true,
                'message' => 'Resource deleted successfully'
            ];
            break;
            
        default:
            http_response_code(405);
            $response = [
                'success' => false,
                'error' => 'Method not allowed'
            ];
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ];
}

// Output JSON response
echo json_encode($response, JSON_PRETTY_PRINT);

// Example helper functions that could be used in a real API

/**
 * Validate API key
 */
function validateApiKey($key) {
    // In production, check against database
    $validKeys = ['test-key-123', 'demo-key-456'];
    return in_array($key, $validKeys);
}

/**
 * Log API request
 */
function logRequest($method, $resource, $response) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $method,
        'resource' => $resource,
        'response_code' => http_response_code(),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // In production, write to database or log file
    error_log(json_encode($logEntry));
}

/**
 * Rate limiting check
 */
function checkRateLimit($identifier) {
    // In production, implement proper rate limiting
    // using Redis, database, or other storage
    return true;
}