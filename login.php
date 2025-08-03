<?php
require 'includes/conexion.php';

$error = '';

// Mostrar mensaje de registro exitoso si existe
if (isset($_SESSION['registro_exitoso'])) {
    $success = $_SESSION['registro_exitoso'];
    unset($_SESSION['registro_exitoso']);
}

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = $conn->real_escape_string($_POST['correo']);
    $password = $_POST['password']; // No escape, we'll verify carefully

    // Buscar usuario por correo
    $sql = "SELECT * FROM usuarios WHERE correo = '$correo'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verificar contraseña usando password_verify
        if (password_verify($password, $user['contraseña'])) {
            $_SESSION['idUsuario'] = $user['idUsuario'];
            $_SESSION['nombre'] = $user['nombre'];
            
            // Verificar si es admin
            $sql_rol = "SELECT r.nombreRol FROM roles r 
                       JOIN usuario_rol ur ON r.idRol = ur.idRol 
                       WHERE ur.idUsuario = ".$user['idUsuario'];
            $result_rol = $conn->query($sql_rol);
            
            while($rol = $result_rol->fetch_assoc()) {
                if($rol['nombreRol'] == 'administrador') {
                    $_SESSION['admin'] = true;
                    header("Location: admin/dashboard.php");
                    exit();
                }
            }
            
            // Redirigir siempre a index.php si no es admin
            header("Location: index.php");
            exit();
        } else {
            $error = "Correo o contraseña incorrectos";
        }
    } else {
        $error = "Correo o contraseña incorrectos";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1.0, width=device-width"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Iniciar Sesión | Mi Ciudad SV</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">

    <!-- Apartado inicial -->
    <div class="flex h-screen">
        <div class="flex-1 bg-cover bg-center relative" style="background-image: url('assets/img/ciudad-bg.jpg');">
            <div class="absolute top-0 left-0 w-full h-full bg-black bg-opacity-50 flex flex-col justify-center items-center text-white">
                <img src="assets/img/Logo.png" alt="Logo" class="max-w-xs mb-7">
                <h2 class="text-3xl font-bold text-center">Bienvenido de vuelta</h2>
            </div>
        </div>
        
        <div class="w-full max-w-md p-8 bg-white shadow-lg flex flex-col justify-center space-y-6">
            <h1 class="text-2xl font-bold mb-4">¡Bienvenido a Mi Ciudad SV!</h1>
            <p class="text-gray-600 mb-6">Ingresa a tu cuenta para disfrutar de tus beneficios.</p>
            
            <?php if(isset($success)): ?>
            <div class="bg-green-100 text-green-700 p-2 rounded mb-4">
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if($error): ?>
            <div id="loginError" class="bg-red-100 text-red-700 p-2 rounded mb-4">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <ul class="flex mb-6 border-b">
                <li class="mr-6">
                    <a href="#" class="text-blue-500 font-bold py-2 px-4 border-b-2 border-blue-500">Iniciar Sesión</a>
                </li>
                <li>
                    <a href="registro.php" class="text-gray-500 py-2 px-4">Registrarse</a>
                </li>
            </ul>
        
            <form id="formLogin" method="POST" class="space-y-4">
                <input type="email" placeholder="Correo electrónico" class="w-full p-3 border border-gray-300 rounded" name="correo" autocomplete="off" required> 
                <div class="relative">
                    <input type="password" placeholder="Contraseña" name="password" class="w-full p-3 border border-gray-300 rounded pr-10" required>
                    <button type="button" class="absolute right-2 top-2 text-gray-600 toggle-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <a href="#" class="text-blue-500 text-sm block text-right">¿Olvidaste tu contraseña?</a>
                <button class="w-full bg-blue-500 text-white p-3 rounded font-bold hover:bg-blue-600" type="submit">Iniciar Sesión</button>
            </form>
            
            <div class="text-center text-sm text-gray-500">
                ¿No tienes cuenta? <a href="registro.php" class="text-blue-500 hover:underline">Regístrate aquí</a>
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
        });
    </script>
</body>
</html>