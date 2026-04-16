<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}
require_once dirname(__DIR__) . '/config/db.php';
include dirname(__DIR__) . '/includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Obtener datos del vendedor
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$seller = $stmt->fetch();

if (!$seller) die("Vendedor no encontrado.");

// Obtener propiedades del vendedor
$stmt = $pdo->prepare("SELECT * FROM properties WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$properties = $stmt->fetchAll();

// Obtener reseñas
$stmt = $pdo->prepare("SELECT r.*, u.nombre, u.apellido, u.foto_perfil, u.foto_perfil_tipo FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE r.seller_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

$reviewDistribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$stmt = $pdo->prepare("SELECT stars, COUNT(*) AS count FROM reviews WHERE seller_id = ? GROUP BY stars");
$stmt->execute([$id]);
foreach ($stmt->fetchAll() as $row) {
    $reviewDistribution[(int)$row['stars']] = (int)$row['count'];
}

$reviewStats = ['avg_rating' => 0, 'total' => 0];
$stmt = $pdo->prepare("SELECT AVG(stars) AS avg_rating, COUNT(*) AS total FROM reviews WHERE seller_id = ?");
$stmt->execute([$id]);
$reviewStats = $stmt->fetch();
$reviewStats['avg_rating'] = $reviewStats['avg_rating'] ? floatval($reviewStats['avg_rating']) : 0;
$reviewStats['total'] = intval($reviewStats['total']);

$userReview = null;
foreach ($reviews as $rev) {
    if ($rev['reviewer_id'] == $user_id) {
        $userReview = $rev;
        break;
    }
}

$sellerInitials = strtoupper(substr($seller['nombre'], 0, 1) . substr($seller['apellido'], 0, 1));
$sellerAvatar = !empty($seller['foto_perfil']) ? 'data:' . $seller['foto_perfil_tipo'] . ';base64,' . base64_encode($seller['foto_perfil']) : null;
?>

<div class="main-container" style="flex-direction: column; max-width: 1200px; margin: 40px auto;">
    <!-- Cabecera de Perfil Mejorada -->
    <div class="filter-card" style="width: 100%; position: static; display: flex; align-items: center; gap: 40px; padding: 50px; margin-bottom: 40px; border-radius: 32px;">
        <div class="profile-avatar-wrapper">
            <div class="seller-avatar-mini" style="width: 160px; height: 160px; border-radius: 50%; font-size: 3rem; border: 5px solid var(--accent-color); box-shadow: var(--shadow-lg);">
                <?php if ($sellerAvatar): ?>
                    <img src="<?php echo $sellerAvatar; ?>" alt="Avatar">
                <?php else: ?>
                    <?php echo $sellerInitials; ?>
                <?php endif; ?>
            </div>
            <?php if (!empty($seller['verified'])): ?>
                <div class="verified-badge" title="Usuario Verificado" style="width: 44px; height: 44px; font-size: 1.2rem; bottom: 10px; right: 10px; border-width: 4px;">V</div>
            <?php endif; ?>
        </div>
        
        <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 12px; flex-wrap: wrap;">
                <h1 style="font-size: 2.8rem; font-weight: 900; margin: 0;"><?php echo htmlspecialchars($seller['nombre'] . ' ' . $seller['apellido']); ?></h1>
                <div style="display: flex; gap: 10px;">
                    <?php if ($seller['tipo_usuario'] == 'compania'): ?>
                        <span class="badge-pill company" style="padding: 6px 18px; font-size: 0.85rem;">COMPAÑÍA</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="font-size: 1.2rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <span style="color: #f59e0b;"><i class="fas fa-star"></i> <?php echo number_format($reviewStats['avg_rating'], 1); ?></span>
                <span style="color: var(--muted-text); font-weight: 600;">(<?php echo $reviewStats['total']; ?> reseñas)</span>
                <span style="width: 4px; height: 4px; background: var(--border-color); border-radius: 50%;"></span>
                <span style="color: var(--muted-text); font-size: 0.95rem;">Miembro desde <?php echo date('M Y', strtotime($seller['created_at'])); ?></span>
            </div>
            
            <p style="font-size: 1.15rem; color: var(--text-color); max-width: 850px; line-height: 1.7;"><?php echo nl2br(htmlspecialchars($seller['bio'] ?: 'Sin biografía disponible.')); ?></p>
        </div>
    </div>

    <!-- Navegación por Pestañas -->
    <div class="profile-tabs">
        <div id="tab-props" class="tab active" onclick="switchTab('props')">Proyectos Activos (<?php echo count($properties); ?>)</div>
        <div id="tab-reviews" class="tab" onclick="switchTab('reviews')">Reseñas de Clientes (<?php echo count($reviews); ?>)</div>
    </div>

    <!-- Sección de Propiedades -->
    <div id="content-props" class="content-grid">
        <?php if (empty($properties)): ?>
            <div class="info-section" style="grid-column: 1/-1; text-align: center; padding: 60px;">
                <i class="fas fa-home" style="font-size: 3rem; color: var(--border-color); margin-bottom: 20px; display: block;"></i>
                <p style="color: var(--muted-text); font-size: 1.1rem; font-weight: 600;">Este vendedor no tiene propiedades activas en este momento.</p>
            </div>
        <?php else: ?>
            <?php foreach ($properties as $prop): ?>
                <?php
                    $imageSrc = getPropertyImageUrl($prop['imagen_url']);
                    if (!empty($prop['imagen'])) {
                        $imageSrc = 'data:' . htmlspecialchars($prop['imagen_tipo']) . ';base64,' . base64_encode($prop['imagen']);
                    }
                ?>
                <div class="property-card" onclick="location.href='property_details.php?id=<?php echo $prop['id']; ?>'">
                    <div class="property-img-container">
                        <span class="op-badge"><?php echo $prop['tipo_operacion']; ?></span>
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
        <?php endif; ?>
    </div>

    <!-- Sección de Reseñas -->
    <div id="content-reviews" style="display: none; flex-direction: column; gap: 30px;">
        <div class="review-summary">
            <div class="rating-card" style="flex: 1; min-width: 300px;">
                <h4>Calificación promedio</h4>
                <div class="rating-value">
                    <?php echo number_format($reviewStats['avg_rating'], 1); ?>
                    <span>/ 5.0</span>
                </div>
                <div class="rating-subtitle">Basado en <?php echo $reviewStats['total']; ?> valoraciones reales de clientes.</div>
            </div>
            <div class="rating-card" style="flex: 2; min-width: 300px;">
                <h4>Distribución de estrellas</h4>
                <div class="rating-bars">
                    <?php foreach ([5, 4, 3, 2, 1] as $star):
                        $count = $reviewDistribution[$star];
                        $percent = $reviewStats['total'] ? round(($count / $reviewStats['total']) * 100) : 0;
                    ?>
                        <div class="rating-bar">
                            <span style="font-weight: 700;"><?php echo $star; ?>★</span>
                            <div class="bar-bg"><div class="bar-fill" style="width: <?php echo $percent; ?>%;"></div></div>
                            <span style="text-align: right; min-width: 20px;"><?php echo $count; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if ($id != $user_id): ?>
            <div class="review-form-card info-section">
                <h4 style="font-size: 1.3rem;"><?php echo $userReview ? 'Actualizar tu reseña' : 'Escribir una reseña'; ?></h4>
                <p style="color: var(--muted-text); margin-bottom: 25px;">Tu opinión ayuda a otros usuarios a tomar mejores decisiones.</p>
                <form action="actions.php" method="POST">
                    <input type="hidden" name="action" value="add_review">
                    <input type="hidden" name="seller_id" value="<?php echo $id; ?>">
                    <input type="hidden" name="redirect_to" value="profile.php?id=<?php echo $id; ?>">
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                        <div class="filter-group">
                            <label>Calificación:</label>
                            <select name="stars" required style="height: 56px; border-radius: 12px;">
                                <option value="5" <?php echo ($userReview['stars'] ?? 5) == 5 ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ (Excelente)</option>
                                <option value="4" <?php echo ($userReview['stars'] ?? '') == 4 ? 'selected' : ''; ?>>⭐⭐⭐⭐ (Muy Bueno)</option>
                                <option value="3" <?php echo ($userReview['stars'] ?? '') == 3 ? 'selected' : ''; ?>>⭐⭐⭐ (Normal)</option>
                                <option value="2" <?php echo ($userReview['stars'] ?? '') == 2 ? 'selected' : ''; ?>>⭐⭐ (Regular)</option>
                                <option value="1" <?php echo ($userReview['stars'] ?? '') == 1 ? 'selected' : ''; ?>>⭐ (Malo)</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Tu comentario:</label>
                            <textarea name="comment" rows="1" placeholder="Describe tu experiencia con este vendedor..." required style="height: 56px; border-radius: 12px; resize: none;"><?php echo htmlspecialchars($userReview['comment'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: auto; padding: 15px 40px; margin-top: 10px;"><?php echo $userReview ? 'Guardar Cambios' : 'Publicar Reseña Ahora'; ?></button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (empty($reviews)): ?>
            <div class="info-section" style="text-align: center; padding: 60px;">
                <i class="fas fa-comment-slash" style="font-size: 3rem; color: var(--border-color); margin-bottom: 20px; display: block;"></i>
                <p style="color: var(--muted-text); font-size: 1.1rem; font-weight: 600;">Aún no hay reseñas para este vendedor.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <?php foreach ($reviews as $rev): ?>
                    <?php 
                        $revInitials = strtoupper(substr($rev['nombre'], 0, 1) . substr($rev['apellido'], 0, 1)); 
                        $revAvatar = !empty($rev['foto_perfil']) ? 'data:' . $rev['foto_perfil_tipo'] . ';base64,' . base64_encode($rev['foto_perfil']) : null;
                    ?>
                    <div class="review-card">
                        <div class="review-card-header">
                            <div class="review-author">
                                <div class="review-author-avatar">
                                    <?php if ($revAvatar): ?>
                                        <img src="<?php echo $revAvatar; ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($revInitials); ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="review-author-name"><?php echo htmlspecialchars($rev['nombre'] . ' ' . $rev['apellido']); ?></div>
                                    <div class="review-meta-note">Publicado el <?php echo formatDate($rev['created_at']); ?></div>
                                </div>
                            </div>
                            <div class="review-stars">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <i class="<?php echo $i < $rev['stars'] ? 'fas' : 'far'; ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p style="padding-left: 62px;"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>

                        <?php if ($rev['reviewer_id'] == $user_id): ?>
                            <div style="padding-left: 62px; margin-top: 15px;">
                                <button type="button" class="btn btn-outline" style="padding: 8px 15px; font-size: 0.85rem;" onclick="toggleReviewEdit(<?php echo $rev['id']; ?>)">
                                    <i class="fas fa-edit"></i> Editar mi reseña
                                </button>
                            </div>
                            <div id="edit-review-<?php echo $rev['id']; ?>" style="display: none; margin-top: 18px; padding-left: 62px;">
                                <div class="review-form-card" style="padding: 25px; background: var(--card-bg);">
                                    <form action="actions.php" method="POST">
                                        <input type="hidden" name="action" value="add_review">
                                        <input type="hidden" name="seller_id" value="<?php echo $id; ?>">
                                        <input type="hidden" name="redirect_to" value="profile.php?id=<?php echo $id; ?>">
                                        <div class="filter-group">
                                            <label>Calificación:</label>
                                            <select name="stars" required>
                                                <option value="5" <?php echo $rev['stars'] == 5 ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ (5 Estrellas)</option>
                                                <option value="4" <?php echo $rev['stars'] == 4 ? 'selected' : ''; ?>>⭐⭐⭐⭐ (4 Estrellas)</option>
                                                <option value="3" <?php echo $rev['stars'] == 3 ? 'selected' : ''; ?>>⭐⭐⭐ (3 Estrellas)</option>
                                                <option value="2" <?php echo $rev['stars'] == 2 ? 'selected' : ''; ?>>⭐⭐ (2 Estrellas)</option>
                                                <option value="1" <?php echo $rev['stars'] == 1 ? 'selected' : ''; ?>>⭐ (1 Estrella)</option>
                                            </select>
                                        </div>
                                        <div class="filter-group">
                                            <label>Comentario:</label>
                                            <textarea name="comment" rows="4" required><?php echo htmlspecialchars($rev['comment']); ?></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Actualizar Cambios</button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function switchTab(tab) {
    const propsTab = document.getElementById('tab-props');
    const reviewsTab = document.getElementById('tab-reviews');
    const propsContent = document.getElementById('content-props');
    const reviewsContent = document.getElementById('content-reviews');

    if (tab === 'props') {
        propsTab.classList.add('active');
        reviewsTab.classList.remove('active');
        propsContent.style.display = 'grid';
        reviewsContent.style.display = 'none';
    } else {
        reviewsTab.classList.add('active');
        propsTab.classList.remove('active');
        propsContent.style.display = 'none';
        reviewsContent.style.display = 'flex';
    }
}

function toggleReviewEdit(reviewId) {
    const el = document.getElementById('edit-review-' + reviewId);
    if (el) {
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }
}
</script>

</body>
</html>
