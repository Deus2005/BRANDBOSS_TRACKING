<?php
/**
 * Inventory - Edit Item
 */
$pageTitle = 'Edit Item';
$breadcrumbs = [
    ['title' => 'Inventory', 'url' => 'index.php'],
    ['title' => 'Edit Item']
];

require_once '../../includes/header.php';

$auth->requirePermission('inventory');

$db = Database::getInstance();
$errors = [];

// Get item ID
$itemId = intval($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$itemId) {
    redirect('index.php', 'Invalid item', 'danger');
}

// Get item details
$item = $db->fetch("SELECT * FROM inventory_items WHERE id = ?", [$itemId]);

if (!$item) {
    redirect('index.php', 'Item not found', 'danger');
}

// Get categories
$categories = $db->fetchAll("SELECT * FROM item_categories WHERE status = 'active' ORDER BY category_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'item_code' => clean($_POST['item_code'] ?? ''),
        'item_name' => clean($_POST['item_name'] ?? ''),
        'category_id' => intval($_POST['category_id'] ?? 0),
        'description' => clean($_POST['description'] ?? ''),
        'unit' => clean($_POST['unit'] ?? 'piece'),
        'reorder_level' => intval($_POST['reorder_level'] ?? 10),
        'unit_cost' => floatval($_POST['unit_cost'] ?? 0),
        'status' => $_POST['status'] ?? 'active'
    ];
    
    // Validation
    $errors = validateRequired($data, ['item_code', 'item_name', 'category_id']);
    
    // Check unique code (excluding current item)
    if (empty($errors) && $db->exists('inventory_items', 'item_code = ? AND id != ?', [$data['item_code'], $itemId])) {
        $errors['item_code'] = 'Item code already exists';
    }
    
    if (empty($errors)) {
        try {
            // Store old values for logging
            $oldValues = [
                'item_code' => $item['item_code'],
                'item_name' => $item['item_name'],
                'category_id' => $item['category_id'],
                'unit_cost' => $item['unit_cost'],
                'status' => $item['status']
            ];
            
            $db->update('inventory_items', $data, 'id = ?', [$itemId]);
            
            // Log activity
            $auth->logActivity($auth->userId(), 'updated_item', 'inventory', 'inventory_items', $itemId, $oldValues, $data);
            
            redirect('view.php?id=' . $itemId, 'Item updated successfully!', 'success');
            
        } catch (Exception $e) {
            $errors['general'] = 'Failed to update item. Please try again.';
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-pencil me-2"></i>Edit Item
    </h1>
    <a href="view.php?id=<?php echo $itemId; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger"><?php echo $errors['general']; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-primary">
        <i class="bi bi-box-seam me-2"></i>Item Information
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?php echo $itemId; ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="item_code" class="form-label">Item Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($errors['item_code']) ? 'is-invalid' : ''; ?>" 
                           id="item_code" name="item_code" 
                           value="<?php echo clean($_POST['item_code'] ?? $item['item_code']); ?>" required>
                    <?php if (isset($errors['item_code'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['item_code']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="item_name" class="form-label">Item Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($errors['item_name']) ? 'is-invalid' : ''; ?>" 
                           id="item_name" name="item_name" 
                           value="<?php echo clean($_POST['item_name'] ?? $item['item_name']); ?>" required>
                    <?php if (isset($errors['item_name'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['item_name']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                    <select class="form-select <?php echo isset($errors['category_id']) ? 'is-invalid' : ''; ?>" 
                            id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                                <?php echo ($_POST['category_id'] ?? $item['category_id']) == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo clean($cat['category_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['category_id'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['category_id']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="unit" class="form-label">Unit of Measure</label>
                    <select class="form-select" id="unit" name="unit">
                        <?php 
                        $units = ['piece', 'set', 'box', 'roll', 'meter', 'kg'];
                        $currentUnit = $_POST['unit'] ?? $item['unit'];
                        foreach ($units as $u): 
                        ?>
                        <option value="<?php echo $u; ?>" <?php echo $currentUnit == $u ? 'selected' : ''; ?>>
                            <?php echo ucfirst($u); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12 mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo clean($_POST['description'] ?? $item['description']); ?></textarea>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="reorder_level" class="form-label">Reorder Level</label>
                    <input type="number" class="form-control" id="reorder_level" name="reorder_level" 
                           value="<?php echo intval($_POST['reorder_level'] ?? $item['reorder_level']); ?>" min="0">
                    <div class="form-text">Alert when stock falls below this level</div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="unit_cost" class="form-label">Unit Cost (₱)</label>
                    <input type="number" class="form-control" id="unit_cost" name="unit_cost" 
                           value="<?php echo floatval($_POST['unit_cost'] ?? $item['unit_cost']); ?>" min="0" step="0.01">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <?php 
                        $statuses = ['active', 'inactive', 'discontinued'];
                        $currentStatus = $_POST['status'] ?? $item['status'];
                        foreach ($statuses as $s): 
                        ?>
                        <option value="<?php echo $s; ?>" <?php echo $currentStatus == $s ? 'selected' : ''; ?>>
                            <?php echo ucfirst($s); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Current Stock:</strong> 
                Available: <strong><?php echo number_format($item['quantity_available']); ?></strong> | 
                Reserved: <strong><?php echo number_format($item['quantity_reserved']); ?></strong> | 
                Installed: <strong><?php echo number_format($item['quantity_installed']); ?></strong>
                <br><small>To adjust stock quantities, use the <a href="stock.php?id=<?php echo $itemId; ?>">Stock Adjustment</a> page.</small>
            </div>
            
            <hr>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="view.php?id=<?php echo $itemId; ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>