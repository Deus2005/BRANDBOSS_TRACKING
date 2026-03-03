<?php
/**
 * Change Password
 */
$pageTitle = 'Change Password';
$breadcrumbs = [['title' => 'Change Password']];

require_once 'includes/header.php';

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($currentPassword)) {
        $errors['current_password'] = 'Current password is required';
    }
    
    if (empty($newPassword)) {
        $errors['new_password'] = 'New password is required';
    } elseif (strlen($newPassword) < 6) {
        $errors['new_password'] = 'Password must be at least 6 characters';
    } elseif ($newPassword === $currentPassword) {
        $errors['new_password'] = 'New password must be different from current password';
    }
    
    if ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        $result = $auth->changePassword($auth->userId(), $currentPassword, $newPassword);
        
        if ($result['success']) {
            $success = true;
        } else {
            $errors['current_password'] = $result['message'];
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-key me-2"></i>Change Password
    </h1>
    <a href="profile.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Profile
    </a>
</div>

<?php if ($success): ?>
<div class="alert alert-success">
    <i class="bi bi-check-circle me-2"></i>
    Password changed successfully! You can continue using the system.
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-primary">
                <i class="bi bi-shield-lock me-2"></i>Change Your Password
            </div>
            <div class="card-body">
                <?php if (!$success): ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control <?php echo isset($errors['current_password']) ? 'is-invalid' : ''; ?>" 
                                   id="current_password" name="current_password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['current_password'])): ?>
                        <div class="text-danger small mt-1"><?php echo $errors['current_password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
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
                        <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
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
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="profile.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Change Password
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-check-circle display-1 text-success mb-3"></i>
                    <h4>Password Updated!</h4>
                    <p class="text-muted">Your password has been successfully changed.</p>
                    <a href="index.php" class="btn btn-primary">
                        <i class="bi bi-house me-1"></i>Go to Dashboard
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body">
                <h6><i class="bi bi-lightbulb me-2"></i>Password Tips</h6>
                <ul class="mb-0 small text-muted">
                    <li>Use at least 6 characters</li>
                    <li>Mix uppercase and lowercase letters</li>
                    <li>Include numbers and special characters</li>
                    <li>Avoid using personal information</li>
                    <li>Don't reuse passwords from other sites</li>
                </ul>
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

require_once 'includes/footer.php'; 
?>