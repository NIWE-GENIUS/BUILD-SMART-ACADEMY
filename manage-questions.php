<?php
// admin/manage-questions.php
// Manage Unit Questions and Final Exam Questions

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('dashboard/');
}

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
if (!$course_id) {
    redirect('admin/courses.php');
}

$db = Database::getConnection();

// Get course details
$stmt = $db->prepare("SELECT title FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    redirect('admin/courses.php');
}

// Get course units
$stmt = $db->prepare("SELECT * FROM course_units WHERE course_id = ? ORDER BY unit_number ASC");
$stmt->execute([$course_id]);
$units = $stmt->fetchAll();

// Handle question addition
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_type = $_POST['question_type'] ?? '';
    $unit_id = intval($_POST['unit_id'] ?? 0);
    $question_text = sanitizeInput($_POST['question_text'] ?? '');
    $option_a = sanitizeInput($_POST['option_a'] ?? '');
    $option_b = sanitizeInput($_POST['option_b'] ?? '');
    $option_c = sanitizeInput($_POST['option_c'] ?? '');
    $option_d = sanitizeInput($_POST['option_d'] ?? '');
    $correct_answer = $_POST['correct_answer'] ?? '';
    $explanation = sanitizeInput($_POST['explanation'] ?? '');
    $points = intval($_POST['points'] ?? 1);
    
    if ($question_type === 'unit' && $unit_id) {
        $stmt = $db->prepare("
            INSERT INTO unit_questions (unit_id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, points)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$unit_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $explanation, $points]);
        $success = 'Unit question added.';
    } elseif ($question_type === 'final') {
        $stmt = $db->prepare("
            INSERT INTO final_exam_questions (course_id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, points)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$course_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $explanation, $points]);
        $success = 'Final exam question added.';
    }
}

// Delete question
if (isset($_GET['delete_unit_q'])) {
    $qid = intval($_GET['delete_unit_q']);
    $stmt = $db->prepare("DELETE FROM unit_questions WHERE id = ?");
    $stmt->execute([$qid]);
    redirect("admin/manage-questions.php?course_id=$course_id");
}

if (isset($_GET['delete_final_q'])) {
    $qid = intval($_GET['delete_final_q']);
    $stmt = $db->prepare("DELETE FROM final_exam_questions WHERE id = ?");
    $stmt->execute([$qid]);
    redirect("admin/manage-questions.php?course_id=$course_id");
}

// Get counts
$stmt = $db->prepare("SELECT COUNT(*) as total FROM final_exam_questions WHERE course_id = ?");
$stmt->execute([$course_id]);
$final_question_count = $stmt->fetch()['total'];

