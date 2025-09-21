<?PHP

function renderTimelineItem($side, $index, $content) {
    global $account, $qik, $display; // To access $account within the function

    // Extract and sanitize data
    $index++;
    $id = intval($content['id']);
    $name = escape($content['name']);
    $category = escape($content['category']);
    $display_name = escape($content['display_name']);
    $label = escape($content['label']);
    $description = escape($content['description']);
    $contentHtml = !empty($content['content']) ? $content['content'] : ''; // Assuming 'content' may contain HTML
    $rank = intval($content['rank']);

    $publish_dt = escape($content['publish_dt']);
   
    $expire_dt = escape($content['expire_dt']);
   $token= $display->inputcsrf_token('tokenonly');
   $linkage='/roadmap?i='. $qik->encodeId($id) . '&_token='. $token;
    // Build the edit icon if the user is an admin
    $editIcon = '';
    if ($account->isadmin()) {
        $editIcon = '<a href="#" data-bs-toggle="modal" data-bs-target="#editModal'.$id.'" class="float-end"><i class="bi bi-gear-fill me-2 text-secondary-subtle"></i></a>';
    }

    // HTML snippets for layout
    $borderx = '<div class="row h-50"><div class="col border-end">&nbsp;</div><div class="col">&nbsp;</div></div>';
    $spacer = '<div class="col-sm"><!--spacer--></div>';

    // Build the content data with the edit icon
    $contentdata = '
    <div class="col-sm py-2">
        <div class="card border-primary shadow radius-15" data-category="'.$category.'">
            <div class="card-body">

<div class="float-end text-primary small">
    ';
if ($account->isactive()) {
      $contentdata .= '
    <!-- Thumbs Up Button -->
    <a href="'. $linkage. '&v=up&" class="text-decoration-none">
        <i class="bi bi-hand-thumbs-up thumbs-up text-primary"></i>
    </a>

    <!-- Thumbs Down Button -->
    <a href="'. $linkage. '&v=down" class="text-decoration-none">
        <i class="bi bi-hand-thumbs-down thumbs-down text-primary"></i>
    </a>
';
}
     $contentdata .= '
    <!-- View Details Button -->
    <a href="/roadmap?details&i=' . $qik->encodeId($id) . '" class="view-details text-decoration-none">
        <i class="bi bi-file-earmark-text"></i>
    </a>
</div>


                '.$editIcon.'
                <h4 class="card-title text-primary">'.$display_name.'</h4>
                <p class="card-text">'.$description.'</p>
            </div>
        </div>
    </div>';

    // Generate the modal if the user is an admin
    $modalHtml = '';
    // Format dates for input fields
$publish_dt_input = $publish_dt ? date('Y-m-d\TH:i', strtotime($publish_dt)) : '';
$expire_dt_input = $expire_dt ? date('Y-m-d\TH:i', strtotime($expire_dt)) : '';

    if ($account->isadmin()) {
        $modalHtml = '
        <!-- Edit Modal -->
        <div class="modal fade" id="editModal'.$id.'" tabindex="-1" aria-labelledby="editModalLabel'.$id.'" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="post" action="">
                    '.$display->inputcsrf_token().'
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel'.$id.'">Edit Roadmap Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Form fields -->
                            <input type="hidden" name="id" value="'.$id.'">
                            <input type="hidden" name="action" value="update_roadmap_item">
                            <div class="mb-3">
                                <label for="name'.$id.'" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name'.$id.'" name="name" value="'.$name.'">
                            </div>
                            <div class="mb-3">
                                <label for="category'.$id.'" class="form-label">Category</label>
                                <input type="text" class="form-control" id="category'.$id.'" name="category" value="'.$category.'">
                            </div>
                            <div class="mb-3">
                                <label for="display_name'.$id.'" class="form-label">Display Name</label>
                                <input type="text" class="form-control" id="display_name'.$id.'" name="display_name" value="'.$display_name.'">
                            </div>
                            <div class="mb-3">
                                <label for="label'.$id.'" class="form-label">Label</label>
                                <input type="text" class="form-control" id="label'.$id.'" name="label" value="'.$label.'">
                            </div>
                            <div class="mb-3">
                                <label for="description'.$id.'" class="form-label">Description</label>
                                <textarea class="form-control" id="description'.$id.'" name="description" rows="7">'.$description.'</textarea>
                            </div>
                            <div class="mb-3">
                                <label for="content'.$id.'" class="form-label">Content</label>
                                <textarea class="form-control" id="content'.$id.'" name="content">'.$contentHtml.'</textarea>
                            </div>
                            <div class="mb-3">
                                <label for="rank'.$id.'" class="form-label">Rank</label>
                                <input type="number" class="form-control" id="rank'.$id.'" name="rank" value="'.$rank.'">
                            </div>
                              <div class="mb-3">
                                <label for="publish_dt'.$id.'" class="form-label">Publish DT</label>
                                <input type="datetime-local" class="form-control" id="publish_dt'.$id.'" name="publish_dt" value="'.$publish_dt_input.'">
                            </div>
                              <div class="mb-3">
                                <label for="expire_dt'.$id.'" class="form-label">Expire DT</label>
                                <input type="datetime-local" class="form-control" id="expire_dt'.$id.'" name="expire_dt" value="'.$expire_dt_input.'">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';
    }

/// ---------------------------------------------------
if ($side === 'left') {
// Left side timeline item
echo '
<!-- timeline item 7 -->
<div class="row g-0">
'.$spacer.'
<div class="col-sm-1 text-center flex-column d-none d-sm-flex">
<div class="row h-50">
<div class="col">&nbsp;</div>
<div class="col">&nbsp;</div>
</div>
<h5 class="m-2">
<span class="badge rounded-pill bg-light border">&nbsp;</span>
</h5>
'.$borderx.'
</div>
'.$contentdata. '
</div>
';
} else {
    echo '<!-- timeline item 2 -->
<div class="row g-0">
'.$contentdata. '
<div class="col-sm-1 text-center flex-column d-none d-sm-flex">
'.$borderx.'
<h5 class="m-2">
<span class="badge rounded-pill bg-light border">&nbsp;</span>
</h5>
'.$borderx.'
</div>
'.$spacer.'
</div>
';
}
echo '
 '.$modalHtml.'
 <!--/row-->';

}





