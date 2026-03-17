<?php
/**
 * Item Categories Management
 */
require_once '../../config/config.php';
require_once '../../classes/Database.php';
require_once '../../classes/Auth.php';
require_once '../../includes/helpers.php';

$auth = Auth::getInstance();
$db = Database::getInstance();

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Authentication check - handle AJAX differently
if (!$auth->isLoggedIn()) {
    if ($isAjax) {
        header('Content-Type: application/json');
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Please login to continue']);
        exit;
    }
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

// Permission check - handle AJAX differently
if (!$auth->can('inventory')) {
    if ($isAjax) {
        header('Content-Type: application/json');
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }
    header('Location: ' . APP_URL . '/403.php');
    exit;
}

// Handle form submissions via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAjax) {
    header('Content-Type: application/json');
    ob_clean(); // Clear any output buffer
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                $name = clean($_POST['category_name'] ?? '');
                $description = clean($_POST['description'] ?? '');
                
                if (empty($name)) {
                    throw new Exception('Category name is required');
                }
                
                if ($db->exists('item_categories', 'category_name = ?', [$name])) {
                    throw new Exception('Category already exists');
                }
                
                $id = $db->insert('item_categories', [
                    'category_name' => $name,
                    'description' => $description,
                    'status' => 'active'
                ]);
                
                echo json_encode(['success' => true, 'message' => 'Category added', 'id' => $id]);
                break;
                
            case 'edit':
                $id = intval($_POST['id'] ?? 0);
                $name = clean($_POST['category_name'] ?? '');
                $description = clean($_POST['description'] ?? '');
                
                if (empty($name)) {
                    throw new Exception('Category name is required');
                }
                
                if ($db->exists('item_categories', 'category_name = ? AND id != ?', [$name, $id])) {
                    throw new Exception('Category name already exists');
                }
                
                $db->update('item_categories', [
                    'category_name' => $name,
                    'description' => $description
                ], 'id = ?', [$id]);
                
                echo json_encode(['success' => true, 'message' => 'Category updated']);
                break;
                
            case 'toggle':
                $id = intval($_POST['id'] ?? 0);
                $category = $db->fetch("SELECT status FROM item_categories WHERE id = ?", [$id]);
                
                if (!$category) {
                    throw new Exception('Category not found');
                }
                
                $newStatus = $category['status'] === 'active' ? 'inactive' : 'active';
                $db->update('item_categories', ['status' => $newStatus], 'id = ?', [$id]);
                
                echo json_encode(['success' => true, 'message' => 'Status updated', 'status' => $newStatus]);
                break;
                
            case 'delete':
                $id = intval($_POST['id'] ?? 0);
                
                // Check if category has items
                if ($db->exists('inventory_items', 'category_id = ?', [$id])) {
                    throw new Exception('Cannot delete category with items');
                }
                
                $db->delete('item_categories', 'id = ?', [$id]);
                echo json_encode(['success' => true, 'message' => 'Category deleted']);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Set page variables and include header for normal page load
$pageTitle = 'Categories';
$breadcrumbs = [
    ['title' => 'Inventory', 'url' => 'index.php'],
    ['title' => 'Categories']
];

require_once '../../includes/header.php';

// Get all categories with item counts
$categories = $db->fetchAll(
    "SELECT c.*, 
            (SELECT COUNT(*) FROM inventory_items WHERE category_id = c.id) as item_count
     FROM item_categories c
     ORDER BY c.category_name"
);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-tags me-2"></i>Item Categories
    </h1>
    <div>
        <a href="index.php" class="btn btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
            <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">Add Category</span>
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="categoriesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th class="text-center">Items</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-tags display-6 d-block mb-2"></i>
                                No categories yet
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($categories as $i => $cat): ?>
                    <tr id="row-<?php echo $cat['id']; ?>">
                        <td><?php echo $i + 1; ?></td>
                        <td><strong><?php echo clean($cat['category_name']); ?></strong></td>
                        <td><?php echo clean($cat['description']) ?: '<span class="text-muted">-</span>'; ?></td>
                        <td class="text-center">
                            <span class="badge bg-info"><?php echo $cat['item_count']; ?></span>
                        </td>
                        <td>
                            <span class="status-badge"><?php echo statusBadge($cat['status']); ?></span>
                        </td>
                        <td class="text-center">
                            <div class="action-btn">
                                <a href="#" class="menu-toggle" style="font-size:1.5rem;text-decoration:none;color:#000;">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </a>
                                <div class="action-dropdown">
                                    <div class="edit-container">
                                        <a href="#" class="btn-edit" 
                                            data-id="<?php echo $cat['id']; ?>"
                                            data-name="<?php echo clean($cat['category_name']); ?>"
                                            data-description="<?php echo clean($cat['description']); ?>">
                                            <div class="block-container">
                                                <div class="edit icon">
                                                    <i class="bi bi-pencil"></i>
                                                </div>
                                                <div class="edit text">
                                                    Edit
                                                </div>                                                 
                                            </div>
                                        </a>
                                    </div>
                                    <div class="activiation">
                                        <a href="#" class="btn-toggle" 
                                            data-id="<?php echo $cat['id']; ?>"
                                            title="<?php echo $cat['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                            <div class="block-container">
                                                <div class="activation icon">
                                                    <i class="bi bi-<?php echo $cat['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                </div>
                                                <div class="activation text">
                                                    <?php echo $cat['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                </div>
                                            </div>
                                        </a>    
                                    </div>
                                        <?php if ($cat['item_count'] == 0): ?>                                    
                                    <div class="delete">
                                        <a href="#" class="btn-delete" 
                                            data-id="<?php echo $cat['id']; ?>"
                                            data-name="<?php echo clean($cat['category_name']); ?>">
                                            <div class="block-container">
                                                <div class="delete icon">
                                                    <i class="bi bi-trash"></i> 
                                                </div>
                                                <div class="delete text">
                                                    Delete
                                                </div>
                                            </div>
                                        </a>                                        
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="categoryForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="categoryId" value="">
                    
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../../assets/js/action.js"></script>

<script src="https://cdn.jsdelivr.net/npm/fitty"></script>

<script>
    fitty('modal', { minSize: 10, maxSize: 20 });
</script>
<?php 
$extraScripts = <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    const form = document.getElementById('categoryForm');
    
    // Add Category Button
    document.querySelector('[data-bs-target="#categoryModal"]').addEventListener('click', function() {
        document.getElementById('modalTitle').textContent = 'Add Category';
        document.getElementById('formAction').value = 'add';
        document.getElementById('categoryId').value = '';
        form.reset();
    });
    
    // Edit Buttons
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modalTitle').textContent = 'Edit Category';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('categoryId').value = this.dataset.id;
            document.getElementById('category_name').value = this.dataset.name;
            document.getElementById('description').value = this.dataset.description;
            modal.show();
        });
    });
    
    // Form Submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        App.showLoading();
        App.ajax('categories.php', Object.fromEntries(new FormData(form)))
            .then(response => {
                App.hideLoading();
                if (response.success) {
                    App.toast(response.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    App.toast(response.message, 'danger');
                }
            });
    });
    
    // Toggle Status
    document.querySelectorAll('.btn-toggle').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            
            App.ajax('categories.php', { action: 'toggle', id: id })
                .then(response => {
                    if (response.success) {
                        App.toast(response.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        App.toast(response.message, 'danger');
                    }
                });
        });
    });
    
    // Delete
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            if (confirm(`Are you sure you want to delete "${name}"?`)) {
                App.ajax('categories.php', { action: 'delete', id: id })
                    .then(response => {
                        if (response.success) {
                            App.toast(response.message, 'success');
                            document.getElementById('row-' + id).remove();
                        } else {
                            App.toast(response.message, 'danger');
                        }
                    });
            }
        });
    });
});
</script>
SCRIPT;

require_once '../../includes/footer.php'; 
?>