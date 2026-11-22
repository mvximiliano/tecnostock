<?php
/**
 * Desactiva o reactiva un producto (borrado lógico).
 * Regla de negocio: los productos NO se eliminan físicamente; se cambia estado a 0.
 * Así se conserva su historial de movimientos.
 */
require_once __DIR__ . '/includes/funciones.php';
requerir_login();

// Solo se acepta POST con token válido (un simple enlace no puede desactivar productos)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    set_flash('danger', 'Solicitud no válida.');
    header('Location: index.php');
    exit;
}

$id     = (int) ($_POST['id'] ?? 0);
$accion = $_POST['accion'] ?? '';

if ($id <= 0 || !in_array($accion, ['desactivar', 'reactivar'], true)) {
    set_flash('danger', 'Solicitud no válida.');
    header('Location: index.php');
    exit;
}

$nuevo_estado = $accion === 'desactivar' ? 0 : 1;

$stmt = $pdo->prepare('SELECT nombre FROM producto WHERE id_producto = ?');
$stmt->execute([$id]);
$nombre = $stmt->fetchColumn();

if ($nombre === false) {
    set_flash('danger', 'El producto no existe.');
} else {
    $stmt = $pdo->prepare('UPDATE producto SET estado = ? WHERE id_producto = ?');
    $stmt->execute([$nuevo_estado, $id]);
    set_flash('success', $nuevo_estado === 0
        ? "Producto «{$nombre}» desactivado. Puede verlo en «Productos desactivados»."
        : "Producto «{$nombre}» reactivado.");
}

header('Location: index.php');
exit;
