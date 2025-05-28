<?php

require_once $dir['vendor'].'/autoload.php';

use obregonco\B2\Client;
use obregonco\B2\Bucket;

class cdn {
    private $cdn_vendor = 'b2';
    private $accountId;
    private $applicationKey;
    private $bucketName;
    private $b2Client;

 

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function __construct($accountId, $applicationKey) {
    // Initialize class properties
    $this->accountId = $accountId;
    $this->bucketName = 'birthdaygold202406-cdn';
    $this->applicationKey = $applicationKey;

    // Access global settings
    global $sitesettings;

    // Configure Guzzle client based on SSL certificate settings
    $guzzleConfig = [
        'keyId' => $accountId,
        'applicationKey' => $applicationKey,
        'guzzle' => []
    ];

    if (isset($sitesettings['ssl_cert']['pem_path'])) {
        // Use the provided PEM file for SSL verification
        $guzzleConfig['guzzle']['verify'] = $sitesettings['ssl_cert']['pem_path'];
    } else {
        // Fallback: Disable SSL verification (not recommended for production)
        $guzzleConfig['guzzle']['verify'] = false;
    }

    // Instantiate the Backblaze B2 client
    $this->b2Client = new Client($accountId, $guzzleConfig);
    $this->b2Client->version = 2; // Set API version
}
 

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function store_object($input, $folderpath) {
        if (file_exists($input)) {
            $filePath = realpath($input);
            $fileName = basename($filePath);
            $content = fopen($filePath, 'r');
        } else {
            $fileName = bin2hex(random_bytes(16));
            $mimeType = $this->getMimeType($input);
            $fileName .= $mimeType ? '.' . $mimeType : '';
            $content = $input;
        }

        return $this->b2Client->upload([
            'BucketName' => $this->bucketName,
            'FileName' => $folderpath . '/' . $fileName,
            'Body' => $content
        ]);
    }

 

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  private function getMimeType($content) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($content);
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/webp' => 'webp',
            'image/tiff' => 'tiff',
            'image/x-icon' => 'ico',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/zip' => 'zip',
            'application/x-rar-compressed' => 'rar',
            'application/x-7z-compressed' => '7z',
            'application/x-tar' => 'tar',
            'application/json' => 'json',
            'application/xml' => 'xml',
            'text/plain' => 'txt',
            'text/html' => 'html',
            'text/css' => 'css',
            'text/javascript' => 'js',
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
            'audio/ogg' => 'ogg',
            'video/mp4' => 'mp4',
            'video/x-msvideo' => 'avi',
            'video/x-matroska' => 'mkv',
            'video/webm' => 'webm',
            'video/quicktime' => 'mov'
        ];

        return $mimeMap[$mimeType] ?? null;
    }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function delete_object($fileId) {
        return $this->b2Client->deleteFileFromArray([
            'FileId' => $fileId
        ]);
    }

 

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function object_details($fileId) {
        return $this->b2Client->download([
            'FileId' => $fileId
        ]);
    }

 

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function bucket_list() {
        return $this->b2Client->listBuckets();
    }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function create_bucket($bucketName, $bucketType = Bucket::TYPE_PRIVATE) {
        return $this->b2Client->createBucket([
            'BucketName' => $bucketName,
            'BucketType' => $bucketType
        ]);
    }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function update_bucket($bucketId, $bucketType) {
        return $this->b2Client->updateBucket([
            'BucketId' => $bucketId,
            'BucketType' => $bucketType
        ]);
    }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function delete_bucket($bucketId) {
        return $this->b2Client->deleteBucket([
            'BucketId' => $bucketId
        ]);
    }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function list_files($bucketId) {
        return $this->b2Client->listFilesFromArray([
            'BucketId' => $bucketId
        ]);
    }
}

/*

// Example usage
$cdn = new cdn('accountId', 'applicationKey');

// Create a bucket
$bucket = $cdn->create_bucket('my-special-bucket', Bucket::TYPE_PRIVATE);

// Upload a file
$file = $cdn->store_object('path/to/file', 'folderpath');

// Get file details
$fileContent = $cdn->object_details($file->getId());

// List all buckets
$buckets = $cdn->bucket_list();

// List files in a bucket
$fileList = $cdn->list_files($bucket->getId());

// Delete a file
$fileDelete = $cdn->delete_object($file->getId());

// Update a bucket
$updatedBucket = $cdn->update_bucket($bucket->getId(), Bucket::TYPE_PUBLIC);

// Delete a bucket
$cdn->delete_bucket('4c2b957661da9c825f465e1b');
*/