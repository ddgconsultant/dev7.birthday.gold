<?php

require_once ($dir['vendor'] . '/autoload.php');

use obregonco\B2\Client as B2Client;

class FileUploader
{
    private $storeMethod;
    private $allowedFileTypes;
    private $maxFileSize;
    private $b2Credentials;

    

 # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function __construct($b2Credentials = [])
    {
        $this->b2Credentials = $b2Credentials;
        $this->storeMethod = 'backblaze'; // Default store method, can be set globally
        $this->allowedFileTypes = ["jpeg", "jpg", "gif", "webp", "png", "image/svg+xml", "svg+xml", "svg"]; // Default allowed file types
        $this->maxFileSize = 5242880; // Default max file size (5MB)
    }



 # ##--------------------------------------------------------------------------------------------------------------------------------------------------
 public function uploadFile($file, $targetlocation = '', $userId = 0)
    {
        if (!isset($file['name'])) {
            return ['success' => false, 'message' => 'No file uploaded.'];
        }

        $fileType = pathinfo($targetlocation, PATHINFO_EXTENSION);
        $DEBUG = false;

        if ($DEBUG) {
            echo '<pre>';
            print_r($file);
        }
        if ($this->validateFile($file, $fileType)) {
          #  $randomString = bin2hex(random_bytes(10));
         #   $hashedFileName = hash('sha256', $userId . $randomString) . '.' . $fileType;
            if ($DEBUG)    echo "processing: " . $this->storeMethod;

            switch ($this->storeMethod) {
                case 'localstorage':
                    return $this->storeLocally($file['tmp_name'], $targetlocation);
                case 'backblaze':
                    return $this->storeBackblazeNative($file['tmp_name'], $targetlocation);
                default:
                    return ['success' => false, 'message' => 'Invalid storage method.', 'system' => $this->storeMethod];
            }
        } else {
            return ['success' => false, 'message' => 'Invalid file type or size. [fxuf1]'];
        }
    }



 # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function validateFile($file, $fileType)
    {
        if ($file['size'] > $this->maxFileSize) {
            return false;
        }

        if (!empty($this->allowedFileTypes) && !in_array($fileType, $this->allowedFileTypes)) {
            return false;
        }

        return true;
    }



 # ##--------------------------------------------------------------------------------------------------------------------------------------------------
 private function storeLocally($incomingfile, $targetlocation)
    {
        $DEBUG = false;

        if ($DEBUG) {
            echo '<pre>';
            echo "prepath: " . $targetlocation;;
        }
        $targetlocation = (__DIR__ . '/../cdn.birthday.gold/' . $targetlocation);
        $targetDir = dirname($targetlocation);

        // Resolve the absolute path
        # $targetlocation = realpath($targetlocation);
        $targetDir = realpath($targetDir);

        if ($DEBUG) {
            echo "<br>";
            print_r("targetlocation: " . $targetlocation);
            echo "<br>";
            print_r("target_dir: " . $targetDir);
        }


        # $fileTmp=$incomingfile['tmp_name'];
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
            print_r("create: " .   $targetDir);
        }
        if ($DEBUG) {
            echo "<Br><hr>";
            echo "<br>";
            print_r("targetlocation: " . $targetlocation);
            echo "<br>";
            print_r("target_dir: " . $targetDir);
            echo "<Br><hr>";
            print_r($incomingfile);
            exit;
        }

