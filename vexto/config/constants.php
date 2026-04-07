<?php
/**
 * Global Constants and Configuration
 */

// Application Settings
define('APP_NAME', 'VEXTO');
define('APP_VERSION', '1.4.0');
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', getenv('APP_DEBUG') ?: false);

// Paths
define('BASE_PATH', dirname(dirname(__FILE__)));
define('UPLOADS_PATH', BASE_PATH . '/uploads/');
define('PUBLICATIONS_PATH', BASE_PATH . '/publicaciones/');
define('ASSETS_PATH', BASE_PATH . '/assets/');
define('VIEWS_PATH', BASE_PATH . '/views/');

// URLs (adjust based on your server configuration)
define('BASE_URL', 'http://localhost/vexto/');
define('UPLOADS_URL', BASE_URL . 'uploads/');
define('PUBLICATIONS_URL', BASE_URL . 'publicaciones/');
define('ASSETS_URL', BASE_URL . 'assets/');

// File Upload Settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// User Settings
define('DEFAULT_MAX_PROPERTIES_USER', 3);
define('DEFAULT_MAX_PROPERTIES_COMPANY', 20);

// Property Types
define('PROPERTY_TYPES', ['casa', 'apartamento', 'local', 'terreno', 'otro']);
define('OPERATION_TYPES', ['venta', 'alquiler']);
define('PROPERTY_STATUS', ['activa', 'inactiva', 'vendida']);

// User Types
define('USER_TYPES', ['usuario', 'compania']);

// Pagination
define('ITEMS_PER_PAGE', 12);

// Session Timeout (in seconds)
define('SESSION_TIMEOUT', 1800); // 30 minutes

// Error Messages
define('ERROR_MESSAGES', [
    'invalid_email' => 'El correo electrónico no es válido.',
    'email_exists' => 'Este correo electrónico ya está registrado.',
    'password_weak' => 'La contraseña debe tener al menos 8 caracteres.',
    'invalid_credentials' => 'Credenciales incorrectas.',
    'file_too_large' => 'El archivo es demasiado grande.',
    'invalid_file_type' => 'Tipo de archivo no permitido.',
    'upload_error' => 'Error al cargar el archivo.',
    'database_error' => 'Error en la base de datos.',
    'unauthorized' => 'No autorizado para realizar esta acción.',
    'not_found' => 'Recurso no encontrado.',
]);

// Success Messages
define('SUCCESS_MESSAGES', [
    'registered' => 'Cuenta creada exitosamente.',
    'logged_in' => 'Sesión iniciada correctamente.',
    'profile_updated' => 'Perfil actualizado.',
    'property_created' => 'Propiedad publicada exitosamente.',
    'property_updated' => 'Propiedad actualizada.',
    'property_deleted' => 'Propiedad eliminada.',
    'favorite_added' => 'Agregado a favoritos.',
    'favorite_removed' => 'Eliminado de favoritos.',
]);
?>
