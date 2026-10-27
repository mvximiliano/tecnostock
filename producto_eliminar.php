<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/database.php';

$id = $_GET['id'] ?? null;

if ($id) {
    // Soft Delete: cambia el estado a 0 (desactivado)
    $stmt = $pdo->prepare("UPDATE producto SET estado = 0 WHERE id_producto = ?");
    $stmt->execute([$id]);
}

header("Location: index.php");
exit;
?>