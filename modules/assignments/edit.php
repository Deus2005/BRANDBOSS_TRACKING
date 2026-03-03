<?php
/**
 * Edit Assignment
 */
$pageTitle = 'Edit Assignment';
$breadcrumbs = [
    ['title' => 'Assignments', 'url' => 'index.php'],
    ['title' => 'Edit Assignment']
];

require_once '../../includes/header.php';

$auth->requireRole(['super_admin', 'user_1']);

$db = Database::getInstance();
$errors = [];

// Get assignment ID
$assignmentId = intval($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$assignmentId) {
    redirect('index.php', 'Invalid assignment', 'danger');
}

// Get assignment details
$assignment = $db->fetch("SELECT * FROM assignments WHERE id = ?", [$assignmentId]);

if (!$assignment) {
    redirect('index.php', 'Assignment not found', 'danger');
}

// Only pending assignments can be edited
if ($assignment['status'] !== 'pending') {
    redirect('view.php?id=' . $assignmentId, 'Only pending assignments can be edited', 'warning');
}

// Get current items
$currentItems = $db->fetchAll(
    "SELECT ai.*, i.item_code, i.item_name, i.unit, i.quantity_available
     FROM assignment_items ai
     JOIN inventory_items i ON ai.item_id = i.id
     WHERE ai.assignment_id = ?",
    [$assignmentId]
);

// Get installers (User 2)
$installers = $db->fetchAll("SELECT id, employee_id, full_name FROM users WHERE role = 'user_2' AND status = 'active' ORDER BY full_name");

// Get areas
$areas = $db->fetchAll("SELECT * FROM installation_areas WHERE status = 'active' ORDER BY area_name");

