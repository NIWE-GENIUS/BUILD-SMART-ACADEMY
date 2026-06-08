<?php
// auth/login.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    if (isSuperAdmin()) {
        redirect('admin/');
    } elseif (isAdmin()) {
        redirect('admin/');
    } else {
        redirect('dashboard/');
    }
}

$page_title = 'Login';
$error = '';
$success = '';

// Check for logout message
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $success = 'You have been successfully logged out.';
}

// Check for registration success
if (isset($_GET['registered']) && $_GET['registered'] == 'success') {
    $success = 'Account created successfully! Please login.';
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed. Please try again.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);
        
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            $db = Database::getConnection();
            
            // Check rate limiting for login attempts
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt = $db->prepare("SELECT COUNT(*) FROM rate_limits WHERE identifier = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
            $stmt->execute(['login_' . $ip]);
            $attempts = $stmt->fetchColumn();
            
            if ($attempts >= 5) {
                $error = 'Too many login attempts. Please try again after 15 minutes.';
            } else {
                // Get user by email
                $stmt = $db->prepare("
                    SELECT id, full_name, email, phone, password_hash, role, is_verified, lifetime_free 
                    FROM users 
                    WHERE email = ?
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    // Check if email is verified
                    if (!$user['is_verified']) {
                        $error = 'Please verify your email address before logging in. Check your inbox for the verification link.';
                    } else {
                        // Login successful - clear rate limiting
                        $stmt = $db->prepare("DELETE FROM rate_limits WHERE identifier = ?");
                        $stmt->execute(['login_' . $ip]);
                        
                        // Update last login
                        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        $stmt->execute([$user['id']]);
                        
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['full_name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['lifetime_free'] = $user['lifetime_free'];
                        
                        // Handle Remember Me
                        if ($remember_me) {
                            $token = bin2hex(random_bytes(32));
                            $hashed_token = password_hash($token, PASSWORD_DEFAULT);
                            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                            
                            // Store token in database
                            $stmt = $db->prepare("
                                INSERT INTO user_tokens (user_id, token, expires_at) 
                                VALUES (?, ?, ?)
                                ON DUPLICATE KEY UPDATE token = ?, expires_at = ?
                            ");
                            $stmt->execute([$user['id'], $hashed_token, $expires, $hashed_token, $expires]);
                            
                            // Set cookie
                            setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
                        }
                        
                        // Create notification for login
                        createNotification($user['id'], 'New Login', 'You logged in to your account from ' . $ip, 'security');
                        
                        // Redirect based on role
                        if ($user['role'] === 'super_admin' || $user['role'] === 'sub_admin') {
                            redirect('admin/');
                        } else {
                            redirect('dashboard/');
                        }
                    }
                } else {
                    // Log failed attempt for rate limiting
                    $stmt = $db->prepare("INSERT INTO rate_limits (identifier) VALUES (?)");
                    $stmt->execute(['login_' . $ip]);
                    
                    $error = 'Invalid email or password.';
                }
            }
        }
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <style>
        .login-container {
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
            color: var(--dark);
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            border-color: var(--orange);
            outline: none;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input {
            width: auto;
        }
        .btn-login {
            width: 100%;
            background: var(--orange);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-login:hover {
            background: #e55a2b;
        }
        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }
        .forgot-password a {
            color: var(--blue);
            text-decoration: none;
        }
        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
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
        h2 {
            text-align: center;
            color: var(--blue);
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container">
        <div class="login-container">
            <h2>Welcome Back</h2>
            <p class="subtitle">Login to continue your learning journey</p>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="your@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                </div>
                
                <div class="form-group">
                    <label class="checkbox-group">
                        <input type="checkbox" name="remember_me"> 
                        <span>Remember me for 30 days</span>
                    </label>
                </div>
                
                <button type="submit" class="btn-login">Login</button>
            </form>
            
            <div class="forgot-password">
                <a href="<?php echo SITE_URL; ?>auth/forgot-password.php">Forgot Password?</a>
            </div>
            
            <div class="register-link">
                <p>Don't have an account? <a href="<?php echo SITE_URL; ?>auth/register.php"><strong>Register Now</strong></a></p>
                <p style="font-size: 12px; color: #888; margin-top: 10px;">First 20 users get lifetime free access!</p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>