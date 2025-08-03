<?php
session_start();
require_once 'includes/conexion.php';
require_once __DIR__ . '/includes/models/reporte.php';
// Verificar si el usuario está logueado
$loggedIn = isset($_SESSION['idUsuario']);
$nombreUsuario = $loggedIn ? $_SESSION['nombre'] : '';

// Instanciar modelo de reporte
$reporteModel = new Reporte($conn);

// Obtener filtros
$filtros = [];
if (isset($_GET['tipo'])) {
    $filtros['tipo'] = $_GET['tipo'];
}
if (isset($_GET['buscar'])) {
    $termino = $_GET['buscar'];
}

// Obtener reportes
if (isset($termino)) {
    $reportes = $reporteModel->buscar($termino);
} else {
    $reportes = $reporteModel->listar($filtros);
}

// Verificar si hay mensaje de sesión
$mensaje = "";
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - MiCiudadSV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
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
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }

        .reports-header {
            padding: 60px 0 40px;
            background: linear-gradient(135deg, #3a6efd 0%, #2856d6 100%);
            color: white;
            margin-bottom: 40px;
        }

        .reports-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .reports-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .search-filters {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .search-bar {
            position: relative;
        }

        .search-bar input {
            padding-left: 45px;
            height: 48px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .filter-btn {
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid #dee2e6;
            background: white;
            color: var(--text-dark);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .filter-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .report-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            overflow: hidden;
        }

        .report-card:hover {
            transform: translateY(-5px);
        }

        .report-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f8f9fa;
        }

        .report-content {
            padding: 20px;
        }

        .report-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .report-description {
            color: var(--text-light);
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .report-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .report-author {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .report-date {
            color: var(--text-light);
        }

        .report-status {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-pending {
            background: var(--warning);
            color: #664d03;
        }

        .status-process {
            background: var(--info);
            color: #0c5460;
        }

        .status-resolved {
            background: var(--success);
            color: #155724;
        }

        .status-urgent {
            background: var(--danger);
            color: white;
        }

        .create-report-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            font-size: 1.5rem;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .create-report-btn:hover {
            transform: scale(1.1);
            background: #2856d6;
            color: white;
            text-decoration: none;
        }

        .no-reports {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .no-reports i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }

        .no-reports h3 {
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .no-reports p {
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .pagination {
            margin-top: 30px;
            justify-content: center;
        }

        .page-link {
            color: var(--primary);
            border-radius: 8px;
            margin: 0 5px;
            border: none;
        }

        .page-link:hover {
            background: rgba(58, 110, 253, 0.1);
        }

        .page-item.active .page-link {
            background: var(--primary);
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Header -->
    <div class="reports-header">
        <div class="container">
            <h1>Reportes Ciudadanos</h1>
            <p>Explora los reportes de tu comunidad y contribuye a mejorar tu ciudad</p>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="container">
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Búsqueda y filtros -->
        <div class="search-filters">
            <form action="" method="GET">
                <div class="search-bar">
                    <i class="bi bi-search"></i>
                    <input type="text" class="form-control" name="buscar" 
                           placeholder="Buscar reportes..." 
                           value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
                </div>
            </form>
            
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">Todos</button>
                <button class="filter-btn" data-filter="emergency">Emergencias</button>
                <button class="filter-btn" data-filter="infrastructure">Infraestructura</button>
                <button class="filter-btn" data-filter="security">Seguridad</button>
                <button class="filter-btn" data-filter="urgent">Urgentes</button>
            </div>
        </div>

        <!-- Lista de reportes -->
        <div class="row">
            <?php if (count($reportes) > 0): ?>
                <?php foreach ($reportes as $reporte): ?>
                    <div class="col-md-4">
                        <div class="report-card">
                            <?php if ($reporte['imagen']): ?>
                                <img src="ver-imagen.php?id=<?php echo $reporte['idReporte']; ?>" 
                                     class="report-image" 
                                     alt="<?php echo htmlspecialchars($reporte['titulo']); ?>">
                            <?php else: ?>
                                <div class="report-image d-flex align-items-center justify-content-center bg-light">
                                    <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="report-content">
                                <h3 class="report-title">
                                    <?php echo htmlspecialchars($reporte['titulo']); ?>
                                </h3>
                                
                                <p class="report-description">
                                    <?php echo htmlspecialchars($reporte['descripcion']); ?>
                                </p>
                                
                                <div class="report-meta">
                                    <div class="report-author">
                                        <i class="bi bi-person-circle"></i>
                                        <?php echo htmlspecialchars($reporte['nombreCreador']); ?>
                                    </div>
                                    <div class="report-date">
                                        <?php echo date('d/m/Y', strtotime($reporte['fechaCreacion'])); ?>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="report-status status-pending">Pendiente</span>
                                    <a href="ver-reporte.php?id=<?php echo $reporte['idReporte']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        Ver detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="no-reports">
                        <i class="bi bi-folder2-open"></i>
                        <h3>No hay reportes disponibles</h3>
                        <p>Sé el primero en reportar un problema en tu comunidad</p>
                        <a href="reportar.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-2"></i>Crear reporte
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Paginación (si tienes muchos reportes) -->
        <?php if (count($reportes) > 0): ?>
            <nav aria-label="Navegación de reportes">
                <ul class="pagination">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1">Anterior</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Siguiente</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Botón flotante para crear reporte -->
    <a href="reportar.php" class="create-report-btn" title="Crear nuevo reporte">
        <i class="bi bi-plus"></i>
    </a>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filtros
            const filterButtons = document.querySelectorAll('.filter-btn');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filter = this.dataset.filter;
                    // Aquí puedes implementar la lógica de filtrado
                    // Por ahora, redirigiremos con un parámetro GET
                    if (filter === 'all') {
                        window.location.href = 'reportes.php';
                    } else {
                        window.location.href = `reportes.php?tipo=${filter}`;
                    }
                });
            });
        });
    </script>
</body>
</html>