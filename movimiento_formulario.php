<?php
require_once __DIR__ . '/includes/funciones.php';
requerir_login();

$errores     = [];
$id_producto = (int) ($_GET['id'] ?? 0);

// Producto seleccionado (solo activos)
$stmt = $pdo->prepare('SELECT * FROM producto WHERE id_producto = ? AND estado = 1');
$stmt->execute([$id_producto]);
$producto = $stmt->fetch();

if (!$producto) {
    set_flash('danger', 'El producto no existe o está desactivado.');
    header('Location: index.php');
    exit;
}

$tipo        = 'entrada';
$cantidad    = '';
$observacion = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo        = $_POST['tipo'] ?? '';
    $cantidad    = trim($_POST['cantidad'] ?? '');
    $observacion = trim($_POST['observacion'] ?? '');

    // --- Validaciones en el servidor ---
    if (!csrf_valido()) {
        $errores[] = 'La sesión del formulario expiró. Recargue la página e intente nuevamente.';
    }
    if (!in_array($tipo, ['entrada', 'salida'], true)) {
        $errores[] = 'Debe seleccionar un tipo de movimiento válido.';
    }
    if (filter_var($cantidad, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        $errores[] = 'La cantidad debe ser un número entero mayor que cero.';
    }
    if (largo($observacion) > 500) {
        $errores[] = 'La observación no puede superar 500 caracteres.';
    }

    if (!$errores) {
        $cantidad = (int) $cantidad;
        try {
            $pdo->beginTransaction();

            // Se vuelve a leer el stock BLOQUEANDO la fila (FOR UPDATE) para que dos
            // movimientos simultáneos no calculen sobre el mismo valor.
            $stmt = $pdo->prepare('SELECT stock_actual, stock_minimo FROM producto WHERE id_producto = ? AND estado = 1 FOR UPDATE');
            $stmt->execute([$id_producto]);
            $actual = $stmt->fetch();

            if (!$actual) {
                throw new RuntimeException('El producto ya no está disponible.');
            }

            $stock_actual = (int) $actual['stock_actual'];

            // Regla: una salida no puede dejar el stock bajo cero
            if ($tipo === 'salida' && $cantidad > $stock_actual) {
                throw new RuntimeException("No se puede registrar la salida: el stock disponible ($stock_actual) es menor que la cantidad solicitada ($cantidad).");
            }

            $nuevo_stock = $tipo === 'entrada' ? $stock_actual + $cantidad : $stock_actual - $cantidad;

            // 1) Registrar el movimiento (fecha y hora las asigna MySQL automáticamente)
            $stmt = $pdo->prepare('INSERT INTO movimiento (tipo, cantidad, observacion, id_producto, id_usuario) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$tipo, $cantidad, $observacion !== '' ? $observacion : null, $id_producto, $_SESSION['usuario_id']]);

            // 2) Actualizar el stock del producto
            $stmt = $pdo->prepare('UPDATE producto SET stock_actual = ? WHERE id_producto = ?');
            $stmt->execute([$nuevo_stock, $id_producto]);

            $pdo->commit();

            $texto = ucfirst($tipo) . " de $cantidad unidad(es) registrada para «{$producto['nombre']}». Stock actual: $nuevo_stock.";
            if ($nuevo_stock < (int) $actual['stock_minimo']) {
                set_flash('warning', $texto . ' ¡Atención! Quedó bajo el stock mínimo (' . (int) $actual['stock_minimo'] . ').');
            } else {
                set_flash('success', $texto);
            }
            header('Location: index.php');
            exit;
        } catch (RuntimeException $ex) {
            $pdo->rollBack();
            $errores[] = $ex->getMessage();
            $producto['stock_actual'] = $actual['stock_actual'] ?? $producto['stock_actual'];
        } catch (PDOException $ex) {
            $pdo->rollBack();
            error_log('Error al registrar movimiento: ' . $ex->getMessage());
            $errores[] = 'Ocurrió un error inesperado al registrar el movimiento. Intente nuevamente.';
        }
    }
}

$titulo = 'Movimiento de inventario';
$activa = 'productos';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Registrar movimiento de inventario</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-secondary mb-3">
                    <strong>Producto:</strong> <?= e($producto['nombre']) ?><br>
                    <strong>Código:</strong> <code><?= e($producto['codigo']) ?></code><br>
                    <strong>Stock actual:</strong> <?= (int) $producto['stock_actual'] ?> unidades
                    (mínimo: <?= (int) $producto['stock_minimo'] ?>)
                    <?php if (stock_bajo($producto)): ?>
                        <span class="badge bg-danger ms-1">Stock bajo</span>
                    <?php endif; ?>
                </div>

                <?php if ($errores): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errores as $err): ?>
                            <div><?= e($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="form-movimiento" data-stock="<?= (int) $producto['stock_actual'] ?>" novalidate>
                    <?= csrf_campo() ?>
                    <div class="mb-3">
                        <label for="tipo" class="form-label fw-bold">Tipo de movimiento *</label>
                        <select id="tipo" name="tipo" class="form-select" required>
                            <option value="entrada" <?= $tipo === 'entrada' ? 'selected' : '' ?>>Entrada (+ aumentar stock)</option>
                            <option value="salida" <?= $tipo === 'salida' ? 'selected' : '' ?>>Salida (− disminuir stock)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="cantidad" class="form-label fw-bold">Cantidad *</label>
                        <input type="number" id="cantidad" name="cantidad" min="1" step="1" class="form-control"
                               value="<?= e($cantidad) ?>" placeholder="Ej: 10" required>
                        <div class="invalid-feedback" id="cantidad-error">La cantidad debe ser un entero mayor que 0.</div>
                        <div class="form-text" id="stock-resultante"></div>
                    </div>

                    <div class="mb-3">
                        <label for="observacion" class="form-label">Observación / motivo</label>
                        <textarea id="observacion" name="observacion" class="form-control" rows="2" maxlength="500"
                                  placeholder="Ej: compra a proveedor, venta boleta 1234 (opcional)"><?= e($observacion) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar movimiento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
