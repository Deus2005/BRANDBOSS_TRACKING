<?php
/**
 * Request Items for Maintenance
 * User 4 requests items from inventory for maintenance work
 */
$pageTitle = 'Request Items';
$breadcrumbs = [
    ['title' => 'Maintenance', 'url' => 'index.php'],
    ['title' => 'Request Items']
];

require_once '../../includes/header.php';

$auth->requireRole('user_4');

$db = Database::getInstance();
$userId = $auth->userId();
$errors = [];

// Get ticket ID
$ticketId = intval($_GET['ticket_id'] ?? $_POST['ticket_id'] ?? 0);

if (!$ticketId) {
    redirect('index.php', 'Invalid ticket', 'danger');
}

// Get ticket details
$ticket = $db->fetch(
    "SELECT mt.*, ir.report_code, ia.area_name, ia.city
     FROM maintenance_tickets mt
     JOIN installation_reports ir ON mt.installation_report_id = ir.id
     JOIN assignments a ON ir.assignment_id = a.id
     JOIN installation_areas ia ON a.area_id = ia.id
     WHERE mt.id = ? AND mt.assigned_to = ?",
    [$ticketId, $userId]
);

if (!$ticket) {
    redirect('index.php', 'Ticket not found or not assigned to you', 'danger');
}

// Get available inventory items
$items = $db->fetchAll(
    "SELECT i.*, c.category_name 
     FROM inventory_items i 
     JOIN item_categories c ON i.category_id = c.id 
     WHERE i.status = 'active' AND i.quantity_available > 0 
     ORDER BY c.category_name, i.item_name"
);

