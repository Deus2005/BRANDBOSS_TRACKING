<?php
/**
 * Survey Results - View and analyze survey responses
 */
$pageTitle = 'Survey Results';
$breadcrumbs = [
    ['title' => 'Surveys', 'url' => 'index.php'],
    ['title' => 'Results']
];

require_once '../../includes/header.php';

$auth->requirePermission('surveys');

$db = Database::getInstance();
$surveyId = intval($_GET['id'] ?? 0);

if (!$surveyId) {
    setFlashMessage('Invalid survey ID', 'danger');
    header('Location: index.php');
    exit;
}

// Get survey with creator info
$survey = $db->fetch(
     "SELECT s.*, CONCAT(u.first_name, ' ', u.last_name) as created_by_name 
     FROM surveys s 
     JOIN users u ON s.created_by = u.id 
     WHERE s.id = ?",
    [$surveyId]
);

if (!$survey) {
    setFlashMessage('Survey not found', 'danger');
    header('Location: index.php');
    exit;
}

// Check permission
if ($auth->role() === 'user_1' && $survey['created_by'] != $auth->userId()) {
    setFlashMessage('You can only view results of your own surveys', 'danger');
    header('Location: index.php');
    exit;
}

// Get questions
$questions = $db->fetchAll(
    "SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY sort_order",
    [$surveyId]
);

// Get response summary
$totalResponses = $db->count('survey_responses', "survey_id = ? AND status = 'completed'", [$surveyId]);
$inProgressResponses = $db->count('survey_responses', "survey_id = ? AND status = 'in_progress'", [$surveyId]);

// Get responses for detailed view (with pagination)
$page = max(1, intval($_GET['page'] ?? 1));
$responsesResult = $db->paginate(
    "SELECT sr.*, u.full_name as respondent_name, u.role as respondent_role
     FROM survey_responses sr
     LEFT JOIN users u ON sr.respondent_id = u.id
     WHERE sr.survey_id = ? AND sr.status = 'completed'
     ORDER BY sr.submitted_at DESC",
    [$surveyId],
    $page,
    10
);
$responses = $responsesResult['data'];

// Get answer statistics for each question
$questionStats = [];
foreach ($questions as $q) {
    $stats = [
        'question' => $q,
        'answers' => [],
        'total' => 0
    ];
    
    $answers = $db->fetchAll(
        "SELECT sa.* 
         FROM survey_answers sa
         JOIN survey_responses sr ON sa.response_id = sr.id
         WHERE sa.question_id = ? AND sr.status = 'completed'",
        [$q['id']]
    );
    
    $stats['total'] = count($answers);
    
    switch ($q['question_type']) {
        case 'radio':
        case 'dropdown':
            $options = json_decode($q['options'], true) ?: [];
            $optionCounts = array_fill_keys($options, 0);
            foreach ($answers as $a) {
                $value = $a['answer_options'];
                if (isset($optionCounts[$value])) {
                    $optionCounts[$value]++;
                }
            }
            $stats['option_counts'] = $optionCounts;
            break;
            
        case 'checkbox':
            $options = json_decode($q['options'], true) ?: [];
            $optionCounts = array_fill_keys($options, 0);
            foreach ($answers as $a) {
                $selected = json_decode($a['answer_options'], true) ?: [];
                foreach ($selected as $sel) {
                    if (isset($optionCounts[$sel])) {
                        $optionCounts[$sel]++;
                    }
                }
            }
            $stats['option_counts'] = $optionCounts;
            break;
            
        case 'rating':
            $ratingCounts = [];
            $totalRating = 0;
            $min = $q['min_value'] ?? 1;
            $max = $q['max_value'] ?? 5;
            for ($i = $min; $i <= $max; $i++) {
                $ratingCounts[$i] = 0;
            }
            foreach ($answers as $a) {
                $rating = intval($a['rating_value']);
                if (isset($ratingCounts[$rating])) {
                    $ratingCounts[$rating]++;
                    $totalRating += $rating;
                }
            }
            $stats['rating_counts'] = $ratingCounts;
            $stats['average'] = $stats['total'] > 0 ? round($totalRating / $stats['total'], 2) : 0;
            break;
            
        case 'number':
            $numbers = array_filter(array_map(function($a) {
                return is_numeric($a['answer_text']) ? floatval($a['answer_text']) : null;
            }, $answers));
            if (!empty($numbers)) {
                $stats['min'] = min($numbers);
                $stats['max'] = max($numbers);
                $stats['average'] = round(array_sum($numbers) / count($numbers), 2);
            }
            break;
            
        default:
            $stats['recent_answers'] = array_slice(array_map(function($a) {
                return $a['answer_text'];
            }, $answers), 0, 10);
            break;
    }
    
    $questionStats[] = $stats;
}

