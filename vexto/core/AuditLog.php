<?php
/**
 * AuditLog Class
 * 
 * Handles audit logging for all user actions and system events.
 */

class AuditLog {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Log an action
     * 
     * @param int $userId
     * @param string $action
     * @param string $entityType
     * @param int $entityId
     * @param array $oldValues
     * @param array $newValues
     * @return bool
     */
    public function log($userId, $action, $entityType, $entityId = null, $oldValues = null, $newValues = null) {
        try {
            $ipAddress = $this->getClientIp();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = $this->pdo->prepare("
                INSERT INTO audit_log (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                $oldValues ? json_encode($oldValues) : null,
                $newValues ? json_encode($newValues) : null,
                $ipAddress,
                $userAgent
            ]);
            
            return true;
        } catch (PDOException $e) {
            logEvent('Audit log error: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Get all audit logs
     * 
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAll($limit = 100, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT al.*, u.nombre, u.apellido, u.email
                FROM audit_log al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logEvent('Get audit logs error: ' . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Get audit logs count
     * 
     * @return int
     */
    public function getCount() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM audit_log");
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            logEvent('Get audit logs count error: ' . $e->getMessage(), 'error');
            return 0;
        }
    }
    
    /**
     * Get audit logs by user
     * 
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByUser($userId, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM audit_log
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$userId, $limit, $offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logEvent('Get audit logs by user error: ' . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Get audit logs by entity
     * 
     * @param string $entityType
     * @param int $entityId
     * @return array
     */
    public function getByEntity($entityType, $entityId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT al.*, u.nombre, u.apellido, u.email
                FROM audit_log al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.entity_type = ? AND al.entity_id = ?
                ORDER BY al.created_at DESC
            ");
            
            $stmt->execute([$entityType, $entityId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logEvent('Get audit logs by entity error: ' . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Get audit logs by action
     * 
     * @param string $action
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByAction($action, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT al.*, u.nombre, u.apellido, u.email
                FROM audit_log al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.action = ?
                ORDER BY al.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$action, $limit, $offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logEvent('Get audit logs by action error: ' . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    private function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        }
        return trim($ip);
    }
}
?>
