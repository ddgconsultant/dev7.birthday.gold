<?PHP


    // Extract and sanitize form values
    $id = $_POST['id'] ?? ''; // Default to an empty string if not set
    $name = $_POST['name'] ?? '';
    $host = $_POST['host'] ?? '';
    $encryptedName = $_POST['username'] ?? '';
    $encryptedValue = $_POST['password'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $dataType = $_POST['data_type'] ?? '';
    // Add other fields as necessary
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $strength=$accessmanager->checkPassword($encryptedValue) ;
    $kipath=$accessmanager->generateKipath();

    // Prepare the SQL statement for updating
    $sql = "UPDATE am_datastore SET modify_dt=now(), type=:type, kipath=:kipath, name=:name, host=:host, encrypted_name=:encryptedName, encrypted_value=:encryptedValue, password_strength=:password_strength, notes=:notes, data_type=:dataType WHERE id=:id";

    $stmt = $database->prepare($sql);

    // Bind values to parameters
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':type', $dataType);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':host', $host);
    $stmt->bindValue(':kipath',$kipath);
    $stmt->bindValue(':password_strength',json_encode($strength));
    $stmt->bindValue(':encryptedName', $accessmanager->encrypt_wki($encryptedName, $kipath));
    $stmt->bindValue(':encryptedValue', $accessmanager->encrypt_wki($encryptedValue, $kipath));
    $stmt->bindValue(':notes', $accessmanager->encrypt_wki($notes, $kipath));
    $stmt->bindValue(':dataType', $dataType);
    // Bind other fields as necessary

    // Execute the statement and check for success
    if ($stmt->execute()) {
        $outputcontent = '<h1 class="text-success">Record updated successfully.</h1>';  
    } else {
        $outputcontent = '<h1 class="text-danger">Error updating record.</h1>';
    }

