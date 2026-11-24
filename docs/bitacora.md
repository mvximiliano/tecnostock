# 📓 Bitácora de desarrollo – TecnoStock

| | |
|---|---|
| **Estudiante** | Maximiliano Vergara |
| **Sección** | 301 |
| **Asignatura** | Taller de Sistemas de Información |
| **Proyecto** | Caso 1 – Sistema de inventario TecnoStock |

---

## Sesión 1 · 26-10-2026 — Base del proyecto

**Actividades**
- Análisis del caso y definición de las entidades: usuario, categoría, producto y movimiento.
- Creación del modelo relacional y del script `database/schema.sql`.
- Vista principal con listado y búsqueda de productos.
- Formulario para crear y editar productos.
- Inicio de sesión con contraseña cifrada.

**Resultado:** primera versión funcional con login y CRUD básico de productos.

**Dificultades:** ajuste en la validación del login, corregido en el commit `fix(auth)`.

---

## Sesión 2 · 22-11-2026 — MVP completo

**Actividades**
- Registro de entradas y salidas con actualización automática del stock, usando transacciones.
- Validaciones en JavaScript (navegador) y en PHP (servidor).
- Desactivación de productos, en lugar de eliminarlos.
- Alerta visual de stock bajo.
- Administración de categorías e historial de movimientos.
- Restricciones en la base de datos: `UNIQUE`, `CHECK` y `ON DELETE RESTRICT`.
- Documentación de requerimientos (`docs/requerimientos.md`) y casos de prueba (`docs/pruebas.md`).

**Resultado:** MVP completo según los requerimientos del caso.

**Dificultades**
- MySQL no iniciaba en XAMPP. Se solucionó restaurando la carpeta `data` desde `backup`.
- El código se revisó y completó con apoyo de una herramienta de IA.

---

## Sesión 3 · 23-11-2026 — Entrega

**Actividades**
- Actualización del README con instrucciones de instalación.
- Preparación de la presentación y del guion.
- Grabación y edición del video explicativo.

**Resultado:** repositorio listo para ejecutar y video de 4:58 publicado.

**Dificultades:** el video superaba los 5 minutos. Se recortaron diapositivas y pausas.

---

## Resumen

| Sesión | Fecha | Avance principal |
|---|---|---|
| 1 | 26-10-2026 | Base de datos, login y CRUD de productos |
| 2 | 22-11-2026 | MVP completo, validaciones y documentación |
| 3 | 23-11-2026 | README, presentación y video |