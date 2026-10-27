<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $clave  = trim($_POST['clave'] ?? '');

    if (!empty($correo) && !empty($clave)) {
        $stmt = $pdo->prepare("SELECT * FROM usuario WHERE correo = ? AND estado = 1");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($clave, $usuario['clave'])) {
            $_SESSION['usuario_id'] = $usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Credenciales incorrectas o usuario inactivo.";
        }
    } else {
        $error = "Por favor ingrese correo y contraseña.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow-sm p-4" style="max-width: 400px; width: 100%;">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-primary">TecnoStock</h3>
            <p class="text-muted">Sistema de Gestión de Inventario</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="correo" class="form-label fw-semibold">Correo electrónico</label>
                <input type="email" name="correo" id="correo" class="form-control" placeholder="admin@tecnostock.cl" required>
            </div>
            <div class="mb-3">
                <label for="clave" class="form-label fw-semibold">Contraseña</label>
                <input type="password" name="clave" id="clave" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Ingresar al sistema</button>
        </form>
    </div>
</body>
</html>