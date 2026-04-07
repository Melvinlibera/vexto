<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}
require_once dirname(__DIR__) . '/config/db.php';
include dirname(__DIR__) . '/includes/header.php';

$userId = $_SESSION['user_id'];

// Obtener lista de conversaciones únicas
$stmt = $pdo->prepare("
    SELECT 
        m.property_id, 
        p.titulo as property_title,
        p.imagen_url,
        u.id as other_user_id,
        u.nombre as other_user_name,
        u.apellido as other_user_surname,
        m.message as last_message,
        m.created_at as last_message_time,
        (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND sender_id = u.id AND property_id = p.id AND is_read = 0) as unread_count
    FROM messages m
    JOIN properties p ON m.property_id = p.id
    JOIN users u ON (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END) = u.id
    WHERE m.id IN (
        SELECT MAX(id) 
        FROM messages 
        WHERE sender_id = ? OR receiver_id = ? 
        GROUP BY property_id, (CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END)
    )
    ORDER BY m.created_at DESC
");
$stmt->execute([$userId, $userId, $userId, $userId, $userId]);
$conversations = $stmt->fetchAll();
?>

<div class="main-container" style="height: calc(100vh - 120px); overflow: hidden;">
    <div class="chat-layout" style="display: flex; width: 100%; background: var(--card-bg); border-radius: 16px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: var(--shadow-lg);">
        <!-- Lista de Conversaciones -->
        <div class="conversations-list" style="width: 350px; border-right: 1px solid var(--border-color); overflow-y: auto; background: var(--secondary-bg);">
            <div style="padding: 20px; border-bottom: 1px solid var(--border-color); background: var(--card-bg);">
                <h2 style="font-size: 1.2rem; font-weight: 900; letter-spacing: 1px;">MENSAJES</h2>
            </div>
            <?php if (empty($conversations)): ?>
                <div style="padding: 40px 20px; text-align: center; color: var(--muted-text);">
                    <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>No tienes mensajes aún.</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                    <div class="conversation-item" 
                         onclick="loadChat(<?php echo $conv['property_id']; ?>, <?php echo $conv['other_user_id']; ?>, '<?php echo addslashes($conv['other_user_name'] . ' ' . $conv['other_user_surname']); ?>', '<?php echo addslashes($conv['property_title']); ?>')"
                         style="padding: 15px 20px; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: var(--transition); display: flex; align-items: center; gap: 15px; position: relative;">
                        <div style="width: 50px; height: 50px; border-radius: 12px; background: var(--secondary-bg); overflow: hidden; flex-shrink: 0;">
                            <img src="<?php echo getPropertyImageUrl($conv['imagen_url']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="flex: 1; overflow: hidden;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <span style="font-weight: 700; font-size: 0.9rem;"><?php echo htmlspecialchars($conv['other_user_name']); ?></span>
                                <span style="font-size: 0.7rem; color: var(--muted-text);"><?php echo timeAgo($conv['last_message_time']); ?></span>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--accent-color); font-weight: 600; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo htmlspecialchars($conv['property_title']); ?>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--muted-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo htmlspecialchars($conv['last_message']); ?>
                            </div>
                        </div>
                        <?php if ($conv['unread_count'] > 0): ?>
                            <span style="background: var(--accent-color); color: var(--accent-text); font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; font-weight: 900;"><?php echo $conv['unread_count']; ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Ventana de Chat -->
        <div class="chat-window" id="chat-window" style="flex: 1; display: flex; flex-direction: column; background: var(--card-bg);">
            <div id="chat-placeholder" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--muted-text); padding: 40px;">
                <i class="fas fa-paper-plane" style="font-size: 4rem; margin-bottom: 20px; opacity: 0.1;"></i>
                <h3>Selecciona una conversación</h3>
                <p>Haz clic en un mensaje de la lista para ver el historial.</p>
            </div>

            <div id="chat-content" style="display: none; flex: 1; flex-direction: column; height: 100%;">
                <!-- Header del Chat -->
                <div style="padding: 15px 25px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; background: var(--card-bg);">
                    <div>
                        <h3 id="chat-user-name" style="font-size: 1rem; font-weight: 800; margin: 0;"></h3>
                        <p id="chat-property-title" style="font-size: 0.8rem; color: var(--accent-color); margin: 0; font-weight: 600;"></p>
                    </div>
                    <a id="view-property-link" href="#" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.75rem;">Ver Propiedad</a>
                </div>

                <!-- Mensajes -->
                <div id="messages-container" style="flex: 1; padding: 25px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; background: var(--secondary-bg);">
                    <!-- Los mensajes se cargarán aquí -->
                </div>

                <!-- Input de Mensaje -->
                <div style="padding: 20px; border-top: 1px solid var(--border-color); background: var(--card-bg);">
                    <form id="chat-form" style="display: flex; gap: 15px;">
                        <input type="hidden" id="chat-property-id">
                        <input type="hidden" id="chat-receiver-id">
                        <input type="text" id="chat-input" placeholder="Escribe un mensaje..." required 
                               style="flex: 1; padding: 12px 20px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--secondary-bg); color: var(--text-color); outline: none;">
                        <button type="submit" class="btn btn-primary" style="padding: 12px 25px;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentChat = { propertyId: null, otherUserId: null };
