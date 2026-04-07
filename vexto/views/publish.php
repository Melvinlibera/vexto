<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}
require_once dirname(__DIR__) . '/config/db.php';
include dirname(__DIR__) . '/includes/header.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$edit_property = null;
$edit_id = $_GET['edit'] ?? null;
if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND user_id = ?");
    $stmt->execute([$edit_id, $user_id]);
    $edit_property = $stmt->fetch();
}

$can_publish = $user['propiedades_publicadas'] < $user['max_propiedades'] || $edit_property !== null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_publish) {
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $tipo_operacion = $_POST['tipo_operacion'];
    $tipo_propiedad = $_POST['tipo_propiedad'];
    $ubicacion = $_POST['ubicacion'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $habitaciones = $_POST['habitaciones'];
    $banos = $_POST['banos'];
    $area_m2 = $_POST['area_m2'];
    $property_id = $_POST['property_id'] ?? null;
    
    $imagen_url = $edit_property['imagen_url'] ?? null;
    $imagen_tipo = $edit_property['imagen_tipo'] ?? null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $maxImageSize = 10 * 1024 * 1024; // 10 MB
        if ($_FILES['imagen']['size'] > $maxImageSize) {
            die('Error al publicar: la imagen supera el tamaño máximo permitido de 10MB.');
        }

        $uploadDir = __DIR__ . '/publicaciones/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['imagen']['name']));
        $targetFile = $uploadDir . $safeName;

        if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $targetFile)) {
            die('Error al publicar: no se pudo mover el archivo de imagen.');
        }

        $imagen_url = 'publicaciones/' . $safeName;
        $imagen_tipo = $_FILES['imagen']['type'];
    }

    try {
        $pdo->beginTransaction();
        if ($property_id) {
            $stmt = $pdo->prepare("UPDATE properties SET titulo = ?, descripcion = ?, precio = ?, tipo_operacion = ?, tipo_propiedad = ?, ubicacion = ?, latitud = ?, longitud = ?, habitaciones = ?, banos = ?, area_m2 = ?, imagen_url = ?, imagen_tipo = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$titulo, $descripcion, $precio, $tipo_operacion, $tipo_propiedad, $ubicacion, $lat, $lng, $habitaciones, $banos, $area_m2, $imagen_url, $imagen_tipo, $property_id, $user_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO properties (user_id, titulo, descripcion, precio, tipo_operacion, tipo_propiedad, ubicacion, latitud, longitud, habitaciones, banos, area_m2, imagen_url, imagen_tipo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $titulo, $descripcion, $precio, $tipo_operacion, $tipo_propiedad, $ubicacion, $lat, $lng, $habitaciones, $banos, $area_m2, $imagen_url, $imagen_tipo]);
            
            $stmt = $pdo->prepare("UPDATE users SET propiedades_publicadas = propiedades_publicadas + 1 WHERE id = ?");
            $stmt->execute([$user_id]);
        }
        
        $pdo->commit();
        header("Location: dashboard.php");
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al publicar: " . $e->getMessage());
    }
}

$titulo = $_POST['titulo'] ?? $edit_property['titulo'] ?? '';
$descripcion = $_POST['descripcion'] ?? $edit_property['descripcion'] ?? '';
$precio = $_POST['precio'] ?? $edit_property['precio'] ?? '';
$tipo_operacion = $_POST['tipo_operacion'] ?? $edit_property['tipo_operacion'] ?? 'venta';
$tipo_propiedad = $_POST['tipo_propiedad'] ?? $edit_property['tipo_propiedad'] ?? 'casa';
$habitaciones = $_POST['habitaciones'] ?? ($edit_property['habitaciones'] ?? 0);
$banos = $_POST['banos'] ?? ($edit_property['banos'] ?? 0);
$area_m2 = $_POST['area_m2'] ?? $edit_property['area_m2'] ?? '';
$ubicacion = $_POST['ubicacion'] ?? $edit_property['ubicacion'] ?? '';
$lat = $_POST['lat'] ?? $edit_property['latitud'] ?? 18.4861;
$lng = $_POST['lng'] ?? $edit_property['longitud'] ?? -69.9312;
$property_id = $_POST['property_id'] ?? $edit_property['id'] ?? null;
?>

