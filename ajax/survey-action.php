<?php
/**
 * Survey AJAX Actions Handler
 */
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Auth.php';

header('Content-Type: application/json');

$auth = Auth::getInstance();

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$db = Database::getInstance();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $auth->userId();
$currentRole = $auth->role();

try {
    switch ($action) {
        case 'publish':
            // Publish a draft survey
            $surveyId = intval($_POST['id'] ?? 0);
            
            if (!$auth->can('surveys')) {
                throw new Exception('Permission denied');
            }
            
            $survey = $db->fetch("SELECT * FROM surveys WHERE id = ?", [$surveyId]);
            
            if (!$survey) {
                throw new Exception('Survey not found');
            }
            
            // Check ownership for user_1
            if ($currentRole === 'user_1' && $survey['created_by'] != $userId) {
                throw new Exception('You can only publish your own surveys');
            }
            
            if ($survey['status'] !== 'draft') {
                throw new Exception('Only draft surveys can be published');
            }
            
            // Check if has questions
            $questionCount = $db->count('survey_questions', 'survey_id = ?', [$surveyId]);
            if ($questionCount == 0) {
                throw new Exception('Cannot publish a survey without questions');
            }
            
            $db->update('surveys', ['status' => 'active'], 'id = ?', [$surveyId]);
            $auth->logActivity($userId, 'published_survey', 'surveys', 'surveys', $surveyId);
            
            echo json_encode(['success' => true, 'message' => 'Survey published successfully']);
            break;
            
        case 'close':
            // Close an active survey
            $surveyId = intval($_POST['id'] ?? 0);
            
            if (!$auth->can('surveys')) {
                throw new Exception('Permission denied');
            }
            
            $survey = $db->fetch("SELECT * FROM surveys WHERE id = ?", [$surveyId]);
            
            if (!$survey) {
                throw new Exception('Survey not found');
            }
            
            if ($currentRole === 'user_1' && $survey['created_by'] != $userId) {
                throw new Exception('You can only close your own surveys');
            }
            
            if ($survey['status'] !== 'active') {
                throw new Exception('Only active surveys can be closed');
            }
            
            $db->update('surveys', ['status' => 'closed'], 'id = ?', [$surveyId]);
            $auth->logActivity($userId, 'closed_survey', 'surveys', 'surveys', $surveyId);
            
            echo json_encode(['success' => true, 'message' => 'Survey closed successfully']);
            break;
            
        case 'delete':
            // Delete a survey (only if no responses)
            $surveyId = intval($_POST['id'] ?? 0);
            
            if (!$auth->can('surveys')) {
                throw new Exception('Permission denied');
            }
            
            $survey = $db->fetch("SELECT * FROM surveys WHERE id = ?", [$surveyId]);
            
            if (!$survey) {
                throw new Exception('Survey not found');
            }
            
            if ($currentRole === 'user_1' && $survey['created_by'] != $userId) {
                throw new Exception('You can only delete your own surveys');
            }
            
            // Check for responses
            $responseCount = $db->count('survey_responses', 'survey_id = ?', [$surveyId]);
            if ($responseCount > 0) {
                throw new Exception('Cannot delete a survey that has responses');
            }
            
            // Delete questions first (cascade should handle this but being explicit)
            $db->delete('survey_questions', 'survey_id = ?', [$surveyId]);
            $db->delete('surveys', 'id = ?', [$surveyId]);
            
            $auth->logActivity($userId, 'deleted_survey', 'surveys', 'surveys', $surveyId);
            
            echo json_encode(['success' => true, 'message' => 'Survey deleted successfully']);
            break;
            
        case 'view_response':
    // View individual response details
    $responseId = intval($_POST['response_id'] ?? 0);

    if (!$responseId) {
        throw new Exception('Invalid response ID');
    }

    $response = $db->fetch(
        "SELECT sr.*, s.created_by, s.is_anonymous, s.id AS survey_id
         FROM survey_responses sr
         JOIN surveys s ON sr.survey_id = s.id
         WHERE sr.id = ? AND sr.status = 'completed'",
        [$responseId]
    );

    if (!$response) {
        throw new Exception('Response not found');
    }

    $currentUserId = $auth->userId();
    $currentRole = $auth->role();

    $canView = false;

    // Allow user to view their own response
    if ((int)$response['respondent_id'] === (int)$currentUserId) {
        $canView = true;
    }

    // Allow users with surveys permission (admin/manager) to view any response
    if ($auth->can('surveys')) {
        $canView = true;
    }

    // Optional: allow survey creator with role user_1 to view responses to their own survey
    if ($currentRole === 'user_1' && (int)$response['created_by'] === (int)$currentUserId) {
        $canView = true;
    }

    if (!$canView) {
        throw new Exception('Permission denied');
    }

    $answers = $db->fetchAll(
        "SELECT q.question_text, q.question_type, q.sort_order,
                a.answer_text, a.answer_options, a.rating_value
         FROM survey_questions q
         LEFT JOIN survey_answers a
            ON a.question_id = q.id
           AND a.response_id = ?
         WHERE q.survey_id = ?
         ORDER BY q.sort_order ASC, q.id ASC",
        [$responseId, $response['survey_id']]
    );

    $data = [];

    foreach ($answers as $a) {
        $answerValue = '';

        switch ($a['question_type']) {
            case 'checkbox':
                $selected = json_decode($a['answer_options'], true);
                $answerValue = !empty($selected) ? implode(', ', $selected) : '';
                break;

            case 'radio':
            case 'dropdown':
                $answerValue = $a['answer_options'] ?? '';
                break;

            case 'rating':
                $answerValue = $a['rating_value'] ?? '';
                break;

            default:
                $answerValue = $a['answer_text'] ?? '';
                break;
        }

        $data[] = [
            'question' => $a['question_text'],
            'answer' => $answerValue
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    exit;
            
            // Check permission
            if ($currentRole === 'user_1' && $response['created_by'] != $userId) {
                throw new Exception('Permission denied');
            }
            
            // Get questions and answers
            $questions = $db->fetchAll(
                "SELECT sq.*, sa.answer_text, sa.answer_options, sa.rating_value
                 FROM survey_questions sq
                 LEFT JOIN survey_answers sa ON sq.id = sa.question_id AND sa.response_id = ?
                 WHERE sq.survey_id = ?
                 ORDER BY sq.sort_order",
                [$responseId, $response['survey_id']]
            );
            
            $data = [];
            foreach ($questions as $q) {
                $answer = '';
                switch ($q['question_type']) {
                    case 'checkbox':
                        $selected = json_decode($q['answer_options'], true) ?: [];
                        $answer = implode(', ', $selected);
                        break;
                    case 'radio':
                    case 'dropdown':
                        $answer = $q['answer_options'] ?? '';
                        break;
                    case 'rating':
                        $answer = $q['rating_value'] ? $q['rating_value'] . ' / ' . ($q['max_value'] ?? 5) : '';
                        break;
                    default:
                        $answer = $q['answer_text'] ?? '';
                }
                
                $data[] = [
                    'question' => htmlspecialchars($q['question_text']),
                    'type' => $q['question_type'],
                    'answer' => htmlspecialchars($answer)
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'get_stats':
            // Get survey statistics
            $surveyId = intval($_POST['id'] ?? 0);
            
            if (!$auth->can('surveys')) {
                throw new Exception('Permission denied');
            }
            
            $survey = $db->fetch("SELECT * FROM surveys WHERE id = ?", [$surveyId]);
            
            if (!$survey) {
                throw new Exception('Survey not found');
            }
            
            $stats = [
                'total_responses' => $db->count('survey_responses', "survey_id = ? AND status = 'completed'", [$surveyId]),
                'in_progress' => $db->count('survey_responses', "survey_id = ? AND status = 'in_progress'", [$surveyId]),
                'question_count' => $db->count('survey_questions', 'survey_id = ?', [$surveyId])
            ];
            
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}