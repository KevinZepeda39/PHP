<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
verificarLogin();

// Verificar si se proporcionó un ID de comunidad
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: comunidades.php");
    exit();
}

$idComunidad = $_GET['id'];
$idUsuario = $_SESSION['idUsuario'];

// Obtener detalles de la comunidad
$query = "SELECT c.*, u.nombre as creador 
         FROM comunidad c 
         INNER JOIN usuarios u ON c.idUsuario = u.idUsuario
         WHERE c.idComunidad = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $idComunidad);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: comunidades.php");
    exit();
}

$comunidad = mysqli_fetch_assoc($result);

// Verificar si el usuario es miembro de la comunidad
$queryMiembro = "SELECT * FROM usuario_comunidad WHERE idUsuario = ? AND idComunidad = ?";
$stmtMiembro = mysqli_prepare($conn, $queryMiembro);
mysqli_stmt_bind_param($stmtMiembro, "ii", $idUsuario, $idComunidad);
mysqli_stmt_execute($stmtMiembro);
$resultMiembro = mysqli_stmt_get_result($stmtMiembro);
$esMiembro = mysqli_num_rows($resultMiembro) > 0;

// Obtener comentarios de la comunidad
$queryComentarios = "SELECT c.*, u.nombre, c.fechaComentario
                    FROM comentarios c
                    INNER JOIN usuarios u ON c.idUsuario = u.idUsuario
                    WHERE c.idComunidad = ?
                    ORDER BY c.fechaComentario DESC";
$stmtComentarios = mysqli_prepare($conn, $queryComentarios);
mysqli_stmt_bind_param($stmtComentarios, "i", $idComunidad);
mysqli_stmt_execute($stmtComentarios);
$resultComentarios = mysqli_stmt_get_result($stmtComentarios);

// Procesar el formulario de comentarios
$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comentario'])) {
    $comentario = trim($_POST['comentario']);
    
    if (empty($comentario)) {
        $mensaje = "El comentario no puede estar vacío.";
    } else {
        $queryInsert = "INSERT INTO comentarios (idComunidad, idUsuario, comentario) VALUES (?, ?, ?)";
        $stmtInsert = mysqli_prepare($conn, $queryInsert);
        mysqli_stmt_bind_param($stmtInsert, "iis", $idComunidad, $idUsuario, $comentario);
        
        if (mysqli_stmt_execute($stmtInsert)) {
            // También agregar la relación en la tabla usuario_comentario
            $idComentario = mysqli_insert_id($conn);
            $queryRelacion = "INSERT INTO usuario_comentario (idUsuario, idComentario, rolEnComentario) VALUES (?, ?, 'autor')";
            $stmtRelacion = mysqli_prepare($conn, $queryRelacion);
            mysqli_stmt_bind_param($stmtRelacion, "ii", $idUsuario, $idComentario);
            mysqli_stmt_execute($stmtRelacion);
            
            // Recargar para mostrar el nuevo comentario
            header("Location: comunidad.php?id=$idComunidad&success=1");
            exit();
        } else {
            $mensaje = "Error al publicar el comentario: " . mysqli_error($conn);
        }
    }
}

// Verificar si hay un mensaje de éxito
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $mensaje = "Comentario publicado correctamente.";
}

