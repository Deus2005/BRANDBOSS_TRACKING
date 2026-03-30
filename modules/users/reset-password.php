<?php
/**
 * Reset User Password
 */
$pageTitle = 'Reset Password';
$breadcrumbs = [
    ['title' => 'Users', 'url' => 'index.php'],
    ['title' => 'Reset Password']
];

require_once '../../includes/header.php';

$auth->requirePermission('users');

$db = Database::getInstance();
$currentRole = $auth->role();
$currentUserId = $auth->userId();
$errors = [];
$success = false;

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
    redirect('index.php', 'You cannot reset password for this user', 'danger');
}

// Cannot reset own password through this page
if ($userId == $currentUserId) {
    redirect(APP_URL . '/change-password.php', 'Please use change password page for your own account', 'info');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($newPassword)) {
        $errors['new_password'] = 'New password is required';
    } elseif (strlen($newPassword) < 6) {
        $errors['new_password'] = 'Password must be at least 6 characters';
    } elseif ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        try {
            $hashedPassword = $auth->hashPassword($newPassword);
            
            $db->update('users', ['password' => $hashedPassword], 'id = ?', [$userId]);
            
            // Log activity
            $auth->logActivity($currentUserId, 'reset_password', 'users', 'users', $userId);
            
            // Notify user
            createNotification(
                $userId,
                'Password Reset',
                'Your password has been reset by an administrator. Please login with your new password.',
                'warning'
            );
            
            $success = true;
            
        } catch (Exception $e) {
            $errors['general'] = 'Failed to reset password. Please try again.';
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-key me-2"></i>Reset Password
    </h1>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if ($success): ?>
<div class="alert alert-success">
    <i class="bi bi-check-circle me-2"></i>
    Password reset successfully! The user has been notified.
    <a href="index.php" class="alert-link ms-2">Back to Users</a>
</div>
<?php endif; ?>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger"><?php echo $errors['general']; ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <span class="d-flex align-text-center">
                <span class="bi bi-exclamation-triangle me-2"></span>
                Reset Password for User
</span>
            </div>
            <div class="card-body">
                <!-- User Info -->
               <div class="d-flex align-items-center mb-4 p-3 bg-light rounded">

    <!-- Avatar -->
    <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center me-3"
         style="width:50px;height:50px;font-weight:600;font-size:18px;">
        <?php 
        echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); 
        ?>
    </div>

    <!-- User info -->
    <div>
        <h5 class="mb-0">
            <?php echo clean($user['first_name'] . ' ' . $user['last_name']); ?>
        </h5>
        <small class="text-muted">
            <?php echo clean($user['username']); ?> • <?php echo roleName($user['role']); ?>
        </small>
    </div>

</div>
                
                <?php if (!$success): ?>
                <form method="POST" action="">
                    <input type="hidden" name="id" value="<?php echo $userId; ?>">
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control <?php echo isset($errors['new_password']) ? 'is-invalid' : ''; ?>" 
                                   id="new_password" name="new_password" required minlength="6">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['new_password'])): ?>
                        <div class="text-danger small mt-1"><?php echo $errors['new_password']; ?></div>
                        <?php endif; ?>
                        <div class="form-text">Minimum 6 characters</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                   id="confirm_password" name="confirm_password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['confirm_password'])): ?>
                        <div class="text-danger small mt-1"><?php echo $errors['confirm_password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="alert alert-info py-2">
                        <i class="bi bi-info-circle me-2"></i>
                        The user will be notified that their password has been reset.
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key me-1"></i>Reset Password
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
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