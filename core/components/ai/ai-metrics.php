<?php
// ai-metrics.php

// Make sure we have our response data
$metrics = $response ?? [];

// Get content safely
$messageContent = $metrics['content'] ?? 'No response content available';

// Get usage metrics safely
$usage = $metrics['usage'] ?? [
    'prompt_tokens' => 0,
    'completion_tokens' => 0,
    'total_tokens' => 0
];

$promptTokens = $usage['prompt_tokens'];
$completionTokens = $usage['completion_tokens'];
$totalTokens = $usage['total_tokens'];

// Safe division for percentages
$promptPercentage = $totalTokens > 0 ? (($promptTokens / $totalTokens) * 100) : 0;
$completionPercentage = $totalTokens > 0 ? (($completionTokens / $totalTokens) * 100) : 0;


// For debugging
// echo '<pre>Response structure: ' . print_r($response, true) . '</pre>';

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Usage Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container py-5">
    <h1 class="text-center mb-4">AI Usage Metrics</h1>
    <p class="text-center text-muted">
        Last Updated: ' . date('r') . '
    </p>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Response</h5>
        </div>
        <div class="card-body">
            <blockquote class="blockquote text-center">
                <p class="mb-0">' . nl2br(htmlspecialchars((string)$messageContent)) . '</p>
                <footer class="blockquote-footer mt-3">Assistant Response</footer>
            </blockquote>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Usage Summary</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4 text-center">
                    <div class="mb-3">
                        <h6>Prompt Tokens</h6>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-success" role="progressbar"
                                 style="width: ' . $promptPercentage . '%;"
                                 aria-valuenow="' . $promptTokens . '" aria-valuemin="0" aria-valuemax="' . $totalTokens . '">
                                ' . $promptTokens . ($totalTokens > 0 ? ' / ' . $totalTokens : '') . '
                            </div>
                        </div>
                        <small>' . $promptTokens . ' tokens used in prompt</small>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="mb-3">
                        <h6>Completion Tokens</h6>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-warning" role="progressbar"
                                 style="width: ' . $completionPercentage . '%;"
                                 aria-valuenow="' . $completionTokens . '" aria-valuemin="0" aria-valuemax="' . $totalTokens . '">
                                ' . $completionTokens . ($totalTokens > 0 ? ' / ' . $totalTokens : '') . '
                            </div>
                        </div>
                        <small>' . $completionTokens . ' tokens used in completion</small>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="mb-3">
                        <h6>Total Tokens</h6>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-info" role="progressbar"
                                 style="width: 100%;"
                                 aria-valuenow="' . $totalTokens . '" aria-valuemin="0" aria-valuemax="' . $totalTokens . '">
                                ' . $totalTokens . '
                            </div>
                        </div>
                        <small>Total tokens used: ' . $totalTokens . '</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Engine Information</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Engine</th>
                        <th>Model</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>' . htmlspecialchars((string)($response['engine'] ?? 'Unknown')) . '</td>
                        <td>' . htmlspecialchars((string)($response['model'] ?? 'Unknown')) . '</td>
                        <td>' . htmlspecialchars((string)($response['type'] ?? 'Unknown')) . '</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';