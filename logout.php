<?php
// auth/logout.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Clear remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    
    // Delete token from database
    if (isLoggedIn()) {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM user_tokens WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
}

// Logout the user
logout();

// Redirect to login page with logout success message
redirect('auth/login.php?logout=success');
?>