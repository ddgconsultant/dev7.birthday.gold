<?php



#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$modalData['avatar'] = [
    'id' => 'uploadAvatarModal',
    'tag' => 'Avatar',
    'show' => 'show active',
    'label' => 'uploadAvatarModalLabel',
    'title' => 'Upload Avatar',
    'cardsize' => 'col-xs-6 col-sm-6 col-md-4 col-lg-3 col-xl-2',
    'inputId' => 'avatarImageInput',
    'hiddentype'=>'avatar',
    'hiddenValue' => 'avatar',
    'recommendedSize' => '500x500px',
    'sql' => "SELECT attribute_id, description, 'attributes' as 'source', 
              ifnull(category, '') as category, create_dt, string_value, 
              `value` as 'size' 
              FROM bg_user_attributes 
              WHERE user_id = :user_id 
              AND `type` = 'profile_image' 
              AND `name`='avatar' 
              AND `status`='active'",
    'params' => [':user_id' => $user_id],
    'displaytype' => 'avatar',
    'content_template' => '
        <div class="d-flex justify-content-center align-items-center" style="height: 100%;">
            <img src="{description}" class="rounded-circle" alt="{tag}" 
                 style="width: 140px; height: 140px; object-fit: cover;" 
                 loading="lazy" 
                 data-bs-toggle="tooltip" 
                 data-bs-html="true" 
                 data-bs-placement="left" 
                data-bs-title="Uploaded: {uploaded_time}<br>Size: {size}<br>Name: {filename}">
    </div>'
];

$componentConfig['avatar'] = [
    'title' => 'Avatars',
    'display_panel' => true,  // or true if you want it visible
    'display_panel_activestate' => false,
    'allow_delete' => true,
    'allow_primary' => true,
    'upload_limits' => [
        'max_files' => 3,
        'max_size' => 3,
        'mime_types' => ['image/jpeg', 'image/png', 'image/webp']
    ]
];


/*
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml', 'image/bmp', 'image/tiff'];
$allowdelete=['avatar'=>'Avatar'];
$uploaderparams=array('avatar' =>array(
    'limit' => 3, // Allow multiple file uploads (e.g., 5 files)
    'maxSize' => 3,
    'createDir' => true,
    'title' => 'md5hashunique',
));

$allowPrimaryswitch=['avatar'=>true];



////--------------------------------
/// DEFINE MODAL
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
];


$content_holder=['avatar'=>  '
<div class="d-flex justify-content-center align-items-center" style="height: 100%;">
<img src="' . $userimage['description'] . '" class="rounded-circle" alt="' . $modaldata['tag'] . '" style="width: 140px; height: 140px; object-fit: cover;" 
loading="lazy" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="left" title="Uploaded: ' .
                        htmlspecialchars($qik->timeago($userimage['create_dt'])['message'] ?? 'not provided') . '<br>
Size: ' . htmlspecialchars($userimage['size'] ?? 'not provided') . '<br>
Name: ' .  htmlspecialchars($userimage['string_value'] ?? 'not provided') . '">
</div>';
];

*/