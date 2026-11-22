<?php
require_once __DIR__ . '/includes/funciones.php';
requerir_login();

$buscar = trim($_GET['buscar'] ?? '');
$ver    = ($_GET['ver'] ?? 'activos') === 'inactivos' ? 'inactivos' : 'activos';

// Listado de productos con su categoría (consulta preparada)
$sql = "SELECT p.*, c.nombre AS categoria_nombre
        FROM producto p
        INNER JOIN categoria c ON p.id_categoria = c.id_categoria
        WHERE p.estado = ?";
$parametros = [$ver === 'activos' ? 1 : 0];

if ($buscar !== '') {
    $sql .= " AND (p.nombre LIKE ? OR p.codigo LIKE ? OR c.nombre LIKE ?)";
    $termino = "%$buscar%";
    array_push($parametros, $termino, $termino, $termino);
}
$sql .= " ORDER BY p.nombre ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$productos = $stmt->fetchAll();

// Cantidad total de productos activos con stock bajo (para la alerta superior)
$total_bajo = (int) $pdo->query(
    "SELECT COUNT(*) FROM producto WHERE estado = 1 AND stock_actual < stock_minimo"
)->fetchColumn();

$titulo = 'Inventario';
$activa = 'productos';
require __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="mb-0">Catálogo de productos</h2>
    <a href="producto_formulario.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Nuevo producto</a>
</div>

<?php if ($total_bajo > 0): ?>
    <div class="alert alert-warning d-flex align-items-center">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div>
            <strong>Atención:</strong> hay <?= $total_bajo ?> producto(s) con stock bajo el mínimo.
            Se destacan en rojo en la tabla.
        </div>
    </div>
<?php endif; ?>

<!-- Buscador -->
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-7">
        <input type="text" name="buscar" class="form-control" maxlength="100"
               placeholder="Buscar por código, nombre o categoría..." value="<?= e($buscar) ?>">
    </div>
    <div class="col-md-3">
        <select name="ver" class="form-select">
            <option value="activos" <?= $ver === 'activos' ? 'selected' : '' ?>>Productos activos</option>
            <option value="inactivos" <?= $ver === 'inactivos' ? 'selected' : '' ?>>Productos desactivados</option>
        </select>
    </div>
    <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Buscar</button>
        <?php if ($buscar !== '' || $ver !== 'activos'): ?>
            <a href="index.php" class="btn btn-outline-secondary" title="Limpiar"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th class="text-end">Precio</th>
                <th class="text-center">Stock</th>
                <th>Estado stock</th>
                <th class="text-end">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($productos) === 0): ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <?= $buscar !== '' ? 'No hay productos que coincidan con la búsqueda.' : 'No hay productos registrados.' ?>
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($productos as $p): ?>
                <?php $bajo = stock_bajo($p); ?>
                <tr class="<?= $bajo && $ver === 'activos' ? 'fila-stock-bajo' : '' ?>">
                    <td><code><?= e($p['codigo']) ?></code></td>
                    <td>
                        <?= e($p['nombre']) ?>
                        <?php if ($p['descripcion']): ?>
                            <div class="small text-muted"><?= e($p['descripcion']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($p['categoria_nombre']) ?></td>
                    <td class="text-end"><?= pesos($p['precio']) ?></td>
                    <td class="text-center"><strong><?= (int) $p['stock_actual'] ?></strong></td>
                    <td>
                        <?php if ($bajo): ?>
                            <span class="badge bg-danger"><i class="bi bi-arrow-down"></i> Stock bajo (mín. <?= (int) $p['stock_minimo'] ?>)</span>
                        <?php else: ?>
                            <span class="badge bg-success">Normal (mín. <?= (int) $p['stock_minimo'] ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end acciones">
                        <?php if ($ver === 'activos'): ?>
                            <a href="movimiento_formulario.php?id=<?= (int) $p['id_producto'] ?>" class="btn btn-sm btn-info text-white" title="Registrar entrada o salida">
                                <i class="bi bi-arrow-left-right"></i> Stock
                            </a>
                            <a href="producto_formulario.php?id=<?= (int) $p['id_producto'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="movimientos.php?id=<?= (int) $p['id_producto'] ?>" class="btn btn-sm btn-outline-secondary" title="Ver historial">
                                <i class="bi bi-clock-history"></i>
                            </a>
                            <form method="POST" action="producto_estado.php" class="d-inline"
                                  data-confirmar="¿Desactivar «<?= e($p['nombre']) ?>»? Dejará de aparecer en el catálogo, pero se conserva su historial.">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $p['id_producto'] ?>">
                                <input type="hidden" name="accion" value="desactivar">
                                <button type="submit" class="btn btn-sm btn-danger" title="Desactivar"><i class="bi bi-slash-circle"></i></button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="producto_estado.php" class="d-inline">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $p['id_producto'] ?>">
                                <input type="hidden" name="accion" value="reactivar">
                                <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise"></i> Reactivar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="small text-muted mt-2"><?= count($productos) ?> producto(s) listado(s).</p>

<?php require __DIR__ . '/includes/footer.php'; ?>
