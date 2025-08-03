<?php
$host = "localhost";
$user = "root"; // Cambiar según tu configuración
$password = "root"; // Cambiar según tu configuración
$database = "MiCiudadSv";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['idUsuario'])) {
    $_SESSION['idUsuario'] = null;
}
if (!isset($_SESSION['nombre'])) {
    $_SESSION['nombre'] = null;
}
?>