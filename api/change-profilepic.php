<?PHP
$pagemode = 'core';
  include('api_coordinator.php');

// Route the request
$path = $_SERVER['PATH_INFO'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];


if ( $method === 'POST') {
  


$input['user_id'] = $_POST['user_id'] ?? null;
$input['media']=$_FILES['media'];


        // Validate the input
        if (isset($input['user_id']) && isset($input['media'])) {
            $user_id  = $input['user_id'];
            $media  = $input['media'];
      
$errors = [];
$file_name = $media['name'];
$file_size = $media['size'];
$file_tmp = $media['tmp_name'];
$file_type = $media['type'];
$explodedFileName = explode('.', $file_name);
$file_ext = strtolower(end($explodedFileName));

$extensions= array("jpeg","jpg","png");

if(in_array($file_ext, $extensions) === false){
$errors[] = "Extension not allowed, please choose a JPEG or PNG file.";
}

if($file_size > 5 * 1024 * 1024){
$errors[] = 'File size must be less than 5 MB';
}

if (empty($errors)) {
// Create a unique hashed filename based on the user_id and a random string
$randomString = bin2hex(random_bytes(10)); // generates a random string
$hashedFileName = hash('sha256', $user_id.$randomString).'.'.$file_ext;
# $filePath = "public/uploads/".$hashedFileName;
$actualurl='cdn.birthday.gold/public/useravatars';

$uploads_dir = '../cdn.birthday.gold/public/useravatars';

// Check if the uploads directory exists, if not create it
if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0777, true); // The 0777 permission will allow read, write, and execute. 'true' enables recursive creation of directories
}

$filePath = $uploads_dir . "/" . $hashedFileName;
$dbsavelocation = str_replace('../', '//', $uploads_dir) . "/" . $hashedFileName;

move_uploaded_file($file_tmp, $filePath);


$updatefields = ['avatar' => $dbsavelocation];
$account->updateSettings($user_id, $updatefields);
$current_user_data= $account->getuserdata($user_id, 'user_id');

$dbsavelocation='https:'.$dbsavelocation;
           // Successful upload
           $payload = [
            'avatar_url' => $dbsavelocation,
           ];
           $api->responseHandler($payload);
       } else {
          // Bad input parameter
       $api->responseError(400, ['message' => implode(' ', $errors)]);
       
       }
   } else {
       // Bad input parameter
       $api->responseError(401, ['message' => 'Invalid user_id or media']);
   }
} else {
   // Method not allowed
   $api->response400(['message' => 'Method not allowed']);
}
