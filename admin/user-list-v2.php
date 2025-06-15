<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Ensure admin access
if (!$account->isadmin()) {
    header('Location: /');
    exit;
}

// Get parameters
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 25);
$offset = ($page - 1) * $limit;

// Filters
$search = $_GET['search'] ?? '';
$userType = $_GET['userType'] ?? '';
$plan = $_GET['plan'] ?? '';
$status = $_GET['status'] ?? '';
$sortBy = $_GET['sortBy'] ?? 'newest';

// Build query conditions
$conditions = [];
$params = [];

// Always exclude sensitive system users
$conditions[] = "u.username NOT IN ('system', 'admin', 'root')";

// Check if we have real users
$checkRealUsers = $database->prepare("SELECT COUNT(*) as count FROM bg_users WHERE type = 'real'");
$checkRealUsers->execute();
$result = $checkRealUsers->fetch(PDO::FETCH_ASSOC);
$hasRealUsers = $result && $result['count'] > 0;

if ($userType) {
    $conditions[] = "u.type = :userType";
    $params[':userType'] = $userType;
} else if ($hasRealUsers) {
    // Default to real users if they exist
    $conditions[] = "u.type = 'real'";
}

if ($plan) {
    $conditions[] = "u.account_plan = :plan";
    $params[':plan'] = $plan;
}

if ($status) {
    $conditions[] = "u.status = :status";
    $params[':status'] = $status;
}

if ($search) {
    $searchLike = "%$search%";
    $conditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.username LIKE :search OR u.email LIKE :search)";
    $params[':search'] = $searchLike;
} else if (empty($userType) && empty($plan) && empty($status)) {
    // Default: show users from last 2 weeks when no filters are active
    $checkSql = "SELECT COUNT(*) FROM bg_users WHERE 1=1";
    if ($hasRealUsers) {
        $checkSql .= " AND type = 'real'";
    }
    $checkSql .= " AND create_dt >= DATE_SUB(NOW(), INTERVAL 14 DAY)";
    $checkResult = $database->prepare($checkSql);
    $checkResult->execute();
    $row = $checkResult->fetch(PDO::FETCH_ASSOC);
    $recentCount = $row ? $row['count'] : 0;
    
    if ($recentCount >= 30) {
        $conditions[] = "u.create_dt >= DATE_SUB(NOW(), INTERVAL 14 DAY)";
    }
}

// Ensure we have at least one condition
if (empty($conditions)) {
    $conditions[] = "1=1";
}

$whereClause = implode(' AND ', $conditions);

// Determine sort order
$orderBy = match($sortBy) {
    'oldest' => 'u.create_dt ASC',
    'name' => 'u.first_name ASC, u.last_name ASC',
    'recent_login' => 'lt.last_login_dt DESC',
    default => 'u.create_dt DESC'
};

