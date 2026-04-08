<?php
/**
 * Maintenance Tickets Management
 */
$pageTitle = 'Maintenance';
$breadcrumbs = [['title' => 'Maintenance']];

require_once '../../includes/header.php';

$db = Database::getInstance();
$currentRole = $auth->role();
$userId = $auth->userId();

// Permission check
if (!$auth->can('maintenance') && !$auth->can('maintenance.view')) {
    redirect(APP_URL, 'Access denied', 'danger');
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$priority = $_GET['priority'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));

// Build query
$where = ['1=1'];
$params = [];

// User 4 sees only assigned tickets
if ($currentRole === 'user_4') {
    $where[] = '(mt.assigned_to = ? OR mt.assigned_to IS NULL)';
    $params[] = $userId;
}

if ($status) {
    $where[] = 'mt.status = ?';
    $params[] = $status;
}

if ($type) {
    $where[] = 'mt.maintenance_type = ?';
    $params[] = $type;
}

if ($priority) {
    $where[] = 'mt.priority = ?';
    $params[] = $priority;
}

$whereClause = implode(' AND ', $where);

$sql = "SELECT mt.*, 
               ir.report_code, ir.latitude, ir.longitude,
               ia.area_name, ia.city,
               CONCAT(u1.first_name, ' ', u1.last_name) as assigned_to_name,
               CONCAT(u2.first_name, ' ', u2.last_name) as created_by_name
        FROM maintenance_tickets mt
        JOIN installation_reports ir ON mt.installation_report_id = ir.id
        JOIN assignments a ON ir.assignment_id = a.id
        JOIN installation_areas ia ON a.area_id = ia.id
        LEFT JOIN users u1 ON mt.assigned_to = u1.id
        JOIN users u2 ON mt.created_by = u2.id
        WHERE {$whereClause}
        ORDER BY 
            FIELD(mt.priority, 'critical', 'urgent', 'high', 'normal', 'low'),
            mt.created_at DESC";

$result = $db->paginate($sql, $params, $page, ITEMS_PER_PAGE);
$tickets = $result['data'];

// Summary stats
$openCount = $db->count('maintenance_tickets', "status IN ('open', 'assigned', 'in_progress')");
$pendingItemsCount = $db->count('maintenance_tickets', "status = 'pending_items'");
$completedCount = $db->count('maintenance_tickets', "status = 'completed'");

