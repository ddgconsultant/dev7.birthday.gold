<?PHP

$input = [
    'user_id' => $current_user_data['user_id'],
    'company_id' => 0,
    'type' => $_POST['type'] ?? $_POST['data_type'] ?? '',
    'data_type' => $_POST['type'] ?? $_POST['data_type'] ?? '',
    'name' => htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'),
    'host' => $_POST['host'] ?? '',
    'username' => $_POST['username'] ?? '',
    'password' => $_POST['password'] ?? '',
    'notes' => $_POST['notes'] ?? '',
    'category' => $_REQUEST['category'] ?? '',
    'grouping' => $_REQUEST['grouping'] ?? '',
    'datatype' => $_REQUEST['datatype'] ?? 'username_password',
  'creator_id' => $_REQUEST['admin_id'] ?? 0,
];

$result =$accessmanager->create_record($input);

        if ($result) {
            // Get the last inserted ID
           # $last_inserted_id = $database->lastInsertId();  
           $last_inserted_id=   $result;
        $accessmanager->logAccess($current_user_data['user_id'], $last_inserted_id, 'addnew');
        $outputcontent = '<h1 class="text-success">Record added successfully.</h1>';
    
    } else {
        $outputcontent = '<h1 class="text-danger">Error adding record.</h1>';
    }