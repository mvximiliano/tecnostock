<?php
require_once __DIR__ . '/includes/funciones.php';

// Si ya inició sesión, va directo al inventario
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error  = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $clave  = $_POST['clave'] ?? '';

    // Validación en servidor (se repite la del navegador)
    if ($correo === '' || $clave === '') {
        $error = 'Por favor ingrese correo y contraseña.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no tiene un formato válido.';
    } else {
        $stmt = $pdo->prepare('SELECT id_usuario, nombre, clave FROM usuario WHERE correo = ? AND estado = 1');
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        // La contraseña se guarda cifrada (password_hash) y se compara con password_verify
        if ($usuario && password_verify($clave, $usuario['clave'])) {
            session_regenerate_id(true); // Evita fijación de sesión
            $_SESSION['usuario_id']     = $usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            header('Location: index.php');
            exit;
        }
        $error = 'Credenciales incorrectas o usuario inactivo.';
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .login-card {
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.98);
        }
        .brand-icon {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white; border-radius: 1rem;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.4);
        }
        .form-control {
            border-radius: 0.75rem;
            padding: 0.75rem 1rem 0.75rem 2.8rem;
            border: 1px solid #cbd5e1;
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }
        .input-icon {
            position: absolute; left: 1rem; top: 1.45rem;
            transform: translateY(-50%);
            color: #64748b; font-size: 1.1rem; z-index: 4;
        }
        .btn-custom {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none; border-radius: 0.75rem; padding: 0.8rem; font-weight: 600;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .btn-custom:hover { background: linear-gradient(135deg, #1d4ed8, #1e40af); }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100 m-0">

<div class="container p-3">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
            <div class="card login-card p-4 p-sm-5">

                <div class="text-center mb-4">
                    <div class="brand-icon mb-3"><i class="bi bi-box-seam-fill"></i></div>
                    <h3 class="fw-bold text-dark mb-1">TecnoStock</h3>
                    <p class="text-muted small">Sistema de Gestión de Inventario</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 shadow-sm rounded-3 py-2 px-3 small d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" id="form-login" novalidate>
                    <div class="mb-3">
                        <label for="correo" class="form-label fw-semibold text-secondary small">Correo electrónico</label>
                        <div class="position-relative">
                            <i class="bi bi-envelope input-icon"></i>
                            <input type="email" name="correo" id="correo" class="form-control"
                                   placeholder="admin@tecnostock.cl" value="<?= e($correo) ?>" required autofocus>
                            <div class="invalid-feedback">Ingrese un correo válido.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="clave" class="form-label fw-semibold text-secondary small">Contraseña</label>
                        <div class="position-relative">
                            <i class="bi bi-lock input-icon"></i>
                            <input type="password" name="clave" id="clave" class="form-control" placeholder="••••••••" required>
                            <div class="invalid-feedback">Ingrese su contraseña.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-custom w-100 text-white">
                        Ingresar al sistema <i class="bi bi-arrow-right-short ms-1 fs-5 align-middle"></i>
                    </button>
                </form>

                <div class="text-center mt-4">
                    <small class="text-muted">&copy; <?= date('Y') ?> TecnoStock</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/validaciones.js"></script>
</body>
</html>
