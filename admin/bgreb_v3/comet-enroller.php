<?PHP
$addClasses = ['enrollment'];
$dir['base'] = $BASEDIR = __DIR__ . "/../.." ?? $_SERVER['DOCUMENT_ROOT'];
require_once($BASEDIR . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$bodycontentclass = '';
$header_flush = true;
$pageError = '';

$userId = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
$aid = isset($_GET['aid']) ? intval($_GET['aid']) : 0;

#-------------------------------------------------------------------------------
# FETCH DATA via Enrollment class
#-------------------------------------------------------------------------------
$userDetails = null;
$registrationList = [];

if ($userId == 0) {
    $pageError = 'Missing user ID (uid parameter required).';
} else {
    $companyId = 0;
    $return = 'local';

    $adminDetails = [];
    if ($aid != 0) {
        $tmpsettings = [];
        $tmpsettings['columns'] = 'user_id, first_name, last_name, username';
        $adminDetails = $account->getuserdata($aid, 'user_id', $tmpsettings);
    }

    list($output, $adminDetails, $userDetails, $registrationList) = $enrollment->grabdetails($database, $adminDetails, $userId, $companyId, $return);

    if (empty($userDetails)) {
        $pageError = 'User not found.';
    }
}

#-------------------------------------------------------------------------------
# BUILD PROFILE DATA
#-------------------------------------------------------------------------------
$profileFields = [
    'Title'          => 'profile_title',
    'First Name'     => 'profile_first_name',
    'Middle Name'    => 'profile_middle_name',
    'Last Name'      => 'profile_last_name',
    'Gender'         => 'profile_gender',
    'Birthdate'      => 'birthdate',
    'Email'          => 'profile_email',
    'Username'       => 'profile_username',
    'Password'       => 'profile_password',
    'Phone'          => 'profile_phone_number',
    'Phone Type'     => 'profile_phone_type',
    'Address'        => 'profile_mailing_address',
    'City'           => 'profile_city',
    'State'          => 'profile_state',
    'Zip Code'       => 'profile_zip_code',
    'Country'        => 'profile_country',
];

// Build flat JSON object for Comet
$flatJson = [];
if ($userDetails) {
    foreach ($profileFields as $label => $key) {
        $flatJson[$key] = $userDetails[$key] ?? '';
    }
}

$additionalstyles = '
<style>
.content-header-admin { margin-top: 0 !important; }
.navbar + .row { height: 0 !important; overflow: hidden; pointer-events: none; }
.navbar + .row + .content-header-admin { margin-top: 0 !important; position: relative; z-index: 1; }
.comet-section { margin-bottom: 2rem; }
.comet-instruction-box {
    background: #fff3cd;
    border: 2px solid #ffc107;
    border-radius: 0.5rem;
    padding: 1.25rem;
    font-size: 1.05rem;
}
.field-mapping-cell { font-family: monospace; font-size: 0.85rem; }
pre.json-block {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 1rem;
    border-radius: 0.5rem;
    max-height: 400px;
    overflow: auto;
    font-size: 0.85rem;
}
.action-cell { white-space: nowrap; }
.action-cell .btn { font-size: 0.75rem; padding: 0.2rem 0.5rem; }
.program-row.row-success { background-color: #d4edda; }
.program-row.row-failed { background-color: #f8d7da; }
.data-field {
    cursor: pointer;
}
.data-field:hover {
    text-decoration: underline;
}
.mapping-copy-btn { opacity: 0; transition: opacity 0.15s; }
tr:hover .mapping-copy-btn { opacity: 1; }
</style>
';

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="content-header-admin no-rounded-corners">
    <div class="container">
        <h1 class="mt-3">Comet Browser Enrollment</h1>
        <p class="lead mb-4">Automated enrollment data for Comet browser agent</p>
    </div>
</div>

<section class="container mt-3 min-vh-0">

<!-- Navigation Bar -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex gap-2">
        <a href="/admin/bgreb_v3/enrollment-listv2" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Enrollment List</a>
        <?php if ($userId > 0): ?>
        <a href="/admin/bgreb_v3/member-enroller?uid=<?= $qik->encodeId($userId) ?>&aid=<?= $qik->encodeId($aid) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right"></i> Open in Member Enroller</a>
        <?php endif; ?>
    </div>
    <a href="/admin/" class="btn btn-sm btn-outline-secondary">Admin Home</a>
</div>

<?php if (!empty($pageError)): ?>
<div class="alert alert-danger mt-3"><?= htmlspecialchars($pageError) ?></div>
<?php else: ?>

<!-- SECTION 1: Summary Header -->
<div class="comet-section">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h4 class="mb-1">Enrolling: <?= htmlspecialchars(($userDetails['profile_first_name'] ?? $userDetails['first_name'] ?? '') . ' ' . ($userDetails['profile_last_name'] ?? $userDetails['last_name'] ?? '')) ?></h4>
            <p class="text-muted mb-0"><?= htmlspecialchars($userDetails['profile_email'] ?? $userDetails['email'] ?? '') ?> &bull; User ID: <?= intval($userId) ?></p>
            <p class="mt-2 mb-0">Enroll this user in all <strong>pending</strong> programs listed below using the profile data provided.</p>
        </div>
    </div>
</div>

<!-- SECTION 2: Member Profile Data (dual format) -->
<div class="comet-section">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Member Profile Data</h5>
            <div>
                <a class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" href="#userJsonBlock" role="button">Toggle Raw JSON</a>
                <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copyJson()">Copy JSON</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Left Column: Profile Information -->
                <div class="col-md-6">
                    <div class="card mb-2">
                        <div class="card-header py-1 bg-light"><h6 class="mb-0 small fw-bold">Enrollment Profile Information</h6></div>
                        <div class="card-body py-2">
                            <?php foreach ($profileFields as $label => $key): ?>
                            <?php $val = $userDetails[$key] ?? ''; if ($val === '') continue; ?>
                            <div class="row mb-1">
                                <div class="col-4 text-end fw-bold text-muted small"><?= htmlspecialchars($label) ?>:</div>
                                <div class="col-8">
                                    <span class="data-field small fw-bold text-success" onclick="copyToClipboard('<?= htmlspecialchars(addslashes($val), ENT_QUOTES) ?>')" title="Click to copy"><?= htmlspecialchars($val) ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <!-- Right Column: Account, Agreements, Allergies, Dietary, Special Status -->
                <div class="col-md-6">
                    <div class="card mb-2">
                        <div class="card-header py-1 bg-light"><h6 class="mb-0 small fw-bold">Account Information</h6></div>
                        <div class="card-body py-2">
                            <?php
                            $accountFields = ['User ID' => 'user_id', 'Account Type' => 'account_type', 'Status' => 'status'];
                            foreach ($accountFields as $lbl => $k):
                                $v = $userDetails[$k] ?? ''; if ($v === '') continue;
                            ?>
                            <div class="row mb-1">
                                <div class="col-4 text-end fw-bold text-muted small"><?= $lbl ?>:</div>
                                <div class="col-8 small"><?= htmlspecialchars($v) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card mb-2">
                        <div class="card-header py-1 bg-light"><h6 class="mb-0 small fw-bold">Agreements & Preferences</h6></div>
                        <div class="card-body py-2">
                            <div class="row">
                                <?php
                                $agreements = ['Terms' => 'profile_agree_terms', 'Email' => 'profile_agree_email', 'Text' => 'profile_agree_text'];
                                foreach ($agreements as $lbl => $k):
                                    $v = $userDetails[$k] ?? '';
                                    $icon = $v ? 'bi-check-circle text-success' : 'bi-x-circle text-danger';
                                ?>
                                <div class="col-4 text-center small"><i class="bi <?= $icon ?>"></i> <?= $lbl ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php
                    $allergyFields = ['Gluten' => 'profile_allergy_gluten', 'Sugar' => 'profile_allergy_sugar', 'Nuts' => 'profile_allergy_nuts', 'Dairy' => 'profile_allergy_dairy'];
                    $dietFields = ['Vegan' => 'profile_diet_vegan', 'Kosher' => 'profile_diet_kosher', 'Vegetarian' => 'profile_diet_vegetarian', 'Keto' => 'profile_diet_keto'];
                    $specialFields = ['Military' => 'profile_military', 'Educator' => 'profile_educator', 'First Responder' => 'profile_firstresponder'];

                    $extraSections = [
                        'Allergies' => $allergyFields,
                        'Dietary Preferences' => $dietFields,
                        'Special Status' => $specialFields,
                    ];
                    foreach ($extraSections as $sectionTitle => $fields):
                        $hasValues = false;
                        foreach ($fields as $k) { if (!empty($userDetails[$k])) { $hasValues = true; break; } }
                        if (!$hasValues) continue;
                    ?>
                    <div class="card mb-2">
                        <div class="card-header py-1 bg-light"><h6 class="mb-0 small fw-bold"><?= $sectionTitle ?></h6></div>
                        <div class="card-body py-2">
                            <?php foreach ($fields as $lbl => $k):
                                $v = $userDetails[$k] ?? ''; if ($v === '') continue;
                            ?>
                            <div class="row mb-1">
                                <div class="col-4 text-end fw-bold text-muted small"><?= $lbl ?>:</div>
                                <div class="col-8 small"><?= htmlspecialchars($v) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="collapse mt-2" id="userJsonBlock">
                <pre class="json-block" id="user-json"><?= htmlspecialchars(json_encode($flatJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 3: Program List Table (grouped by platform) -->
<?php
// Group programs by signup domain/platform
$groupedPrograms = [];
foreach ($registrationList as $company) {
    $domain = $company['signup_domain'] ?? parse_url($company['signup_url'] ?? '', PHP_URL_HOST) ?? 'Other';
    // Normalize common platforms to a friendly label
    $domain = preg_replace('#^https?://#', '', $domain);
    $groupedPrograms[$domain][] = $company;
}
ksort($groupedPrograms);
?>
<div class="comet-section">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Programs to Enroll (<?= count($registrationList) ?>) &mdash; <?= count($groupedPrograms) ?> platforms</h5>
            <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-secondary" onclick="toggleAllMappings(true)" title="Expand all field mappings"><i class="bi bi-arrows-expand"></i> Expand All</button>
                <button class="btn btn-sm btn-outline-secondary" onclick="toggleAllMappings(false)" title="Collapse all field mappings"><i class="bi bi-arrows-collapse"></i> Collapse All</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" id="programTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Program Name</th>
                            <th>Signup URL</th>
                            <th>Status</th>
                            <th>Field Mappings</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $rowNum = 0;
                    foreach ($groupedPrograms as $platformDomain => $companies):
                    ?>
                        <!-- Platform Group Header -->
                        <tr class="table-secondary">
                            <td colspan="6" class="fw-bold small py-1">
                                <i class="bi bi-globe2"></i> <?= htmlspecialchars($platformDomain) ?>
                                <span class="badge bg-secondary ms-1"><?= count($companies) ?></span>
                            </td>
                        </tr>
                    <?php
                    foreach ($companies as $company):
                        $rowNum++;
                        $status = $company['status'] ?? 'unknown';
                        $statusLower = strtolower($status);
                        $companyId = $company['company_id'] ?? 0;
                        $ucid = $company['user_company_id'] ?? 0;

                        if (str_contains($statusLower, 'success')) {
                            $badgeClass = 'bg-success';
                            $rowClass = 'row-success';
                        } elseif (str_contains($statusLower, 'fail')) {
                            $badgeClass = 'bg-danger';
                            $rowClass = 'row-failed';
                        } else {
                            $badgeClass = 'bg-warning text-dark';
                            $rowClass = '';
                        }

                        $enrollerLink = '/admin/bgreb_v3/member-enroller?bid=' . $qik->encodeId($companyId) . '&uid=' . $qik->encodeId($userId) . '&aid=' . $qik->encodeId($aid);
                    ?>
                        <tr class="program-row <?= $rowClass ?>" id="row-<?= $companyId ?>">
                            <td><?= $rowNum ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($company['company_name'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($company['signup_url'])): ?>
                                    <div class="d-flex align-items-center gap-1">
                                        <a href="<?= htmlspecialchars($company['signup_url']) ?>" target="enrollerwindow" rel="noopener" class="text-truncate small" style="max-width:180px" title="<?= htmlspecialchars($company['signup_url']) ?>"><?= htmlspecialchars($company['signup_domain'] ?? parse_url($company['signup_url'], PHP_URL_HOST) ?? $company['signup_url']) ?></a>
                                        <button class="btn btn-outline-secondary btn-sm px-1 py-0" onclick="copyToClipboard('<?= htmlspecialchars(addslashes($company['signup_url']), ENT_QUOTES) ?>')" title="Copy URL"><i class="bi bi-clipboard"></i></button>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= $badgeClass ?>" id="status-<?= $companyId ?>"><?= htmlspecialchars($status) ?></span></td>
                            <td class="field-mapping-cell">
                                <?php if (!empty($company['FIELDMAPPING']) && is_array($company['FIELDMAPPING'])): ?>
                                    <details class="field-details" open>
                                        <summary class="text-primary" style="cursor:pointer"><?= count($company['FIELDMAPPING']) ?> fields</summary>
                                        <table class="table table-sm table-borderless mt-1 mb-0" style="font-size:0.8rem">
                                            <?php foreach ($company['FIELDMAPPING'] as $mappingKey => $mappingValue):
                                                $parts = explode('||', $mappingKey, 2);
                                                $fieldName = $parts[1] ?? $mappingKey;
                                            ?>
                                            <tr>
                                                <td class="text-muted py-0" style="width:35%"><?= htmlspecialchars($fieldName) ?></td>
                                                <td class="py-0">
                                                    <span class="d-inline-flex align-items-center gap-1">
                                                        <strong><?= htmlspecialchars($mappingValue) ?></strong>
                                                        <button class="btn btn-link btn-sm p-0 text-muted mapping-copy-btn" onclick="copyToClipboard('<?= htmlspecialchars(addslashes($mappingValue), ENT_QUOTES) ?>')" title="Copy value"><i class="bi bi-clipboard" style="font-size:0.7rem"></i></button>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    </details>
                                <?php else: ?>
                                    <span class="text-muted">No mappings</span>
                                <?php endif; ?>
                            </td>
                            <td class="action-cell">
                                <div class="d-flex flex-column gap-1">
                                    <button class="btn btn-success btn-sm" onclick="updateStatus(<?= $ucid ?>, <?= $companyId ?>, 'success-btn')" title="Mark as Enrolled">
                                        <i class="bi bi-check-circle"></i> Success
                                    </button>
                                    <div class="btn-group">
                                        <button class="btn btn-danger btn-sm dropdown-toggle" data-bs-toggle="dropdown" title="Mark as Failed">
                                            <i class="bi bi-x-circle"></i> Failed
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item small" href="#" onclick="updateStatus(<?= $ucid ?>, <?= $companyId ?>, 'failed-exists'); return false;">Account Exists</a></li>
                                            <li><a class="dropdown-item small" href="#" onclick="updateStatus(<?= $ucid ?>, <?= $companyId ?>, 'failed-password'); return false;">Password Failed</a></li>
                                            <li><a class="dropdown-item small" href="#" onclick="updateStatus(<?= $ucid ?>, <?= $companyId ?>, 'failed-missing'); return false;">Missing Data</a></li>
                                            <li><a class="dropdown-item small" href="#" onclick="updateStatus(<?= $ucid ?>, <?= $companyId ?>, 'failed-form'); return false;">Form Failure</a></li>
                                            <li><a class="dropdown-item small" href="#" onclick="updateStatus(<?= $ucid ?>, <?= $companyId ?>, 'failed-research'); return false;">Research Failed</a></li>
                                        </ul>
                                    </div>
                                    <a href="<?= $enrollerLink ?>" target="enrollerwindow" class="btn btn-outline-primary btn-sm" title="Open in full Member Enroller">
                                        <i class="bi bi-box-arrow-up-right"></i> Enroller
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 4: Task for Comet instruction block -->
<div class="comet-section">
    <div class="comet-instruction-box">
        <h5 class="mb-2">Task for Comet</h5>
        <p class="mb-0">For the user <strong><?= htmlspecialchars(($userDetails['profile_first_name'] ?? '') . ' ' . ($userDetails['profile_last_name'] ?? '')) ?></strong> (<?= htmlspecialchars($userDetails['profile_email'] ?? '') ?>), open each <strong>PENDING</strong> program's signup URL listed above. For each program, create an enrollment/account using the profile data and field mappings provided. Use the exact field mapping values shown for each program — these have already been formatted for that specific company's signup form. Report the result for each program: enrolled successfully or failed with reason.</p>
    </div>
</div>

<?php endif; ?>

</section>

<script>
var COMET_AID = <?= intval($aid) ?>;
var COMET_UID = <?= intval($userId) ?>;

function updateStatus(ucid, companyId, action) {
    var row = document.getElementById('row-' + companyId);
    var badge = document.getElementById('status-' + companyId);

    var params = new URLSearchParams();
    params.append('ucid', ucid);
    params.append('cid', companyId);
    params.append('aid', COMET_AID);
    params.append('uid', COMET_UID);
    params.append('act', action);
    params.append('message', 'done');
    params.append('version', 'comet-enroller-1.0');

    fetch('/admin/bgreb_v3/bgr_actions.php?' + params.toString())
    .then(function(response) {
        if (response.ok) {
            // Update badge
            var isSuccess = action.indexOf('success') !== -1;
            badge.className = 'badge ' + (isSuccess ? 'bg-success' : 'bg-danger');
            badge.textContent = isSuccess ? 'success' : action.replace('-', ': ');
            // Update row class
            row.className = 'program-row ' + (isSuccess ? 'row-success' : 'row-failed');
        } else {
            alert('Failed to update status. HTTP ' + response.status);
        }
    })
    .catch(function(err) {
        alert('Error updating status: ' + err.message);
    });
}

function toggleAllMappings(expand) {
    document.querySelectorAll('.field-details').forEach(function(el) {
        el.open = expand;
    });
}

function copyJson() {
    var jsonText = document.getElementById('user-json').textContent;
    navigator.clipboard.writeText(jsonText).then(function() {
        var btn = event.target;
        var orig = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(function() { btn.textContent = orig; }, 1500);
    });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        var tooltip = document.createElement('div');
        tooltip.className = 'position-fixed bg-dark text-white px-2 py-1 rounded';
        tooltip.style.zIndex = '9999';
        tooltip.textContent = 'Copied!';
        document.body.appendChild(tooltip);
        document.addEventListener('mousemove', function handler(e) {
            tooltip.style.left = (e.clientX + 10) + 'px';
            tooltip.style.top = (e.clientY + 10) + 'px';
            document.removeEventListener('mousemove', handler);
        });
        setTimeout(function() { tooltip.remove(); }, 1000);
    });
}
</script>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
