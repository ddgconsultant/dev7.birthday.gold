<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>


<?PHP
if (!empty($enableadminpageeditor)) {   $admin->admineditor('body-1'); }
### ADMIN PAGE EDITOR: START-body-1 ###
?>
<div class="container flex-grow-1 my-5">
    <!-- Banner with tagline -->
    <div class="container mt-4">
        <div class="jumbotron text-center">
            <h1>Your Trust, Our Priority</h1>
            <p>Understanding our platform's commitment to transparency, privacy, and responsibility.</p>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">

            <!-- Terms and Conditions Card -->
            <div class="col-md-6 col-sm-12 mb-4">
                <div class="card cursor-pointer" onclick="window.location.href='/legalhub/terms';">
                    <div class="card-header h5">
                        Terms and Conditions
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Explore our terms to understand our platform's rules. Ensuring clarity and fairness for all users!</p>
                        <div class="d-md-flex">
                            <div class="mb-2 mb-md-0">
                                <a href="/legalhub/terms" class="btn btn-primary">Read our Terms</a>
                            </div>
                            <div class="d-none d-md-inline mx-2 mt-1">
                                -
                            </div>
                            <div>
                                <a href="/legalhub/terms?full" class="btn btn-sm btn-secondary mt-1">Full Terms and Conditions</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Privacy Policy Card -->
            <div class="col-md-6 col-sm-12 mb-4">
                <div class="card cursor-pointer" onclick="window.location.href='/legalhub/privacy';">
                    <div class="card-header h5">
                        Privacy Policy
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Your privacy is paramount to us. Dive into our policy to see how we protect your data.</p>
                        <div class="d-md-flex flex-wrap">
                            <div class="mb-2 mb-md-0">
                                <a href="/legalhub/privacy" class="btn btn-primary">Read our Policy</a>
                            </div>
                            <div class="d-none d-md-inline mx-2 mt-1">
                                -
                            </div>
                            <div class="mb-2 mb-md-0">
                                <a href="/legalhub/privacy?full" class="btn btn-sm btn-secondary mt-1">Full Privacy Policy</a>
                            </div>
                            <div class="ms-md-3">
                                <a href="/legalhub/cookies" class="btn btn-sm btn-secondary mt-1">Manage Cookies</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Our Responsibilities Card -->
            <div class="col-md-6 col-sm-12 mb-4">
                <div class="card cursor-pointer" onclick="window.location.href='/legalhub/responsibilities';">
                    <div class="card-header h5">
                        Our Responsibilities
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Discover our commitment to you. Upholding our responsibilities is a top priority.</p>
                        <a href="/legalhub/responsibilities" class="btn btn-primary">Learn More</a>
                    </div>
                </div>
            </div>

            <!-- Your Data Rights Card -->
            <div class="col-md-6 col-sm-12 mb-4">
                <div class="card cursor-pointer" onclick="window.location.href='/legalhub/datarights';">
                    <div class="card-header h5">
                        Your Data Rights
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Learn about your data rights. Empowering users through transparent data practices!</p>
                        <a href="/legalhub/datarights" class="btn btn-primary">Know Your Rights</a>
                    </div>
                </div>
            </div>

        </div>
        <div class="row justify-content-center">

            <!-- CopyRight Card -->
            <div class="col-md-6 col-sm-12 mb-4">
                <div class="card cursor-pointer" onclick="window.location.href='/legalhub/digitalrights';">
                    <div class="card-header h5">
                        Copyrights, Licenses, DMCA
                    </div>
                    <div class="card-body">
                        <p class="card-text my-3">Copyright notices, Licenses and DMCA take down information.</p>
                        <a href="/legalhub/digitalrights" class="btn btn-primary">Read more</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<?PHP
### ADMIN PAGE EDITOR: END-body-1 ###
?>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
