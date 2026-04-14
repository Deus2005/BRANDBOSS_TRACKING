<?php
/**
 * Installations - Report Listing
 */
$pageTitle = 'Installations';
$breadcrumbs = [['title' => 'Installations']];

require_once '../../includes/header.php';

$db = Database::getInstance();
$currentRole = $auth->role();
$userId = $auth->userId();

// Permission check
if (!$auth->can('installations') && !$auth->can('installations.view')) {
    redirect(APP_URL, 'Access denied', 'danger');
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$installer = $_GET['installer'] ?? '';
$area = $_GET['area'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));

// Build query
$where = ['1=1']; 
$params = [];

// User 2 can only see their own installations
if ($currentRole === 'user_2') {
    $where[] = 'ir.installer_id = ?';
    $params[] = $userId;
}

if ($status) {
    $where[] = 'ir.status = ?';
    $params[] = $status;
}

if ($installer && $currentRole !== 'user_2') {
    $where[] = 'ir.installer_id = ?';
    $params[] = $installer;
}

if ($area) {
    $where[] = 'a.area_id = ?';
    $params[] = $area;
}

if ($dateFrom) {
    $where[] = 'ir.installation_date >= ?';
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = 'ir.installation_date <= ?';
    $params[] = $dateTo;
}

$whereClause = implode(' AND ', $where);

$sql = "SELECT ir.*, 
               a.assignment_code,
               ia.area_name, ia.city,
               u.full_name as installer_name,
               (SELECT COUNT(*) FROM installation_report_items WHERE report_id = ir.id) as item_count,
               (SELECT SUM(quantity_installed) FROM installation_report_items WHERE report_id = ir.id) as total_installed
        FROM installation_reports ir
        JOIN assignments a ON ir.assignment_id = a.id
        JOIN installation_areas ia ON a.area_id = ia.id
        JOIN users u ON ir.installer_id = u.id
        WHERE {$whereClause}
        ORDER BY ir.created_at DESC";

$result = $db->paginate($sql, $params, $page, ITEMS_PER_PAGE);
$installations = $result['data'];

// Get installers for filter
$installers = $db->fetchAll("SELECT id, full_name FROM users WHERE role = 'user_2' AND status = 'active' ORDER BY full_name");

// Get areas for filter
$areas = $db->fetchAll("SELECT id, area_name FROM installation_areas WHERE status = 'active' ORDER BY area_name");

$reports = $db->fetch("SELECT * FROM installation_reports WHERE id = ?", [$installations['id']]);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-camera me-2"></i>Installation Reports
    </h1>
    <?php if ($currentRole === 'user_2'): ?>
    <a href="create.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">New Report</span>
    </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <?php if ($currentRole !== 'user_2'): ?>
            <div class="col-md-2">
                <select name="installer"     class="form-select">
                    <option value="">All Installers</option>
                    <?php foreach ($installers as $inst): ?>
                    <option value="<?php echo $inst['id']; ?>" <?php echo $installer == $inst['id'] ? 'selected' : ''; ?>>
                        <?php echo clean($inst['full_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2">
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
                    <option value="submitted" <?php echo $status === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                    <option value="reviewed" <?php echo $status === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="date_from" value="<?php echo $dateFrom; ?>" placeholder="From">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="date_to" value="<?php echo $dateTo; ?>" placeholder="To">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Installations Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Report Code</th>
                        <th>Area</th>
                        <?php if ($currentRole !== 'user_2'): ?>
                        <th>Installer</th>
                        <?php endif; ?>
                        <th>Date</th>
                        <th class="text-center">Items</th>
                        <th>GPS</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($installations)): ?>
                    <tr>
                        <td colspan="<?php echo $currentRole !== 'user_2' ? 8 : 7; ?>" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-camera display-6 d-block mb-2"></i>
                                No installation reports found
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($installations as $inst): ?>
                    <tr>
                        <td>
                            <strong><?php echo clean($inst['report_code']); ?></strong>
                            <br><small class="text-muted"><?php echo clean($inst['assignment_code']); ?></small>
                        </td>
                        <td>
                            <?php echo clean($inst['area_name']); ?>
                            <br><small class="text-muted"><?php echo clean($inst['city']); ?></small>
                        </td>
                        <?php if ($currentRole !== 'user_2'): ?>
                        <td><?php echo clean($inst['installer_name']); ?></td>
                        <?php endif; ?>
                        <td><?php echo formatDate($inst['installation_date']); ?></td>
                        <td class="text-center">
                            <span class="badge bg-info"><?php echo $inst['item_count']; ?> type(s)</span>
                            <br><small><?php echo number_format($inst['total_installed']); ?> units</small>
                        </td>
                        <td>
                            <small>
                                <i class="bi bi-geo-alt text-danger"></i>
                                <?php echo number_format($inst['latitude'], 4); ?>,
                                <?php echo number_format($inst['longitude'], 4); ?>
                            </small>
            
                        </td>
                        <td><?php echo statusBadge($inst['status']); ?></td>
                        <td class="text-center">
                                <div class="action-btn">
                                    <a href="#" class="menu-toggle" style="font-size:1.5rem;text-decoration:none;color:#000;">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </a>
                                        <div class="action-dropdown">
                                                <?php if($inst['Permission'] === 'Permitted'): ?>
                                                    <div class="edit-container">
                                                        <a href="edit.php?id=<?php echo $inst['id']; ?>" class="btn-view" 
                                                            title="edit">
                                                            <div class="block-container">
                                                                <div class="edit icon">
                                                                    <i class="bi bi-pencil"></i>
                                                                </div>
                                                                <div class="edit text">
                                                                    Edit
                                                                </div>                                                 
                                                            </div>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            <div class="view-container">
                                                <a href="view.php?id=<?php echo $inst['id']; ?>" class="btn-view" 
                                                    title="View">
                                                    <div class="block-container">
                                                        <div class="view icon">
                                                            <i class="bi bi-eye"></i>
                                                        </div>
                                                        <div class="view text">
                                                            View
                                                        </div>                                                 
                                                    </div>
                                                </a>
                                            </div>
                                            <div class="map">
                                                <a href="map.php?id=<?php echo $inst['id']; ?>" class="btn-map" 
                                                    title="Map ">
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
                                            <?php if (in_array($currentRole, ['super_admin', 'user_1']) && $inst['status'] === 'rejected' && $inst['Permission'] === 'Not Permitted'): ?>        
                                                    <div class="Permit-container">
                                                        <a href="view.php?id=<?php echo $inst['id']; ?>" class="btn-review" 
                                                            title="Permit Edit"
                                                            data-id="<?php echo $inst['id']; ?>"
                                                            data-name="<?php echo clean($inst['report_code']); ?>">
                                                            <div class="block-container">
                                                                <div class="permit icon">
                                                                    <i class="bi bi-check2-square"></i>
                                                                </div>
                                                                <div class="permit text">
                                                                    Permit Edit
                                                                </div>
                                                            </div>
                                                        </a>                                        
                                                    </div>                                                                       
                                            <?php endif; ?>
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
                of <?php echo $result['total']; ?> reports
            </small>
            <?php echo paginationHtml($result['current_page'], $result['total_pages'], 'index.php'); ?>
        </div>
    </div>
    <?php endif; ?>

    <script src="../../assets/js/action.js"></script>
</div>

<?php require_once '../../includes/footer.php'; ?>
