<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/database.php';

$error = '';
$id_producto = $_GET['id'] ?? null;

// Obtener lista de categorías para el desplegable
$stmt_cat = $pdo->query("SELECT * FROM categoria WHERE estado = 1");
$categorias = $stmt_cat->fetchAll();

// Si viene con ID, cargamos los datos para editar
$producto = [
    'codigo' => '',
    'nombre' => '',
    'descripcion' => '',
    'precio' => '',
    'stock_actual' => '',
    'stock_minimo' => '',
    'id_categoria' => ''
];

if ($id_producto) {
    $stmt = $pdo->prepare("SELECT * FROM producto WHERE id_producto = ? AND estado = 1");
    $stmt->execute([$id_producto]);
    $prod_db = $stmt->fetch();
    if ($prod_db) {
        $producto = $prod_db;
    } else {
        header("Location: index.php");
        exit;
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo       = trim($_POST['codigo'] ?? '');
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $precio       = floatval($_POST['precio'] ?? 0);
    $stock_actual = intval($_POST['stock_actual'] ?? 0);
    $stock_minimo = intval($_POST['stock_minimo'] ?? 0);
    $id_categoria = intval($_POST['id_categoria'] ?? 0);

    // Validaciones de Reglas de Negocio
    if (empty($codigo) || empty($nombre) || $id_categoria <= 0) {
        $error = "El código, nombre y categoría son obligatorios.";
    } elseif ($precio < 0 || $stock_actual < 0 || $stock_minimo < 0) {
        $error = "El precio y los valores de stock no pueden ser negativos.";
    } else {
        // Validar código único
        $sql_check = "SELECT id_producto FROM producto WHERE codigo = ? AND id_producto != ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$codigo, $id_producto ?? 0]);
        
        if ($stmt_check->fetch()) {
            $error = "El código de producto ya existe. Debe ser único.";
        } else {
            if ($id_producto) {
                // Actualizar
                $sql_update = "UPDATE producto SET codigo = ?, nombre = ?, descripcion = ?, precio = ?, stock_actual = ?, stock_minimo = ?, id_categoria = ? WHERE id_producto = ?";
                $stmt_up = $pdo->prepare($sql_update);
                $stmt_up->execute([$codigo, $nombre, $descripcion, $precio, $stock_actual, $stock_minimo, $id_categoria, $id_producto]);
            } else {
                // Insertar
                $sql_insert = "INSERT INTO producto (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt_in = $pdo->prepare($sql_insert);
                $stmt_in->execute([$codigo, $nombre, $descripcion, $precio, $stock_actual, $stock_minimo, $id_categoria]);
            }
            header("Location: index.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $id_producto ? 'Editar' : 'Nuevo' ?> Producto - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><?= $id_producto ? 'Editar Producto' : 'Registrar Nuevo Producto' ?></h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Código Único</label>
                                <input type="text" name="codigo" class="form-control" value="<?= htmlspecialchars($producto['codigo']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nombre del Producto</label>
                                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($producto['nombre']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Categoría</label>
                            <select name="id_categoria" class="form-select" required>
                                <option value="">-- Seleccionar Categoría --</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= $c['id_categoria'] ?>" <?= $producto['id_categoria'] == $c['id_categoria'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2"><?= htmlspecialchars($producto['descripcion']) ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Precio ($)</label>
                                <input type="number" step="0.01" min="0" name="precio" class="form-control" value="<?= htmlspecialchars($producto['precio']) ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Stock Actual</label>
                                <input type="number" min="0" name="stock_actual" class="form-control" value="<?= htmlspecialchars($producto['stock_actual']) ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Stock Mínimo</label>
                                <input type="number" min="0" name="stock_minimo" class="form-control" value="<?= htmlspecialchars($producto['stock_minimo']) ?>" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <a href="index.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success">Guardar Producto</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>