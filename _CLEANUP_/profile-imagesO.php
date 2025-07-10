<?php
$addClasses[] = 'fileuploader';
#$addClasses[] = 'fileuploader_ui';
#$classparams2['fileuploader_ui']=['uploadDir' => $_SERVER['DOCUMENT_ROOT'] . '/file-uploads/', 'title'=>'useridhash___{random}'];
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$user_id = $current_user_data['user_id'];
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml', 'image/bmp', 'image/tiff'];
$output_end = '';
$errormessage = '';
$transferpagedata = [];
$newfileuploadedid = false;


#-------------------------------------------------------------------------------
# HANDLE POSTED FORM
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    $skip = false;

    //================================================================================================
    // manage DELETE requests
    if (isset($_POST['did']) && !$skip) {
        $response = ['success' => false, 'message' => ''];
        $fileToDelete = $qik->decodeId($_POST['did']);
        if ($fileToDelete) {
            
            $database->query("update bg_user_attributes set `status`='deleted', modify_dt=now() where attribute_id= ? ", [$fileToDelete]);
            # [$user_id, $_POST['type'], "//files.birthday.gold/{$targetLocation}"]);
            $response['success'] = true;
         #   $response['message'] = "File deleted successfully.";
            $response['message'] = ($_POST['deletetype'] == 'avatar' ? 'Avatar' : 'Cover') . " deleted successfully.";
    
        }
        $skip = true;
        $transferpage['message'] = $response['message'];
        $transferpage['url'] = '/myaccount/profile-images';
        # $transferpage['selfredirecting'] = true;
        $system->endpostpage($transferpage);
        exit;
    }


    $type = $_POST['type'] ?? '';

    //================================================================================================
    //////////////////////////////////////////
    if (!isset($_POST['sid'])) {
        if (($type === 'avatar' || $type === 'account_cover') && !$skip) {
            include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.fileuploader_ui.php');
            $userDirHash = md5($user_id);  // Hash the user ID for a unique user directory
            $userDir = $_SERVER['DOCUMENT_ROOT'] . '/file-uploads/' . $userDirHash . '/';

            // Define target directories
            $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/public/uploads/profile/";
            $response = ['success' => false, 'message' => ''];
            
            $uploaderparams=array(
                'limit' => 5, // Allow multiple file uploads (e.g., 5 files)
                'maxSize' => 3,
                'extensions' => $allowedMimeTypes,
                'createDir' => true,
                'uploadDir' => $userDir,
                'title' => 'md5hashunique',
            );

            // Initialize the new file uploader UI with multi-file support
            $fileuploader_ui = new fileuploader_ui('files', $uploaderparams);

            // Unlink the files (only for preloaded files)
            foreach ($fileuploader_ui->getRemovedFiles('file') as $key => $value) {
                unlink($_SERVER['DOCUMENT_ROOT'] . '/public/uploads/' . $value['name']);
            }

            // Call to upload the files
            $data = $fileuploader_ui->upload();

            // If uploaded and success
            if ($data['isSuccess'] && count($data['files']) > 0) {
                // Get uploaded files
                $uploadedFiles = $data['files'];
            }

            // If warnings occurred
            if ($data['hasWarnings']) {
                $warnings = $data['warnings'];
                $transferpage['message'] = $warnings;
                $transferpage['url'] = '/myaccount/profile-images';
                # $transferpage['selfredirecting'] = true;
                $system->endpostpage($transferpage);
                exit;
            }

            // Get the file list
            $fileList = $fileuploader_ui->getFileList();

            // Loop through the file list and upload to Backblaze
            foreach ($fileList as $file) {
                // Prepare the file details for Backblaze upload
                $filename = $file['old_name']; // Use original file name
                $hash = md5($filename . '_' . rand(1000, 9999) . '_' . time()); // Hash the file name for a unique identifier
                $userDirHash = md5($user_id); // User-specific hashed directory
                $extension = strtolower($file['extension']);
                $targetLocation = "public/usermedia/{$userDirHash}/{$hash}.{$extension}"; // Backblaze target path

                // Simulate file upload with the path from the new file uploader
                $tmpFile = $file['file'];
                $filesize = $file['size'];

                // Create an array for Backblaze file uploader
                $fileArray = [
                    'name' => $filename,
                    'type' => $file['type'],
                    'tmp_name' => $tmpFile,
                    'error' => 0, // Assuming no error
                    'size' => $filesize
                ];

                // Upload the file using the Backblaze file uploader
                $uploadResult = $fileuploader->uploadFile($fileArray, $targetLocation);

                // Check if the upload was successful
                if ($uploadResult['success']) {
                    $response['success'] = true;
                    $response['message'] .= "File uploaded successfully to: //files.birthday.gold/{$targetLocation}. ";
                    @unlink($tmpFile);

                    // Check if the directory is empty and remove it if so
                    if (is_dir($userDir) && count(scandir($userDir)) == 2) { // Only '.' and '..' should be present
                        rmdir($userDir); // Remove the directory
                    }

                    // Insert file metadata into the database
                    $sql = "INSERT INTO bg_user_attributes (user_id, `type`, `name`, `description`, `status`, create_dt, modify_dt, string_value, `value`)
VALUES (:userid, 'profile_image', :name, :description, 'active', now(), now(), :filename, :size)";
                    $stmt = $database->prepare($sql);
                    $result = $stmt->execute([
                        ':userid' => $user_id,
                        ':name' => $type,
                        ':description' => "//files.birthday.gold/{$targetLocation}",
                        ':filename' => $filename,
                        ':size' => $filesize
                    ]);

                    $newfileuploadedid = $database->lastInsertId();
                } else {
                    $response['uploadresult'] = $uploadResult;
                    $response['message'] .= "File upload failed for {$filename}. ";
                }
            }

            // Final response after all files processed
            echo json_encode($response);
        } else {
            $response['message'] = "No files to upload.";
        }
    }




    //================================================================================================
    // manage SELECT requests -- or make the latest uploaded file the primary
    if ((isset($_POST['sid']) && !empty($_POST['sid'])) || $newfileuploadedid) {
        $response = ['success' => false, 'message' => ''];
        $fileToSelectid = $qik->decodeId($_POST['sid']);
        if (empty($fileToSelectid)) $fileToSelectid = $newfileuploadedid;
        $sql = "SELECT `name`, description FROM bg_user_attributes WHERE attribute_id = :id limit 1";
        $stmt = $database->prepare($sql);
        $stmt->execute([':id' => $fileToSelectid]);

        $fileselected = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fileselected) {
            $database->query("update bg_user_attributes set `category`=null where user_id= ? and `type`='profile_image' and `name`= ?  ", [$user_id, $fileselected['name']]);
            $database->query("update bg_user_attributes set `category`='primary', modify_dt=now() where attribute_id= ?  ", [$fileToSelectid]);
            if ($fileselected['name'] == 'avatar') {
                $database->query("update bg_users set `avatar`=?, modify_dt=now() where user_id= ? ", [$fileselected['description'], $user_id]);
            }
            # [$user_id, $_POST['type'], "//files.birthday.gold/{$targetLocation}"]);
            $response['success'] = true;
            $response['message'] = ($fileselected['name'] == 'avatar' ? 'Avatar' : 'Cover') . " changed successfully.";
        }

        $account->getuserdata($user_id,  'user_id');

        $session->set($fileselected['name'], $fileselected['description']);


        $skip = true;
        $transferpage['message'] = $response['message'];
        $transferpage['url'] = '/myaccount/profile-images';
        # $transferpage['selfredirecting'] = true;
        $system->endpostpage($transferpage);
        exit;
    }



    //================================================================================================
    // return the response
    if ($response['success']) {
        // If successful, set the message and redirect URL
        $response['url'] = '/myaccount/profile-images';
        echo json_encode($response);
        exit;
    } else {
        // If not successful, return an error message
        $response['success'] = false;
        $response['message'] = 'Error occurred while processing the form.';
        echo json_encode($response);
        exit;
    }

    $transferpage['message'] = $response['message'];
    $transferpage['url'] = '/myaccount/profile-images';
    unset($transferpage['selfredirecting']);
    $system->endpostpage($transferpage);
    exit;
}





