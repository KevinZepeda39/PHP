<?php
require(__DIR__ . '/../includes/conexion.php');
require(__DIR__ . '/../includes/auth.php');

// Verificar si es administrador
if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    header("Location: ../login.php");
    exit();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $correo = $conn->real_escape_string($_POST['correo']);
    $password = $_POST['password'];
    $idRol = $conn->real_escape_string($_POST['idRol']);

    // Primero, verificar si el correo ya existe
    $check = $conn->query("SELECT idUsuario FROM usuarios WHERE correo = '$correo'");
    if ($check->num_rows > 0) {
        $error = "El correo electrónico ya está registrado";
    } else {
        // Hash the password using PHP's password_hash function
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insertar nuevo usuario con contraseña hasheada
        $sql = "INSERT INTO usuarios (nombre, correo, contraseña) VALUES ('$nombre', '$correo', '$hashed_password')";
        
        if ($conn->query($sql)) {
            $idUsuario = $conn->insert_id;
            
            // Asignar rol si se seleccionó uno
            if ($idRol != '0') {
                $conn->query("INSERT INTO usuario_rol (idUsuario, idRol) VALUES ($idUsuario, $idRol)");
            }
            
            $_SESSION['mensaje'] = "Usuario creado exitosamente";
            header("Location: usuarios.php");
            exit();
        } else {
            $error = "Error al crear usuario: " . $conn->error;
        }
    }
}

$roles = $conn->query("SELECT * FROM roles");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Usuario | Mi Ciudad SV</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .card {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.15), 0 15px 15px -5px rgba(0, 0, 0, 0.08);
        }
        .input-group {
            position: relative;
        }
        .input-group .floating-label {
            position: absolute;
            top: -7px;
            left: 12px;
            background: white;
            padding: 0 5px;
            font-size: 0.75rem;
            color: #6b7280;
            transition: all 0.2s ease;
            pointer-events: none;
        }
        .input-group input:focus + .floating-label,
        .input-group input:not(:placeholder-shown) + .floating-label {
            top: -7px;
            color: #3b82f6;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6b7280;
            transition: color 0.2s ease;
        }
        .toggle-password:hover {
            color: #3b82f6;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex flex-col">
    <!-- Header del Dashboard -->
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="flex flex-1">
        <!-- Sidebar -->
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="flex-1 p-8 space-y-6">
            <div class="max-w-4xl mx-auto">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-user-plus mr-3 text-blue-600"></i>
                            Agregar Nuevo Usuario
                        </h1>
                        <p class="text-gray-600 mt-2">Crea un nuevo usuario con roles y permisos personalizados</p>
                    </div>
                    <a href="usuarios.php" class="btn btn-secondary flex items-center space-x-2 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-100 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                        <span>Volver a usuarios</span>
                    </a>
                </div>
                
                <?php if(isset($error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3 text-xl"></i>
                    <div>
                        <h3 class="font-semibold">Error de Registro</h3>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="bg-white card rounded-xl overflow-hidden">
                    <div class="p-6 bg-blue-50 border-b border-blue-100 flex items-center">
                        <i class="fas fa-user-shield text-blue-600 mr-3 text-2xl"></i>
                        <h2 class="text-xl font-semibold text-blue-800">Información del Usuario</h2>
                    </div>
                    
                    <form method="POST" class="p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="input-group col-span-2">
                                <input 
                                    type="text" 
                                    name="nombre" 
                                    id="nombre"
                                    placeholder=" " 
                                    required 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                <label for="nombre" class="floating-label">Nombre Completo</label>
                            </div>
                            
                            <div class="input-group">
                                <input 
                                    type="email" 
                                    name="correo" 
                                    id="correo"
                                    placeholder=" " 
                                    required 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                <label for="correo" class="floating-label">Correo Electrónico</label>
                            </div>
                            
                            <div class="input-group">
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="password"
                                    placeholder=" " 
                                    required 
                                    minlength="8"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-10"
                                >
                                <label for="password" class="floating-label">Contraseña</label>
                                <button 
                                    type="button" 
                                    class="toggle-password absolute right-3 top-1/2 transform -translate-y-1/2"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>
                                <p class="text-xs text-gray-500 mt-1 ml-1">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Mínimo 8 caracteres, incluye mayúsculas, minúsculas y números
                                </p>
                            </div>
                            
                            <div class="col-span-2 input-group">
                                <select 
                                    name="idRol" 
                                    id="idRol"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option value="0">Seleccionar Rol</option>
                                    <?php while($rol = $roles->fetch_assoc()): ?>
                                    <option value="<?php echo $rol['idRol']; ?>">
                                        <?php echo ucfirst($rol['nombreRol']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <label for="idRol" class="floating-label">Rol de Usuario</label>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                            <a 
                                href="usuarios.php" 
                                class="px-6 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                            >
                                Cancelar
                            </a>
                            <button 
                                type="submit" 
                                class="bg-blue-600 text-white px-8 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2"
                            >
                                <i class="fas fa-save mr-2"></i>
                                Guardar Usuario
                            </button>
                        </div>
                    </form>
                </div>
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

            // Password strength indicator (basic)
            $('#password').on('input', function() {
                const password = $(this).val();
                const strengthIndicator = $(this).siblings('p');
                
                if (password.length >= 8) {
                    strengthIndicator.html('<i class="fas fa-check-circle text-green-500 mr-1"></i> Contraseña segura');
                } else {
                    strengthIndicator.html('<i class="fas fa-info-circle mr-1"></i> Mínimo 8 caracteres, incluye mayúsculas, minúsculas y números');
                }
            });
        });
    </script>
</body>
</html>