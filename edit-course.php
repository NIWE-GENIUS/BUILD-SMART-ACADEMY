<?php
// admin/edit-course.php
// Edit Course and Manage Units

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('dashboard/');
}

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$course_id) {
    redirect('admin/courses.php');
}

$db = Database::getConnection();
$user_id = getCurrentUserId();

// Get course details
$stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    redirect('admin/courses.php');
}

// Handle unit addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_course') {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $category = sanitizeInput($_POST['category'] ?? '');
        $price = intval($_POST['price'] ?? 0);
        $is_paid = $price > 0 ? 1 : 0;
        
        $stmt = $db->prepare("
            UPDATE courses SET title = ?, description = ?, category = ?, price = ?, is_paid = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $category, $price, $is_paid, $course_id]);
        $success = 'Course updated successfully.';
        
        // Refresh course data
        $stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch();
    }
    
    if ($_POST['action'] === 'add_unit') {
        $unit_number = intval($_POST['unit_number'] ?? 0);
        $unit_title = sanitizeInput($_POST['unit_title'] ?? '');
        $video_url = sanitizeInput($_POST['video_url'] ?? '');
        $document_url = sanitizeInput($_POST['document_url'] ?? '');
        $duration_minutes = intval($_POST['duration_minutes'] ?? 0);
        
        if ($unit_number && $unit_title) {
            $stmt = $db->prepare("
                INSERT INTO course_units (course_id, unit_number, title, video_url, document_url, duration_minutes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$course_id, $unit_number, $unit_title, $video_url, $document_url, $duration_minutes]);
            $unit_success = 'Unit added successfully.';
        }
    }
    
    if ($_POST['action'] === 'delete_unit') {
        $unit_id = intval($_POST['unit_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM course_units WHERE id = ? AND course_id = ?");
        $stmt->execute([$unit_id, $course_id]);
        $unit_success = 'Unit deleted.';
    }
}

// Get course units
$stmt = $db->prepare("SELECT * FROM course_units WHERE course_id = ? ORDER BY unit_number ASC");
$stmt->execute([$course_id]);
$units = $stmt->fetchAll();

$csrf_token = generateCSRFToken();
$page_title = 'Edit Course - ' . $course['title'];
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
        
        .btn-save {
            background: var(--orange);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .unit-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .unit-number {
            font-weight: bold;
            width: 50px;
        }
        
        .unit-title {
            flex: 1;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
            padding: 4px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
        }
        
        .success {
            background: #27ae60;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        h3 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="admin-container">
        <div class="admin-sidebar">
            <ul class="nav">
                <li><a href="<?php echo SITE_URL; ?>admin/"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/courses.php" class="active"><i class="fas fa-book"></i> Courses</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/add-course.php"><i class="fas fa-plus-circle"></i> Add Course</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/manage-questions.php?course_id=<?php echo $course_id; ?>"><i class="fas fa-question-circle"></i> Questions</a></li>
            </ul>
        </div>
        
        <div class="admin-main">
            <h1>Edit Course: <?php echo htmlspecialchars($course['title']); ?></h1>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Edit Course Form -->
            <div class="section">
                <h3>Course Information</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="update_course">
                    
                    <div class="form-group">
                        <label>Course Title</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($course['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="Quantity Surveying" <?php echo $course['category'] == 'Quantity Surveying' ? 'selected' : ''; ?>>Quantity Surveying</option>
                            <option value="Construction Management" <?php echo $course['category'] == 'Construction Management' ? 'selected' : ''; ?>>Construction Management</option>
                            <option value="Cost Estimation" <?php echo $course['category'] == 'Cost Estimation' ? 'selected' : ''; ?>>Cost Estimation</option>
                            <option value="Procurement" <?php echo $course['category'] == 'Procurement' ? 'selected' : ''; ?>>Procurement</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Price (RWF) - 0 for free</label>
                        <input type="number" name="price" value="<?php echo $course['price']; ?>" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="5"><?php echo htmlspecialchars($course['description']); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-save">Save Changes</button>
                </form>
            </div>
            
            <!-- Add Unit Form -->
            <div class="section">
                <h3>Add New Unit</h3>
                <?php if (isset($unit_success)): ?>
                    <div class="success"><?php echo $unit_success; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="add_unit">
                    
                    <div class="form-group">
                        <label>Unit Number</label>
                        <input type="number" name="unit_number" required placeholder="1, 2, 3...">
                    </div>
                    
                    <div class="form-group">
                        <label>Unit Title</label>
                        <input type="text" name="unit_title" required placeholder="e.g., Introduction to Cost Estimation">
                    </div>
                    
                    <div class="form-group">
                        <label>Video URL (YouTube or Vimeo link)</label>
                        <input type="url" name="video_url" placeholder="https://...">
                    </div>
                    
                    <div class="form-group">
                        <label>Document URL (Downloadable materials)</label>
                        <input type="url" name="document_url" placeholder="https://...">
                    </div>
                    
                    <div class="form-group">
                        <label>Duration (minutes)</label>
                        <input type="number" name="duration_minutes" placeholder="30">
                    </div>
                    
                    <button type="submit" class="btn-save">Add Unit</button>
                </form>
            </div>
            
            <!-- Course Units List -->
            <div class="section">
                <h3>Course Units (<?php echo count($units); ?> units)</h3>
                <?php foreach ($units as $unit): ?>
                    <div class="unit-item">
                        <span class="unit-number">Unit <?php echo $unit['unit_number']; ?></span>
                        <span class="unit-title"><?php echo htmlspecialchars($unit['title']); ?></span>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this unit?')">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="delete_unit">
                            <input type="hidden" name="unit_id" value="<?php echo $unit['id']; ?>">
                            <button type="submit" class="btn-delete">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
                
                <?php if (count($units) == 0): ?>
                    <p>No units added yet. Add your first unit above.</p>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h3>Next Steps</h3>
                <p>After adding units, you need to:</p>
                <ol>
                    <li>Add 50 unit test questions for each unit</li>
                    <li>Add 100 final exam questions for this course</li>
                </ol>
                <a href="<?php echo SITE_URL; ?>admin/manage-questions.php?course_id=<?php echo $course_id; ?>" class="btn-save" style="display: inline-block; text-decoration: none;">
                    <i class="fas fa-question-circle"></i> Manage Questions
                </a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>