#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------
$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);
$bodycontentclass = 'profile-image-page'; // Additional class for custom styling
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');

$additionalstyles .= '
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Material+Icons+Outlined">
<link rel="stylesheet" href="/public/js/fileupload-drag-drop/fileUpload.css">

<style>
.image-wrapper:hover .image-overlay {
opacity: 1;
}
.uploaded-images-grid {
display: flex;
flex-wrap: wrap;
}
.uploaded-image {
flex: 0 0 25%;
max-width: 25%;
}

</style>

<!-- fonts -->
<link href="https://fonts.googleapis.com/css?family=Roboto:400,700" rel="stylesheet">
<link href="/public/js/fileuploader_ui/font/font-fileuploader.css" rel="stylesheet">

<!-- styles -->
<link href="/public/js/fileuploader_ui/jquery.fileuploader.min.css" media="all" rel="stylesheet">

<!-- js -->
<script src="/public/js/fileuploader_ui/jquery.fileuploader.js" type="text/javascript"></script>
<script src="/public/js/fileuploader_ui/examples/default/js/custom.js" type="text/javascript"></script>

<style>
form {margin: 15px;}
.fileuploader {border: 2px dashed #e5e5e5;}
</style>
';




$bdarray = explode('-', $current_user_data['birthdate']);
$coverbanner = '/public/images/site_covers/cbanner_' . $bdarray[1] . '.jpg';

echo '
<div class="container main-content mt-lg-5 pt-lg-5">
<div class="row">

<div class="col-md-12">
' . $display->formaterrormessage($transferpagedata['message']) . '
<div class="card">
<div class="card-header">
<h5 class="mb-0">Your Images</h5>
</div>
<div class="card-body">
<!-- Tabs navigation -->
<ul class="nav nav-tabs" id="imageTabs" role="tablist">
<li class="nav-item">
<a class="nav-link active" id="avatars-tab" data-bs-toggle="tab" href="#avatars" role="tab">Avatars</a>
</li>
<li class="nav-item">
<a class="nav-link" id="cover-images-tab" data-bs-toggle="tab" href="#cover-images" role="tab">Cover Images</a>
</li>
</ul>
';

////--------------------------------
/// DEFINE MODALS
$modals = [
    'avatars' => [
        'id' => 'uploadAvatarModal',
        'tag' => 'Avatar',
        'show' => 'show active',
        'label' => 'uploadAvatarModalLabel',
        'title' => 'Upload Avatar',
        'cardsize' => 'col-xs-6 col-sm-6 col-md-4 col-lg-3 col-xl-2',
        'inputId' => 'avatarImageInput',
        'hiddenValue' => 'avatar',
        'recommendedSize' => '500x500px',
        'sql' => "SELECT attribute_id, description, 'attributes' as 'source', ifnull(category, '') as category , create_dt , string_value , `value` as 'size' 
FROM bg_user_attributes WHERE user_id = :user_id and `type` = 'profile_image' and `name`='avatar' and `status`='active'",
        'params' => [':user_id' => $user_id],
        'displaytype' => 'avatar'
    ],
    'cover images' => [
        'id' => 'uploadCoverModal',
        'tag' => 'Cover Image',
        'show' => '',
        'label' => 'uploadCoverModalLabel',
        'title' => 'Upload Cover Image',
        'cardsize' => 'col-md-4',
        'inputId' => 'coverImageInput',
        'hiddenValue' => 'account_cover',
        'recommendedSize' => '1920x300px',
        'sql' => "SELECT attribute_id, description, 'attributes' as 'source', ifnull(category, '') as category , create_dt , string_value , `value` as 'size' 
FROM bg_user_attributes WHERE user_id = :user_id and `type` = 'profile_image' and `name`='account_cover' and `status`='active' ",
        'params' => [':user_id' => $user_id],
        'displaytype' => 'cover'
    ]
];


echo '
<div class="tab-content mt-3" id="imageTabsContent">
';
foreach ($modals as $modal => $modaldata) {
    echo '<!-- ' . $modaldata['tag'] . ' Tab -->
<div class="tab-pane fade ' . $modaldata['show'] . '" id="' . str_replace(' ', '-', $modal) . '" role="tabpanel">
<div class="row">';

    // Fetch previously uploaded images
    $sql = $modaldata['sql'];

    $stmt = $database->prepare($sql);
    $stmt->execute($modaldata['params']);
    $user_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($user_images)) {
        foreach ($user_images as $userimage) {
            $selectededmark = '';
            $selectedbg = '';
            $allowdelete = true;
            if ($userimage['category'] == 'primary') {
                $selectedbg = 'bg-success-subtle';
                $selectededmark = '    <div class="position-absolute top-0 end-0 p-2"><i class="bi bi-check-circle-fill text-success fs-3"></i></div>';
                $selectbutton = '';
                $allowdelete = false;
            }


            echo '
<div class="' . $modaldata['cardsize'] . ' mb-3">
<div class="card position-relative text-center ' . $selectedbg . '" style="width: 100%; height: 250px;">
';
            switch ($modaldata['displaytype']) {
                case 'avatar':
                    echo '
<div class="d-flex justify-content-center align-items-center" style="height: 100%;">
<img src="' . $userimage['description'] . '" class="rounded-circle" alt="' . $modaldata['tag'] . '" style="width: 140px; height: 140px; object-fit: cover;" 
loading="lazy" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="left" title="Uploaded: ' .
                        htmlspecialchars($qik->timeago($userimage['create_dt'])['message'] ?? 'not provided') . '<br>
Size: ' . htmlspecialchars($userimage['size'] ?? 'not provided') . '<br>
Name: ' .  htmlspecialchars($userimage['string_value'] ?? 'not provided') . '">
</div>';
                    break;
                case 'cover':
                    echo '
<img src="' . $userimage['description'] . '" class="card-img-top" alt="' . $modaldata['tag'] . '" style="width: 100%; height: 150px; object-fit: cover;" 
loading="lazy" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="left" title="Uploaded: ' .
                        htmlspecialchars($qik->timeago($userimage['create_dt'])['message'] ?? 'not provided') . '<br>
Size: ' . htmlspecialchars($userimage['size'] ?? 'not provided') . '<br>
Name: ' . htmlspecialchars($userimage['string_value'] ?? 'not provided') . '">';
                    break;
            }



            if ($allowdelete) {
                $assetid = $qik->encodeId($userimage['attribute_id']);

                $selectbutton = '<form method="POST" name="selectasset" id="selectasset-' . $assetid . '"  class="m-0 mx-1">
' . $display->inputcsrf_token() . '
<input type="hidden" name="sid" value="' . $assetid . '">
<input type="hidden" name="formname" value="selectasset">
<button class="btn btn-primary btn-sm"  type="submit" >SELECT</button>
</form>';
            }


            echo $selectededmark . '
<div class="card-body d-flex justify-content-center g-2"> <!-- Added flexbox container here -->

';
            if ($allowdelete) {
                $assetid = $qik->encodeId($userimage['attribute_id']);
                echo '   ' . $selectbutton . '';
                echo '
<form method="POST" name="deleteasset" id="deleteasset-' . $assetid . '" class="m-0 mx-1">
' . $display->inputcsrf_token() . '
<input type="hidden" name="did" value="' . $assetid . '">
<input type="hidden" name="deletetype" value="' . $modaldata['hiddenValue'] . '">
<input type="hidden" name="formname" value="deleteasset">

<button type="submit" class="btn btn-sm btn-danger"> <!-- Added margin to create spacing -->
<i class="bi bi-trash"></i>
</button>
</form>
';
            }
            echo  '
</div> <!-- End of flexbox container -->
</div>
</div>';
        }
    } else {
        echo '<p class="text-muted">No ' . $modal . ' uploaded yet.</p>';
    }


    // Add circle "+" button for upload modal
    ////--------------------------------
    echo '
<!-- Add circle "+" button for upload modal -->
<div class="' . $modaldata['cardsize'] . ' mb-3 d-flex align-items-center justify-content-center">
<div class="card text-center" style="width: 100%; height: 250px;">
<button class="btn d-flex flex-column align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#' . $modaldata['id'] . '" style="height: 100%; width: 100%; border: none; background: none;">
<i class="bi bi-plus-circle-dotted fs-1"></i>
<span class="mt-2 text-primary" data-bs-toggle="tooltip" title="Click to add a new image">Upload New</span>
</button>
</div>
</div>
</div>
</div>
';

    // upload modal 
    ////--------------------------------
    $output_end .= '
<!-- Modal for uploading ' . $modaldata['title'] . ' with drag-and-drop UI -->
<div class="modal modal-lg fade" id="' . $modaldata['id'] . '" tabindex="-1" aria-labelledby="' . $modaldata['label'] . '" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" enctype="multipart/form-data">
' . $display->inputcsrf_token() . '
<input type="hidden" name="type" value="' . $modaldata['hiddenValue'] . '">
<input type="hidden" name="formname" value="fileupload">
<div class="modal-header">
<h5 class="modal-title" id="' . $modaldata['label'] . '">' . $modaldata['title'] . '</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body text-center">
<input type="file" name="files">           
<div class="drop-zone border-1" id="' . $modaldata['inputId'] . '"></div>
<small class="text-muted mt-2 d-block">Recommended size: ' . $modaldata['recommendedSize'] . '</small>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-primary submitformbtn' . $modaldata['hiddenValue'] . '">Upload</button>
</div>
</form>
</div>
</div>
</div>


';
}
echo '
</div>
</div>
</div>
</div>
</div>
</div>';
echo $output_end;
?>
<?

/*
echo '
<script src="/public/js/fileupload-drag-drop/fileUpload.js?' . rand() . '"></script>
';
*/
$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
