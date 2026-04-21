<?php
/**
 * View Installation Report
 */
$pageTitle = 'View Installation';
$breadcrumbs = [
    ['title' => 'Installations', 'url' => 'index.php'],
    ['title' => 'View Report']
];

require_once '../../includes/header.php';

$db = Database::getInstance();
$currentRole = $auth->role();
$userId = $auth->userId();

// Permission check
if (!$auth->can('installations') && !$auth->can('installations.view')) {
    redirect(APP_URL, 'Access denied', 'danger');
}

// Get installation ID
$installationId = intval($_GET['id'] ?? 0);

if (!$installationId) {
    redirect('index.php', 'Invalid installation', 'danger');
}

// Get installation details with store details and address
$installation = $db->fetch(
    "SELECT ir.*,
            a.assignment_code, a.priority,
            ia.area_code, ia.area_name, ia.address, ia.city, ia.province,
            u1.full_name as installer_name, u1.phone as installer_phone, u1.email as installer_email,
            u2.full_name as reviewed_by_name,
            sd.*,
            addr.*,
            addr.complete_address
     FROM installation_reports ir
     JOIN assignments a ON ir.assignment_id = a.id
     JOIN installation_areas ia ON a.area_id = ia.id
     JOIN users u1 ON ir.installer_id = u1.id
     LEFT JOIN users u2 ON ir.reviewed_by = u2.id
     LEFT JOIN installation_store_details sd ON ir.id = sd.report_id
     LEFT JOIN installation_detailed_addresses addr ON ir.id = addr.report_id
     WHERE ir.id = ?",
    [$installationId]
);

if (!$installation) {
    redirect('index.php', 'Installation report not found', 'danger');
}

// Check access for User 2
if ($currentRole === 'user_2' && $installation['installer_id'] != $userId) {
    redirect('index.php', 'Access denied', 'danger');
}

$validate = '';
$color = '';

if ($installation['latitude'] === $installation['mnl_latitude'] && $installation['longitude'] === $installation['mnl_longitude']) {
    $validate = 'Location Match';
} else {
    $validate = 'Location Mismatch';
}

if ($validate === 'Location Match') {
    $color = '#198754'; // green
} else {
    $color = '#dc3545'; // red  
}

// Get overall installation photos
$overallPhotos = $db->fetchAll(
    "SELECT * FROM installation_report_photos
     WHERE report_id = ?
     ORDER BY photo_type, display_order",
    [$installationId]
);

// Group overall photos by type
$overallBeforePhotos = array_filter($overallPhotos, fn($p) => $p['photo_type'] === 'before');
$overallAfterPhotos = array_filter($overallPhotos, fn($p) => $p['photo_type'] === 'after');
$overallStorePhotos = array_filter($overallPhotos, fn($p) => $p['photo_type'] === 'StoreImage');

// Get report items with photos
$reportItems = $db->fetchAll(
    "SELECT iri.*, ai.quantity_assigned, i.item_code, i.item_name, i.unit
     FROM installation_report_items iri
     JOIN assignment_items ai ON iri.assignment_item_id = ai.id
     JOIN inventory_items i ON ai.item_id = i.id
     WHERE iri.report_id = ?
     ORDER BY i.item_name",
    [$installationId]
);

// Get all item photos
$itemPhotos = [];
foreach ($reportItems as $item) {
    $itemPhotos[$item['id']] = [
        'before' => $db->fetchAll(
            "SELECT * FROM installation_item_photos
             WHERE report_item_id = ? AND photo_type = 'before'
             ORDER BY display_order",
            [$item['id']]
        ),
        'after' => $db->fetchAll(
            "SELECT * FROM installation_item_photos
             WHERE report_item_id = ? AND photo_type = 'after'
             ORDER BY display_order",
            [$item['id']]
        )
    ];
}

