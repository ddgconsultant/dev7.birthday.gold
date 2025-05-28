<?php
// Display the details of a single roadmap item
$content = $contents[0]; // Assuming $contents contains the roadmap data

// Safely retrieve fields, fallback to empty strings if the key doesn't exist
$title = isset($content['name']) ? htmlspecialchars($content['name']) : 'Untitled';
$description = isset($content['description']) ? htmlspecialchars($content['description']) : 'No description available';
$contentText = isset($content['content']) ? ($content['content']) : 'No content available';
$publish_dt = isset($content['publish_dt']) ? date('Y-m-d', strtotime($content['publish_dt'])) : 'Unknown';
$expire_dt = isset($content['expire_dt']) ? date('Y-m-d', strtotime($content['expire_dt'])) : 'Not Set';
$rank = isset($content['rank']) ? $content['rank'] : 'N/A';
$status = isset($content['status']) ? htmlspecialchars($content['status']) : 'N/A';
$category = isset($content['category']) ? htmlspecialchars($content['category']) : 'General';
$version = isset($content['version']) ? htmlspecialchars($content['version']) : 'N/A';

$token= $display->inputcsrf_token('tokenonly');
$linkage = '/roadmap?i=' . $qik->encodeId($content['id']) . '&_token=' . $token;

// Query to get vote counts
$sql = "
    SELECT `name`, COUNT(*) as vote_count,
    (SELECT COUNT(*) FROM bg_user_attributes 
     WHERE `type` = 'roadmap_vote' AND `value` = :roadmap_id0 AND `user_id` = :current_user_id) as uservoted
    FROM bg_user_attributes
    WHERE `type` = 'roadmap_vote' AND `value` = :roadmap_id1
    GROUP BY `name`
";

$stmt = $database->prepare($sql);
$stmt->execute([
    ':roadmap_id0' => $content['id'],
    ':roadmap_id1' => $content['id'],
    ':current_user_id' => ($current_user_data['user_id']??0)
]);
$votes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize vote counts and check if user has voted
$upvotes = 0;
$downvotes = 0;
$userVotedUp = false;
$userVotedDown = false;

foreach ($votes as $vote) {
    if ($vote['name'] == 'up') {
        $upvotes = $vote['vote_count'];
        if ($vote['uservoted'] > 0) $userVotedUp = true;
    } elseif ($vote['name'] == 'down') {
        $downvotes = $vote['vote_count'];
        if ($vote['uservoted'] > 0) $userVotedDown = true;
    }
}

// Render the content in a card layout
echo '
<div class="text-end my-3">
    <a href="/roadmap" class="btn btn-primary">Back to Roadmap</a>
</div>
<div class="card mb-3">
    <div class="card-header">
        <h4 class="mb-0">' . $title . '</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6><strong>Published:</strong> ' . $publish_dt . '</h6>
                <h6><strong>Expires:</strong> ' . $expire_dt . '</h6>
                <h6><strong>Rank:</strong> ' . $rank . '</h6>
                <h6><strong>Status:</strong> ' . ucfirst($status) . '</h6>
                <h6><strong>Category:</strong> ' . $category . '</h6>
                <h6><strong>Version:</strong> ' . $version . '</h6>
            </div>
            <div class="col-md-6">
                <h6><strong>Description:</strong></h6>
                <p>' . $description . '</p>
            </div>
        </div>
        <hr>
        <h5>Details</h5>
        <div class="m-5" style="line-height: 1.9;">' . $contentText . '</div>
    </div>
    <div class="card-footer">
        <div class="float-end">
            <!-- Thumbs Up Button -->
            ';

            $uptag='  <span class="me-3"><i class="bi bi-hand-thumbs-up-fill text-success"></i> ' . $upvotes . '</span>';
            if ($userVotedDown) {
                echo   $uptag;
             } else {
                echo '   <a href="'.$linkage . '&v=up" class="thumbs-up  text-decoration-none">'.$uptag.'</a>';
               
            }


            $downtag='  <span class="me-3"><i class="bi bi-hand-thumbs-down-fill text-success"></i> ' . $downvotes . '</span>';
            if ($userVotedDown) {
                echo   $downtag;
            } else {
                echo '   <a href="'.$linkage . '&v=down" class="thumbs-down  text-decoration-none">'.$downtag.'</a>';
            }

            echo '
        </div>
    </div>
</div>';
?>
