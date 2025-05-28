<?php

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Include any necessary headers, session classes, or configuration files



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------




#-------------------------------------------------------------------------------
# HANDLE FORM ACTIONS
#-------------------------------------------------------------------------------
// Processing form submission
if ($app->formposted()) {
    // Grab form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $social_profile = $_POST['social_profile'];
    $photo_id = $_FILES['photo_id'];

    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($social_profile) || empty($photo_id)) {
        echo '<div class="alert alert-danger">All fields are required. Please fill in all information.</div>';
    } else {
        // File upload logic for the photo ID
        $target_dir = "/uploads/photo_id/";
        $target_file = $target_dir . basename($photo_id["name"]);
        if (move_uploaded_file($photo_id["tmp_name"], $target_file)) {
            // Save form data into the database
            $database->query('INSERT INTO bg_verified_accounts (name, email, phone, address, social_profile, photo_id_path) VALUES (?, ?, ?, ?, ?, ?)', 
                [$name, $email, $phone, $address, $social_profile, $target_file]);

            echo '<div class="alert alert-success">Your application has been submitted. We will review your request.</div>';
        } else {
            echo '<div class="alert alert-danger">Error uploading your photo ID. Please try again.</div>';
        }
    }
}




#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');



include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');

$additionalstyles.='
<style>
    h3 {
       margin-top: 3.5rem !important;
         margin-bottom: .5rem !important;
    }
        </style>
        
     
