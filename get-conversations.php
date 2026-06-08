<?php
// admin/api/get-conversations.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$db = Database::getConnection();
$admin_id = getCurrentUserId();

$stmt = $db->prepare("
    SELECT c.*, u.full_name as user_name,
           (SELECT message FROM chat_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
           (SELECT COUNT(*) FROM chat_messages WHERE conversation_id = c.id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM chat_conversations c
    JOIN users u ON c.user_id = u.id
    WHERE c.status IN ('active', 'pending')
    ORDER BY c.last_message_at DESC
");
$stmt->execute([$admin_id]);
$conversations = $stmt->fetchAll();

echo json_encode(['conversations' => $conversations]);
?>