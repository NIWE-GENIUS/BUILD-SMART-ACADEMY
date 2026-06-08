<?php
// payment/webhook.php
// Webhook endpoint for payment gateways (IyziPay, Stripe, etc.)

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

// Get webhook payload
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit();
}

$db = Database::getConnection();

// Verify signature (implement based on your payment gateway)
// $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
// if (!verifySignature($payload, $signature)) {
//     http_response_code(401);
//     echo json_encode(['error' => 'Invalid signature']);
//     exit();
// }

$transaction_id = $data['transaction_id'] ?? '';
$status = $data['status'] ?? '';

if ($status == 'success') {
    // Find pending payment
    $stmt = $db->prepare("SELECT id, user_id, course_id FROM payments WHERE transaction_id = ? AND status = 'pending'");
    $stmt->execute([$transaction_id]);
    $payment = $stmt->fetch();
    
    if ($payment) {
        // Update payment status
        $stmt = $db->prepare("UPDATE payments SET status = 'completed', paid_at = NOW() WHERE id = ?");
        $stmt->execute([$payment['id']]);
        
        // Enroll user
        $stmt = $db->prepare("INSERT INTO enrollments (user_id, course_id, payment_id) VALUES (?, ?, ?)");
        $stmt->execute([$payment['user_id'], $payment['course_id'], $payment['id']]);
        
        // Initialize progress
        $stmt = $db->prepare("SELECT id FROM course_units WHERE course_id = ? ORDER BY unit_number ASC");
        $stmt->execute([$payment['course_id']]);
        $units = $stmt->fetchAll();
        
        $stmt = $db->prepare("INSERT INTO user_unit_progress (user_id, course_id, unit_id, status) VALUES (?, ?, ?, 'locked')");
        foreach ($units as $unit) {
            $stmt->execute([$payment['user_id'], $payment['course_id'], $unit['id']]);
        }
        
        // Unlock first unit
        if (count($units) > 0) {
            $stmt = $db->prepare("UPDATE user_unit_progress SET status = 'in_progress' WHERE user_id = ? AND course_id = ? AND unit_id = ?");
            $stmt->execute([$payment['user_id'], $payment['course_id'], $units[0]['id']]);
        }
        
        // Send notification
        createNotification($payment['user_id'], 'Payment Successful', 'Your enrollment is complete. Start learning now!', 'payment');
        
        echo json_encode(['success' => true]);
        exit();
    }
}

echo json_encode(['success' => false]);
?>