USE tecnostock;

-- 1. Tabla Usuario
CREATE TABLE IF NOT EXISTS usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    clave VARCHAR(255) NOT NULL,
    estado TINYINT(1) DEFAULT 1
);

-- 2. Tabla Categoría
CREATE TABLE IF NOT EXISTS categoria (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado TINYINT(1) DEFAULT 1
);

-- 3. Tabla Producto
CREATE TABLE IF NOT EXISTS producto (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) CHECK (precio >= 0),
    stock_actual INT CHECK (stock_actual >= 0),
    stock_minimo INT CHECK (stock_minimo >= 0),
    estado TINYINT(1) DEFAULT 1,
    id_categoria INT,
    FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
);

-- 4. Tabla Movimiento
CREATE TABLE IF NOT EXISTS movimiento (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    tipo ENUM('entrada', 'salida') NOT NULL,
    cantidad INT CHECK (cantidad > 0),
    observacion TEXT,
    id_producto INT,
    id_usuario INT,
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

-- Insertar usuario admin inicial
INSERT INTO usuario (nombre, correo, clave) 
VALUES ('Administrador', 'admin@tecnostock.cl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insertar categorías iniciales
INSERT INTO categoria (nombre, descripcion) VALUES 
('Periféricos', 'Teclados, mouses y accesorios'),
('Audio', 'Audífonos y parlantes');