<?php
/**
 * Reports Module
 */
$pageTitle = 'Reports';
$breadcrumbs = [['title' => 'Reports']];

require_once '../../includes/header.php';

$auth->requirePermission('reports');

$db = Database::getInstance();

// Get date range filter
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Today

// Summary Statistics
$stats = [
    'installations' => $db->count('installation_reports', "installation_date BETWEEN ? AND ?", [$dateFrom, $dateTo]),
    'items_installed' => $db->fetchColumn(
        "SELECT COALESCE(SUM(iri.quantity_installed), 0) 
         FROM installation_report_items iri
         JOIN installation_reports ir ON iri.report_id = ir.id
         WHERE ir.installation_date BETWEEN ? AND ?",
        [$dateFrom, $dateTo]
    ),
    'inspections' => $db->count('inspection_reports', "inspection_date BETWEEN ? AND ?", [$dateFrom, $dateTo]),
    'issues_found' => $db->count('inspection_reports', "inspection_date BETWEEN ? AND ? AND overall_status != 'all_intact'", [$dateFrom, $dateTo]),
    'tickets_created' => $db->count('maintenance_tickets', "DATE(created_at) BETWEEN ? AND ?", [$dateFrom, $dateTo]),
    'tickets_completed' => $db->count('maintenance_tickets', "DATE(completed_at) BETWEEN ? AND ?", [$dateFrom, $dateTo])
];

// Top Installers
$topInstallers = $db->fetchAll(
    "SELECT 
        CONCAT(u.first_name, ' ', u.last_name) AS full_name,
        COUNT(ir.id) AS total_installations,
        COALESCE(SUM(iri.total), 0) AS total_items
     FROM users u
     LEFT JOIN installation_reports ir 
        ON u.id = ir.installer_id 
       AND ir.installation_date BETWEEN ? AND ?
     LEFT JOIN (
         SELECT report_id, SUM(quantity_installed) AS total
         FROM installation_report_items
         GROUP BY report_id
     ) iri ON ir.id = iri.report_id
     WHERE u.role = 'user_2' AND u.status = 'active'
     GROUP BY u.id, u.first_name, u.last_name
     ORDER BY total_installations DESC
     LIMIT 10",
    [$dateFrom, $dateTo]
);

// Top Areas by Installation
$topAreas = $db->fetchAll(
    "SELECT ia.area_name, ia.city, COUNT(ir.id) AS total_installations
     FROM installation_areas ia
     LEFT JOIN assignments a ON ia.id = a.area_id
     LEFT JOIN installation_reports ir ON a.id = ir.assignment_id AND ir.installation_date BETWEEN ? AND ?
     WHERE ia.status = 'active'
     GROUP BY ia.id, ia.area_name, ia.city
     HAVING total_installations > 0
     ORDER BY total_installations DESC
     LIMIT 10",
    [$dateFrom, $dateTo]
);

// Inventory Summary
$inventorySummary = $db->fetchAll(
    "SELECT c.category_name, 
            COUNT(i.id) AS item_count,
            COALESCE(SUM(i.quantity_available), 0) AS total_available,
            COALESCE(SUM(i.quantity_reserved), 0) AS total_reserved,
            COALESCE(SUM(i.quantity_installed), 0) AS total_installed
     FROM item_categories c
     LEFT JOIN inventory_items i ON c.id = i.category_id AND i.status = 'active'
     WHERE c.status = 'active'
     GROUP BY c.id, c.category_name
     ORDER BY c.category_name"
);

// Monthly Trend (last 6 months)
$monthlyTrend = $db->fetchAll(
    "SELECT 
        DATE_FORMAT(installation_date, '%Y-%m') AS month,
        DATE_FORMAT(installation_date, '%b %Y') AS month_label,
        COUNT(*) AS installations
     FROM installation_reports
     WHERE installation_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(installation_date, '%Y-%m'), DATE_FORMAT(installation_date, '%b %Y')
     ORDER BY month"
);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-graph-up me-2"></i>Reports & Analytics
    </h1>
    <button onclick="window.print()" class="btn btn-outline-secondary">
        <i class="bi bi-printer"></i> Print
    </button>
</div>

<!-- Date Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo clean($dateFrom); ?>">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo clean($dateTo); ?>">
            </div>
            <div class="col-md-6">
                <button type="submit" class="btn btn-dark">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="index.php" class="btn btn-primary">Clear</a>
                <div class="btn-group ms-2">
                    <a href="?date_from=<?php echo date('Y-m-01'); ?>&date_to=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-primary btn-sm">This Month</a>
                    <a href="?date_from=<?php echo date('Y-01-01'); ?>&date_to=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-primary btn-sm">This Year</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg-2">
        <div class="card stats-card h-100" style="border-left-color: #0d6efd;">
            <div class="card-body text-center">
                <h3 class="mb-0" style="color: #0d6efd;"><?php echo number_format($stats['installations']); ?></h3>
                <small class="text-muted">Installations</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stats-card h-100" style="border-left-color: #198754;">
            <div class="card-body text-center">
                <h3 class="mb-0" style="color: #198754;"><?php echo number_format($stats['items_installed']); ?></h3>
                <small class="text-muted">Items Installed</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stats-card h-100" style="border-left-color: #6c757d;">
            <div class="card-body text-center">
                <h3 class="mb-0"><?php echo number_format($stats['inspections']); ?></h3>
                <small class="text-muted">Inspections</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stats-card h-100" style="border-left-color: #ffc107;">
            <div class="card-body text-center">
                <h3 class="mb-0" style="color: #ffc107;"><?php echo number_format($stats['issues_found']); ?></h3>
                <small class="text-muted">Issues Found</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stats-card h-100" style="border-left-color: #dc3545;">
            <div class="card-body text-center">
                <h3 class="mb-0" style="color: #dc3545;"><?php echo number_format($stats['tickets_created']); ?></h3>
                <small class="text-muted">Tickets Created</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stats-card h-100" style="border-left-color: #20c997;">
            <div class="card-body text-center">
                <h3 class="mb-0" style="color: #20c997;"><?php echo number_format($stats['tickets_completed']); ?></h3>
                <small class="text-muted">Tickets Resolved</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Top Installers -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-dark text-white">
                <span class="d-flex align-text-center">
                <span class="bi bi-trophy me-2"></span>
                Top Installers
