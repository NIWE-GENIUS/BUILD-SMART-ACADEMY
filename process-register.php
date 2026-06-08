<?php
// auth/process-register.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

// Check if there's a pending registration
if (!isset($_SESSION['temp_registration'])) {
    echo json_encode(['success' => false, 'message' => 'No pending registration']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$email_otp = $data['email_otp'] ?? '';
$phone_otp = $data['phone_otp'] ?? '';
$temp_data = $_SESSION['temp_registration'];

$db = Database::getConnection();

// Verify OTPs from database
$stmt = $db->prepare("
    SELECT * FROM otp_verification 
    WHERE email = ? AND phone = ? 
    AND email_otp = ? AND phone_otp = ?
    AND email_otp_expires > NOW() AND phone_otp_expires > NOW()
");
$stmt->execute([$temp_data['email'], $temp_data['phone'], $email_otp, $phone_otp]);
$otp_record = $stmt->fetch();

if (!$otp_record) {
    // Check if OTP exists but expired
    $stmt = $db->prepare("
        SELECT * FROM otp_verification 
        WHERE email = ? AND phone = ?
    ");
    $stmt->execute([$temp_data['email'], $temp_data['phone']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired verification code. Please request a new one.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please try again.']);
    }
    exit();
}

// Check how many users already exist (for first 20 promotion)
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$user_count = $stmt->fetch();
$total_users = $user_count['total'];
$is_lifetime_free = ($total_users < 20); // First 20 users get lifetime free

// Create user account
$stmt = $db->prepare("
    INSERT INTO users (full_name, email, phone, password_hash, role, is_verified, lifetime_free)
    VALUES (?, ?, ?, ?, 'user', 1, ?)
");
$stmt->execute([
    $temp_data['full_name'],
    $temp_data['email'],
    $temp_data['phone'],
    $temp_data['password_hash'],
    $is_lifetime_free ? 1 : 0
]);

$user_id = $db->lastInsertId();

// Clean up OTP records
$stmt = $db->prepare("DELETE FROM otp_verification WHERE email = ?");
$stmt->execute([$temp_data['email']]);

// Clear temporary session data
unset($_SESSION['temp_registration']);

// Create user session
$_SESSION['user_id'] = $user_id;
$_SESSION['user_name'] = $temp_data['full_name'];
$_SESSION['user_email'] = $temp_data['email'];
$_SESSION['role'] = 'user';

// Create welcome notification
$welcome_title = "Welcome to " . SITE_NAME . "!";
$welcome_message = "Hello " . $temp_data['full_name'] . ", welcome to BUILD SMART ACADEMY! ";
if ($is_lifetime_free) {
    $welcome_message .= "🎉 Congratulations! You are one of our first 20 users. You have LIFETIME FREE ACCESS to all courses!";
} else {
    $welcome_message .= "Start exploring our courses and advance your quantity surveying career.";
}

$stmt = $db->prepare("
    INSERT INTO notifications (user_id, title, message, type) 
    VALUES (?, ?, ?, 'welcome')
");
$stmt->execute([$user_id, $welcome_title, $welcome_message]);

$message = "Account created successfully! ";
if ($is_lifetime_free) {
    $message .= "🎉 You are one of our first 20 users! You have lifetime free access to all courses.";
} else {
    $message .= "Welcome to BUILD SMART ACADEMY!";
}

echo json_encode([
    'success' => true,
    'message' => $message,
    'lifetime_free' => $is_lifetime_free
]);
?>