// Get user statistics
$statsData = [];
try {
    // Total users
    if ($hasRealUsers) {
        $totalStmt = $database->prepare("SELECT COUNT(*) FROM bg_users WHERE type = 'real'");
    } else {
        $totalStmt = $database->prepare("SELECT COUNT(*) FROM bg_users WHERE username NOT IN ('system', 'admin', 'root')");
    }
    $totalStmt->execute();
    $statsData['totalUsers'] = $totalStmt->fetchColumn();

    // New users today
    if ($hasRealUsers) {
        $todayStmt = $database->prepare("SELECT COUNT(*) FROM bg_users WHERE type = 'real' AND DATE(create_dt) = CURDATE()");
    } else {
        $todayStmt = $database->prepare("SELECT COUNT(*) FROM bg_users WHERE username NOT IN ('system', 'admin', 'root') AND DATE(create_dt) = CURDATE()");
    }
    $todayStmt->execute();
    $statsData['newToday'] = $todayStmt->fetchColumn();

    // Paid users
    if ($hasRealUsers) {
        $paidStmt = $database->prepare("SELECT COUNT(*) FROM bg_users WHERE type = 'real' AND account_plan != 'free' AND account_plan IS NOT NULL");
    } else {
        $paidStmt = $database->prepare("SELECT COUNT(*) FROM bg_users WHERE username NOT IN ('system', 'admin', 'root') AND account_plan != 'free' AND account_plan IS NOT NULL");
    }
    $paidStmt->execute();
    $statsData['paidUsers'] = $paidStmt->fetchColumn();

    // Active users (logged in within last 30 days)
    if ($hasRealUsers) {
        $activeStmt = $database->prepare("
            SELECT COUNT(DISTINCT u.user_id)
            FROM bg_users u
            INNER JOIN bg_logintracking lt ON u.user_id = lt.user_id
            WHERE u.type = 'real' 
            AND lt.modify_dt >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND lt.status = 'A'
        ");
    } else {
        $activeStmt = $database->prepare("
            SELECT COUNT(DISTINCT u.user_id)
            FROM bg_users u
            INNER JOIN bg_logintracking lt ON u.user_id = lt.user_id
            WHERE u.username NOT IN ('system', 'admin', 'root')
            AND lt.modify_dt >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND lt.status = 'A'
        ");
    }
    $activeStmt->execute();
    $statsData['activeUsers'] = $activeStmt->fetchColumn();
    
    $statsData['activeRate'] = $statsData['totalUsers'] > 0 ? round(($statsData['activeUsers'] / $statsData['totalUsers']) * 100, 1) : 0;
} catch (Exception $e) {
    // Set defaults if stats fail
    $statsData = [
        'totalUsers' => 0,
        'newToday' => 0,
        'paidUsers' => 0,
        'activeUsers' => 0,
        'activeRate' => 0
    ];
}

// Count total users for pagination
$countSql = "SELECT COUNT(DISTINCT u.user_id) as total FROM bg_users u WHERE $whereClause";
$countStmt = $database->prepare($countSql);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $limit);

// Get users with pagination
$sql = "
    SELECT 
        u.user_id,
        u.first_name,
        u.last_name,
        u.username,
        u.email,
        u.birthdate,
        u.phone_number as phone,
        u.city,
        u.state,
        u.country,
        u.status,
        u.account_plan,
        u.account_type,
        u.create_dt,
        u.modify_dt,
        DATEDIFF(NOW(), u.create_dt) as days_old,
        TIMESTAMPDIFF(YEAR, u.birthdate, CURDATE()) as age,
        a.description as avatar,
        lt.last_login_dt,
        admin_attr.description as account_admin,
        staff_attr.description as account_staff
    FROM bg_users u
    LEFT JOIN bg_user_attributes a ON u.user_id = a.user_id 
        AND a.name = 'avatar' 
        AND a.category = 'primary' 
        AND a.status = 'active'
    LEFT JOIN bg_user_attributes admin_attr ON u.user_id = admin_attr.user_id 
        AND admin_attr.name = 'account_admin' 
        AND admin_attr.status = 'active'
    LEFT JOIN bg_user_attributes staff_attr ON u.user_id = staff_attr.user_id 
        AND staff_attr.name = 'account_staff' 
        AND staff_attr.status = 'active'
    LEFT JOIN (
        SELECT user_id, MAX(modify_dt) as last_login_dt 
        FROM bg_logintracking 
        WHERE status = 'A' 
        GROUP BY user_id
    ) lt ON u.user_id = lt.user_id
    WHERE $whereClause
    ORDER BY $orderBy
    LIMIT :limit OFFSET :offset
";

$stmt = $database->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$users = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Clean up avatar URL
    if ($row['avatar']) {
        $row['avatar'] = str_replace('cdn.birthday.gold', $website['cdnurl'], $row['avatar']);
    }
    
    // Add badges/flags based on attributes
    $row['is_admin'] = !empty($row['account_admin']) && $row['account_admin'] !== 'N';
    $row['is_staff'] = !empty($row['account_staff']) && $row['account_staff'] !== 'N';
    $row['is_verified'] = $account->isverified('*', $row['user_id']);
    
    $users[] = $row;
}

