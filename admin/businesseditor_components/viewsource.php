<?php
if (!isset($componentmode) || $componentmode != 'include') {
    include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
}

$business_id = $company_id ?? $_REQUEST['bid'] ?? null;

#-------------------------------------------------------------------------------
# HANDLE POST
#-------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $html = $_POST['html'] ?? null;

    $stmt = $database->query("SELECT * FROM bg_company_attributes WHERE company_id = :company_id AND type='signup_html'", ['company_id' => $company_id]);
    $existingRow = $stmt->fetch();

    if ($existingRow) {
        if (strpos($html, '{{ADDTO}}') !== false) {
            $html = $existingRow['description'] . $html;
        }

        $stmt = $database->query("UPDATE bg_company_attributes SET description = :description, modify_dt = :modify_dt WHERE attribute_id = :attribute_id", 
            ['description' => $html, 'modify_dt' => date("Y-m-d H:i:s"), 'attribute_id' => $existingRow['attribute_id']]);
    } else {
        $stmt = $database->query("INSERT INTO bg_company_attributes (company_id, type, description, create_dt, modify_dt) VALUES (:company_id, :type, :description, :create_dt, :modify_dt)", 
            ['company_id' => $company_id, 'type' => 'signup_html', 'description' => $html, 'create_dt' => date("Y-m-d H:i:s"), 'modify_dt' => date("Y-m-d H:i:s")]);
    }
}

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
if (!isset($url)) {
    $url = urldecode($_GET['url'] ?? $company['signup_url'] ?? '');
}

$sourceType = 'DB';
$stmt = $database->query("SELECT description FROM bg_company_attributes WHERE company_id = :company_id AND type='signup_html'", ['company_id' => $business_id]);
$sourceCode = $stmt->fetchColumn();

if (empty($sourceCode) && !empty($url)) {
    $sourceCode = @file_get_contents($url);
    $sourceType = 'URL';
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

// Function to highlight specific HTML tags and attributes
function highlight($sourceCode, $tag, $cssClass) {
    return preg_replace("/(&lt;\/?" . $tag . ".*?&gt;)/", '<span class="' . $cssClass . '">$1</span>', $sourceCode);
}

$sourceCode = htmlspecialchars($sourceCode);

// Highlight form tags
$sourceCode = str_replace('&lt;form', '<hr class="formfound" style="border: 5px solid red;">&lt;form', $sourceCode);
$sourceCode = str_replace('&lt;/form&gt;', '&lt;/form&gt;<hr class="formfound" style="border: 5px solid red;">', $sourceCode);

// Highlight input tags
$sourceCode = highlight($sourceCode, 'input', 'highlightorange');

// Highlight id attributes
$sourceCode = preg_replace('/(id=&quot;.*?&quot;)/', '<span class="highlightyellow">$1</span>', $sourceCode);

$output = $sourceCode;

$additionalstyles .= '
<style>
    body {
        margin: 0;
        padding: 0;
    }
    .fixed-header {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        background-color: #f9f9f9;
        z-index: 1000;
        padding: 10px;
        box-shadow: 0px 2px 10px #aaa;
    }
    .content {
        margin-top: 20px;
        padding: 10px;
    }
    pre {
        white-space: pre-wrap; /* Allows line wrapping within <pre> elements */
    }
    .highlightorange {
        background-color: orange;
    }
    .highlightyellow {
        background-color: yellow;
    }
    .highlightyellow:hover {
        background-color: green;
        cursor: pointer;
    }
</style>
';
?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(function() {
            var formFoundElement = document.querySelector(".formfound");
            if (formFoundElement) {
                formFoundElement.scrollIntoView();
            }
        }, 100);
    });
</script>

<div class="content-header">
    <h3><b><?php echo $sourceType . ': </b>' . $url; ?></h3>
    <p>use: {{ADDTO}}</p>
    <form method="post">
        <textarea name="html" rows="2" cols="95" placeholder="Paste new HTML here..."></textarea>
        <button type="submit" class="btn btn-primary">Submit</button>
        <input name="bid" value="<?php echo $business_id; ?>" type="hidden">
    </form>
    <hr>
</div>

<div class="content" id="businesseditor_viewsource_content">
    <pre><?php echo $output; ?></pre>
</div>

<script>
    $(document).ready(function() {
        $('.highlightyellow').click(function() {
            var content = $(this).text(); // Get the text content of the clicked span
            content = content.replace(/"/g, ''); // Remove double quotes
            content = content.replace(/id=/g, ''); // Remove "id="

            copyToClipboard(content);
        });

        function copyToClipboard(text) {
            var tempInput = $('<input>');
            $('body').append(tempInput);
            tempInput.val(text).select();
            document.execCommand('copy');
            tempInput.remove();
        }
    });
</script>