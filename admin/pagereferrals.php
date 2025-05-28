<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$sql = "SELECT 
bg_sessiontracking.page, 
bg_sessiontracking.site 
FROM bg_sessiontracking WHERE user_id = " . $workinguserdata['user_id'] . " ";

if ($mode != 'dev') {
     $sql .=  " and bg_sessiontracking.site ='www' "; 
} else {
     $sql .= " and `type`='user' ";}

$sql .= " ORDER BY create_dt DESC LIMIT 0, 100";
// Prepare the statement
$stmt = $database->prepare($sql);


$stmt->execute();

$logrows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($logrows) {

    echo '<div class="card">
    <div class="card-header">
    <h5 class="mb-0">Logs</h5>
    </div>
    <div class="card-body border-top p-0">';
    
foreach($logrows as $logrow) {

// Check if the IP address has been mapped to a color, if not, assign the next color
if (!isset($ipColorMap[$logrow['ip']])) {
$ipColorMap[$logrow['ip']] = $badgeColors[$colorIndex];
$colorIndex = ($colorIndex + 1) % count($badgeColors); // Move to the next color, cycle back to the first color if at the end
}

}
}