$newheader = 'x';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Enhanced User List with Dynamic Search and Pagination -->
<section class="mt-0 pt-0 main-content container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">User Management</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary" id="exportBtn">
                <i class="bi bi-download"></i> Export
            </button>
            <a href="/admin/" class="btn btn-sm btn-outline-secondary">Back to Admin</a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Users</h6>
                            <h3 class="mb-0"><?= number_format($statsData['totalUsers']) ?></h3>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-people-fill fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">New Today</h6>
                            <h3 class="mb-0"><?= $statsData['newToday'] ?></h3>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-person-plus-fill fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Paid Users</h6>
                            <h3 class="mb-0"><?= number_format($statsData['paidUsers']) ?></h3>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-credit-card-fill fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Active Rate</h6>
                            <h3 class="mb-0"><?= $statsData['activeRate'] ?>%</h3>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-activity fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="get" action="" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search users</label>
                    <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Search by name, email, username...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">User Type</label>
                    <select class="form-select" name="userType">
                        <option value="">All Users</option>
                        <option value="real" <?= $userType === 'real' ? 'selected' : '' ?>>Real Users</option>
                        <option value="test" <?= $userType === 'test' ? 'selected' : '' ?>>Test Users</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Plan</label>
                    <select class="form-select" name="plan">
                        <option value="">All Plans</option>
                        <option value="free" <?= $plan === 'free' ? 'selected' : '' ?>>Free</option>
                        <option value="silver" <?= $plan === 'silver' ? 'selected' : '' ?>>Silver</option>
                        <option value="gold" <?= $plan === 'gold' ? 'selected' : '' ?>>Gold</option>
                        <option value="platinum" <?= $plan === 'platinum' ? 'selected' : '' ?>>Platinum</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="locked" <?= $status === 'locked' ? 'selected' : '' ?>>Locked</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sort By</label>
                    <select class="form-select" name="sortBy">
                        <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                        <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Name</option>
                        <option value="recent_login" <?= $sortBy === 'recent_login' ? 'selected' : '' ?>>Recent Login</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Last Login</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    No users found matching your criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr class="align-middle" style="cursor: pointer;" 
                                    onclick="window.location.href='/admin/user-details.php?id=<?= $user['user_id'] ?>'">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= $user['avatar'] ?: '/public/images/defaultavatar.png' ?>" 
                                                 class="rounded-circle me-3 avatar-sm" alt="Avatar">
                                            <div>
                                                <div class="fw-semibold">
                                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                                    <?php if ($user['is_admin']): ?>
                                                        <span class="badge bg-danger ms-1">Admin</span>
                                                    <?php elseif ($user['is_staff']): ?>
                                                        <span class="badge bg-info ms-1">Staff</span>
                                                    <?php endif; ?>
                                                    <?php if ($user['is_verified']): ?>
                                                        <i class="bi bi-patch-check-fill text-primary ms-1"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="small text-muted">@<?= htmlspecialchars($user['username'] ?: 'N/A') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($user['email']) ?></div>
                                        <?php if ($user['phone']): ?>
                                            <div class="small text-muted"><?= htmlspecialchars($user['phone']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $planClass = match($user['account_plan']) {
                                            'platinum' => 'bg-dark',
                                            'gold' => 'bg-warning text-dark',
                                            'silver' => 'bg-secondary',
                                            default => 'bg-light text-dark'
                                        };
                                        ?>
                                        <span class="badge <?= $planClass ?>">
                                            <?= ucfirst($user['account_plan'] ?: 'free') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match($user['status']) {
                                            'active' => 'bg-success',
                                            'inactive' => 'bg-secondary',
                                            'locked' => 'bg-danger',
                                            'pending' => 'bg-warning text-dark',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $statusClass ?>">
                                            <?= ucfirst($user['status'] ?: 'Active') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><?= date('M d, Y', strtotime($user['create_dt'])) ?></div>
                                        <div class="small text-muted"><?= $user['days_old'] ?> days ago</div>
                                    </td>
                                    <td>
                                        <?php if ($user['last_login_dt']): ?>
                                            <?= date('M d, Y', strtotime($user['last_login_dt'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="event.stopPropagation(); viewUserDetails(<?= $user['user_id'] ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" 
                                                onclick="event.stopPropagation(); editUser(<?= $user['user_id'] ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer border-0 d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Showing <?= ($offset + 1) ?> to <?= min($offset + $limit, $totalUsers) ?> of <?= $totalUsers ?> entries
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
                            <!-- Previous -->
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            
                            <!-- Page Numbers -->
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">
                                        <?= $totalPages ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <!-- Next -->
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
.stats-card {
    transition: transform 0.2s;
}

.stats-card:hover {
    transform: translateY(-2px);
}

.avatar-sm {
    width: 40px;
    height: 40px;
    object-fit: cover;
}

.table > :not(caption) > * > * {
    padding: 1rem 0.75rem;
}

.pagination {
    gap: 0.25rem;
}

.pagination .page-link {
    border-radius: 0.25rem;
    margin: 0 2px;
}
</style>

<script>
// Export functionality
document.getElementById('exportBtn').addEventListener('click', function() {
    const queryParams = new URLSearchParams(window.location.search);
    queryParams.set('export', 'csv');
    window.location.href = '/admin/export-users.php?' + queryParams.toString();
});

// View user details
function viewUserDetails(userId) {
    // Load user details via AJAX
    fetch('/admin/user_components/get_userdetails.php?id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                const content = `
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <img src="${user.avatar || '/public/images/defaultavatar.png'}" 
                                 class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                            <h5>${user.first_name} ${user.last_name}</h5>
                            <p class="text-muted">@${user.username || 'N/A'}</p>
                        </div>
                        <div class="col-md-8">
                            <h6 class="text-muted mb-3">User Information</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>${user.email}</td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td>${user.phone || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Birthday:</strong></td>
                                    <td>${user.birthdate || 'N/A'} (Age: ${user.age || 'N/A'})</td>
                                </tr>
                                <tr>
                                    <td><strong>Location:</strong></td>
                                    <td>${user.city || ''} ${user.state || ''} ${user.country || ''}</td>
                                </tr>
                                <tr>
                                    <td><strong>Account Plan:</strong></td>
                                    <td><span class="badge bg-secondary">${user.account_plan || 'free'}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td><span class="badge bg-success">${user.status}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Joined:</strong></td>
                                    <td>${new Date(user.create_dt).toLocaleDateString()}</td>
                                </tr>
                                <tr>
                                    <td><strong>Last Login:</strong></td>
                                    <td>${user.last_login_dt ? new Date(user.last_login_dt).toLocaleDateString() : 'Never'}</td>
                                </tr>
                            </table>
                            
                            <div class="mt-4">
                                <a href="/admin/user-details.php?id=${userId}" class="btn btn-primary btn-sm">
                                    View Full Details
                                </a>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('userDetailsContent').innerHTML = content;
                const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
                modal.show();
            }
        })
        .catch(error => {
            console.error('Error loading user details:', error);
        });
}

// Edit user
function editUser(userId) {
    window.location.href = '/admin/user-details.php?id=' + userId + '&edit=1';
}
</script>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>