<?php
/**
 * View Inspection Report
 */
$pageTitle = 'View Inspection';
$breadcrumbs = [
    ['title' => 'Inspections', 'url' => 'index.php'],
    ['title' => 'View Report']
];

require_once '../../includes/header.php';

$db = Database::getInstance();
$currentRole = $auth->role();

// Permission check
if (!$auth->can('inspections') && !$auth->can('inspections.view')) {
    redirect(APP_URL, 'Access denied', 'danger');
}

// Get inspection ID
$inspectionId = intval($_GET['id'] ?? 0);

if (!$inspectionId) {
    redirect('index.php', 'Invalid inspection', 'danger');
}

// Get inspection details
$inspection = $db->fetch(
    "SELECT insp.*, 
            isc.month_number, isc.scheduled_date,
            ir.id as installation_id, ir.report_code as installation_code, ir.installation_date,
            ir.latitude as install_lat, ir.longitude as install_lng,
            ia.area_name, ia.city, ia.address,
            CONCAT(u1.first_name, ' ', u1.last_name) as inspector_name, u1.phone as inspector_phone,
            CONCAT(u2.first_name, ' ', u2.last_name) as installer_name
            
     FROM inspection_reports insp
     JOIN inspection_schedules isc ON insp.schedule_id = isc.id
     JOIN installation_reports ir ON isc.installation_report_id = ir.id
     JOIN assignments a ON ir.assignment_id = a.id
     JOIN installation_areas ia ON a.area_id = ia.id
     JOIN users u1 ON insp.inspector_id = u1.id
     JOIN users u2 ON ir.installer_id = u2.id
     WHERE insp.id = ?",
    [$inspectionId]
);

if (!$inspection) {
    redirect('index.php', 'Inspection report not found', 'danger');
}

// Get inspection items
$inspectionItems = $db->fetchAll(
    "SELECT ii.*, 
            iri.quantity_installed, iri.before_photo, iri.after_photo,
            i.item_code, i.item_name, i.unit,
            (SELECT ticket_code FROM maintenance_tickets WHERE inspection_item_id = ii.id LIMIT 1) as ticket_code,
            (SELECT id FROM maintenance_tickets WHERE inspection_item_id = ii.id LIMIT 1) as ticket_id
     FROM inspection_items ii
     JOIN installation_report_items iri ON ii.installation_report_item_id = iri.id
     JOIN assignment_items ai ON iri.assignment_item_id = ai.id
     JOIN inventory_items i ON ai.item_id = i.id
     WHERE ii.inspection_report_id = ?
     ORDER BY i.item_name",
    [$inspectionId]
);

// Calculate summary
$totalItems = count($inspectionItems);
$intactCount = 0;
$issuesCount = 0;
$escalatedCount = 0;

