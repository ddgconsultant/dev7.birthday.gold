<?php

class Leantime
{
    private $apiUrl;
    private $apiKey;

    public function __construct($apiUrl, $apiKey)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
    }

    // Function to send JSON-RPC request to Leantime API
    private function sendRequest($method, $params = [], $id = 1)
    {
        $payload = [
            "method" => $method,
            "jsonrpc" => "2.0",
            "id" => $id,
            "params" => $params
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . '/api/jsonrpc');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-api-key: " . $this->apiKey,
            "Content-Type: application/json"
        ]);

        // Disable SSL verification for development (remove in production)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'cURL error: ' . curl_error($ch);
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo 'JSON decode error: ' . json_last_error_msg();
            return false;
        }

        return $decodedResponse;
    }

    // Method to fetch user ID by email
    public function getUserIdByEmail($email)
    {
        $params = [
            "email" => $email,
            "status" => "active" // Assuming 'active' status, adjust as needed
        ];

        $response = $this->sendRequest("leantime.rpc.users.users.getUserByEmail", $params);

        if (isset($response['result']['id'])) {
            return $response['result']['id'];
        } else {
            echo "Error: User not found.";
            return null;
        }
    }


    // Method to get all users
    public function getAllUsers($activeOnly = false)
    {
        $params = [
            "activeOnly" => $activeOnly
        ];

        $response = $this->sendRequest("leantime.rpc.users.users.getAll", $params);

        // Debug: Print the raw response to inspect the returned values
        echo "<pre>User API Response: ";
        print_r($response);
        echo "</pre>";

        return $response;
    }



    // Method to fetch all projects
// Method to fetch all projects with debug output
public function getProjectIdByName($projectName)
{
    $response = $this->sendRequest("leantime.rpc.projects.projects.getAllProjects");

    // Debug: Print the raw response for troubleshooting
    echo "<pre>Project API Response: ";
    print_r($response);
    echo "</pre>";

    // Check if the response has the 'result' key
    if (isset($response['result']) && is_array($response['result'])) {
        foreach ($response['result'] as $project) {
            if (isset($project['name']) && $project['name'] == $projectName) {
                return $project['id'];
            }
        }
        echo "Error: Project '$projectName' not found.";
        return null;
    } else {
        echo "Error: No projects found.";
        return null;
    }
}

    // Method to create a new ticket
    public function createTicket($projectId, $title, $description, $status, $priority, $assignedUserId)
    {
        $values = [
            "headline" => $title,
            "description" => $description,
            "projectId" => $projectId,
            "userId" => $assignedUserId,  // ID of the user creating the ticket
            "status" => $status,
            "priority" => $priority
        ];

        return $this->sendRequest("leantime.rpc.tickets.tickets.addTicket", ["values" => $values]);
    }
}
