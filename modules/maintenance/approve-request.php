<?php
/**
 * Approve Item Requests
 * User 1 / Super Admin approves item requests from maintenance
 */
$pageTitle = 'Approve Request';
$breadcrumbs = [
    ['title' => 'Maintenance', 'url' => 'index.php'],
    ['title' => 'Approve Request']
];

require_once '../../includes/header.php';

$auth->requireRole(['super_admin', 'user_1']);

$db = Database::getInstance();
$userId = $auth->userId();
$errors = [];

// Get request ID
$requestId = intval($_GET['id'] ?? $_POST['request_id'] ?? 0);

if (!$requestId) {
    redirect('index.php', 'Invalid request', 'danger');
}

// Get request details
$request = $db->fetch(
    "SELECT mir.*, mt.ticket_code, mt.id as ticket_id, CONCAT(u.first_name, ' ', u.last_name) as requested_by_name
     FROM maintenance_item_requests mir
     JOIN maintenance_tickets mt ON mir.ticket_id = mt.id
     JOIN users u ON mir.requested_by = u.id
     WHERE mir.id = ?",
    [$requestId]
);

if (!$request) {
    redirect('index.php', 'Request not found', 'danger');
}

// Get request items
$requestItems = $db->fetchAll(
    "SELECT mri.*, i.item_code, i.item_name, i.unit, i.quantity_available
     FROM maintenance_request_items mri
     JOIN inventory_items i ON mri.item_id = i.id
     WHERE mri.request_id = ?",
    [$requestId]
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $approvedQuantities = $_POST['approved_qty'] ?? [];
    
    if ($action === 'approve') {
        try {
            $db->beginTransaction();
            
            $hasApproved = false;
            $allRejected = true;
            
            foreach ($requestItems as $item) {
                $approvedQty = intval($approvedQuantities[$item['id']] ?? 0);
                
                if ($approvedQty > 0) {
                    $hasApproved = true;
                    $allRejected = false;
                    
                    // Update request item
                    $db->update('maintenance_request_items', [
                        'quantity_approved' => $approvedQty,
                        'status' => 'approved'
                    ], 'id = ?', [$item['id']]);
                    
                } else {
                    // Reject item
                    $db->update('maintenance_request_items', [
                        'quantity_approved' => 0,
                        'status' => 'rejected'
                    ], 'id = ?', [$item['id']]);
                }
            }
            
            // Update request status
            $newStatus = $allRejected ? 'rejected' : ($hasApproved ? 'approved' : 'rejected');
            $db->update('maintenance_item_requests', [
                'status' => $newStatus,
                'approved_by' => $userId,
                'approved_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$requestId]);
            
            // Notify requester
            createNotification(
                $request['requested_by'],
                'Item Request ' . ucfirst($newStatus),
                "Your item request {$request['request_code']} has been {$newStatus}.",
                $newStatus === 'approved' ? 'success' : 'danger',
                APP_URL . "/modules/maintenance/view.php?id={$request['ticket_id']}"
            );
            
            // Log activity
            $auth->logActivity($userId, 'approved_request', 'maintenance', 'maintenance_item_requests', $requestId);
            
            $db->commit();
            redirect("view.php?id={$request['ticket_id']}", "Request {$newStatus}!", 'success');
            
        } catch (Exception $e) {
            $db->rollback();
            $errors['general'] = 'Failed to process request: ' . $e->getMessage();
        }
        
    } elseif ($action === 'issue') {
        // Issue approved items
        try {
            $db->beginTransaction();
            
            foreach ($requestItems as $item) {
                if ($item['status'] === 'approved' && $item['quantity_approved'] > 0) {
                    $issueQty = $item['quantity_approved'];
                    
                    // Update inventory
                    $db->query(
                        "UPDATE inventory_items SET quantity_available = quantity_available - ? WHERE id = ?",
                        [$issueQty, $item['item_id']]
                    );
                    
                    // Log transaction
                    $db->insert('inventory_transactions', [
                        'item_id' => $item['item_id'],
                        'transaction_type' => 'stock_out',
                        'quantity' => $issueQty,
                        'reference_type' => 'maintenance',
                        'reference_id' => $request['ticket_id'],
                        'notes' => "Issued for maintenance ticket {$request['ticket_code']}",
                        'created_by' => $userId
                    ]);
                    
                    // Update request item
                    $db->update('maintenance_request_items', [
                        'quantity_issued' => $issueQty,
                        'status' => 'issued'
                    ], 'id = ?', [$item['id']]);
                }
            }
            
            // Update request status
            $db->update('maintenance_item_requests', [
                'status' => 'issued',
                'issued_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$requestId]);
            
            // Update ticket status back to assigned/in_progress
            $db->update('maintenance_tickets', ['status' => 'in_progress'], 'id = ?', [$request['ticket_id']]);
            
            // Notify requester
            createNotification(
                $request['requested_by'],
                'Items Issued',
                "Items for request {$request['request_code']} have been issued. You can now proceed with maintenance.",
                'success',
                APP_URL . "/modules/maintenance/work.php?id={$request['ticket_id']}"
            );
            
            // Log activity
            $auth->logActivity($userId, 'issued_items', 'maintenance', 'maintenance_item_requests', $requestId);
            
            $db->commit();
            redirect("view.php?id={$request['ticket_id']}", "Items issued successfully!", 'success');
            
        } catch (Exception $e) {
            $db->rollback();
            $errors['general'] = 'Failed to issue items: ' . $e->getMessage();
        }
        
    } elseif ($action === 'reject_all') {
        try {
            $db->beginTransaction();
            
            // Reject all items
            $db->update('maintenance_request_items', ['status' => 'rejected', 'quantity_approved' => 0], 'request_id = ?', [$requestId]);
            
            // Update request
            $db->update('maintenance_item_requests', [
                'status' => 'rejected',
                'approved_by' => $userId,
                'approved_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$requestId]);
            
            // Update ticket status
            $db->update('maintenance_tickets', ['status' => 'assigned'], 'id = ?', [$request['ticket_id']]);
            
            // Notify requester
            createNotification(
                $request['requested_by'],
                'Item Request Rejected',
                "Your item request {$request['request_code']} has been rejected.",
                'danger',
                APP_URL . "/modules/maintenance/view.php?id={$request['ticket_id']}"
            );
            
            $db->commit();
            redirect("view.php?id={$request['ticket_id']}", "Request rejected!", 'warning');
            
        } catch (Exception $e) {
            $db->rollback();
            $errors['general'] = 'Failed to reject request: ' . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-check2-square me-2"></i>Approve Item Request
    </h1>
    <a href="view.php?id=<?php echo $request['ticket_id']; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Ticket
    </a>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger"><?php echo $errors['general']; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Request Info -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header bg-primary">
                <i class="bi bi-info-circle me-2"></i>Request Information
            </div>
            <div class="card-body">
                <h5 class="mb-1"><?php echo clean($request['request_code']); ?></h5>
                <?php echo statusBadge($request['status']); ?>
                
                <hr>
                
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Ticket:</td>
                        <td><strong><?php echo clean($request['ticket_code']); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Requested By:</td>
                        <td><?php echo clean($request['requested_by_name']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Date:</td>
                        <td><?php echo formatDateTime($request['created_at']); ?></td>
                    </tr>
                </table>
                
                <?php if ($request['notes']): ?>
                <hr>
                <h6>Notes</h6>
                <p class="text-muted mb-0"><?php echo nl2br(clean($request['notes'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Items -->
    <div class="col-lg-8 mb-4">
        <form method="POST" action="">
            <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
            
            <div class="card">
                <div class="card-header bg-primary">
                    <i class="bi bi-box-seam me-2"></i>Requested Items
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Available</th>
                                    <th class="text-center">Requested</th>
                                    <?php if ($request['status'] === 'pending'): ?>
                                    <th style="width: 150px;">Approve Qty</th>
                                    <?php else: ?>
                                    <th class="text-center">Approved</th>
                                    <th class="text-center">Issued</th>
                                    <th>Status</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requestItems as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo clean($item['item_code']); ?></strong>
                                        <br><small class="text-muted"><?php echo clean($item['item_name']); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $item['quantity_available'] >= $item['quantity_requested'] ? 'success' : 'warning'; ?>">
                                            <?php echo number_format($item['quantity_available']); ?>
                                        </span>
                                        <br><small class="text-muted"><?php echo $item['unit']; ?></small>
                                    </td>
                                    <td class="text-center">
                                        <strong><?php echo number_format($item['quantity_requested']); ?></strong>
                                    </td>
                                    <?php if ($request['status'] === 'pending'): ?>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" 
                                               name="approved_qty[<?php echo $item['id']; ?>]"
                                               value="<?php echo min($item['quantity_requested'], $item['quantity_available']); ?>"
                                               min="0" max="<?php echo min($item['quantity_requested'], $item['quantity_available']); ?>">
                                    </td>
                                    <?php else: ?>
                                    <td class="text-center">
                                        <?php if ($item['quantity_approved'] > 0): ?>
                                        <span class="text-success"><?php echo number_format($item['quantity_approved']); ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($item['quantity_issued'] > 0): ?>
                                        <span class="text-primary"><?php echo number_format($item['quantity_issued']); ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo statusBadge($item['status']); ?></td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <?php if ($request['status'] === 'pending'): ?>
                    <div class="d-flex justify-content-between">
                        <button type="submit" name="action" value="reject_all" class="btn btn-danger"
                                onclick="return confirm('Reject all items in this request?')">
                            <i class="bi bi-x-lg me-1"></i>Reject All
                        </button>
                        <button type="submit" name="action" value="approve" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i>Approve Selected
                        </button>
                    </div>
                    <?php elseif ($request['status'] === 'approved'): ?>
                    <div class="d-flex justify-content-end">
                        <button type="submit" name="action" value="issue" class="btn btn-primary">
                            <i class="bi bi-box-arrow-right me-1"></i>Issue Items
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted">
                        This request has been <?php echo $request['status']; ?>.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>