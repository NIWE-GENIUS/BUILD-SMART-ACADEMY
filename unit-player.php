<?php
// courses/unit-player.php
// Unit player with video and test

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$unit_id = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;
$user_id = getCurrentUserId();

if (!$course_id || !$unit_id) {
    redirect('dashboard/my-courses.php');
}

// Check enrollment
if (!isEnrolled($user_id, $course_id)) {
    redirect('courses/course-details.php?id=' . $course_id);
}

// Get unit details
$db = Database::getConnection();
$stmt = $db->prepare("
    SELECT u.*, c.title as course_title 
    FROM course_units u
    JOIN courses c ON u.course_id = c.id
    WHERE u.id = ? AND u.course_id = ?
");
$stmt->execute([$unit_id, $course_id]);
$unit = $stmt->fetch();

if (!$unit) {
    redirect('courses/course-details.php?id=' . $course_id);
}

// Get user progress for this unit
$stmt = $db->prepare("
    SELECT status, best_score, attempts FROM user_unit_progress 
    WHERE user_id = ? AND course_id = ? AND unit_id = ?
");
$stmt->execute([$user_id, $course_id, $unit_id]);
$progress = $stmt->fetch();

$is_locked = $progress && $progress['status'] === 'locked';
$is_completed = $progress && $progress['status'] === 'completed';

// Check if previous units are completed
if ($is_locked) {
    // Check previous unit
    $stmt = $db->prepare("
        SELECT status FROM user_unit_progress 
        WHERE user_id = ? AND course_id = ? AND unit_id = (
            SELECT id FROM course_units 
            WHERE course_id = ? AND unit_number < ? 
            ORDER BY unit_number DESC LIMIT 1
        )
    ");
    $stmt->execute([$user_id, $course_id, $course_id, $unit['unit_number']]);
    $prev = $stmt->fetch();
    
    if (!$prev || $prev['status'] === 'completed') {
        // Unlock this unit
        $stmt = $db->prepare("
            UPDATE user_unit_progress SET status = 'in_progress'
            WHERE user_id = ? AND course_id = ? AND unit_id = ?
        ");
        $stmt->execute([$user_id, $course_id, $unit_id]);
        $is_locked = false;
    }
}

// Check if test has been passed (mark in session for quiz results)
$test_passed = isset($_SESSION['unit_test_passed_' . $unit_id]) && $_SESSION['unit_test_passed_' . $unit_id] === true;

if ($test_passed && !$is_completed) {
    // Mark unit as completed
    $stmt = $db->prepare("
        UPDATE user_unit_progress SET status = 'completed', completed_at = NOW()
        WHERE user_id = ? AND course_id = ? AND unit_id = ?
    ");
    $stmt->execute([$user_id, $course_id, $unit_id]);
    $is_completed = true;
    
    // Award badge
    awardBadge($user_id, 'Unit ' . $unit['unit_number'] . ' Champion', $course_id, $unit_id);
    
    // Clear session flag
    unset($_SESSION['unit_test_passed_' . $unit_id]);
}

$page_title = $unit['title'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo htmlspecialchars($unit['title']); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .player-container {
            display: flex;
            min-height: calc(100vh - 150px);
        }
        
        .video-section {
            flex: 3;
            background: #1a1a2e;
            padding: 20px;
        }
        
        .video-container {
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .video-container video {
            width: 100%;
            display: block;
        }
        
        .unit-info h1 {
            color: white;
            font-size: 1.3rem;
            margin-bottom: 10px;
        }
        
        .download-link {
            color: var(--orange);
            text-decoration: none;
        }
        
        .sidebar {
            flex: 1;
            background: white;
            border-left: 1px solid #eee;
            padding: 20px;
        }
        
        .unit-list {
            list-style: none;
            padding: 0;
        }
        
        .unit-list li {
            padding: 12px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .unit-list li.completed {
            color: #27ae60;
        }
        
        .unit-list li.active {
            background: #fff8e1;
            border-left: 3px solid var(--orange);
        }
        
        .unit-list li.locked {
            color: #ccc;
        }
        
        .test-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        
        .btn-test {
            background: var(--orange);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
        }
        
        .completed-badge {
            background: #27ae60;
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .player-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="player-container">
        <div class="video-section">
            <div class="video-container">
                <?php if ($unit['video_url']): ?>
                    <video controls poster="<?php echo SITE_URL; ?>assets/images/video-placeholder.jpg">
                        <source src="<?php echo htmlspecialchars($unit['video_url']); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                <?php else: ?>
                    <div style="background: #333; height: 300px; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-video" style="font-size: 48px;"></i>
                        <p style="margin-left: 15px;">Video content will be available soon</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="unit-info">
                <h1><?php echo htmlspecialchars($unit['title']); ?></h1>
                
                <?php if ($unit['document_url']): ?>
                    <a href="<?php echo htmlspecialchars($unit['document_url']); ?>" class="download-link" download>
                        <i class="fas fa-download"></i> Download Materials
                    </a>
                <?php endif; ?>
                
                <?php if (!$is_completed && !$test_passed && !$is_locked): ?>
                    <div class="test-section">
                        <button id="startTestBtn" class="btn-test">
                            <i class="fas fa-question-circle"></i> Take Unit Test (70% to pass)
                        </button>
                        <p style="font-size: 12px; color: #666; margin-top: 10px;">
                            Time limit: <?php echo UNIT_TEST_TIME_LIMIT_MINUTES; ?> minutes | <?php echo UNIT_TEST_PASSING_SCORE; ?>% required to pass
                        </p>
                    </div>
                <?php elseif ($is_completed): ?>
                    <div class="completed-badge">
                        <i class="fas fa-check-circle"></i> Unit Completed!
                        <div>Your score: <?php echo $progress['best_score']; ?>%</div>
                        <?php if ($next_unit = getNextUnit($course_id, $unit['unit_number'])): ?>
                            <a href="unit-player.php?course_id=<?php echo $course_id; ?>&unit_id=<?php echo $next_unit['id']; ?>" style="color: white; display: inline-block; margin-top: 10px;">
                                Next Unit →
                            </a>
                        <?php else: ?>
                            <a href="course-details.php?id=<?php echo $course_id; ?>" style="color: white; display: inline-block; margin-top: 10px;">
                                View Course Details →
                            </a>
                        <?php endif; ?>
                    </div>
                <?php elseif ($is_locked): ?>
                    <div class="completed-badge" style="background: #e74c3c;">
                        <i class="fas fa-lock"></i> Unit Locked
                        <div>Complete previous unit first</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="sidebar">
            <h3>Course Units</h3>
            <ul class="unit-list">
                <?php
                $stmt = $db->prepare("SELECT id, unit_number, title FROM course_units WHERE course_id = ? ORDER BY unit_number ASC");
                $stmt->execute([$course_id]);
                $all_units = $stmt->fetchAll();
                
                foreach ($all_units as $u):
                    $u_progress = $user_progress ?? [];
                    $u_status = 'locked';
                    $u_class = 'locked';
                    
                    if ($u['id'] == $unit_id) {
                        $u_class = 'active';
                        $u_status = 'active';
                    } elseif (($user_progress[$u['id']]['status'] ?? 'locked') === 'completed') {
                        $u_class = 'completed';
                        $u_status = 'completed';
                    }
                ?>
                    <li class="<?php echo $u_class; ?>">
                        <?php if ($u_class === 'completed'): ?>
                            <i class="fas fa-check-circle"></i>
                        <?php elseif ($u_class === 'active'): ?>
                            <i class="fas fa-play-circle"></i>
                        <?php else: ?>
                            <i class="fas fa-lock"></i>
                        <?php endif; ?>
                        Unit <?php echo $u['unit_number']; ?>: <?php echo htmlspecialchars($u['title']); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Test Modal -->
    <div id="testModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; overflow-y: auto;">
        <div style="max-width: 800px; margin: 50px auto; background: white; border-radius: 15px; padding: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Unit Test: <?php echo htmlspecialchars($unit['title']); ?></h2>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            
            <div id="testTimer" style="background: #f0f0f0; padding: 10px; border-radius: 8px; text-align: center; margin-bottom: 20px;">
                Time Remaining: <span id="timer">01:00:00</span>
            </div>
            
            <div id="testQuestions"></div>
            
            <button id="submitTest" class="btn-test" style="margin-top: 20px;">Submit Test</button>
            <div id="testResult" style="margin-top: 20px;"></div>
        </div>
    </div>

    <script>
        // Load test questions
        document.getElementById('startTestBtn')?.addEventListener('click', async function() {
            const modal = document.getElementById('testModal');
            modal.style.display = 'block';
            
            // Load questions
            const response = await fetch('<?php echo SITE_URL; ?>exams/load-unit-test.php?unit_id=<?php echo $unit_id; ?>');
            const data = await response.json();
            
            if (data.success) {
                displayQuestions(data.questions);
                startTimer(<?php echo UNIT_TEST_TIME_LIMIT_MINUTES * 60; ?>);
            } else {
                document.getElementById('testQuestions').innerHTML = '<p style="color: red;">Error loading questions.</p>';
            }
        });
        
        function displayQuestions(questions) {
            let html = '';
            questions.forEach((q, index) => {
                html += `
                    <div class="question" style="margin-bottom: 25px; padding: 15px; border: 1px solid #eee; border-radius: 8px;">
                        <p><strong>Question ${index + 1}:</strong> ${q.question_text}</p>
                        <div style="margin-left: 20px;">
                            <label><input type="radio" name="q_${q.id}" value="A"> ${q.option_a}</label><br>
                            <label><input type="radio" name="q_${q.id}" value="B"> ${q.option_b}</label><br>
                            <label><input type="radio" name="q_${q.id}" value="C"> ${q.option_c}</label><br>
                            <label><input type="radio" name="q_${q.id}" value="D"> ${q.option_d}</label>
                        </div>
                    </div>
                `;
            });
            document.getElementById('testQuestions').innerHTML = html;
        }
        
        let timerInterval;
        let timeLeft = <?php echo UNIT_TEST_TIME_LIMIT_MINUTES * 60; ?>;
        
        function startTimer(seconds) {
            timeLeft = seconds;
            updateTimerDisplay();
            
            timerInterval = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    document.getElementById('submitTest').click();
                } else {
                    timeLeft--;
                    updateTimerDisplay();
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
        
        document.getElementById('submitTest')?.addEventListener('click', async function() {
            clearInterval(timerInterval);
            
            // Collect answers
            const answers = {};
            document.querySelectorAll('.question').forEach(question => {
                const radio = question.querySelector('input[type="radio"]:checked');
                if (radio) {
                    const name = radio.name;
                    answers[name.split('_')[1]] = radio.value;
                }
            });
            
            const response = await fetch('<?php echo SITE_URL; ?>exams/submit-unit-test.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    unit_id: <?php echo $unit_id; ?>,
                    answers: answers,
                    time_spent: <?php echo UNIT_TEST_TIME_LIMIT_MINUTES * 60; ?> - timeLeft
                })
            });
            
            const result = await response.json();
            const resultDiv = document.getElementById('testResult');
            
            if (result.passed) {
                resultDiv.innerHTML = `
                    <div style="background: #27ae60; color: white; padding: 20px; border-radius: 8px;">
                        <i class="fas fa-trophy"></i> ${result.message}
                        <p>Your score: ${result.score}%</p>
                        <button onclick="location.reload()" class="btn-test" style="margin-top: 15px;">Continue to Next Unit →</button>
                    </div>
                `;
            } else {
                let incorrectHtml = '<div style="background: #e74c3c; color: white; padding: 15px; border-radius: 8px; margin-bottom: 15px;">' + result.message + '</div>';
                incorrectHtml += '<h4>Review your answers:</h4>';
                result.results.forEach(r => {
                    if (!r.is_correct) {
                        incorrectHtml += `
                            <div style="margin-bottom: 15px; padding: 10px; border-left: 3px solid #e74c3c; background: #fff8e1;">
                                <strong>Question:</strong> ${r.question_text}<br>
                                <strong>Your answer:</strong> ${r.user_answer || 'Not answered'}<br>
                                <strong>Correct answer:</strong> ${r.correct_answer}<br>
                                <strong>Explanation:</strong> ${r.explanation || 'Review the material and try again.'}
                            </div>
                        `;
                    }
                });
                incorrectHtml += '<button onclick="location.reload()" class="btn-test" style="margin-top: 15px;">Retake Test</button>';
                resultDiv.innerHTML = incorrectHtml;
            }
        });
        
        function closeModal() {
            document.getElementById('testModal').style.display = 'none';
            if (timerInterval) clearInterval(timerInterval);
        }
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>