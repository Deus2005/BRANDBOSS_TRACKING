<?php
/**
 * Survey - Answer/Take Survey
 * Available to users based on target_roles setting
 */
$pageTitle = 'Take Survey';
$breadcrumbs = [
    ['title' => 'Surveys', 'url' => 'my-surveys.php'],
    ['title' => 'Answer Survey']
];

require_once '../../includes/header.php';

$db = Database::getInstance();
$surveyId = intval($_GET['id'] ?? 0);

if (!$surveyId) {
    setFlashMessage('Invalid survey ID', 'danger');
    header('Location: my-surveys.php');
    exit;
}

// Get survey
$survey = $db->fetch("SELECT * FROM surveys WHERE id = ? AND status = 'active'", [$surveyId]);

if (!$survey) {
    setFlashMessage('Survey not found or not available', 'danger');
    header('Location: my-surveys.php');
    exit;
}

// Check if survey is within date range
$today = date('Y-m-d');
if ($survey['start_date'] && $today < $survey['start_date']) {
    setFlashMessage('This survey has not started yet', 'warning');
    header('Location: my-surveys.php');
    exit;
}
if ($survey['end_date'] && $today > $survey['end_date']) {
    setFlashMessage('This survey has ended', 'warning');
    header('Location: my-surveys.php');
    exit;
}

// Check if user role is allowed
$targetRoles = json_decode($survey['target_roles'], true) ?: [];
$currentRole = $auth->role();

if (!empty($targetRoles) && !in_array($currentRole, $targetRoles) && $currentRole !== 'super_admin') {
    setFlashMessage('You are not allowed to take this survey', 'danger');
    header('Location: my-surveys.php');
    exit;
}

// Check if user already responded (if multiple not allowed)
if (!$survey['allow_multiple']) {
    $existingResponse = $db->fetch(
        "SELECT id FROM survey_responses WHERE survey_id = ? AND respondent_id = ? AND status = 'completed'",
        [$surveyId, $auth->userId()]
    );
    
    if ($existingResponse) {
        setFlashMessage('You have already completed this survey', 'info');
        header('Location: my-surveys.php');
        exit;
    }
}

// Get questions
$questions = $db->fetchAll(
    "SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY sort_order",
    [$surveyId]
);

