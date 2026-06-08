<?php
// auth/send-otp.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
$phone = $data['phone'] ?? '';
$full_name = $data['full_name'] ?? '';
$password = $data['password'] ?? '';

// Validate inputs
if (empty($email) || empty($phone) || empty($full_name) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Check if email already exists
$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit();
}

// Check if phone already exists
$stmt = $db->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->execute([$phone]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Phone number already registered']);
    exit();
}

// Check rate limiting
$ip = $_SERVER['REMOTE_ADDR'];
$stmt = $db->prepare("SELECT COUNT(*) FROM otp_verification WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) AND ? = ?");
// Simple rate limit check
$stmt = $db->prepare("SELECT COUNT(*) FROM otp_verification WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$stmt->execute([$email]);
$attempts = $stmt->fetchColumn();

if ($attempts >= 3) {
    echo json_encode(['success' => false, 'message' => 'Too many attempts. Please try again later.']);
    exit();
}

// Generate OTPs
$email_otp = generateOTP(OTP_LENGTH);
$phone_otp = generateOTP(OTP_LENGTH);

// Set expiry times
$email_expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
$phone_expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));

// Delete any existing OTP for this email/phone
$stmt = $db->prepare("DELETE FROM otp_verification WHERE email = ? OR phone = ?");
$stmt->execute([$email, $phone]);

// Store OTPs in database
$stmt = $db->prepare("
    INSERT INTO otp_verification (email, phone, email_otp, phone_otp, email_otp_expires, phone_otp_expires) 
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([$email, $phone, $email_otp, $phone_otp, $email_expiry, $phone_expiry]);

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
<html>
<head>
    <title>Email Verification</title>
</head>
<body>
    <h2>Welcome to " . SITE_NAME . "!</h2>
    <p>Hello <strong>" . htmlspecialchars($full_name) . "</strong>,</p>
    <p>Your email verification code is:</p>
    <h1 style=\"color: #FF6B35; font-size: 32px;\">$email_otp</h1>
    <p>This code will expire in " . OTP_EXPIRY_MINUTES . " minutes.</p>
    <p>If you didn't request this, please ignore this email.</p>
    <hr>
    <p>Best regards,<br>QS Philemon IRUTABYOSE<br>" . SITE_NAME . "</p>
</body>
</html>
";

$email_sent = sendEmail($email, $email_subject, $email_body);

// Send SMS OTP (using Africa's Talking - placeholder function)
$sms_message = "Your " . SITE_NAME . " verification code is: $phone_otp. Valid for " . OTP_EXPIRY_MINUTES . " minutes.";
$sms_sent = sendSMS($phone, $sms_message);

// For development without SMS, log the OTP
if (!$sms_sent) {
    error_log("SMS OTP for $phone: $phone_otp");
}

if ($email_sent) {
    echo json_encode([
        'success' => true, 
        'message' => 'Verification codes sent to your email and phone'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to send verification email. Please try again.'
    ]);
}
?>