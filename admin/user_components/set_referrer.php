<?PHP


#-------------------------------------------------------------------------------
# HANDLE REFERER ACTION
#-------------------------------------------------------------------------------
if ($app->formposted()  && isset($_REQUEST['referer_id'])  && isset($_POST['referer_action'])) {
    $userId = $workingUser;
    $refererId = $qik->decodeId($_REQUEST['referer_id']);
    
    
    $refererbuttontitle='Add Referer';
    switch ($_REQUEST['referer_action']) {
    

    case 'addreferer':
    $sql = "INSERT INTO `bg_user_attributes` (`attribute_id`, `user_id`, `type`, `name`, `description`, `status`, `create_dt`, `modify_dt`, `rank`, `value`, `grouping`, `category`, `start_dt`, `end_dt`) 
    VALUES (NULL, :referer_id,  'referred', :user_id, :accountplan, 'pending', now(), now(), '3', NULL, NULL, NULL, NULL, NULL)";
    
    $params = [
    ':user_id' => $userId,
    ':referer_id' => $refererId,
    ':accountplan' => $workinguserdata['account_plan'],
    ];
    break;
    

    case 'changereferer':    
    $sql = "UPDATE `bg_user_attributes` 
    SET user_id = :referer_id, `modify_dt` = :modify_dt 
    WHERE `name` = :user_id AND `type` = 'referred'";
    
    $params = [
    ':user_id' => $userId,
    ':referer_id' => $refererId,
    ':modify_dt' => $currentDateTime
    ];
    break;    
    }


    $stmt = $database->prepare($sql);
    if ($stmt->execute($params)) {
    // Success message or redirect
   
    } else {
    // Error message
    echo "Error: Unable to update referer.";
    }
    }


$referrer = $_SERVER['HTTP_REFERER'] ?? '/myaccount/';
header('Location: ' . $referrer);