<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiCiudadSv - Tu comunidad local</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Avatar CSS -->
    <link rel="stylesheet" href="assets/css/avatar.css">
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-light
    navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/img/logo.png" alt="MiCiudadSv" height="40">
            </a>
            
            <div class="d-flex flex-grow-1 mx-4">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Buscar" aria-label="Buscar">
                    <button class="btn btn-outline-secondary" type="button"><i class="fas fa-search"></i></button>
                </div>
            </div>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if(isset($_SESSION['idUsuario'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="reportes.php"><i class="fas fa-flag"></i> Reportes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="comunidades.php"><i class="fas fa-users"></i> Comunidades</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="comentarios.php"><i class="fas fa-comments"></i> Comentarios</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php 
                            // Generar avatar con primera letra del nombre
                            $nombreUsuario = $_SESSION['nombre'] ?? 'Usuario';
                            $primeraLetra = mb_substr($nombreUsuario, 0, 1, 'UTF-8');
                            $claseBg = 'bg-letter-' . strtolower($primeraLetra);
                            ?>
                            <div class="avatar-letter avatar-letter-sm <?php echo $claseBg; ?> me-2"><?php echo $primeraLetra; ?></div>
                            <?php echo $_SESSION['nombre']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user me-2"></i> Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Mi Dashboard</a></li>
                            <?php if (isset($_SESSION['admin']) && $_SESSION['admin']): ?>
                            <li><a class="dropdown-item" href="admin/dashboard.php"><i class="fas fa-users-cog me-2"></i> Administración</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Iniciar Sesión</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="registro.php">Registrarse</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Contenido principal -->
    <main class="py-4">