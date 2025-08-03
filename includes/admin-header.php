<?php
// Este archivo debe guardarse como includes/admin-header.php
if (!isset($_SESSION)) {
    session_start();
}

// Cerrar sesión
if (isset($_GET['logout'])) {
    // Destruir todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la sesión
    session_destroy();
    
    // Redirigir al login
    header("Location: ../login.php");
    exit();
}
?>

<header class="bg-white shadow-md">
    <div class="container mx-auto px-4 py-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center">
                <a href="dashboard.php" class="flex items-center">
                    <img src="../assets/img/Logo.png" alt="Logo" class="h-10 mr-3">
                </a>
                <h1 class="text-xl font-bold text-gray-800">
                    <a href="dashboard.php">Panel de Administración</a>
                </h1>
                <a href="../index.php" class="ml-4 px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded text-sm flex items-center">
                    <i class="fas fa-home mr-1"></i> Ir al sitio
                </a>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="text-gray-700">
                    <span class="mr-2">Bienvenido,</span>
                    <span class="font-semibold"><?php echo isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Administrador'; ?></span>
                </div>
                
                <a href="?logout=1" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded flex items-center">
                    <i class="fas fa-sign-out-alt mr-2"></i> Salir
                </a>
            </div>
        </div>
    </div>
</header>