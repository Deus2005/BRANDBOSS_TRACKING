<?php
/**
 * Work on Maintenance Ticket
 * User 4 performs maintenance actions
 */
$pageTitle = 'Work on Ticket';
$breadcrumbs = [
    ['title' => 'Maintenance', 'url' => 'index.php'],
    ['title' => 'Work on Ticket']
];

require_once '../../includes/header.php';

$auth->requireRole('user_4');

$db = Database::getInstance();
$userId = $auth->userId();
$errors = [];

// Get ticket ID
$ticketId = intval($_GET['id'] ?? $_POST['ticket_id'] ?? 0);

if (!$ticketId) {
    redirect('index.php', 'Invalid ticket', 'danger');
}

// Get ticket details
$ticket = $db->fetch(
    "SELECT mt.*, 
            ir.report_code, ir.latitude, ir.longitude, ir.location_address,
            ia.area_name, ia.city,
            ii.item_status, ii.quantity_damaged, ii.quantity_missing, ii.quantity_needs_replacement,
            inv.item_code, inv.item_name
     FROM maintenance_tickets mt
     JOIN installation_reports ir ON mt.installation_report_id = ir.id
     JOIN assignments a ON ir.assignment_id = a.id
     JOIN installation_areas ia ON a.area_id = ia.id
     LEFT JOIN inspection_items ii ON mt.inspection_item_id = ii.id
     LEFT JOIN installation_report_items iri ON ii.installation_report_item_id = iri.id
     LEFT JOIN assignment_items ai ON iri.assignment_item_id = ai.id
     LEFT JOIN inventory_items inv ON ai.item_id = inv.id
     WHERE mt.id = ? AND mt.assigned_to = ?",
    [$ticketId, $userId]
);

if (!$ticket) {
    redirect('index.php', 'Ticket not found or not assigned to you', 'danger');
}

// Get previous actions for this ticket
$previousActions = $db->fetchAll(
    "SELECT ma.*, u.full_name as performed_by_name
     FROM maintenance_actions ma
     JOIN users u ON ma.performed_by = u.id
     WHERE ma.ticket_id = ?
     ORDER BY ma.created_at DESC",
    [$ticketId]
);

