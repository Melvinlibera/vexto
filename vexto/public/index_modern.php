<?php
/**
 * Modern Authentication Page
 * Improved login and registration interface
 */

session_start();
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../core/helpers.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . 'views/dashboard.php');
}

// Get query parameters
$registered = $_GET['registered'] ?? false;
$error = $_GET['error'] ?? '';
$register = $_GET['register'] ?? false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VEXTO - Plataforma de Bienes Raíces</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/modern.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/auth_modern.css">
    <style>
        .auth-container {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }

        [data-theme="dark"] .auth-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }

        .logo-text {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--accent-color);
            letter-spacing: -0.5px;
        }

        .logo-tagline {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }

        .form-tabs-wrapper {
            margin-bottom: 2rem;
        }

        .form-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .form-tab {
            flex: 1;
            padding: 1rem;
            background: none;
            border: none;
            color: var(--text-secondary);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            font-size: 1rem;
        }

        .form-tab.active {
            color: var(--accent-color);
            border-bottom-color: var(--accent-color);
        }

        .form-tab:hover {
            color: var(--accent-color);
        }

        .form-content {
            display: none;
        }

        .form-content.active {
            display: block;
        }

        .theme-toggle {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.5rem;
            transition: var(--transition);
            z-index: 100;
        }

        .theme-toggle:hover {
            background-color: var(--accent-light);
        }
    </style>
