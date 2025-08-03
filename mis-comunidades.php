<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
verificarLogin();

$idUsuario = $_SESSION['idUsuario'];
$nombre = $_SESSION['nombre'];

// Obtener solo las comunidades a las que pertenece el usuario
$query = "SELECT c.*, u.nombre as creador, 
         (SELECT COUNT(*) FROM comentarios WHERE idComunidad = c.idComunidad) as total_comentarios,
         uc.rolEnComunidad, uc.fechaUnion
         FROM usuario_comunidad uc
         INNER JOIN comunidad c ON uc.idComunidad = c.idComunidad
         INNER JOIN usuarios u ON c.idUsuario = u.idUsuario
         WHERE uc.idUsuario = ?
         ORDER BY uc.fechaUnion DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $idUsuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Verificar si hay un mensaje de éxito
$mensaje = "";
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']); // Limpiar el mensaje después de mostrarlo
}

include 'includes/header.php';
?>

<style>
.community-card {
    transition: transform 0.3s, box-shadow 0.3s;
    border-radius: 10px;
    overflow: hidden;
    height: 100%;
    border: none;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.community-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
}

.community-header {
    background-image: linear-gradient(to right, #7474BF 0%, #348AC7 100%);
    padding: 40px 0;
    margin-bottom: 30px;
    color: white;
}

.badge-role {
    position: absolute;
    top: 15px;
    right: 15px;
    z-index: 10;
    font-size: 0.8rem;
    padding: 5px 10px;
    border-radius: 20px;
}

.badge-role.creador {
    background-color: #ffc107;
    color: #212529;
}

.badge-role.administrador {
    background-color: #6f42c1;
    color: white;
}

.badge-role.moderador {
    background-color: #20c997;
    color: white;
}

.badge-role.miembro {
    background-color: #0d6efd;
    color: white;
}

.community-placeholder {
    height: 120px;
    background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
    background-size: 400% 400%;
    animation: gradient 15s ease infinite;
}

@keyframes gradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state .icon {
    font-size: 60px;
    margin-bottom: 20px;
    color: #dee2e6;
}

.stats-badge {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    margin-right: 10px;
}
</style>

<!-- Cabecera de mis comunidades -->
<section class="community-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fw-bold mb-3">Mis Comunidades</h1>
                <p class="lead mb-0">Comunidades a las que perteneces y en las que participas activamente</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="d-flex justify-content-md-end mt-3 mt-md-0">
                    <span class="stats-badge">
                        <i class="fas fa-users me-2"></i> <?php echo mysqli_num_rows($result); ?> comunidades
                    </span>
                    <a href="crear-comunidad.php" class="btn btn-light">
                        <i class="fas fa-plus me-2"></i>Nueva comunidad
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo (strpos($mensaje, 'Error') !== false) ? 'danger' : 'success'; ?> alert-dismissible fade show mb-4">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-9">
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <div class="row">
                    <?php while ($comunidad = mysqli_fetch_assoc($result)): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card community-card h-100">
                            <!-- Insignia de rol -->
                            <span class="badge badge-role <?php echo strtolower($comunidad['rolEnComunidad']); ?>">
                                <?php echo ucfirst($comunidad['rolEnComunidad']); ?>
                            </span>
                            
                            <!-- Imagen de fondo -->
                            <div class="card-img-top community-placeholder"></div>
                            
                            <!-- Contenido de la comunidad -->
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <?php echo generarAvatar($comunidad['creador'], 'sm'); ?>
                                    <div class="ms-2">
                                        <small class="text-muted">Creado por</small>
                                        <div class="small fw-bold"><?php echo htmlspecialchars($comunidad['creador']); ?></div>
                                    </div>
                                </div>
                                
                                <h5 class="card-title">
                                    <a href="comunidad.php?id=<?php echo $comunidad['idComunidad']; ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($comunidad['titulo']); ?>
                                    </a>
                                </h5>
                                <p class="card-text small text-muted">
                                    <?php 
                                        $descripcion = $comunidad['descripcion'];
                                        echo strlen($descripcion) > 80 ? substr(htmlspecialchars($descripcion), 0, 80) . '...' : htmlspecialchars($descripcion); 
                                    ?>
                                </p>
                            </div>
                            
                            <!-- Footer con estadísticas -->
                            <div class="card-footer bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Te uniste el <?php echo formatoFecha($comunidad['fechaUnion']); ?>
                                    </small>
                                    <span class="badge bg-light text-dark">
                                        <i class="far fa-comment me-1"></i> <?php echo $comunidad['total_comentarios']; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Enlace para ir a la comunidad -->
                            <a href="comunidad.php?id=<?php echo $comunidad['idComunidad']; ?>" class="stretched-link"></a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">
                        <i class="fas fa-users-slash"></i>
                    </div>
                    <h3 class="mb-3">Aún no perteneces a ninguna comunidad</h3>
                    <p class="text-muted mb-4">Unirse a comunidades te permitirá conectar con otros usuarios, compartir ideas y participar en conversaciones sobre temas que te interesan.</p>
                    <a href="comunidades.php" class="btn btn-primary me-2">Explorar comunidades</a>
                    <a href="crear-comunidad.php" class="btn btn-outline-primary">Crear mi comunidad</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-3">
            <!-- Perfil del usuario -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body text-center">
                    <?php echo generarAvatar($nombre, 'md'); ?>
                    <h5 class="mt-3 mb-0"><?php echo htmlspecialchars($nombre); ?></h5>
                    <p class="text-muted small mb-3">
                        <?php
                        // Determinar el rol más alto del usuario
                        $rolPrincipal = "Miembro";
                        if ($result && mysqli_num_rows($result) > 0) {
                            mysqli_data_seek($result, 0); // Reiniciar el puntero
                            while ($comunidad = mysqli_fetch_assoc($result)) {
                                if ($comunidad['rolEnComunidad'] == 'creador') {
                                    $rolPrincipal = "Creador";
                                    break;
                                } elseif ($comunidad['rolEnComunidad'] == 'administrador' && $rolPrincipal != "Creador") {
                                    $rolPrincipal = "Administrador";
                                } elseif ($comunidad['rolEnComunidad'] == 'moderador' && $rolPrincipal != "Creador" && $rolPrincipal != "Administrador") {
                                    $rolPrincipal = "Moderador";
                                }
                            }
                            mysqli_data_seek($result, 0); // Reiniciar el puntero de nuevo
                        }
                        echo $rolPrincipal;
                        ?>
                    </p>
                    
                    <div class="d-grid">
                        <a href="perfil.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-user-edit me-2"></i>Editar perfil
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas de participación -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Mi actividad</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Comunidades</span>
                        <span class="badge bg-primary rounded-pill">
                            <?php echo mysqli_num_rows($result); ?>
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Comentarios</span>
                        <span class="badge bg-info rounded-pill">
                            <?php
                            // Contar comentarios del usuario
                            $queryComentarios = "SELECT COUNT(*) as total FROM comentarios WHERE idUsuario = ?";
                            $stmtComentarios = mysqli_prepare($conn, $queryComentarios);
                            mysqli_stmt_bind_param($stmtComentarios, "i", $idUsuario);
                            mysqli_stmt_execute($stmtComentarios);
                            $resultComentarios = mysqli_stmt_get_result($stmtComentarios);
                            $comentarios = mysqli_fetch_assoc($resultComentarios);
                            echo $comentarios['total'];
                            ?>
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Rol más alto</span>
                        <span class="badge bg-warning text-dark rounded-pill">
                            <?php echo $rolPrincipal; ?>
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <span>Comunidades creadas</span>
                        <span class="badge bg-success rounded-pill">
                            <?php
                            // Contar comunidades creadas por el usuario
                            $queryCreadas = "SELECT COUNT(*) as total FROM comunidad WHERE idUsuario = ?";
                            $stmtCreadas = mysqli_prepare($conn, $queryCreadas);
                            mysqli_stmt_bind_param($stmtCreadas, "i", $idUsuario);
                            mysqli_stmt_execute($stmtCreadas);
                            $resultCreadas = mysqli_stmt_get_result($stmtCreadas);
                            $creadas = mysqli_fetch_assoc($resultCreadas);
                            echo $creadas['total'];
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Comandos rápidos -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Acciones rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="comunidades.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-compass me-2"></i>Explorar comunidades
                        </a>
                        <a href="crear-comunidad.php" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-plus me-2"></i>Crear comunidad
                        </a>
                        <a href="perfil.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-user me-2"></i>Volver a mi perfil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>