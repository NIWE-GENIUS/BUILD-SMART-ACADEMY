<?php
// auth/register.php
// BUILD SMART ACADEMY - Registration Page (Email OTP Only)

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('dashboard/');
}

$page_title = 'Register';
$error = '';
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed. Please refresh the page and try again.';
    } else {
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($full_name) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (!validateEmail($email)) {
            $error = 'Please enter a valid email address.';
        } elseif (!validatePasswordStrength($password)) {
            $error = 'Password must be at least 8 characters and contain uppercase, lowercase, number, and special character.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            $db = Database::getConnection();
            
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered. Please login or use a different email.';
            } else {
                // Check rate limiting
                $ip = $_SERVER['REMOTE_ADDR'];
                $stmt = $db->prepare("SELECT COUNT(*) FROM rate_limits WHERE identifier = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
                $stmt->execute(['register_' . $ip]);
                $attempts = $stmt->fetchColumn();
                
                if ($attempts >= 5) {
                    $error = 'Too many registration attempts. Please try again later.';
                } else {
                    // Generate OTP
                    $email_otp = generateOTP(OTP_LENGTH);
                    
                    // Set expiry time
                    $email_expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
                    
                    // Delete any existing OTP for this email
                    $stmt = $db->prepare("DELETE FROM otp_verification WHERE email = ?");
                    $stmt->execute([$email]);
                    
                    // Store OTP in database
                    $stmt = $db->prepare("
                        INSERT INTO otp_verification (email, email_otp, email_otp_expires) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$email, $email_otp, $email_expiry]);
                    
                    // Store temporary user data in session
                    $_SESSION['temp_registration'] = [
                        'full_name' => $full_name,
                        'email' => $email,
                        'phone' => $phone,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT)
                    ];
                    
                    // Send Email OTP
                    $email_subject = "Verify Your Email - " . SITE_NAME;
                    $email_body = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Email Verification</title>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: linear-gradient(135deg, #FF6B35, #1A5F7A); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                            .content { padding: 30px; background: #f9f9f9; border-radius: 0 0 10px 10px; }
                            .otp-code { font-size: 36px; font-weight: bold; color: #FF6B35; text-align: center; padding: 20px; letter-spacing: 8px; background: #fff; border-radius: 10px; margin: 20px 0; }
                            .button { background: #FF6B35; color: white; padding: 12px 25px; text-decoration: none; border-radius: 30px; display: inline-block; }
                            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>" . SITE_NAME . "</h2>
                                <p>Empowering Quantity Surveyors for the Future of Construction</p>
                            </div>
                            <div class='content'>
                                <h3>Hello " . htmlspecialchars($full_name) . ",</h3>
                                <p>Thank you for registering with BUILD SMART ACADEMY. Please use the verification code below to complete your registration:</p>
                                <div class='otp-code'>$email_otp</div>
                                <p>This code will expire in " . OTP_EXPIRY_MINUTES . " minutes.</p>
                                <p>If you didn't request this, please ignore this email.</p>
                            </div>
                            <div class='footer'>
                                <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    sendEmail($email, $email_subject, $email_body);
                    
                    // Log rate limit attempt
                    $stmt = $db->prepare("INSERT INTO rate_limits (identifier) VALUES (?)");
                    $stmt->execute(['register_' . $ip]);
                    
                    $_SESSION['registration_email'] = $email;
                    
                    redirect('auth/verify-otp.php');
                }
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo SITE_NAME; ?> - Create Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0e27;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        :root {
            --orange: #FF6B35;
            --orange-dark: #e55a2b;
            --blue: #1A5F7A;
            --green: #27AE60;
            --yellow: #F39C12;
            --dark-card: #13172b;
            --gray: #8a8f9e;
            --gray-light: #2a2f45;
            --shadow-lg: 0 15px 45px rgba(0,0,0,0.2);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }
        
        .bg-animation .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.4;
            animation: floatShape 15s ease-in-out infinite;
        }
        
        .shape-1 { top: 10%; left: -5%; width: 300px; height: 300px; background: radial-gradient(circle, var(--orange), transparent); }
        .shape-2 { bottom: 10%; right: -5%; width: 400px; height: 400px; background: radial-gradient(circle, var(--blue), transparent); animation-delay: 3s; }
        .shape-3 { top: 40%; left: 60%; width: 200px; height: 200px; background: radial-gradient(circle, var(--yellow), transparent); animation-delay: 6s; }
        
        @keyframes floatShape {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(50px, -50px) scale(1.1); }
            66% { transform: translate(-30px, 30px) scale(0.9); }
        }
        
        .register-wrapper {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .glass-card {
            max-width: 500px;
            width: 100%;
            background: rgba(19, 23, 43, 0.85);
            backdrop-filter: blur(20px);
            border-radius: 40px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            animation: fadeInScale 0.8s cubic-bezier(0.34, 1.2, 0.64, 1) forwards;
        }
        
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95) translateY(30px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        
        .register-header {
            background: linear-gradient(135deg, var(--orange), var(--blue));
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .register-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-20px, -20px); }
        }
        
        .register-header h1 {
            color: white;
            font-size: 1.8rem;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
            animation: fadeInDown 0.6s ease;
        }
        
        .register-header p {
            color: rgba(255,255,255,0.9);
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.6s ease 0.1s both;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .promo-banner {
            background: linear-gradient(135deg, rgba(243,156,18,0.2), rgba(230,126,34,0.2));
            border: 1px solid rgba(243,156,18,0.3);
            margin: 20px 25px 0;
            padding: 18px;
            border-radius: 20px;
            text-align: center;
            animation: pulseGlow 2s infinite;
        }
        
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(243,156,18,0.4); }
            50% { box-shadow: 0 0 0 10px rgba(243,156,18,0); }
        }
        
        .promo-banner i {
            font-size: 28px;
            color: var(--yellow);
            animation: bounce 1s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        .promo-banner h4 {
            color: var(--yellow);
            font-size: 1rem;
            margin: 8px 0 5px;
        }
        
        .promo-banner p {
            color: #ccc;
            font-size: 0.8rem;
        }
        
        .register-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 22px;
            animation: fadeSlideIn 0.6s ease backwards;
        }
        
        .form-group:nth-child(1) { animation-delay: 0.05s; }
        .form-group:nth-child(2) { animation-delay: 0.1s; }
        .form-group:nth-child(3) { animation-delay: 0.15s; }
        .form-group:nth-child(4) { animation-delay: 0.2s; }
        .form-group:nth-child(5) { animation-delay: 0.25s; }
        
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #ccc;
            font-size: 0.85rem;
            transition: var(--transition);
        }
        
        .form-group:hover label {
            color: var(--orange);
        }
        
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            color: var(--gray);
            transition: var(--transition);
            font-size: 16px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            background: var(--gray-light);
            border: 2px solid transparent;
            border-radius: 16px;
            font-size: 15px;
            color: white;
            transition: var(--transition);
            font-family: inherit;
        }
        
        .form-control:focus {
            border-color: var(--orange);
            outline: none;
            background: rgba(255,107,53,0.1);
            box-shadow: 0 0 0 4px rgba(255,107,53,0.15);
            transform: translateY(-2px);
        }
        
        .form-control.error {
            border-color: #e74c3c;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            cursor: pointer;
            color: var(--gray);
            transition: var(--transition);
        }
        
        .password-toggle:hover {
            color: var(--orange);
            transform: scale(1.1);
        }
        
        .password-strength {
            margin-top: 10px;
            height: 4px;
            border-radius: 2px;
            background: var(--gray-light);
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background 0.3s ease;
        }
        
        .strength-text {
            font-size: 11px;
            margin-top: 6px;
            transition: var(--transition);
        }
        
        .info-text {
            font-size: 11px;
            color: var(--gray);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-register {
            width: 100%;
            background: linear-gradient(135deg, var(--orange), var(--orange-dark));
            color: white;
            padding: 16px;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-register::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-register:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255,107,53,0.4);
        }
        
        .error-message {
            background: linear-gradient(135deg, rgba(231,76,60,0.2), rgba(192,57,43,0.2));
            border: 1px solid #e74c3c;
            color: #ff6b6b;
            padding: 14px 18px;
            border-radius: 16px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .login-link p {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .login-link a {
            color: var(--orange);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .login-link a:hover {
            color: var(--orange-dark);
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .register-form {
                padding: 20px;
            }
            .register-header {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    
    <div class="register-wrapper">
        <div class="glass-card">
            <div class="register-header">
                <h1><i class="fas fa-user-plus"></i> Create Account</h1>
                <p>Join BUILD SMART ACADEMY and start your learning journey</p>
            </div>
            
            <div class="promo-banner">
                <i class="fas fa-gift"></i>
                <h4>🎁 Limited Time Offer!</h4>
                <p>First 20 users get <strong>LIFETIME FREE ACCESS</strong> to all courses!</p>
            </div>
            
            <div class="register-form">
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Full Name *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="full_name" class="form-control" 
                                   placeholder="Enter your full name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Address *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" name="email" class="form-control" 
                                   placeholder="your@email.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="info-text">
                            <i class="fas fa-info-circle"></i> We'll send a verification code to this email
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone-alt"></i> Phone Number (Optional)</label>
                        <div class="input-wrapper">
                            <i class="fas fa-phone-alt input-icon"></i>
                            <input type="tel" name="phone" class="form-control" 
                                   placeholder="+250XXXXXXXXX" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                        <div class="info-text">
                            <i class="fas fa-info-circle"></i> Optional - for course updates and support
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="Create a strong password" required>
                            <i class="fas fa-eye-slash password-toggle" onclick="togglePassword('password', this)"></i>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar"></div>
                        </div>
                        <div class="strength-text" id="strengthText"></div>
                        <div class="info-text">
                            <i class="fas fa-shield-alt"></i> Min 8 chars: uppercase, lowercase, number, special
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-check-circle"></i> Confirm Password *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-check-circle input-icon"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   placeholder="Confirm your password" required>
                            <i class="fas fa-eye-slash password-toggle" onclick="togglePassword('confirm_password', this)"></i>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-register">
                        <i class="fas fa-paper-plane"></i> Register & Send OTP
                    </button>
                </form>
                
                <div class="login-link">
                    <p>Already have an account? <a href="<?php echo SITE_URL; ?>auth/login.php">Sign in here</a></p>
                    <p style="margin-top: 12px; font-size: 12px;">
                        <i class="fas fa-shield-alt"></i> Your information is encrypted and secure
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(fieldId, element) {
            const field = document.getElementById(fieldId);
            if (field.type === 'password') {
                field.type = 'text';
                element.classList.remove('fa-eye-slash');
                element.classList.add('fa-eye');
            } else {
                field.type = 'password';
                element.classList.remove('fa-eye');
                element.classList.add('fa-eye-slash');
            }
        }
        
        // Password strength checker
        const passwordInput = document.getElementById('password');
        const strengthBar = document.querySelector('.strength-bar');
        const strengthText = document.getElementById('strengthText');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let message = '';
                let color = '';
                
                if (password.length >= 8) strength++;
                if (password.match(/[a-z]/)) strength++;
                if (password.match(/[A-Z]/)) strength++;
                if (password.match(/[0-9]/)) strength++;
                if (password.match(/[@$!%*?&]/)) strength++;
                
                switch(strength) {
                    case 0: case 1: message = 'Very Weak'; color = '#e74c3c'; strengthBar.style.width = '20%'; break;
                    case 2: message = 'Weak'; color = '#e67e22'; strengthBar.style.width = '40%'; break;
                    case 3: message = 'Medium'; color = '#f39c12'; strengthBar.style.width = '60%'; break;
                    case 4: message = 'Strong'; color = '#27ae60'; strengthBar.style.width = '80%'; break;
                    case 5: message = 'Very Strong'; color = '#2ecc71'; strengthBar.style.width = '100%'; break;
                }
                
                strengthBar.style.background = color;
                strengthText.textContent = message;
                strengthText.style.color = color;
            });
        }
        
        // Confirm password validation
        const confirmInput = document.getElementById('confirm_password');
        if (confirmInput) {
            confirmInput.addEventListener('input', function() {
                if (this.value !== passwordInput.value) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });
        }
        
        // Form submission animation
        const form = document.getElementById('registerForm');
        if (form) {
            form.addEventListener('submit', function() {
                const submitBtn = document.querySelector('.btn-register');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending OTP...';
                submitBtn.disabled = true;
            });
        }
    </script>
</body>
</html>