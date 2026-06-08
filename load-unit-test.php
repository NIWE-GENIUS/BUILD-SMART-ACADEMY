<?php
// exams/load-unit-test.php
// Load random questions for unit test (15 from 50 bank)

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to take tests']);
    exit();
}

$unit_id = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;

if (!$unit_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid unit ID']);
    exit();
}

$user_id = getCurrentUserId();

// Check if user is enrolled in the course
$db = Database::getConnection();
$stmt = $db->prepare("
    SELECT c.course_id FROM course_units cu
    JOIN enrollments e ON e.course_id = cu.course_id
    WHERE cu.id = ? AND e.user_id = ?
");
$stmt->execute([$unit_id, $user_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You are not enrolled in this course']);
    exit();
}

// Get random questions
$questions = getRandomUnitQuestions($unit_id);

if (count($questions) === 0) {
    echo json_encode(['success' => false, 'message' => 'No questions available for this unit']);
    exit();
}

// Remove correct answers from response (for security)
foreach ($questions as &$q) {
    unset($q['correct_answer']);
    unset($q['explanation']);
}

// Store questions in session for verification when submitting
$_SESSION['unit_test_' . $unit_id] = [
    'questions' => $questions,
    'start_time' => time()
];

echo json_encode([
    'success' => true,
    'questions' => $questions,
    'time_limit' => UNIT_TEST_TIME_LIMIT_MINUTES
]);
?>