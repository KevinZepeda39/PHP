<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
verificarLogin();

$idUsuario = $_SESSION['idUsuario'];
$mensaje = "";

// Verificar que se recibieron los datos necesarios
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['id_comentario']) || !isset($_POST['comentario'])) {
    header("Location: comentarios.php");
    exit();
}

// Validar CSRF token
if (!isset($_POST['csrf_token']) || !verificarTokenCSRF($_POST['csrf_token'])) {
    $_SESSION['mensaje'] = "Error de seguridad. Por favor, intente nuevamente.";
    header("Location: comentarios.php");
    exit();
}

$idComentario = $_POST['id_comentario'];
$nuevoComentario = trim($_POST['comentario']);

// Verificar que el comentario no esté vacío
if (empty($nuevoComentario)) {
    $_SESSION['mensaje'] = "El comentario no puede estar vacío.";
    header("Location: comentarios.php");
    exit();
}

// Verificar que el usuario sea el propietario del comentario
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

// Verificar que el usuario actual es el autor del comentario
if ($comentarioData['idUsuario'] != $idUsuario) {
    $_SESSION['mensaje'] = "No tienes permiso para editar este comentario.";
    header("Location: comentarios.php");
    exit();
}

// Actualizar el comentario
$queryUpdate = "UPDATE comentarios_globales SET comentario = ? WHERE id = ? AND idUsuario = ?";
$stmtUpdate = mysqli_prepare($conn, $queryUpdate);
mysqli_stmt_bind_param($stmtUpdate, "sii", $nuevoComentario, $idComentario, $idUsuario);

if (mysqli_stmt_execute($stmtUpdate)) {
    $_SESSION['mensaje'] = "Comentario actualizado correctamente.";
} else {
    $_SESSION['mensaje'] = "Error al actualizar el comentario: " . mysqli_error($conn);
}

// Redirigir de vuelta a la página de comentarios
header("Location: comentarios.php");
exit();