// Get pending requests for this ticket
$pendingRequests = $db->fetchAll(
    "SELECT mir.*, 
            (SELECT GROUP_CONCAT(CONCAT(i.item_code, ' (', mri.quantity_requested, ')') SEPARATOR ', ')
             FROM maintenance_request_items mri
             JOIN inventory_items i ON mri.item_id = i.id
             WHERE mri.request_id = mir.id) as items_summary
     FROM maintenance_item_requests mir
     WHERE mir.ticket_id = ? AND mir.status IN ('pending', 'approved')
     ORDER BY mir.created_at DESC",
    [$ticketId]
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedItems = $_POST['items'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $notes = clean($_POST['notes'] ?? '');
    
    // Validation
    if (empty($selectedItems)) {
        $errors['items'] = 'Please select at least one item';
    }
    
    // Validate quantities and stock
    foreach ($selectedItems as $itemId) {
        $qty = intval($quantities[$itemId] ?? 0);
        if ($qty <= 0) {
            $errors['items'] = 'All selected items must have a quantity greater than 0';
            break;
        }
        
        $item = $db->fetch("SELECT quantity_available FROM inventory_items WHERE id = ?", [$itemId]);
        if ($item && $qty > $item['quantity_available']) {
            $errors['items'] = 'Requested quantity exceeds available stock for one or more items';
            break;
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Create request
            $requestCode = generateCode('MIR-');
            $requestId = $db->insert('maintenance_item_requests', [
                'request_code' => $requestCode,
                'ticket_id' => $ticketId,
                'requested_by' => $userId,
                'status' => 'pending',
                'notes' => $notes
            ]);
            
            // Add request items
            foreach ($selectedItems as $itemId) {
                $qty = intval($quantities[$itemId]);
                
                $db->insert('maintenance_request_items', [
                    'request_id' => $requestId,
                    'item_id' => $itemId,
                    'quantity_requested' => $qty,
                    'status' => 'pending'
                ]);
            }
            
            // Update ticket status
            $db->update('maintenance_tickets', ['status' => 'pending_items'], 'id = ?', [$ticketId]);
            
            // Notify managers
            $managers = $db->fetchAll("SELECT id FROM users WHERE role IN ('super_admin', 'user_1') AND status = 'active'");
            foreach ($managers as $manager) {
                createNotification(
                    $manager['id'],
                    'New Item Request',
                    "Item request {$requestCode} submitted for ticket {$ticket['ticket_code']}. Pending approval.",
                    'warning',
                    APP_URL . "/modules/maintenance/approve-request.php?id={$requestId}"
                );
            }
            
            // Log activity
            $auth->logActivity($userId, 'item_request', 'maintenance', 'maintenance_item_requests', $requestId);
            
            $db->commit();
            redirect("view.php?id={$ticketId}", 'Item request submitted successfully! Waiting for approval.', 'success');
            
        } catch (Exception $e) {
            $db->rollback();
            $errors['general'] = 'Failed to submit request: ' . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-box-arrow-in-down me-2"></i>Request Items
    </h1>
    <a href="work.php?id=<?php echo $ticketId; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Ticket
    </a>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger"><?php echo $errors['general']; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Ticket Info & Pending Requests -->
    <div class="col-lg-4 mb-4">
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <i class="bi bi-ticket me-2"></i>Ticket Information
            </div>
            <div class="card-body">
                <h5 class="mb-1"><?php echo clean($ticket['ticket_code']); ?></h5>
                <p class="text-muted mb-2"><?php echo clean($ticket['report_code']); ?></p>
                
                <hr>
                
                <p class="mb-1"><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $ticket['maintenance_type'])); ?></p>
                <p class="mb-1"><strong>Area:</strong> <?php echo clean($ticket['area_name']); ?></p>
                <p class="mb-0"><strong>City:</strong> <?php echo clean($ticket['city']); ?></p>
            </div>
        </div>
        
        <?php if (!empty($pendingRequests)): ?>
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-hourglass-split me-2"></i>Pending Requests
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($pendingRequests as $req): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?php echo clean($req['request_code']); ?></strong>
                                <br><small class="text-muted"><?php echo clean($req['items_summary']); ?></small>
                            </div>
                            <?php echo statusBadge($req['status']); ?>
                        </div>
                        <small class="text-muted"><?php echo formatDateTime($req['created_at']); ?></small>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Item Selection -->
    <div class="col-lg-8 mb-4">
        <form method="POST" action="" id="requestForm">
            <input type="hidden" name="ticket_id" value="<?php echo $ticketId; ?>">
            
            <div class="card">
                <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box-seam me-2"></i>Select Items</span>
                    <input type="text" class="form-control form-control-sm w-auto" id="itemSearch" placeholder="Search items...">
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    <?php if (isset($errors['items'])): ?>
                    <div class="alert alert-danger m-3"><?php echo $errors['items']; ?></div>
                    <?php endif; ?>
                    
                    <table class="table table-hover mb-0" id="itemsTable">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                                <th>Item</th>
                                <th class="text-center" style="width: 100px;">Available</th>
                                <th style="width: 120px;">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No items available</td>
                            </tr>
                            <?php else: ?>
                            <?php 
                            $currentCategory = '';
                            foreach ($items as $item): 
                                if ($item['category_name'] !== $currentCategory):
                                    $currentCategory = $item['category_name'];
                            ?>
                            <tr class="table-secondary">
                                <td colspan="4"><strong><?php echo clean($currentCategory); ?></strong></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="item-row" data-name="<?php echo strtolower($item['item_name'] . ' ' . $item['item_code']); ?>">
                                <td>
                                    <input type="checkbox" class="form-check-input item-checkbox" 
                                           name="items[]" value="<?php echo $item['id']; ?>"
                                           <?php echo in_array($item['id'], $_POST['items'] ?? []) ? 'checked' : ''; ?>>
                                </td>
                                <td>
                                    <strong><?php echo clean($item['item_code']); ?></strong>
                                    <br><small><?php echo clean($item['item_name']); ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success"><?php echo number_format($item['quantity_available']); ?></span>
                                    <br><small class="text-muted"><?php echo $item['unit']; ?></small>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm quantity-input" 
                                           name="quantities[<?php echo $item['id']; ?>]" 
                                           value="<?php echo $_POST['quantities'][$item['id']] ?? 1; ?>"
                                           min="1" max="<?php echo $item['quantity_available']; ?>"
                                           disabled>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <div class="mb-3">
                        <label for="notes" class="form-label">Request Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" 
                                  placeholder="Explain why you need these items..."><?php echo clean($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <span id="selectedCount">0 items selected</span>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Submit Request
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php 
$extraScripts = <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const selectAll = document.getElementById('selectAll');
    const selectedCount = document.getElementById('selectedCount');
    const itemSearch = document.getElementById('itemSearch');
    
    function updateCount() {
        const checked = document.querySelectorAll('.item-checkbox:checked').length;
        selectedCount.textContent = checked + ' item(s) selected';
    }
    
    // Toggle quantity input on checkbox change
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const row = this.closest('tr');
            const qtyInput = row.querySelector('.quantity-input');
            qtyInput.disabled = !this.checked;
            if (this.checked) {
                qtyInput.focus();
            }
            updateCount();
        });
        
        // Initialize state
        if (cb.checked) {
            cb.closest('tr').querySelector('.quantity-input').disabled = false;
        }
    });
    
    // Select all
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
            cb.dispatchEvent(new Event('change'));
        });
    });
    
    // Search filter
    itemSearch.addEventListener('input', function() {
        const search = this.value.toLowerCase();
        document.querySelectorAll('.item-row').forEach(row => {
            const name = row.dataset.name;
            row.style.display = name.includes(search) ? '' : 'none';
        });
    });
    
    updateCount();
});
</script>
SCRIPT;

require_once '../../includes/footer.php'; 
?>