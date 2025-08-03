<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/functions.php';
require_once 'includes/models/reporte.php';

// Verificar si el usuario está autenticado
verificarLogin();

$idUsuario = $_SESSION['idUsuario'];
$nombre = $_SESSION['nombre'];

// Determinar qué pestaña mostrar (seguidores o siguiendo)
$tab = isset($_GET['tab']) && $_GET['tab'] == 'siguiendo' ? 'siguiendo' : 'seguidores';

// Ver seguidores o siguiendo de otro usuario si se especifica
// Ver seguidores o siguiendo de otro usuario si se especifica
$idPerfilUsuario = isset($_GET['id']) && is_numeric($_GET['id']) ? $_GET['id'] : $idUsuario;
$esPerfilPropio = ($idPerfilUsuario == $idUsuario);

// Obtener información del usuario del perfil
$queryUsuario = "SELECT idUsuario, nombre, correo FROM usuarios WHERE idUsuario = ?";
$stmtUsuario = mysqli_prepare($conn, $queryUsuario);
mysqli_stmt_bind_param($stmtUsuario, "i", $idPerfilUsuario);
mysqli_stmt_execute($stmtUsuario);
$resultUsuario = mysqli_stmt_get_result($stmtUsuario);

if (mysqli_num_rows($resultUsuario) == 0) {
    $_SESSION['mensaje'] = "El usuario no existe.";
    header("Location: index.php");
    exit();
}

$perfilUsuario = mysqli_fetch_assoc($resultUsuario);

// Verificar si existe la tabla 'seguidores'
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
        $error = "Error al crear la funcionalidad de seguimiento: " . mysqli_error($conn);
    }
}

// Obtener seguidores o siguiendo según la pestaña seleccionada
if ($tab == 'seguidores') {
    // Obtener usuarios que siguen al usuario del perfil
    $queryUsuarios = "SELECT u.idUsuario, u.nombre, u.correo, s.fecha_seguimiento,
                     (SELECT COUNT(*) FROM seguidores WHERE seguidor_id = ? AND seguido_id = u.idUsuario) as lo_sigo
                     FROM seguidores s
                     INNER JOIN usuarios u ON s.seguidor_id = u.idUsuario
                     WHERE s.seguido_id = ?
                     ORDER BY s.fecha_seguimiento DESC";
    $stmtUsuarios = mysqli_prepare($conn, $queryUsuarios);
    mysqli_stmt_bind_param($stmtUsuarios, "ii", $idUsuario, $idPerfilUsuario);
} else {
    // Obtener usuarios a los que sigue el usuario del perfil
    $queryUsuarios = "SELECT u.idUsuario, u.nombre, u.correo, s.fecha_seguimiento,
                     (SELECT COUNT(*) FROM seguidores WHERE seguidor_id = ? AND seguido_id = u.idUsuario) as lo_sigo
                     FROM seguidores s
                     INNER JOIN usuarios u ON s.seguido_id = u.idUsuario
                     WHERE s.seguidor_id = ?
                     ORDER BY s.fecha_seguimiento DESC";
    $stmtUsuarios = mysqli_prepare($conn, $queryUsuarios);
    mysqli_stmt_bind_param($stmtUsuarios, "ii", $idUsuario, $idPerfilUsuario);
}

mysqli_stmt_execute($stmtUsuarios);
$resultUsuarios = mysqli_stmt_get_result($stmtUsuarios);

// Obtener contadores
$queryContadores = "SELECT 
                  (SELECT COUNT(*) FROM seguidores WHERE seguido_id = ?) as total_seguidores,
                  (SELECT COUNT(*) FROM seguidores WHERE seguidor_id = ?) as total_siguiendo";
$stmtContadores = mysqli_prepare($conn, $queryContadores);
mysqli_stmt_bind_param($stmtContadores, "ii", $idPerfilUsuario, $idPerfilUsuario);
mysqli_stmt_execute($stmtContadores);
$resultContadores = mysqli_stmt_get_result($stmtContadores);
$contadores = mysqli_fetch_assoc($resultContadores);

