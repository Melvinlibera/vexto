<?php
/**
 * Property Class
 * 
 * Handles property-related operations: create, read, update, delete.
 */

class Property {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new property
     * 
     * @param int $userId
     * @param array $data
     * @return array
     */
    public function create($userId, $data) {
        try {
            // Validate required fields
            $required = ['titulo', 'descripcion', 'precio', 'tipo_operacion', 'tipo_propiedad', 'ubicacion'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'errors' => ["El campo $field es obligatorio."]];
                }
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO properties (user_id, titulo, descripcion, precio, tipo_operacion, tipo_propiedad, ubicacion, latitud, longitud, habitaciones, banos, area_m2, imagen_url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                sanitize($data['titulo']),
                sanitize($data['descripcion']),
                floatval($data['precio']),
                $data['tipo_operacion'],
                $data['tipo_propiedad'],
                sanitize($data['ubicacion']),
                floatval($data['latitud'] ?? 0),
                floatval($data['longitud'] ?? 0),
                intval($data['habitaciones'] ?? 0),
                intval($data['banos'] ?? 0),
                floatval($data['area_m2'] ?? 0),
                $data['imagen_url'] ?? null
            ]);
            
            $propertyId = $this->pdo->lastInsertId();
            
            // Update user's property count
            $this->pdo->prepare("UPDATE users SET propiedades_publicadas = propiedades_publicadas + 1 WHERE id = ?")->execute([$userId]);
            
            return ['success' => true, 'message' => SUCCESS_MESSAGES['property_created'], 'property_id' => $propertyId];
        } catch (PDOException $e) {
            logEvent('Property creation error: ' . $e->getMessage(), 'error');
            return ['success' => false, 'errors' => [ERROR_MESSAGES['database_error']]];
        }
    }
    
    /**
     * Get property by ID
     * 
     * @param int $propertyId
     * @return array|null
     */
    public function getById($propertyId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*, u.nombre, u.apellido, u.tipo_usuario, u.rating
                FROM properties p
                JOIN users u ON p.user_id = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$propertyId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            logEvent('Get property error: ' . $e->getMessage(), 'error');
            return null;
        }
    }
    
    /**
     * Get all properties with pagination
     * 
     * @param int $page
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getAll($page = 1, $limit = ITEMS_PER_PAGE, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $query = "SELECT p.*, u.nombre, u.apellido FROM properties p JOIN users u ON p.user_id = u.id WHERE p.estado = 'activa'";
            $params = [];
            
            if (!empty($filters['tipo_operacion'])) {
                $query .= " AND p.tipo_operacion = ?";
                $params[] = $filters['tipo_operacion'];
            }
            
            if (!empty($filters['tipo_propiedad'])) {
                $query .= " AND p.tipo_propiedad = ?";
                $params[] = $filters['tipo_propiedad'];
            }
            
            if (!empty($filters['search'])) {
                $query .= " AND (p.titulo LIKE ? OR p.descripcion LIKE ? OR p.ubicacion LIKE ?)";
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
            }
            
            $query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logEvent('Get all properties error: ' . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Get user's properties
     * 
     * @param int $userId
     * @return array
     */
    public function getByUserId($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM properties WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logEvent('Get user properties error: ' . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Update property
     * 
     * @param int $propertyId
     * @param int $userId
     * @param array $data
     * @return array
     */
    public function update($propertyId, $userId, $data) {
        try {
            // Verify ownership
            $stmt = $this->pdo->prepare("SELECT user_id FROM properties WHERE id = ?");
            $stmt->execute([$propertyId]);
            $property = $stmt->fetch();
            
            if (!$property || $property['user_id'] != $userId) {
                return ['success' => false, 'errors' => [ERROR_MESSAGES['unauthorized']]];
            }
            
            $updates = [];
            $params = [];
            
            if (isset($data['titulo'])) {
                $updates[] = "titulo = ?";
                $params[] = sanitize($data['titulo']);
            }
            
            if (isset($data['descripcion'])) {
                $updates[] = "descripcion = ?";
                $params[] = sanitize($data['descripcion']);
            }
            
            if (isset($data['precio'])) {
                $updates[] = "precio = ?";
                $params[] = floatval($data['precio']);
            }
            
            if (isset($data['estado'])) {
                $updates[] = "estado = ?";
                $params[] = $data['estado'];
            }
            
            if (empty($updates)) {
                return ['success' => false, 'errors' => ['No hay datos para actualizar']];
            }
            
            $params[] = $propertyId;
            $query = "UPDATE properties SET " . implode(", ", $updates) . " WHERE id = ?";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            return ['success' => true, 'message' => SUCCESS_MESSAGES['property_updated']];
        } catch (PDOException $e) {
            logEvent('Property update error: ' . $e->getMessage(), 'error');
            return ['success' => false, 'errors' => [ERROR_MESSAGES['database_error']]];
        }
    }
    
    /**
     * Delete property
     * 
     * @param int $propertyId
     * @param int $userId
     * @return array
     */
    public function delete($propertyId, $userId) {
        try {
            // Verify ownership
            $stmt = $this->pdo->prepare("SELECT user_id FROM properties WHERE id = ?");
            $stmt->execute([$propertyId]);
            $property = $stmt->fetch();
            
            if (!$property || $property['user_id'] != $userId) {
                return ['success' => false, 'errors' => [ERROR_MESSAGES['unauthorized']]];
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM properties WHERE id = ?");
            $stmt->execute([$propertyId]);
            
            // Update user's property count
            $this->pdo->prepare("UPDATE users SET propiedades_publicadas = propiedades_publicadas - 1 WHERE id = ?")->execute([$userId]);
            
            return ['success' => true, 'message' => SUCCESS_MESSAGES['property_deleted']];
        } catch (PDOException $e) {
            logEvent('Property deletion error: ' . $e->getMessage(), 'error');
            return ['success' => false, 'errors' => [ERROR_MESSAGES['database_error']]];
        }
    }
    
    /**
     * Increment property views
     * 
     * @param int $propertyId
     * @return void
     */
    public function incrementViews($propertyId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE properties SET vistas = vistas + 1 WHERE id = ?");
            $stmt->execute([$propertyId]);
        } catch (PDOException $e) {
            logEvent('View increment error: ' . $e->getMessage(), 'error');
        }
    }
}
?>
