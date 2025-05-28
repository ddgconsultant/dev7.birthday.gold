<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


$pagedata['pagetitle']='Birthday Deals Online - Perfect Birthday Plans - Birthday Gold';
$pagedata['metakeywords']='Birthday Deals, Birthday Deals Online';
$pagedata['metadescriptions']='Find the best Birthday Deals Online! Unlock exclusive offers and create Perfect Birthday Plans with amazing discounts. Celebrate big and save more today!';


include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


#-------------------------------------------------------------------------------
# PREP VARAIBLES
#-------------------------------------------------------------------------------
$animatetag = ' ';
$gotvalidpromo = false;
$invalidvalidpromo = false;
$promosuccessmessage = '';
$section = 'selection';
$headerattribute['additionalcss'] = '<!--- plan loader -->';



#-------------------------------------------------------------------------------
# SKIP THIS IF ACCOUNT IS GIFT CERTIFICATE
#-------------------------------------------------------------------------------
$giftcode = $session->get('generateGiftCertificateCode');
$giftuserid =  $session->get('generateGiftCertificateCode_user_id');
if (!empty($giftcode) && !empty($giftuserid)) {
  header('location: applyplan?plan=life');
  exit;
}



#-------------------------------------------------------------------------------
# HANDLE THE PROMO CODE ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted()) {
  $animatetag = ' ';
  $promocode = (isset($_POST['promocode']) ? $_POST['promocode'] : '');
  $promodata = $app->getpromocode($promocode);
  if (!$promodata['status']) {
    $invalidvalidpromo = true;
    $promofailedmessage = '<p class="text-danger">' . $promodata['resultmessage'] . ' <small>[' . $promocode . ']</small></p>';
  } else {
    $gotvalidpromo = true;
    $promodata['plan'] = 'gold';
    $session->set('plan_promodata', $promodata);
    $promosuccessmessage = '<p class="border-1 border-black text-success mt-0 pt-0">' . $promodata['resultmessage'] . '<br><small><a href="/plans"  class="text-danger">Remove</a></small></p>';
  }
}



#-------------------------------------------------------------------------------
# SHOW PLAN MATRIX
#-------------------------------------------------------------------------------
$css = 'plans2.css';

if ($website['plan_version'] == 'v3') {
  $section .= '-v3';
  $section='selection-myaccount-v3';
} 


if (isset($_REQUEST['learn']) && $_REQUEST['learn'] == 'more') {
  $section = 'matrix';
  $css = 'plans.css';
  $section='matrix-myaccount-v3';
}

if (strpos($website['fulluri']['uri'], 'myaccount') || $account->isactive()) {
  $section .= '-myaccount';
  $css = 'plans.css';
  $section='selection-myaccount-v3';
}


$headerattribute['additionalcss'] .= '<!-- loading ' . $section . ' -->';
$headerattribute['additionalcss'] .= '<link href="/public/css/' . $css . '" rel="stylesheet">';

#echo '<h1>'.$section.'</h1>';
include_once($_SERVER['DOCUMENT_ROOT'] . '/core/' . $website['ui_version'] . '/plan-' . $section . '.php');
?>
<!-- Plans End -->
<div class="row m-5"></div>

<?PHP

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
