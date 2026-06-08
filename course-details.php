<?php
// courses/course-details.php
// Course Details Page with Units List

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$course_id) {
    redirect('courses/');
}

// Get course details
$db = Database::getConnection();
$stmt = $db->prepare("
    SELECT c.*, u.full_name as instructor_name 
    FROM courses c
    LEFT JOIN users u ON c.instructor_id = u.id
    WHERE c.id = ? AND c.status = 'published'
");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    redirect('courses/');
}

$user_id = getCurrentUserId();
$is_enrolled = isLoggedIn() ? isEnrolled($user_id, $course_id) : false;
$is_lifetime_free = isLoggedIn() ? hasLifetimeFree() : false;
$needs_payment = !$is_enrolled && $course['is_paid'] && !$is_lifetime_free;

// Get course units
$stmt = $db->prepare("
    SELECT * FROM course_units 
    WHERE course_id = ? 
    ORDER BY unit_number ASC
");
$stmt->execute([$course_id]);
$units = $stmt->fetchAll();

// Get user progress if enrolled
$user_progress = [];
if ($is_enrolled) {
    $stmt = $db->prepare("
        SELECT unit_id, status, completed_at, best_score 
        FROM user_unit_progress 
        WHERE user_id = ? AND course_id = ?
    ");
    $stmt->execute([$user_id, $course_id]);
    $user_progress = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

$page_title = $course['title'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .course-header {
            background: linear-gradient(135deg, var(--blue), var(--orange));
            color: white;
            padding: 50px 0;
            margin-bottom: 30px;
        }
        
        .course-header h1 {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .course-price {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 15px 0;
        }
        
        .free-badge {
            background: #27ae60;
            padding: 5px 15px;
            border-radius: 25px;
            display: inline-block;
        }
        
        .enroll-btn {
            background: white;
            color: var(--orange);
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }
        
        .course-content {
            max-width: 1000px;
            margin: 0 auto 50px;
            padding: 0 20px;
        }
        
        .course-info-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section-title {
            font-size: 1.3rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .units-list {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .unit-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            transition: background 0.3s;
        }
        
        .unit-item:hover {
            background: #f9f9f9;
        }
        
        .unit-number {
            width: 40px;
            height: 40px;
            background: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .unit-info {
            flex: 1;
        }
        
        .unit-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .unit-duration {
            font-size: 12px;
            color: #888;
        }
        
        .unit-status {
            width: 80px;
            text-align: right;
        }
        
        .status-completed {
            color: #27ae60;
        }
        
        .status-locked {
            color: #e74c3c;
        }
        
        .status-in-progress {
            color: var(--orange);
        }
        
        .final-exam-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .final-exam-btn {
            background: var(--orange);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
        }
        
        .final-exam-locked {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="course-header">
        <div class="container">
            <h1><?php echo htmlspecialchars($course['title']); ?></h1>
            <p><?php echo htmlspecialchars($course['description']); ?></p>
            
            <div class="course-price">
                <?php if ($course['is_paid'] && !$is_lifetime_free): ?>
                    <?php echo number_format($course['price']); ?> RWF
                <?php else: ?>
                    <span class="free-badge">FREE</span>
                <?php endif; ?>
            </div>
            
            <?php if (!$is_enrolled && isLoggedIn()): ?>
                <?php if ($needs_payment): ?>
                    <a href="<?php echo SITE_URL; ?>payment/checkout.php?course_id=<?php echo $course_id; ?>" class="enroll-btn">
                        <i class="fas fa-shopping-cart"></i> Enroll Now
                    </a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>courses/enroll.php?id=<?php echo $course_id; ?>" class="enroll-btn">
                        <i class="fas fa-graduation-cap"></i> Enroll for Free
                    </a>
                <?php endif; ?>
            <?php elseif (!$is_enrolled && !isLoggedIn()): ?>
                <a href="<?php echo SITE_URL; ?>auth/register.php" class="enroll-btn">
                    <i class="fas fa-user-plus"></i> Register to Enroll
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="course-content">
        <div class="course-info-card">
            <div class="section-title">About This Course</div>
            <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
            
            <div style="display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap;">
                <div><i class="fas fa-user"></i> Instructor: <?php echo htmlspecialchars($course['instructor_name'] ?? 'BUILD SMART ACADEMY'); ?></div>
                <div><i class="fas fa-layer-group"></i> Units: <?php echo count($units); ?></div>
                <div><i class="fas fa-certificate"></i> Certificate upon completion</div>
            </div>
        </div>
        
        <div class="section-title">Course Curriculum</div>
        <div class="units-list">
            <?php foreach ($units as $index => $unit): 
                $status = $user_progress[$unit['id']] ?? 'locked';
                $status_text = '';
                $status_class = '';
                
                if ($status === 'completed') {
                    $status_text = 'Completed';
                    $status_class = 'status-completed';
                } elseif ($status === 'in_progress') {
                    $status_text = 'In Progress';
                    $status_class = 'status-in-progress';
                } else {
                    $status_text = 'Locked';
                    $status_class = 'status-locked';
                }
            ?>
                <div class="unit-item">
                    <div class="unit-number"><?php echo $unit['unit_number']; ?></div>
                    <div class="unit-info">
                        <div class="unit-title"><?php echo htmlspecialchars($unit['title']); ?></div>
                        <?php if ($unit['duration_minutes']): ?>
                            <div class="unit-duration"><i class="far fa-clock"></i> <?php echo $unit['duration_minutes']; ?> minutes</div>
                        <?php endif; ?>
                    </div>
                    <div class="unit-status <?php echo $status_class; ?>">
                        <?php if ($is_enrolled && $status !== 'locked'): ?>
                            <a href="<?php echo SITE_URL; ?>courses/unit-player.php?course_id=<?php echo $course_id; ?>&unit_id=<?php echo $unit['id']; ?>" style="color: inherit; text-decoration: none;">
                                <?php echo $status_text; ?> →
                            </a>
                        <?php else: ?>
                            <?php echo $status_text; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($is_enrolled): ?>
            <?php
            // Check if all units are completed
            $all_units_completed = true;
            foreach ($units as $unit) {
                if (($user_progress[$unit['id']] ?? 'locked') !== 'completed') {
                    $all_units_completed = false;
                    break;
                }
            }
            
            // Check if final exam is already passed
            $stmt = $db->prepare("
                SELECT passed, attempt_number FROM final_exam_attempts 
                WHERE user_id = ? AND course_id = ? AND passed = TRUE
                LIMIT 1
            ");
            $stmt->execute([$user_id, $course_id]);
            $exam_passed = $stmt->fetch();
            
            // Get attempt count
            $stmt = $db->prepare("
                SELECT COUNT(*) as attempts FROM final_exam_attempts 
                WHERE user_id = ? AND course_id = ?
            ");
            $stmt->execute([$user_id, $course_id]);
            $attempt_count = $stmt->fetch()['attempts'];
            $can_retake = $attempt_count < MAX_FINAL_EXAM_RETAKES;
            ?>
            
            <div class="final-exam-section">
                <h3><i class="fas fa-check-double"></i> Final Examination</h3>
                <p>Complete all units to take the final exam. You need 80% to pass and earn your certificate.</p>
                <p>Maximum attempts: <?php echo MAX_FINAL_EXAM_RETAKES; ?> | Time limit: <?php echo FINAL_EXAM_TIME_LIMIT_MINUTES; ?> minutes</p>
                
                <?php if ($exam_passed): ?>
                    <div style="background: #e8f5e9; padding: 15px; border-radius: 10px; margin: 15px 0;">
                        <i class="fas fa-trophy" style="color: #27ae60;"></i>
                        <strong>Congratulations! You have passed the final exam and earned your certificate!</strong>
                        <br>
                        <a href="<?php echo SITE_URL; ?>dashboard/my-certificates.php" style="color: var(--orange);">View Certificate →</a>
                    </div>
                <?php elseif ($all_units_completed): ?>
                    <a href="<?php echo SITE_URL; ?>exams/take-final-exam.php?course_id=<?php echo $course_id; ?>" class="final-exam-btn">
                        <i class="fas fa-play"></i> Take Final Exam
                    </a>
                    <?php if (!$can_retake && $attempt_count > 0): ?>
                        <p style="color: red; margin-top: 10px;">You have used all <?php echo MAX_FINAL_EXAM_RETAKES; ?> attempts. Contact admin for assistance.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <div>
                        <button class="final-exam-btn final-exam-locked" disabled>
                            <i class="fas fa-lock"></i> Complete all units first
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>