</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Installer</th>
                                <th class="text-center">Installations</th>
                                <th class="text-center">Items</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topInstallers) || $topInstallers[0]['total_installations'] == 0): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No data for selected period</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($topInstallers as $i => $installer): ?>
                                    <?php if ($installer['total_installations'] > 0): ?>
                                    <tr>
                                        <td>
                                            <?php if ($i < 3): ?>
                                            <span class="badge bg-<?php echo $i == 0 ? 'warning' : ($i == 1 ? 'secondary' : 'danger'); ?>">
                                                <?php echo $i + 1; ?>
                                            </span>
                                            <?php else: ?>
                                            <?php echo $i + 1; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo clean($installer['full_name']); ?>
                                        </td>
                                        <td class="text-center"><strong><?php echo number_format($installer['total_installations']); ?></strong></td>
                                        <td class="text-center"><?php echo number_format($installer['total_items']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Areas -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-dark text-white">
                <span class= "d-flex align-text-center">
                <span class="bi bi-geo-alt me-2"></span>
                Top Installation Areas
             </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Area</th>
                                <th>City</th>
                                <th class="text-center">Installations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topAreas)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No data for selected period</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($topAreas as $i => $area): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo clean($area['area_name']); ?></td>
                                    <td><?php echo clean($area['city']); ?></td>
                                    <td class="text-center"><strong><?php echo number_format($area['total_installations']); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Summary -->
<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <span class= "d-flex align-text-center">
        <span class="bi bi-box-seam me-2"></span>
        Inventory Summary by Category
       </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-center">Item Types</th>
                        <th class="text-center">Available</th>
                        <th class="text-center">Reserved</th>
                        <th class="text-center">Installed</th>
                        <th class="text-center">Total Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grandAvailable = 0;
                    $grandReserved = 0;
                    $grandInstalled = 0;
                    ?>

                    <?php if (empty($inventorySummary)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No inventory data available</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($inventorySummary as $cat): ?>
                        <?php
                        $grandAvailable += $cat['total_available'] ?? 0;
                        $grandReserved += $cat['total_reserved'] ?? 0;
                        $grandInstalled += $cat['total_installed'] ?? 0;
                        ?>
                        <tr>
                            <td><strong><?php echo clean($cat['category_name']); ?></strong></td>
                            <td class="text-center"><?php echo number_format($cat['item_count']); ?></td>
                            <td class="text-center"><span class="badge bg-success"><?php echo number_format($cat['total_available']); ?></span></td>
                            <td class="text-center"><span class="badge bg-warning text-dark"><?php echo number_format($cat['total_reserved']); ?></span></td>
                            <td class="text-center"><span class="badge bg-info"><?php echo number_format($cat['total_installed']); ?></span></td>
                            <td class="text-center">
                                <strong><?php echo number_format($cat['total_available'] + $cat['total_reserved'] + $cat['total_installed']); ?></strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-danger">
                    <tr>
                        <td><strong>Grand Total</strong></td>
                        <td class="text-center"><strong><?php echo count($inventorySummary); ?></strong></td>
                        <td class="text-center"><strong><?php echo number_format($grandAvailable); ?></strong></td>
                        <td class="text-center"><strong><?php echo number_format($grandReserved); ?></strong></td>
                        <td class="text-center"><strong><?php echo number_format($grandInstalled); ?></strong></td>
                        <td class="text-center"><strong><?php echo number_format($grandAvailable + $grandReserved + $grandInstalled); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Monthly Trend -->
<?php if (!empty($monthlyTrend)): ?>
<div class="card">
    <div class="card-header bg-dark text-white">
        <span class="d-flex align-text-center">
        <span class="bi bi-bar-chart me-2"></span>
        Monthly Installation Trend
</span>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($monthlyTrend as $month): ?>
            <div class="col">
                <div class="text-center">
                    <div class="mb-2">
                        <div class="bg-danger rounded" style="height: <?php echo max(20, min(150, $month['installations'] * 10)); ?>px; width: 40px; margin: 0 auto;"></div>
                    </div>
                    <small class="text-muted d-block"><?php echo clean($month['month_label']); ?></small>
                    <strong><?php echo number_format($month['installations']); ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>