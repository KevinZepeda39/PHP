<?php
session_start(); // ESTO ES CRUCIAL - DEBE IR AL INICIO
require 'includes/conexion.php';

// Verificar si el usuario está logueado
$loggedIn = isset($_SESSION['idUsuario']);
$nombreUsuario = $loggedIn ? $_SESSION['nombre'] : '';

// Mensaje de depuración (opcional, quitar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chain App - Página de Inicio</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- Estilos personalizados -->
    <style>
       :root {
            /* Definir variables CSS */
            --primary: #4e67f7;
            --danger: #ff6a6a;
            --text-dark: #212529;
            --text-light: #6c757d;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
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
        
        /* Estilos para el dropdown del usuario */
        .auth-buttons {
            gap: 0.5rem;
        }

        .dropdown-menu {
            min-width: 200px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
            padding: 0.5rem 0;
        }

        .dropdown-header {
            padding: 0.5rem 1rem;
            margin-bottom: 0;
            color: #212529;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            clear: both;
            font-weight: 400;
            color: #212529;
            text-align: inherit;
            white-space: nowrap;
            background-color: transparent;
            border: 0;
            display: flex;
            align-items: center;
            transition: all 0.2s ease-in-out;
        }

        .dropdown-item:hover, .dropdown-item:focus {
            color: #1e293b;
            background-color: #f8f9fa;
        }

        .dropdown-item.text-danger:hover {
            color: #fff;
            background-color: #dc3545;
        }

        .dropdown-divider {
            height: 0;
            margin: 0.5rem 0;
            overflow: hidden;
            border-top: 1px solid #e9ecef;
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline-primary:hover {
            color: #fff;
            background-color: var(--primary);
            border-color: var(--primary);
        }

        /* Estilos para los íconos de Bootstrap */
        .bi {
            display: inline-block;
            vertical-align: -0.125em;
            fill: currentColor;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        /* Resto de tus estilos... */
        .hero-section {
            position: relative;
            min-height: 85vh;
            background: linear-gradient(45deg, #3b5df5 0%, #5aceff 100%);
            overflow: hidden;
        }
        
        .hero-wave {
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background-color: white;
            border-radius: 0 0 0 70%;
            z-index: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            padding-top: 80px;
        }
        
        .hero-title {
            font-size: 48px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .hero-text {
            color: #666;
            margin-bottom: 30px;
            max-width: 500px;
        }
        
        .hero-buttons .btn {
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 500;
            margin-right: 15px;
            margin-bottom: 15px;
        }
        
        .hero-image {
            text-align: center;
        }
        
        .hero-image img {
            max-width: 100%;
            height: auto;
        }
        
        @media (max-width: 991px) {
            .hero-content {
                text-align: center;
                padding-top: 40px;
            }
            
            .hero-text {
                margin-left: auto;
                margin-right: auto;
            }
            
            .hero-image {
                margin-top: 40px;
            }
        }

        @media (max-width: 768px) {
            .dropdown-menu {
                width: 100%;
            }
            
            .auth-buttons {
                width: 100%;
                justify-content: center;
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Mensaje de depuración (quitar en producción) -->
    <?php
    echo "<!-- Debug Info: ";
    echo "Session active: " . (session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No') . ", ";
    echo "Logged In: " . ($loggedIn ? 'Yes' : 'No') . ", ";
    echo "Session ID Usuario: " . (isset($_SESSION['idUsuario']) ? $_SESSION['idUsuario'] : 'Not Set') . ", ";
    echo "Nombre: " . (isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Not Set');
    echo " -->";
    ?>

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
                        <a class="nav-link" href="reporte.php">Reportes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reportar.php">Reportar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="estadistica.php">Estadísticas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="comunidades.php">Comunidad</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./reportes.php">Ayuda</a>
                    </li>
                </ul>
                
                <div class="auth-buttons d-flex align-items-center">
                    <?php if($loggedIn): ?>
                        <!-- Dropdown para usuario logueado -->
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-2"></i>
                                <?php echo htmlspecialchars($nombreUsuario); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li class="dropdown-header">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person-circle fs-4 me-2"></i>
                                        <div>
                                            <strong><?php echo htmlspecialchars($nombreUsuario); ?></strong>
                                            <?php if(isset($_SESSION['admin'])): ?>
                                                <div class="text-muted small">Administrador</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="perfil.php">
                                    <i class="bi bi-person me-2"></i>Mi Perfil
                                </a></li>
                                <li><a class="dropdown-item" href="mis-reportes.php">
                                    <i class="bi bi-file-text me-2"></i>Mis Reportes
                                </a></li>
                                <li><a class="dropdown-item" href="comunidades.php?mis-comunidades">
                                    <i class="bi bi-people me-2"></i>Mis Comunidades
                                </a></li>
                                <?php if(isset($_SESSION['admin'])): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="admin/dashboard.php">
                                        <i class="bi bi-speedometer2 me-2"></i>Panel Admin
                                    </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                                </a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Botones para usuario no logueado -->
                        <a href="login.php" class="btn btn-outline-primary me-2">Iniciar Sesión</a>
                        <a href="registro.php" class="btn btn-primary">Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sección Hero -->
    <section class="hero-section">
        <div class="hero-wave"></div>
        <div class="container">
            <div class="row align-items-center hero-content">
                <div class="col-lg-6">
                    <h1 class="hero-title">Ingresa tu reporte<br>y salva vidas<br>es tu oportunidad</h1>
                    <p class="hero-text">
                        Contribuye a crear una cuidad más segura reportando incidentes, alertando a la comunidad y actuando juntos para prevenir situaciones de riesgo.
                    </p>
                    <div class="hero-buttons">
                        <a href="#" id="crearReporteBtn" class="btn btn-primary">Crear Reporte</a>
                        <a href="#" class="btn btn-outline-primary">Ver Mapa</a>
                    </div>
                </div>
                <div class="col-lg-6 hero-image">
                    <img src="./assets/img/img1-removebg-preview.png" alt="App Screenshot" class="img-oval">
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle WITH Popper - IMPORTANTE -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    
    <!-- Script personalizado -->
    <script>
        // Función para redirigir al hacer clic en el botón "Crear Reporte"
        document.addEventListener('DOMContentLoaded', function() {
            const crearReporteBtn = document.getElementById('crearReporteBtn');
            
            crearReporteBtn.addEventListener('click', function(event) {
                event.preventDefault();
                // Cambia 'crear-reporte.html' por la URL a la que quieres redirigir
                window.location.href = './informacion.php';
            });
        });
    </script>
                
    <?php include 'includes/footer.php'; ?>


    <script>
function toggleDropdown(event) {
    event.preventDefault();
    event.stopPropagation();
    
    var dropdownMenu = document.getElementById('userDropdownMenu');
    var isShown = dropdownMenu.style.display === 'block';
    
    // Cerrar todos los dropdowns abiertos
    document.querySelectorAll('.dropdown-menu').forEach(function(el) {
        el.style.display = 'none';
    });
    
    // Toggle este dropdown
    dropdownMenu.style.display = isShown ? 'none' : 'block';
}

// Cerrar dropdown cuando se hace clic fuera
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(function(el) {
            el.style.display = 'none';
        });
    }
});
</script>

</body>
</html>