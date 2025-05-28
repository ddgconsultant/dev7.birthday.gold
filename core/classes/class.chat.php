<?PHP

class Chat {
    private $rocketchat_url;
    private $rocketchat_password;

    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function __construct($config) {
        $this->rocketchat_url = $config['ROCKETCHAT_WEBHOOK'];
        $this->rocketchat_password = $config['ROCKETCHAT_TOKEN'];
        
    }


    
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function sendRocketChatMessage($message) {
    $DEBUG=false;
    $payload = json_encode([
        'text' => $message,
        
    ]);
  if ($DEBUG)    echo 'Payload: ' . $payload . '<br>';

    $ch = curl_init($this->rocketchat_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable SSL verification
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
      if ($DEBUG)    echo 'cURL error: ' . curl_error($ch) . '<br>';
    } else {
      if ($DEBUG)    echo 'HTTP code: ' . $http_code . '<br>';
      if ($DEBUG)    echo 'Response: ' . htmlspecialchars($response) . '<br>';
        
        // Additional logging based on the HTTP code
        if ($http_code !== 200) {
          if ($DEBUG)    echo 'Failed to send message. HTTP code: ' . $http_code . '<br>';
        } else {
          if ($DEBUG)    echo 'Message sent successfully.<br>';
        }
    }

    curl_close($ch);
}
}


