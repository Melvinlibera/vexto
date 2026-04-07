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

// Incrementar vistas
$stmt = $pdo->prepare("UPDATE properties SET vistas = vistas + 1 WHERE id = ?");
$stmt->execute([$id]);

// Obtener detalles
$stmt = $pdo->prepare("SELECT p.*, u.nombre, u.apellido, u.rating, u.total_reviews, u.foto_perfil, u.foto_perfil_tipo, u.tipo_usuario, u.bio, u.verified 
                      FROM properties p 
                      JOIN users u ON p.user_id = u.id 
                      WHERE p.id = ?");
$stmt->execute([$id]);
$prop = $stmt->fetch();

if (!$prop) die("Propiedad no encontrada.");

// Verificar si es favorito
$stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND property_id = ?");
$stmt->execute([$user_id, $id]);
$is_fav = $stmt->fetch();

// Obtener reseñas del vendedor
$stmt = $pdo->prepare("SELECT r.*, u.nombre, u.apellido, u.foto_perfil, u.foto_perfil_tipo FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE r.seller_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$prop['user_id']]);
$property_reviews = $stmt->fetchAll();

$reviewStats = ['avg_rating' => 0, 'total' => 0];
$stmt = $pdo->prepare("SELECT AVG(stars) AS avg_rating, COUNT(*) AS total FROM reviews WHERE seller_id = ?");
$stmt->execute([$prop['user_id']]);
$reviewStats = $stmt->fetch();
$reviewStats['avg_rating'] = $reviewStats['avg_rating'] ? floatval($reviewStats['avg_rating']) : 0;
$reviewStats['total'] = intval($reviewStats['total']);

$stmt = $pdo->prepare("SELECT * FROM reviews WHERE seller_id = ? AND reviewer_id = ?");
$stmt->execute([$prop['user_id'], $user_id]);
$user_review = $stmt->fetch();

$sellerInitials = strtoupper(substr($prop['nombre'], 0, 1) . substr($prop['apellido'], 0, 1));
$sellerAvatar = !empty($prop['foto_perfil']) ? 'data:' . $prop['foto_perfil_tipo'] . ';base64,' . base64_encode($prop['foto_perfil']) : null;
?>

<div class="main-container" style="flex-direction: column; max-width: 1200px; margin: 40px auto;">
    <?php
        $imageSrc = getPropertyImageUrl($prop['imagen_url']);
        if (!empty($prop['imagen'])) {
            $imageSrc = 'data:' . htmlspecialchars($prop['imagen_tipo']) . ';base64,' . base64_encode($prop['imagen']);
        }
    ?>
    <div class="property-img-container" style="height: 550px; border-radius: 32px; margin-bottom: 40px; box-shadow: var(--shadow-lg);">
        <span class="op-badge" style="font-size: 1rem; padding: 10px 25px; border-radius: 12px;"><?php echo $prop['tipo_operacion']; ?></span>
        <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="property-img" alt="Propiedad">
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
        <div class="info-section">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; gap: 20px;">
                <div>
                    <h1 style="font-size: 2.8rem; font-weight: 900; margin-bottom: 10px;"><?php echo htmlspecialchars($prop['titulo']); ?></h1>
                    <p style="font-size: 1.25rem; color: var(--muted-text); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-map-marker-alt" style="color: var(--accent-color);"></i> <?php echo htmlspecialchars($prop['ubicacion']); ?>
                    </p>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 2.8rem; font-weight: 900; color: var(--accent-color);">$<?php echo number_format($prop['precio'], 2); ?></div>
                    <p style="font-size: 0.95rem; color: var(--muted-text); font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Precio de <?php echo $prop['tipo_operacion']; ?></p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; background: var(--secondary-bg); border-radius: 20px; padding: 25px; margin-bottom: 40px;">
                <div style="text-align: center; border-right: 1px solid var(--border-color);">
                    <div style="font-size: 1.6rem; font-weight: 900;"><?php echo $prop['habitaciones']; ?></div>
                    <div style="font-size: 0.75rem; color: var(--muted-text); text-transform: uppercase; font-weight: 700; margin-top: 4px;">Habitaciones</div>
                </div>
                <div style="text-align: center; border-right: 1px solid var(--border-color);">
                    <div style="font-size: 1.6rem; font-weight: 900;"><?php echo $prop['banos']; ?></div>
                    <div style="font-size: 0.75rem; color: var(--muted-text); text-transform: uppercase; font-weight: 700; margin-top: 4px;">Baños</div>
                </div>
                <div style="text-align: center; border-right: 1px solid var(--border-color);">
                    <div style="font-size: 1.6rem; font-weight: 900;"><?php echo $prop['area_m2'] ?: 'N/A'; ?></div>
                    <div style="font-size: 0.75rem; color: var(--muted-text); text-transform: uppercase; font-weight: 700; margin-top: 4px;">Área m²</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 1.6rem; font-weight: 900;"><?php echo ucfirst($prop['tipo_propiedad']); ?></div>
                    <div style="font-size: 0.75rem; color: var(--muted-text); text-transform: uppercase; font-weight: 700; margin-top: 4px;">Tipo</div>
                </div>
            </div>

            <div style="margin-bottom: 50px;">
                <h3 style="margin-bottom: 20px; font-size: 1.6rem; font-weight: 900; display: flex; align-items: center; gap: 12px;">
                    <span style="width: 8px; height: 32px; background: var(--accent-color); border-radius: 4px;"></span>
                    Descripción del Proyecto
                </h3>
                <p style="font-size: 1.15rem; line-height: 1.8; color: var(--text-color);"><?php echo nl2br(htmlspecialchars($prop['descripcion'])); ?></p>
            </div>

            <div style="margin-bottom: 50px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; gap: 24px;">
                    <div>
                        <h3 style="margin: 0; font-size: 1.6rem; font-weight: 900;">Reseñas del Vendedor</h3>
                        <p style="margin: 8px 0 0; color: var(--muted-text); font-size: 1rem;">Experiencias compartidas por otros clientes.</p>
                    </div>
                    <div style="background: var(--secondary-bg); padding: 10px 20px; border-radius: 12px; font-size: 1.1rem; color: #f59e0b; font-weight: 800; display: flex; align-items: center; gap: 10px; border: 1px solid var(--border-color);">
                        <?php echo number_format($reviewStats['avg_rating'], 1); ?> <i class="fas fa-star"></i>
                        <span style="font-size: 0.9rem; color: var(--muted-text); font-weight: 600;">(<?php echo $reviewStats['total']; ?>)</span>
                    </div>
                </div>

                <?php if ($prop['user_id'] != $user_id): ?>
                    <div class="review-form-card" style="margin-bottom: 35px; background: var(--secondary-bg); border-radius: 24px; padding: 30px;">
                        <h4 style="margin-bottom: 20px; font-weight: 900; font-size: 1.2rem;"><?php echo $user_review ? 'Actualizar tu reseña' : 'Escribir una reseña'; ?></h4>
                        <form action="actions.php" method="POST">
                            <input type="hidden" name="action" value="add_review">
                            <input type="hidden" name="seller_id" value="<?php echo $prop['user_id']; ?>">
                            <input type="hidden" name="property_id" value="<?php echo $id; ?>">
                            <input type="hidden" name="redirect_to" value="property_details.php?id=<?php echo $id; ?>">
                            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 15px;">
                                <div class="filter-group">
                                    <label>Calificación:</label>
                                    <select name="stars" required style="height: 56px; border-radius: 12px;">
                                        <option value="5" <?php echo ($user_review['stars'] ?? 5) == 5 ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ 5 Estrellas</option>
                                        <option value="4" <?php echo ($user_review['stars'] ?? '') == 4 ? 'selected' : ''; ?>>⭐⭐⭐⭐ 4 Estrellas</option>
                                        <option value="3" <?php echo ($user_review['stars'] ?? '') == 3 ? 'selected' : ''; ?>>⭐⭐⭐ 3 Estrellas</option>
                                        <option value="2" <?php echo ($user_review['stars'] ?? '') == 2 ? 'selected' : ''; ?>>⭐⭐ 2 Estrellas</option>
                                        <option value="1" <?php echo ($user_review['stars'] ?? '') == 1 ? 'selected' : ''; ?>>⭐ 1 Estrella</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label>Comentario:</label>
                                    <textarea name="comment" rows="1" placeholder="Describe tu experiencia con este vendedor..." required style="height: 56px; border-radius: 12px; resize: none;"><?php echo htmlspecialchars($user_review['comment'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: auto; padding: 15px 40px;"><?php echo $user_review ? 'Actualizar Reseña' : 'Publicar Reseña Ahora'; ?></button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if (empty($property_reviews)): ?>
                    <div style="text-align:center; color:var(--muted-text); padding: 40px; background: var(--secondary-bg); border-radius: 20px; border: 1px dashed var(--border-color);">
                        <i class="fas fa-comment-slash" style="font-size: 2rem; margin-bottom: 15px; display: block;"></i>
                        Aún no hay reseñas para este vendedor.
                    </div>
                <?php else: ?>
                    <div style="display: grid; gap: 20px;">
                        <?php foreach ($property_reviews as $review): ?>
                            <?php 
                                $revInitials = strtoupper(substr($review['nombre'], 0, 1) . substr($review['apellido'], 0, 1)); 
                                $revAvatar = !empty($review['foto_perfil']) ? 'data:' . $review['foto_perfil_tipo'] . ';base64,' . base64_encode($review['foto_perfil']) : null;
                            ?>
                            <div class="review-card" style="background: var(--secondary-bg); border-radius: 24px; padding: 25px;">
                                <div class="review-card-header" style="margin-bottom: 15px;">
                                    <div class="review-author">
                                        <div class="review-author-avatar" style="width: 50px; height: 50px; border-radius: 50%; background: var(--avatar-bg); color: var(--avatar-text); display: flex; align-items: center; justify-content: center; font-weight: 800;">
                                            <?php if ($revAvatar): ?>
                                                <img src="<?php echo $revAvatar; ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($revInitials); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="review-author-name" style="font-weight: 800;"><?php echo htmlspecialchars($review['nombre'] . ' ' . $review['apellido']); ?></div>
                                            <div class="review-meta-note" style="font-size: 0.85rem; color: var(--muted-text);">Publicado el <?php echo formatDate($review['created_at']); ?></div>
                                        </div>
                                    </div>
                                    <div class="review-stars" style="color: #f59e0b;">
                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                            <i class="<?php echo $i < $review['stars'] ? 'fas' : 'far'; ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p style="padding-left: 64px; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                <?php if ($review['reviewer_id'] == $user_id): ?>
                                    <div style="padding-left: 64px; margin-top: 15px;">
                                        <button type="button" class="btn btn-outline" style="padding: 8px 18px; font-size: 0.85rem;" onclick="toggleReviewEdit(<?php echo $review['id']; ?>)">
                                            <i class="fas fa-edit"></i> Editar mi reseña
                                        </button>
                                    </div>
                                    <div id="edit-review-<?php echo $review['id']; ?>" style="display: none; margin-top: 18px; padding-left: 64px;">
                                        <div class="review-form-card" style="padding: 25px; background: var(--card-bg);">
                                            <form action="actions.php" method="POST">
                                                <input type="hidden" name="action" value="add_review">
                                                <input type="hidden" name="seller_id" value="<?php echo $prop['user_id']; ?>">
                                                <input type="hidden" name="property_id" value="<?php echo $id; ?>">
                                                <input type="hidden" name="redirect_to" value="property_details.php?id=<?php echo $id; ?>">
                                                <div class="filter-group">
                                                    <label>Calificación:</label>
                                                    <select name="stars" required>
                                                        <option value="5" <?php echo $review['stars'] == 5 ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ 5 Estrellas</option>
                                                        <option value="4" <?php echo $review['stars'] == 4 ? 'selected' : ''; ?>>⭐⭐⭐⭐ 4 Estrellas</option>
                                                        <option value="3" <?php echo $review['stars'] == 3 ? 'selected' : ''; ?>>⭐⭐⭐ 3 Estrellas</option>
                                                        <option value="2" <?php echo $review['stars'] == 2 ? 'selected' : ''; ?>>⭐⭐ 2 Estrellas</option>
                                                        <option value="1" <?php echo $review['stars'] == 1 ? 'selected' : ''; ?>>⭐ 1 Estrella</option>
                                                    </select>
                                                </div>
                                                <div class="filter-group">
                                                    <label>Comentario:</label>
                                                    <textarea name="comment" rows="4" required><?php echo htmlspecialchars($review['comment']); ?></textarea>
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

            <div style="margin-bottom: 40px;">
                <h3 style="margin-bottom: 20px; font-size: 1.6rem; font-weight: 900; display: flex; align-items: center; gap: 12px;">
                    <span style="width: 8px; height: 32px; background: var(--accent-color); border-radius: 4px;"></span>
                    Ubicación Exacta
                </h3>
                <div id="map" style="height: 450px; border-radius: 24px; box-shadow: var(--shadow);"></div>
            </div>
        </div>

        <aside class="actions-sidebar">
            <div class="filter-card" style="position: sticky; top: 100px; border-radius: 32px; padding: 35px; box-shadow: var(--shadow-lg);">
                <div style="display: flex; align-items: center; gap: 18px; margin-bottom: 30px; cursor: pointer; padding: 10px; border-radius: 16px; transition: var(--transition);" onclick="location.href='profile.php?id=<?php echo $prop['user_id']; ?>'" onmouseover="this.style.background='var(--secondary-bg)'" onmouseout="this.style.background='transparent'">
                    <div class="profile-avatar-wrapper">
                        <div class="seller-avatar-mini" style="width: 70px; height: 70px; border-radius: 50%; font-size: 1.5rem; border: 3px solid var(--accent-color);">
                            <?php if ($sellerAvatar): ?>
                                <img src="<?php echo $sellerAvatar; ?>" alt="Avatar">
                            <?php else: ?>
                                <?php echo $sellerInitials; ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($prop['verified'])): ?>
                            <div class="verified-badge small" style="width: 24px; height: 24px; font-size: 0.75rem; border-width: 2px;">V</div>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 900; font-size: 1.15rem; margin-bottom: 4px;"><?php echo htmlspecialchars($prop['nombre'] . ' ' . $prop['apellido']); ?></div>
                        <div style="font-size: 0.85rem; color: var(--muted-text); display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <?php if ($prop['tipo_usuario'] == 'compania'): ?>
                                <span class="badge-pill company" style="font-size: 0.65rem; padding: 3px 8px;">COMPAÑÍA</span>
                            <?php endif; ?>
                            <span style="color: #f59e0b; font-weight: 700;"><i class="fas fa-star"></i> <?php echo number_format($reviewStats['avg_rating'], 1); ?></span>
                            <span style="color: var(--muted-text);">(<?php echo $reviewStats['total']; ?>)</span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php if ($prop['user_id'] != $user_id): ?>
                        <button class="btn btn-primary" style="width: 100%; padding: 18px; font-size: 1rem;" onclick="openChatModal()">
                            <i class="fas fa-comment-dots"></i> Contactar Vendedor
                        </button>
                        <button class="btn btn-outline" style="width: 100%; padding: 18px; font-size: 1rem;" onclick="openModal()">
                            <i class="fas fa-calendar-alt"></i> Agendar una Cita
                        </button>
                    <?php else: ?>
                        <button class="btn btn-primary" style="width: 100%; padding: 18px; font-size: 1rem;" onclick="location.href='publish.php?edit=<?php echo $id; ?>'">
                            <i class="fas fa-edit"></i> Editar Publicación
                        </button>
                    <?php endif; ?>
                    
                    <form action="actions.php" method="POST">
                        <input type="hidden" name="action" value="toggle_favorite">
                        <input type="hidden" name="property_id" value="<?php echo $id; ?>">
                        <button type="submit" class="btn btn-outline" style="width: 100%; padding: 18px; font-size: 1rem; border-color: #ff4444; color: <?php echo $is_fav ? '#fff' : '#ff4444'; ?>; background: <?php echo $is_fav ? '#ff4444' : 'transparent'; ?>;">
                            <i class="<?php echo $is_fav ? 'fas' : 'far'; ?> fa-heart"></i> 
                            <?php echo $is_fav ? 'En mis Favoritos' : 'Guardar en Favoritos'; ?>
                        </button>
                    </form>

                    <button class="btn" style="width: 100%; color: var(--muted-text); font-size: 0.85rem; background: var(--secondary-bg); border: 1px solid var(--border-color); margin-top: 10px; padding: 12px;" onclick="reportPost()">
                        <i class="fas fa-flag"></i> Reportar este anuncio
                    </button>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- Modales y Scripts (Sin cambios significativos en lógica) -->
<div id="appointmentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 2000; justify-content: center; align-items: center; backdrop-filter: blur(8px);">
    <div class="filter-card" style="width: 420px; position: static; animation: scaleIn 0.3s ease; border-radius: 32px; padding: 40px;">
        <h2 style="margin-bottom: 15px; font-weight: 900; font-size: 1.8rem;">AGENDAR CITA</h2>
        <p style="color: var(--muted-text); margin-bottom: 25px; font-size: 0.95rem;">Selecciona el mejor momento para visitar esta propiedad.</p>
        <form action="actions.php" method="POST">
            <input type="hidden" name="action" value="schedule_appointment">
            <input type="hidden" name="property_id" value="<?php echo $id; ?>">
            <input type="hidden" name="seller_id" value="<?php echo $prop['user_id']; ?>">
            <div class="filter-group">
                <label style="font-weight: 800;">Fecha y Hora:</label>
                <input type="datetime-local" name="fecha" required style="width: 100%; padding: 15px; border-radius: 12px; border: 2px solid var(--border-color); background: var(--secondary-bg); font-family: inherit;">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 12px; padding: 18px; font-size: 1rem;">Confirmar Cita</button>
            <button type="button" class="btn btn-outline" style="width: 100%; padding: 15px;" onclick="closeModal()">Tal vez luego</button>
        </form>
    </div>
</div>

<div id="chatModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 2000; justify-content: center; align-items: center; backdrop-filter: blur(8px);">
    <div class="filter-card" style="width: 480px; position: static; animation: scaleIn 0.3s ease; border-radius: 32px; padding: 40px;">
        <h2 style="margin-bottom: 15px; font-weight: 900; font-size: 1.8rem;">CONTACTAR</h2>
        <p style="font-size: 0.95rem; color: var(--muted-text); margin-bottom: 25px;">Escribe un mensaje directamente al vendedor de este proyecto.</p>
        <form id="quick-chat-form">
            <input type="hidden" name="property_id" value="<?php echo $id; ?>">
            <input type="hidden" name="receiver_id" value="<?php echo $prop['user_id']; ?>">
            <div class="filter-group">
                <textarea name="message" placeholder="Hola, me interesa esta propiedad, ¿podemos conversar?" required 
                          style="width: 100%; height: 150px; padding: 20px; border-radius: 16px; border: 2px solid var(--border-color); background: var(--secondary-bg); color: var(--text-color); resize: none; font-family: inherit; font-size: 1rem;"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 12px; padding: 18px; font-size: 1rem;">Enviar Mensaje Ahora</button>
            <button type="button" class="btn btn-outline" style="width: 100%; padding: 15px;" onclick="closeChatModal()">Cerrar</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof initMap === 'function') {
            initMap(<?php echo $prop['latitud'] ?: 18.4861; ?>, <?php echo $prop['longitud'] ?: -69.9312; ?>, 'map', false);
        }
    });

    function openModal() { document.getElementById('appointmentModal').style.display = 'flex'; }
    function closeModal() { document.getElementById('appointmentModal').style.display = 'none'; }
    
    function openChatModal() { document.getElementById('chatModal').style.display = 'flex'; }
    function closeChatModal() { document.getElementById('chatModal').style.display = 'none'; }

    function toggleReviewEdit(reviewId) {
        const el = document.getElementById('edit-review-' + reviewId);
        if (el) {
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }
    }

    document.getElementById('quick-chat-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('chat_handler.php?action=send', {
            method: 'POST',
            body: formData
        }).then(res => res.json())
          .then(data => {
              if (data.success) {
                  alert('¡Mensaje enviado con éxito!');
                  closeChatModal();
                  location.href = 'messages.php';
              } else {
                  alert('Error: ' + data.message);
              }
          });
    });

    function reportPost() {
        const motivo = prompt("¿Por qué deseas reportar esta publicación?");
        if (motivo) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'actions.php';
            form.innerHTML = `
                <input type="hidden" name="action" value="report">
                <input type="hidden" name="property_id" value="<?php echo $id; ?>">
                <input type="hidden" name="motivo" value="${motivo}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>

</body>
</html>
