<?php
// auth/forgot-password.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

$page_title = 'Forgot Password';
$error = '';
$success = '';

// Process request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db = Database::getConnection();
        
        // Check if email exists
        $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $stmt = $db->prepare("
                INSERT INTO password_resets (user_id, token, expires_at) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE token = ?, expires_at = ?
            ");
            $stmt->execute([$user['id'], $reset_token, $expires, $reset_token, $expires]);
            
            // Send reset email
            $reset_link = SITE_URL . "auth/reset-password.php?token=" . $reset_token;
            
            $email_subject = "Reset Your Password - " . SITE_NAME;
            $email_body = "
            <html>
            <head>
                <title>Password Reset Request</title>
            </head>
            <body>
                <h2>Password Reset Request</h2>
                <p>Hello <strong>" . htmlspecialchars($user['full_name']) . "</strong>,</p>
                <p>We received a request to reset your password. Click the button below to create a new password:</p>
                <p style='text-align: center;'>
                    <a href='$reset_link' style='background: #FF6B35; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;'>Reset Password</a>
                </p>
                <p>This link will expire in 1 hour.</p>
                <p>If you didn't request this, please ignore this email.</p>
                <hr>
                <p>Best regards,<br>" . SITE_NAME . "</p>
            </body>
            </html>
            ";
            
            sendEmail($email, $email_subject, $email_body);
            
            $success = 'Password reset link has been sent to your email. Please check your inbox.';
        } else {
            // Don't reveal that email doesn't exist for security
            $success = 'If your email is registered, you will receive a password reset link.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <style>
        .forgot-container {
            max-width: 450px;
            margin: 60px auto;
            background: white;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        .btn-submit {
            width: 100%;
            background: var(--orange);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
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
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container">
        <div class="forgot-container">
            <h2 style="text-align: center;">Forgot Password?</h2>
            <p style="text-align: center; color: #666; margin-bottom: 30px;">Enter your email to receive a password reset link</p>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="your@email.com">
                </div>
                
                <button type="submit" class="btn-submit">Send Reset Link</button>
            </form>
            
            <div class="back-link">
                <a href="<?php echo SITE_URL; ?>auth/login.php">← Back to Login</a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>