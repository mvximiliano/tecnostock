<?php
require_once __DIR__ . '/includes/funciones.php';
requerir_login();

$errores     = [];
$id_producto = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$es_edicion  = $id_producto > 0;

// Valores por defecto para un producto nuevo
$producto = [
    'codigo' => '', 'nombre' => '', 'descripcion' => '', 'precio' => '',
    'stock_actual' => '', 'stock_minimo' => '', 'id_categoria' => '',
];
$stock_original = 0;

// Si viene un ID, se cargan los datos para editar
if ($es_edicion) {
    $stmt = $pdo->prepare('SELECT * FROM producto WHERE id_producto = ? AND estado = 1');
    $stmt->execute([$id_producto]);
    $encontrado = $stmt->fetch();
    if (!$encontrado) {
        set_flash('danger', 'El producto no existe o está desactivado.');
        header('Location: index.php');
        exit;
    }
    $producto       = $encontrado;
    $stock_original = (int) $encontrado['stock_actual'];
}

// Categorías activas (y la categoría actual del producto, aunque esté inactiva)
$stmt_cat = $pdo->prepare('SELECT id_categoria, nombre FROM categoria WHERE estado = 1 OR id_categoria = ? ORDER BY nombre');
$stmt_cat->execute([(int) ($producto['id_categoria'] ?: 0)]);
$categorias = $stmt_cat->fetchAll();

// ---------- Procesar formulario ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $errores[] = 'La sesión del formulario expiró. Recargue la página e intente nuevamente.';
    }

    $producto['codigo']       = strtoupper(trim($_POST['codigo'] ?? ''));
    $producto['nombre']       = trim($_POST['nombre'] ?? '');
    $producto['descripcion']  = trim($_POST['descripcion'] ?? '');
    $producto['precio']       = trim($_POST['precio'] ?? '');
    $producto['stock_actual'] = trim($_POST['stock_actual'] ?? '');
    $producto['stock_minimo'] = trim($_POST['stock_minimo'] ?? '');
    $producto['id_categoria'] = (int) ($_POST['id_categoria'] ?? 0);

    // --- Validaciones en el servidor (reglas de negocio) ---
    if ($producto['codigo'] === '' || !preg_match('/^[A-Z0-9\-]{2,50}$/', $producto['codigo'])) {
        $errores[] = 'El código es obligatorio (2 a 50 caracteres: letras, números o guion).';
    }
    if ($producto['nombre'] === '' || largo($producto['nombre']) > 150) {
        $errores[] = 'El nombre es obligatorio y no puede superar 150 caracteres.';
    }
    if (!is_numeric($producto['precio']) || (float) $producto['precio'] < 0) {
        $errores[] = 'El precio debe ser un número mayor o igual a 0.';
    }
    if (filter_var($producto['stock_actual'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
        $errores[] = 'El stock actual debe ser un número entero mayor o igual a 0.';
    }
    if (filter_var($producto['stock_minimo'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
        $errores[] = 'El stock mínimo debe ser un número entero mayor o igual a 0.';
    }

    // La categoría debe existir (y estar activa, salvo que sea la que ya tenía el producto)
    $ids_validos = array_column($categorias, 'id_categoria');
    if (!in_array($producto['id_categoria'], array_map('intval', $ids_validos), true)) {
        $errores[] = 'Debe seleccionar una categoría válida.';
    }

    // Regla: el código no puede repetirse (se revisan también los productos desactivados)
    if (!$errores) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM producto WHERE codigo = ? AND id_producto <> ?');
        $stmt->execute([$producto['codigo'], $id_producto]);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = 'El código «' . $producto['codigo'] . '» ya existe. Debe ser único.';
        }
    }

    if (!$errores) {
        $precio       = round((float) $producto['precio'], 2);
        $stock_nuevo  = (int) $producto['stock_actual'];
        $stock_minimo = (int) $producto['stock_minimo'];

        try {
            // Transacción: el producto y su movimiento de stock se guardan juntos o no se guarda nada
            $pdo->beginTransaction();

            if ($es_edicion) {
                $stmt = $pdo->prepare('UPDATE producto
                    SET codigo = ?, nombre = ?, descripcion = ?, precio = ?, stock_actual = ?, stock_minimo = ?, id_categoria = ?
                    WHERE id_producto = ?');
                $stmt->execute([$producto['codigo'], $producto['nombre'], $producto['descripcion'], $precio,
                    $stock_nuevo, $stock_minimo, $producto['id_categoria'], $id_producto]);

                // Si se cambió el stock a mano, se registra como movimiento para no perder trazabilidad
                $diferencia = $stock_nuevo - $stock_original;
                if ($diferencia !== 0) {
                    $stmt = $pdo->prepare('INSERT INTO movimiento (tipo, cantidad, observacion, id_producto, id_usuario) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([
                        $diferencia > 0 ? 'entrada' : 'salida',
                        abs($diferencia),
                        "Ajuste manual desde edición del producto (de $stock_original a $stock_nuevo)",
                        $id_producto,
                        $_SESSION['usuario_id'],
                    ]);
                }
                $mensaje = 'Producto actualizado correctamente.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO producto (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria)
                    VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$producto['codigo'], $producto['nombre'], $producto['descripcion'], $precio,
                    $stock_nuevo, $stock_minimo, $producto['id_categoria']]);
                $nuevo_id = (int) $pdo->lastInsertId();

                // El stock inicial queda registrado como primera entrada
                if ($stock_nuevo > 0) {
                    $stmt = $pdo->prepare('INSERT INTO movimiento (tipo, cantidad, observacion, id_producto, id_usuario) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute(['entrada', $stock_nuevo, 'Stock inicial al registrar el producto', $nuevo_id, $_SESSION['usuario_id']]);
                }
                $mensaje = 'Producto «' . $producto['nombre'] . '» registrado correctamente.';
            }

            $pdo->commit();

            if ($stock_nuevo < $stock_minimo) {
                $mensaje .= ' Atención: el stock quedó bajo el mínimo.';
                set_flash('warning', $mensaje);
            } else {
                set_flash('success', $mensaje);
            }
            header('Location: index.php');
            exit;
        } catch (PDOException $ex) {
            $pdo->rollBack();
            error_log('Error al guardar producto: ' . $ex->getMessage());
            // 23000 = violación de restricción (por ejemplo, código duplicado)
            $errores[] = $ex->getCode() === '23000'
                ? 'No se pudo guardar: el código ya existe o un dato no cumple las restricciones.'
                : 'Ocurrió un error inesperado al guardar. Intente nuevamente.';
        }
    }
}

