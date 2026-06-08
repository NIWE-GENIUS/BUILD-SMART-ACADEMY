<?php
// payment/momo-pay.php
// Mobile Money Payment Processing (Simulated)

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

// Check if there's a pending payment
if (!isset($_SESSION['pending_payment'])) {
    redirect('courses/');
}

$payment = $_SESSION['pending_payment'];

if ($payment['method'] != 'momo') {
    redirect('payment/checkout.php?course_id=' . $payment['course_id']);
}

$db = Database::getConnection();
$user_id = getCurrentUserId();

// For demo purposes, we'll simulate a successful payment
// In production, integrate with IyziPay or Africa's Talking API

// Simulate payment processing
$payment_success = true; // Simulate success
$transaction_reference = 'MOMO_' . time() . '_' . $user_id;

if ($payment_success) {
    // Update payment record
    $stmt = $db->prepare("
        UPDATE payments 
        SET status = 'completed', transaction_id = ?, paid_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$transaction_reference, $payment['payment_id']]);
    
    // Enroll user in course
    $stmt = $db->prepare("
        INSERT INTO enrollments (user_id, course_id, payment_id)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user_id, $payment['course_id'], $payment['payment_id']]);
    
    // Initialize unit progress
    $stmt = $db->prepare("SELECT id FROM course_units WHERE course_id = ? ORDER BY unit_number ASC");
    $stmt->execute([$payment['course_id']]);
    $units = $stmt->fetchAll();
    
    $stmt = $db->prepare("
        INSERT INTO user_unit_progress (user_id, course_id, unit_id, status)
        VALUES (?, ?, ?, 'locked')
    ");
    foreach ($units as $unit) {
        $stmt->execute([$user_id, $payment['course_id'], $unit['id']]);
    }
    
    // Unlock first unit
    if (count($units) > 0) {
        $stmt = $db->prepare("
            UPDATE user_unit_progress SET status = 'in_progress'
            WHERE user_id = ? AND course_id = ? AND unit_id = ?
        ");
        $stmt->execute([$user_id, $payment['course_id'], $units[0]['id']]);
    }
    
    // Create notification
    createNotification($user_id, 'Payment Successful', 'Your payment of ' . number_format($payment['amount']) . ' RWF for course enrollment was successful.', 'payment');
    
    // Clear session
    unset($_SESSION['pending_payment']);
    
    // Redirect to success page
    header("Location: " . SITE_URL . "payment/success.php?course_id=" . $payment['course_id']);
    exit();
} else {
    // Payment failed
    $stmt = $db->prepare("UPDATE payments SET status = 'failed' WHERE id = ?");
    $stmt->execute([$payment['payment_id']]);
    
    unset($_SESSION['pending_payment']);
    header("Location: " . SITE_URL . "payment/failed.php");
    exit();
}
?>