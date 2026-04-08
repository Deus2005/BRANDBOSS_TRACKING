<?php
/**
 * Survey Management - Edit Survey
 */
$pageTitle = 'Edit Survey';
$breadcrumbs = [
    ['title' => 'Surveys', 'url' => 'index.php'],
    ['title' => 'Edit Survey']
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

// Get survey
$survey = $db->fetch("SELECT * FROM surveys WHERE id = ?", [$surveyId]);

if (!$survey) {
    setFlashMessage('Survey not found', 'danger');
    header('Location: index.php');
    exit;
}

// Check permission
if ($auth->role() === 'user_1' && $survey['created_by'] != $auth->userId()) {
    setFlashMessage('You can only edit your own surveys', 'danger');
    header('Location: index.php');
    exit;
}

// Cannot edit closed surveys
if ($survey['status'] === 'closed') {
    setFlashMessage('Cannot edit a closed survey', 'warning');
    header('Location: index.php');
    exit;
}

// Get existing questions
$questions = $db->fetchAll(
    "SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY sort_order",
    [$surveyId]
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    $isAnonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    $allowMultiple = isset($_POST['allow_multiple']) ? 1 : 0;
    $targetRoles = isset($_POST['target_roles']) ? json_encode($_POST['target_roles']) : null;
    $newQuestions = $_POST['questions'] ?? [];

    $errors = [];

    if (empty($title)) {
        $errors[] = 'Survey title is required';
    }

    if (empty($newQuestions)) {
        $errors[] = 'At least one question is required';
    }

    if ($startDate && $endDate && strtotime($startDate) > strtotime($endDate)) {
        $errors[] = 'End date must be after start date';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Update survey
            $db->update('surveys', [
                'title' => $title,
                'description' => $description ?: null,
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
                'is_anonymous' => $isAnonymous,
                'allow_multiple' => $allowMultiple,
                'target_roles' => $targetRoles
            ], 'id = ?', [$surveyId]);

            // Delete existing questions (only if no responses yet)
            $responseCount = $db->count('survey_responses', 'survey_id = ?', [$surveyId]);

            if ($responseCount == 0) {
                $db->delete('survey_questions', 'survey_id = ?', [$surveyId]);

                // Insert new questions
                foreach ($newQuestions as $index => $q) {
                    if (empty(trim($q['text']))) continue;

                    $options = null;
                    if (in_array($q['type'], ['radio', 'checkbox', 'dropdown']) && !empty($q['options'])) {
                        $optArray = array_filter(array_map('trim', explode("\n", $q['options'])));
                        $options = json_encode(array_values($optArray));
                    }

                    $db->insert('survey_questions', [
                        'survey_id' => $surveyId,
                        'question_text' => trim($q['text']),
                        'question_type' => $q['type'],
                        'options' => $options,
                        'is_required' => isset($q['required']) ? 1 : 0,
                        'sort_order' => $index,
                        'min_value' => $q['min_value'] ?? null,
                        'max_value' => $q['max_value'] ?? null,
                        'placeholder' => $q['placeholder'] ?? null
                    ]);
                }
            } else {
                // Only update existing questions' text (not structure) if has responses
                // This is a simplified approach - in production you might want more granular control
            }

            $db->commit();

            $auth->logActivity($auth->userId(), 'updated_survey', 'surveys', 'surveys', $surveyId);

            setFlashMessage('Survey updated successfully!', 'success');
            header('Location: edit.php?id=' . $surveyId);
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Failed to update survey: ' . $e->getMessage();
        }
    }
}

// Use POST data if validation failed, otherwise use DB data
$formData = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $survey;
$targetRolesArray = json_decode($survey['target_roles'] ?? '[]', true) ?: [];

// Question types
$questionTypes = [
    'text' => ['label' => 'Short Text', 'icon' => 'bi-input-cursor-text', 'hasOptions' => false],
    'textarea' => ['label' => 'Long Text', 'icon' => 'bi-textarea-t', 'hasOptions' => false],
    'radio' => ['label' => 'Single Choice', 'icon' => 'bi-ui-radios', 'hasOptions' => true],
    'checkbox' => ['label' => 'Multiple Choice', 'icon' => 'bi-ui-checks', 'hasOptions' => true],
    'dropdown' => ['label' => 'Dropdown', 'icon' => 'bi-menu-button-wide', 'hasOptions' => true],
    'rating' => ['label' => 'Rating Scale', 'icon' => 'bi-star', 'hasOptions' => false, 'hasRange' => true],
    'number' => ['label' => 'Number', 'icon' => 'bi-123', 'hasOptions' => false],
    'date' => ['label' => 'Date', 'icon' => 'bi-calendar-date', 'hasOptions' => false]
];

