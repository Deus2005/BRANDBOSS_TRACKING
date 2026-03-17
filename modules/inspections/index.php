<?php
/**
 * Inspections Management
 */
$pageTitle = 'Inspections';
$breadcrumbs = [['title' => 'Inspections']];

require_once '../../includes/header.php';

$db = Database::getInstance();
$currentRole = $auth->role();
$userId = $auth->userId();

// Permission check
if (!$auth->can('inspections') && !$auth->can('inspections.view')) {
    redirect(APP_URL, 'Access denied', 'danger');
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$month = $_GET['month'] ?? '';
$overdue = $_GET['overdue'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));

// Build query
$where = ['1=1'];
$params = [];

if ($status) {
    $where[] = 'isc.status = ?';
    $params[] = $status;
}

if ($month) {
    $where[] = 'isc.month_number = ?';
    $params[] = $month;
}

if ($overdue === '1') {
    $where[] = "isc.status = 'pending' AND isc.scheduled_date <= CURDATE()"; 
}

$whereClause = implode(' AND ', $where);

$sql = "SELECT isc.*, 
               ir.report_code, ir.installation_date, ir.latitude, ir.longitude,
               ia.area_name, ia.city,
               u.full_name as installer_name,
               insp.full_name as inspector_name,
               (SELECT id FROM inspection_reports WHERE schedule_id = isc.id LIMIT 1) as inspection_report_id
        FROM inspection_schedules isc
        JOIN installation_reports ir ON isc.installation_report_id = ir.id
        JOIN assignments a ON ir.assignment_id = a.id
        JOIN installation_areas ia ON a.area_id = ia.id
        JOIN users u ON ir.installer_id = u.id
        LEFT JOIN users insp ON isc.inspector_id = insp.id
        WHERE {$whereClause}
        ORDER BY isc.scheduled_date ASC, isc.month_number ASC";

$result = $db->paginate($sql, $params, $page, ITEMS_PER_PAGE);
$schedules = $result['data'];

// Summary stats
$totalPending = $db->count('inspection_schedules', "status = 'pending'");
$totalOverdue = $db->count('inspection_schedules', "status = 'pending' AND scheduled_date <= CURDATE()");
$totalCompleted = $db->count('inspection_schedules', "status = 'completed'");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-search me-2"></i>Inspections
    </h1>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="card stats-card" style="border-left-color: #ffc107;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3" style="width: 45px; height: 45px; font-size: 1.25rem; background: #fff3cd; color: #ffc107;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <h4 class="mb-0" style="color: #ffc107;"><?php echo number_format($totalPending); ?></h4>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card stats-card" style="border-left-color: #dc3545;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3" style="width: 45px; height: 45px; font-size: 1.25rem; background: #f8d7da; color: #dc3545;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h4 class="mb-0" style="color: #dc3545;"><?php echo number_format($totalOverdue); ?></h4>
                        <small class="text-muted">Overdue</small>
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
                        <h4 class="mb-0" style="color: #198754;"><?php echo number_format($totalCompleted); ?></h4>
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
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="scheduled" <?php echo $status === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="month" class="form-select">
                    <option value="">All Months</option>
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo $month == $i ? 'selected' : ''; ?>>Month <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <div class="form-check form-check-inline mt-2">
                    <input class="form-check-input" type="checkbox" name="overdue" value="1" id="overdueCheck"
                           <?php echo $overdue === '1' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="overdueCheck">Overdue Only</label>
                </div>
            </div>
            <div class="col-md-4">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Inspections Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Installation</th>
                        <th>Area</th>
                        <th class="text-center" style="display: none;">Month</th>
                        <th>Scheduled</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($schedules)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-search display-6 d-block mb-2"></i>
                                No inspection schedules found
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($schedules as $sched): ?>
                    <?php 
                    $isOverdue = $sched['status'] === 'pending' && strtotime($sched['scheduled_date']) < time();
                    ?>
                    <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                        <td>
                            <strong><?php echo clean($sched['report_code']); ?></strong>
                            <br><small class="text-muted">Installed: <?php echo formatDate($sched['installation_date']); ?></small>
                        </td>
                        <td>
                            <?php echo clean($sched['area_name']); ?>
                            <br><small class="text-muted"><?php echo clean($sched['city']); ?></small>
                        </td>
                        <td class="text-center" style="display: none;">
                            <span class="badge bg-primary fs-6">
                                <?php echo $sched['month_number']; ?>/6
                            </span> 
                        </td>
                        <td>
                            <?php echo formatDate($sched['scheduled_date']); ?>
                            <?php if ($isOverdue): ?>
                            <br><span class="badge bg-danger">Overdue</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo statusBadge($sched['status']); ?></td>
                        <td class="text-center">
                            <div class="action-btn">
                                <a href="#" class="menu-toggle" style="font-size:1.5rem;text-decoration:none;color:#000;">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </a>
                                <div class="action-dropdown">
                                    <?php if ($sched['inspection_report_id']): ?>
                                    <div class="edit-container">
                                        <a href="view.php?id=<?php echo $sched['inspection_report_id']; ?>" class="btn-report" title="View Report">
                                            <div class="block-container">
                                                <div class="view icon">
                                                    <i class="bi bi-eye"></i>
                                                </div>
                                                <div class="view text">
                                                    View Report
                                                </div>                                                 
                                            </div>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (in_array($sched['status'], ['pending', 'scheduled']) && $auth->can('inspections')): ?>
                                    <div class="activiation">
                                        <a href="create.php?schedule_id=<?php echo $sched['id']; ?>" class="btn-toggle" 
                                            title="Conduct Inspection">
                                            <div class="block-container">
                                                <div class="activation icon">
                                                    <i class="bi bi-clipboard-check"></i>
                                                </div>
                                                <div class="activation text">
                                                    Conduct Inspection
                                                </div>
                                            </div>
                                        </a>    
                                    </div>
                                    <?php endif; ?>                              
                                    <div class="delete">
                                        <a href="map.php?lat=<?php echo $sched['latitude']; ?>&lng=<?php echo $sched['longitude']; ?>" 
                                        class="btn-map" title="View Location">
                                            <div class="block-container">
                                                <div class="map icon">
                                                    <i class="bi bi-geo-alt"></i>
                                                </div>
                                                <div class="map text">
                                                    Map
                                                </div>
                                            </div>
                                        </a>                                        
                                    </div>
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
                of <?php echo $result['total']; ?> schedules
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
    var overdueCheckbox = form.querySelector('input[name="overdue"]');

    // Debounce for typing
    var autoSubmit = App.debounce(function() {
        form.submit();
    }, 800);

    if (searchInput) {
        searchInput.focus();

        setTimeout(function(){
            var valueLength = searchInput.value.length;
            searchInput.setSelectionRange(valueLength, valueLength);
        }, 0);

        searchInput.addEventListener('input', function() {
            autoSubmit();
        });
    }

    // Select filters
    filterSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            form.submit();
        });
    });

    // Overdue checkbox
    if (overdueCheckbox) {
        overdueCheckbox.addEventListener('change', function() {
            form.submit();
        });
    }
});
</script>
SCRIPT;

require_once '../../includes/footer.php'; 
?>
