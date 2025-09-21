<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


$slideNumber = $_GET['slide'] ?? 1;


$grouping = $_GET['grp'] ?? '';
$presentation = $_GET['pres'] ?? '';


$url = "https://webslides.tv/demos/portfolios";
$content = file_get_contents($url);

$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($content);
libxml_clear_errors();

$xpath = new DOMXPath($dom);
$sections = $xpath->query("//section");

// Check if the slide number is valid
if ($sections->length >= $slideNumber) {
    $section = $sections->item($slideNumber - 1); // Adjust for zero-based index
    
    // Extract the class attribute of the section
    $sectionClass = $section->getAttribute('class');
    
    $innerHTML = '';
    foreach ($section->childNodes as $child) {
        $innerHTML .= $dom->saveHTML($child);
    }
    $slideContent = $innerHTML;
    $slideContent = "<!--- Template #$slideNumber -->
    ".trim($slideContent);
} else {
    $slideContent = "Slide not found.";
    $sectionClass='';
}

// Now $sectionClass contains the class attribute of the section
// You can use $sectionClass as needed


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Slide</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-group {
            display: flex;
    align-items: flex-start; /* Vertically align items to the top */
            margin-bottom: 1rem;
        }
        .form-group label {
            flex: 0 0 120px;
            margin-bottom: 0;
            text-align: right;
            font-weight: bold;
            padding-right: 5px;
        }
        .form-group input, .form-group textarea {
            flex: 1;
        }
    </style>
</head>
<body>


    <div class="container-fluid p-3 ">
        <form action="admin.php" method="post">
            <div class="form-group">
                <label for="presentation" class="form-label">Presentation:</label>
                <input type="text" class="form-control" id="presentation" name="presentation" value="<? echo $presentation; ?>" required>
            </div>
            <div class="form-group">
                <label for="grouping" class="form-label">Grouping:</label>
                <input type="text" class="form-control" id="grouping" name="grouping" value="<? echo $grouping; ?>">
            </div>
            <div class="form-group">
                <label for="slide_order" class="form-label">Slide Order:</label>
                <input type="number" class="form-control" id="slide_order" name="slide_order" value="10">
            </div>
            <div class="form-group">
                <label for="section_prefix" class="form-label">Section Prefix:</label>
                <textarea class="form-control" id="section_prefix" name="section_prefix"></textarea>
            </div>
            <div class="form-group">
                <label for="section_class" class="form-label">Section Class:</label>
                <input type="text" class="form-control" id="section_class" name="section_class" value="<?php echo $sectionClass; ?>">
            </div>
            <div class="form-group">
                <label for="section_tag" class="form-label">Section Tag:</label>
                <textarea class="form-control" id="section_tag" name="section_tag"></textarea>
            </div>
            <div class="form-group">
                <label for="content" class="form-label">Content:</label>
<textarea class="form-control" id="content" name="content" rows="10"><?php echo htmlspecialchars($slideContent); ?></textarea>
<input type="hidden" name="slide" value="<?php echo $slideNumber; ?>">
            </div>
            <div class="form-group">
                <label for="speech_script" class="form-label">Speech Script:</label>
                <textarea class="form-control" id="speech_script" name="speech_script"></textarea>
            </div>
            <div class="form-group">
                <label for="status" class="form-label">Status:</label>
                <input type="text" class="form-control" id="status" name="status" value="active">
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
        <a href="admin" target="_top" class="mt-3 btn btn-success">Admin</a>

    </div>     
    <?PHP echo $website['bootstrap_js']; ?>    
 </body>
</html>
