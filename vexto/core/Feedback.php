<?php
/**
 * Feedback Class
 * 
 * Handles user feedback operations: creation, retrieval, and status updates.
 */

class Feedback {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create new feedback
     * 
     * @param int $userId
     * @param array $data
     * @return array
     */
    public function create($userId, $data) {
        $errors = [];
        
        // Validate required fields
        if (empty($data['titulo'])) {
            $errors[] = 'El título del feedback es obligatorio.';
        }
        
        if (empty($data['descripcion'])) {
            $errors[] = 'La descripción del feedback es obligatoria.';
        }
        
        if (empty($data['tipo']) || !in_array($data['tipo'], ['sugerencia', 'problema', 'elogio'])) {
            $errors[] = 'El tipo de feedback no es válido.';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO feedback (user_id, titulo, descripcion, tipo, estado)
                VALUES (?, ?, ?, ?, 'nuevo')
            ");
            
            $stmt->execute([
                $userId,
                sanitize($data['titulo']),
                sanitize($data['descripcion']),
                $data['tipo']
            ]);
            
            return ['success' => true, 'message' => 'Feedback enviado exitosamente.'];
        } catch (PDOException $e) {
            logEvent('Feedback creation error: ' . $e->getMessage(), 'error');
            return ['success' => false, 'errors' => ['Error al guardar el feedback.']];
        }
    }
    
    /**
     * Get all feedback (for admin)
     * 
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAll($limit = 50, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT f.*, u.nombre, u.apellido, u.email
                FROM feedback f
                LEFT JOIN users u ON f.user_id = u.id
                ORDER BY f.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logEvent('Get feedback error: ' . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Get feedback count
     * 
     * @return int
     */
    public function getCount() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM feedback");
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            logEvent('Get feedback count error: ' . $e->getMessage(), 'error');
            return 0;
        }
    }
    
    /**
     * Get feedback by ID
     * 
     * @param int $feedbackId
     * @return array|null
     */
    public function getById($feedbackId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT f.*, u.nombre, u.apellido, u.email
                FROM feedback f
                LEFT JOIN users u ON f.user_id = u.id
                WHERE f.id = ?
            ");
            
            $stmt->execute([$feedbackId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            logEvent('Get feedback by ID error: ' . $e->getMessage(), 'error');
            return null;
        }
    }
    
    /**
     * Update feedback status
     * 
     * @param int $feedbackId
     * @param string $status
     * @return array
     */
    public function updateStatus($feedbackId, $status) {
        if (!in_array($status, ['nuevo', 'en_revision', 'resuelto', 'rechazado'])) {
            return ['success' => false, 'errors' => ['Estado no válido.']];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE feedback
                SET estado = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$status, $feedbackId]);
            return ['success' => true, 'message' => 'Estado del feedback actualizado.'];
        } catch (PDOException $e) {
            logEvent('Update feedback status error: ' . $e->getMessage(), 'error');
            return ['success' => false, 'errors' => ['Error al actualizar el feedback.']];
        }
    }
    
    /**
     * Get feedback by status
     * 
     * @param string $status
     * @return array
     */
    public function getByStatus($status) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT f.*, u.nombre, u.apellido, u.email
                FROM feedback f
                LEFT JOIN users u ON f.user_id = u.id
                WHERE f.estado = ?
                ORDER BY f.created_at DESC
            ");
            
            $stmt->execute([$status]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logEvent('Get feedback by status error: ' . $e->getMessage(), 'error');
            return [];
        }
    }
}
?>
