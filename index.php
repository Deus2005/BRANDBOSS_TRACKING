<?php
/**
 * Dashboard - Main Index Page
 */
$pageTitle = 'Dashboard';

require_once 'includes/header.php';

$db = Database::getInstance();
$role = $auth->role();
$userId = $auth->userId();

// Get statistics based on role
$stats = [];

if (in_array($role, ['super_admin', 'user_1'])) {
    // Full statistics for managers
    $stats = [
        'total_items' => $db->count('inventory_items', "status = 'active'"),
        'total_stock' => $db->fetchColumn("SELECT COALESCE(SUM(quantity_available), 0) FROM inventory_items"),
        'active_users' => $db->count('users', "status = 'active'"),
        'pending_assignments' => $db->count('assignments', "status = 'pending'"),
        'ongoing_assignments' => $db->count('assignments', "status = 'in_progress'"),
        'completed_assignments' => $db->count('assignments', "status = 'completed'"),
        'pending_installations' => $db->count('installation_reports', "status = 'submitted'"),
        'overdue_inspections' => $db->count('inspection_schedules', "status = 'pending' AND scheduled_date <= CURDATE()"),
        'open_tickets' => $db->count('maintenance_tickets', "status IN ('open', 'assigned', 'in_progress')")
    ];
    
    // Recent activities
    $recentActivities = $db->fetchAll(
        "SELECT al.*, u.full_name 
         FROM activity_logs al 
         JOIN users u ON al.user_id = u.id 
         ORDER BY al.created_at DESC 
         LIMIT 10"
    );
    
    // Recent installations
    $recentInstallations = $db->fetchAll(
        "SELECT ir.*, u.full_name as installer_name, ia.area_name
         FROM installation_reports ir
         JOIN users u ON ir.installer_id = u.id
         JOIN assignments a ON ir.assignment_id = a.id
         JOIN installation_areas ia ON a.area_id = ia.id
         ORDER BY ir.created_at DESC
         LIMIT 5"
    );
    
} elseif ($role === 'user_2') {
    // Installer statistics
    $stats = [
        'my_pending' => $db->count('assignments', "assigned_to = ? AND status = 'pending'", [$userId]),
        'my_ongoing' => $db->count('assignments', "assigned_to = ? AND status = 'in_progress'", [$userId]),
        'my_completed' => $db->count('assignments', "assigned_to = ? AND status = 'completed'", [$userId]),
        'my_installations' => $db->count('installation_reports', "installer_id = ?", [$userId])
    ];
    
    // My assignments
    $myAssignments = $db->fetchAll(
        "SELECT a.*, ia.area_name, ia.city,
                (SELECT COUNT(*) FROM assignment_items WHERE assignment_id = a.id) as item_count
         FROM assignments a
         JOIN installation_areas ia ON a.area_id = ia.id
         WHERE a.assigned_to = ? AND a.status IN ('pending', 'in_progress')
         ORDER BY a.due_date ASC
         LIMIT 10",
        [$userId]
    );
    
} elseif ($role === 'user_3') {
    // Inspector statistics
    $stats = [
        'pending_inspections' => $db->count('inspection_schedules', "inspector_id = ? AND status = 'pending'", [$userId]),
        'overdue_inspections' => $db->count('inspection_schedules', "inspector_id = ? AND status = 'pending' AND scheduled_date <= CURDATE()", [$userId]),
        'completed_inspections' => $db->count('inspection_reports', "inspector_id = ?", [$userId]),
        'escalated_issues' => $db->count('maintenance_tickets', "created_by = ?", [$userId])
    ];
    
    // Due inspections
    $dueInspections = $db->fetchAll(
        "SELECT isc.*, ir.report_code, ia.area_name, ia.city
         FROM inspection_schedules isc
         JOIN installation_reports ir ON isc.installation_report_id = ir.id
         JOIN assignments a ON ir.assignment_id = a.id
         JOIN installation_areas ia ON a.area_id = ia.id
         WHERE (isc.inspector_id = ? OR isc.inspector_id IS NULL) 
         AND isc.status IN ('pending', 'scheduled')
         ORDER BY isc.scheduled_date ASC
         LIMIT 10",
        [$userId]
    );
    
} elseif ($role === 'user_4') {
    // Maintenance statistics
    $stats = [
        'assigned_tickets' => $db->count('maintenance_tickets', "assigned_to = ? AND status = 'assigned'", [$userId]),
        'in_progress' => $db->count('maintenance_tickets', "assigned_to = ? AND status = 'in_progress'", [$userId]),
        'pending_requests' => $db->count('maintenance_item_requests', "requested_by = ? AND status = 'pending'", [$userId]),
        'completed_tickets' => $db->count('maintenance_tickets', "assigned_to = ? AND status = 'completed'", [$userId])
    ];
    
    // My tickets
    $myTickets = $db->fetchAll(
        "SELECT mt.*, ir.report_code, ia.area_name
         FROM maintenance_tickets mt
         JOIN installation_reports ir ON mt.installation_report_id = ir.id
         JOIN assignments a ON ir.assignment_id = a.id
         JOIN installation_areas ia ON a.area_id = ia.id
         WHERE mt.assigned_to = ? AND mt.status IN ('assigned', 'in_progress')
         ORDER BY mt.priority DESC, mt.created_at ASC
         LIMIT 10",
        [$userId]
    );
}
?>

