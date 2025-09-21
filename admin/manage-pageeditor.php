<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Handle the toggle via $_REQUEST
if (isset($_REQUEST['toggle_editor'])) {
    $currentEditorState = $session->get('enable_adminpageeditor', false); // Default is false if not set
    $newEditorState = $currentEditorState ? false : true;
    $session->set('enable_adminpageeditor', $newEditorState);

    // Optional: Message to confirm change
    $message = $newEditorState ? 'Admin page editor enabled.' : 'Admin page editor disabled.';
}

// Fetch current state
$currentEditorState = $session->get('enable_adminpageeditor', false);
if ($currentEditorState) {

$statetag='enabled';
$btnclass= 'btn-danger' ;
$btnlabel='Disable Editor' ;
$editbutton='<span  class="ms-5"><a href="https://dev.birthday.gold/" class="btn  btn-primary">Edit Site</a></span>';
} else {    
$statetag='disabled';
$btnclass= 'btn-success' ;
$btnlabel='Enable Editor' ;
$editbutton='';

}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$additionalstyles .= '
<style>
    .toggle-button {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }
    .frame {
        border: 2px solid #007bff; /* Adds a blue border around the content */
        border-radius: 10px; /* Slightly round the corners */
        padding: 20px; /* Adds padding inside the border */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Adds a slight shadow */
    }
</style>
';




echo '    <div class="container main-content py-5">
<div class="frame">
        <h3>Toggle Admin Page Editor</h3>
        <p>The admin page editor is currently <strong>' . $statetag . '</strong>.</p>';

if (isset($message)) {
    echo '<div class="alert alert-success">' . $message . '</div>';
}


echo '<div class="toggle-button ">
        <form method="POST" >
            <input type="hidden" name="toggle_editor" value="1">
            <button type="submit" class="btn ' . $btnclass . '">
                ' .$btnlabel . '
            </button>
        </form>
      '.$editbutton.'
      </div>
            </div>
    </div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
