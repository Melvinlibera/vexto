<?php
/**
 * Admin Setup Script
 * Creates the admin user with the specified credentials
 * 
 * Username: admin
 * Password: 569246
 */

require_once 'db.php';
require_once '../core/helpers.php';

try {
    // Hash the password
    $password = '569246';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@vexto.com']);
    $adminExists = $stmt->fetch();

    if (!$adminExists) {
        // Insert admin user
        $stmt = $pdo->prepare("
            INSERT INTO users (nombre, apellido, genero, cedula, rnc, telefono, email, password, tipo_usuario, max_propiedades, bio, rating, total_reviews, verified)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            'Admin',
            'Vexto',
            'Masculino',
            '00000000000',
            '000000000',
            '+1-809-000-0000',
            'admin@vexto.com',
            $hashedPassword,
            'admin',
            100,
            'Administrador del sistema Vexto',
            5.0,
            0,
            1
        ]);

        echo "✓ Usuario administrador creado exitosamente.\n";
        echo "Email: admin@vexto.com\n";
        echo "Contraseña: 569246\n";
    } else {
        echo "✓ El usuario administrador ya existe.\n";
        
        // Update password and ensure admin type if needed
        $stmt = $pdo->prepare("UPDATE users SET password = ?, tipo_usuario = 'admin', max_propiedades = 100, verified = 1 WHERE email = ?");
        $stmt->execute([$hashedPassword, 'admin@vexto.com']);
        echo "✓ Contraseña del administrador actualizada y tipo de usuario asegurado.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
