<?php
/**
 * Database Connection Configuration
 * 
 * This file handles all database connections using PDO
 * with proper error handling and security best practices.
 */

// Load environment variables or use defaults
$host = getenv('DB_HOST') ?: 'localhost';
$db = getenv('DB_NAME') ?: 'vexto_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION wait_timeout=300",
    PDO::ATTR_TIMEOUT => 30,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET NAMES utf8mb4");
    
    // Auto-migrate: Add missing columns if needed
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'properties'");
        if ($stmt && $stmt->fetch()) {
            $stmt = $pdo->query("SHOW COLUMNS FROM properties LIKE 'imagen_url'");
            if (!$stmt || !$stmt->fetch()) {
                $pdo->exec("ALTER TABLE properties ADD COLUMN imagen_url VARCHAR(255) DEFAULT NULL");
            }
        }

        $stmt = $pdo->query("SHOW TABLES LIKE 'messages'");
        if (!$stmt || !$stmt->fetch()) {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    sender_id INT NOT NULL,
                    receiver_id INT NOT NULL,
                    property_id INT NOT NULL,
                    message TEXT NOT NULL,
                    is_read BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email_change_count'");
        if (!$stmt || !$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN email_change_count TINYINT(1) NOT NULL DEFAULT 0 AFTER email");
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'verified'");
        if (!$stmt || !$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN verified TINYINT(1) NOT NULL DEFAULT 0 AFTER email_change_count");
        }
        // Crear tabla de feedback si no existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'feedback'");
        if (!$stmt || !$stmt->fetch()) {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS feedback (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    titulo VARCHAR(150) NOT NULL,
                    descripcion TEXT NOT NULL,
                    tipo ENUM('sugerencia', 'problema', 'elogio') DEFAULT 'sugerencia',
                    estado ENUM('nuevo', 'en_revision', 'resuelto', 'rechazado') DEFAULT 'nuevo',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        }

        // Crear tabla de auditoría si no existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'audit_log'");
        if (!$stmt || !$stmt->fetch()) {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS audit_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    action VARCHAR(100) NOT NULL,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id INT,
                    old_values JSON,
                    new_values JSON,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        }

        // Crear tabla de recuperación de contraseña si no existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'password_resets'");
        if (!$stmt || !$stmt->fetch()) {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS password_resets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(255) UNIQUE NOT NULL,
                    expires_at TIMESTAMP NOT NULL,
                    used TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        }

        // Agregar columna 'verified' a tabla users si no existe
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'verified'");
        if (!$stmt || !$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN verified TINYINT(1) NOT NULL DEFAULT 0 AFTER email_change_count");
        }

        // Actualizar tipo_usuario para permitir 'admin'
        try {
            $pdo->exec("ALTER TABLE users MODIFY tipo_usuario ENUM('usuario', 'compania', 'admin') DEFAULT 'usuario'");
        } catch (Exception $e) {
            // Ignorar si ya existe
        }
    } catch (Exception $e) {
        // Silently ignore if table doesn't exist yet
    }
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>
