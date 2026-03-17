<?php
/**
 * Installation Areas Management
 */

// Handle AJAX requests BEFORE including header (which outputs HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../classes/Database.php';
    require_once __DIR__ . '/../../classes/Auth.php';
    require_once __DIR__ . '/../../includes/helpers.php';

    $auth = Auth::getInstance();
    $auth->requireAuth();
    $auth->requireRole(['super_admin', 'user_1']);

    $db = Database::getInstance();
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                $data = [
                    'area_code' => clean($_POST['area_code'] ?? ''),
                    'area_name' => clean($_POST['area_name'] ?? ''),
                    'address' => clean($_POST['address'] ?? ''),
                    'city' => clean($_POST['city'] ?? ''),
                    'province' => clean($_POST['province'] ?? ''),
                    'region' => clean($_POST['region'] ?? ''),
                    'latitude' => floatval($_POST['latitude'] ?? 0) ?: null,
                    'longitude' => floatval($_POST['longitude'] ?? 0) ?: null,
                    'status' => 'active'
                ];
                
                if (empty($data['area_code']) || empty($data['area_name'])) {
                    throw new Exception('Area code and name are required');
                }
                
                if ($db->exists('installation_areas', 'area_code = ?', [$data['area_code']])) {
                    throw new Exception('Area code already exists');
                }
                
                $id = $db->insert('installation_areas', $data);
                echo json_encode(['success' => true, 'message' => 'Area added', 'id' => $id]);
                break;
                
            case 'edit':
                $id = intval($_POST['id'] ?? 0);
                $data = [
                    'area_code' => clean($_POST['area_code'] ?? ''),
                    'area_name' => clean($_POST['area_name'] ?? ''),
                    'address' => clean($_POST['address'] ?? ''),
                    'city' => clean($_POST['city'] ?? ''),
                    'province' => clean($_POST['province'] ?? ''),
                    'region' => clean($_POST['region'] ?? ''),
                    'latitude' => floatval($_POST['latitude'] ?? 0) ?: null,
                    'longitude' => floatval($_POST['longitude'] ?? 0) ?: null
                ];
                
                if ($db->exists('installation_areas', 'area_code = ? AND id != ?', [$data['area_code'], $id])) {
                    throw new Exception('Area code already exists');
                }
                
                $db->update('installation_areas', $data, 'id = ?', [$id]);
                echo json_encode(['success' => true, 'message' => 'Area updated']);
                break;
                
            case 'toggle':
                $id = intval($_POST['id'] ?? 0);
                $area = $db->fetch("SELECT status FROM installation_areas WHERE id = ?", [$id]);
                $newStatus = $area['status'] === 'active' ? 'inactive' : 'active';
                $db->update('installation_areas', ['status' => $newStatus], 'id = ?', [$id]);
                echo json_encode(['success' => true, 'message' => 'Status updated']);
                break;
                
            case 'delete':
                $id = intval($_POST['id'] ?? 0);
                if ($db->exists('assignments', 'area_id = ?', [$id])) {
                    throw new Exception('Cannot delete area with assignments');
                }
                $db->delete('installation_areas', 'id = ?', [$id]);
                echo json_encode(['success' => true, 'message' => 'Area deleted']);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Normal page load - include header (outputs HTML)
$pageTitle = 'Installation Areas';
$breadcrumbs = [['title' => 'Areas']];

require_once '../../includes/header.php';

$auth->requireRole(['super_admin', 'user_1']);

$db = Database::getInstance();

// Get areas
$page = max(1, intval($_GET['page'] ?? 1));
$search = $_GET['search'] ?? '';

$where = '1=1';
$params = [];

if ($search) {
    $where = '(area_code LIKE ? OR area_name LIKE ? OR city LIKE ?)';
    $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
}

$sql = "SELECT *, 
        (SELECT COUNT(*) FROM assignments WHERE area_id = installation_areas.id) as assignment_count
        FROM installation_areas 
        WHERE {$where} 
        ORDER BY area_name";

