<?php
require(__DIR__ . '/../includes/conexion.php');
require(__DIR__ . '/../includes/auth.php');
// Verificar si es administrador
if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    header("Location: ../login.php");
    exit();
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Eliminar usuario
    if (isset($_POST['eliminar'])) {
        $idUsuario = $conn->real_escape_string($_POST['idUsuario']);
        $conn->query("DELETE FROM usuarios WHERE idUsuario = $idUsuario");
    }
    
    // Cambiar rol
    if (isset($_POST['cambiar_rol'])) {
        $idUsuario = $conn->real_escape_string($_POST['idUsuario']);
        $idRol = $conn->real_escape_string($_POST['idRol']);
        
        // Eliminar roles actuales
        $conn->query("DELETE FROM usuario_rol WHERE idUsuario = $idUsuario");
        
        // Asignar nuevo rol
        if ($idRol != '0') {
            $conn->query("INSERT INTO usuario_rol (idUsuario, idRol) VALUES ($idUsuario, $idRol)");
        }
    }
}

// Obtener todos los usuarios
$usuarios = $conn->query("SELECT * FROM usuarios ORDER BY nombre");
$roles = $conn->query("SELECT * FROM roles");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Usuarios | Mi Ciudad SV</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
        }
        .card {
            transition: all 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .badge {
            display: inline-block;
            padding: 0.25em 0.5em;
            font-size: 75%;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.375rem;
        }
        .table th {
            font-weight: 600;
            background-color: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }
        .table tr {
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.2s;
        }
        .table tr:hover {
            background-color: #f9fafb;
        }
        .table td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
        }
        .btn {
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn:hover {
            transform: translateY(-1px);
        }
        .btn:active {
            transform: translateY(1px);
        }
        .select-role {
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        }
    </style>
</head>
<body>
    <!-- Header del Dashboard -->
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="flex">
        <!-- Sidebar -->
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 p-6 md:p-8">
            <div class="max-w-6xl mx-auto">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Gestión de Usuarios</h1>
                        <p class="text-gray-600 mt-1">Administra los usuarios y sus roles en el sistema</p>
                    </div>
                    <a href="agregar-usuario.php" class="btn bg-blue-500 hover:bg-blue-600 text-white px-4 py-2.5 rounded-lg shadow-md">
                        <i class="fas fa-plus mr-2"></i>Nuevo Usuario
                    </a>
                </div>
                
                <!-- Tabla de usuarios -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden card">
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th class="text-left pl-6 py-4 text-gray-700">ID</th>
                                    <th class="text-left py-4 text-gray-700">Nombre</th>
                                    <th class="text-left py-4 text-gray-700">Correo</th>
                                    <th class="text-left py-4 text-gray-700">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($usuario = $usuarios->fetch_assoc()): ?>
                                <tr>
                                    <td class="pl-6"><?php echo $usuario['idUsuario']; ?></td>
                                    <td class="font-medium">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3">
                                                <i class="fas fa-user text-sm"></i>
                                            </div>
                                            <?php echo htmlspecialchars($usuario['nombre']); ?>
                                        </div>
                                    </td>
                                    <td class="text-gray-600"><?php echo htmlspecialchars($usuario['correo']); ?></td>
                                    <td>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <!-- Botón de editar -->
                                            <a href="editar-usuario.php?id=<?php echo $usuario['idUsuario']; ?>" 
                                               class="btn bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg text-sm shadow-sm">
                                                <i class="fas fa-edit mr-1"></i>Editar
                                            </a>
                                            
                                            <!-- Formulario para cambiar rol -->
                                            <form method="POST" class="flex items-center">
                                                <input type="hidden" name="idUsuario" value="<?php echo $usuario['idUsuario']; ?>">
                                                <select name="idRol" class="select-role border border-gray-300 rounded-lg px-3 py-1.5 mr-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                                    <option value="0">Sin rol</option>
                                                    <?php 
                                                    $roles->data_seek(0); // Reiniciar puntero
                                                    while($rol = $roles->fetch_assoc()): 
                                                        // Verificar si el usuario tiene este rol
                                                        $isSelected = false;
                                                        $rolesUsuario = $conn->query("
                                                            SELECT r.idRol 
                                                            FROM roles r 
                                                            JOIN usuario_rol ur ON r.idRol = ur.idRol 
                                                            WHERE ur.idUsuario = ".$usuario['idUsuario']." AND r.idRol = ".$rol['idRol']);
                                                        if ($rolesUsuario->num_rows > 0) {
                                                            $isSelected = true;
                                                        }
                                                    ?>
                                                    <option value="<?php echo $rol['idRol']; ?>" <?php echo $isSelected ? 'selected' : ''; ?>>
                                                        <?php echo $rol['nombreRol']; ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                                <button type="submit" name="cambiar_rol" class="btn bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-lg text-sm shadow-sm">
                                                    <i class="fas fa-check mr-1"></i>Asignar
                                                </button>
                                            </form>
                                            
                                            <!-- Botón para eliminar -->
                                            <form method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.');">
                                                <input type="hidden" name="idUsuario" value="<?php echo $usuario['idUsuario']; ?>">
                                                <button type="submit" name="eliminar" class="btn bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-sm shadow-sm">
                                                    <i class="fas fa-trash-alt mr-1"></i>Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Indicador de usuarios -->
                <div class="mt-6 text-right text-sm text-gray-600">
                    <?php
                    $totalUsuarios = $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];
                    echo "Mostrando $totalUsuarios usuarios en total";
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Confirmación antes de eliminar
            $('form[onsubmit]').submit(function() {
                return confirm('¿Estás seguro de realizar esta acción? Esta operación no se puede deshacer.');
            });
        });
    </script>
</body>
</html>