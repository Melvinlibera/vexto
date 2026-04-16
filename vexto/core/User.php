<?php
/**
 * User Class
 * 
 * Handles user-related operations: registration, login, profile updates.
 */

class User {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Register a new user
     * 
     * @param array $data
     * @return array
     */
    public function register($data) {
        $errors = [];
        
        // Basic required fields
        $required = ['nombre', 'apellido', 'email', 'password', 'tipo_usuario'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "El campo " . $field . " es obligatorio.";
            }
        }

        // Profile photo is mandatory for normal users
        if (!empty($data['tipo_usuario']) && $data['tipo_usuario'] !== 'compania') {
            if (!isset($_FILES['foto_perfil']) || $_FILES['foto_perfil']['error'] !== 0) {
                $errors[] = 'La foto de perfil es obligatoria para usuarios comunes.';
            }
        }

        // Validate email
        if (!empty($data['email']) && !isValidEmail($data['email'])) {
            $errors[] = ERROR_MESSAGES['invalid_email'];
        }
        
        // Check if email already exists
        if (!empty($data['email']) && $this->emailExists($data['email'])) {
            $errors[] = ERROR_MESSAGES['email_exists'];
        }
        
        // Validate password
        if (!empty($data['password']) && !isValidPassword($data['password'])) {
            $errors[] = ERROR_MESSAGES['password_weak'];
        }

        // Validate Cedula (basic length check)
        if (!empty($data['cedula']) && strlen(preg_replace('/\D/', '', $data['cedula'])) < 11) {
            $errors[] = "La cédula debe tener al menos 11 dígitos.";
        }

        if (!empty($data['rnc']) && strlen(preg_replace('/\D/', '', $data['rnc'])) < 9) {
            $errors[] = "El RNC debe tener al menos 9 dígitos.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $maxProperties = ($data['tipo_usuario'] === 'compania') ? DEFAULT_MAX_PROPERTIES_COMPANY : DEFAULT_MAX_PROPERTIES_USER;
        
        // Handle profile photo
        $fotoPerfil = null;
        $fotoPerfilTipo = null;
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === 0) {
            $validationErrors = validateFileUpload($_FILES['foto_perfil'], 'image');
            if (!empty($validationErrors)) {
                return ['success' => false, 'errors' => $validationErrors];
            }
            $fotoPerfil = file_get_contents($_FILES['foto_perfil']['tmp_name']);
            $fotoPerfilTipo = $_FILES['foto_perfil']['type'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (nombre, apellido, email, password, tipo_usuario, cedula, rnc, genero, foto_perfil, foto_perfil_tipo, max_propiedades)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                sanitize($data['nombre']),
                sanitize($data['apellido']),
                sanitize($data['email']),
                $hashedPassword,
                $data['tipo_usuario'],
                sanitize($data['cedula'] ?? ''),
                $data['tipo_usuario'] === 'compania' ? sanitize($data['rnc'] ?? '') : null,
                null,
                $fotoPerfil,
                $fotoPerfilTipo,
                $maxProperties
            ]);
            
            return ['success' => true, 'message' => SUCCESS_MESSAGES['registered']];
        } catch (PDOException $e) {
            logEvent('Registration error: ' . $e->getMessage(), 'error');
            return ['success' => false, 'errors' => [ERROR_MESSAGES['database_error']]];
        }
    }
    
    /**
     * Login user
     * 
     * @param string $email
     * @param string $password
     * @return array
     */
    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([sanitize($email)]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['tipo_usuario'] = $user['tipo_usuario'];
                
                return ['success' => true, 'message' => SUCCESS_MESSAGES['logged_in']];
            }
            
            return ['success' => false, 'errors' => [ERROR_MESSAGES['invalid_credentials']]];
        } catch (PDOException $e) {
            logEvent('Login error: ' . $e->getMessage(), 'error');
            return ['success' => false, 'errors' => [ERROR_MESSAGES['database_error']]];
        }
    }
    
    /**
     * Get user by ID
     * 
     * @param int $userId
     * @return array|null
     */
    public function getById($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            logEvent('Get user error: ' . $e->getMessage(), 'error');
            return null;
        }
    }
    
    /**
     * Check if email exists
     * 
     * @param string $email
     * @return bool
     */
    public function emailExists($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([sanitize($email)]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            logEvent('Email check error: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Update user profile
     * 
     * @param int $userId
     * @param array $data
     * @return array
     */
    public function updateProfile($userId, $data) {
        try {
            $updates = [];
            $params = [];
            
            if (isset($data['bio'])) {
                $updates[] = "bio = ?";
                $params[] = sanitize($data['bio']);
            }
            
            if (isset($data['telefono'])) {
                $updates[] = "telefono = ?";
                $params[] = sanitize($data['telefono']);
            }
            
            if (isset($data['theme_preference'])) {
                $updates[] = "theme_preference = ?";
                $params[] = $data['theme_preference'];
            }
            
            if (empty($updates)) {
                return ['success' => false, 'errors' => ['No hay datos para actualizar']];
            }
            
            $params[] = $userId;
            $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            return ['success' => true, 'message' => SUCCESS_MESSAGES['profile_updated']];
        } catch (PDOException $e) {
            logEvent('Profile update error: ' . $e->getMessage(), 'error');
            return ['success' => false, 'errors' => [ERROR_MESSAGES['database_error']]];
        }
    }
}
?>
