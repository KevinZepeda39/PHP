<?php
require 'includes/conexion.php';

$error = '';

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $correo = $conn->real_escape_string($_POST['correo']);
    $password = $_POST['password']; // No escape, we'll handle hashing carefully
    
    // Verificar si el correo ya existe
    $check = $conn->query("SELECT idUsuario FROM usuarios WHERE correo = '$correo'");
    
    if ($check->num_rows > 0) {
        $error = "Este correo electrónico ya está registrado";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Preparar la consulta de inserción con la contraseña hasheada
        $sql = "INSERT INTO usuarios (nombre, correo, contraseña) 
                VALUES ('$nombre', '$correo', '$hashed_password')";
        
        if ($conn->query($sql)) {
            $idUsuario = $conn->insert_id;
            // Asignar rol de usuario normal (idRol = 1)
            $conn->query("INSERT INTO usuario_rol (idUsuario, idRol) VALUES ($idUsuario, 1)");
            
            // Redirigir al login con mensaje de éxito
            $_SESSION['registro_exitoso'] = "¡Registro exitoso! Por favor inicia sesión";
            header("Location: login.php");
            exit();
        } else {
            $error = "Error al registrar: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1.0, width=device-width"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Registro | Mi Ciudad SV</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">

    <!-- Apartado inicial -->
    <div class="flex h-screen">
        <div class="flex-1 bg-cover bg-center relative" style="background-image: url('assets/img/ciudad-bg.jpg');">
            <div class="absolute top-0 left-0 w-full h-full bg-black bg-opacity-50 flex flex-col justify-center items-center text-white">
                <img src="assets/img/Logo.png" alt="Logo" class="max-w-xs mb-7">
                <h2 class="text-3xl font-bold text-center">Únete a nuestra comunidad</h2>
            </div>
        </div>
        
        <div class="w-full max-w-md p-8 bg-white shadow-lg flex flex-col justify-center space-y-6">
            <h1 class="text-2xl font-bold mb-4">¡Bienvenido a Mi Ciudad SV!</h1>
            <p class="text-gray-600 mb-6">Crea una cuenta para disfrutar de todos los beneficios.</p>
            
            <?php if($error): ?>
            <div id="registerError" class="bg-red-100 text-red-700 p-2 rounded mb-4">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <ul class="flex mb-6 border-b">
                <li class="mr-6">
                    <a href="login.php" class="text-gray-500 py-2 px-4">Iniciar Sesión</a>
                </li>
                <li>
                    <a href="#" class="text-blue-500 font-bold py-2 px-4 border-b-2 border-blue-500">Registrarse</a>
                </li>
            </ul>
        
            <form id="formRegistro" method="POST" class="space-y-4">
                <input type="text" placeholder="Nombre y Apellidos" class="w-full p-3 border border-gray-300 rounded" name="nombre" autocomplete="off" required> 
                <input type="email" placeholder="Correo electrónico" class="w-full p-3 border border-gray-300 rounded" name="correo" autocomplete="off" required> 
                <div class="relative">
                    <input type="password" placeholder="Contraseña" name="password" class="w-full p-3 border border-gray-300 rounded pr-10" required minlength="8">
                    <button type="button" class="absolute right-2 top-2 text-gray-600 toggle-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <button class="w-full bg-blue-500 text-white p-3 rounded font-bold hover:bg-blue-600" type="submit">Registrarse</button>
            </form>
            
            <div class="text-center text-sm text-gray-500">
                ¿Ya tienes cuenta? <a href="login.php" class="text-blue-500 hover:underline">Inicia sesión aquí</a>
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