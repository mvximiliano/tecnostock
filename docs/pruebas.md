# Casos de prueba – TecnoStock

**Datos de partida:** base cargada desde `database/schema.sql`. Usuario `admin@tecnostock.cl` / `admin123`.

Para la evidencia, guardar capturas en `docs/evidencias/` con el ID del caso como nombre (por ejemplo `CP06.png`) y marcar el resultado obtenido.

| ID | Tipo | Caso | Datos de entrada | Resultado esperado | Resultado obtenido |
|---|---|---|---|---|---|
| CP01 | Error | Acceso sin sesión | Abrir `index.php` sin iniciar sesión | Redirige a `login.php` | ✅ |
| CP02 | Error | Login con clave incorrecta | admin@tecnostock.cl / `xx` | Mensaje «Credenciales incorrectas o usuario inactivo» | ✅ |
| CP03 | Normal | Login correcto | admin@tecnostock.cl / `admin123` | Ingresa al catálogo y muestra el nombre del usuario | ✅ |
| CP04 | Normal | Alerta de stock bajo | Catálogo inicial | Aviso «hay 2 producto(s)…»; PER-002 y AUD-002 en rojo | ✅ |
| CP05 | Normal | Búsqueda por categoría | Buscar `audio` | Muestra solo AUD-001 y AUD-002 | ✅ |
| CP06 | Error | Código duplicado | Nuevo producto con código `PER-001` | Mensaje «El código… ya existe» y no se guarda | ✅ |
| CP07 | Límite | Precio negativo | Precio `-5` | JS marca el campo; PHP rechaza si se salta JS | ✅ |
| CP08 | Normal | Crear producto | ALM-002, Disco SSD 1TB, $59.990, stock 4, mín. 2 | Se guarda y aparece en el listado; se registra entrada «Stock inicial» | ✅ |
| CP09 | Normal | Editar producto | Cambiar precio de PER-001 a 32990 y stock de 15 a 12 | Se actualiza; historial muestra salida «Ajuste manual… de 15 a 12» | ✅ |
| CP10 | Normal | Registrar entrada | PER-002, entrada de 10 | Stock pasa de 3 a 13; deja de estar en rojo | ✅ |
| CP11 | Error | Salida mayor al stock | PER-002 (stock 3), salida de 99 | Mensaje «No se puede registrar la salida…»; stock sin cambios | ✅ |
| CP12 | Límite | Salida igual al stock | AUD-002 (stock 2), salida de 2 | Stock queda en 0 y aparece aviso de stock bajo | ✅ |
| CP13 | Límite | Cantidad cero o negativa | Cantidad `0` o `-3` | Mensaje «La cantidad debe ser… mayor que cero» | ✅ |
| CP14 | Normal | Desactivar producto | Desactivar ALM-001 y confirmar | Desaparece de activos; aparece en «Productos desactivados» con su historial intacto | ✅ |
| CP15 | Normal | Reactivar producto | Reactivar ALM-001 | Vuelve al catálogo activo | ✅ |
| CP16 | Error | Desactivar categoría con productos activos | Desactivar «Periféricos» | Mensaje indicando que tiene productos activos | ✅ |
| CP17 | Normal | Crear categoría | «Cables» | Aparece en la lista y en el formulario de productos | ✅ |
| CP18 | Normal | Historial por producto | Botón de historial de PER-001 | Lista sus movimientos con fecha, tipo, cantidad y usuario | ✅ |
| CP19 | Seguridad | Intento de XSS en búsqueda | Buscar `<script>alert(1)</script>` | Se muestra como texto; no se ejecuta | ✅ |
| CP20 | Seguridad | Desactivar por URL (GET) | Abrir `producto_estado.php?id=5` | Mensaje «Solicitud no válida»; el producto sigue activo | ✅ |
| CP21 | Integridad | Borrado físico en la BD | `DELETE FROM producto WHERE id_producto = 1;` | MySQL lo impide (error de clave foránea) | ✅ |
| CP22 | Integridad | Stock negativo directo en la BD | `UPDATE producto SET stock_actual = -1 WHERE id_producto = 1;` | MySQL lo impide (restricción CHECK) | ✅ |
| CP23 | Integridad | Stock coherente con movimientos | Consulta de verificación (abajo) | `stock_actual` = suma de entradas − salidas en todos los productos | ✅ |
| CP24 | Normal | Cerrar sesión | Botón «Cerrar sesión» | Vuelve al login; `index.php` ya no es accesible | ✅ |

## Consulta de verificación de integridad (CP23)

```sql
SELECT p.codigo, p.stock_actual,
       SUM(CASE WHEN m.tipo = 'entrada' THEN m.cantidad ELSE -m.cantidad END) AS stock_segun_movimientos
FROM producto p
JOIN movimiento m ON m.id_producto = p.id_producto
GROUP BY p.id_producto, p.codigo, p.stock_actual;
```

Ambas columnas deben coincidir en todas las filas.
