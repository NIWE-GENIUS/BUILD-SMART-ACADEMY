<?php
// admin/api/get-messages.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$conversation_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;

if (!$conversation_id) {
    echo json_encode(['error' => 'Invalid conversation']);
    exit();
}

$db = Database::getConnection();
$admin_id = getCurrentUserId();

// Mark messages as read
$stmt = $db->prepare("
    UPDATE chat_messages SET is_read = 1 
    WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0
");
$stmt->execute([$conversation_id, $admin_id]);

// Get messages
$stmt = $db->prepare("
    SELECT m.*, 
           CASE WHEN m.sender_id = ? THEN 'sent' ELSE 'received' END as direction,
           DATE_FORMAT(m.created_at, '%Y-%m-%d %H:%i:%s') as formatted_date
    FROM chat_messages m
    WHERE m.conversation_id = ?
    ORDER BY m.created_at ASC
");
$stmt->execute([$admin_id, $conversation_id]);
$messages = $stmt->fetchAll();

foreach ($messages as &$msg) {
    $msg['time_ago'] = timeAgo($msg['created_at']);
}

echo json_encode(['messages' => $messages]);
?>