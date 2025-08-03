<?php
require '../includes/auth.php'; // Verificar sesión y rol admin

// Obtener estadísticas
$usuarios = $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc();
$reportes = $conn->query("SELECT COUNT(*) as total FROM reportes")->fetch_assoc();
$comunidad = $conn->query("SELECT COUNT(*) as total FROM comunidad")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Panel de Administración</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-semibold">Usuarios</h3>
                <p class="text-4xl font-bold"><?php echo $usuarios['total']; ?></p>
                <a href="usuarios.php" class="text-blue-500 hover:underline">Gestionar</a>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-semibold">Reportes</h3>
                <p class="text-4xl font-bold"><?php echo $reportes['total']; ?></p>
                <a href="reportes.php" class="text-blue-500 hover:underline">Gestionar</a>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-semibold">Publicaciones</h3>
                <p class="text-4xl font-bold"><?php echo $comunidad['total']; ?></p>
                <a href="publicaciones.php" class="text-blue-500 hover:underline">Gestionar</a>
            </div>
        </div>
        
        <!-- Últimos reportes -->
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h2 class="text-2xl font-bold mb-4">Reportes Recientes</h2>
            <?php
            $sql = "SELECT r.*, u.nombre FROM reportes r JOIN usuario_reporte ur ON r.idReporte = ur.idReporte JOIN usuarios u ON ur.idUsuario = u.idUsuario WHERE ur.rolEnReporte = 'creador' ORDER BY r.fechaCreacion DESC LIMIT 5";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo '<div class="border-b py-3">';
                    echo '<h3 class="font-semibold">'.$row['titulo'].'</h3>';
                    echo '<p class="text-gray-600">'.$row['descripcion'].'</p>';
                    echo '<p class="text-sm text-gray-500">Publicado por: '.$row['nombre'].' - '.$row['fechaCreacion'].'</p>';
                    echo '</div>';
                }
            } else {
                echo '<p>No hay reportes recientes</p>';
            }
            ?>
            <a href="reportes.php" class="text-blue-500 hover:underline mt-4 inline-block">Ver todos</a>
        </div>
    </div>
</body>
</html>