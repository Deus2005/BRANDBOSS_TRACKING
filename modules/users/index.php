<?php
/**
 * User Management
 */
$pageTitle = 'Users';
$breadcrumbs = [['title' => 'Users']];

require_once '../../includes/header.php';

$auth->requirePermission('users');

$db = Database::getInstance();
$currentRole = $auth->role();
$currentUserId = $auth->userId();

/**
 * Handle suspend / activate action
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_action'], $_POST['user_id'])) {
    $targetUserId = (int) $_POST['user_id'];
    $userAction = trim($_POST['user_action']);

    if ($targetUserId === $currentUserId) {
        $_SESSION['flash_message'] = 'You cannot change your own account status.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: index.php');
        exit;
    }

    if (!in_array($userAction, ['activate', 'suspend'], true)) {
        $_SESSION['flash_message'] = 'Invalid action.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: index.php');
        exit;
    }

    // Get target user first
    $targetUser = $db->fetch(
        "SELECT id, role, status, first_name, last_name FROM users WHERE id = ? LIMIT 1",
        [$targetUserId]
    );

    if (!$targetUser) {
        $_SESSION['flash_message'] = 'User not found.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: index.php');
        exit;
    }

    // Permission restriction:
    // user_1 can only manage user_2, user_3, user_4
    if ($currentRole === 'user_1' && !in_array($targetUser['role'], ['user_2', 'user_3', 'user_4'], true)) {
        $_SESSION['flash_message'] = 'You are not allowed to manage this user.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: index.php');
        exit;
    }

    // If not super admin or user_1, adjust this if needed for your system
    if (!in_array($currentRole, ['super_admin', 'user_1'], true)) {
        $_SESSION['flash_message'] = 'You do not have permission to update user status.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: index.php');
        exit;
    }

    $newStatus = ($userAction === 'activate') ? 'active' : 'suspended';

    $updated = $db->update(
        'users',
        ['status' => $newStatus],
        'id = ?',
        [$targetUserId]
    );

    if ($updated) {
        $_SESSION['flash_message'] = 'User status updated successfully.';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Failed to update user status.';
        $_SESSION['flash_type'] = 'danger';
    }

    header('Location: index.php');
    exit;
}

// Get filter parameters
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? 'active';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));

// Build query based on current user's role
$where = ['1=1'];
$params = [];

// User 1 can only manage User 2, 3, 4
if ($currentRole === 'user_1') {
    $where[] = "role IN ('user_2', 'user_3', 'user_4')";
}

if ($roleFilter) {
    $where[] = 'role = ?';
    $params[] = $roleFilter;
}

if ($statusFilter) {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}

if ($search) {
    $where[] = '(first_name LIKE ? OR last_name LIKE ? OR username LIKE ? OR email LIKE ? OR employee_id LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

$sql = "SELECT * FROM users WHERE {$whereClause} ORDER BY role, first_name, last_name";
$result = $db->paginate($sql, $params, $page, ITEMS_PER_PAGE);
$users = $result['data'];

// Get manageable roles for dropdown
$manageableRoles = $currentRole === 'super_admin'
    ? ['super_admin', 'user_1', 'user_2', 'user_3', 'user_4']
    : ['user_2', 'user_3', 'user_4'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-people me-2"></i>User Management
    </h1>
    <a href="create.php" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> <span class="d-none d-md-inline">Add User</span>
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text"
                       class="form-control"
                       name="search"
                       placeholder="Search users..."
                       value="<?php echo clean($search); ?>">
            </div>

            <div class="col-md-3">
                <select name="role" class="form-select" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    <?php foreach ($manageableRoles as $role): ?>
                    <option value="<?php echo clean($role); ?>" <?php echo $roleFilter === $role ? 'selected' : ''; ?>>
                        <?php echo roleName($role); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="suspended" <?php echo $statusFilter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                </select>
            </div>

            <div class="col-md-4">
                <a href="index.php" class="btn btn-danger">
                    <i class="bi bi-x-lg"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-people display-6 d-block mb-2"></i>
                                No users found
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?php echo clean($user['employee_id']); ?></strong></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                        <?php
                                        $first = isset($user['first_name']) ? substr($user['first_name'], 0, 1) : '';
                                        $last  = isset($user['last_name']) ? substr($user['last_name'], 0, 1) : '';
                                        echo strtoupper($first . $last);
                                        ?>
                                    </div>
                                    <?php echo clean($user['first_name'] . ' ' . $user['last_name']); ?>
                                </div>
                            </td>
                            <td><?php echo clean($user['username']); ?></td>
                            <td><?php echo clean($user['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $user['role'] === 'super_admin' ? 'danger' : 'primary'; ?>">
                                    <?php echo roleName($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo statusBadge($user['status']); ?></td>
                            <td>
                                <?php echo $user['last_login'] ? formatDateTime($user['last_login']) : '<span class="text-muted">Never</span>'; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($user['id'] != $currentUserId): ?>
                                    <div class="droplist">
                                        <button type="button"
                                                class="btn btn-sm btn-link text-dark p-0 border-0"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>

                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="edit.php?id=<?php echo $user['id']; ?>">
                                                    <i class="bi bi-pencil me-2"></i>Edit
                                                </a>
                                            </li>

                                            <li>
                                                <a class="dropdown-item" href="reset-password.php?id=<?php echo $user['id']; ?>">
                                                    <i class="bi bi-key me-2"></i>Reset Password
                                                </a>
                                            </li>

                                            <li><hr class="dropdown-divider"></li>

                                            <li>
                                                <form method="POST" class="m-0">
                                                    <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">

                                                    <?php if ($user['status'] === 'active'): ?>
                                                        <input type="hidden" name="user_action" value="suspend">
                                                        <button type="submit"
                                                                class="dropdown-item text-danger"
                                                                onclick="return confirm('Are you sure you want to suspend this user?');">
                                                            <i class="bi bi-pause me-2"></i>Suspend
                                                        </button>
                                                    <?php else: ?>
                                                        <input type="hidden" name="user_action" value="activate">
                                                        <button type="submit"
                                                                class="dropdown-item text-success"
                                                                onclick="return confirm('Are you sure you want to activate this user?');">
                                                            <i class="bi bi-play me-2"></i>Activate
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-danger">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($result['total_pages'] > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing <?php echo (($page - 1) * ITEMS_PER_PAGE) + 1; ?> -
                <?php echo min($page * ITEMS_PER_PAGE, $result['total']); ?>
                of <?php echo $result['total']; ?> users
            </small>
            <?php echo paginationHtml($result['current_page'], $result['total_pages'], 'index.php'); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>