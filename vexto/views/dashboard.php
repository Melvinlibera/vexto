<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}
require_once dirname(__DIR__) . '/config/db.php';
include dirname(__DIR__) . '/includes/header.php';

// Filtros
$filter_op = $_GET['op'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_price_min = (float)($_GET['pmin'] ?? 0);
$filter_price_max = (float)($_GET['pmax'] ?? 999999999);
$filter_loc = $_GET['loc'] ?? '';
$search = $_GET['q'] ?? '';

$query = "SELECT p.*, u.nombre, u.apellido, u.rating, u.tipo_usuario, u.foto_perfil, u.foto_perfil_tipo, u.verified 
          FROM properties p 
          JOIN users u ON p.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($filter_op) { $query .= " AND p.tipo_operacion = ?"; $params[] = $filter_op; }
if ($filter_type) { $query .= " AND p.tipo_propiedad = ?"; $params[] = $filter_type; }
if ($filter_price_min) { $query .= " AND p.precio >= ?"; $params[] = $filter_price_min; }
if ($filter_price_max) { $query .= " AND p.precio <= ?"; $params[] = $filter_price_max; }
if ($filter_loc) { $query .= " AND p.ubicacion LIKE ?"; $params[] = "%$filter_loc%"; }
if ($search) { $query .= " AND (p.titulo LIKE ? OR p.descripcion LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$query .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$properties = $stmt->fetchAll();
?>

<div class="main-container">
    <!-- Sidebar de Filtros Mejorada -->
    <aside class="sidebar">
        <div class="filter-card" style="border-radius: 24px; padding: 30px; box-shadow: var(--shadow-lg);">
            <h2 style="margin-bottom: 25px; font-weight: 900; font-size: 1.4rem;">FILTRAR PROYECTOS</h2>
            <form action="dashboard.php" method="GET">
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>">
                
                <div class="filter-group">
                    <h3>Operación</h3>
                    <select name="op" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--secondary-bg); color: var(--text-color);">
                        <option value="">Cualquiera</option>
                        <option value="venta" <?php echo $filter_op == 'venta' ? 'selected' : ''; ?>>Venta</option>
                        <option value="alquiler" <?php echo $filter_op == 'alquiler' ? 'selected' : ''; ?>>Alquiler</option>
                    </select>
                </div>

                <div class="filter-group">
                    <h3>Tipo de Propiedad</h3>
                    <select name="type" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--secondary-bg); color: var(--text-color);">
                        <option value="">Todos los tipos</option>
                        <option value="casa" <?php echo $filter_type == 'casa' ? 'selected' : ''; ?>>Casa</option>
                        <option value="apartamento" <?php echo $filter_type == 'apartamento' ? 'selected' : ''; ?>>Apartamento</option>
                        <option value="local" <?php echo $filter_type == 'local' ? 'selected' : ''; ?>>Local Comercial</option>
                        <option value="terreno" <?php echo $filter_type == 'terreno' ? 'selected' : ''; ?>>Terreno</option>
                    </select>
                </div>

                <div class="filter-group">
                    <h3>Ubicación</h3>
                    <input type="text" name="loc" placeholder="Ciudad o zona..." value="<?php echo htmlspecialchars($filter_loc); ?>" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--secondary-bg); color: var(--text-color);">
                </div>

                <div class="filter-group">
                    <h3>Rango de Precio</h3>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" name="pmin" placeholder="Mínimo" value="<?php echo $filter_price_min ?: ''; ?>" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--secondary-bg); color: var(--text-color);">
                        <input type="number" name="pmax" placeholder="Máximo" value="<?php echo $filter_price_max == 999999999 ? '' : $filter_price_max; ?>" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--secondary-bg); color: var(--text-color);">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; border-radius: 12px; font-weight: 800;">Aplicar Filtros</button>
                <a href="dashboard.php" class="btn btn-outline" style="width: 100%; margin-top: 10px; padding: 12px; border-radius: 12px; font-weight: 700; border-width: 2px;">Limpiar Filtros</a>
            </form>
        </div>
    </aside>

    <!-- Grid de Propiedades Mejorada -->
    <main class="content-grid">
        <?php if (empty($properties)): ?>
            <div class="info-section" style="grid-column: 1/-1; text-align: center; padding: 80px; border-radius: 32px;">
                <i class="fas fa-search" style="font-size: 3.5rem; color: var(--border-color); margin-bottom: 25px; display: block;"></i>
                <h2 style="font-weight: 900;">No encontramos resultados</h2>
                <p style="color: var(--muted-text); font-size: 1.1rem; max-width: 400px; margin: 15px auto;">Intenta ajustar tus filtros o realiza una búsqueda diferente.</p>
                <a href="dashboard.php" class="btn btn-outline" style="margin-top: 20px; border-radius: 12px; padding: 12px 30px;">Ver todas las propiedades</a>
            </div>
        <?php else: ?>
            <?php foreach ($properties as $prop): ?>
                <div class="property-card" onclick="location.href='property_details.php?id=<?php echo $prop['id']; ?>'" style="border-radius: 24px; box-shadow: var(--shadow);">
                    <div class="property-img-container" style="height: 240px;">
                        <span class="op-badge" style="border-radius: 8px; padding: 8px 15px; font-weight: 800;"><?php echo strtoupper($prop['tipo_operacion']); ?></span>
                        <?php if ($prop['tipo_usuario'] == 'compania'): ?>
                            <span class="user-badge" style="background: rgba(0,0,0,0.8); color: #fff; border-radius: 20px; padding: 5px 12px; font-weight: 700;"><i class="fas fa-building"></i> PRO</span>
                        <?php endif; ?>
                        <?php
                            $imgSrc = getPropertyImageUrl($prop['imagen_url']);
                            if (!empty($prop['imagen'])) {
                                $imgSrc = 'data:' . htmlspecialchars($prop['imagen_tipo']) . ';base64,' . base64_encode($prop['imagen']);
                            }
                        ?>
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" class="property-img" alt="Propiedad">
                    </div>
                    <div class="property-info" style="padding: 25px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <div class="property-price" style="font-size: 1.6rem; font-weight: 900; color: var(--accent-color);">
                                $<?php echo number_format($prop['precio'], 0); ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--muted-text); font-weight: 700; text-transform: uppercase; background: var(--secondary-bg); padding: 4px 10px; border-radius: 6px;">
                                <?php echo htmlspecialchars($prop['tipo_propiedad']); ?>
                            </div>
                        </div>
                        <div class="property-title" style="font-size: 1.1rem; font-weight: 800; margin-bottom: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?php echo htmlspecialchars($prop['titulo']); ?>
                        </div>
                        <div class="property-meta" style="display: flex; gap: 15px; color: var(--muted-text); font-size: 0.85rem; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">
                            <span><i class="fas fa-bed"></i> <?php echo $prop['habitaciones']; ?></span>
                            <span><i class="fas fa-bath"></i> <?php echo $prop['banos']; ?></span>
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($prop['ubicacion']); ?></span>
                        </div>
                        
                        <div class="seller-mini" onclick="event.stopPropagation(); location.href='profile.php?id=<?php echo $prop['user_id']; ?>'" style="border-top: none; padding-top: 0; margin-top: 0; display: flex; align-items: center; gap: 12px;">
                            <div class="profile-avatar-wrapper">
                                <div class="seller-avatar-mini" style="width: 36px; height: 36px; border-radius: 50%; font-size: 0.8rem; border: 2px solid var(--border-color);">
                                    <?php if (!empty($prop['foto_perfil'])): ?>
                                        <img src="data:<?php echo $prop['foto_perfil_tipo']; ?>;base64,<?php echo base64_encode($prop['foto_perfil']); ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($prop['nombre'], 0, 1) . substr($prop['apellido'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($prop['verified'])): ?>
                                    <div class="verified-badge small" style="width: 16px; height: 16px; font-size: 0.5rem; border-width: 1.5px;">V</div>
                                <?php endif; ?>
                            </div>
                            <div class="seller-name-mini" style="font-size: 0.9rem; font-weight: 700; color: var(--text-color);">
                                <?php echo htmlspecialchars($prop['nombre']); ?>
                            </div>
                            <div style="margin-left: auto; font-size: 0.85rem; font-weight: 800; color: #f59e0b; background: rgba(245, 158, 11, 0.1); padding: 4px 10px; border-radius: 8px;">
                                <i class="fas fa-star"></i> <?php echo number_format($prop['rating'], 1); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>

</body>
</html>