$page_title = 'Manage Questions';
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
        .admin-container {
            display: flex;
            min-height: calc(100vh - 150px);
        }
        
        .admin-sidebar {
            width: 260px;
            background: #2C3E50;
            color: white;
            flex-shrink: 0;
        }
        
        .admin-sidebar .nav {
            list-style: none;
            padding: 0;
        }
        
        .admin-sidebar .nav li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 25px;
            color: #ecf0f1;
            text-decoration: none;
        }
        
        .admin-sidebar .nav li a:hover, .admin-sidebar .nav li a.active {
            background: var(--orange);
        }
        
        .admin-main {
            flex: 1;
            background: #f5f6fa;
            padding: 25px;
        }
        
        .section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .options-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .btn-add {
            background: var(--orange);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .question-list {
            margin-top: 20px;
        }
        
        .question-item {
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .question-text {
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .question-meta {
            font-size: 12px;
            color: #666;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
            padding: 4px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
        }
        
        .progress-bar {
            background: #eee;
            border-radius: 10px;
            height: 10px;
            margin: 10px 0;
            overflow: hidden;
        }
        
        .progress-fill {
            background: var(--orange);
            height: 100%;
            width: 0%;
        }
        
        .success {
            background: #27ae60;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .info {
            background: #17a2b8;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="admin-container">
        <div class="admin-sidebar">
            <ul class="nav">
                <li><a href="<?php echo SITE_URL; ?>admin/"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/courses.php"><i class="fas fa-book"></i> Courses</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/manage-questions.php?course_id=<?php echo $course_id; ?>" class="active"><i class="fas fa-question-circle"></i> Questions</a></li>
            </ul>
        </div>
        
        <div class="admin-main">
            <h1>Manage Questions: <?php echo htmlspecialchars($course['title']); ?></h1>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Final Exam Progress -->
            <div class="section">
                <h3>Final Exam Questions (Need 100 total)</h3>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo min(100, ($final_question_count / 100) * 100); ?>%"></div>
                </div>
                <p><?php echo $final_question_count; ?> / 100 questions added</p>
                <?php if ($final_question_count < 100): ?>
                    <div class="info">You need <?php echo 100 - $final_question_count; ?> more questions for the final exam.</div>
                <?php else: ?>
                    <div class="success">✓ Complete! 100 questions ready for final exam.</div>
                <?php endif; ?>
            </div>
            
            <!-- Add Unit Question -->
            <div class="section">
                <h3>Add Unit Test Question (50 per unit)</h3>
                <form method="POST">
                    <input type="hidden" name="question_type" value="unit">
                    
                    <div class="form-group">
                        <label>Select Unit</label>
                        <select name="unit_id" required>
                            <option value="">-- Select Unit --</option>
                            <?php foreach ($units as $unit): ?>
                                <option value="<?php echo $unit['id']; ?>">
                                    Unit <?php echo $unit['unit_number']; ?>: <?php echo htmlspecialchars($unit['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Question Text</label>
                        <textarea name="question_text" rows="3" required></textarea>
                    </div>
                    
                    <div class="options-row">
                        <div class="form-group">
                            <label>Option A</label>
                            <input type="text" name="option_a" required>
                        </div>
                        <div class="form-group">
                            <label>Option B</label>
                            <input type="text" name="option_b" required>
                        </div>
                        <div class="form-group">
                            <label>Option C</label>
                            <input type="text" name="option_c" required>
                        </div>
                        <div class="form-group">
                            <label>Option D</label>
                            <input type="text" name="option_d" required>
                        </div>
                    </div>
                    
                    <div class="options-row">
                        <div class="form-group">
                            <label>Correct Answer</label>
                            <select name="correct_answer" required>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Points</label>
                            <input type="number" name="points" value="1" min="1" max="10">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Explanation (shown when answer is wrong)</label>
                        <textarea name="explanation" rows="2" placeholder="Explain why the correct answer is right..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-add">Add Unit Question</button>
                </form>
            </div>
            
            <!-- Add Final Exam Question -->
            <div class="section">
                <h3>Add Final Exam Question (Need 100 total)</h3>
                <form method="POST">
                    <input type="hidden" name="question_type" value="final">
                    
                    <div class="form-group">
                        <label>Question Text</label>
                        <textarea name="question_text" rows="3" required></textarea>
                    </div>
                    
                    <div class="options-row">
                        <div class="form-group">
                            <label>Option A</label>
                            <input type="text" name="option_a" required>
                        </div>
                        <div class="form-group">
                            <label>Option B</label>
                            <input type="text" name="option_b" required>
                        </div>
                        <div class="form-group">
                            <label>Option C</label>
                            <input type="text" name="option_c" required>
                        </div>
                        <div class="form-group">
                            <label>Option D</label>
                            <input type="text" name="option_d" required>
                        </div>
                    </div>
                    
                    <div class="options-row">
                        <div class="form-group">
                            <label>Correct Answer</label>
                            <select name="correct_answer" required>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Points</label>
                            <input type="number" name="points" value="1" min="1" max="10">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Explanation (shown when answer is wrong)</label>
                        <textarea name="explanation" rows="2" placeholder="Explain why the correct answer is right..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-add">Add Final Exam Question</button>
                </form>
            </div>
            
            <!-- Unit Questions List -->
            <?php foreach ($units as $unit): 
                $stmt = $db->prepare("SELECT * FROM unit_questions WHERE unit_id = ? LIMIT 10");
                $stmt->execute([$unit['id']]);
                $unit_questions = $stmt->fetchAll();
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM unit_questions WHERE unit_id = ?");
                $stmt->execute([$unit['id']]);
                $unit_q_count = $stmt->fetch()['total'];
            ?>
                <div class="section">
                    <h3>Unit <?php echo $unit['unit_number']; ?>: <?php echo htmlspecialchars($unit['title']); ?></h3>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min(100, ($unit_q_count / 50) * 100); ?>%"></div>
                    </div>
                    <p><?php echo $unit_q_count; ?> / 50 questions</p>
                    
                    <?php foreach ($unit_questions as $q): ?>
                        <div class="question-item">
                            <div class="question-text"><?php echo htmlspecialchars($q['question_text']); ?></div>
                            <div class="question-meta">
                                Correct: <?php echo $q['correct_answer']; ?> | Points: <?php echo $q['points']; ?>
                                <a href="?delete_unit_q=<?php echo $q['id']; ?>&course_id=<?php echo $course_id; ?>" class="btn-delete" style="margin-left: 10px;" onclick="return confirm('Delete this question?')">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>