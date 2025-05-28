<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>



<!-- Terms Start -->
<div class="container main-content py-6">
    <div class="container">
        <div class="row">
            <div class="col">
                <h1 class="display-1">Terms and Conditions</h1>
                

                <?PHP
                $type = '';

                if (isset($_REQUEST['full'])) $type = 'full';
                switch ($type) {

                    case 'full':
                        $header_content= ' <h5 class="mt-5">We encourage you to read our full Terms and Conditions and understand the terms that may bind you by using our service.</h5>';

                        $query = "SELECT id, content, DATE_FORMAT(publish_dt, '%M %e, %Y') AS effective_date  FROM bg_content WHERE name= 'terms_full' and `status`='active' order by create_dt desc limit 1";
                        $include = $_SERVER['DOCUMENT_ROOT'] . '/core/' . $website['ui_version'] . '/content-termsfull.php';
                        break;

                    default:
                        $header_content= '
<h5 class="mt-5">People have been known to click accept without reading these.  We encourage you to read these Terms and Conditions so you know what to expect from us and our expectations of you as you use our service.</h5>

<p>At birthday.gold, we want you to be informed without piling on a whole bunch of mumbo jumbo. </p>
<p>If you ever get bored or need to read the fine print... you can always read our <a href="/legalhub/terms?full" class="btn btn-primary btn-sm py-0 my-0">FULL TERMS AND CONDITIONS</a> as well as get in touch with our legal department if you have any concerns or questions.  
We provide the condensed version of our full terms and conditions and nothing substitutes the legal requirements listed in our full terms and conditions below the condensed version.</p>
';
                        $query = "SELECT id, content, DATE_FORMAT(publish_dt, '%M %e, %Y') AS effective_date FROM bg_content WHERE name= 'terms' and `status`='active' order by create_dt desc limit 1";
                        $include = $_SERVER['DOCUMENT_ROOT'] . '/core/' . $website['ui_version'] . '/content-terms.php';
                }

                $stmt = $database->prepare($query);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $content = '<!-- '.$result['id']. ' -->'.$result['content'];
                    $effective_date = $result['effective_date'];
                    echo '<p class="mb-4">Effective Date: '.$effective_date.'</p>';
                    echo $header_content;
                    echo $content;
                } else {
                    include($include);
                }


                if (isset($_REQUEST['register'])) {
                    # $referringPage = $_SERVER['HTTP_REFERER'] ?? '/signup';

                    echo '                <a class="btn btn-primary py-3 px-5 no-print" href="' . htmlspecialchars($_SERVER['HTTP_REFERER'] ?? '/signup') . '">Go Back</a>';
                } else {
                    echo '                <a class="btn btn-primary py-3 px-5 no-print" href="'. htmlspecialchars($_SERVER['HTTP_REFERER'] ?? '/').'">Go Back</a>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
<!-- Terms End -->


<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
