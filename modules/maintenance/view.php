<?php
/**
 * View Maintenance Ticket
 */
$pageTitle = 'View Ticket';
$breadcrumbs = [
    ['title' => 'Maintenance', 'url' => 'index.php'],
    ['title' => 'View Ticket']
];

require_once '../../includes/header.php';

$db = Database::getInstance();
$currentRole = $auth->role();
$userId = $auth->userId();

// Permission check
if (!$auth->can('maintenance') && !$auth->can('maintenance.view')) {
    redirect(APP_URL, 'Access denied', 'danger');
}

// Get ticket ID
$ticketId = intval($_GET['id'] ?? 0);

if (!$ticketId) {
    redirect('index.php', 'Invalid ticket', 'danger');
}

// Get ticket details
$ticket = $db->fetch(
    "SELECT mt.*, 
            ir.report_code as installation_code, ir.installation_date, ir.latitude, ir.longitude,
            ia.area_name, ia.city, ia.address,
            ii.item_status, ii.quantity_damaged, ii.quantity_missing, ii.quantity_needs_replacement,
            inv.item_code, inv.item_name, inv.unit,
            u1.full_name as assigned_to_name, u1.phone as assigned_to_phone,
            u2.full_name as created_by_name,
            insp.inspection_code
     FROM maintenance_tickets mt
     JOIN installation_reports ir ON mt.installation_report_id = ir.id
     JOIN assignments a ON ir.assignment_id = a.id
     JOIN installation_areas ia ON a.area_id = ia.id
     LEFT JOIN inspection_items ii ON mt.inspection_item_id = ii.id
     LEFT JOIN installation_report_items iri ON ii.installation_report_item_id = iri.id
     LEFT JOIN assignment_items ai ON iri.assignment_item_id = ai.id
     LEFT JOIN inventory_items inv ON ai.item_id = inv.id
     LEFT JOIN users u1 ON mt.assigned_to = u1.id
     JOIN users u2 ON mt.created_by = u2.id
     LEFT JOIN inspection_reports insp ON ii.inspection_report_id = insp.id
     WHERE mt.id = ?",
    [$ticketId]
);

if (!$ticket) {
    redirect('index.php', 'Ticket not found', 'danger');
}

// Get maintenance actions
$actions = $db->fetchAll(
    "SELECT ma.*, u.full_name as performed_by_name
     FROM maintenance_actions ma
     JOIN users u ON ma.performed_by = u.id
     WHERE ma.ticket_id = ?
     ORDER BY ma.created_at DESC",
    [$ticketId]
);

// Get item requests
$itemRequests = $db->fetchAll(
    "SELECT mir.*, u1.full_name as requested_by_name, u2.full_name as approved_by_name
     FROM maintenance_item_requests mir
     JOIN users u1 ON mir.requested_by = u1.id
     LEFT JOIN users u2 ON mir.approved_by = u2.id
     WHERE mir.ticket_id = ?
     ORDER BY mir.created_at DESC",
    [$ticketId]
);

