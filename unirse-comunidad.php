<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
verificarLogin();

// Verificar si se proporcionó un ID de comunidad
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: comunidades.php");
    exit();
}

$idComunidad = $_GET['id'];
$idUsuario = $_SESSION['idUsuario'];
$token = $_GET['token'] ?? '';

// Verificar token CSRF
if (!verificarTokenCSRF($token)) {
    $_SESSION['mensaje'] = "Error de seguridad. Por favor, intente nuevamente.";
    header("Location: comunidad.php?id=$idComunidad");
    exit();
}

// Verificar que la comunidad existe
$query = "SELECT idComunidad, titulo FROM comunidad WHERE idComunidad = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $idComunidad);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['mensaje'] = "La comunidad seleccionada no existe.";
    header("Location: comunidades.php");
    exit();
}

$comunidad = mysqli_fetch_assoc($result);
$tituloComunidad = $comunidad['titulo'];

// Verificar si el usuario ya es miembro de la comunidad
$queryMiembro = "SELECT * FROM usuario_comunidad WHERE idUsuario = ? AND idComunidad = ?";
$stmtMiembro = mysqli_prepare($conn, $queryMiembro);
mysqli_stmt_bind_param($stmtMiembro, "ii", $idUsuario, $idComunidad);
mysqli_stmt_execute($stmtMiembro);
$resultMiembro = mysqli_stmt_get_result($stmtMiembro);

if (mysqli_num_rows($resultMiembro) > 0) {
    // Si ya es miembro, se desvincula (dejar de seguir)
    $queryDelete = "DELETE FROM usuario_comunidad WHERE idUsuario = ? AND idComunidad = ?";
    $stmtDelete = mysqli_prepare($conn, $queryDelete);
    mysqli_stmt_bind_param($stmtDelete, "ii", $idUsuario, $idComunidad);
    
    if (mysqli_stmt_execute($stmtDelete)) {
        $_SESSION['mensaje'] = "Has dejado de seguir la comunidad \"" . htmlspecialchars($tituloComunidad) . "\".";
    } else {
        $_SESSION['mensaje'] = "Error al dejar de seguir la comunidad: " . mysqli_error($conn);
    }
} else {
    // Si no es miembro, se une
    $queryInsert = "INSERT INTO usuario_comunidad (idUsuario, idComunidad, rolEnComunidad, fechaUnion) VALUES (?, ?, 'miembro', NOW())";
    $stmtInsert = mysqli_prepare($conn, $queryInsert);
    mysqli_stmt_bind_param($stmtInsert, "ii", $idUsuario, $idComunidad);
    
    if (mysqli_stmt_execute($stmtInsert)) {
        $_SESSION['mensaje'] = "Te has unido a la comunidad \"" . htmlspecialchars($tituloComunidad) . "\" correctamente.";
    } else {
        $_SESSION['mensaje'] = "Error al unirse a la comunidad: " . mysqli_error($conn);
    }
}

// Redireccionar de vuelta a la página de la comunidad
header("Location: comunidad.php?id=$idComunidad");
exit();