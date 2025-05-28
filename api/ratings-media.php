<?PHP
$pagemode = 'core';
  include('api_coordinator.php');

// Route the request
$path = $_SERVER['PATH_INFO'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

#$existingMedia=[];
if ( $method === 'POST') {
  


$input['rating_id'] = $_POST['rating_id'] ?? null;
$input['media']=$_FILES['media'];


        // Validate the input
        if (isset($input['rating_id']) && isset($input['media'])) {
            $rating_id  = $input['rating_id'];
            $media  = $input['media'];
            $result = $app->getcompany($rating_id);

               // Fetch existing media JSON from the database
           # $existingMediaJSON = $database->query("SELECT media FROM bg_company_rewards_ratings WHERE rating_id = ?", [$rating_id])->fetchColumn();
            $sql="SELECT media FROM bg_company_rewards_ratings WHERE rating_id = ?";
            $stmt = $database->prepare($sql);
            $stmt->execute([$rating_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($result['media'])) {
                $existingMedia = json_decode($result['media'], true);
            } else {
                $existingMedia = [];
            }


$errors = [];
$file_name = $media['name'];
$file_size = $media['size'];
$file_tmp = $media['tmp_name'];
$file_type = $media['type'];
$explodedFileName = explode('.', $file_name);
$file_ext = strtolower(end($explodedFileName));

$extensions= ['jpg', 'jpeg', 'png', 'mp4']; // Add more if needed

if(in_array($file_ext, $extensions) === false){
$errors[] = "Extension not allowed, please choose a JPEG, PNG, mp4 file.";
}

if($file_size > 25 * 1024 * 1024){
$errors[] = 'File size must be less than 50 MB';
}

if (empty($errors)) {
// Create a unique hashed filename based on the rating_id and a random string
$randomString = bin2hex(random_bytes(10)); // generates a random string
$hashedFileName = hash('sha256', $rating_id.$randomString).'.'.$file_ext;
# $filePath = "public/uploads/".$hashedFileName;
$actualurl='cdn.birthday.gold/public/ratingsmedia';

$uploads_dir = '../cdn.birthday.gold/public/useravatars';

// Check if the uploads directory exists, if not create it
if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0777, true); // The 0777 permission will allow read, write, and execute. 'true' enables recursive creation of directories
}

$filePath = $uploads_dir . "/" . $hashedFileName;
$dbsavelocation = str_replace('../', '//', $uploads_dir) . "/" . $hashedFileName;

move_uploaded_file($file_tmp, $filePath);


#print_r($existingMedia); exit;

if (is_array($existingMedia)) {
    $existingMedia[] = $dbsavelocation;
} elseif (is_string($existingMedia)) {
    $existingMedia = [$existingMedia, $dbsavelocation];  // Create a new array
} else {
    $existingMedia = [$dbsavelocation];  // If it's neither an array nor a string, initialize it as an array
}
$medialist = [];

if (is_array($existingMedia)) {
    foreach ($existingMedia as $mediaItem) {
        $medialist[] = 'https:' . $mediaItem;
    }
} elseif (is_string($existingMedia)) {
    $medialist[] = 'https:' . $existingMedia;
}


    // Update media column in the database
    $updatedMediaJSON = json_encode($existingMedia);
    $database->query("UPDATE bg_company_rewards_ratings SET media = ? WHERE rating_id = ?", [$updatedMediaJSON, $rating_id]);



$dbsavelocation='https:'.$dbsavelocation;

           // Successful upload
           $payload = ['success' => true, 
           'message' => 'Media successfully uploaded',

            'avatar_url' => $dbsavelocation,
            'numberofmedia'=>count($medialist),
            'medialist'=> $medialist,
           ];
           $api->responseHandler($payload);
       } else {
          // Bad input parameter
       $api->responseError(400, ['message' => implode(' ', $errors)]);
       
       }
   } else {
       // Bad input parameter
       $api->responseError(400, ['message' => 'Invalid rating_id or media']);
   }
} else {
   // Method not allowed
   $api->response400(['message' => 'Method not allowed']);
}

//=======================================================================


