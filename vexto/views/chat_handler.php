<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('No autorizado');
}

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/core/helpers.php';

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];

if ($action === 'send') {
    $receiverId = (int)$_POST['receiver_id'];
    $propertyId = (int)$_POST['property_id'];
    $message = sanitize($_POST['message']);

    if (empty($message)) {
        jsonResponse(false, 'El mensaje no puede estar vacío');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, property_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $receiverId, $propertyId, $message]);
        jsonResponse(true, 'Mensaje enviado');
    } catch (PDOException $e) {
        logEvent('Chat send error: ' . $e->getMessage(), 'error');
        jsonResponse(false, 'Error al enviar el mensaje');
    }
} elseif ($action === 'fetch') {
    $propertyId = (int)$_GET['property_id'];
    $otherUserId = (int)$_GET['other_user_id'];

    try {
        $stmt = $pdo->prepare("
            SELECT m.*, u.nombre as sender_name 
            FROM messages m 
            JOIN users u ON m.sender_id = u.id 
            WHERE m.property_id = ? 
            AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$propertyId, $userId, $otherUserId, $otherUserId, $userId]);
        $messages = $stmt->fetchAll();

        // Marcar como leídos
        $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND property_id = ?")
            ->execute([$userId, $otherUserId, $propertyId]);

        jsonResponse(true, '', $messages);
    } catch (PDOException $e) {
        logEvent('Chat fetch error: ' . $e->getMessage(), 'error');
        jsonResponse(false, 'Error al obtener mensajes');
    }
} elseif ($action === 'list_conversations') {
    try {
        // Obtener conversaciones únicas por propiedad y usuario
        $stmt = $pdo->prepare("
            SELECT m.*, p.titulo as property_title, u.nombre as other_user_name, u.apellido as other_user_surname
            FROM messages m
            JOIN properties p ON m.property_id = p.id
            JOIN users u ON (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END) = u.id
            WHERE m.sender_id = ? OR m.receiver_id = ?
            GROUP BY m.property_id, (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END)
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$userId, $userId, $userId, $userId]);
        $conversations = $stmt->fetchAll();
        jsonResponse(true, '', $conversations);
    } catch (PDOException $e) {
        logEvent('Chat list error: ' . $e->getMessage(), 'error');
        jsonResponse(false, 'Error al listar conversaciones');
    }
}
?>