<h1 class="page-title">
    <i class="bi bi-speedometer2 me-2"></i>Dashboard
</h1>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="card-container">
        <?php if (in_array($role, ['super_admin', 'user_1'])): ?>
        <!-- Manager/Admin Stats -->
        <div class="col-6 col-lg-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-info">
                        <h3><?php echo number_format($stats['total_items']); ?></h3>
                        <p>Inventory Items</p>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-lg-3">
            <div class="card stats-card" style="border-left-color: #0d6efd;">
                <div class="card-body">
                    <div class="stats-info">
                        <h3 style="color: #0d6efd;"><?php echo $stats['ongoing_assignments']; ?></h3>
                        <p>Ongoing Assignments</p>
                    </div>
                    <div class="stats-icon" style="background: #cfe2ff; color: #0d6efd;">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-lg-3">
            <div class="card stats-card" style="border-left-color: #ffc107;">
                <div class="card-body">
                    <div class="stats-info">
                        <h3 style="color: #ffc107;"><?php echo $stats['open_tickets']; ?></h3>
                        <p>Open Tickets</p>
                    </div>
                    <div class="stats-icon" style="background: #fff3cd; color: #ffc107;">
                        <i class="bi bi-wrench"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card stats-card" style="border-left-color: #198754;">
                <div class="card-body">
                    <div class="stats-info">
                        <h3 style="color: #198754;"><?php echo number_format($stats['total_stock']); ?></h3>
                        <p>Total Stock</p>
                    </div>
                    <div class="stats-icon" style="background: #d1e7dd; color: #198754;">
                        <i class="bi bi-stack"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
        
<script src="https://cdn.jsdelivr.net/npm/fitty"></script>

<script>
    fitty('.stock-value', { minSize: 10, maxSize: 20 });
    fitty('.stock-icon', { minSize: 10, maxSize: 20 });
