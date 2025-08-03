<?php
session_start();
require 'includes/conexion.php';
require 'includes/models/reporte.php';

// Verificar si el usuario está logueado
$loggedIn = isset($_SESSION['idUsuario']);
$nombreUsuario = $loggedIn ? $_SESSION['nombre'] : '';

// Si no está logueado, redirigir al login
if (!$loggedIn) {
    header("Location: login.php?redirect=reportar.php");
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- Leaflet CSS for maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <!-- Estilos personalizados -->
    <style>
        /* ... (todos los estilos que ya tienes) ... */
    </style>
</head>
<body>
    <!-- Barra de navegación -->
    <?php include 'includes/header.php'; ?>

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
                                    <label for="titulo" class="form-label">Título del reporte</label>
                                    <input type="text" class="form-control" id="titulo" name="titulo" required 
                                           placeholder="Escribe un título descriptivo (Ej. Accidente en Av. Principal)"
                                           value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>">
                                </div>
                                
                                <!-- Ubicación -->
                                <div class="mb-3">
                                    <label for="ubicacion" class="form-label">Ubicación</label>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="ubicacion" name="ubicacion" 
                                               placeholder="Ingresa la dirección o selecciona en el mapa"
                                               value="<?php echo isset($_POST['ubicacion']) ? htmlspecialchars($_POST['ubicacion']) : ''; ?>">
                                        <button class="btn btn-outline-secondary" type="button" id="useMyLocation">
                                            <i class="bi bi-geo-alt-fill"></i> Mi ubicación
                                        </button>
                                    </div>
                                    <div id="mapContainer"></div>
                                </div>
                                
                                <!-- Descripción -->
                                <div class="mb-4">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required
                                              placeholder="Describe el incidente con el mayor detalle posible"><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                                </div>
                                
                                <!-- Subir fotos -->
                                <div class="mb-4">
                                    <label class="form-label">Foto (opcional)</label>
                                    <div class="file-upload">
                                        <input type="file" class="file-upload-input" id="imagen" name="imagen" accept="image/*">
                                        <div class="file-upload-button">
                                            <i class="bi bi-camera-fill"></i> Añadir fotos
                                        </div>
                                    </div>
                                    <div class="file-preview" id="imagePreview"></div>
                                    <small class="text-muted">Máximo 5MB. Formatos: JPG, PNG, GIF</small>
                                </div>
                                
                                <!-- Marcar como urgente -->
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="urgente" name="urgente" 
                                               <?php echo isset($_POST['urgente']) ? 'checked' : ''; ?>>
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
                
                <div class="col-lg-4">
                    <!-- Consejos y recomendaciones -->
                    <div class="card">
                        <div class="card-header">
                            Consejos para un buen reporte
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li class="mb-2">Incluye detalles específicos sobre la ubicación.</li>
                                <li class="mb-2">Describe claramente lo que está sucediendo.</li>
                                <li class="mb-2">Agrega fotos si es posible para mejor visualización.</li>
                                <li class="mb-2">Marca como urgente solo en casos que requieran atención inmediata.</li>
                                <li>Actualiza el reporte si hay cambios en la situación.</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Reportes recientes cercanos -->
                    <div class="card mt-4">
                        <div class="card-header">
                            Reportes recientes
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php
                                // Obtener los últimos 3 reportes
                                $reporteModel = new Reporte($conn);
                                $reportesRecientes = $reporteModel->listar(['limite' => 3]);
                                
                                foreach ($reportesRecientes as $reporte):
                                ?>
                                <a href="ver-reporte.php?id=<?php echo $reporte['idReporte']; ?>" 
                                   class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($reporte['titulo']); ?></h6>
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    </div>
                                    <p class="mb-1 text-muted small">
                                        <?php echo htmlspecialchars(substr($reporte['descripcion'], 0, 50)) . '...'; ?>
                                    </p>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($reporte['fechaCreacion'])); ?>
                                    </small>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Leaflet JS for maps -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    
    <!-- Script personalizado -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Activar los botones de tipo de reporte
            const reportTypes = document.querySelectorAll('.report-type');
            const reportTypeInput = document.getElementById('reportType');
            
            reportTypes.forEach(type => {
                type.addEventListener('click', function() {
                    reportTypes.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    reportTypeInput.value = this.dataset.type;
                });
            });
           
            // Previsualización de imagen
            const fileInput = document.getElementById('imagen');
            const imagePreview = document.getElementById('imagePreview');
           
            fileInput.addEventListener('change', function() {
                imagePreview.innerHTML = '';
               
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    if (!file.type.match('image.*')) {
                        alert('Por favor selecciona una imagen válida');
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'file-preview-item';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        
                        const removeBtn = document.createElement('div');
                        removeBtn.className = 'remove-btn';
                        removeBtn.innerHTML = '<i class="bi bi-x"></i>';
                        removeBtn.addEventListener('click', function() {
                            previewItem.remove();
                            fileInput.value = '';
                        });
                        
                        previewItem.appendChild(img);
                        previewItem.appendChild(removeBtn);
                        imagePreview.appendChild(previewItem);
                    }
                    
                    reader.readAsDataURL(file);
                }
            });
           
            // Mapa
            const map = L.map('mapContainer').setView([13.6929, -89.2182], 8);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            let marker;
            
            map.on('click', function(e) {
                if (marker) {
                    map.removeLayer(marker);
                }
                
                marker = L.marker(e.latlng).addTo(map);
                document.getElementById('ubicacion').value = `Lat: ${e.latlng.lat.toFixed(5)}, Lng: ${e.latlng.lng.toFixed(5)}`;
            });
            
            // Usar mi ubicación
            const useMyLocationBtn = document.getElementById('useMyLocation');
            useMyLocationBtn.addEventListener('click', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        if (marker) {
                            map.removeLayer(marker);
                        }
                        
                        marker = L.marker([lat, lng]).addTo(map);
                        map.setView([lat, lng], 15);
                        
                        document.getElementById('ubicacion').value = `Lat: ${lat.toFixed(5)}, Lng: ${lng.toFixed(5)}`;
                    }, function() {
                        alert('No se pudo acceder a tu ubicación. Por favor, permite el acceso o ingresa la ubicación manualmente.');
                    });
                } else {
                    alert('Tu navegador no soporta geolocalización. Por favor, ingresa la ubicación manualmente.');
                }
            });
        });
    </script>
</body>
</html>