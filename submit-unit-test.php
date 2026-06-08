<?php
// exams/submit-unit-test.php
// Auto-mark unit test and update progress

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit tests']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$unit_id = $data['unit_id'] ?? 0;
$user_answers = $data['answers'] ?? [];
$time_spent = $data['time_spent'] ?? 0;

if (!$unit_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid unit ID']);
    exit();
}

$user_id = getCurrentUserId();

// Verify this test was started
if (!isset($_SESSION['unit_test_' . $unit_id])) {
    echo json_encode(['success' => false, 'message' => 'Test session expired. Please reload and try again.']);
    exit();
}

// Check time limit
$start_time = $_SESSION['unit_test_' . $unit_id]['start_time'];
$time_elapsed = time() - $start_time;
$time_limit_seconds = UNIT_TEST_TIME_LIMIT_MINUTES * 60;

if ($time_elapsed > $time_limit_seconds) {
    echo json_encode(['success' => false, 'message' => 'Time limit exceeded. Please retake the test.']);
    exit();
}

// Get unit details
$db = Database::getConnection();
$stmt = $db->prepare("
    SELECT cu.*, c.id as course_id 
    FROM course_units cu
    JOIN courses c ON cu.course_id = c.id
    WHERE cu.id = ?
");
$stmt->execute([$unit_id]);
$unit = $stmt->fetch();

if (!$unit) {
    echo json_encode(['success' => false, 'message' => 'Unit not found']);
    exit();
}

// Check and grade answers
$grading_result = checkUnitTestAnswers($unit_id, $user_answers);

// Store attempt
$attempt_number = 1;
$stmt = $db->prepare("
    SELECT MAX(attempt_number) as max_attempt FROM unit_test_attempts 
    WHERE user_id = ? AND unit_id = ?
");
$stmt->execute([$user_id, $unit_id]);
$result = $stmt->fetch();
if ($result && $result['max_attempt']) {
    $attempt_number = $result['max_attempt'] + 1;
}

$answers_json = json_encode($user_answers);

$stmt = $db->prepare("
    INSERT INTO unit_test_attempts (user_id, unit_id, attempt_number, score_percent, answers, passed, completed_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");
$stmt->execute([
    $user_id, 
    $unit_id, 
    $attempt_number, 
    $grading_result['score'], 
    $answers_json, 
    $grading_result['passed'] ? 1 : 0
]);

// Update user progress
$stmt = $db->prepare("
    UPDATE user_unit_progress 
    SET attempts = attempts + 1, 
        best_score = GREATEST(best_score, ?),
        status = CASE WHEN ? = 1 THEN 'completed' ELSE 'failed' END
    WHERE user_id = ? AND course_id = ? AND unit_id = ?
");
$stmt->execute([
    $grading_result['score'],
    $grading_result['passed'] ? 1 : 0,
    $user_id,
    $unit['course_id'],
    $unit_id
]);

// Record question performance for weighted randomization
foreach ($grading_result['results'] as $result_item) {
    recordQuestionPerformance(
        $user_id, 
        $result_item['question_id'], 
        'unit', 
        $result_item['is_correct']
    );
}

// Clear session data
unset($_SESSION['unit_test_' . $unit_id]);

// Create notification based on result
if ($grading_result['passed']) {
    createNotification(
        $user_id, 
        'Unit Test Passed!', 
        'You passed the test for Unit ' . $unit['unit_number'] . ' with a score of ' . $grading_result['score'] . '%',
        'test'
    );
    
    // Set session flag for unit completion
    $_SESSION['unit_test_passed_' . $unit_id] = true;
    
    echo json_encode([
        'success' => true,
        'passed' => true,
        'score' => $grading_result['score'],
        'message' => 'Congratulations! You passed the unit test with ' . $grading_result['score'] . '%!',
        'results' => $grading_result['results']
    ]);
} else {
    createNotification(
        $user_id, 
        'Unit Test Failed', 
        'You scored ' . $grading_result['score'] . '% on Unit ' . $unit['unit_number'] . '. Required: ' . UNIT_TEST_PASSING_SCORE . '%. Please review and retake.',
        'test'
    );
    
    echo json_encode([
        'success' => true,
        'passed' => false,
        'score' => $grading_result['score'],
        'message' => 'You scored ' . $grading_result['score'] . '%. You need ' . UNIT_TEST_PASSING_SCORE . '% to pass. Please review the material and retake the test.',
        'results' => $grading_result['results']
    ]);
}
?>