<div class="main-container" style="max-width: 800px; margin: 40px auto; flex-direction: column;">
    <div class="filter-card" style="width: 100%; position: static;">
        <h1 style="margin-bottom: 30px; font-size: 2rem; font-weight: 800;">
            <?php echo $edit_property ? 'Editar Propiedad' : 'Publicar Propiedad'; ?>
        </h1>
        
        <?php if (!$can_publish): ?>
            <div style="background: #fff5f5; border: 1px solid #feb2b2; padding: 20px; border-radius: 8px; color: #c53030; margin-bottom: 20px;">
                <h3>Límite alcanzado</h3>
                <p>Has publicado <?php echo $user['propiedades_publicadas']; ?> de <?php echo $user['max_propiedades']; ?> permitidas. Mejora tu plan en configuración.</p>
                <a href="settings.php" class="btn btn-primary" style="margin-top: 15px;">Mejorar Plan</a>
            </div>
        <?php else: ?>
            <form action="publish.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($property_id); ?>">
                <div class="filter-group">
                    <h3>Información Básica</h3>
                    <label>Título de la publicación</label>
                    <input type="text" name="titulo" placeholder="Ej: Hermosa casa en la playa" value="<?php echo htmlspecialchars($titulo); ?>" required>
                    
                    <label>Descripción detallada</label>
                    <textarea name="descripcion" rows="5" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--secondary-bg); color: var(--text-color);" required><?php echo htmlspecialchars($descripcion); ?></textarea>
                </div>

                <div class="filter-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label>Precio ($)</label>
                        <input type="number" name="precio" step="0.01" value="<?php echo htmlspecialchars($precio); ?>" required>
                    </div>
                    <div>
                        <label>Tipo de Operación</label>
                        <select name="tipo_operacion" required>
                            <option value="venta" <?php echo $tipo_operacion === 'venta' ? 'selected' : ''; ?>>Venta</option>
                            <option value="alquiler" <?php echo $tipo_operacion === 'alquiler' ? 'selected' : ''; ?>>Alquiler</option>
                        </select>
                    </div>
                </div>

                <div class="filter-group" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div>
                        <label>Tipo de Propiedad</label>
                        <select name="tipo_propiedad" required>
                            <option value="casa" <?php echo $tipo_propiedad === 'casa' ? 'selected' : ''; ?>>Casa</option>
                            <option value="apartamento" <?php echo $tipo_propiedad === 'apartamento' ? 'selected' : ''; ?>>Apartamento</option>
                            <option value="local" <?php echo $tipo_propiedad === 'local' ? 'selected' : ''; ?>>Local Comercial</option>
                            <option value="terreno" <?php echo $tipo_propiedad === 'terreno' ? 'selected' : ''; ?>>Terreno</option>
                        </select>
                    </div>
                    <div>
                        <label>Habitaciones</label>
                        <input type="number" name="habitaciones" value="<?php echo htmlspecialchars($habitaciones); ?>">
                    </div>
                    <div>
                        <label>Baños</label>
                        <input type="number" name="banos" value="<?php echo htmlspecialchars($banos); ?>">
                    </div>
                </div>

                <div class="filter-group">
                    <label>Área (m²)</label>
                    <input type="number" name="area_m2" step="0.01" value="<?php echo htmlspecialchars($area_m2); ?>" required>
                </div>

                <div class="filter-group">
                    <h3>Ubicación Exacta</h3>
                    <label>Dirección o Zona</label>
                    <input type="text" name="ubicacion" placeholder="Ej: Calle 123, Ciudad" value="<?php echo htmlspecialchars($ubicacion); ?>" required>
                    
                    <label>Selecciona en el mapa</label>
                    <div id="map" style="height: 300px;"></div>
                    <input type="hidden" name="lat" id="lat" value="<?php echo htmlspecialchars($lat); ?>">
                    <input type="hidden" name="lng" id="lng" value="<?php echo htmlspecialchars($lng); ?>">
                </div>

                <div class="filter-group">
                    <h3>Multimedia</h3>
                    <label>Imagen Principal</label>
                    <div class="file-upload-wrapper">
                        <label for="imagenFile" class="btn btn-outline file-select-btn">
                            <i class="fas fa-upload"></i> Seleccionar imagen
                        </label>
                        <span id="selectedFileName" class="file-upload-label">Ningún archivo seleccionado</span>
                    </div>
                    <input type="file" id="imagenFile" name="imagen" accept="image/*" <?php echo empty($edit_property['imagen_url']) ? 'required' : ''; ?> style="display:none;">

                    <div id="imagenPreview" style="margin-top: 15px; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; width: 100%; max-height: 300px; display: none; cursor: pointer;">
                        <img id="imagenPreviewImg" src="" alt="Vista previa" style="width: 100%; height: auto; display: block; object-fit: cover;" />
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1.1rem;">Publicar Proyecto</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Inicializar mapa con coordenadas actuales o por defecto
        const lat = parseFloat(document.getElementById('lat').value) || 18.4861;
        const lng = parseFloat(document.getElementById('lng').value) || -69.9312;
        initMap(lat, lng, 'map', true);
    });
    document.addEventListener('DOMContentLoaded', () => {
        const imagenFileInput = document.getElementById('imagenFile');
        const imagenPreview = document.getElementById('imagenPreview');
        const imagenPreviewImg = document.getElementById('imagenPreviewImg');

        const selectedFileName = document.getElementById('selectedFileName');

        if (imagenFileInput) {
            imagenFileInput.addEventListener('change', e => {
                const file = e.target.files[0];
                if (!file) {
                    imagenPreview.style.display = 'none';
                    if (selectedFileName) selectedFileName.textContent = 'Ningún archivo seleccionado';
                    return;
                }

                if (selectedFileName) selectedFileName.textContent = file.name;

                const reader = new FileReader();
                reader.onload = event => {
                    imagenPreviewImg.src = event.target.result;
                    imagenPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            });
        }

        if (imagenPreview) {
            imagenPreview.addEventListener('click', () => {
                const url = imagenPreviewImg.src;
                if (url) window.open(url, '_blank');
            });
        }
    });
</script>

</body>
</html>
