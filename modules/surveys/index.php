<?php
/**
 * Survey Management - Index
 * Only accessible by user_1 (Manager) and super_admin
 */
$pageTitle = 'Surveys';
$breadcrumbs = [['title' => 'Surveys']];

require_once '../../includes/header.php';

// Only manager and super_admin can access survey management
$auth->requirePermission('surveys');

$db = Database::getInstance();
$currentRole = $auth->role();
$userId = $auth->userId();

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));

// Build query
$where = ['1=1'];
$params = [];

// If user_1, only show their own surveys
if ($currentRole === 'user_1') {
    $where[] = 's.created_by = ?';
    $params[] = $userId;
}

if ($statusFilter) {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}

if ($search) {
    $where[] = '(title LIKE ? OR survey_code LIKE ? OR description LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

$sql = "SELECT s.*, u.full_name as created_by_name,
        (SELECT COUNT(*) FROM survey_questions WHERE survey_id = s.id) as question_count,
        (SELECT COUNT(*) FROM survey_responses WHERE survey_id = s.id AND status = 'completed') as response_count
        FROM surveys s
        JOIN users u ON s.created_by = u.id
        WHERE {$whereClause}
        ORDER BY s.created_at DESC";

$result = $db->paginate($sql, $params, $page, ITEMS_PER_PAGE);
$surveys = $result['data'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-clipboard2-data me-2"></i>Survey Management
    </h1>
    <a href="create.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">Create Survey</span>
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="Search surveys..." 
                       value="<?php echo clean($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>
            <div class="col-md-5">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Surveys Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Questions</th>
                        <th>Responses</th>
                        <th>Date Range</th>
                        <th>Created</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($surveys)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-clipboard2-data display-6 d-block mb-2"></i>
                                No surveys found
                                <div class="mt-2">
                                    <a href="create.php" class="btn btn-primary btn-sm">Create your first survey</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($surveys as $survey): ?>
                    <tr>
                        <td><strong><?php echo clean($survey['survey_code']); ?></strong></td>
                        <td>
                            <div><?php echo clean($survey['title']); ?></div>
                            <?php if ($survey['description']): ?>
                            <small class="text-muted"><?php echo clean(truncate($survey['description'], 50)); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $statusColors = [
                                'draft' => 'secondary',
                                'active' => 'success',
                                'closed' => 'danger'
                            ];
                            $statusColor = $statusColors[$survey['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $statusColor; ?>">
                                <?php echo ucfirst($survey['status']); ?>
                            </span>
                            <?php if ($survey['is_anonymous']): ?>
                            <span class="badge bg-info" title="Anonymous"><i class="bi bi-incognito"></i></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-primary"><?php echo $survey['question_count']; ?></span>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo $survey['response_count']; ?></span>
                        </td>
                        <td>
                            <?php if ($survey['start_date'] && $survey['end_date']): ?>
                            <small>
                                <?php echo formatDate($survey['start_date']); ?> - 
                                <?php echo formatDate($survey['end_date']); ?>
                            </small>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?php echo formatDateTime($survey['created_at']); ?></small>
                            <div class="text-muted small"><?php echo clean($survey['created_by_name']); ?></div>
                        </td>
                        <td class="text-center">
                            <div class="action-btn">
                                <a href="#" class="menu-toggle" style="font-size:1.5rem;text-decoration:none;color:#000;">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </a>
                                <div class="action-dropdown">
                                    <?php if ($survey['response_count'] > 0): ?>
                                    <div class="edit-container">
                                        <a href="results.php?id=<?php echo $survey['id']; ?>" title="View Results">
                                            <div class="block-container">
                                                <div class="results icon">
                                                    <i class="bi bi-graph-up"></i>
                                                </div>
                                                <div class="results text">
                                                    ViewResult
                                                </div>                                                 
                                            </div>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($survey['status'] !== 'closed'): ?>
                                    <div class="edit">
                                         <a href="edit.php?id=<?php echo $survey['id']; ?>" class="btn-edit" title="Edit">
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
                                    <?php endif; ?>
                                    <?php if ($survey['status'] === 'draft'): ?>
                                        <div class="publish">
                                            <a href="#"
                                                data-id="<?php echo $survey['id']; ?>"
                                                title="Publish">
                                            <div class="block-container">
                                                <div class="publish icon">
                                                    <i class="bi bi-pencil"></i>
                                                </div>
                                                <div class="publish text">
                                                    Publish
                                                </div>
                                            </div>   
                                            </a>
                                         
                                        </div>
                                        <?php elseif($survey['status'] === 'active'): ?>
                                            <div class="close">
                                                <a href="#" data-id="<?php echo $survey['id']; ?>" title="Publish">
                                                    <div class="block-container">
                                                        <div class="close icon">
                                                            <i class="bi bi-x-circle"></i>
                                                        </div>
                                                        <div class="close text">
                                                            Close Survey
                                                        </div>
                                                    </div>   
                                                </a>             
                                            </div>
                                    <?php endif; ?>
                                    <?php if ($survey['response_count'] == 0): ?>                                
                                    <div class="delete">
                                        <a href="#" class="btn-delete" 
                                            data-id="<?php echo $survey['id']; ?>"
                                            data-name="<?php echo clean($survey['title']); ?>"
                                            title="Close Survey">
                                            
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
    
    <?php if ($result['total_pages'] > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing <?php echo (($page - 1) * ITEMS_PER_PAGE) + 1; ?> - <?php echo min($page * ITEMS_PER_PAGE, $result['total']); ?> 
                of <?php echo $result['total']; ?> surveys
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
// Publish Survey
document.querySelectorAll('.btn-publish').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        if (confirm('Are you sure you want to publish this survey? It will become available to respondents.')) {
            App.ajax('../../ajax/survey-action.php', { id: id, action: 'publish' })
                .then(response => {
                    if (response.success) {
                        App.toast(response.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        App.toast(response.message, 'danger');
                    }
                });
        }
    });
});

// Close Survey
document.querySelectorAll('.btn-close-survey').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        if (confirm('Are you sure you want to close this survey? No more responses will be accepted.')) {
            App.ajax('../../ajax/survey-action.php', { id: id, action: 'close' })
                .then(response => {
                    if (response.success) {
                        App.toast(response.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        App.toast(response.message, 'danger');
                    }
                });
        }
    });
});

// Delete Survey
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        if (confirm('Are you sure you want to delete this survey? This action cannot be undone.')) {
            App.ajax('../../ajax/survey-action.php', { id: id, action: 'delete' })
                .then(response => {
                    if (response.success) {
                        App.toast(response.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        App.toast(response.message, 'danger');
                    }
                });
        }
    });
});
</script>
SCRIPT;

require_once '../../includes/footer.php'; 
?>