if (empty($questions)) {
    setFlashMessage('This survey has no questions', 'warning');
    header('Location: my-surveys.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answers'] ?? [];
    $errors = [];
    
    // Validate required questions
    foreach ($questions as $q) {
        if ($q['is_required']) {
            $answer = $answers[$q['id']] ?? null;
            if (empty($answer) && $answer !== '0') {
                $errors[] = 'Please answer question: ' . truncate($q['question_text'], 50);
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Generate response code
            $responseCode = 'RSP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Create response record
            $responseId = $db->insert('survey_responses', [
                'survey_id' => $surveyId,
                'respondent_id' => $survey['is_anonymous'] ? null : $auth->userId(),
                'response_code' => $responseCode,
                'status' => 'completed',
                'submitted_at' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            // Save answers
            foreach ($questions as $q) {
                $answer = $answers[$q['id']] ?? null;
                
                $answerData = [
                    'response_id' => $responseId,
                    'question_id' => $q['id'],
                    'answer_text' => null,
                    'answer_options' => null,
                    'rating_value' => null
                ];
                
                switch ($q['question_type']) {
                    case 'checkbox':
                        $answerData['answer_options'] = is_array($answer) ? json_encode($answer) : null;
                        break;
                    case 'radio':
                    case 'dropdown':
                        $answerData['answer_options'] = $answer;
                        break;
                    case 'rating':
                        $answerData['rating_value'] = intval($answer);
                        break;
                    default:
                        $answerData['answer_text'] = $answer;
                }
                
                $db->insert('survey_answers', $answerData);
            }
            
            $db->commit();
            
            // Log activity
            $auth->logActivity($auth->userId(), 'submitted_survey', 'surveys', 'survey_responses', $responseId);
            
            setFlashMessage('Thank you! Your response has been submitted.', 'success');
            header('Location: my-surveys.php');
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Failed to submit response: ' . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-clipboard2-check me-2"></i><?php echo clean($survey['title']); ?>
    </h1>
    <a href="my-surveys.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo clean($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Survey Info -->
<div class="card mb-4">
    <div class="card-body">
        <?php if ($survey['description']): ?>
        <p class="mb-2"><?php echo nl2br(clean($survey['description'])); ?></p>
        <?php endif; ?>
        <small class="text-muted">
            <i class="bi bi-question-circle me-1"></i><?php echo count($questions); ?> questions
            <?php if ($survey['is_anonymous']): ?>
            <span class="ms-3"><i class="bi bi-incognito me-1"></i>Anonymous Survey</span>
            <?php endif; ?>
        </small>
    </div>
</div>

<form method="POST" id="surveyForm">
    <div class="card">
        <div class="card-body">
            <?php foreach ($questions as $index => $q): ?>
            <div class="question-item mb-4 pb-4 <?php echo $index < count($questions) - 1 ? 'border-bottom' : ''; ?>">
                <label class="form-label fw-bold">
                    <span class="badge bg-primary me-2">Q<?php echo $index + 1; ?></span>
                    <?php echo clean($q['question_text']); ?>
                    <?php if ($q['is_required']): ?>
                    <span class="text-danger">*</span>
                    <?php endif; ?>
                </label>
                
                <?php
                $type = $q['question_type'];
                $options = json_decode($q['options'], true) ?: [];
                $value = $_POST['answers'][$q['id']] ?? '';
                ?>
                
                <?php if ($type === 'text'): ?>
                <input type="text" class="form-control" name="answers[<?php echo $q['id']; ?>]"
                       value="<?php echo clean($value); ?>"
                       placeholder="<?php echo clean($q['placeholder'] ?? ''); ?>"
                       <?php echo $q['is_required'] ? 'required' : ''; ?>>
                
                <?php elseif ($type === 'textarea'): ?>
                <textarea class="form-control" name="answers[<?php echo $q['id']; ?>]" rows="4"
                          placeholder="<?php echo clean($q['placeholder'] ?? ''); ?>"
                          <?php echo $q['is_required'] ? 'required' : ''; ?>><?php echo clean($value); ?></textarea>
                
                <?php elseif ($type === 'radio'): ?>
                <div class="mt-2">
                    <?php foreach ($options as $opt): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]"
                               value="<?php echo clean($opt); ?>" id="q<?php echo $q['id']; ?>_<?php echo md5($opt); ?>"
                               <?php echo $value === $opt ? 'checked' : ''; ?>
                               <?php echo $q['is_required'] ? 'required' : ''; ?>>
                        <label class="form-check-label" for="q<?php echo $q['id']; ?>_<?php echo md5($opt); ?>">
                            <?php echo clean($opt); ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php elseif ($type === 'checkbox'): ?>
                <div class="mt-2">
                    <?php foreach ($options as $opt): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="answers[<?php echo $q['id']; ?>][]"
                               value="<?php echo clean($opt); ?>" id="q<?php echo $q['id']; ?>_<?php echo md5($opt); ?>"
                               <?php echo (is_array($value) && in_array($opt, $value)) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="q<?php echo $q['id']; ?>_<?php echo md5($opt); ?>">
                            <?php echo clean($opt); ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php elseif ($type === 'dropdown'): ?>
                <select class="form-select" name="answers[<?php echo $q['id']; ?>]"
                        <?php echo $q['is_required'] ? 'required' : ''; ?>>
                    <option value="">-- Select an option --</option>
                    <?php foreach ($options as $opt): ?>
                    <option value="<?php echo clean($opt); ?>" <?php echo $value === $opt ? 'selected' : ''; ?>>
                        <?php echo clean($opt); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <?php elseif ($type === 'rating'): ?>
                <?php 
                $min = $q['min_value'] ?? 1;
                $max = $q['max_value'] ?? 5;
                ?>
                <div class="mt-2">
                    <div class="rating-container d-flex align-items-center gap-2">
                        <span class="text-muted small"><?php echo $min; ?></span>
                        <?php for ($i = $min; $i <= $max; $i++): ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]"
                                   value="<?php echo $i; ?>" id="q<?php echo $q['id']; ?>_r<?php echo $i; ?>"
                                   <?php echo $value == $i ? 'checked' : ''; ?>
                                   <?php echo $q['is_required'] ? 'required' : ''; ?>>
                            <label class="form-check-label" for="q<?php echo $q['id']; ?>_r<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </label>
                        </div>
                        <?php endfor; ?>
                        <span class="text-muted small"><?php echo $max; ?></span>
                    </div>
                </div>
                
                <?php elseif ($type === 'number'): ?>
                <input type="number" class="form-control" name="answers[<?php echo $q['id']; ?>]"
                       value="<?php echo clean($value); ?>"
                       placeholder="<?php echo clean($q['placeholder'] ?? ''); ?>"
                       <?php echo $q['min_value'] !== null ? 'min="' . $q['min_value'] . '"' : ''; ?>
                       <?php echo $q['max_value'] !== null ? 'max="' . $q['max_value'] . '"' : ''; ?>
                       <?php echo $q['is_required'] ? 'required' : ''; ?>>
                
                <?php elseif ($type === 'date'): ?>
                <input type="date" class="form-control" name="answers[<?php echo $q['id']; ?>]"
                       value="<?php echo clean($value); ?>"
                       <?php echo $q['is_required'] ? 'required' : ''; ?>>
                
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Fields marked with <span class="text-danger">*</span> are required
                </small>
                <div>
                    <a href="my-surveys.php" class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i> Submit Response
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php 
$extraScripts = <<<'SCRIPT'
<script>
document.getElementById('surveyForm').addEventListener('submit', function(e) {
    const requiredChecks = document.querySelectorAll('input[type="radio"][required]');
    const radioGroups = {};
    
    requiredChecks.forEach(radio => {
        radioGroups[radio.name] = radioGroups[radio.name] || false;
        if (radio.checked) radioGroups[radio.name] = true;
    });
    
    for (const group in radioGroups) {
        if (!radioGroups[group]) {
            e.preventDefault();
            App.toast('Please answer all required questions', 'danger');
            return false;
        }
    }
});
</script>
SCRIPT;

require_once '../../includes/footer.php'; 
?>