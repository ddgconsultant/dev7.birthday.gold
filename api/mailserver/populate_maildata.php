<?PHP
#
$pagemode = 'core';
include('../api_coordinator.php');
#echo 'hello123'; exit;
// Route the request
$path = $_SERVER['PATH_INFO'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

#$mode = 'refresh';
$mode = $_REQUEST['type'] ?? 'refresh_users';



if (strpos($mode, 'refresh') !== false) {
    $usercriteria = ' WHERE modify_dt >= DATE_SUB(NOW(), INTERVAL 2 HOUR)';
    $companycriteria = '  WHERE modify_dt >= DATE_SUB(NOW(), INTERVAL 2 DAY)';
} else {
    $usercriteria =  $companycriteria = '';
}
#$params = [];

switch ($mode) {
        ///=====================================================
    case 'refresh_users':
    case 'populate_users':

        $query = "SELECT 
        user_id, 
        feature_email, 
        status, 
        create_dt, 
        MAX(modify_dt) AS latest_modify_dt
    FROM 
        bvw_bg_mail_users
        " . $usercriteria . "
GROUP BY user_id, feature_email, status, create_dt
    ORDER BY       latest_modify_dt ASC
    ";
      #   breakpoint($query);
        $stmt = $database->prepare($query);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
        break;
        ///=====================================================
    case 'refresh_companies':
    case 'populate_companies':
   
        $query = "select distinct company_id, email_domain, max(`status`) as status, max(create_dt) as create_dt,  MAX(modify_dt) AS latest_modify_dt  from bvw_bg_mail_companies " . $companycriteria . ' group by company_id, email_domain  ORDER BY MAX(modify_dt) ASC ';
      
     #   breakpoint($query);

        $stmt = $database->prepare($query);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
        break;
}


/*
### Use this to update the missing email_domains ###
####################################################
UPDATE messages
JOIN bg_mail_companies ON SUBSTRING_INDEX(messages.sender, '@', -1) = bg_mail_companies.email_domain
SET messages.company_id = bg_mail_companies.company_id
WHERE messages.company_id = 0;

*/