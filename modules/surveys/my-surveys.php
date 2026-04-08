<?php
/**
 * My Surveys - List available surveys for current user + survey history
 */
$pageTitle = 'Available Surveys';
$breadcrumbs = [['title' => 'Surveys']];

require_once '../../includes/header.php';

$db = Database::getInstance();
$currentRole = $auth->role();
$userId = $auth->userId();
$today = date('Y-m-d');

// Get active surveys available to this user
$sql = "SELECT s.*,
               CONCAT(u.first_name, ' ', u.last_name) AS created_by_name,
               (SELECT COUNT(*) 
                FROM survey_questions 
                WHERE survey_id = s.id) AS question_count,
               (SELECT COUNT(*) 
                FROM survey_responses 
                WHERE survey_id = s.id 
                  AND respondent_id = ? 
                  AND status = 'completed') AS my_responses,
               (SELECT COUNT(*) 
                FROM survey_responses 
                WHERE survey_id = s.id 
                  AND status = 'completed') AS response_count,
               (SELECT id
                FROM survey_responses
                WHERE survey_id = s.id
                  AND respondent_id = ?
                  AND status = 'completed'
                ORDER BY submitted_at DESC
                LIMIT 1) AS latest_response_id,
               (SELECT response_code
                FROM survey_responses
                WHERE survey_id = s.id
                  AND respondent_id = ?
                  AND status = 'completed'
                ORDER BY submitted_at DESC
                LIMIT 1) AS latest_response_code,
               (SELECT submitted_at
                FROM survey_responses
                WHERE survey_id = s.id
                  AND respondent_id = ?
                  AND status = 'completed'
                ORDER BY submitted_at DESC
                LIMIT 1) AS latest_submitted_at
        FROM surveys s
        JOIN users u ON s.created_by = u.id
        WHERE s.status = 'active'
          AND (s.start_date IS NULL OR s.start_date <= ?)
          AND (s.end_date IS NULL OR s.end_date >= ?)
          AND (
                s.target_roles IS NULL
                OR s.target_roles = ''
                OR s.target_roles = '[]'
                OR s.target_roles = 'null'
                OR s.target_roles LIKE ?
          )
        ORDER BY s.created_at DESC";

$surveys = $db->fetchAll($sql, [
    $userId,
    $userId,
    $userId,
    $userId,
    $today,
    $today,
    '%"' . $currentRole . '"%'
]);

$availableSurveys = [];
$completedSurveys = [];

foreach ($surveys as $survey) {
    if ($survey['my_responses'] > 0 && !$survey['allow_multiple']) {
        $completedSurveys[] = $survey;
    } else {
        $availableSurveys[] = $survey;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-clipboard2-data me-2"></i>Available Surveys
    </h1>
</div>

<!-- Available Surveys -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-clipboard2 me-2"></i>Surveys to Complete
                </span>
                <?php if (!empty($availableSurveys)): ?>
                    <span class="badge bg-primary"><?php echo count($availableSurveys); ?></span>
                <?php endif; ?>
            </div>

        <div class="card-body">
            <?php if (empty($availableSurveys)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-check-circle display-4 d-block mb-3"></i>
                    <p class="mb-0">No surveys available at the moment</p>
                </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($availableSurveys as $survey): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 border">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo clean($survey['title']); ?></h5>

                                <?php if (!empty($survey['description'])): ?>
                                    <p class="card-text text-muted small">
                                        <?php echo clean(truncate($survey['description'], 100)); ?>
                                    </p>
                                <?php endif; ?>

                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-question-circle me-1"></i>
                                        <?php echo (int)$survey['question_count']; ?> questions
                                    </span>

                                    <?php if (!empty($survey['is_anonymous'])): ?>
                                        <span class="badge bg-info">
                                            <i class="bi bi-incognito me-1"></i>Anonymous
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($survey['allow_multiple'])): ?>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-arrow-repeat me-1"></i>Multiple allowed
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($survey['end_date'])): ?>
                                    <small class="text-muted d-block mb-2">
                                        <i class="bi bi-calendar me-1"></i>
                                        Ends: <?php echo formatDate($survey['end_date']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>

                            <div class="card-footer bg-transparent">
                                <a href="answer.php?id=<?php echo (int)$survey['id']; ?>" class="btn btn-primary w-100">
                                    <i class="bi bi-pencil-square me-1"></i>Take Survey
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

            <!-- Survey History -->
            <?php if (!empty($completedSurveys)): ?>
            <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-clock-history me-2"></i>Survey History
                </span>
                <span class="badge bg-success"><?php echo count($completedSurveys); ?></span>
            </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                            <tr>
                <th style="width:15%">Survey</th>
                <th style="width:15%">Questions</th>
                <th style="width:15%">My Submission</th>
                <th style="width:15%">Status</th>
                <th class="text-center" style="width:15%">Action</th>
            </tr>
                </thead>
                <tbody>
                    <?php foreach ($completedSurveys as $survey): ?>
                        <tr>
                            <td>
                                <strong><?php echo clean($survey['title']); ?></strong>
                                <?php if (!empty($survey['description'])): ?>
                                    <div class="text-muted small">
                                        <?php echo clean(truncate($survey['description'], 60)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge bg-light text-dark">
                                    <?php echo (int)$survey['question_count']; ?>
                                </span>
                            </td>

                            <td>
                                <?php echo !empty($survey['latest_submitted_at']) ? formatDateTime($survey['latest_submitted_at']) : '-'; ?>
                            </td>

                            <td>
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>Completed
                                </span>
                            </td>

                            <td class="text-center">

                        <?php if (!empty($survey['latest_response_id'])): ?>

                        <div class="droplist">
                            <button type="button"
                                    class="btn btn-sm btn-link text-dark p-0 border-0"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>

                            <ul class="dropdown-menu dropdown-menu-end">

                                <li>
                                    <button type="button"
                                            class="dropdown-item btn-view-response"
                                            data-id="<?php echo (int)$survey['latest_response_id']; ?>"
                                            data-code="<?php echo clean($survey['latest_response_code']); ?>">
                                        <i class="bi bi-eye me-2"></i>View Response
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <?php else: ?>
                        <span class="text-muted">No record</span>
                        <?php endif; ?>

                        </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Response Detail Modal -->
<div class="modal fade" id="responseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">My Survey Response</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="responseModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = <<<'SCRIPT'
<script>
document.querySelectorAll('.btn-view-response').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const code = this.dataset.code;

        const modal = new bootstrap.Modal(document.getElementById('responseModal'));
        const body = document.getElementById('responseModalBody');

        body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
        modal.show();

        App.ajax('../../ajax/survey-action.php', {
            action: 'view_response',
            response_id: id
        }).then(response => {
            if (response.success) {
                let html = '<div class="mb-3"><strong>Response Code:</strong> ' + code + '</div>';

                response.data.forEach((item, index) => {
                    html += `
                        <div class="card mb-2">
                            <div class="card-body py-2">
                                <div class="text-muted small mb-1">Q${index + 1}: ${item.question}</div>
                                <div>${item.answer || '<span class="text-muted">No answer</span>'}</div>
                            </div>
                        </div>
                    `;
                });

                body.innerHTML = html;
            } else {
                body.innerHTML = '<div class="alert alert-danger">' + response.message + '</div>';
            }
        }).catch(() => {
            body.innerHTML = '<div class="alert alert-danger">Unable to load response details.</div>';
        });
    });
});
</script>
SCRIPT;

require_once '../../includes/footer.php';
?>