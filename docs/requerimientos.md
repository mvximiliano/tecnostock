# Análisis y requerimientos – TecnoStock

## 1. Problema

TecnoStock comercializa accesorios tecnológicos y registra sus productos en una planilla. Esto ha generado códigos duplicados, errores en las cantidades disponibles y poca claridad sobre el stock real.

## 2. Objetivo

Construir una aplicación web, ejecutable en un servidor local, que centralice el catálogo de productos y controle sus entradas y salidas, conservando la integridad de la información y siendo fácil de usar para una persona no técnica.

## 3. Alcance

**Incluye:** inicio de sesión, gestión de productos y categorías, registro de movimientos de stock, historial y alerta de stock bajo.

**No incluye:** ventas o facturación, proveedores, múltiples bodegas, roles distintos de usuario ni registro de nuevos usuarios desde la interfaz (el usuario se crea en el script SQL).

## 4. Actor

| Actor | Responsabilidad |
|---|---|
| Encargado de inventario | Administra productos, categorías y movimientos de existencias. |

## 5. Requerimientos funcionales

| ID | Requerimiento | Implementación |
|---|---|---|
| RF01 | Iniciar sesión con un usuario registrado y activo | `login.php` |
| RF02 | Cerrar sesión | `logout.php` |
| RF03 | Registrar productos con código, nombre, categoría, descripción, precio, stock actual y stock mínimo | `producto_formulario.php` |
| RF04 | Consultar el listado de productos | `index.php` |
| RF05 | Buscar productos por nombre, código o categoría | `index.php` (parámetro `buscar`) |
| RF06 | Modificar precio, descripción, categoría y cantidades | `producto_formulario.php?id=` |
| RF07 | Desactivar (y reactivar) productos | `producto_estado.php` |
| RF08 | Registrar entradas y salidas de existencias | `movimiento_formulario.php` |
| RF09 | Mostrar alerta cuando el stock actual sea menor que el mínimo | `index.php`, `movimiento_formulario.php` |
| RF10 | Consultar el historial de movimientos (general y por producto) | `movimientos.php` |
| RF11 | Administrar categorías (crear, editar, desactivar) | `categorias.php` |

## 6. Reglas de negocio

| ID | Regla | Cómo se cumple |
|---|---|---|
| RN01 | El código de producto no puede repetirse | Validación PHP antes de guardar + `UNIQUE (codigo)` |
| RN02 | Precio, stock actual, stock mínimo y cantidad no pueden ser negativos | Validación JS + PHP + restricciones `CHECK` |
| RN03 | Todo movimiento registra fecha, tipo, cantidad, producto y usuario | Columnas `NOT NULL`, fecha automática y usuario tomado de la sesión |
| RN04 | Una salida no puede dejar el stock bajo cero | Validación JS + PHP con la fila bloqueada (`FOR UPDATE`) + `CHECK (stock_actual >= 0)` |
| RN05 | Un producto con movimientos se desactiva, no se elimina | Borrado lógico (`estado = 0`) + `ON DELETE RESTRICT` |
| RN06 | El stock se actualiza automáticamente tras cada movimiento válido | Transacción que inserta el movimiento y actualiza el stock |

**Decisiones de diseño agregadas (justificadas):**

- Al crear un producto con stock inicial, o al cambiar el stock desde la edición, el sistema registra automáticamente un movimiento. Así la suma de entradas menos salidas siempre coincide con el stock actual, y no se pierde trazabilidad.
- No se puede desactivar una categoría que tenga productos activos, para evitar productos huérfanos en el catálogo.
- La alerta se muestra cuando el stock actual es **menor** que el mínimo, tal como lo indica el caso.

## 7. Requerimientos no funcionales

- Funciona en servidor local (XAMPP, PHP 8, MySQL/MariaDB).
- Interfaz responsiva con Bootstrap 5 y mensajes claros en español.
- Seguridad: consultas preparadas, escape de salida, CSRF y contraseñas cifradas.
- Sin frameworks complejos.

## 8. Modelo de datos

```
usuario (id_usuario PK, nombre, correo UQ, clave, estado)
categoria (id_categoria PK, nombre UQ, descripcion, estado)
producto (id_producto PK, codigo UQ, nombre, descripcion, precio, stock_actual,
          stock_minimo, estado, id_categoria FK → categoria)
movimiento (id_movimiento PK, fecha_hora, tipo, cantidad, observacion,
            id_producto FK → producto, id_usuario FK → usuario)
```

Relaciones: una categoría tiene muchos productos (1:N); un producto tiene muchos movimientos (1:N); un usuario registra muchos movimientos (1:N). El modelo está en tercera forma normal: cada atributo depende solo de la clave de su tabla y la categoría se referencia por clave foránea en vez de repetirse como texto.
