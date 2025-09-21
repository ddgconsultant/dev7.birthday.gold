<?php

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
// components/profile_coverimages.inc
$modalData['cover'] = [
    'id' => 'uploadCoverModal',
    'tag' => 'Cover Image',
    'show' => '',
    'label' => 'uploadCoverModalLabel',
    'title' => 'Upload Cover Image',
    'cardsize' => 'col-md-4',
    'inputId' => 'coverImageInput',
    'hiddentype'=>'cover',
    'hiddenValue' => 'account_cover',
    'recommendedSize' => '1920x300px',
    'sql' => "SELECT attribute_id, description, 'attributes' as 'source', 
              ifnull(category, '') as category, create_dt, string_value, 
              `value` as 'size' 
              FROM bg_user_attributes 
              WHERE user_id = :user_id 
              AND `type` = 'profile_image' 
              AND `name`='account_cover' 
              AND `status`='active'",
    'params' => [':user_id' => $user_id],
    'displaytype' => 'cover',
    'content_template' => '
        <img src="{description}" class="card-img-top" alt="{tag}" 
             style="width: 100%; height: 150px; object-fit: cover;" 
             loading="lazy" 
             data-bs-toggle="tooltip" 
             data-bs-html="true" 
             data-bs-placement="left" 
              title="' . htmlspecialchars('Uploaded: {uploaded_time}<br>Size: {size}<br>Name: {filename}', ENT_QUOTES) . '">'
];

$componentConfig['cover'] = [
    'title' => 'Cover Images',    
    'display_panel' => true,
    'display_panel_activestate' =>false,
    'allow_delete' => true,
    'allow_primary' => true,
    'upload_limits' => [
        'max_files' => 1,  // Only one cover image at a time
        'max_size' => 5,   // Larger size limit for cover images
        'mime_types' => [
            'image/jpeg', 
            'image/png', 
            'image/webp'
        ]
    ]
];

/*
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml', 'image/bmp', 'image/tiff'];
$allowdelete=['cover'=>'Cover'];
$uploaderparams=array('cover' =>array(
    'limit' => 1, // Allow multiple file uploads (e.g., 5 files)
    'maxSize' => 3,
    'createDir' => true,
    'title' => 'md5hashunique',
));

$allowPrimaryswitch=['cover'=>true];




////--------------------------------
/// DEFINE MODAL
$modals = [
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



$bdarray = explode('-', $current_user_data['birthdate']);
$coverbanner = '/public/images/site_covers/cbanner_' . $bdarray[1] . '.jpg';



$content_holder=['cover'=>  '
<img src="' . $userimage['description'] . '" class="card-img-top" alt="' . $modaldata['tag'] . '" style="width: 100%; height: 150px; object-fit: cover;" 
loading="lazy" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="left" title="Uploaded: ' .
                        htmlspecialchars($qik->timeago($userimage['create_dt'])['message'] ?? 'not provided') . '<br>
Size: ' . htmlspecialchars($userimage['size'] ?? 'not provided') . '<br>
Name: ' . htmlspecialchars($userimage['string_value'] ?? 'not provided') . '">';
];


*/