<?php
/**
 * Conexión a MySQL mediante PDO.
 * Valores por defecto de XAMPP (usuario root sin contraseña).
 */
$host    = 'localhost';
$db      = 'tecnostock';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Los errores lanzan excepciones
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Resultados como arreglos asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Consultas preparadas reales
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('Error de conexión: ' . $e->getMessage());
    http_response_code(500);
    die('No fue posible conectar con la base de datos. Verifique que MySQL esté iniciado en XAMPP y que exista la base "tecnostock".');
}
