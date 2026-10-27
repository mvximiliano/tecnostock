<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/database.php';

$buscar = trim($_GET['buscar'] ?? '');

// Consulta para listar productos activos con su categoría
$sql = "SELECT p.*, c.nombre AS categoria_nombre 
        FROM producto p 
        LEFT JOIN categoria c ON p.id_categoria = c.id_categoria 
        WHERE p.estado = 1";

if (!empty($buscar)) {
    $sql .= " AND (p.nombre LIKE ? OR p.codigo LIKE ? OR c.nombre LIKE ?)";
}

$sql .= " ORDER BY p.id_producto DESC";

$stmt = $pdo->prepare($sql);

if (!empty($buscar)) {
    $term = "%$buscar%";
    $stmt->execute([$term, $term, $term]);
} else {
    $stmt->execute();
}

$productos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">TecnoStock</a>
            <div class="d-flex align-items-center text-white">
                <span class="me-3">Hola, <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Catálogo de Productos</h2>
            <a href="producto_formulario.php" class="btn btn-success">+ Nuevo Producto</a>
        </div>

        <!-- Buscador -->
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-10">
                <input type="text" name="buscar" class="form-control" placeholder="Buscar por código, nombre o categoría..." value="<?= htmlspecialchars($buscar) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
            </div>
        </form>

        <!-- Tabla de Productos -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado Stock</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($productos) > 0): ?>
                            <?php foreach ($productos as $p): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($p['codigo']) ?></code></td>
                                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                                    <td><?= htmlspecialchars($p['categoria_nombre'] ?? 'Sin categoría') ?></td>
                                    <td>$<?= number_format($p['precio'], 0, ',', '.') ?></td>
                                    <td><strong><?= $p['stock_actual'] ?></strong></td>
                                    <td>
                                        <?php if ($p['stock_actual'] <= $p['stock_minimo']): ?>
                                            <span class="badge bg-danger">Stock Bajo (Mín: <?= $p['stock_minimo'] ?>)</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="movimiento_formulario.php?id=<?= $p['id_producto'] ?>" class="btn btn-sm btn-info text-white">Stock</a>
                                        <a href="producto_formulario.php?id=<?= $p['id_producto'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                        <a href="producto_eliminar.php?id=<?= $p['id_producto'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que deseas desactivar este producto?');">Desactivar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No se encontraron productos registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>