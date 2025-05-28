<?php
#ini_set('display_errors', 0);
#ini_set('display_startup_errors', 0);
#ini_set('log_errors', 1);
class AccessManager
{
    private $db; // Database connection
    private $encryptMethod = 'AES-256-CBC'; // Encryption method
    private $secretKey; // Secret key for encryption and decryption
    private $secretIv; // Secret IV for encryption and decryption
    private $keyarraylist; // Array of keys and IVs
    private $keyiv_selector; // Array of keys and IVs


    public function __construct($database, $keyarraylist, $keyiv_selector_in)
    {
        $this->db = $database;
        $this->keyarraylist = $keyarraylist; // Initialize keyarraylist as a class property
        $this->keyiv_selector = $keyiv_selector_in; /// default kipath
        list($key_id, $iv_id) = explode('/', $keyiv_selector_in);
        $this->secretKey = $keyarraylist[$key_id];
        $this->secretIv = $keyarraylist[$iv_id];
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function isLocked()
    {
        // Assuming you have a settings or config table where you can store the lock state
        $stmt = $this->db->prepare("SELECT value FROM am_config WHERE name = 'encryption_lock'");
        $stmt->execute();
        $lock = $stmt->fetchColumn();

        return $lock === '1'; // '1' means locked, '0' means not locked
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Encrypt data
    public function encrypt($data, $checklock = true, $p_key = '', $p_iv = '')
    {
        if ($checklock) {
            if ($this->isLocked()) {
                throw new Exception("Encryption operations are currently locked due to re-encryption.");
            }
        }

        if ($p_key == '') {
            $p_key = $this->secretKey;
        }

        if ($p_iv == '') {
            $p_iv = $this->secretIv;
        }

        $output = false;
        $key = hash('sha256', $p_key);
        $iv = substr(hash('sha256', $p_iv), 0, 16);
        $output = openssl_encrypt($data, $this->encryptMethod, $key, 0, $iv);
        return base64_encode($output);
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Decrypt data
    public function decrypt($data, $checklock = true)
    {
        if ($checklock) {
            if ($this->isLocked()) {
                throw new Exception("Decryption operations are currently locked due to re-encryption.");
            }
        }
        $output = false;
        $key = hash('sha256', $this->secretKey);
        $iv = substr(hash('sha256', $this->secretIv), 0, 16);
        $output = openssl_decrypt(base64_decode($data), $this->encryptMethod, $key, 0, $iv);
        return $output;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function create_record($input = [])
    {
        $DEBUG = true;
        ///////////////////////////////////////////////////////////////////////////
        // ADD THE NEW DATA ELEMENT - form was submitted with the "addnew" action

        // Extract and sanitize form values
        $encryptedValue = !empty($input['password']) ? $input['password'] : (!empty($input['value']) ? $input['value'] : (!empty($input['payload']) ? $input['payload'] : (!empty($input['encryptedValue']) ? $input['encryptedValue'] : '')));

        $input['strength'] = !empty($input['strength']) ? $input['strength'] : (!empty($this->checkPassword($encryptedValue)) ? $this->checkPassword($encryptedValue) : '');

        $input['notes'] = !empty($input['notes']) ? $input['notes'] : (!empty($input['registration_detail']) ? $input['registration_detail'] : '');
        if (is_array($input['notes'])) $input['notes'] = json_encode($input['notes']);
        $kipath = $this->generateKipath();

        $params = [
            ':user_id' => !empty($input['user_id']) ? $input['user_id'] : '',
            ':company_id' => isset($input['company_id']) ? $input['company_id'] : '-1',
            ':type' => ($input['type'] ??  $input['data_type'] ??  'username_password'),
            ':name' => htmlspecialchars(!empty($input['name']) ? $input['name'] : '', ENT_QUOTES, 'UTF-8'),
            ':host' => !empty($input['host']) ? $input['host'] : (!empty($input['signup_url']) ? $input['signup_url'] : ''),
            ':host_link_type' => !empty($input['host_link_type']) ? $input['host_link_type'] : 'url',
            ':kipath' => $kipath,
            ':category' => !empty($input['category']) ? $input['category'] : '',
            ':grouping' => !empty($input['grouping']) ? $input['grouping'] : '',
            ':encryptedName' => $this->encrypt_wki(!empty($input['username']) ? $input['username'] : (!empty($input['title']) ? $input['title'] : (!empty($input['encryptedName']) ? $input['encryptedName'] : '')), $kipath),
            ':encryptedValue' => $this->encrypt_wki($encryptedValue, $kipath),
            ':password_strength' => json_encode($input['strength']),
            ':notes' => $this->encrypt_wki(!empty($input['notes']) ? $input['notes'] : (!empty($input['registration_detail']) ? $input['registration_detail'] : ''), $kipath),
            ':dataType' => ($input['datatype'] ?? $input['dataType']  ?? 'username_password'),
            ':create_dt' => !empty($input['create_dt']) ? $input['create_dt'] : null,
            ':modify_dt' => !empty($input['modify_dt']) ? $input['modify_dt'] : null,

        ];


        if ($DEBUG)    session_tracking("AM-PARAMS", json_encode($params), '__NOREQUESTDATA__');
        $sql_insert = "INSERT INTO am_datastore (user_id, company_id, type, name, host, host_link_type, kipath, category, `grouping`, encrypted_name, encrypted_value, password_strength, notes, data_type, create_dt, modify_dt) 
    VALUES (:user_id, :company_id, :type, :name, :host, :host_link_type, :kipath, :category, :grouping, :encryptedName, :encryptedValue, :password_strength, :notes, :dataType, COALESCE(:create_dt, NOW()), COALESCE(:modify_dt, NOW())  )";

        $stmt_insert = $this->db->prepare($sql_insert);
        if ($DEBUG)  session_tracking("AM-SQL", $stmt_insert->queryString, '__NOREQUESTDATA__');

        if ($DEBUG)  session_tracking("AM-PREPARE", $sql_insert . '/////' . json_encode($params), '__NOREQUESTDATA__');
        $stmt_insert->execute($params);

        // Execute the statement and check for success
        if ($stmt_insert->rowCount() > 0) {
            global $current_user_data;
            $last_inserted_id = $this->db->lastInsertId();
            if ($DEBUG)    session_tracking("AM-COMPLETED ID", $last_inserted_id, '__NOREQUESTDATA__');
            #   breakpoint("HELLO". $last_inserted_id);
            if ($input['grouping'] == '__SYSTEMRESTRICTED__') {
                $userId = !empty($current_user_data['user_id']) ? $current_user_data['user_id'] : (!empty($input['creator_id']) ? $input['creator_id'] : '-1');
                $this->logAccess($userId, $last_inserted_id, 'addnew');
                $outputcontent = '<h1 class="text-success">Record added successfully.</h1>';
            }
            #echo $last_inserted_id;
            return $last_inserted_id;
        } else {
            if ($DEBUG)    session_tracking("AM-BAD", '__NOREQUESTDATA__');
            #     breakpoint("BYE");

            $outputcontent = '<h1 class="text-danger">Error adding record.</h1>';
            return false;
        }
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // NEW Encrypt data with KIPATH
    public function encrypt_wki($data, $kipath = '',  $checklock = true)
    {
        if ($checklock) {
            if ($this->isLocked()) {
                throw new Exception("Encryption operations are currently locked due to re-encryption.");
            }
        }
        if ($kipath == '') $kipath = $this->keyiv_selector;
        list($kipath_key, $kipath_iv) = explode('/', $kipath);

        $keyarraylist = $this->keyarraylist;
        $p_iv_offset = 0;
        $p_key = $keyarraylist[$kipath_key];

        if (strpos($kipath_iv, '.') !== false) {
            list($kipath_iv,  $p_iv_offset) = explode('.', $kipath_iv);
        }
        $p_iv = $keyarraylist[$kipath_iv];



        $output = false;
        $key = hash('sha256', $p_key);
        $iv = substr(hash('sha256', $p_iv), $p_iv_offset, 16);
        $output = openssl_encrypt($data, $this->encryptMethod, $key, 0, $iv);
        return base64_encode($output);
    }


    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // NEW Decrypt data with KIPATH
    public function decrypt_wki($data, $kipath = '', $checklock = true)
    {
        if ($checklock) {
            if ($this->isLocked()) {
                throw new Exception("Decryption operations are currently locked due to re-encryption.");
            }
        }

        // Split the $kipath to extract key and IV identifiers, and possible IV offset
        list($kipath_key, $kipath_iv_with_offset) = explode('/', $kipath);
        $p_iv_offset = 0; // Default IV offset is 0

        // Check if IV contains an offset and split it if necessary
        if (strpos($kipath_iv_with_offset, '.') !== false) {
            list($kipath_iv, $p_iv_offset) = explode('.', $kipath_iv_with_offset);
        } else {
            $kipath_iv = $kipath_iv_with_offset;
        }

        // Retrieve the actual key and IV values from $this->keyarraylist using the identifiers
        $p_key = $this->keyarraylist[$kipath_key];
        $p_iv = $this->keyarraylist[$kipath_iv];

        // Apply the IV offset directly to grab the specific portion of the IV, ensuring it's 16 bytes long
        #$iv = substr($p_iv, $p_iv_offset, 16); 
        $iv = substr(hash('sha256', $p_iv), $p_iv_offset, 16);
        // Use the retrieved key and the adjusted IV for decryption
        $key = hash('sha256', $p_key); // Hash the key to ensure it's the correct length for the encryption method

        # breakpoint($iv);
        // Perform the decryption
        $output = openssl_decrypt(base64_decode($data), $this->encryptMethod, $key, 0, $iv);

        return $output;
    }



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
// Define the function to retrieve and decrypt the encrypted_value
function getDecryptedValue($id) {
    global $am_default_kidirpath;
    
    // Prepare the SQL query
    $sql = 'SELECT d.id,
    IFNULL(d.data_type, "") AS `data_type`,
    IFNULL(d.kipath, "'.$am_default_kidirpath.'") AS `kipath`,
    IFNULL(d.encrypted_value, "") AS `encrypted_value_raw`
    FROM am_datastore d 
    LEFT JOIN am_types t1 ON (d.type = t1.type and t1.category="category")
    LEFT JOIN am_types t2 ON (d.data_type = t2.type and t2.category="data_type")
    WHERE d.id=:id LIMIT 1';

    // Prepare the statement
    $stmt =  $this->db->prepare($sql);

    // Bind the parameter
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    // Execute the statement
    $stmt->execute();

    // Fetch the result
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        // Decrypt the encrypted values
     //   $row['encrypted_name'] = $accessmanager->decrypt_wki($row['encrypted_name_raw'], $row['kipath']);
        $row['encrypted_value'] = $this->decrypt_wki($row['encrypted_value_raw'], $row['kipath']);
        return $row['encrypted_value'];
    } else {
        return null; // Return null if no record is found
    }
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
// Define the function to retrieve and decrypt the encrypted_value
function getDecryptedRecord($id) {
    global $am_default_kidirpath;
    
    // Prepare the SQL query
    $sql = 'SELECT d.id,
    IFNULL(d.data_type, "") AS `data_type`,
    IFNULL(d.kipath, "'.$am_default_kidirpath.'") AS `kipath`,
    IFNULL(d.encrypted_name, "") AS `encrypted_value_name`
    IFNULL(d.encrypted_value, "") AS `encrypted_value_raw`
    FROM am_datastore d 
    LEFT JOIN am_types t1 ON (d.type = t1.type and t1.category="category")
    LEFT JOIN am_types t2 ON (d.data_type = t2.type and t2.category="data_type")
    WHERE d.id=:id LIMIT 1';

    // Prepare the statement
    $stmt =  $this->db->prepare($sql);

    // Bind the parameter
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    // Execute the statement
    $stmt->execute();

    // Fetch the result
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        // Decrypt the encrypted values
     //   $row['encrypted_name'] = $accessmanager->decrypt_wki($row['encrypted_name_raw'], $row['kipath']);
        $row['encrypted_value'] = $this->decrypt_wki($row['encrypted_value_raw'], $row['kipath']);
        return $row['encrypted_value'];
    } else {
        return null; // Return null if no record is found
    }
}



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function generateKipathxx()
    {
        $length = count($this->keyarraylist);

        // Make sure the array is not empty
        if ($length === 0) {
            return; # $this->$keyiv_selector;
        }

        // Generate random indices within the array bounds
        $keyId = rand(0, $length - 1);
        $ivId = rand(0, $length - 1);

        // Use the random indices to select $newKey and $newIv
        $newKey = $this->keyarraylist[$keyId];
        $newIv = $this->keyarraylist[$ivId];
        $maxoffset = max(0, strlen($newIv) - 17);
        $kipath = $newKey . '/' . $newIv . '.' . rand(0, $maxoffset);

        return $kipath;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function getStrength($value)
    {
        $strengthResult = json_decode($value, true);

        // Initialize an array to hold the strength information with default values
        $strengthInfo = [
            'scale' => 0, // Default value for 'scale'
            'color' => 'secondary', // Default value for 'color'
            'num' => 0 // Default value for 'num'
        ];

        // Check if decoding was successful and the required keys exist
        if ($strengthResult !== null && isset($strengthResult['scale'], $strengthResult['color'])) {
            // Extract the 'scale' value and update it if it exists
            $strengthInfo['scale'] = $strengthResult['scale'];

            // Extract the 'color' value and update it if it exists
            $strengthInfo['color'] = $strengthResult['color'];

            // Extract the 'num' value and update it if it exists
            $strengthInfo['num'] = isset($strengthResult['num']) ? $strengthResult['num'] : 0;
        }

        return $strengthInfo;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function generateKipath()
    {
        $keys = array_keys($this->keyarraylist); // Get all the keys (which includes IVs)
        $keyCount = count($keys);
        if ($keyCount < 2) { // Ensure there are at least two keys for key and IV
            throw new Exception("Insufficient keys in the keyarraylist");
        }

        // Randomly select two different keys for the new key and IV
        do {
            $newKeyIndex = rand(0, $keyCount - 1);
            $newIvIndex = rand(0, $keyCount - 1);
        } while ($newKeyIndex == $newIvIndex); // Ensure that key and IV are not the same

        $newKey = $keys[$newKeyIndex];
        $newIv = $keys[$newIvIndex];

        // Assuming $maxoffset is defined elsewhere in your class. If not, you can define it here.
        $maxoffset = max(0, strlen($this->keyarraylist[$newIv]) - 17);
        $randomOffset = rand(0, $maxoffset);

        // Construct the new kipath
        $kipath = $newKey . '/' . $newIv . '.' . $randomOffset;

        return $kipath;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function reEncryptAll($keyiv_selector)
    {
        // Extract the new key and IV identifiers from the $keyiv_selector
        list($newKey_id, $newIv_id) = explode('/', $keyiv_selector);
        $newKey = $this->keyarraylist[$newKey_id];
        $newIv = $this->keyarraylist[$newIv_id];

        // Set the lock
        $stmt = $this->db->prepare("UPDATE am_config SET value = '1', modify_dt=now() WHERE name = 'encryption_lock'");
        $stmt->execute();


        // Fetch all encrypted data
        $stmt = $this->db->prepare("SELECT id, encrypted_name, encrypted_value , kipath FROM am_datastore");
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            // Decrypt each value with the old key and IV
            #$oldDecrypted_name = openssl_decrypt(base64_decode($row['encrypted_data']), $this->encryptMethod, hash('sha256', $this->secretKey), 0, substr(hash('sha256', $this->secretIv), 0, 16));
            #$oldDecrypted_value = openssl_decrypt(base64_decode($row['encrypted_data']), $this->encryptMethod, hash('sha256', $this->secretKey), 0, substr(hash('sha256', $this->secretIv), 0, 16));

            // Attempt to decrypt the name and value
            $oldDecrypted_name = $this->decrypt_wki($row['encrypted_name'], $row['kipath'], false);
            $oldDecrypted_value = $this->decrypt_wki($row['encrypted_value'], $row['kipath'], false);

            // Check if decryption was unsuccessful for the name
            if ($oldDecrypted_name === false || $oldDecrypted_name === '') {
                // Decryption failed or the value was not encrypted, use the original value
                $oldDecrypted_name = $row['encrypted_name'];
            }

            // Check if decryption was unsuccessful for the value
            if ($oldDecrypted_value === false || $oldDecrypted_value === '') {
                // Decryption failed or the value was not encrypted, use the original value
                $oldDecrypted_value = $row['encrypted_value'];
            }

            $kipath = $this->generateKipath();

            // Re-encrypt using the new key and IV
            #  $newEncrypted_name = base64_encode(openssl_encrypt($oldDecrypted_name, $this->encryptMethod, hash('sha256', $newKey), 0, substr(hash('sha256', $newIv), 0, 16)));
            # $newEncrypted_value = base64_encode(openssl_encrypt($oldDecrypted_value, $this->encryptMethod, hash('sha256', $newKey), 0, substr(hash('sha256', $newIv), 0, 16)));
            $newEncrypted_name = $this->encrypt_wki($oldDecrypted_name, $kipath, false);
            $newEncrypted_value = $this->encrypt_wki($oldDecrypted_value, $kipath, false);

            $strength = $this->checkPassword($oldDecrypted_value);

            // Update the database with the new encrypted value
            $updateStmt = $this->db->prepare("UPDATE am_datastore SET kipath=:kipath,  encrypted_name = :newEncrypted_name , encrypted_value = :newEncrypted_value, password_strength=:strength, modify_dt=now() WHERE id = :id");
            $updateStmt->execute([':kipath' => $kipath,    ':newEncrypted_name' => $newEncrypted_name, ':newEncrypted_value' => $newEncrypted_value, ':strength' => json_encode($strength), ':id' => $row['id']]);
            echo 'updated: ' . implode('///', $row) . '<br>';
        }

        // Update the class properties to the new key and IV
        $this->secretKey = $newKey;
        $this->secretIv = $newIv;

        // Release the lock

        $stmt = $this->db->prepare("UPDATE am_config SET value = :path, modify_dt=now()  WHERE name = 'path'");
        $stmt->execute([':path' => $keyiv_selector]);

        $stmt = $this->db->prepare("UPDATE am_config SET value = '0', modify_dt=now() WHERE name = 'encryption_lock'");
        $stmt->execute();
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function getAccessibleEncryptedData($userId, $pdo)
    {
        $sql = "SELECT ed.id, ed.name, ed.description, ed.category, ed.grouping, ed.encrypted_value
            FROM encrypted_data ed
            LEFT JOIN acl_users au ON ed.id = au.encrypted_data_id AND au.user_id = :userId
            LEFT JOIN user_group_relations ugr ON ugr.user_id = :userId
            LEFT JOIN acl_groups ag ON ed.id = ag.encrypted_data_id AND ag.group_id = ugr.group_id
            WHERE (au.access_granted = TRUE OR ag.access_granted = TRUE)
            GROUP BY ed.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        $results = $stmt->fetchAll();

        return $results;
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function logAccess($userId, $dataId, $action = 'view')
    {
        // Prepare the SQL statement
        $sql = "INSERT INTO am_accesshistory (user_id, data_id, action, create_dt) VALUES (:user_id, :data_id, :action, NOW())";

        $stmt =  $this->db->prepare($sql);

        // Bind values to parameters
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':data_id', $dataId, PDO::PARAM_INT);
        $stmt->bindValue(':action', $action);

        // Execute the statement
        if ($stmt->execute()) {
            // Success
            return "Access logged successfully.";
        } else {
            // Error
            return false;
        }
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Hash a password
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Verify a password against a hash
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    // Update a user's password
    public function updatePassword($userId, $newPassword)
    {
        $newHash = $this->hashPassword($newPassword);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = :newHash WHERE id = :userId");
        $stmt->execute(['newHash' => $newHash, 'userId' => $userId]);

        return $stmt->rowCount() > 0;
    }

    // Additional methods related to access management...



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * @package         PHP-Lib
     * @description     Class is used to check the complexity of password
     * @author          Peeyush Budhia <peeyush.budhia@phpnmysql.com>
     * @license         GNU GPL v2.0
     */

    /**
     * @author          Peeyush Budhia <peeyush.budhia@phpnmysql.com>
     * @description     The function is used to check the complexity of password based on the different criteria like password includes Mixed char case/digits/special characters.
     *
     * @param $password Use to set the password to check the complexity
     *
     * @return mixed    The strength of password
     */

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public   function checkPassword($password)
    {

        $strength = [
            ['word' => 'Excellent', 'num' => 0, 'scale' => 90, 'color' => 'success'], // High strength - Green
            ['word' => 'Strong', 'num' => 1, 'scale' => 70, 'color' => 'success'], // High strength - Green
            ['word' => 'Good', 'num' => 2, 'scale' => 50, 'color' => 'warning'], // Medium strength - Yellow
            ['word' => 'Weak', 'num' => 3, 'scale' => 10, 'color' => 'danger'] // Low strength - Red
        ];


        if ($this->isEnoughLength($password, 12) && $this->containsMixedCase($password) && $this->containsDigits($password) && $this->containsSpecialChars($password)) {
            return $strength[0];
        } elseif ($this->isEnoughLength($password, 10) && $this->containsMixedCase($password) && $this->containsDigits($password)) {
            return $strength[1];
        } elseif ($this->isEnoughLength($password, 8) && $this->containsMixedCase($password)) {
            return $strength[2];
        } elseif ($this->isEnoughLength($password, 8) && $this->containsDigits($password)) {
            return $strength[2];
        } elseif ($this->isEnoughLength($password, 8) && $this->containsSpecialChars($password)) {
            return $strength[2];
        } else {
            return $strength[3];
        }
    }

    /**
     * @author          Peeyush Budhia <peeyush.budhia@phpnmysql.com>
     * @description     The function is used to check the length of password
     *
     * @param $password Use to set the password to check
     * @param $length   Use to set the length of password
     *
     * @return bool     "true" if the password is not empty or length matches the criteria
     *                  "false" if the password is empty and does not matches the criteria
     */
    private function isEnoughLength($password, $length)
    {
        if (empty($password)) {
            return false;
        } elseif (strlen($password) < $length) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @author          Peeyush Budhia <peeyush.budhia@phpnmysql.com>
     * @description     The function is used to check the mixed case of password
     *
     * @param $password Use to set the password to check
     *
     * @return bool "true" if password contains mixed case characters
     *              "false" if password does not contains mixed case characters
     */
    private function containsMixedCase($password)
    {
        if (preg_match('/[a-z]+/', $password) && preg_match('/[A-Z]+/', $password)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @author          Peeyush Budhia <peeyush.budhia@phpnmysql.com>
     * @description     The function is used to check the digits are included in password or not
     *
     * @param $password Use to set the password to check
     *
     * @return bool "true" if password contains digits
     *              "false" if password does not contains digits
     */
    private function containsDigits($password)
    {
        if (preg_match("/\d/", $password)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @author          Peeyush Budhia <peeyush.budhia@phpnmysql.com>
     * @description     The function is used to check the special characters are included in password or not
     *
     * @param $password Use to set the password to check
     *
     * @return bool "true" if password contains special characters
     *              "false" if password does not contains special characters
     */
    private function containsSpecialChars($password)
    {
        if (preg_match("/[^\da-z]/", $password)) {
            return true;
        } else {
            return false;
        }
    }
}
