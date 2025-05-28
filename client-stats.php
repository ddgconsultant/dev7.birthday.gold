<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>



    <div class="container-xxl py-6">
        <div class="container">
            <div class="row">
                <div class="col">
                    <i class="bi bi-chart display-1 text-primary"></i>
                    <h1 class="display-1">Stats</h1>
</div>
<?PHP 
echo '<div>';
                  echo '<p>'.date('r').'</p>';
                  echo '<hr><h1>SESSION</h1>';
                  echo '<pre>'.print_r($_SESSION,1).'</pre>';
                  echo '<hr><h1>REQUEST</h1>';
                  echo '<pre>'.print_r($_REQUEST,1).'</pre>';
                  echo '<hr><h1>SERVER</h1>';
                  echo '<pre>'.print_r($_SERVER,1).'</pre>';

                  echo '<hr><h1>CODE RELEASE CHANNEL</h1>';
                  $filename = "__releasedate.txt";
                  if (file_exists($filename)) {
                      $filecontents = file_get_contents($filename);
             
                      echo $filecontents;
                  } else {
                    date_default_timezone_set('America/Denver'); // Set timezone to MST
                    $latestTimestamp = 0;
                    $latestFilename = '';
                    foreach (glob('*.php') as $file) {
                        $fileTimestamp = filemtime($file);
                        if ($fileTimestamp > $latestTimestamp) {
                            $latestTimestamp = $fileTimestamp;
                            $latestFilename = $file;
                        }
                    }
                
                    // Convert the timestamp to a human-readable date format
                    $fileDate = date('F j, Y H:i:s', $latestTimestamp);
                
                    if (!empty($latestFilename)) {
                     #   echo 'Latest .php file (' . $latestFilename . ') date in MST: ' . $fileDate;
                   
                    echo 'Release channel info not available, providing: ' . $fileDate .' MST - '.str_replace('.php', '', $latestFilename);
                    }
                    #  echo 'Release channel info not available.';
                  }
                  
                  ?>
                  <hr>
                    <a class="btn btn-primary py-3 px-5" href="">Go Back To Home</a>
                </div>
            </div>
        </div>
    </div>
        

    
<?PHP
$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
