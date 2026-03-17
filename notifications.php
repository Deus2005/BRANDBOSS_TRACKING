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
        <span id="unread-counter" class="badge bg-danger"><?php echo $unreadCount; ?> unread</span>
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

<div class="notification-box">

    <!-- FILTER HEADER -->
    <div class="notification-header">
        <a class="notif-filter <?php echo $filter === 'all' ? 'active' : ''; ?>" href="?filter=all">
            All <span id="count-all" class="count dark"><?php echo $totalCount; ?></span>
        </a>
        <a class="notif-filter <?php echo $filter === 'unread' ? 'active' : ''; ?>" href="?filter=unread">
            Unread <span id="count-unread" class="count red"><?php echo $unreadCount; ?></span>
        </a>
        <a class="notif-filter <?php echo $filter === 'read' ? 'active' : ''; ?>" href="?filter=read">
            Read <span id="count-read" class="count green"><?php echo $totalCount - $unreadCount; ?></span>
        </a>
    </div>
    
    <!-- NOTIFICATION LIST -->
    <div class="notification-list">
        <?php if (empty($notifications)): ?>
            <div class="empty-notification text-center text-muted py-5">
                <i class="bi bi-bell-slash display-5 mb-3"></i>
                <h6>No notifications</h6>
                <p class="mb-0 small">You're all caught up!</p>
            </div>
        <?php else: ?>

        <?php foreach ($notifications as $notif): ?>
        <?php
        $typeColors = [
            'info' => 'blue',
            'success' => 'green',
            'warning' => 'yellow',
            'danger' => 'red'
        ];
        $color = $typeColors[$notif['type']] ?? 'gray';
        ?>

        <a href="<?php echo $notif['link'] ?: '#'; ?>" 
            class="notification-item <?php echo !$notif['is_read'] ? 'bg-light' : ''; ?>"
            onclick="markAsRead(<?php echo $notif['id']; ?>, this)">

            <div class="notif-icon <?php echo $color; ?>">
                <i class="bi 
                    <?php 
                    echo $notif['type'] === 'success' ? 'bi-check' :
                        ($notif['type'] === 'warning' ? 'bi-exclamation' :
                        ($notif['type'] === 'danger' ? 'bi-x' : 'bi-info'));
                    ?>">
                </i>
            </div>

            <div class="notif-content">
                <div class="notif-title">
                    <?php echo clean($notif['title']); ?>
                </div>

                <div class="notif-message">
                    <?php echo clean($notif['message']); ?>
                </div>
            </div>

            <div class="notif-meta">
                <div class="notif-time">
                    <?php echo formatDateTime($notif['created_at']); ?>
                </div>

                <?php if (!$notif['is_read']): ?>
                <span class="notif-new">NEW</span>
                <?php endif; ?>
            </div>

        </a>

        <?php endforeach; ?>
        <?php endif; ?>
    </div>
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
function markAsRead(id, el){

    const badge = el.querySelector('.notif-new');

    if(badge){

        badge.remove();

        // remove highlight
        el.classList.remove('bg-light');

        // update unread header
        const headerCounter = document.getElementById('unread-counter');

        if(headerCounter){
            let count = parseInt(headerCounter.innerText);
            count = Math.max(count - 1, 0);
            headerCounter.innerText = count + " unread";
        }

        // update filter counters
        const unread = document.getElementById('count-unread');
        const read = document.getElementById('count-read');

        if(unread && read){

            let unreadCount = parseInt(unread.innerText);
            let readCount = parseInt(read.innerText);

            unread.innerText = Math.max(unreadCount - 1,0);
            read.innerText = readCount + 1;

        }

    }

    // send ajax update
    fetch('ajax/notifications.php',{
        method:'POST',
        headers:{
            'Content-Type':'application/x-www-form-urlencoded'
        },
        body:'action=read&id='+id
    });

}

</script>
SCRIPT;

require_once 'includes/footer.php'; 
?>