// Verificar si hay un mensaje de la sesión (por ejemplo, de unirse a la comunidad)
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <!-- Cabecera de la comunidad -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <a href="comunidades.php" class="btn btn-sm btn-light me-3">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <h2 class="mb-0"><?php echo htmlspecialchars($comunidad['titulo']); ?></h2>
                    </div>
                    
                    <div class="d-flex align-items-center mb-3">
                        <?php echo generarAvatar($comunidad['creador'], 'md'); ?>
                        <div class="ms-2">
                            <div class="fw-bold"><?php echo htmlspecialchars($comunidad['creador']); ?></div>
                            <small class="text-muted"><?php echo formatoFecha($comunidad['fechaCreacion']); ?></small>
                        </div>
                    </div>
                    
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($comunidad['descripcion'])); ?></p>
                    
                    <div class="d-flex mt-3">
                        <button class="btn btn-outline-primary me-2"><i class="far fa-thumbs-up me-1"></i> Me gusta</button>
                        <button class="btn btn-outline-secondary me-2"><i class="far fa-comment me-1"></i> Comentar</button>
                        
                        <!-- Botón para unirse/dejar la comunidad -->
                        <?php if ($esMiembro): ?>
                        <a href="unirse-comunidad.php?id=<?php echo $idComunidad; ?>&token=<?php echo generarTokenCSRF(); ?>" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-1"></i> Dejar comunidad
                        </a>
                        <?php else: ?>
                        <a href="unirse-comunidad.php?id=<?php echo $idComunidad; ?>&token=<?php echo generarTokenCSRF(); ?>" class="btn btn-outline-success">
                            <i class="fas fa-sign-in-alt me-1"></i> Unirse a comunidad
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Formulario de comentarios -->
            <div class="card mb-4">
                <div class="card-body">
                    <?php if (!empty($mensaje)): ?>
                        <div class="alert alert-<?php echo (strpos($mensaje, 'Error') !== false) ? 'danger' : 'success'; ?> alert-dismissible fade show">
                            <?php echo $mensaje; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <h5 class="card-title">Deja tu comentario</h5>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                        <div class="d-flex mb-3">
                            <!-- Avatar del usuario que comenta -->
                            <?php echo generarAvatar($_SESSION['nombre'], 'md'); ?>
                            <div class="ms-2 flex-grow-1">
                                <textarea class="form-control" name="comentario" rows="3" placeholder="Escribe tu comentario aquí..." required></textarea>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Publicar</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Comentarios -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Comentarios (<?php echo mysqli_num_rows($resultComentarios); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($resultComentarios) > 0): ?>
                        <div class="comentarios">
                            <?php while ($comentario = mysqli_fetch_assoc($resultComentarios)): ?>
                                <div class="comentario mb-4" id="comentario-<?php echo $comentario['idComentario']; ?>">
                                    <div class="d-flex">
                                        <?php echo generarAvatar($comentario['nombre'], 'md'); ?>
                                        <div class="flex-grow-1 ms-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="fw-bold"><?php echo htmlspecialchars($comentario['nombre']); ?></div>
                                                <small class="text-muted"><?php echo formatoFecha($comentario['fechaComentario']); ?></small>
                                            </div>
                                            <p class="mb-1"><?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?></p>
                                            <div class="d-flex align-items-center">
                                                <button class="btn btn-sm btn-link text-muted p-0 me-3">Me gusta</button>
                                                <button class="btn btn-sm btn-link text-muted p-0 responder-btn" data-id="<?php echo $comentario['idComentario']; ?>">Responder</button>
                                                
                                                <?php if ($comentario['idUsuario'] == $idUsuario): ?>
                                                <!-- Opciones para el autor del comentario -->
                                                <div class="dropdown ms-auto">
                                                    <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-h"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editarComentarioModal" data-id="<?php echo $comentario['idComentario']; ?>" data-texto="<?php echo htmlspecialchars($comentario['comentario']); ?>"><i class="fas fa-edit me-2"></i> Editar</a></li>
                                                        <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#eliminarComentarioModal" data-id="<?php echo $comentario['idComentario']; ?>"><i class="fas fa-trash me-2"></i> Eliminar</a></li>
                                                    </ul>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Formulario para responder (inicialmente oculto) -->
                                            <div class="responder-form mt-2 d-none" id="responder-<?php echo $comentario['idComentario']; ?>">
                                                <div class="d-flex">
                                                    <?php echo generarAvatar($_SESSION['nombre'], 'sm'); ?>
                                                    <div class="ms-2 flex-grow-1">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control form-control-sm" placeholder="Escribe una respuesta...">
                                                            <button class="btn btn-sm btn-primary">Enviar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No hay comentarios aún. ¡Sé el primero en comentar!</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Acciones de la comunidad -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Acciones</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($esMiembro): ?>
                        <a href="unirse-comunidad.php?id=<?php echo $idComunidad; ?>&token=<?php echo generarTokenCSRF(); ?>" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt me-1"></i> Dejar comunidad
                        </a>
                        <?php else: ?>
                        <a href="unirse-comunidad.php?id=<?php echo $idComunidad; ?>&token=<?php echo generarTokenCSRF(); ?>" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-1"></i> Unirse a esta comunidad
                        </a>
                        <?php endif; ?>
                        <button class="btn btn-outline-secondary" type="button">Invitar amigos</button>
                    </div>
                </div>
            </div>
            
            <!-- Miembros activos -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Miembros activos</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Consultar usuarios que han comentado en esta comunidad
                    $queryUsuariosActivos = "SELECT DISTINCT u.idUsuario, u.nombre, COUNT(c.idComentario) as total
                                           FROM usuarios u
                                           INNER JOIN comentarios c ON u.idUsuario = c.idUsuario
                                           WHERE c.idComunidad = ?
                                           GROUP BY u.idUsuario
                                           ORDER BY total DESC
                                           LIMIT 5";
                    $stmtUsuariosActivos = mysqli_prepare($conn, $queryUsuariosActivos);
                    mysqli_stmt_bind_param($stmtUsuariosActivos, "i", $idComunidad);
                    mysqli_stmt_execute($stmtUsuariosActivos);
                    $resultUsuariosActivos = mysqli_stmt_get_result($stmtUsuariosActivos);
                    
                    if (mysqli_num_rows($resultUsuariosActivos) > 0):
                    ?>
                    <ul class="list-group list-group-flush">
                        <?php while ($usuario = mysqli_fetch_assoc($resultUsuariosActivos)): ?>
                        <li class="list-group-item px-0 py-2 border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <?php echo generarAvatar($usuario['nombre'], 'sm'); ?>
                                    <span class="ms-2"><?php echo htmlspecialchars($usuario['nombre']); ?></span>
                                </div>
                                <span class="badge bg-light text-dark"><?php echo $usuario['total']; ?> comentarios</span>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                    <?php else: ?>
                    <p class="text-muted">No hay miembros activos aún.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Comunidades similares -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Comunidades similares</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Consultar otras comunidades
                    $queryOtrasComunidades = "SELECT c.idComunidad, c.titulo, COUNT(co.idComentario) as total_comentarios
                                             FROM comunidad c
                                             LEFT JOIN comentarios co ON c.idComunidad = co.idComunidad
                                             WHERE c.idComunidad != ?
                                             GROUP BY c.idComunidad
                                             ORDER BY RAND()
                                             LIMIT 3";
                    $stmtOtrasComunidades = mysqli_prepare($conn, $queryOtrasComunidades);
                    mysqli_stmt_bind_param($stmtOtrasComunidades, "i", $idComunidad);
                    mysqli_stmt_execute($stmtOtrasComunidades);
                    $resultOtrasComunidades = mysqli_stmt_get_result($stmtOtrasComunidades);
                    
                    if (mysqli_num_rows($resultOtrasComunidades) > 0):
                    ?>
                    <ul class="list-group list-group-flush">
                        <?php while ($otraComunidad = mysqli_fetch_assoc($resultOtrasComunidades)): ?>
                        <li class="list-group-item px-0 py-2 border-0">
                            <a href="comunidad.php?id=<?php echo $otraComunidad['idComunidad']; ?>" class="text-decoration-none">
                                <h6 class="mb-1"><?php echo htmlspecialchars($otraComunidad['titulo']); ?></h6>
                            </a>
                            <small class="text-muted"><?php echo $otraComunidad['total_comentarios']; ?> comentarios</small>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                    <?php else: ?>
                    <p class="text-muted">No hay comunidades similares.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar comentario -->
