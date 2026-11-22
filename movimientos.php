<?php
require_once __DIR__ . '/includes/funciones.php';
requerir_login();

$id_producto = (int) ($_GET['id'] ?? 0);
$tipo        = $_GET['tipo'] ?? '';
$producto    = null;

// Historial: cada movimiento con su producto y usuario responsable
$sql = "SELECT m.*, p.codigo, p.nombre AS producto_nombre, u.nombre AS usuario_nombre
        FROM movimiento m
        INNER JOIN producto p ON m.id_producto = p.id_producto
        INNER JOIN usuario u  ON m.id_usuario  = u.id_usuario
        WHERE 1 = 1";
$parametros = [];

if ($id_producto > 0) {
    $sql .= ' AND m.id_producto = ?';
    $parametros[] = $id_producto;

    $stmt = $pdo->prepare('SELECT codigo, nombre, stock_actual FROM producto WHERE id_producto = ?');
    $stmt->execute([$id_producto]);
    $producto = $stmt->fetch();
}
if (in_array($tipo, ['entrada', 'salida'], true)) {
    $sql .= ' AND m.tipo = ?';
    $parametros[] = $tipo;
}
$sql .= ' ORDER BY m.fecha_hora DESC, m.id_movimiento DESC LIMIT 300';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$movimientos = $stmt->fetchAll();

$titulo = 'Movimientos';
$activa = 'movimientos';
require __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="mb-0">Historial de movimientos</h2>
    <?php if ($producto): ?>
        <a href="movimientos.php" class="btn btn-outline-secondary btn-sm">Ver todos los productos</a>
    <?php endif; ?>
</div>

<?php if ($producto): ?>
    <div class="alert alert-secondary">
        Mostrando movimientos de <strong><?= e($producto['nombre']) ?></strong>
        (<code><?= e($producto['codigo']) ?></code>) · Stock actual: <strong><?= (int) $producto['stock_actual'] ?></strong>
    </div>
<?php endif; ?>

<form method="GET" class="row g-2 mb-3">
    <?php if ($id_producto > 0): ?>
        <input type="hidden" name="id" value="<?= $id_producto ?>">
    <?php endif; ?>
    <div class="col-md-4">
        <select name="tipo" class="form-select" onchange="this.form.submit()">
            <option value="">Todos los tipos</option>
            <option value="entrada" <?= $tipo === 'entrada' ? 'selected' : '' ?>>Solo entradas</option>
            <option value="salida" <?= $tipo === 'salida' ? 'selected' : '' ?>>Solo salidas</option>
        </select>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
            <tr>
                <th>Fecha y hora</th>
                <th>Tipo</th>
                <th>Producto</th>
                <th class="text-center">Cantidad</th>
                <th>Observación</th>
                <th>Responsable</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$movimientos): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No hay movimientos registrados.</td></tr>
            <?php endif; ?>
            <?php foreach ($movimientos as $m): ?>
                <tr>
                    <td><?= e(date('d-m-Y H:i', strtotime($m['fecha_hora']))) ?></td>
                    <td>
                        <?php if ($m['tipo'] === 'entrada'): ?>
                            <span class="badge bg-success"><i class="bi bi-plus"></i> Entrada</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><i class="bi bi-dash"></i> Salida</span>
                        <?php endif; ?>
                    </td>
                    <td><code><?= e($m['codigo']) ?></code> <?= e($m['producto_nombre']) ?></td>
                    <td class="text-center fw-bold"><?= $m['tipo'] === 'entrada' ? '+' : '−' ?><?= (int) $m['cantidad'] ?></td>
                    <td class="small"><?= e($m['observacion'] ?? '') ?></td>
                    <td><?= e($m['usuario_nombre']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="small text-muted mt-2">Se muestran como máximo los últimos 300 movimientos.</p>

<?php require __DIR__ . '/includes/footer.php'; ?>
