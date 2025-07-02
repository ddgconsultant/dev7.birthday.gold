<?php

/**
 * SMS Gateway Class
 * Handles sending SMS messages through the Birthday Gold SMS gateway
 */
class sms {
    
    private $server;
    private $apiKey;
    
    const USE_SPECIFIED = 0;
    const USE_ALL_DEVICES = 1;
    const USE_ALL_SIMS = 2;
    
    /**
     * Constructor
     * @param array $config Configuration array with 'server' and 'api_key' keys
     */
    public function __construct($config = []) {
        // Use config if provided, otherwise use defaults (will be replaced by ENV_CONFIG)
        $this->server = $config['server'] ?? 'https://sms.bd.gold';
        $this->apiKey = $config['api_key'] ?? '2e545357a07f667e396da33d5d0ddde564afef55';
    }
    
    /**
     * Send a single SMS message
     * @param string     $number      The mobile number where you want to send message.
     * @param string     $message     The message you want to send.
     * @param int|string $device      The ID of a device you want to use to send this message.
     * @param int        $schedule    Set it to timestamp when you want to send this message.
     * @param bool       $isMMS       Set it to true if you want to send MMS message instead of SMS.
     * @param string     $attachments Comma separated list of image links you want to attach to the message. Only works for MMS messages.
     * @param bool       $prioritize  Set it to true if you want to prioritize this message.
     * @return array     Returns The array containing information about the message.
     * @throws Exception If there is an error while sending a message.
     */
    public function sendSingleMessage($number, $message, $device = 0, $schedule = null, $isMMS = false, $attachments = null, $prioritize = false) {
        $url = $this->server . "/services/send.php";
        $postData = array(
            'number' => $number,
            'message' => $message,
            'schedule' => $schedule,
            'key' => $this->apiKey,
            'devices' => $device,
            'type' => $isMMS ? "mms" : "sms",
            'attachments' => $attachments,
            'prioritize' => $prioritize ? 1 : 0
        );
        return $this->sendRequest($url, $postData)["messages"][0];
    }
    
    /**
     * Send multiple messages
     * @param array  $messages        The array containing numbers and messages.
     * @param int    $option          Set this to USE_SPECIFIED if you want to use devices and SIMs specified in devices argument.
     * @param array  $devices         The array of ID of devices you want to use to send these messages.
     * @param int    $schedule        Set it to timestamp when you want to send these messages.
     * @param bool   $useRandomDevice Set it to true if you want to send messages using only one random device from selected devices.
     * @return array Returns The array containing messages.
     * @throws Exception If there is an error while sending messages.
     */
    public function sendMessages($messages, $option = self::USE_SPECIFIED, $devices = [], $schedule = null, $useRandomDevice = false) {
        $url = $this->server . "/services/send.php";
        $postData = [
            'messages' => json_encode($messages),
            'schedule' => $schedule,
            'key' => $this->apiKey,
            'devices' => json_encode($devices),
            'option' => $option,
            'useRandomDevice' => $useRandomDevice
        ];
        return $this->sendRequest($url, $postData)["messages"];
    }
    
    /**
     * Send message to contacts list
     * @param int    $listID      The ID of the contacts list where you want to send this message.
     * @param string $message     The message you want to send.
     * @param int    $option      Set this to USE_SPECIFIED if you want to use devices and SIMs specified in devices argument.
     * @param array  $devices     The array of ID of devices you want to use to send the message.
     * @param int    $schedule    Set it to timestamp when you want to send this message.
     * @param bool   $isMMS       Set it to true if you want to send MMS message instead of SMS.
     * @param string $attachments Comma separated list of image links you want to attach to the message.
     * @return array Returns The array containing messages.
     * @throws Exception If there is an error while sending messages.
     */
    public function sendMessageToContactsList($listID, $message, $option = self::USE_SPECIFIED, $devices = [], $schedule = null, $isMMS = false, $attachments = null) {
        $url = $this->server . "/services/send.php";
        $postData = [
            'listID' => $listID,
            'message' => $message,
            'schedule' => $schedule,
            'key' => $this->apiKey,
            'devices' => json_encode($devices),
            'option' => $option,
            'type' => $isMMS ? "mms" : "sms",
            'attachments' => $attachments
        ];
        return $this->sendRequest($url, $postData)["messages"];
    }
    
