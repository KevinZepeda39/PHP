<?php
include '../includes/config.php';
include '../includes/funciones.php';
// Verificar que sea administrador
verificarSesionAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id <= 0) {
    header('Location: publicaciones.php?modo=comunidades');
    exit;
}

// Eliminar la comunidad y todos sus datos relacionados
// Nota: Esto depende de cómo esté configurada tu base de datos (ON DELETE CASCADE o no)

// Primero eliminamos los comentarios de las publicaciones de esta comunidad
$query1 = "DELETE comentarios FROM comentarios 
          INNER JOIN publicaciones ON comentarios.id_publicacion = publicaciones.id 
          WHERE publicaciones.id_comunidad = $id";
mysqli_query($conexion, $query1);

// Eliminamos los likes de las publicaciones
$query2 = "DELETE likes FROM likes 
          INNER JOIN publicaciones ON likes.id_publicacion = publicaciones.id 
          WHERE publicaciones.id_comunidad = $id";
mysqli_query($conexion, $query2);

// Eliminamos las publicaciones
$query3 = "DELETE FROM publicaciones WHERE id_comunidad = $id";
mysqli_query($conexion, $query3);

// Eliminamos los miembros de la comunidad
$query4 = "DELETE FROM miembros_comunidad WHERE id_comunidad = $id";
mysqli_query($conexion, $query4);

// Finalmente eliminamos la comunidad
$query5 = "DELETE FROM comunidades WHERE id = $id";
mysqli_query($conexion, $query5);

header('Location: publicaciones.php?modo=comunidades&mensaje=eliminado');
exit;