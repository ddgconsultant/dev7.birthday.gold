<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// initialize variables here
$bodycontentclass = '';
$additionalstyles = '
<style>
.table-responsive { margin-top: 1rem; }
.table th, .table td { vertical-align: middle; }
</style>
';

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
// handle any form posted process here
if ($app->formposted()) {
    // No form processing needed for this page
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');

// Fetch invite history
$sql = '
    SELECT
        name AS invitee_name,
        string_value AS invitee_email,
        description,
        status,
        create_dt AS sent_dt,
        modify_dt AS status_update_dt
    FROM
        bg_user_attributes
    WHERE
        user_id = :user_id AND type = "friend_invite"
    ORDER BY
        create_dt DESC
';

$stmt = $database->prepare($sql);
$stmt->execute(['user_id' => $current_user_data['user_id']]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Validate result
if ($results === false) {
    $results = []; // Ensure $invites is always an array
}

echo '    
<div class="container main-content mt-0 pt-0">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Invite History</h2>
    <a href="/myaccount" class="btn btn-sm btn-outline-secondary">Back to My Account</a>
  </div>
';

if (empty($results)) {
    echo '
    <div class="alert alert-info">
        You haven\'t sent any invites yet.
    </div>
    ';
} else {
    echo '
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Invitee Name</th>
                            <th>Invitee Email</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Date Sent</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
    ';

    foreach ($results as $invite) {
        if (!is_array($invite)) {
            continue; // Skip malformed rows
        }
        echo '
                        <tr>
                            <td>' . htmlspecialchars($invite['invitee_name']) . '</td>
                            <td>' . htmlspecialchars($invite['invitee_email']) . '</td>
                            <td>' . htmlspecialchars($invite['description']) . '</td>
                            <td>
        ';
        if ($invite['status'] === 'accepted') {
            echo '<span class="badge bg-success">Accepted</span>';
        } elseif ($invite['status'] === 'pending') {
            echo '<span class="badge bg-warning">Pending</span>';
        } else {
            echo '<span class="badge bg-danger">Declined</span>';
        }
        echo '
                            </td>
                            <td>' . date('F j, Y', strtotime($invite['sent_dt'])) . '</td>
                            <td>' . ($invite['status_update_dt'] ? date('F j, Y', strtotime($invite['status_update_dt'])) : 'N/A') . '</td>
                        </tr>
        ';
    }

    echo '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    ';
}

echo '</div>            </div>
        </div>
    </div>'; // Close main container

$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