// Verificar si el usuario actual sigue al usuario del perfil
$estaSiguiendo = false;
if (!$esPerfilPropio) {
    $queryVerificarSigue = "SELECT * FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?";
    $stmtVerificarSigue = mysqli_prepare($conn, $queryVerificarSigue);
    mysqli_stmt_bind_param($stmtVerificarSigue, "ii", $idUsuario, $idPerfilUsuario);
    mysqli_stmt_execute($stmtVerificarSigue);
    $resultVerificarSigue = mysqli_stmt_get_result($stmtVerificarSigue);
    $estaSiguiendo = mysqli_num_rows($resultVerificarSigue) > 0;
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <!-- Información del perfil -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <?php echo generarAvatar($perfilUsuario['nombre'], 'lg'); ?>
                    </div>
                    <h5 class="card-title"><?php echo htmlspecialchars($perfilUsuario['nombre']); ?></h5>
                    <?php if ($esPerfilPropio || isset($_SESSION['admin'])): ?>
                    <p class="card-text text-muted"><?php echo htmlspecialchars($perfilUsuario['correo']); ?></p>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-center my-3">
                        <a href="seguidores.php?id=<?php echo $idPerfilUsuario; ?>" class="text-decoration-none me-4">
                            <div class="fw-bold"><?php echo $contadores['total_seguidores']; ?></div>
                            <div class="small text-muted">Seguidores</div>
                        </a>
                        <a href="seguidores.php?id=<?php echo $idPerfilUsuario; ?>&tab=siguiendo" class="text-decoration-none">
                            <div class="fw-bold"><?php echo $contadores['total_siguiendo']; ?></div>
                            <div class="small text-muted">Siguiendo</div>
                        </a>
                    </div>
                    
                    <?php if ($esPerfilPropio): ?>
                    <div class="d-grid">
                        <a href="perfil.php" class="btn btn-primary">Editar Perfil</a>
                    </div>
                    <?php elseif ($estaSiguiendo): ?>
                    <div class="d-grid">
                        <a href="seguir-usuario.php?id=<?php echo $idPerfilUsuario; ?>&token=<?php echo generarTokenCSRF(); ?>" class="btn btn-primary">
                            <i class="fas fa-user-check me-1"></i> Siguiendo
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="d-grid">
                        <a href="seguir-usuario.php?id=<?php echo $idPerfilUsuario; ?>&token=<?php echo generarTokenCSRF(); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus me-1"></i> Seguir
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Enlaces rápidos -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Enlaces rápidos</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="index.php" class="list-group-item list-group-item-action px-0 border-0">
                            <i class="fas fa-home me-2"></i> Inicio
                        </a>
                        <a href="perfil.php" class="list-group-item list-group-item-action px-0 border-0">
                            <i class="fas fa-user me-2"></i> Mi Perfil
                        </a>
                        <a href="comunidades.php" class="list-group-item list-group-item-action px-0 border-0">
                            <i class="fas fa-users me-2"></i> Comunidades
                        </a>
                        <a href="comentarios.php" class="list-group-item list-group-item-action px-0 border-0">
                            <i class="fas fa-comments me-2"></i> Comentarios
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Pestañas de seguidores/siguiendo -->
            <div class="card">
                <div class="card-header bg-white">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $tab == 'seguidores' ? 'active' : ''; ?>" href="seguidores.php?id=<?php echo $idPerfilUsuario; ?>">
                                Seguidores <span class="badge bg-primary rounded-pill"><?php echo $contadores['total_seguidores']; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $tab == 'siguiendo' ? 'active' : ''; ?>" href="seguidores.php?id=<?php echo $idPerfilUsuario; ?>&tab=siguiendo">
                                Siguiendo <span class="badge bg-primary rounded-pill"><?php echo $contadores['total_siguiendo']; ?></span>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <?php 
                        $nombrePosesivo = $esPerfilPropio ? "Tus" : "Usuarios que " . ($tab == 'seguidores' ? "siguen a" : "sigue");
                        $nombreUsuario = $esPerfilPropio ? "" : " " . htmlspecialchars($perfilUsuario['nombre']);
                        echo $nombrePosesivo . $nombreUsuario; 
                        ?>
                    </h5>
                    
                    <?php if (mysqli_num_rows($resultUsuarios) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while ($usuario = mysqli_fetch_assoc($resultUsuarios)): ?>
                        <div class="list-group-item px-0 border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <a href="seguidores.php?id=<?php echo $usuario['idUsuario']; ?>" class="text-decoration-none me-2">
                                        <?php echo generarAvatar($usuario['nombre'], 'md'); ?>
                                    </a>
                                    <div>
                                        <a href="seguidores.php?id=<?php echo $usuario['idUsuario']; ?>" class="text-decoration-none">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($usuario['nombre']); ?></h6>
                                        </a>
                                        <small class="text-muted">
                                            Desde <?php echo formatoFecha($usuario['fecha_seguimiento']); ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <?php if ($usuario['idUsuario'] != $idUsuario): ?>
                                <a href="seguir-usuario.php?id=<?php echo $usuario['idUsuario']; ?>&token=<?php echo generarTokenCSRF(); ?>" class="btn btn-sm <?php echo $usuario['lo_sigo'] ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill">
                                    <?php echo $usuario['lo_sigo'] ? '<i class="fas fa-user-check me-1"></i> Siguiendo' : '<i class="fas fa-user-plus me-1"></i> Seguir'; ?>
                                </a>
                                <?php else: ?>
                                <span class="badge bg-light text-dark">Tú</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <?php 
                        echo $esPerfilPropio ? 
                            "Aún no " . ($tab == 'seguidores' ? "tienes seguidores" : "sigues a nadie") . "." :
                            ($tab == 'seguidores' ? "Este usuario no tiene seguidores aún." : "Este usuario no sigue a nadie aún."); 
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>