<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/helpers.php';
require_once dirname(__DIR__) . '/config/db.php';

$user_id = $_SESSION['user_id'] ?? null;
$user = null;
$unreadCount = 0;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT COUNT(*) as unread FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $countData = $stmt->fetch();
    $unreadCount = $countData['unread'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="<?php echo $user ? htmlspecialchars($user['theme_preference']) : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VEXTO | Marketplace Inmobiliario</title>
    
    <!-- Fuentes y Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    
    <!-- Leaflet (Mapas) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Anime.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    
    <!-- Estilos Propios -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style_enhanced.css">
</head>
<body data-theme="<?php echo $user ? htmlspecialchars($user['theme_preference']) : 'light'; ?>">

<header>
    <div class="logo" onclick="location.href='<?php echo BASE_URL; ?>views/dashboard.php'">VEXTO</div>
    
    <form action="<?php echo BASE_URL; ?>views/dashboard.php" method="GET" class="search-bar">
        <input type="text" name="q" placeholder="Buscar casas, locales, terrenos..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
        <button type="submit"><i class="fas fa-search"></i></button>
    </form>
    
    <div class="nav-actions">
        <div id="theme-toggle" class="theme-toggle">
            <i class="fas fa-moon"></i>
        </div>
        
        <?php if ($user): ?>
            <a href="<?php echo BASE_URL; ?>views/publish.php"><i class="fas fa-plus-circle"></i> Publicar</a>
            <a href="<?php echo BASE_URL; ?>views/my_publications.php"><i class="fas fa-list"></i> Mis Publicaciones</a>
            <a href="<?php echo BASE_URL; ?>views/messages.php"><i class="fas fa-comments"></i> Mensajes<?php if ($unreadCount > 0): ?> <span style="display:inline-block; margin-left:6px; font-size:0.8rem; background: var(--accent-color); color:#fff; padding:2px 8px; border-radius:999px; font-weight:700;"><?php echo $unreadCount; ?></span><?php endif; ?></a>
            <a href="<?php echo BASE_URL; ?>views/favorites.php"><i class="fas fa-heart"></i> Favoritos</a>
            <a href="<?php echo BASE_URL; ?>views/settings.php"><i class="fas fa-user-circle"></i> Perfil</a>
            <?php if ($user['tipo_usuario'] == 'compania'): ?>
                <a href="<?php echo BASE_URL; ?>views/stats.php"><i class="fas fa-chart-line"></i> Panel</a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>views/logout.php" title="Cerrar Sesión" onclick="showLogoutConfirm(this.href); return false;"><i class="fas fa-sign-out-alt"></i></a>
        <?php else: ?>
            <a href="index.php">Iniciar Sesión</a>
            <a href="index.php?register=1" class="btn btn-primary" style="padding: 8px 15px;">Registrarse</a>
        <?php endif; ?>
    </div>
</header>

<div id="logoutConfirmModal" class="modal-overlay" style="display:none;">
    <div class="confirm-modal">
        <div class="confirm-modal-header">
            <h3>¿Seguro que quieres salir?</h3>
        </div>
        <div class="confirm-modal-body">
            Al cerrar sesión, tendrás que iniciar sesión de nuevo para continuar.
        </div>
        <div class="confirm-modal-actions">
            <button type="button" class="btn btn-outline" onclick="closeLogoutConfirm()">No, quedarme</button>
            <button type="button" class="btn btn-primary" onclick="confirmLogout()">Sí, salir</button>
        </div>
    </div>
</div>

<script src="<?php echo ASSETS_URL; ?>js/main_enhanced.js"></script>
