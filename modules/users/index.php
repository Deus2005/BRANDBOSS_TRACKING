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
    $where[] = '(full_name LIKE ? OR username LIKE ? OR email LIKE ? OR employee_id LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

$sql = "SELECT * FROM users WHERE {$whereClause} ORDER BY role, full_name";
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
                <input type="text" class="form-control" name="search" placeholder="Search users..." 
                       value="<?php echo clean($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select" name="form-select">
                    <option value="">All Roles</option>
                    <?php foreach ($manageableRoles as $role): ?>
                    <option value="<?php echo $role; ?>" <?php echo $roleFilter === $role ? 'selected' : ''; ?>>
                        <?php echo roleName($role); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select" name="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="suspended" <?php echo $statusFilter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
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
                            <?php 
                                $color = '';
                                if($user['role'] == 'super_admin') {
                                    $color = '#DC3545';
                                } elseif ($user['role'] == 'user_1') {
                                    $color = '#0d6efd ';
                                } elseif ($user['role'] == 'user_2') {
                                    $color = '#FFC107';
                                } elseif ($user['role'] == 'user_3') {
                                    $color = '#6C757D';
                                } elseif ($user['role'] == 'user_4') {
                                    $color = '#F8D7DA';
                                }
                            ?>
                    <tr>
                        <td><strong><?php echo clean($user['employee_id']); ?></strong></td>
                        <td>

                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-2" style="width: 32px; height: 32px; font-size: 0.8rem; background: <?php echo $color; ?>">
                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                </div>
                                <?php echo clean($user['full_name']); ?>
                            </div>
                        </td>
                        <td><?php echo clean($user['username']); ?></td>
                        <td><?php echo clean($user['email']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $user['role'] === 'super_admin' ? 'danger' : 'primary'; ?>" style="color: #000000; font-weight: bold; background: none !important">
                                <?php echo roleName($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo statusBadge($user['status']); ?></td>
                        <td>
                            <?php echo $user['last_login'] ? formatDateTime($user['last_login']) : '<span class="text-muted">Never</span>'; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($user['id'] != $auth->userId()): ?>
                                <div class="action-btn">
                                    <a href="#" class="menu-toggle" style="font-size:1.5rem;text-decoration:none;color:#000;">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </a>
                                    <div class="action-dropdown">
                                        <div class="edit-container">
                                            <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn-edit">
                                                <div class="block-container">
                                                    <div class="edit icon">
                                                        <i class="bi bi-key"></i>
                                                    </div>
                                                    <div class="edit text">
                                                        Edit
                                                    </div>                                                 
                                                </div>
                                            </a>
                                        </div>
                                        <div class="reset-password-container">
                                            <a href="reset-password.php?id=<?php echo $user['id']; ?>" class="btn-reset-password">
                                                <div class="block-container">
                                                    <div class="reset icon">
                                                        <i class="bi bi-pencil"></i>
                                                    </div>
                                                    <div class="reset text">
                                                        Reset Password
                                                    </div>                                                 
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">Current User</span>
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
                Showing <?php echo (($page - 1) * ITEMS_PER_PAGE) + 1; ?> - <?php echo min($page * ITEMS_PER_PAGE, $result['total']); ?> 
                of <?php echo $result['total']; ?> users
            </small>
            <?php echo paginationHtml($result['current_page'], $result['total_pages'], 'index.php'); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="../../assets/js/action.js"></script>

<?php 
$extraScripts = <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('form');
    if (!form) return;

    var searchInput = form.querySelector('input[name="search"]');
    var filterSelects = form.querySelectorAll('select');

    // Longer debounce
    var autoSubmit = App.debounce(function() {
        form.submit();
    }, 800);

    if (searchInput) {
        searchInput.focus();

        // Move cursor to end safely
        setTimeout(function(){
            var valueLength = searchInput.value.length;
            searchInput.setSelectionRange(valueLength, valueLength);
        }, 0);

        searchInput.addEventListener('input', function() {
            autoSubmit();
        });
    }

    filterSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            form.submit(); // no need debounce for selects
        });
    });
});

document.querySelectorAll('.btn-status').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const action = this.dataset.action;
        const actionText = action === 'activate' ? 'activate' : 'suspend';
        
        if (confirm(`Are you sure you want to ${actionText} this user?`)) {
            App.ajax('ajax/user-status.php', { id: id, action: action })
                .then(response => {
                    if (response.success) {
                        App.toast(response.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        App.toast(response.message, 'danger');
                    }
                });
        }
    });
});
</script>
SCRIPT;

require_once '../../includes/footer.php'; 
?>
