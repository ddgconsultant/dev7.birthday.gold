<?php
use phpformbuilder\Form;
use phpformbuilder\Validator\Validator;
use fileuploader\server\FileUploader;
#ini_set('display_errors', 1);
/* =============================================
    start session and include form class
============================================= */
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include_once rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . '/core/applications/phpformbuilder/Form.php';

// include the fileuploader

include_once rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . '/core/applications/phpformbuilder/plugins/fileuploader/server/class.fileuploader.php';
$has_signature_error=false;
$data_uri=[];

if (empty($current_user_data['user_id'])) {

   # breakpoint($_GET);
   $errormessage = '<div class="alert alert-danger">You must be logged in before accessing.</div>';
   $transferpage['url']='/login';
   $transferpage['message']=$errormessage;
   $system->endpostpage($transferpage);
exit;
}

$current_user_data=$account->getuserdata($current_user_data['user_id'], 'user_id');






/* =============================================
    validation if posted
============================================= */
$v_formname='bd-hr_main1';
if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken($v_formname)) {
    // create validator & auto-validate required fields
    $validator = Form::validate($v_formname);
    // additional validation
    $validator->email()->validate('e_contact1_email');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors'][$v_formname] = $validator->getAllErrors();
    } else {
        $uploaded_files = [];
        if (isset($_POST['i9_passport_full']) && !empty($_POST['i9_passport_full'])) {
            $posted_file = FileUploader::getPostedFiles($_POST['i9_passport_full']);
            $uploaded_files['i9_passport_full'] = [
                'upload_dir' => 'hr/file-uploads/',
                'filename' => $posted_file[0]['file']
            ];
        }
        if (isset($_POST['i9_listb_upload']) && !empty($_POST['i9_listb_upload'])) {
            $posted_file = FileUploader::getPostedFiles($_POST['i9_listb_upload']);
            $uploaded_files['i9_listb_upload'] = [
                'upload_dir' => 'hr/file-uploads/',
                'filename' => $posted_file[0]['file']
            ];
        }
        if (isset($_POST['i9_listc_upload']) && !empty($_POST['i9_listc_upload'])) {
            $posted_file = FileUploader::getPostedFiles($_POST['i9_listc_upload']);
            $uploaded_files['i9_listc_upload'] = [
                'upload_dir' => 'hr/file-uploads/',
                'filename' => $posted_file[0]['file']
            ];
        }



        if (isset($_POST['user-signature'])) {
            $data_uri = $_POST['user-signature'];
            $exploded_data_uri = explode(',', $data_uri);
        
            // Check if there's at least two elements after exploding the data URI
            if (count($exploded_data_uri) >= 2) {
                $encoded_image = $exploded_data_uri[1];
        
                // Check if encoded_image is not null before decoding
                if ($encoded_image !== null) {
                    $decoded_image = base64_decode($encoded_image);
        
                    // Ensure $formuserid is defined; you need to set $formuserid appropriately
                    if (isset($formuserid)) {
                        file_put_contents($v_formname . '__' . $formuserid . '__signature.png', $decoded_image);
                    } else {
                        // Handle the case where $formuserid is not set
                        $signature_message= "Error: formuserid is undefined.";
                    }
                } else {
                    $signature_message= "Error: Encoded image is null.";
                }
            } else {
                $signature_message= "Error: Invalid data URI format.";
            }
        } else {
            $signature_message= "Error: User signature data not posted.";
        }
      #  breakpoint(  $data_uri);



        // clear the form
        Form::clear($v_formname);

$listofvariables=array_merge($_POST, $uploaded_files);
$formnum=0;
$formuserid=$current_user_data['user_id'];
$form_key='fullformdata';
$form_val=json_encode($listofvariables);
$sql = "INSERT INTO bg_user_attributes (user_id, `type`, `name`, `description`, `status`, `rank`, create_dt, modify_dt)
VALUES (:user_id, 'formdata_".$v_formname."', :formkey, :formvalue, 'active', :formnum, NOW(), NOW())";
#$stmt = $database->query($sql, [':user_id' => $formuserid, ':formkey' => $form_key, ':formvalue' => $form_val, ':formnum' => $formnum]);
$stmt = $database->prepare($sql);
$stmt->execute([':user_id' => $formuserid, ':formkey' => $form_key, ':formvalue' => $form_val, ':formnum' => $formnum]);



foreach ($listofvariables as $form_key=>$form_val) {
    $formnum++;
        $sql = "INSERT INTO bg_user_attributes (user_id, `type`, `name`, `description`, `status`, `rank`, create_dt, modify_dt)
        VALUES (:user_id, 'formdata_".$v_formname."', :formkey, :formvalue, 'active', :formnum, NOW(), NOW())";
   #     $stmt = $database->query($sql, [':user_id' => $formuserid, ':formkey' => $form_key, ':formvalue' => $form_val, ':formnum' => $formnum]);
        $stmt = $database->prepare($sql);
$stmt->execute([':user_id' => $formuserid, ':formkey' => $form_key, ':formvalue' => $form_val, ':formnum' => $formnum]);
}


$sql = "update bg_user_attributes set `status`='completed', modify_dt=now() WHERE user_id = :user_id AND `name` = 'myaccount_redirect_hr_onboarding' AND `status`='active' limit 1";
$stmt = $database->prepare($sql);
$stmt->execute([':user_id' => $formuserid]);

        # echo 'done'; breakpoint($_REQUEST); exit;
        header('location: /myaccount/'); exit;
    }
}  else {
    $formuserid=$current_user_data['user_id'];
    $sql = "SELECT `description` FROM bg_user_attributes WHERE user_id = :user_id AND `type` = :type AND `name` = :name LIMIT 1";
#$stmt = $database->query($sql, [':user_id' => $formuserid, ':type' => 'formdata_'.$v_formname, ':name' => 'fullformdata']);
$stmt = $database->prepare($sql);
$stmt->execute([':user_id' => $formuserid, ':type' => 'formdata_'.$v_formname, ':name' => 'fullformdata']);

$result  = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if results were fetched
if ($result) {
    // Ensure the session variable for this form is an array
  // Decode the JSON string from the description into an associative array
  $formDataArray = json_decode($result['description'], true);

  // Check if the decoding was successful
  if (json_last_error() === JSON_ERROR_NONE) {
      // Ensure the session variable for this form is an array
      if (!isset($_SESSION[$v_formname]) || !is_array($_SESSION[$v_formname])) {
          $_SESSION[$v_formname] = [];
      }

      // Loop through each key-value pair in the decoded array and assign to $_SESSION
      foreach ($formDataArray as $key => $value) {
          $_SESSION[$v_formname][$key] = $value;
      }

}
}
}

 
/* ==================================================
    The Form
 ================================================== */

