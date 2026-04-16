<?php
/**
 * View Assignment Details
 */
$pageTitle = 'View Assignment';
$breadcrumbs = [
    ['title' => 'Assignments', 'url' => 'index.php'],
    ['title' => 'View Assignment']
];

require_once '../../includes/header.php';

// Allow both full assignments permission and view-only permission
if (!$auth->can('assignments') && !$auth->can('assignments.view')) {
    header('Location: ../../403.php');
    exit;
}

$db = Database::getInstance();
$currentRole = $auth->role();
$userId = $auth->userId();

// Get assignment ID
$assignmentId = intval($_GET['id'] ?? 0);

if (!$assignmentId) {
    redirect('index.php', 'Invalid assignment', 'danger');
}

// Get assignment details
$assignment = $db->fetch(
    "SELECT a.*, 
            ia.area_code, ia.area_name, ia.address, ia.city, ia.province, ia.latitude, ia.longitude,
            u1.full_name as assigned_to_name, u1.phone as assigned_to_phone, u1.email as assigned_to_email,
            u2.full_name as assigned_by_name
     FROM assignments a
     JOIN installation_areas ia ON a.area_id = ia.id
     JOIN users u1 ON a.assigned_to = u1.id
     JOIN users u2 ON a.assigned_by = u2.id
     WHERE a.id = ?",
    [$assignmentId]
);

if (!$assignment) {
    redirect('index.php', 'Assignment not found', 'danger');
}

// Check access for User 2
if ($currentRole === 'user_2' && $assignment['assigned_to'] != $userId) {
    redirect('index.php', 'Access denied', 'danger');
}

// Get assignment items
$items = $db->fetchAll(
    "SELECT ai.*, i.item_code, i.item_name, i.unit, i.unit_cost
     FROM assignment_items ai
     JOIN inventory_items i ON ai.item_id = i.id
     WHERE ai.assignment_id = ?
     ORDER BY i.item_name",
    [$assignmentId]
);

// Get installation reports for this assignment
$installations = $db->fetchAll(
    "SELECT ir.*, u.full_name as installer_name,
            (SELECT COUNT(*) FROM installation_report_items WHERE report_id = ir.id) as item_count
     FROM installation_reports ir
     JOIN users u ON ir.installer_id = u.id
     WHERE ir.assignment_id = ?
     ORDER BY ir.created_at DESC",
    [$assignmentId]
);

// Calculate totals
$totalAssigned = 0;
$totalInstalled = 0;
$totalValue = 0;

foreach ($items as $item) {
    $totalAssigned += $item['quantity_assigned'];
    $totalInstalled += $item['quantity_installed'];
    $totalValue += $item['quantity_assigned'] * $item['unit_cost'];
}

$progress = $totalAssigned > 0 ? round(($totalInstalled / $totalAssigned) * 100) : 0;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-clipboard-check me-2"></i>Assignment Details
    </h1>
    <div>
        <?php if ($currentRole === 'user_2' && in_array($assignment['status'], ['pending', 'in_progress'])): ?>
        <a href="../installations/create.php?assignment_id=<?php echo $assignmentId; ?>" class="btn btn-success me-2">
            <i class="bi bi-camera"></i> Submit Installation
        </a>
        <?php endif; ?>
        <?php if (in_array($currentRole, ['super_admin', 'user_1']) && $assignment['status'] === 'pending'): ?>
        <a href="edit.php?id=<?php echo $assignmentId; ?>" class="btn btn-outline-primary me-2">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <!-- Assignment Info -->
    <div class="col-lg-4 mb-4">
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <span class= "d-flex align-text-center">
                <span class="bi bi-info-circle me-2"></span>Assignment Information
        </span>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4 class="mb-1"><?php echo clean($assignment['assignment_code']); ?></h4>
                    <?php echo statusBadge($assignment['status']); ?>
                    <?php echo priorityBadge($assignment['priority']); ?>
                </div>
                
                <hr>
                
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" width="40%">Assigned To:</td>
                        <td><strong><?php echo clean($assignment['assigned_to_name']); ?></strong></td>
                    </tr>
                    <?php if ($assignment['assigned_to_phone']): ?>
<tr>
    <td class="text-muted">Phone:</td>
    <td>
        <div class="d-flex align-items-center gap-1">
            <span><?php echo clean($assignment['assigned_to_phone']); ?></span>
            <button
                type="button"
                class="btn p-0"
                style="border: none; background: none;"
                onclick="copyPhone('<?php echo addslashes($assignment['assigned_to_phone']); ?>', this)">
                <i class="bi bi-copy text-secondary" style="font-size: 14px;"></i>
            </button>
        </div>
    </td>
</tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-muted">Assigned By:</td>
                        <td><?php echo clean($assignment['assigned_by_name']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Date:</td>
                        <td><?php echo formatDate($assignment['assignment_date']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Due Date:</td>
                        <td>
                            <?php 
                            echo formatDate($assignment['due_date']);
                            if ($assignment['status'] !== 'completed' && strtotime($assignment['due_date']) < time()) {
                                echo ' <span class="badge bg-danger">Overdue</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php if ($assignment['completed_at']): ?>
                    <tr>
                        <td class="text-muted">Completed:</td>
                        <td><?php echo formatDateTime($assignment['completed_at']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <?php if ($assignment['notes']): ?>
                <hr>
                <h6>Notes</h6>
                <p class="text-muted mb-0"><?php echo nl2br(clean($assignment['notes'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <span class= "d-flex align-text-center">
                <span class="bi bi-geo-alt me-2"></span>Installation Area
                </span>
            </div>
            <div class="card-body">
                <h5 class="mb-1"><?php echo clean($assignment['area_name']); ?></h5>
                <p class="text-muted mb-2"><?php echo clean($assignment['area_code']); ?></p>
                
                <?php if ($assignment['address']): ?>
                <p class="mb-1"><i class="bi bi-pin-map me-2"></i><?php echo clean($assignment['address']); ?></p>
                <?php endif; ?>
                <p class="mb-0">
                    <i class="bi bi-building me-2"></i>
                    <?php echo clean($assignment['city']); ?>
                    <?php if ($assignment['province']): ?>, <?php echo clean($assignment['province']); ?><?php endif; ?>
                </p>
                
                <?php if ($assignment['latitude'] && $assignment['longitude']): ?>
                <hr>
                <a href="../installations/map.php?lat=<?php echo $assignment['latitude']; ?>&lng=<?php echo $assignment['longitude']; ?>" 
                   class="btn btn-outline-primary btn-sm w-100" target="_blank">
                    <i class="bi bi-map me-1"></i>View on Map
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Progress -->
        <div class="card">
            <div class="card-header bg-primary">
                <span class= "d-flex align-text-center">
                <span class="bi bi-graph-up me-2"></span>Progress
                </span>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Installation Progress</span>
                    <strong><?php echo $progress; ?>%</strong>
                </div>
                <div class="progress mb-3" style="height: 10px;">
                    <div class="progress-bar bg-<?php echo $progress >= 100 ? 'success' : ($progress > 0 ? 'primary' : 'secondary'); ?>" 
                         style="width: <?php echo $progress; ?>%"></div>
                </div>
                
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="mb-0"><?php echo number_format($totalAssigned); ?></h4>
                        <small class="text-muted">Total Assigned</small>
                    </div>
                    <div class="col-6">
                        <h4 class="mb-0 text-success"><?php echo number_format($totalInstalled); ?></h4>
                        <small class="text-muted">Installed</small>
                    </div>
                </div>
                
                <hr>
                
                <div class="text-center">
                    <small class="text-muted">Total Value</small>
                    <h5 class="mb-0"><?php echo formatCurrency($totalValue); ?></h5>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Items and Installations -->
    <div class="col-lg-8 mb-4">
        <!-- Assignment Items -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <span class= "d-flex align-text-center">
                <span class="bi bi-box-seam me-2"></span>Assignment Items
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Assigned</th>
                                <th class="text-center">Installed</th>
                                <th class="text-center">Remaining</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <?php $remaining = $item['quantity_assigned'] - $item['quantity_installed']; ?>
                            <tr>
                                <td>
                                    <strong><?php echo clean($item['item_code']); ?></strong>
                                    <br><small class="text-muted"><?php echo clean($item['item_name']); ?></small>
                                </td>
                                <td class="text-center">
                                    <?php echo number_format($item['quantity_assigned']); ?>
                                    <br><small class="text-muted"><?php echo $item['unit']; ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="text-success"><?php echo number_format($item['quantity_installed']); ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($remaining > 0): ?>
                                    <span class="text-warning"><?php echo number_format($remaining); ?></span>
                                    <?php else: ?>
                                    <span class="text-success">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo statusBadge($item['status']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Installation Reports -->
        <div class="card">
            <div class="card-header bg-primary">
                <span class= "d-flex align-text-center">
                <span class="bi bi-camera me-2"></span>Installation Reports
                                    </span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($installations)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-camera display-4 d-block mb-2"></i>
                    <p class="mb-0">No installation reports yet</p>
                    <?php if ($currentRole === 'user_2' && in_array($assignment['status'], ['pending', 'in_progress'])): ?>
                    <a href="../installations/create.php?assignment_id=<?php echo $assignmentId; ?>" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-lg me-1"></i>Submit First Report
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Report Code</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>GPS</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($installations as $inst): ?>
                            <tr>
                                <td>
                                    <strong><?php echo clean($inst['report_code']); ?></strong>
                                    <br><small class="text-muted">by <?php echo clean($inst['installer_name']); ?></small>
                                </td>
                                <td><?php echo formatDate($inst['installation_date']); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $inst['item_count']; ?> type(s)</span>
                                </td>
                                <td>
                                    <small>
                                        <?php echo number_format($inst['latitude'], 4); ?>,
                                        <?php echo number_format($inst['longitude'], 4); ?>
                                    </small>
                                </td>
                                <td><?php echo statusBadge($inst['status']); ?></td>
                                <td class="text-center">
                                    <a href="../installations/view.php?id=<?php echo $inst['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function copyPhone(number, button) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(number).then(function() {
            button.innerHTML = '<i class="bi bi-check-lg text-success" style="font-size: 14px;"></i>';
            setTimeout(function() {
                button.innerHTML = '<i class="bi bi-copy text-secondary" style="font-size: 14px;"></i>';
            }, 1500);
        }).catch(function(err) {
            fallbackCopy(number);
        });
    } else {
        fallbackCopy(number);
    }

    function fallbackCopy(text) {
        let textarea = document.createElement("textarea");
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand("copy");
            button.innerHTML = '<i class="bi bi-check-lg text-success" style="font-size: 14px;"></i>';
        } catch (err) {
            alert('Failed to copy number');
        }
        document.body.removeChild(textarea);

        setTimeout(function() {
            button.innerHTML = '<i class="bi bi-copy text-secondary" style="font-size: 14px;"></i>';
        }, 1500);
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>