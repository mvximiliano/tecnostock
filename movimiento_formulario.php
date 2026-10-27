<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/database.php';

$error = '';
$id_producto = $_GET['id'] ?? null;

if (!$id_producto) {
    header("Location: index.php");
    exit;
}

// Obtener datos del producto seleccionado
$stmt = $pdo->prepare("SELECT * FROM producto WHERE id_producto = ? AND estado = 1");
$stmt->execute([$id_producto]);
$producto = $stmt->fetch();

if (!$producto) {
    header("Location: index.php");
    exit;
}

// Procesar registro de movimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo        = $_POST['tipo'] ?? '';
    $cantidad    = intval($_POST['cantidad'] ?? 0);
    $observacion = trim($_POST['observacion'] ?? '');
    $id_usuario  = $_SESSION['usuario_id'];

    if (!in_array($tipo, ['entrada', 'salida'])) {
        $error = "Debe seleccionar un tipo de movimiento válido.";
    } elseif ($cantidad <= 0) {
        $error = "La cantidad debe ser mayor a cero.";
    } elseif ($tipo === 'salida' && $producto['stock_actual'] < $cantidad) {
        $error = "Error: No se puede realizar la salida. El stock disponible ({$producto['stock_actual']}) es insuficiente.";
    } else {
        try {
            $pdo->beginTransaction();

            $sql_mov = "INSERT INTO movimiento (tipo, cantidad, observacion, id_producto, id_usuario) VALUES (?, ?, ?, ?, ?)";
            $stmt_mov = $pdo->prepare($sql_mov);
            $stmt_mov->execute([$tipo, $cantidad, $observacion, $id_producto, $id_usuario]);

            $nuevo_stock = ($tipo === 'entrada') ? $producto['stock_actual'] + $cantidad : $producto['stock_actual'] - $cantidad;
            
            $sql_upd = "UPDATE producto SET stock_actual = ? WHERE id_producto = ?";
            $stmt_upd = $pdo->prepare($sql_upd);
            $stmt_upd->execute([$nuevo_stock, $id_producto]);

            $pdo->commit();

            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Ocurrió un error al procesar el movimiento: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimiento de Inventario - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Registrar Movimiento de Inventario</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-secondary mb-3">
                        <strong>Producto:</strong> <?= htmlspecialchars($producto['nombre']) ?><br>
                        <strong>Código:</strong> <code><?= htmlspecialchars($producto['codigo']) ?></code><br>
                        <strong>Stock Actual:</strong> <?= $producto['stock_actual'] ?> unidades
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo de Movimiento</label>
                            <select name="tipo" class="form-select" required>
                                <option value="entrada">Entrada (+ Aumentar Stock)</option>
                                <option value="salida">Salida (- Disminuir Stock)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Cantidad</label>
                            <input type="number" name="cantidad" min="1" class="form-control" placeholder="Ej: 10" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observación / Nota</label>
                            <textarea name="observacion" class="form-control" rows="2" placeholder="Motivo de la entrada/salida (opcional)"></textarea>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="index.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Guardar Movimiento</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>