$form = new Form('bd-hr_main1', 'horizontal', 'novalidate, data-fv-no-icon=true', 'bs5');
 $form->setMode('production');
$form->addHtml('<section>');
$form->addHeading('Your Employment Forms', 'h1', '');
$form->addHtml('<p>Our HR system dynamically builds all the forms necessary for you to complete using this information.  This allows us to perform your onboarding experience in a faster, more effiencent manner.
Espcially since you will not have to fill out multiple forms with the same information.</p>
<p>Your completed generated forms will be available for you online after they have been verified by the Human Resources team.</p>');
$form->addHtml('</section><section>');
$form->addHeading('Your Personal Contact Information', 'h2', '');
$form->addInput('text', 'fullname', ($current_user_data['full_name']??''), 'Your Full Name', 'readonly');
$form->addInput('text', 'username', ($current_user_data['username']??''), 'Your User Name', 'readonly');
$form->addInput('text', 'name_pref', ($current_user_data['first_name']??''), 'Your Preferred Name', '');
$form->addHtml('<hr class="mt-2 mb-5">');
$form->addHeading('Legal Details', 'h5', '');
$form->addInput('text', 'name_first', ($current_user_data['first_name']??''), 'Legal First Name',  'required=required');
$form->addInput('text', 'name_middle', ($current_user_data['middle_name']??''), 'Middle',  'required=required');
$form->addInput('text', 'name_last', ($current_user_data['last_name']??''),'Last',  'required=required');
$form->addInput('password', 'ssn', '', 'SSN', 'placeholder=###-##-####,required=required');
$form->addHtml('<hr class="mt-2 mb-5">');
$form->addHeading('Mailing Address', 'h5', '');
$form->addInput('text', 'mail_address', '', 'Address',  'required=required');
$form->addInput('text', 'mail_city', '', 'City',  'required=required');
$form->addOption('mail_state', 'alabama', 'Alabama', '', '');
$form->addOption('mail_state', 'alaska', 'Alaska', '', '');
$form->addOption('mail_state', 'arizona', 'Arizona', '', '');
$form->addOption('mail_state', 'arkansas', 'Arkansas', '', '');
$form->addOption('mail_state', 'california', 'California', '', '');
$form->addOption('mail_state', 'colorado', 'Colorado', '', '');
$form->addOption('mail_state', 'connecticut', 'Connecticut', '', '');
$form->addOption('mail_state', 'delaware', 'Delaware', '', '');
$form->addOption('mail_state', 'florida', 'Florida', '', '');
$form->addOption('mail_state', 'georgia', 'Georgia', '', '');
$form->addOption('mail_state', 'hawaii', 'Hawaii', '', '');
$form->addOption('mail_state', 'idaho', 'Idaho', '', '');
$form->addOption('mail_state', 'illinois', 'Illinois', '', '');
$form->addOption('mail_state', 'indiana', 'Indiana', '', '');
$form->addOption('mail_state', 'iowa', 'Iowa', '', '');
$form->addOption('mail_state', 'kansas', 'Kansas', '', '');
$form->addOption('mail_state', 'kentucky', 'Kentucky', '', '');
$form->addOption('mail_state', 'louisiana', 'Louisiana', '', '');
$form->addOption('mail_state', 'maine', 'Maine', '', '');
$form->addOption('mail_state', 'maryland', 'Maryland', '', '');
$form->addOption('mail_state', 'massachusetts', 'Massachusetts', '', '');
$form->addOption('mail_state', 'michigan', 'Michigan', '', '');
$form->addOption('mail_state', 'minnesota', 'Minnesota', '', '');
$form->addOption('mail_state', 'mississippi', 'Mississippi', '', '');
$form->addOption('mail_state', 'missouri', 'Missouri', '', '');
$form->addOption('mail_state', 'montana', 'Montana', '', '');
$form->addOption('mail_state', 'nebraska', 'Nebraska', '', '');
$form->addOption('mail_state', 'nevada', 'Nevada', '', '');
$form->addOption('mail_state', 'new_hampshire', 'New Hampshire', '', '');
$form->addOption('mail_state', 'new_jersey', 'New Jersey', '', '');
$form->addOption('mail_state', 'new_mexico', 'New Mexico', '', '');
$form->addOption('mail_state', 'new_york', 'New York', '', '');
$form->addOption('mail_state', 'north_carolina', 'North Carolina', '', '');
$form->addOption('mail_state', 'north_dakota', 'North Dakota', '', '');
$form->addOption('mail_state', 'ohio', 'Ohio', '', '');
$form->addOption('mail_state', 'oklahoma', 'Oklahoma', '', '');
$form->addOption('mail_state', 'oregon', 'Oregon', '', '');
$form->addOption('mail_state', 'pennsylvania', 'Pennsylvania', '', '');
$form->addOption('mail_state', 'rhode_island', 'Rhode Island', '', '');
$form->addOption('mail_state', 'south_carolina', 'South Carolina', '', '');
$form->addOption('mail_state', 'south_dakota', 'South Dakota', '', '');
$form->addOption('mail_state', 'tennessee', 'Tennessee', '', '');
$form->addOption('mail_state', 'texas', 'Texas', '', '');
$form->addOption('mail_state', 'utah', 'Utah', '', '');
$form->addOption('mail_state', 'vermont', 'Vermont', '', '');
$form->addOption('mail_state', 'virginia', 'Virginia', '', '');
$form->addOption('mail_state', 'washington', 'Washington', '', '');
$form->addOption('mail_state', 'west_virginia', 'West Virginia', '', '');
$form->addOption('mail_state', 'wisconsin', 'Wisconsin', '', '');
$form->addOption('mail_state', 'wyoming', 'Wyoming', '', '');
$form->addSelect('mail_state', 'State',  'required=required');
$form->addInput('text', 'mail_zip', '', 'Zip',  'required=required');
$form->addHtml('<hr class="mt-2 mb-5">');
$form->addHeading('Emergency Contact', 'h5', '');
$form->addInput('text', 'e_contact1_name', '', 'Contact Name', '');
$form->addInput('tel', 'e_contact1_phone', '', 'Contact Phone', '');
$form->addInput('email', 'e_contact1_email', '', 'Contact Email', '');
$form->addOption('e_contact1_relationship', '', 'Pick One', '', '');
$form->addOption('e_contact1_relationship', 'parent', 'Parent', '', '');
$form->addOption('e_contact1_relationship', 'spouse', 'Spouse', '', '');
$form->addOption('e_contact1_relationship', 'sibling', 'Sibling', '', '');
$form->addOption('e_contact1_relationship', 'child', 'Child', '', '');
$form->addOption('e_contact1_relationship', 'relative', 'Relative', '', '');
$form->addOption('e_contact1_relationship', 'friend', 'Friend', '', '');
$form->addOption('e_contact1_relationship', 'co_worker', 'Co-worker', '', '');
$form->addOption('e_contact1_relationship', 'legal_guardian', 'Legal Guardian', '', '');
$form->addOption('e_contact1_relationship', 'domestic_partner', 'Domestic Partner', '', '');
$form->addOption('e_contact1_relationship', 'other', 'Other', '', '');
$form->addSelect('e_contact1_relationship', 'Relationship', '');
$form->addHtml('</section><section>');
$form->addHeading('Federal Employment Forms', 'h1', '');
$form->addHtml('<hr class="mt-2 mb-5">');
$form->addHeading('W4 Tax Withholding Form <a href="https://www.irs.gov/pub/irs-pdf/fw4.pdf" target="_blank" title="IRS Form W-4"><i class="bi bi-question-circle fs-4"></i></a>', 'h2', '');
$form->addOption('w4_filing_status', 'single_marriedseparately', 'Single or Maried filing separately', '', '');
$form->addOption('w4_filing_status', 'marriedfilingjoinly', 'Married filing joinly or Qualifing widow(er)', '', '');
$form->addOption('w4_filing_status', 'headofhouse', 'Head of household', '', '');
$form->addSelect('w4_filing_status', 'Tax Filing Status', '');
$form->addCheckbox('w4_checkonly1', '(1) hold more than one job at a time, or <br>(2) are married filing jointly and your spouse also works. The correct amount of withholding depends on income earned from all of these jobs.', '1', 'data-toggle=false');
$form->printCheckboxGroup('w4_checkonly1', 'Only Check if...', true, '');
$form->addOption('w4_completecheck', 'yes', 'Yes', '', '');
$form->addOption('w4_completecheck', 'no', 'No', '', '');
$form->addSelect('w4_completecheck', 'Is your total income 200,000 or less 400,000 or less if married filing jointly):', '');
$form->startDependentFields('w4_completecheck', 'yes');
$form->addInput('number', 'w4_childdependents', '0', 'Number of Dependents under age 17', '');
$form->startDependentFields('w4_childdependents', '0', true);
$form->addInput('text', 'w4_childmultipler', '', 'Multiply the number of qualifying children under age 17 by 2,000', '');
$form->endDependentFields();

$form->addInput('number', 'w4_otherdependents', '0', 'Other Dependents', '');
$form->startDependentFields('w4_otherdependents', '0', true);
$form->addInput('text', 'w4_othermultipler', '', 'Multiply the number of other dependents by 500', '');
$form->endDependentFields();

$form->addIcon('w4_total', '<span class="bi bi-currency-dollar"></span>', 'before');
$form->addInput('text', 'w4_total', '', 'Total', '');

$form->addIcon('w4_a', '<span class="bi bi-currency-dollar"></span>', 'before');
$form->addInput('text', 'w4_a', '', '(a) Other income not from jobs:', '');

$form->addIcon('w4_b', '<span class="bi bi-currency-dollar"></span>', 'before');
$form->addInput('text', 'w4_b', '', '(b) Deductions:', '');

$form->addIcon('w4_c', '<span class="bi bi-currency-dollar"></span>', 'before');
$form->addInput('text', 'w4_c', '', '(c) Extra withholding: <small>Enter any additional tax you want withheld each pay period</small>', '');
$form->endDependentFields();
$form->addHtml('<hr class="mt-2 mb-5">');
$form->addHeading('I-9 Form - Employment Eligibility Verification <a href="https://www.uscis.gov/sites/default/files/document/forms/i-9.pdf" target="_blank" title="I-9 Employment Eligibility Verification"><i class="bi bi-question-circle fs-4"></i></a>', 'h2', '');
$form->addOption('citizentype', 'citizen', 'A citizen of the United States', '', '');
$form->addOption('citizentype', 'noncitizen', 'A noncitizen national of the United Stats (see Instructions)', '', '');
$form->addOption('citizentype', 'resident', 'A lawful permanent resident (Alien registration Number/USCIS Number)', '', '');
$form->addOption('citizentype', 'alien', 'An alien authorized to work', '', '');
$form->addSelect('citizentype', 'Select a Choice', '');
$form->startDependentFields('citizentype', 'noncitizen, resident, alien');
$form->addInput('text', 'ARNUSCISNumber', '', 'Alien Registration Number/USCIS Number', '');
$form->addOption('preparer', 'no-preparer', 'I did not use  a preparer or translator', '', '');
$form->addOption('preparer', 'yes-preparer', 'A preparer(s) and/or translator(s) assisted the employee in completing Section 1.', '', '');
$form->addSelect('preparer', 'Preparer and/or Translator Certification:', '');
$form->endDependentFields();
$form->addRadio('i9_uspassport', 'Yes', 'yes', 'data-toggle=false');
$form->addRadio('i9_uspassport', 'No', 'no', 'data-toggle=false');
$form->printRadioGroup('i9_uspassport', 'I have a valid, unexpired US Passport', true, 'required=required');
$form->startDependentFields('i9_uspassport', 'yes');
$form->addHeading('Upload List A Document - Passport', 'h5', '');

// Prefill upload with existing file
$current_file = ''; // default empty

$current_file_path = '/hr/file-uploads/';

/* INSTRUCTIONS:
    If you get a filename from your database or anywhere
    and want to prefill the uploader with this file,
    replace "filename.ext" with your filename variable in the line below.
*/
$current_file_name = 'filename_i9_passport_full.ext';

if (file_exists($current_file_path . $current_file_name)) {
    $current_file_size = filesize($current_file_path . $current_file_name);
    $current_file_type = mime_content_type($current_file_path . $current_file_name);
    $current_file = array(
        'name' => $current_file_name,
        'size' => $current_file_size,
        'type' => $current_file_type,
        'file' => $current_file_path . $current_file_name, // url of the file
        'data' => array(
            'listProps' => array(
                'file' => $current_file_name
            )
        )
    );
}

$fileUpload_config = array(
    'upload_dir'    => 'hr/file-uploads/',
    'limit'         => 1,
    'file_max_size' => 5,'extensions'    => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
    'debug'         => true
);

$form->addFileUpload('i9_passport_full', '', 'Image of US Passport', '', $fileUpload_config, [$current_file]);
$form->endDependentFields();
$form->startDependentFields('i9_uspassport', 'no');
$form->addHtml('<hr>');
$form->addHeading('Upload List B Document', 'h5', '');
$form->addOption('i9_listb_documenttype', 'us_driver_license_or_id', 'Driver\'s license or ID card issued by a State or outlying possession of the U.S. with photo or personal info', '', '');
$form->addOption('i9_listb_documenttype', 'government_issued_id', 'ID card issued by federal, state or local government with photo or personal info', '', '');
$form->addOption('i9_listb_documenttype', 'school_id_with_photo', 'School ID card with a photograph', '', '');
$form->addOption('i9_listb_documenttype', 'voter_registration_card', 'Voter\'s registration card', '', '');
$form->addOption('i9_listb_documenttype', 'us_military_card_or_draft_record', 'U.S. Military card or draft record', '', '');
$form->addOption('i9_listb_documenttype', 'military_dependent_id_card', 'Military dependent\'s ID card', '', '');
$form->addOption('i9_listb_documenttype', 'us_coast_guard_merchant_mariner_card', 'U.S. Coast Guard Merchant Mariner Card', '', '');
$form->addOption('i9_listb_documenttype', 'native_american_tribal_document', 'Native American tribal document', '', '');
$form->addOption('i9_listb_documenttype', 'canadian_driver_license', 'Driver\'s license issued by a Canadian government authority', '', '');
$form->addOption('i9_listb_documenttype', 'school_record_or_report_card', 'School record or report card (for persons under age 18)', '', '');
$form->addOption('i9_listb_documenttype', 'clinic_doctor_hospital_record', 'Clinic, doctor, or hospital record (for persons under age 18)', '', '');
$form->addOption('i9_listb_documenttype', 'daycare_nursery_school_record', 'Day-care or nursery school record (for persons under age 18)', '', '');
$form->addOption('i9_listb_documenttype', 'receipt_for_replacement_list_b', 'Receipt for a replacement of a lost, stolen, or damaged List B document (temporary)', '', '');
$form->addSelect('i9_listb_documenttype', 'List B Document Type', '');
$form->addInput('text', 'i9_listb_authority', '', 'List B Document Issuing Authority', '');
$form->addInput('date', 'i9_listb_exp', '', 'List B Document Expiration Date', '');

// Prefill upload with existing file
$current_file = ''; // default empty

$current_file_path = 'hr/file-uploads/';

/* INSTRUCTIONS:
    If you get a filename from your database or anywhere
    and want to prefill the uploader with this file,
    replace "filename.ext" with your filename variable in the line below.
*/
$current_file_name = 'filename_i9_listb_upload.ext';

if (file_exists($current_file_path . $current_file_name)) {
    $current_file_size = filesize($current_file_path . $current_file_name);
    $current_file_type = mime_content_type($current_file_path . $current_file_name);
    $current_file = array(
        'name' => $current_file_name,
        'size' => $current_file_size,
        'type' => $current_file_type,
        'file' => $current_file_path . $current_file_name, // url of the file
        'data' => array(
            'listProps' => array(
                'file' => $current_file_name
            )
        )
    );
}

$fileUpload_config = array(
    'upload_dir'    => '/hr/file-uploads/',
    'limit'         => 2,
    'file_max_size' => 10,'extensions'    => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
    'debug'         => true
);
$form->addFileUpload('i9_listb_upload', '', 'Upload Front and Back Images', '', $fileUpload_config, [$current_file]);
$form->addHtml('<hr>');
$form->addHeading('Upload List C Document', 'h5', '');
$form->addOption('i9_listc_documenttype', 'us_social_security_card', 'U.S. Social Security Card', '', '');
$form->addOption('i9_listc_documenttype', 'certification_of_birth_abroad', 'Certification of report of birth issued by the Department of State (Forms DS-1350, FS-545, FS-240)', '', '');
$form->addOption('i9_listc_documenttype', 'us_birth_certificate', 'Original or certified copy of birth certificate issued by a State, county, municipal authority, or territory of the U.S. with official seal', '', '');
$form->addOption('i9_listc_documenttype', 'native_american_tribal_document_c', 'Native American tribal document', '', '');
$form->addOption('i9_listc_documenttype', 'us_citizen_id_card', 'U.S. Citizen ID Card (Form I-197)', '', '');
$form->addOption('i9_listc_documenttype', 'resident_citizen_id_card', 'Identification Card for Use of Resident Citizen in the United States (Form I-179)', '', '');
$form->addOption('i9_listc_documenttype', 'dhs_employment_authorization', 'Employment authorization document issued by the Department of Homeland Security (DHS)', '', '');
$form->addOption('i9_listc_documenttype', 'receipt_for_replacement_list_c', 'Receipt for a replacement of a lost, stolen, or damaged List C document (temporary)', '', '');
$form->addSelect('i9_listc_documenttype', 'Select 8', '');
$form->addInput('text', 'i9_listc_authority', '', 'List C Document Issuing Authority', '');
$form->addInput('date', 'i9_listc_exp', '', 'List C Document Expiration Date', '');

// Prefill upload with existing file
$current_file = ''; // default empty

$current_file_path = '/hr/file-uploads/';

/* INSTRUCTIONS:
    If you get a filename from your database or anywhere
    and want to prefill the uploader with this file,
    replace "filename.ext" with your filename variable in the line below.
*/
$current_file_name = 'filename_i9_listc_upload.ext';

if (file_exists($current_file_path . $current_file_name)) {
    $current_file_size = filesize($current_file_path . $current_file_name);
    $current_file_type = mime_content_type($current_file_path . $current_file_name);
    $current_file = array(
        'name' => $current_file_name,
        'size' => $current_file_size,
        'type' => $current_file_type,
        'file' => $current_file_path . $current_file_name, // url of the file
        'data' => array(
            'listProps' => array(
                'file' => $current_file_name
            )
        )
    );
}

$fileUpload_config = array(
    'upload_dir'    => '/hr/file-uploads/',
    'limit'         => 2,
    'file_max_size' => 10,'extensions'    => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
    'debug'         => true
);
$form->addFileUpload('i9_listc_upload', '', 'Upload Front and Back Images', '', $fileUpload_config, [$current_file]);
$form->endDependentFields();
$form->addHtml('</section><section>');
$form->addHtml('<hr>');
$form->addHeading('Payroll Forms', 'h1', '');
$form->addHtml('<p>At this time, Birthday.Gold does not withold your federal and state income taxes.  It is important that you plan to pay your tax liability on your own.

This will change in the near future and you will be notified in writing when we implement tax withholdings.</p>');
$form->addOption('payroll_pay_vendor', '', 'Pick One', '', '');
$form->addOption('payroll_pay_vendor', 'paypal', 'Paypal', '', '');
$form->addOption('payroll_pay_vendor', 'venmo', 'Vemno', '', '');
$form->addOption('payroll_pay_vendor', 'cashapp', 'Cashapp', '', '');
$form->addSelect('payroll_pay_vendor', 'Get paid via', 'required=required');
$form->startDependentFields('payroll_pay_vendor', '', true);
$form->addInput('text', 'payroll_account_username', '', 'Account Username', 'required=required');
$form->endDependentFields();
$form->addHtml('</section><section>');
$form->addHtml('<hr>');
$form->addHeading('Policy Agreements and Signature  <a href="/hr/policies" target="_blank" title="Policies"><i class="bi bi-question-circle fs-4"></i></a>', 'h1', '');

/*
$form->addCheckbox('acknowledgements', 'NDA', 'acknowledgements_nda', 'data-toggle=false');
$form->addCheckbox('acknowledgements', 'TimeKeeping Policy', 'acknowledgements_timekeeping', 'data-toggle=false');
$form->addCheckbox('acknowledgements', 'At-Will Employment', 'acknowledgements_atwill', 'data-toggle=false');
$form->addCheckbox('acknowledgements', 'Future Benefits', 'acknowledgements_futurebenefits', 'data-toggle=false');
*/

include('policies_text.inc');
$search=["</p>-\n", '<style>.hanging-indent {text-indent: -1em;  margin-left: 2em;}</style>'];
$replace=["</p>\n", ''];
foreach ($policies as $policy_name => $policy_text) {
    $policy[$policy_name] = strip_tags(str_replace($search, $replace, $policy_text));
}

$form->addTextarea('acknowledgements_general_text', $policy['General Company Policies'], 'General Policies', 'rows=7, style=font-size:11px, readonly, disabled');
$form->addCheckbox('acknowledgements_general', 'I agree', 'acknowledgements_general', 'data-toggle=false');
$form->printCheckboxGroup('acknowledgements_general', ' ', true,  'required=required');

$form->addHtml('<hr>');

$form->addTextarea('acknowledgements_nda_text', $policy['Non-Disclosure Agreement'], 'NDA', 'rows=7, style=font-size:11px, readonly, disabled');
$form->addCheckbox('acknowledgements_nda', 'I agree', 'acknowledgements_nda', 'data-toggle=false');
$form->printCheckboxGroup('acknowledgements_nda', ' ', true,  'required=required');

$form->addHtml('<hr>');

$form->addTextarea('acknowledgements_timekeeping_text', $policy['Timekeeping and Production Requirements'], 'TimeKeeping Policy', 'rows=7, style=font-size:11px, readonly, disabled');
$form->addCheckbox('acknowledgements_timekeeping', 'I agree', 'acknowledgements_timekeeping', 'data-toggle=false');
$form->printCheckboxGroup('acknowledgements_timekeeping', ' ', true,  'required=required');

$form->addHtml('<hr>');

$form->addTextarea('acknowledgements_atwill_text', $policy['At-Will Employment Policy'], 'At-Will Employment', 'rows=7, style=font-size:11px, readonly, disabled');
$form->addCheckbox('acknowledgements_atwill', 'I agree', 'acknowledgements_atwill', 'data-toggle=false');
$form->printCheckboxGroup('acknowledgements_atwill', ' ', true,  'required=required');

$form->addHtml('<hr>');

$form->addTextarea('acknowledgements_futurebenefits_text', $policy['Employment Benefits Policy'], 'Future Benefits', 'rows=7, style=font-size:11px, readonly, disabled');
$form->addCheckbox('acknowledgements_futurebenefits', 'I agree', 'acknowledgements_futurebenefits', 'data-toggle=false');
$form->printCheckboxGroup('acknowledgements_futurebenefits', ' ', true,  'required=required');

$form->addHtml('<hr>');






$form->addHtml('</section>');
$form->addPlugin('formvalidation', '#bd-hr_main1', 'default', array('language' => 'en_US'));





if ($has_signature_error) {
    $signature_error_msg = Form::buildAlert('Please add your signature to confirm your agreement', 'bs5', 'danger');
    $form->addHtml($signature_error_msg);
}

$form->addInput('hidden', 'user-signature', '', 'Sign to confirm your agreement', 'data-signature-pad=true, data-background-color=#F7F7F7, data-pen-color=#333, data-width=100%, data-clear-button=true, data-clear-button-class=btn btn-warning, data-clear-button-text=clear');
$form->centerContent();
$form->addRecaptchaV3('6LfKkfQnAAAAADivF6ErR38G1xqCHmfOWKwZykUJ');
$form->addBtn('submit', 'submit-btn', 1, 'Agree <i class="bi bi-check-lg ms-2" aria-hidden="true"></i>', 'class=btn btn-primary, data-ladda-button=true, data-style=zoom-in');
#$form->endFieldset();


include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header2.inc');


?>
    <!-- bootstrap-icons -->
    
    <link rel="stylesheet" href="/public/js/plugins/min/css/bs5-bd-hr_main1.min.css">

    <link href="/public/js/plugins/signature-pad/signature_pad.min.css" rel="stylesheet" media="screen">
    <link href="/public/js/plugins/ladda/ladda-themeless.min.css" rel="stylesheet" media="screen">
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
 
    
    <?php
  #   $form->printIncludes('css');
      ?>

<section>
    <h1 class="text-center fw-bold">HR Employee Onboarding Forms</h1>

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-md-11 col-lg-10">
            <?php
                if (isset($sent_message)) {
                    echo $sent_message;
                }
                $form->render();
                ?>

            </div>
        </div>
    </div>
            </section>



    <!-- Bootstrap 5 JavaScript -->
    <script src="/public/js/plugins/signature-pad/signature_pad.min.js"></script>
    <script src="/public/js/plugins/min/js/bs5-bd-hr_main1.min.js"></script>
    
<script src="/public/js/plugins/ladda/spin.min.js"></script>
<script src="/public/js/plugins/ladda/ladda.min.js"></script>
<script src="/public/js/plugins/formvalidation/js/FormValidation.full.min.js"></script>
<script src="/public/js/plugins/formvalidation/js/locales/en_US.min.js"></script>
<script src="/public/js/plugins/formvalidation/js/plugins/AutoFocus.min.js"></script>
<script src="/public/js/plugins/formvalidation/js/plugins/Transformer.min.js"></script>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous">
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<?php
$search=['https://dev.birthday.gold/core/applications/phpformbuilder/', 
"});\n</script>"];
#$replace=['/public/js/'];
$replace=['/core/applications/phpformbuilder/', ''];




 

// Read the contents of onboarding_scriptinclude1.inc
$scriptInclude1 = file_get_contents('onboarding_scriptinclude1.inc');
if ($scriptInclude1 === false) {
    // Handle error, the file could not be read
    die('Failed to read onboarding_scriptinclude1.inc');
}

// Read the contents of onboarding_scriptinclude2.inc
$scriptInclude2 = file_get_contents('onboarding_scriptinclude2.inc');
if ($scriptInclude2 === false) {
    // Handle error, the file could not be read
    die('Failed to read onboarding_scriptinclude2.inc');
}

// Replace the placeholder text in the first file with the contents of the second file
$updatedContent = str_replace('//// --- INSERT SCRIPTINCLUDE2 ---- ////', $scriptInclude2, $scriptInclude1);




echo $updatedContent;





include($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footer.inc');

include($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footerjs.inc');
?>
</body>
</html>