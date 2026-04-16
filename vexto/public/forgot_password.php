<?php
/**
 * Forgot Password Page
 * Allows users to request password reset via email
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
$step = 'email'; // email or reset

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'request_reset') {
            $email = $_POST['email'] ?? '';
            
            if (empty($email)) {
                $error = 'Por favor ingresa tu correo electrónico.';
            } elseif (!isValidEmail($email)) {
                $error = 'El correo electrónico no es válido.';
            } else {
                $passwordReset = new PasswordReset($pdo);
                $user = $passwordReset->getUserByEmail($email);
                
                if ($user) {
                    $tokenResult = $passwordReset->generateToken($user['id']);
                    if ($tokenResult['success']) {
                        $token = $tokenResult['token'];
                        $resetLink = BASE_URL . 'public/reset_password.php?token=' . $token;
                        
                        // TODO: Send email with reset link
                        // For now, display the link (in production, send via email)
                        $success = 'Se ha enviado un enlace de recuperación a tu correo electrónico.';
                        $step = 'email_sent';
                        
                        // Log the action
                        logEvent('Password reset requested for email: ' . $email, 'info');
                    } else {
                        $error = 'Error al generar el token de recuperación.';
                    }
                } else {
                    // Don't reveal if email exists or not (security)
                    $success = 'Si la cuenta existe, recibirás un correo con instrucciones.';
                    $step = 'email_sent';
                }
            }
        }
    }
}

// Get query parameters
$token = $_GET['token'] ?? '';
if (!empty($token)) {
    $step = 'reset';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - VEXTO</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/modern.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/auth_modern.css">
    <style>
        .recovery-step {
            display: none;
        }
        .recovery-step.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-wrapper">
            <div class="auth-header">
                <div class="auth-logo">VEXTO</div>
                <div class="auth-subtitle">Recuperar Contraseña</div>
            </div>

            <div class="auth-card">
                <!-- Step 1: Request Reset -->
                <div class="recovery-step <?php echo ($step === 'email' ? 'active' : ''); ?>">
                    <div class="recovery-info">
                        <strong>Recupera tu acceso:</strong> Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form">
                        <input type="hidden" name="action" value="request_reset">
                        
                        <div class="auth-form-group">
                            <label for="email">Correo Electrónico</label>
                            <input type="email" id="email" name="email" required placeholder="tu@correo.com">
                        </div>

                        <button type="submit" class="auth-form-submit">Enviar Enlace de Recuperación</button>
                    </form>

                    <div class="auth-footer">
                        ¿Recordaste tu contraseña? <a href="index.php">Inicia sesión aquí</a>
                    </div>
                </div>

                <!-- Step 2: Email Sent Confirmation -->
                <div class="recovery-step <?php echo ($step === 'email_sent' ? 'active' : ''); ?>">
                    <div class="alert alert-success">
                        <strong>¡Éxito!</strong> Si la cuenta existe, recibirás un correo electrónico con instrucciones para restablecer tu contraseña.
                    </div>

                    <div style="text-align: center; margin: 2rem 0;">
                        <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                            Por favor revisa tu bandeja de entrada (y la carpeta de spam) en los próximos minutos.
                        </p>
                        <p style="color: var(--text-tertiary); font-size: 0.9rem;">
                            El enlace de recuperación expirará en 1 hora.
                        </p>
                    </div>

                    <div class="auth-footer">
                        <a href="index.php">Volver al inicio de sesión</a>
                    </div>
                </div>

                <!-- Step 3: Reset Password (if token provided) -->
                <div class="recovery-step <?php echo ($step === 'reset' ? 'active' : ''); ?>">
                    <?php
                    if (!empty($token)) {
                        $passwordReset = new PasswordReset($pdo);
                        $resetData = $passwordReset->validateToken($token);
                        
                        if ($resetData):
                    ?>
                        <div class="recovery-info">
                            <strong>Restablecer contraseña:</strong> Ingresa tu nueva contraseña. Debe tener al menos 8 caracteres.
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="reset_password.php" class="auth-form">
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
                    <?php
                        else:
                    ?>
                        <div class="alert alert-error">
                            <strong>Enlace inválido o expirado</strong>. Por favor, solicita un nuevo enlace de recuperación.
                        </div>

                        <div class="auth-footer">
                            <a href="forgot_password.php">Solicitar nuevo enlace</a>
                        </div>
                    <?php
                        endif;
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo ASSETS_URL; ?>js/theme.js"></script>
</body>
</html>
