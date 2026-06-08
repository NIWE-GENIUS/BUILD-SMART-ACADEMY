<?php
// exams/take-final-exam.php
// Final exam page with weighted random questions

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$user_id = getCurrentUserId();

if (!$course_id) {
    redirect('dashboard/my-courses.php');
}

// Check if user is enrolled
if (!isEnrolled($user_id, $course_id)) {
    redirect('courses/course-details.php?id=' . $course_id);
}

// Check if all units are completed
if (!areAllUnitsCompleted($user_id, $course_id)) {
    createNotification($user_id, 'Final Exam Locked', 'Complete all units before taking the final exam.', 'warning');
    redirect('courses/course-details.php?id=' . $course_id);
}

// Check if already passed
$db = Database::getConnection();
$stmt = $db->prepare("
    SELECT id, passed, score_percent FROM final_exam_attempts 
    WHERE user_id = ? AND course_id = ? AND passed = 1
    LIMIT 1
");
$stmt->execute([$user_id, $course_id]);
$already_passed = $stmt->fetch();

if ($already_passed) {
    createNotification($user_id, 'Exam Already Passed', 'You have already passed the final exam with ' . $already_passed['score_percent'] . '%.', 'info');
    redirect('courses/course-details.php?id=' . $course_id);
}

// Check retake limits
if (!canRetakeFinalExam($user_id, $course_id)) {
    $attempt_count = getFinalExamAttemptCount($user_id, $course_id);
    createNotification($user_id, 'Exam Attempts Exhausted', 'You have used all ' . MAX_FINAL_EXAM_RETAKES . ' attempts. Contact admin for assistance.', 'warning');
    redirect('courses/course-details.php?id=' . $course_id);
}

// Get course details
$course = getCourseById($course_id);

if (!$course) {
    redirect('courses/');
}

// Get weighted random questions
$questions = getWeightedFinalExamQuestions($user_id, $course_id);

if (count($questions) === 0) {
    die('No questions available for this exam. Please contact admin.');
}

// Store exam session
$_SESSION['final_exam_' . $course_id] = [
    'questions' => $questions,
    'start_time' => time(),
    'attempt_number' => getFinalExamAttemptCount($user_id, $course_id) + 1
];

$page_title = 'Final Exam - ' . $course['title'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .exam-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .exam-header {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 70px;
            z-index: 100;
        }
        
        .exam-title {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }
        
        .exam-timer {
            background: linear-gradient(135deg, var(--orange), var(--blue));
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .exam-timer.warning {
            background: #e74c3c;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .question-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .question-number {
            background: var(--orange);
            color: white;
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-bottom: 15px;
        }
        
        .question-text {
            font-size: 1rem;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .options {
            margin-left: 20px;
        }
        
        .option {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .option input {
            margin: 0;
        }
        
        .submit-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-top: 20px;
            margin-bottom: 50px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .btn-submit {
            background: var(--orange);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .btn-submit:hover {
            background: #e55a2b;
        }
        
        .progress-bar {
            background: #eee;
            border-radius: 10px;
            height: 8px;
            margin-top: 15px;
            overflow: hidden;
        }
        
        .progress-fill {
            background: var(--orange);
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
        
        .info-text {
            text-align: center;
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .exam-header {
                top: 60px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="exam-container">
        <div class="exam-header">
            <div class="exam-title">
                <i class="fas fa-graduation-cap"></i> Final Exam: <?php echo htmlspecialchars($course['title']); ?>
            </div>
            <div class="exam-timer" id="examTimer">
                Time Remaining: <span id="timer">02:00:00</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="info-text">
                <i class="fas fa-info-circle"></i> You need <?php echo FINAL_EXAM_PASSING_SCORE; ?>% to pass. 
                Maximum attempts: <?php echo MAX_FINAL_EXAM_RETAKES; ?> | Cooldown: <?php echo FINAL_EXAM_COOLDOWN_HOURS; ?> hours
            </div>
        </div>
        
        <form id="examForm">
            <div id="questionsContainer"></div>
            
            <div class="submit-section">
                <button type="submit" class="btn-submit" id="submitExam">
                    <i class="fas fa-check-circle"></i> Submit Final Exam
                </button>
            </div>
        </form>
    </div>

    <script>
        const questions = <?php echo json_encode($questions); ?>;
        let timeLeft = <?php echo FINAL_EXAM_TIME_LIMIT_MINUTES * 60; ?>;
        let timerInterval;
        let answers = {};
        
        // Display questions
        function displayQuestions() {
            const container = document.getElementById('questionsContainer');
            let html = '';
            
            questions.forEach((q, index) => {
                html += `
                    <div class="question-card" data-qid="${q.id}">
                        <div class="question-number">Question ${index + 1} of ${questions.length}</div>
                        <div class="question-text">${escapeHtml(q.question_text)}</div>
                        <div class="options">
                            <label class="option">
                                <input type="radio" name="q_${q.id}" value="A" onchange="saveAnswer(${q.id}, 'A')">
                                A) ${escapeHtml(q.option_a)}
                            </label>
                            <label class="option">
                                <input type="radio" name="q_${q.id}" value="B" onchange="saveAnswer(${q.id}, 'B')">
                                B) ${escapeHtml(q.option_b)}
                            </label>
                            <label class="option">
                                <input type="radio" name="q_${q.id}" value="C" onchange="saveAnswer(${q.id}, 'C')">
                                C) ${escapeHtml(q.option_c)}
                            </label>
                            <label class="option">
                                <input type="radio" name="q_${q.id}" value="D" onchange="saveAnswer(${q.id}, 'D')">
                                D) ${escapeHtml(q.option_d)}
                            </label>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
            updateProgress();
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function saveAnswer(questionId, answer) {
            answers[questionId] = answer;
            updateProgress();
        }
        
        function updateProgress() {
            const answered = Object.keys(answers).length;
            const percentage = (answered / questions.length) * 100;
            document.getElementById('progressFill').style.width = percentage + '%';
        }
        
        // Timer
        function startTimer() {
            updateTimerDisplay();
            
            timerInterval = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    document.getElementById('examTimer').innerHTML = '<div style="text-align: center;">Time\'s Up! Submitting automatically...</div>';
                    document.getElementById('submitExam').click();
                } else {
                    timeLeft--;
                    updateTimerDisplay();
                    
                    // Warning colors
                    const timerDiv = document.getElementById('examTimer');
                    if (timeLeft <= 300) { // 5 minutes
                        timerDiv.classList.add('warning');
                    }
                }
            }, 1000);
        }
        
        function updateTimerDisplay() {
            const hours = Math.floor(timeLeft / 3600);
            const minutes = Math.floor((timeLeft % 3600) / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timer').textContent = 
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        
        // Submit exam
        document.getElementById('examForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Confirm submission
            const answered = Object.keys(answers).length;
            const unanswered = questions.length - answered;
            
            if (unanswered > 0) {
                if (!confirm(`You have ${unanswered} unanswered questions. Submit anyway?`)) {
                    return;
                }
            }
            
            if (!confirm('Are you sure you want to submit your final exam? This action cannot be undone.')) {
                return;
            }
            
            const submitBtn = document.getElementById('submitExam');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            clearInterval(timerInterval);
            
            const response = await fetch('<?php echo SITE_URL; ?>exams/submit-final-exam.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    course_id: <?php echo $course_id; ?>,
                    answers: answers,
                    time_spent: <?php echo FINAL_EXAM_TIME_LIMIT_MINUTES * 60; ?> - timeLeft
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (result.passed) {
                    alert('🎉 ' + result.message);
                    window.location.href = '<?php echo SITE_URL; ?>dashboard/my-certificates.php';
                } else {
                    alert('❌ ' + result.message);
                    window.location.href = '<?php echo SITE_URL; ?>courses/course-details.php?id=<?php echo $course_id; ?>';
                }
            } else {
                alert('Error: ' + result.message);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Final Exam';
                startTimer();
            }
        });
        
        // Start the exam
        displayQuestions();
        startTimer();
        
        // Warn before leaving page
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = 'Your exam progress will be lost. Are you sure you want to leave?';
            return 'Your exam progress will be lost. Are you sure you want to leave?';
        });
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>