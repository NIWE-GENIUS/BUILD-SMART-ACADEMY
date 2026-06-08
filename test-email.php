<?php
// test-email.php
// Test your email configuration

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

$result_message = '';
$result_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_email = $_POST['email'] ?? 'irutabyosephilemon78@gmail.com';
    
    $subject = "BUILD SMART ACADEMY - Test Email";
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Test Email</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #FF6B35; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; background: #f9f9f9; }
            .otp-code { font-size: 32px; font-weight: bold; color: #FF6B35; text-align: center; padding: 20px; letter-spacing: 5px; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>BUILD SMART ACADEMY</h2>
            </div>
            <div class='content'>
                <h3>✅ Email Test Successful!</h3>
                <p>If you are reading this, your email configuration is working perfectly.</p>
                <div class='otp-code'>TEST-123456</div>
                <p>This is a test email from BUILD SMART ACADEMY.</p>
                <p>Your registration OTPs will look similar to this.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2024 BUILD SMART ACADEMY</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    if (sendEmail($test_email, $subject, $body)) {
        $result_message = "✅ Test email sent successfully to: $test_email! Check your inbox (and spam folder).";
        $result_type = "success";
    } else {
        $result_message = "❌ Failed to send test email. Check error logs in C:\\xampp\\php\\logs\\php_error_log";
        $result_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email - BUILD SMART ACADEMY</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #0a0e27, #13172b);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            max-width: 550px;
            width: 100%;
            background: rgba(19, 23, 43, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
        }
        h1 {
            color: #FF6B35;
            font-size: 1.8rem;
            margin-bottom: 20px;
            text-align: center;
        }
        .success {
            background: rgba(39,174,96,0.2);
            border: 1px solid #27ae60;
            color: #6fef9f;
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: center;
        }
        .error {
            background: rgba(231,76,60,0.2);
            border: 1px solid #e74c3c;
            color: #ff6b6b;
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: #ccc;
            margin-bottom: 8px;
        }
        input {
            width: 100%;
            padding: 14px 16px;
            background: #2a2f45;
            border: 2px solid transparent;
            border-radius: 16px;
            font-size: 16px;
            color: white;
            transition: all 0.3s;
        }
        input:focus {
            border-color: #FF6B35;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255,107,53,0.2);
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #FF6B35, #e55a2b);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255,107,53,0.3);
        }
        .info-box {
            background: rgba(255,107,53,0.1);
            border-radius: 15px;
            padding: 15px;
            margin-top: 25px;
            font-size: 13px;
            color: #aaa;
        }
        .info-box code {
            background: #1a1f35;
            padding: 2px 8px;
            border-radius: 5px;
            font-family: monospace;
            color: #FF6B35;
        }
        hr {
            border-color: rgba(255,255,255,0.1);
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Test Email Configuration</h1>
        
        <?php if ($result_message): ?>
            <div class="<?php echo $result_type; ?>">
                <?php echo $result_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>📨 Send test email to:</label>
                <input type="email" name="email" value="irutabyosephilemon78@gmail.com" required>
            </div>
            <button type="submit">📤 Send Test Email</button>
        </form>
        
        <hr>
        
        <div class="info-box">
            <strong>📌 Important Steps:</strong><br><br>
            1. Get Gmail App Password:<br>
            <code>https://myaccount.google.com/apppasswords</code><br><br>
            2. Update <code>config/functions.php</code> with your 16-char app password<br><br>
            3. Check spam folder if email doesn't appear in inbox<br><br>
            4. View error log: <code>C:\xampp\php\logs\php_error_log</code>
        </div>
    </div>
</body>
</html>