<?php
/**
 * Create Inspection Report
 * User 3 inspects installations and can escalate issues to User 4
 */
$pageTitle = 'Conduct Inspection';
$breadcrumbs = [
    ['title' => 'Inspections', 'url' => 'index.php'],
    ['title' => 'New Report']
];

require_once '../../includes/header.php';

$auth->requirePermission('inspections');

$db = Database::getInstance();
$userId = $auth->userId();
$errors = [];

// Get schedule ID
$scheduleId = intval($_GET['schedule_id'] ?? $_POST['schedule_id'] ?? 0);

if (!$scheduleId) {
    redirect('index.php', 'Invalid inspection schedule', 'danger');
}

// Get schedule details
$schedule = $db->fetch(
    "SELECT isc.*, 
            ir.id as installation_report_id, ir.report_code, ir.installation_date, 
            ir.latitude, ir.longitude, ir.location_address,
            ia.area_name, ia.city,
            CONCAT(u.first_name, ' ', u.last_name) as installer_name
     FROM inspection_schedules isc
     JOIN installation_reports ir ON isc.installation_report_id = ir.id
     JOIN assignments a ON ir.assignment_id = a.id
     JOIN installation_areas ia ON a.area_id = ia.id
     JOIN users u ON ir.installer_id = u.id
     WHERE isc.id = ? AND isc.status IN ('pending', 'scheduled')",
    [$scheduleId]
);

if (!$schedule) {
    redirect('index.php', 'Inspection schedule not found or already completed', 'danger');
}

