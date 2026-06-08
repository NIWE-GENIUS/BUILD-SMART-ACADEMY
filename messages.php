<?php
// dashboard/messages.php
// User messages with admin

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$user_id = getCurrentUserId();
$error = '';
$success = '';

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed.';
    } else {
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $message = sanitizeInput($_POST['message'] ?? '');
        $type = $_POST['type'] ?? 'question';
        $course_id = !empty($_POST['course_id']) ? intval($_POST['course_id']) : null;
        
        if (empty($subject) || empty($message)) {
            $error = 'Please fill in all fields.';
        } else {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO user_messages (user_id, course_id, subject, message, type)
                VALUES (?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([$user_id, $course_id, $subject, $message, $type])) {
                $success = 'Your message has been sent. Admin will respond within 48 hours.';
                
                // Notify admin (super admin)
                $stmt = $db->prepare("SELECT id FROM users WHERE role = 'super_admin' LIMIT 1");
                $stmt->execute();
                $admin = $stmt->fetch();
                if ($admin) {
                    createNotification($admin['id'], 'New User Message', 'A user has sent a new ' . $type . ': ' . $subject, 'message');
                }
            } else {
                $error = 'Failed to send message. Please try again.';
            }
        }
    }
}

// Get user's messages
$db = Database::getConnection();
$stmt = $db->prepare("
    SELECT * FROM user_messages 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$messages = $stmt->fetchAll();

// Get enrolled courses for dropdown
$courses = getEnrolledCourses($user_id);

$csrf_token = generateCSRFToken();
$page_title = 'Messages';
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
        .messages-container {
            max-width: 1000px;
            margin: 30px auto;
        }
        
        .message-form {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .form-group {
            margin-bottom: 15px;
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
            font-size: 14px;
        }
        
        .btn-send {
            background: var(--orange);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .message-item {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .message-subject {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .message-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-answered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-closed {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .message-body {
            color: #555;
            margin: 15px 0;
            line-height: 1.5;
        }
        
        .admin-response {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            border-left: 3px solid var(--orange);
        }
        
        .admin-response strong {
            color: var(--orange);
        }
        
        .message-time {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 15px;
        }
        
        .error-message {
            background: #e74c3c;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background: #27ae60;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container">
        <div class="messages-container">
            <h1><i class="fas fa-envelope"></i> Messages</h1>
            <p>Ask questions or send suggestions to admin</p>
            
            <!-- Message Form -->
            <div class="message-form">
                <h3>Send a New Message</h3>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="send">
                    
                    <div class="form-group">
                        <label for="type">Message Type</label>
                        <select id="type" name="type" required>
                            <option value="question">Question</option>
                            <option value="suggestion">Suggestion</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="course_id">Related Course (Optional)</label>
                        <select id="course_id" name="course_id">
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required 
                               placeholder="Brief summary of your message">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="4" required 
                                  placeholder="Type your question or suggestion here..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-send">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
            
            <!-- Message History -->
            <h3 style="margin: 30px 0 15px;">Message History</h3>
            
            <?php if (count($messages) > 0): ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message-item">
                        <div class="message-header">
                            <span class="message-subject">
                                <?php echo htmlspecialchars($msg['subject']); ?>
                                <span style="font-size: 12px; color: #999;">
                                    (<?php echo $msg['type'] == 'question' ? 'Question' : 'Suggestion'; ?>)
                                </span>
                            </span>
                            <span class="message-status status-<?php echo $msg['status']; ?>">
                                <?php echo ucfirst($msg['status']); ?>
                            </span>
                        </div>
                        
                        <div class="message-body">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        </div>
                        
                        <?php if ($msg['admin_response']): ?>
                            <div class="admin-response">
                                <strong><i class="fas fa-user-shield"></i> Admin Response:</strong><br>
                                <?php echo nl2br(htmlspecialchars($msg['admin_response'])); ?>
                                <div class="message-time">
                                    Responded: <?php echo formatDateTime($msg['responded_at']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="message-time">
                            Sent: <?php echo timeAgo($msg['created_at']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #ddd;"></i>
                    <p>No messages yet. Send your first question or suggestion above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>