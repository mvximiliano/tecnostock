<?php
// Cierra la sesión del usuario y vuelve al login
session_start();
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
