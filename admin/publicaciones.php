<?php
session_start();
require_once '../includes/conexion.php';
require_once '../includes/functions.php';

// Verificar si el usuario es administrador
if (!isset($_SESSION['esAdmin']) || $_SESSION['esAdmin'] != 1) {
    header('Location: ../index.php');
    exit;
}

// Determinar qué modo de visualización usar
$modo = isset($_GET['modo']) ? $_GET['modo'] : 'comunidades';
$id_comunidad = isset($_GET['comunidad']) ? intval($_GET['comunidad']) : 0;

// Incluir el header de administración
include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Barra lateral del admin (si existe) -->
        <?php if (file_exists('../includes/admin-sidebar.php')): ?>
            <?php include '../includes/admin-sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <?php else: ?>
            <main class="col-12 px-4">
        <?php endif; ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Administración de Comunidades</h1>
            </div>

            <!-- Alerta de mensaje si existe -->
            <?php if(isset($_GET['mensaje'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    $mensaje = $_GET['mensaje'];
                    switch($mensaje) {
                        case 'actualizado':
                            echo 'La comunidad se ha actualizado correctamente.';
                            break;
                        case 'eliminado':
                            echo 'La comunidad se ha eliminado correctamente.';
                            break;
                        default:
                            echo 'Operación realizada con éxito.';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Menú de navegación entre modos -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a href="?modo=comunidades" class="nav-link <?php echo $modo == 'comunidades' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Comunidades
                    </a>
                </li>
                <?php if($id_comunidad > 0): ?>
                <li class="nav-item">
                    <a href="?modo=publicaciones&comunidad=<?php echo $id_comunidad; ?>" class="nav-link <?php echo $modo == 'publicaciones' ? 'active' : ''; ?>">
                        <i class="fas fa-file-alt"></i> Publicaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?modo=comentarios&comunidad=<?php echo $id_comunidad; ?>" class="nav-link <?php echo $modo == 'comentarios' ? 'active' : ''; ?>">
                        <i class="fas fa-comments"></i> Comentarios
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <?php if($modo == 'comunidades'): ?>
                <!-- LISTADO DE COMUNIDADES -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-users"></i> Comunidades Existentes
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th>Creador</th>
                                        <th>Miembros</th>
                                        <th>Comentarios</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Obtener todas las comunidades con detalles adicionales
                                    $query = "SELECT c.*, u.nombre as creador, 
                                             (SELECT COUNT(*) FROM comentarios WHERE idComunidad = c.idComunidad) as total_comentarios,
                                             (SELECT COUNT(*) FROM usuario_comunidad WHERE idComunidad = c.idComunidad) as total_miembros
                                             FROM comunidad c 
                                             INNER JOIN usuarios u ON c.idUsuario = u.idUsuario
                                             ORDER BY c.fechaCreacion DESC";
                                    $result = mysqli_query($conn, $query);
                                    
                                    if ($result && mysqli_num_rows($result) > 0) {
                                        while($comunidad = mysqli_fetch_assoc($result)): 
                                    ?>
                                        <tr>
                                            <td><?php echo $comunidad['idComunidad']; ?></td>
                                            <td><?php echo htmlspecialchars($comunidad['titulo']); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php 
                                                    $categoria = $comunidad['categoria'] ?? 'otros';
                                                    switch($categoria) {
                                                        case 'vecinos': echo 'bg-danger'; break;
                                                        case 'seguridad': echo 'bg-info'; break;
                                                        case 'eventos': echo 'bg-warning text-dark'; break;
                                                        case 'infraestructura': echo 'bg-primary'; break;
                                                        case 'servicios': echo 'bg-success'; break;
                                                        default: echo 'bg-secondary';
                                                    }
                                                    ?>">
                                                    <?php 
                                                    $categorias = [
                                                        'vecinos' => 'Vecinos',
                                                        'seguridad' => 'Seguridad',
                                                        'eventos' => 'Eventos',
                                                        'infraestructura' => 'Infraestructura',
                                                        'servicios' => 'Servicios',
                                                        'otros' => 'Otros'
                                                    ];
                                                    echo $categorias[$categoria] ?? 'Otros'; 
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($comunidad['creador']); ?></td>
                                            <td><span class="badge bg-primary"><?php echo $comunidad['total_miembros']; ?></span></td>
                                            <td><span class="badge bg-secondary"><?php echo $comunidad['total_comentarios']; ?></span></td>
                                            <td><?php echo date('d/m/Y', strtotime($comunidad['fechaCreacion'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="?modo=publicaciones&comunidad=<?php echo $comunidad['idComunidad']; ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="editar-comunidad.php?id=<?php echo $comunidad['idComunidad']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="eliminar-comunidad.php?id=<?php echo $comunidad['idComunidad']; ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('¿Está seguro de eliminar esta comunidad? Esta acción no se puede deshacer.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    } else {
                                        echo '<tr><td colspan="8" class="text-center">No hay comunidades registradas</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            
            <?php elseif($modo == 'publicaciones' && $id_comunidad > 0): ?>
                <!-- LISTADO DE PUBLICACIONES DE UNA COMUNIDAD -->
                <?php
                // Obtener información de la comunidad
                $query_com = "SELECT titulo FROM comunidad WHERE idComunidad = $id_comunidad";
                $resultado_com = mysqli_query($conn, $query_com);
                $comunidad = mysqli_fetch_assoc($resultado_com);
                ?>
                
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-file-alt"></i> Publicaciones de la comunidad: <?php echo htmlspecialchars($comunidad['titulo']); ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Título</th>
                                        <th>Autor</th>
                                        <th>Contenido</th>
                                        <th>Fecha</th>
                                        <th>Comentarios</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Consulta para obtener las publicaciones de la comunidad
                                    $query = "SELECT p.*, u.nombre as nombre_usuario, 
                                             (SELECT COUNT(*) FROM comentarios WHERE idPublicacion = p.idPublicacion) as total_comentarios
                                             FROM publicaciones p 
                                             JOIN usuarios u ON p.idUsuario = u.idUsuario
                                             WHERE p.idComunidad = $id_comunidad
                                             ORDER BY p.fechaCreacion DESC";
                                    $resultado = mysqli_query($conn, $query);
                                    
                                    if ($resultado && mysqli_num_rows($resultado) > 0) {
                                        while($publicacion = mysqli_fetch_assoc($resultado)): 
                                        ?>
                                        <tr>
                                            <td><?php echo $publicacion['idPublicacion']; ?></td>
                                            <td><?php echo htmlspecialchars($publicacion['titulo']); ?></td>
                                            <td><?php echo htmlspecialchars($publicacion['nombre_usuario']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($publicacion['contenido'], 0, 100)) . '...'; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($publicacion['fechaCreacion'])); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo $publicacion['total_comentarios']; ?></span></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="editar-publicacion.php?id=<?php echo $publicacion['idPublicacion']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="eliminar-publicacion.php?id=<?php echo $publicacion['idPublicacion']; ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('¿Está seguro de eliminar esta publicación?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile;
                                    } else {
                                        echo '<tr><td colspan="7" class="text-center">No hay publicaciones en esta comunidad</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            <?php elseif($modo == 'comentarios' && $id_comunidad > 0): ?>
                <!-- LISTADO DE COMENTARIOS DE LAS PUBLICACIONES DE UNA COMUNIDAD -->
                <?php
                // Obtener información de la comunidad
                $query_com = "SELECT titulo FROM comunidad WHERE idComunidad = $id_comunidad";
                $resultado_com = mysqli_query($conn, $query_com);
                $comunidad = mysqli_fetch_assoc($resultado_com);
                ?>
                
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <i class="fas fa-comments"></i> Comentarios de la comunidad: <?php echo htmlspecialchars($comunidad['titulo']); ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Publicación</th>
                                        <th>Usuario</th>
                                        <th>Comentario</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $query = "SELECT c.*, u.nombre as nombre_usuario, p.titulo as titulo_publicacion 
                                              FROM comentarios c 
                                              JOIN usuarios u ON c.idUsuario = u.idUsuario 
                                              JOIN publicaciones p ON c.idPublicacion = p.idPublicacion 
                                              WHERE p.idComunidad = $id_comunidad
                                              ORDER BY c.fecha DESC";
                                    $resultado = mysqli_query($conn, $query);
                                    
                                    if ($resultado && mysqli_num_rows($resultado) > 0) {
                                        while($comentario = mysqli_fetch_assoc($resultado)): 
                                        ?>
                                        <tr>
                                            <td><?php echo $comentario['idComentario']; ?></td>
                                            <td><?php echo htmlspecialchars($comentario['titulo_publicacion']); ?></td>
                                            <td><?php echo htmlspecialchars($comentario['nombre_usuario']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($comentario['contenido'], 0, 100)) . '...'; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($comentario['fecha'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="editar-comentario.php?id=<?php echo $comentario['idComentario']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="eliminar-comentario.php?id=<?php echo $comentario['idComentario']; ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('¿Está seguro de eliminar este comentario?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile;
                                    } else {
                                        echo '<tr><td colspan="6" class="text-center">No hay comentarios en esta comunidad</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Bootstrap JS y otros scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script>
// JavaScript para manejar la interacción del usuario
document.addEventListener('DOMContentLoaded', function() {
    // Código de inicialización si es necesario
});
</script>

<?php include '../includes/footer.php'; ?>