<?php
// admin/announcements.php
// Manage Announcements

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

if (!isAdmin()) {
    redirect('dashboard/');
}

$db = Database::getConnection();
$user_id = getCurrentUserId();

// Handle create/update/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $title = sanitizeInput($_POST['title']);
        $content = sanitizeInput($_POST['content']);
        $type = $_POST['type'];
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        
        $stmt = $db->prepare("
            INSERT INTO announcements (title, content, type, expires_at, created_by) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $content, $type, $expires_at, $user_id]);
        
        // Clear homepage cache if any
        $_SESSION['announcement_updated'] = true;
        
    } elseif (isset($_POST['delete'])) {
        $id = intval($_POST['id']);
        $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$id]);
    } elseif (isset($_POST['toggle_status'])) {
        $id = intval($_POST['id']);
        $stmt = $db->prepare("UPDATE announcements SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
    }
    
    redirect('admin/announcements.php');
}

// Get all announcements
$stmt = $db->prepare("
    SELECT a.*, u.full_name as creator_name
    FROM announcements a
    LEFT JOIN users u ON a.created_by = u.id
    ORDER BY a.created_at DESC
");
$stmt->execute();
$announcements = $stmt->fetchAll();

$page_title = 'Announcements';
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
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
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
        .btn-primary {
            background: #FF6B35;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
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
        .announcement-info { border-left: 4px solid #3498DB; }
        .announcement-success { border-left: 4px solid #27AE60; }
        .announcement-warning { border-left: 4px solid #F39C12; }
        .announcement-danger { border-left: 4px solid #E74C3C; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-bullhorn"></i> Announcements Manager</h1>
        <p>Create and manage site-wide announcements that appear on the homepage.</p>
        
        <!-- Create Announcement Form -->
        <div class="card">
            <h3>Create New Announcement</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required placeholder="Special Offer!">
                </div>
                <div class="form-group">
                    <label>Content</label>
                    <textarea name="content" rows="3" required placeholder="Announcement details..."></textarea>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="type">
                        <option value="info">Info (Blue)</option>
                        <option value="success">Success (Green)</option>
                        <option value="warning">Warning (Yellow)</option>
                        <option value="danger">Danger (Red)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Expiry Date (Optional)</label>
                    <input type="datetime-local" name="expires_at">
                </div>
                <button type="submit" name="create" class="btn-primary">Publish Announcement</button>
            </form>
        </div>
        
        <!-- Announcements List -->
        <div class="card">
            <h3>All Announcements</h3>
            <table class="table">
                <thead>
                    <tr><th>Title</th><th>Type</th><th>Status</th><th>Created</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($announcements as $ann): ?>
                        <tr class="announcement-<?php echo $ann['type']; ?>">
                            <td><?php echo htmlspecialchars($ann['title']); ?></td>
                            <td><?php echo ucfirst($ann['type']); ?></td>
                            <td><?php echo $ann['is_active'] ? 'Active' : 'Inactive'; ?></td>
                            <td><?php echo formatDate($ann['created_at']); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $ann['id']; ?>">
                                    <button type="submit" name="toggle_status" class="btn-sm" style="background: #27AE60;">Toggle</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $ann['id']; ?>">
                                    <button type="submit" name="delete" class="btn-sm" style="background: #E74C3C;" onclick="return confirm('Delete this announcement?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>