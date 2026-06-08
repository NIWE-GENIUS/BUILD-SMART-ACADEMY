<?php
// setup-admin.php
// Run this file once to create admin account

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

echo "<h1>Setting up Admin Account</h1>";

$db = Database::getConnection();

// Check if connection works
if (!$db) {
    die("Database connection failed!");
}

echo "✅ Database connected<br>";

// Delete existing admin
$stmt = $db->prepare("DELETE FROM users WHERE email = ?");
$stmt->execute(['irutabyosephilemon78@gmail.com']);
echo "✅ Removed existing account (if any)<br>";

// Create new admin with password 'Irut@200206'
$full_name = 'QS Philemon IRUTABYOSE';
$email = 'irutabyosephilemon78@gmail.com';
$phone = '+250793000960';
$password = 'Irut@200206';
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'super_admin';
$is_verified = 1;
$lifetime_free = 1;

$stmt = $db->prepare("
    INSERT INTO users (full_name, email, phone, password_hash, role, is_verified, lifetime_free, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");

$result = $stmt->execute([$full_name, $email, $phone, $password_hash, $role, $is_verified, $lifetime_free]);

if ($result) {
    echo "✅ Admin account created successfully!<br>";
    echo "<br><strong>Login Credentials:</strong><br>";
    echo "Email: <code>" . $email . "</code><br>";
    echo "Password: <code>" . $password . "</code><br>";
    echo "<br><a href='auth/login.php' style='background: #FF6B35; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Click here to Login →</a>";
} else {
    echo "❌ Failed to create admin account.<br>";
    print_r($stmt->errorInfo());
}

// Display user count
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$count = $stmt->fetch();
echo "<br><br>Total users in database: " . $count['total'];
?>