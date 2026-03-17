<?php
/**
 * Assignments Management
 */
$pageTitle = 'Assignments';
$breadcrumbs = [['title' => 'Assignments']];

require_once '../../includes/header.php';

// Allow both full assignments permission and view-only permission
if (!$auth->can('assignments') && !$auth->can('assignments.view')) {
    header('Location: ../../403.php');
    exit;
}

$db = Database::getInstance();
$currentRole = $auth->role();
$userId = $auth->userId();

// Get filter parameters
$status = $_GET['status'] ?? '';
$installer = $_GET['installer'] ?? '';
$area = $_GET['area'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));

// Build query
$where = ['1=1'];
$params = [];

// User 2 can only see their own assignments
if ($currentRole === 'user_2') {
    $where[] = 'a.assigned_to = ?';
    $params[] = $userId;
}

if ($status) {
    $where[] = 'a.status = ?';
    $params[] = $status;
}

if ($installer && $currentRole !== 'user_2') {
    $where[] = 'a.assigned_to = ?';
    $params[] = $installer;
}

if ($area) {
    $where[] = 'a.area_id = ?';
    $params[] = $area;
}

$whereClause = implode(' AND ', $where);

$sql = "SELECT a.*, 
               ia.area_name, ia.city,
               u1.full_name as assigned_to_name,
               u2.full_name as assigned_by_name,
               (SELECT COUNT(*) FROM assignment_items WHERE assignment_id = a.id) as item_count,
               (SELECT SUM(quantity_assigned) FROM assignment_items WHERE assignment_id = a.id) as total_items
        FROM assignments a
        JOIN installation_areas ia ON a.area_id = ia.id
        JOIN users u1 ON a.assigned_to = u1.id
        JOIN users u2 ON a.assigned_by = u2.id
        WHERE {$whereClause}
        ORDER BY a.created_at DESC";

$result = $db->paginate($sql, $params, $page, ITEMS_PER_PAGE);
$assignments = $result['data'];

// Get installers for filter (User 2s)
$installers = $db->fetchAll("SELECT id, full_name FROM users WHERE role = 'user_2' AND status = 'active' ORDER BY full_name");

// Get areas for filter
$areas = $db->fetchAll("SELECT id, area_name FROM installation_areas WHERE status = 'active' ORDER BY area_name");

