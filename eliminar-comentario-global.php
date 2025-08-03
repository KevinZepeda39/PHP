<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
verificarLogin();

$idUsuario = $_SESSION['idUsuario'];

// Verificar que se recibieron los datos necesarios
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: comentarios.php");
    exit();
}

// Validar CSRF token
if (!isset($_GET['token']) || !verificarTokenCSRF($_GET['token'])) {
    $_SESSION['mensaje'] = "Error de seguridad. Por favor, intente nuevamente.";
    header("Location: comentarios.php");
    exit();
}

$idComentario = $_GET['id'];

// Verificar que el usuario sea el propietario del comentario o un administrador
$queryVerificar = "SELECT idUsuario FROM comentarios_globales WHERE id = ?";
$stmtVerificar = mysqli_prepare($conn, $queryVerificar);
mysqli_stmt_bind_param($stmtVerificar, "i", $idComentario);
mysqli_stmt_execute($stmtVerificar);
$resultVerificar = mysqli_stmt_get_result($stmtVerificar);

if (mysqli_num_rows($resultVerificar) != 1) {
    $_SESSION['mensaje'] = "No se encontró el comentario.";
    header("Location: comentarios.php");
    exit();
}

$comentarioData = mysqli_fetch_assoc($resultVerificar);
$comentarioData = mysqli_fetch_assoc($resultVerificar);

// Verificar que el usuario actual es el autor del comentario o un administrador
$esAdmin = isset($_SESSION['admin']) && $_SESSION['admin'];
if ($comentarioData['idUsuario'] != $idUsuario && !$esAdmin) {
    $_SESSION['mensaje'] = "No tienes permiso para eliminar este comentario.";
    header("Location: comentarios.php");
    exit();
}

// Eliminar el comentario
$queryDelete = "DELETE FROM comentarios_globales WHERE id = ?";
$stmtDelete = mysqli_prepare($conn, $queryDelete);
mysqli_stmt_bind_param($stmtDelete, "i", $idComentario);

if (mysqli_stmt_execute($stmtDelete)) {
    $_SESSION['mensaje'] = "Comentario eliminado correctamente.";
} else {
    $_SESSION['mensaje'] = "Error al eliminar el comentario: " . mysqli_error($conn);
}

// Redirigir de vuelta a la página de comentarios
header("Location: comentarios.php");
exit();