// Export functionality
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="survey_' . $survey['survey_code'] . '_results.csv"');
    
    ob_end_clean();
    $output = fopen('php://output', 'w');
    
    $headers = ['Response ID', 'Respondent', 'Submitted At'];
    foreach ($questions as $q) {
        $headers[] = $q['question_text'];
    }
    fputcsv($output, $headers);
    
    $allResponses = $db->fetchAll(
        "SELECT sr.*, u.full_name as respondent_name
         FROM survey_responses sr
         LEFT JOIN users u ON sr.respondent_id = u.id
         WHERE sr.survey_id = ? AND sr.status = 'completed'
         ORDER BY sr.submitted_at",
        [$surveyId]
    );
    
    foreach ($allResponses as $resp) {
        $row = [
            $resp['response_code'],
            $survey['is_anonymous'] ? 'Anonymous' : ($resp['respondent_name'] ?? 'Unknown'),
            $resp['submitted_at']
        ];
        
        foreach ($questions as $q) {
            $answer = $db->fetch(
                "SELECT * FROM survey_answers WHERE response_id = ? AND question_id = ?",
                [$resp['id'], $q['id']]
            );
            
            if ($answer) {
                switch ($q['question_type']) {
                    case 'checkbox':
                        $row[] = implode(', ', json_decode($answer['answer_options'], true) ?: []);
                        break;
                    case 'radio':
                    case 'dropdown':
                        $row[] = $answer['answer_options'];
                        break;
                    case 'rating':
                        $row[] = $answer['rating_value'];
                        break;
                    default:
                        $row[] = $answer['answer_text'];
                }
            } else {
                $row[] = '';
            }
        }
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-bar-chart me-2"></i>Survey Results
    </h1>
    <div>
        <?php if ($totalResponses > 0): ?>
        <a href="?id=<?php echo $surveyId; ?>&export=csv" class="btn btn-success">
            <i class="bi bi-download"></i> Export CSV
        </a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<!-- Survey Info -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h4><?php echo clean($survey['title']); ?></h4>
                <p class="text-muted mb-2"><?php echo clean($survey['description']); ?></p>
                <small class="text-muted">
                    Code: <strong><?php echo clean($survey['survey_code']); ?></strong> |
                    Created by: <strong><?php echo clean($survey['created_by_name']); ?></strong>
                </small>
            </div>
            <div class="col-md-6">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="border rounded p-3">
                            <div class="display-6 text-primary"><?php echo $totalResponses; ?></div>
                            <small class="text-muted">Completed</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-3">
                            <div class="display-6 text-warning"><?php echo $inProgressResponses; ?></div>
                            <small class="text-muted">In Progress</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-3">
                            <div class="display-6 text-info"><?php echo count($questions); ?></div>
                            <small class="text-muted">Questions</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($totalResponses == 0): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    No completed responses yet. Share the survey to collect responses.
</div>
<?php else: ?>

<!-- Question Statistics -->
<div class="row">
    <?php foreach ($questionStats as $index => $stat): ?>
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <span class="badge bg-primary me-2">Q<?php echo $index + 1; ?></span>
                <?php echo clean($stat['question']['question_text']); ?>
                <?php if ($stat['question']['is_required']): ?>
                <span class="text-danger">*</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <small class="text-muted d-block mb-3">
                    <?php echo $stat['total']; ?> response(s) | Type: <?php echo ucfirst($stat['question']['question_type']); ?>
                </small>
                
                <?php 
                $type = $stat['question']['question_type'];
                
                if (in_array($type, ['radio', 'dropdown', 'checkbox'])): 
                ?>
                <?php if (!empty($stat['option_counts'])): ?>
                <?php foreach ($stat['option_counts'] as $option => $count): ?>
                <?php $percent = $stat['total'] > 0 ? round(($count / $stat['total']) * 100) : 0; ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><?php echo clean($option); ?></span>
                        <span><?php echo $count; ?> (<?php echo $percent; ?>%)</span>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-primary" style="width: <?php echo $percent; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <?php elseif ($type === 'rating'): ?>
                <div class="text-center mb-3">
                    <div class="display-4 text-warning">
                        <?php echo $stat['average']; ?>
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <small class="text-muted">Average Rating</small>
                </div>
                <?php if (!empty($stat['rating_counts'])): ?>
                <?php foreach ($stat['rating_counts'] as $rating => $count): ?>
                <?php $percent = $stat['total'] > 0 ? round(($count / $stat['total']) * 100) : 0; ?>
                <div class="d-flex align-items-center mb-1">
                    <span class="me-2" style="width: 30px;"><?php echo $rating; ?> <i class="bi bi-star-fill text-warning small"></i></span>
                    <div class="progress flex-grow-1" style="height: 15px;">
                        <div class="progress-bar bg-warning" style="width: <?php echo $percent; ?>%"></div>
                    </div>
                    <span class="ms-2 small"><?php echo $count; ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <?php elseif ($type === 'number'): ?>
                <?php if (isset($stat['average'])): ?>
                <div class="row text-center">
                    <div class="col-4">
                        <div class="h4 text-info"><?php echo $stat['min']; ?></div>
                        <small class="text-muted">Min</small>
                    </div>
                    <div class="col-4">
                        <div class="h4 text-primary"><?php echo $stat['average']; ?></div>
                        <small class="text-muted">Average</small>
                    </div>
                    <div class="col-4">
                        <div class="h4 text-success"><?php echo $stat['max']; ?></div>
                        <small class="text-muted">Max</small>
                    </div>
                </div>
                <?php else: ?>
                <p class="text-muted">No numeric responses</p>
                <?php endif; ?>
                
                <?php else: ?>
                <?php if (!empty($stat['recent_answers'])): ?>
                <div class="list-group list-group-flush" style="max-height: 200px; overflow-y: auto;">
                    <?php foreach ($stat['recent_answers'] as $answer): ?>
                    <?php if (!empty(trim($answer))): ?>
                    <div class="list-group-item px-0 py-2">
                        <small><?php echo clean(truncate($answer, 150)); ?></small>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">No text responses</p>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Individual Responses Table -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-list-ul me-2"></i>Individual Responses
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Response Code</th>
                        <th>Respondent</th>
                        <th>Role</th>
                        <th>Submitted</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($responses)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No responses yet</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($responses as $resp): ?>
                    <tr>
                        <td><strong><?php echo clean($resp['response_code']); ?></strong></td>
                        <td>
                            <?php if ($survey['is_anonymous']): ?>
                            <span class="text-muted"><i class="bi bi-incognito me-1"></i>Anonymous</span>
                            <?php else: ?>
                            <?php echo clean($resp['respondent_name'] ?? 'Unknown'); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$survey['is_anonymous'] && $resp['respondent_role']): ?>
                            <span class="badge bg-secondary"><?php echo roleName($resp['respondent_role']); ?></span>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo formatDateTime($resp['submitted_at']); ?></small></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-primary btn-sm btn-view-response"
                                    data-id="<?php echo $resp['id']; ?>"
                                    data-code="<?php echo clean($resp['response_code']); ?>">
                                <i class="bi bi-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if ($responsesResult['total_pages'] > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing <?php echo (($page - 1) * 10) + 1; ?> - <?php echo min($page * 10, $responsesResult['total']); ?> 
                of <?php echo $responsesResult['total']; ?> responses
            </small>
            <?php echo paginationHtml($responsesResult['current_page'], $responsesResult['total_pages'], 'results.php?id=' . $surveyId); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<!-- Response Detail Modal -->
<div class="modal fade" id="responseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Response Details</h5>
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
        
        App.ajax('../../ajax/survey-action.php', { action: 'view_response', response_id: id })
            .then(response => {
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
            });
    });
});
</script>
SCRIPT;

require_once '../../includes/footer.php'; 
?>