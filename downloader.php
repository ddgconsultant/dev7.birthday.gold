<?php
// Starting session (or resume existing)


// File type and file name from query parameters
$file_type = isset($_GET['t']) ? $_GET['t'] : null;
$file_name = isset($_GET['f']) ? $_GET['f'] : null;

if ($file_type && $file_name) {
    // Construct file path based on file type and file name
    // This is just a sample, adapt as needed
    $base_path = $_SERVER['DOCUMENT_ROOT'].'/downloads/'; // Base directory
    $file_path = $base_path . $file_type . '/' . $file_name;
#$search=array('.pdf', 'pdf' , '.jpg', 'jpg');
#$replace=array('.pdf', '.pdf', '.jpg', '.jpg');

  #  $file_path=str_replace($search, $replace,  $file_path);
    // Security checks can go here

    // Check if file exists and is readable
    if (file_exists($file_path) && is_readable($file_path)) {
   
        $file_info = pathinfo($file_path);
        $extension = strtolower($file_info['extension']);
        
        // Determine the correct Content-Type based on file extension
        switch ($extension) {
            case 'pdf':
                $content_type = 'application/pdf';
                break;
            case 'jpg':
                $content_type = 'image/jpeg';
                break;
            default:
                $content_type = 'application/octet-stream';
        }

        // Setting up headers for file download
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));

        // Output the file content
        readfile($file_path);
    } else {
        // File does not exist or is not readable
        header('HTTP/1.1 404 Not Found');
       # echo $file_path.'<br>';
        echo 'File not found or is not readable.';
    }
} else {
    // Missing or invalid parameters
    header('HTTP/1.1 400 Bad Request');
    echo 'Missing or invalid parameters.';
}