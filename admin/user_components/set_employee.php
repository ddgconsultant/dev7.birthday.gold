<?PHP

$addClasses[] = 'Mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
$skip = false;
if (!$account->isadmin()) {
    $skip = true;
}


if ($app->formposted()) {

    if (empty($_REQUEST['u']) || !isset($_REQUEST['employee_action'])) {
        $skip = true;
    }
}


if (!$skip) {

    #  $userId = $workingUser;
    $userId = $qik->decodeId($_REQUEST['u']);
    $currentDateTime = date('Y-m-d H:i:s');


    // Convert hire_date to MySQL safe date format using strtotime
    $timestamp = strtotime($_POST['hire_date']);

    if ($timestamp !== false) {
        $mysqlHireDate = date('Y-m-d', $timestamp);
    } else {
        // Handle the error appropriately
        echo 'Invalid hire date format. Please use a recognizable date format.<br>';

        // Optionally log the error
        error_log('Invalid hire date format: ' . $_POST['hire_date']);
        // Set a default value or take other appropriate action
        $mysqlHireDate = '0000-00-00'; // Example of a default value
    }

    $attributes = [
        'staff' => ['type' => 'staff', 'value' => 'staff'],
        'department' => ['type' => 'employee_onboarding', 'value' => $_POST['department'] ?? ''],
        'title' => ['type' => 'employee_onboarding', 'value' => $_POST['title'] ?? ''],
        'hire_date' => ['type' => 'employee_onboarding', 'value' => $mysqlHireDate ?? ''],
        'employment_status' => ['type' => 'employee_onboarding', 'value' => $_POST['employment_status'] ?? ''],
        'pay' => ['type' => 'employee_onboarding', 'value' => $_POST['pay'] ?? ''],
        'corporate_email' => ['type' => 'employee_onboarding', 'value' => $_POST['corporate_email'] ?? ''],
        'corporate_password' => ['type' => 'employee_onboarding', 'value' => $_POST['corporate_password'] ?? ''],
        'equipment' => ['type' => 'employee_onboarding', 'value' => $_POST['equipment'] ?? ''],
        'manager' => ['type' => 'employee_onboarding', 'value' => $_POST['manager'] ?? ''],
        'work_location' => ['type' => 'employee_onboarding', 'value' => $_POST['work_location'] ?? ''],
        'notes' => ['type' => 'employee_onboarding', 'value' => $_POST['notes'] ?? ''],
    ];



    switch ($_REQUEST['employee_action']) {
        case 'makeemployee':

            // Assuming this is how you bind the params and execute the query
            $sql = "INSERT INTO `bg_user_attributes` (
`user_id`, `type`, `name`, `description`, `status`, `create_dt`, `modify_dt`, `rank`, `value`, `grouping`, `category`, `start_dt`, `end_dt`
) VALUES (
:user_id, :type, :name, :description, 'active', :dt1, :dt2, '100', NULL, NULL, 'bdgold_employee', :startdt, NULL
)";

            foreach ($attributes as $name => $attribute) {
                $type = $attribute['type'];
                $value = $attribute['value'] ?? ''; // Fallback to an empty string if $value is null

                $params = [
                    ':user_id' => $userId,
                    ':name' => $name,
                    ':dt1' => $currentDateTime,
                    ':dt2' => $currentDateTime,
                    ':description' => $value, // This was originally $description, now it's the 'value'
                    ':startdt' => $mysqlHireDate,
                    ':type' => $type, // Add the type to bind
                ];
                $stmt = $database->prepare($sql);
                if (!$stmt->execute($params)) {
                    echo "Error: Unable to insert $name.";
                }
            }
            break;

        case 'terminateemployee':
            $sql = "UPDATE `bg_user_attributes` 
SET  `modify_dt` = :modify_dt, `status` = 'terminated' , end_dt=:enddt
WHERE `name` = :user_id AND `type` in ( 'employee_onboarding', 'staff')";

            $params = [
                ':user_id' => $userId,
                ':referer_id' => $refererId,
                ':modify_dt' => $currentDateTime,
                ':enddt' => $currentDateTime,
            ];

            $stmt = $database->prepare($sql);
            if (!$stmt->execute($params)) {
                echo "Error: Unable to update referer.";
            }
            break;
    }




    // Function to generate a unique code
    function generateCode($length = 8)
    {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, $length));
    }

    // store the code in the database

    $code = generateCode();
    $create_dt = date('Y-m-d H:i:s');
    $type = 'onboarding';
    $name = 'access_code';
    $description = 'Onboarding access code for user';



    $query = "INSERT INTO bg_user_attributes (user_id, type, name, description, status, create_dt, string_value)
VALUES (:user_id, :type, :name, :description, :status, :create_dt, :string_value)";

    $params = [
        ':user_id' => $userId,
        ':type' => $type,
        ':name' => $name,
        ':description' => $description,
        ':status' => $status,
        ':create_dt' => $create_dt,
        ':string_value' => $code
    ];

    $stmt = $database->prepare($query);
    $stmt->execute($params);
}


$referrer = $_SERVER['HTTP_REFERER'] ?? '/myaccount/';
header('Location: ' . $referrer);
