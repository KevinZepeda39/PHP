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

// Procesar formulario si se envió
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $contenido = mysqli_real_escape_string($conexion, $_POST['contenido']);
    
    $query = "UPDATE comentarios SET contenido = '$contenido' WHERE id = $id";
    
    if(mysqli_query($conexion, $query)) {
        // Obtener a qué comunidad pertenece este comentario
        $query_com = "SELECT p.id_comunidad 
                      FROM comentarios c 
                      JOIN publicaciones p ON c.id_publicacion = p.id 
                      WHERE c.id = $id";
        $resultado_com = mysqli_query($conexion, $query_com);
        $comunidad = mysqli_fetch_assoc($resultado_com);
        
        header('Location: publicaciones.php?modo=comentarios&comunidad=' . $comunidad['id_comunidad'] . '&mensaje=actualizado');
        exit;
    }
}

// Obtener datos actuales
$query = "SELECT c.*, p.id_comunidad, p.titulo as titulo_publicacion, u.nombre as nombre_usuario
          FROM comentarios c 
          JOIN publicaciones p ON c.id_publicacion = p.id 
          JOIN usuarios u ON c.id_usuario = u.id
          WHERE c.id = $id";
$resultado = mysqli_query($conexion, $query);
$comentario = mysqli_fetch_assoc($resultado);

// Incluir el header de administración
include '../includes/admin-header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Breadcrumbs-->
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="dashboard.php">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="publicaciones.php?modo=comunidades">Comunidades</a>
            </li>
            <li class="breadcrumb-item">
                <a href="publicaciones.php?modo=comentarios&comunidad=<?php echo $comentario['id_comunidad']; ?>">Comentarios</a>
            </li>
            <li class="breadcrumb-item active">Editar Comentario</li>
        </ol>

        <div class="card mb-3">
            <div class="card-header">
                <i class="fa fa-edit"></i> Editar Comentario #<?php echo $id; ?>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <p><strong>Publicación:</strong> <?php echo $comentario['titulo_publicacion']; ?></p>
                    <p><strong>Usuario:</strong> <?php echo $comentario['nombre_usuario']; ?></p>
                    <p><strong>Fecha:</strong> <?php echo $comentario['fecha']; ?></p>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="contenido"><strong>Contenido del comentario:</strong></label>
                        <textarea class="form-control" id="contenido" name="contenido" rows="5" required><?php echo $comentario['contenido']; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-save"></i> Guardar Cambios
                        </button>
                        <a href="publicaciones.php?modo=comentarios&comunidad=<?php echo $comentario['id_comunidad']; ?>" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin-sidebar.php'; ?>
<?php include '../includes/footer.php'; ?>