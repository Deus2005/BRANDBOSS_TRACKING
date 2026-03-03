<?php
/**
 * Inventory - View Item Details
 */
$pageTitle = 'View Item';
$breadcrumbs = [
    ['title' => 'Inventory', 'url' => 'index.php'],
    ['title' => 'View Item']
];

require_once '../../includes/header.php';

$auth->requirePermission('inventory');

$db = Database::getInstance();

// Get item ID
$itemId = intval($_GET['id'] ?? 0);

if (!$itemId) {
    redirect('index.php', 'Invalid item', 'danger');
}

// Get item details
$item = $db->fetch(
    "SELECT i.*, c.category_name, u.full_name as created_by_name
     FROM inventory_items i
     LEFT JOIN item_categories c ON i.category_id = c.id
     LEFT JOIN users u ON i.created_by = u.id
     WHERE i.id = ?",
    [$itemId]
);

if (!$item) {
    redirect('index.php', 'Item not found', 'danger');
}

// Get recent transactions
$transactions = $db->fetchAll(
    "SELECT it.*, u.full_name as created_by_name
     FROM inventory_transactions it
     LEFT JOIN users u ON it.created_by = u.id
     WHERE it.item_id = ?
     ORDER BY it.created_at DESC
     LIMIT 50",
    [$itemId]
);

// Get assignment usage
$assignments = $db->fetchAll(
    "SELECT ai.*, a.assignment_code, a.status as assignment_status, 
            ia.area_name, u.full_name as assigned_to_name
     FROM assignment_items ai
     JOIN assignments a ON ai.assignment_id = a.id
     JOIN installation_areas ia ON a.area_id = ia.id
     JOIN users u ON a.assigned_to = u.id
     WHERE ai.item_id = ?
     ORDER BY a.created_at DESC
     LIMIT 20",
    [$itemId]
);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-box-seam me-2"></i>Item Details
    </h1>
    <div>
        <a href="edit.php?id=<?php echo $itemId; ?>" class="btn btn-outline-primary me-2">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="stock.php?id=<?php echo $itemId; ?>" class="btn btn-outline-success me-2">
            <i class="bi bi-plus-slash-minus"></i> Adjust Stock
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <!-- Item Information -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary">
                <i class="bi bi-info-circle me-2"></i>Item Information
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="stats-icon mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h4 class="mb-1"><?php echo clean($item['item_code']); ?></h4>
                    <p class="text-muted mb-0"><?php echo clean($item['item_name']); ?></p>
                </div>
                
                <hr>
                
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted" width="40%">Category:</td>
                        <td><strong><?php echo clean($item['category_name']); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Unit:</td>
                        <td><?php echo clean($item['unit']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Unit Cost:</td>
                        <td><?php echo formatCurrency($item['unit_cost']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Reorder Level:</td>
                        <td><?php echo number_format($item['reorder_level']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status:</td>
                        <td><?php echo statusBadge($item['status']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Created:</td>
                        <td><?php echo formatDateTime($item['created_at']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Created By:</td>
                        <td><?php echo clean($item['created_by_name']); ?></td>
                    </tr>
                </table>
                
                <?php if ($item['description']): ?>
                <hr>
                <h6>Description</h6>
                <p class="text-muted mb-0"><?php echo nl2br(clean($item['description'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Stock Summary -->
    <div class="col-lg-8 mb-4">
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card stats-card h-100" style="border-left-color: #198754;">
                    <div class="card-body text-center">
                        <h2 class="mb-1" style="color: #198754;"><?php echo number_format($item['quantity_available']); ?></h2>
                        <p class="text-muted mb-0">Available</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card h-100" style="border-left-color: #ffc107;">
                    <div class="card-body text-center">
                        <h2 class="mb-1" style="color: #ffc107;"><?php echo number_format($item['quantity_reserved']); ?></h2>
                        <p class="text-muted mb-0">Reserved</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card h-100" style="border-left-color: #0d6efd;">
                    <div class="card-body text-center">
                        <h2 class="mb-1" style="color: #0d6efd;"><?php echo number_format($item['quantity_installed']); ?></h2>
                        <p class="text-muted mb-0">Installed</p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($item['quantity_available'] <= $item['reorder_level']): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Low Stock Warning!</strong> Available quantity is at or below reorder level.
        </div>
        <?php endif; ?>
        
        <!-- Transaction History -->
        <div class="card">
            <div class="card-header bg-primary">
                <i class="bi bi-clock-history me-2"></i>Transaction History
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover mb-0">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th class="text-center">Qty</th>
                                <th>Reference</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No transactions yet</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($transactions as $trans): ?>
                            <?php
                            $typeColors = [
                                'stock_in' => 'success',
                                'stock_out' => 'danger',
                                'reserved' => 'warning',
                                'released' => 'info',
                                'adjustment' => 'secondary'
                            ];
                            $typeColor = $typeColors[$trans['transaction_type']] ?? 'secondary';
                            $qtyPrefix = in_array($trans['transaction_type'], ['stock_in', 'released']) ? '+' : '-';
                            ?>
                            <tr>
                                <td>
                                    <small><?php echo formatDateTime($trans['created_at']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $typeColor; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $trans['transaction_type'])); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <strong class="text-<?php echo $typeColor; ?>">
                                        <?php echo $qtyPrefix . number_format($trans['quantity']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <small>
                                        <?php echo ucfirst($trans['reference_type'] ?? '-'); ?>
                                        <?php if ($trans['reference_id']): ?>
                                        #<?php echo $trans['reference_id']; ?>
                                        <?php endif; ?>
                                    </small>
                                    <?php if ($trans['notes']): ?>
                                    <br><small class="text-muted"><?php echo clean(truncate($trans['notes'], 30)); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><small><?php echo clean($trans['created_by_name']); ?></small></td>
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

<!-- Assignment Usage -->
<?php if (!empty($assignments)): ?>
<div class="card">
    <div class="card-header bg-primary">
        <i class="bi bi-clipboard-check me-2"></i>Assignment Usage
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Assignment</th>
                        <th>Area</th>
                        <th>Assigned To</th>
                        <th class="text-center">Assigned</th>
                        <th class="text-center">Installed</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assign): ?>
                    <tr>
                        <td>
                            <a href="../assignments/view.php?id=<?php echo $assign['assignment_id']; ?>">
                                <strong><?php echo clean($assign['assignment_code']); ?></strong>
                            </a>
                        </td>
                        <td><?php echo clean($assign['area_name']); ?></td>
                        <td><?php echo clean($assign['assigned_to_name']); ?></td>
                        <td class="text-center"><?php echo number_format($assign['quantity_assigned']); ?></td>
                        <td class="text-center"><?php echo number_format($assign['quantity_installed']); ?></td>
                        <td><?php echo statusBadge($assign['status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>