// Can create assignments? Only User 1 and Super Admin
$canCreate = in_array($currentRole, ['super_admin', 'user_1']);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-clipboard-check me-2"></i>Assignments
    </h1>
    <?php if ($canCreate): ?>
    <a href="create.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">New Assignment</span>
    </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <?php if ($currentRole !== 'user_2'): ?>
            <div class="col-md-3">
                <select name="installer" class="form-select">
                    <option value="">All Installers</option>
                    <?php foreach ($installers as $inst): ?>
                    <option value="<?php echo $inst['id']; ?>" <?php echo $installer == $inst['id'] ? 'selected' : ''; ?>>
                        <?php echo clean($inst['full_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-3">
                <select name="area" class="form-select">
                    <option value="">All Areas</option>
                    <?php foreach ($areas as $a): ?>
                    <option value="<?php echo $a['id']; ?>" <?php echo $area == $a['id'] ? 'selected' : ''; ?>>
                        <?php echo clean($a['area_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Assignments Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Area</th>
                        <?php if ($currentRole !== 'user_2'): ?>
                        <th>Assigned To</th>
                        <?php endif; ?>
                        <th>Items</th>
                        <th>Due Date</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assignments)): ?>
                    <tr>
                        <td colspan="<?php echo $currentRole !== 'user_2' ? 8 : 7; ?>" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-clipboard display-6 d-block mb-2"></i>
                                No assignments found
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($assignments as $assign): ?>
                    <tr>
                        <td><strong><?php echo clean($assign['assignment_code']); ?></strong></td>
                        <td>
                            <?php echo clean($assign['area_name']); ?>
                            <br><small class="text-muted"><?php echo clean($assign['city']); ?></small>
                        </td>
                        <?php if ($currentRole !== 'user_2'): ?>
                        <td><?php echo clean($assign['assigned_to_name']); ?></td>
                        <?php endif; ?>
                        <td>
                            <span class="badge bg-info"><?php echo $assign['item_count']; ?> type(s)</span>
                            <br><small class="text-muted"><?php echo number_format($assign['total_items']); ?> units</small>
                        </td>
                        <td>
                            <?php 
                                echo formatDate($assign['due_date']);

                                if ($assign['status'] !== 'completed' && strtotime($assign['due_date']) < time()) {

                                    $dueDate = strtotime($assign['due_date']);
                                    $graceEnd = strtotime('+3 days', $dueDate);
                                    $daysLeft = ceil(($graceEnd - time()) / (60 * 60 * 24));

                                    if ($daysLeft > 0) {
                                        echo ' <span class="badge bg-danger">Overdue</span>';
                                        echo '<br><span style="font-size: 0.8em;color: #5d5d5d9d">closes in '.$daysLeft.' day'.($daysLeft > 1 ? 's' : '').'</span>';
                                    } else {
                                        echo ' <span class="badge bg-dark">Closed</span>';
                                    }
                                }
                            ?>
                        </td>
                        <td><?php echo priorityBadge($assign['priority']); ?></td>
                        <td><?php echo statusBadge($assign['status']); ?></td>
                        <td class="text-center">
                            <div class="action-btn">
                                <a href="#" class="menu-toggle" style="font-size:1.5rem;text-decoration:none;color:#000;">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </a>
                                <div class="action-dropdown">
                                    <div class="view-container">
                                        <a href="view.php?id=<?php echo $assign['id']; ?>" class="btn-view" >
                                            <div class="block-container">
                                                <div class="view icon">
                                                    <i class="bi bi-eye"></i>
                                                </div>
                                                <div class="edit text">
                                                    View
                                                </div>                                                 
                                            </div>
                                        </a>
                                    </div>
                                    <?php if ($currentRole === 'user_2' && in_array($assign['status'], ['pending', 'in_progress'])): ?>
                                    <div class="install-container">
                                        <a href="../installations/create.php?assignment_id=<?php echo $assign['id']; ?>" class="btn-edit">
                                            <div class="block-container">
                                                <div class="install icon">
                                                    <i class="bi bi-camera"></i>
                                                </div>
                                                <div class="install text">
                                                    Installation
                                                </div>
                                            </div>
                                        </a>    
                                    </div>
                                    <?php endif; ?>      
                                    <?php if ($canCreate && $assign['status'] === 'pending'): ?>                       
                                    <div class="stock-container">
                                        <a href="edit.php?id=<?php echo $assign['id']; ?>" class="btn-stock" class="btn-stock">
                                            <div class="block-container">
                                                <div class="stock icon">
                                                    <i class="bi bi-pencil"></i>
                                                </div>
                                                <div class="stock text">
                                                    Edit
                                                </div>
                                            </div>
                                        </a>                                        
                                    </div>
                                    <?php endif; ?>
                                </div>
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
                of <?php echo $result['total']; ?> assignments
            </small>
            <?php echo paginationHtml($result['current_page'], $result['total_pages'], 'index.php'); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="../../assets/js/action.js"></script>
<?php 
$extraScripts = <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('form');
    if (!form) return;

    var searchInput = form.querySelector('input[name="search"]');
    var filterSelects = form.querySelectorAll('select');

    // Longer debounce
    var autoSubmit = App.debounce(function() {
        form.submit();
    }, 800);

    if (searchInput) {
        searchInput.focus();

        // Move cursor to end safely
        setTimeout(function(){
            var valueLength = searchInput.value.length;
            searchInput.setSelectionRange(valueLength, valueLength);
        }, 0);

        searchInput.addEventListener('input', function() {
            autoSubmit();
        });
    }

    filterSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            form.submit(); // no need debounce for selects
        });
    });
});
</script>
SCRIPT;

require_once '../../includes/footer.php'; 
?>
