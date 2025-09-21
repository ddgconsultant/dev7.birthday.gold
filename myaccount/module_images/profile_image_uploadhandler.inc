<?php
// At the very top of profile_image_uploadhandler.inc
$DEBUG = true; // Set to false in production

// Debug function to keep logs clean and controlled
function debug_log($message, $data = null) {
    global $DEBUG;
    if (!$DEBUG) return;
    
    $log = "[BG-DEBUG] " . $message;
    if ($data !== null) {
        $log .= ": " . (is_array($data) || is_object($data) ? json_encode($data) : $data);
    }
    error_log($log);
    session_tracking('upload-debug', $log);
}

// Start tracking
debug_log("Upload handler started");
debug_log("Request data", $_POST);
debug_log("Files data", $_FILES);

// Log environment information
debug_log("PHP version", phpversion());
debug_log("Server software", $_SERVER['SERVER_SOFTWARE']);
debug_log("Upload max filesize", ini_get('upload_max_filesize'));
debug_log("Post max size", ini_get('post_max_size'));
debug_log("Max execution time", ini_get('max_execution_time'));
debug_log("Memory limit", ini_get('memory_limit'));
debug_log("Document root", $_SERVER['DOCUMENT_ROOT']);

$skip = false;
$response = ['success' => false, 'message' => ''];
$type = $_POST['type'] ?? '';

debug_log("Upload type", $type);
debug_log("Component config keys", array_keys($componentConfig));

// Then check if type exists
if (!isset($componentConfig[$type])) {
    $response['message'] = "Invalid upload type";
    debug_log("Invalid upload type", $type);
    $response['type'] = $type;
    $response['available_types'] = array_keys($componentConfig);
    $response['post_data'] = $_POST;

    $transferpage['message'] = json_encode($response);
    $transferpage['url'] = '/error';
    $system->endpostpage($transferpage);
    exit;
}


//================================================================================================
// manage DELETE requests
if (isset($_POST['did']) && !$skip) {
    debug_log("Processing delete request");
    $fileToDelete = $qik->decodeId($_POST['did']);
    debug_log("File to delete ID", $fileToDelete);
    
    if ($fileToDelete) {
        // Check if this is a verification ID and if user has permission
        $sql = "SELECT `name` FROM bg_user_attributes WHERE attribute_id = :id AND user_id = :user_id LIMIT 1";
        debug_log("SQL query", $sql);
        
        $stmt = $database->prepare($sql);
        $stmt->execute([':id' => $fileToDelete, ':user_id' => $user_id]);
        $fileInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        debug_log("File info", $fileInfo);

        $database->query("update bg_user_attributes set `status`='deleted', modify_dt=now() where attribute_id= ? ", [$fileToDelete]);
        debug_log("Database update executed");
        
        $response['success'] = true;
        $response['message'] = ($_POST['deletetype'] == 'avatar' ? 'Avatar' : 
                             ($_POST['deletetype'] == 'verification_id' ? 'Verification ID' : 'Cover')) . 
                             " deleted successfully.";
    }
    $skip = true;
    debug_log("Redirecting after delete", $response['message']);
    $transferpage['message'] = $response['message'];
    $transferpage['url'] = '/myaccount/profile-images';
    $system->endpostpage($transferpage);
    exit;
}


