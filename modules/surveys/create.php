<?php
/**
 * Survey Management - Create Survey
 */
$pageTitle = 'Create Survey';
$breadcrumbs = [
    ['title' => 'Surveys', 'url' => 'index.php'],
    ['title' => 'Create Survey']
];

require_once '../../includes/header.php';

$auth->requirePermission('surveys');

$db = Database::getInstance();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    $isAnonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    $allowMultiple = isset($_POST['allow_multiple']) ? 1 : 0;
    $targetRoles = isset($_POST['target_roles']) ? json_encode($_POST['target_roles']) : null;
    $questions = $_POST['questions'] ?? [];

    // SET SURVEY STATUS BASED ON BUTTON CLICKED
    $action = $_POST['action'] ?? 'draft';
$status = $_POST['status'] ?? 'draft';

if (!in_array($status, ['draft', 'active', 'closed'], true)) {
    $status = 'draft';
}
    
    $errors = [];
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Survey title is required';
    }
    
    if (empty($questions)) {
        $errors[] = 'At least one question is required';
    }
    
    if ($startDate && $endDate && strtotime($startDate) > strtotime($endDate)) {
        $errors[] = 'End date must be after start date';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Generate survey code
            $surveyCode = 'SRV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
            
            // Insert survey
            $surveyId = $db->insert('surveys', [
                'survey_code' => $surveyCode,
                'title' => $title,
                'description' => $description ?: null,
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
                'is_anonymous' => $isAnonymous,
                'allow_multiple' => $allowMultiple,
                'target_roles' => $targetRoles,
                'status' => $status,
                'created_by' => $auth->userId()
            ]);
            
            // Insert questions
            foreach ($questions as $index => $q) {
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
            
            $db->commit();
            
            // Log activity
            $auth->logActivity($auth->userId(), 'created_survey', 'surveys', 'surveys', $surveyId);

            if ($status === 'draft') {
                setFlashMessage('Saved as draft!', 'success');
}           
            elseif ($status === 'active') {
                setFlashMessage('Survey published successfully!', 'success');

} 

header('Location: edit.php?id=' . $surveyId);
exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Failed to create survey: ' . $e->getMessage();
        }
    }
}

