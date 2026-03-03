<?php
/**
 * User Status AJAX Handler
 */
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Auth.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json');

$auth = Auth::getInstance();

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Check permission
if (!$auth->can('users')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$db = Database::getInstance();
$userId = $auth->userId();
$currentRole = $auth->role();

$targetId = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$targetId || !in_array($action, ['activate', 'suspend'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Get target user
    $targetUser = $db->fetch("SELECT * FROM users WHERE id = ?", [$targetId]);
    
    if (!$targetUser) {
        throw new Exception('User not found');
    }
    
    // Cannot modify own account
    if ($targetId == $userId) {
        throw new Exception('Cannot modify your own account');
    }
    
    // Check role permissions
    if ($currentRole === 'user_1') {
        // User 1 can only manage User 2, 3, 4
        if (!in_array($targetUser['role'], ['user_2', 'user_3', 'user_4'])) {
            throw new Exception('You cannot modify this user');
        }
    }
    
    // Super admin check - cannot suspend another super admin unless you're the original
    if ($currentRole === 'super_admin' && $targetUser['role'] === 'super_admin' && $targetId != 1) {
        // Allow only first super admin to manage other super admins
        if ($userId != 1) {
            throw new Exception('Cannot modify another super admin');
        }
    }
    
    // Determine new status
    $newStatus = $action === 'activate' ? 'active' : 'suspended';
    
    // Update user
    $db->update('users', ['status' => $newStatus], 'id = ?', [$targetId]);
    
    // Log activity
    $auth->logActivity($userId, $action . '_user', 'users', 'users', $targetId, 
        ['status' => $targetUser['status']], 
        ['status' => $newStatus]
    );
    
    $message = $action === 'activate' ? 'User activated successfully' : 'User suspended successfully';
    echo json_encode(['success' => true, 'message' => $message, 'status' => $newStatus]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}