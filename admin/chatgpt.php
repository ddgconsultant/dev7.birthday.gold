<?php 
/**
 * ChatGPT Integration for Company Description Rewriting
 * 
 * SECURITY NOTE: This file previously contained a hardcoded API key which has been removed.
 * API keys should NEVER be committed to version control.
 * The OpenAI API key should be configured in your config-ai.inc file.
 */

include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 

require_once($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php');

use GuzzleHttp\Client;

// Initialize your database (based on your existing setup)
// $database = new YourDatabaseClass();

// Initialize Guzzle Client for GPT API
$client = new Client([
    'base_uri' => 'https://api.openai.com/',
    'verify' => false,  // Disable SSL verification
]);




// If form is submitted, update the spinner_description with custom response
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['custom_response']) && isset($_POST['company_id'])) {
    $customResponse = trim($_POST['custom_response']);
    $companyId = $_POST['company_id'];
    $updateSQL = "UPDATE bg_companies SET spinner_description = :description WHERE company_id = :id";
    $updateStmt = $database->prepare($updateSQL);
    $updateStmt->bindParam(':description', $customResponse);
    $updateStmt->bindParam(':id', $companyId);
    $updateStmt->execute();
  #  echo "Custom description updated successfully.<br><br>";
}




// Fetch phrase to rewrite from your bg_companies table
$sql = "SELECT company_id as id, company_name, description FROM bg_companies WHERE spinner_description is null  and description is not null and company_status='active' and status='finalized' LIMIT 1"; // Add your WHERE condition or ORDER, LIMIT etc
$stmt = $database->prepare($sql);
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $phraseToRewrite = $row['description'];
    $id = $row['id'];
#$prompt="Please rephrase and shorten this so that it reads like [Did you know at ".$row['company_name']." you can receive  xxxxxx] :  $phraseToRewrite";
$prompt="The final response should follow this conversation pattern [Did you know at ".$row['company_name']." you can receive  xxxxxx].  Just don't include [Did you know at ".$row['company_name']."].  Please extract the accurate offer details and rewrite them to be marketing fun, catchy, and less than 255 characters, and don't repeat any of the words in the reponse, from the following sentence: [Did you know at ".$row['company_name']." you can receive ... $phraseToRewrite";
$prompt.=".  DO NOT INCLUDE \"Did you know at ".$row['company_name'].'"';
$prompt.=".  DO NOT INCLUDE anything like see more details";
$prompt = "Rewrite the following offer from ".$row['company_name']." in a catchy, marketing-friendly way without using repeated words, and keep it under 255 characters, and remove any 'see more' phrases: ".$phraseToRewrite;
$prompt="PHRASE: '".$phraseToRewrite."'
INSTRUCTIONS:
- Extract the offer detail from the PHRASE,
- and rewrite the found offer in PHRASE in a catchy, marketing-friendly way without using repeated words,
- and remove any 'see more' phrases,
- and it should sound like \"Did you know at ".$row['company_name']."... without including that in the response,
- and it must be less than 255 characters,
- and make it only one sentence.";


    // Make request to GPT API using modern endpoint
    try {
        // Get API key from config - NEVER hardcode API keys!
        // Using the existing openai_goldie configuration
        if (!isset($sitesettings_ai['ai']['openai_goldie']['api_key']) || empty($sitesettings_ai['ai']['openai_goldie']['api_key'])) {
            throw new Exception('OpenAI API key not configured. Please check your AI configuration file.');
        }
        $apiKey = $sitesettings_ai['ai']['openai_goldie']['api_key'];
        
        $response = $client->post('v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo', // Using current model
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant that rewrites business descriptions.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.1,
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
        $rewrittenPhrase = $result['choices'][0]['message']['content'] ?? '';
#$search=array($company.':', $company);
$search='';

$rewrittenPhrase= str_replace($search, '', $rewrittenPhrase);
echo '<h2>'.$row['company_name'].':</h2><pre>
'.$prompt.'<hr>
Description updated to: <b>'.$rewrittenPhrase.'</b><br><br>';
flush();


  echo '<hr>
  <!-- Form to update spinner_description with custom response -->
  <form action="" method="post">
      <label for="custom_response">Custom Response:</label>
      <input type="text" id="custom_response" name="custom_response" style="width: 500px;" required>

      <input type="hidden" name="company_id" value="'. $id.'">  <!-- Assuming $id contains the company ID -->
      <input type="submit" value="Update">
  </form>
  ';

  flush();
        // Update description in your bg_companies table
        $updateSQL = "UPDATE bg_companies SET spinner_description = :description WHERE company_id = :id";
        $updateStmt = $database->prepare($updateSQL);
        $updateStmt->bindParam(':description', $rewrittenPhrase);
        $updateStmt->bindParam(':id', $id);
        $updateStmt->execute();

          # sleep(20);

    } catch (GuzzleHttp\Exception\ClientException $e) {
        $response = $e->getResponse();
        $responseBody = $response->getBody()->getContents();
        $errorData = json_decode($responseBody, true);
        
        echo '<div style="color: red; border: 1px solid red; padding: 10px; margin: 10px 0;">';
        echo '<strong>OpenAI API Error:</strong><br>';
        echo 'Status: ' . $response->getStatusCode() . '<br>';
        echo 'Message: ' . ($errorData['error']['message'] ?? 'Unknown error') . '<br>';
        
        if (strpos($errorData['error']['message'] ?? '', 'deprecated') !== false) {
            echo '<br><strong>Note:</strong> The model being used is deprecated. Please update to use gpt-3.5-turbo or gpt-4.<br>';
        }
        if (strpos($errorData['error']['message'] ?? '', 'API key') !== false || $response->getStatusCode() == 401) {
            echo '<br><strong>Note:</strong> There appears to be an issue with the API key. Please check your OpenAI API key configuration.<br>';
        }
        
        echo '</div>';
        
        if ($this->debug ?? false) {
            echo '<pre>Full Response: ' . htmlspecialchars($responseBody) . '</pre>';
        }
    } catch (Exception $e) {
        echo '<div style="color: red; border: 1px solid red; padding: 10px; margin: 10px 0;">';
        echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}

?>