let chatInterval = null;
let firstMessagesLoad = true;
let lastMessageCount = 0;

function loadChat(propertyId, otherUserId, userName, propertyTitle) {
    currentChat = { propertyId, otherUserId };
    
    document.getElementById('chat-placeholder').style.display = 'none';
    document.getElementById('chat-content').style.display = 'flex';
    document.getElementById('chat-user-name').textContent = userName;
    document.getElementById('chat-property-title').textContent = propertyTitle;
    document.getElementById('chat-property-id').value = propertyId;
    document.getElementById('chat-receiver-id').value = otherUserId;
    document.getElementById('view-property-link').href = 'property_details.php?id=' + propertyId;
    
    fetchMessages();
    
    if (chatInterval) clearInterval(chatInterval);
    chatInterval = setInterval(fetchMessages, 3000);
}

function fetchMessages() {
    if (!currentChat.propertyId) return;
    
    fetch(`chat_handler.php?action=fetch&property_id=${currentChat.propertyId}&other_user_id=${currentChat.otherUserId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('messages-container');
                const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;
                
                container.innerHTML = data.data.map(msg => {
                    const isMe = msg.sender_id == <?php echo $userId; ?>;
                    const senderLabel = isMe ? 'Tú' : msg.sender_name;
                    return `
                        <div style="display: flex; flex-direction: column; align-items: ${isMe ? 'flex-end' : 'flex-start'};">
                            <div style="max-width: 70%; padding: 12px 18px; border-radius: ${isMe ? '18px 18px 2px 18px' : '18px 18px 18px 2px'}; 
                                        background: ${isMe ? 'var(--accent-color)' : 'var(--card-bg)'}; 
                                        color: ${isMe ? 'var(--accent-text)' : 'var(--text-color)'};
                                        box-shadow: var(--shadow); font-size: 0.9rem;">
                                <div style="font-size: 0.75rem; font-weight: 700; margin-bottom: 6px; color: ${isMe ? 'var(--accent-text)' : 'var(--muted-text)'};">${senderLabel}</div>
                                ${msg.message}
                            </div>
                            <span style="font-size: 0.65rem; color: var(--muted-text); margin-top: 4px; padding: 0 5px;">
                                ${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                            </span>
                        </div>
                    `;
                }).join('');

                const newMessageCount = data.data.length;
                if (!firstMessagesLoad && newMessageCount > lastMessageCount) {
                    const newMessages = data.data.slice(lastMessageCount);
                    const incoming = newMessages.reverse().find(msg => msg.sender_id != <?php echo $userId; ?>);
                    if (incoming) {
                        showNotification(`Nuevo mensaje de ${incoming.sender_name}`, 'info');
                    }
                }
                firstMessagesLoad = false;
                lastMessageCount = newMessageCount;
                
                if (isAtBottom) {
                    container.scrollTop = container.scrollHeight;
                }
            }
        });
}

document.getElementById('chat-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    if (!message) return;
    
    const formData = new FormData();
    formData.append('property_id', currentChat.propertyId);
    formData.append('receiver_id', currentChat.otherUserId);
    formData.append('message', message);
    
    input.value = '';
    
    fetch('chat_handler.php?action=send', {
        method: 'POST',
        body: formData
    }).then(res => res.json())
      .then(data => {
          if (data.success) fetchMessages();
      });
});
</script>

</body>
</html>
