<?php
/**
 * Encabezado y menú común. Antes de incluirlo, definir:
 *   $titulo  -> título de la pestaña
 *   $activa  -> 'productos' | 'movimientos' | 'categorias'
 */
$titulo = $titulo ?? 'TecnoStock';
$activa = $activa ?? '';
$flash  = get_flash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/estilos.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-box-seam-fill me-1"></i> TecnoStock</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $activa === 'productos' ? 'active' : '' ?>" href="index.php">Productos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activa === 'movimientos' ? 'active' : '' ?>" href="movimientos.php">Movimientos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activa === 'categorias' ? 'active' : '' ?>" href="categorias.php">Categorías</a>
                </li>
            </ul>
            <div class="d-flex align-items-center text-white">
                <span class="me-3"><i class="bi bi-person-circle"></i> <?= e($_SESSION['usuario_nombre'] ?? '') ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
            </div>
        </div>
    </div>
</nav>

<main class="container pb-5">
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['tipo']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['mensaje']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>
