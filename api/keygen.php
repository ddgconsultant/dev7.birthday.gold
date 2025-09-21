<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

function addApiKey($database, $name, $description, $userId)
{
    global $session;
    $apiKey = bin2hex(random_bytes(32));
    $sql = "INSERT INTO bg_api_keys (api_key, name, description, user_id, create_dt, status) VALUES (:api_key, :name, :description, :user_id, NOW(), 'active')";
    $stmt = $database->prepare($sql);
    $params = [':api_key' => $apiKey, ':name' => $name, ':description' => $description, ':user_id' => $userId];
    $stmt->execute($params);
    $session->set('new_api_key' , $apiKey);
    return $apiKey;
}

function listApiKeys($database)
{
    $sql = "SELECT id, api_key, name, description, user_id, create_dt, status FROM bg_api_keys ORDER BY create_dt DESC";
    $stmt = $database->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function deleteApiKey($database, $id)
{
    $sql = "DELETE FROM bg_api_keys WHERE id = :id";
    $stmt = $database->prepare($sql);
    $params = [':id' => $id];
    $stmt->execute($params);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add':
            $name = $_POST['name'] ?? ('Key ' .  strtoupper($qik->generateRandomWord()));
            $description = $_POST['description'] ?? 'No description';
            $userId = $_POST['user_id'] ?? $current_user_data['user_id'] ?? 0;
           if ($name) {
                $apiKey = addApiKey($database, $name, $description, $userId);
                echo json_encode(['success' => true, 'apiKey' => $apiKey]);
            } else {
                echo json_encode(['error' => 'Name is required']);
            }
            break;
        case 'delete':
            $id = $_POST['id'] ?? null;
            if ($id) {
                deleteApiKey($database, $id);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'ID is required']);
            }
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}

$apiKeys = listApiKeys($database);




#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>
<div class="container mt-5 main-content">
    <h1>API Key Management</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addApiKeyModal">Add New API Key</button>

    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th class="col-2">Name</th>
                <th class="col-6">API Key Details</th>
                <th class="col-1">Status</th>
                <th class="col-1">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($apiKeys as $key) : ?>
                <tr>

                    <td><span class="fw-bold"><?= htmlspecialchars($key['name'] ?? '') ?></span>
                        <br><span class="text-secondary"><?= htmlspecialchars($key['create_dt'] ?? '') ?></span>
                    </td>

                            <?php
                            $api_key = $key['api_key'] ?? '';
                            $escaped_api_key = htmlspecialchars($api_key);
                            if ($escaped_api_key === ($_SESSION['new_api_key'] ?? '')) {
                                echo '<td class="bg-success-subtle">';
                                echo '<div class="font-monospace fw-bold" id="newApiKey">' . ($escaped_api_key) . '</div>';
                               echo ' <div>'.htmlspecialchars($key['description'] ?? '') .'</div>';

echo '<form action="/admin/accessmanager/index.php" method="post">
'.$display->inputcsrf_token().'
<input type="hidden" name="act" value="addnew">
<input type="hidden" name="datatype" value="special">
<input type="hidden" name="data_type" value="special">
<input type="hidden" name="user_id" value="'.$current_user_data['user_id'].'">
<input type="hidden" name="name" value="'.$key['name'].'">
<input type="hidden" name="password" value="'.$escaped_api_key.'">
<input type="hidden" name="notes" value="Automatically Added from API Key Management on '.$key['create_dt'].' / '.$key['description'].'">
';
                               echo '<i><i class="bi bi-exclamation-triangle">
                               </i> This key will only be shown once. Please write it down or 
                               <button class="btn btn-secondary btn-sm py-0" onclick="copyToClipboard()">Click to Copy</button>
                                or <button class="btn btn-secondary btn-sm py-0" type="submit">Save to AccessManager</button>.
                                </i>
                                </form>';
                                // Clear the session variable so it doesn't match multiple times
                                unset($_SESSION['new_api_key']);
                            } else {
                            $display_api_key = strlen($escaped_api_key) > 8
                                ? substr($escaped_api_key, 0, 4) . '...' . substr($escaped_api_key, -4)
                                : $escaped_api_key;
                                echo '<td class="">';
                                echo '<div class="font-monospace fw-bold">' . $display_api_key . '</div>';
                                echo ' <div>'.htmlspecialchars($key['description'] ?? '') .'</div>';
                        }
                        ?>
                  
                    </td>
                    <td><?= htmlspecialchars($key['status'] ?? '') ?></td>
                    <td>
                        <button class="btn btn-danger btn-delete" data-id="<?= htmlspecialchars($key['id']) ?>">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add API Key Modal -->
<div class="modal fade" id="addApiKeyModal" tabindex="-1" aria-labelledby="addApiKeyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addApiKeyModalLabel">Add New API Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addApiKeyForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description"></textarea>
                    </div>
        <!--            <div class="mb-3">
                        <label for="user_id" class="form-label">User ID</label>
                        <input type="text" class="form-control" id="user_id" name="user_id" required>
                    </div>  -->
                    <input type="hidden" name="action" value="add">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add API Key</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    $(document).ready(function() {
        $('#addApiKeyForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: '',
                data: $(this).serialize(),
                success: function(response) {
                    var res = JSON.parse(response);
                    if (res.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + res.error);
                    }
                }
            });
        });

        $('.btn-delete').on('click', function() {
            if (confirm('Are you sure you want to delete this API key?')) {
                var id = $(this).data('id');
                $.ajax({
                    type: 'POST',
                    url: '',
                    data: {
                        action: 'delete',
                        id: id
                    },
                    success: function(response) {
                        var res = JSON.parse(response);
                        if (res.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + res.error);
                        }
                    }
                });
            }
        });

        function copyToClipboard() {
        var copyText = document.getElementById("newApiKey").innerText;
        navigator.clipboard.writeText(copyText).then(function() {
            alert("API Key copied to clipboard");
        }, function(err) {
            alert("Failed to copy text: ", err);
        });
    }
    });

</script>
<?PHP

$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
