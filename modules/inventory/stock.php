<?php
/**
 * Inventory - Stock Adjustment
 */
$pageTitle = 'Stock Adjustment';
$breadcrumbs = [
    ['title' => 'Inventory', 'url' => 'index.php'],
    ['title' => 'Stock Adjustment']
];

require_once '../../includes/header.php';

$auth->requirePermission('inventory');

$db = Database::getInstance();
$errors = [];

// Get item ID
$itemId = intval($_GET['id'] ?? $_POST['item_id'] ?? 0);

if (!$itemId) {
    redirect('index.php', 'Invalid item', 'danger');
}

// Get item details
$item = $db->fetch(
    "SELECT i.*, c.category_name 
     FROM inventory_items i
     LEFT JOIN item_categories c ON i.category_id = c.id
     WHERE i.id = ?",
    [$itemId]
);

if (!$item) {
    redirect('index.php', 'Item not found', 'danger');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adjustmentType = $_POST['adjustment_type'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $reason = clean($_POST['reason'] ?? '');
    $notes = clean($_POST['notes'] ?? '');
    
    // Validation
    if (empty($adjustmentType)) {
        $errors['adjustment_type'] = 'Please select adjustment type';
    }
    
    if ($quantity <= 0) {
        $errors['quantity'] = 'Quantity must be greater than 0';
    }
    
    if (empty($reason)) {
        $errors['reason'] = 'Reason is required';
    }
    
    // Check if stock out doesn't exceed available
    if ($adjustmentType === 'stock_out' && $quantity > $item['quantity_available']) {
        $errors['quantity'] = 'Cannot remove more than available stock (' . $item['quantity_available'] . ')';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Update inventory
            if ($adjustmentType === 'stock_in') {
                $db->query(
                    "UPDATE inventory_items SET quantity_available = quantity_available + ? WHERE id = ?",
                    [$quantity, $itemId]
                );
            } else {
                $db->query(
                    "UPDATE inventory_items SET quantity_available = quantity_available - ? WHERE id = ?",
                    [$quantity, $itemId]
                );
            }
            
            // Log transaction
            $db->insert('inventory_transactions', [
                'item_id' => $itemId,
                'transaction_type' => $adjustmentType,
                'quantity' => $quantity,
                'reference_type' => 'adjustment',
                'notes' => "Reason: {$reason}" . ($notes ? ". Notes: {$notes}" : ''),
                'created_by' => $auth->userId()
            ]);
            
            // Log activity
            $auth->logActivity($auth->userId(), 'stock_adjustment', 'inventory', 'inventory_items', $itemId);
            
            $db->commit();
            
            redirect('view.php?id=' . $itemId, 'Stock adjusted successfully!', 'success');
            
        } catch (Exception $e) {
            $db->rollback();
            $errors['general'] = 'Failed to adjust stock. Please try again.';
        }
    }
}

