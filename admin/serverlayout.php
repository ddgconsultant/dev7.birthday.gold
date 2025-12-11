<?PHP
$securityoverride_referrer='https://bd.gold/';

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Handle save action
$saveMessage = '';
$savedSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    if (isset($_POST['content'])) {
        $newContent = $_POST['content'];
        // Normalize line endings to Unix style
        $newContent = str_replace("\r\n", "\n", $newContent);
        $newContent = str_replace("\r", "\n", $newContent);

        if (file_put_contents(__DIR__ . '/serverlayout.txt', $newContent) !== false) {
            // Redirect to view mode after successful save
            header('Location: ?saved=1');
            exit;
        } else {
            $saveMessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                Error saving file!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        }
    }
}

// Show success message after redirect
if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $saveMessage = '<div class="alert alert-success alert-dismissible fade show" role="alert">
        File saved successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

// Check if edit mode is requested
$editMode = isset($_GET['edit']) && $_GET['edit'] === '1';

// Read the contents of the file
$fileContents = file_get_contents(__DIR__ . '/serverlayout.txt');

// Escape special HTML characters to prevent XSS attacks
$escapedContents = htmlspecialchars($fileContents);

// Show save message if any
if ($editMode) {
    // Full width layout for edit mode
    echo '<div class="main-content" style="width: 100%; max-width: 100%; padding: 20px;">';
    echo $saveMessage;
} else {
    echo '<div class="main-content fluid-container ">
    <div class="d-flex justify-content-center">
    <div class="fluid-container">';
    echo $saveMessage;
}


$search=['@startuml', '@enduml'];
$umlCode=trim(str_replace($search, '', $fileContents));


function encodep($text) {
$data = utf8_encode($text);
$compressed = gzdeflate($data, 9);
return encode64($compressed);
}

function encode6bit($b) {
if ($b < 10) {
return chr(48 + $b);
}
$b -= 10;
if ($b < 26) {
return chr(65 + $b);
}
$b -= 26;
if ($b < 26) {
return chr(97 + $b);
}
$b -= 26;
if ($b == 0) {
return '-';
}
if ($b == 1) {
return '_';
}
return '?';
}

function append3bytes($b1, $b2, $b3) {
$c1 = $b1 >> 2;
$c2 = (($b1 & 0x3) << 4) | ($b2 >> 4);
$c3 = (($b2 & 0xF) << 2) | ($b3 >> 6);
$c4 = $b3 & 0x3F;
$r = "";
$r .= encode6bit($c1 & 0x3F);
$r .= encode6bit($c2 & 0x3F);
$r .= encode6bit($c3 & 0x3F);
$r .= encode6bit($c4 & 0x3F);
return $r;
}

function encode64($c) {
$str = "";
$len = strlen($c);
for ($i = 0; $i < $len; $i+=3) {
if ($i+2==$len) {
$str .= append3bytes(ord(substr($c, $i, 1)), ord(substr($c, $i+1, 1)), 0);
} else if ($i+1==$len) {
$str .= append3bytes(ord(substr($c, $i, 1)), 0, 0);
} else {
$str .= append3bytes(ord(substr($c, $i, 1)), ord(substr($c, $i+1, 1)),
ord(substr($c, $i+2, 1)));
}
}
return $str;
}


$encode = encodep($umlCode);
// Print or use the Base64 encoded compressed string
$encodedContents=$encode;
$link='http://www.plantuml.com/plantuml/png/';
$url=$link.$encodedContents;


// Only show diagram in view mode
if (!$editMode) {
    echo '<a href="' . $url . '" target="_blank">';
    echo '<img src="' . $url . '" alt="Diagram" class="img-fluid" />';
    echo '</a>';
}

// Edit/View toggle button
if ($editMode) {
    // Editor form - full width with inline styles to override any CSS
    echo '
    <style>
        #serverlayout-editor { width: 100% !important; min-width: 800px; display: block; }
        #serverlayout-form { display: block; width: 100%; }
    </style>
    <div style="display: block; width: 100%;">
        <div class="mb-3">
            <a href="?edit=0" class="btn btn-secondary"><i class="fas fa-eye"></i> View Mode</a>
        </div>
        <form method="POST" action="" id="serverlayout-form">
            <input type="hidden" name="action" value="save">
            <div class="mb-3" style="display: block; width: 100%;">
                <label for="serverlayout-editor" class="form-label">Edit PlantUML Source:</label>
                <textarea name="content" id="serverlayout-editor" class="form-control font-monospace" rows="40" style="font-size: 13px;">' . $escapedContents . '</textarea>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                <a href="?edit=0" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>';
} else {
    echo '<div class="container mt-3">';
    echo '<div class="mb-3">
        <a href="?edit=1" class="btn btn-primary"><i class="fas fa-edit"></i> Edit</a>
    </div>';

    // Display the content within a <pre> tag
    echo '<pre class="border border-1 rounded p-3" style="white-space: pre-wrap;">' . $escapedContents . '</pre>';
    echo '</div>';
    echo '</div></div></div>'; // Close view mode wrappers
}

if ($editMode) {
    echo '</div>'; // Close edit mode wrapper
}


$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
