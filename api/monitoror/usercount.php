<?php
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 



#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
if (isset($_GET['today'])) {
$queryB = "SELECT `status`, count(*) as count from bg_users where date(create_dt)=CURDATE() group by `status`"; 

$queryB="SELECT
all_statuses.status,
COALESCE(today_counts.count, 0) AS count
FROM
(SELECT DISTINCT status FROM bg_users WHERE create_dt <= CURDATE()) AS all_statuses
LEFT JOIN
(SELECT status, COUNT(*) AS count 
 FROM bg_users 
 WHERE DATE(create_dt) = CURDATE() 
 GROUP BY status) AS today_counts
ON all_statuses.status = today_counts.status;
";
} else {
$queryB = "SELECT `status`, count(*) as count from bg_users group by `status`"; 
}
$stmt = $database->prepare($queryB);
$stmt->execute();
#$listofcompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Fetch results into an associative array
$results = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $results[$row['status']] = (int) $row['count'];
}

// Wrap results in another array under a single key
$wrappedResults = ['userCounts' => $results];

// Convert the results to JSON
$json = json_encode($wrappedResults);

// Output the JSON
echo $json;
