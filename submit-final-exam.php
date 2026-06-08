<?php
// exams/submit-final-exam.php
// Auto-mark final exam and generate certificate

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit exam']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$course_id = $data['course_id'] ?? 0;
$user_answers = $data['answers'] ?? [];
$time_spent = $data['time_spent'] ?? 0;

if (!$course_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
    exit();
}

$user_id = getCurrentUserId();

// Verify exam session
if (!isset($_SESSION['final_exam_' . $course_id])) {
    echo json_encode(['success' => false, 'message' => 'Exam session expired. Please restart the exam.']);
    exit();
}

$exam_session = $_SESSION['final_exam_' . $course_id];
$start_time = $exam_session['start_time'];
$attempt_number = $exam_session['attempt_number'];

// Check time limit
$time_elapsed = time() - $start_time;
$time_limit_seconds = FINAL_EXAM_TIME_LIMIT_MINUTES * 60;

if ($time_elapsed > $time_limit_seconds) {
    echo json_encode(['success' => false, 'message' => 'Time limit exceeded. Please retake the exam.']);
    exit();
}

// Check if user already passed
$db = Database::getConnection();
$stmt = $db->prepare("
    SELECT id FROM final_exam_attempts 
    WHERE user_id = ? AND course_id = ? AND passed = 1
    LIMIT 1
");
$stmt->execute([$user_id, $course_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You have already passed this exam.']);
    exit();
}

// Check retake limit
if (!canRetakeFinalExam($user_id, $course_id)) {
    echo json_encode(['success' => false, 'message' => 'You have reached the maximum number of attempts.']);
    exit();
}

// Check all units completed
if (!areAllUnitsCompleted($user_id, $course_id)) {
    echo json_encode(['success' => false, 'message' => 'Complete all units before taking the final exam.']);
    exit();
}

// Grade the exam
$grading_result = checkFinalExamAnswers($course_id, $user_answers);

// Store attempt
$answers_json = json_encode($user_answers);
$certificate_number = null;

$stmt = $db->prepare("
    INSERT INTO final_exam_attempts 
    (user_id, course_id, attempt_number, score_percent, answers, passed, completed_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");
$stmt->execute([
    $user_id,
    $course_id,
    $attempt_number,
    $grading_result['score'],
    $answers_json,
    $grading_result['passed'] ? 1 : 0
]);

$attempt_id = $db->lastInsertId();

// Record question performance
foreach ($grading_result['results'] as $result_item) {
    recordQuestionPerformance(
        $user_id,
        $result_item['question_id'],
        'final',
        $result_item['is_correct']
    );
}

// Clear exam session
unset($_SESSION['final_exam_' . $course_id]);

// Create notification
if ($grading_result['passed']) {
    createNotification(
        $user_id,
        'Final Exam Passed! 🎉',
        'Congratulations! You passed the final exam for ' . $course_id . ' with a score of ' . $grading_result['score'] . '%. Your certificate is ready!',
        'exam'
    );
    
    // Generate certificate
    $course = getCourseById($course_id);
    $user = getUserById($user_id);
    
    $certificate_number = generateCertificateNumber();
    $verification_code = generateVerificationCode();
    
    // Generate PDF certificate (simplified - will create actual PDF in separate function)
    $certificate_filename = $certificate_number . '.pdf';
    $certificate_path = CERTIFICATE_PATH . $certificate_filename;
    
    // Store certificate in database
    $stmt = $db->prepare("
        INSERT INTO certificates (user_id, course_id, certificate_number, verification_code, final_score, pdf_url)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $course_id,
        $certificate_number,
        $verification_code,
        $grading_result['score'],
        $certificate_filename
    ]);
    
    // Update enrollment as completed
    $stmt = $db->prepare("
        UPDATE enrollments SET is_completed = 1, completed_at = NOW()
        WHERE user_id = ? AND course_id = ?
    ");
    $stmt->execute([$user_id, $course_id]);
    
    // Update attempt record with certificate number
    $stmt = $db->prepare("
        UPDATE final_exam_attempts SET certificate_generated = 1, certificate_number = ?
        WHERE id = ?
    ");
    $stmt->execute([$certificate_number, $attempt_id]);
    
    echo json_encode([
        'success' => true,
        'passed' => true,
        'score' => $grading_result['score'],
        'message' => 'Congratulations! You passed the final exam with ' . $grading_result['score'] . '%! Your certificate has been generated.',
        'certificate_number' => $certificate_number
    ]);
} else {
    createNotification(
        $user_id,
        'Final Exam Failed',
        'You scored ' . $grading_result['score'] . '% on the final exam. Required: ' . FINAL_EXAM_PASSING_SCORE . '%. You have ' . (MAX_FINAL_EXAM_RETAKES - $attempt_number) . ' attempts remaining.',
        'exam'
    );
    
    echo json_encode([
        'success' => true,
        'passed' => false,
        'score' => $grading_result['score'],
        'message' => 'You scored ' . $grading_result['score'] . '%. You need ' . FINAL_EXAM_PASSING_SCORE . '% to pass. Please review and retake the exam.',
        'results' => $grading_result['results']
    ]);
}
?>