// Available roles for targeting
$availableRoles = [
    'user_2' => 'Installer (User 2)',
    'user_3' => 'Inspector (User 3)',
    'user_4' => 'Maintenance (User 4)'
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-clipboard2-plus me-2"></i>Create Survey
    </h1>
    <a href="index.php" class="btn btn-outline-secondary">
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

<form method="POST" id="surveyForm">
    <div class="row">
        <!-- Survey Details -->
<div class="col-lg-4">
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <span class="d-flex align-items-center">
                <i class="bi bi-info-circle me-2"></i>
                Survey Details
            </span>
        </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" required
                               value="<?php echo clean($_POST['title'] ?? ''); ?>"
                               placeholder="Enter survey title">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"
                                  placeholder="Brief description of the survey"><?php echo clean($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date"
                                   value="<?php echo clean($_POST['start_date'] ?? ''); ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date"
                                   value="<?php echo clean($_POST['end_date'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Target Respondents</label>
                        <div class="border rounded p-2">
                            <?php foreach ($availableRoles as $roleKey => $roleName): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="target_roles[]" 
                                       value="<?php echo $roleKey; ?>" id="role_<?php echo $roleKey; ?>"
                                       <?php echo (isset($_POST['target_roles']) && in_array($roleKey, $_POST['target_roles'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="role_<?php echo $roleKey; ?>">
                                    <?php echo $roleName; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="form-text">Leave unchecked to allow all users</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_anonymous" id="is_anonymous"
                                   <?php echo isset($_POST['is_anonymous']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_anonymous">
                                <i class="bi bi-incognito me-1"></i>Anonymous Responses
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-0">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="allow_multiple" id="allow_multiple"
                                   <?php echo isset($_POST['allow_multiple']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="allow_multiple">
                                <i class="bi bi-arrow-repeat me-1"></i>Allow Multiple Responses
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Question Types Reference -->
            <div class="card">

                <div class="card-body p-2">
                    <div class="list-group list-group-flush">
                        <?php foreach ($questionTypes as $type => $info): ?>
                        <div class="list-group-item d-flex align-items-center py-2 draggable-type" 
                             data-type="<?php echo $type; ?>" style="cursor: grab;">
                            <i class="<?php echo $info['icon']; ?> me-2 text-primary"></i>
                            <small><?php echo $info['label']; ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Questions Builder -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body" id="questionsContainer">
                    <!-- Questions will be added here dynamically -->
                    <div class="text-center py-5 text-muted" id="emptyState">
                        <i class="bi bi-clipboard2 display-4 d-block mb-3"></i>
                        <p>No questions added yet</p>
                       <button type="button" class="btn btn-primary btn-sm" id="addQuestion">
                            <i class="bi bi-plus-lg"></i> Add Your First Question
                        </button>
                    </div>
                </div>
            </div>
            
            <input type="hidden" name="status" id="surveyStatus" value="draft">

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>

            <button type="submit" class="btn btn-secondary" onclick="document.getElementById('surveyStatus').value='draft'">
                 <i class="bi bi-save"></i> Save as Draft
            </button>

            <button type="submit" class="btn text-white" style="background:#b30000;"
                 onclick="document.getElementById('surveyStatus').value='active'">
                <i class="bi bi-check-circle"></i> Publish
            </button>
        </div>
        </div>
    </div>
</form>

<!-- Question Template -->
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
            
            <!-- Options for radio/checkbox/dropdown -->
            <div class="mb-3 options-container" style="display: none;">
                <label class="form-label">Options (one per line)</label>
                <textarea class="form-control question-options" name="questions[0][options]" rows="4"
                          placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
            </div>
            
            <!-- Rating range -->
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
            
            <!-- Placeholder for text types -->
            <div class="mb-3 placeholder-container">
                <label class="form-label">Placeholder Text</label>
                <input type="text" class="form-control" name="questions[0][placeholder]" 
                       placeholder="Optional hint text">
            </div>
            
            <div class="form-check">
                <input class="form-check-input question-required" type="checkbox" 
                       name="questions[0][required]" checked>
                <label class="form-check-label">Required</label>
            </div>
        </div>
    </div>
</template>

<?php 
$extraScripts = <<<'SCRIPT'
<script>
let questionIndex = 0;

function addQuestion(type = 'text') {
    const container = document.getElementById('questionsContainer');
    const template = document.getElementById('questionTemplate');
    const emptyState = document.getElementById('emptyState');
    
    // Hide empty state
    if (emptyState) emptyState.style.display = 'none';
    
    // Clone template
    const clone = template.content.cloneNode(true);
    const card = clone.querySelector('.question-card');
    
    // Update index and names
    card.dataset.index = questionIndex;
    card.querySelectorAll('[name]').forEach(el => {
        el.name = el.name.replace('[0]', `[${questionIndex}]`);
    });
    
    // Set question type
    const typeSelect = card.querySelector('.question-type');
    typeSelect.value = type;
    
    container.appendChild(clone);
    
    // Bind events
    bindQuestionEvents(container.lastElementChild);
    
    // Update numbers and type display
    updateQuestionNumbers();
    toggleTypeFields(container.lastElementChild);
    
    questionIndex++;
}

function bindQuestionEvents(card) {
    // Remove button
    card.querySelector('.btn-remove').addEventListener('click', function() {
        if (confirm('Remove this question?')) {
            card.remove();
            updateQuestionNumbers();
            checkEmptyState();
        }
    });
    
    // Move up
    card.querySelector('.btn-move-up').addEventListener('click', function() {
        const prev = card.previousElementSibling;
        if (prev && prev.classList.contains('question-card')) {
            card.parentNode.insertBefore(card, prev);
            updateQuestionNumbers();
        }
    });
    
    // Move down
    card.querySelector('.btn-move-down').addEventListener('click', function() {
        const next = card.nextElementSibling;
        if (next && next.classList.contains('question-card')) {
            card.parentNode.insertBefore(next, card);
            updateQuestionNumbers();
        }
    });
    
    // Type change
    card.querySelector('.question-type').addEventListener('change', function() {
        toggleTypeFields(card);
    });
}

function toggleTypeFields(card) {
    const type = card.querySelector('.question-type').value;
    const optionsContainer = card.querySelector('.options-container');
    const ratingContainer = card.querySelector('.rating-container');
    const placeholderContainer = card.querySelector('.placeholder-container');
    
    // Hide all
    optionsContainer.style.display = 'none';
    ratingContainer.style.display = 'none';
    placeholderContainer.style.display = 'none';
    
    // Show based on type
    if (['radio', 'checkbox', 'dropdown'].includes(type)) {
        optionsContainer.style.display = 'block';
    } else if (type === 'rating') {
        ratingContainer.style.display = 'block';
    } else if (['text', 'textarea', 'number'].includes(type)) {
        placeholderContainer.style.display = 'block';
    }
}

function updateQuestionNumbers() {
    const cards = document.querySelectorAll('.question-card');
    cards.forEach((card, index) => {
        card.querySelector('.question-number').textContent = `Q${index + 1}`;
        // Update input names to maintain order
        card.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace(/\[\d+\]/, `[${index}]`);
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

// Add question button
document.getElementById('addQuestion').addEventListener('click', function() {
    addQuestion();
});

// Form validation
document.getElementById('surveyForm').addEventListener('submit', function(e) {
    const questions = document.querySelectorAll('.question-card');
    if (questions.length === 0) {
        e.preventDefault();
        App.toast('Please add at least one question', 'danger');
        return false;
    }
    
    // Validate required options for radio/checkbox/dropdown
    let valid = true;
    questions.forEach(card => {
        const type = card.querySelector('.question-type').value;
        const options = card.querySelector('.question-options');
        if (['radio', 'checkbox', 'dropdown'].includes(type)) {
            if (!options.value.trim()) {
                valid = false;
                options.classList.add('is-invalid');
            } else {
                options.classList.remove('is-invalid');
            }
        }
    });
    
    if (!valid) {
        e.preventDefault();
        App.toast('Please provide options for choice questions', 'danger');
    }
});

// Initialize with one question if empty
document.addEventListener('DOMContentLoaded', function() {
    // Don't add initial question - let user start fresh
});
</script>
SCRIPT;

require_once '../../includes/footer.php'; 
?>