//================================================================================================
// Handle file uploads
if (!isset($_POST['sid']) && !$skip) {
    debug_log("Processing file upload");
    
    // Check if this is a valid upload type
    if (!isset($componentConfig[$type])) {
        $response['message'] = "Invalid upload type";
        debug_log("Invalid upload type in upload section", $type);
        $response['$componentConfig[$type]'] = $componentConfig[$type]??'notset';
        echo json_encode($response);
        exit;
    }

    $currentConfig = $componentConfig[$type];
    debug_log("Current config", $currentConfig);

    include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.fileuploader_ui.php');
    debug_log("Included fileuploader_ui class");
    
    $userDirHash = md5($user_id);
    $userDir = $_SERVER['DOCUMENT_ROOT'] . '/file-uploads/' . $userDirHash . '/';
    debug_log("User directory", $userDir);

    $uploaderparams = array(
        'limit' => $currentConfig['upload_limits']['max_files'],
        'maxSize' => $currentConfig['upload_limits']['max_size'],
        'extensions' => $currentConfig['upload_limits']['mime_types'],
        'createDir' => true,
        'uploadDir' => $userDir,
        'title' => 'md5hashunique',
    );
    debug_log("Uploader params", $uploaderparams);

    try {
        $fileuploader_ui = new fileuploader_ui('files', $uploaderparams);
        debug_log("Created fileuploader_ui instance successfully");
    } catch (Exception $e) {
        debug_log("Error creating fileuploader_ui instance", $e->getMessage());
        $response['message'] = "Error initializing uploader: " . $e->getMessage();
        $transferpage['message'] = $response['message'];
        $transferpage['url'] = '/myaccount/profile-images';
        $system->endpostpage($transferpage);
        exit;
    }
    
    // Clean up removed files
    try {
        $removedFiles = $fileuploader_ui->getRemovedFiles('file');
        debug_log("Removed files", $removedFiles);
        
        foreach ($removedFiles as $key => $value) {
            debug_log("Removing file", $value['name']);
            @unlink($_SERVER['DOCUMENT_ROOT'] . '/public/uploads/' . $value['name']);
        }
    } catch (Exception $e) {
        debug_log("Error processing removed files", $e->getMessage());
    }

    try {
        $data = $fileuploader_ui->upload();
        debug_log("Upload result", $data);
    } catch (Exception $e) {
        debug_log("Error during upload", $e->getMessage());
        $response['message'] = "Upload error: " . $e->getMessage();
        $transferpage['message'] = $response['message'];
        $transferpage['url'] = '/myaccount/profile-images';
        $system->endpostpage($transferpage);
        exit;
    }

    if ($data['hasWarnings']) {
        debug_log("Upload warnings", $data['warnings']);
        $transferpage['message'] = $data['warnings'];
        $transferpage['url'] = '/myaccount/profile-images';
        $system->endpostpage($transferpage);
        exit;
    }

    if ($data['isSuccess'] && count($data['files']) > 0) {
        try {
            $fileList = $fileuploader_ui->getFileList();
            debug_log("File list", $fileList);
        } catch (Exception $e) {
            debug_log("Error getting file list", $e->getMessage());
            $response['message'] = "Error processing files: " . $e->getMessage();
            $transferpage['message'] = $response['message'];
            $transferpage['url'] = '/myaccount/profile-images';
            $system->endpostpage($transferpage);
            exit;
        }

        foreach ($fileList as $file) {
            $filename = $file['old_name'];
            $hash = md5($filename . '_' . rand(1000, 9999) . '_' . time());
            $extension = strtolower($file['extension']);
            
            // Debugging file details
            debug_log("Processing file", [
                'filename' => $filename,
                'hash' => $hash,
                'extension' => $extension,
                'file_path' => $file['file'],
                'file_exists' => file_exists($file['file']) ? 'Yes' : 'No'
            ]);
            
            // Special handling for verification IDs
            if ($type === 'verification_id') {
                $targetLocation = "public/usermedia/{$userDirHash}/.verification/{$hash}.{$extension}";
            } else {
                $targetLocation = "public/usermedia/{$userDirHash}/{$hash}.{$extension}";
            }
            debug_log("Target location", $targetLocation);

            $fileArray = [
                'name' => $filename,
                'type' => $file['type'],
                'tmp_name' => $file['file'],
                'error' => 0,
                'size' => $file['size']
            ];
            debug_log("File array", $fileArray);

            // Check if $fileuploader exists before using it
            if (!isset($fileuploader) || !is_object($fileuploader)) {
                debug_log("NOTICE: fileuploader object not defined, using fallback method");
                
                // Fallback to direct file move
                debug_log("Using fallback file upload method");
                $targetDir = dirname($_SERVER['DOCUMENT_ROOT'] . '/' . $targetLocation);
                
                // Check directory exists
                if (!is_dir($targetDir)) {
                    debug_log("Creating directory", $targetDir);
                    $mkdir_result = mkdir($targetDir, 0755, true);
                    debug_log("Directory creation result", $mkdir_result ? "Success" : "Failed");
                    
                    if (!$mkdir_result) {
                        debug_log("Directory creation error", error_get_last());
                    }
                } else {
                    debug_log("Target directory already exists");
                }
                
                // Double check directory exists and is writable
                debug_log("Target directory exists", is_dir($targetDir) ? "Yes" : "No");
                debug_log("Target directory writable", is_writable($targetDir) ? "Yes" : "No");
                
                // Move the uploaded file directly
                $fullTargetPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $targetLocation;
                debug_log("Moving file to", $fullTargetPath);
                debug_log("Temporary file exists", file_exists($file['file']) ? "Yes" : "No");
                
                if (file_exists($file['file'])) {
                    debug_log("Temporary file readable", is_readable($file['file']) ? "Yes" : "No");
                    debug_log("Temporary file size", filesize($file['file']));
                }
                
                $moveResult = move_uploaded_file($file['file'], $fullTargetPath);
                debug_log("Move result", $moveResult ? "Success" : "Failed");
                
                if (!$moveResult) {
                    debug_log("Move error", error_get_last());
                    // Try a copy as last resort
                    debug_log("Attempting to copy instead");
                    $copyResult = copy($file['file'], $fullTargetPath);
                    debug_log("Copy result", $copyResult ? "Success" : "Failed");
                    
                    if (!$copyResult) {
                        debug_log("Copy error", error_get_last());
                    } else {
                        $moveResult = true; // Consider it a success if copy worked
                    }
                }
                
                $uploadResult = ['success' => $moveResult];
            } else {
                debug_log("Using fileuploader object method");
                try {
                    $uploadResult = $fileuploader->uploadFile($fileArray, $targetLocation);
                    debug_log("Upload result from fileuploader", $uploadResult);
                } catch (Exception $e) {
                    debug_log("Error using fileuploader", $e->getMessage());
                    $uploadResult = ['success' => false, 'error' => $e->getMessage()];
                }
            }

            if ($uploadResult['success']) {
                debug_log("File upload successful");
                $response['success'] = true;
                $response['message'] .= "File uploaded successfully.";
                
                // Clean up temporary file if it still exists
                if (file_exists($file['file'])) {
                    @unlink($file['file']);
                    debug_log("Cleaned up temporary file");
                }

                // Clean up temporary directory if empty
                if (is_dir($userDir) && count(scandir($userDir)) <= 2) { // Only . and .. directories
                    rmdir($userDir);
                    debug_log("Removed empty temporary directory");
                }

                // Special handling for verification ID storage
                $description = $type === 'verification_id' 
                    ?  "//files.birthday.gold/{$targetLocation}" 
                    : "//files.birthday.gold/{$targetLocation}";
                debug_log("File description", $description);

                // Double check if file was actually created at destination
                $finalFilePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $targetLocation;
                debug_log("Final file exists", file_exists($finalFilePath) ? "Yes" : "No");
                if (file_exists($finalFilePath)) {
                    debug_log("Final file size", filesize($finalFilePath));
                    debug_log("Final file permissions", substr(sprintf('%o', fileperms($finalFilePath)), -4));
                }

                // Insert into database
                $sql = "INSERT INTO bg_user_attributes 
                       (user_id, `type`, `name`, `description`, `status`, create_dt, modify_dt, 
                        string_value, `value`, category)
                       VALUES 
                       (:userid, 'profile_image', :name, :description, :status, now(), now(), 
                        :filename, :size, :category)";
                
                $params = [
                    ':userid' => $user_id,
                    ':name' => $type,
                    ':description' => $description,
                    ':filename' => $filename,
                    ':size' => $file['size'],
                    ':status' => $type === 'verification_id' ? 'pending_review' : 'active',
                    ':category' => null
                ];
                debug_log("SQL", $sql);
                debug_log("SQL params", $params);

                try {
                    $stmt = $database->prepare($sql);
                    $result = $stmt->execute($params);
                    debug_log("Database insert result", $result ? "Success" : "Failed");

                    if (!$result) {
                        debug_log("Database error", $database->errorInfo());
                    } else {
                        $newfileuploadedid = $database->lastInsertId();
                        debug_log("New file ID", $newfileuploadedid);
                    }
                } catch (Exception $e) {
                    debug_log("Database error", $e->getMessage());
                    $response['message'] .= " Warning: Database update failed.";
                }
            } else {
                debug_log("File upload failed", $uploadResult);
                $response['uploadresult'] = $uploadResult;
                $response['message'] .= "File upload failed for {$filename}. ";
            }
        }
    } else {
        debug_log("No files to process or upload failed");
    }

   debug_log("Redirecting to profile images page with message", $response['message']);
   $transferpage['message'] = $response['message'];
   $transferpage['url'] = '/myaccount/profile-images';
   $system->endpostpage($transferpage);
   exit;
}