foreach ($inspectionItems as $item) {
    if ($item['item_status'] === 'intact') {
        $intactCount++;
    } else {
        $issuesCount++;
    }
    if ($item['escalate_to_maintenance']) {
        $escalatedCount++;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-clipboard-check me-2"></i>Inspection Report
    </h1>
    <div>
        <?php if ($inspection['latitude'] && $inspection['longitude']): ?>
        <a href="map.php?lat=<?php echo $inspection['latitude']; ?>&lng=<?php echo $inspection['longitude']; ?>" 
           class="btn btn-outline-success me-2">
            <i class="bi bi-geo-alt"></i> View Map
        </a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <!-- Report Info -->
    <div class="col-lg-4 mb-4">
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <span class="d-flex align-text-center">
                <span class="bi bi-info-circle me-2"></span>
                Inspection Information
        </span> 
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4 class="mb-1"><?php echo clean($inspection['inspection_code']); ?></h4>
                    <span class="badge bg-primary fs-6 mb-2">Month <?php echo $inspection['month_number']; ?> of 6</span>
                    <br>
                    <?php 
                    $statusColors = [
                        'all_intact' => 'success',
                        'issues_found' => 'warning',
                        'critical' => 'danger'
                    ];
                    $statusColor = $statusColors[$inspection['overall_status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $statusColor; ?>">
                        <?php echo ucwords(str_replace('_', ' ', $inspection['overall_status'])); ?>
                    </span>
                </div>
                
                <hr>
                
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" width="40%">Inspector:</td>
                        <td><strong><?php echo clean($inspection['inspector_name']); ?></strong></td>
                    </tr>
                    <?php if (!empty($inspection['inspector_phone'])): ?>
<tr>
    <td class="text-muted">Phone:</td>
    <td>
        <div class="d-flex align-items-center gap-1">
            <span><?php echo clean($inspection['inspector_phone']); ?></span>
            <button
                type="button"
                class="btn p-0"
                style="border: none; background: none;"
                onclick="copyPhone('<?php echo addslashes($inspection['inspector_phone']); ?>', this)">
                <i class="bi bi-copy text-secondary" style="font-size: 14px;"></i>
            </button>
        </div>
    </td>
</tr>
<?php endif; ?>
                    <tr>
                        <td class="text-muted">Inspection Date:</td>
                        <td><?php echo formatDate($inspection['inspection_date']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Scheduled:</td>
                        <td><?php echo formatDate($inspection['scheduled_date']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Submitted:</td>
                        <td><?php echo formatDateTime($inspection['created_at']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <span class="d-flex align-text-center">
                <span class="bi bi-camera me-2"></span>
                Installation Reference
                    </span>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Code:</strong> 
                    <a href="../installations/view.php?id=<?php echo $inspection['installation_id']; ?>">
                        <?php echo clean($inspection['installation_code']); ?>
                    </a>
                </p>
                <p class="mb-2"><strong>Installed:</strong> <?php echo formatDate($inspection['installation_date']); ?></p>
                <p class="mb-2"><strong>Installer:</strong> <?php echo clean($inspection['installer_name']); ?></p>
                
                <hr>
                
                <h6 class="mb-2"><?php echo clean($inspection['area_name']); ?></h6>
                <?php if ($inspection['address']): ?>
                <p class="mb-1"><small><?php echo clean($inspection['address']); ?></small></p>
                <?php endif; ?>
                <p class="mb-0"><small class="text-muted"><?php echo clean($inspection['city']); ?></small></p>
            </div>
        </div>
        
        <?php if ($inspection['overall_remarks']): ?>
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <i class="bi bi-chat-text me-2"></i>Overall Remarks
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(clean($inspection['overall_remarks'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($inspection['latitude'] && $inspection['longitude']): ?>
        <div class="card">
            <div class="card-header bg-primary">
                <span class="d-flex align-text-center">
                <span class="bi bi-geo-alt me-2"></span>
                GPS Location
        </span>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <small class="text-muted d-block">Latitude</small>
                        <strong><?php echo number_format($inspection['latitude'], 6); ?></strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Longitude</small>
                        <strong><?php echo number_format($inspection['longitude'], 6); ?></strong>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Items Inspection Results -->
    <div class="col-lg-8 mb-4">
        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card stats-card h-100" style="border-left-color: #198754;">
                    <div class="card-body text-center">
                        <h3 class="mb-0" style="color: #198754;"><?php echo $intactCount; ?></h3>
                        <small class="text-muted">Items Intact</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card h-100" style="border-left-color: #ffc107;">
                    <div class="card-body text-center">
                        <h3 class="mb-0" style="color: #ffc107;"><?php echo $issuesCount; ?></h3>
                        <small class="text-muted">With Issues</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card h-100" style="border-left-color: #dc3545;">
                    <div class="card-body text-center">
                        <h3 class="mb-0" style="color: #dc3545;"><?php echo $escalatedCount; ?></h3>
                        <small class="text-muted">Escalated</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Inspection Items -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <span class="d-flex align-text-center">
                <span class="bi bi-list-check me-2"></span>
                Item Inspection Results
        </span>
            </div>
            <div class="card-body">
                <?php foreach ($inspectionItems as $index => $item): ?>
                <?php
                $statusColors = [
                    'intact' => 'success',
                    'damaged' => 'warning',
                    'missing' => 'danger',
                    'needs_replacement' => 'danger',
                    'mixed' => 'warning'
                ];
                $itemStatusColor = $statusColors[$item['item_status']] ?? 'secondary';
                ?>
                <div class="card mb-3 <?php echo $index === count($inspectionItems) - 1 ? 'mb-0' : ''; ?> border-<?php echo $itemStatusColor; ?>">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span>
                            <strong><?php echo clean($item['item_code']); ?></strong> - 
                            <?php echo clean($item['item_name']); ?>
                        </span>
                        <span class="badge bg-<?php echo $itemStatusColor; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $item['item_status'])); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6 col-md-3 text-center">
                                <small class="text-muted d-block">Installed</small>
                                <strong><?php echo $item['quantity_installed']; ?></strong>
                            </div>
                            <div class="col-6 col-md-3 text-center">
                                <small class="text-success d-block">Intact</small>
                                <strong class="text-success"><?php echo $item['quantity_intact']; ?></strong>
                            </div>
                            <div class="col-6 col-md-3 text-center">
                                <small class="text-warning d-block">Damaged</small>
                                <strong class="text-warning"><?php echo $item['quantity_damaged']; ?></strong>
                            </div>
                            <div class="col-6 col-md-3 text-center">
                                <small class="text-danger d-block">Missing</small>
                                <strong class="text-danger"><?php echo $item['quantity_missing']; ?></strong>
                            </div>
                        </div>
                        
                        <?php if ($item['quantity_needs_replacement'] > 0): ?>
                        <div class="alert alert-danger py-2 mb-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong><?php echo $item['quantity_needs_replacement']; ?></strong> unit(s) need replacement
                        </div>
                        <?php endif; ?>
                        
                       <?php if (!empty($item['photo'])): ?>
<?php 
$photoPath = APP_URL . '/uploads/inspections/' . $item['photo'];
$modalId = 'photoModal_' . $index; // unique per item
?>

<div class="mb-3">
    <small class="text-muted d-block mb-2">Inspection Photo:</small>

    <!-- Thumbnail (click to open modal) -->
    <img src="<?php echo $photoPath; ?>" 
         class="img-thumbnail"
         style="max-height: 150px; cursor: pointer;"
         data-bs-toggle="modal"
         data-bs-target="#<?php echo $modalId; ?>">
</div>

<!-- Modal -->
<div class="modal fade" id="<?php echo $modalId; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">

            <!-- Close button -->
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3 bg-white" 
                    data-bs-dismiss="modal"></button>

            <!-- Image -->
            <img src="<?php echo $photoPath; ?>" 
                 class="img-fluid rounded">
        </div>
    </div>
</div>
<?php endif; ?>
                        <?php if ($item['remarks']): ?>
                        <p class="mb-2"><small><strong>Remarks:</strong> <?php echo clean($item['remarks']); ?></small></p>
                        <?php endif; ?>
                        
                        <?php if ($item['escalate_to_maintenance']): ?>
                        <div class="alert alert-warning py-2 mb-0">
                            <i class="bi bi-arrow-up-circle me-2"></i>
                            <strong>Escalated to Maintenance</strong>
                            <?php if ($item['ticket_code']): ?>
                            - Ticket: 
                            <a href="../maintenance/view.php?id=<?php echo $item['ticket_id']; ?>">
                                <?php echo clean($item['ticket_code']); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
function copyPhone(number, button) {
    navigator.clipboard.writeText(number).then(function() {
        button.innerHTML = '<i class="bi bi-check-lg text-success" style="font-size: 14px;"></i>';

        setTimeout(function() {
            button.innerHTML = '<i class="bi bi-copy text-secondary" style="font-size: 14px;"></i>';
        }, 1500);
    }).catch(function(err) {
        console.error('Copy failed:', err);
        alert('Failed to copy number');
    });
}
</script>
<?php require_once '../../includes/footer.php'; ?>