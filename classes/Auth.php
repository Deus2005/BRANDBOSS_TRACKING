<?php
/**
 * Authentication & Authorization Class
 */

require_once __DIR__ . '/Database.php';

class Auth {
    private $db;
    private static $instance = null;
    
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Attempt user login
     */
    public function login(string $username, string $password): array {
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'",
            [$username, $username]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        // Update last login
        $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Log activity
        $this->logActivity($user['id'], 'login', 'auth', 'users', $user['id']);
        
        return ['success' => true, 'message' => 'Login successful', 'user' => $user];
    }
    
    /**
     * Logout user
     */
    public function logout(): void {
        if ($this->isLoggedIn()) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'auth', 'users', $_SESSION['user_id']);
        }
        
        session_unset();
        session_destroy();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Get current user data
     */
    public function user(): ?array {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }
    
    /**
     * Get current user ID
     */
    public function userId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user role
     */
    public function role(): ?string {
        return $_SESSION['role'] ?? null;
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole(string|array $roles): bool {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $roles = (array) $roles;
        return in_array($_SESSION['role'], $roles);
    }
    
    /**
     * Check if user has permission
     */
    public function can(string $permission): bool {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $role = $_SESSION['role'];
        $permissions = ROLE_PERMISSIONS[$role] ?? [];
        
        // Super admin has all permissions
        if (in_array('all', $permissions)) {
            return true;
        }
        
        // Check exact permission
        if (in_array($permission, $permissions)) {
            return true;
        }
        
        // Check wildcard permissions (e.g., 'inventory' allows 'inventory.view')
        $parts = explode('.', $permission);
        if (count($parts) > 1 && in_array($parts[0], $permissions)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Require authentication
     */
    public function requireAuth(): void {
        if (!$this->isLoggedIn()) {
            if ($this->isAjax()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Authentication required']);
                exit;
            }
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }
    
    /**
     * Require specific role(s)
     */
    public function requireRole(string|array $roles): void {
        $this->requireAuth();
        
        if (!$this->hasRole($roles)) {
            if ($this->isAjax()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit;
            }
            header('Location: ' . APP_URL . '/403.php');
            exit;
        }
    }
    
    /**
     * Require specific permission
     */
    public function requirePermission(string $permission): void {
        $this->requireAuth();
        
        if (!$this->can($permission)) {
            if ($this->isAjax()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                exit;
            }
            header('Location: ' . APP_URL . '/403.php');
            exit;
        }
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Hash password
     */
    public function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Change password
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array {
        $user = $this->db->fetch("SELECT password FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        $this->db->update('users', ['password' => $this->hashPassword($newPassword)], 'id = ?', [$userId]);
        $this->logActivity($userId, 'password_changed', 'auth', 'users', $userId);
        
        return ['success' => true, 'message' => 'Password changed successfully'];
    }
    
    /**
     * Log user activity
     */
    public function logActivity(int $userId, string $action, string $module, string $refType = null, int $refId = null, array $oldValues = null, array $newValues = null): void {
        $this->db->insert('activity_logs', [
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    /**
     * Get users by role that current user can manage
     */
    public function getManageableUsers(): array {
        $role = $this->role();
        
        if ($role === 'super_admin') {
            return $this->db->fetchAll("SELECT * FROM users WHERE status = 'active' ORDER BY role, full_name");
        }
        
        if ($role === 'user_1') {
            return $this->db->fetchAll(
                "SELECT * FROM users WHERE role IN ('user_2', 'user_3', 'user_4') AND status = 'active' ORDER BY role, full_name"
            );
        }
        
        return [];
    }
    
    /**
     * Get users by specific role
     */
    public function getUsersByRole(string $role): array {
        return $this->db->fetchAll(
            "SELECT id, employee_id, full_name, email FROM users WHERE role = ? AND status = 'active' ORDER BY full_name",
            [$role]
        );
    }
}
