<?php
$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
if (!$account->isadmin())
{
    header('location: /401'); exit;
}
// Your OpenAI API Key
$apiKey =  $sitesettings_ai['ai']['openai_goldie']['api_key'];

// ChatGPT API endpoint
$url =  $sitesettings_ai['ai']['openai_goldie']['api_url'];
$PROJECT_ID='proj_0CA1Q4ehMnQ1pz58DaWsnln3';
$headers=[
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey,
    "OpenAI-Project: $PROJECT_ID"
];
// The data you want to send to ChatGPT
$data = [
    'model' => 'gpt-4o',  // 'gpt-3.5-turbo', // Model to use
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant that knows everything about the online service birthday.gold located at https://birthday.gold.'],
        ['role' => 'user', 'content' => 'Hello, Can you tell me something exciting about birthday.gold?']
    ],
    'max_tokens' => 200, // Limit of tokens in the response
    'temperature' => 0.7 // Controls randomness
];
if (!file_exists('w:/BIRTHDAY_SERVER/_CERTS_/birthday.gold/STAR_birthday_gold_combined.pem')) {
    die('File not found!');
}
/*
// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable SSL verification
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey,
    "OpenAI-Project: $PROJECT_ID"
]);

// Execute the request
$response = curl_exec($ch);
*/

$response=$system->curlRequest($url, $headers, $data);

// Check for errors
if (!empty($response['error'])) {
    echo 'Error: ' . $response['error'];
} else {
    // Decode the response
    $decodedResponse = $response['decoded'];
    // Display the response
 /*   echo date('r') . '<br>';
    echo '<pre>';
    print_r($decodedResponse);
    echo '</pre>';
*/
    echo '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatGPT Usage Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container py-5">



    <h1 class="text-center mb-4">ChatGPT Usage Metrics</h1>
    <p class="text-center text-muted">
        Last Updated: ' . date('r') . '
    </p>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">ChatGPT Response</h5>
        </div>
        <div class="card-body">
            <blockquote class="blockquote text-center">
                <p class="mb-0">' . nl2br(htmlspecialchars($decodedResponse['choices'][0]['message']['content'])) . '</p>
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
                                 style="width: ' . (($decodedResponse['usage']['prompt_tokens'] / $decodedResponse['usage']['total_tokens']) * 100) . '%;"
                                 aria-valuenow="' . $decodedResponse['usage']['prompt_tokens'] . '" aria-valuemin="0" aria-valuemax="' . $decodedResponse['usage']['total_tokens'] . '">
                                ' . $decodedResponse['usage']['prompt_tokens'] . ' / ' . $decodedResponse['usage']['total_tokens'] . '
                            </div>
                        </div>
                        <small>' . $decodedResponse['usage']['prompt_tokens'] . ' tokens used in prompt</small>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="mb-3">
                        <h6>Completion Tokens</h6>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-warning" role="progressbar"
                                 style="width: ' . (($decodedResponse['usage']['completion_tokens'] / $decodedResponse['usage']['total_tokens']) * 100) . '%;"
                                 aria-valuenow="' . $decodedResponse['usage']['completion_tokens'] . '" aria-valuemin="0" aria-valuemax="' . $decodedResponse['usage']['total_tokens'] . '">
                                ' . $decodedResponse['usage']['completion_tokens'] . ' / ' . $decodedResponse['usage']['total_tokens'] . '
                            </div>
                        </div>
                        <small>' . $decodedResponse['usage']['completion_tokens'] . ' tokens used in completion</small>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="mb-3">
                        <h6>Total Tokens</h6>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-info" role="progressbar"
                                 style="width: 100%;"
                                 aria-valuenow="' . $decodedResponse['usage']['total_tokens'] . '" aria-valuemin="0" aria-valuemax="' . $decodedResponse['usage']['total_tokens'] . '">
                                ' . $decodedResponse['usage']['total_tokens'] . '
                            </div>
                        </div>
                        <small>Total tokens used: ' . $decodedResponse['usage']['total_tokens'] . '</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Detailed Token Metrics</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Cached Tokens</td>
                        <td>' . $decodedResponse['usage']['prompt_tokens_details']['cached_tokens'] . '</td>
                        <td>Tokens retrieved from cache.</td>
                    </tr>
                    <tr>
                        <td>Audio Tokens</td>
                        <td>' . $decodedResponse['usage']['prompt_tokens_details']['audio_tokens'] . '</td>
                        <td>Tokens used for audio processing.</td>
                    </tr>
                    <tr>
                        <td>Reasoning Tokens</td>
                        <td>' . $decodedResponse['usage']['completion_tokens_details']['reasoning_tokens'] . '</td>
                        <td>Tokens used for reasoning tasks.</td>
                    </tr>
                    <tr>
                        <td>Accepted Prediction Tokens</td>
                        <td>' . $decodedResponse['usage']['completion_tokens_details']['accepted_prediction_tokens'] . '</td>
                        <td>Tokens accepted as part of the response.</td>
                    </tr>
                    <tr>
                        <td>Rejected Prediction Tokens</td>
                        <td>' . $decodedResponse['usage']['completion_tokens_details']['rejected_prediction_tokens'] . '</td>
                        <td>Tokens rejected during generation.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';

}
