<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}
require_once dirname(__DIR__) . '/config/db.php';
include dirname(__DIR__) . '/includes/header.php';

$user_id = $_SESSION['user_id'];

// Procesar eliminación de publicación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $property_id = $_POST['property_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Verificar que la propiedad pertenece al usuario
        $stmt = $pdo->prepare("SELECT id FROM properties WHERE id = ? AND user_id = ?");
        $stmt->execute([$property_id, $user_id]);
        if ($stmt->fetch()) {
            // Eliminar la propiedad
            $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ? AND user_id = ?");
            $stmt->execute([$property_id, $user_id]);
            
            // Decrementar contador de propiedades
            $stmt = $pdo->prepare("UPDATE users SET propiedades_publicadas = propiedades_publicadas - 1 WHERE id = ?");
            $stmt->execute([$user_id]);
            
            $pdo->commit();
            header("Location: my_publications.php?deleted=1");
            exit();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al eliminar: " . $e->getMessage());
    }
}

// Obtener todas las publicaciones del usuario
$stmt = $pdo->prepare("SELECT * FROM properties WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$properties = $stmt->fetchAll();

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();

$userInitials = strtoupper(substr($user_data['nombre'], 0, 1) . substr($user_data['apellido'], 0, 1));
$userAvatar = !empty($user_data['foto_perfil']) ? 'data:' . $user_data['foto_perfil_tipo'] . ';base64,' . base64_encode($user_data['foto_perfil']) : null;
?>

<div class="main-container" style="flex-direction: column; max-width: 1200px; margin: 40px auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px;">
        <h1 style="font-size: 2.5rem; font-weight: 900;">Mis Publicaciones</h1>
        <a href="publish.php" class="btn btn-primary" style="padding: 15px 30px; border-radius: 12px; font-weight: 800;">
            <i class="fas fa-plus-circle"></i> Nueva Publicación
        </a>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); padding: 15px 25px; border-radius: 12px; color: #b91c1c; margin-bottom: 30px; display: flex; align-items: center; gap: 12px; font-weight: 600;">
            <i class="fas fa-check-circle"></i> Publicación eliminada correctamente.
        </div>
    <?php endif; ?>

    <!-- Tarjeta de Estado del Usuario Mejorada -->
    <div class="filter-card" style="width: 100%; position: static; display: flex; align-items: center; gap: 35px; padding: 40px; margin-bottom: 40px; border-radius: 28px; box-shadow: var(--shadow-lg);">
        <div class="profile-avatar-wrapper">
            <div class="seller-avatar-mini" style="width: 110px; height: 110px; border-radius: 50%; font-size: 2.2rem; border: 4px solid var(--accent-color); box-shadow: var(--shadow);">
                <?php if ($userAvatar): ?>
                    <img src="<?php echo $userAvatar; ?>" alt="Avatar">
                <?php else: ?>
                    <?php echo $userInitials; ?>
                <?php endif; ?>
            </div>
            <?php if (!empty($user_data['verified'])): ?>
                <div class="verified-badge" style="width: 32px; height: 32px; font-size: 0.9rem; border-width: 3px;">V</div>
            <?php endif; ?>
        </div>
        <div style="flex: 1;">
            <div style="font-size: 1.8rem; font-weight: 900; margin-bottom: 10px; color: var(--text-color);">¡Hola, <?php echo htmlspecialchars($user_data['nombre']); ?>!</div>
            <div style="display: flex; gap: 25px; color: var(--muted-text); font-weight: 700; font-size: 0.95rem; flex-wrap: wrap;">
                <span><i class="fas fa-home" style="color: var(--accent-color);"></i> <?php echo count($properties); ?> Publicadas</span>
                <span><i class="fas fa-chart-pie" style="color: var(--accent-color);"></i> Límite: <?php echo $user_data['max_propiedades']; ?></span>
                <span><i class="fas fa-star" style="color: #f59e0b;"></i> <?php echo number_format($user_data['rating'], 1); ?> Calificación</span>
            </div>
        </div>
        <div style="text-align: right;">
            <a href="settings.php" class="btn btn-outline" style="padding: 12px 25px; font-size: 0.9rem; border-radius: 12px; font-weight: 700; border-width: 2px;">Configurar Cuenta</a>
        </div>
    </div>

    <div class="content-grid">
        <?php if (empty($properties)): ?>
            <div class="info-section" style="grid-column: 1/-1; text-align: center; padding: 80px; border-radius: 32px;">
                <i class="fas fa-home" style="font-size: 3.5rem; color: var(--border-color); margin-bottom: 25px; display: block;"></i>
                <h2 style="font-weight: 900;">Aún no tienes publicaciones</h2>
                <p style="color: var(--muted-text); font-size: 1.1rem; max-width: 400px; margin: 15px auto;">Comienza a publicar tus proyectos inmobiliarios hoy mismo.</p>
                <a href="publish.php" class="btn btn-primary" style="margin-top: 20px; border-radius: 12px; padding: 15px 40px;">Crear mi primera publicación</a>
            </div>
        <?php else: ?>
            <?php foreach ($properties as $prop): ?>
                <?php
                    $imageSrc = getPropertyImageUrl($prop['imagen_url']);
                    if (!empty($prop['imagen'])) {
                        $imageSrc = 'data:' . htmlspecialchars($prop['imagen_tipo']) . ';base64,' . base64_encode($prop['imagen']);
                    }
                ?>
                <div class="property-card" style="border-radius: 24px; box-shadow: var(--shadow);">
                    <div class="property-img-container" style="height: 240px;">
                        <span class="op-badge" style="border-radius: 8px; padding: 8px 15px; font-weight: 800;"><?php echo strtoupper($prop['tipo_operacion']); ?></span>
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="property-img" alt="Propiedad">
                        <div style="position: absolute; top: 15px; right: 15px; background: rgba(0,0,0,0.7); color: #fff; padding: 6px 15px; font-size: 0.75rem; border-radius: 20px; font-weight: 700; backdrop-filter: blur(4px);">
                            <?php echo strtoupper($prop['estado']); ?>
                        </div>
                    </div>
                    <div class="property-info" style="padding: 25px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <div class="property-price" style="font-size: 1.6rem; font-weight: 900; color: var(--accent-color);">
                                $<?php echo number_format($prop['precio'], 0); ?>
                            </div>
                            <span style="font-size: 0.75rem; color: var(--muted-text); font-weight: 700; text-transform: uppercase; background: var(--secondary-bg); padding: 4px 10px; border-radius: 6px;">
                                <?php echo htmlspecialchars($prop['tipo_propiedad']); ?>
                            </span>
                        </div>
                        <div class="property-title" style="font-size: 1.1rem; font-weight: 800; margin-bottom: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?php echo htmlspecialchars($prop['titulo']); ?>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: var(--muted-text); font-weight: 700; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                            <span><i class="fas fa-eye"></i> <?php echo $prop['vistas']; ?> vistas</span>
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($prop['created_at'])); ?></span>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <a href="publish.php?edit=<?php echo $prop['id']; ?>" class="btn btn-primary" style="padding: 12px; font-size: 0.9rem; border-radius: 12px; font-weight: 700;">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <form action="my_publications.php" method="POST" style="display: contents;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="property_id" value="<?php echo $prop['id']; ?>">
                                <button type="submit" class="btn btn-outline" style="padding: 12px; font-size: 0.9rem; border-radius: 12px; font-weight: 700; border-width: 2px; color: #ef4444; border-color: #ef4444;" onclick="return confirm('¿Estás seguro de que deseas eliminar esta publicación permanentemente?');">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </form>
                        </div>
                        <a href="property_details.php?id=<?php echo $prop['id']; ?>" class="btn" style="width: 100%; margin-top: 12px; padding: 12px; font-size: 0.9rem; border-radius: 12px; font-weight: 700; background: var(--secondary-bg); color: var(--text-color);">Ver Publicación Pública</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($properties)): ?>
        <div class="info-section" style="margin-top: 50px; padding: 40px; border-radius: 28px;">
            <h3 style="margin-bottom: 25px; font-weight: 900; font-size: 1.5rem;">Resumen de Rendimiento</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px;">
                <div style="background: var(--secondary-bg); padding: 25px; border-radius: 20px; border: 1px solid var(--border-color);">
                    <div style="font-size: 0.9rem; color: var(--muted-text); margin-bottom: 8px; font-weight: 700; text-transform: uppercase;">Total Publicadas</div>
                    <div style="font-size: 2.5rem; font-weight: 900; color: var(--accent-color);"><?php echo count($properties); ?></div>
                </div>
                <div style="background: var(--secondary-bg); padding: 25px; border-radius: 20px; border: 1px solid var(--border-color);">
                    <div style="font-size: 0.9rem; color: var(--muted-text); margin-bottom: 8px; font-weight: 700; text-transform: uppercase;">Vistas Totales</div>
                    <div style="font-size: 2.5rem; font-weight: 900; color: var(--accent-color);">
                        <?php 
                        $total_views = 0;
                        foreach ($properties as $prop) { $total_views += $prop['vistas']; }
                        echo number_format($total_views);
                        ?>
                    </div>
                </div>
                <div style="background: var(--secondary-bg); padding: 25px; border-radius: 20px; border: 1px solid var(--border-color);">
                    <div style="font-size: 0.9rem; color: var(--muted-text); margin-bottom: 8px; font-weight: 700; text-transform: uppercase;">Promedio Vistas</div>
                    <div style="font-size: 2.5rem; font-weight: 900; color: var(--accent-color);">
                        <?php echo count($properties) > 0 ? round($total_views / count($properties)) : 0; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
