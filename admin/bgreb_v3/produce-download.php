<?php
// Include the site-controller.php file
$dir['base'] = $BASEDIR = __DIR__ . "/../.." ?? $_SERVER['DOCUMENT_ROOT'];
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

if (!class_exists('ZipArchive')) {
    exit('ZipArchive is not installed or enabled.');
}
#-------------------------------------------------------------------------------
# Get download file
#-------------------------------------------------------------------------------
if (isset($_REQUEST['zip'])) {
  /**
   * Zip and download a folder recursively 
   */
  function zip_and_download($source_folder)
  {

    
    // Initialize archive
    $zip = new ZipArchive();
global $website;
    // Set final download name
    $zip_name = 'BGREB_chrome_extension_'.$website['bge_extensionversion'].'.zip';

    // Open archive
    if ($zip->open($zip_name, ZipArchive::CREATE) !== TRUE) {
      exit("cannot open <$zip_name>\n");
    }

    // Create iterator 
    $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($source_folder),
      RecursiveIteratorIterator::SELF_FIRST  // Change here to SELF_FIRST
    );

    foreach ($files as $name => $file) {

      // For directories
      if ($file->isDir()) {
        $relativePath = substr($file->getPathname(), strlen($source_folder) + 1);
        $zip->addEmptyDir($relativePath);
      } else { // For files

        // Get real and relative path
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($source_folder) + 1);

        // Add current file to archive
        $zip->addFile($filePath, $relativePath);
      }
    }

    // Close zip 
    $zip->close();

    // Send headers
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename=' . $zip_name);
    header('Content-Length: ' . filesize($zip_name));

    // Read file
    readfile($zip_name);

    // Remove zip
    unlink($zip_name);
    exit;
  }


  // Usage
  #echo  "DOWNLOADED !! -- check your downloaded folder, extract and open <a href='chrome://extensions' target='_extensions'>CHROME EXTENSIONS</a>";
  #ob_flush();
  #ob_clean();
  #$dir['bge_raw'] = 'W:/BIRTHDAY_SERVER/dev5.birthday.gold/admin/bgreb_v3';
  global $website;
  $zipdir = $dir['bge_raw'] . '/chrome_extension/'.$website['bge_extensionversion'] ;


if (!is_dir($zipdir)) {
    exit('The directory ' . $zipdir . ' does not exist or is not accessible.');
}


  #breakpoint(  $zipdir);
  zip_and_download($zipdir);

  #echo  "DOWNLOADED !! -- check your downloaded folder, extract and open <a href='chrome://extensions' target='_extensions'>CHROME EXTENSIONS</a>";
  exit;
}