// Get inspection schedules
$inspections = $db->fetchAll(
    "SELECT isc.*, insp.full_name as inspector_name,
            (SELECT id FROM inspection_reports WHERE schedule_id = isc.id LIMIT 1) as report_id
     FROM inspection_schedules isc
     LEFT JOIN users insp ON isc.inspector_id = insp.id
     WHERE isc.installation_report_id = ?
     ORDER BY isc.month_number",
    [$installationId]
);

// Calculate totals
$totalInstalled = 0;
foreach ($reportItems as $item) {
    $totalInstalled += $item['quantity_installed'];
}

// Handle review action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($currentRole, ['super_admin', 'user_1'])) {
    $action = $_POST['action'] ?? '';
    $checkbox = $_POST['edit_perm'] ?? '';
    $permission = null;
    $message = null;

    if ($action === 'permit_edit') {
        $db->update('installation_reports', [
            'Permission' => 'Permitted',
            'reviewed_by' => $userId,
            'reviewed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$installationId]);

        createNotification(
            $installation['installer_id'],
            'Edit Permission Granted',
            "Your installation report {$installation['report_code']} has been allowed to be edited.",
            'info',
            APP_URL . "/modules/installations/view.php?id={$installationId}"
        );

        $auth->logActivity($userId, 'permit_edit_installation', 'installations', 'installation_reports', $installationId);
        redirect("view.php?id={$installationId}", 'Edit permission granted.', 'success');
    }

    if (in_array($action, ['approve', 'reject'])) {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        
        if($newStatus === 'rejected') {
            $permission = $checkbox === 'checked' ? 'Permitted' : 'Not Permitted';

            $reason = $_POST['reject_message'] ?? '';
            $message = "Reason for rejection: {$reason}";
        }

        $db->update('installation_reports', [
            'status' => $newStatus,
            'Permission' => $permission,
            'reviewed_by' => $userId,
            'reviewed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$installationId]);
        
        // Notify installer
        createNotification(
            $installation['installer_id'],
            'Installation Report ' . ucfirst($newStatus),
            "Your installation report {$installation['report_code']} has been {$newStatus}."."{$message}",
            $action === 'approve' ? 'success' : 'danger',
            APP_URL . "/modules/installations/view.php?id={$installationId}"
        );
        
        $auth->logActivity($userId, $action . '_installation', 'installations', 'installation_reports', $installationId);
        
        redirect("view.php?id={$installationId}", "Installation report {$newStatus}!", 'success');
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-camera me-2"></i>Installation Report
    </h1>
    <div>
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
                <span class= "d-flex align-text-center">
                <span class="bi bi-info-circle me-2"></span>Report Information
</span>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4 class="mb-1"><?php echo clean($installation['report_code']); ?></h4>
                    <?php echo statusBadge($installation['status']); ?>
                </div>
                
                <hr>
                
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" width="40%">Assignment:</td>
                        <td>
                            <a href="../assignments/view.php?id=<?php echo $installation['assignment_id']; ?>">
                                <?php echo clean($installation['assignment_code']); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Installer:</td>
                        <td><strong><?php echo clean($installation['installer_name']); ?></strong></td>
                    </tr>
                    <?php if ($installation['installer_phone']): ?>
                    <tr>
                         <td class="text-muted">Phone:</td>
                    <td>
                        <div class="d-flex align-items-center gap-1">
                            <span><?php echo clean($installation['installer_phone']); ?></span>

                            <button
                                type="button"
                                class="btn p-0"
                                style="border: none; background: none;"
                                onclick="copyPhone('<?php echo addslashes($installation['installer_phone']); ?>', this)">
                                
                                <i class="bi bi-copy text-secondary" style="font-size: 14px;"></i>
                            </button>
                        </div>
                    </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-muted">Install Date:</td>
                        <td><?php echo formatDate($installation['installation_date']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Submitted:</td>
                        <td><?php echo formatDateTime($installation['created_at']); ?></td>
                    </tr>
                    <?php if ($installation['reviewed_by']): ?>
                    <tr>
                        <td class="text-muted">Reviewed By:</td>
                        <td><?php echo clean($installation['reviewed_by_name']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Reviewed At:</td>
                        <td><?php echo formatDateTime($installation['reviewed_at']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <span class= "d-flex align-text-center"> 
                <span class="bi bi-geo-alt me-2"></span>Location
                    </span>
            </div>
            <div class="card-body">
                <h5 class="mb-1"><?php echo clean($installation['area_name']); ?></h5>
                <p class="text-muted mb-2"><?php echo clean($installation['area_code']); ?></p>
                
                <?php if ($installation['address']): ?>
                <p class="mb-1"><small><i class="bi bi-pin-map me-2"></i><?php echo clean($installation['address']); ?></small></p>
                <?php endif; ?>
                <p class="mb-2">
                    <small>
                        <i class="bi bi-building me-2"></i>
                        <?php echo clean($installation['city']); ?>
                        <?php if ($installation['province']): ?>, <?php echo clean($installation['province']); ?><?php endif; ?>
                    </small>
                </p>
                
                <hr>
                
                <div class="row text-center">
                    <div class="validate" >
                        <div class="validate-container" style="display:flex; justify-content: center">
                            <div class="fit-content">
                                <strong class="validate-text" style="
                                background-color: <?php echo $color; ?>; 
                                font-size: small; 
                                color: #ffffff; 
                                height: fit-content; 
                                width: fit-content; 
                                padding: 3px;
                                border-radius: 5px"><?php echo $validate; ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="col-6">
                        <small class="text-muted d-block">Latitude</small>
                        <strong><?php echo number_format($installation['latitude'], 6); ?></strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Longitude</small>
                        <strong><?php echo number_format($installation['longitude'], 6); ?></strong>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (isset($installation['owner_name'])): ?>
        <!-- Store Details -->
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <span class= "d-flex align-text-center">
                <span class="bi bi-shop me-2"></span>Store Details
        </span>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <?php if ($installation['agency_store_code']): ?>
                    <tr>
                        <td class="text-muted" width="45%">Store Code:</td>
                        <td><strong><?php echo clean($installation['agency_store_code']); ?></strong></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-muted">Visit Date:</td>
                        <td><?php echo formatDate($installation['date_of_visit']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status:</td>
                        <td>
                            <?php
                            $statusLabels = [
                                'agree_to_dressup' => 'Agreed to Dress-Up',
                                'store_closed' => 'Store Closed',
                                'refused_to_dressup' => 'Refused',
                                'reschedule' => 'Rescheduled',
                                'others' => 'Others'
                            ];
                            echo '<span class="badge bg-' . ($installation['store_status'] === 'agree_to_dressup' ? 'success' : 'warning') . '">';
                            echo $statusLabels[$installation['store_status']] ?? $installation['store_status'];
                            echo '</span>';
                            ?>
                        </td>
                    </tr>
                    <?php if ($installation['reschedule_date']): ?>
                    <tr>
                        <td class="text-muted">Reschedule:</td>
                        <td><?php echo formatDateTime($installation['reschedule_date']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($installation['status_remarks']): ?>
                    <tr>
                        <td class="text-muted">Status Remarks:</td>
                        <td><small><?php echo nl2br(clean($installation['status_remarks'])); ?></small></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($installation['store_name_before']): ?>
                    <tr>
                        <td class="text-muted">Name Before:</td>
                        <td><?php echo clean($installation['store_name_before']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($installation['store_name_after']): ?>
                    <tr>
                        <td class="text-muted">Name After:</td>
                        <td><strong><?php echo clean($installation['store_name_after']); ?></strong></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-muted">Owner:</td>
                        <td><strong><?php echo clean($installation['owner_name']); ?></strong></td>
                    </tr>
                     <tr>
                        <td class="text-muted">Store Type:</td>
                        <td><strong><?php echo clean($installation['store_type']); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Contact:</td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <span><?php echo clean(preg_replace('/[^0-9]/', '', $installation['contact_number'])); ?></span>

                        <button
                            type="button"
                            class="btn p-0"
                            style="border: none; background: none;"
                            onclick="copyPhone('<?php echo addslashes(preg_replace('/[^0-9]/', '', $installation['contact_number'])); ?>', this)">
                            
                            <i class="bi bi-copy text-secondary" style="font-size: 14px;"></i>
                        </button>
                    </div>
                </td>
                    </tr>
                    <?php if ($installation['total_area_sqm'] > 0): ?>
                    <tr>
                        <td class="text-muted">Store Area:</td>
                        <td>
                            <strong class="text-primary"><?php echo number_format($installation['total_area_sqm'], 2); ?> sqm</strong>
                            <?php if ($installation['area_length'] && $installation['area_width']): ?>
                            <br><small class="text-muted"><?php echo $installation['area_length']; ?>m × <?php echo $installation['area_width']; ?>m<?php echo $installation['additional_area_sqm'] ? ' + ' . $installation['additional_area_sqm'] . ' sqm' : ''; ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Detailed Address -->
        <?php if (isset($installation['complete_address'])): ?>
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <span class= "d-flex align-text-center">
                <span class="bi bi-pin-map me-2"></span>Complete Address
        </span>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong><?php echo clean($installation['complete_address']); ?></strong></p>
                <hr>
                <div class="row g-2">
                    <?php if ($installation['house_no']): ?>
                    <div class="col-6"><small class="text-muted">House:</small> <?php echo clean($installation['house_no']); ?></div>
                    <?php endif; ?>
                    <?php if ($installation['block']): ?>
                    <div class="col-6"><small class="text-muted">Block:</small> <?php echo clean($installation['block']); ?></div>
                    <?php endif; ?>
                    <?php if ($installation['lot']): ?>
                    <div class="col-6"><small class="text-muted">Lot:</small> <?php echo clean($installation['lot']); ?></div>
                    <?php endif; ?>
                    <?php if ($installation['street_name']): ?>
                    <div class="col-6"><small class="text-muted">Street:</small> <?php echo clean($installation['street_name']); ?></div>
                    <?php endif; ?>
                    <?php if ($installation['purok']): ?>
                    <div class="col-6"><small class="text-muted">Purok:</small> <?php echo clean($installation['purok']); ?></div>
                    <?php endif; ?>
                    <?php if ($installation['sitio']): ?>
                    <div class="col-6"><small class="text-muted">Sitio:</small> <?php echo clean($installation['sitio']); ?></div>
                    <?php endif; ?>
                    <?php if ($installation['zone']): ?>
                    <div class="col-6"><small class="text-muted">Zone:</small> <?php echo clean($installation['zone']); ?></div>
                    <?php endif; ?>
                    <?php if ($installation['phase']): ?>
                    <div class="col-6"><small class="text-muted">Phase:</small> <?php echo clean($installation['phase']); ?></div>
                    <?php endif; ?>
                    <?php if ($installation['road']): ?>
                    <div class="col-12"><small class="text-muted">Road:</small> <?php echo clean($installation['road']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($installation['overall_remarks']): ?>
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <i class="bi bi-chat-text me-2"></i>Remarks
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(clean($installation['overall_remarks'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Review Actions -->
        <?php if (in_array($currentRole, ['super_admin', 'user_1']) && $installation['status'] === 'submitted'): ?>
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <span class= "d-flex align-text-center">
                <span class="bi bi-check2-square me-2"></span>Review Actions
            </div>
            <div class="card-body">
                <p class="mb-3">Review this installation report:</p>

                <form method="POST" id="reviewForm">
                    <!-- Hidden input to track action -->
                    <input type="hidden" name="action" id="actionInput">

                    <!-- Reject message (hidden by default) -->
                    <div id="rejectMessageBox" class="mb-3" style="display: none;">
                        <label class="form-label">Reason for Rejection</label>
                        <textarea 
                            name="reject_message" 
                            class="form-control" 
                            rows="3" 
                            placeholder="Please provide reason for rejection..."></textarea>
                        
                        <input type="checkbox" name="edit_perm" id="edit_perm" value="checked" style="margin-top: 10px;"> 
                        <label for="edit_perm">Allow edits</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button 
                            type="button" 
                            class="btn btn-success flex-fill"
                            onclick="submitReview('approve')">
                            <i class="bi bi-check-lg me-1"></i>Approve
                        </button>

                        <button 
                            type="button" 
                            class="btn btn-danger flex-fill"
                            onclick="handleReject()">
                            <i class="bi bi-x-lg me-1"></i>Reject
                        </button>
                    </div>

                    <!-- Final submit button (only appears when rejecting) -->
                    <div id="confirmRejectBox" class="mt-3" style="display: none;">
                    <button type="submit" class="btn btn-danger w-100" onclick="return validateReject()">
                            Confirm Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($currentRole, ['super_admin', 'user_1']) && $installation['status'] === 'rejected' && $installation['Permission'] === 'Not Permitted'): ?>
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <i class="bi bi-check2-square me-2"></i>Permit Edit
            </div>
            <div class="card-body">
                <p class="mb-3">This report was rejected and currently cannot be edited. Grant edit permission so the installer can update the rejected report.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="permit_edit">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check2-square me-1"></i> Allow Edit
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Items and Photos -->
    <div class="col-lg-8 mb-4">
        <!-- Summary -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card stats-card h-100" style="border-left-color: #0d6efd;">
                    <div class="card-body text-center">
                        <h3 class="mb-0" style="color: #0d6efd;"><?php echo count($reportItems); ?></h3>
                        <small class="text-muted">Item Types</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card h-100" style="border-left-color: #198754;">
                    <div class="card-body text-center">
                        <h3 class="mb-0" style="color: #198754;"><?php echo number_format($totalInstalled); ?></h3>
                        <small class="text-muted">Units Installed</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card h-100" style="border-left-color: #6c757d;">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?php echo count($reportItems) * 2; ?></h3>
                        <small class="text-muted">Photos</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overall Installation Photos -->
        <?php if (!empty($overallBeforePhotos) || !empty($overallAfterPhotos) || !empty($overallStorePhotos)): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary">
                <span class= "d-flex align-text-center">
                <span class="bi bi-images me-2"></span> Overall Installation Photos
        </span>
            </div>
            <div class="card-body">
                <?php if (!empty($overallBeforePhotos)): ?>
                <h6 class="text-muted mb-3"><i class="bi bi-image me-1"></i>Before Installation</h6>
                <div class="row g-2 mb-4">
                    <?php foreach ($overallBeforePhotos as $photo): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="<?php echo APP_URL; ?>/uploads/before/<?php echo $photo['photo_filename']; ?>"
                           target="_blank" class="d-block position-relative photo-thumbnail">
                            <img src="<?php echo APP_URL; ?>/uploads/before/<?php echo $photo['photo_filename']; ?>"
                                 class="img-fluid rounded" alt="Before"
                                 style="aspect-ratio: 1; object-fit: cover; width: 100%;">
                            <span class="position-absolute top-0 start-0 m-2 badge bg-dark bg-opacity-75">
                                <?php echo $photo['display_order'] + 1; ?>
                            </span>
                        </a>
                        <?php if ($photo['caption']): ?>
                        <small class="text-muted d-block mt-1"><?php echo clean($photo['caption']); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($overallAfterPhotos)): ?>
                <h6 class="text-muted mb-3"><i class="bi bi-image me-1"></i>After Installation</h6>
                <div class="row g-2 mb-4">
                    <?php foreach ($overallAfterPhotos as $photo): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="<?php echo APP_URL; ?>/uploads/after/<?php echo $photo['photo_filename']; ?>"
                           target="_blank" class="d-block position-relative photo-thumbnail">
                            <img src="<?php echo APP_URL; ?>/uploads/after/<?php echo $photo['photo_filename']; ?>"
                                 class="img-fluid rounded" alt="After"
                                 style="aspect-ratio: 1; object-fit: cover; width: 100%;">
                            <span class="position-absolute top-0 start-0 m-2 badge bg-dark bg-opacity-75">
                                <?php echo $photo['display_order'] + 1; ?>
                            </span>
                        </a>
                        <?php if ($photo['caption']): ?>
                        <small class="text-muted d-block mt-1"><?php echo clean($photo['caption']); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($overallStorePhotos)): ?>
                <h6 class="text-muted mb-3"><i class="bi bi-building me-1"></i>Store Photos</h6>
                <div class="row g-2">
                    <?php foreach ($overallStorePhotos as $photo): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="<?php echo APP_URL; ?>/uploads/store_images/<?php echo $photo['photo_filename']; ?>"
                           target="_blank" class="d-block position-relative photo-thumbnail">
                            <img src="<?php echo APP_URL; ?>/uploads/store_images/<?php echo $photo['photo_filename']; ?>"
                                 class="img-fluid rounded" alt="Store"
                                 style="aspect-ratio: 1; object-fit: cover; width: 100%;">
                            <span class="position-absolute top-0 start-0 m-2 badge bg-dark bg-opacity-75">
                                <?php echo $photo['display_order'] + 1; ?>
                            </span>
                        </a>
                        <?php if ($photo['caption']): ?>
                        <small class="text-muted d-block mt-1"><?php echo clean($photo['caption']); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Installed Items -->
        <div class="card mb-4">
            <div class="card-header bg-primary">
                <span class= "d-flex align-text-center">
                <span class="bi bi-box-seam me-2"></span>Installed Items
                        </span>
            </div>
            <div class="card-body">
                <?php foreach ($reportItems as $index => $item): ?>
                <div class="card mb-3 <?php echo $index === count($reportItems) - 1 ? 'mb-0' : ''; ?>">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>
                                <strong><?php echo clean($item['item_code']); ?></strong> - 
                                <?php echo clean($item['item_name']); ?>
                            </span>
                            <span class="badge bg-success">
                                <?php echo number_format($item['quantity_installed']); ?> <?php echo $item['unit']; ?> installed
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $hasMultiplePhotos = !empty($itemPhotos[$item['id']]['before']) || !empty($itemPhotos[$item['id']]['after']);
                        $beforePhotos = $itemPhotos[$item['id']]['before'] ?? [];
                        $afterPhotos = $itemPhotos[$item['id']]['after'] ?? [];
                        ?>

                        <?php if ($hasMultiplePhotos): ?>
                        <!-- Display multiple photos from new system -->
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <h6 class="text-muted mb-2">
                                    <i class="bi bi-image me-1"></i>Before Photos
                                    <span class="badge bg-secondary"><?php echo count($beforePhotos); ?></span>
                                </h6>
                                <div class="row g-2">
                                    <?php foreach ($beforePhotos as $photo): ?>
                                    <div class="col-6">
                                        <a href="<?php echo APP_URL; ?>/uploads/before/<?php echo $photo['photo_filename']; ?>"
                                           target="_blank" class="d-block position-relative photo-thumbnail">
                                            <img src="<?php echo APP_URL; ?>/uploads/before/<?php echo $photo['photo_filename']; ?>"
                                                 class="img-fluid rounded" alt="Before"
                                                 style="aspect-ratio: 1; object-fit: cover; width: 100%;">
                                            <span class="position-absolute top-0 start-0 m-1 badge bg-dark bg-opacity-75" style="font-size: 0.7rem;">
                                                <?php echo $photo['display_order'] + 1; ?>
                                            </span>
                                        </a>
                                        <?php if ($photo['caption']): ?>
                                        <small class="text-muted d-block mt-1"><?php echo clean($photo['caption']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">
                                    <i class="bi bi-image me-1"></i>After Photos
                                    <span class="badge bg-secondary"><?php echo count($afterPhotos); ?></span>
                                </h6>
                                <div class="row g-2">
                                    <?php foreach ($afterPhotos as $photo): ?>
                                    <div class="col-6">
                                        <a href="<?php echo APP_URL; ?>/uploads/after/<?php echo $photo['photo_filename']; ?>"
                                           target="_blank" class="d-block position-relative photo-thumbnail">
                                            <img src="<?php echo APP_URL; ?>/uploads/after/<?php echo $photo['photo_filename']; ?>"
                                                 class="img-fluid rounded" alt="After"
                                                 style="aspect-ratio: 1; object-fit: cover; width: 100%;">
                                            <span class="position-absolute top-0 start-0 m-1 badge bg-dark bg-opacity-75" style="font-size: 0.7rem;">
                                                <?php echo $photo['display_order'] + 1; ?>
                                            </span>
                                        </a>
                                        <?php if ($photo['caption']): ?>
                                        <small class="text-muted d-block mt-1"><?php echo clean($photo['caption']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($item['before_photo'] && $item['after_photo']): ?>
                        <!-- Fallback to old single photo system for backward compatibility -->
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <h6 class="text-muted mb-2"><i class="bi bi-image me-1"></i>Before Photo</h6>
                                <a href="<?php echo APP_URL; ?>/uploads/before/<?php echo $item['before_photo']; ?>"
                                   target="_blank" class="d-block">
                                    <img src="<?php echo APP_URL; ?>/uploads/before/<?php echo $item['before_photo']; ?>"
                                         class="img-fluid rounded" alt="Before"
                                         style="max-height: 200px; width: 100%; object-fit: cover;">
                                </a>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2"><i class="bi bi-image me-1"></i>After Photo</h6>
                                <a href="<?php echo APP_URL; ?>/uploads/after/<?php echo $item['after_photo']; ?>"
                                   target="_blank" class="d-block">
                                    <img src="<?php echo APP_URL; ?>/uploads/after/<?php echo $item['after_photo']; ?>"
                                         class="img-fluid rounded" alt="After"
                                         style="max-height: 200px; width: 100%; object-fit: cover;">
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($item['remarks']): ?>
                        <hr>
                        <p class="mb-0"><small><strong>Remarks:</strong> <?php echo clean($item['remarks']); ?></small></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Inspection Schedule -->
        <div class="card">
            <div class="card-header bg-primary">
                <span class= "d-flex align-text-center">
                <span class="bi bi-calendar-check me-2"></span> Inspection Schedule (6 Months)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" width="80">Month</th>
                                <th>Scheduled Date</th>
                                <th>Inspector</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inspections as $insp): ?>
                            <?php $isOverdue = $insp['status'] === 'pending' && strtotime($insp['scheduled_date']) < time(); ?>
                            <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                                <td class="text-center">
                                    <span class="badge bg-primary"><?php echo $insp['month_number']; ?>/6</span>
                                </td>
                                <td>
                                    <?php echo formatDate($insp['scheduled_date']); ?>
                                    <?php if ($isOverdue): ?>
                                    <span class="badge bg-danger ms-1">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $insp['inspector_name'] ? clean($insp['inspector_name']) : '<span class="text-muted">Unassigned</span>'; ?>
                                </td>
                                <td><?php echo statusBadge($insp['status']); ?></td>
                                <td class="text-center">
                                    <?php if ($insp['report_id']): ?>
                                    <a href="../inspections/view.php?id=<?php echo $insp['report_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <?php elseif ($currentRole === 'user_3' && in_array($insp['status'], ['pending', 'scheduled'])): ?>
                                    <a href="../inspections/create.php?schedule_id=<?php echo $insp['id']; ?>" 
                                       class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-clipboard-check"></i> Inspect
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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