<?php
// forum/topic.php
// View Forum Topic and Replies

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

$topic_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$topic_id) {
    redirect('forum/');
}

$db = Database::getConnection();

// Get topic details
$stmt = $db->prepare("
    SELECT t.*, u.full_name, u.profile_picture, u.role
    FROM forum_topics t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch();

if (!$topic) {
    redirect('forum/');
}

// Increment view count
$stmt = $db->prepare("UPDATE forum_topics SET views = views + 1 WHERE id = ?");
$stmt->execute([$topic_id]);

// Get replies
$stmt = $db->prepare("
    SELECT r.*, u.full_name, u.profile_picture, u.role
    FROM forum_replies r
    JOIN users u ON r.user_id = u.id
    WHERE r.topic_id = ?
    ORDER BY r.created_at ASC
");
$stmt->execute([$topic_id]);
$replies = $stmt->fetchAll();

// Handle reply submission
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed.';
    } else {
        $content = sanitizeInput($_POST['content'] ?? '');
        
        if (empty($content)) {
            $error = 'Reply content cannot be empty.';
        } else {
            $stmt = $db->prepare("
                INSERT INTO forum_replies (topic_id, user_id, content)
                VALUES (?, ?, ?)
            ");
            if ($stmt->execute([$topic_id, getCurrentUserId(), $content])) {
                // Create notification for topic author
                if (getCurrentUserId() != $topic['user_id']) {
                    createNotification($topic['user_id'], 'New Reply', 'Someone replied to your forum topic: ' . $topic['title'], 'forum');
                }
                redirect('forum/topic.php?id=' . $topic_id);
            } else {
                $error = 'Failed to post reply.';
            }
        }
    }
}

$csrf_token = generateCSRFToken();
$page_title = $topic['title'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo htmlspecialchars($topic['title']); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .topic-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--orange);
            text-decoration: none;
        }
        
        .topic-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .topic-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .topic-title {
            font-size: 1.5rem;
            margin: 0;
        }
        
        .topic-author {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--orange);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }
        
        .author-info {
            flex: 1;
        }
        
        .author-name {
            font-weight: 600;
        }
        
        .role-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            background: #f0f0f0;
            margin-left: 8px;
        }
        
        .topic-content {
            line-height: 1.6;
            margin: 20px 0;
        }
        
        .topic-meta {
            font-size: 12px;
            color: #888;
        }
        
        .reply-card {
            background: #f9f9f9;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .reply-form {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .reply-textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            min-height: 120px;
            resize: vertical;
        }
        
        .btn-reply {
            background: var(--orange);
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .error {
            background: #e74c3c;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .login-prompt {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="topic-container">
        <a href="<?php echo SITE_URL; ?>forum/" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Forum
        </a>
        
        <!-- Topic -->
        <div class="topic-card">
            <div class="topic-header">
                <h1 class="topic-title"><?php echo htmlspecialchars($topic['title']); ?></h1>
                <?php if ($topic['is_pinned']): ?>
                    <span style="background: #f39c12; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px;">Pinned</span>
                <?php endif; ?>
            </div>
            
            <div class="topic-author">
                <div class="author-avatar">
                    <?php echo strtoupper(substr($topic['full_name'], 0, 1)); ?>
                </div>
                <div class="author-info">
                    <div class="author-name">
                        <?php echo htmlspecialchars($topic['full_name']); ?>
                        <?php echo getRoleBadge($topic['role']); ?>
                    </div>
                    <div class="topic-meta">
                        Posted: <?php echo timeAgo($topic['created_at']); ?> | 
                        Views: <?php echo $topic['views']; ?>
                    </div>
                </div>
            </div>
            
            <div class="topic-content">
                <?php echo nl2br(htmlspecialchars($topic['content'])); ?>
            </div>
        </div>
        
        <!-- Replies -->
        <h3><?php echo count($replies); ?> Replies</h3>
        
        <?php foreach ($replies as $reply): ?>
            <div class="reply-card">
                <div class="topic-author" style="margin-bottom: 10px;">
                    <div class="author-avatar" style="width: 40px; height: 40px; font-size: 16px;">
                        <?php echo strtoupper(substr($reply['full_name'], 0, 1)); ?>
                    </div>
                    <div class="author-info">
                        <div class="author-name">
                            <?php echo htmlspecialchars($reply['full_name']); ?>
                            <?php echo getRoleBadge($reply['role']); ?>
                        </div>
                        <div class="topic-meta"><?php echo timeAgo($reply['created_at']); ?></div>
                    </div>
                </div>
                <div class="topic-content">
                    <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Reply Form -->
        <?php if (isLoggedIn()): ?>
            <div class="reply-form">
                <h3>Post a Reply</h3>
                <?php if ($error): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <textarea name="content" class="reply-textarea" placeholder="Write your reply..."></textarea>
                    <button type="submit" class="btn-reply">Post Reply</button>
                </form>
            </div>
        <?php else: ?>
            <div class="login-prompt">
                <i class="fas fa-lock" style="font-size: 48px; color: #ddd;"></i>
                <p>Please login to join the discussion.</p>
                <a href="<?php echo SITE_URL; ?>auth/login.php" class="btn-primary">Login</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>