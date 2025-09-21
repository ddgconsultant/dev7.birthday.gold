<?php
// Certificate Sync Endpoint for Deployment
// This endpoint provides certificate files for production deployment
// Only accessible with proper token authentication

// Security check - require deployment token
$required_token = 'DEPLOY_CERT_SECRET_2025';
$provided_token = $_GET['token'] ?? '';

if ($provided_token !== $required_token) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

// Define certificate directory - relative to this script
$cert_base_dir = realpath(__DIR__ . '/../../_CERTS_/birthday.gold');

if (!$cert_base_dir || !is_dir($cert_base_dir)) {
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => 'Certificate directory not found']));
}

// Certificate files we serve
$cert_files = [
    'STAR_birthday_gold.crt',
    'STAR_birthday_gold_chained.crt',
    'STAR_birthday_gold_combined.pem',
    'star.birthday.gold.key',
    'SectigoRSADomainValidationSecureServerCA.crt',
    'USERTrustRSAAAACA.crt'
];

// Handle different actions
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        // List available certificates with SHA1 checksums
        $available_certs = [];
        foreach ($cert_files as $cert_file) {
            $full_path = $cert_base_dir . '/' . $cert_file;
            if (file_exists($full_path)) {
                $available_certs[$cert_file] = [
                    'exists' => true,
                    'sha1' => sha1_file($full_path),
                    'size' => filesize($full_path),
                    'modified' => filemtime($full_path)
                ];
            } else {
                $available_certs[$cert_file] = ['exists' => false];
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'certificates' => $available_certs
        ]);
        break;

    case 'get':
        // Get specific certificate file content
        $file = $_GET['file'] ?? '';

        // Security: ensure file is in our allowed list
        if (!in_array($file, $cert_files)) {
            http_response_code(400);
            die(json_encode(['status' => 'error', 'message' => 'Invalid file requested']));
        }

        $full_path = $cert_base_dir . '/' . $file;

        if (!file_exists($full_path)) {
            http_response_code(404);
            die(json_encode(['status' => 'error', 'message' => 'File not found']));
        }

        // Return file content base64 encoded for safe transport
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'file' => $file,
            'content' => base64_encode(file_get_contents($full_path)),
            'sha1' => sha1_file($full_path)
        ]);
        break;

    case 'checksums':
        // Just return SHA1 checksums for comparison
        $checksums = [];
        foreach ($cert_files as $cert_file) {
            $full_path = $cert_base_dir . '/' . $cert_file;
            if (file_exists($full_path)) {
                $checksums[$cert_file] = sha1_file($full_path);
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'checksums' => $checksums
        ]);
        break;

    default:
        http_response_code(400);
        die(json_encode(['status' => 'error', 'message' => 'Invalid action']));
}