<?php
/**
 * Inventory - Add New Item
 */
$pageTitle = 'Add Item';
$breadcrumbs = [
    ['title' => 'Inventory', 'url' => 'index.php'],
    ['title' => 'Add Item']
];

require_once '../../includes/header.php';

$auth->requirePermission('inventory');

$db = Database::getInstance();
$errors = [];

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
        'quantity_available' => intval($_POST['quantity_available'] ?? 0),
        'reorder_level' => intval($_POST['reorder_level'] ?? 10),
        'unit_cost' => floatval($_POST['unit_cost'] ?? 0),
        'status' => $_POST['status'] ?? 'active',
        'created_by' => $auth->userId()
    ];
    
    // Validation
    $errors = validateRequired($data, ['item_code', 'item_name', 'category_id']);
    
    // Check unique code
    if (empty($errors) && $db->exists('inventory_items', 'item_code = ?', [$data['item_code']])) {
        $errors['item_code'] = 'Item code already exists';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            $itemId = $db->insert('inventory_items', $data);
            
            // Log initial stock if any
            if ($data['quantity_available'] > 0) {
                $db->insert('inventory_transactions', [
                    'item_id' => $itemId,
                    'transaction_type' => 'stock_in',
                    'quantity' => $data['quantity_available'],
                    'reference_type' => 'adjustment',
                    'notes' => 'Initial stock',
                    'created_by' => $auth->userId()
                ]);
            }
            
            // Log activity
            $auth->logActivity($auth->userId(), 'created_item', 'inventory', 'inventory_items', $itemId);
            
            $db->commit();
            redirect('index.php', 'Item added successfully!', 'success');
            
        } catch (Exception $e) {
            $db->rollback();
            $errors['general'] = 'Failed to add item. Please try again.';
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-plus-circle me-2"></i>Add New Item
    </h1>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger"><?php echo $errors['general']; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-primary">
        <span class="d-flex align-text-center">
        <span class="bi bi-box-seam me-2"></span>Item Information
</span>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="item_code" class="form-label">Item Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($errors['item_code']) ? 'is-invalid' : ''; ?>" 
                           id="item_code" name="item_code" value="<?php echo clean($_POST['item_code'] ?? ''); ?>" required>
                    <?php if (isset($errors['item_code'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['item_code']; ?></div>
                    <?php endif; ?>
                    <div class="form-text">Unique identifier for this item</div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="item_name" class="form-label">Item Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($errors['item_name']) ? 'is-invalid' : ''; ?>" 
                           id="item_name" name="item_name" value="<?php echo clean($_POST['item_name'] ?? ''); ?>" required>
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
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
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
                        <option value="piece" <?php echo ($_POST['unit'] ?? '') == 'piece' ? 'selected' : ''; ?>>Piece</option>
                        <option value="set" <?php echo ($_POST['unit'] ?? '') == 'set' ? 'selected' : ''; ?>>Set</option>
                        <option value="box" <?php echo ($_POST['unit'] ?? '') == 'box' ? 'selected' : ''; ?>>Box</option>
                        <option value="roll" <?php echo ($_POST['unit'] ?? '') == 'roll' ? 'selected' : ''; ?>>Roll</option>
                        <option value="meter" <?php echo ($_POST['unit'] ?? '') == 'meter' ? 'selected' : ''; ?>>Meter</option>
                        <option value="kg" <?php echo ($_POST['unit'] ?? '') == 'kg' ? 'selected' : ''; ?>>Kilogram</option>
                    </select>
                </div>
                
                <div class="col-12 mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo clean($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="quantity_available" class="form-label">Initial Quantity</label>
                    <input type="number" class="form-control" id="quantity_available" name="quantity_available" 
                           value="<?php echo intval($_POST['quantity_available'] ?? 0); ?>" min="0">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="reorder_level" class="form-label">Reorder Level</label>
                    <input type="number" class="form-control" id="reorder_level" name="reorder_level" 
                           value="<?php echo intval($_POST['reorder_level'] ?? 10); ?>" min="0">
                    <div class="form-text">Alert when stock falls below this level</div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="unit_cost" class="form-label">Unit Cost (₱)</label>
                    <input type="number" class="form-control" id="unit_cost" name="unit_cost" 
                           value="<?php echo floatval($_POST['unit_cost'] ?? 0); ?>" min="0" step="0.01">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo ($_POST['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($_POST['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <hr>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Save Item
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
