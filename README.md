# TecnoStock · Sistema de inventario

Aplicación web para el emprendimiento **TecnoStock**, que reemplaza la planilla de productos por un sistema centralizado: catálogo de productos, categorías, entradas y salidas de stock y alerta de stock bajo.

Proyecto del **Caso 1 – Taller de Sistemas de Información**, desarrollado sin frameworks: PHP 8 + PDO, MySQL, HTML5, Bootstrap 5 y JavaScript.

## Funcionalidades

- Inicio y cierre de sesión con contraseña cifrada (`password_hash` / `password_verify`).
- Registro, listado, búsqueda (por código, nombre o categoría) y edición de productos.
- Desactivación y reactivación de productos (borrado lógico; nunca se eliminan físicamente).
- Administración de categorías.
- Registro de entradas y salidas con actualización automática del stock.
- Historial de movimientos con fecha, tipo, cantidad, producto y usuario responsable.
- Alerta visual cuando el stock actual es menor que el stock mínimo.
- Validaciones en JavaScript (cliente) repetidas en PHP (servidor).

## Requisitos

- [XAMPP](https://www.apachefriends.org/) con PHP 8 o superior (Apache + MySQL/MariaDB).
- Navegador web actualizado.

## Instalación local

1. Copiar la carpeta del proyecto en `C:\xampp\htdocs\tecnostock` (en macOS: `/Applications/XAMPP/htdocs/tecnostock`).
2. Iniciar **Apache** y **MySQL** desde el panel de XAMPP.
3. Abrir `http://localhost/phpmyadmin`, ir a la pestaña **Importar** y cargar `database/schema.sql`.
   El script crea la base `tecnostock`, sus tablas y datos de prueba.
4. Si su MySQL usa otra contraseña, editarla en `config/database.php`.
5. Entrar a `http://localhost/tecnostock`.

**Usuario de prueba:** `admin@tecnostock.cl` · **Contraseña:** `admin123`

## Estructura

```
tecnostock/
├── index.php                  Listado, búsqueda y alerta de stock bajo
├── login.php / logout.php     Inicio y cierre de sesión
├── producto_formulario.php    Crear y editar productos
├── producto_estado.php        Desactivar / reactivar productos (POST)
├── movimiento_formulario.php  Registrar entradas y salidas
├── movimientos.php            Historial de movimientos
├── categorias.php             Administración de categorías
├── config/database.php        Conexión PDO
├── includes/                  Funciones comunes, encabezado y pie
├── assets/js/validaciones.js  Validaciones del lado del cliente
├── assets/css/estilos.css     Ajustes visuales
├── database/schema.sql        Script de creación y datos de prueba
└── docs/                      Requerimientos y casos de prueba
```

## Reglas de negocio y dónde se cumplen

| Regla | Aplicación (PHP) | Respaldo en la base de datos |
|---|---|---|
| El código de producto no se repite | `producto_formulario.php` | `UNIQUE (codigo)` |
| Precio, stock y cantidades no negativos | Formularios PHP + JS | `CHECK (precio >= 0)`, `CHECK (stock_actual >= 0)`, `CHECK (cantidad > 0)` |
| Todo movimiento registra fecha, tipo, cantidad, producto y usuario | `movimiento_formulario.php` | `NOT NULL`, `DEFAULT CURRENT_TIMESTAMP`, claves foráneas |
| Una salida no deja el stock bajo cero | `movimiento_formulario.php` (lectura con `FOR UPDATE`) | `CHECK (stock_actual >= 0)` |
| Los productos con movimientos no se eliminan | `producto_estado.php` (borrado lógico) | `ON DELETE RESTRICT` |
| El stock se actualiza tras cada movimiento | Transacción: `INSERT` movimiento + `UPDATE` stock | InnoDB (transacciones) |

## Seguridad aplicada

- Consultas preparadas con PDO en todas las operaciones (evita inyección SQL).
- Salida escapada con `htmlspecialchars` (evita XSS).
- Token CSRF en todos los formularios que modifican datos.
- Regeneración del ID de sesión al iniciar sesión.
- Los errores técnicos se registran en el log y el usuario ve mensajes comprensibles.

Documentación adicional: [`docs/requerimientos.md`](docs/requerimientos.md) y [`docs/pruebas.md`](docs/pruebas.md).
