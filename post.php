<?php
// blog/post.php
// Single Blog Post

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';

if (!$slug) {
    redirect('blog/');
}

$db = Database::getConnection();

// Get post
$stmt = $db->prepare("
    SELECT b.*, u.full_name as author_name
    FROM blog_posts b
    JOIN users u ON b.author_id = u.id
    WHERE b.slug = ? AND b.status = 'published'
");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    redirect('blog/');
}

// Increment view count
$stmt = $db->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?");
$stmt->execute([$post['id']]);

$page_title = $post['title'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo htmlspecialchars($post['title']); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .post-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--orange);
            text-decoration: none;
        }
        
        .post-card {
            background: white;
            border-radius: 15px;
            padding: 35px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .post-title {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .post-meta {
            font-size: 14px;
            color: #888;
            padding-bottom: 20px;
            margin-bottom: 25px;
            border-bottom: 1px solid #eee;
        }
        
        .post-content {
            line-height: 1.8;
            font-size: 16px;
        }
        
        .post-content p {
            margin-bottom: 20px;
        }
        
        .share-buttons {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 15px;
        }
        
        .share-btn {
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
        }
        
        .share-facebook { background: #3b5998; }
        .share-twitter { background: #1da1f2; }
        .share-linkedin { background: #0077b5; }
        .share-whatsapp { background: #25d366; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="post-container">
        <a href="<?php echo SITE_URL; ?>blog/" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Blog
        </a>
        
        <div class="post-card">
            <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            <div class="post-meta">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author_name']); ?>
                <i class="fas fa-calendar-alt"></i> <?php echo formatDate($post['published_at']); ?>
                <i class="fas fa-folder"></i> <?php echo htmlspecialchars($post['category']); ?>
                <i class="fas fa-eye"></i> <?php echo $post['views']; ?> views
            </div>
            <div class="post-content">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>
            
            <div class="share-buttons">
                <span>Share this post:</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . 'blog/post.php?slug=' . $post['slug']); ?>" target="_blank" class="share-btn share-facebook">
                    <i class="fab fa-facebook-f"></i> Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . 'blog/post.php?slug=' . $post['slug']); ?>&text=<?php echo urlencode($post['title']); ?>" target="_blank" class="share-btn share-twitter">
                    <i class="fab fa-twitter"></i> Twitter
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(SITE_URL . 'blog/post.php?slug=' . $post['slug']); ?>" target="_blank" class="share-btn share-linkedin">
                    <i class="fab fa-linkedin-in"></i> LinkedIn
                </a>
                <a href="https://wa.me/?text=<?php echo urlencode($post['title'] . ' - ' . SITE_URL . 'blog/post.php?slug=' . $post['slug']); ?>" target="_blank" class="share-btn share-whatsapp">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>