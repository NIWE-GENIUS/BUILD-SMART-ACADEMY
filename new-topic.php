<?php
// forum/new-topic.php
// Create New Forum Topic

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
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
        $category = sanitizeInput($_POST['category'] ?? '');
        $content = sanitizeInput($_POST['content'] ?? '');
        
        if (empty($title) || empty($category) || empty($content)) {
            $error = 'All fields are required.';
        } else {
            $stmt = $db->prepare("
                INSERT INTO forum_topics (user_id, category, title, content)
                VALUES (?, ?, ?, ?)
            ");
            if ($stmt->execute([$user_id, $category, $title, $content])) {
                $topic_id = $db->lastInsertId();
                redirect('forum/topic.php?id=' . $topic_id);
            } else {
                $error = 'Failed to create topic. Please try again.';
            }
        }
    }
}

$csrf_token = generateCSRFToken();
$page_title = 'New Topic';
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
        .new-topic-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
        
        .form-group textarea {
            min-height: 200px;
            resize: vertical;
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
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container">
        <div class="new-topic-container">
            <h1><i class="fas fa-plus-circle"></i> Create New Topic</h1>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label>Topic Title</label>
                    <input type="text" name="title" required placeholder="What would you like to discuss?">
                </div>
                
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="">-- Select Category --</option>
                        <option value="cost_estimation">Cost Estimation</option>
                        <option value="procurement">Procurement</option>
                        <option value="project_management">Project Management</option>
                        <option value="general">General Quantity Surveying</option>
                        <option value="career">Career Advice</option>
                        <option value="software">Tech & Software</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Content</label>
                    <textarea name="content" required placeholder="Describe your topic in detail..."></textarea>
                </div>
                
                <button type="submit" class="btn-submit">Post Topic</button>
                <a href="<?php echo SITE_URL; ?>forum/" class="btn-secondary" style="margin-left: 10px;">Cancel</a>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>