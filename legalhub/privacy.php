<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>



<!-- 404 Start -->
<div class="container main-content py-6">
    <div class="container">
        <div class="row">
            <div class="col">
                <h1 class="display-1">Privacy Policy</h1>
                <p class="mb-4">Effective Date: October 11, 2023</p>

                <?PHP
                $type = '';

                if (isset($_REQUEST['full'])) $type = 'full';

                switch ($type) {

                    case 'full':
                        echo '
At birthday.gold, we and its affiliates, take your privacy seriously.  
This is our full privacy policy.  Our policy is intended to comply with the rules and regulations to protect you as a consumer.  
If at any time, you feel that we are not upholding your rights, please get in touch with our legal department and share your concerns or questions.  
';

                        $query = "SELECT content FROM bg_content WHERE name= 'privacy_fullx' and status='active' order by create_dt desc limit 1";
                        $include = $_SERVER['DOCUMENT_ROOT'] . '/core/' . $website['ui_version'] . '/content-privacyfull.php';
                        break;

                    default:
                        echo '
At birthday.gold, we and its affiliates, take your privacy seriously.   
We do want you to be informed without piling on a whole bunch of mumbo jumbo.  
If you ever get bored or need to read the fine print... you can always read our <a href="/legalhub/privacy?full" class="btn btn-primary btn-sm py-0 my-0">FULL PRIVACY POLICY</a> as well get in touch with our legal department if you have any concerns or questions.  
We provide the condensed version of our full privacy policy and nothing substitutes the legal requirements listed in our full privacy policy below the condensed version.
';
                        $query = "SELECT content FROM bg_content WHERE name= 'privacyx' and status='active' order by create_dt desc limit 1";
                        $include = $_SERVER['DOCUMENT_ROOT'] . '/core/' . $website['ui_version'] . '/content-privacy.php';
                }
                $stmt = $database->prepare($query);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $content = $result['content'];

                    echo $content;
                } else {


                    include($include);
                }

                if (isset($_REQUEST['register'])) {
                    echo '                <a class="btn btn-primary py-3 px-5 no-print" href="' . htmlspecialchars($_SERVER['HTTP_REFERER'] ?? '/signup') . '">Go Back</a>';
                } else {
                    echo '                <a class="btn btn-primary py-3 px-5 no-print" href="/">Go Back To Home</a>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
<!--  End -->


<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
