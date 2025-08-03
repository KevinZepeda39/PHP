<?php


session_start();
require_once 'includes/conexion.php';
require_once 'includes/functions.php';
require_once 'includes/models/reporte.php';
    
// Verificar si el usuario está autenticado
verificarLogin();

// Crear instancia del modelo de reporte
$reporteModel = new Reporte($conn);

// Obtener los reportes más recientes (limitado a 5)
$reportesRecientes = $reporteModel->listar(['limite' => 5]);

// Obtener estadísticas
$totalReportes = count($reporteModel->listar());
$reportesResueltos = 0; // Aquí podrías contar los reportes resueltos
$usuariosActivos = 0; // Aquí podrías contar los usuarios activos

// Esta consulta cuenta todos los usuarios
$queryUsuarios = "SELECT COUNT(*) as total FROM usuarios";
$resultUsuarios = mysqli_query($conn, $queryUsuarios);
if($resultUsuarios) {
    $row = mysqli_fetch_assoc($resultUsuarios);
    $usuariosActivos = $row['total'];
}
?>

    


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiCuidadSV</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- Estilos personalizados -->
    <style>
        :root {
            --primary: #3a6efd;
            --secondary: #f8f9fa;
            --text-dark: #212529;
            --text-light: #6c757d;
            --success: #8cd2af;
            --warning: #ffe0a3;
            --danger: #ff6a6a;
            --info: #58a8ff;
            --process: #5c8dff;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
            background-color: #ffffff;
            overflow-x: hidden;
        }
        
        /* Navbar */
        .navbar {
            padding: 15px 0;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
        }
        
        .navbar-logo {
            height: 40px;
            width: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }
        
        .navbar-brand span {
            font-weight: 700;
            font-size: 20px;
            margin-left: 10px;
        }
        
        .nav-link {
            color: var(--text-dark) !important;
            font-weight: 500;
            padding: 8px 16px !important;
        }
        
        .notification-badge {
            position: relative;
            background-color: var(--danger);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            margin-right: 15px;
        }
        
        .btn-auth {
            border-radius: 5px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-sign-in {
            background-color: white;
            color: var(--primary);
            border: 1px solid #dee2e6;
        }
        
        .btn-register {
            background-color: var(--primary);
            color: white;
            border: none;
        }
        
        /* Sections */
        .section {
            padding: 60px 0;
        }
        
        .section-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .section-subtitle {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .section-text {
            color: var(--text-light);
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.6;
        }
        
        /* Cards */
        .feature-card {
            border-radius: 16px;
            padding: 30px;
            background-color: white;
            border: 1px solid #f1f1f1;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .icon-report {
            background-color: #e6f0ff;
            color: var(--primary);
        }
        
        .icon-edit {
            background-color: #f0e6ff;
            color: #7c58ff;
        }
        
        .icon-observe {
            background-color: #e6fff0;
            color: #58ff8e;
        }
        
        .feature-title {
            font-weight: 700;
            font-size: 20px;
            margin-bottom: 15px;
        }
        
        .feature-text {
            color: var(--text-light);
            margin-bottom: 20px;
            font-size: 15px;
            line-height: 1.5;
        }
        
        .feature-link {
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        
        .feature-link:hover {
            text-decoration: underline;
        }
        
        /* Map Preview */
        .map-preview {
            background-color: #f8f9fa;
            border-radius: 16px;
            height: 220px;
            position: relative;
            overflow: hidden;
        }
        
        .map-dot {
            position: absolute;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .map-dot.red {
            background-color: #ff5c5c;
            top: 50px;
            right: 80px;
        }
        
        .map-dot.blue {
            background-color: #5c8dff;
            top: 90px;
            left: 55%;
        }
        
        .map-dot.yellow {
            background-color: #ffcc29;
            top: 120px;
            left: 40%;
        }
        
        .map-dot.purple {
            background-color: #9747ff;
            bottom: 70px;
            left: 45%;
        }
        
        .map-dot.green {
            background-color: #4cd471;
            right: 30%;
            bottom: 50px;
        }
        
        .map-label {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #aaa;
            font-size: 16px;
        }
        
        .map-controls {
            position: absolute;
            right: 10px;
            top: 10px;
            display: flex;
            flex-direction: column;
        }
        
        .map-control-btn {
            width: 30px;
            height: 30px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 500;
            color: #666;
        }
        
        /* Stats */
        .stats-card {
            background-color: var(--secondary);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            height: 100%;
        }
        
        .stats-card.blue {
            background-color: #e6f0ff;
        }
        
        .stats-card.green {
            background-color: #e6fff0;
        }
        
        .stats-card.purple {
            background-color: #f0e6ff;
        }
        
        .stats-card.yellow {
            background-color: #fffbe6;
        }
        
        .stats-number {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .stats-card.blue .stats-number {
            color: #5c8dff;
        }
        
        .stats-card.green .stats-number {
            color: #4cd471;
        }
        
        .stats-card.purple .stats-number {
            color: #9747ff;
        }
        
        .stats-card.yellow .stats-number {
            color: #ffcc29;
        }
        
        .stats-label {
            color: var(--text-light);
            font-size: 14px;
        }
        
        /* Reports */
        .report-card {
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #f1f1f1;
            background-color: white;
            margin-bottom: 15px;
            position: relative;
        }
        
        .report-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            border-radius: 4px 0 0 4px;
        }
        
        .report-card.red::before {
            background-color: var(--danger);
        }
        
        .report-card.blue::before {
            background-color: var(--process);
        }
        
        .report-card.green::before {
            background-color: var(--success);
        }
        
        .report-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .report-meta {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 5px;
        }
        
        .report-status {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: var(--warning);
            color: #b45309;
        }
        
        .status-processing {
            background-color: #d6e4ff;
            color: #1864ab;
        }
        
        .status-resolved {
            background-color: var(--success);
            color: #2b8a3e;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            border-radius: 5px;
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-outline-primary {
            border-color: var(--primary);
            color: var(--primary);
            border-radius: 5px;
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-primary:hover, .btn-outline-primary:hover {
            background-color: #2a5eef;
            border-color: #2a5eef;
            color: white;
        }
        
        /* Footer */
        .footer {
            background-color: #1e2936;
            color: white;
            padding: 50px 0 20px;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .footer-icon {
            height: 40px;
            width: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }
        
        .footer-brand {
            font-weight: 700;
            font-size: 20px;
            margin-left: 10px;
        }
        
        .footer-text {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            max-width: 300px;
            margin-bottom: 20px;
        }
        
        .footer-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .footer-newsletter {
            margin-top: 20px;
        }
        
        .footer-newsletter p {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .newsletter-form {
            display: flex;
        }
        
        .newsletter-input {
            flex-grow: 1;
            height: 40px;
            border-radius: 5px 0 0 5px;
            border: none;
            padding: 0 15px;
            font-size: 14px;
            background-color: #2c3747;
            color: white;
        }
        
        .newsletter-input::placeholder {
            color: rgba(255,255,255,0.5);
        }
        
        .newsletter-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            height: 40px;
            padding: 0 15px;
            border-radius: 0 5px 5px 0;
            font-weight: 500;
            cursor: pointer;
        }
        
        .footer-copyright {
            text-align: center;
            padding-top: 20px;
            margin-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.5);
            font-size: 14px;
        }


        /* Agregar estos estilos al final de tu sección de estilos */

/* Dropdown del usuario */
.dropdown-menu {
    border: 1px solid rgba(0,0,0,0.1);
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 8px;
    padding: 0.5rem;
}

.dropdown-item {
    border-radius: 6px;
    padding: 0.5rem 1rem;
    display: flex;
    align-items: center;
    color: var(--text-dark);
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background-color: var(--secondary);
    color: var(--primary);
}

.dropdown-item.text-danger:hover {
    background-color: #fee2e2;
    color: #dc2626;
}

.dropdown-divider {
    margin: 0.5rem 0;
}

.btn-secondary {
    background-color: white;
    color: var(--text-dark);
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 8px 16px;
    font-weight: 500;
    display: flex;
    align-items: center;
}

.btn-secondary:hover {
    background-color: var(--secondary);
    color: var(--primary);
    border-color: var(--primary);
}

.bi {
    display: inline-block;
    vertical-align: -0.125em;
}

    </style>
</head>
<body>
   <!-- Barra de navegación -->
   <nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">
        <a class="navbar-brand" href="#">
            <div class="navbar-logo">Sv</div>
            <span>MiCiudadSV</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="informacion.php">Información</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reportes.php">Reportes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Estadísticas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="comunidades.php">Comunidad</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Ayuda</a>
                </li>
            </ul>
            
            <div class="d-flex align-items-center">
                <div class="notification-badge">2</div>
                <?php if(isset($_SESSION['idUsuario'])): ?>
                    <!-- Mostrar información del usuario cuando está logueado -->
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person me-2"></i>Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="mis-reportes.php"><i class="bi bi-file-text me-2"></i>Mis Reportes</a></li>
                            <li><a class="dropdown-item" href="comunidades.php"><i class="bi bi-people me-2"></i>Mis Comunidades</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Mostrar botones de login/registro cuando NO está logueado -->
                    <a href="login.php" class="btn btn-auth btn-sign-in me-2">Iniciar Sesión</a>
                    <a href="registro.php" class="btn btn-auth btn-register">Registrarse</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

    <!-- Sección de contribución -->
    <section class="section">
        <div class="container">
            <h2 class="section-title">¿Cómo puedes contribuir?</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon icon-report">
                            <i class="bi bi-pin-map-fill"></i>
                        </div>
                        <h3 class="feature-title">Ingresar Reporte</h3>
                        <p class="feature-text">
                            Informa sobre situaciones de riesgo, problemas de infraestructura o incidentes que requieran atención inmediata. Tu aporte puede salvar vidas.
                        </p>
                        <a href="reportar.php" class="feature-link">
                            Crear un reporte →
                        </a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon icon-edit">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                        <h3 class="feature-title">Editar tu reporte</h3>
                        <p class="feature-text">
                            Puedes ingresar más información a un reporte y actualizar su estado para mantener a la comunidad informada sobre su evolución.
                        </p>
                        <a href="mis-reportes.php" class="feature-link">
                            Mis reportes →
                        </a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon icon-observe">
                            <i class="bi bi-eye-fill"></i>
                        </div>
                        <h3 class="feature-title">Observar</h3>
                        <p class="feature-text">
                            Ver la información publicada por otras personas e informarte sobre problemas cercanos a tu ubicación para estar preparado.
                        </p>
                        <a href="mapa.php" class="feature-link">
                            Explorar mapa →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de mapa -->
    <section class="section bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <h2 class="section-subtitle">Mapa de Incidentes en Tiempo Real</h2>
                    <p class="section-text">
                        Visualiza incidentes reportados por la comunidad en tiempo real. Filtra por tipo de reporte, nivel de urgencia o zona para mantenerte informado.
                    </p>
                    <a href="mapa.php" class="btn btn-primary">Ver mapa completo</a>
                </div>
                <div class="col-lg-7">
                    <div class="map-preview">
                        <div class="map-dot red"></div>
                        <div class="map-dot blue"></div>
                        <div class="map-dot yellow"></div>
                        <div class="map-dot purple"></div>
                        <div class="map-dot green"></div>
                        <div class="map-label">Vista previa del mapa</div>
                        <div class="map-controls">
                            <button class="map-control-btn">+</button>
                            <button class="map-control-btn">−</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de estadísticas -->
    <section class="section">
        <div class="container">
            <h2 class="section-title">Impacto Comunitario</h2>
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card blue">
                        <div class="stats-number"><?php echo $totalReportes; ?></div>
                        <div class="stats-label">Total Reportes</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card green">
                        <div class="stats-number">0</div>
                        <div class="stats-label">Reportes Resueltos</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card purple">
                        <div class="stats-number"><?php echo $usuariosActivos; ?></div>
                        <div class="stats-label">Usuarios Activos</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card yellow">
                        <div class="stats-number">0</div>
                        <div class="stats-label">Comunidades Seguras</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de reportes recientes -->
    <section class="section bg-light">
        <div class="container">
            <h2 class="section-subtitle">Reportes Recientes</h2>
            <div class="row">
                <div class="col-lg-12">
                    <?php if(!empty($reportesRecientes)): ?>
                        <?php foreach($reportesRecientes as $reporte): ?>
                            <?php
                            // Determinar el color y estado del reporte
                            $cardColor = 'blue'; // Por defecto
                            $statusClass = 'status-pending';
                            $statusText = 'Pendiente';
                            
                            // Calcular tiempo transcurrido
                            $fechaCreacion = new DateTime($reporte['fechaCreacion']);
                            $ahora = new DateTime();
                            $intervalo = $fechaCreacion->diff($ahora);
                            
                            if($intervalo->days > 0) {
                                $tiempoTranscurrido = "Hace " . $intervalo->days . " días";
                            } elseif($intervalo->h > 0) {
                                $tiempoTranscurrido = "Hace " . $intervalo->h . " horas";
                            } elseif($intervalo->i > 0) {
                                $tiempoTranscurrido = "Hace " . $intervalo->i . " minutos";  
                            } else {
                                $tiempoTranscurrido = "Hace unos segundos";
                            }
                            ?>
                            <div class="report-card <?php echo $cardColor; ?>">
                                <h3 class="report-title"><?php echo htmlspecialchars($reporte['titulo']); ?></h3>
                                <div class="report-meta">
                                    <?php echo $tiempoTranscurrido; ?> • Por <?php echo htmlspecialchars($reporte['nombreCreador']); ?>
                                </div>
                                <span class="report-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">No hay reportes recientes</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-4">
                        <a href="reportes.php" class="btn btn-outline-primary">Ver todos los reportes</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row mb-4">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="footer-logo">
                        <div class="footer-icon">MC</div>
                        <span class="footer-brand">MiCiudadSos</span>
                    </div>
                    <p class="footer-text">Plataforma ciudadana para reportes comunitarios y prevención de riesgos.</p>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-4 mb-md-0">
                    <h4 class="footer-title">Enlaces</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="mapa.php">Mapa</a></li>
                        <li><a href="reportes.php">Reportes</a></li>
                        <li><a href="estadisticas.php">Estadísticas</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-4 mb-md-0">
                    <h4 class="footer-title">Recursos</h4>
                    <ul class="footer-links">
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Preguntas frecuentes</a></li>
                        <li><a href="#">Tutoriales</a></li>
                        <li><a href="#">Contacto</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4">
                    <h4 class="footer-title">Conecta con nosotros</h4>
                    <p>Suscríbete para recibir alertas y actualizaciones.</p>
                    <div class="newsletter-form">
                        <input type="email" class="newsletter-input" placeholder="Tu correo electrónico">
                        <button type="submit" class="newsletter-btn">Enviar</button>
                    </div>
                </div>
            </div>
            <div class="footer-copyright">
                <p>© 2025 MiCiudadSos. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>



    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>