<style>
    .hanging-indent {
        display: flex;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    .hanging-indent i {
        flex-shrink: 0;
        margin-right: 10px; /* Space between icon and title */
        font-size: 1.5rem; /* Adjust icon size if needed */
        margin-top: 2px; /* Fine-tune the icon alignment to title */
        vertical-align: top; /* Ensures icon stays aligned */
    }
    .hanging-indent .title-and-button {
        display: flex;
        align-items: center; /* Align button and title */
    }
    .hanging-indent .title-and-button button {
        margin-left: 10px; /* Space between title and button */
    }


       ul.custom-spacing li {
        margin-bottom: 1rem; /* Increase spacing between list items */
    }
</style>
';

?>
<div class="container main-content mt-0 pt-0">
    <div class="row align-items-center">
        <!-- Column for the heading -->
        <div class="col-8">
            <h2>Verified Accounts on Birthday.Gold</h2>
        </div>
        <!-- Column for the button -->
        <div class="col-4 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#verificationModal">
                Apply for Verification
            </button>
        </div>
    </div>


<div class="content-panel mt-3 p-4">
    <p>At Birthday.Gold, we aim to create a trusted and engaging platform where users can enjoy exclusive rewards from their favorite brands. A verified account helps ensure that your profile is authentic, enhancing trust and credibility within our community. These accounts have a special badge that indicates their verified status. This helps brands and other users know that you are who you claim to be, giving you access to enhanced opportunities and offers.</p>

    <h3>Benefits of Becoming a Verified Account</h3>
    <ul class="custom-spacing">
        <li><strong>Access to Exclusive Birthday Rewards:</strong> Verified users are given priority access to exclusive birthday rewards, promotions, and events from our partner brands.</li>
        <li><strong>Enhanced Trust and Visibility:</strong> A verified badge adds credibility to your profile, making it easier for brands to engage with you and offer more personalized rewards.</li>
        <li><strong>Increased Profile Exposure:</strong> Verified accounts may appear more prominently on Birthday.Gold’s platform, giving you more exposure to brands looking for verified users to provide special birthday offers.</li>
        <li><strong>100 Enrollment Credits:</strong> You'll receive 100 Enrollment credits to enroll in even more rewards.</li>
        </ul>

    <h3>Who is Eligible to Become Verified?</h3>
    <p>To be eligible for a verified badge, you must meet the following criteria:</p>
 <div class="ps-4">
 <ul class="list-unstyled">
       

 <!-- Complete Profile Check -->
<?php
$profileComplete = ($profilecompletion['required_percentage'] == 100);
$icon = $profileComplete ? 'check-circle-fill text-success' : 'check-circle text-muted';
$button = $profileComplete ? '' : '<a href="/myaccount/profile" class="btn btn-sm btn-danger py-0 mt-1 ms-3">Complete this task</a>';
$message = $profileComplete 
    ? 'Your profile is complete with your full name, phone number, and email address. Thank you!'
    : 'Please complete your profile by adding your full name, phone number, and a valid email address.';

echo '<li class="hanging-indent">
    <i class="bi bi-' . $icon . '"></i>
    <div>
        <div class="title-and-button">
            <strong class="fs-4">Profile Details:</strong>
            ' . $button . '
        </div>
        <div>' . $message . '</div>
    </div>
</li>';
?>


<!-- Profile Picture Check -->
<?php
$hasAvatar = ($account->getUserAttribute($current_user_data['user_id'], 'avatar') != null);
$icon = $hasAvatar ? 'check-circle-fill text-success' : 'check-circle text-muted';
$button = $hasAvatar ? '' : '<a href="/myaccount/profile-pic" class="btn btn-sm btn-danger py-0 mt-1 ms-3">Complete this task</a>';
$message = $hasAvatar 
    ? 'You have successfully uploaded a profile picture. Looking good!'
    : 'Please upload a profile picture to complete your verification.';

echo '<li class="hanging-indent">
    <i class="bi bi-' . $icon . '"></i>
    <div>
        <div class="title-and-button">
            <strong class="fs-4">Profile Picture:</strong>
            ' . $button . '
        </div>
        <div>' . $message . '</div>
    </div>
</li>';
?>

<!-- Authenticity Check -->
<?php
$hasVerification = ($account->getUserAttribute($current_user_data['user_id'], 'verification_id') != null);
$icon = $hasVerification ? 'check-circle-fill text-success' : 'check-circle text-muted';
$button = $hasVerification ? '' : '<a href="/myaccount/profile-verificationid" class="btn btn-sm btn-danger py-0 mt-1 ms-3">Complete this task</a>';
$message = $hasVerification 
    ? 'Your identity has been successfully verified with your government-issued ID. Thank you!'
    : 'You need to provide valid identification to verify your identity, such as a government-issued ID (passport, driver\'s license, etc.).';

echo '<li class="hanging-indent">
    <i class="bi bi-' . $icon . '"></i>
    <div>
        <div class="title-and-button">
            <strong class="fs-4">Authenticity:</strong>
            ' . $button . '
        </div>
        <div>' . $message . '</div>
    </div>
</li>';
?>

<!-- Activity Check -->
<?php
#$postCount = $social->posts();
$postCount=8;
$hasEnoughPosts = ($postCount > 5);
$icon = $hasEnoughPosts ? 'check-circle-fill text-success' : 'check-circle text-muted';
$button = $hasEnoughPosts ? '' : '<a href="/social" class="btn btn-sm btn-danger py-0 mt-1 ms-3">Complete this task</a>';
$message = $hasEnoughPosts 
    ? "You're an active member of our community with {$postCount} engaging posts. Keep it up!"
    : "You need to be more active in our community. Currently you have ".$qik->plural2($postCount, 'post')." - make at least 5 engaging posts to complete this requirement.";

echo '<li class="hanging-indent">
    <i class="bi bi-' . $icon . '"></i>
    <div>
        <div class="title-and-button">
            <strong class="fs-4">Activity:</strong>
            ' . $button . '
        </div>
        <div>' . $message . '</div>
    </div>
</li>';
?>


    </ul>
</div>

    <h3>How to Apply for Verification</h3>
    <p>If you believe you qualify for verification, you can apply through the form by clicking the "Apply for Verification" button above. The application process will require you to submit personal information such as your full name, email address, and a government-issued photo ID for verification purposes.</p>

    <h3>What Happens After Verification?</h3>
    <p>After your account is verified, a special badge will be displayed on your profile, indicating that you are an authentic user. This badge signals to brands and other users that you have been vetted by Birthday.Gold and are eligible for premium offers and rewards.</p>

    <h3>Maintaining Your Verified Status</h3>
    <p>To maintain your verified status, you should continue to follow our platform's terms of service and guidelines. Birthday.Gold reserves the right to remove verification from accounts that engage in suspicious activity or violate our policies.</p>

    <h3>FAQs</h3>
    <p><strong>How long does the verification process take?</strong> We typically process verification applications within a few business days. You will receive an email notification once your application has been reviewed.</p>
    <p><strong>Can I lose my verification status?</strong> Yes, accounts that engage in suspicious behavior or violate Birthday.Gold’s guidelines may have their verification revoked.</p>
    <p><strong>What information is required for verification?</strong> We require basic identification such as your full name, email address, and a government-issued photo ID. This helps us confirm your identity and protect the integrity of our platform.</p>

<?PHP
    echo '
 <!-- Modal Form -->
<div class="modal modal-lg fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="verificationModalLabel">Apply for Verification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-5">
                <form action="/path-to-verification-form-handler.php" method="POST" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <label for="name" class="col-sm-3 col-form-label text-end">Full Name:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="name" name="name"  value="'.$current_user_data['first_name'].' '.$current_user_data['last_name'].'"  required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="email" class="col-sm-3 col-form-label text-end">Email Address:</label>
                        <div class="col-sm-9">
                            <input type="email" class="form-control" id="email" name="email" value="'.$current_user_data['email'].'" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="phone" class="col-sm-3 col-form-label text-end">Phone Number:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="phone" name="phone"  value="'.$current_user_data['phone_number'].'" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="address" class="col-sm-3 col-form-label text-end">Home Address:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="address" name="address"  value="'.$current_user_data['mailing_address'].'"  required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="social_profile" class="col-sm-3 col-form-label text-end">Social Media Profile:</label>
                        <div class="col-sm-9">
                            <input type="url" class="form-control" id="social_profile" name="social_profile" placeholder="https://your-social-media.com/profile" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="photo_id" class="col-sm-3 col-form-label text-end">Upload Photo ID:</label>
                        <div class="col-sm-9">
                            <input type="file" class="form-control" id="photo_id" name="photo_id" accept="image/*" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-9 offset-sm-3">
                            <button type="submit" class="btn btn-primary">Submit Application</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
';
?>

</div>



</div>
</div>
</div>
</div>

<?php

$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