//================================================================================================
// Handle SELECT/Primary setting
if (!$skip && ((isset($_POST['sid']) && !empty($_POST['sid'])) || $newfileuploadedid)) {
    debug_log("Processing select/primary setting");
    $fileToSelectid = isset($_POST['sid']) ? $qik->decodeId($_POST['sid']) : null;
    debug_log("File to select from POST", $fileToSelectid);
    
    if (empty($fileToSelectid) && $newfileuploadedid) {
        $fileToSelectid = $newfileuploadedid;
        debug_log("Using newly uploaded file ID", $fileToSelectid);
    }
    
    $sql = "SELECT `name`, description FROM bg_user_attributes WHERE attribute_id = :id limit 1";
    debug_log("SQL query", $sql);
    
    $stmt = $database->prepare($sql);
    $stmt->execute([':id' => $fileToSelectid]);
    $fileselected = $stmt->fetch(PDO::FETCH_ASSOC);
    debug_log("Selected file info", $fileselected);

    if ($fileselected) {
        // Don't allow primary selection for verification IDs
        if ($fileselected['name'] === 'verification_id') {
            $response['message'] = "Cannot set verification ID as primary.";
            debug_log("Cannot set verification ID as primary");
            echo json_encode($response);
            exit;
        }

        $database->query("update bg_user_attributes set `category`=null 
                        where user_id= ? and `type`='profile_image' and `name`= ?  ", 
                        [$user_id, $fileselected['name']]);
        debug_log("Reset primary category for other files");
        
        $database->query("update bg_user_attributes set `category`='primary', modify_dt=now() 
                        where attribute_id= ?  ", [$fileToSelectid]);
        debug_log("Set new primary file");
        
        if ($fileselected['name'] == 'avatar') {
            $database->query("update bg_users set `avatar`=?, modify_dt=now() 
                            where user_id= ? ", [$fileselected['description'], $user_id]);
            debug_log("Updated avatar in users table");
        }
        
        $response['success'] = true;
        $response['message'] = ($fileselected['name'] == 'avatar' ? 'Avatar' : 'Cover') . 
                             " changed successfully.";
                             
        $account->getuserdata($user_id, 'user_id');
        $session->set($fileselected['name'], $fileselected['description']);
        debug_log("Updated session data");
    }

    $skip = true;
    debug_log("Redirecting after primary selection", $response['message']);
    $transferpage['message'] = $response['message'];
    $transferpage['url'] = '/myaccount/profile-images';
    $system->endpostpage($transferpage);
    exit;
}



//================================================================================================
// Return response for any other cases
if ($response['success']) {
    $response['url'] = '/myaccount/profile-images';
} else {
    $response['message'] = 'Error occurred while processing the form.';
}

debug_log("End of upload handler, redirecting");
header('Location: /myaccount/profile-images');
exit;
?>