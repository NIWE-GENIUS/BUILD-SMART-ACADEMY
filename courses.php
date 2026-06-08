<?php
// admin/courses.php
// Manage Courses

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('dashboard/');
}

$db = Database::getConnection();
$is_super_admin = isSuperAdmin();

// Handle delete
if (isset($_GET['delete']) && $is_super_admin) {
    $course_id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    redirect('admin/courses.php?deleted=1');
}

// Handle publish/unpublish
if (isset($_GET['toggle']) && $is_super_admin) {
    $course_id = intval($_GET['toggle']);
    $stmt = $db->prepare("UPDATE courses SET status = IF(status = 'published', 'draft', 'published') WHERE id = ?");
    $stmt->execute([$course_id]);
    redirect('admin/courses.php');
}

// Get all courses
$stmt = $db->prepare("
    SELECT c.*, u.full_name as instructor_name,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrolled_count
    FROM courses c
    LEFT JOIN users u ON c.instructor_id = u.id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$courses = $stmt->fetchAll();

$page_title = 'Manage Courses';
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
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background: #f8f9fa;
        }
        
        .btn-add {
            background: var(--orange);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
        }
        
        .btn-edit {
            background: var(--blue);
            color: white;
            padding: 4px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
            padding: 4px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
        }
        
        .status-published {
            background: #d4edda;
            color: #155724;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .status-draft {
            background: #fff3cd;
            color: #856404;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            .admin-sidebar {
                width: 100%;
            }
            .table {
                display: block;
                overflow-x: auto;
            }
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
                <li><a href="<?php echo SITE_URL; ?>admin/users.php"><i class="fas fa-users"></i> Users</a></li>
                <?php if ($is_super_admin): ?>
                    <li><a href="<?php echo SITE_URL; ?>admin/sub-admins.php"><i class="fas fa-user-shield"></i> Sub Admins</a></li>
                <?php endif; ?>
                <li><a href="<?php echo SITE_URL; ?>admin/messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            </ul>
        </div>
        
        <div class="admin-main">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1><i class="fas fa-book"></i> Manage Courses</h1>
                <a href="<?php echo SITE_URL; ?>admin/add-course.php" class="btn-add"><i class="fas fa-plus"></i> Add New Course</a>
            </div>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">Course deleted successfully.</div>
            <?php endif; ?>
            
            <div class="section">
                <table class="table">
                    <thead>
                        <tr><th>ID</th><th>Title</th><th>Price</th><th>Status</th><th>Enrolled</th><th>Created</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?php echo $course['id']; ?></td>
                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                                <td><?php echo $course['is_paid'] ? number_format($course['price']) . ' RWF' : 'Free'; ?></td>
                                <td>
                                    <span class="status-<?php echo $course['status']; ?>">
                                        <?php echo ucfirst($course['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $course['enrolled_count']; ?></td>
                                <td><?php echo formatDate($course['created_at']); ?></td>
                                <td>
                                    <a href="<?php echo SITE_URL; ?>admin/edit-course.php?id=<?php echo $course['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="<?php echo SITE_URL; ?>admin/manage-questions.php?course_id=<?php echo $course['id']; ?>" class="btn-edit" style="background: #17a2b8;"><i class="fas fa-question-circle"></i> Questions</a>
                                    <?php if ($is_super_admin): ?>
                                        <a href="?toggle=<?php echo $course['id']; ?>" class="btn-edit" style="background: #6c757d;">
                                            <?php echo $course['status'] == 'published' ? 'Unpublish' : 'Publish'; ?>
                                        </a>
                                        <a href="?delete=<?php echo $course['id']; ?>" class="btn-delete" onclick="return confirm('Delete this course? All data will be lost.')"><i class="fas fa-trash"></i> Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>