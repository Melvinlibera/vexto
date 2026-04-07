CREATE DATABASE IF NOT EXISTS vexto_db;
USE vexto_db;

-- Tabla de Usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    genero ENUM('Masculino', 'Femenino', 'Otro') NOT NULL,
    cedula VARCHAR(20) UNIQUE NOT NULL,
    rnc VARCHAR(20),
    telefono VARCHAR(20),
    email VARCHAR(100) UNIQUE NOT NULL,
    email_change_count TINYINT(1) NOT NULL DEFAULT 0,
    password VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('usuario', 'compania') DEFAULT 'usuario',
    propiedades_publicadas INT DEFAULT 0,
    max_propiedades INT DEFAULT 3,
    bio TEXT,
    foto_perfil LONGBLOB,
    foto_perfil_tipo VARCHAR(50),
    rating DECIMAL(3, 2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    theme_preference ENUM('light', 'dark') DEFAULT 'light',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de Propiedades
CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    precio DECIMAL(15, 2) NOT NULL,
    tipo_operacion ENUM('venta', 'alquiler') NOT NULL,
    tipo_propiedad ENUM('casa', 'apartamento', 'local', 'terreno', 'otro') DEFAULT 'casa',
    ubicacion VARCHAR(255) NOT NULL,
    latitud DECIMAL(10, 8),
    longitud DECIMAL(11, 8),
    habitaciones INT DEFAULT 0,
    banos INT DEFAULT 0,
    area_m2 DECIMAL(10, 2),
    imagen LONGBLOB,
    imagen_tipo VARCHAR(50),
    imagen_url VARCHAR(255),
    vistas INT DEFAULT 0,
    estado ENUM('activa', 'inactiva', 'vendida') DEFAULT 'activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de Favoritos
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    UNIQUE KEY unique_fav (user_id, property_id)
);

-- Tabla de Mensajes (Chat)
CREATE TABLE IF NOT EXISTS messages (
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
);

-- Tabla de Citas
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, -- El comprador que solicita la cita
    seller_id INT NOT NULL, -- El vendedor que recibe la cita
    property_id INT NOT NULL,
    fecha_cita DATETIME NOT NULL,
    estado ENUM('pendiente', 'confirmada', 'cancelada') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Tabla de Reportes
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    motivo TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Tabla de Valoraciones
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT NOT NULL,
    seller_id INT NOT NULL,
    stars INT CHECK (stars BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insertar usuario de ejemplo: Melvyn Liberata Torres
INSERT IGNORE INTO users (nombre, apellido, genero, cedula, rnc, telefono, email, password, tipo_usuario, max_propiedades, bio, rating, total_reviews)
VALUES (
    'Melvyn',
    'Liberata Torres',
    'Masculino',
    '12345678901',
    '123456789',
    '+1-809-555-0123',
    'melvyn@vexto.com',
    '$2y$10$YourHashedPasswordHere',
    'compania',
    20,
    'Empresa especializada en propiedades de lujo y bienes raíces premium en el Caribe.',
    4.8,
    45
);