// Get installed items for this installation
$installedItems = $db->fetchAll(
    "SELECT iri.*, i.item_code, i.item_name, i.unit
     FROM installation_report_items iri
     JOIN assignment_items ai ON iri.assignment_item_id = ai.id
     JOIN inventory_items i ON ai.item_id = i.id
     WHERE iri.report_id = ?",
    [$schedule['installation_report_id']]
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $inspectionDate = $_POST['inspection_date'] ?? date('Y-m-d');
    $overallStatus = $_POST['overall_status'] ?? 'all_intact';
    $overallRemarks = clean($_POST['overall_remarks'] ?? '');
    
    $itemData = $_POST['item_data'] ?? [];
    
    // Validation
    if (empty($itemData)) {
        $errors['items'] = 'Please provide inspection data for all items';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Create inspection report
            $inspectionCode = generateCode('INP-');
            $inspectionId = $db->insert('inspection_reports', [
                'inspection_code' => $inspectionCode,
                'schedule_id' => $scheduleId,
                'inspector_id' => $userId,
                'inspection_date' => $inspectionDate,
                'latitude' => $latitude ?: null,
                'longitude' => $longitude ?: null,
                'overall_status' => $overallStatus,
                'overall_remarks' => $overallRemarks
            ]);
            
            // Process each item
            $hasIssues = false;
            foreach ($itemData as $iriId => $data) {
                $intact = intval($data['intact'] ?? 0);
                $damaged = intval($data['damaged'] ?? 0);
                $missing = intval($data['missing'] ?? 0);
                $needsReplacement = intval($data['needs_replacement'] ?? 0);
                $remarks = clean($data['remarks'] ?? '');
                $escalate = isset($data['escalate']) ? 1 : 0;
                
                // Determine item status
                $itemStatus = 'intact';
                if ($damaged > 0 || $missing > 0 || $needsReplacement > 0) {
                    $hasIssues = true;
                    if ($damaged > 0 && $missing == 0 && $needsReplacement == 0 && $intact == 0) {
                        $itemStatus = 'damaged';
                    } elseif ($missing > 0 && $damaged == 0 && $needsReplacement == 0 && $intact == 0) {
                        $itemStatus = 'missing';
                    } elseif ($needsReplacement > 0) {
                        $itemStatus = 'needs_replacement';
                    } else {
                        $itemStatus = 'mixed';
                    }
                }
                
                // Handle photo upload if provided
                $photoFilename = null;
                if (!empty($_FILES['photo_' . $iriId]['name'])) {
                    $uploadResult = uploadImage(
                        $_FILES['photo_' . $iriId],
                        UPLOAD_PATH . 'inspections/',
                        $latitude,
                        $longitude
                    );
                    if ($uploadResult['success']) {
                        $photoFilename = $uploadResult['filename'];
                    }
                }
                
                // Insert inspection item
                $inspectionItemId = $db->insert('inspection_items', [
                    'inspection_report_id' => $inspectionId,
                    'installation_report_item_id' => $iriId,
                    'quantity_intact' => $intact,
                    'quantity_damaged' => $damaged,
                    'quantity_missing' => $missing,
                    'quantity_needs_replacement' => $needsReplacement,
                    'item_status' => $itemStatus,
                    'photo' => $photoFilename,
                    'remarks' => $remarks,
                    'escalate_to_maintenance' => $escalate
                ]);
                
                // Create maintenance ticket if escalated
                if ($escalate && ($damaged > 0 || $missing > 0 || $needsReplacement > 0)) {
                    $maintenanceType = 'general';
                    if ($damaged > 0) $maintenanceType = 'repair';
                    if ($needsReplacement > 0) $maintenanceType = 'replacement';
                    if ($missing > 0) $maintenanceType = 'missing_item';
                    
                    $ticketCode = generateCode('MNT-');
                    $ticketId = $db->insert('maintenance_tickets', [
                        'ticket_code' => $ticketCode,
                        'inspection_item_id' => $inspectionItemId,
                        'installation_report_id' => $schedule['installation_report_id'],
                        'maintenance_type' => $maintenanceType,
                        'priority' => $needsReplacement > 0 || $missing > 0 ? 'high' : 'normal',
                        'description' => "Issue found during Month {$schedule['month_number']} inspection. " .
                                        "Damaged: {$damaged}, Missing: {$missing}, Needs Replacement: {$needsReplacement}. " .
                                        ($remarks ? "Remarks: {$remarks}" : ''),
                        'status' => 'open',
                        'created_by' => $userId
                    ]);
                    
                    // Notify User 4 (Maintenance)
                    $maintenanceUsers = $db->fetchAll("SELECT id FROM users WHERE role = 'user_4' AND status = 'active'");
                    foreach ($maintenanceUsers as $mUser) {
                        createNotification(
                            $mUser['id'],
                            'New Maintenance Ticket',
                            "Maintenance ticket {$ticketCode} has been created from inspection.",
                            'warning',
                            APP_URL . "/modules/maintenance/view.php?id={$ticketId}"
                        );
                    }
                }
            }
            
            // Update schedule status
            $db->update('inspection_schedules', [
                'status' => 'completed',
                'inspector_id' => $userId
            ], 'id = ?', [$scheduleId]);
            
            // Notify managers
            $managers = $db->fetchAll("SELECT id FROM users WHERE role IN ('super_admin', 'user_1') AND status = 'active'");
            foreach ($managers as $manager) {
                createNotification(
                    $manager['id'],
                    'Inspection Completed',
                    "Month {$schedule['month_number']} inspection for {$schedule['report_code']} completed. Status: " . ucfirst(str_replace('_', ' ', $overallStatus)),
                    $hasIssues ? 'warning' : 'success',
                    APP_URL . "/modules/inspections/view.php?id={$inspectionId}"
                );
            }
            
            // Log activity
            $auth->logActivity($userId, 'completed_inspection', 'inspections', 'inspection_reports', $inspectionId);
            
            $db->commit();
            redirect('index.php', 'Inspection report submitted successfully!', 'success');
            
        } catch (Exception $e) {
            $db->rollback();
            $errors['general'] = 'Failed to submit report: ' . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-clipboard-check me-2"></i>Conduct Inspection
    </h1>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger"><?php echo $errors['general']; ?></div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" id="inspectionForm">
    <input type="hidden" name="schedule_id" value="<?php echo $scheduleId; ?>">
    <input type="hidden" name="latitude" id="latitude" value="">
    <input type="hidden" name="longitude" id="longitude" value="">
    
    <div class="row">
        <!-- Installation Info -->
        <div class="col-lg-4 mb-4">
            <div class="card mb-3">
                <div class="card-header bg-primary">
                    <span class= "d-flex align-text-center">
                    <span class="bi bi-info-circle me-2"></span>Installation Details
</span>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Code:</strong> <?php echo clean($schedule['report_code']); ?></p>
                    <p class="mb-2"><strong>Area:</strong> <?php echo clean($schedule['area_name']); ?></p>
                    <p class="mb-2"><strong>City:</strong> <?php echo clean($schedule['city']); ?></p>
                    <p class="mb-2"><strong>Installed:</strong> <?php echo formatDate($schedule['installation_date']); ?></p>
                    <p class="mb-0"><strong>Installer:</strong> <?php echo clean($schedule['installer_name']); ?></p>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-header bg-primary">
                    <span class= "d-flex align-text-center">
                    <span class="bi bi-calendar me-2"></span>Inspection Info
</span>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <span class="badge bg-primary fs-4 px-4 py-2">
                            Month <?php echo $schedule['month_number']; ?> of 6
                        </span>
                    </div>
                    <p class="mb-2"><strong>Scheduled:</strong> <?php echo formatDate($schedule['scheduled_date']); ?></p>
                    
                    <div class="mb-3">
                        <label for="inspection_date" class="form-label">Inspection Date</label>
                        <input type="date" class="form-control" id="inspection_date" name="inspection_date" 
                               value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="overall_status" class="form-label">Overall Status</label>
                        <select class="form-select" id="overall_status" name="overall_status">
                            <option value="all_intact">All Intact</option>
                            <option value="issues_found">Issues Found</option>
                            <option value="critical">Critical Issues</option>
                        </select>
                    </div>
                    
                    <div class="mb-0">
                        <label for="overall_remarks" class="form-label">Overall Remarks</label>
                        <textarea class="form-control" id="overall_remarks" name="overall_remarks" rows="3"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary">
                    <span class = "d-flex align-text-center">
                    <span class="bi bi-geo-alt me-2"></span>GPS Location
</span>
                </div>
                <div class="card-body">
                    <div id="gps-status" class="text-center py-2">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ms-2">Getting location...</span>
                    </div>
                    <div id="gps-result" style="display: none;">
                        <small class="text-muted">
                            <i class="bi bi-geo-alt"></i>
                            <span id="coords-display">-</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Items Inspection -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header bg-primary">
                    <span class= "d-flex align-text-center">
                    <span class="bi bi-list-check me-2"></span>Inspect Items
</span>
                </div>
                <div class="card-body">
                    <?php if (isset($errors['items'])): ?>
                    <div class="alert alert-danger"><?php echo $errors['items']; ?></div>
                    <?php endif; ?>
                    
                    <?php foreach ($installedItems as $index => $item): ?>
                    <div class="card mb-3 border">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>
                                    <strong><?php echo clean($item['item_code']); ?></strong> - 
                                    <?php echo clean($item['item_name']); ?>
                                </span>
                                <span class="badge bg-info">
                                    <?php echo $item['quantity_installed']; ?> <?php echo $item['unit']; ?> installed
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <label class="form-label text-success">
                                        <i class="bi bi-check-circle"></i> Intact
                                    </label>
                                    <input type="number" class="form-control" 
                                           name="item_data[<?php echo $item['id']; ?>][intact]"
                                           value="<?php echo $item['quantity_installed']; ?>"
                                           min="0" max="<?php echo $item['quantity_installed']; ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label text-warning">
                                        <i class="bi bi-exclamation-triangle"></i> Damaged
                                    </label>
                                    <input type="number" class="form-control" 
                                           name="item_data[<?php echo $item['id']; ?>][damaged]"
                                           value="0" min="0" max="<?php echo $item['quantity_installed']; ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label text-danger">
                                        <i class="bi bi-x-circle"></i> Missing
                                    </label>
                                    <input type="number" class="form-control" 
                                           name="item_data[<?php echo $item['id']; ?>][missing]"
                                           value="0" min="0" max="<?php echo $item['quantity_installed']; ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label text-danger">
                                        <i class="bi bi-arrow-repeat"></i> Replace
                                    </label>
                                    <input type="number" class="form-control" 
                                           name="item_data[<?php echo $item['id']; ?>][needs_replacement]"
                                           value="0" min="0" max="<?php echo $item['quantity_installed']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Remarks</label>
                                    <input type="text" class="form-control" 
                                           name="item_data[<?php echo $item['id']; ?>][remarks]"
                                           placeholder="Optional notes">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Photo (if issues)</label>
                                    <input type="file" class="form-control form-control-sm" 
                                           name="photo_<?php echo $item['id']; ?>" accept="image/*">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label d-block">&nbsp;</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="item_data[<?php echo $item['id']; ?>][escalate]" 
                                               id="escalate_<?php echo $item['id']; ?>">
                                        <label class="form-check-label text-danger" for="escalate_<?php echo $item['id']; ?>">
                                            <i class="bi bi-arrow-up-circle"></i> Escalate
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Check <strong>Escalate</strong> to create a maintenance ticket for items with issues. 
                        This will notify the Maintenance Team (User 4).
                    </div>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-lg me-1"></i>Submit Inspection Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php 
$extraScripts = <<<'SCRIPT'
<script>
// Get GPS Location
function getLocation() {
    const status = document.getElementById('gps-status');
    const result = document.getElementById('gps-result');
    
    App.getLocation()
        .then(coords => {
            document.getElementById('latitude').value = coords.latitude;
            document.getElementById('longitude').value = coords.longitude;
            document.getElementById('coords-display').textContent = 
                coords.latitude.toFixed(6) + ', ' + coords.longitude.toFixed(6);
            
            status.style.display = 'none';
            result.style.display = 'block';
        })
        .catch(err => {
            status.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> ' + err.message + '</span>';
        });
}

// Auto-update overall status based on item inputs
document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('change', function() {
        let hasIssues = false;
        let critical = false;
        
        document.querySelectorAll('input[name$="[damaged]"], input[name$="[missing]"], input[name$="[needs_replacement]"]').forEach(i => {
            if (parseInt(i.value) > 0) {
                hasIssues = true;
                if (i.name.includes('missing') || i.name.includes('needs_replacement')) {
                    critical = true;
                }
            }
        });
        
        const statusSelect = document.getElementById('overall_status');
        if (critical) {
            statusSelect.value = 'critical';
        } else if (hasIssues) {
            statusSelect.value = 'issues_found';
        } else {
            statusSelect.value = 'all_intact';
        }
    });
});

document.addEventListener('DOMContentLoaded', getLocation);
</script>
SCRIPT;

require_once '../../includes/footer.php'; 
?>