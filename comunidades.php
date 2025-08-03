<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
verificarLogin();

$idUsuario = $_SESSION['idUsuario'];
$nombre = $_SESSION['nombre'];

// Obtener todas las comunidades con sus categorías
$query = "SELECT c.*, u.nombre as creador, 
         (SELECT COUNT(*) FROM comentarios WHERE idComunidad = c.idComunidad) as total_comentarios,
         (SELECT COUNT(*) FROM usuario_comunidad WHERE idComunidad = c.idComunidad) as total_miembros
         FROM comunidad c 
         INNER JOIN usuarios u ON c.idUsuario = u.idUsuario
         ORDER BY c.fechaCreacion DESC";
$result = mysqli_query($conn, $query);

// Verificar si hay un mensaje de éxito
$mensaje = "";
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidades - MiCiudadSV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }

        .comunidades-container {
            padding: 80px 0;
            position: relative;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f1 100%);
        }

        .comunidades-header {
            text-align: center;
            margin-bottom: 60px;
            position: relative;
            z-index: 2;
        }

        .comunidades-header h1 {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(45deg, #000, #333);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }

        .comunidades-header p {
            font-size: 1.3rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        .comunidades-search {
            max-width: 800px;
            margin: 0 auto 60px;
            position: relative;
            z-index: 2;
        }

        .search-bar {
            position: relative;
            margin-bottom: 20px;
        }

        .search-bar input {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .search-bar input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
        }

        .search-bar i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.2rem;
        }

        .filter-options {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn:hover {
            border-color: #3498db;
            color: #3498db;
        }

        .filter-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .categories-nav {
            margin-bottom: 40px;
            text-align: center;
        }

        .categories-nav h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .category-pills {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .category-pill {
            padding: 8px 18px;
            border: none;
            background: #f1f3f5;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .category-pill:hover {
            background: #e9ecef;
        }

        .category-pill.active {
            background: #3498db;
            color: white;
        }

        .communities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .community-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
        }

        .community-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .community-banner {
            height: 150px;
            background-size: cover;
            background-position: center;
            position: relative;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 15px;
        }

        .members-badge {
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .community-info {
            padding: 20px;
            position: relative;
            margin-top: -30px;
        }

        .community-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            border: 3px solid white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            margin-bottom: 15px;
            overflow: hidden;
        }

        .community-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .community-info h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .community-info p {
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .community-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .tag {
            background: #f1f3f5;
            color: #666;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .community-activity {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-top: 1px solid #f1f3f5;
            background: #f8f9fa;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #666;
        }

        .activity-item i {
            color: #3498db;
        }

        .community-action {
            display: flex;
            gap: 10px;
            padding: 15px 20px;
            border-top: 1px solid #f1f3f5;
        }

        .join-btn {
            flex: 1;
            padding: 8px 16px;
            border: none;
            background: #3498db;
            color: white;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
        }

        .join-btn:hover {
            background: #2980b9;
            color: white;
        }

        .join-btn.joined {
            background: #e3f2fd;
            color: #3498db;
        }

        .info-btn {
            padding: 8px 16px;
            border: 2px solid #e0e0e0;
            background: white;
            color: #666;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .info-btn:hover {
            border-color: #3498db;
            color: #3498db;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 60px;
        }

        .pagination-btn {
            padding: 10px 16px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 40px;
            text-align: center;
        }

        .pagination-btn:hover {
            border-color: #3498db;
            color: #3498db;
        }

        .pagination-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .create-community-section {
            text-align: center;
            margin-bottom: 80px;
        }

        .create-community-card {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 60px 40px;
            border-radius: 20px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .create-community-card .card-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .create-community-card .card-icon i {
            font-size: 2.5rem;
        }

        .create-community-card h3 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .create-community-card p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .create-btn {
            padding: 12px 30px;
            border: 2px solid white;
            background: transparent;
            color: white;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .create-btn:hover {
            background: white;
            color: #3498db;
        }

        /* Colores para diferentes categorías */
        .cat-vecinos { background-color: #FF6B6B; color: white; }
        .cat-seguridad { background-color: #4ECDC4; color: white; }
        .cat-eventos { background-color: #FFD166; color: #333; }
        .cat-infraestructura { background-color: #6A0572; color: white; }
        .cat-servicios { background-color: #1A535C; color: white; }
        .cat-otros { background-color: #95A5A6; color: white; }

        .category-badge {
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            position: absolute;
            top: 15px;
            left: 15px;
        }

        @media (max-width: 768px) {
            .comunidades-header h1 {
                font-size: 2.5rem;
            }

            .communities-grid {
                grid-template-columns: 1fr;
            }

            .filter-options {
                flex-direction: column;
            }

            .filter-btn {
                width: 100%;
            }

            .community-action {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <section class="comunidades-container">
        <div class="container">
            <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="comunidades-header">
                <h1>Comunidades</h1>
                <p>Encuentra grupos de personas con tus mismos intereses</p>
            </div>

            <div class="comunidades-search">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchCommunity" placeholder="Buscar comunidades...">
                </div>
                <div class="filter-options">
                    <button class="filter-btn active" data-filter="all">Todas</button>
                    <button class="filter-btn" data-filter="popular">Populares</button>
                    <button class="filter-btn" data-filter="recent">Recientes</button>
                    <button class="filter-btn" data-filter="my">Mis Comunidades</button>
                </div>
            </div>

            <div class="categories-nav">
                <h3>Categorías</h3>
                <div class="category-pills">
                    <button class="category-pill active" data-category="all">Todas</button>
                    <button class="category-pill" data-category="vecinos">Vecinos</button>
                    <button class="category-pill" data-category="seguridad">Seguridad</button>
                    <button class="category-pill" data-category="eventos">Eventos</button>
                    <button class="category-pill" data-category="infraestructura">Infraestructura</button>
                    <button class="category-pill" data-category="servicios">Servicios</button>
                    <button class="category-pill" data-category="otros">Otros</button>
                </div>
            </div>

            <div class="communities-grid">
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($comunidad = mysqli_fetch_assoc($result)): ?>
                        <?php
                        // Obtener la categoría real de la base de datos
                        $categoria = $comunidad['categoria'] ?? 'otros';
                        
                        // Mapeo de categorías a clases CSS
                        $categoriasCss = [
                            'vecinos' => 'cat-vecinos',
                            'seguridad' => 'cat-seguridad',
                            'eventos' => 'cat-eventos',
                            'infraestructura' => 'cat-infraestructura',
                            'servicios' => 'cat-servicios',
                            'otros' => 'cat-otros'
                        ];
                        
                        $categoriaCss = $categoriasCss[$categoria] ?? 'cat-otros';
                        
                        // Nombres para mostrar
                        $nombresCategorias = [
                            'vecinos' => 'Vecinos',
                            'seguridad' => 'Seguridad',
                            'eventos' => 'Eventos',
                            'infraestructura' => 'Infraestructura',
                            'servicios' => 'Servicios',
                            'otros' => 'Otros'
                        ];
                        
                        $categoriaNombre = $nombresCategorias[$categoria] ?? 'Otros';
                        ?>
                        
                        <div class="community-card" 
                             data-category="<?php echo htmlspecialchars($categoria); ?>" 
                             data-members="<?php echo $comunidad['total_miembros'] ?? 0; ?>">
                            
                            <div class="community-banner" style="background-image: url('https://picsum.photos/400/200?random=<?php echo $comunidad['idComunidad']; ?>');">
                                <span class="category-badge <?php echo $categoriaCss; ?>">
                                    <?php echo $categoriaNombre; ?>
                                </span>
                                <span class="members-badge" style="position: absolute; top: 15px; right: 15px;">
                                    <i class="fas fa-users"></i> <?php echo $comunidad['total_miembros'] ?? 0; ?>
                                </span>
                            </div>
                            
                            <div class="community-info">
                                <div class="community-icon">
                                    <?php echo generarAvatar($comunidad['titulo'][0], 'md'); ?>
                                </div>
                                <h3><?php echo htmlspecialchars($comunidad['titulo']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($comunidad['descripcion'], 0, 120)) . '...'; ?></p>
                                <div class="community-tags">
                                    <?php if (!empty($comunidad['tags'])): ?>
                                        <?php $tags = explode(',', $comunidad['tags']); ?>
                                        <?php foreach ($tags as $tag): ?>
                                            <span class="tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="tag"><?php echo $categoriaNombre; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="community-activity">
                                <div class="activity-item">
                                    <i class="fas fa-comment"></i>
                                    <span><?php echo $comunidad['total_comentarios']; ?> discusiones</span>
                                </div>
                                <div class="activity-item">
                                    <i class="fas fa-file-alt"></i>
                                    <span>0 recursos</span>
                                </div>
                                <div class="activity-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>0 eventos</span>
                                </div>
                            </div>
                            
                            <div class="community-action">
                                <a href="comunidad.php?id=<?php echo $comunidad['idComunidad']; ?>" class="join-btn">Unirse</a>
                                <button class="info-btn"><i class="fas fa-info-circle"></i></button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <h3>No hay comunidades disponibles</h3>
                        <p>¡Sé el primero en crear una comunidad!</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="pagination">
                <button class="pagination-btn prev"><i class="fas fa-chevron-left"></i></button>
                <button class="pagination-btn page active">1</button>
                <button class="pagination-btn page">2</button>
                <button class="pagination-btn page">3</button>
                <button class="pagination-btn next"><i class="fas fa-chevron-right"></i></button>
            </div>

            <div class="create-community-section">
                <div class="create-community-card">
                    <div class="card-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3>Crea tu propia comunidad</h3>
                    <p>¿No encuentras una comunidad que se ajuste a tus intereses? Crea la tuya y conecta con personas que comparten tu pasión.</p>
                    <button class="create-btn" onclick="window.location.href='crear-comunidad.php'">Crear Comunidad</button>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <?php include 'includes/footer.php'; ?>

    <script src="./assets/js/main.js"></script>
</body>
<!-- Socket.io -->
<script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
<script src="./admin/js/admin-comunidades.js"></script>
</html>