// Get available inventory items (include items already in this assignment)
$currentItemIds = array_column($currentItems, 'item_id');
$items = $db->fetchAll(
    "SELECT i.*, c.category_name 
     FROM inventory_items i 
     JOIN item_categories c ON i.category_id = c.id 
     WHERE i.status = 'active' AND (i.quantity_available > 0 OR i.id IN (" . 
     (empty($currentItemIds) ? '0' : implode(',', $currentItemIds)) . "))
     ORDER BY c.category_name, i.item_name"
);

// Create lookup for current items
$currentItemLookup = [];
foreach ($currentItems as $ci) {
    $currentItemLookup[$ci['item_id']] = $ci;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'assigned_to' => intval($_POST['assigned_to'] ?? 0),
        'area_id' => intval($_POST['area_id'] ?? 0),
        'due_date' => $_POST['due_date'] ?? null,
        'priority' => $_POST['priority'] ?? 'normal',
        'notes' => clean($_POST['notes'] ?? '')
    ];
    
    $selectedItems = $_POST['items'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    
    // Validation
    $errors = validateRequired($data, ['assigned_to', 'area_id']);
    
    if (empty($selectedItems)) {
        $errors['items'] = 'Please select at least one item';
    }
    
    // Validate quantities
    foreach ($selectedItems as $itemId) {
        $qty = intval($quantities[$itemId] ?? 0);
        if ($qty <= 0) {
            $errors['items'] = 'All selected items must have a quantity greater than 0';
            break;
        }
        
        // Check available stock (considering what's already reserved for this assignment)
        $item = $db->fetch("SELECT quantity_available FROM inventory_items WHERE id = ?", [$itemId]);
        $currentlyReserved = isset($currentItemLookup[$itemId]) ? $currentItemLookup[$itemId]['quantity_assigned'] : 0;
        $availableForAssignment = $item['quantity_available'] + $currentlyReserved;
        
        if ($qty > $availableForAssignment) {
            $errors['items'] = 'Insufficient stock for one or more items';
            break;
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Update assignment
            $db->update('assignments', $data, 'id = ?', [$assignmentId]);
            
            // Process item changes
            $processedItems = [];
            
            foreach ($selectedItems as $itemId) {
                $newQty = intval($quantities[$itemId]);
                $processedItems[] = $itemId;
                
                if (isset($currentItemLookup[$itemId])) {
                    // Update existing item
                    $oldQty = $currentItemLookup[$itemId]['quantity_assigned'];
                    $diff = $newQty - $oldQty;
                    
                    if ($diff != 0) {
                        // Update assignment item
                        $db->update('assignment_items', 
                            ['quantity_assigned' => $newQty], 
                            'id = ?', 
                            [$currentItemLookup[$itemId]['id']]
                        );
                        
                        // Update inventory
                        if ($diff > 0) {
                            // Need more - reserve more
                            $db->query(
                                "UPDATE inventory_items SET quantity_available = quantity_available - ?, quantity_reserved = quantity_reserved + ? WHERE id = ?",
                                [$diff, $diff, $itemId]
                            );
                        } else {
                            // Need less - release some
                            $db->query(
                                "UPDATE inventory_items SET quantity_available = quantity_available + ?, quantity_reserved = quantity_reserved - ? WHERE id = ?",
                                [abs($diff), abs($diff), $itemId]
                            );
                        }
                        
                        // Log transaction
                        $db->insert('inventory_transactions', [
                            'item_id' => $itemId,
                            'transaction_type' => $diff > 0 ? 'reserved' : 'released',
                            'quantity' => abs($diff),
                            'reference_type' => 'assignment',
                            'reference_id' => $assignmentId,
                            'notes' => "Assignment {$assignment['assignment_code']} updated",
                            'created_by' => $auth->userId()
                        ]);
                    }
                } else {
                    // New item
                    $db->insert('assignment_items', [
                        'assignment_id' => $assignmentId,
                        'item_id' => $itemId,
                        'quantity_assigned' => $newQty,
                        'status' => 'pending'
                    ]);
                    
                    // Reserve stock
                    $db->query(
                        "UPDATE inventory_items SET quantity_available = quantity_available - ?, quantity_reserved = quantity_reserved + ? WHERE id = ?",
                        [$newQty, $newQty, $itemId]
                    );
                    
                    // Log transaction
                    $db->insert('inventory_transactions', [
                        'item_id' => $itemId,
                        'transaction_type' => 'reserved',
                        'quantity' => $newQty,
                        'reference_type' => 'assignment',
                        'reference_id' => $assignmentId,
                        'notes' => "Added to assignment {$assignment['assignment_code']}",
                        'created_by' => $auth->userId()
                    ]);
                }
            }
            
            // Remove items that are no longer selected
            foreach ($currentItems as $ci) {
                if (!in_array($ci['item_id'], $processedItems)) {
                    // Release reserved stock
                    $db->query(
                        "UPDATE inventory_items SET quantity_available = quantity_available + ?, quantity_reserved = quantity_reserved - ? WHERE id = ?",
                        [$ci['quantity_assigned'], $ci['quantity_assigned'], $ci['item_id']]
                    );
                    
                    // Delete assignment item
                    $db->delete('assignment_items', 'id = ?', [$ci['id']]);
                    
                    // Log transaction
                    $db->insert('inventory_transactions', [
                        'item_id' => $ci['item_id'],
                        'transaction_type' => 'released',
                        'quantity' => $ci['quantity_assigned'],
                        'reference_type' => 'assignment',
                        'reference_id' => $assignmentId,
                        'notes' => "Removed from assignment {$assignment['assignment_code']}",
                        'created_by' => $auth->userId()
                    ]);
                }
            }
            
            // Log activity
            $auth->logActivity($auth->userId(), 'updated_assignment', 'assignments', 'assignments', $assignmentId);
            
            $db->commit();
            redirect('view.php?id=' . $assignmentId, 'Assignment updated successfully!', 'success');
            
        } catch (Exception $e) {
            $db->rollback();
            $errors['general'] = 'Failed to update assignment: ' . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-pencil me-2"></i>Edit Assignment
    </h1>
    <a href="view.php?id=<?php echo $assignmentId; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger"><?php echo $errors['general']; ?></div>
<?php endif; ?>

<form method="POST" action="" id="assignmentForm">
    <input type="hidden" name="id" value="<?php echo $assignmentId; ?>">
    
    <div class="row">
        <!-- Assignment Details -->
        <div class="col-lg-5 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary">
                    <i class="bi bi-info-circle me-2"></i>Assignment Details
                    <span class="badge bg-light text-dark ms-auto"><?php echo clean($assignment['assignment_code']); ?></span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="assigned_to" class="form-label">Assign To <span class="text-danger">*</span></label>
                        <select class="form-select <?php echo isset($errors['assigned_to']) ? 'is-invalid' : ''; ?>" 
                                id="assigned_to" name="assigned_to" required>
                            <option value="">Select Installer</option>
                            <?php foreach ($installers as $inst): ?>
                            <option value="<?php echo $inst['id']; ?>" 
                                    <?php echo ($_POST['assigned_to'] ?? $assignment['assigned_to']) == $inst['id'] ? 'selected' : ''; ?>>
                                <?php echo clean($inst['full_name']); ?> (<?php echo $inst['employee_id']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="area_id" class="form-label">Installation Area <span class="text-danger">*</span></label>
                        <select class="form-select <?php echo isset($errors['area_id']) ? 'is-invalid' : ''; ?>" 
                                id="area_id" name="area_id" required>
                            <option value="">Select Area</option>
                            <?php foreach ($areas as $area): ?>
                            <option value="<?php echo $area['id']; ?>" 
                                    <?php echo ($_POST['area_id'] ?? $assignment['area_id']) == $area['id'] ? 'selected' : ''; ?>>
                                <?php echo clean($area['area_name']); ?> - <?php echo clean($area['city']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date" 
                               value="<?php echo $_POST['due_date'] ?? $assignment['due_date']; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <?php 
                            $priorities = ['low', 'normal', 'high', 'urgent'];
                            $currentPriority = $_POST['priority'] ?? $assignment['priority'];
                            foreach ($priorities as $p): 
                            ?>
                            <option value="<?php echo $p; ?>" <?php echo $currentPriority === $p ? 'selected' : ''; ?>>
                                <?php echo ucfirst($p); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo clean($_POST['notes'] ?? $assignment['notes']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Item Selection -->
        <div class="col-lg-7 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box-seam me-2"></i>Items</span>
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
                            <?php 
                            $currentCategory = '';
                            foreach ($items as $item): 
                                // Calculate available (add back what's currently reserved for this assignment)
                                $currentlyReserved = isset($currentItemLookup[$item['id']]) ? $currentItemLookup[$item['id']]['quantity_assigned'] : 0;
                                $availableForAssignment = $item['quantity_available'] + $currentlyReserved;
                                
                                $isSelected = isset($currentItemLookup[$item['id']]) || in_array($item['id'], $_POST['items'] ?? []);
                                $currentQty = $_POST['quantities'][$item['id']] ?? ($currentItemLookup[$item['id']]['quantity_assigned'] ?? 1);
                                
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
                                           <?php echo $isSelected ? 'checked' : ''; ?>
                                           <?php echo $availableForAssignment <= 0 && !$isSelected ? 'disabled' : ''; ?>>
                                </td>
                                <td>
                                    <strong><?php echo clean($item['item_code']); ?></strong>
                                    <br><small><?php echo clean($item['item_name']); ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo $availableForAssignment > 0 ? 'success' : 'secondary'; ?>">
                                        <?php echo number_format($availableForAssignment); ?>
                                    </span>
                                    <br><small class="text-muted"><?php echo $item['unit']; ?></small>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm quantity-input" 
                                           name="quantities[<?php echo $item['id']; ?>]" 
                                           value="<?php echo $currentQty; ?>"
                                           min="1" max="<?php echo $availableForAssignment; ?>"
                                           <?php echo !$isSelected ? 'disabled' : ''; ?>>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <span id="selectedCount">0 items selected</span>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Save Changes
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
    });
    
    // Select all
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => {
            if (!cb.disabled) {
                cb.checked = this.checked;
                cb.dispatchEvent(new Event('change'));
            }
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