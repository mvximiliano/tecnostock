<?php
/**
 * Administración de categorías: crear, editar, desactivar y reactivar.
 * El encargado de inventario administra productos, categorías y movimientos.
 */
require_once __DIR__ . '/includes/funciones.php';
requerir_login();

$errores   = [];
$id_editar = (int) ($_GET['editar'] ?? 0);
$categoria = ['id_categoria' => 0, 'nombre' => '', 'descripcion' => ''];

// Cargar categoría a editar
if ($id_editar > 0) {
    $stmt = $pdo->prepare('SELECT * FROM categoria WHERE id_categoria = ?');
    $stmt->execute([$id_editar]);
    $categoria = $stmt->fetch() ?: $categoria;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if (!csrf_valido()) {
        $errores[] = 'La sesión del formulario expiró. Recargue la página.';
    } elseif ($accion === 'guardar') {
        $id          = (int) ($_POST['id_categoria'] ?? 0);
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $categoria   = ['id_categoria' => $id, 'nombre' => $nombre, 'descripcion' => $descripcion];

        if ($nombre === '' || largo($nombre) > 100) {
            $errores[] = 'El nombre es obligatorio y no puede superar 100 caracteres.';
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM categoria WHERE nombre = ? AND id_categoria <> ?');
            $stmt->execute([$nombre, $id]);
            if ($stmt->fetchColumn() > 0) {
                $errores[] = 'Ya existe una categoría con ese nombre.';
            }
        }

        if (!$errores) {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE categoria SET nombre = ?, descripcion = ? WHERE id_categoria = ?');
                $stmt->execute([$nombre, $descripcion, $id]);
                set_flash('success', 'Categoría actualizada.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO categoria (nombre, descripcion) VALUES (?, ?)');
                $stmt->execute([$nombre, $descripcion]);
                set_flash('success', "Categoría «{$nombre}» creada.");
            }
            header('Location: categorias.php');
            exit;
        }
    } elseif ($accion === 'desactivar' || $accion === 'reactivar') {
        $id = (int) ($_POST['id_categoria'] ?? 0);

        if ($accion === 'desactivar') {
            // No se desactiva una categoría que todavía tiene productos activos
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM producto WHERE id_categoria = ? AND estado = 1');
            $stmt->execute([$id]);
            $activos = (int) $stmt->fetchColumn();
            if ($activos > 0) {
                set_flash('warning', "No se puede desactivar: la categoría tiene $activos producto(s) activo(s). Cámbielos de categoría o desactívelos primero.");
                header('Location: categorias.php');
                exit;
            }
        }

        $stmt = $pdo->prepare('UPDATE categoria SET estado = ? WHERE id_categoria = ?');
        $stmt->execute([$accion === 'desactivar' ? 0 : 1, $id]);
        set_flash('success', $accion === 'desactivar' ? 'Categoría desactivada.' : 'Categoría reactivada.');
        header('Location: categorias.php');
        exit;
    }
}

// Listado con cantidad de productos activos por categoría
$categorias = $pdo->query(
    "SELECT c.*, COUNT(p.id_producto) AS productos
     FROM categoria c
     LEFT JOIN producto p ON p.id_categoria = c.id_categoria AND p.estado = 1
     GROUP BY c.id_categoria
     ORDER BY c.estado DESC, c.nombre"
)->fetchAll();

$titulo = 'Categorías';
$activa = 'categorias';
require __DIR__ . '/includes/header.php';
?>

<h2 class="mb-3">Categorías</h2>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <?= $categoria['id_categoria'] ? 'Editar categoría' : 'Nueva categoría' ?>
            </div>
            <div class="card-body">
                <?php if ($errores): ?>
                    <div class="alert alert-danger py-2">
                        <?php foreach ($errores as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="categorias.php" id="form-categoria" novalidate>
                    <?= csrf_campo() ?>
                    <input type="hidden" name="accion" value="guardar">
                    <input type="hidden" name="id_categoria" value="<?= (int) $categoria['id_categoria'] ?>">
                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-bold">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" maxlength="100"
                               value="<?= e($categoria['nombre']) ?>" required>
                        <div class="invalid-feedback">El nombre es obligatorio.</div>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea id="descripcion" name="descripcion" class="form-control" rows="2" maxlength="500"><?= e($categoria['descripcion']) ?></textarea>
                    </div>
                    <div class="d-flex justify-content-between">
                        <?php if ($categoria['id_categoria']): ?>
                            <a href="categorias.php" class="btn btn-secondary">Cancelar</a>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th class="text-center">Productos activos</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($categorias as $c): ?>
                        <tr class="<?= $c['estado'] ? '' : 'text-muted' ?>">
                            <td class="fw-semibold"><?= e($c['nombre']) ?></td>
                            <td class="small"><?= e($c['descripcion']) ?></td>
                            <td class="text-center"><?= (int) $c['productos'] ?></td>
                            <td>
                                <span class="badge <?= $c['estado'] ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $c['estado'] ? 'Activa' : 'Inactiva' ?>
                                </span>
                            </td>
                            <td class="text-end acciones">
                                <a href="categorias.php?editar=<?= (int) $c['id_categoria'] ?>" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="categorias.php" class="d-inline"
                                      <?= $c['estado'] ? 'data-confirmar="¿Desactivar la categoría «' . e($c['nombre']) . '»?"' : '' ?>>
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="id_categoria" value="<?= (int) $c['id_categoria'] ?>">
                                    <?php if ($c['estado']): ?>
                                        <input type="hidden" name="accion" value="desactivar">
                                        <button class="btn btn-sm btn-danger" title="Desactivar"><i class="bi bi-slash-circle"></i></button>
                                    <?php else: ?>
                                        <input type="hidden" name="accion" value="reactivar">
                                        <button class="btn btn-sm btn-outline-success" title="Reactivar"><i class="bi bi-arrow-counterclockwise"></i></button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
