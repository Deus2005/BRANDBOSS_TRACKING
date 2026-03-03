<?php
/**
 * Notifications Page
 */
$pageTitle = 'Notifications';
$breadcrumbs = [['title' => 'Notifications']];

require_once 'includes/header.php';

$db = Database::getInstance();
$userId = $auth->userId();

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'read_all') {
        $db->update('notifications', ['is_read' => 1], 'user_id = ?', [$userId]);
        redirect('notifications.php', 'All notifications marked as read', 'success');
    } elseif ($action === 'delete_all') {
        $db->delete('notifications', 'user_id = ? AND is_read = 1', [$userId]);
        redirect('notifications.php', 'Read notifications deleted', 'success');
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));

// Build query
$where = 'user_id = ?';
$params = [$userId];

if ($filter === 'unread') {
    $where .= ' AND is_read = 0';
} elseif ($filter === 'read') {
    $where .= ' AND is_read = 1';
}

$sql = "SELECT * FROM notifications WHERE {$where} ORDER BY created_at DESC";
$result = $db->paginate($sql, $params, $page, 20);
$notifications = $result['data'];

// Get counts
$totalCount = $db->count('notifications', 'user_id = ?', [$userId]);
$unreadCount = $db->count('notifications', 'user_id = ? AND is_read = 0', [$userId]);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-bell me-2"></i>Notifications
        <?php if ($unreadCount > 0): ?>
        <span class="badge bg-danger"><?php echo $unreadCount; ?> unread</span>
        <?php endif; ?>
    </h1>
    
    <?php if ($totalCount > 0): ?>
    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-gear"></i> Actions
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <form method="POST" class="d-inline">
                    <button type="submit" name="action" value="read_all" class="dropdown-item">
                        <i class="bi bi-check-all me-2"></i>Mark All as Read
                    </button>
                </form>
            </li>
            <li>
                <form method="POST" class="d-inline" onsubmit="return confirm('Delete all read notifications?')">
                    <button type="submit" name="action" value="delete_all" class="dropdown-item text-danger">
                        <i class="bi bi-trash me-2"></i>Delete Read
                    </button>
                </form>
            </li>
        </ul>
    </div>
    <?php endif; ?>
</div>

<!-- Filter Tabs -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" href="?filter=all">
            All <span class="badge bg-secondary"><?php echo $totalCount; ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $filter === 'unread' ? 'active' : ''; ?>" href="?filter=unread">
            Unread <span class="badge bg-danger"><?php echo $unreadCount; ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $filter === 'read' ? 'active' : ''; ?>" href="?filter=read">
            Read <span class="badge bg-success"><?php echo $totalCount - $unreadCount; ?></span>
        </a>
    </li>
</ul>

<!-- Notifications List -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($notifications)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-bell-slash display-1 mb-3"></i>
            <h5>No notifications</h5>
            <p class="mb-0">You're all caught up!</p>
        </div>
        <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $notif): ?>
            <?php
            $typeColors = [
                'info' => 'primary',
                'success' => 'success',
                'warning' => 'warning',
                'danger' => 'danger'
            ];
            $color = $typeColors[$notif['type']] ?? 'secondary';
            ?>
            <a href="<?php echo $notif['link'] ?: '#'; ?>" 
               class="list-group-item list-group-item-action <?php echo !$notif['is_read'] ? 'bg-light' : ''; ?>"
               onclick="markAsRead(<?php echo $notif['id']; ?>)">
                <div class="d-flex align-items-start">
                    <span class="badge bg-<?php echo $color; ?> rounded-circle p-2 me-3 mt-1">
                        <i class="bi bi-<?php echo $notif['type'] === 'success' ? 'check' : ($notif['type'] === 'warning' ? 'exclamation' : ($notif['type'] === 'danger' ? 'x' : 'info')); ?>"></i>
                    </span>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="mb-1 <?php echo !$notif['is_read'] ? 'fw-bold' : ''; ?>">
                                <?php echo clean($notif['title']); ?>
                            </h6>
                            <small class="text-muted ms-2 text-nowrap">
                                <?php echo formatDateTime($notif['created_at']); ?>
                            </small>
                        </div>
                        <p class="mb-0 text-muted small"><?php echo clean($notif['message']); ?></p>
                    </div>
                    <?php if (!$notif['is_read']): ?>
                    <span class="badge bg-primary rounded-pill ms-2">New</span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($result['total_pages'] > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing <?php echo (($page - 1) * 20) + 1; ?> - <?php echo min($page * 20, $result['total']); ?> 
                of <?php echo $result['total']; ?> notifications
            </small>
            <?php echo paginationHtml($result['current_page'], $result['total_pages'], 'notifications.php?filter=' . $filter); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php 
$extraScripts = <<<'SCRIPT'
<script>
function markAsRead(id) {
    App.ajax('ajax/notifications.php', { action: 'read', id: id });
}
</script>
SCRIPT;

require_once 'includes/footer.php'; 
?>