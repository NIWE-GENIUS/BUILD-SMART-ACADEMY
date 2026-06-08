<?php
// auth/verify-otp.php
// BUILD SMART ACADEMY - OTP Verification Page (Email Only)

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('dashboard/');
}

// Check if there's a pending registration
if (!isset($_SESSION['temp_registration'])) {
    redirect('auth/register.php');
}

$page_title = 'Verify OTP';
$error = '';
$success = '';

// Get registration info
$temp_data = $_SESSION['temp_registration'];
$email = $temp_data['email'];

// Process OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_otp = sanitizeInput($_POST['email_otp'] ?? '');
    
    if (empty($email_otp)) {
        $error = 'Please enter the verification code.';
    } else {
        $db = Database::getConnection();
        
        // Verify OTP from database
        $stmt = $db->prepare("
            SELECT * FROM otp_verification 
            WHERE email = ? AND email_otp = ? AND email_otp_expires > NOW()
        ");
        $stmt->execute([$email, $email_otp]);
        $otp_record = $stmt->fetch();
        
        if (!$otp_record) {
            $error = 'Invalid or expired verification code. Please request a new one.';
        } else {
            // Check how many users already exist (for first 20 promotion)
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
            $stmt->execute();
            $user_count = $stmt->fetch();
            $total_users = $user_count['total'];
            $is_lifetime_free = ($total_users < PROMOTION_MAX_USERS);
            
            // Create user account
            $stmt = $db->prepare("
                INSERT INTO users (full_name, email, phone, password_hash, role, is_verified, lifetime_free)
                VALUES (?, ?, ?, ?, 'user', 1, ?)
            ");
            $stmt->execute([
                $temp_data['full_name'],
                $email,
                $temp_data['phone'],
                $temp_data['password_hash'],
                $is_lifetime_free ? 1 : 0
            ]);
            
            $user_id = $db->lastInsertId();
            
            // Clean up OTP records
            $stmt = $db->prepare("DELETE FROM otp_verification WHERE email = ?");
            $stmt->execute([$email]);
            
            // Clear temporary session data
            unset($_SESSION['temp_registration']);
            
            // Create user session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $temp_data['full_name'];
            $_SESSION['user_email'] = $email;
            $_SESSION['role'] = 'user';
            $_SESSION['lifetime_free'] = $is_lifetime_free;
            
            // Create welcome notification
            $welcome_title = "Welcome to " . SITE_NAME . "!";
            $welcome_message = "Hello " . $temp_data['full_name'] . ", welcome to BUILD SMART ACADEMY! ";
            if ($is_lifetime_free) {
                $welcome_message .= "🎉 Congratulations! You are one of our first 20 users. You have LIFETIME FREE ACCESS to all courses!";
            } else {
                $welcome_message .= "Start exploring our courses and advance your quantity surveying career.";
            }
            
            createNotification($user_id, $welcome_title, $welcome_message, 'welcome');
            
            // Redirect to dashboard
            redirect('dashboard/');
        }
    }
}

// Handle resend OTP
if (isset($_GET['resend'])) {
    $db = Database::getConnection();
    
    // Generate new OTP
    $email_otp = generateOTP(OTP_LENGTH);
    $email_expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
    
    // Update OTP in database
    $stmt = $db->prepare("
        UPDATE otp_verification 
        SET email_otp = ?, email_otp_expires = ?, attempt_count = 0
        WHERE email = ?
    ");
    $stmt->execute([$email_otp, $email_expiry, $email]);
    
    // Send new OTP email
    $email_subject = "Your New Verification Code - " . SITE_NAME;
    $email_body = "
    <!DOCTYPE html>
    <html>
    <head>
        <title>New Verification Code</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #FF6B35; color: white; padding: 20px; text-align: center; }
            .otp-code { font-size: 32px; font-weight: bold; color: #FF6B35; text-align: center; padding: 20px; letter-spacing: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>" . SITE_NAME . "</h2>
            </div>
            <div class='otp-code'>$email_otp</div>
            <p>This code will expire in " . OTP_EXPIRY_MINUTES . " minutes.</p>
        </div>
    </body>
    </html>
    ";
    
    sendEmail($email, $email_subject, $email_body);
    
    $success = "New verification code sent to your email.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo SITE_NAME; ?> - Verify Your Account</title>
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
            --blue: #1A5F7A;
            --green: #27AE60;
            --dark-card: #13172b;
            --gray: #8a8f9e;
            --gray-light: #2a2f45;
            --shadow-lg: 0 15px 45px rgba(0,0,0,0.2);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
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
        .shape-3 { top: 40%; left: 60%; width: 200px; height: 200px; background: radial-gradient(circle, var(--green), transparent); animation-delay: 6s; }
        
        @keyframes floatShape {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(50px, -50px) scale(1.1); }
            66% { transform: translate(-30px, 30px) scale(0.9); }
        }
        
        .verify-wrapper {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .glass-card {
            max-width: 480px;
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
        
        .verify-header {
            background: linear-gradient(135deg, var(--orange), var(--blue));
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .verify-header::before {
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
        
        .verify-header h1 {
            color: white;
            font-size: 1.8rem;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
            animation: fadeInDown 0.6s ease;
        }
        
        .verify-header p {
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
        
        .verify-body {
            padding: 35px;
        }
        
        .info-box {
            background: rgba(255,107,53,0.1);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
            border: 1px solid rgba(255,107,53,0.2);
            animation: slideIn 0.6s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .info-box i {
            font-size: 32px;
            color: var(--orange);
            margin-bottom: 10px;
        }
        
        .info-box p {
            color: #ccc;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .info-box strong {
            color: white;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 25px;
            animation: fadeSlideUp 0.6s ease 0.1s both;
        }
        
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
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
            font-size: 18px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 16px 15px 50px;
            background: var(--gray-light);
            border: 2px solid transparent;
            border-radius: 16px;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 4px;
            color: white;
            text-align: center;
            transition: var(--transition);
            font-family: monospace;
        }
        
        .form-control:focus {
            border-color: var(--orange);
            outline: none;
            background: rgba(255,107,53,0.1);
            box-shadow: 0 0 0 4px rgba(255,107,53,0.15);
            transform: translateY(-2px);
        }
        
        .timer-container {
            background: var(--gray-light);
            border-radius: 16px;
            padding: 15px;
            text-align: center;
            margin-bottom: 25px;
            animation: fadeSlideUp 0.6s ease 0.15s both;
        }
        
        .timer {
            font-size: 32px;
            font-weight: 700;
            font-family: monospace;
            color: var(--orange);
            letter-spacing: 2px;
        }
        
        .timer.warning {
            color: #e74c3c;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        .btn-verify {
            width: 100%;
            background: linear-gradient(135deg, var(--orange), #e55a2b);
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
        
        .btn-verify::before {
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
        
        .btn-verify:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-verify:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255,107,53,0.4);
        }
        
        .resend-link {
            text-align: center;
            margin-top: 20px;
            animation: fadeSlideUp 0.6s ease 0.2s both;
        }
        
        .resend-link a {
            color: var(--orange);
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .resend-link a:hover {
            color: #e55a2b;
            transform: translateX(3px);
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .back-link a {
            color: var(--gray);
            text-decoration: none;
            font-size: 0.85rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-link a:hover {
            color: var(--orange);
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
        
        .success-message {
            background: linear-gradient(135deg, rgba(39,174,96,0.2), rgba(46,204,113,0.2));
            border: 1px solid var(--green);
            color: #6fef9f;
            padding: 14px 18px;
            border-radius: 16px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.5s ease;
        }
        
        @media (max-width: 480px) {
            .verify-body {
                padding: 25px 20px;
            }
            .timer {
                font-size: 24px;
            }
            .form-control {
                font-size: 16px;
                letter-spacing: 2px;
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
    
    <div class="verify-wrapper">
        <div class="glass-card">
            <div class="verify-header">
                <h1><i class="fas fa-shield-alt"></i> Verify Your Email</h1>
                <p>Enter the verification code sent to your email</p>
            </div>
            
            <div class="verify-body">
                <div class="info-box">
                    <i class="fas fa-envelope"></i>
                    <p>Verification code sent to:</p>
                    <p><strong><?php echo htmlspecialchars($email); ?></strong></p>
                </div>
                
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="timer-container">
                    <div class="timer" id="timer">10:00</div>
                    <p style="font-size: 12px; color: var(--gray); margin-top: 5px;">Code expires in</p>
                </div>
                
                <form method="POST" action="" id="verifyForm">
                    <div class="form-group">
                        <label for="email_otp"><i class="fas fa-key"></i> Verification Code</label>
                        <div class="input-wrapper">
                            <i class="fas fa-key input-icon"></i>
                            <input type="text" id="email_otp" name="email_otp" class="form-control" 
                                   placeholder="000000" maxlength="6" autocomplete="off" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-verify" id="verifyBtn">
                        <i class="fas fa-check-circle"></i> Verify & Create Account
                    </button>
                </form>
                
                <div class="resend-link">
                    <a href="?resend=1" id="resendLink">
                        <i class="fas fa-redo-alt"></i> Didn't receive code? Resend
                    </a>
                </div>
                
                <div class="back-link">
                    <a href="<?php echo SITE_URL; ?>auth/register.php">
                        <i class="fas fa-arrow-left"></i> Back to Registration
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Timer functionality
        let timeLeft = <?php echo OTP_EXPIRY_MINUTES * 60; ?>;
        const timerElement = document.getElementById('timer');
        let timerInterval;
        
        function startTimer() {
            timerInterval = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerElement.textContent = 'Expired';
                    timerElement.classList.add('warning');
                    document.getElementById('verifyBtn').disabled = true;
                } else {
                    timeLeft--;
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    
                    if (timeLeft <= 60) {
                        timerElement.classList.add('warning');
                    }
                }
            }, 1000);
        }
        
        startTimer();
        
        // Form submission animation
        const form = document.getElementById('verifyForm');
        const verifyBtn = document.getElementById('verifyBtn');
        
        form.addEventListener('submit', function() {
            verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            verifyBtn.disabled = true;
        });
        
        // Auto-hide error messages after 5 seconds
        setTimeout(() => {
            const errors = document.querySelectorAll('.error-message');
            errors.forEach(error => {
                setTimeout(() => {
                    error.style.opacity = '0';
                    setTimeout(() => error.remove(), 500);
                }, 5000);
            });
        }, 100);
    </script>
</body>
</html>