$availableRoles = [
    'user_2' => 'Installer (User 2)',
    'user_3' => 'Inspector (User 3)',
    'user_4' => 'Maintenance (User 4)'
];

$responseCount = $db->count('survey_responses', 'survey_id = ?', [$surveyId]);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-clipboard2-data me-2"></i>Edit Survey
        <small class="text-muted fs-6"><?php echo clean($survey['survey_code']); ?></small>
    </h1>
    <div>
        <?php if ($survey['status'] === 'active'): ?>
        <span class="badge bg-success me-2">Active</span>
        <?php elseif ($survey['status'] === 'draft'): ?>
        <span class="badge bg-secondary me-2">Draft</span>
        <?php endif; ?>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
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

<?php if ($responseCount > 0): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    This survey has <strong><?php echo $responseCount; ?></strong> response(s). 
    You can only edit the title, description, and dates. Questions cannot be modified.
</div>
<?php endif; ?>

<form method="POST" id="surveyForm">
    <div class="row">
        <!-- Survey Details -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-info-circle me-2"></i>Survey Details
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" required
                               value="<?php echo clean($formData['title'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"><?php echo clean($formData['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date"
                                   value="<?php echo clean($formData['start_date'] ?? ''); ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date"
                                   value="<?php echo clean($formData['end_date'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Target Respondents</label>
                        <div class="border rounded p-2">
                            <?php foreach ($availableRoles as $roleKey => $roleName): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="target_roles[]" 
                                       value="<?php echo $roleKey; ?>" id="role_<?php echo $roleKey; ?>"
                                       <?php echo in_array($roleKey, $targetRolesArray) ? 'checked' : ''; ?>
                                       <?php echo $responseCount > 0 ? 'disabled' : ''; ?>>
                                <label class="form-check-label" for="role_<?php echo $roleKey; ?>">
                                    <?php echo $roleName; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_anonymous" id="is_anonymous"
                                   <?php echo ($formData['is_anonymous'] ?? false) ? 'checked' : ''; ?>
                                   <?php echo $responseCount > 0 ? 'disabled' : ''; ?>>
                            <label class="form-check-label" for="is_anonymous">
                                <i class="bi bi-incognito me-1"></i>Anonymous Responses
                            </label>
                        </div>
                    </div>

                    <div class="mb-0">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="allow_multiple" id="allow_multiple"
                                   <?php echo ($formData['allow_multiple'] ?? false) ? 'checked' : ''; ?>
                                   <?php echo $responseCount > 0 ? 'disabled' : ''; ?>>
                            <label class="form-check-label" for="allow_multiple">
                                <i class="bi bi-arrow-repeat me-1"></i>Allow Multiple Responses
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-graph-up me-2"></i>Statistics
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Questions:</span>
                        <strong><?php echo count($questions); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Responses:</span>
                        <strong><?php echo $responseCount; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Created:</span>
                        <strong><?php echo formatDateTime($survey['created_at']); ?></strong>
                    </div>

                    <?php if ($responseCount > 0): ?>
                    <hr>
                    <a href="results.php?id=<?php echo $surveyId; ?>" class="btn btn-info btn-sm w-100">
                        <i class="bi bi-bar-chart"></i> View Results
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Questions Builder -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-check me-2"></i>Questions</span>
                    <?php if ($responseCount == 0): ?>
                    <button type="button" class="btn btn-primary btn-sm" id="addQuestion">
                        <i class="bi bi-plus-lg"></i> Add Question
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body" id="questionsContainer">
                    <?php if (empty($questions)): ?>
                    <div class="text-center py-5 text-muted" id="emptyState">
                        <i class="bi bi-clipboard2 display-4 d-block mb-3"></i>
                        <p>No questions added yet</p>
                        <button type="button" class="btn btn-outline-primary" onclick="addQuestion()">
                            <i class="bi bi-plus-lg"></i> Add Your First Question
                        </button>
                    </div>
                    <?php else: ?>
                    <?php foreach ($questions as $index => $q): ?>
                    <?php 
                    $options = $q['options'] ? implode("\n", json_decode($q['options'], true)) : '';
                    ?>
                    <div class="question-card card mb-3" data-index="<?php echo $index; ?>">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                            <div class="d-flex align-items-center">
                                <span class="question-handle me-2" style="cursor: grab;">
                                    <i class="bi bi-grip-vertical text-muted"></i>
                                </span>
                                <span class="question-number fw-bold text-primary">Q<?php echo $index + 1; ?></span>
                            </div>
                            <?php if ($responseCount == 0): ?>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary btn-move-up" title="Move Up">
                                    <i class="bi bi-arrow-up"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-move-down" title="Move Down">
                                    <i class="bi bi-arrow-down"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-remove" title="Remove">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label class="form-label">Question Text <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control question-text" 
                                           name="questions[<?php echo $index; ?>][text]" 
                                           value="<?php echo clean($q['question_text']); ?>"
                                           <?php echo $responseCount > 0 ? 'readonly' : ''; ?> required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Type</label>
                                    <select class="form-select question-type" name="questions[<?php echo $index; ?>][type]"
                                            <?php echo $responseCount > 0 ? 'disabled' : ''; ?>>
                                        <?php foreach ($questionTypes as $type => $info): ?>
                                        <option value="<?php echo $type; ?>" <?php echo $q['question_type'] === $type ? 'selected' : ''; ?>>
                                            <?php echo $info['label']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($responseCount > 0): ?>
                                    <input type="hidden" name="questions[<?php echo $index; ?>][type]" value="<?php echo $q['question_type']; ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                            </div>  

                            <!-- Options for radio/checkbox/dropdown -->
                            <div class="mb-3 options-container" style="<?php echo in_array($q['question_type'], ['radio', 'checkbox', 'dropdown']) ? '' : 'display: none;'; ?>">
                                <label class="form-label">Options (one per line)</label>
                                <textarea class="form-control question-options" name="questions[<?php echo $index; ?>][options]" rows="4"
                                          <?php echo $responseCount > 0 ? 'readonly' : ''; ?>><?php echo clean($options); ?></textarea>
                            </div>

                            <!-- Rating range -->
                            <div class="row mb-3 rating-container" style="<?php echo $q['question_type'] === 'rating' ? '' : 'display: none;'; ?>">
                                <div class="col-6">
                                    <label class="form-label">Min Value</label>
                                    <input type="number" class="form-control" name="questions[<?php echo $index; ?>][min_value]" 
                                           value="<?php echo $q['min_value'] ?? 1; ?>"
                                           <?php echo $responseCount > 0 ? 'readonly' : ''; ?>>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Max Value</label>
                                    <input type="number" class="form-control" name="questions[<?php echo $index; ?>][max_value]" 
                                           value="<?php echo $q['max_value'] ?? 5; ?>"
                                           <?php echo $responseCount > 0 ? 'readonly' : ''; ?>>
                                </div>
                            </div>

                            <!-- Placeholder -->
                            <div class="mb-3 placeholder-container" style="<?php echo in_array($q['question_type'], ['text', 'textarea', 'number']) ? '' : 'display: none;'; ?>">
                                <label class="form-label">Placeholder Text</label>
                                <input type="text" class="form-control" name="questions[<?php echo $index; ?>][placeholder]" 
                                       value="<?php echo clean($q['placeholder'] ?? ''); ?>"
                                       <?php echo $responseCount > 0 ? 'readonly' : ''; ?>>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input question-required" type="checkbox" 
                                       name="questions[<?php echo $index; ?>][required]"
                                       <?php echo $q['is_required'] ? 'checked' : ''; ?>
                                       <?php echo $responseCount > 0 ? 'disabled' : ''; ?>>
                                <label class="form-check-label">Required</label>
                                <?php if ($responseCount > 0 && $q['is_required']): ?>
                                <input type="hidden" name="questions[<?php echo $index; ?>][required]" value="1">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Question Template (same as create page) -->
<template id="questionTemplate">
    <div class="question-card card mb-3" data-index="">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
            <div class="d-flex align-items-center">
                <span class="question-handle me-2" style="cursor: grab;">
                    <i class="bi bi-grip-vertical text-muted"></i>
                </span>
                <span class="question-number fw-bold text-primary">Q1</span>
            </div>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary btn-move-up" title="Move Up">
                    <i class="bi bi-arrow-up"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-move-down" title="Move Down">
                    <i class="bi bi-arrow-down"></i>
                </button>
                <button type="button" class="btn btn-outline-danger btn-remove" title="Remove">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label">Question Text <span class="text-danger">*</span></label>
                    <input type="text" class="form-control question-text" name="questions[0][text]" 
                           placeholder="Enter your question" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select class="form-select question-type" name="questions[0][type]">
                        <option value="text">Short Text</option>
                        <option value="textarea">Long Text</option>
                        <option value="radio">Single Choice</option>
                        <option value="checkbox">Multiple Choice</option>
                        <option value="dropdown">Dropdown</option>
                        <option value="rating">Rating Scale</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                    </select>
                </div>
            </div>

            <div class="mb-3 options-container" style="display: none;">
                <label class="form-label">Options (one per line)</label>
                <textarea class="form-control question-options" name="questions[0][options]" rows="4"></textarea>
            </div>

            <div class="row mb-3 rating-container" style="display: none;">
                <div class="col-6">
                    <label class="form-label">Min Value</label>
                    <input type="number" class="form-control" name="questions[0][min_value]" value="1">
                </div>
                <div class="col-6">
                    <label class="form-label">Max Value</label>
                    <input type="number" class="form-control" name="questions[0][max_value]" value="5">
                </div>
            </div>

            <div class="mb-3 placeholder-container">
                <label class="form-label">Placeholder Text</label>
                <input type="text" class="form-control" name="questions[0][placeholder]">
            </div>

            <div class="form-check">
                <input class="form-check-input question-required" type="checkbox" name="questions[0][required]" checked>
                <label class="form-check-label">Required</label>
            </div>
        </div>
    </div>
</template>

<?php 
$questionCount = count($questions);
$canEdit = $responseCount == 0;
$extraScripts = <<<SCRIPT
<script>
let questionIndex = {$questionCount};
const canEdit = {$canEdit};

function addQuestion(type = 'text') {
    if (!canEdit) return;
    
    const container = document.getElementById('questionsContainer');
    const template = document.getElementById('questionTemplate');
    const emptyState = document.getElementById('emptyState');
    
    if (emptyState) emptyState.style.display = 'none';
    
    const clone = template.content.cloneNode(true);
    const card = clone.querySelector('.question-card');
    
    card.dataset.index = questionIndex;
    card.querySelectorAll('[name]').forEach(el => {
        el.name = el.name.replace('[0]', `[` + questionIndex + `]`);
    });
    
    container.appendChild(clone);
    bindQuestionEvents(container.lastElementChild);
    updateQuestionNumbers();
    toggleTypeFields(container.lastElementChild);
    
    questionIndex++;
}

function bindQuestionEvents(card) {
    card.querySelector('.btn-remove')?.addEventListener('click', function() {
        if (confirm('Remove this question?')) {
            card.remove();
            updateQuestionNumbers();
            checkEmptyState();
        }
    });
    
    card.querySelector('.btn-move-up')?.addEventListener('click', function() {
        const prev = card.previousElementSibling;
        if (prev && prev.classList.contains('question-card')) {
            card.parentNode.insertBefore(card, prev);
            updateQuestionNumbers();
        }
    });
    
    card.querySelector('.btn-move-down')?.addEventListener('click', function() {
        const next = card.nextElementSibling;
        if (next && next.classList.contains('question-card')) {
            card.parentNode.insertBefore(next, card);
            updateQuestionNumbers();
        }
    });
    
    card.querySelector('.question-type')?.addEventListener('change', function() {
        toggleTypeFields(card);
    });
}

function toggleTypeFields(card) {
    const type = card.querySelector('.question-type').value;
    const optionsContainer = card.querySelector('.options-container');
    const ratingContainer = card.querySelector('.rating-container');
    const placeholderContainer = card.querySelector('.placeholder-container');
    
    if (optionsContainer) optionsContainer.style.display = 'none';
    if (ratingContainer) ratingContainer.style.display = 'none';
    if (placeholderContainer) placeholderContainer.style.display = 'none';
    
    if (['radio', 'checkbox', 'dropdown'].includes(type)) {
        if (optionsContainer) optionsContainer.style.display = 'block';
    } else if (type === 'rating') {
        if (ratingContainer) ratingContainer.style.display = 'block';
    } else if (['text', 'textarea', 'number'].includes(type)) {
        if (placeholderContainer) placeholderContainer.style.display = 'block';
    }
}

function updateQuestionNumbers() {
    const cards = document.querySelectorAll('.question-card');
    cards.forEach((card, index) => {
        card.querySelector('.question-number').textContent = 'Q' + (index + 1);
        card.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace(/\[\d+\]/, '[' + index + ']');
        });
    });
}

function checkEmptyState() {
    const container = document.getElementById('questionsContainer');
    const emptyState = document.getElementById('emptyState');
    const hasQuestions = container.querySelectorAll('.question-card').length > 0;
    
    if (emptyState) {
        emptyState.style.display = hasQuestions ? 'none' : 'block';
    }
}

// Initialize existing questions
document.querySelectorAll('.question-card').forEach(card => {
    bindQuestionEvents(card);
});

document.getElementById('addQuestion')?.addEventListener('click', function() {
    addQuestion();
});
</script>
SCRIPT;

require_once '../../includes/footer.php';