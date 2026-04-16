<?php
/**
 * Reset Password Handler
 * Processes password reset requests with token validation
 */

session_start();
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../core/helpers.php';
require_once '../core/PasswordReset.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . 'views/dashboard.php');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (empty($token)) {
        $error = 'Token no válido.';
    } elseif (empty($password)) {
        $error = 'Por favor ingresa una nueva contraseña.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $passwordReset = new PasswordReset($pdo);
        $result = $passwordReset->resetPassword($token, $password);
        
        if ($result['success']) {
            $success = $result['message'];
            // Redirect to login after 2 seconds
            header('Refresh: 2; url=' . BASE_URL . 'public/index.php');
        } else {
            $error = $result['error'] ?? 'Error al restablecer la contraseña.';
        }
    }
}

// If GET request with token, validate it
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$tokenValid = false;

if (!empty($token)) {
    $passwordReset = new PasswordReset($pdo);
    $resetData = $passwordReset->validateToken($token);
    $tokenValid = $resetData !== null;
}

// Redirect to forgot password if no token
if (empty($token)) {
    redirect(BASE_URL . 'public/forgot_password.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - VEXTO</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/modern.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/auth_modern.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-wrapper">
            <div class="auth-header">
                <div class="auth-logo">VEXTO</div>
                <div class="auth-subtitle">Restablecer Contraseña</div>
            </div>

            <div class="auth-card">
                <?php if ($tokenValid): ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <strong>¡Éxito!</strong> <?php echo htmlspecialchars($success); ?>
                        </div>
                        <p style="text-align: center; color: var(--text-secondary); margin: 1rem 0;">
                            Serás redirigido al inicio de sesión en unos momentos...
                        </p>
                    <?php else: ?>
                        <div class="recovery-info">
                            <strong>Restablecer contraseña:</strong> Ingresa tu nueva contraseña. Debe tener al menos 8 caracteres.
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" class="auth-form">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            
                            <div class="auth-form-group">
                                <label for="password">Nueva Contraseña</label>
                                <input type="password" id="password" name="password" required placeholder="Mínimo 8 caracteres">
                                <small style="color: var(--text-tertiary); display: block; margin-top: 0.25rem;">
                                    Usa mayúsculas, minúsculas y números para mayor seguridad
                                </small>
                            </div>

                            <div class="auth-form-group">
                                <label for="password_confirm">Confirmar Contraseña</label>
                                <input type="password" id="password_confirm" name="password_confirm" required placeholder="Repite tu contraseña">
                            </div>

                            <button type="submit" class="auth-form-submit">Restablecer Contraseña</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-error">
                        <strong>Enlace inválido o expirado</strong>. El enlace de recuperación ha expirado o no es válido. Por favor, solicita un nuevo enlace.
                    </div>

                    <div class="auth-footer">
                        <a href="forgot_password.php">Solicitar nuevo enlace</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="<?php echo ASSETS_URL; ?>js/theme.js"></script>
</body>
</html>
