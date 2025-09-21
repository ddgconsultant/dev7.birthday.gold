<?php

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Path to the storage file for processed RSS item GUIDs
$processedItemsFile = $_SERVER['DOCUMENT_ROOT'] . '/monitoring/processed_rss_items.json';

// Load previously processed RSS item GUIDs
$processedItems = file_exists($processedItemsFile) ? json_decode(file_get_contents($processedItemsFile), true) : [];

// Function to save processed RSS items
function saveProcessedItems($file, $items) {
    file_put_contents($file, json_encode($items, JSON_PRETTY_PRINT));
}

// Function to convert HTML to Rocket.Chat Markdown
function convertHtmlToMarkdown($html) {
    $markdown = $html;

    // Convert basic HTML tags to markdown equivalents
    $markdown = preg_replace('/<strong>(.*?)<\/strong>/', '*$1*', $markdown); // Bold
    $markdown = preg_replace('/<em>(.*?)<\/em>/', '_$1_', $markdown);        // Italics
    $markdown = preg_replace('/<br\s*\/?>/', "\n", $markdown);              // Line breaks
    $markdown = preg_replace('/<a href="(.*?)">(.*?)<\/a>/', '[$2]($1)', $markdown); // Links

    // Strip remaining tags
    $markdown = strip_tags($markdown);

    return $markdown;
}

// Handle incoming webhook payload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $headers = getallheaders();

    // Decode the JSON payload
    $data = json_decode($input, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        // Format the message to send to Rocket.Chat
        $message = "New Webhook Notification Received:\n";
        foreach ($data as $key => $value) {
            $message .= "$key: $value\n";
        }

        // Send the message to Rocket.Chat
        $channel = '#BG-Technical'; // Replace with the desired channel
        $system->postToRocketChat($message, $channel);

        // Respond with success
        http_response_code(200);
        echo 'Webhook received successfully.';
    } else {
        // Respond with error for invalid JSON
        http_response_code(400);
        echo 'Invalid JSON payload.';
    }
    exit;
}

// Handle RSS feed fetching and processing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['rss_url'])) {
    $i=0;
    $rssUrl = $_GET['rss_url'];

    // Fetch the RSS feed
    $rssContent = file_get_contents($rssUrl);
    if ($rssContent === false) {
        http_response_code(404);
        echo 'Failed to fetch RSS feed.';
        exit;
    }

    // Parse the RSS feed
    $rssXml = new SimpleXMLElement($rssContent);

    // Track and send new RSS items
    $newItems = [];
    foreach ($rssXml->channel->item as $item) {
        $guid = (string)$item->guid; // Unique identifier for each RSS item
        if (!in_array($guid, $processedItems, true)) {
            $newItems[] = [
                'title' => (string)$item->title,
                'link' => (string)$item->link,
                'description' => (string)$item->description,
                'guid' => $guid
            ];
            $processedItems[] = $guid; // Mark this item as processed
        }
    }

    // Save the updated list of processed items
    saveProcessedItems($processedItemsFile, $processedItems);
  
    // Send new items to Rocket.Chat
    if (!empty($newItems)) {
        foreach ($newItems as $newItem) {
            $i++;
            $descriptionMarkdown = convertHtmlToMarkdown($newItem['description']);

            $message = "New OPENAPI (ChatGPT Status) RSS Feed Update:\n";
            $message .= "Title: {$newItem['title']}\n";
            $message .= "Link: {$newItem['link']}\n";
            $message .= "Description:\n$descriptionMarkdown";
            $message_in['nopreview']=true;
            $message_in['message']=$message;
            // Replace with the desired channel
            $channel = '#BG-Technical'; 
            $channel = '#testing'; 
     if ($i<3)       $system->postToRocketChat($message_in, $channel);
        }
    }



    
if ($i > 0) {
    // New items were sent to Rocket.Chat
    http_response_code(201); // Indicates new resources were created (messages sent)
    echo 'RSS feed processed ' . $i . ' new items successfully.';
} else {
    // No new items
    http_response_code(200); // Indicates the feed was processed but nothing new was sent
    echo 'RSS feed processed successfully. No new items found.';
}

    exit;
}

// Fallback for unsupported methods
http_response_code(405);
echo 'Method not allowed.';
?>
