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

// Procesar formulario si se envió
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
    
    $query = "UPDATE comunidades SET 
              nombre = '$nombre', 
              descripcion = '$descripcion'
              WHERE id = $id";
    
    if(mysqli_query($conexion, $query)) {
        header('Location: publicaciones.php?modo=comunidades&mensaje=actualizado');
        exit;
    }
}

// Obtener datos actuales
$query = "SELECT * FROM comunidades WHERE id = $id";
$resultado = mysqli_query($conexion, $query);
$comunidad = mysqli_fetch_assoc($resultado);

include 'includes/header.php';
?>

<div class="container">
    <h1>Editar Comunidad</h1>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="nombre">Nombre:</label>
            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $comunidad['nombre']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required><?php echo $comunidad['descripcion']; ?></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        <a href="publicaciones.php?modo=comunidades" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>