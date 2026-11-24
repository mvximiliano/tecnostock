--
-- Script de base de datos TecnoStock
-- Crea la base, las tablas y datos de prueba.
-- Ojo: si ya existen las tablas, se borran y se vuelven a crear.
--

CREATE DATABASE IF NOT EXISTS tecnostock
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tecnostock;
SET NAMES utf8mb4;  -- Asegura que tildes y ñ se guarden correctamente

-- Se eliminan en orden inverso a sus dependencias (claves foráneas)
DROP TABLE IF EXISTS movimiento;
DROP TABLE IF EXISTS producto;
DROP TABLE IF EXISTS categoria;
DROP TABLE IF EXISTS usuario;

-- Tabla de usuarios, con correo único y estado activo/inactivo. La clave se guarda como hash.
CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL,
    correo     VARCHAR(100) NOT NULL,
    clave      VARCHAR(255) NOT NULL COMMENT 'Hash generado con password_hash()',
    estado     TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1 = activo, 0 = inactivo',
    CONSTRAINT uq_usuario_correo UNIQUE (correo),
    CONSTRAINT chk_usuario_estado CHECK (estado IN (0, 1))
) ENGINE = InnoDB;

-- Tabla de categorías de productos, con nombre único y estado activo/inactivo.
CREATE TABLE categoria (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    descripcion  TEXT,
    estado       TINYINT(1)   NOT NULL DEFAULT 1,
    CONSTRAINT uq_categoria_nombre UNIQUE (nombre),
    CONSTRAINT chk_categoria_estado CHECK (estado IN (0, 1))
) ENGINE = InnoDB;

-- Tabla de productos, con stock y precio. Se relaciona con categoría.
CREATE TABLE producto (
    id_producto  INT AUTO_INCREMENT PRIMARY KEY,
    codigo       VARCHAR(50)   NOT NULL,
    nombre       VARCHAR(150)  NOT NULL,
    descripcion  TEXT,
    precio       DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_actual INT           NOT NULL DEFAULT 0,
    stock_minimo INT           NOT NULL DEFAULT 0,
    estado       TINYINT(1)    NOT NULL DEFAULT 1 COMMENT '0 = desactivado (no se elimina físicamente)',
    id_categoria INT           NOT NULL,
    CONSTRAINT uq_producto_codigo   UNIQUE (codigo),                 -- Regla: código no se repite
    CONSTRAINT chk_producto_precio  CHECK (precio >= 0),             -- Regla: valores no negativos
    CONSTRAINT chk_producto_stock   CHECK (stock_actual >= 0),       -- Regla: stock nunca bajo cero
    CONSTRAINT chk_producto_minimo  CHECK (stock_minimo >= 0),
    CONSTRAINT chk_producto_estado  CHECK (estado IN (0, 1)),
    CONSTRAINT fk_producto_categoria FOREIGN KEY (id_categoria)
        REFERENCES categoria (id_categoria)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;

CREATE INDEX idx_producto_nombre ON producto (nombre);

-- Tabla de movimientos de inventario (entradas y salidas)
CREATE TABLE movimiento (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,   -- Regla: todo movimiento registra fecha
    tipo          ENUM('entrada', 'salida') NOT NULL,
    cantidad      INT      NOT NULL,
    observacion   VARCHAR(500),
    id_producto   INT      NOT NULL,
    id_usuario    INT      NOT NULL,                             -- Regla: usuario responsable obligatorio
    CONSTRAINT chk_movimiento_cantidad CHECK (cantidad > 0),
    -- ON DELETE RESTRICT: la base impide borrar un producto que tiene movimientos
    CONSTRAINT fk_movimiento_producto FOREIGN KEY (id_producto)
        REFERENCES producto (id_producto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_movimiento_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;

CREATE INDEX idx_movimiento_fecha ON movimiento (fecha_hora);


--  DATOS DE PRUEBA


-- Usuario: admin@tecnostock.cl / contraseña: admin123
INSERT INTO usuario (nombre, correo, clave) VALUES
('Encargado de Inventario', 'admin@tecnostock.cl', '$2y$10$pklRt8GGNxR9DGo1kzY.YeiW4A0993HCGZ7s1.JOUzbtV6Ia9Drwe');

INSERT INTO categoria (nombre, descripcion) VALUES
('Periféricos',     'Teclados, mouses y accesorios'),
('Audio',           'Audífonos y parlantes'),
('Almacenamiento',  'Pendrives, discos y tarjetas de memoria');

-- Productos (dos de ellos quedan con stock bajo el mínimo para probar la alerta)
INSERT INTO producto (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria) VALUES
('PER-001', 'Teclado mecánico RGB',     'Switch red, layout español', 34990, 15, 5,  1),
('PER-002', 'Mouse inalámbrico',        'Receptor USB, 1600 DPI',     12990,  3, 5,  1),
('AUD-001', 'Audífonos Bluetooth',      'Cancelación de ruido',       24990,  8, 4,  2),
('AUD-002', 'Parlante portátil',        'Resistente al agua',         19990,  2, 3,  2),
('ALM-001', 'Pendrive 64 GB',           'USB 3.0',                     7990, 25, 10, 3);

-- Movimientos coherentes con el stock actual de cada producto
INSERT INTO movimiento (fecha_hora, tipo, cantidad, observacion, id_producto, id_usuario) VALUES
(NOW() - INTERVAL 5 DAY, 'entrada', 20, 'Stock inicial al registrar el producto', 1, 1),
(NOW() - INTERVAL 2 DAY, 'salida',   5, 'Venta mayorista',                        1, 1),
(NOW() - INTERVAL 5 DAY, 'entrada', 10, 'Stock inicial al registrar el producto', 2, 1),
(NOW() - INTERVAL 1 DAY, 'salida',   7, 'Venta en tienda',                        2, 1),
(NOW() - INTERVAL 4 DAY, 'entrada',  8, 'Stock inicial al registrar el producto', 3, 1),
(NOW() - INTERVAL 4 DAY, 'entrada',  2, 'Stock inicial al registrar el producto', 4, 1),
(NOW() - INTERVAL 3 DAY, 'entrada', 25, 'Stock inicial al registrar el producto', 5, 1);
