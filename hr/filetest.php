<?php
use phpformbuilder\Form;
use phpformbuilder\Validator\Validator;
use fileuploader\server\FileUploader;

/* =============================================
    start session and include form class
============================================= */

session_start();
include_once rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . '/core/applications/phpformbuilder/Form.php';

// include the fileuploader

include_once rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . '/core/applications/phpformbuilder/plugins/fileuploader/server/class.fileuploader.php';

/* =============================================
    validation if posted
============================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('testfileupload')) {
    // create validator & auto-validate required fields
    $validator = Form::validate('testfileupload');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['testfileupload'] = $validator->getAllErrors();
    } else {
        $uploaded_files = [];
        if (isset($_POST['i9_passport_full']) && !empty($_POST['i9_passport_full'])) {
            $posted_file = FileUploader::getPostedFiles($_POST['i9_passport_full']);
            $uploaded_files['i9_passport_full'] = [
                'upload_dir' => '/hr/file-uploads/',
                'filename' => $posted_file[0]['file']
            ];
        }
        // clear the form
        Form::clear('testfileupload');
        // redirect after success
        header('Location:testdone.php');
        exit;
    }
}

/* ==================================================
    The Form
 ================================================== */

$form = new Form('testfileupload', 'horizontal', 'novalidate, data-fv-no-icon=true', 'bs5');
// $form->setMode('development');

// Prefill upload with existing file
$current_file = []; // default empty

$current_file_path = '/hr/file-uploads/';

/* INSTRUCTIONS:
    If you get a filename from your database or anywhere
    and want to prefill the uploader with this file,
    replace "filename.ext" with your filename variable in the line below.
*/
$current_file_name = 'filename.ext';

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
    'file_max_size' => 5,'extensions'    => ['jpg', 'jpeg', 'png', 'gif'],
    'debug'         => true
);
$form->addFileUpload('i9_passport_full', '', 'Passport images', '', $fileUpload_config, $current_file);
$form->setCols(0, 12);
$form->centerContent();
$form->centerContent();
$form->addBtn('submit', 'submit', '', 'Submit', 'class=btn btn-success');
$form->centerContent(false);
$form->addPlugin('formvalidation', '#testfileupload', 'default', array('language' => 'en_US'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Php Form Builder - Bootstrap 5 form</title>
    <meta name="description" content="">

    <!-- Bootstrap 5 CSS -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

    <!-- bootstrap-icons -->
    
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <?php $form->printIncludes('css'); ?>
</head>

<body>

    <h1 class="text-center">Php Form Builder - Bootstrap 5 form</h1>

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-md-11 col-lg-10">
                <?php
                $form->render();
                ?>

            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JavaScript -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <?php
    $form->printIncludes('js');
    $form->printJsCode();
    ?>

</body>

</html>
