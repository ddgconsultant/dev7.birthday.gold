<?php
if (isset($_GET['url'])) {
    $target_url = $_GET['url'];

    // Validate the URL
    if (filter_var($target_url, FILTER_VALIDATE_URL)) {
        // Fetch the content from the target URL
        $options = [
            'http' => [
                'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36'
            ]
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($target_url, false, $context);

        // Output the content
        echo $response;
    } else {
        echo 'Invalid URL.';
    }
} else {
    echo 'No URL provided.';
}
?>