</script>

    <?php elseif ($role === 'user_2'): ?>
    <!-- Installer Stats -->
    <div class="col-6 col-lg-3">
        <div class="card stats-card" style="border-left-color: #ffc107;">
            <div class="card-body">
                <div class="stats-info">
                    <h3 style="color: #ffc107;"><?php echo $stats['my_pending']; ?></h3>
                    <p>Pending</p>
                </div>
                <div class="stats-icon" style="background: #fff3cd; color: #ffc107;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="card stats-card" style="border-left-color: #0d6efd;">
            <div class="card-body">
                <div class="stats-info">
                    <h3 style="color: #0d6efd;"><?php echo $stats['my_ongoing']; ?></h3>
                    <p>In Progress</p>
                </div>
                <div class="stats-icon" style="background: #cfe2ff; color: #0d6efd;">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="card stats-card" style="border-left-color: #198754;">
            <div class="card-body">
                <div class="stats-info">
                    <h3 style="color: #198754;"><?php echo $stats['my_completed']; ?></h3>
                    <p>Completed</p>
                </div>
                <div class="stats-icon" style="background: #d1e7dd; color: #198754;">
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-info">
                    <h3><?php echo $stats['my_installations']; ?></h3>
                    <p>Installations</p>
                </div>
                <div class="stats-icon">
                    <i class="bi bi-camera"></i>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($role === 'user_3'): ?>
    <!-- Inspector Stats -->
    <div class="col-6 col-lg-3">
        <div class="card stats-card" style="border-left-color: #ffc107;">
            <div class="card-body">
                <div class="stats-info">
                    <h3 style="color: #ffc107;"><?php echo $stats['pending_inspections']; ?></h3>
                    <p>Pending</p>
                </div>
                <div class="stats-icon" style="background: #fff3cd; color: #ffc107;">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="card stats-card" style="border-left-color: #dc3545;">
            <div class="card-body">
                <div class="stats-info">
                    <h3 style="color: #dc3545;"><?php echo $stats['overdue_inspections']; ?></h3>
                    <p>Overdue</p>
                </div>
                <div class="stats-icon" style="background: #f8d7da; color: #dc3545;">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="card stats-card" style="border-left-color: #198754;">
            <div class="card-body">
                <div class="stats-info">
                    <h3 style="color: #198754;"><?php echo $stats['completed_inspections']; ?></h3>
                    <p>Completed</p>
                </div>
                <div class="stats-icon" style="background: #d1e7dd; color: #198754;">
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-info">
                    <h3><?php echo $stats['escalated_issues']; ?></h3>
                    <p>Escalated</p>
                </div>
                <div class="stats-icon">
                    <i class="bi bi-arrow-up-circle"></i>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($role === 'user_4'): ?>
    <!-- Maintenance Stats -->
    <div class="col-6 col-lg-3">
        <div class="card stats-card" style="border-left-color: #0d6efd;">
            <div class="card-body">
                <div class="stats-info">
                    <h3 style="color: #0d6efd;"><?php echo $stats['assigned_tickets']; ?></h3>
                    <p>Assigned</p>
                </div>
                <div class="stats-icon" style="background: #cfe2ff; color: #0d6efd;">
                    <i class="bi bi-clipboard-check"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="card stats-card" style="border-left-color: #ffc107;">
            <div class="card-body">
                <div class="stats-info">
                    <h3 style="color: #ffc107;"><?php echo $stats['in_progress']; ?></h3>
                    <p>In Progress</p>
                </div>
                <div class="stats-icon" style="background: #fff3cd; color: #ffc107;">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-info">
                    <h3><?php echo $stats['pending_requests']; ?></h3>
                    <p>Item Requests</p>
                </div>
                <div class="stats-icon">
                    <i class="bi bi-box-arrow-in-down"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="card stats-card" style="border-left-color: #198754;">
            <div class="card-body">
                <div class="stats-info">
                    <h3 style="color: #198754;"><?php echo $stats['completed_tickets']; ?></h3>
                    <p>Completed</p>
                </div>
                <div class="stats-icon" style="background: #d1e7dd; color: #198754;">
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <?php if (in_array($role, ['super_admin', 'user_1'])): ?>
    <!-- Manager View -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-camera me-2"></i>Recent Installations
                <a href="<?php echo APP_URL; ?>/modules/installations/index.php" class="btn btn-sm btn-outline-primary ms-auto">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Area</th>
                                <th>Installer</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentInstallations)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No installations yet</td></tr>
                            <?php else: ?>
                            <?php foreach ($recentInstallations as $install): ?>
                            <tr>
                                <td><strong><?php echo clean($install['report_code']); ?></strong></td>
                                <td><?php echo clean($install['area_name']); ?></td>
                                <td><?php echo clean($install['installer_name']); ?></td>
                                <td><?php echo formatDate($install['installation_date']); ?></td>
                                <td><?php echo statusBadge($install['status']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-activity me-2"></i>Recent Activity
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php if (empty($recentActivities)): ?>
                    <div class="text-center text-muted py-3">No recent activity</div>
                    <?php else: ?>
                    <?php foreach (array_slice($recentActivities, 0, 6) as $activity): ?>
                    <div class="timeline-item">
                        <div class="timeline-date"><?php echo formatDateTime($activity['created_at']); ?></div>
                        <div class="small">
                            <strong><?php echo clean($activity['full_name']); ?></strong>
                            <?php echo clean($activity['action']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($role === 'user_2'): ?>
    <!-- Installer View -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clipboard-check me-2"></i>My Assignments
                <a href="<?php echo APP_URL; ?>/modules/assignments/index.php" class="btn btn-sm btn-outline-primary ms-auto">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Area</th>
                                <th>Items</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($myAssignments)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No pending assignments</td></tr>
                            <?php else: ?>
                            <?php foreach ($myAssignments as $assign): ?>
                            <tr>
                                <td><strong><?php echo clean($assign['assignment_code']); ?></strong></td>
                                <td>
                                    <?php echo clean($assign['area_name']); ?>
                                    <br><small class="text-muted"><?php echo clean($assign['city']); ?></small>
                                </td>
                                <td><?php echo $assign['item_count']; ?> type(s)</td>
                                <td>
                                    <?php 
                                    echo formatDate($assign['due_date']);
                                    if (strtotime($assign['due_date']) < time()) {
                                        echo ' <span class="badge bg-danger">Overdue</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo statusBadge($assign['status']); ?></td>
                                <td>
                                    <a href="<?php echo APP_URL; ?>/modules/installations/create.php?assignment_id=<?php echo $assign['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="bi bi-camera"></i> Install
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($role === 'user_3'): ?>
    <!-- Inspector View -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-search me-2"></i>Due Inspections
                <a href="<?php echo APP_URL; ?>/modules/inspections/index.php" class="btn btn-sm btn-outline-primary ms-auto">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Installation</th>
                                <th>Area</th>
                                <th>Month</th>
                                <th>Scheduled</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dueInspections)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No due inspections</td></tr>
                            <?php else: ?>
                            <?php foreach ($dueInspections as $insp): ?>
                            <tr>
                                <td><strong><?php echo clean($insp['report_code']); ?></strong></td>
                                <td>
                                    <?php echo clean($insp['area_name']); ?>
                                    <br><small class="text-muted"><?php echo clean($insp['city']); ?></small>
                                </td>
                                <td>Month <?php echo $insp['month_number']; ?>/6</td>
                                <td>
                                    <?php 
                                    echo formatDate($insp['scheduled_date']);
                                    if (strtotime($insp['scheduled_date']) < time()) {
                                        echo ' <span class="badge bg-danger">Overdue</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo statusBadge($insp['status']); ?></td>
                                <td>
                                    <a href="<?php echo APP_URL; ?>/modules/inspections/create.php?schedule_id=<?php echo $insp['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="bi bi-search"></i> Inspect
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($role === 'user_4'): ?>
    <!-- Maintenance View -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-wrench me-2"></i>My Tickets
                <a href="<?php echo APP_URL; ?>/modules/maintenance/index.php" class="btn btn-sm btn-outline-primary ms-auto">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Type</th>
                                <th>Area</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($myTickets)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No assigned tickets</td></tr>
                            <?php else: ?>
                            <?php foreach ($myTickets as $ticket): ?>
                            <tr>
                                <td><strong><?php echo clean($ticket['ticket_code']); ?></strong></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $ticket['maintenance_type'])); ?></td>
                                <td><?php echo clean($ticket['area_name']); ?></td>
                                <td><?php echo priorityBadge($ticket['priority']); ?></td>
                                <td><?php echo statusBadge($ticket['status']); ?></td>
                                <td>
                                    <a href="<?php echo APP_URL; ?>/modules/maintenance/work.php?id=<?php echo $ticket['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="bi bi-tools"></i> Work
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
