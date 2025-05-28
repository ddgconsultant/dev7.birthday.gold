<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 

require_once('vendor/autoload.php');

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


    // Make request to GPT API
    try {
        $response = $client->post('v1/engines/davinci-instruct-beta/completions', [
            'headers' => [
                'Authorization' => 'Bearer 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', // Replace with your OpenAI API key bg_datarewriter
            ],
            'json' => [
                #'model' => "text-babbage-001",
                'prompt' => $prompt,
                'max_tokens' => 1000, // Limit tokens (words) returned by the API
                'temperature' => 0.1,  // Adjust as needed
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
        $rewrittenPhrase = $result['choices'][0]['text'] ?? '';
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

    } catch (Exception $e) {
        echo 'API or Database error: ' . $e->getMessage();
    }
}

?>