<div class="modal fade" id="editarComentarioModal" tabindex="-1" aria-labelledby="editarComentarioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarComentarioModalLabel">Editar comentario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="editar-comentario.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                    <input type="hidden" name="id_comentario" id="id-comentario-editar">
                    <input type="hidden" name="id_comunidad" value="<?php echo $idComunidad; ?>">
                    <div class="mb-3">
                        <label for="texto-comentario" class="form-label">Comentario</label>
                        <textarea class="form-control" id="texto-comentario" name="comentario" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para eliminar comentario -->
<div class="modal fade" id="eliminarComentarioModal" tabindex="-1" aria-labelledby="eliminarComentarioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eliminarComentarioModalLabel">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar este comentario? Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="confirmar-eliminar" class="btn btn-danger">Eliminar</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar botones de "Responder"
    const responderBtns = document.querySelectorAll('.responder-btn');
    responderBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const comentarioId = this.getAttribute('data-id');
            const formContainer = document.getElementById('responder-' + comentarioId);
            
            // Toggle la clase d-none para mostrar/ocultar el formulario
            formContainer.classList.toggle('d-none');
        });
    });
    
    // Configurar modal de edición
    const editarModal = document.getElementById('editarComentarioModal');
    if (editarModal) {
        editarModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const texto = button.getAttribute('data-texto');
            
            document.getElementById('id-comentario-editar').value = id;
            document.getElementById('texto-comentario').value = texto;
        });
    }
    
    // Configurar modal de eliminación
    const eliminarModal = document.getElementById('eliminarComentarioModal');
    if (eliminarModal) {
        eliminarModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            
            const confirmarBtn = document.getElementById('confirmar-eliminar');
            confirmarBtn.href = 'eliminar-comentario.php?id=' + id + '&comunidad_id=<?php echo $idComunidad; ?>&token=<?php echo generarTokenCSRF(); ?>';
        });
    }
});
</script>

<!-- Al final de tu archivo, antes de cerrar el body -->
<script src="assets/js/comunidades-realtime.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if(isset($_SESSION['idUsuario'])): ?>
    // Inicializar el sistema de tiempo real para la página de comunidad
    comunidadesRealtime.inicializarPaginaComunidad(
        <?php echo $idComunidad; ?>,
        <?php echo $_SESSION['idUsuario']; ?>
    );
    <?php endif; ?>
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar botones de "Responder"
    const responderBtns = document.querySelectorAll('.responder-btn');
    responderBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const comentarioId = this.getAttribute('data-id');
            const formContainer = document.getElementById('responder-' + comentarioId);
            
            // Toggle la clase d-none para mostrar/ocultar el formulario
            formContainer.classList.toggle('d-none');
        });
    });
    
    // Configurar modal de edición
    const editarModal = document.getElementById('editarComentarioModal');
    if (editarModal) {
        editarModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const texto = button.getAttribute('data-texto');
            
            document.getElementById('id-comentario-editar').value = id;
            document.getElementById('texto-comentario').value = texto;
        });
    }
    
    // Configurar modal de eliminación
    const eliminarModal = document.getElementById('eliminarComentarioModal');
    if (eliminarModal) {
        eliminarModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            
            const confirmarBtn = document.getElementById('confirmar-eliminar');
            confirmarBtn.href = 'eliminar-comentario.php?id=' + id + '&comunidad_id=<?php echo $idComunidad; ?>&token=<?php echo generarTokenCSRF(); ?>';
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>