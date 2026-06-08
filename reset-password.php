<?php
// auth/reset-password.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

$page_title = 'Reset Password';
$error = '';
$success = '';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    redirect('auth/forgot-password.php');
}

$db = Database::getConnection();

// Verify token
$stmt = $db->prepare("
    SELECT user_id, expires_at FROM password_resets 
    WHERE token = ? AND expires_at > NOW() AND used = FALSE
");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $error = 'Invalid or expired password reset link. Please request a new one.';
} else {
    $user_id = $reset['user_id'];
    
    // Process password reset
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($password) || empty($confirm_password)) {
            $error = 'Please enter a new password.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (!validatePasswordStrength($password)) {
            $error = 'Password must be at least 8 characters and contain uppercase, lowercase, number, and special character.';
        } else {
            // Update password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$password_hash, $user_id]);
            
            // Mark token as used
            $stmt = $db->prepare("UPDATE password_resets SET used = TRUE WHERE token = ?");
            $stmt->execute([$token]);
            
            // Create notification
            createNotification($user_id, 'Password Changed', 'Your password was successfully changed.', 'security');
            
            $success = 'Password changed successfully! You can now login with your new password.';
            
            // Redirect after 3 seconds
            header("refresh:3;url=" . SITE_URL . "auth/login.php");
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
        .reset-container {
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
        .info-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container">
        <div class="reset-container">
            <h2 style="text-align: center;">Reset Password</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
                <p style="text-align: center; margin-top: 20px;">
                    <a href="<?php echo SITE_URL; ?>auth/login.php">Click here to login</a>
                </p>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" required>
                        <div class="info-text">Min 8 chars, uppercase, lowercase, number, special character</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn-submit">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>