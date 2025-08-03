<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
verificarLogin();

$idUsuario = $_SESSION['idUsuario'];
$mensaje = "";
$error = "";

// Obtener datos del usuario
$query = "SELECT * FROM usuarios WHERE idUsuario = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $idUsuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$usuario = mysqli_fetch_assoc($result);

// Verificar si existe la tabla seguidores, y si no, crearla
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
    
    mysqli_query($conn, $queryCreateTable);
}

// Procesar actualizaciones del perfil
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'actualizar_perfil') {
    // Verificar token CSRF
    if (!verificarTokenCSRF($_POST['csrf_token'])) {
        $error = "Error de seguridad. Por favor, intente nuevamente.";
    } else {
        $nombre = validarDatos($_POST['nombre']);
        $correo = validarDatos($_POST['correo']);
        $contrasenaActual = $_POST['contrasena_actual'] ?? '';
        $contrasenaNueva = $_POST['contrasena_nueva'] ?? '';
        $contrasenaConfirm = $_POST['contrasena_confirm'] ?? '';
        
        // Validar nombre y correo
        if (empty($nombre)) {
            $error = "El nombre es obligatorio.";
        } elseif (empty($correo)) {
            $error = "El correo es obligatorio.";
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = "El formato del correo no es válido.";
        } else {
            // Verificar si el correo ya existe (si se está cambiando)
            if ($correo != $usuario['correo']) {
                $queryVerificar = "SELECT idUsuario FROM usuarios WHERE correo = ? AND idUsuario != ?";
                $stmtVerificar = mysqli_prepare($conn, $queryVerificar);
                mysqli_stmt_bind_param($stmtVerificar, "si", $correo, $idUsuario);
                mysqli_stmt_execute($stmtVerificar);
                $resultVerificar = mysqli_stmt_get_result($stmtVerificar);
                
                if (mysqli_num_rows($resultVerificar) > 0) {
                    $error = "El correo ya está en uso por otro usuario.";
                }
            }
            
            // Si no hay errores, actualizar datos básicos
            if (empty($error)) {
                $queryUpdate = "UPDATE usuarios SET nombre = ?, correo = ? WHERE idUsuario = ?";
                $stmtUpdate = mysqli_prepare($conn, $queryUpdate);
                mysqli_stmt_bind_param($stmtUpdate, "ssi", $nombre, $correo, $idUsuario);
                
                if (mysqli_stmt_execute($stmtUpdate)) {
                    $mensaje = "Datos actualizados correctamente.";
                    $_SESSION['nombre'] = $nombre; // Actualizar el nombre en la sesión
                } else {
                    $error = "Error al actualizar los datos: " . mysqli_error($conn);
                }
            }
            
            // Si se está intentando cambiar la contraseña
            if (!empty($contrasenaActual) && !empty($contrasenaNueva)) {
                // Verificar contraseña actual
                if (password_verify($contrasenaActual, $usuario['contraseña'])) {
                    // Verificar que las nuevas contraseñas coincidan
                    if ($contrasenaNueva == $contrasenaConfirm) {
                        // Actualizar contraseña
                        $contrasenaNuevaHash = password_hash($contrasenaNueva, PASSWORD_DEFAULT);
                        $queryUpdatePass = "UPDATE usuarios SET contraseña = ? WHERE idUsuario = ?";
                        $stmtUpdatePass = mysqli_prepare($conn, $queryUpdatePass);
                        mysqli_stmt_bind_param($stmtUpdatePass, "si", $contrasenaNuevaHash, $idUsuario);
                        
                        if (mysqli_stmt_execute($stmtUpdatePass)) {
                            $mensaje .= " Contraseña actualizada correctamente.";
                        } else {
                            $error = "Error al actualizar la contraseña: " . mysqli_error($conn);
                        }
                    } else {
                        $error = "Las nuevas contraseñas no coinciden.";
                    }
                } else {
                    $error = "La contraseña actual es incorrecta.";
                }
            }
        }
    }
    
    // Volver a obtener datos actualizados
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $usuario = mysqli_fetch_assoc($result);
}

// Obtener estadísticas del usuario
$totalSeguidores = 0;
$totalSiguiendo = 0;

