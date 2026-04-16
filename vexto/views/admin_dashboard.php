<?php
/**
 * Admin Dashboard
 * Restricted access for administrators only
 */

session_start();
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../core/helpers.php';
require_once '../core/User.php';
require_once '../core/AuditLog.php';
require_once '../core/Feedback.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . 'public/index.php');
}

// Check if user is admin
$userId = getCurrentUserId();
$userClass = new User($pdo);
$user = $userClass->getById($userId);

if (!$user || $user['tipo_usuario'] !== 'admin') {
    redirect(BASE_URL . 'views/dashboard.php');
}

// Initialize classes
$auditLog = new AuditLog($pdo);
$feedback = new Feedback($pdo);

// Get statistics
$auditCount = $auditLog->getCount();
$feedbackCount = $feedback->getCount();
$userCount = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$propertyCount = (int) $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();

// Get recent audit logs
$recentLogs = $auditLog->getAll(20, 0);

// Get feedback summary
$newFeedback = $feedback->getByStatus('nuevo');
$inReviewFeedback = $feedback->getByStatus('en_revision');

// Get query parameters
$tab = $_GET['tab'] ?? 'overview';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Load admin lists
$usersList = $pdo->prepare("SELECT id, nombre, apellido, email, tipo_usuario, verified, created_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?");
$usersList->execute([$limit, $offset]);
$usersList = $usersList->fetchAll();

