<?php

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
// components/profile_verificationid.inc


$modalData['avatar']['show'] ='';
$componentConfig['avatar']['display_panel_activestate'] = false;


$modalData['verification_id'] = [
    'id' => 'uploadVerificationModal',
    'tag' => 'Verification ID',
    'show' => 'show active',
    'label' => 'uploadVerificationModalLabel',
    'title' => 'Upload Verification ID',
    'cardsize' => 'col-md-4',
    'inputId' => 'verificationImageInput',
    'hiddentype'=>'verification_id',
    'hiddenValue' => 'verification_id',
    'recommendedSize' => '800x1200px',
    'sql' => "SELECT attribute_id, description, 'attributes' as 'source', 
              ifnull(category, '') as category, create_dt, string_value, 
              `value` as 'size' 
              FROM bg_user_attributes 
              WHERE user_id = :user_id 
              AND `type` = 'profile_image' 
              AND `name`='verification_id' 
              AND `status` in ('pending_review', 'active')",
    'params' => [':user_id' => $user_id],
    'displaytype' => 'verification',
    'content_template' => '
        <div class="card" style="min-height: 600px;">
           <div class="position-relative p-2">
            <img src="{description}" class="card-img-top" alt="{tag}" 
            style="width: 100%; height: 500px; object-fit: contain;"    loading="lazy" 
                 data-bs-toggle="tooltip" 
                 data-bs-html="true" 
                 data-bs-placement="left" 
                 title="Uploaded: {uploaded_time}<br>Size: {size}<br>Name: {filename}">
            <div class="position-absolute top-0 start-0 p-2">
                <span class="badge bg-{verification_status_color}">{verification_status}</span>
            </div>
                </div>
        </div>',
    'extra_info' => '
        <div class="alert alert-info mb-3">
            <h6 class="alert-heading">Verification ID Requirements:</h6>
            <ul class="mb-0">
                <li>Government-issued ID (Driver\'s License, Passport, etc.)</li>
                <li>Must be current/valid</li>
                <li>All corners must be visible</li>
                <li>Information must be clearly readable</li>
            </ul>
        </div>'
];

$componentConfig['verification_id'] = [
    'title' => 'Verification ID',
    'display_panel' => true,
    'display_panel_activestate' =>true,
    'allow_delete' => true,
    'allow_primary' => false,  // No primary selection for verification IDs
    'upload_limits' => [
        'max_files' => 2,  // Allow front and back of ID
        'max_size' => 10,  // Larger size limit for high-quality scans
        'mime_types' => [
            'image/jpeg', 
            'image/png', 
            'image/webp',
            'application/pdf'  // Also allow PDF uploads
        ]
    ],
    'verification_statuses' => [
        'pending' => [
            'label' => 'Pending Review',
            'color' => 'warning'
        ],
        'verified' => [
            'label' => 'Verified',
            'color' => 'success'
        ],
        'rejected' => [
            'label' => 'Rejected',
            'color' => 'danger'
        ]
    ],
    'security' => [
        'restricted_access' => true,  // Only allow admin access to full-size images
        'encryption_required' => true, // Encrypt stored verification images
        'audit_log_required' => true  // Log all access to verification images
    ]
];
/*
// Add logic to append verification status to the content
if ($modalData['verification_id']['displaytype'] === 'verification') {
    $content_template = $modalData['verification']['content_template'];
    // Get verification status from the database or default to pending
    $verification_status = $image['verification_status'] ?? 'pending';
    $status_info = $componentConfig['verification_id']['verification_statuses'][$verification_status];
    
    $content_template = str_replace(
        ['{verification_status}', '{verification_status_color}'],
        [$status_info['label'], $status_info['color']],
        $content_template
    );
    
    $modalData['verification_id']['content_template'] = $content_template;
}
*/
#$modalData['verification_id']['content_template'] = $content_template;





/*
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml', 'image/bmp', 'image/tiff', 'doc/pdf'];
$allowdelete=['verficationid'=>'Avatar'];
$uploaderparams=array('verficationid' =>array(
    'limit' => 4, // Allow multiple file uploads (e.g., 4 files)
    'maxSize' => 3,
    'createDir' => true,
    'title' => 'md5hashunique',
));

$allowPrimaryswitch=['verficationid'=>false];




////--------------------------------
/// DEFINE MODAL
$modals = [
    'verficationid' => [
        'id' => 'uploadVerificationModal',
        'tag' => 'Verification ID',
        'show' => '',
        'label' => 'uploadVerificationModalLabel',
        'title' => 'Upload Verification ID',
        'cardsize' => 'col-md-4',
        'inputId' => 'verficationidImageInput',
        'hiddenValue' => 'account_verificationid',
        'recommendedSize' => '800x1200px',
        'sql' => "SELECT attribute_id, description, 'attributes' as 'source', ifnull(category, '') as category , create_dt , string_value , `value` as 'size' 
FROM bg_user_attributes WHERE user_id = :user_id and `type` = 'profile_image' and `name`='verficationid' and `status`='active' ",
        'params' => [':user_id' => $user_id],
        'displaytype' => 'verficationid'
    ]
];





$content_holder=['verficationid'=>  '
<img src="' . $userimage['description'] . '" class="card-img-top" alt="' . $modaldata['tag'] . '" style="width: 100%; height: 150px; object-fit: cover;" 
loading="lazy" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="left" title="Uploaded: ' .
                        htmlspecialchars($qik->timeago($userimage['create_dt'])['message'] ?? 'not provided') . '<br>
Size: ' . htmlspecialchars($userimage['size'] ?? 'not provided') . '<br>
Name: ' . htmlspecialchars($userimage['string_value'] ?? 'not provided') . '">';
];


*/