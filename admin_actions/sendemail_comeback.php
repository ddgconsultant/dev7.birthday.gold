<?php 

$addClasses[] = 'Mail';

include ($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php'); 

$sql = 'SELECT
u.user_id, 
u.email, 
u.first_name, 
u.middle_name, 	
u.last_name,
v.validation_minicode, 
v.validation_code, 
v.expire_dt,
u.create_dt
FROM
bg_users AS u
INNER JOIN
bg_validations AS v
ON 
    u.user_id = v.user_id
WHERE
u.`STATUS` = "pending" and u.email LIKE "%ddg.mx" LIMIT 5';

$stmt = $database->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $process_user_data) {
    echo '<hr>'.$process_user_data['user_id'].'/'.$process_user_data['email'].'<br>';
  

// If a validation code is found, set it to the validation_button variable
$link=$website['fullurl'].'/validate-account?t='.$process_user_data['validation_code'];

    $input['validation_button'] =  $mail->emailbutton('Complete Your Registration', $link);
    ;
    $input['first_name'] = $process_user_data['first_name'];
    $input['create_dt']= $process_user_data['create_dt'];
 #   $input['to'] = $process_user_data['first_name'];
 #   $input['to'] = [$process_user_data['email'], $process_user_data['first_name']];
    $input['to'] = $process_user_data['email'];
    $input['templatename'] = 'comeback';


    // Assign $from and $message to $input before sending
    #$input['from'] = $from;
    #$input['message'] = $message;

    // Now proceed to send the email using the sendtemplate method
    $result= $mail->sendtemplate($input);
    print_r($result);
    echo '<hr>';

}

?>
