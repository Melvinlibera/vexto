<?php
/**
 * Feedback Page
 * Allows users to submit feedback about the platform
 */

session_start();
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../core/helpers.php';
require_once '../core/Feedback.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . 'public/index.php');
}

$userId = getCurrentUserId();
$feedbackClass = new Feedback($pdo);
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $tipo = $_POST['tipo'] ?? '';

    $result = $feedbackClass->create($userId, [
        'titulo' => $titulo,
        'descripcion' => $descripcion,
        'tipo' => $tipo
    ]);

    if ($result['success']) {
        $success = $result['message'];
        // Clear form
        $_POST = [];
    } else {
        $error = implode(', ', $result['errors']);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Feedback - VEXTO</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/modern.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/auth_modern.css">
</head>
<body>
    <?php require_once '../includes/header.php'; ?>

    <div class="container container-sm" style="margin-top: 2rem; margin-bottom: 2rem;">
        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header">
                <h1 style="margin: 0;">Enviar Feedback</h1>
                <p style="margin: 0.5rem 0 0 0; color: var(--text-secondary);">
                    Tu opinión es importante para mejorar VEXTO
                </p>
            </div>

            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <strong>¡Gracias!</strong> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <div class="auth-form-group">
                        <label for="titulo">Título del Feedback *</label>
                        <input 
                            type="text" 
                            id="titulo" 
                            name="titulo" 
                            required 
                            placeholder="Ej: Mejorar la búsqueda de propiedades"
                            value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>"
                        >
                    </div>

                    <div class="auth-form-group">
                        <label for="tipo">Tipo de Feedback *</label>
                        <select id="tipo" name="tipo" required>
                            <option value="">Selecciona un tipo</option>
                            <option value="sugerencia" <?php echo (($_POST['tipo'] ?? '') === 'sugerencia' ? 'selected' : ''); ?>>
                                💡 Sugerencia
                            </option>
                            <option value="problema" <?php echo (($_POST['tipo'] ?? '') === 'problema' ? 'selected' : ''); ?>>
                                🐛 Problema/Error
                            </option>
                            <option value="elogio" <?php echo (($_POST['tipo'] ?? '') === 'elogio' ? 'selected' : ''); ?>>
                                ⭐ Elogio
                            </option>
                        </select>
                    </div>

                    <div class="auth-form-group">
                        <label for="descripcion">Descripción Detallada *</label>
                        <textarea 
                            id="descripcion" 
                            name="descripcion" 
                            required 
                            placeholder="Cuéntanos más sobre tu feedback..."
                            style="min-height: 150px;"
                        ><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                    </div>

                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Enviar Feedback</button>
                        <a href="dashboard.php" class="btn btn-secondary" style="flex: 1; text-align: center;">Cancelar</a>
                    </div>
                </form>
            </div>

            <div class="card-footer">
                <p style="margin: 0; font-size: 0.9rem; color: var(--text-tertiary);">
                    Tu feedback será revisado por nuestro equipo y nos ayudará a mejorar la plataforma.
                </p>
            </div>
        </div>
    </div>

    <script src="<?php echo ASSETS_URL; ?>js/theme.js"></script>
</body>
</html>
