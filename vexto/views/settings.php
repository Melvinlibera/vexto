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
$user_data = $stmt->fetch();

$errors = [];
$updated = false;
$purchaseErrors = [];
$purchaseMessage = '';
$emailEditable = intval($user_data['email_change_count'] ?? 0) === 0;
$formData = [
    'nombre' => $user_data['nombre'],
    'apellido' => $user_data['apellido'],
    'telefono' => $user_data['telefono'],
    'bio' => $user_data['bio'],
    'email' => $user_data['email'],
    'cedula' => $user_data['cedula'],
    'rnc' => $user_data['rnc'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_plan'])) {
    $selectedPlan = $_POST['max_publicaciones'] ?? '';
    $customPlan = trim($_POST['custom_max_publicaciones'] ?? '');
    $buyValidation = isset($_POST['buy_validation']) && $_POST['buy_validation'] === '1';

    if ($selectedPlan === 'custom') {
        $newMax = intval($customPlan);
    } else {
        $newMax = intval($selectedPlan);
    }

    $currentMax = intval($user_data['max_propiedades']);
    $wantsPlanIncrease = $newMax > $currentMax;
    $wantsVerification = $buyValidation;

    if (!$wantsPlanIncrease && !$wantsVerification) {
        $purchaseErrors[] = 'Selecciona un plan de publicaciones mayor al actual o compra la validación.';
    }

    if ($wantsPlanIncrease) {
        if ($newMax <= $currentMax) {
            $purchaseErrors[] = 'El plan debe ser mayor que tu límite actual.';
        }
        if ($newMax < 5) {
            $purchaseErrors[] = 'El plan mínimo es de 5 publicaciones.';
        }
        if ($newMax > 100) {
            $purchaseErrors[] = 'El límite máximo permitido es 100 publicaciones.';
        }
    }

    if (empty($purchaseErrors)) {
        $updateFields = [];
        $params = [];

        if ($wantsVerification && !$user_data['verified']) {
            $updateFields[] = 'verified';
            $params[] = 1;
        }

        if ($wantsPlanIncrease) {
            $updateFields[] = 'max_propiedades';
            $params[] = $newMax;
        }

        if (!empty($updateFields)) {
            $sql = 'UPDATE users SET ' . implode(', ', array_map(fn($field) => "$field = ?", $updateFields)) . ' WHERE id = ?';
            $params[] = $user_id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $purchaseMessage = 'Tu plan se actualizó correctamente.';
            if ($wantsVerification && !$user_data['verified']) {
                $purchaseMessage .= ' Tu cuenta ahora tiene validación oficial.';
            }
            $user_data['max_propiedades'] = $wantsPlanIncrease ? $newMax : $user_data['max_propiedades'];
            $user_data['verified'] = $wantsVerification ? 1 : $user_data['verified'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $email = trim($_POST['email'] ?? $user_data['email']);
    $cedula = trim($_POST['cedula'] ?? '');
    $rnc = trim($_POST['rnc'] ?? '');

    $formData = [
        'nombre' => $nombre,
        'apellido' => $apellido,
        'telefono' => $telefono,
        'bio' => $bio,
        'email' => $email,
        'cedula' => $cedula,
        'rnc' => $rnc,
    ];

    if ($nombre === '') $errors[] = 'El nombre es obligatorio.';
    if ($apellido === '') $errors[] = 'El apellido es obligatorio.';
    if ($telefono === '') $errors[] = 'El teléfono es obligatorio.';
    if ($cedula === '') $errors[] = 'La cédula es obligatoria.';
    if ($password !== '') {
        if (strlen($password) < 8) $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        if ($password !== $confirmPassword) $errors[] = 'Las contraseñas no coinciden.';
    }

    $emailEditable = intval($user_data['email_change_count'] ?? 0) === 0;
    if ($emailEditable && $email !== $user_data['email']) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo electrónico no es válido.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) $errors[] = 'Este correo ya está registrado.';
        }
    }

    // Profile photo upload
    $fotoPerfil = null;
    $fotoPerfilTipo = null;
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === 0) {
        $fotoPerfil = file_get_contents($_FILES['foto_perfil']['tmp_name']);
        $fotoPerfilTipo = $_FILES['foto_perfil']['type'];
    }

    if (empty($errors)) {
        $updateFields = ['nombre', 'apellido', 'telefono', 'bio', 'cedula'];
        $params = [$nombre, $apellido, $telefono, $bio, $cedula];

        if ($password !== '') {
            $updateFields[] = 'password';
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        if ($emailEditable && $email !== $user_data['email']) {
            $updateFields[] = 'email';
            $params[] = $email;
            $updateFields[] = 'email_change_count';
            $params[] = 1;
        }
        if ($user_data['tipo_usuario'] === 'compania' && $rnc !== '') {
            $updateFields[] = 'rnc';
            $params[] = $rnc;
        }
        if ($fotoPerfil !== null) {
            $updateFields[] = 'foto_perfil';
            $params[] = $fotoPerfil;
            $updateFields[] = 'foto_perfil_tipo';
            $params[] = $fotoPerfilTipo;
        }

        $sql = 'UPDATE users SET ' . implode(', ', array_map(fn($field) => "$field = ?", $updateFields)) . ' WHERE id = ?';
        $params[] = $user_id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        header("Location: settings.php?updated=1");
        exit();
    }
}

$userInitials = strtoupper(substr($user_data['nombre'], 0, 1) . substr($user_data['apellido'], 0, 1));
$userAvatar = !empty($user_data['foto_perfil']) ? 'data:' . $user_data['foto_perfil_tipo'] . ';base64,' . base64_encode($user_data['foto_perfil']) : null;
?>

<div class="main-container" style="flex-direction: column; max-width: 1200px; margin: 40px auto;">
    <h1 style="font-size: 2.5rem; font-weight: 900; margin-bottom: 30px;">Configuración de Cuenta</h1>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <!-- Formulario de Perfil -->
        <div class="filter-card info-section" style="border-radius: 28px; padding: 40px;">
            <h2 style="font-weight: 900; margin-bottom: 25px; font-size: 1.5rem;">Información Personal</h2>
            
            <?php if (isset($_GET['updated'])): ?>
                <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); padding: 15px; border-radius: 12px; color: #059669; margin-bottom: 25px; font-weight: 600;">
                    ¡Perfil actualizado con éxito!
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); padding: 15px; border-radius: 12px; color: #b91c1c; margin-bottom: 25px; font-size: 0.9rem;">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="settings.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                
                <div style="text-align: center; margin-bottom: 35px;">
                    <div class="profile-avatar-wrapper">
                        <div class="seller-avatar-mini" style="width: 120px; height: 120px; border-radius: 50%; font-size: 2.5rem; border: 4px solid var(--accent-color); margin: 0 auto;">
                            <?php if ($userAvatar): ?>
                                <img src="<?php echo $userAvatar; ?>" alt="Avatar">
                            <?php else: ?>
                                <?php echo $userInitials; ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($user_data['verified'])): ?>
                            <div class="verified-badge" style="width: 34px; height: 34px; font-size: 0.95rem; border-width: 3px;">V</div>
                        <?php endif; ?>
                    </div>
                    <div style="margin-top: 15px;">
                        <label for="foto_perfil" style="cursor: pointer; color: var(--accent-color); font-weight: 700; font-size: 0.9rem;">Cambiar foto de perfil</label>
                        <input type="file" id="foto_perfil" name="foto_perfil" style="display: none;" accept="image/*">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="filter-group">
                        <label>Nombre</label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($formData['nombre']); ?>" required>
                    </div>
                    <div class="filter-group">
                        <label>Apellido</label>
                        <input type="text" name="apellido" value="<?php echo htmlspecialchars($formData['apellido']); ?>" required>
                    </div>
                </div>

                <div class="filter-group">
                    <label>Email (Solo se puede cambiar una vez)</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" <?php echo !$emailEditable ? 'disabled' : ''; ?>>
                </div>

                <div class="filter-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($formData['telefono']); ?>" required>
                </div>

                <div class="filter-group">
                    <label>Biografía</label>
                    <textarea name="bio" rows="4" style="border-radius: 12px; padding: 15px;"><?php echo htmlspecialchars($formData['bio']); ?></textarea>
                </div>

                <div style="margin-top: 30px; border-top: 1px solid var(--border-color); padding-top: 25px;">
                    <h3 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 20px;">Cambiar Contraseña (Opcional)</h3>
                    <div class="filter-group">
                        <label>Nueva Contraseña</label>
                        <input type="password" name="password" placeholder="Mínimo 8 caracteres">
                    </div>
                    <div class="filter-group">
                        <label>Confirmar Contraseña</label>
                        <input type="password" name="confirm_password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 18px; border-radius: 14px; font-weight: 800; margin-top: 20px;">Guardar Cambios</button>
            </form>
        </div>

        <!-- Planes y Verificación -->
        <div style="display: flex; flex-direction: column; gap: 30px;">
            <div class="filter-card info-section" style="border-radius: 28px; padding: 40px; background: var(--secondary-bg);">
                <h2 style="font-weight: 900; margin-bottom: 15px; font-size: 1.5rem;">Plan y Verificación</h2>
                <p style="color: var(--muted-text); margin-bottom: 25px;">Aumenta tu visibilidad y capacidad de publicación.</p>

                <?php if ($purchaseMessage): ?>
                    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); padding: 15px; border-radius: 12px; color: #059669; margin-bottom: 25px; font-weight: 600;">
                        <?php echo $purchaseMessage; ?>
                    </div>
                <?php endif; ?>

                <form action="settings.php" method="POST">
                    <input type="hidden" name="purchase_plan" value="1">
                    
                    <div class="filter-group">
                        <label>Límite de Publicaciones</label>
                        <select name="max_publicaciones" id="planSelect" style="border-radius: 12px; padding: 15px;">
                            <option value="<?php echo $user_data['max_propiedades']; ?>">Actual (<?php echo $user_data['max_propiedades']; ?>)</option>
                            <option value="10">10 Publicaciones</option>
                            <option value="20">20 Publicaciones</option>
                            <option value="50">50 Publicaciones</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </div>

                    <div id="customPlanWrapper" class="filter-group" style="display: none;">
                        <label>Cantidad personalizada</label>
                        <input type="number" name="custom_max_publicaciones" id="customMaxPublicaciones" placeholder="Ej: 30">
                    </div>

                    <div style="background: var(--card-bg); border-radius: 20px; padding: 25px; border: 1px solid var(--border-color); margin-bottom: 25px;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div class="verified-badge" style="position: static; width: 40px; height: 40px; font-size: 1.1rem;">V</div>
                                <div>
                                    <div style="font-weight: 800; font-size: 1.05rem;">Validación de Cuenta</div>
                                    <div style="font-size: 0.85rem; color: var(--muted-text);">Insignia de confianza en tu perfil.</div>
                                </div>
                            </div>
                            <?php if ($user_data['verified']): ?>
                                <span style="color: #059669; font-weight: 800; font-size: 0.9rem;"><i class="fas fa-check"></i> ACTIVO</span>
                            <?php else: ?>
                                <label class="switch" style="position: relative; display: inline-block; width: 50px; height: 26px;">
                                    <input type="checkbox" name="buy_validation" value="1" style="opacity: 0; width: 0; height: 0;">
                                    <span class="slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px;"></span>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="planCost" style="text-align: center; font-weight: 900; font-size: 1.4rem; margin-bottom: 25px; color: var(--accent-color);">
                        $0.00
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 18px; border-radius: 14px; font-weight: 800;">Actualizar Plan</button>
                </form>
            </div>

            <div class="filter-card info-section" style="border-radius: 28px; padding: 40px;">
                <h2 style="font-weight: 900; margin-bottom: 15px; font-size: 1.3rem;">Estado de la Cuenta</h2>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.95rem;">
                        <span style="color: var(--muted-text);">Tipo de Usuario:</span>
                        <span style="font-weight: 800; text-transform: uppercase;"><?php echo $user_data['tipo_usuario']; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.95rem;">
                        <span style="color: var(--muted-text);">Miembro desde:</span>
                        <span style="font-weight: 800;"><?php echo date('d M, Y', strtotime($user_data['created_at'])); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.95rem;">
                        <span style="color: var(--muted-text);">ID de Usuario:</span>
                        <span style="font-weight: 800;">#<?php echo $user_data['id']; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