</head>
<body>
    <button id="theme-toggle" class="theme-toggle" aria-label="Cambiar tema" aria-pressed="false">🌙</button>

    <div class="auth-container">
        <div class="auth-wrapper">
            <div class="logo-section">
                <div class="logo-icon">🏠</div>
                <div class="logo-text">VEXTO</div>
                <div class="logo-tagline">Tu plataforma de bienes raíces</div>
            </div>

            <div class="auth-card">
                <!-- Success Messages -->
                <?php if ($registered): ?>
                    <div class="alert alert-success">
                        <strong>¡Bienvenido!</strong> Tu cuenta ha sido creada exitosamente. Por favor inicia sesión.
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Form Tabs -->
                <div class="form-tabs-wrapper">
                    <div class="form-tabs">
                        <button class="form-tab active" data-tab="login">Iniciar Sesión</button>
                        <button class="form-tab" data-tab="register">Registrarse</button>
                    </div>

                    <!-- Login Form -->
                    <div class="form-content active" id="login-form">
                        <form method="POST" action="auth.php" class="auth-form">
                            <input type="hidden" name="action" value="login">
                            
                            <div class="auth-form-group">
                                <label for="login-email">Correo Electrónico</label>
                                <input type="email" id="login-email" name="email" required placeholder="tu@correo.com">
                            </div>

                            <div class="auth-form-group">
                                <label for="login-password">Contraseña</label>
                                <input type="password" id="login-password" name="password" required placeholder="Tu contraseña">
                            </div>

                            <div class="forgot-password-link" style="margin-bottom: 1.2rem; text-align: right;">
                                <a href="forgot_password.php"><i class="fas fa-unlock-alt"></i> ¿Olvidaste tu contraseña?</a>
                            </div>

                            <button type="submit" class="auth-form-submit">Iniciar Sesión</button>
                        </form>

                        <div class="auth-footer">
                            ¿No tienes cuenta? <a href="#" class="switch-tab" data-tab="register">Regístrate aquí</a>
                        </div>
                    </div>

                    <!-- Register Form -->
                    <div class="form-content" id="register-form">
                        <form method="POST" action="auth.php" enctype="multipart/form-data" class="auth-form">
                            <input type="hidden" name="action" value="register">
                            
                            <!-- User Type Selection -->
                            <div class="auth-form-group">
                                <label>Tipo de Cuenta</label>
                                <div class="form-toggle">
                                    <input type="radio" id="tipo-usuario" name="tipo_usuario" value="usuario" checked>
                                    <label for="tipo-usuario">Usuario</label>
                                </div>
                                <div class="form-toggle">
                                    <input type="radio" id="tipo-compania" name="tipo_usuario" value="compania">
                                    <label for="tipo-compania">Empresa</label>
                                </div>
                            </div>

                            <!-- Name Fields -->
                            <div class="form-row">
                                <div class="auth-form-group">
                                    <label for="reg-nombre">Nombre</label>
                                    <input type="text" id="reg-nombre" name="nombre" required placeholder="Tu nombre">
                                </div>
                                <div class="auth-form-group">
                                    <label for="reg-apellido">Apellido</label>
                                    <input type="text" id="reg-apellido" name="apellido" required placeholder="Tu apellido">
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="auth-form-group">
                                <label for="reg-email">Correo Electrónico</label>
                                <input type="email" id="reg-email" name="email" required placeholder="tu@correo.com">
                            </div>

                            <!-- Password -->
                            <div class="auth-form-group">
                                <label for="reg-password">Contraseña</label>
                                <input type="password" id="reg-password" name="password" required placeholder="Mínimo 8 caracteres">
                                <small style="color: var(--text-tertiary); display: block; margin-top: 0.25rem;">
                                    Usa mayúsculas, minúsculas y números para mayor seguridad
                                </small>
                            </div>

                            <!-- Cedula -->
                            <div class="auth-form-group">
                                <label for="reg-cedula">Cédula</label>
                                <input type="text" id="reg-cedula" name="cedula" required placeholder="Ej: 001-0000000-0">
                            </div>

                            <!-- RNC (for companies) -->
                            <div class="auth-form-group" id="rnc-field" style="display: none;">
                                <label for="reg-rnc">RNC</label>
                                <input type="text" id="reg-rnc" name="rnc" placeholder="Ej: 123456789">
                            </div>

                            <!-- Profile Photo -->
                            <div class="auth-form-group">
                                <label for="reg-foto">Foto de Perfil</label>
                                <label for="reg-foto" class="file-input-label">
                                    <span>📷 Selecciona una imagen</span>
                                </label>
                                <input type="file" id="reg-foto" name="foto_perfil" accept="image/*" required>
                                <div class="file-preview" id="preview"></div>
                            </div>

                            <!-- Terms -->
                            <div class="form-toggle">
                                <input type="checkbox" id="terms" name="terms" required>
                                <label for="terms">Acepto los términos y condiciones</label>
                            </div>

                            <button type="submit" class="auth-form-submit">Crear Cuenta</button>
                        </form>

                        <div class="auth-footer">
                            ¿Ya tienes cuenta? <a href="#" class="switch-tab" data-tab="login">Inicia sesión aquí</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo ASSETS_URL; ?>js/theme.js"></script>
    <script>
        // Form tab switching
        document.querySelectorAll('.form-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const tabName = tab.getAttribute('data-tab');
                switchTab(tabName);
            });
        });

        document.querySelectorAll('.switch-tab').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const tabName = link.getAttribute('data-tab');
                switchTab(tabName);
            });
        });

        function switchTab(tabName) {
            // Hide all forms
            document.querySelectorAll('.form-content').forEach(form => {
                form.classList.remove('active');
            });
            
            // Deactivate all tabs
            document.querySelectorAll('.form-tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected form
            document.getElementById(tabName + '-form').classList.add('active');
            
            // Activate selected tab
            document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
        }

        // File input preview
        document.getElementById('reg-foto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('preview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    preview.innerHTML = `<img src="${event.target.result}" alt="Preview">`;
                };
                reader.readAsDataURL(file);
            }
        });

        // Toggle RNC field based on user type
        document.querySelectorAll('input[name="tipo_usuario"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const rncField = document.getElementById('rnc-field');
                if (this.value === 'compania') {
                    rncField.style.display = 'block';
                    document.getElementById('reg-rnc').required = true;
                } else {
                    rncField.style.display = 'none';
                    document.getElementById('reg-rnc').required = false;
                }
            });
        });

        // Set initial register tab if requested
        <?php if ($register): ?>
            switchTab('register');
        <?php endif; ?>
    </script>
</body>
</html>
