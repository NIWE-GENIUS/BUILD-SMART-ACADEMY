<?php
// dashboard/my-courses.php
// User's Enrolled Courses Page

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$user_id = getCurrentUserId();
$page_title = 'My Courses';

// Get all enrolled courses
$enrolled_courses = getEnrolledCourses($user_id);

// Filter by status
$status = $_GET['status'] ?? 'all';
$filtered_courses = [];
$completed_count = 0;
$in_progress_count = 0;

foreach ($enrolled_courses as $course) {
    if ($course['is_completed']) {
        $completed_count++;
        if ($status === 'completed') {
            $filtered_courses[] = $course;
        }
    } else {
        $in_progress_count++;
        if ($status === 'in_progress') {
            $filtered_courses[] = $course;
        }
    }
}

if ($status === 'all') {
    $filtered_courses = $enrolled_courses;
}
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
        .my-courses-container {
            max-width: 1000px;
            margin: 30px auto;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-header h1 {
            margin: 0;
        }
        
        .status-filters {
            display: flex;
            gap: 10px;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border-radius: 25px;
            text-decoration: none;
            background: #f0f0f0;
            color: #666;
            transition: all 0.3s;
        }
        
        .filter-btn.active {
            background: var(--orange);
            color: white;
        }
        
        .filter-btn:hover:not(.active) {
            background: #ddd;
        }
        
        .stats-row {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            border-radius: 12px;
            padding: 15px 25px;
            flex: 1;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: var(--orange);
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .course-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .course-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .course-image {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--blue), var(--orange));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            flex-shrink: 0;
        }
        
        .course-info {
            flex: 1;
        }
        
        .course-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .course-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        
        .course-progress {
            margin: 15px 0;
        }
        
        .progress-bar {
            background: #eee;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
        }
        
        .progress-fill {
            background: var(--orange);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s;
        }
        
        .progress-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .course-meta {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: #888;
            margin-bottom: 15px;
        }
        
        .course-meta i {
            margin-right: 5px;
        }
        
        .course-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn-continue {
            background: var(--orange);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-review {
            background: #f0f0f0;
            color: #666;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .completed-badge {
            background: #27ae60;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 15px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .browse-btn {
            display: inline-block;
            background: var(--orange);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .course-card {
                flex-direction: column;
            }
            
            .course-image {
                width: 100%;
                height: 100px;
            }
            
            .stats-row {
                flex-direction: column;
            }
            
            .page-header {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container">
        <div class="my-courses-container">
            <div class="page-header">
                <h1><i class="fas fa-book-open"></i> My Courses</h1>
                <div class="status-filters">
                    <a href="?status=all" class="filter-btn <?php echo $status === 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?status=in_progress" class="filter-btn <?php echo $status === 'in_progress' ? 'active' : ''; ?>">In Progress</a>
                    <a href="?status=completed" class="filter-btn <?php echo $status === 'completed' ? 'active' : ''; ?>">Completed</a>
                </div>
            </div>
            
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-number"><?php echo count($enrolled_courses); ?></div>
                    <div class="stat-label">Total Enrolled</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $in_progress_count; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $completed_count; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            
            <?php if (count($filtered_courses) > 0): ?>
                <?php foreach ($filtered_courses as $course): 
                    $progress = getCourseProgress($user_id, $course['id']);
                ?>
                    <div class="course-card">
                        <div class="course-image">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="course-info">
                            <div class="course-title"><?php echo htmlspecialchars($course['title']); ?></div>
                            <div class="course-description">
                                <?php echo htmlspecialchars(substr($course['description'], 0, 120)) . '...'; ?>
                            </div>
                            
                            <div class="course-meta">
                                <span><i class="far fa-calendar-alt"></i> Enrolled: <?php echo formatDate($course['enrolled_at']); ?></span>
                                <?php if ($course['is_completed']): ?>
                                    <span><i class="fas fa-check-circle"></i> Completed: <?php echo formatDate($course['completed_at']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="course-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                                <div class="progress-text"><?php echo $progress; ?>% Complete</div>
                            </div>
                            
                            <div class="course-actions">
                                <?php if ($course['is_completed']): ?>
                                    <span class="completed-badge"><i class="fas fa-check"></i> Completed</span>
                                    <a href="<?php echo SITE_URL; ?>courses/course-details.php?id=<?php echo $course['id']; ?>" class="btn-review">Review Course</a>
                                <?php else: ?>
                                    <a href="<?php echo SITE_URL; ?>courses/continue.php?id=<?php echo $course['id']; ?>" class="btn-continue">Continue Learning →</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>No courses found</h3>
                    <?php if ($status !== 'all'): ?>
                        <p>You don't have any <?php echo $status; ?> courses.</p>
                        <a href="?status=all" class="browse-btn">View All Courses</a>
                    <?php else: ?>
                        <p>You haven't enrolled in any courses yet.</p>
                        <a href="<?php echo SITE_URL; ?>courses/" class="browse-btn">Browse Available Courses</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>