<style>
/* Estilos para el switch de validación */
.switch input:checked + .slider { background-color: #0ea5e9; }
.switch .slider:before {
    position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px;
    background-color: white; transition: .4s; border-radius: 50%;
}
.switch input:checked + .slider:before { transform: translateX(24px); }

input[type="text"], input[type="email"], input[type="password"], input[type="number"], select, textarea {
    width: 100%; padding: 15px; border-radius: 12px; border: 2px solid var(--border-color);
    background: var(--secondary-bg); color: var(--text-color); font-family: inherit; transition: var(--transition);
}
input:focus, select:focus, textarea:focus { border-color: var(--accent-color); outline: none; background: var(--card-bg); }
.filter-group label { display: block; margin-bottom: 10px; font-weight: 800; font-size: 0.9rem; color: var(--text-color); }
</style>

<script>
document.getElementById('planSelect').addEventListener('change', function() {
    document.getElementById('customPlanWrapper').style.display = this.value === 'custom' ? 'block' : 'none';
    updateCost();
});

const currentMax = <?php echo $user_data['max_propiedades']; ?>;
function updateCost() {
    const select = document.getElementById('planSelect');
    const custom = document.getElementById('customMaxPublicaciones');
    const validation = document.querySelector('input[name="buy_validation"]');
    const costDisplay = document.getElementById('planCost');
    
    let newMax = select.value === 'custom' ? parseInt(custom.value || 0) : parseInt(select.value);
    let diff = Math.max(0, newMax - currentMax);
    let valCost = (validation && validation.checked) ? 10 : 0;
    let total = diff + valCost;
    
    costDisplay.textContent = '$' + total.toFixed(2);
}

document.getElementById('customMaxPublicaciones').addEventListener('input', updateCost);
const valCheck = document.querySelector('input[name="buy_validation"]');
if (valCheck) valCheck.addEventListener('change', updateCost);
</script>

</body>
</html>