// Consultar seguidores
$querySeguidores = "SELECT COUNT(*) as total FROM seguidores WHERE seguido_id = ?";
$stmtSeguidores = mysqli_prepare($conn, $querySeguidores);
if ($stmtSeguidores) {
    mysqli_stmt_bind_param($stmtSeguidores, "i", $idUsuario);
    mysqli_stmt_execute($stmtSeguidores);
    $resultSeguidores = mysqli_stmt_get_result($stmtSeguidores);
    if ($resultSeguidores && mysqli_num_rows($resultSeguidores) > 0) {
        $seguidores = mysqli_fetch_assoc($resultSeguidores);
        $totalSeguidores = $seguidores['total'];
    }
}

// Consultar siguiendo
$querySiguiendo = "SELECT COUNT(*) as total FROM seguidores WHERE seguidor_id = ?";
$stmtSiguiendo = mysqli_prepare($conn, $querySiguiendo);
if ($stmtSiguiendo) {
    mysqli_stmt_bind_param($stmtSiguiendo, "i", $idUsuario);
    mysqli_stmt_execute($stmtSiguiendo);
    $resultSiguiendo = mysqli_stmt_get_result($stmtSiguiendo);
    if ($resultSiguiendo && mysqli_num_rows($resultSiguiendo) > 0) {
        $siguiendo = mysqli_fetch_assoc($resultSiguiendo);
        $totalSiguiendo = $siguiendo['total'];
    }
}

// Obtener comunidades del usuario
$comunidadesCount = 0;
$queryComunidades = "SELECT COUNT(*) as total FROM usuario_comunidad WHERE idUsuario = ?";
$stmtComunidades = mysqli_prepare($conn, $queryComunidades);
if ($stmtComunidades) {
    mysqli_stmt_bind_param($stmtComunidades, "i", $idUsuario);
    mysqli_stmt_execute($stmtComunidades);
    $resultComunidades = mysqli_stmt_get_result($stmtComunidades);
    if ($resultComunidades && mysqli_num_rows($resultComunidades) > 0) {
        $comunidades = mysqli_fetch_assoc($resultComunidades);
        $comunidadesCount = $comunidades['total'];
    }
}

// Obtener comentarios del usuario
$comentariosCount = 0;
$queryComentarios = "SELECT COUNT(*) as total FROM comentarios WHERE idUsuario = ?";
$stmtComentarios = mysqli_prepare($conn, $queryComentarios);
if ($stmtComentarios) {
    mysqli_stmt_bind_param($stmtComentarios, "i", $idUsuario);
    mysqli_stmt_execute($stmtComentarios);
    $resultComentarios = mysqli_stmt_get_result($stmtComentarios);
    if ($resultComentarios && mysqli_num_rows($resultComentarios) > 0) {
        $comentarios = mysqli_fetch_assoc($resultComentarios);
        $comentariosCount = $comentarios['total'];
    }
}

// Obtener usuarios para seguir
$queryUsuarios = "SELECT u.idUsuario, u.nombre, 
                 (SELECT COUNT(*) FROM seguidores WHERE seguidor_id = ? AND seguido_id = u.idUsuario) as lo_sigo
                 FROM usuarios u 
                 WHERE u.idUsuario != ?
                 ORDER BY u.nombre ASC
                 LIMIT 10";
$stmtUsuarios = mysqli_prepare($conn, $queryUsuarios);
mysqli_stmt_bind_param($stmtUsuarios, "ii", $idUsuario, $idUsuario);
mysqli_stmt_execute($stmtUsuarios);
$resultUsuarios = mysqli_stmt_get_result($stmtUsuarios);

// Obtener lista de seguidores
$querySeguidoresList = "SELECT u.idUsuario, u.nombre, s.fecha_seguimiento
                       FROM seguidores s
                       INNER JOIN usuarios u ON s.seguidor_id = u.idUsuario
                       WHERE s.seguido_id = ?
                       ORDER BY s.fecha_seguimiento DESC
                       LIMIT 10";
$stmtSeguidoresList = mysqli_prepare($conn, $querySeguidoresList);
mysqli_stmt_bind_param($stmtSeguidoresList, "i", $idUsuario);
mysqli_stmt_execute($stmtSeguidoresList);
$resultSeguidoresList = mysqli_stmt_get_result($stmtSeguidoresList);

