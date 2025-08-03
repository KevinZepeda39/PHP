<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
verificarLogin();

$idUsuario = $_SESSION['idUsuario'];
$nombre = $_SESSION['nombre'];

// Verificar si existe la tabla comentarios_globales, si no, crearla
$queryCheckTable = "SHOW TABLES LIKE 'comentarios_globales'";
$resultCheckTable = mysqli_query($conn, $queryCheckTable);

if (mysqli_num_rows($resultCheckTable) == 0) {
    // Crear la tabla si no existe
    $queryCreateTable = "CREATE TABLE comentarios_globales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        idUsuario INT NOT NULL,
        comentario TEXT NOT NULL,
        fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario) ON DELETE CASCADE
    )";
    
    if (!mysqli_query($conn, $queryCreateTable)) {
        $error = "Error al crear la tabla de comentarios globales: " . mysqli_error($conn);
    }
}

// Procesar envío de comentario global
$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comentario_global'])) {
    $comentario = validarDatos($_POST['comentario_global']);
    $token = $_POST['csrf_token'] ?? '';
    
    // Verificar token CSRF
    if (!verificarTokenCSRF($token)) {
        $mensaje = "Error de seguridad. Por favor, intente nuevamente.";
    } elseif (empty($comentario)) {
        $mensaje = "El comentario no puede estar vacío.";
    } else {
        // Insertar comentario global
        $queryInsert = "INSERT INTO comentarios_globales (idUsuario, comentario) VALUES (?, ?)";
        $stmtInsert = mysqli_prepare($conn, $queryInsert);
        mysqli_stmt_bind_param($stmtInsert, "is", $idUsuario, $comentario);
        
        if (mysqli_stmt_execute($stmtInsert)) {
            $mensaje = "Comentario publicado correctamente.";
            
            // Redireccionar para evitar reenvío del formulario
            header("Location: comentarios.php?success=1");
            exit();
        } else {
            $mensaje = "Error al publicar el comentario: " . mysqli_error($conn);
        }
    }
}

// Verificar si hay un mensaje de éxito desde la redirección
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $mensaje = "Comentario publicado correctamente.";
}

// Obtener comentarios globales
$queryComentarios = "SELECT cg.*, u.nombre as autor, u.idUsuario as idAutor
                   FROM comentarios_globales cg
                   INNER JOIN usuarios u ON cg.idUsuario = u.idUsuario
                   ORDER BY cg.fecha_creacion DESC
                   LIMIT 50";
$resultComentarios = mysqli_query($conn, $queryComentarios);

// Obtener usuarios activos
$queryUsuariosActivos = "SELECT u.idUsuario, u.nombre, 
                        (SELECT COUNT(*) FROM comentarios_globales WHERE idUsuario = u.idUsuario) as total_comentarios 
                        FROM usuarios u
                        ORDER BY total_comentarios DESC
                        LIMIT 5";
$resultUsuariosActivos = mysqli_query($conn, $queryUsuariosActivos);

include 'includes/header.php';
?>

<style>
.chat-container {
    height: calc(100vh - 250px);
    min-height: 500px;
    display: flex;
    flex-direction: column;
}

.chat-messages {
    flex-grow: 1;
    overflow-y: auto;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 10px;
}

.chat-input {
    padding: 15px;
    background-color: white;
    border-top: 1px solid #dee2e6;
}

