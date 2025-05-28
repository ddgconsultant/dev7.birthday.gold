<?php
$addClasses[] = 'fileuploader';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$modalData = [];
$componentConfig = [];
$output_end = '';
$errormessage = '';
$transferpagedata = [];
$newfileuploadedid = false;
$user_id = $current_user_data['user_id'];
$moduleimages=$_SERVER['DOCUMENT_ROOT'] . '/myaccount/module_images';

// Load component configurations
include($moduleimages . '/images_avatars.inc');
include($moduleimages . '/images_coverbanners.inc');

$allowverificationid=$session->get('enable_verificationid_upload', '');
if (!empty($allowverificationid)) include($moduleimages . '/images_verificationid.inc');


#-------------------------------------------------------------------------------
# HANDLE POSTED FORM
#-------------------------------------------------------------------------------
$uploadhandlerurl=$moduleimages . '/profile_image_uploadhandler.inc';
if ($app->formposted()) {
    include($moduleimages . '/profile_image_uploadhandler.inc');
}


#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------
$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);
$bodycontentclass = 'profile-image-page';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');

// Include required styles
#include($dir['core_components'] . '/profile_images_styles.inc');

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


// Main content
echo '<div class="container main-content mt-lg-5 pt-lg-5">
    <div class="row">
        <div class="col-md-12">
            ' . $display->formaterrormessage($transferpagedata['message']) . '
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Your Images</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="imageTabs" role="tablist">';

// First, determine which tab should be active
$activeTab = null;
foreach ($componentConfig as $type => $config) {
    if ($config['display_panel'] && $config['display_panel_activestate']) {
        $activeTab = $type;
        break;
    }
}

// If no tab is explicitly set as active, default to first visible tab
if (!$activeTab) {
    foreach ($componentConfig as $type => $config) {
        if ($config['display_panel']) {
            $activeTab = $type;
            break;
        }
    }
}


// Build tabs
foreach ($componentConfig as $type => $config) {
    
    if ($config['display_panel']) {
        $isActive = ($type === $activeTab) ? 'active' : '';
        echo '<li class="nav-item">
                <a class="nav-link ' . $isActive  . '" 
                   id="' . $type . '-tab" 
                   data-bs-toggle="tab" 
                   href="#' . $type . '" 
                   role="tab">' . $config['title'] . '</a>
              </li>';
    }
}

echo '</ul><div class="tab-content mt-3" id="imageTabsContent">';

