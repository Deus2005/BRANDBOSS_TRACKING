<?php
/**
 * Create New User
 */
$pageTitle = 'Add User';
$breadcrumbs = [
    ['title' => 'Users', 'url' => 'index.php'],
    ['title' => 'Add User']
];

require_once '../../includes/header.php';

$auth->requirePermission('users');

$db = Database::getInstance();
$currentRole = $auth->role();
$errors = [];

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
        'role' => $_POST['role'] ?? '',
        'status' => $_POST['status'] ?? 'active',
        'created_by' => $auth->userId()
    ];
    
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    $errors = validateRequired($data, ['employee_id', 'username', 'email', 'full_name', 'role']);
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // Check unique fields
    if (empty($errors)) {
        if ($db->exists('users', 'employee_id = ?', [$data['employee_id']])) {
            $errors['employee_id'] = 'Employee ID already exists';
        }
        if ($db->exists('users', 'username = ?', [$data['username']])) {
            $errors['username'] = 'Username already exists';
        }
        if ($db->exists('users', 'email = ?', [$data['email']])) {
            $errors['email'] = 'Email already exists';
        }
    }
    
    // Check role permission
    if (!array_key_exists($data['role'], $manageableRoles)) {
        $errors['role'] = 'Invalid role selection';
    }
    
    if (empty($errors)) {
        $data['password'] = $auth->hashPassword($password);
        
        try {
            $userId = $db->insert('users', $data);
            $auth->logActivity($auth->userId(), 'created_user', 'users', 'users', $userId);
            
            redirect('index.php', 'User created successfully!', 'success');
        } catch (Exception $e) {
            $errors['general'] = 'Failed to create user. Please try again.';
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-person-plus me-2"></i>Add New User
    </h1>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger"><?php echo $errors['general']; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-primary">
        <i class="bi bi-person me-2"></i>User Information
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($errors['employee_id']) ? 'is-invalid' : ''; ?>" 
                           id="employee_id" name="employee_id" value="<?php echo clean($_POST['employee_id'] ?? ''); ?>" required>
                    <?php if (isset($errors['employee_id'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['employee_id']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>" 
                           id="full_name" name="full_name" value="<?php echo clean($_POST['full_name'] ?? ''); ?>" required>
                    <?php if (isset($errors['full_name'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['full_name']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                           id="username" name="username" value="<?php echo clean($_POST['username'] ?? ''); ?>" required>
                    <?php if (isset($errors['username'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                           id="email" name="email" value="<?php echo clean($_POST['email'] ?? ''); ?>" required>
                    <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           value="<?php echo clean($_POST['phone'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                    <select class="form-select <?php echo isset($errors['role']) ? 'is-invalid' : ''; ?>" 
                            id="role" name="role" required>
                        <option value="">Select Role</option>
                        <?php foreach ($manageableRoles as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo ($_POST['role'] ?? '') === $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['role'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['role']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                               id="password" name="password" required minlength="6">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                    <div class="text-danger small"><?php echo $errors['password']; ?></div>
                    <?php endif; ?>
                    <div class="form-text">Minimum 6 characters</div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                               id="confirm_password" name="confirm_password" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($errors['confirm_password'])): ?>
                    <div class="text-danger small"><?php echo $errors['confirm_password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo ($_POST['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($_POST['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <hr>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Create User
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
$extraScripts = <<<'SCRIPT'
<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>
SCRIPT;

require_once '../../includes/footer.php'; 
?>
