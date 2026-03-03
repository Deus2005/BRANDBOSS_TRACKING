<?php
/**
 * Edit User
 */
$pageTitle = 'Edit User';
$breadcrumbs = [
    ['title' => 'Users', 'url' => 'index.php'],
    ['title' => 'Edit User']
];

require_once '../../includes/header.php';

$auth->requirePermission('users');

$db = Database::getInstance();
$currentRole = $auth->role();
$currentUserId = $auth->userId();
$errors = [];

// Get user ID
$userId = intval($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$userId) {
    redirect('index.php', 'Invalid user', 'danger');
}

// Get user details
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);

if (!$user) {
    redirect('index.php', 'User not found', 'danger');
}

// Check permission to edit this user
if ($currentRole === 'user_1' && !in_array($user['role'], ['user_2', 'user_3', 'user_4'])) {
    redirect('index.php', 'You cannot edit this user', 'danger');
}

// Cannot edit own account through this page
if ($userId == $currentUserId) {
    redirect(APP_URL . '/profile.php', 'Please use profile page to edit your own account', 'info');
}

// Get manageable roles
$manageableRoles = $currentRole === 'super_admin' 
    ? ['super_admin' => 'Super Admin', 'user_1' => 'Manager (User 1)', 'user_2' => 'Installer (User 2)', 'user_3' => 'Inspector (User 3)', 'user_4' => 'Maintenance (User 4)']
    : ['user_2' => 'Installer (User 2)', 'user_3' => 'Inspector (User 3)', 'user_4' => 'Maintenance (User 4)'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'employee_id' => clean($_POST['employee_id'] ?? ''),
        'username' => clean($_POST['username'] ?? ''),
        'email' => clean($_POST['email'] ?? ''),
        'full_name' => clean($_POST['full_name'] ?? ''),
        'phone' => clean($_POST['phone'] ?? ''),
        'role' => $_POST['role'] ?? $user['role'],
        'status' => $_POST['status'] ?? $user['status']
    ];
    
    // Validation
    $errors = validateRequired($data, ['employee_id', 'username', 'email', 'full_name', 'role']);
    
    // Check unique fields (excluding current user)
    if (empty($errors)) {
        if ($db->exists('users', 'employee_id = ? AND id != ?', [$data['employee_id'], $userId])) {
            $errors['employee_id'] = 'Employee ID already exists';
        }
        if ($db->exists('users', 'username = ? AND id != ?', [$data['username'], $userId])) {
            $errors['username'] = 'Username already exists';
        }
        if ($db->exists('users', 'email = ? AND id != ?', [$data['email'], $userId])) {
            $errors['email'] = 'Email already exists';
        }
    }
    
    // Check role permission
    if (!array_key_exists($data['role'], $manageableRoles)) {
        $errors['role'] = 'Invalid role selection';
    }
    
    if (empty($errors)) {
        try {
            // Store old values for logging
            $oldValues = [
                'employee_id' => $user['employee_id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'phone' => $user['phone'],
                'role' => $user['role'],
                'status' => $user['status']
            ];
            
            $db->update('users', $data, 'id = ?', [$userId]);
            
            // Log activity with old/new values
            $auth->logActivity($currentUserId, 'updated_user', 'users', 'users', $userId, $oldValues, $data);
            
            redirect('index.php', 'User updated successfully!', 'success');
        } catch (Exception $e) {
            $errors['general'] = 'Failed to update user. Please try again.';
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-person-gear me-2"></i>Edit User
    </h1>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger"><?php echo $errors['general']; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-primary">
                <i class="bi bi-person me-2"></i>User Information
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="id" value="<?php echo $userId; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo isset($errors['employee_id']) ? 'is-invalid' : ''; ?>" 
                                   id="employee_id" name="employee_id" 
                                   value="<?php echo clean($_POST['employee_id'] ?? $user['employee_id']); ?>" required>
                            <?php if (isset($errors['employee_id'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['employee_id']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>" 
                                   id="full_name" name="full_name" 
                                   value="<?php echo clean($_POST['full_name'] ?? $user['full_name']); ?>" required>
                            <?php if (isset($errors['full_name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['full_name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                                   id="username" name="username" 
                                   value="<?php echo clean($_POST['username'] ?? $user['username']); ?>" required>
                            <?php if (isset($errors['username'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" 
                                   value="<?php echo clean($_POST['email'] ?? $user['email']); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo clean($_POST['phone'] ?? $user['phone']); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select <?php echo isset($errors['role']) ? 'is-invalid' : ''; ?>" 
                                    id="role" name="role" required>
                                <?php foreach ($manageableRoles as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($_POST['role'] ?? $user['role']) === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['role'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['role']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo ($_POST['status'] ?? $user['status']) === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($_POST['status'] ?? $user['status']) === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="suspended" <?php echo ($_POST['status'] ?? $user['status']) === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <a href="reset-password.php?id=<?php echo $userId; ?>" class="btn btn-outline-warning">
                            <i class="bi bi-key me-1"></i>Reset Password
                        </a>
                        <div>
                            <a href="index.php" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Update User
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-primary">
                <i class="bi bi-info-circle me-2"></i>Account Info
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="user-avatar mx-auto mb-2" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <h5 class="mb-1"><?php echo clean($user['full_name']); ?></h5>
                    <span class="badge bg-<?php echo $user['role'] === 'super_admin' ? 'danger' : 'primary'; ?>">
                        <?php echo roleName($user['role']); ?>
                    </span>
                </div>
                
                <hr>
                
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Created:</td>
                        <td><?php echo formatDateTime($user['created_at']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Last Login:</td>
                        <td><?php echo $user['last_login'] ? formatDateTime($user['last_login']) : '<span class="text-muted">Never</span>'; ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status:</td>
                        <td><?php echo statusBadge($user['status']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>