    /**
     * Get a single message by ID
     * @param int $id The ID of a message you want to retrieve.
     * @return array The array containing a message.
     * @throws Exception If there is an error while getting a message.
     */
    public function getMessageByID($id) {
        $url = $this->server . "/services/read-messages.php";
        $postData = [
            'key' => $this->apiKey,
            'id' => $id
        ];
        return $this->sendRequest($url, $postData)["messages"][0];
    }
    
    /**
     * Get messages by group ID
     * @param string $groupId The group ID of messages you want to retrieve.
     * @return array The array containing messages.
     * @throws Exception If there is an error while getting messages.
     */
    public function getMessagesByGroupID($groupId) {
        $url = $this->server . "/services/read-messages.php";
        $postData = [
            'key' => $this->apiKey,
            'groupId' => $groupId
        ];
        return $this->sendRequest($url, $postData)["messages"];
    }
    
    /**
     * Get messages by status
     * @param string         $status         The status of messages you want to retrieve.
     * @param int            $deviceID       The deviceID of the device which messages you want to retrieve.
     * @param int            $simSlot        Sim slot of the device which messages you want to retrieve.
     * @param int|null       $startTimestamp Search for messages sent or received after this time.
     * @param int|null       $endTimestamp   Search for messages sent or received before this time.
     * @return array The array containing messages.
     * @throws Exception If there is an error while getting messages.
     */
    public function getMessagesByStatus($status, $deviceID = null, $simSlot = null, $startTimestamp = null, $endTimestamp = null) {
        $url = $this->server . "/services/read-messages.php";
        $postData = [
            'key' => $this->apiKey,
            'status' => $status,
            'deviceID' => $deviceID,
            'simSlot' => $simSlot,
            'startTimestamp' => $startTimestamp,
            'endTimestamp' => $endTimestamp
        ];
        return $this->sendRequest($url, $postData)["messages"];
    }
    
    /**
     * Resend messages by status
     * @param string $status         The status of messages you want to resend.
     * @param int    $deviceID       The deviceID of the device which messages you want to resend.
     * @param int    $simSlot        Sim slot of the device which messages you want to resend.
     * @param int    $startTimestamp Resend messages sent or received after this time.
     * @param int    $endTimestamp   Resend messages sent or received before this time.
     * @return array The array containing messages.
     * @throws Exception If there is an error while resending messages.
     */
    public function resendMessagesByStatus($status, $deviceID = null, $simSlot = null, $startTimestamp = null, $endTimestamp = null) {
        $url = $this->server . "/services/resend.php";
        $postData = [
            'key' => $this->apiKey,
            'status' => $status,
            'deviceID' => $deviceID,
            'simSlot' => $simSlot,
            'startTimestamp' => $startTimestamp,
            'endTimestamp' => $endTimestamp
        ];
        return $this->sendRequest($url, $postData)["messages"];
    }
    
    /**
     * Add a contact to a list
     * @param int    $listID      The ID of the contacts list where you want to add this contact.
     * @param string $number      The mobile number of the contact.
     * @param string $name        The name of the contact.
     * @param bool   $resubscribe Set it to true if you want to resubscribe this contact if it already exists.
     * @return array The array containing a newly added contact.
     * @throws Exception If there is an error while adding a new contact.
     */
    public function addContact($listID, $number, $name = null, $resubscribe = false) {
        $url = $this->server . "/services/manage-contacts.php";
        $postData = [
            'key' => $this->apiKey,
            'listID' => $listID,
            'number' => $number,
            'name' => $name,
            'resubscribe' => $resubscribe
        ];
        return $this->sendRequest($url, $postData)["contact"];
    }
    
