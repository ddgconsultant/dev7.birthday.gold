SELECT cip , max(create_dt) create_dt from  `bg_errors` where (cip!='' and cip not in (
'71.33.250.241',
'71.33.250.254'
)
)
 group by cip having count(*)>30
 order by create_dt, cip


 SELECT DISTINCT
    LEFT(hit, LENGTH(hit) - LENGTH(SUBSTRING_INDEX(hit, '/', -1)) - 1) AS folder_path
FROM
    bg_errors;




    <?php
// Database connection
$servername = "your_server";
$username = "your_username";
$password = "your_password";
$dbname = "your_database";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to get unique folder paths
$sql = "SELECT DISTINCT LEFT(hit, LENGTH(hit) - LENGTH(SUBSTRING_INDEX(hit, '/', -1)) - 1) AS folder_path FROM bg_errors";
$result = $conn->query($sql);

// Prepare the .htaccess content
$htaccessContent = "# Auto-generated .htaccess rules to block specific directories\n";
$htaccessContent .= "RewriteEngine On\n\n";

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $folderPath = trim($row['folder_path'], '/');
        $htaccessContent .= "RewriteRule ^" . preg_quote($folderPath, '/') . "(/|$) /499 [R=302,L]\n";
    }
} else {
    echo "No folder paths found.";
}

$conn->close();

// Write to the .htaccess file
file_put_contents('/path/to/your/.htaccess', $htaccessContent);

echo "The .htaccess file has been updated.";
?>
