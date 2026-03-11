<?php
/**
 * Header Include
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/helpers.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$currentUser = $auth->user();
$currentRole = $auth->role();
$notificationCount = getUnreadNotificationCount($auth->userId());

// Get current page for active nav
$currentPage = basename($_SERVER['REQUEST_URI'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?php echo APP_URL; ?>">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    
    <!-- Leaflet CSS for Maps -->
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="bi bi-tools"></i>
            </div>
            <div class="sidebar-brand">
                BRANDBOSS
                <small>Tracking System</small>
            </div>
        </div>
        
        <nav class="sidebar-nav" id="sidebar-nav">
            <div class="nav-section">Main</div>
            
            <a href="<?php echo APP_URL; ?>/index.php?view=dashboard" class="nav-link <?php echo (isset($_GET['view']) && $_GET['view'] == 'dashboard') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i>
                Dashboard
            </a>

            <?php if ($auth->can('inventory')): ?>
            <div class="nav-section">Inventory</div>
            
            <a href="<?php echo APP_URL; ?>/modules/inventory/index.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/modules/inventory/index.php') !== false ? 'active' : ''; ?>">
                <i class="bi bi-box-seam"></i>
                Items
            </a>
            
            <a href="<?php echo APP_URL; ?>/modules/inventory/categories.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/modules/inventory/categories.php') !== false ? 'active' : ''; ?>">
                <i class="bi bi-tags"></i>
                Categories
            </a>
            <?php endif; ?>
            
            <?php if ($auth->can('users')): ?>
            <div class="nav-section">Administration</div>
            
            <a href="<?php echo APP_URL; ?>/modules/users/index.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/modules/users/') !== false ? 'active' : ''; ?>">
                <i class="bi bi-people"></i>
                Users
            </a>
            
            <a href="<?php echo APP_URL; ?>/modules/areas/index.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/modules/areas/index.php') !== false ? 'active' : ''; ?>">
                <i class="bi bi-geo-alt"></i>
                Areas
            </a>
            <?php endif; ?>
            
            <?php if ($auth->can('assignments') || $auth->can('assignments.view')): ?>
            <div class="nav-section">Operations</div>
            
            <a href="<?php echo APP_URL; ?>/modules/assignments/index.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/modules/assignments/index.php') !== false ? 'active' : ''; ?>">
                <i class="bi bi-clipboard-check"></i>
                Assignments
                <?php 
                if ($currentRole === 'user_2') {
                    $db = Database::getInstance();
                    $pendingCount = $db->count('assignments', "assigned_to = ? AND status IN ('pending', 'in_progress')", [$auth->userId()]);
                    if ($pendingCount > 0) echo "<span class='badge bg-light text-dark ms-auto'>{$pendingCount}</span>";
                }
                ?>
            </a>
            <?php endif; ?>
            
            <?php if ($auth->can('installations') || $auth->can('installations.view')): ?>
            <a href="<?php echo APP_URL; ?>/modules/installations/index.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/modules/installations/index.php') !== false ? 'active' : ''; ?>">
                <i class="bi bi-camera"></i>
                Installations
            </a>
            <?php endif; ?>
            
            <?php if ($auth->can('inspections') || $auth->can('inspections.view')): ?>
            <a href="<?php echo APP_URL; ?>/modules/inspections/index.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/modules/inspections/index.php') !== false ? 'active' : ''; ?>">
                <i class="bi bi-search"></i>
                Inspections
                <?php 
                if ($currentRole === 'user_3') {
                    $db = Database::getInstance();
                    $dueCount = $db->count('inspection_schedules', "status = 'pending' AND scheduled_date <= CURDATE()");
                    if ($dueCount > 0) echo "<span class='badge bg-warning text-dark ms-auto'>{$dueCount}</span>";
                }
                ?>
            </a>
            <?php endif; ?>
            
            <?php if ($auth->can('maintenance') || $auth->can('maintenance.view')): ?>
            <a href="<?php echo APP_URL; ?>/modules/maintenance/index.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/modules/maintenance/index.php') !== false ? 'active' : ''; ?>">
                <i class="bi bi-wrench"></i>
                Maintenance
                <?php 
                if ($currentRole === 'user_4') {
                    $db = Database::getInstance();
                    $openCount = $db->count('maintenance_tickets', "assigned_to = ? AND status IN ('assigned', 'in_progress')", [$auth->userId()]);
                    if ($openCount > 0) echo "<span class='badge bg-danger ms-auto'>{$openCount}</span>";
                }
                ?>
            </a>
            <?php endif; ?>
            
            <?php if ($auth->can('surveys') || $auth->can('surveys.answer')): ?>
            <div class="nav-section">Surveys</div>
            
            <?php if ($auth->can('surveys')): ?>
            <a href="<?php echo APP_URL; ?>/modules/surveys/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'surveys/index') !== false || strpos($_SERVER['PHP_SELF'], 'surveys/create') !== false || strpos($_SERVER['PHP_SELF'], 'surveys/edit') !== false || strpos($_SERVER['PHP_SELF'], 'surveys/results') !== false ? 'active' : ''; ?>">
                <i class="bi bi-clipboard2-data"></i>
                Manage Surveys
            </a>
            <?php endif; ?>
            
            <?php if ($auth->can('surveys.answer')): ?>
            <a href="<?php echo APP_URL; ?>/modules/surveys/my-surveys.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'my-surveys') !== false || strpos($_SERVER['PHP_SELF'], 'surveys/answer') !== false ? 'active' : ''; ?>">
                <i class="bi bi-clipboard2-check"></i>
                My Surveys
                <?php 
                // FIXED: Show count of available surveys with better query
                $db = Database::getInstance();
                $today = date('Y-m-d');
                $userId = $auth->userId();
                
                // Get surveys available for this user's role
                $surveyCountSql = "SELECT COUNT(DISTINCT s.id) as cnt FROM surveys s
                    WHERE s.status = 'active' 
                    AND (s.start_date IS NULL OR s.start_date <= ?)
                    AND (s.end_date IS NULL OR s.end_date >= ?)
                    AND (
                        s.target_roles IS NULL 
                        OR s.target_roles = '' 
                        OR s.target_roles = '[]'
                        OR s.target_roles = 'null'
                        OR s.target_roles LIKE ?
                    )
                    AND NOT EXISTS (
                        SELECT 1 FROM survey_responses sr 
                        WHERE sr.survey_id = s.id 
                        AND sr.respondent_id = ? 
                        AND sr.status = 'completed'
                        AND s.allow_multiple = 0
                    )";
                $countResult = $db->fetch($surveyCountSql, [$today, $today, '%"' . $currentRole . '"%', $userId]);
                $availableCount = $countResult ? intval($countResult['cnt']) : 0;
                
                if ($availableCount > 0) echo "<span class='badge bg-info ms-auto'>{$availableCount}</span>";
                ?>
            </a>
            <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($auth->can('reports')): ?>
            <div class="nav-section">Reports</div>
            
            <a href="<?php echo APP_URL; ?>/modules/reports/index.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/modules/reports/index.php') !== false ? 'active' : ''; ?>">
                <i class="bi bi-graph-up"></i>
                Reports
            </a>
            <?php endif; ?>
            
            <div class="nav-section">Account</div>
            
            <a href="<?php echo APP_URL; ?>/profile.php" class="nav-link <?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
                <i class="bi bi-person-circle"></i>
                My Profile
            </a>
            
            <a href="<?php echo APP_URL; ?>/logout.php" class="nav-link text-danger" data-confirm="Are you sure you want to logout?">
                <i class="bi bi-box-arrow-left"></i>
                Logout
            </a>
        </nav>
    </aside>

    <script>
        const sidebar = document.getElementById('sidebar');
        const scrollSpeed = 5; // pixels per frame
        const edgeThreshold = 50; // pixels from top/bottom to start scrolling
        let scrollDelta = 0;

        sidebar.addEventListener('mousemove', e => {
            const rect = sidebar.getBoundingClientRect();
            const y = e.clientY - rect.top;

            if (y < edgeThreshold) scrollDelta = -scrollSpeed;
            else if (y > rect.height - edgeThreshold) scrollDelta = scrollSpeed;
            else scrollDelta = 0;
        });

        function autoScroll() {
            if (scrollDelta !== 0) {
                sidebar.scrollBy({ top: scrollDelta, behavior: 'auto' });
            }
            requestAnimationFrame(autoScroll);
        }
        autoScroll();

        document.addEventListener("DOMContentLoaded", function () {
            const aside = document.querySelector("aside");

            const pos = sessionStorage.getItem("asideScroll");
            if (pos) aside.scrollTop = pos;

            aside.addEventListener("scroll", function () {
                sessionStorage.setItem("asideScroll", aside.scrollTop);
            });
        });
    </script>
    
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay"></div>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-toggle" type="button">
                    <i class="bi bi-list"></i>
                </button>
                <nav aria-label="breadcrumb" class="d-none d-md-block">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>" style="text-decoration: none; color: inherit; cursor: pointer; font-weight: bold;">Home</a></li>
                        <?php if (isset($breadcrumbs)): ?>
                            <?php foreach ($breadcrumbs as $crumb): ?>
                                <?php if (isset($crumb['url'])): ?>
                                    <li class="breadcrumb-item"><a href="<?php echo $crumb['url']; ?>" style="text-decoration: none; color: inherit; cursor: pointer; font-weight: bold;"><?php echo $crumb['title']; ?></a></li>
                                <?php else: ?>
                                    <li class="breadcrumb-item active"><?php echo $crumb['title']; ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>
            
            <div class="navbar-right">
                <!-- Notifications -->
                <div class="dropdown">
                    <button class="notification-btn" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <span class="notification-badge" style="<?php echo $notificationCount > 0 ? '' : 'display:none'; ?>">
                            <?php echo $notificationCount; ?>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" style="width: 320px;">
                        <h6 class="dropdown-header">Notifications</h6>
                        <div id="notification-list" style="max-height: 300px; overflow-y: auto;">
                            <!-- Loaded via AJAX -->
                            <div class="text-center py-3 text-muted">
                                <small>Loading...</small>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo APP_URL; ?>/notifications.php" class="dropdown-item text-center small">View All</a>
                    </div>
                </div>
                
                <!-- User Dropdown -->
                <div class="dropdown user-dropdown">
                    <button class="user-btn" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo clean($currentUser['full_name']); ?></div>
                            <div class="user-role"><?php echo roleName($currentRole); ?></div>
                        </div>
                        <i class="bi bi-chevron-down ms-1"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="<?php echo APP_URL; ?>/profile.php">
                                <i class="bi bi-person"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo APP_URL; ?>/change-password.php">
                                <i class="bi bi-key"></i> Change Password
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?php echo APP_URL; ?>/logout.php" data-confirm="Are you sure you want to logout?">
                                <i class="bi bi-box-arrow-left"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>
        
        <!-- Content Area -->
        <div class="content-area">
            <?php
            // Flash Messages
            $flash = getFlashMessage();
            if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>