        if (move_uploaded_file($incomingfile, $targetlocation)) {
            return ['success' => true, 'message' => 'File uploaded successfully.', 'file_path' => $targetlocation, 'system' => 'local'];
        } else {
            return ['success' => false, 'message' => 'There was an error uploading your file.', 'system' => 'local'];
        }
    }

    

 # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function storeBackblazeNative($incomingfile, $targetlocation)
    {
    
    $baseUrl = 'https://api.backblazeb2.com/b2api/v2/';
        $authUrl = $baseUrl . 'b2_authorize_account';
        $applicationKeyId = $this->b2Credentials['BACKBLAZE_ACCOUNT_ID'];
        $applicationKey = $this->b2Credentials['BACKBLAZE_APP_KEY'];
        $bucketId = 'fe4994ab7fa52b3397050c1c';  ## $this->b2Credentials['BACKBLAZE_BUCKET_ID'];

        // Authorize account
        $auth = base64_encode($applicationKeyId . ':' . $applicationKey);
        $headers = [
            'Authorization: Basic ' . $auth
        ];

        $ch = curl_init($authUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable SSL verification
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code
        $curlError = curl_error($ch); // Capture cURL error
        curl_close($ch);

        $authData = json_decode($response, true);

        if ($httpCode == 200 && isset($authData['authorizationToken'])) {
            $uploadUrl = $authData['apiUrl'] . '/b2api/v2/b2_get_upload_url';
            $headers = [
                'Authorization: ' . $authData['authorizationToken']
            ];

            $postFields = json_encode([
                'bucketId' => $bucketId
            ]);

            $ch = curl_init($uploadUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable SSL verification
            $response = curl_exec($ch);
            curl_close($ch);

            $uploadData = json_decode($response, true);

            if (isset($uploadData['uploadUrl'])) {
                $file = fopen($incomingfile, 'r');
                $sha1OfFile = sha1_file($incomingfile);

                $headers = [
                    'Authorization: ' . $uploadData['authorizationToken'],
                    'X-Bz-File-Name: ' . $targetlocation,
                    'Content-Type: b2/x-auto',
                    'X-Bz-Content-Sha1: ' . $sha1OfFile,
                    'X-Bz-Info-Author: unknown'
                ];

                $ch = curl_init($uploadData['uploadUrl']);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, fread($file, filesize($incomingfile)));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable SSL verification
                $response = curl_exec($ch);
                curl_close($ch);
                fclose($file);

                $result = json_decode($response, true);

                if (isset($result['fileId'])) {
                    return [
                        'success' => true,
                        'message' => 'File uploaded successfully',
                        'file_path' => $targetlocation,
                        'system' => 'backblaze'                        
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'File upload failed: ' . $response,
                        'system' => 'backblaze'       
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Could not get upload URL: ' . $response,
                    'system' => 'backblaze'       
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Authorization failed: HTTP Code ' . $httpCode . ' - ' . $response . ' - cURL Error: ' . $curlError,
                'system' => 'backblaze'       
            ];
        }
    }



 # ##--------------------------------------------------------------------------------------------------------------------------------------------------
 public function listBuckets($system)
 {
     $authData = $this->authorizeAccount($system);
     if (!$authData) {
         return ['success' => false, 'message' => 'Authorization failed.'];
     }

     $url = $authData['apiUrl'] . '/b2api/v2/b2_list_buckets';
     $headers = ['Authorization: ' . $authData['authorizationToken']];

     $response = $system->curlRequest($url, $headers, [], 'POST');
if (!$response || !isset($response['decoded'])) {
    echo "Debug: Failed response - " . json_encode($response) . "\n";
}

     return $response['decoded'] ?? ['success' => false, 'message' => 'Failed to list buckets.'];
 }

 
 # ##--------------------------------------------------------------------------------------------------------------------------------------------------
 public function listFiles($system, $bucketId)
 {
     $authData = $this->authorizeAccount($system);
     if (!$authData) {
         return ['success' => false, 'message' => 'Authorization failed.'];
     }

     $url = $authData['apiUrl'] . '/b2api/v2/b2_list_file_names';
     $headers = ['Authorization: ' . $authData['authorizationToken']];
     $postData = ['bucketId' => $bucketId];

     $response = $system->curlRequest($url, $headers, $postData, 'POST');
     return $response['decoded'] ?? ['success' => false, 'message' => 'Failed to list files.'];
 }

 
 # ##--------------------------------------------------------------------------------------------------------------------------------------------------
 public function deleteFile($system, $fileId, $fileName)
 {
     $authData = $this->authorizeAccount($system);
     if (!$authData) {
         return ['success' => false, 'message' => 'Authorization failed.'];
     }

     $url = $authData['apiUrl'] . '/b2api/v2/b2_delete_file_version';
     $headers = ['Authorization: ' . $authData['authorizationToken']];
     $postData = ['fileId' => $fileId, 'fileName' => $fileName];

     $response = $system->curlRequest($url, $headers, $postData, 'POST');
     return $response['decoded'] ?? ['success' => false, 'message' => 'Failed to delete file.'];
 }


 # ##--------------------------------------------------------------------------------------------------------------------------------------------------
 private function authorizeAccount($system)
 {
     $authUrl = 'https://api.backblazeb2.com/b2api/v2/b2_authorize_account';
     $auth = base64_encode($this->b2Credentials['BACKBLAZE_ACCOUNT_ID'] . ':' . $this->b2Credentials['BACKBLAZE_APP_KEY']);
     $headers = ['Authorization: Basic ' . $auth];

     $response = $system->curlRequest($authUrl, $headers, [], 'GET');
     return $response['decoded'] ?? null;
 }



}
