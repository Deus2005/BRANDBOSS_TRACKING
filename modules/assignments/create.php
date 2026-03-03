<?php
/**
 * Create New Assignment
 */
$pageTitle = 'New Assignment';
$breadcrumbs = [
    ['title' => 'Assignments', 'url' => 'index.php'],
    ['title' => 'New Assignment']
];

require_once '../../includes/header.php';

$auth->requireRole(['super_admin', 'user_1']);

$db = Database::getInstance();
$errors = [];

// Get installers (User 2)
$installers = $db->fetchAll("SELECT id, employee_id, full_name FROM users WHERE role = 'user_2' AND status = 'active' ORDER BY full_name");

// Get areas
$areas = $db->fetchAll("SELECT * FROM installation_areas WHERE status = 'active' ORDER BY area_name");

// Get available inventory items
$items = $db->fetchAll(
    "SELECT i.*, c.category_name 
     FROM inventory_items i 
     JOIN item_categories c ON i.category_id = c.id 
     WHERE i.status = 'active' AND i.quantity_available > 0 
     ORDER BY c.category_name, i.item_name"
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'assignment_code' => generateCode('ASN-'),
        'assigned_to' => intval($_POST['assigned_to'] ?? 0),
        'area_id' => intval($_POST['area_id'] ?? 0),
        'assignment_date' => $_POST['assignment_date'] ?? date('Y-m-d'),
        'due_date' => $_POST['due_date'] ?? null,
        'priority' => $_POST['priority'] ?? 'normal',
        'notes' => clean($_POST['notes'] ?? ''),
        'assigned_by' => $auth->userId(),
        'status' => 'pending'
    ];
    
    $selectedItems = $_POST['items'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    
    // Validation
    $errors = validateRequired($data, ['assigned_to', 'area_id']);
    
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
            $errors['items'] = 'Insufficient stock for one or more items';
            break;
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Create assignment
            $assignmentId = $db->insert('assignments', $data);
            
            // Add items and reserve stock
            foreach ($selectedItems as $itemId) {
                $qty = intval($quantities[$itemId]);
                
                // Add assignment item
                $db->insert('assignment_items', [
                    'assignment_id' => $assignmentId,
                    'item_id' => $itemId,
                    'quantity_assigned' => $qty,
                    'status' => 'pending'
                ]);
                
                // Reserve stock
                $db->query(
                    "UPDATE inventory_items SET quantity_available = quantity_available - ?, quantity_reserved = quantity_reserved + ? WHERE id = ?",
                    [$qty, $qty, $itemId]
                );
                
                // Log transaction
                $db->insert('inventory_transactions', [
                    'item_id' => $itemId,
                    'transaction_type' => 'reserved',
                    'quantity' => $qty,
                    'reference_type' => 'assignment',
                    'reference_id' => $assignmentId,
                    'notes' => "Reserved for assignment {$data['assignment_code']}",
                    'created_by' => $auth->userId()
                ]);
            }
            
            // Notify installer
            createNotification(
                $data['assigned_to'],
                'New Assignment',
                "You have been assigned to install items at a new location. Code: {$data['assignment_code']}",
                'info',
                APP_URL . "/modules/assignments/view.php?id={$assignmentId}"
            );
            
            // Log activity
            $auth->logActivity($auth->userId(), 'created_assignment', 'assignments', 'assignments', $assignmentId);
            
            $db->commit();
            redirect('index.php', 'Assignment created successfully!', 'success');
            
        } catch (Exception $e) {
            $db->rollback();
            $errors['general'] = 'Failed to create assignment. Please try again.';
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-clipboard-plus me-2"></i>Create Assignment
    </h1>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger"><?php echo $errors['general']; ?></div>
<?php endif; ?>

<form method="POST" action="" id="assignmentForm">
    <div class="row">
        <!-- Assignment Details -->
        <div class="col-lg-5 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary">
                    <i class="bi bi-info-circle me-2"></i>Assignment Details
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="assigned_to" class="form-label">Assign To <span class="text-danger">*</span></label>
                        <select class="form-select <?php echo isset($errors['assigned_to']) ? 'is-invalid' : ''; ?>" 
                                id="assigned_to" name="assigned_to" required>
                            <option value="">Select Installer</option>
                            <?php foreach ($installers as $inst): ?>
                            <option value="<?php echo $inst['id']; ?>" <?php echo ($_POST['assigned_to'] ?? '') == $inst['id'] ? 'selected' : ''; ?>>
                                <?php echo clean($inst['full_name']); ?> (<?php echo $inst['employee_id']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['assigned_to'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['assigned_to']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="area_id" class="form-label">Installation Area <span class="text-danger">*</span></label>
                        <select class="form-select <?php echo isset($errors['area_id']) ? 'is-invalid' : ''; ?>" 
                                id="area_id" name="area_id" required>
                            <option value="">Select Area</option>
                            <?php foreach ($areas as $area): ?>
                            <option value="<?php echo $area['id']; ?>" <?php echo ($_POST['area_id'] ?? '') == $area['id'] ? 'selected' : ''; ?>>
                                <?php echo clean($area['area_name']); ?> - <?php echo clean($area['city']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['area_id'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['area_id']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="assignment_date" class="form-label">Assignment Date</label>
                            <input type="date" class="form-control" id="assignment_date" name="assignment_date" 
                                   value="<?php echo $_POST['assignment_date'] ?? date('Y-m-d'); ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" 
                                   value="<?php echo $_POST['due_date'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="low" <?php echo ($_POST['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="normal" <?php echo ($_POST['priority'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="high" <?php echo ($_POST['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="urgent" <?php echo ($_POST['priority'] ?? '') === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo clean($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Item Selection -->
        <div class="col-lg-7 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box-seam me-2"></i>Select Items</span>
                    <input type="text" class="form-control form-control-sm w-auto" id="itemSearch" placeholder="Search items...">
                </div>
                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
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
                            <tr class="item-row" data-name="<?php echo strtolower($item['item_name']); ?>">
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
                    <div class="d-flex justify-content-between align-items-center">
                        <span id="selectedCount">0 items selected</span>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Create Assignment
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