$result = $db->paginate($sql, $params, $page, ITEMS_PER_PAGE);
$areas = $result['data'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-geo-alt me-2"></i>Installation Areas
    </h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#areaModal">
        <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">Add Area</span>
    </button>
</div>

<!-- Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <input type="text" class="form-control" name="search" placeholder="Search areas..." 
                       value="<?php echo clean($search); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Search
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Areas Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Area Name</th>
                        <th>City</th>
                        <th>Province</th>
                        <th class="text-center">Assignments</th>
                        <th>GPS</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($areas)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-geo-alt display-6 d-block mb-2"></i>
                                No areas found
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($areas as $area): ?>
                    <tr id="row-<?php echo $area['id']; ?>">
                        <td><strong><?php echo clean($area['area_code']); ?></strong></td>
                        <td><?php echo clean($area['area_name']); ?></td>
                        <td><?php echo clean($area['city']); ?></td>
                        <td><?php echo clean($area['province']); ?></td>
                        <td class="text-center">
                            <span class="badge bg-info"><?php echo $area['assignment_count']; ?></span>
                        </td>
                        <td>
                            <?php if ($area['latitude'] && $area['longitude']): ?>
                            <small class="text-success">
                                <i class="bi bi-geo-alt"></i>
                                <?php echo number_format($area['latitude'], 4); ?>,
                                <?php echo number_format($area['longitude'], 4); ?>
                            </small>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo statusBadge($area['status']); ?></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <div class="action-btn">
                                    <a href="#" class="menu-toggle" style="font-size:1.5rem;text-decoration:none;color:#000;">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </a>
                                        <div class="action-dropdown">
                                            <div class="edit-container">
                                                <a href="#" class="btn-edit"
                                                    data-id="<?php echo $area['id']; ?>"
                                                    data-code="<?php echo clean($area['area_code']); ?>"
                                                    data-name="<?php echo clean($area['area_name']); ?>"
                                                    data-address="<?php echo clean($area['address']); ?>"
                                                    data-city="<?php echo clean($area['city']); ?>"
                                                    data-province="<?php echo clean($area['province']); ?>"
                                                    data-region="<?php echo clean($area['region']); ?>"
                                                    data-lat="<?php echo $area['latitude']; ?>"
                                                    data-lng="<?php echo $area['longitude']; ?>">
                                                    <div class="block-container">
                                                        <div class="edit icon">
                                                            <i class="bi bi-key"></i>
                                                        </div>
                                                        <div class="edit text">
                                                            Edit
                                                        </div>                                                 
                                                    </div>
                                                </a>
                                            </div>
                                            <div class="activiation">
                                                <a href="#" class="btn-status btn-toggle"
                                                    data-id="<?php echo $area['id']; ?>"
                                                    data-action="<?php echo $area['status'] === 'active' ? 'suspend' : 'activate'; ?>"
                                                    title="<?php echo $area['status'] === 'active' ? 'Suspend' : 'Activate'; ?>">

                                                    <div class="block-container">
                                                        <div class="activation icon">
                                                            <i class="bi bi-<?php echo $area['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                        </div>
                                                        <div class="activation text">
                                                            <?php echo $area['status'] === 'active' ? 'Suspend' : 'Activate'; ?>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                            <div class="deletion">
                                                <a href="#" class="btn-delete"
                                                    data-id="<?php echo $area['id']; ?>"
                                                    data-name="<?php echo clean($area['area_name']); ?>">
                                                    <div class="block-container">
                                                        <div class="deletion icon">
                                                            <i class="bi bi-trash"></i>
                                                        </div>
                                                        <div class="deletion text">
                                                            Delete
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
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
        <?php echo paginationHtml($result['current_page'], $result['total_pages'], 'index.php'); ?>
    </div>
    <?php endif; ?>
</div>

<!-- Area Modal -->
<div class="modal fade" id="areaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Area</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="areaForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="areaId" value="">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="area_code" class="form-label">Area Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="area_code" name="area_code" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="area_name" class="form-label">Area Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="area_name" name="area_name" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="province" class="form-label">Province</label>
                            <input type="text" class="form-control" id="province" name="province">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="region" class="form-label">Region</label>
                            <input type="text" class="form-control" id="region" name="region">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="number" class="form-control" id="latitude" name="latitude" step="0.00000001">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="number" class="form-control" id="longitude" name="longitude" step="0.00000001">
                        </div>
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

document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('areaModal'));
    const form = document.getElementById('areaForm');
    
    // Reset form for Add
    document.querySelector('[data-bs-target="#areaModal"]').addEventListener('click', function() {
        document.getElementById('modalTitle').textContent = 'Add Area';
        document.getElementById('formAction').value = 'add';
        document.getElementById('areaId').value = '';
        form.reset();
    });
    
    // Edit buttons
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modalTitle').textContent = 'Edit Area';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('areaId').value = this.dataset.id;
            document.getElementById('area_code').value = this.dataset.code;
            document.getElementById('area_name').value = this.dataset.name;
            document.getElementById('address').value = this.dataset.address;
            document.getElementById('city').value = this.dataset.city;
            document.getElementById('province').value = this.dataset.province;
            document.getElementById('region').value = this.dataset.region;
            document.getElementById('latitude').value = this.dataset.lat;
            document.getElementById('longitude').value = this.dataset.lng;
            modal.show();
        });
    });
    
    // Form submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        App.showLoading();
        App.ajax('index.php', Object.fromEntries(new FormData(form)))
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
    
    // Toggle status
    document.querySelectorAll('.btn-toggle').forEach(btn => {
        btn.addEventListener('click', function() {
            App.ajax('index.php', { action: 'toggle', id: this.dataset.id })
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
            if (confirm('Delete area "' + this.dataset.name + '"?')) {
                App.ajax('index.php', { action: 'delete', id: this.dataset.id })
                    .then(response => {
                        if (response.success) {
                            App.toast(response.message, 'success');
                            document.getElementById('row-' + this.dataset.id).remove();
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
