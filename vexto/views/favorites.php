<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}
require_once dirname(__DIR__) . '/config/db.php';
include dirname(__DIR__) . '/includes/header.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT p.*, u.nombre, u.apellido, u.rating, u.tipo_usuario 
                      FROM properties p 
                      JOIN favorites f ON p.id = f.property_id 
                      JOIN users u ON p.user_id = u.id 
                      WHERE f.user_id = ? 
                      ORDER BY f.created_at DESC");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll();
?>

<div class="main-container" style="flex-direction: column; max-width: 1200px; margin: 40px auto;">
    <h1 style="margin-bottom: 30px; font-size: 2.5rem; font-weight: 900;">Mis Favoritos</h1>
    
    <?php if (empty($favorites)): ?>
        <div style="text-align: center; padding: 100px;">
            <i class="far fa-heart" style="font-size: 4rem; color: #ccc; margin-bottom: 20px;"></i>
            <h2>Aún no has guardado ninguna propiedad</h2>
            <p>Explora el marketplace y guarda las propiedades que más te gusten.</p>
            <a href="dashboard.php" class="btn btn-primary" style="margin-top: 20px;">Explorar Marketplace</a>
        </div>
    <?php else: ?>
        <div class="content-grid">
            <?php foreach ($favorites as $prop): ?>
                <div class="property-card" onclick="location.href='property_details.php?id=<?php echo $prop['id']; ?>'">
                    <div class="property-img-container">
                        <span class="op-badge"><?php echo $prop['tipo_operacion']; ?></span>
                        <?php
                            $imageSrc = getPropertyImageUrl($prop['imagen_url']);
                            if (!empty($prop['imagen'])) {
                                $imageSrc = 'data:' . htmlspecialchars($prop['imagen_tipo']) . ';base64,' . base64_encode($prop['imagen']);
                            }
                        ?>
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="property-img" alt="Propiedad">
                    </div>
                    <div class="property-info">
                        <div class="property-price">$<?php echo number_format($prop['precio'], 2); ?></div>
                        <div class="property-title"><?php echo htmlspecialchars($prop['titulo']); ?></div>
                        <div class="property-meta">
                            <span><i class="fas fa-bed"></i> <?php echo $prop['habitaciones']; ?></span>
                            <span><i class="fas fa-bath"></i> <?php echo $prop['banos']; ?></span>
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($prop['ubicacion']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
