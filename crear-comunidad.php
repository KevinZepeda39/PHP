<?php
require 'includes/conexion.php';
require 'includes/auth.php';
require 'includes/models/modelo-comunidad.php';  // Actualiza esta línea

// Verificar que el usuario esté logueado
if (!isset($_SESSION['idUsuario'])) {
    header("Location: login.php?redirect=crear-comunidad.php");
    exit();
}

$error = '';
$success = '';

// Instanciar modelo de comunidad
$comunidadModel = new Comunidad($conn);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = isset($_POST['titulo']) ? $_POST['titulo'] : '';
    $descripcion = isset($_POST['descripcion']) ? $_POST['descripcion'] : '';
    $categoria = isset($_POST['categoria']) ? $_POST['categoria'] : '';
    $tags = isset($_POST['tags']) ? $_POST['tags'] : '';
    
    // Validaciones
    if (empty($titulo)) {
        $error = "El título de la comunidad es obligatorio";
    } elseif (strlen($titulo) < 3) {
        $error = "El título debe tener al menos 3 caracteres";
    } elseif (strlen($titulo) > 50) {
        $error = "El título no puede exceder los 50 caracteres";
    } elseif (empty($descripcion)) {
        $error = "La descripción es obligatoria";
    } elseif (strlen($descripcion) > 500) {
        $error = "La descripción no puede exceder los 500 caracteres";
    } elseif (empty($categoria)) {
        $error = "La categoría es obligatoria";
    } elseif (!in_array($categoria, ['vecinos', 'seguridad', 'eventos', 'infraestructura', 'servicios', 'otros'])) {
        $error = "Categoría no válida";
    } else {
        // Crear comunidad
        $idComunidad = $comunidadModel->crear($titulo, $descripcion, $_SESSION['idUsuario'], $categoria, $tags);
        
        if ($idComunidad) {
            $success = "¡Comunidad creada con éxito!";
            $_SESSION['mensaje'] = $success;
            header("Location: comunidad.php?id=" . $idComunidad);
            exit();
        } else {
            $error = "Error al crear la comunidad. Inténtalo de nuevo.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Comunidad | Mi Ciudad SV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .create-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        .create-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 40px;
        }
        
        .create-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .create-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .create-header p {
            font-size: 1.1rem;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
        }
        
        .form-control-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: white;
        }
        
        .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
        }
        
        .form-helper {
            font-size: 0.9rem;
            color: #999;
            margin-top: 5px;
        }
        
        .btn-primary {
            background-color: #3498db;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: #f1f3f5;
            border: none;
            color: #666;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background-color: #e9ecef;
            color: #333;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }
        
        .alert {
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 25px;
        }
        
        .benefits-section {
            margin-top: 60px;
            text-align: center;
        }
        
        .benefits-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 30px;
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .benefit-card {
            padding: 30px;
            background: #f8f9fa;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .benefit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .benefit-icon {
            font-size: 2.5rem;
            color: #3498db;
            margin-bottom: 15px;
        }
        
        .benefit-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .benefit-text {
            font-size: 0.95rem;
            color: #666;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="create-container">
        <div class="create-card">
            <div class="create-header">
                <h1>Crear Nueva Comunidad</h1>
                <p>Conecta con personas que comparten tus intereses</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="titulo">Nombre de la Comunidad *</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" 
                           placeholder="Ej: Vecinos de Colonia San Benito"
                           value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>"
                           required>
                    <div class="form-helper">Entre 3 y 50 caracteres</div>
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción *</label>
                    <textarea class="form-control form-control-textarea" id="descripcion" name="descripcion" 
                              placeholder="Describe el propósito y actividades de tu comunidad..."
                              required><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                    <div class="form-helper">Máximo 500 caracteres</div>
                </div>
                
                <div class="form-group">
                    <label for="categoria">Categoría *</label>
                    <select class="form-select" id="categoria" name="categoria" required>
                        <option value="">Selecciona una categoría</option>
                        <option value="vecinos" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'vecinos') ? 'selected' : ''; ?>>Vecinos</option>
                        <option value="seguridad" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'seguridad') ? 'selected' : ''; ?>>Seguridad</option>
                        <option value="eventos" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'eventos') ? 'selected' : ''; ?>>Eventos</option>
                        <option value="infraestructura" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'infraestructura') ? 'selected' : ''; ?>>Infraestructura</option>
                        <option value="servicios" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'servicios') ? 'selected' : ''; ?>>Servicios</option>
                        <option value="otros" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'otros') ? 'selected' : ''; ?>>Otros</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="tags">Etiquetas</label>
                    <input type="text" class="form-control" id="tags" name="tags" 
                           placeholder="Ej: vecinos, comunidad, ayuda mutua"
                           value="<?php echo isset($_POST['tags']) ? htmlspecialchars($_POST['tags']) : ''; ?>">
                    <div class="form-helper">Separa las etiquetas con comas</div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='comunidades.php'">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Crear Comunidad
                    </button>
                </div>
            </form>
        </div>
        
        <div class="benefits-section">
            <h2 class="benefits-title">¿Por qué crear una comunidad?</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="benefit-title">Conecta con vecinos</h3>
                    <p class="benefit-text">Reúne a personas de tu colonia o barrio</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="benefit-title">Mejora la seguridad</h3>
                    <p class="benefit-text">Comparte alertas y coordina seguridad vecinal</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="benefit-title">Organiza eventos</h3>
                    <p class="benefit-text">Planifica actividades comunitarias y festividades</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>