$titulo = $es_edicion ? 'Editar producto' : 'Nuevo producto';
$activa = 'productos';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><?= $es_edicion ? 'Editar producto' : 'Registrar nuevo producto' ?></h4>
            </div>
            <div class="card-body">
                <?php if ($errores): ?>
                    <div class="alert alert-danger">
                        <strong>Revise los siguientes datos:</strong>
                        <ul class="mb-0">
                            <?php foreach ($errores as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="form-producto" novalidate>
                    <?= csrf_campo() ?>
                    <p class="small text-muted">Los campos marcados con * son obligatorios.</p>

                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="codigo" class="form-label fw-bold">Código único *</label>
                            <input type="text" id="codigo" name="codigo" class="form-control text-uppercase"
                                   value="<?= e($producto['codigo']) ?>" maxlength="50" pattern="[A-Za-z0-9\-]{2,50}"
                                   placeholder="Ej: PER-003" required>
                            <div class="invalid-feedback">Use de 2 a 50 caracteres: letras, números o guion.</div>
                        </div>
                        <div class="col-md-7 mb-3">
                            <label for="nombre" class="form-label fw-bold">Nombre del producto *</label>
                            <input type="text" id="nombre" name="nombre" class="form-control"
                                   value="<?= e($producto['nombre']) ?>" maxlength="150" required>
                            <div class="invalid-feedback">El nombre es obligatorio.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="id_categoria" class="form-label fw-bold">Categoría *</label>
                        <select id="id_categoria" name="id_categoria" class="form-select" required>
                            <option value="">-- Seleccionar categoría --</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= (int) $c['id_categoria'] ?>" <?= (int) $producto['id_categoria'] === (int) $c['id_categoria'] ? 'selected' : '' ?>>
                                    <?= e($c['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Seleccione una categoría.</div>
                        <div class="form-text">¿No está la categoría? <a href="categorias.php">Administrar categorías</a>.</div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea id="descripcion" name="descripcion" class="form-control" rows="2" maxlength="1000"><?= e($producto['descripcion']) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="precio" class="form-label fw-bold">Precio ($) *</label>
                            <input type="number" id="precio" name="precio" step="1" min="0" class="form-control"
                                   value="<?= e($producto['precio'] === '' ? '' : (float) $producto['precio']) ?>" required>
                            <div class="invalid-feedback">El precio no puede ser negativo.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="stock_actual" class="form-label fw-bold">Stock actual *</label>
                            <input type="number" id="stock_actual" name="stock_actual" step="1" min="0" class="form-control"
                                   value="<?= e($producto['stock_actual']) ?>" required>
                            <div class="invalid-feedback">Debe ser un entero mayor o igual a 0.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="stock_minimo" class="form-label fw-bold">Stock mínimo *</label>
                            <input type="number" id="stock_minimo" name="stock_minimo" step="1" min="0" class="form-control"
                                   value="<?= e($producto['stock_minimo']) ?>" required>
                            <div class="invalid-feedback">Debe ser un entero mayor o igual a 0.</div>
                        </div>
                    </div>

                    <?php if ($es_edicion): ?>
                        <div class="alert alert-info small py-2">
                            <i class="bi bi-info-circle"></i> Para entradas y salidas habituales use el botón <strong>Stock</strong> del listado.
                            Si cambia aquí el stock actual, el sistema registrará el ajuste en el historial de movimientos.
                        </div>
                    <?php endif; ?>

                    <div id="aviso-stock" class="alert alert-warning small py-2 d-none">
                        <i class="bi bi-exclamation-triangle"></i> El stock actual es menor que el mínimo: el producto aparecerá con alerta.
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Guardar producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
