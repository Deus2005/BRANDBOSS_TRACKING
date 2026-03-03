<?php
/**
 * 403 Access Denied Page
 */
require_once 'config/config.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'includes/helpers.php';

$auth = Auth::getInstance();
$isLoggedIn = $auth->isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        .error-card {
            max-width: 500px;
            text-align: center;
            padding: 3rem;
        }
        .error-icon {
            font-size: 6rem;
            color: #dc3545;
            margin-bottom: 1.5rem;
        }
        .error-code {
            font-size: 4rem;
            font-weight: 700;
            color: #dc3545;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body class="error-page">
    <div class="error-card">
        <div class="error-icon">
            <i class="bi bi-shield-x"></i>
        </div>
        <div class="error-code">403</div>
        <h2 class="mb-3">Access Denied</h2>
        <p class="text-muted mb-4">
            Sorry, you don't have permission to access this page. 
            Please contact your administrator if you believe this is an error.
        </p>
        
        <div class="d-flex gap-2 justify-content-center">
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Go Back
            </a>
            <?php if ($isLoggedIn): ?>
            <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-primary">
                <i class="bi bi-house me-1"></i>Dashboard
            </a>
            <?php else: ?>
            <a href="<?php echo APP_URL; ?>/login.php" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-right me-1"></i>Login
            </a>
            <?php endif; ?>
        </div>
        
        <?php if ($isLoggedIn): ?>
        <div class="mt-4 pt-4 border-top">
            <small class="text-muted">
                Logged in as: <strong><?php echo clean($auth->user()['full_name']); ?></strong>
                <br>Role: <?php echo roleName($auth->role()); ?>
            </small>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>