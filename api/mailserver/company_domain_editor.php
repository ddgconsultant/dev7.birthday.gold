<?php
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 



#-------------------------------------------------------------------------------
# PROCESS LOGIN ATTEMPT
#-------------------------------------------------------------------------------

if ( $app->formposted()) {    
// Function to insert data into `bg_company_attributes` table
function insertCompanyAttribute($database, $company_id, $email_domain) {
    $query = "INSERT INTO `bg_company_attributes` (`company_id`, `type`, `description`, `status`, `create_dt`, modify_dt)
              VALUES (:company_id, 'email_domain', :description, 'active', NOW(), NOW())";    
    $stmt = $database->prepare($query);
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindParam(':description', $email_domain, PDO::PARAM_STR);    
    return $stmt->execute();
}

$database_mail = new Database($sitesettings['database_mail']);
$search=array("'", '"',);
$replace=array( "\'", '\"');


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'company') === 0) {
            $company_id = $_POST[$key];
            $email_domain = $_POST['email_domain'];

            // Insert data into `bg_company_attributes` table
            if (insertCompanyAttribute($database, $company_id, $email_domain)) {
                // Update the matching senders in the `messages` table
                $updateQuery = "UPDATE `messages` SET `company_id` = :company_id WHERE `sender` like '%".str_replace($search, $replace, $email_domain)."%'";
                $updateStmt = $database_mail->prepare($updateQuery);
                $updateStmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
                $updateStmt->execute();
            }
        }
    }
}


}












#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------


// Query companies from Server B
$queryB = "SELECT company_id, company_name FROM bg_companies WHERE `status` !='duplicate' ORDER BY company_name"; // Replace with your actual table and column names
$stmt = $database->prepare($queryB);
$stmt->execute();
$listofcompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$database_mail = new Database($sitesettings['database_mail']);

// Query email domain details from Server A
$queryA = "SELECT distinct sender FROM messages WHERE company_id=99 LIMIT 25";
$stmt = $database_mail->prepare($queryA);
$stmt->execute();
$emailDomains = $stmt->fetchAll(PDO::FETCH_COLUMN);
$i = 0;


$displayform=true;
if (isset($_GET['monitor']) && $_GET['monitor']) {
    if (count($emailDomains)==0)     echo '<span aria-label="'.count($emailDomains).' unaccounted for domains">'.count($emailDomains).' unaccounted for domains</span>';
    else
    echo 'PLEASE CLEAR '.count($emailDomains).' UNACCOUNTED FOR DOMAINS';
    $displayform=false;
}
if ($displayform){
    $headerattribute['additionalcss']='';
include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/header.php'); 



    echo '
<style>
/* Add this CSS to your stylesheet */
.hover-highlight:hover {
    background-color: #f5f5f5; /* Change the background color to the desired highlight color */
    cursor: pointer; /* Change the cursor to indicate interactivity */
}
</style>
';

echo '<div class="container mt-5   flex-grow-1 ">';
echo '<h1>Company Email Domain Editor</h1>';

    echo '<span aria-label="'.count($emailDomains).' unaccounted for domains">'.count($emailDomains).' unaccounted for domains</span>';
echo '<hr>';

foreach ($emailDomains as $domain) {
    $i++;
    echo '<div class="row  hover-highlight">';
    echo '<div class="col-6">';
    echo '<form method="post">';
 echo $display->inputcsrf_token(); 
    echo '<label for="company'.$i.'" class="me-2">Sender: </label>';
    echo '<input type="text" class="col-10" name="email_domain" value="' . htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') . '">';
    echo '</div>';
    echo '<div class="col-5">Company: ';
    echo '<select name="company'.$i.'">';
    $closestCompany = null;
    $closestDistance = PHP_INT_MAX;

    foreach ($listofcompanies as $row) {
        $domainTokens = preg_split("/[\s@.]+/", $domain);
        $distance = PHP_INT_MAX;

        // Calculate the minimum distance for each token
        foreach ($domainTokens as $token) {
            $tokenDistance = levenshtein(strtolower($token), strtolower($row['company_name']));
            $distance = min($distance, $tokenDistance);
        }

        // Update the closest match if this distance is smaller
        if ($distance < $closestDistance) {
            $closestDistance = $distance;
            $closestCompany = $row;
        }

        // Check if this option should be selected
        $selected = $row['company_id'] === $closestCompany['company_id'] ? 'selected' : '';

        echo '<option value="' . $row['company_id'] . '" ' . $selected . '>' . $row['company_name'] . '</option>';
    }
    echo '</select>';
    echo '</div>';
    echo '<div class="col-1">';

    echo '<input type="submit" value="Assign">';
    echo '</form>';
    echo '</div>';
    echo '</div>';
}
echo '</div>';

include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footer.php');
}

?>
