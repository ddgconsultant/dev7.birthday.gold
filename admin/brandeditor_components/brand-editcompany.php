<?php
if (!isset($componentmode) || $componentmode != 'include') {
// Include the site-controller.php file
include_once $_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php';
}


#-------------------------------------------------------------------------------
# HANDLE POST
#-------------------------------------------------------------------------------
if ($app->formposted()) {
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($componentmode) || $componentmode != 'include')) {

$isAjax = isset($_POST['isAjax']) && $_POST['isAjax'] == 'true';


if ($isAjax) {
// Retrieve values from POST data
$companyId = $_POST['company_id'];
$status = $_POST['status'];

// Update company data in the database
$database->update('bg_companies', [
    'status' => $status
], [
    'company_id' => $companyId
]);

// send response
if ($updateResult) {
    // Send a JSON response
    echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
} else {
    // Handle failure
    echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
}

exit;
} else {
// Retrieve values from POST data
$companyId = $_POST['company_id'];
$parentCompanyName = $_POST['parent_company_name'];
$companyName = $_POST['company_name'];
$companyDisplayName = $_POST['company_display_name'];
$category = $_POST['category'];
$source = $_POST['source'];
$company_url = $_POST['company_url'];
$infoUrl = $_POST['info_url'];
$signupUrl = $_POST['signup_url'];
$appGoogle = $_POST['appgoogle'];
$appApple = $_POST['appapple'];

$minage = $_POST['minage'];
$maxage = $_POST['maxage'];
if ($minage == '') $minage = 0;
if ($maxage == '') $maxage = 200;
if ($maxage < $minage) {
    list($minage, $maxage) = array($maxage, $minage);
}

if (strtolower($signupUrl)=='app only' || strtolower($signupUrl)=='apponly' || strtolower($signupUrl)=='app-only'|| strtolower($signupUrl)=='app_only') {
$signupUrl = $website['apponlytag'];
}

$description = $_POST['description'];
$status = $_POST['status'];

// Update company data in the database
$database->update('bg_companies', [
    'parent_company_name' => $parentCompanyName,
    'company_name' => $companyName,
    'company_display_name' => $companyDisplayName,
    'category' => $category,
    'source' => $source,
    'company_url' => $company_url,
    'info_url' => $infoUrl,
    'signup_url' => $signupUrl,
    'description' => $description,
    'appgoogle' => $appGoogle,
    'appapple' => $appApple,
    'minage' => $minage,
    'maxage' => $maxage,
    'facebook' => $_POST['facebook'],
    'tiktok' => $_POST['tiktok'],
    'twitter' => $_POST['twitter'],
    'instagram' => $_POST['instagram'],
    'status' => $status
], [
    'company_id' => $companyId
]);

// Redirect user back to the edit page
header("Location: /admin/brand-editor?cid=$companyId");
exit;
}
}
}


#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
// Retrieve company ID from GET data

if (empty($cid)) {
$cid = $_GET['cid'];
#   $cid = $qik->decodeId($_REQUEST['cid'] ?? '');
}
$companyId=$cid;

