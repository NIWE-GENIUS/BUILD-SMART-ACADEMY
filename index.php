<?php
// forum/index.php
// Forums Home Page - List all topics

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

$db = Database::getConnection();

// Get filter
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : 'all';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$sql = "
    SELECT t.*, u.full_name, u.profile_picture,
           (SELECT COUNT(*) FROM forum_replies WHERE topic_id = t.id) as reply_count,
           (SELECT COUNT(*) FROM forum_replies WHERE topic_id = t.id AND user_id = ?) as user_replied
    FROM forum_topics t
    JOIN users u ON t.user_id = u.id
";
if ($category != 'all') {
    $sql .= " WHERE t.category = ?";
}
$sql .= " ORDER BY t.is_pinned DESC, t.created_at DESC LIMIT ? OFFSET ?";

$stmt = $db->prepare($sql);
$params = [getCurrentUserId() ?: 0];
if ($category != 'all') {
    $params[] = $category;
}
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$topics = $stmt->fetchAll();

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM forum_topics";
if ($category != 'all') {
    $count_sql .= " WHERE category = '$category'";
}
$stmt = $db->prepare($count_sql);
$stmt->execute();
$total_topics = $stmt->fetch()['total'];
$total_pages = ceil($total_topics / $limit);

$page_title = 'Community Forum';
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
        .forum-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .forum-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .btn-new-topic {
            background: var(--orange);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
        }
        
        .category-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .category-btn {
            padding: 8px 20px;
            border-radius: 25px;
            text-decoration: none;
            background: #f0f0f0;
            color: #666;
        }
        
        .category-btn.active {
            background: var(--orange);
            color: white;
        }
        
        .topic-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            gap: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .topic-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .topic-pinned {
            border-left: 4px solid #f39c12;
        }
        
        .topic-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--orange);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            flex-shrink: 0;
        }
        
        .topic-content {
            flex: 1;
        }
        
        .topic-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .topic-title a {
            color: var(--dark);
            text-decoration: none;
        }
        
        .topic-title a:hover {
            color: var(--orange);
        }
        
        .topic-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #888;
            flex-wrap: wrap;
        }
        
        .topic-category {
            background: #f0f0f0;
            padding: 2px 10px;
            border-radius: 15px;
            font-size: 11px;
        }
        
        .topic-stats {
            text-align: right;
            min-width: 100px;
        }
        
        .replies-count {
            font-size: 14px;
            font-weight: 600;
            color: var(--orange);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 15px;
            background: white;
            border-radius: 5px;
            text-decoration: none;
            color: var(--dark);
        }
        
        .pagination .active {
            background: var(--orange);
            color: white;
        }
        
        @media (max-width: 768px) {
            .topic-item {
                flex-direction: column;
            }
            .topic-stats {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="forum-container">
        <div class="forum-header">
            <h1><i class="fas fa-comments"></i> Community Forum</h1>
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo SITE_URL; ?>forum/new-topic.php" class="btn-new-topic">
                    <i class="fas fa-plus"></i> New Topic
                </a>
            <?php endif; ?>
        </div>
        
        <div class="category-nav">
            <a href="?category=all" class="category-btn <?php echo $category == 'all' ? 'active' : ''; ?>">All Topics</a>
            <a href="?category=cost_estimation" class="category-btn <?php echo $category == 'cost_estimation' ? 'active' : ''; ?>">Cost Estimation</a>
            <a href="?category=procurement" class="category-btn <?php echo $category == 'procurement' ? 'active' : ''; ?>">Procurement</a>
            <a href="?category=project_management" class="category-btn <?php echo $category == 'project_management' ? 'active' : ''; ?>">Project Management</a>
            <a href="?category=general" class="category-btn <?php echo $category == 'general' ? 'active' : ''; ?>">General QS</a>
            <a href="?category=career" class="category-btn <?php echo $category == 'career' ? 'active' : ''; ?>">Career Advice</a>
            <a href="?category=software" class="category-btn <?php echo $category == 'software' ? 'active' : ''; ?>">Tech & Software</a>
        </div>
        
        <?php if (count($topics) > 0): ?>
            <?php foreach ($topics as $topic): ?>
                <div class="topic-item <?php echo $topic['is_pinned'] ? 'topic-pinned' : ''; ?>">
                    <div class="topic-avatar">
                        <?php echo strtoupper(substr($topic['full_name'], 0, 1)); ?>
                    </div>
                    <div class="topic-content">
                        <div class="topic-title">
                            <?php if ($topic['is_pinned']): ?>
                                <i class="fas fa-thumbtack" style="color: #f39c12;"></i>
                            <?php endif; ?>
                            <a href="<?php echo SITE_URL; ?>forum/topic.php?id=<?php echo $topic['id']; ?>">
                                <?php echo htmlspecialchars($topic['title']); ?>
                            </a>
                        </div>
                        <div class="topic-meta">
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($topic['full_name']); ?></span>
                            <span><i class="far fa-clock"></i> <?php echo timeAgo($topic['created_at']); ?></span>
                            <span class="topic-category"><?php echo str_replace('_', ' ', ucfirst($topic['category'])); ?></span>
                        </div>
                    </div>
                    <div class="topic-stats">
                        <div class="replies-count">
                            <i class="fas fa-comment"></i> <?php echo $topic['reply_count']; ?> replies
                        </div>
                        <?php if ($topic['user_replied']): ?>
                            <span style="font-size: 11px; color: var(--orange);">You replied</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&category=<?php echo $category; ?>">&laquo; Previous</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&category=<?php echo $category; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&category=<?php echo $category; ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 60px; background: white; border-radius: 12px;">
                <i class="fas fa-comments" style="font-size: 48px; color: #ddd;"></i>
                <h3>No topics yet</h3>
                <p>Be the first to start a discussion!</p>
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo SITE_URL; ?>forum/new-topic.php" class="btn-new-topic" style="display: inline-block; margin-top: 15px;">
                        Create New Topic
                    </a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>auth/login.php" class="btn-primary" style="display: inline-block; margin-top: 15px;">
                        Login to Post
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>