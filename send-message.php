<?php
// admin/api/send-message.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$conversation_id = $data['conversation_id'] ?? 0;
$message = sanitizeInput($data['message'] ?? '');

if (!$conversation_id || empty($message)) {
    echo json_encode(['error' => 'Invalid data']);
    exit();
}

$db = Database::getConnection();
$admin_id = getCurrentUserId();

// Get conversation details
$stmt = $db->prepare("SELECT user_id FROM chat_conversations WHERE id = ?");
$stmt->execute([$conversation_id]);
$conv = $stmt->fetch();

if ($conv) {
    $stmt = $db->prepare("
        INSERT INTO chat_messages (conversation_id, sender_id, receiver_id, message) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$conversation_id, $admin_id, $conv['user_id'], $message]);
    
    // Update last message time
    $stmt = $db->prepare("UPDATE chat_conversations SET last_message_at = NOW() WHERE id = ?");
    $stmt->execute([$conversation_id]);
    
    // Create notification for user
    createNotification($conv['user_id'], 'New Message from Admin', 'You have a new message from support.', 'chat');
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Conversation not found']);
}
?>