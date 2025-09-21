<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// initialize variables here
$bodycontentclass = '';

// Add v7 theme CSS that includes content-header-dark
$additionalstyles .= '<link rel="stylesheet" href="/public/css/v7/bg_theme.css">';
$additionalstyles .= '
<style>
.table-responsive { margin-top: 1rem; }
.table th, .table td { vertical-align: middle; }
.main-content {
    padding-top: 2rem;
}
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
<!-- Content Header Dark -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-envelope-heart me-3"></i>Invite History</h1>
            <p class="lead mb-0">Track the friends you\'ve invited to join Birthday Gold</p>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container main-content">
';

if (empty($results)) {
    echo '
    <div class="text-center mb-4">
        <a href="/myaccount/invite" class="btn btn-primary me-2">Send New Invite</a>
        <a href="/myaccount" class="btn btn-outline-secondary">Back to My Account</a>
    </div>
    <div class="alert alert-info">
        You haven\'t sent any invites yet.
    </div>
    ';
} else {
    echo '
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold">Invitation History</span>
            <div>
                <a href="/myaccount/invite" class="btn btn-primary btn-sm me-2">Send New Invite</a>
                <a href="/myaccount" class="btn btn-outline-secondary btn-sm">Back to My Account</a>
            </div>
        </div>
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

echo '</div>'; // Close main container

$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
