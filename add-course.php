<?php
// admin/add-course.php
// Add New Course

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('dashboard/');
}

$db = Database::getConnection();
$user_id = getCurrentUserId();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed.';
    } else {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $category = sanitizeInput($_POST['category'] ?? '');
        $price = intval($_POST['price'] ?? 0);
        $is_paid = $price > 0 ? 1 : 0;
        
        if (empty($title)) {
            $error = 'Course title is required.';
        } else {
            $stmt = $db->prepare("
                INSERT INTO courses (title, description, category, price, is_paid, status, created_by, instructor_id)
                VALUES (?, ?, ?, ?, ?, 'draft', ?, ?)
            ");
            if ($stmt->execute([$title, $description, $category, $price, $is_paid, $user_id, $user_id])) {
                $course_id = $db->lastInsertId();
                $success = 'Course created successfully!';
                header("refresh:2;url=" . SITE_URL . "admin/edit-course.php?id=" . $course_id);
            } else {
                $error = 'Failed to create course.';
            }
        }
    }
}

$csrf_token = generateCSRFToken();
$page_title = 'Add Course';
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
        
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            max-width: 800px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .btn-submit {
            background: var(--orange);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .error {
            background: #e74c3c;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .success {
            background: #27ae60;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
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
                <li><a href="<?php echo SITE_URL; ?>admin/add-course.php" class="active"><i class="fas fa-plus-circle"></i> Add Course</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
            </ul>
        </div>
        
        <div class="admin-main">
            <h1><i class="fas fa-plus-circle"></i> Add New Course</h1>
            
            <div class="form-container">
                <?php if ($error): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="title">Course Title *</label>
                        <input type="text" id="title" name="title" required placeholder="e.g., Advanced Quantity Surveying">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="Quantity Surveying">Quantity Surveying</option>
                            <option value="Construction Management">Construction Management</option>
                            <option value="Cost Estimation">Cost Estimation</option>
                            <option value="Procurement">Procurement</option>
                            <option value="Project Management">Project Management</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (RWF) - 0 for free</label>
                        <input type="number" id="price" name="price" value="0" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Course Description</label>
                        <textarea id="description" name="description" rows="6" placeholder="Detailed description of the course..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">Create Course</button>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>