// Get approved item requests
$approvedItems = $db->fetchAll(
    "SELECT mri.*, i.item_code, i.item_name, i.unit
     FROM maintenance_request_items mri
     JOIN maintenance_item_requests mir ON mri.request_id = mir.id
     JOIN inventory_items i ON mri.item_id = i.id
     WHERE mir.ticket_id = ? AND mri.status = 'issued'",
    [$ticketId]
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionType = $_POST['action_type'] ?? '';
    $actionDate = $_POST['action_date'] ?? date('Y-m-d');
    $description = clean($_POST['description'] ?? '');
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $itemsUsed = $_POST['items_used'] ?? [];
    $markComplete = isset($_POST['mark_complete']);
    
    // Validation
    if (empty($actionType)) {
        $errors['action_type'] = 'Action type is required';
    }
    if (empty($description)) {
        $errors['description'] = 'Description is required';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Handle photo uploads
            $beforePhoto = null;
            $afterPhoto = null;
            
            if (!empty($_FILES['before_photo']['name'])) {
                $result = uploadImage($_FILES['before_photo'], MAINTENANCE_PHOTO_PATH, $latitude, $longitude);
                if ($result['success']) {
                    $beforePhoto = $result['filename'];
                }
            }
            
            if (!empty($_FILES['after_photo']['name'])) {
                $result = uploadImage($_FILES['after_photo'], MAINTENANCE_PHOTO_PATH, $latitude, $longitude);
                if ($result['success']) {
                    $afterPhoto = $result['filename'];
                }
            }
            
            // Create maintenance action
            $db->insert('maintenance_actions', [
                'ticket_id' => $ticketId,
                'action_type' => $actionType,
                'performed_by' => $userId,
                'action_date' => $actionDate,
                'latitude' => $latitude ?: null,
                'longitude' => $longitude ?: null,
                'before_photo' => $beforePhoto,
                'after_photo' => $afterPhoto,
                'description' => $description,
                'items_used' => !empty($itemsUsed) ? json_encode($itemsUsed) : null
            ]);
            
            // Update ticket status
            if ($markComplete) {
                $db->update('maintenance_tickets', [
                    'status' => 'completed',
                    'completed_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$ticketId]);
                
                // Notify managers
                $managers = $db->fetchAll("SELECT id FROM users WHERE role IN ('super_admin', 'user_1') AND status = 'active'");
                foreach ($managers as $manager) {
                    createNotification(
                        $manager['id'],
                        'Maintenance Completed',
                        "Ticket {$ticket['ticket_code']} has been completed.",
                        'success',
                        APP_URL . "/modules/maintenance/view.php?id={$ticketId}"
                    );
                }
            } else {
                $db->update('maintenance_tickets', ['status' => 'in_progress'], 'id = ?', [$ticketId]);
            }
            
            // Log activity
            $auth->logActivity($userId, 'maintenance_action', 'maintenance', 'maintenance_tickets', $ticketId);
            
            $db->commit();
            
            if ($markComplete) {
                redirect('index.php', 'Maintenance completed successfully!', 'success');
            } else {
                redirect("work.php?id={$ticketId}", 'Action logged successfully!', 'success');
            }
            
        } catch (Exception $e) {
            $db->rollback();
            $errors['general'] = 'Failed to log action: ' . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-tools me-2"></i>Work on Ticket
    </h1>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger"><?php echo $errors['general']; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Ticket Info -->
    <div class="col-lg-4 mb-4">
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <i class="bi bi-ticket me-2"></i>Ticket Details
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Code:</strong> <?php echo clean($ticket['ticket_code']); ?></p>
                <p class="mb-2"><strong>Type:</strong> 
                    <span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $ticket['maintenance_type'])); ?></span>
                </p>
                <p class="mb-2"><strong>Priority:</strong> <?php echo priorityBadge($ticket['priority']); ?></p>
                <p class="mb-2"><strong>Status:</strong> <?php echo statusBadge($ticket['status']); ?></p>
                <hr>
                <p class="mb-2"><strong>Area:</strong> <?php echo clean($ticket['area_name']); ?></p>
                <p class="mb-2"><strong>City:</strong> <?php echo clean($ticket['city']); ?></p>
                <p class="mb-0"><strong>Installation:</strong> <?php echo clean($ticket['report_code']); ?></p>
            </div>
        </div>
        
        <?php if ($ticket['item_name']): ?>
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <i class="bi bi-box me-2"></i>Affected Item
            </div>
            <div class="card-body">
                <p class="mb-2"><strong><?php echo clean($ticket['item_code']); ?></strong></p>
                <p class="mb-2"><?php echo clean($ticket['item_name']); ?></p>
                <div class="small">
                    <span class="text-warning">Damaged: <?php echo $ticket['quantity_damaged']; ?></span> |
                    <span class="text-danger">Missing: <?php echo $ticket['quantity_missing']; ?></span> |
                    <span class="text-danger">Replace: <?php echo $ticket['quantity_needs_replacement']; ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <i class="bi bi-info-circle me-2"></i>Description
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(clean($ticket['description'])); ?></p>
            </div>
        </div>
        
        <?php if (!empty($approvedItems)): ?>
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-box-seam me-2"></i>Items Issued
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($approvedItems as $item): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <strong><?php echo clean($item['item_code']); ?></strong>
                            <br><small><?php echo clean($item['item_name']); ?></small>
                        </span>
                        <span class="badge bg-success"><?php echo $item['quantity_issued']; ?> <?php echo $item['unit']; ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Action Form & History -->
    <div class="col-lg-8 mb-4">
        <!-- Log Action Form -->
        <div class="card mb-4">
            <div class="card-header bg-primary">
                <i class="bi bi-plus-circle me-2"></i>Log Maintenance Action
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticketId; ?>">
                    <input type="hidden" name="latitude" id="latitude" value="">
                    <input type="hidden" name="longitude" id="longitude" value="">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="action_type" class="form-label">Action Type <span class="text-danger">*</span></label>
                            <select class="form-select <?php echo isset($errors['action_type']) ? 'is-invalid' : ''; ?>" 
                                    id="action_type" name="action_type" required>
                                <option value="">Select Action</option>
                                <option value="repair">Repair</option>
                                <option value="replace">Replace</option>
                                <option value="reinstall">Reinstall</option>
                                <option value="remove">Remove</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="action_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="action_date" name="action_date" 
                                   value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-12 mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                                      id="description" name="description" rows="3" required
                                      placeholder="Describe the work performed..."></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Before Photo</label>
                            <input type="file" class="form-control" name="before_photo" accept="image/*" capture="environment">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">After Photo</label>
                            <input type="file" class="form-control" name="after_photo" accept="image/*" capture="environment">
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="mark_complete" id="markComplete">
                            <label class="form-check-label text-success fw-bold" for="markComplete">
                                <i class="bi bi-check-circle me-1"></i>Mark as Completed
                            </label>
                        </div>
                        <div>
                            <a href="request-items.php?ticket_id=<?php echo $ticketId; ?>" class="btn btn-outline-warning me-2">
                                <i class="bi bi-box-arrow-in-down me-1"></i>Request Items
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Log Action
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Action History -->
        <div class="card">
            <div class="card-header bg-primary">
                <i class="bi bi-clock-history me-2"></i>Action History
            </div>
            <div class="card-body">
                <?php if (empty($previousActions)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox display-6 d-block mb-2"></i>
                    No actions logged yet
                </div>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($previousActions as $action): ?>
                    <div class="timeline-item">
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-primary"><?php echo ucfirst($action['action_type']); ?></span>
                            <small class="text-muted"><?php echo formatDateTime($action['created_at']); ?></small>
                        </div>
                        <p class="mb-1 mt-2"><?php echo nl2br(clean($action['description'])); ?></p>
                        <small class="text-muted">by <?php echo clean($action['performed_by_name']); ?></small>
                        
                        <?php if ($action['before_photo'] || $action['after_photo']): ?>
                        <div class="mt-2">
                            <?php if ($action['before_photo']): ?>
                            <a href="<?php echo APP_URL; ?>/uploads/maintenance/<?php echo $action['before_photo']; ?>" 
                               target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-image"></i> Before
                            </a>
                            <?php endif; ?>
                            <?php if ($action['after_photo']): ?>
                            <a href="<?php echo APP_URL; ?>/uploads/maintenance/<?php echo $action['after_photo']; ?>" 
                               target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-image"></i> After
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
$extraScripts = <<<'SCRIPT'
<script>
// Get GPS
App.getLocation()
    .then(coords => {
        document.getElementById('latitude').value = coords.latitude;
        document.getElementById('longitude').value = coords.longitude;
    })
    .catch(err => console.log('GPS not available'));
</script>
SCRIPT;

require_once '../../includes/footer.php'; 
?>
