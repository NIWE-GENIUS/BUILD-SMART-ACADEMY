<?php
// create-admin.php
// Run this file once to create admin account

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

$db = Database::getConnection();

// Delete existing
$stmt = $db->prepare("DELETE FROM users WHERE email = ?");
$stmt->execute(['irutabyosephilemon78@gmail.com']);

// Create new admin with password 'Irut@200206'
$password = 'Irut@200206';
$password_hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare("
    INSERT INTO users (full_name, email, phone, password_hash, role, is_verified, lifetime_free) 
    VALUES (?, ?, ?, ?, 'super_admin', 1, 1)
");

$result = $stmt->execute([
    'QS Philemon IRUTABYOSE',
    'irutabyosephilemon78@gmail.com',
    '+250793000960',
    $password_hash
]);

if ($result) {
    echo "✅ Admin account created successfully!<br>";
    echo "Email: irutabyosephilemon78@gmail.com<br>";
    echo "Password: Irut@200206<br>";
    echo "<br><a href='auth/login.php'>Click here to login</a>";
} else {
    echo "❌ Failed to create admin account.";
}

// Display the hash for reference
echo "<br><br>Password Hash: " . $password_hash;
?>