echo '


   <!-- Flex Container -->
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <!-- Left Side: Filter Pills -->
        <div id="filter-pills">
            <!-- "Show All" Button -->
            <button class="btn btn-outline-primary m-1 pill" onclick="filterCards()">Show All</button>
            <button class="btn btn-outline-primary m-1 pill" onclick="filterCards(\'Features\')">Features</button>
            <button class="btn btn-outline-primary m-1 pill" onclick="filterCards(\'SystemEngineering\')">System Engineering</button>
            <button class="btn btn-outline-primary m-1 pill" onclick="filterCards(\'HR\')">HR</button>
            <button class="btn btn-outline-primary m-1 pill" onclick="filterCards(\'Sales\')">Sales</button>
        </div>';

if ($account->isadmin()) {
    echo '
        <!-- Right Side: "Create New" Button -->
        <div>
            <button class="btn btn-outline-primary pill" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="bi bi-plus-circle-fill me-2"></i>Create New
            </button>
        </div>';
}

echo '
    </div>
    <!-- End of Flex Container -->
';


 echo '
<hr>
';
// Start timeline item function


/// ---------------------------------------------------
// Begin the timeline container
echo '<div class="timeline">';

foreach ($contents as $index => $content) {
// Determine if the card should be on the left or right
$side = ($index % 2 === 0) ?  'right' : 'left' ;

// Call the function with dynamic content
renderTimelineItem(
$side,           // Position (left or right)
$index,
$content
);
}

// End the timeline container
echo '</div>';


echo'
<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="">
'.$display->inputcsrf_token().'
                <div class="modal-header">
                    <h5 class="modal-title" id="createModalLabel">Create Roadmap Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Form fields -->
                    <input type="hidden" name="action" value="create_roadmap_item">
                    <div class="mb-3">
                        <label for="name_new" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name_new" name="name" value="">
                    </div>
                    <div class="mb-3">
                        <label for="category_new" class="form-label">Category</label>
                        <input type="text" class="form-control" id="category_new" name="category" value="">
                    </div>
                    <div class="mb-3">
                        <label for="display_name_new" class="form-label">Display Name</label>
                        <input type="text" class="form-control" id="display_name_new" name="display_name" value="">
                    </div>
                    <div class="mb-3">
                        <label for="label_new" class="form-label">Label</label>
                        <input type="text" class="form-control" id="label_new" name="label" value="">
                    </div>

                    <div class="mb-3">
    <label for="type_new" class="form-label">Type</label>
    <input type="text" class="form-control" id="type_new" name="type" value="roadmap">
</div>

                    <div class="mb-3">
                        <label for="description_new" class="form-label">Description</label>
                        <textarea class="form-control" id="description_new" name="description" rows="7"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="content_new" class="form-label">Content</label>
                        <textarea class="form-control" id="content_new" name="content"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="rank_new" class="form-label">Rank</label>
                        <input type="number" class="form-control" id="rank_new" name="rank" value="50">
                    </div>
                    <div class="mb-3">
    <label for="publish_dt_new" class="form-label">Publish DT</label>
    <input type="datetime-local" class="form-control" id="publish_dt_new" name="publish_dt" 
           value="'.date('Y-m-d\TH:i').'">
</div>
                    <div class="mb-3">
                        <label for="expire_dt_new" class="form-label">Expire DT</label>
                        <input type="datetime-local" class="form-control" id="expire_dt_new" name="expire_dt" value="">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Item</button>
                </div>
            </form>
        </div>
    </div>
</div>
';