$propertiesList = $pdo->prepare("SELECT p.id, p.titulo, p.precio, p.estado, u.nombre, u.apellido FROM properties p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
$propertiesList->execute([$limit, $offset]);
$propertiesList = $propertiesList->fetchAll();

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_feedback_status') {
        $feedbackId = $_POST['feedback_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        if ($feedbackId && $status) {
            $result = $feedback->updateStatus($feedbackId, $status);
            if ($result['success']) {
                $successMessage = 'Estado del feedback actualizado.';
            }
        }
    } elseif ($_POST['action'] === 'toggle_user_verify') {
        $userIdToVerify = intval($_POST['user_id'] ?? 0);
        if ($userIdToVerify) {
            $verify = isset($_POST['verify']) && $_POST['verify'] === '1' ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE users SET verified = ? WHERE id = ?");
            if ($stmt->execute([$verify, $userIdToVerify])) {
                $successMessage = $verify ? 'Usuario verificado correctamente.' : 'Usuario desverificado correctamente.';
            }
        }
    } elseif ($_POST['action'] === 'toggle_property_status') {
        $propertyId = intval($_POST['property_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        if ($propertyId && in_array($newStatus, ['activa', 'inactiva', 'vendida'], true)) {
            $stmt = $pdo->prepare("UPDATE properties SET estado = ? WHERE id = ?");
            if ($stmt->execute([$newStatus, $propertyId])) {
                $successMessage = 'Estado de la publicación actualizado.';
            }
        }
    } elseif ($_POST['action'] === 'update_user') {
        $userIdToUpdate = intval($_POST['user_id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $tipoUsuario = $_POST['tipo_usuario'] ?? 'usuario';
        $verified = isset($_POST['verified']) && $_POST['verified'] === '1' ? 1 : 0;
        $cedula = trim($_POST['cedula'] ?? '');
        $rnc = trim($_POST['rnc'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $errors = [];

        if ($userIdToUpdate && $nombre && $apellido && $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Correo electrónico inválido.';
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
            $stmt->execute([$email, $userIdToUpdate]);
            if ($stmt->fetch()) {
                $errors[] = 'El correo ya está en uso por otro usuario.';
            }

            if ($password !== '' && strlen($password) < 8) {
                $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
            }

            if (empty($errors)) {
                $updateFields = [
                    'nombre = ?',
                    'apellido = ?',
                    'email = ?',
                    'telefono = ?',
                    'tipo_usuario = ?',
                    'verified = ?',
                    'cedula = ?',
                    'rnc = ?'
                ];
                $params = [$nombre, $apellido, $email, $telefono, $tipoUsuario, $verified, $cedula, $rnc];

                if ($password !== '') {
                    $updateFields[] = 'password = ?';
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }

                $params[] = $userIdToUpdate;
                $stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $updateFields) . ' WHERE id = ?');
                if ($stmt->execute($params)) {
                    $successMessage = 'Usuario actualizado correctamente.';
                }
            } else {
                $errorMessage = implode(' ', $errors);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - VEXTO</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/modern.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style_enhanced.css">
    <style>
        .admin-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
            background-color: var(--bg-color);
        }

        .admin-sidebar {
            background-color: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            padding: 1.5rem 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .admin-sidebar-header {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }

        .admin-sidebar-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--accent-color);
        }

        .admin-sidebar-menu {
            list-style: none;
        }

        .admin-sidebar-menu li {
            margin: 0;
        }

        .admin-sidebar-menu a {
            display: block;
            padding: 0.75rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }

        .admin-sidebar-menu a:hover,
        .admin-sidebar-menu a.active {
            background-color: var(--accent-light);
            color: var(--accent-color);
            border-left-color: var(--accent-color);
        }

        .admin-content {
            padding: 2rem;
            overflow-y: auto;
            min-height: 100vh;
        }

        .admin-mobile-tabs {
            display: none;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .admin-mobile-tab {
            flex: 1 1 calc(50% - 0.5rem);
            padding: 0.85rem 1rem;
            border-radius: 999px;
            border: 1px solid var(--border-color);
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            text-align: center;
            text-decoration: none;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .admin-mobile-tab.active {
            background-color: var(--accent-color);
            color: var(--bg-primary);
            border-color: transparent;
        }

        .section-title {
            margin-bottom: 1rem;
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 700;
        }

        .card-section {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
            gap: 1rem;
        }

        .admin-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .admin-user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-user-info a {
            color: var(--accent-color);
            font-weight: 500;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .table-wrapper {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            overflow: hidden;
        }

        .table-header {
            padding: 1rem 1.5rem;
            background-color: var(--accent-light);
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
            color: var(--text-primary);
        }

        .table-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
        }

        .table-row:hover {
            background-color: var(--accent-light);
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .table-cell {
            font-size: 0.95rem;
            color: var(--text-secondary);
        }

        .table-cell-primary {
            font-weight: 600;
            color: var(--text-primary);
        }

        .table-row .table-cell {
            min-width: 0;
        }

        @media (max-width: 768px) {
            .table-row {
                display: grid;
                grid-template-columns: 1fr;
                padding: 1rem;
                border-bottom: 1px solid var(--border-color);
                background-color: var(--bg-primary);
                border-radius: 1rem;
                margin-bottom: 0.75rem;
            }

            .table-row:last-child {
                margin-bottom: 0;
            }

            .table-header {
                display: none;
            }

            .table-wrapper {
                border: none;
                box-shadow: none;
            }

            .table-cell {
                display: block;
                width: 100%;
            }

            .table-cell::before {
                content: attr(data-label);
                display: block;
                font-size: 0.85rem;
                color: var(--text-secondary);
                margin-bottom: 0.25rem;
                text-transform: uppercase;
                letter-spacing: 0.02em;
            }

            .user-card {
                border: 1px solid var(--border-color);
                box-shadow: none;
            }

            .admin-container {
                grid-template-columns: 1fr;
            }

            .admin-sidebar {
                display: none;
            }

            .admin-content {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .admin-mobile-tabs {
                display: flex;
            }
        }

        .admin-users-grid {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }

        .user-card {
            background-color: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            box-shadow: var(--shadow);
            padding: 1rem 1.25rem;
            display: grid;
            gap: 1rem;
        }

        .user-card-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .user-card-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .user-card-subtitle {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .user-card-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .user-card-info {
            font-size: 0.9rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .user-card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .user-card-actions .btn-sm {
            padding: 0.6rem 0.9rem;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .table-row {
                display: block;
                padding: 1rem;
            }

            .table-header {
                display: none;
            }

            .table-wrapper {
                border: none;
                box-shadow: none;
            }
        }

        .table-cell-primary {
            font-weight: 600;
            color: var(--text-primary);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-success {
            background-color: rgba(16, 185, 129, 0.2);
            color: var(--success-color);
        }

        .badge-warning {
            background-color: rgba(245, 158, 11, 0.2);
            color: var(--warning-color);
        }

        .badge-error {
            background-color: rgba(239, 68, 68, 0.2);
            color: var(--error-color);
        }

        .badge-info {
            background-color: rgba(59, 130, 246, 0.2);
            color: var(--info-color);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.25rem;
            text-decoration: none;
            color: var(--text-primary);
            transition: var(--transition);
        }

        .pagination a:hover {
            background-color: var(--accent-light);
            border-color: var(--accent-color);
        }

        .pagination .active {
            background-color: var(--accent-color);
            color: var(--bg-primary);
            border-color: var(--accent-color);
        }

        .modal-form {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-form.active {
            display: flex;
        }

        .modal-form-content {
            background-color: var(--bg-primary);
            border-radius: 0.75rem;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            box-shadow: var(--shadow-lg);
        }

        @media (max-width: 768px) {
            .admin-container {
                grid-template-columns: 1fr;
            }

            .admin-sidebar {
                display: none;
            }

            .admin-content {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <div class="admin-sidebar-title">VEXTO Admin</div>
            </div>
            <ul class="admin-sidebar-menu">
                <li><a href="?tab=overview" class="<?php echo ($tab === 'overview' ? 'active' : ''); ?>">📊 Resumen</a></li>
                <li><a href="?tab=audit" class="<?php echo ($tab === 'audit' ? 'active' : ''); ?>">📋 Auditoría</a></li>
                <li><a href="?tab=feedback" class="<?php echo ($tab === 'feedback' ? 'active' : ''); ?>">💬 Feedback</a></li>
                <li><a href="?tab=users" class="<?php echo ($tab === 'users' ? 'active' : ''); ?>">👥 Usuarios</a></li>
                <li><a href="?tab=properties" class="<?php echo ($tab === 'properties' ? 'active' : ''); ?>">🏠 Propiedades</a></li>
                <li><a href="../views/dashboard.php">← Volver</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <h1 class="admin-title">Panel de Administración</h1>
                <div class="admin-user-info">
                    <span><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></span>
                    <a href="../views/logout.php">Cerrar Sesión</a>
                </div>
            </div>

            <div class="admin-mobile-tabs">
                <a href="?tab=overview" class="admin-mobile-tab <?php echo ($tab === 'overview' ? 'active' : ''); ?>">Resumen</a>
                <a href="?tab=audit" class="admin-mobile-tab <?php echo ($tab === 'audit' ? 'active' : ''); ?>">Auditoría</a>
                <a href="?tab=feedback" class="admin-mobile-tab <?php echo ($tab === 'feedback' ? 'active' : ''); ?>">Feedback</a>
                <a href="?tab=users" class="admin-mobile-tab <?php echo ($tab === 'users' ? 'active' : ''); ?>">Usuarios</a>
                <a href="?tab=properties" class="admin-mobile-tab <?php echo ($tab === 'properties' ? 'active' : ''); ?>">Publicaciones</a>
            </div>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success" style="margin-bottom:1.5rem;">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-error" style="margin-bottom:1.5rem;">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'overview'): ?>
                <!-- Overview Tab -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $auditCount; ?></div>
                        <div class="stat-label">Movimientos Registrados</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $userCount; ?></div>
                        <div class="stat-label">Usuarios Totales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $propertyCount; ?></div>
                        <div class="stat-label">Publicaciones Totales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($newFeedback); ?></div>
                        <div class="stat-label">Feedback Nuevo</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($inReviewFeedback); ?></div>
                        <div class="stat-label">Feedback en Revisión</div>
                    </div>
                </div>

                <h2 class="section-title">Movimientos Recientes</h2>
                <div class="card-section">
                    <div class="table-wrapper">
                        <div class="table-header">
                            <strong>Usuario</strong> | <strong>Acción</strong> | <strong>Entidad</strong> | <strong>Fecha</strong>
                        </div>
                        <?php foreach ($recentLogs as $log): ?>
                            <div class="table-row">
                                <div class="table-cell-primary" data-label="Usuario"><?php echo htmlspecialchars($log['nombre'] . ' ' . $log['apellido']); ?></div>
                                <div class="table-cell" data-label="Acción"><?php echo htmlspecialchars($log['action']); ?></div>
                                <div class="table-cell" data-label="Entidad"><?php echo htmlspecialchars($log['entity_type']); ?></div>
                                <div class="table-cell" data-label="Fecha"><?php echo formatDate($log['created_at']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php elseif ($tab === 'audit'): ?>
                <!-- Audit Log Tab -->
                <h2 class="section-title">Registro de Auditoría</h2>
                <div class="card-section">
                    <div class="table-wrapper">
                        <div class="table-header">
                            <strong>Usuario</strong> | <strong>Acción</strong> | <strong>Entidad</strong> | <strong>IP</strong> | <strong>Fecha</strong>
                        </div>
                        <?php 
                        $auditLogs = $auditLog->getAll($limit, $offset);
                        foreach ($auditLogs as $log): 
                        ?>
                            <div class="table-row">
                                <div class="table-cell-primary" data-label="Usuario"><?php echo htmlspecialchars($log['nombre'] . ' ' . $log['apellido']); ?></div>
                                <div class="table-cell" data-label="Acción"><?php echo htmlspecialchars($log['action']); ?></div>
                                <div class="table-cell" data-label="Entidad"><?php echo htmlspecialchars($log['entity_type']); ?></div>
                                <div class="table-cell" data-label="IP"><?php echo htmlspecialchars($log['ip_address']); ?></div>
                                <div class="table-cell" data-label="Fecha"><?php echo formatDate($log['created_at']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php elseif ($tab === 'feedback'): ?>
                <!-- Feedback Tab -->
                <h2 class="section-title">Feedback de Usuarios</h2>
                <div class="card-section">
                    <div class="table-wrapper">
                        <div class="table-header">
                            <strong>Usuario</strong> | <strong>Título</strong> | <strong>Tipo</strong> | <strong>Estado</strong> | <strong>Acciones</strong>
                        </div>
                        <?php 
                        $feedbackList = $feedback->getAll($limit, $offset);
                        foreach ($feedbackList as $fb): 
                        ?>
                            <div class="table-row">
                                <div class="table-cell-primary" data-label="Usuario"><?php echo htmlspecialchars($fb['nombre'] . ' ' . $fb['apellido']); ?></div>
                                <div class="table-cell" data-label="Título"><?php echo htmlspecialchars(substr($fb['titulo'], 0, 30)); ?></div>
                                <div class="table-cell" data-label="Tipo">
                                    <span class="badge badge-info"><?php echo htmlspecialchars($fb['tipo']); ?></span>
                                </div>
                                <div class="table-cell" data-label="Estado">
                                    <span class="badge <?php echo 'badge-' . ($fb['estado'] === 'nuevo' ? 'warning' : ($fb['estado'] === 'resuelto' ? 'success' : 'info')); ?>">
                                        <?php echo htmlspecialchars($fb['estado']); ?>
                                    </span>
                                </div>
                                <div class="table-cell" data-label="Acciones">
                                    <div class="action-buttons">
                                        <button class="btn btn-secondary btn-sm" onclick="viewFeedback(<?php echo $fb['id']; ?>)">Ver</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php elseif ($tab === 'users'): ?>
                <!-- Users Management Tab -->
                <h2 class="section-title">Gestión de Usuarios</h2>
                <div class="card-section">
                    <div class="admin-users-grid">
                        <?php foreach ($usersList as $u): ?>
                            <div class="user-card">
                            <div class="user-card-header">
                                <div>
                                    <div class="user-card-title"><?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellido']); ?></div>
                                    <div class="user-card-subtitle"><?php echo htmlspecialchars($u['email']); ?></div>
                                </div>
                                <div>
                                    <span class="badge <?php echo $u['verified'] ? 'badge-success' : 'badge-warning'; ?>" style="min-width: 72px; text-align: center;">
                                        <?php echo $u['verified'] ? 'VERIFICADO' : 'NO VERIFICADO'; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="user-card-details">
                                <div class="user-card-info"><strong>Rol:</strong> <?php echo htmlspecialchars(strtoupper($u['tipo_usuario'])); ?></div>
                                <div class="user-card-info"><strong>Cédula:</strong> <?php echo htmlspecialchars($u['cedula'] ?? '—'); ?></div>
                                <div class="user-card-info"><strong>Teléfono:</strong> <?php echo htmlspecialchars($u['telefono'] ?? '—'); ?></div>
                            </div>

                            <div class="user-card-actions">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="openUserEditModal(this)"
                                    data-user-id="<?php echo $u['id']; ?>"
                                    data-user-nombre="<?php echo htmlspecialchars($u['nombre'], ENT_QUOTES); ?>"
                                    data-user-apellido="<?php echo htmlspecialchars($u['apellido'], ENT_QUOTES); ?>"
                                    data-user-email="<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>"
                                    data-user-tipo="<?php echo htmlspecialchars($u['tipo_usuario'], ENT_QUOTES); ?>"
                                    data-user-verified="<?php echo $u['verified']; ?>"
                                    data-user-telefono="<?php echo htmlspecialchars($u['telefono'] ?? '', ENT_QUOTES); ?>"
                                    data-user-cedula="<?php echo htmlspecialchars($u['cedula'] ?? '', ENT_QUOTES); ?>"
                                    data-user-rnc="<?php echo htmlspecialchars($u['rnc'] ?? '', ENT_QUOTES); ?>"
                                >Editar</button>
                                <form method="POST" style="display:inline-flex; margin:0;">
                                    <input type="hidden" name="action" value="toggle_user_verify">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="verify" value="<?php echo $u['verified'] ? '0' : '1'; ?>">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <?php echo $u['verified'] ? 'Desverificar' : 'Verificar'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($tab === 'properties'): ?>
                <!-- Properties Management Tab -->
                <h2 class="section-title">Gestión de Publicaciones</h2>
                <div class="card-section">
                    <div class="table-wrapper">
                        <div class="table-header">
                            <strong>Publicación</strong> | <strong>Propietario</strong> | <strong>Precio</strong> | <strong>Estado</strong> | <strong>Acciones</strong>
                        </div>
                        <?php foreach ($propertiesList as $propItem): ?>
                            <div class="table-row">
                                <div class="table-cell-primary" data-label="Publicación"><?php echo htmlspecialchars($propItem['titulo']); ?></div>
                                <div class="table-cell" data-label="Propietario"><?php echo htmlspecialchars($propItem['nombre'] . ' ' . $propItem['apellido']); ?></div>
                                <div class="table-cell" data-label="Precio">$<?php echo number_format($propItem['precio'], 2); ?></div>
                                <div class="table-cell" data-label="Estado">
                                    <span class="badge <?php echo $propItem['estado'] === 'activa' ? 'badge-success' : ($propItem['estado'] === 'vendida' ? 'badge-info' : 'badge-warning'); ?>">
                                        <?php echo htmlspecialchars(strtoupper($propItem['estado'])); ?>
                                    </span>
                                </div>
                                <div class="table-cell" data-label="Acciones">
                                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                                        <a href="../views/publish.php?edit=<?php echo $propItem['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                        <a href="../views/property_details.php?id=<?php echo $propItem['id']; ?>" class="btn btn-secondary btn-sm">Ver</a>
                                    </div>
                                    <form method="POST" style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:0.75rem;">
                                        <input type="hidden" name="action" value="toggle_property_status">
                                        <input type="hidden" name="property_id" value="<?php echo $propItem['id']; ?>">
                                        <button type="submit" name="new_status" value="activa" class="btn btn-secondary btn-sm">Activa</button>
                                        <button type="submit" name="new_status" value="inactiva" class="btn btn-secondary btn-sm">Inactiva</button>
                                        <button type="submit" name="new_status" value="vendida" class="btn btn-secondary btn-sm">Vendida</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php endif; ?>

            <div id="editUserModal" class="modal-form" aria-hidden="true">
                <div class="modal-form-content">
                    <h2 style="margin-top:0; margin-bottom: 1rem;">Editar Usuario</h2>
                    <form method="POST" id="editUserForm">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" id="editUserId">

                        <div style="display:grid; gap:1rem;">
                            <div>
                                <label for="editUserNombre">Nombre</label>
                                <input id="editUserNombre" type="text" name="nombre" required style="width:100%; padding:12px; border:1px solid var(--border-color); border-radius:12px;">
                            </div>
                            <div>
                                <label for="editUserApellido">Apellido</label>
                                <input id="editUserApellido" type="text" name="apellido" required style="width:100%; padding:12px; border:1px solid var(--border-color); border-radius:12px;">
                            </div>
                            <div>
                                <label for="editUserEmail">Email</label>
                                <input id="editUserEmail" type="email" name="email" required style="width:100%; padding:12px; border:1px solid var(--border-color); border-radius:12px;">
                            </div>
                            <div>
                                <label for="editUserTelefono">Teléfono</label>
                                <input id="editUserTelefono" type="text" name="telefono" style="width:100%; padding:12px; border:1px solid var(--border-color); border-radius:12px;">
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                                <div>
                                    <label for="editUserTipo">Tipo de Usuario</label>
                                    <select id="editUserTipo" name="tipo_usuario" style="width:100%; padding:12px; border:1px solid var(--border-color); border-radius:12px;">
                                        <option value="usuario">Usuario</option>
                                        <option value="compania">Compañía</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="editUserVerified">Verificado</label>
                                    <select id="editUserVerified" name="verified" style="width:100%; padding:12px; border:1px solid var(--border-color); border-radius:12px;">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label for="editUserCedula">Cédula</label>
                                <input id="editUserCedula" type="text" name="cedula" style="width:100%; padding:12px; border:1px solid var(--border-color); border-radius:12px;">
                            </div>
                            <div>
                                <label for="editUserRnc">RNC</label>
                                <input id="editUserRnc" type="text" name="rnc" style="width:100%; padding:12px; border:1px solid var(--border-color); border-radius:12px;">
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                                <div>
                                    <label for="editUserPassword">Nueva contraseña</label>
                                    <input id="editUserPassword" type="password" name="password" style="width:100%; padding:12px; border:1px solid var(--border-color); border-radius:12px;">
                                </div>
                                <div>
                                    <label>&nbsp;</label>
                                    <span style="display:block; color: var(--text-secondary); font-size:0.9rem;">Dejar vacío para no cambiar.</span>
                                </div>
                            </div>
                        </div>

                        <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1.5rem;">
                            <button type="button" class="btn btn-outline" onclick="closeUserModal()">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="<?php echo ASSETS_URL; ?>js/theme.js"></script>
    <script>
        function viewFeedback(feedbackId) {
            alert('Feedback ID: ' + feedbackId);
        }

        function openUserEditModal(button) {
            const modal = document.getElementById('editUserModal');
            document.getElementById('editUserId').value = button.dataset.userId;
            document.getElementById('editUserNombre').value = button.dataset.userNombre;
            document.getElementById('editUserApellido').value = button.dataset.userApellido;
            document.getElementById('editUserEmail').value = button.dataset.userEmail;
            document.getElementById('editUserTelefono').value = button.dataset.userTelefono;
            document.getElementById('editUserTipo').value = button.dataset.userTipo;
            document.getElementById('editUserVerified').value = button.dataset.userVerified;
            document.getElementById('editUserCedula').value = button.dataset.userCedula;
            document.getElementById('editUserRnc').value = button.dataset.userRnc;
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeUserModal() {
            const modal = document.getElementById('editUserModal');
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
        }

        window.addEventListener('keydown', function(event) {
            const modal = document.getElementById('editUserModal');
            if (event.key === 'Escape' && modal.classList.contains('active')) {
                closeUserModal();
            }
        });
    </script>
</body>
</html>
