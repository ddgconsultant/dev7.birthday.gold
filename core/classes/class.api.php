<?PHP

$codemode = 'api';

class Api
{
    // Other properties and methods
    private $database;



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function __construct()
    {
        if (isset($GLOBALS['database']) && $GLOBALS['database'] instanceof PDO) {
            $this->database = $GLOBALS['database'];
        } else {
            $this->initDatabase();
        }
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function initDatabase()
    {
        global $dir;
        try {
            #### YOU MUST MAKE SURE THIS EXISTS -- this will change if we move the API directory
            include($dir['configs'].'/config-database.inc');
            $this->database = new PDO($db['host'], $db['user'], $db['password']);
            $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die(date('r') . ': ' . "-Database connection failed: " . $e->getMessage());
        }
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function validateToken()
    {
        if (!empty($_REQUEST['auth_token'])) {
            $params = [
                ':page' => $_SERVER['REQUEST_URI'],
                ':token' => $_REQUEST['auth_token'],
            ];

            $query = "UPDATE bg_api_sessions SET expire_dt = NOW() + INTERVAL 1 HOUR, last_page = :page WHERE session_id = :token AND expire_dt <= NOW() LIMIT 1";
            $stmt = $this->database->prepare($query);
            $stmt->execute($params);

            if ($stmt->rowCount() == 1) {
                return true;
            }
        }
        return  http_response_code(401);
    }


    
    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function authenticate_api_key($api_key) {
        $auth_url = '//dev.birthday.gold/api/auth';
        $data = ['api_key' => $api_key, 'get_owner'=>1];
        
        $options = [
            'http' => [
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ],
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($auth_url, false, $context);
        if ($result === false) {
            $error = error_get_last();
            error_log("API call failed: " . $error['message']);
            return ['success' => false, 'message' => 'Failed to contact API'];
        }
        
    
        return json_decode($result, true);
    }
    


    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function getContent($name = 'terms')
    {
        // Retrieve terms from the database (Replace with your actual query)
        $query = "SELECT content  FROM bg_content WHERE name= '" . $name . "' and status='active' order by create_dt desc limit 1";
        $stmt = $this->database->prepare($query);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $content = $result['content'];

            return $content;
        } else return false;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function responseHandler($content = null, $customMessage = null, $extraPayload = [])
    {
        header('Content-Type: application/json');


        // Measure the size of $content['data'] and respond with 413 if it exceeds 3MB
        if (isset($content) && strlen(json_encode($content)) > 3145728) {
            http_response_code(413);
            echo json_encode(['message' => 'Payload too large']);
            return;
        }

        // Status 200 OK
        if (!is_null($content) && !empty($content)) {
            http_response_code(200);
            // Check if both $content and $extraPayload are arrays
            if (is_array($content) && is_array($extraPayload)) {
                echo json_encode(array_merge($content, $extraPayload));
            } elseif (is_array($extraPayload)) {
                echo json_encode(array_merge([$content], $extraPayload));
            } else {
                echo json_encode([$content]);
            }
            #   echo json_encode($content + $extraPayload);
            return;
        }

        // Status 204 No Content
        if (!is_null($content) && empty($content)) {
            http_response_code(204);
            echo json_encode(['message' => 'No content']); // No content to send
            return;
        }
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function responseError($code = null, $customMessage = null)
    {
        http_response_code($code);
        echo json_encode($customMessage);
        return;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function response400($message = 'Bad input parameter')
    {
        // 400 Bad Request
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['message' => $message]);
    }
}