// Process each component
foreach ($modalData as $type => $data) {
 // Skip if component is not configured
 if (!isset($componentConfig[$type])) {
    continue;
}

// Skip if panel should not be displayed
if (!$componentConfig[$type]['display_panel']) {
    continue;
}

// Skip if required parameters are missing
if (empty($data['params'])) {
    continue;
}

// Determine if this pane should be active
$isActive = ($type === $activeTab) ? 'show active' : '';

           echo '<div class="tab-pane fade ' . $isActive . '" 
                   id="' . $type . '" role="tabpanel">
                <div class="row">';

        // Get images
        $stmt = $database->prepare($data['sql']);
        $stmt->execute($data['params']??[]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Display images
        if (!empty($images)) {
            foreach ($images as $image) {
                $selectededmark = '';
                $selectedbg = '';
                $allowdelete = $componentConfig[$type]['allow_delete'];
                $data['hiddenValue']= $data['hiddentype']; 
                if ($image['category'] == 'primary') {
                    $selectedbg = 'bg-success-subtle';
                    $selectededmark = '<div class="position-absolute top-0 end-0 p-2">
                        <i class="bi bi-check-circle-fill text-success fs-3"></i>
                    </div>';
                    $allowdelete = false;
                }

                // Build image card
                echo '<div class="' . $data['cardsize'] . ' mb-3">
                    <div class="card position-relative text-center ' . $selectedbg . '"  style="width: 100%; height: 250px;">';
                
                // Replace placeholders in content template
                $content = str_replace(
                    [
                        '{description}', 
                        '{tag}', 
                        '{uploaded_time}',  // Changed from {uploaded}
                        '{size}', 
                        '{filename}'
                    ],
                    [
                        $image['description'],
                        $data['tag'],
                        htmlspecialchars($qik->timeago($image['create_dt'])['message'] ?? 'not provided', ENT_QUOTES),  // Just get the message
                        htmlspecialchars($image['size'] ?? 'not provided', ENT_QUOTES),
                        htmlspecialchars($image['string_value'] ?? 'not provided', ENT_QUOTES)
                    ],
                    $data['content_template']
                );
                
                echo $content;
                echo $selectededmark;
                
                // Add action buttons
                echo '<div class="card-body d-flex justify-content-center g-2">';
                
                if ($allowdelete) {
                    $assetid = $qik->encodeId($image['attribute_id']);
                    
                    // Select button
                    if ($componentConfig[$type]['allow_primary']) {
                        echo '<form method="POST" name="selectasset" id="selectasset-' . $assetid . '"  class="m-0 mx-1">
                            ' . $display->inputcsrf_token() . '
                             <input type="hidden" name="type" value="' . $data['hiddenValue'] . '">
                            <input type="hidden" name="sid" value="' . $assetid . '">
                            <input type="hidden" name="formname" value="selectasset">
                            <button class="btn btn-primary btn-sm" type="submit">SELECT</button>
                        </form>';
                    }
                    
                    // Delete button
                    echo '<form method="POST" name="deleteasset" id="deleteasset-' . $assetid . '" class="m-0 mx-1">
                        ' . $display->inputcsrf_token() . '
                         <input type="hidden" name="type" value="' . $data['hiddenValue'] . '">
                        <input type="hidden" name="did" value="' . $assetid . '">
                        <input type="hidden" name="deletetype" value="' . $data['hiddenValue'] . '">
                        <input type="hidden" name="formname" value="deleteasset">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>';
                }
                
                echo '</div></div></div>';
            }
        } else {
            echo '<p class="text-muted">No ' . strtolower($data['tag']) . 's uploaded yet.</p>';
        }

        // Add upload button
        echo '<div class="' . $data['cardsize'] . ' mb-3 d-flex align-items-center justify-content-center">
            <div class="card text-center" style="width: 100%; height: 250px;">
                <button class="btn d-flex flex-column align-items-center justify-content-center" 
                        data-bs-toggle="modal" 
                        data-bs-target="#' . $data['id'] . '" 
                        style="height: 100%; width: 100%; border: none; background: none;">
                    <i class="bi bi-plus-circle-dotted fs-1"></i>
                    <span class="mt-2 text-primary" data-bs-toggle="tooltip" 
                          title="Click to add a new image">Upload New</span>
                </button>
            </div>
        </div>';

        echo '</div></div>';

        // Build modal
        $output_end .= '<div class="modal modal-lg fade" id="' . $data['id'] . '" 
                            tabindex="-1" aria-labelledby="' . $data['label'] . '" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" enctype="multipart/form-data">
                        ' . $display->inputcsrf_token() . '
                        <input type="hidden" name="type" value="' . $data['hiddenValue'] . '">
                        <input type="hidden" name="formname" value="fileupload">
                        <div class="modal-header">
                            <h5 class="modal-title" id="' . $data['label'] . '">' . $data['title'] . '</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <input type="file" name="files">           
                            <div class="drop-zone border-1" id="' . $data['inputId'] . '"></div>
                            <small class="text-muted mt-2 d-block">
                                Recommended size: ' . $data['recommendedSize'] . '
                            </small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary submitformbtn' . $data['hiddenValue'] . '">
                                Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';
}

echo '</div></div></div></div></div>';
$override_bstooltips=true;
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    const tooltipTriggerList = document.querySelectorAll(\'[data-bs-toggle="tooltip"]\');
    tooltipTriggerList.forEach(el => {
        const title = el.getAttribute("data-bs-title").replace(/&#10;/g, "<br>");
        new bootstrap.Tooltip(el, {
            html: true,
            title: title
        });
    });
});
</script>';

echo $output_end;
echo '</div>';
$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>