$maintenanceUsers = $db->fetchAll("
    SELECT id, first_name, last_name 
    FROM users 
    WHERE role = 'user_4' 
      AND status = 'active'
    ORDER BY first_name, last_name
");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-wrench me-2"></i>Maintenance Tickets
    </h1>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="card stats-card" style="border-left-color: #dc3545;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3" style="width: 45px; height: 45px; font-size: 1.25rem; background: #f8d7da; color: #dc3545;">
                        <i class="bi bi-exclamation-circle"></i>
                    </div>
                    <div>
                        <h4 class="mb-0" style="color: #dc3545;"><?php echo number_format($openCount); ?></h4>
                        <small class="text-muted">Open</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card stats-card" style="border-left-color: #ffc107;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3" style="width: 45px; height: 45px; font-size: 1.25rem; background: #fff3cd; color: #ffc107;">
                        <i class="bi bi-box-arrow-in-down"></i>
                    </div>
                    <div>
                        <h4 class="mb-0" style="color: #ffc107;"><?php echo number_format($pendingItemsCount); ?></h4>
                        <small class="text-muted">Pending Items</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card stats-card" style="border-left-color: #198754;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3" style="width: 45px; height: 45px; font-size: 1.25rem; background: #d1e7dd; color: #198754;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <h4 class="mb-0" style="color: #198754;"><?php echo number_format($completedCount); ?></h4>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="open" <?php echo $status === 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="assigned" <?php echo $status === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                    <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="pending_items" <?php echo $status === 'pending_items' ? 'selected' : ''; ?>>Pending Items</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="repair" <?php echo $type === 'repair' ? 'selected' : ''; ?>>Repair</option>
                    <option value="replacement" <?php echo $type === 'replacement' ? 'selected' : ''; ?>>Replacement</option>
                    <option value="missing_item" <?php echo $type === 'missing_item' ? 'selected' : ''; ?>>Missing Item</option>
                    <option value="general" <?php echo $type === 'general' ? 'selected' : ''; ?>>General</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="priority" class="form-select" onchange="this.form.submit()">
                    <option value="">All Priority</option>
                    <option value="critical" <?php echo $priority === 'critical' ? 'selected' : ''; ?>>Critical</option>
                    <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="normal" <?php echo $priority === 'normal' ? 'selected' : ''; ?>>Normal</option>
                    <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
                </select>
            </div>

            <div class="col-md-5">
                <a href="index.php" class="btn btn-danger">
                    <i class="bi bi-x-lg"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tickets Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Priority</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-wrench display-6 d-block mb-2"></i>
                                No maintenance tickets found
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($tickets as $ticket): ?>
                    <tr class="<?php echo in_array($ticket['priority'], ['critical', 'urgent']) ? 'table-danger' : ''; ?>">
                        <td>
                            <strong><?php echo clean($ticket['ticket_code']); ?></strong>
                            <br><small class="text-muted"><?php echo clean($ticket['report_code']); ?></small>
                        </td>
                        <td>
                            <?php 
                            $typeIcons = [
                                'repair' => 'tools',
                                'replacement' => 'arrow-repeat',
                                'missing_item' => 'x-circle',
                                'general' => 'wrench'
                            ];
                            $icon = $typeIcons[$ticket['maintenance_type']] ?? 'wrench';
                            ?>
                            <i class="bi bi-<?php echo $icon; ?> me-1"></i>
                            <?php echo ucfirst(str_replace('_', ' ', $ticket['maintenance_type'])); ?>
                        </td>
                        <td>
                            <?php echo clean($ticket['area_name']); ?>
                            <br><small class="text-muted"><?php echo clean($ticket['city']); ?></small>
                        </td>
                        <td><?php echo priorityBadge($ticket['priority']); ?></td>
                        <td>
                            <?php if ($ticket['assigned_to_name']): ?>
                                <?php echo clean($ticket['assigned_to_name']); ?>
                            <?php else: ?>
                                <span class="text-muted">Unassigned</span>
                                <?php if (in_array($currentRole, ['super_admin', 'user_1'])): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-assign ms-1" 
                                        data-id="<?php echo $ticket['id']; ?>" data-code="<?php echo $ticket['ticket_code']; ?>">
                                    <i class="bi bi-person-plus"></i>
                                </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo statusBadge($ticket['status']); ?></td>
                        <td>
                            <?php echo formatDate($ticket['created_at']); ?>
                            <br><small class="text-muted">by <?php echo clean($ticket['created_by_name']); ?></small>
                        </td>
                        <td class="text-center">
    <div class="droplist">
        <button 
            class="btn btn-sm p-0 text-muted" 
            type="button" 
            data-bs-toggle="dropdown"
            style="border: none; background: none;">
            
            <i class="bi bi-three-dots-vertical" style="font-size: 18px;"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">

            <li>
                <a class="dropdown-item" href="view.php?id=<?php echo $ticket['id']; ?>">
                    <i class="bi bi-eye me-2"></i> View
                </a>
            </li>

            <?php if ($currentRole === 'user_4' && in_array($ticket['status'], ['assigned', 'in_progress'])): ?>
            <li>
                <a class="dropdown-item" href="work.php?id=<?php echo $ticket['id']; ?>">
                    <i class="bi bi-tools me-2"></i> Work on Ticket
                </a>
            </li>
            <?php endif; ?>

            <?php if ($currentRole === 'user_4' && $ticket['status'] === 'assigned'): ?>
            <li>
                <a class="dropdown-item" href="request-items.php?ticket_id=<?php echo $ticket['id']; ?>">
                    <i class="bi bi-box-arrow-in-down me-2"></i> Request Items
                </a>
            </li>
            <?php endif; ?>

        </ul>
    </div>
</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if ($result['total_pages'] > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing <?php echo (($page - 1) * ITEMS_PER_PAGE) + 1; ?> - <?php echo min($page * ITEMS_PER_PAGE, $result['total']); ?> 
                of <?php echo $result['total']; ?> tickets
            </small>
            <?php echo paginationHtml($result['current_page'], $result['total_pages'], 'index.php'); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Assignment Modal -->
<?php if (in_array($currentRole, ['super_admin', 'user_1'])): ?>
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignForm">
                <div class="modal-body">
                    <input type="hidden" name="ticket_id" id="assignTicketId">
                    <p>Assign ticket <strong id="assignTicketCode"></strong> to:</p>
                    
                    <div class="mb-3">
                        <label for="assignTo" class="form-label">Maintenance Personnel</label>
                        <select class="form-select" id="assignTo" name="assign_to" required>
                            <option value="">Select Personnel</option>
                            <?php foreach ($maintenanceUsers as $mUser): ?>
                           <option value="<?php echo $mUser['id']; ?>">
    <?php echo clean($mUser['first_name'] . ' ' . $mUser['last_name']); ?>
</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Assign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$extraScripts = <<<'SCRIPT'
<script>
const assignModal = new bootstrap.Modal(document.getElementById('assignModal'));

document.querySelectorAll('.btn-assign').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('assignTicketId').value = this.dataset.id;
        document.getElementById('assignTicketCode').textContent = this.dataset.code;
        assignModal.show();
    });
});

document.getElementById('assignForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'assign');
    
    App.showLoading();
    App.ajax('../../ajax/ticket-action.php', Object.fromEntries(formData))
        .then(response => {
            App.hideLoading();
            if (response.success) {
                App.toast(response.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                App.toast(response.message, 'danger');
            }
        });
});
</script>
SCRIPT;
?>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>