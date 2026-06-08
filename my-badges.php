<?php
// dashboard/my-badges.php
// Display user's earned badges

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$user_id = getCurrentUserId();
$badges = getUserBadges($user_id);

$page_title = 'My Badges';
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
        .badges-container {
            max-width: 1000px;
            margin: 30px auto;
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--orange), var(--blue));
            color: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .stats-number {
            font-size: 48px;
            font-weight: bold;
        }
        
        .badges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 20px;
        }
        
        .badge-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: transform 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .badge-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .badge-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #FF6B35, #F39C12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 36px;
            color: white;
        }
        
        .badge-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .badge-date {
            font-size: 11px;
            color: #999;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 15px;
        }
        
        .share-btn {
            background: none;
            border: none;
            color: var(--blue);
            cursor: pointer;
            font-size: 12px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container">
        <div class="badges-container">
            <div class="stats-card">
                <div class="stats-number"><?php echo count($badges); ?></div>
                <div>Badges Earned</div>
                <p style="margin-top: 10px; opacity: 0.9;">Keep learning to unlock more achievements!</p>
            </div>
            
            <?php if (count($badges) > 0): ?>
                <div class="badges-grid">
                    <?php foreach ($badges as $badge): ?>
                        <div class="badge-card">
                            <div class="badge-icon">
                                <i class="fas fa-award"></i>
                            </div>
                            <div class="badge-name"><?php echo htmlspecialchars($badge['badge_name']); ?></div>
                            <div class="badge-date">Earned: <?php echo formatDate($badge['awarded_at']); ?></div>
                            <button class="share-btn" onclick="shareBadge('<?php echo htmlspecialchars($badge['badge_name']); ?>')">
                                <i class="fas fa-share-alt"></i> Share
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-medal" style="font-size: 64px; color: #ddd; margin-bottom: 20px;"></i>
                    <h3>No Badges Yet</h3>
                    <p>Complete course units to earn badges and showcase your achievements!</p>
                    <a href="<?php echo SITE_URL; ?>dashboard/my-courses.php" class="btn-primary" style="display: inline-block; margin-top: 20px;">
                        Start Learning
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function shareBadge(badgeName) {
            if (navigator.share) {
                navigator.share({
                    title: 'My Badge from BUILD SMART ACADEMY',
                    text: `I earned the "${badgeName}" badge at BUILD SMART ACADEMY!`,
                    url: window.location.href
                });
            } else {
                alert(`Share this: I earned the "${badgeName}" badge at BUILD SMART ACADEMY!`);
            }
        }
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>