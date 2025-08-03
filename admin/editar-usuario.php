<?php
require(__DIR__ . '/../includes/conexion.php');
require(__DIR__ . '/../includes/auth.php');

// Verificar si es administrador
if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';
$usuario = null;

// Obtener usuario a editar
if (isset($_GET['id'])) {
    $idUsuario = $conn->real_escape_string($_GET['id']);
    $result = $conn->query("SELECT * FROM usuarios WHERE idUsuario = $idUsuario");
    
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
    } else {
        header("Location: usuarios.php");
        exit();
    }
}

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar'])) {
    $idUsuario = $conn->real_escape_string($_POST['idUsuario']);
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $correo = $conn->real_escape_string($_POST['correo']);
    $password = $_POST['password']; // No escape yet, we'll handle it carefully
    
    // Verificar si el correo ya existe y no es del usuario actual
    $check = $conn->query("SELECT idUsuario FROM usuarios WHERE correo = '$correo' AND idUsuario != $idUsuario");
    if ($check->num_rows > 0) {
        $error = "Este correo electrónico ya está registrado para otro usuario";
    } else {
        // Preparar la consulta de actualización
        $sql = "UPDATE usuarios SET nombre = '$nombre', correo = '$correo'";
        
        // Si se proporciona una nueva contraseña, hashearla
        if (!empty($password)) {
            // Hash the password using PHP's password_hash function
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql .= ", contraseña = '$hashed_password'";
        }
        
        // Completar la consulta
        $sql .= " WHERE idUsuario = $idUsuario";
        
        if ($conn->query($sql)) {
            $success = "Usuario actualizado correctamente";
            // Actualizar datos del usuario para mostrarlos actualizados
            $result = $conn->query("SELECT * FROM usuarios WHERE idUsuario = $idUsuario");
            $usuario = $result->fetch_assoc();
        } else {
            $error = "Error al actualizar: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario | Mi Ciudad SV</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }
        .transition-all {
            transition: all 0.3s ease;
        }
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
        .user-profile-header {
            background-image: linear-gradient(to right, #3b82f6, #60a5fa);
            height: 120px;
            border-radius: 0.5rem 0.5rem 0 0;
        }
        .user-avatar {
            width: 100px;
            height: 100px;
            margin-top: -50px;
            border: 4px solid white;
        }
        .button-shine {
            position: relative;
            overflow: hidden;
        }
        .button-shine:after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(to right, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.3) 50%, rgba(255, 255, 255, 0) 100%);
            transform: rotate(30deg);
            opacity: 0;
            transition: opacity 0.6s;
        }
        .button-shine:hover:after {
            opacity: 1;
        }
        .floating-label {
            position: absolute;
            pointer-events: none;
            left: 12px;
            top: 12px;
            transition: 0.2s ease all;
            color: #6b7280;
        }
        .form-control:focus ~ .floating-label,
        .form-control:not(:placeholder-shown) ~ .floating-label {
            transform: translateY(-22px) scale(0.85);
            color: #3b82f6;
            background-color: white;
            padding: 0 5px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header del Dashboard -->
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="flex">
        <!-- Sidebar -->
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 p-6 md:p-8 bg-gray-50">
            <div class="max-w-4xl mx-auto">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Editar Usuario</h1>
                        <p class="text-gray-600 mt-1">Actualizar información del perfil</p>
                    </div>
                    <a href="usuarios.php" class="bg-gray-600 hover:bg-gray-700 text-white px-5 py-2.5 rounded-lg flex items-center transition-all shadow-md">
                        <i class="fas fa-arrow-left mr-2"></i>Volver a Usuarios
                    </a>
                </div>
                
                <?php if($error): ?>
                <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-6 shadow-sm border-l-4 border-red-500 flex items-start">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-1"></i>
                    <div>
                        <h3 class="font-semibold">¡Error!</h3>
                        <p><?php echo $error; ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if($success): ?>
                <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-6 shadow-sm border-l-4 border-green-500 flex items-start">
                    <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                    <div>
                        <h3 class="font-semibold">¡Éxito!</h3>
                        <p><?php echo $success; ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Formulario de edición -->
                <?php if($usuario): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden card">
                    <!-- Cabecera del perfil -->
                    <div class="user-profile-header relative flex justify-center">
                        <div class="absolute text-white top-6 right-6">
                            <span class="px-4 py-1 rounded-full bg-white/20 backdrop-blur-sm text-sm">
                                <i class="fas fa-user-shield mr-1"></i>ID: <?php echo $usuario['idUsuario']; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="relative flex flex-col items-center">
                        <div class="user-avatar rounded-full bg-blue-100 flex items-center justify-center text-blue-800 text-4xl">
                            <i class="fas fa-user"></i>
                        </div>
                        <h2 class="text-xl font-bold mt-2"><?php echo htmlspecialchars($usuario['nombre']); ?></h2>
                        <p class="text-gray-500"><?php echo htmlspecialchars($usuario['correo']); ?></p>
                        
                        <!-- Mostrar roles del usuario -->
                        <div class="mt-2 mb-4">
                            <?php
                            $rolesUsuario = $conn->query("
                                SELECT r.nombreRol 
                                FROM roles r 
                                JOIN usuario_rol ur ON r.idRol = ur.idRol 
                                WHERE ur.idUsuario = ".$usuario['idUsuario']);
                            
                            if ($rolesUsuario->num_rows > 0) {
                                while($rol = $rolesUsuario->fetch_assoc()) {
                                    echo '<span class="inline-block bg-blue-100 text-blue-800 text-xs font-medium mr-1 px-2.5 py-0.5 rounded-full">'
                                        .$rol['nombreRol'].'</span>';
                                }
                            } else {
                                echo '<span class="inline-block bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Sin roles</span>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="p-8">
                        <h3 class="text-lg font-semibold mb-5 pb-2 border-b border-gray-200">Información de la cuenta</h3>
                        
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="idUsuario" value="<?php echo $usuario['idUsuario']; ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="relative">
                                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" 
                                        class="form-control w-full p-4 border border-gray-300 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-transparent"
                                        placeholder=" " required>
                                    <label for="nombre" class="floating-label font-medium">Nombre completo</label>
                                    <i class="fas fa-user text-gray-400 absolute right-4 top-4"></i>
                                </div>
                                
                                <div class="relative">
                                    <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($usuario['correo']); ?>" 
                                        class="form-control w-full p-4 border border-gray-300 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-transparent"
                                        placeholder=" " required>
                                    <label for="correo" class="floating-label font-medium">Correo electrónico</label>
                                    <i class="fas fa-envelope text-gray-400 absolute right-4 top-4"></i>
                                </div>
                            </div>
                            
                            <div class="relative">
                                <input type="password" id="password" name="password" 
                                    class="form-control w-full p-4 border border-gray-300 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-transparent"
                                    placeholder=" ">
                                <label for="password" class="floating-label font-medium">Nueva contraseña</label>
                                <button type="button" class="absolute right-4 top-4 text-gray-500 toggle-password focus:outline-none hover:text-gray-700">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <p class="mt-1 text-sm text-gray-500">Dejar en blanco para mantener la contraseña actual</p>
                            </div>
                            
                            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                                <a href="usuarios.php" class="text-gray-600 hover:text-gray-800 transition-all">
                                    <i class="fas fa-times mr-1"></i> Cancelar
                                </a>
                                <button type="submit" name="editar" class="button-shine bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition-all shadow-md flex items-center">
                                    <i class="fas fa-save mr-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-yellow-50 text-yellow-800 p-6 rounded-lg border-l-4 border-yellow-400 flex items-start shadow-md">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-3 text-xl"></i>
                    <div>
                        <h3 class="font-bold text-lg mb-1">Usuario no encontrado</h3>
                        <p>No se pudo encontrar información para el usuario solicitado.</p>
                        <a href="usuarios.php" class="mt-4 inline-block bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition-all">
                            Volver a la lista de usuarios
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Mostrar/ocultar contraseña
            $('.toggle-password').click(function() {
                const input = $(this).siblings('input');
                const icon = $(this).find('i');
                
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });