<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>


<?PHP
if (!empty($enableadminpageeditor)) {   $admin->admineditor('body-1'); }
### ADMIN PAGE EDITOR: START-body-1 ###
?>
<!--  Start -->
<div class="container-xxl py-6 flex-grow-1 ">
    <div class="container">
        <div class="row">
            <div class="col">
                <h1 class="display-1">Copyrights, Licenses, DMCA</h1>
                <p class="mb-4">Last updated: December 19, 2023</p>


                <!-- Copyright Notices -->
                <section id="copyrights">
                    <h5 class="mt-5">Copyright Notices</h5>
                    <p>
                        All content created and published by birthday.gold, including but not limited to, text, graphics, logos, and user interface design, is the property of birthday.gold and is protected by international copyright laws. All rights reserved.
                    </p>
                    <p>
                        However, all logos, brands, loyalty programs, rewards, and other content relating to other businesses represented on our site are the sole property of their respective owners. They are used on our platform purely for descriptive purposes, enabling our users to make informed decisions and take appropriate actions. We neither claim any ownership over these assets nor aim to benefit from their use beyond the context explained.
                    </p>
                </section>

                <hr>
                <!-- Licensing of Third-Party Software -->
                <section id="licensing">
                    <h5 class="mt-5">Licensing of Third-Party Software</h5>
                    <p>
                        While we utilize various software and tools to provide our services, we acknowledge the ownership and rights of these third-party software. We particularly employ tools like Apache, PHP, and MySQL in the delivery of our services. Their use on our platform does not imply ownership. Instead, we use these tools under their respective licenses to ensure the best service delivery to our users.
                    </p>
                  <!--  <p>Attributions, credits and specific licensing: <a href="/legalhub/licenses" target="_link" class="btn btn-sm ms-4 btn-primary">Here</a></p> -->
                </section>


                <hr>
                <!-- DMCA Takedown Requests -->
                <section id="dmca">
                    <h5 class="mt-5">DMCA Takedown Requests</h5>
                    <p>
                        At birthday.gold, we respect intellectual property rights. If you believe that content on our site infringes upon your copyright or that of someone you represent, please contact us immediately. Provide all relevant details, including the copyrighted work, a link to the infringing content, and your contact information. We'll review the information and take appropriate action, including removing the content if necessary.
                    </p>
                    <p>
                        You can submit your Digital Millennium Copyright Act takedown requests: <a href="/legalhub/dmca" class="btn btn-sm ms-md-3 btn-primary">Here</a>
                    </p>
                </section>

            </div>

        </div>


        <?PHP
        if (isset($_REQUEST['register'])) {
            echo '                <a class="btn btn-primary py-3 px-5 no-print mt-5" href="/signup">Go Back To Sign Up</a>';
        } else {
            echo '                <a class="btn btn-primary py-3 px-5 no-print mt-5" href="/">Go Back To Home</a>';
        }
        ?>
    </div>
</div>
<!--  End -->
<?PHP
### ADMIN PAGE EDITOR: END-body-1 ###
?>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
