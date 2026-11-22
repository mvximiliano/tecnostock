<?php
/**
 * Funciones comunes: sesión, conexión, seguridad y mensajes.
 * Todas las páginas incluyen este archivo al inicio.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/** Redirige al login si no hay un usuario autenticado. */
function requerir_login(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

/** Escapa texto antes de mostrarlo en HTML (evita XSS). */
function e($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Formatea un precio en pesos chilenos. */
function pesos($valor): string
{
    return '$' . number_format((float) $valor, 0, ',', '.');
}

/** Guarda un mensaje para mostrarlo en la siguiente página (success, danger, warning, info). */
function set_flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

/** Obtiene y elimina el mensaje pendiente. */
function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/** Token CSRF: protege los formularios POST contra envíos desde otros sitios. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_valido(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

/** Regla de negocio: hay alerta cuando el stock actual es menor que el stock mínimo. */
function stock_bajo(array $producto): bool
{
    return (int) $producto['stock_actual'] < (int) $producto['stock_minimo'];
}

/** Largo de un texto contando tildes y ñ como un carácter (funciona aunque falte mbstring). */
function largo(string $texto): int
{
    return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : (int) preg_match_all('/./us', $texto);
}
