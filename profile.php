<?php
/**
 * User Profile
 */
$pageTitle = 'My Profile';
$breadcrumbs = [['title' => 'My Profile']];

require_once 'includes/header.php';

$db = Database::getInstance();
$userId = $auth->userId();
$errors = [];
$success = false;

// Get user details
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name' => clean($_POST['full_name'] ?? ''),
        'email' => clean($_POST['email'] ?? ''),
        'phone' => clean($_POST['phone'] ?? '')
    ];
    
    // Validation
    if (empty($data['full_name'])) {
        $errors['full_name'] = 'Full name is required';
    }
    
    if (empty($data['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } elseif ($db->exists('users', 'email = ? AND id != ?', [$data['email'], $userId])) {
        $errors['email'] = 'Email already in use';
    }
    
    if (empty($errors)) {
        try {
            $db->update('users', $data, 'id = ?', [$userId]);
            
            // Update session
            $_SESSION['full_name'] = $data['full_name'];
            $_SESSION['email'] = $data['email'];
            
            $auth->logActivity($userId, 'updated_profile', 'profile', 'users', $userId);
            
            $success = true;
            $user = array_merge($user, $data);
            
        } catch (Exception $e) {
            $errors['general'] = 'Failed to update profile. Please try again.';
        }
    }
}

// Get activity summary
$activityCount = $db->count('activity_logs', 'user_id = ?', [$userId]);
$lastActivity = $db->fetch(
    "SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
    [$userId]
);

// Get role-specific stats
$stats = [];
$currentRole = $auth->role();

if ($currentRole === 'user_2') {
    $stats = [
        'Total Assignments' => $db->count('assignments', 'assigned_to = ?', [$userId]),
        'Completed' => $db->count('assignments', "assigned_to = ? AND status = 'completed'", [$userId]),
        'Installations' => $db->count('installation_reports', 'installer_id = ?', [$userId])
    ];
} elseif ($currentRole === 'user_3') {
    $stats = [
        'Inspections Done' => $db->count('inspection_reports', 'inspector_id = ?', [$userId]),
        'Issues Escalated' => $db->count('maintenance_tickets', 'created_by = ?', [$userId])
    ];
} elseif ($currentRole === 'user_4') {
    $stats = [
        'Tickets Assigned' => $db->count('maintenance_tickets', 'assigned_to = ?', [$userId]),
        'Completed' => $db->count('maintenance_tickets', "assigned_to = ? AND status = 'completed'", [$userId])
    ];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-person-circle me-2"></i>My Profile
    </h1>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i>Profile updated successfully!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger"><?php echo $errors['general']; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Profile Card -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="user-avatar mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem;">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <h4 class="mb-1"><?php echo clean($user['full_name']); ?></h4>
                <p class="text-muted mb-2"><?php echo clean($user['username']); ?></p>
                <span class="badge bg-<?php echo $user['role'] === 'super_admin' ? 'danger' : 'primary'; ?> mb-3">
                    <?php echo roleName($user['role']); ?>
                </span>
                
                <hr>
                
                <table class="table table-sm table-borderless text-start mb-0">
                    <tr>
                        <td class="text-muted"><i class="bi bi-envelope me-2"></i>Email:</td>
                        <td><?php echo clean($user['email']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="bi bi-phone me-2"></i>Phone:</td>
                        <td><?php echo $user['phone'] ? clean($user['phone']) : '<span class="text-muted">Not set</span>'; ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="bi bi-badge-id me-2"></i>Employee ID:</td>
                        <td><?php echo clean($user['employee_id']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="bi bi-calendar me-2"></i>Member Since:</td>
                        <td><?php echo formatDate($user['created_at']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="bi bi-clock me-2"></i>Last Login:</td>
                        <td><?php echo $user['last_login'] ? formatDateTime($user['last_login']) : 'Never'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if (!empty($stats)): ?>
        <div class="card mt-3">
            <div class="card-header bg-primary">
                <i class="bi bi-graph-up me-2"></i>My Statistics
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <?php foreach ($stats as $label => $value): ?>
                    <tr>
                        <td class="text-muted"><?php echo $label; ?>:</td>
                        <td class="text-end"><strong><?php echo number_format($value); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Edit Profile -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header bg-primary">
                <i class="bi bi-pencil me-2"></i>Edit Profile
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employee_id" class="form-label">Employee ID</label>
                            <input type="text" class="form-control" id="employee_id" 
                                   value="<?php echo clean($user['employee_id']); ?>" disabled>
                            <div class="form-text">Contact administrator to change</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" 
                                   value="<?php echo clean($user['username']); ?>" disabled>
                            <div class="form-text">Contact administrator to change</div>
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
                            <label for="role" class="form-label">Role</label>
                            <input type="text" class="form-control" id="role" 
                                   value="<?php echo roleName($user['role']); ?>" disabled>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <a href="change-password.php" class="btn btn-outline-warning">
                            <i class="bi bi-key me-1"></i>Change Password
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>