    /**
     * Unsubscribe a contact from a list
     * @param int    $listID The ID of the contacts list from which you want to unsubscribe this contact.
     * @param string $number The mobile number of the contact.
     * @return array The array containing the unsubscribed contact.
     * @throws Exception If there is an error while setting subscription to false.
     */
    public function unsubscribeContact($listID, $number) {
        $url = $this->server . "/services/manage-contacts.php";
        $postData = [
            'key' => $this->apiKey,
            'listID' => $listID,
            'number' => $number,
            'unsubscribe' => true
        ];
        return $this->sendRequest($url, $postData)["contact"];
    }
    
    /**
     * Get account balance
     * @return string The amount of message credits left.
     * @throws Exception If there is an error while getting message credits.
     */
    public function getBalance() {
        $url = $this->server . "/services/send.php";
        $postData = [
            'key' => $this->apiKey
        ];
        $credits = $this->sendRequest($url, $postData)["credits"];
        return is_null($credits) ? "Unlimited" : $credits;
    }
    
    /**
     * Send USSD request
     * @param string $request   USSD request you want to execute. e.g. *150#
     * @param int $device       The ID of a device you want to use to send this message.
     * @param int|null $simSlot Sim you want to use for this USSD request.
     * @return array The array containing details about USSD request that was sent.
     * @throws Exception If there is an error while sending a USSD request.
     */
    public function sendUssdRequest($request, $device, $simSlot = null) {
        $url = $this->server . "/services/send-ussd-request.php";
        $postData = [
            'key' => $this->apiKey,
            'request' => $request,
            'device' => $device,
            'sim' => $simSlot
        ];
        return $this->sendRequest($url, $postData)["request"];
    }
    
    /**
     * Get USSD request by ID
     * @param int $id The ID of a USSD request you want to retrieve.
     * @return array The array containing details about USSD request you requested.
     * @throws Exception If there is an error while getting a USSD request.
     */
    public function getUssdRequestByID($id) {
        $url = $this->server . "/services/read-ussd-requests.php";
        $postData = [
            'key' => $this->apiKey,
            'id' => $id
        ];
        return $this->sendRequest($url, $postData)["requests"][0];
    }
    
    /**
     * Get USSD requests
     * @param string   $request        The request text you want to look for.
     * @param int      $deviceID       The deviceID of the device which USSD requests you want to retrieve.
     * @param int      $simSlot        Sim slot of the device which USSD requests you want to retrieve.
     * @param int|null $startTimestamp Search for USSD requests sent after this time.
     * @param int|null $endTimestamp   Search for USSD requests sent before this time.
     * @return array The array containing USSD requests.
     * @throws Exception If there is an error while getting USSD requests.
     */
    public function getUssdRequests($request, $deviceID = null, $simSlot = null, $startTimestamp = null, $endTimestamp = null) {
        $url = $this->server . "/services/read-ussd-requests.php";
        $postData = [
            'key' => $this->apiKey,
            'request' => $request,
            'deviceID' => $deviceID,
            'simSlot' => $simSlot,
            'startTimestamp' => $startTimestamp,
            'endTimestamp' => $endTimestamp
        ];
        return $this->sendRequest($url, $postData)["requests"];
    }
    
    /**
     * Get all enabled devices
     * @return array The array containing all enabled devices
     * @throws Exception If there is an error while getting devices.
     */
    public function getDevices() {
        $url = $this->server . "/services/get-devices.php";
        $postData = [
            'key' => $this->apiKey
        ];
        return $this->sendRequest($url, $postData)["devices"];
    }
    
    /**
     * Send request to API
     * @param string $url      The URL to send request to
     * @param array  $postData The data to send
     * @return array The response data
     * @throws Exception If there is an error in the request
     */
    private function sendRequest($url, $postData) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        // Disable SSL verification for development environment
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        curl_close($ch);
        if ($httpCode == 200) {
            $json = json_decode($response, true);
            if ($json == false) {
                if (empty($response)) {
                    throw new Exception("Missing data in request. Please provide all the required information to send messages.");
                } else {
                    throw new Exception($response);
                }
            } else {
                if ($json["success"]) {
                    return $json["data"];
                } else {
                    throw new Exception($json["error"]["message"]);
                }
            }
        } else {
            throw new Exception("HTTP Error Code : {$httpCode}");
        }
    }
}