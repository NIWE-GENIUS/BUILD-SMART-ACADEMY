<?php
// config/config.php
// BUILD SMART ACADEMY - Configuration File
// Version: 3.0 - Complete

// =============================================
// DATABASE CONFIGURATION
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'buildsmart_academy');

// =============================================
// SITE CONFIGURATION
// =============================================

define('SITE_URL', 'http://localhost/buildsmartacademy/');
define('SITE_NAME', 'BUILD SMART ACADEMY');
define('SITE_TAGLINE', 'Empowering Quantity Surveyors for the Future of Construction');

// =============================================
// ENVIRONMENT (development or production)
// =============================================

define('ENVIRONMENT', 'development');

// =============================================
// EMAIL CONFIGURATION (Gmail SMTP)
// =============================================

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'irutabyosephilemon78@gmail.com');
define('SMTP_PASS', 'wlfncanltdwqjfvu'); // Your 16-char App Password (no spaces)
define('SMTP_FROM_EMAIL', 'irutabyosephilemon78@gmail.com');
define('SMTP_FROM_NAME', 'BUILD SMART ACADEMY');

// =============================================
// SMS CONFIGURATION (Africa's Talking - for production)
// =============================================

define('SMS_API_KEY', 'your-africastalking-api-key');
define('SMS_USERNAME', 'sandbox');
define('SMS_SENDER_ID', 'BSACADEMY');

// =============================================
// OTP SETTINGS
// =============================================

define('OTP_LENGTH', 6);
define('OTP_EXPIRY_MINUTES', 10);
define('OTP_MAX_ATTEMPTS', 5);
define('OTP_LOCKOUT_MINUTES', 15);

// =============================================
// COURSE SETTINGS
// =============================================

define('UNIT_TEST_PASSING_SCORE', 70);
define('FINAL_EXAM_PASSING_SCORE', 80);
define('MAX_FINAL_EXAM_RETAKES', 5);
define('FINAL_EXAM_COOLDOWN_HOURS', 24);
define('UNIT_TEST_TIME_LIMIT_MINUTES', 60);
define('FINAL_EXAM_TIME_LIMIT_MINUTES', 120);

// =============================================
// CERTIFICATE SETTINGS
// =============================================

define('CERTIFICATE_ISSUER_NAME', 'QS Philemon IRUTABYOSE');
define('CERTIFICATE_ISSUER_TITLE', 'Super Administrator & Certified Quantity Surveyor');

// =============================================
// UPLOAD SETTINGS
// =============================================

define('MAX_FILE_SIZE', 5242880); // 5MB
define('PROFILE_PICTURE_PATH', __DIR__ . '/../uploads/profile_pictures/');
define('CERTIFICATE_PATH', __DIR__ . '/../uploads/certificates/');
define('COURSE_MATERIALS_PATH', __DIR__ . '/../uploads/course_materials/');

// =============================================
// FIRST 20 USERS PROMOTION
// =============================================

define('PROMOTION_ENABLED', true);
define('PROMOTION_MAX_USERS', 20);
define('PROMOTION_MESSAGE', 'First 20 users get LIFETIME FREE access to all courses!');

// =============================================
// START SESSION
// =============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =============================================
// TIMEZONE SETTING
// =============================================

date_default_timezone_set('Africa/Kigali');

// =============================================
// ERROR REPORTING (Development only)
// =============================================

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// =============================================
// CREATE REQUIRED DIRECTORIES
// =============================================

$directories = [
    PROFILE_PICTURE_PATH,
    CERTIFICATE_PATH,
    COURSE_MATERIALS_PATH,
    __DIR__ . '/../uploads/badges/',
    __DIR__ . '/../uploads/course_images/',
    __DIR__ . '/../uploads/blog_images/'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

// =============================================
// CSRF TOKEN GENERATION
// =============================================

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// =============================================
// SESSION REGENERATION (Security)
// =============================================

if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// =============================================
// CONFIGURATION COMPLETE
// =============================================
?>