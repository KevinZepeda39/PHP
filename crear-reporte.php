<?php
session_start();
require 'includes/conexion.php';
require 'includes/models/reporte.php';

// Verificar si el usuario está logueado
$loggedIn = isset($_SESSION['idUsuario']);
$nombreUsuario = $loggedIn ? $_SESSION['nombre'] : '';

// Si no está logueado, redirigir al login
if (!$loggedIn) {
    header("Location: login.php?redirect=crear-reporte.php");
    exit();
}

$error = '';
$success = '';

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reporteModel = new Reporte($conn);
    
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $ubicacion = $_POST['ubicacion'] ?? '';
    $tipo = $_POST['tipo'] ?? 'general';
    $urgente = isset($_POST['urgente']);
    
    // Validaciones
    if (empty($titulo)) {
        $error = "El título es obligatorio";
    } elseif (empty($descripcion)) {
        $error = "La descripción es obligatoria";
    } else {
        // Manejar la imagen si se subió
        $imagen = null;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $imagen = $_FILES['imagen'];
            
            // Validar tipo de imagen
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($imagen['type'], $allowed_types)) {
                $error = "Solo se permiten imágenes JPG, PNG o GIF";
            }
            
            // Validar tamaño (máximo 5MB)
            if ($imagen['size'] > 5 * 1024 * 1024) {
                $error = "La imagen no debe superar los 5MB";
            }
        }
        
        if (!$error) {
            $idReporte = $reporteModel->crear($titulo, $descripcion, $_SESSION['idUsuario'], $ubicacion, $tipo, $urgente, $imagen);
            
            if ($idReporte) {
                $_SESSION['mensaje'] = "¡Reporte creado exitosamente!";
                header("Location: reportes.php");
                exit();
            } else {
                $error = "Error al crear el reporte. Por favor, intenta nuevamente.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Reporte - MiCiudadSV</title>
    <!-- ... (resto del head) ... -->
</head>
<body>
    <!-- ... (navbar) ... -->

    <!-- Contenido principal -->
    <section class="content-section">
        <div class="container">
            <h1 class="page-title">Crear Nuevo Reporte</h1>
            
            <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <!-- Formulario principal -->
                    <div class="card">
                        <div class="card-header">
                            Información del reporte
                        </div>
                        <div class="card-body">
                            <form id="reportForm" method="POST" action="" enctype="multipart/form-data">
                                <!-- Tipo de reporte -->
                                <div class="mb-4">
                                    <label class="form-label">Tipo de Reporte</label>
                                    <div class="report-type-container">
                                        <div class="report-type active" data-type="emergency">
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                            <div class="report-type-text">Emergencia</div>
                                        </div>
                                        <div class="report-type" data-type="infrastructure">
                                            <i class="bi bi-tools"></i>
                                            <div class="report-type-text">Infraestructura</div>
                                        </div>
                                        <div class="report-type" data-type="security">
                                            <i class="bi bi-shield-fill-check"></i>
                                            <div class="report-type-text">Seguridad</div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="tipo" id="reportType" value="emergency">
                                </div>
                                
                                <!-- Título del reporte -->
                                <div class="mb-3">
                                    <label for="titulo" class="form-label">Título del reporte *</label>
                                    <input type="text" class="form-control" id="titulo" name="titulo" required 
                                           placeholder="Escribe un título descriptivo">
                                </div>
                                
                                <!-- Ubicación -->
                                <div class="mb-3">
                                    <label for="ubicacion" class="form-label">Ubicación</label>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="ubicacion" name="ubicacion" 
                                               placeholder="Ingresa la dirección o selecciona en el mapa">
                                        <button class="btn btn-outline-secondary" type="button" id="useMyLocation">
                                            <i class="bi bi-geo-alt-fill"></i> Mi ubicación
                                        </button>
                                    </div>
                                    <div id="mapContainer"></div>
                                </div>
                                
                                <!-- Descripción -->
                                <div class="mb-4">
                                    <label for="descripcion" class="form-label">Descripción *</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required
                                              placeholder="Describe el incidente con el mayor detalle posible"></textarea>
                                </div>
                                
                                <!-- Subir fotos -->
                                <div class="mb-4">
                                    <label class="form-label">Foto (opcional)</label>
                                    <div class="file-upload">
                                        <input type="file" class="file-upload-input" id="imagen" name="imagen" accept="image/*">
                                        <div class="file-upload-button">
                                            <i class="bi bi-camera-fill"></i> Añadir foto
                                        </div>
                                    </div>
                                    <div class="file-preview" id="imagePreview"></div>
                                </div>
                                
                                <!-- Marcar como urgente -->
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="urgente" name="urgente">
                                        <label class="form-check-label" for="urgente">
                                            Marcar como urgente
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Botones de acción -->
                                <div class="d-flex justify-content-end">
                                    <a href="reportes.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                                    <button type="submit" class="btn btn-primary">Enviar reporte</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- ... (sidebar con consejos) ... -->
            </div>
        </div>
    </section>

    <!-- ... (footer) ... -->

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Manejar tipo de reporte
            const reportTypes = document.querySelectorAll('.report-type');
            const reportTypeInput = document.getElementById('reportType');
            
            reportTypes.forEach(type => {
                type.addEventListener('click', function() {
                    reportTypes.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    reportTypeInput.value = this.dataset.type;
                });
            });
            
            // ... (resto del JavaScript para mapa y preview de imagen) ...
        });
    </script>
</body>
</html>