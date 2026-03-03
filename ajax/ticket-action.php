<?php
/**
 * Maintenance Ticket Actions AJAX Handler
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
$currentRole = $auth->role();
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'assign':
            // Only managers can assign tickets
            if (!in_array($currentRole, ['super_admin', 'user_1'])) {
                throw new Exception('Permission denied');
            }
            
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            $assignTo = intval($_POST['assign_to'] ?? 0);
            
            if (!$ticketId || !$assignTo) {
                throw new Exception('Invalid parameters');
            }
            
            // Verify ticket exists
            $ticket = $db->fetch("SELECT * FROM maintenance_tickets WHERE id = ?", [$ticketId]);
            if (!$ticket) {
                throw new Exception('Ticket not found');
            }
            
            // Verify user is maintenance personnel
            $assignee = $db->fetch("SELECT * FROM users WHERE id = ? AND role = 'user_4' AND status = 'active'", [$assignTo]);
            if (!$assignee) {
                throw new Exception('Invalid maintenance personnel');
            }
            
            // Update ticket
            $db->update('maintenance_tickets', [
                'assigned_to' => $assignTo,
                'status' => 'assigned'
            ], 'id = ?', [$ticketId]);
            
            // Notify assignee
            createNotification(
                $assignTo,
                'New Ticket Assigned',
                "Maintenance ticket {$ticket['ticket_code']} has been assigned to you.",
                'info',
                APP_URL . "/modules/maintenance/view.php?id={$ticketId}"
            );
            
            // Log activity
            $auth->logActivity($userId, 'assigned_ticket', 'maintenance', 'maintenance_tickets', $ticketId);
            
            echo json_encode(['success' => true, 'message' => 'Ticket assigned successfully']);
            break;
            
        case 'start':
            // User 4 starts working on ticket
            if ($currentRole !== 'user_4') {
                throw new Exception('Permission denied');
            }
            
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            
            $ticket = $db->fetch(
                "SELECT * FROM maintenance_tickets WHERE id = ? AND assigned_to = ? AND status = 'assigned'",
                [$ticketId, $userId]
            );
            
            if (!$ticket) {
                throw new Exception('Ticket not found or not assigned to you');
            }
            
            $db->update('maintenance_tickets', ['status' => 'in_progress'], 'id = ?', [$ticketId]);
            
            echo json_encode(['success' => true, 'message' => 'Ticket started']);
            break;
            
        case 'complete':
            // User 4 marks ticket as complete
            if ($currentRole !== 'user_4') {
                throw new Exception('Permission denied');
            }
            
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            
            $ticket = $db->fetch(
                "SELECT * FROM maintenance_tickets WHERE id = ? AND assigned_to = ? AND status = 'in_progress'",
                [$ticketId, $userId]
            );
            
            if (!$ticket) {
                throw new Exception('Ticket not found or cannot be completed');
            }
            
            $db->update('maintenance_tickets', [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$ticketId]);
            
            // Notify managers
            $managers = $db->fetchAll("SELECT id FROM users WHERE role IN ('super_admin', 'user_1') AND status = 'active'");
            foreach ($managers as $manager) {
                createNotification(
                    $manager['id'],
                    'Ticket Completed',
                    "Maintenance ticket {$ticket['ticket_code']} has been completed.",
                    'success',
                    APP_URL . "/modules/maintenance/view.php?id={$ticketId}"
                );
            }
            
            $auth->logActivity($userId, 'completed_ticket', 'maintenance', 'maintenance_tickets', $ticketId);
            
            echo json_encode(['success' => true, 'message' => 'Ticket completed']);
            break;
            
        case 'close':
            // Managers close completed tickets
            if (!in_array($currentRole, ['super_admin', 'user_1'])) {
                throw new Exception('Permission denied');
            }
            
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            
            $ticket = $db->fetch(
                "SELECT * FROM maintenance_tickets WHERE id = ? AND status = 'completed'",
                [$ticketId]
            );
            
            if (!$ticket) {
                throw new Exception('Ticket not found or not completed');
            }
            
            $db->update('maintenance_tickets', [
                'status' => 'closed',
                'closed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$ticketId]);
            
            echo json_encode(['success' => true, 'message' => 'Ticket closed']);
            break;
            
        case 'cancel':
            // Managers can cancel tickets
            if (!in_array($currentRole, ['super_admin', 'user_1'])) {
                throw new Exception('Permission denied');
            }
            
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            
            $ticket = $db->fetch(
                "SELECT * FROM maintenance_tickets WHERE id = ? AND status NOT IN ('completed', 'closed', 'cancelled')",
                [$ticketId]
            );
            
            if (!$ticket) {
                throw new Exception('Ticket not found or cannot be cancelled');
            }
            
            $db->update('maintenance_tickets', ['status' => 'cancelled'], 'id = ?', [$ticketId]);
            
            // Notify assignee if any
            if ($ticket['assigned_to']) {
                createNotification(
                    $ticket['assigned_to'],
                    'Ticket Cancelled',
                    "Maintenance ticket {$ticket['ticket_code']} has been cancelled.",
                    'warning',
                    APP_URL . "/modules/maintenance/view.php?id={$ticketId}"
                );
            }
            
            echo json_encode(['success' => true, 'message' => 'Ticket cancelled']);
            break;
            
        case 'reassign':
            // Managers can reassign tickets
            if (!in_array($currentRole, ['super_admin', 'user_1'])) {
                throw new Exception('Permission denied');
            }
            
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            $assignTo = intval($_POST['assign_to'] ?? 0);
            
            $ticket = $db->fetch(
                "SELECT * FROM maintenance_tickets WHERE id = ? AND status NOT IN ('completed', 'closed', 'cancelled')",
                [$ticketId]
            );
            
            if (!$ticket) {
                throw new Exception('Ticket not found or cannot be reassigned');
            }
            
            $assignee = $db->fetch("SELECT * FROM users WHERE id = ? AND role = 'user_4' AND status = 'active'", [$assignTo]);
            if (!$assignee) {
                throw new Exception('Invalid maintenance personnel');
            }
            
            $oldAssignee = $ticket['assigned_to'];
            
            $db->update('maintenance_tickets', [
                'assigned_to' => $assignTo,
                'status' => 'assigned'
            ], 'id = ?', [$ticketId]);
            
            // Notify new assignee
            createNotification(
                $assignTo,
                'Ticket Assigned',
                "Maintenance ticket {$ticket['ticket_code']} has been assigned to you.",
                'info',
                APP_URL . "/modules/maintenance/view.php?id={$ticketId}"
            );
            
            // Notify old assignee
            if ($oldAssignee && $oldAssignee != $assignTo) {
                createNotification(
                    $oldAssignee,
                    'Ticket Reassigned',
                    "Maintenance ticket {$ticket['ticket_code']} has been reassigned to another person.",
                    'warning',
                    APP_URL . "/modules/maintenance/view.php?id={$ticketId}"
                );
            }
            
            echo json_encode(['success' => true, 'message' => 'Ticket reassigned']);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}