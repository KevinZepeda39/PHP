<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
verificarLogin();

$idUsuario = $_SESSION['idUsuario'];

// Verificar que se recibieron los datos necesarios
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

// Validar CSRF token
if (!isset($_GET['token']) || !verificarTokenCSRF($_GET['token'])) {
    $_SESSION['mensaje'] = "Error de seguridad. Por favor, intente nuevamente.";
    header("Location: index.php");
    exit();
}

$idUsuarioSeguido = $_GET['id'];

// Verificar que no está intentando seguirse a sí mismo
if ($idUsuario == $idUsuarioSeguido) {
    $_SESSION['mensaje'] = "No puedes seguirte a ti mismo.";
    header("Location: index.php");
    exit();
}

// Verificar que el usuario a seguir existe
$queryUsuario = "SELECT idUsuario, nombre FROM usuarios WHERE idUsuario = ?";
$stmtUsuario = mysqli_prepare($conn, $queryUsuario);
mysqli_stmt_bind_param($stmtUsuario, "i", $idUsuarioSeguido);
mysqli_stmt_execute($stmtUsuario);
$resultUsuario = mysqli_stmt_get_result($stmtUsuario);

if (mysqli_num_rows($resultUsuario) != 1) {
    $_SESSION['mensaje'] = "El usuario que intentas seguir no existe.";
    header("Location: index.php");
    exit();
}

$usuario = mysqli_fetch_assoc($resultUsuario);

// Verificar si ya existe la tabla 'seguidores'
$queryCheckTable = "SHOW TABLES LIKE 'seguidores'";
$resultCheckTable = mysqli_query($conn, $queryCheckTable);

if (mysqli_num_rows($resultCheckTable) == 0) {
    // Crear la tabla si no existe
    $queryCreateTable = "CREATE TABLE seguidores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seguidor_id INT NOT NULL,
        seguido_id INT NOT NULL,
        fecha_seguimiento DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_seguidor_seguido (seguidor_id, seguido_id),
        FOREIGN KEY (seguidor_id) REFERENCES usuarios(idUsuario) ON DELETE CASCADE,
        FOREIGN KEY (seguido_id) REFERENCES usuarios(idUsuario) ON DELETE CASCADE
    )";
    
    if (!mysqli_query($conn, $queryCreateTable)) {
        $_SESSION['mensaje'] = "Error al crear la funcionalidad de seguimiento: " . mysqli_error($conn);
        header("Location: index.php");
        exit();
    }
}

// Verificar si ya sigue a este usuario
$queryVerificar = "SELECT * FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?";
$stmtVerificar = mysqli_prepare($conn, $queryVerificar);
mysqli_stmt_bind_param($stmtVerificar, "ii", $idUsuario, $idUsuarioSeguido);
mysqli_stmt_execute($stmtVerificar);
$resultVerificar = mysqli_stmt_get_result($stmtVerificar);

if (mysqli_num_rows($resultVerificar) > 0) {
    // Si ya lo sigue, dejar de seguir
    $queryDejarSeguir = "DELETE FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?";
    $stmtDejarSeguir = mysqli_prepare($conn, $queryDejarSeguir);
    mysqli_stmt_bind_param($stmtDejarSeguir, "ii", $idUsuario, $idUsuarioSeguido);
    
    if (mysqli_stmt_execute($stmtDejarSeguir)) {
        $_SESSION['mensaje'] = "Has dejado de seguir a " . htmlspecialchars($usuario['nombre']) . ".";
    } else {
        $_SESSION['mensaje'] = "Error al dejar de seguir: " . mysqli_error($conn);
    }
} else {
    // Si no lo sigue, comenzar a seguir
    $querySeguir = "INSERT INTO seguidores (seguidor_id, seguido_id) VALUES (?, ?)";
    $stmtSeguir = mysqli_prepare($conn, $querySeguir);
    mysqli_stmt_bind_param($stmtSeguir, "ii", $idUsuario, $idUsuarioSeguido);
    
    if (mysqli_stmt_execute($stmtSeguir)) {
        $_SESSION['mensaje'] = "Has comenzado a seguir a " . htmlspecialchars($usuario['nombre']) . ".";
    } else {
        $_SESSION['mensaje'] = "Error al seguir: " . mysqli_error($conn);
    }
}

// Redirigir de vuelta a la página anterior o al índice
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: $referer");
exit();