// Common adjustment reasons
$reasons = [
    'stock_in' => [
        'New purchase/delivery',
        'Return from assignment',
        'Return from maintenance',
        'Found/recovered items',
        'Inventory count correction',
        'Transfer from other location',
        'Other'
    ],
    'stock_out' => [
        'Damaged/defective items',
        'Lost items',
        'Expired items',
        'Inventory count correction',
        'Transfer to other location',
        'Disposed/scrapped',
        'Other'
    ]
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-plus-slash-minus me-2"></i>Stock Adjustment
    </h1>
    <a href="view.php?id=<?php echo $itemId; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger"><?php echo $errors['general']; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Item Info -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header bg-primary">
                <i class="bi bi-box-seam me-2"></i>Item Information
            </div>
            <div class="card-body text-center">
                <h5 class="mb-1"><?php echo clean($item['item_code']); ?></h5>
                <p class="text-muted mb-3"><?php echo clean($item['item_name']); ?></p>
                
                <span class="badge bg-secondary mb-3"><?php echo clean($item['category_name']); ?></span>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-4">
                        <h4 class="text-success mb-0"><?php echo number_format($item['quantity_available']); ?></h4>
                        <small class="text-muted">Available</small>
                    </div>
                    <div class="col-4">
                        <h4 class="text-warning mb-0"><?php echo number_format($item['quantity_reserved']); ?></h4>
                        <small class="text-muted">Reserved</small>
                    </div>
                    <div class="col-4">
                        <h4 class="text-info mb-0"><?php echo number_format($item['quantity_installed']); ?></h4>
                        <small class="text-muted">Installed</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary">
                <i class="bi bi-info-circle me-2"></i>Guidelines
            </div>
            <div class="card-body">
                <ul class="mb-0 ps-3">
                    <li class="mb-2"><strong>Stock In:</strong> Use for new deliveries, returns, or corrections that increase stock.</li>
                    <li class="mb-2"><strong>Stock Out:</strong> Use for damaged items, losses, or corrections that decrease stock.</li>
                    <li>Always provide a clear reason for audit purposes.</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Adjustment Form -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header bg-primary">
                <i class="bi bi-sliders me-2"></i>Adjustment Details
            </div>
            <div class="card-body">
                <form method="POST" action="" id="adjustmentForm">
                    <input type="hidden" name="item_id" value="<?php echo $itemId; ?>">
                    
                    <!-- Adjustment Type -->
                    <div class="mb-4">
                        <label class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="card border-2 adjustment-option <?php echo ($_POST['adjustment_type'] ?? '') === 'stock_in' ? 'border-success' : ''; ?>" 
                                     data-type="stock_in" style="cursor: pointer;">
                                    <div class="card-body text-center py-4">
                                        <i class="bi bi-plus-circle text-success" style="font-size: 2.5rem;"></i>
                                        <h5 class="mt-2 mb-0 text-success">Stock In</h5>
                                        <small class="text-muted">Add to inventory</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card border-2 adjustment-option <?php echo ($_POST['adjustment_type'] ?? '') === 'stock_out' ? 'border-danger' : ''; ?>" 
                                     data-type="stock_out" style="cursor: pointer;">
                                    <div class="card-body text-center py-4">
                                        <i class="bi bi-dash-circle text-danger" style="font-size: 2.5rem;"></i>
                                        <h5 class="mt-2 mb-0 text-danger">Stock Out</h5>
                                        <small class="text-muted">Remove from inventory</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="adjustment_type" id="adjustment_type" 
                               value="<?php echo clean($_POST['adjustment_type'] ?? ''); ?>" required>
                        <?php if (isset($errors['adjustment_type'])): ?>
                        <div class="text-danger small mt-2"><?php echo $errors['adjustment_type']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control form-control-lg <?php echo isset($errors['quantity']) ? 'is-invalid' : ''; ?>" 
                                       id="quantity" name="quantity" 
                                       value="<?php echo intval($_POST['quantity'] ?? 1); ?>" 
                                       min="1" max="<?php echo $item['quantity_available'] + 100000; ?>" required>
                                <span class="input-group-text"><?php echo clean($item['unit']); ?></span>
                            </div>
                            <?php if (isset($errors['quantity'])): ?>
                            <div class="text-danger small mt-1"><?php echo $errors['quantity']; ?></div>
                            <?php endif; ?>
                            <div class="form-text" id="stockOutWarning" style="display: none;">
                                <span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Max available: <?php echo number_format($item['quantity_available']); ?></span>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                            <select class="form-select <?php echo isset($errors['reason']) ? 'is-invalid' : ''; ?>" 
                                    id="reason" name="reason" required>
                                <option value="">Select Reason</option>
                            </select>
                            <?php if (isset($errors['reason'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['reason']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Enter any additional details about this adjustment..."><?php echo clean($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Preview -->
                    <div class="alert alert-secondary" id="preview" style="display: none;">
                        <h6 class="mb-2"><i class="bi bi-eye me-2"></i>Preview</h6>
                        <div id="previewText"></div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="view.php?id=<?php echo $itemId; ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                            <i class="bi bi-check-lg me-1"></i>Apply Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
$reasonsJson = json_encode($reasons);
$currentQty = $item['quantity_available'];
$prevAdjustmentType = clean($_POST['adjustment_type'] ?? '');
$prevReason = clean($_POST['reason'] ?? '');

$extraScripts = <<<SCRIPT
<script>
const reasons = {$reasonsJson};
const currentQty = {$currentQty};
const prevAdjustmentType = '{$prevAdjustmentType}';
const prevReason = '{$prevReason}';
let selectedType = '';

// Adjustment type selection
document.querySelectorAll('.adjustment-option').forEach(card => {
    card.addEventListener('click', function() {
        // Remove previous selection
        document.querySelectorAll('.adjustment-option').forEach(c => {
            c.classList.remove('border-success', 'border-danger');
            c.classList.add('border-2');
        });
        
        // Set selection
        selectedType = this.dataset.type;
        document.getElementById('adjustment_type').value = selectedType;
        
        if (selectedType === 'stock_in') {
            this.classList.add('border-success');
            document.getElementById('stockOutWarning').style.display = 'none';
            document.getElementById('quantity').max = 100000;
        } else {
            this.classList.add('border-danger');
            document.getElementById('stockOutWarning').style.display = 'block';
            document.getElementById('quantity').max = currentQty;
        }
        
        // Update reasons dropdown
        const reasonSelect = document.getElementById('reason');
        reasonSelect.innerHTML = '<option value="">Select Reason</option>';
        reasons[selectedType].forEach(r => {
            reasonSelect.innerHTML += '<option value="' + r + '">' + r + '</option>';
        });
        
        updatePreview();
        validateForm();
    });
});

// Quantity and reason change
document.getElementById('quantity').addEventListener('input', function() {
    updatePreview();
    validateForm();
});

document.getElementById('reason').addEventListener('change', function() {
    updatePreview();
    validateForm();
});

function updatePreview() {
    const qty = parseInt(document.getElementById('quantity').value) || 0;
    const reason = document.getElementById('reason').value;
    const preview = document.getElementById('preview');
    const previewText = document.getElementById('previewText');
    
    if (selectedType && qty > 0) {
        preview.style.display = 'block';
        
        let newQty = selectedType === 'stock_in' ? currentQty + qty : currentQty - qty;
        let action = selectedType === 'stock_in' ? 'Adding' : 'Removing';
        let color = selectedType === 'stock_in' ? 'success' : 'danger';
        
        previewText.innerHTML = 
            '<strong>' + action + ' ' + qty.toLocaleString() + ' unit(s)</strong><br>' +
            'Current Stock: <strong>' + currentQty.toLocaleString() + '</strong> → ' +
            'New Stock: <strong class="text-' + color + '">' + newQty.toLocaleString() + '</strong>';
    } else {
        preview.style.display = 'none';
    }
}

function validateForm() {
    const qty = parseInt(document.getElementById('quantity').value) || 0;
    const reason = document.getElementById('reason').value;
    const btn = document.getElementById('submitBtn');
    
    let valid = selectedType && qty > 0 && reason;
    
    if (selectedType === 'stock_out' && qty > currentQty) {
        valid = false;
    }
    
    btn.disabled = !valid;
}

// Initialize if there's a previous selection
if (prevAdjustmentType) {
    const prevCard = document.querySelector('[data-type="' + prevAdjustmentType + '"]');
    if (prevCard) {
        prevCard.click();
        document.getElementById('reason').value = prevReason;
        updatePreview();
        validateForm();
    }
}
</script>
SCRIPT;

require_once '../../includes/footer.php'; 
?>