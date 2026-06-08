<?php
// payment/success.php
// Payment Success Page

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if (!$course_id) {
    redirect('dashboard/my-courses.php');
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT title FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

$page_title = 'Payment Successful';
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
        .success-container {
            max-width: 600px;
            margin: 80px auto;
            text-align: center;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .success-icon {
            font-size: 80px;
            color: #27ae60;
            margin-bottom: 20px;
        }
        
        .btn-continue {
            background: var(--orange);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container">
        <div class="success-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Payment Successful!</h1>
            <p>Thank you for your purchase.</p>
            <p>You have been enrolled in:</p>
            <h3><?php echo htmlspecialchars($course['title']); ?></h3>
            <a href="<?php echo SITE_URL; ?>courses/continue.php?id=<?php echo $course_id; ?>" class="btn-continue">
                <i class="fas fa-play"></i> Start Learning Now
            </a>
            <br>
            <a href="<?php echo SITE_URL; ?>dashboard/my-courses.php" style="display: inline-block; margin-top: 15px; color: var(--blue);">
                Go to My Courses
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>