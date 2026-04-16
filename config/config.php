<?php
/**
 * Installation & Maintenance Tracking System
 * Main Configuration File
 */

// Start output buffering to prevent "headers already sent" issues
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error Reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 to prevent PHP errors from breaking JSON

// Timezone
date_default_timezone_set('Asia/Manila');
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'installation_tracking');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'Installation & Maintenance Tracking System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://127.0.0.1/brandboss_tracking');

// Directory Paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('BEFORE_PHOTO_PATH', UPLOAD_PATH . 'before/');
define('AFTER_PHOTO_PATH', UPLOAD_PATH . 'after/');
define('MAINTENANCE_PHOTO_PATH', UPLOAD_PATH . 'maintenance/');
define('STORE_PHOTO_PATH', UPLOAD_PATH . 'store_images/');

// Upload Configuration
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'webp']);

// Inspection Configuration
define('INSPECTION_MONTHS', 6);

// Pagination
define('ITEMS_PER_PAGE', 25);

// Role Definitions
define('ROLES', [
    'super_admin' => 'Super Admin',
    'user_1' => 'Manager (User 1)',
    'user_2' => 'Installer (User 2)',
    'user_3' => 'Inspector (User 3)',
    'user_4' => 'Maintenance (User 4)'
]);

// Role Permissions
define('ROLE_PERMISSIONS', [
    'super_admin' => ['all'],
    'user_1' => [
        'dashboard', 'inventory', 'users', 'assignments', 
        'installations.view', 'inspections.view', 'maintenance.view', 
        'reports', 'areas', 'surveys'
    ],
    'user_2' => [
        'dashboard', 'assignments.view', 'installations', 'surveys.answer'
    ],
    'user_3' => [
        'dashboard', 'installations.view', 'inspections', 'maintenance.escalate', 'surveys.answer'
    ],
    'user_4' => [
        'dashboard', 'installations.view', 'maintenance', 'inventory.request', 'surveys.answer'
    ]
]);

// Status Colors (Bootstrap classes)
define('STATUS_COLORS', [
    'pending' => 'warning',
    'in_progress' => 'info',
    'completed' => 'success',
    'cancelled' => 'secondary',
    'submitted' => 'primary',
    'reviewed' => 'info',
    'approved' => 'success',
    'rejected' => 'danger',
    'open' => 'danger',
    'assigned' => 'info',
    'closed' => 'secondary',
    'active' => 'success',
    'inactive' => 'secondary',
    'intact' => 'success',
    'damaged' => 'warning',
    'missing' => 'danger',
    'needs_replacement' => 'danger',
    'draft' => 'secondary'
]);

// Priority Colors
define('PRIORITY_COLORS', [
    'low' => 'secondary',
    'normal' => 'info',
    'high' => 'warning',
    'urgent' => 'danger',
    'critical' => 'danger'
]);