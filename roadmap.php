<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');





#-------------------------------------------------------------------------------
# HANDLE FORM SUBMISSION
#-------------------------------------------------------------------------------

// Check if the user is an admin and if there is a POST request to update a record
// Handle form submission for creating a new roadmap item
if ( $app->formposted('REQUEST')){

    $id = (intval($_REQUEST['id']??''));
    $name = $_REQUEST['name'] ?? '';
    $category = $_REQUEST['category'] ?? '';
    $grouping = $_REQUEST['grouping'] ?? '';
    $display_name = $_REQUEST['display_name'] ?? '';
    $label = $_REQUEST['label'] ?? '';
    $description = $_REQUEST['description'] ?? '';
    $content_text = $_REQUEST['content'] ?? '';
    $version = $_REQUEST['version'] ?? '';
    $rank = intval($_REQUEST['rank'] ?? 50);
    $publish_dt = !empty($_REQUEST['publish_dt']) ? date('Y-m-d H:i:s', strtotime($_REQUEST['publish_dt'])) : null;
    $expire_dt = !empty($_REQUEST['expire_dt']) ? date('Y-m-d H:i:s', strtotime($_REQUEST['expire_dt'])) : null;
    $status = $_REQUEST['status'] ?? 'active';
    $type = 'roadmap'; // Ensuring the type is set to 'roadmap'
    $create_dt = date('Y-m-d H:i:s'); // Current date and time

    
//====================================================================================================
// post a vote  (thumbs up or down)
if ( isset($_REQUEST['v']) && ($_REQUEST['v'] === 'up' || $_REQUEST['v'] === 'down')) {
    // Sanitize and retrieve POST data
    $vote = $_REQUEST['v'] === 'up' ? 'up' : 'down';
    $roadmap_id = $qik->decodeId($_REQUEST['i']??'');

    if (!empty($roadmap_id)) {
   // Prepare the SQL to insert the vote into bg_user_attributes
$sql = "INSERT INTO bg_user_attributes (user_id, `type`, `name`, `value`, `status`, `rank`, create_dt, modify_dt)
VALUES (:user_id, 'roadmap_vote', :vote, :roadmap_id, 'active', 100, NOW(), NOW())";
#
// Prepare and execute the statement
$stmt = $database->prepare($sql);
$stmt->execute([
':roadmap_id' => $roadmap_id,
':user_id' => $current_user_data['user_id'],
':vote' => $vote
]);

// Optionally, you can check if the insertion was successful
if ($stmt->rowCount()) {
#echo 'Vote recorded successfully!';
}

} else {
#echo 'Failed to record the vote.';
}

header('Location: /roadmap');
exit;
}

//====================================================================================================
if ($account->isadmin() && isset($_REQUEST['action']) && $_REQUEST['action'] === 'create_roadmap_item') {
    // Sanitize and retrieve POST data
   
    // Prepare and execute insert statement
    $query='  INSERT INTO bg_content (
            name, category, `type`, `grouping`, display_name, label, description, content, `version`, `rank`, publish_dt, expire_dt, create_dt, modify_dt, status
        ) VALUES (
            :name, :category, :type, :grouping, :display_name, :label, :description, :content_text, :version, :rank, :publish_dt, :expire_dt, now(), now(), :status
        )    ';

   $procestype='insert';   
   $error_message = "Error creating new roadmap item.";
}

//====================================================================================================
// Check if the user is an admin and if there is a POST request to update a record
if ($account->isadmin() && isset($_REQUEST['action']) && $_REQUEST['action'] === 'update_roadmap_item') {

  
    // Prepare and execute update statement
     $query=' UPDATE bg_content SET
            name = :name, category = :category, `grouping` = :grouping, display_name = :display_name,
            label = :label, description = :description, content = :content_text, `version` = :version,
            `rank` = :rank, publish_dt = :publish_dt, expire_dt = :expire_dt, type = :type, status = :status, modify_dt=now()
        WHERE id = :id    ';

 $procestype='update'; 
 $error_message = "Error updating record.";
}


