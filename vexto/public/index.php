<?php
session_start();

// Load configuration and dependencies
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/core/helpers.php';

// Redirect to dashboard if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . 'views/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> | Inmobiliaria Profesional</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/auth.css">
</head>
<body>

    <div id="splash">
        <div class="logo-anim"><?php echo APP_NAME; ?></div>
    </div>

    <div class="auth-card" id="auth-container">
        <!-- Login -->
        <div id="login-section">
            <div class="auth-header">
                <h1><?php echo APP_NAME; ?></h1>
                <p>Ingresa a tu cuenta inmobiliaria</p>
            </div>
            <form action="auth.php" method="POST" id="login-form">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary">Iniciar Sesión</button>
            </form>
            <div class="toggle-auth">
                ¿No tienes cuenta? <span onclick="switchForm('register')">Regístrate aquí</span>
            </div>
        </div>

        <!-- Register -->
        <div id="register-section" class="hidden">
            <div class="auth-header">
                <h1>Únete a <?php echo APP_NAME; ?></h1>
                <p>Crea tu perfil de usuario o compañía</p>
            </div>
            <div class="form-container">
                <form action="auth.php" method="POST" enctype="multipart/form-data" id="register-form">
                    <input type="hidden" name="action" value="register">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre" required>
                        </div>
                        <div class="form-group">
                            <label>Apellido</label>
                            <input type="text" name="apellido" required>
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Tipo de Cuenta</label>
                            <select name="tipo_usuario" id="tipoUsuario" required onchange="toggleAccountType()">
                                <option value="usuario">Usuario Común</option>
                                <option value="compania">Compañía / Empresa</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Foto de Perfil</label>
                            <input type="file" id="fotoPerfil" name="foto_perfil" accept="image/*">
                            <small id="fotoPerfilStatus">Ningún archivo seleccionado</small>
                            <small id="fotoPerfilHint">Foto opcional para usuarios comunes.</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" class="btn-primary">Crear Cuenta</button>
                </form>
            </div>
            <div class="toggle-auth">
                ¿Ya tienes cuenta? <span onclick="switchForm('login')">Inicia sesión</span>
            </div>
        </div>
    </div>

    <script src="<?php echo ASSETS_URL; ?>js/auth.js"></script>
</body>
</html>
