<?php
/**
 * Inventory Management - Item Listing
 */
$pageTitle = 'Inventory';
$breadcrumbs = [['title' => 'Inventory']];

require_once '../../includes/header.php';

$auth->requirePermission('inventory');

$db = Database::getInstance();

// Get filter parameters
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? 'active';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));

// Build query
$where = ['1=1'];
$params = [];

if ($category) {
    $where[] = 'i.category_id = ?';
    $params[] = $category;
}

if ($status) {
    $where[] = 'i.status = ?';
    $params[] = $status;
}

if ($search) {
    $where[] = '(i.item_code LIKE ? OR i.item_name LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

$sql = "SELECT i.*, c.category_name, u.full_name as created_by_name
        FROM inventory_items i
        LEFT JOIN item_categories c ON i.category_id = c.id
        LEFT JOIN users u ON i.created_by = u.id
        WHERE {$whereClause}
        ORDER BY i.item_name ASC";

$result = $db->paginate($sql, $params, $page, ITEMS_PER_PAGE);
$items = $result['data'];

// Get categories for filter
$categories = $db->fetchAll("SELECT * FROM item_categories WHERE status = 'active' ORDER BY category_name");

// Get summary stats
$totalItems = $db->count('inventory_items', "status = 'active'");
$totalStock = $db->fetchColumn("SELECT COALESCE(SUM(quantity_available), 0) FROM inventory_items WHERE status = 'active'");
$lowStock = $db->count('inventory_items', "status = 'active' AND quantity_available <= reorder_level");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-box-seam me-2"></i>Inventory Items
    </h1>
    <div>
        <a href="categories.php" class="btn btn-outline-primary me-2">
            <i class="bi bi-tags"></i> <span class="d-none d-md-inline">Categories</span>
        </a>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">Add Item</span>
        </a>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="card stats-card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3" style="width: 45px; height: 45px; font-size: 1.25rem;">
                        <i class="bi bi-box"></i>
                    </div>
                    <div>
                        <h4 class="mb-0"><?php echo number_format($totalItems); ?></h4>
                        <small class="text-muted">Total Items</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card stats-card" style="border-left-color: #198754;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3" style="width: 45px; height: 45px; font-size: 1.25rem; background: #d1e7dd; color: #198754;">
                        <i class="bi bi-stack"></i>
                    </div>
                    <div>
                        <h4 class="mb-0" style="color: #198754;"><?php echo number_format($totalStock); ?></h4>
                        <small class="text-muted">Total Stock</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card stats-card" style="border-left-color: #ffc107;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3" style="width: 45px; height: 45px; font-size: 1.25rem; background: #fff3cd; color: #ffc107;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h4 class="mb-0" style="color: #ffc107;"><?php echo number_format($lowStock); ?></h4>
                        <small class="text-muted">Low Stock</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" class="form-control" name="search" placeholder="Search items..." 
                       value="<?php echo clean($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo clean($cat['category_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-4">
                <!-- <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filter
                </button> -->
                <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-x-lg"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Items Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th class="text-center">Available</th>
                        <th class="text-center">Reserved</th>
                        <th class="text-center">Installed</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                No items found
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><strong><?php echo clean($item['item_code']); ?></strong></td>
                        <td>
                            <?php echo clean($item['item_name']); ?>
                            <?php if ($item['quantity_available'] <= $item['reorder_level']): ?>
                            <span class="badge bg-warning ms-1" title="Low Stock">
                                <i class="bi bi-exclamation-triangle"></i>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo clean($item['category_name']); ?></td>
                        <td class="text-center">
                            <span class="badge bg-success"><?php echo number_format($item['quantity_available']); ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark"><?php echo number_format($item['quantity_reserved']); ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info"><?php echo number_format($item['quantity_installed']); ?></span>
                        </td>
                        <td><?php echo statusBadge($item['status']); ?></td>
                        <td class="text-center">
                            <div class="action-btn">
                                <a href="#" class="menu-toggle" style="font-size:1.5rem;text-decoration:none;color:#000;">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </a>
                                <div class="action-dropdown">
                                    <div class="view-container">
                                        <a href="view.php?id=<?php echo $item['id']; ?>" class="btn-view" >
                                            <div class="block-container">
                                                <div class="view icon">
                                                    <i class="bi bi-eye"></i>
                                                </div>
                                                <div class="edit text">
                                                    View
                                                </div>                                                 
                                            </div>
                                        </a>
                                    </div>
                                    <div class="edit-container">
                                        <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn-edit">
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
                                        <?php if ($cat['item_count'] == 0): ?>                                    
                                    <div class="stock-container">
                                        <a href="stock.php?id=<?php echo $item['id']; ?>" class="btn-stock" class="btn-stock">
                                            <div class="block-container">
                                                <div class="stock icon">
                                                    <i class="bi bi-box-seam"></i>
                                                </div>
                                                <div class="stock text">
                                                    Stock
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
    
    <?php if ($result['total_pages'] > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing <?php echo (($page - 1) * ITEMS_PER_PAGE) + 1; ?> - <?php echo min($page * ITEMS_PER_PAGE, $result['total']); ?> 
                of <?php echo $result['total']; ?> items
            </small>
            <?php echo paginationHtml($result['current_page'], $result['total_pages'], 'index.php'); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="../../assets/js/action.js"></script>

<?php 
$extraScripts = <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('form');
    if (!form) return;

    var searchInput = form.querySelector('input[name="search"]');
    var filterSelects = form.querySelectorAll('select');

    // Longer debounce
    var autoSubmit = App.debounce(function() {
        form.submit();
    }, 800);

    if (searchInput) {
        searchInput.focus();

        // Move cursor to end safely
        setTimeout(function(){
            var valueLength = searchInput.value.length;
            searchInput.setSelectionRange(valueLength, valueLength);
        }, 0);

        searchInput.addEventListener('input', function() {
            autoSubmit();
        });
    }

    filterSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            form.submit(); // no need debounce for selects
        });
    });
});
</script>
SCRIPT;

require_once '../../includes/footer.php'; 
?>