$stmt = $database->prepare($query);
if ($procestype=='update') {
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
}

if ($procestype=='insert') {
    $stmt->bindParam(':type', $type, PDO::PARAM_INT);
}

$stmt->bindParam(':name', $name);
$stmt->bindParam(':category', $category);
$stmt->bindParam(':grouping', $grouping);
$stmt->bindParam(':display_name', $display_name);
$stmt->bindParam(':label', $label);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':content_text', $content_text);
$stmt->bindParam(':version', $version);
$stmt->bindParam(':rank', $rank, PDO::PARAM_INT);
$stmt->bindValue(':publish_dt', $publish_dt, $publish_dt ? PDO::PARAM_STR : PDO::PARAM_NULL);
$stmt->bindValue(':expire_dt', $expire_dt, $expire_dt ? PDO::PARAM_STR : PDO::PARAM_NULL);
$stmt->bindParam(':status', $status);

    if ($stmt->execute()) {
        // Insert successful; redirect to avoid form resubmission
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } 
}

//====================================================================================================
//====================================================================================================
//====================================================================================================





// Fetch all active records from bg_content ordered by rank and publish date
$stmt = $database->prepare("SELECT * FROM bg_content WHERE status = 'active' and `type`='roadmap' ORDER BY `rank` ASC, publish_dt ASC");
$displaymode='list';


// Function to sanitize output to prevent XSS
function escape($string) {
return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

$additionalstyles.="
<style>
.pill {
border: none;
border-radius: 50px;
padding: 10px 20px;
margin: 5px;
cursor: pointer;
/* Add more styling as needed */
}


.text-secondary-subtle {
color: var(--bs-yellow) !important;
}
.text-secondary-subtle:hover {
color: var(--bs-secondary) !important;
}


    /* Thumbs Up */
    .thumbs-up:hover,  .thumbs-down:hover  {
        color: var(--bs-secondary) !important; /* secondary */
    }
    .thumbs-up.voted, .thumbs-down.voted  {
        color: var(--bs-success) !important; /* success */
    }


    /* Optional to adjust spacing */
    .thumbs-up, .thumbs-down, .view-details {
        margin-left: 5px;
    }
</style>

";
#-------------------------------------------------------------------------------
# DISPLAY THE DETAILS OF A ROADMAP ITEM
#-------------------------------------------------------------------------------
$id=$qik->decodeId($_REQUEST['i'] ?? '');
if (!empty($id)) {
$stmt = $database->prepare("SELECT * FROM bg_content WHERE id=$id and status = 'active' and `type`='roadmap' ORDER BY `rank` ASC, publish_dt DESC");
$displaymode='details';

}



#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------


$stmt->execute();
$contents = $stmt->fetchAll(PDO::FETCH_ASSOC);


include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
echo '<div class="container py-2 main-content">
<h2 class="font-weight-light text-center text-muted py-3">Our Roadmap</h2>';

switch($displaymode) {
    case 'details':
        include($_SERVER['DOCUMENT_ROOT'] . '/components/roadmap-details.inc');
        break;
    default:
include($_SERVER['DOCUMENT_ROOT'] . '/components/roadmap-list.inc');
break;
}
?>


</div>
</div>
</div>
</div>

<script>
function filterCards(category = null) {
    // Get all cards
    var cards = document.getElementsByClassName('card');    
    for (var i = 0; i < cards.length; i++) {
        if (category === null) {
            // Show all cards if no category is provided
            cards[i].style.display = 'block';
        } else {
            // If card's category matches the filter, show it, else hide it
            if (cards[i].getAttribute('data-category') === category) {
                cards[i].style.display = 'block';
            } else {
                cards[i].style.display = 'none';
            }
        }
    }
}
</script>


<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
