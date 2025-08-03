<?php
require_once '../includes/conexion.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Verificar permisos (si tienes una función de verificación)

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id <= 0) {
    header('Location: publicaciones.php?modo=comunidades');
    exit;
}

// Obtener a qué comunidad pertenece este comentario
$query_com = "SELECT p.id_comunidad 
              FROM comentarios c 
              JOIN publicaciones p ON c.id_publicacion = p.id 
              WHERE c.id = $id";
$resultado_com = mysqli_query($conexion, $query_com);

if($resultado_com && mysqli_num_rows($resultado_com) > 0) {
    $comunidad = mysqli_fetch_assoc($resultado_com);
    $id_comunidad = $comunidad['id_comunidad'];
    
    // Eliminar el comentario
    $query = "DELETE FROM comentarios WHERE id = $id";
    if(mysqli_query($conexion, $query)) {
        header('Location: publicaciones.php?modo=comentarios&comunidad=' . $id_comunidad . '&mensaje=eliminado');
        exit;
    } else {
        // Error al eliminar
        header('Location: publicaciones.php?modo=comentarios&comunidad=' . $id_comunidad . '&error=1');
        exit;
    }
} else {
    // No se encontró el comentario
    header('Location: publicaciones.php?modo=comunidades&error=2');
    exit;
}