.message-bubble {
    max-width: 80%;
    padding: 12px 15px;
    border-radius: 18px;
    margin-bottom: 15px;
    position: relative;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.message-bubble.outgoing {
    background-color: #007bff;
    color: white;
    margin-left: auto;
    border-bottom-right-radius: 5px;
}

.message-bubble.incoming {
    background-color: white;
    color: #212529;
    margin-right: auto;
    border-bottom-left-radius: 5px;
}

.message-header {
    display: flex;
    align-items: center;
    margin-bottom: 5px;
}

.message-time {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
    margin-left: auto;
}

.message-time.dark {
    color: #6c757d;
}

.message-text {
    word-break: break-word;
    font-size: 0.95rem;
    line-height: 1.4;
}

.message-avatar {
    margin-right: 10px;
}

.online-indicator {
    width: 10px;
    height: 10px;
    background-color: #28a745;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
}

.btn-emoji {
    background: none;
    border: none;
    font-size: 1.25rem;
    line-height: 1;
    padding: 0.375rem;
    cursor: pointer;
    transition: transform 0.15s;
}

.btn-emoji:hover {
    transform: scale(1.2);
}

.chat-header {
    padding: 15px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}

.users-online {
    max-height: 500px;
    overflow-y: auto;
}

.activity-log {
    max-height: 200px;
    overflow-y: auto;
}

.chat-date-divider {
    display: flex;
    align-items: center;
    margin: 20px 0;
    color: #6c757d;
}

.chat-date-divider::before,
.chat-date-divider::after {
    content: "";
    flex-grow: 1;
    height: 1px;
    background-color: #dee2e6;
}

.chat-date-divider::before {
    margin-right: 10px;
}

.chat-date-divider::after {
    margin-left: 10px;
}

.typing-indicator {
    display: flex;
    align-items: center;
    padding: 5px 10px;
    background-color: #f8f9fa;
    border-radius: 18px;
    width: fit-content;
    margin-bottom: 10px;
}

.typing-indicator span {
    height: 8px;
    width: 8px;
    border-radius: 50%;
    background-color: #007bff;
    display: inline-block;
    margin-right: 3px;
    animation: typing 1s infinite ease-in-out;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
    margin-right: 0;
}

@keyframes typing {
    0% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
    100% { transform: translateY(0); }
}
</style>

<div class="container mt-4">
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo (strpos($mensaje, 'Error') !== false) ? 'danger' : 'success'; ?> alert-dismissible fade show">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Chat Global</h5>
                    <div>
                        <span class="badge bg-success rounded-pill">
                            <?php
                                // Contar usuarios activos (últimos 15 minutos)
                                $queryUsuariosOnline = "SELECT COUNT(DISTINCT idUsuario) as total FROM comentarios_globales WHERE fecha_creacion > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
                                $resultUsuariosOnline = mysqli_query($conn, $queryUsuariosOnline);
                                $usuariosOnline = mysqli_fetch_assoc($resultUsuariosOnline);
                                echo $usuariosOnline['total'] . " online";
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="chat-container">
                    <div class="chat-messages" id="chat-messages">
                        <?php
                        $currentDate = '';
                        if ($resultComentarios && mysqli_num_rows($resultComentarios) > 0):
                            while ($comentario = mysqli_fetch_assoc($resultComentarios)):
                                // Formatear fecha para agrupar por día
                                $messageDate = date('Y-m-d', strtotime($comentario['fecha_creacion']));
                                $isToday = $messageDate === date('Y-m-d');
                                $isYesterday = $messageDate === date('Y-m-d', strtotime('-1 day'));
                                
                                // Mostrar divisor de fecha si cambia el día
                                if ($messageDate !== $currentDate):
                                    $currentDate = $messageDate;
                                    $displayDate = $isToday ? 'Hoy' : ($isYesterday ? 'Ayer' : date('d/m/Y', strtotime($comentario['fecha_creacion'])));
                        ?>
                        <div class="chat-date-divider">
                            <span><?php echo $displayDate; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Mensaje -->
                        <div class="d-flex align-items-start <?php echo $comentario['idAutor'] == $idUsuario ? 'justify-content-end' : ''; ?> mb-3">
                            <?php if ($comentario['idAutor'] != $idUsuario): ?>
                            <div class="message-avatar">
                                <?php echo generarAvatar($comentario['autor'], 'sm'); ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="message-bubble <?php echo $comentario['idAutor'] == $idUsuario ? 'outgoing' : 'incoming'; ?>">
                                <?php if ($comentario['idAutor'] != $idUsuario): ?>
                                <div class="fw-bold"><?php echo htmlspecialchars($comentario['autor']); ?></div>
                                <?php endif; ?>
                                
                                <div class="message-text">
                                    <?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?>
                                </div>
                                
                                <div class="message-time <?php echo $comentario['idAutor'] == $idUsuario ? '' : 'dark'; ?> mt-1">
                                    <?php echo date('H:i', strtotime($comentario['fecha_creacion'])); ?>
                                </div>
                                
                                <?php if ($comentario['idAutor'] == $idUsuario): ?>
                                <div class="dropdown position-absolute top-0 end-0 mt-1 me-2">
                                    <button class="btn btn-sm text-white p-0" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editarComentarioModal" data-id="<?php echo $comentario['id']; ?>" data-texto="<?php echo htmlspecialchars($comentario['comentario']); ?>"><i class="fas fa-edit me-2"></i>Editar</a></li>
                                        <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#eliminarComentarioModal" data-id="<?php echo $comentario['id']; ?>"><i class="fas fa-trash me-2"></i>Eliminar</a></li>
                                    </ul>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-comments fa-4x text-muted"></i>
                            </div>
                            <h5>No hay mensajes aún</h5>
                            <p class="text-muted">¡Sé el primero en enviar un mensaje!</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chat-input">
                        <form method="POST" action="" id="chat-form">
                            <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                            <div class="input-group">
                                <button type="button" class="btn btn-emoji" data-bs-toggle="tooltip" title="Emojis">
                                    <i class="far fa-smile"></i>
                                </button>
                                <textarea class="form-control" name="comentario_global" id="comentario_global" rows="1" placeholder="Escribe un mensaje..." required></textarea>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3">
            <!-- Panel del usuario -->
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <?php echo generarAvatar($nombre, 'md'); ?>
                    <h5 class="mt-3 mb-0"><?php echo htmlspecialchars($nombre); ?></h5>
                    <p class="text-muted small mb-3">Online ahora</p>
                    
                    <div class="d-grid">
                        <a href="perfil.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-user-edit me-2"></i>Editar perfil
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Usuarios online -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Usuarios Activos</h5>
                </div>
                <div class="card-body p-0">
                    <div class="users-online">
                        <ul class="list-group list-group-flush">
                            <?php
                            if ($resultUsuariosActivos && mysqli_num_rows($resultUsuariosActivos) > 0):
                                while ($usuario = mysqli_fetch_assoc($resultUsuariosActivos)):
                                    if ($usuario && $usuario['idUsuario'] != $idUsuario): // No mostrar al usuario actual
                            ?>
                            <li class="list-group-item d-flex align-items-center">
                                <?php echo generarAvatar($usuario['nombre'], 'sm'); ?>
                                <div class="ms-3">
                                    <div class="fw-bold"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                                    <small class="text-muted"><?php echo $usuario['total_comentarios']; ?> mensajes</small>
                                </div>
                                
                                <?php
                                // Verificar si existe la tabla seguidores
                                $tablaSeguidoresExiste = false;
                                $queryCheckSeguidores = "SHOW TABLES LIKE 'seguidores'";
                                $resultCheckSeguidores = mysqli_query($conn, $queryCheckSeguidores);
                                if ($resultCheckSeguidores && mysqli_num_rows($resultCheckSeguidores) > 0):
                                    $tablaSeguidoresExiste = true;
                                    
                                    // Verificar si sigue a este usuario
                                    $siguiendo = false;
                                    $querySiguiendo = "SELECT * FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?";
                                    $stmtSiguiendo = mysqli_prepare($conn, $querySiguiendo);
                                    mysqli_stmt_bind_param($stmtSiguiendo, "ii", $idUsuario, $usuario['idUsuario']);
                                    mysqli_stmt_execute($stmtSiguiendo);
                                    $resultSiguiendo = mysqli_stmt_get_result($stmtSiguiendo);
                                    $siguiendo = mysqli_num_rows($resultSiguiendo) > 0;
                                ?>
                                <a href="seguir-usuario.php?id=<?php echo $usuario['idUsuario']; ?>&token=<?php echo generarTokenCSRF(); ?>" class="btn btn-sm <?php echo $siguiendo ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill ms-auto">
                                    <?php echo $siguiendo ? '<i class="fas fa-check"></i>' : '<i class="fas fa-plus"></i>'; ?>
                                </a>
                                <?php else: ?>
                                <button class="btn btn-sm btn-outline-primary rounded-pill ms-auto">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <?php endif; ?>
                            </li>
                            <?php 
                                    endif;
                                endwhile;
                            else:
                            ?>
                            <li class="list-group-item text-center py-3">
                                <p class="text-muted mb-0">No hay usuarios activos</p>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Enlaces rápidos -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Enlaces Rápidos</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="comunidades.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-users me-2"></i>Comunidades
                        </a>
                        <a href="reportes.php" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-exclamation-triangle me-2"></i>Reportes
                        </a>
                        <a href="perfil.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-user me-2"></i>Mi Perfil
                        </a>
                    </div>
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
                <h5 class="modal-title" id="editarComentarioModalLabel">Editar mensaje</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="editar-comentario-global.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                    <input type="hidden" name="id_comentario" id="id-comentario-editar">
                    <div class="mb-3">
                        <label for="texto-comentario" class="form-label">Mensaje</label>
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
                <p>¿Estás seguro de que deseas eliminar este mensaje? Esta acción no se puede deshacer.</p>
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
    // Scroll al fondo del chat al cargar
    const chatMessages = document.getElementById('chat-messages');
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Auto-expandir textarea
    const textarea = document.getElementById('comentario_global');
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
        // Limitar a 5 líneas
        if (this.scrollHeight > 150) {
            this.style.height = '150px';
        }
    });
    
    // Enviar con Enter (pero nueva línea con Shift+Enter)
    textarea.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chat-form').submit();
        }
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
            confirmarBtn.href = 'eliminar-comentario-global.php?id=' + id + '&token=<?php echo generarTokenCSRF(); ?>';
        });
    }
    
    // Activar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include 'includes/footer.php'; ?>