// Retrieve company data from the database
#$company = $database->get('bg_companies', '*', ['company_id' => $companyId]);
$sql = "SELECT * FROM bg_companies WHERE company_id = ?";
$stmt = $database->prepare($sql);
$stmt->execute([$companyId]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$company = $results[0] ?? null;



#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
/*

*/
$additionalstyles.='
<style>
    .col-form-label {
        font-weight: bold; /* Make the labels bold */
    }
    .input-group-text {
        font-weight: bold;
    }
        
    input.form-control, 
    textarea.form-control, 
    select.form-control {
        color: var(--bs-primary);
    }
    

    /* Add a caret to the select box */
    select.form-control {
        background-image: url(\'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-caret-down-fill" viewBox="0 0 16 16"%3E%3Cpath d="M7.247 11.14l-4.796-5.481C1.868 5.294 2.06 4.5 2.614 4.5h10.772c.554 0 .746.794.364 1.16l-4.796 5.48a.678.678 0 0 1-1.007 0z"/%3E%3C/svg%3E\');
        background-position: right 10px center;
        background-repeat: no-repeat;
        background-size: 16px 16px;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        padding-right: 30px; /* Make room for the caret */
    }
</style>
';

?>
<form method="POST" action="/admin/brandeditor_components/brand-editcompany.php">
    <input type="hidden" name="company_id" value="<?= $company['company_id'] ?>">
    
    <!-- Parent Company -->
    <div class="form-group row no-gutters small-row">
        <label class="col-3 col-form-label" for="parent_company_name">Parent Company:</label>
        <div class="col-9"> 
            <input type="text" class="form-control" id="parent_company_name" name="parent_company_name" value="<?= $company['parent_company_name'] ?>">
        </div>
    </div>

    <!-- Company Name -->
    <div class="form-group row no-gutters small-row">
        <label class="col-3 col-form-label" for="company_name">Company Name:</label>
        <div class="col-6"> 
            <input type="text" class="form-control" id="company_name" name="company_name" value="<?= $company['company_name'] ?>">
        </div>
        <div class="col-3"> 
            <input type="text" class="form-control" id="company_display_name" name="company_display_name" placeholder="Display Name" value="<?= $company['company_display_name'] ?>">
        </div>
    </div>

    <!-- Category -->
    <div class="form-group row no-gutters small-row">
        <label class="col-3 col-form-label" for="category">Category:</label>
        <div class="col-9"> 
            <input type="text" class="form-control" id="category" name="category" value="<?= $company['category'] ?>">
        </div>
    </div>

    <!-- Source -->
    <div class="form-group row no-gutters small-row">
        <label class="col-3 col-form-label" for="source">Source:</label>
        <div class="col-9"> 
        <div class="input-group">
                <span class="input-group-text"><i class="bi bi-globe"></i></span>
          <input type="text" class="form-control" id="source" name="source" value="<?= $company['source'] ?>">
          </div>
          </div>
    </div>

    <!-- Company URL with icon -->
    <div class="form-group row no-gutters small-row">
        <label class="col-3 col-form-label" for="company_url">Company URL:</label>
        <div class="col-9">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-globe"></i></span>
                <input type="text" class="form-control" id="company_url" name="company_url" value="<?= $company['company_url'] ?>">
            </div>
        </div>
    </div>

    <!-- Info URL with icon -->
    <div class="form-group row no-gutters small-row">
        <label class="col-3 col-form-label" for="info_url">Info URL:</label>
        <div class="col-9">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-globe"></i></span>
                <input type="text" class="form-control" id="info_url" name="info_url" value="<?= $company['info_url'] ?>">
            </div>
        </div>
    </div>

    <!-- Signup URL with icon -->
    <div class="form-group row no-gutters small-row">
        <label class="col-3 col-form-label" for="signup_url">Signup URL:</label>
        <div class="col-9">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-globe"></i></span>
                <input type="text" class="form-control" id="signup_url" name="signup_url" value="<?= $company['signup_url'] ?>">
            </div>
        </div>
    </div>

<!-- Apps with placeholders -->
<div class="form-group row no-gutters small-row">
    <label class="col-3 col-form-label" for="signup_url">Apps:</label>
    
    <!-- Google App input with Google icon -->
    <div class="col-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-google"></i></span>
            <input type="text" class="form-control" id="appgoogle" name="appgoogle" placeholder="Google" value="<?= $company['appgoogle'] ?>">
        </div>
    </div>

    <!-- Apple App input with Apple icon -->
    <div class="col-5">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-apple"></i></span>
            <input type="text" class="form-control" id="appapple" name="appapple" placeholder="Apple" value="<?= $company['appapple'] ?>">
        </div>
    </div>
</div>


    <!-- Description -->
    <div class="form-group row no-gutters small-row">
        <label class="col-3 col-form-label" for="description">Description:</label>
        <div class="col-9">
            <textarea class="form-control" id="description" name="description" rows="4"><?= $company['description'] ?></textarea>
        </div>
    </div>

    <!-- Age Limits -->
    <div class="form-group row no-gutters small-row">
        <label class="col-3 col-form-label" for="signup_url">Age Limits:</label>
        <div class="col-4"> 
            <input type="text" class="form-control" id="minage" name="minage" placeholder="Min Age" value="<?= $company['minage'] ?>">
        </div>
        <div class="col-5"> 
            <input type="text" class="form-control" id="maxage" name="maxage" placeholder="Max Age" value="<?= $company['maxage'] ?>">
        </div>
    </div>


    <hr>
    <?PHP
    ///------------------------------------------------------------
    $links = array();
    $platforms = array('facebook', 'twitter', 'tiktok', 'instagram');
    $links['totalplatform'] = count($platforms);
    $links['numplatformset'] = 0;
    foreach ($platforms as $platform) {
        if (!empty($company[$platform])) {
            $links[$platform] = $company[$platform];
            $links['numplatformset']++;
        } else  $links[$platform] = '';
    }


    ///------------------------------------------------------------
    if ($links['numplatformset'] < $links['totalplatform']) {
        $websiteurl = !empty($company['info_url']) ? $company['info_url'] :  $company['signup_url'];  // Replace with your website URL

        // Try file_get_contents first
        /*
$html = @file_get_contents($websiteurl);
if ($html !== false) {
$links = extractSocialMediaLinks($links, $html, $platforms);
}
*/
        #$html === false || 
        if (($links['numplatformset'] < $links['totalplatform']) && !empty($websiteurl)) {
            $curl = curl_init($websiteurl);
            // Set the cURL options
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_URL, $websiteurl);
            // Set the cURL option to disable SSL verification
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

            $html = @curl_exec($curl);
            if ($html === false) {
                die('Error: ' . curl_error($curl));
            }
            curl_close($curl);

            $links = extractSocialMediaLinks($links, $html, $platforms);
        }
    }

    ///------------------------------------------------------------
    // Function to extract links from HTML
    function extractSocialMediaLinks($links, $html, $platforms)
    {
        if (empty($html)) {
            return $links;
        }

        $dom = new DOMDocument();

        libxml_use_internal_errors(true); // Disable libxml errors

        $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);

        libxml_clear_errors(); // Clear any errors if they exist

        $anchors = $dom->getElementsByTagName('a');

        foreach ($anchors as $element) {
            $href = $element->getAttribute('href');
            foreach ($platforms as $platform) {
                if ((strpos($href, $platform) !== false) && $links[$platform] == '') {
                    $links[$platform] = $href;
                    $links['numplatformset']++;
                }
            }
        }

        return $links;
    }


    $facebook = $links['facebook'];
    $instagram = $links['instagram'];
    $twitter =  $links['twitter'];
    $tiktok =  $links['tiktok'];

    ?>
