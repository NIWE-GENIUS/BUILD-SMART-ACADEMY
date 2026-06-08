<?php
// dashboard/notifications.php
// User Notifications Page

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$user_id = getCurrentUserId();

// Mark all as read if requested
if (isset($_GET['mark_all_read'])) {
    markAllNotificationsRead($user_id);
    redirect('dashboard/notifications.php');
}

// Mark single notification as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    markNotificationRead($_GET['mark_read'], $user_id);
    redirect('dashboard/notifications.php');
}

// Get all notifications
$db = Database::getConnection();
$stmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

$unread_count = getUnreadNotificationCount($user_id);
$page_title = 'Notifications';
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
        .notifications-container {
            max-width: 800px;
            margin: 30px auto;
        }
        
        .notifications-header {
            background: white;
            border-radius: 15px;
            padding: 20px 25px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .notifications-header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .mark-all-btn {
            background: var(--blue);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .notification-item {
            background: white;
            border-radius: 12px;
            padding: 20px 25px;
            margin-bottom: 12px;
            display: flex;
            gap: 15px;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .notification-item.unread {
            background: #fff8e1;
            border-left: 4px solid var(--orange);
        }
        
        .notification-icon {
            width: 50px;
            height: 50px;
            background: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .notification-icon i {
            font-size: 20px;
            color: var(--orange);
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .notification-message {
            color: #555;
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        .notification-time {
            font-size: 12px;
            color: #999;
        }
        
        .mark-read-btn {
            background: none;
            border: none;
            color: var(--blue);
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
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
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--orange);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container">
        <div class="notifications-container">
            <div class="notifications-header">
                <h1><i class="fas fa-bell"></i> Notifications</h1>
                <?php if ($unread_count > 0): ?>
                    <a href="?mark_all_read=1" class="mark-all-btn">
                        <i class="fas fa-check-double"></i> Mark All as Read
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">
                                <?php echo htmlspecialchars($notification['title']); ?>
                                <?php if (!$notification['is_read']): ?>
                                    <a href="?mark_read=<?php echo $notification['id']; ?>" class="mark-read-btn">
                                        Mark as read
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="notification-message">
                                <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                            </div>
                            <div class="notification-time">
                                <i class="far fa-clock"></i> <?php echo timeAgo($notification['created_at']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No Notifications</h3>
                    <p>You're all caught up! Check back later for updates.</p>
                    <a href="<?php echo SITE_URL; ?>dashboard/" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>