// Obtener lista de seguidos
$querySiguiendoList = "SELECT u.idUsuario, u.nombre, s.fecha_seguimiento
                      FROM seguidores s
                      INNER JOIN usuarios u ON s.seguido_id = u.idUsuario
                      WHERE s.seguidor_id = ?
                      ORDER BY s.fecha_seguimiento DESC
                      LIMIT 10";
$stmtSiguiendoList = mysqli_prepare($conn, $querySiguiendoList);
mysqli_stmt_bind_param($stmtSiguiendoList, "i", $idUsuario);
mysqli_stmt_execute($stmtSiguiendoList);
$resultSiguiendoList = mysqli_stmt_get_result($stmtSiguiendoList);

include 'includes/header.php';
?>

<div class="container mt-4">
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Panel izquierdo: Perfil, estadísticas y edición de datos -->
        <div class="col-md-8">
            <!-- Cabecera del perfil con estadísticas -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center">
                            <?php echo generarAvatar($usuario['nombre'], 'lg'); ?>
                        </div>
                        <div class="col-md-9">
                            <h2 class="mb-1"><?php echo htmlspecialchars($usuario['nombre']); ?></h2>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($usuario['correo']); ?></p>
                            
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <a href="#seguidores" class="text-decoration-none" data-bs-toggle="tab">
                                        <div class="fw-bold"><?php echo $totalSeguidores; ?></div>
                                        <div class="small text-muted">Seguidores</div>
                                    </a>
                                </div>
                                <div class="col-4">
                                    <a href="#siguiendo" class="text-decoration-none" data-bs-toggle="tab">
                                        <div class="fw-bold"><?php echo $totalSiguiendo; ?></div>
                                        <div class="small text-muted">Siguiendo</div>
                                    </a>
                                </div>
                                <div class="col-4">
                                    <a href="#communities-tab" class="text-decoration-none" data-bs-toggle="tab">
                                        <div class="fw-bold"><?php echo $comunidadesCount; ?></div>
                                        <div class="small text-muted">Comunidades</div>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="#editPerfil" class="btn btn-primary" data-bs-toggle="tab">Editar Perfil</a>
                                <a href="mis-comunidades.php" class="btn btn-outline-primary">Mis Comunidades</a>
                                <a href="comentarios.php" class="btn btn-outline-secondary">Comentarios</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pestañas para diferentes secciones -->
            <div class="card">
                <div class="card-header bg-white">
                    <ul class="nav nav-tabs card-header-tabs" id="perfilTabs">
                        <li class="nav-item">
                            <a class="nav-link active" href="#editPerfil" data-bs-toggle="tab">Editar Perfil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#communities-tab" data-bs-toggle="tab">Comunidades</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#seguidores" data-bs-toggle="tab">Seguidores</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#siguiendo" data-bs-toggle="tab">Siguiendo</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Tab Editar Perfil -->
                        <div class="tab-pane fade show active" id="editPerfil">
                            <h5 class="card-title mb-4">Información Personal</h5>
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                                <input type="hidden" name="accion" value="actualizar_perfil">
                                
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre completo</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="correo" class="form-label">Correo electrónico</label>
                                    <input type="email" class="form-control" id="correo" name="correo" value="<?php echo htmlspecialchars($usuario['correo']); ?>" required>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h5 class="card-title mb-4">Cambiar Contraseña</h5>
                                
                                <div class="mb-3">
                                    <label for="contrasena_actual" class="form-label">Contraseña actual</label>
                                    <input type="password" class="form-control" id="contrasena_actual" name="contrasena_actual">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="contrasena_nueva" class="form-label">Nueva contraseña</label>
                                        <input type="password" class="form-control" id="contrasena_nueva" name="contrasena_nueva">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="contrasena_confirm" class="form-label">Confirmar nueva contraseña</label>
                                        <input type="password" class="form-control" id="contrasena_confirm" name="contrasena_confirm">
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Tab Comunidades - Modificado para mostrar solo las del usuario -->
                        <div class="tab-pane fade" id="communities-tab">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0">Mis Comunidades</h5>
                                <a href="comunidades.php" class="btn btn-sm btn-outline-primary">Explorar Comunidades</a>
                            </div>
                            
                            <?php 
                            // Consultar solo las comunidades del usuario actual
                            $queryComunidadesUsuario = "SELECT c.idComunidad, c.titulo, uc.rolEnComunidad, uc.fechaUnion
                                                      FROM usuario_comunidad uc
                                                      INNER JOIN comunidad c ON uc.idComunidad = c.idComunidad
                                                      WHERE uc.idUsuario = ?
                                                      ORDER BY uc.fechaUnion DESC
                                                      LIMIT 5"; // Mostrar solo 5 en el perfil
                            $stmtComunidadesUsuario = mysqli_prepare($conn, $queryComunidadesUsuario);
                            mysqli_stmt_bind_param($stmtComunidadesUsuario, "i", $idUsuario);
                            mysqli_stmt_execute($stmtComunidadesUsuario);
                            $resultComunidadesUsuario = mysqli_stmt_get_result($stmtComunidadesUsuario);
                            
                            if (mysqli_num_rows($resultComunidadesUsuario) > 0): 
                            ?>
                            <div class="list-group">
                                <?php while ($comunidad = mysqli_fetch_assoc($resultComunidadesUsuario)): ?>
                                <a href="comunidad.php?id=<?php echo $comunidad['idComunidad']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($comunidad['titulo']); ?></h6>
                                            <small class="text-muted">Te uniste el <?php echo formatoFecha($comunidad['fechaUnion']); ?></small>
                                        </div>
                                        <span class="badge <?php 
                                            switch(strtolower($comunidad['rolEnComunidad'])) {
                                                case 'creador': echo 'bg-warning text-dark'; break;
                                                case 'administrador': echo 'bg-danger'; break;
                                                case 'moderador': echo 'bg-success'; break;
                                                default: echo 'bg-primary';
                                            }
                                        ?> rounded-pill">
                                            <?php echo ucfirst($comunidad['rolEnComunidad']); ?>
                                        </span>
                                    </div>
                                </a>
                                <?php endwhile; ?>
                            </div>
                            
                            <?php if ($comunidadesCount > 5): ?>
                            <div class="text-center mt-3">
                                <a href="mis-comunidades.php" class="btn btn-primary">
                                    <i class="fas fa-users me-2"></i>Ver todas mis comunidades
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <?php else: ?>
                            <div class="alert alert-info">
                                <p>No perteneces a ninguna comunidad.</p>
                                <div class="mt-3">
                                    <a href="comunidades.php" class="btn btn-primary me-2">Explorar comunidades</a>
                                    <a href="crear-comunidad.php" class="btn btn-outline-primary">Crear comunidad</a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Tab Seguidores -->
                        <div class="tab-pane fade" id="seguidores">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0">Personas que te siguen</h5>
                                <?php if ($totalSeguidores > 10): ?>
                                <a href="seguidores.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($resultSeguidoresList && mysqli_num_rows($resultSeguidoresList) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($seguidor = mysqli_fetch_assoc($resultSeguidoresList)): ?>
                                <div class="list-group-item px-0 border-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <?php echo generarAvatar($seguidor['nombre'], 'md'); ?>
                                            <div class="ms-3">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($seguidor['nombre']); ?></h6>
                                                <small class="text-muted">Te sigue desde <?php echo formatoFecha($seguidor['fecha_seguimiento']); ?></small>
                                            </div>
                                        </div>
                                        
                                        <?php
                                        // Verificar si sigues de vuelta a este usuario
                                        $queryCheckSiguiendo = "SELECT * FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?";
                                        $stmtCheckSiguiendo = mysqli_prepare($conn, $queryCheckSiguiendo);
                                        mysqli_stmt_bind_param($stmtCheckSiguiendo, "ii", $idUsuario, $seguidor['idUsuario']);
                                        mysqli_stmt_execute($stmtCheckSiguiendo);
                                        $resultCheckSiguiendo = mysqli_stmt_get_result($stmtCheckSiguiendo);
                                        $siguiendoDeVuelta = mysqli_num_rows($resultCheckSiguiendo) > 0;
                                        ?>
                                        
                                        <a href="seguir-usuario.php?id=<?php echo $seguidor['idUsuario']; ?>&token=<?php echo generarTokenCSRF(); ?>" class="btn btn-sm <?php echo $siguiendoDeVuelta ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill">
                                            <?php echo $siguiendoDeVuelta ? 'Siguiendo' : 'Seguir'; ?>
                                        </a>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                Aún no tienes seguidores. ¡Participa más en la comunidad para conseguir seguidores!
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Tab Siguiendo -->
                        <div class="tab-pane fade" id="siguiendo">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0">Personas que sigues</h5>
                                <?php if ($totalSiguiendo > 10): ?>
                                <a href="seguidores.php?tab=siguiendo" class="btn btn-sm btn-outline-primary">Ver todos</a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($resultSiguiendoList && mysqli_num_rows($resultSiguiendoList) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($siguiendo = mysqli_fetch_assoc($resultSiguiendoList)): ?>
                                <div class="list-group-item px-0 border-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <?php echo generarAvatar($siguiendo['nombre'], 'md'); ?>
                                            <div class="ms-3">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($siguiendo['nombre']); ?></h6>
                                                <small class="text-muted">Sigues desde <?php echo formatoFecha($siguiendo['fecha_seguimiento']); ?></small>
                                            </div>
                                        </div>
                                        
                                        <a href="seguir-usuario.php?id=<?php echo $siguiendo['idUsuario']; ?>&token=<?php echo generarTokenCSRF(); ?>" class="btn btn-sm btn-primary rounded-pill">
                                            Dejar de seguir
                                        </a>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                Aún no sigues a nadie. ¡Encuentra personas interesantes para seguir!
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Panel derecho: Sugerencias para seguir -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Sugerencias para seguir</h5>
                </div>
                <div class="card-body">
                    <?php if ($resultUsuarios && mysqli_num_rows($resultUsuarios) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while ($usuarioSugerido = mysqli_fetch_assoc($resultUsuarios)): ?>
                        <div class="list-group-item px-0 py-2 border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <?php echo generarAvatar($usuarioSugerido['nombre'], 'sm'); ?>
                                    <span class="ms-2"><?php echo htmlspecialchars($usuarioSugerido['nombre']); ?></span>
                                </div>
                                <a href="seguir-usuario.php?id=<?php echo $usuarioSugerido['idUsuario']; ?>&token=<?php echo generarTokenCSRF(); ?>" class="btn btn-sm <?php echo $usuarioSugerido['lo_sigo'] ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill px-3">
                                    <?php echo $usuarioSugerido['lo_sigo'] ? 'Siguiendo' : '+ Follow'; ?>
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        No hay sugerencias disponibles en este momento.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Actividad reciente</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <div class="fw-bold"><?php echo $comentariosCount; ?></div>
                        <div class="small text-muted">Comentarios realizados</div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <div class="fw-bold"><?php echo $comunidadesCount; ?></div>
                        <div class="small text-muted">Comunidades</div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <div class="fw-bold"><?php
                            // Contar reportes del usuario
                            $reportesCount = 0;
                            $queryReportes = "SELECT COUNT(*) as total FROM reportes WHERE idUsuario = ?";
                            $stmtReportes = mysqli_prepare($conn, $queryReportes);
                            $stmtReportes = mysqli_prepare($conn, $queryReportes);
if ($stmtReportes) {
    mysqli_stmt_bind_param($stmtReportes, "i", $idUsuario);
    mysqli_stmt_execute($stmtReportes);
    $resultReportes = mysqli_stmt_get_result($stmtReportes);
    if ($resultReportes && mysqli_num_rows($resultReportes) > 0) {
        $reportes = mysqli_fetch_assoc($resultReportes);
        $reportesCount = $reportes['total'];
    }
}
echo $reportesCount; 
?></div>
                        <div class="small text-muted">Reportes realizados</div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <div class="fw-bold"><?php
                            // Obtener la fecha de registro del usuario
                            $fechaRegistro = new DateTime($usuario['fechaRegistro']);
                            $fechaActual = new DateTime();
                            $diferencia = $fechaRegistro->diff($fechaActual);
                            echo $diferencia->days;
                        ?></div>
                        <div class="small text-muted">Días en la plataforma</div>
                    </div>
                    
                    <hr class="my-3">
                    
                    <div class="d-grid gap-2">
                        <a href="comentarios.php?usuario=<?php echo $idUsuario; ?>" class="btn btn-outline-primary btn-sm">
                            Ver todos mis comentarios
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>