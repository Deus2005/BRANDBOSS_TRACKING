<?php
/**
 * My Surveys - List available surveys for current user
 */
$pageTitle = 'Available Surveys';
$breadcrumbs = [['title' => 'Surveys']];

require_once '../../includes/header.php';

$db = Database::getInstance();
$currentRole = $auth->role();
$userId = $auth->userId();

// Get available surveys for this user
$today = date('Y-m-d');

// Build query - get active surveys that target this user's role (or all users if no target)
// Using LIKE instead of JSON_CONTAINS for better compatibility
$sql = "SELECT s.*, u.full_name as created_by_name,
        (SELECT COUNT(*) FROM survey_questions WHERE survey_id = s.id) as question_count,
        (SELECT COUNT(*) FROM survey_responses WHERE survey_id = s.id AND respondent_id = ? AND status = 'completed') as my_responses
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

$surveys = $db->fetchAll($sql, [$userId, $today, $today, '%"' . $currentRole . '"%']);

// Separate into available and completed
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
    <div class="card-header">
        <i class="bi bi-clipboard2 me-2"></i>Surveys to Complete
        <?php if (!empty($availableSurveys)): ?>
        <span class="badge bg-primary ms-2"><?php echo count($availableSurveys); ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($availableSurveys)): ?>
        <div class="text-center py-4 text-muted">
            <i class="bi bi-check-circle display-4 d-block mb-3"></i>
            <p>No surveys available at the moment</p>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($availableSurveys as $survey): ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100 border">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo clean($survey['title']); ?></h5>
                        <?php if ($survey['description']): ?>
                        <p class="card-text text-muted small"><?php echo clean(truncate($survey['description'], 100)); ?></p>
                        <?php endif; ?>
                        
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-question-circle me-1"></i><?php echo $survey['question_count']; ?> questions
                            </span>
                            <?php if ($survey['is_anonymous']): ?>
                            <span class="badge bg-info">
                                <i class="bi bi-incognito me-1"></i>Anonymous
                            </span>
                            <?php endif; ?>
                            <?php if ($survey['allow_multiple']): ?>
                            <span class="badge bg-secondary">
                                <i class="bi bi-arrow-repeat me-1"></i>Multiple allowed
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($survey['end_date']): ?>
                        <small class="text-muted d-block mb-2">
                            <i class="bi bi-calendar me-1"></i>Ends: <?php echo formatDate($survey['end_date']); ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="answer.php?id=<?php echo $survey['id']; ?>" class="btn btn-primary w-100">
                            <i class="bi bi-pencil-square"></i> Take Survey
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Completed Surveys -->
<?php if (!empty($completedSurveys)): ?>
<div class="card">
    <div class="card-header">
        <i class="bi bi-check-circle me-2"></i>Completed Surveys
        <span class="badge bg-success ms-2"><?php echo count($completedSurveys); ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Survey</th>
                        <th>Questions</th>
                        <th>Completed</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completedSurveys as $survey): ?>
                    <tr>
                        <td>
                            <strong><?php echo clean($survey['title']); ?></strong>
                            <?php if ($survey['description']): ?>
                            <div class="text-muted small"><?php echo clean(truncate($survey['description'], 50)); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-light text-dark"><?php echo $survey['question_count']; ?></span></td>
                        <td>
                            <?php 
                            $lastResponse = $db->fetch(
                                "SELECT submitted_at FROM survey_responses WHERE survey_id = ? AND respondent_id = ? AND status = 'completed' ORDER BY submitted_at DESC LIMIT 1",
                                [$survey['id'], $userId]
                            );
                            echo $lastResponse ? formatDateTime($lastResponse['submitted_at']) : '-';
                            ?>
                        </td>
                        <td>
                            <span class="badge bg-success"><i class="bi bi-check"></i> Completed</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>