// Get request items for each request
foreach ($itemRequests as &$request) {
    $request['items'] = $db->fetchAll(
        "SELECT mri.*, i.item_code, i.item_name, i.unit
         FROM maintenance_request_items mri
         JOIN inventory_items i ON mri.item_id = i.id
         WHERE mri.request_id = ?",
        [$request['id']]
    );
}
unset($request);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-wrench me-2"></i>Maintenance Ticket
    </h1>
    <div>
        <?php if ($currentRole === 'user_4' && in_array($ticket['status'], ['assigned', 'in_progress'])): ?>
        <a href="work.php?id=<?php echo $ticketId; ?>" class="btn btn-success me-2">
            <i class="bi bi-tools"></i> Work on Ticket
        </a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <!-- Ticket Info -->
    <div class="col-lg-4 mb-4">
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <i class="bi bi-ticket me-2"></i>Ticket Information
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4 class="mb-1"><?php echo clean($ticket['ticket_code']); ?></h4>
                    <?php echo statusBadge($ticket['status']); ?>
                    <?php echo priorityBadge($ticket['priority']); ?>
                </div>
                
                <hr>
                
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" width="40%">Type:</td>
                        <td>
                            <span class="badge bg-info">
                                <?php echo ucfirst(str_replace('_', ' ', $ticket['maintenance_type'])); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Assigned To:</td>
                        <td>
                            <?php if ($ticket['assigned_to_name']): ?>
                            <strong><?php echo clean($ticket['assigned_to_name']); ?></strong>
                            <?php else: ?>
                            <span class="text-muted">Unassigned</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($ticket['assigned_to_phone']): ?>
                    <tr>
                        <td class="text-muted">Phone:</td>
                        <td><a href="tel:<?php echo $ticket['assigned_to_phone']; ?>"><?php echo clean($ticket['assigned_to_phone']); ?></a></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-muted">Created By:</td>
                        <td><?php echo clean($ticket['created_by_name']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Created:</td>
                        <td><?php echo formatDateTime($ticket['created_at']); ?></td>
                    </tr>
                    <?php if ($ticket['completed_at']): ?>
                    <tr>
                        <td class="text-muted">Completed:</td>
                        <td><?php echo formatDateTime($ticket['completed_at']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <i class="bi bi-geo-alt me-2"></i>Location
            </div>
            <div class="card-body">
                <h6 class="mb-1"><?php echo clean($ticket['area_name']); ?></h6>
                <?php if ($ticket['address']): ?>
                <p class="mb-1"><small><?php echo clean($ticket['address']); ?></small></p>
                <?php endif; ?>
                <p class="text-muted mb-2"><small><?php echo clean($ticket['city']); ?></small></p>
                
                <hr>
                
                <p class="mb-1">
                    <strong>Installation:</strong> 
                    <a href="../installations/view.php?id=<?php echo $ticket['installation_report_id']; ?>">
                        <?php echo clean($ticket['installation_code']); ?>
                    </a>
                </p>
                <p class="mb-0"><small class="text-muted">Installed: <?php echo formatDate($ticket['installation_date']); ?></small></p>
                
                <?php if ($ticket['inspection_code']): ?>
                <p class="mb-0 mt-2">
                    <strong>From Inspection:</strong> <?php echo clean($ticket['inspection_code']); ?>
                </p>
                <?php endif; ?>
                
                <?php if ($ticket['latitude'] && $ticket['longitude']): ?>
                <hr>
                <a href="../installations/map.php?lat=<?php echo $ticket['latitude']; ?>&lng=<?php echo $ticket['longitude']; ?>" 
                   class="btn btn-outline-primary btn-sm w-100" target="_blank">
                    <i class="bi bi-map me-1"></i>View on Map
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($ticket['item_name']): ?>
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <i class="bi bi-box me-2"></i>Affected Item
            </div>
            <div class="card-body">
                <h6 class="mb-1"><?php echo clean($ticket['item_code']); ?></h6>
                <p class="text-muted mb-3"><?php echo clean($ticket['item_name']); ?></p>
                
                <div class="row text-center">
                    <div class="col-4">
                        <small class="text-warning d-block">Damaged</small>
                        <strong class="text-warning"><?php echo $ticket['quantity_damaged']; ?></strong>
                    </div>
                    <div class="col-4">
                        <small class="text-danger d-block">Missing</small>
                        <strong class="text-danger"><?php echo $ticket['quantity_missing']; ?></strong>
                    </div>
                    <div class="col-4">
                        <small class="text-danger d-block">Replace</small>
                        <strong class="text-danger"><?php echo $ticket['quantity_needs_replacement']; ?></strong>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header bg-primary">
                <i class="bi bi-file-text me-2"></i>Description
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(clean($ticket['description'])); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Actions and Requests -->
    <div class="col-lg-8 mb-4">
        <!-- Item Requests -->
        <?php if (!empty($itemRequests)): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary">
                <i class="bi bi-box-arrow-in-down me-2"></i>Item Requests
            </div>
            <div class="card-body">
                <?php foreach ($itemRequests as $request): ?>
                <div class="card mb-3">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <span>
                            <strong><?php echo clean($request['request_code']); ?></strong>
                            <small class="text-muted ms-2">by <?php echo clean($request['requested_by_name']); ?></small>
                        </span>
                        <?php echo statusBadge($request['status']); ?>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Requested</th>
                                    <th class="text-center">Approved</th>
                                    <th class="text-center">Issued</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($request['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo clean($item['item_code']); ?></strong>
                                        <br><small><?php echo clean($item['item_name']); ?></small>
                                    </td>
                                    <td class="text-center"><?php echo $item['quantity_requested']; ?></td>
                                    <td class="text-center">
                                        <?php if ($item['quantity_approved'] > 0): ?>
                                        <span class="text-success"><?php echo $item['quantity_approved']; ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($item['quantity_issued'] > 0): ?>
                                        <span class="text-primary"><?php echo $item['quantity_issued']; ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($request['notes']): ?>
                    <div class="card-footer">
                        <small><strong>Notes:</strong> <?php echo clean($request['notes']); ?></small>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Action History -->
        <div class="card">
            <div class="card-header bg-primary">
                <i class="bi bi-clock-history me-2"></i>Action History
            </div>
            <div class="card-body">
                <?php if (empty($actions)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox display-4 d-block mb-2"></i>
                    <p class="mb-0">No actions logged yet</p>
                </div>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($actions as $action): ?>
                    <div class="timeline-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-primary"><?php echo ucfirst($action['action_type']); ?></span>
                            <small class="text-muted"><?php echo formatDateTime($action['created_at']); ?></small>
                        </div>
                        
                        <p class="mb-2"><?php echo nl2br(clean($action['description'])); ?></p>
                        <small class="text-muted">by <?php echo clean($action['performed_by_name']); ?></small>
                        
                        <?php if ($action['before_photo'] || $action['after_photo']): ?>
                        <div class="mt-3">
                            <div class="row g-2">
                                <?php if ($action['before_photo']): ?>
                                <div class="col-6">
                                    <small class="text-muted d-block mb-1">Before:</small>
                                    <a href="<?php echo APP_URL; ?>/uploads/maintenance/<?php echo $action['before_photo']; ?>" target="_blank">
                                        <img src="<?php echo APP_URL; ?>/uploads/maintenance/<?php echo $action['before_photo']; ?>" 
                                             class="img-thumbnail" style="max-height: 100px;">
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php if ($action['after_photo']): ?>
                                <div class="col-6">
                                    <small class="text-muted d-block mb-1">After:</small>
                                    <a href="<?php echo APP_URL; ?>/uploads/maintenance/<?php echo $action['after_photo']; ?>" target="_blank">
                                        <img src="<?php echo APP_URL; ?>/uploads/maintenance/<?php echo $action['after_photo']; ?>" 
                                             class="img-thumbnail" style="max-height: 100px;">
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($action['latitude'] && $action['longitude']): ?>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="bi bi-geo-alt"></i>
                                <?php echo number_format($action['latitude'], 6); ?>, 
                                <?php echo number_format($action['longitude'], 6); ?>
                            </small>
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

<?php require_once '../../includes/footer.php'; ?>