<!-- Social Media with icons -->
<div class="form-group row no-gutters small-row">
    <label class="col-3 col-form-label" for="signup_url">Social Media:</label>
    
    <!-- Facebook input with Facebook icon -->
    <div class="col-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-facebook"></i></span>
            <input type="text" class="form-control" id="facebook" name="facebook" placeholder="Facebook" value="<?= $facebook ?>">
        </div>
    </div>

    <!-- Twitter input with Twitter icon -->
    <div class="col-5">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-twitter"></i></span>
            <input type="text" class="form-control" id="twitter" name="twitter" placeholder="Twitter" value="<?= $twitter ?>">
        </div>
    </div>
</div>

<div class="form-group row no-gutters small-row">
    <label class="col-3 col-form-label" for="signup_url"></label>
    
    <!-- Instagram input with Instagram icon -->
    <div class="col-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-instagram"></i></span>
            <input type="text" class="form-control" id="instagram" name="instagram" placeholder="Instagram" value="<?= $instagram ?>">
        </div>
    </div>

    <!-- TikTok input with TikTok icon -->
    <div class="col-5">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-tiktok"></i></span>
            <input type="text" class="form-control" id="tiktok" name="tiktok" placeholder="TikTok" value="<?= $tiktok ?>">
        </div>
    </div>
</div>
    <hr>
  
<div class="form-group row no-gutters small-row">
    <label class="col-3 col-form-label" for="status">Status:</label>
    <div class="col-9">
        <select class="form-control" id="status" name="status">
            <?php
            $statuses = ['finalized', 'active', 'inactive', 'duplicate', 'pending', 'new', 'notworking', 'toocomplex', 'otprequired', 'ng_toocomplex', 'finalized_otp_bgm'];
            foreach ($statuses as $status) {
                $selected = ($company['status'] === $status) ? 'selected' : '';
                echo '<option value="' . $status . '" ' . $selected . '>' . ucfirst(str_replace('_', ' ', $status)) . '</option>';
            }
            ?>
        </select>
    </div>
</div>


    <button type="submit" class="btn btn-primary">Save</button>
</form>
</div>
<?PHP echo $website['bootstrap_js']; ?>
