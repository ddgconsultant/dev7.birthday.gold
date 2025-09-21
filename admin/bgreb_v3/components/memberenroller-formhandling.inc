<?PHP


function handle_add_hint($company_id, $post_data)
{
    global $database;

    // Validate all inputs
    $name = $_REQUEST['hint_name'] ?? '';
    $description = $_REQUEST['hint_description'] ?? '';
    $grouping = $_REQUEST['hint_grouping'] ?? '';
    $rank = $_REQUEST['hint_rank'] ?? 999999;

    // Additional validation
    if (strlen($description) > 1000) {
        session_tracking('hint too long', json_encode(['status' => 'error', 'message' => 'Description too long']));

        return false;
    }

    if ($rank < 0 || $rank > 999999) {
        session_tracking('wrong rank', json_encode(['status' => 'error', 'message' => 'Invalid rank value']));

        return false;
    }


    $sql = "INSERT INTO bg_company_attributes 
(company_id, `type`, name, description, `grouping`, `rank`, `status`, create_dt, modify_dt) 
VALUES (:company_id, 'enroller-hint', :name, :description, :grouping, :rank, 'active', NOW(), NOW())";

    $stmt = $database->prepare($sql);
    $result = $stmt->execute([
        'company_id' => $company_id,
        'name' => $name,
        'description' => $description,
        'grouping' => $grouping,
        'rank' => $rank
    ]);

    #    if (!$result) {
    #       throw new Exception("Database insert failed");
    #    }

    // Log the successful action
    # error_log("Hint added successfully for company $company_id");
    session_tracking('hint recorded',  json_encode(['status' => 'success']));


    return true;
}

function handle_enrollment_success($company_id, $working_user_data, $admin_user_data, $post_data)
{
    global $database;

    try {
        $sql = "UPDATE bg_user_enrollments 
SET `status` = 'success',
modify_dt = CURRENT_TIMESTAMP,
registration_dt = NOW(),
registration_id = :admin_id,
registered_by = :admin_username,
registration_engine = '2.2023.10.2804',
registration_detail=:details
WHERE user_id = :user_id 
AND company_id = :company_id";

        $stmt = $database->prepare($sql);

        // Bind parameters
        $params = [
            ':admin_id' => $admin_user_data['user_id'],
            ':admin_username' => $admin_user_data['username'],
            ':user_id' => $working_user_data['user_id'],
            ':company_id' => $company_id,
            ':details' => $post_data['registration_data']
        ];

        $result = $stmt->execute($params);
        /*
if (!$result) {
throw new Exception("Database update failed");
}

if ($stmt->rowCount() === 0) {
throw new Exception("No eligible enrollment found to update");
}
*/
        #  echo json_encode(['status' => 'success']);

    } catch (Exception $e) {
        #  breakpoint("Error updating enrollment: " . $e->getMessage());
        #  http_response_code(500);
        session_tracking('Error updating enrollment-success', ['status' => 'error', 'message' => 'Failed to update enrollment', 'sql' => $sql]);
        return false;
    }
    return true;
}

function handle_enrollment_failure($company_id, $working_user_data, $admin_user_data, $reason='', $post_data)
{
    global $database;


    try {
        $sql = "UPDATE bg_user_enrollments 
SET `status` = 'failed',
reason = :reason,
modify_dt = CURRENT_TIMESTAMP,
registration_dt = NOW(),
registration_id = :admin_id,
registered_by = :admin_username,
registration_engine = '2.2023.10.2804',
registration_detail=:details
WHERE user_id = :user_id 
AND company_id = :company_id";

        $stmt = $database->prepare($sql);

        // Ensure registration_data exists and is an array before encoding
        $registration_details = isset($post_data['registration_data']) ?
            json_encode($post_data['registration_data']) :
            json_encode([]);

        // Bind parameters
        $params = [
            ':reason' => $reason,
            ':admin_id' => $admin_user_data['user_id'],
            ':admin_username' => $admin_user_data['username'],
            ':user_id' => $working_user_data['user_id'],
            ':company_id' => $company_id,
            ':details' => $registration_details
        ];

        $stmt->execute($params);


        /*
if (!$result) {
throw new Exception("Database update failed");
}

if ($stmt->rowCount() === 0) {
throw new Exception("No eligible enrollment found to update");
}
*/
        session_tracking('enrollment failed recorded',  json_encode(['status' => 'success']));
    } catch (Exception $e) {
        #  error_log("Error updating enrollment: " . $e->getMessage());
        # # http_response_code(500);
        # echo json_encode(['status' => 'error', 'message' => 'Failed to update enrollment']);
        session_tracking('Error updating enrollment-failed',  ['status' => 'error', 'message' => 'Failed to update enrollment', 'sql' => $sql]);
        return false;
    }
    #   exit;
    return true;
}
