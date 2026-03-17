<?php
/**
 * Notifications AJAX Handler
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

$db = Database::getInstance();
$userId = $auth->userId();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'count':
            $count = $db->count('notifications', 'user_id = ? AND is_read = 0', [$userId]);
            echo json_encode(['success' => true, 'count' => $count]);
            break;
            
        case 'list':
            $notifications = $db->fetchAll(
                "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
                [$userId]
            );
            echo json_encode(['success' => true, 'notifications' => $notifications]);
            break;
            
        case 'read':
            $id = intval($_POST['id'] ?? 0);
            if ($id) {
                $db->update('notifications', ['is_read' => 1], 'id = ? AND user_id = ?', [$id, $userId]);
            }
            echo json_encode(['success' => true]);
            break;
            
        case 'read_all':
            $db->update('notifications', ['is_read' => 1], 'user_id = ?', [$userId]);
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
