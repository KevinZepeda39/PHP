<?php
// Este archivo debe guardarse como includes/admin-sidebar.php
?>

<aside class="w-64 bg-gray-800 text-white h-screen sticky top-0">
    <div class="p-4">
        <h2 class="text-lg font-semibold mb-4">Administración</h2>
        
        <nav>
            <ul class="space-y-2">
                <li>
                    <a href="dashboard.php" class="flex items-center py-2 px-4 rounded hover:bg-gray-700 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-gray-700' : ''; ?>">
                        <i class="fas fa-tachometer-alt w-6"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li>
                    <a href="usuarios.php" class="flex items-center py-2 px-4 rounded hover:bg-gray-700 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'bg-gray-700' : ''; ?>">
                        <i class="fas fa-users w-6"></i>
                        <span>Usuarios</span>
                    </a>
                </li>
                
                <li>
                    <a href="reportes.php" class="flex items-center py-2 px-4 rounded hover:bg-gray-700 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'bg-gray-700' : ''; ?>">
                        <i class="fas fa-exclamation-triangle w-6"></i>
                        <span>Reportes</span>
                    </a>
                </li>
                
                <li>
                    <a href="publicaciones.php" class="flex items-center py-2 px-4 rounded hover:bg-gray-700 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'publicaciones.php' ? 'bg-gray-700' : ''; ?>">
                        <i class="fas fa-newspaper w-6"></i>
                        <span>Publicaciones</span>
                    </a>
                </li>
                
                <li>
                    <a href="categorias.php" class="flex items-center py-2 px-4 rounded hover:bg-gray-700 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'categorias.php' ? 'bg-gray-700' : ''; ?>">
                        <i class="fas fa-tags w-6"></i>
                        <span>Categorías</span>
                    </a>
                </li>
                
                <li class="mt-8">
                    <a href="?logout=1" class="flex items-center py-2 px-4 rounded hover:bg-red-600 bg-red-500 transition-colors">
                        <i class="fas fa-sign-out-alt w-6"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>