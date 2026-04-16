<?php
/**
 * Helper Functions
 * 
 * Common utility functions for validation, sanitization, and security.
 */

/**
 * Sanitize user input
 * 
 * @param string $input
 * @return string
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 * 
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * 
 * @param string $password
 * @return bool
 */
function isValidPassword($password) {
    return strlen($password) >= 8;
}

/**
 * Check if file upload is valid
 * 
 * @param array $file
 * @param string $type
 * @return array
 */
function validateFileUpload($file, $type = 'image') {
    $errors = [];
    
    if (!isset($file) || $file['error'] !== 0) {
        $errors[] = 'Error al cargar el archivo.';
        return $errors;
    }
    
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $errors[] = 'El archivo es demasiado grande.';
    }
    
    if ($type === 'image') {
        if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
            $errors[] = 'Tipo de imagen no permitida.';
        }
    }
    
    return $errors;
}

/**
 * Generate unique filename for uploads
 * 
 * @param string $originalName
 * @return string
 */
function generateUniqueFilename($originalName) {
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    return time() . '_' . uniqid() . '.' . $ext;
}

/**
 * Redirect to URL
 * 
 * @param string $url
 * @return void
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Check if user is logged in
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user ID
 * 
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user type
 * 
 * @return string|null
 */
function getCurrentUserType() {
    return $_SESSION['tipo_usuario'] ?? null;
}

/**
 * Format price for display
 * 
 * @param float $price
 * @param string $currency
 * @return string
 */
function formatPrice($price, $currency = 'RD$') {
    return $currency . ' ' . number_format($price, 2, '.', ',');
}

/**
 * Format date for display
 * 
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Get a default SVG placeholder image for properties.
 *
 * @return string
 */
function getDefaultPropertyImageSrc() {
    static $src = null;
    if ($src === null) {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500"><rect width="100%" height="100%" fill="#f5f5f5"/><text x="50%" y="50%" fill="#999" font-family="Inter, sans-serif" font-size="32" text-anchor="middle" dominant-baseline="middle">Sin imagen</text></svg>';
        $src = 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    return $src;
}

/**
 * Resolve a property image URL from the stored path.
 *
 * @param string $imagePath
 * @return string
 */
function getPropertyImageUrl($imagePath) {
    if (empty($imagePath)) {
        return getDefaultPropertyImageSrc();
    }

    $filename = basename($imagePath);
    if (!empty($filename) && file_exists(PUBLICATIONS_PATH . $filename)) {
        return PUBLICATIONS_URL . $filename;
    }

    return getDefaultPropertyImageSrc();
}

/**
 * Get time ago string
 * 
 * @param string $date
 * @return string
 */
function timeAgo($date) {
    $time = strtotime($date);
    $diff = time() - $time;
    
    if ($diff < 60) return 'hace unos segundos';
    if ($diff < 3600) return 'hace ' . floor($diff / 60) . ' minutos';
    if ($diff < 86400) return 'hace ' . floor($diff / 3600) . ' horas';
    if ($diff < 604800) return 'hace ' . floor($diff / 86400) . ' días';
    
    return 'hace ' . floor($diff / 604800) . ' semanas';
}

/**
 * Log error or event
 * 
 * @param string $message
 * @param string $type
 * @return void
 */
function logEvent($message, $type = 'info') {
    $logFile = BASE_PATH . '/logs/' . date('Y-m-d') . '.log';
    
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    $logMessage = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($type) . '] ' . $message . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Send JSON response
 * 
 * @param bool $success
 * @param string $message
 * @param array $data
 * @return void
 */
function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ]);
    exit();
}
?>
