<?php
/**
 * PasswordReset Class
 * 
 * Handles password reset token generation and validation.
 */

class PasswordReset {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Generate reset token for user
     * 
     * @param int $userId
     * @return array
     */
    public function generateToken($userId) {
        try {
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Delete old tokens
            $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE user_id = ? AND used = 0");
            $stmt->execute([$userId]);
            
            // Insert new token
            $stmt = $this->pdo->prepare("
                INSERT INTO password_resets (user_id, token, expires_at)
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([$userId, $token, $expiresAt]);
            
            return ['success' => true, 'token' => $token];
        } catch (PDOException $e) {
            logEvent('Generate reset token error: ' . $e->getMessage(), 'error');
            return ['success' => false, 'error' => 'Error al generar el token.'];
        }
    }
    
    /**
     * Validate reset token
     * 
     * @param string $token
     * @return array|null
     */
    public function validateToken($token) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT pr.*, u.email
                FROM password_resets pr
                JOIN users u ON pr.user_id = u.id
                WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
            ");
            
            $stmt->execute([$token]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            logEvent('Validate reset token error: ' . $e->getMessage(), 'error');
            return null;
        }
    }
    
    /**
     * Reset password using token
     * 
     * @param string $token
     * @param string $newPassword
     * @return array
     */
    public function resetPassword($token, $newPassword) {
        // Validate password
        if (!isValidPassword($newPassword)) {
            return ['success' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres.'];
        }
        
        try {
            // Validate token
            $resetData = $this->validateToken($token);
            if (!$resetData) {
                return ['success' => false, 'error' => 'Token inválido o expirado.'];
            }
            
            $userId = $resetData['user_id'];
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            // Mark token as used
            $stmt = $this->pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            return ['success' => true, 'message' => 'Contraseña restablecida exitosamente.'];
        } catch (PDOException $e) {
            logEvent('Reset password error: ' . $e->getMessage(), 'error');
            return ['success' => false, 'error' => 'Error al restablecer la contraseña.'];
        }
    }
    
    /**
     * Get user by email
     * 
     * @param string $email
     * @return array|null
     */
    public function getUserByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, email FROM users WHERE email = ?");
            $stmt->execute([sanitize($email)]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            logEvent('Get user by email error: ' . $e->getMessage(), 'error');
            return null;
        }
    }
}
?>
