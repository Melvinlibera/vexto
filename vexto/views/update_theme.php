<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('No autorizado');
}

require_once dirname(__DIR__) . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme'])) {
    $theme = $_POST['theme'];
    if ($theme !== 'light' && $theme !== 'dark') {
        http_response_code(400);
        exit('Tema inválido');
    }

    $stmt = $pdo->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
    $stmt->execute([$theme, $_SESSION['user_id']]);

    echo 'OK';
} else {
    http_response_code(400);
    exit('Solicitud inválida');
}
?>